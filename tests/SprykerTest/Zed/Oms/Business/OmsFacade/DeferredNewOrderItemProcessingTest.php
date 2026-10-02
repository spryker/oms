<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Oms\Business\OmsFacade;

use Codeception\Test\Unit;
use DateInterval;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Orm\Zed\Oms\Persistence\SpyOmsEventTimeout;
use Orm\Zed\Sales\Persistence\Map\SpySalesOrderItemTableMap;
use Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery;
use Spryker\Zed\Oms\Business\OrderStateMachine\Timeout;
use SprykerTest\Zed\Oms\OmsBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Oms
 * @group Business
 * @group OmsFacade
 * @group DeferredNewOrderItemProcessingTest
 * Add your own group annotations below this line
 */
class DeferredNewOrderItemProcessingTest extends Unit
{
    protected const string PROCESS_NAME = 'Test07';

    protected const string STATE_NEW = 'new';

    protected const string STATE_STARTED = 'started';

    protected const string STATE_CANCELLED = 'cancelled';

    /**
     * @var \SprykerTest\Zed\Oms\OmsBusinessTester
     */
    protected OmsBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->configureTestStateMachine([static::PROCESS_NAME]);
        $this->tester->ensureOmsEventTimeoutTableIsEmpty();
    }

    /**
     * @dataProvider triggerEventForNewOrderItemsDataProvider
     */
    public function testTriggerEventForNewOrderItems(
        bool $isDeferredNewOrderItemProcessingEnabled,
        string $expectedStateName,
        bool $isDeferredTimeoutExpected
    ): void {
        // Arrange
        $this->tester->mockConfigMethod('isDeferredNewOrderItemProcessingEnabled', $isDeferredNewOrderItemProcessingEnabled);
        $orderItemIds = $this->getOrderItemIds($this->tester->createOrderByStateMachineProcessName(static::PROCESS_NAME));

        // Act
        $this->tester->getFacade()->triggerEventForNewOrderItems($orderItemIds);

        // Assert
        $this->assertOrderItemsInState($orderItemIds, $expectedStateName);

        $omsEventTimeoutEvents = array_map(
            static fn (SpyOmsEventTimeout $omsEventTimeoutEntity): string => $omsEventTimeoutEntity->getEvent(),
            $this->tester->getOmsEventTimeoutEntities()->getData(),
        );
        $expectedOmsEventTimeoutEvents = $isDeferredTimeoutExpected
            ? array_fill(0, count($orderItemIds), Timeout::EVENT_DEFERRED_NEW_ORDER_ITEM)
            : [];

        $this->assertSame($expectedOmsEventTimeoutEvents, $omsEventTimeoutEvents);
    }

    /**
     * @return array<string, array<bool|string>>
     */
    protected function triggerEventForNewOrderItemsDataProvider(): array
    {
        return [
            'deferred processing disabled executes onEnter event immediately' => [false, static::STATE_STARTED, false],
            'deferred processing enabled schedules deferred timeouts' => [true, static::STATE_NEW, true],
        ];
    }

    public function testCheckTimeoutsExecutesDeferredNewOrderItemsAndRemovesTheirTimeouts(): void
    {
        // Arrange
        $this->tester->mockConfigMethod('isDeferredNewOrderItemProcessingEnabled', true);
        $orderItemIds = $this->getOrderItemIds($this->tester->createOrderByStateMachineProcessName(static::PROCESS_NAME));
        $this->tester->getFacade()->triggerEventForNewOrderItems($orderItemIds);
        $this->expireTimeouts($orderItemIds);

        // Act
        $this->tester->getFacade()->checkTimeouts();

        // Assert
        $this->assertOrderItemsInState($orderItemIds, static::STATE_STARTED);
        $this->assertCount(0, $this->tester->getOmsEventTimeoutEntities());
    }

    public function testCheckTimeoutsRemovesDeferredTimeoutsWithoutExecutionWhenOrderItemsLeftScheduledState(): void
    {
        // Arrange
        $this->tester->mockConfigMethod('isDeferredNewOrderItemProcessingEnabled', true);
        $orderItemIds = $this->getOrderItemIds($this->tester->createOrderByStateMachineProcessName(static::PROCESS_NAME));
        $this->tester->getFacade()->triggerEventForNewOrderItems($orderItemIds);
        $this->expireTimeouts($orderItemIds);

        foreach ($orderItemIds as $idSalesOrderItem) {
            $this->tester->setItemState($idSalesOrderItem, static::STATE_CANCELLED);
        }

        // Act
        $this->tester->getFacade()->checkTimeouts();

        // Assert
        $this->assertOrderItemsInState($orderItemIds, static::STATE_CANCELLED);
        $this->assertCount(0, $this->tester->getOmsEventTimeoutEntities());
    }

    /**
     * @return array<int>
     */
    protected function getOrderItemIds(OrderTransfer $orderTransfer): array
    {
        return array_map(
            static fn (ItemTransfer $itemTransfer): int => $itemTransfer->getIdSalesOrderItemOrFail(),
            $orderTransfer->getItems()->getArrayCopy(),
        );
    }

    /**
     * @param array<int> $orderItemIds
     */
    protected function expireTimeouts(array $orderItemIds): void
    {
        foreach ($orderItemIds as $idSalesOrderItem) {
            $this->tester->moveItemAfterTimeOut($idSalesOrderItem, new DateInterval('PT1M'));
        }
    }

    /**
     * @param array<int> $orderItemIds
     */
    protected function assertOrderItemsInState(array $orderItemIds, string $expectedStateName): void
    {
        SpySalesOrderItemTableMap::clearInstancePool();

        $salesOrderItemEntities = SpySalesOrderItemQuery::create()
            ->joinWithState()
            ->filterByIdSalesOrderItem_In($orderItemIds)
            ->find();

        $this->assertCount(count($orderItemIds), $salesOrderItemEntities);

        foreach ($salesOrderItemEntities as $salesOrderItemEntity) {
            $this->assertSame($expectedStateName, $salesOrderItemEntity->getState()->getName());
        }
    }
}
