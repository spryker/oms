<?php
/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Oms\Business;

use Codeception\Test\Unit;
use DateTime;
use Orm\Zed\Oms\Persistence\SpyOmsOrderItemState;
use Orm\Zed\Oms\Persistence\SpyOmsOrderItemStateQuery;
use Orm\Zed\Oms\Persistence\SpyOmsProductReservationQuery;
use Orm\Zed\Sales\Persistence\SpySalesOrder;
use Orm\Zed\Sales\Persistence\SpySalesOrderAddress;
use Orm\Zed\Sales\Persistence\SpySalesOrderItem;
use Spryker\Zed\Oms\Business\OmsFacade;

/**
 * Auto-generated group annotations
 * @group SprykerTest
 * @group Zed
 * @group Oms
 * @group Business
 * @group Facade
 * @group OmsFacadeSumReservedItemsTest
 * Add your own group annotations below this line
 */
class OmsFacadeSumReservedItemsTest extends Unit
{
    const ORDER_REFERENCE = '123';
    const ORDER_ITEM_SKU = 'oms-reserverd-sku-test';
    const RESERVER_ITEM_STATE = 'paid';

    /**
     * @return void
     */
    public function testSumReservedItemsShouldSumAllItemsInReservedState()
    {
        $this->createTestOrder();

        $omsFacade = $this->createOmsFacade();
        $sumOfQuantities = $omsFacade->sumReservedProductQuantitiesForSku(self::ORDER_ITEM_SKU);

        $this->assertEquals(50, $sumOfQuantities);
    }

    /**
     * @return void
     */
    public function testGetOmsReservedProductQuantitiesForSkuSumAllItemsInReservedState()
    {
        $this->createTestOrder();

        $omsFacade = $this->createOmsFacade();
        $reservationQuantities = $omsFacade->getOmsReservedProductQuantitiesForSku(self::ORDER_ITEM_SKU);

        $this->assertEquals(50, $reservationQuantities);
    }

    /**
     * @return \Orm\Zed\Sales\Persistence\SpySalesOrder
     */
    protected function createTestOrder()
    {
        $salesOrderAddressEntity = $this->createSalesOrderAddress();

        $salesOrderEntity = $this->createSalesOrder($salesOrderAddressEntity);
        $omsStateEntity = $this->createOmsOrderItemState();

        $this->createSalesOrderItem($omsStateEntity, $salesOrderEntity);
        $this->updateReservation($salesOrderEntity->getItems()->getFirst());

        return $salesOrderEntity;
    }

    /**
     * @return \Spryker\Zed\Oms\Business\OmsFacade
     */
    protected function createOmsFacade()
    {
        return new OmsFacade();
    }

    /**
     * @return \Orm\Zed\Sales\Persistence\SpySalesOrderAddress
     */
    protected function createSalesOrderAddress()
    {
        $salesOrderAddressEntity = new SpySalesOrderAddress();
        $salesOrderAddressEntity->setAddress1(1);
        $salesOrderAddressEntity->setAddress2(2);
        $salesOrderAddressEntity->setSalutation('Mr');
        $salesOrderAddressEntity->setCellPhone('123456789');
        $salesOrderAddressEntity->setCity('City');
        $salesOrderAddressEntity->setCreatedAt(new DateTime());
        $salesOrderAddressEntity->setUpdatedAt(new DateTime());
        $salesOrderAddressEntity->setComment('comment');
        $salesOrderAddressEntity->setDescription('describtion');
        $salesOrderAddressEntity->setCompany('company');
        $salesOrderAddressEntity->setFirstName('First name');
        $salesOrderAddressEntity->setLastName('Last Name');
        $salesOrderAddressEntity->setFkCountry(1);
        $salesOrderAddressEntity->setEmail('email');
        $salesOrderAddressEntity->setZipCode(10405);
        $salesOrderAddressEntity->save();

        return $salesOrderAddressEntity;
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderAddress $salesOrderAddressEntity
     *
     * @return \Orm\Zed\Sales\Persistence\SpySalesOrder
     */
    protected function createSalesOrder(SpySalesOrderAddress $salesOrderAddressEntity)
    {
        $salesOrderEntity = new SpySalesOrder();
        $salesOrderEntity->setBillingAddress($salesOrderAddressEntity);
        $salesOrderEntity->setShippingAddress(clone $salesOrderAddressEntity);
        $salesOrderEntity->setOrderReference(self::ORDER_REFERENCE);
        $salesOrderEntity->save();

        return $salesOrderEntity;
    }

    /**
     * @return \Orm\Zed\Oms\Persistence\SpyOmsOrderItemState
     */
    protected function createOmsOrderItemState()
    {
        $omsStateEntity = SpyOmsOrderItemStateQuery::create()
            ->filterByName(self::RESERVER_ITEM_STATE)
            ->findOneOrCreate();

        $omsStateEntity->save();

        return $omsStateEntity;
    }

    /**
     * @param \Orm\Zed\Oms\Persistence\SpyOmsOrderItemState $omsStateEntity
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $salesOrderEntity
     *
     * @return void
     */
    protected function createSalesOrderItem(SpyOmsOrderItemState $omsStateEntity, SpySalesOrder $salesOrderEntity)
    {
        $salesOrderItem = new SpySalesOrderItem();
        $salesOrderItem->setGrossPrice(150);
        $salesOrderItem->setQuantity(50);
        $salesOrderItem->setSku(self::ORDER_ITEM_SKU);
        $salesOrderItem->setName('test1');
        $salesOrderItem->setTaxRate(12);
        $salesOrderItem->setFkOmsOrderItemState($omsStateEntity->getIdOmsOrderItemState());
        $salesOrderItem->setFkSalesOrder($salesOrderEntity->getIdSalesOrder());
        $salesOrderItem->save();
    }

    /**
     * @internal param string $sku
     *
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem $spySalesOrderItem
     *
     * @return void
     */
    protected function updateReservation(SpySalesOrderItem $spySalesOrderItem)
    {
        $spyOmsReservationEntity = SpyOmsProductReservationQuery::create()
            ->filterBySku($spySalesOrderItem->getSku())
            ->findOneOrCreate();

        $spyOmsReservationEntity->setReservationQuantity($spySalesOrderItem->getQuantity());
        $spyOmsReservationEntity->save();
    }
}
