<?php
/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Functional\Spryker\Zed\Oms\Business;

use Codeception\TestCase\Test;
use DateTime;
use Orm\Zed\Oms\Persistence\Base\SpyOmsOrderItemStateQuery;
use Orm\Zed\Oms\Persistence\SpyOmsOrderItemState;
use Orm\Zed\Sales\Persistence\SpySalesOrder;
use Orm\Zed\Sales\Persistence\SpySalesOrderAddress;
use Orm\Zed\Sales\Persistence\SpySalesOrderItem;
use Spryker\Zed\Oms\Business\OmsFacade;

/**
 * @group Functional
 * @group Spryker
 * @group Zed
 * @group Oms
 * @group Business
 * @group OmsFacadeSumReservedItemsTest
 */
class OmsFacadeSumReservedItemsTest extends Test
{

    const ORDER_REFERENCE = '123';
    const ORDER_ITEM_SKU = 'oms-reserverd-sku-test';
    const RESERVER_ITEM_STATE = 'paid';

    /**
     * @return void
     */
    public function testSumReservedItemsShouldSumAllItemsInReserverdState()
    {
        $this->createTestOrder();

        $omsFacade = $this->createOmsFacade();
        $sumOfQuantities = $omsFacade->sumReservedProductQuantitiesForSku(self::ORDER_ITEM_SKU);

        $this->assertEquals(50, $sumOfQuantities);
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
            ->findOneByName(self::RESERVER_ITEM_STATE);

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

}
