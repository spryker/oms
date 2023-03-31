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

class Reservation implements ReservationInterface
{
    /**
     * @var \Spryker\Zed\Oms\Business\Reader\ReservationReaderInterface
     */
    protected $reservationReader;

    /**
     * @var array<\Spryker\Zed\Oms\Dependency\Plugin\ReservationHandlerPluginInterface>
     */
    protected $reservationHandlerPlugins;

    /**
     * @var \Spryker\Zed\Oms\Dependency\Facade\OmsToStoreFacadeInterface
     */
    protected $storeFacade;

    /**
     * @var \Spryker\Zed\Oms\Persistence\OmsRepositoryInterface
     */
    protected $omsRepository;

    /**
     * @var \Spryker\Zed\Oms\Persistence\OmsEntityManagerInterface
     */
    protected $omsEntityManager;

    /**
     * @var array<\Spryker\Zed\OmsExtension\Dependency\Plugin\OmsReservationWriterStrategyPluginInterface>
     */
    protected $omsReservationWriterStrategyPlugins;

    /**
     * @var array<\Spryker\Zed\OmsExtension\Dependency\Plugin\ReservationPostSaveTerminationAwareStrategyPluginInterface>
     */
    protected $reservationHandlerTerminationAwareStrategyPlugins;

    /**
     * @param \Spryker\Zed\Oms\Business\Reader\ReservationReaderInterface $reservationReader
     * @param array<\Spryker\Zed\Oms\Dependency\Plugin\ReservationHandlerPluginInterface> $reservationHandlerPlugins
     * @param \Spryker\Zed\Oms\Dependency\Facade\OmsToStoreFacadeInterface $storeFacade
     * @param \Spryker\Zed\Oms\Persistence\OmsRepositoryInterface $omsRepository
     * @param \Spryker\Zed\Oms\Persistence\OmsEntityManagerInterface $omsEntityManager
     * @param array<\Spryker\Zed\OmsExtension\Dependency\Plugin\OmsReservationWriterStrategyPluginInterface> $omsReservationWriterStrategyPlugins
     * @param array<\Spryker\Zed\OmsExtension\Dependency\Plugin\ReservationPostSaveTerminationAwareStrategyPluginInterface> $reservationHandlerTerminationAwareStrategyPlugins
     */
    public function __construct(
        ReservationReaderInterface $reservationReader,
        array $reservationHandlerPlugins,
        OmsToStoreFacadeInterface $storeFacade,
        OmsRepositoryInterface $omsRepository,
        OmsEntityManagerInterface $omsEntityManager,
        array $omsReservationWriterStrategyPlugins,
        array $reservationHandlerTerminationAwareStrategyPlugins
    ) {
        $this->reservationReader = $reservationReader;
        $this->reservationHandlerPlugins = $reservationHandlerPlugins;
        $this->storeFacade = $storeFacade;
        $this->omsRepository = $omsRepository;
        $this->omsEntityManager = $omsEntityManager;
        $this->omsReservationWriterStrategyPlugins = $omsReservationWriterStrategyPlugins;
        $this->reservationHandlerTerminationAwareStrategyPlugins = $reservationHandlerTerminationAwareStrategyPlugins;
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

    /**
     * @param \Generated\Shared\Transfer\ReservationRequestTransfer $reservationRequestTransfer
     *
     * @return void
     */
    public function updateReservation(ReservationRequestTransfer $reservationRequestTransfer): void
    {
        foreach ($this->storeFacade->getAllStores() as $storeTransfer) {
            $reservationRequestTransfer->setStore($storeTransfer);

            $reservationQuantity = $this->reservationReader->sumReservedProductQuantities($reservationRequestTransfer);
            $reservationRequestTransfer->setReservationQuantity($reservationQuantity);

            $this->writeReservation($reservationRequestTransfer);
        }

        foreach ($this->reservationHandlerTerminationAwareStrategyPlugins as $reservationHandlerTerminationAwareStrategyPlugin) {
            if ($reservationHandlerTerminationAwareStrategyPlugin->isTerminated($reservationRequestTransfer)) {
                return;
            }

            if (!$reservationHandlerTerminationAwareStrategyPlugin->isApplicable($reservationRequestTransfer)) {
                continue;
            }

            $reservationHandlerTerminationAwareStrategyPlugin->handle($reservationRequestTransfer);
        }

        $this->handleReservationPlugins($reservationRequestTransfer->getSku());
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

        $omsProductReservationTransfer = $this->omsRepository->findProductReservation($reservationRequestTransfer);

        if (!$omsProductReservationTransfer) {
            $this->omsEntityManager->createReservation($reservationRequestTransfer);

            return;
        }

        $this->omsEntityManager->updateReservation($reservationRequestTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\ReservationRequestTransfer $reservationRequestTransfer
     *
     * @return void
     */
    protected function writeReservation(ReservationRequestTransfer $reservationRequestTransfer): void
    {
        foreach ($this->omsReservationWriterStrategyPlugins as $omsReservationWriterStrategyPlugin) {
            if ($omsReservationWriterStrategyPlugin->isApplicable($reservationRequestTransfer)) {
                $omsReservationWriterStrategyPlugin->writeReservation($reservationRequestTransfer);

                return;
            }
        }

        $omsProductReservationTransfer = $this->omsRepository->findProductReservation($reservationRequestTransfer);

        if (!$omsProductReservationTransfer) {
            $this->omsEntityManager->createReservation($reservationRequestTransfer);

            return;
        }

        $this->omsEntityManager->updateReservation($reservationRequestTransfer);
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
}
