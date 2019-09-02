<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Persistence;

use Generated\Shared\Transfer\ProductSalesAggregationTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Orm\Zed\Oms\Persistence\Map\SpyOmsOrderItemStateTableMap;
use Orm\Zed\Oms\Persistence\Map\SpyOmsOrderProcessTableMap;
use Orm\Zed\Sales\Persistence\Map\SpySalesOrderItemTableMap;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;

/**
 * @method \Spryker\Zed\Oms\Persistence\OmsPersistenceFactory getFactory()
 *
 */
class OmsRepository extends AbstractRepository implements OmsRepositoryInterface
{
    protected const SUM_COLUMN = 'aggregationSum';
    protected const SKU_COLUMN = 'sku';
    protected const PROCESS_NAME_COLUMN = 'processName';
    protected const STATE_NAME_COLUMN = 'stateName';

    /**
     * @param int[] $processIds
     * @param int[] $stateBlackList
     *
     * @return array
     */
    public function getMatrixOrderItems(array $processIds, array $stateBlackList): array
    {
        $orderItemsMatrixResult = $this->getFactory()->getOmsQueryContainer()
            ->queryGroupedMatrixOrderItems($processIds, $stateBlackList)
            ->find();

        return $this->getFactory()
            ->createOrderItemMapper()
            ->mapOrderItemMatrix($orderItemsMatrixResult->getArrayCopy());
    }

    /**
     * @param string[] $stateNames
     * @param string $sku
     * @param \Generated\Shared\Transfer\StoreTransfer|null $storeTransfer
     *
     * @return \Generated\Shared\Transfer\ProductSalesAggregationTransfer[]
     */
    public function getSalesOrderAggregationBySkuAndStatesNames(array $stateNames, string $sku, ?StoreTransfer $storeTransfer): array
    {
        $salesOrderItemQuery = $this->getFactory()
            ->getSalesQueryContainer()
            ->querySalesOrderItem()
            ->select([
                SpySalesOrderItemTableMap::COL_SKU,
            ])->filterBySku($sku)
            ->innerJoinProcess()
            ->useStateQuery()
                ->filterByName_In($stateNames)
            ->endUse()
            ->groupByFkOmsOrderItemState()
            ->groupByFkOmsOrderProcess()
            ->withColumn(SpySalesOrderItemTableMap::COL_SKU, static::SKU_COLUMN)
            ->withColumn(SpyOmsOrderProcessTableMap::COL_NAME, static::PROCESS_NAME_COLUMN)
            ->withColumn(SpyOmsOrderItemStateTableMap::COL_NAME, static::STATE_NAME_COLUMN)
            ->withColumn('SUM(' . SpySalesOrderItemTableMap::COL_QUANTITY . ')', static::SUM_COLUMN);

        if ($storeTransfer !== null) {
            $salesOrderItemQuery
                ->useOrderQuery()
                    ->filterByStore($storeTransfer->getName())
                ->endUse();
        }

        $salesAggregationTransfers = [];
        foreach ($salesOrderItemQuery->find() as $salesOrderItemAggregation) {
            $salesAggregationTransfers[] = (new ProductSalesAggregationTransfer())->fromArray($salesOrderItemAggregation, true);
        }

        return $salesAggregationTransfers;
    }
}
