<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\Checker;

use Generated\Shared\Transfer\OrderTransfer;

interface OrderCheckerInterface
{
    public function areOrderItemsSatisfiedByFlag(OrderTransfer $orderTransfer, string $flag): bool;
}
