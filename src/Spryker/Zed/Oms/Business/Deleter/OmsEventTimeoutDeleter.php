<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\Deleter;

use Generated\Shared\Transfer\OmsEventTimeoutCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\OmsEventTimeoutCollectionResponseTransfer;
use Spryker\Zed\Oms\Persistence\OmsEntityManagerInterface;

class OmsEventTimeoutDeleter implements OmsEventTimeoutDeleterInterface
{
    public function __construct(protected OmsEntityManagerInterface $omsEntityManager)
    {
    }

    public function deleteOmsEventTimeoutCollection(
        OmsEventTimeoutCollectionDeleteCriteriaTransfer $omsEventTimeoutCollectionDeleteCriteriaTransfer
    ): OmsEventTimeoutCollectionResponseTransfer {
        if ($omsEventTimeoutCollectionDeleteCriteriaTransfer->getSalesOrderItemIds()) {
            $this->omsEntityManager->deleteOmsEventTimeoutsBySalesOrderItemIds(
                $omsEventTimeoutCollectionDeleteCriteriaTransfer->getSalesOrderItemIds(),
            );
        }

        return new OmsEventTimeoutCollectionResponseTransfer();
    }
}
