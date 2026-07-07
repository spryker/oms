<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Persistence\Propel\Mapper;

use Generated\Shared\Transfer\QueryCriteriaTransfer;
use Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery;
use Propel\Runtime\ActiveQuery\Criteria;

class OmsReservationAggregationQueryCriteriaMapper
{
    public function mapQueryCriteriaTransferToSalesOrderItemQuery(
        QueryCriteriaTransfer $queryCriteriaTransfer,
        SpySalesOrderItemQuery $salesOrderItemQuery
    ): SpySalesOrderItemQuery {
        $salesOrderItemQuery = $this->addJoins($queryCriteriaTransfer, $salesOrderItemQuery);
        $salesOrderItemQuery = $this->addWithColumns($queryCriteriaTransfer, $salesOrderItemQuery);
        $salesOrderItemQuery = $this->addGroupByColumns($queryCriteriaTransfer, $salesOrderItemQuery);
        $salesOrderItemQuery = $this->addWhereClauses($queryCriteriaTransfer, $salesOrderItemQuery);

        return $salesOrderItemQuery;
    }

    protected function addJoins(
        QueryCriteriaTransfer $queryCriteriaTransfer,
        SpySalesOrderItemQuery $salesOrderItemQuery
    ): SpySalesOrderItemQuery {
        foreach ($queryCriteriaTransfer->getJoins() as $queryJoinTransfer) {
            $joinType = $queryJoinTransfer->getJoinType() ?? Criteria::INNER_JOIN;
            $relation = $queryJoinTransfer->getRelation();

            if ($relation === null) {
                $salesOrderItemQuery->addJoin($queryJoinTransfer->getLeft(), $queryJoinTransfer->getRight(), $joinType);

                continue;
            }

            $salesOrderItemQuery->join($relation, $joinType);

            $condition = $queryJoinTransfer->getCondition();
            if ($condition === null) {
                continue;
            }

            $salesOrderItemQuery->addJoinCondition($relation, $condition);
        }

        return $salesOrderItemQuery;
    }

    protected function addWithColumns(
        QueryCriteriaTransfer $queryCriteriaTransfer,
        SpySalesOrderItemQuery $salesOrderItemQuery
    ): SpySalesOrderItemQuery {
        foreach ($queryCriteriaTransfer->getWithColumns() as $alias => $clause) {
            if (!is_array($clause)) {
                $salesOrderItemQuery->withColumn($clause, $alias);

                continue;
            }

            $nestedAlias = array_key_first($clause);
            if (!is_string($nestedAlias)) {
                continue;
            }

            $salesOrderItemQuery->withColumn($clause[$nestedAlias], $nestedAlias);
        }

        return $salesOrderItemQuery;
    }

    protected function addGroupByColumns(
        QueryCriteriaTransfer $queryCriteriaTransfer,
        SpySalesOrderItemQuery $salesOrderItemQuery
    ): SpySalesOrderItemQuery {
        foreach ($queryCriteriaTransfer->getGroupByColumns() as $column) {
            $salesOrderItemQuery->addGroupByColumn($column);
        }

        return $salesOrderItemQuery;
    }

    protected function addWhereClauses(
        QueryCriteriaTransfer $queryCriteriaTransfer,
        SpySalesOrderItemQuery $salesOrderItemQuery
    ): SpySalesOrderItemQuery {
        foreach ($queryCriteriaTransfer->getWhereClauses() as $whereClauseTransfer) {
            $clause = $whereClauseTransfer->getClause();
            if ($clause === null || $clause === '') {
                continue;
            }

            $parameters = $whereClauseTransfer->getParameters();

            $salesOrderItemQuery->where(
                /** @phpstan-ignore-next-line argument.type */
                '(' . $clause . ')',
                count($parameters) > 1 ? $parameters : ($parameters[0] ?? null),
            );
        }

        return $salesOrderItemQuery;
    }
}
