<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\OrderStateMachine;

use Generated\Shared\Transfer\OrderTransfer;

interface ManualEventReaderInterface
{
    /**
     * @deprecated use {@link getGroupedDistinctManualEventsBySalesOrderTransfer } instead
     *
     * @param int $idSalesOrder
     *
     * @return array<string>
     */
    public function getGroupedDistinctManualEventsByIdSalesOrder(int $idSalesOrder): array;

    /**
     * @param \Generated\Shared\Transfer\OrderTransfer $orderTransfer
     *
     * @return array<string>
     */
    public function getGroupedDistinctManualEventsBySalesOrderTransfer(OrderTransfer $orderTransfer): array;
}
