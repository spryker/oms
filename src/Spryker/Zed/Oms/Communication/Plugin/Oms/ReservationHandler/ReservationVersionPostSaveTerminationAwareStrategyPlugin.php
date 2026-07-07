<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Communication\Plugin\Oms\ReservationHandler;

use Generated\Shared\Transfer\ReservationRequestTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\OmsExtension\Dependency\Plugin\PrioritizedReservationPostSaveTerminationAwareStrategyPluginInterface;

/**
 * @method \Spryker\Zed\Oms\OmsConfig getConfig()
 * @method \Spryker\Zed\Oms\Persistence\OmsQueryContainerInterface getQueryContainer()
 * @method \Spryker\Zed\Oms\Communication\OmsCommunicationFactory getFactory()
 * @method \Spryker\Zed\Oms\Business\OmsFacadeInterface getFacade()
 */
class ReservationVersionPostSaveTerminationAwareStrategyPlugin extends AbstractPlugin implements PrioritizedReservationPostSaveTerminationAwareStrategyPluginInterface
{
    /**
     * {@inheritDoc}
     *
     * @api
     */
    public function getPriority(): int
    {
        return 100;
    }

    /**
     * {@inheritDoc}
     * - Execution should not be terminated for concrete products.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ReservationRequestTransfer $reservationRequestTransfer
     *
     * @return bool
     */
    public function isTerminated(ReservationRequestTransfer $reservationRequestTransfer): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     * - Checks if request is applicable for the concrete products.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ReservationRequestTransfer $reservationRequestTransfer
     *
     * @return bool
     */
    public function isApplicable(ReservationRequestTransfer $reservationRequestTransfer): bool
    {
        return $reservationRequestTransfer->getSku() !== null;
    }

    /**
     * {@inheritDoc}
     * - Saves reservation version.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\ReservationRequestTransfer $reservationRequestTransfer
     *
     * @return void
     */
    public function handle(ReservationRequestTransfer $reservationRequestTransfer): void
    {
        $this->getFacade()->saveReservationVersion($reservationRequestTransfer->getSku(), $reservationRequestTransfer->getStore());
    }
}
