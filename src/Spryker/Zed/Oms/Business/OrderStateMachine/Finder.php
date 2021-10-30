<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\OrderStateMachine;

use Exception;
use Generated\Shared\Transfer\ItemTransfer;
use Orm\Zed\Sales\Persistence\SpySalesOrder;
use Orm\Zed\Sales\Persistence\SpySalesOrderItem;
use Spryker\Shared\Oms\OmsConfig;
use Spryker\Zed\Oms\Business\Exception\StateNotFoundException;
use Spryker\Zed\Oms\Business\Process\StateInterface;
use Spryker\Zed\Oms\Persistence\OmsQueryContainerInterface;

class Finder implements FinderInterface
{
    /**
     * @var \Spryker\Zed\Oms\Persistence\OmsQueryContainerInterface
     */
    protected $queryContainer;

    /**
     * @var \Spryker\Zed\Oms\Business\OrderStateMachine\BuilderInterface
     */
    protected $builder;

    /**
     * @var array
     */
    protected $activeProcesses;

    /**
     * @param \Spryker\Zed\Oms\Persistence\OmsQueryContainerInterface $queryContainer
     * @param \Spryker\Zed\Oms\Business\OrderStateMachine\BuilderInterface $builder
     * @param array $activeProcesses
     */
    public function __construct(
        OmsQueryContainerInterface $queryContainer,
        BuilderInterface $builder,
        array $activeProcesses
    ) {
        $this->queryContainer = $queryContainer;
        $this->builder = $builder;
        $this->activeProcesses = $activeProcesses;
    }

    /**
     * @param int $idOrderItem
     *
     * @return array<string>
     */
    public function getManualEvents($idOrderItem)
    {
        $orderItemEntity = $this->queryContainer
            ->querySalesOrderItems([$idOrderItem])
            ->findOne();

        return $this->getManualEventsByOrderItemEntity($orderItemEntity);
    }

    /**
     * @param int $idSalesOrder
     *
     * @return array<array<string>>
     */
    public function getManualEventsByIdSalesOrder($idSalesOrder)
    {
        $orderItems = $this->queryContainer->querySalesOrderItemsByIdSalesOrder($idSalesOrder)->find();

        $events = [];
        foreach ($orderItems as $orderItemEntity) {
            $events[$orderItemEntity->getIdSalesOrderItem()] = $this->getManualEventsByOrderItemEntity($orderItemEntity);
        }

        return $events;
    }

    /**
     * @param int $idSalesOrder
     *
     * @return array<string>
     */
    public function getDistinctManualEventsByIdSalesOrder($idSalesOrder)
    {
        $events = $this->getManualEventsByIdSalesOrder($idSalesOrder);

        $allEvents = [];
        foreach ($events as $eventList) {
            $allEvents = array_merge($allEvents, $eventList);
        }

        return array_unique($allEvents);
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem $orderItem
     *
     * @return array<string>
     */
    protected function getManualEventsByOrderItemEntity(SpySalesOrderItem $orderItem)
    {
        $state = $orderItem->getState()->getName();
        $processName = $orderItem->getProcess()->getName();

        $processBuilder = clone $this->builder;
        $events = $processBuilder->createProcess($processName)->getManualEventsBySource();

        if (!isset($events[$state])) {
            return [];
        }

        return $events[$state];
    }

    /**
     * @param int $idOrder
     * @param string $flag
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function isOrderFlagged($idOrder, $flag)
    {
        $order = $this->queryContainer
            ->querySalesOrderById($idOrder)
            ->findOne();

        if ($order === null) {
            throw new Exception('Order not found for id ' . $idOrder);
        }

        $flaggedOrderItems = $this->getItemsByFlag($order, $flag, true);

        return (count($flaggedOrderItems) > 0);
    }

    /**
     * @param int $idOrder
     * @param string $flag
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function isOrderFlaggedAll($idOrder, $flag)
    {
        $order = $this->queryContainer
            ->querySalesOrderById($idOrder)
            ->findOne();

        if ($order === null) {
            throw new Exception('Order not found for id ' . $idOrder);
        }

        $flaggedOrderItems = $this->getItemsByFlag($order, $flag, true);

        return $flaggedOrderItems && (count($flaggedOrderItems) === count($order->getItems()));
    }

    /**
     * @param int $idOrder
     *
     * @return bool
     */
    public function isOrderFlaggedExcludeFromCustomer($idOrder)
    {
        return $this->isOrderFlaggedAll($idOrder, OmsConfig::STATE_TYPE_FLAG_EXCLUDE_FROM_CUSTOMER);
    }

    /**
     * @deprecated Not in use anymore.
     *
     * @param array<\Spryker\Zed\Oms\Business\Process\StateInterface> $states
     * @param string $sku
     * @param bool $returnTest
     *
     * @return \Orm\Zed\Sales\Persistence\SpySalesOrderItem|null
     */
    protected function countOrderItemsForSku(array $states, $sku, $returnTest = true)
    {
        return $this->queryContainer
            ->sumProductQuantitiesForAllSalesOrderItemsBySku($states, $sku, $returnTest)
            ->findOne();
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $order
     *
     * @return array
     */
    public function getGroupedManuallyExecutableEvents(SpySalesOrder $order)
    {
        $processBuilder = clone $this->builder;
        $processName = $order->getItems()->getFirst()->getProcess()->getName();
        $process = $processBuilder->createProcess($processName);
        $eventsBySource = $process->getManualEventsBySource();

        $eventsByItem = [];
        foreach ($order->getItems() as $item) {
            $itemId = $item->getIdSalesOrderItem();
            $stateName = $item->getState()->getName();
            $eventsByItem[$itemId] = [];

            if (!isset($eventsBySource[$stateName])) {
                continue;
            }
            $manualEvents = $eventsBySource[$stateName];
            $eventsByItem[$itemId] = $manualEvents;
        }

        $allEvents = [];
        foreach ($order->getItems() as $item) {
            $stateName = $item->getState()->getName();
            $events = $process->getStateFromAllProcesses($stateName)->getEvents();
            foreach ($events as $event) {
                if ($event->isManual()) {
                    $allEvents[] = $event->getName();
                }
            }
        }

        $orderEvents = array_unique($allEvents);

        $result = [
            'order_events' => $orderEvents,
            'item_events' => $eventsByItem,
        ];

        return $result;
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $order
     * @param string $flag
     *
     * @return array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem>
     */
    public function getItemsWithFlag(SpySalesOrder $order, $flag)
    {
        return $this->getItemsByFlag($order, $flag, true);
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $order
     * @param string $flag
     *
     * @return array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem>
     */
    public function getItemsWithoutFlag(SpySalesOrder $order, $flag)
    {
        return $this->getItemsByFlag($order, $flag, false);
    }

    /**
     * @return array<\Spryker\Zed\Oms\Business\Process\ProcessInterface>
     */
    public function getProcesses()
    {
        $processes = [];
        foreach ($this->activeProcesses as $processName) {
            $builder = clone $this->builder;
            $processes[$processName] = $builder->createProcess($processName);
        }

        return $processes;
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $order
     * @param string $flag
     * @param bool $hasFlag
     *
     * @return array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem>
     */
    protected function getItemsByFlag(SpySalesOrder $order, $flag, $hasFlag)
    {
        $items = $order->getItems();
        $states = $this->getStatesByFlag(
            $items->getFirst()->getProcess()->getName(),
            $flag,
            $hasFlag,
        );

        $selectedItems = [];
        foreach ($items as $item) {
            if (array_key_exists($item->getState()->getName(), $states)) {
                $selectedItems[] = $item;
            }
        }

        return $selectedItems;
    }

    /**
     * @param string $processName
     * @param string $flag
     * @param bool $hasFlag
     *
     * @return array<\Spryker\Zed\Oms\Business\Process\StateInterface>
     */
    protected function getStatesByFlag($processName, $flag, $hasFlag)
    {
        $selectedStates = [];
        $builder = clone $this->builder;
        $processStateList = $builder->createProcess($processName)->getAllStates();
        foreach ($processStateList as $state) {
            if (($hasFlag && $state->hasFlag($flag)) || (!$hasFlag && !$state->hasFlag($flag))) {
                $selectedStates[$state->getName()] = $state;
            }
        }

        return $selectedStates;
    }

    /**
     * @return array
     */
    protected function retrieveReservedStates()
    {
        $reservedStates = [];
        foreach ($this->activeProcesses as $processName) {
            $builder = clone $this->builder;
            $process = $builder->createProcess($processName);
            $reservedStates = array_merge($reservedStates, $process->getAllReservedStates());
        }

        return $reservedStates;
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem $orderItem
     *
     * @throws \Spryker\Zed\Oms\Business\Exception\StateNotFoundException
     *
     * @return string
     */
    public function getStateDisplayName(SpySalesOrderItem $orderItem)
    {
        $processName = $orderItem->getProcess()->getName();
        $builder = clone $this->builder;
        $process = $builder->createProcess($processName);
        $stateName = $orderItem->getState()->getName();

        $allStates = $process->getAllStates();
        if (!isset($allStates[$stateName])) {
            throw new StateNotFoundException(sprintf(
                sprintf(
                    'State with name "%s" not found in any StateMachine processes.',
                    $stateName,
                ),
            ));
        }

        $state = $allStates[$stateName];

        return $state->getDisplay();
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @return \Spryker\Zed\Oms\Business\Process\StateInterface|null
     */
    public function findStateByName(ItemTransfer $itemTransfer): ?StateInterface
    {
        $processName = $itemTransfer->requireProcess()->getProcess();
        $process = $this->builder->createProcess($processName);
        $stateName = $itemTransfer->requireState()->getState()->getName();
        $allStates = $process->getAllStates();

        return $allStates[$stateName] ?? null;
    }
}
