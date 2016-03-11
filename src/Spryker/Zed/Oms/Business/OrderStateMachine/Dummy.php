<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\OrderStateMachine;

use Orm\Zed\Country\Persistence\SpyCountryQuery;
use Orm\Zed\Oms\Persistence\SpyOmsOrderItemState;
use Orm\Zed\Oms\Persistence\SpyOmsOrderProcessQuery;
use Orm\Zed\Sales\Persistence\SpySalesOrder;
use Orm\Zed\Sales\Persistence\SpySalesOrderAddress;
use Orm\Zed\Sales\Persistence\SpySalesOrderItem;

// FIXME core-120 move queries to queryContainer
class Dummy implements DummyInterface
{

    /**
     * @var \Spryker\Zed\Oms\Business\OrderStateMachine\BuilderInterface
     */
    protected $builder;

    /**
     * @param \Spryker\Zed\Oms\Business\OrderStateMachine\BuilderInterface $builder
     */
    public function __construct(BuilderInterface $builder)
    {
        $this->builder = $builder;
    }

    /**
     * @param string $processName
     *
     * @throws \Exception
     * @throws \Propel\Runtime\Exception\PropelException
     *
     * @return array
     */
    public function prepareItems($processName)
    {
        $orderItemsArray = $this->getOrderItems($processName);

        $orders = [];

        $txtArray = [];
        foreach ($orderItemsArray as $orderItemArray) {
            if (!isset($orders[$orderItemArray['orderId']])) {
                $order = new SpySalesOrder();

                $order->setGrandTotal(10000);
                $order->setSubtotal(9900);
                $order->setIsTest(false);

                $address = new SpySalesOrderAddress();
                $address->setLastName('Doe');
                $address->setFirstName('John');
                $address->setCity('Berlin');
                $address->setZipCode('12345');
                $address->setAddress1('Blastr 1');

                $country = SpyCountryQuery::create()->findOneByIdCountry(1);
                $address->setCountry($country);

                $order->setBillingAddress($address);
                $order->setShippingAddress($address);

                $orders[$orderItemArray['orderId']] = $order;
            }
        }

        $states = [];

        $orderItems = [];
        foreach ($orderItemsArray as $orderItemArray) {
            if (isset($states[$orderItemArray['state']])) {
                $state = $states[$orderItemArray['state']];
            } else {
                $state = new SpyOmsOrderItemState();
                $state->setName($orderItemArray['state']);
                $state->save();
                $states[$orderItemArray['state']] = $state;
            }

            $txtArray[] = 'State: ' . $state->getName();

            $process = SpyOmsOrderProcessQuery::create()->filterByName($orderItemArray['process'])->findOneOrCreate();
            $process->setName($orderItemArray['process']);
            $process->save();
            $txtArray[] = 'Process: ' . $process->getName();

            $item = new SpySalesOrderItem();
            $item->setState($state);
            $item->setProcess($process);

            $item->setName('Testproduct');
            $item->setSku('12345ABC');
            $item->setGrossPrice(10);

            $orders[$orderItemArray['orderId']]->addItem($item);

            $orderItems[] = $item;
        }

        foreach ($orderItems as $orderItem) {
            $orderItem->save();
            $txtArray[] = 'orderItem saved: ' . $orderItem->getIdSalesOrderItem();
        }

        return $txtArray;
    }

    /**
     * @param string $processName
     *
     * @return array
     */
    public function getOrderItems($processName)
    {
        $orderItemsArray = [];
        $c = 0;
        $process = $this->builder->createProcess($processName);
        for ($i = 0; $i < 2; $i++) {
            foreach ($process->getAllStates() as $state) {
                $orderItemsArray[] = [
                    'id' => $c,
                    'process' => $processName,
                    'state' => $state->getName(),
                    'orderId' => $i,
                ];
                $c++;
                break 2;
            }
        }

        return $orderItemsArray;
    }

}
