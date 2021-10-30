<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\OrderStateMachine;

interface OrderItemManualEventReaderInterface
{
    /**
     * @param iterable<\Generated\Shared\Transfer\ItemTransfer> $orderItemTransfers
     *
     * @return array<array<string>>
     */
    public function getManualEventsByIdSalesOrder(iterable $orderItemTransfers): array;
}
