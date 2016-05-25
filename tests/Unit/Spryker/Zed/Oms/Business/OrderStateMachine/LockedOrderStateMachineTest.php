<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Unit\Spryker\Zed\Oms\Business\OrderStateMachine;

use Orm\Zed\Oms\Persistence\Base\SpyOmsStateMachineLockQuery;
use Orm\Zed\Oms\Persistence\SpyOmsStateMachineLock;
use Orm\Zed\Sales\Persistence\SpySalesOrderItem;
use Propel\Runtime\Exception\PropelException;
use Spryker\Zed\Oms\Business\Lock\TriggerLocker;
use Spryker\Zed\Oms\Business\OrderStateMachine\LockedOrderStateMachine;
use Spryker\Zed\Oms\Business\OrderStateMachine\OrderStateMachineInterface;
use Spryker\Zed\Oms\OmsConfig;
use Spryker\Zed\Oms\Persistence\OmsQueryContainer;

/**
 * @group Spryker
 * @group Zed
 * @group Oms
 * @group Business
 * @group Builder
 */
class LockedOrderStateMachineTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $stateMachineMock;

    /**
     * @var \Spryker\Zed\Oms\Business\OrderStateMachine\LockedOrderStateMachine
     */
    protected $lockedStateMachine;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $omsQueryContainerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $omsQueryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $omsStateMachineLockMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $triggerLockerMock;

    /**
     * Because LockedStateMachine contains 5 similar methods (because it is a decorator), which are just calling
     * different StateMachine methods, this data provider is used to test similar cases for all these methods,
     * reducing the amount of code 5 times
     *
     * @return array
     */
    public function triggerEventsDataProvider()
    {
        $eventId = 'eventId';
        $orderItems = $this->createOrderItems();
        $orderItemIds = [10, 11, 12];
        $orderItemsIdentifier = '10-11-12';
        $singleOrderItemIdentifier = '10';

        return [
            ['triggerEvent', $orderItemsIdentifier, $eventId, $orderItems, []],
            ['triggerEventForNewItem', $orderItemsIdentifier,  $orderItems, []],
            ['triggerEventForNewOrderItems', $orderItemsIdentifier, $orderItemIds, []],
            ['triggerEventForOneOrderItem', $singleOrderItemIdentifier, $eventId, $singleOrderItemIdentifier, []],
            ['triggerEventForOrderItems', $orderItemsIdentifier, $eventId, $orderItemIds, []],
        ];
    }

    /**
     * @dataProvider triggerEventsDataProvider
     *
     * @expectedException \Spryker\Zed\Oms\Business\Exception\LockException
     *
     * @return void
     */
    public function testTriggerSimilarEventsWhenTriggerIsLocked()
    {
        $arguments = func_get_args();
        $methodToTest = array_shift($arguments);
        $expectedIdentifier = array_shift($arguments);

        $lockedStateMachine = $this->createLockedStateMachine();
        $this->expectStateMachineLockSaveFails($expectedIdentifier);

        call_user_func_array([$lockedStateMachine, $methodToTest], $arguments);
    }

    /**
     * @dataProvider triggerEventsDataProvider
     *
     * @return void
     */
    public function testTriggerSimilarEventsLockReleasesWhenTriggerSuccess()
    {
        $arguments = func_get_args();
        $methodToTest = array_shift($arguments);
        $expectedIdentifier = array_shift($arguments);

        $lockedStateMachine = $this->createLockedStateMachine();

        $this->expectStateMachineLockSaveSuccess($expectedIdentifier);
        $this->expectTriggerRelease($expectedIdentifier);

        call_user_func_array([$lockedStateMachine, $methodToTest], $arguments);
    }

    /**
     * @dataProvider triggerEventsDataProvider
     *
     * @expectedException \Exception
     *
     * @return void
     */
    public function testTriggerEventLockReleasesWhenTriggerFails()
    {
        $arguments = func_get_args();
        $methodToTest = array_shift($arguments);
        $expectedIdentifier = array_shift($arguments);

        $lockedStateMachine = $this->createLockedStateMachine();

        $this->expectStateMachineLockSaveSuccess($expectedIdentifier);

        $this->stateMachineMock->expects($this->once())
            ->method($methodToTest)
            ->willThrowException(new \Exception('Something bad happened'));

        $this->expectTriggerRelease($expectedIdentifier);

        call_user_func_array([$lockedStateMachine, $methodToTest], $arguments);
    }

    /**
     * @return void
     */
    public function testCheckConditionMethodIsDecorated()
    {
        $lockedStateMachine = $this->createLockedStateMachine();
        $logContext = ['some log context'];

        $this->stateMachineMock->expects($this->once())
            ->method('checkConditions')
            ->with($logContext);

        $lockedStateMachine->checkConditions($logContext);
    }

    /**
     * @param string $expectedIdentifier
     */
    protected function expectStateMachineLockSaveSuccess($expectedIdentifier)
    {
        $stateMachineLock = $this->createSpyOmsStateMachineLockMock();

        $stateMachineLock->expects($this->once())
            ->method('setIdentifier')
            ->with($expectedIdentifier);

        $stateMachineLock->expects($this->once())
            ->method('setExpires')
            ->with($this->isInstanceOf(\DateTime::class));

        $stateMachineLock->expects($this->once())
            ->method('save')
            ->willReturn(1);

        $this->triggerLockerMock->expects($this->once())
            ->method('createStateMachineLockEntity')
            ->willReturn($stateMachineLock);
    }

    /**
     * @param string $expectedIdentifier
     */
    protected function expectStateMachineLockSaveFails($expectedIdentifier)
    {
        $stateMachineLock = $this->createSpyOmsStateMachineLockMock();

        $stateMachineLock->expects($this->once())
            ->method('setIdentifier')
            ->with($expectedIdentifier);

        $stateMachineLock->expects($this->once())
            ->method('setExpires')
            ->with($this->isInstanceOf(\DateTime::class));

        $stateMachineLock->expects($this->once())
            ->method('save')
            ->willThrowException(new PropelException());

        $this->triggerLockerMock->expects($this->once())
            ->method('createStateMachineLockEntity')
            ->willReturn($stateMachineLock);
    }

    /**
     * @param string $identifier
     */
    protected function expectTriggerRelease($identifier)
    {
        $queryMock = $this->createOmsQueryMock();
        $queryMock->expects($this->once())
            ->method('delete');

        $this->omsQueryContainerMock->expects($this->once())
            ->method('queryLockItemsByIdentifier')
            ->with($identifier)
            ->willReturn($queryMock);
    }


    /**
     * @return \Spryker\Zed\Oms\Business\OrderStateMachine\LockedOrderStateMachine
     */
    protected function createLockedStateMachine()
    {
        return new LockedOrderStateMachine(
            $this->createStateMachineMock(),
            $this->createTriggerLockerMock()
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createStateMachineMock()
    {
        $this->stateMachineMock = $this->getMockForAbstractClass(
            OrderStateMachineInterface::class, [], '', true, true, true, [
                'triggerEvent'
            ]
        );
        return $this->stateMachineMock;
    }

    /**
     * @return \Spryker\Zed\Oms\Business\Lock\TriggerLocker
     */
    protected function createTriggerLockerMock()
    {
        $this->triggerLockerMock = $this->getMock(
            TriggerLocker::class,
            ['createStateMachineLockEntity'],
            [$this->createOmsQueryContainerMock(), $this->createOmsConfig()]
        );
        return $this->triggerLockerMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createOmsQueryContainerMock()
    {
        $this->omsQueryContainerMock = $this->getMock(
            OmsQueryContainer::class,
            [
                'queryLockedItemsByIdentifierAndExpirationDate',
                'queryLockItemsByIdentifier'
            ]
        );
        return $this->omsQueryContainerMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createOmsQueryMock()
    {
        $this->omsQueryMock = $this->getMock(SpyOmsStateMachineLockQuery::class, ['count', 'delete']);
        return $this->omsQueryMock;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createSpyOmsStateMachineLockMock()
    {
        $this->omsStateMachineLockMock = $this->getMock(
            SpyOmsStateMachineLock::class,
            ['setIdentifier', 'setExpires', 'save']
        );

        return $this->omsStateMachineLockMock;
    }

    /**
     * @return \Spryker\Zed\Oms\OmsConfig
     */
    protected function createOmsConfig()
    {
        return new OmsConfig();
    }

    /**
     * @return array
     */
    protected function createOrderItems()
    {
        $orderItem1 = (new SpySalesOrderItem())
            ->setIdSalesOrderItem(10);

        $orderItem2 = (new SpySalesOrderItem())
            ->setIdSalesOrderItem(11);

        $orderItem3 = (new SpySalesOrderItem())
            ->setIdSalesOrderItem(12);

        return [$orderItem1, $orderItem2, $orderItem3];
    }

    /**
     * @param array $orderItems
     * 
     * @return string
     */
    protected function getOrderItemsIdentifier($orderItems)
    {
        $orderItemIds = [];
        foreach ($orderItems as $orderItem) {
            $orderItemIds[] = $orderItem->getIdSalesOrderItem();
        }

        return implode('-', $orderItemIds);
    }

}
