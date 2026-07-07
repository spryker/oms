<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\Reader;

use Generated\Shared\Transfer\OmsProcessTransfer;
use Generated\Shared\Transfer\OmsStateCollectionTransfer;
use Generated\Shared\Transfer\OmsStateTransfer;
use Generated\Shared\Transfer\ReservationRequestTransfer;
use Generated\Shared\Transfer\ReservationResponseTransfer;
use Generated\Shared\Transfer\SalesOrderItemStateAggregationTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\DecimalObject\Decimal;
use Spryker\Zed\Oms\Business\Util\ActiveProcessFetcherInterface;
use Spryker\Zed\Oms\Dependency\Facade\OmsToStoreFacadeInterface;
use Spryker\Zed\Oms\Persistence\OmsRepositoryInterface;

class ReservationReader implements ReservationReaderInterface
{
    /**
     * @param \Spryker\Zed\Oms\Persistence\OmsRepositoryInterface $omsRepository
     * @param \Spryker\Zed\Oms\Dependency\Facade\OmsToStoreFacadeInterface $storeFacade
     * @param \Spryker\Zed\Oms\Business\Util\ActiveProcessFetcherInterface $activeProcessFetcher
     * @param array<\Spryker\Zed\OmsExtension\Dependency\Plugin\OmsReservationReaderStrategyPluginInterface> $omsReservationReaderStrategyPlugins
     * @param array<\Spryker\Zed\OmsExtension\Dependency\Plugin\ReservationAggregationStrategyPluginInterface> $reservationAggregationPlugins
     * @param array<\Spryker\Zed\OmsExtension\Dependency\Plugin\OmsReservationAggregationPluginInterface> $omsReservationAggregationPlugins
     * @param array<\Spryker\Zed\OmsExtension\Dependency\Plugin\OmsReservationAggregationQueryCriteriaExpanderPluginInterface> $omsReservationAggregationQueryCriteriaExpanderPlugins
     */
    public function __construct(
        protected OmsRepositoryInterface $omsRepository,
        protected OmsToStoreFacadeInterface $storeFacade,
        protected ActiveProcessFetcherInterface $activeProcessFetcher,
        protected array $omsReservationReaderStrategyPlugins,
        protected array $reservationAggregationPlugins,
        /**
         * @deprecated Use {@link $omsReservationAggregationQueryCriteriaExpanderPlugins} instead.
         */
        protected array $omsReservationAggregationPlugins,
        protected array $omsReservationAggregationQueryCriteriaExpanderPlugins = []
    ) {
    }

    public function getOmsReservedProductQuantityForSku(string $sku, StoreTransfer $storeTransfer): Decimal
    {
        $idStore = $this->getIdStore($storeTransfer);
        $reservationQuantity = $this->omsRepository->findProductReservationQuantity($sku, $idStore);
        $reservationQuantity = $reservationQuantity->add(
            $this->getReservationsFromOtherStores($sku, $storeTransfer),
        );

        return $reservationQuantity;
    }

    /**
     * @param array<string> $skus
     * @param \Generated\Shared\Transfer\StoreTransfer $storeTransfer
     *
     * @return \Spryker\DecimalObject\Decimal
     */
    public function getOmsReservedProductQuantityForSkus(array $skus, StoreTransfer $storeTransfer): Decimal
    {
        $idStore = $this->getIdStore($storeTransfer);

        return $this->omsRepository->getSumOmsReservedProductQuantityByConcreteProductSkusForStore($skus, $idStore);
    }

    public function getReservationsFromOtherStores(string $sku, StoreTransfer $currentStoreTransfer): Decimal
    {
        $reservationQuantity = new Decimal(0);
        $reservationResponseTransfers = $this->omsRepository->findProductReservationStores($sku);

        foreach ($reservationResponseTransfers as $reservationResponseTransfer) {
            if ($reservationResponseTransfer->getStoreName() === $currentStoreTransfer->getName()) {
                continue;
            }

            $reservationQuantity = $reservationQuantity->add(
                $reservationResponseTransfer->getReservationQuantity(),
            );
        }

        return $reservationQuantity;
    }

    public function getOmsReservedStateCollection(): OmsStateCollectionTransfer
    {
        $reservedStatesTransfer = new OmsStateCollectionTransfer();
        $stateProcessMap = [];
        foreach ($this->activeProcessFetcher->getReservedStatesFromAllActiveProcesses() as $reservedState) {
            $stateProcessMap[$reservedState->getName()][] = $reservedState->getProcess()->getName();
        }

        foreach ($stateProcessMap as $reservedStateName => $stateProcesses) {
            $stateTransfer = (new OmsStateTransfer())->setName($reservedStateName);
            foreach ($stateProcesses as $processName) {
                $stateTransfer->addProcess($processName, (new OmsProcessTransfer())->setName($processName));
            }

            $reservedStatesTransfer->addState($reservedStateName, $stateTransfer);
        }

        return $reservedStatesTransfer;
    }

    public function getOmsReservedProductQuantity(ReservationRequestTransfer $reservationRequestTransfer): ReservationResponseTransfer
    {
        foreach ($this->omsReservationReaderStrategyPlugins as $omsReservationReaderStrategyPlugin) {
            if ($omsReservationReaderStrategyPlugin->isApplicable($reservationRequestTransfer)) {
                return $omsReservationReaderStrategyPlugin->getReservationQuantity($reservationRequestTransfer);
            }
        }

        $reservationQuantity = $this->getOmsReservedProductQuantityForSku(
            $reservationRequestTransfer->requireSku()->getSku(),
            $reservationRequestTransfer->requireStore()->getStore(),
        );

        return (new ReservationResponseTransfer())->setReservationQuantity($reservationQuantity);
    }

    public function sumReservedProductQuantities(ReservationRequestTransfer $reservationRequestTransfer): Decimal
    {
        $reservedStates = $this->getOmsReservedStateCollection();
        $reservationRequestTransfer->setReservedStates($reservedStates);

        $salesOrderItemStateAggregationTransfers = $this->aggregateReservations($reservationRequestTransfer);

        return $this->calculateReservationQuantity(
            $reservedStates,
            $salesOrderItemStateAggregationTransfers,
        );
    }

    /**
     * @deprecated Use {@link sumReservedProductQuantities()} instead.
     *
     * @param string $sku
     * @param \Generated\Shared\Transfer\StoreTransfer|null $storeTransfer
     *
     * @return \Spryker\DecimalObject\Decimal
     */
    public function sumReservedProductQuantitiesForSku(string $sku, ?StoreTransfer $storeTransfer = null): Decimal
    {
        $reservedStates = $this->getOmsReservedStateCollection();
        $salesAggregationTransfers = $this->aggregateSalesOrderItemReservations($reservedStates, $sku, $storeTransfer);

        return $this->calculateReservationQuantity(
            $reservedStates,
            $salesAggregationTransfers,
        );
    }

    /**
     * @param \Generated\Shared\Transfer\StoreTransfer $storeTransfer
     *
     * @return int
     */
    protected function getIdStore(StoreTransfer $storeTransfer)
    {
        if ($storeTransfer->getIdStore()) {
            return $storeTransfer->getIdStore();
        }

        $storeTransfer->requireName();

        return $this->storeFacade
            ->getStoreByName($storeTransfer->getName())
            ->getIdStore();
    }

    /**
     * @param \Generated\Shared\Transfer\ReservationRequestTransfer $reservationRequestTransfer
     *
     * @return array<\Generated\Shared\Transfer\SalesOrderItemStateAggregationTransfer>
     */
    protected function aggregateReservations(
        ReservationRequestTransfer $reservationRequestTransfer
    ): array {
        foreach ($this->omsReservationAggregationPlugins as $omsReservationAggregationPlugin) {
            return $omsReservationAggregationPlugin->aggregateReservations($reservationRequestTransfer);
        }

        if ($this->omsReservationAggregationQueryCriteriaExpanderPlugins) {
            return $this->omsRepository->getReservationAggregations($reservationRequestTransfer);
        }

        return $this->aggregateSalesOrderItemReservations(
            $reservationRequestTransfer->getReservedStates(),
            $reservationRequestTransfer->getSku(),
            $reservationRequestTransfer->getStore(),
        );
    }

    /**
     * @param \Generated\Shared\Transfer\OmsStateCollectionTransfer $reservedStates
     * @param array<\Generated\Shared\Transfer\SalesOrderItemStateAggregationTransfer> $salesAggregationTransfers
     *
     * @return \Spryker\DecimalObject\Decimal
     */
    protected function calculateReservationQuantity(OmsStateCollectionTransfer $reservedStates, array $salesAggregationTransfers): Decimal
    {
        $sumQuantity = new Decimal(0);
        foreach ($salesAggregationTransfers as $salesAggregationTransfer) {
            $this->assertAggregationTransfer($salesAggregationTransfer);
            if (!$this->assertStateAndProcessExists($reservedStates, $salesAggregationTransfer->getStateName(), $salesAggregationTransfer->getProcessName())) {
                continue;
            }

            $salesAggregationTransfer->requireSumAmount();
            $sumQuantity = $sumQuantity->add($salesAggregationTransfer->getSumAmount());
        }

        return $sumQuantity;
    }

    /**
     * @param \Generated\Shared\Transfer\OmsStateCollectionTransfer $reservedStates
     * @param string $sku
     * @param \Generated\Shared\Transfer\StoreTransfer|null $storeTransfer
     *
     * @return array<\Generated\Shared\Transfer\SalesOrderItemStateAggregationTransfer>
     */
    protected function aggregateSalesOrderItemReservations(
        OmsStateCollectionTransfer $reservedStates,
        string $sku,
        ?StoreTransfer $storeTransfer = null
    ): array {
        foreach ($this->reservationAggregationPlugins as $reservationAggregationPlugin) {
            $salesAggregationTransfers = $reservationAggregationPlugin->aggregateReservations(
                $sku,
                $reservedStates,
                $storeTransfer,
            );

            if ($salesAggregationTransfers !== []) {
                return $salesAggregationTransfers;
            }
        }

        return $this->omsRepository->getSalesOrderAggregationBySkuAndStatesNames(
            array_keys($reservedStates->getStates()->getArrayCopy()),
            $sku,
            $storeTransfer,
        );
    }

    protected function assertAggregationTransfer(SalesOrderItemStateAggregationTransfer $salesAggregationTransfer): void
    {
        $salesAggregationTransfer
            ->requireSku()
            ->requireProcessName()
            ->requireStateName();
    }

    protected function assertStateAndProcessExists(OmsStateCollectionTransfer $statesCollection, string $stateName, string $processName): bool
    {
        $omsStateTransfer = $statesCollection->getStates()[$stateName] ?? null;

        if (!$omsStateTransfer) {
            return false;
        }

        if ($omsStateTransfer->getProcesses()->offsetExists($processName)) {
            return true;
        }

        $reservedStateNames = $this->activeProcessFetcher
            ->getReservedStateNamesForActiveProcessByProcessName($processName);

        return in_array($stateName, $reservedStateNames, true);
    }
}
