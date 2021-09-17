<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\Expander;

use Generated\Shared\Transfer\OrderTransfer;

interface OrderExpanderInterface
{
    /**
     * @param \Generated\Shared\Transfer\OrderTransfer $orderTransfer
     *
     * @return \Generated\Shared\Transfer\OrderTransfer
     */
    public function expandOrderWithOmsStates(OrderTransfer $orderTransfer): OrderTransfer;

    /**
     * @param array<\Generated\Shared\Transfer\OrderTransfer> $orderTransfers
     *
     * @return array<\Generated\Shared\Transfer\OrderTransfer>
     */
    public function setOrderIsCancellableByItemState(array $orderTransfers): array;
}
