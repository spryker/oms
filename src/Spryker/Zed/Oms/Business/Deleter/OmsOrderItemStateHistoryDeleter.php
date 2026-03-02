<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\Deleter;

use Generated\Shared\Transfer\OmsOrderItemStateHistoryCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\OmsOrderItemStateHistoryCollectionResponseTransfer;
use Spryker\Zed\Oms\Persistence\OmsEntityManagerInterface;

class OmsOrderItemStateHistoryDeleter implements OmsOrderItemStateHistoryDeleterInterface
{
    public function __construct(protected OmsEntityManagerInterface $omsEntityManager)
    {
    }

    public function deleteOmsOrderItemStateHistoryCollection(
        OmsOrderItemStateHistoryCollectionDeleteCriteriaTransfer $omsOrderItemStateHistoryCollectionDeleteCriteriaTransfer
    ): OmsOrderItemStateHistoryCollectionResponseTransfer {
        if ($omsOrderItemStateHistoryCollectionDeleteCriteriaTransfer->getSalesOrderItemIds()) {
            $this->omsEntityManager->deleteOmsOrderItemStateHistoryBySalesOrderItemIds(
                $omsOrderItemStateHistoryCollectionDeleteCriteriaTransfer->getSalesOrderItemIds(),
            );
        }

        return new OmsOrderItemStateHistoryCollectionResponseTransfer();
    }
}
