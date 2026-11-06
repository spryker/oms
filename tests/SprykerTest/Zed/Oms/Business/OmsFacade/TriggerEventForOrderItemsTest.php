<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Oms\Business\OmsFacade;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use SprykerTest\Zed\Oms\OmsBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Oms
 * @group Business
 * @group OmsFacade
 * @group TriggerEventForOrderItemsTest
 * Add your own group annotations below this line
 */
class TriggerEventForOrderItemsTest extends Unit
{
    protected const string PROCESS_NAME = 'Test08';

    protected const string STORE_NAME = 'DE';

    protected const string EVENT_RESERVE = 'reserve';

    protected const string EVENT_COMPLETE = 'complete';

    protected const string EVENT_CANCEL = 'cancel';

    protected OmsBusinessTester $tester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->resetReservationCaches();
        $this->tester->configureTestStateMachine([static::PROCESS_NAME]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->tester->resetReservationCaches();
    }

    public function testGivenAnItemInANonReservedStateWhenAnEventMovesItIntoAReservedStateThenItsQuantityIsReservedInTheOrderStore(): void
    {
        // Arrange
        $itemTransfer = $this->haveOrderItemInProcess();
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => static::STORE_NAME]);

        // Act
        $this->tester->getFacade()->triggerEventForOrderItems(static::EVENT_RESERVE, [$itemTransfer->getIdSalesOrderItem()]);

        // Assert
        $reservedQuantity = $this->tester->getFacade()->getOmsReservedProductQuantityForSku($itemTransfer->getSku(), $storeTransfer);
        $this->assertSame($itemTransfer->getQuantity(), $reservedQuantity->toInt());
    }

    public function testGivenAReservedItemWhenAnEventMovesItToANonReservedStateThenTheReservationDropsToZero(): void
    {
        // Arrange
        $itemTransfer = $this->haveOrderItemInProcess();
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => static::STORE_NAME]);
        $this->tester->getFacade()->triggerEventForOrderItems(static::EVENT_RESERVE, [$itemTransfer->getIdSalesOrderItem()]);

        // Act
        $this->tester->getFacade()->triggerEventForOrderItems(static::EVENT_COMPLETE, [$itemTransfer->getIdSalesOrderItem()]);

        // Assert
        $reservedQuantity = $this->tester->getFacade()->getOmsReservedProductQuantityForSku($itemTransfer->getSku(), $storeTransfer);
        $this->assertSame(0, $reservedQuantity->toInt());
    }

    public function testGivenAReservedItemWhenTheCancelEventIsTriggeredThenTheReservationDropsToZero(): void
    {
        // Arrange
        $itemTransfer = $this->haveOrderItemInProcess();
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => static::STORE_NAME]);
        $this->tester->getFacade()->triggerEventForOrderItems(static::EVENT_RESERVE, [$itemTransfer->getIdSalesOrderItem()]);

        // Act
        $this->tester->getFacade()->triggerEventForOrderItems(static::EVENT_CANCEL, [$itemTransfer->getIdSalesOrderItem()]);

        // Assert
        $reservedQuantity = $this->tester->getFacade()->getOmsReservedProductQuantityForSku($itemTransfer->getSku(), $storeTransfer);
        $this->assertSame(0, $reservedQuantity->toInt());
    }

    protected function haveOrderItemInProcess(): ItemTransfer
    {
        $orderTransfer = $this->tester->createOrderByStateMachineProcessName(static::PROCESS_NAME);

        return $orderTransfer->getItems()->offsetGet(0);
    }
}
