<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\OrderStateMachine;

use Generated\Shared\Transfer\ItemTransfer;
use Orm\Zed\Sales\Persistence\SpySalesOrder;
use Orm\Zed\Sales\Persistence\SpySalesOrderItem;
use Spryker\Zed\Oms\Business\Process\StateInterface;

interface FinderInterface
{
    /**
     * @param int $idOrderItem
     *
     * @return array<string>
     */
    public function getManualEvents($idOrderItem);

    /**
     * @param int $idOrder
     * @param string $flag
     *
     * @return bool
     */
    public function isOrderFlagged($idOrder, $flag);

    /**
     * @param int $idOrder
     * @param string $flag
     *
     * @return bool
     */
    public function isOrderFlaggedAll($idOrder, $flag);

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $order
     *
     * @return array
     */
    public function getGroupedManuallyExecutableEvents(SpySalesOrder $order);

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $order
     * @param string $flag
     *
     * @return array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem>
     */
    public function getItemsWithFlag(SpySalesOrder $order, $flag);

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $order
     * @param string $flag
     *
     * @return array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem>
     */
    public function getItemsWithoutFlag(SpySalesOrder $order, $flag);

    /**
     * @return array<\Spryker\Zed\Oms\Business\Process\ProcessInterface>
     */
    public function getProcesses();

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem $orderItem
     *
     * @throws \Spryker\Zed\Oms\Business\Exception\StateNotFoundException
     *
     * @return string
     */
    public function getStateDisplayName(SpySalesOrderItem $orderItem);

    /**
     * @param int $idSalesOrder
     *
     * @return array<array<string>>
     */
    public function getManualEventsByIdSalesOrder($idSalesOrder);

    /**
     * @param int $idSalesOrder
     *
     * @return array<string>
     */
    public function getDistinctManualEventsByIdSalesOrder($idSalesOrder);

    /**
     * @param int $idOrder
     *
     * @return bool
     */
    public function isOrderFlaggedExcludeFromCustomer($idOrder);

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @return \Spryker\Zed\Oms\Business\Process\StateInterface|null
     */
    public function findStateByName(ItemTransfer $itemTransfer): ?StateInterface;
}
