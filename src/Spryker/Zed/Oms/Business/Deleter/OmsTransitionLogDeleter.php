<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\Deleter;

use Generated\Shared\Transfer\OmsTransitionLogCollectionDeleteCriteriaTransfer;
use Generated\Shared\Transfer\OmsTransitionLogCollectionResponseTransfer;
use Spryker\Zed\Oms\Persistence\OmsEntityManagerInterface;

class OmsTransitionLogDeleter implements OmsTransitionLogDeleterInterface
{
    public function __construct(protected OmsEntityManagerInterface $omsEntityManager)
    {
    }

    public function deleteOmsTransitionLogCollection(
        OmsTransitionLogCollectionDeleteCriteriaTransfer $omsTransitionLogCollectionDeleteCriteriaTransfer
    ): OmsTransitionLogCollectionResponseTransfer {
        if ($omsTransitionLogCollectionDeleteCriteriaTransfer->getSalesOrderItemIds()) {
            $this->omsEntityManager->deleteOmsTransitionLogsBySalesOrderItemIds(
                $omsTransitionLogCollectionDeleteCriteriaTransfer->getSalesOrderItemIds(),
            );
        }

        return new OmsTransitionLogCollectionResponseTransfer();
    }
}
