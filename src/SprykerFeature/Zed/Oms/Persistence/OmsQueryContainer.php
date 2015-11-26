<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace SprykerFeature\Zed\Oms\Persistence;

use DateTime;
use Orm\Zed\Oms\Persistence\SpyOmsOrderItemStateQuery;
use Orm\Zed\Oms\Persistence\SpyOmsOrderProcessQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use SprykerEngine\Zed\Kernel\Persistence\AbstractQueryContainer;
use SprykerFeature\Zed\Oms\Business\Process\StateInterface;
use SprykerFeature\Zed\Oms\OmsDependencyProvider;
use Orm\Zed\Oms\Persistence\Map\SpyOmsTransitionLogTableMap;
use Orm\Zed\Oms\Persistence\SpyOmsTransitionLogQuery;
use Orm\Zed\Sales\Persistence\SpySalesOrder;
use Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery;
use Orm\Zed\Sales\Persistence\SpySalesOrderQuery;
use SprykerFeature\Zed\Sales\Persistence\SalesQueryContainerInterface;

/**
 */
class OmsQueryContainer extends AbstractQueryContainer implements OmsQueryContainerInterface
{

    /**
     * @param array $states
     * @param string $processName
     *
     * @return SpySalesOrderItemQuery
     */
    public function querySalesOrderItemsByState(array $states, $processName)
    {
        return $this->getSalesQueryContainer()->querySalesOrderItem()
            ->joinProcess(null, $joinType = Criteria::INNER_JOIN)
            ->joinState(null, $joinType = Criteria::INNER_JOIN)
            ->where('Process.name = ?', $processName)
            ->where("State.name IN ('" . implode("', '", $states) . "')");
    }

    /**
     * @return SalesQueryContainerInterface
     */
    protected function getSalesQueryContainer()
    {
        return $this->getProvidedDependency(OmsDependencyProvider::QUERY_CONTAINER_SALES);
    }

    /**
     * @param int $idOrder
     *
     * @return SpySalesOrderItemQuery
     */
    public function querySalesOrderItemsByIdOrder($idOrder)
    {
        return $this->getSalesQueryContainer()->querySalesOrderItem()
            ->filterByFkSalesOrder($idOrder);
    }

    /**
     * @param SpySalesOrder $order
     *
     * @return SpyOmsTransitionLogQuery
     */
    public function queryLogForOrder(SpySalesOrder $order)
    {
        return SpyOmsTransitionLogQuery::create()
            ->filterByOrder($order)
            ->orderBy(SpyOmsTransitionLogTableMap::COL_ID_OMS_TRANSITION_LOG, Criteria::DESC);
    }

    /**
     * @param int $idOrder
     * @param bool $orderById
     *
     * @return SpyOmsTransitionLogQuery
     */
    public function queryLogByIdOrder($idOrder, $orderById = true)
    {
        $transitionLogQuery = new SpyOmsTransitionLogQuery();

        $transitionLogQuery
            ->filterByFkSalesOrder($idOrder);

        if ($orderById) {
            $transitionLogQuery->orderBy(SpyOmsTransitionLogTableMap::COL_ID_OMS_TRANSITION_LOG, Criteria::DESC);
        }

        return $transitionLogQuery;
    }

    /**
     * @param DateTime $now
     *
     * @return SpySalesOrderItemQuery
     */
    public function querySalesOrderItemsWithExpiredTimeouts(DateTime $now)
    {
        return $this->getSalesQueryContainer()->querySalesOrderItem()
            ->joinEventTimeout()
            ->where('EventTimeout.timeout < ?', $now)
            ->withColumn('EventTimeout.event', 'event');
    }

    /**
     * @param StateInterface[] $states
     * @param string $sku
     * @param bool $returnTest
     *
     * @return SpySalesOrderItemQuery
     */
    public function countSalesOrderItemsForSku(array $states, $sku, $returnTest = true)
    {
        $query = $this->getSalesQueryContainer()->querySalesOrderItem();
        $query->withColumn('COUNT(*)', 'Count')->select(['Count']);

        if ($returnTest === false) {
            $query->useOrderQuery()->filterByIsTest(false)->endUse();
        }

        $stateNames = [];
        foreach ($states as $state) {
            $stateNames[] = $state->getName();
        }

        $query->useStateQuery()->filterByName($stateNames)->endUse();
        $query->filterBySku($sku);

        return $query;
    }

    /**
     * @param StateInterface[] $states
     * @param string $sku
     * @param bool $returnTest
     *
     * @return SpySalesOrderItemQuery
     */
    public function querySalesOrderItemsForSku(array $states, $sku, $returnTest = true)
    {
        $query = $this->getSalesQueryContainer()->querySalesOrderItem();

        if ($returnTest === false) {
            $query->useOrderQuery()->filterByIsTest(false)->endUse();
        }

        $stateNames = [];
        foreach ($states as $state) {
            $stateNames[] = $state->getName();
        }

        $query->useStateQuery()->filterByName($stateNames)->endUse();
        $query->filterBySku($sku);

        return $query;
    }

    /**
     * @param array $orderItemIds
     *
     * @return SpySalesOrderItemQuery
     */
    public function querySalesOrderItems(array $orderItemIds)
    {
        return $this->getSalesQueryContainer()->querySalesOrderItem()
            ->filterByIdSalesOrderItem($orderItemIds);
    }

    /**
     * @param int $idOrder
     *
     * @return SpySalesOrderQuery
     */
    public function querySalesOrderById($idOrder)
    {
        return $this->getSalesQueryContainer()->querySalesOrder()
            ->filterByIdSalesOrder($idOrder);
    }

    /**
     * @param array|string[] $activeProcesses
     *
     * @return SpyOmsOrderProcessQuery
     */
    public function getActiveProcesses($activeProcesses)
    {
        $query = SpyOmsOrderProcessQuery::create();

        return $query->filterByName($activeProcesses);
    }

    /**
     * @param array $orderItemStates
     *
     * @return SpyOmsOrderItemStateQuery
     */
    public function getOrderItemStates(array $orderItemStates)
    {
        $query = SpyOmsOrderItemStateQuery::create();

        return $query->filterByIdOmsOrderItemState($orderItemStates);
    }

    /**
     * @param array $processIds
     * @param array $stateBlacklist
     *
     * @return SpySalesOrderItemQuery
     */
    public function queryMatrixOrderItems(array $processIds, array $stateBlacklist)
    {
        $query = SpySalesOrderItemQuery::create();
        $query->filterByFkOmsOrderProcess($processIds);
        if ($stateBlacklist) {
            $query->filterByFkOmsOrderItemState($stateBlacklist, Criteria::NOT_IN);
        }

        return $query;
    }

    /**
     * @param string[] $orderItemStates
     *
     * @return SpyOmsOrderItemStateQuery
     */
    public function querySalesOrderItemStatesByName($orderItemStates)
    {
        $query = SpyOmsOrderItemStateQuery::create();
        $query->filterByName($orderItemStates);

        return $query;
    }

}
