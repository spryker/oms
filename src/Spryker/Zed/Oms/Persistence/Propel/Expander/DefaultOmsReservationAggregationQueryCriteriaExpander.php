<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Persistence\Propel\Expander;

use Generated\Shared\Transfer\QueryCriteriaTransfer;
use Generated\Shared\Transfer\ReservationRequestTransfer;
use Generated\Shared\Transfer\SalesOrderItemStateAggregationTransfer;
use Generated\Shared\Transfer\WhereClauseTransfer;
use Orm\Zed\Sales\Persistence\Map\SpySalesOrderItemTableMap;

class DefaultOmsReservationAggregationQueryCriteriaExpander implements DefaultOmsReservationAggregationQueryCriteriaExpanderInterface
{
    public function expand(
        QueryCriteriaTransfer $queryCriteriaTransfer,
        ReservationRequestTransfer $reservationRequestTransfer
    ): QueryCriteriaTransfer {
        $withColumns = $queryCriteriaTransfer->getWithColumns();
        if (array_key_exists(SalesOrderItemStateAggregationTransfer::SUM_AMOUNT, $withColumns)) {
            return $queryCriteriaTransfer;
        }

        $withColumns[SalesOrderItemStateAggregationTransfer::SUM_AMOUNT] = sprintf(
            'SUM(%s)',
            SpySalesOrderItemTableMap::COL_QUANTITY,
        );
        $queryCriteriaTransfer->setWithColumns($withColumns);

        $whereClauseTransfer = (new WhereClauseTransfer())
            ->setClause(sprintf('%s = ?', SpySalesOrderItemTableMap::COL_SKU))
            ->setParameters([$reservationRequestTransfer->getSkuOrFail()]);
        $queryCriteriaTransfer->addWhereClause($whereClauseTransfer);

        return $queryCriteriaTransfer;
    }
}
