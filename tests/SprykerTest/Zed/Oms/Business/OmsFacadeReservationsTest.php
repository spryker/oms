<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Oms\Business;

use Codeception\Test\Unit;
use Generated\Shared\DataBuilder\OmsAvailabilityReservationRequestBuilder;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OmsAvailabilityReservationRequestTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Orm\Zed\Oms\Persistence\SpyOmsOrderItemStateQuery;
use Orm\Zed\Oms\Persistence\SpyOmsProductReservation;
use Orm\Zed\Sales\Persistence\SpySalesOrderQuery;
use Spryker\DecimalObject\Decimal;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Oms
 * @group Business
 * @group Facade
 * @group OmsFacadeReservationsTest
 * Add your own group annotations below this line
 */
class OmsFacadeReservationsTest extends Unit
{
    /**
     * @var string
     */
    protected const STORE_NAME_AT = 'AT';

    /**
     * @var string
     */
    protected const STORE_NAME_DE = 'DE';

    /**
     * @var string
     */
    protected const TEST_SKU_1 = 'test-sku-1';

    /**
     * @var string
     */
    protected const TEST_SKU_2 = 'test-sku-2';

    /**
     * @var \SprykerTest\Zed\Oms\OmsBusinessTester
     */
    protected $tester;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->tester->resetReservedStatesCache();
        $this->tester->configureTestStateMachine(['Test01', 'Test02', 'Test03']);
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
        $this->tester->resetReservedStatesCache();
    }

    /**
     * Import from store AT to store DE
     *
     * @return void
     */
    public function testImportReservationShouldHaveAmountInReservationTotals(): void
    {
        // Origin store
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => static::STORE_NAME_AT], false);

        $availabilityReservationRequestTransfer = (new OmsAvailabilityReservationRequestBuilder([
            OmsAvailabilityReservationRequestTransfer::SKU => 123,
            OmsAvailabilityReservationRequestTransfer::VERSION => 1,
            OmsAvailabilityReservationRequestTransfer::ORIGIN_STORE => $storeTransfer,
            OmsAvailabilityReservationRequestTransfer::RESERVATION_AMOUNT => 1,
        ]))->build();

        $this->getOmsFacade()->importReservation($availabilityReservationRequestTransfer);

        // Other store
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => static::STORE_NAME_DE]);
        $reservedAmount = $this->getOmsFacade()->getOmsReservedProductQuantityForSku('123', $storeTransfer);

        $this->assertTrue($reservedAmount->equals(1));
    }

    /**
     * @return void
     */
    public function testExportReservationShouldExportAllUnExportedReservations(): void
    {
        $testStateMachineProcessName = 'Test01';
        $saveOrderTransfer = $this->tester->haveOrder([
            ItemTransfer::UNIT_PRICE => 100,
            ItemTransfer::SUM_PRICE => 100,
        ], $testStateMachineProcessName);

        $salesOrderEntity = SpySalesOrderQuery::create()
            ->filterByIdSalesOrder($saveOrderTransfer->getIdSalesOrder())
            ->findOne();

        $items = $salesOrderEntity->getItems();

        $this->getOmsFacade()->triggerEvent('authorize', $items, []);
        $this->getOmsFacade()->triggerEvent('pay', $items, []);

        $this->getOmsFacade()->saveReservationVersion($items[0]->getSku());
        $this->getOmsFacade()->exportReservation();

        $this->assertGreaterThan(0, $this->getOmsFacade()->getLastExportedReservationVersion());
    }

    /**
     * @return void
     */
    public function testSumReservedItemsShouldSumAllItemsInReservedState(): void
    {
        $testSku = 'oms-sku-test-reservation';

        $saveOrderTransfer1 = $this->tester->haveOrder(
            [
                ItemTransfer::SKU => $testSku,
                ItemTransfer::UNIT_PRICE => 100,
                ItemTransfer::SUM_PRICE => 100,
            ],
            'Test01',
        );

        $saveOrderTransfer2 = $this->tester->haveOrder(
            [
                ItemTransfer::SKU => $testSku,
                ItemTransfer::UNIT_PRICE => 100,
                ItemTransfer::SUM_PRICE => 100,
            ],
            'Test02',
        );

        $saveOrderTransfer3 = $this->tester->haveOrder(
            [
                ItemTransfer::SKU => $testSku,
                ItemTransfer::UNIT_PRICE => 100,
                ItemTransfer::SUM_PRICE => 100,
            ],
            'Test03',
        );

        $expectedQuantity = new Decimal(0);
        foreach ([$saveOrderTransfer1, $saveOrderTransfer2] as $orderTransfer) {
            $orderItems = SpySalesOrderQuery::create()
                ->filterByIdSalesOrder($orderTransfer->getIdSalesOrder())
                ->findOne()
                ->getItems()
                ->getArrayCopy();

            $this->setItemsState($orderItems, 'paid');

            foreach ($orderItems as $orderItem) {
                $expectedQuantity = $expectedQuantity->add($orderItem->getQuantity());
            }
        }

        $orderItems = SpySalesOrderQuery::create()
            ->filterByIdSalesOrder($saveOrderTransfer3->getIdSalesOrder())
            ->findOne()
            ->getItems()
            ->getArrayCopy();

        $this->setItemsState($orderItems, 'paid');

        $this->assertTrue(
            $this->getOmsFacade()
                ->sumReservedProductQuantitiesForSku($testSku)
                ->equals($expectedQuantity),
        );
    }

    /**
     * @param array<\Orm\Zed\Sales\Persistence\SpySalesOrderItem> $orderItems
     * @param string $stateName
     *
     * @return void
     */
    protected function setItemsState(array $orderItems, string $stateName): void
    {
        $omsStateEntity = SpyOmsOrderItemStateQuery::create()
            ->filterByName($stateName)
            ->findOneOrCreate();

        foreach ($orderItems as $orderItem) {
            $orderItem->setState($omsStateEntity)->save();
        }
    }

    /**
     * Import from store AT to store DE
     *
     * @return void
     */
    public function testGetReservationsFromOtherStoresShouldReturnReservations(): void
    {
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => static::STORE_NAME_AT], false);

        $availabilityReservationRequestTransfer = (new OmsAvailabilityReservationRequestBuilder([
            OmsAvailabilityReservationRequestTransfer::SKU => 123,
            OmsAvailabilityReservationRequestTransfer::VERSION => 1,
            OmsAvailabilityReservationRequestTransfer::ORIGIN_STORE => $storeTransfer,
            OmsAvailabilityReservationRequestTransfer::RESERVATION_AMOUNT => 1,
        ]))->build();

        $this->getOmsFacade()->importReservation($availabilityReservationRequestTransfer);

        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => static::STORE_NAME_DE]);
        $reserved = $this->getOmsFacade()->getReservationsFromOtherStores('123', $storeTransfer);

        $this->assertTrue($reserved->equals(1));
    }

    /**
     * @return void
     */
    public function testGetOmsReservedProductQuantityForSkuShouldReturnCorrectReservedAmount(): void
    {
        //Arrange
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => static::STORE_NAME_DE]);
        $reservationQuantity = new Decimal(random_int(1, 100));
        $this->createProductReservation(static::TEST_SKU_1, $storeTransfer, $reservationQuantity);

        //Act
        $reserved = $this->getOmsFacade()->getOmsReservedProductQuantityForSku(static::TEST_SKU_1, $storeTransfer);

        //Assert
        $this->assertSame(
            $reservationQuantity->toInt(),
            $reserved->toInt(),
            'Calculated reserved amount does not match expected value.',
        );
    }

    /**
     * @return void
     */
    public function testGetOmsReservedProductQuantityForSkusShouldReturnCorrectReservedAmount(): void
    {
        //Arrange
        $storeTransfer = $this->tester->haveStore([StoreTransfer::NAME => static::STORE_NAME_DE]);
        $reservationQuantity1 = new Decimal(random_int(1, 100));
        $reservationQuantity2 = new Decimal(random_int(1, 100));
        $this->createProductReservation(static::TEST_SKU_1, $storeTransfer, $reservationQuantity1);
        $this->createProductReservation(static::TEST_SKU_2, $storeTransfer, $reservationQuantity2);

        //Act
        $reserved = $this->getOmsFacade()->getOmsReservedProductQuantityForSkus([
            static::TEST_SKU_1, static::TEST_SKU_2,
        ], $storeTransfer);

        //Assert
        $this->assertSame(
            $reservationQuantity1->add($reservationQuantity2)->toInt(),
            $reserved->toInt(),
            'Calculated reserved amount does not match expected value.',
        );
    }

    /**
     * @param string $sku
     * @param \Generated\Shared\Transfer\StoreTransfer $storeTransfer
     * @param \Spryker\DecimalObject\Decimal $reservationQuantity
     *
     * @return void
     */
    public function createProductReservation(string $sku, StoreTransfer $storeTransfer, Decimal $reservationQuantity): void
    {
        $omsProductReservationEntity = new SpyOmsProductReservation();
        $omsProductReservationEntity->setSku($sku)
            ->setFkStore($storeTransfer->getIdStore())
            ->setReservationQuantity($reservationQuantity->toString())
            ->save();
    }

    /**
     * @return \Spryker\Zed\Oms\Business\OmsFacadeInterface
     */
    protected function getOmsFacade()
    {
        return $this->tester->getFacade();
    }
}
