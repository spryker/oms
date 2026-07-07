<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\Util;

use Generated\Shared\Transfer\ReservationRequestTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\DecimalObject\Decimal;
use Spryker\Zed\Oms\Business\Reader\ReservationReaderInterface;
use Spryker\Zed\Oms\Dependency\Facade\OmsToStoreFacadeInterface;
use Spryker\Zed\Oms\Persistence\OmsEntityManagerInterface;
use Spryker\Zed\Oms\Persistence\OmsRepositoryInterface;
use Spryker\Zed\OmsExtension\Dependency\Plugin\PrioritizedReservationPostSaveTerminationAwareStrategyPluginInterface;
use Spryker\Zed\OmsExtension\Dependency\Plugin\ReservationPostSaveTerminationAwareStrategyPluginInterface;

class Reservation implements ReservationInterface
{
    /**
     * @var array<\Generated\Shared\Transfer\StoreTransfer>
     */
    protected static $allStoreTransfersCache = [];

    /**
     * @param \Spryker\Zed\Oms\Business\Reader\ReservationReaderInterface $reservationReader
     * @param array<\Spryker\Zed\Oms\Dependency\Plugin\ReservationHandlerPluginInterface> $reservationHandlerPlugins
     * @param \Spryker\Zed\Oms\Dependency\Facade\OmsToStoreFacadeInterface $storeFacade
     * @param \Spryker\Zed\Oms\Persistence\OmsRepositoryInterface $omsRepository
     * @param \Spryker\Zed\Oms\Persistence\OmsEntityManagerInterface $omsEntityManager
     * @param array<\Spryker\Zed\OmsExtension\Dependency\Plugin\OmsReservationWriterStrategyPluginInterface> $omsReservationWriterStrategyPlugins
     * @param array<\Spryker\Zed\OmsExtension\Dependency\Plugin\ReservationPostSaveTerminationAwareStrategyPluginInterface> $reservationHandlerTerminationAwareStrategyPlugins
     * @param array<\Spryker\Zed\OmsExtension\Dependency\Plugin\ReservationRequestExpanderPluginInterface> $reservationRequestExpanderPlugins
     * @param array<\Spryker\Zed\OmsExtension\Dependency\Plugin\ReservationPostSaveTerminationAwareStrategyPluginInterface> $storeAwareReservationPostSaveTerminationAwareStrategyPlugins
     */
    public function __construct(
        protected ReservationReaderInterface $reservationReader,
        protected array $reservationHandlerPlugins,
        protected OmsToStoreFacadeInterface $storeFacade,
        protected OmsRepositoryInterface $omsRepository,
        protected OmsEntityManagerInterface $omsEntityManager,
        protected array $omsReservationWriterStrategyPlugins,
        protected array $reservationHandlerTerminationAwareStrategyPlugins,
        protected array $reservationRequestExpanderPlugins = [],
        protected array $storeAwareReservationPostSaveTerminationAwareStrategyPlugins = []
    ) {
    }

    /**
     * @deprecated Use {@link updateReservation()} instead.
     *
     * @param string $sku
     *
     * @return void
     */
    public function updateReservationQuantity($sku)
    {
        $reservationAmount = $this->reservationReader->sumReservedProductQuantitiesForSku($sku);
        foreach ($this->storeFacade->getAllStores() as $storeTransfer) {
            $this->saveReservation($sku, $storeTransfer, $reservationAmount);
        }

        $this->handleReservationPlugins($sku);
    }

    public function updateReservation(ReservationRequestTransfer $originalReservationRequestTransfer): void
    {
        $storeAwareReservationPostSaveTerminationAwareStrategyPlugins = $this->getSortedStoreAwareReservationPostSaveTerminationAwareStrategyPlugins();

        $storeTransfer = null;
        foreach ($this->getAllStoreTransfersCache() as $storeTransfer) {
            $reservationRequestTransfer = (new ReservationRequestTransfer())->fromArray($originalReservationRequestTransfer->toArray(), true);
            $reservationRequestTransfer->setStore($storeTransfer);

            $reservationRequestTransfer = $this->expandReservationRequest($reservationRequestTransfer);

            $reservationQuantity = $this->reservationReader->sumReservedProductQuantities($reservationRequestTransfer);
            $reservationRequestTransfer->setReservationQuantity($reservationQuantity);

            $this->writeReservation($reservationRequestTransfer);

            foreach ($storeAwareReservationPostSaveTerminationAwareStrategyPlugins as $storeAwareReservationPostSaveTerminationAwareStrategyPlugin) {
                if ($storeAwareReservationPostSaveTerminationAwareStrategyPlugin->isTerminated($reservationRequestTransfer)) {
                    continue 2;
                }

                if (!$storeAwareReservationPostSaveTerminationAwareStrategyPlugin->isApplicable($reservationRequestTransfer)) {
                    continue;
                }

                $storeAwareReservationPostSaveTerminationAwareStrategyPlugin->handle($reservationRequestTransfer);
            }

            // for BC compatibility
            $originalReservationRequestTransfer->setReservationQuantity($reservationRequestTransfer->getReservationQuantity());
            $originalReservationRequestTransfer->setStore($storeTransfer);
        }

        foreach ($this->reservationHandlerTerminationAwareStrategyPlugins as $reservationHandlerTerminationAwareStrategyPlugin) {
            if ($reservationHandlerTerminationAwareStrategyPlugin->isTerminated($originalReservationRequestTransfer)) {
                return;
            }

            if (!$reservationHandlerTerminationAwareStrategyPlugin->isApplicable($originalReservationRequestTransfer)) {
                continue;
            }

            $reservationHandlerTerminationAwareStrategyPlugin->handle($originalReservationRequestTransfer);
        }

        $this->handleReservationPlugins($originalReservationRequestTransfer->getSku());
    }

    /**
     * @deprecated Will be removed without replacement.
     *
     * @param string $sku
     * @param \Generated\Shared\Transfer\StoreTransfer $storeTransfer
     * @param \Spryker\DecimalObject\Decimal $reservationQuantity
     *
     * @return void
     */
    public function saveReservation(string $sku, StoreTransfer $storeTransfer, Decimal $reservationQuantity): void
    {
        $storeTransfer->requireIdStore();
        $reservationRequestTransfer = (new ReservationRequestTransfer())
            ->setSku($sku)
            ->setReservationQuantity($reservationQuantity)
            ->setStore($storeTransfer);

        $this->omsEntityManager->saveReservation($reservationRequestTransfer);
    }

    protected function writeReservation(ReservationRequestTransfer $reservationRequestTransfer): void
    {
        foreach ($this->omsReservationWriterStrategyPlugins as $omsReservationWriterStrategyPlugin) {
            if ($omsReservationWriterStrategyPlugin->isApplicable($reservationRequestTransfer)) {
                $omsReservationWriterStrategyPlugin->writeReservation($reservationRequestTransfer);

                return;
            }
        }

        $this->omsEntityManager->saveReservation($reservationRequestTransfer);
    }

    /**
     * @param string $sku
     *
     * @return void
     */
    protected function handleReservationPlugins($sku)
    {
        foreach ($this->reservationHandlerPlugins as $reservationHandlerPluginInterface) {
            $reservationHandlerPluginInterface->handle($sku);
        }
    }

    protected function expandReservationRequest(ReservationRequestTransfer $reservationRequestTransfer): ReservationRequestTransfer
    {
        $reservationRequestExpanderPlugins = $this->reservationRequestExpanderPlugins;
        usort($reservationRequestExpanderPlugins, fn ($a, $b) => $b->getPriority() <=> $a->getPriority());

        foreach ($reservationRequestExpanderPlugins as $reservationRequestExpanderPlugin) {
            if ($reservationRequestExpanderPlugin->isApplicable($reservationRequestTransfer)) {
                return $reservationRequestExpanderPlugin->expand($reservationRequestTransfer);
            }
        }

        return $reservationRequestTransfer;
    }

    /**
     * @return array<\Spryker\Zed\OmsExtension\Dependency\Plugin\ReservationPostSaveTerminationAwareStrategyPluginInterface>
     */
    protected function getSortedStoreAwareReservationPostSaveTerminationAwareStrategyPlugins(): array
    {
        $storeAwareReservationPostSaveTerminationAwareStrategyPlugins = $this->storeAwareReservationPostSaveTerminationAwareStrategyPlugins;
        usort(
            $storeAwareReservationPostSaveTerminationAwareStrategyPlugins,
            fn ($a, $b) => $this->resolveStoreAwarePluginPriority($b) <=> $this->resolveStoreAwarePluginPriority($a),
        );

        return $storeAwareReservationPostSaveTerminationAwareStrategyPlugins;
    }

    protected function resolveStoreAwarePluginPriority(
        ReservationPostSaveTerminationAwareStrategyPluginInterface $reservationPostSaveTerminationAwareStrategyPlugin
    ): int {
        return $reservationPostSaveTerminationAwareStrategyPlugin instanceof PrioritizedReservationPostSaveTerminationAwareStrategyPluginInterface
            ? $reservationPostSaveTerminationAwareStrategyPlugin->getPriority()
            : 0;
    }

    /**
     * @return array<\Generated\Shared\Transfer\StoreTransfer>
     */
    protected function getAllStoreTransfersCache(): array
    {
        if (static::$allStoreTransfersCache) {
            return static::$allStoreTransfersCache;
        }
        static::$allStoreTransfersCache = $this->storeFacade->getAllStores();

        return static::$allStoreTransfersCache;
    }
}
