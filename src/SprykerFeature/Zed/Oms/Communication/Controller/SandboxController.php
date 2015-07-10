<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace SprykerFeature\Zed\Oms\Communication\Controller;

use Propel\Runtime\ActiveQuery\Criteria;
use SprykerFeature\Zed\Application\Communication\Controller\AbstractController;
use SprykerFeature\Zed\Country\Persistence\Propel\SpyCountryQuery;
use SprykerFeature\Zed\Country\Persistence\Propel\SpyCountry;
use SprykerFeature\Zed\Oms\Business\OmsFacade;
use SprykerFeature\Zed\Oms\Persistence\Propel\Base\SpyOmsOrderProcessQuery;
use SprykerFeature\Zed\Oms\Persistence\Propel\SpyOmsOrderItemState;
use SprykerFeature\Zed\Oms\Persistence\Propel\SpyOmsOrderItemStateQuery;
use SprykerFeature\Zed\Oms\Persistence\Propel\SpyOmsOrderProcess;
use SprykerFeature\Zed\Sales\Persistence\Propel\Base\SpySalesOrderQuery;
use SprykerFeature\Zed\Sales\Persistence\Propel\SpySalesOrder;
use SprykerFeature\Zed\Sales\Persistence\Propel\SpySalesOrderAddress;
use SprykerFeature\Zed\Sales\Persistence\Propel\SpySalesOrderItem;
use SprykerFeature\Zed\Sales\Persistence\Propel\SpySalesOrderItemQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * this class is only for test purpose. It will be removed from repository
 *
 * @method OmsFacade getFacade()
 */
class SandboxController extends AbstractController
{

    const PROCESS_NAME = 'Nopayment01';

    const STATE_NAME = 'new';

    /**
     * @return array
     */
    public function indexAction()
    {
        $ordersQuery = SpySalesOrderQuery::create();
        $ordersQuery->orderByIdSalesOrder(Criteria::DESC);

        $orders = $ordersQuery->find();

        return [
            'orders' => $orders,
            'processName' => self::PROCESS_NAME,
        ];
    }

    /**
     * @return RedirectResponse
     */
    public function addAction()
    {
        $this->createOrderItem();

        return $this->redirectResponse('/oms/sandbox/');
    }

    /**
     * @deprecated
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function triggerAction(Request $request)
    {
        $event = $request->query->get('event');

        $idOrderItem = $request->query->get('id');
        $orderItem = SpySalesOrderItemQuery::create()->findPk($idOrderItem);
        if ($orderItem === null) {
            throw new NotFoundHttpException('Unknown OrderItem Id');
        }

        $this->getFacade()->triggerEventForOneItem($event, $orderItem, []);

        return $this->redirectResponse('/oms/sandbox');
    }

    /**
     * Create test order with 4 items
     *
     * @throws PropelException
     */
    protected function createOrderItem()
    {
        $country = SpyCountryQuery::create()->findOne();

        $state = $this->saveTestState();

        $cities = [
            'Berlin',
            'Hamburg',
            'Dresden',
            'Muenchen',
        ];

        $address = $this->saveTestAddress($cities, $country);

        $order = $this->saveTestOrder($address);

        $process = $this->saveTestProcess();

        $skus = [
            'QA123' => rand(10, 300),
            'QA456' => rand(100, 100),
            'QA789' => rand(200, 200),
        ];

        $total = 0;
        foreach ($skus as $sku => $price) {
            $this->addOrderItem($order, $state, $process, $price, $sku);
            $total += $price;
        }

        $key = array_rand(array_keys($skus));
        $duplicateSku = array_keys($skus)[$key];

        $this->addOrderItem($order, $state, $process, $skus[$duplicateSku], $duplicateSku);

        $total += $skus[$duplicateSku];

        $this->updateTestOrderTotalPrice($order, $total);
    }

    /**
     * @param SpySalesOrder $order
     * @param SpyOmsOrderItemState $state
     * @param SpyOmsOrderProcess $process
     * @param float $price
     * @param string $sku
     */
    protected function addOrderItem(
        SpySalesOrder $order,
        SpyOmsOrderItemState $state,
        SpyOmsOrderProcess $process,
        $price,
        $sku
    ) {
        $orderItem = new SpySalesOrderItem();
        $orderItem->setOrder($order);
        $orderItem->setState($state);
        $orderItem->setProcess($process);
        $orderItem->setGrossPrice($price);
        $orderItem->setPriceToPay($price);
        $orderItem->setSku($sku);
        $orderItem->setName('Answering Machine');
        $orderItem->save();
    }

    /**
     * @param bool $isLast
     *
     * @return string
     */
    protected function generateCustomer($isLast = false)
    {
        $firstNames = [
            'Adam', 'Alexia', 'Astrid', 'Bruno', 'Denis', 'Mathias',
        ];
        $lastNames = [
            'Mueller', 'Schneider', 'Schulz', 'Braun', 'Vogel',
        ];

        $nameType = ($isLast === true) ? 'lastNames' : 'firstNames';
        $key = array_rand($$nameType);

        return ${$nameType}[$key];
    }

    /**
     * @param array $cities
     * @param SpyCountry $country
     *
     * @return SpySalesOrderAddress
     */
    protected function saveTestAddress($cities, SpyCountry $country)
    {
        $address = new SpySalesOrderAddress();
        $address->setFirstName($this->generateCustomer());
        $address->setLastName($this->generateCustomer(true));
        $address->setAddress1('Address');
        $address->setZipCode(10115);
        $address->setCity($cities[array_rand($cities)]);
        $address->setCountry($country);
        $address->save();

        return $address;
    }

    /**
     * @param SpySalesOrderAddress $address
     *
     * @return SpySalesOrder
     */
    protected function saveTestOrder(SpySalesOrderAddress $address)
    {
        $order = new SpySalesOrder();
        $order->setIsTest(true);
        $order->setFirstName($address->getFirstName());
        $order->setLastName($address->getLastName());
        $order->setShippingAddress($address);
        $order->setBillingAddress($address);
        $order->setSubtotal(0);
        $order->setGrandTotal(0);
        $order->save();

        return $order;
    }

    /**
     * @return SpyOmsOrderProcess
     */
    protected function saveTestProcess()
    {
        $process = SpyOmsOrderProcessQuery::create()->findOneByName(self::PROCESS_NAME);
        if ($process === null) {
            $process = new SpyOmsOrderProcess();
            $process->setName(self::PROCESS_NAME);
            $process->save();
        }

        return $process;
    }

    /**
     * @return SpyOmsOrderItemState
     */
    protected function saveTestState()
    {
        $state = SpyOmsOrderItemStateQuery::create()->findOneByName(self::STATE_NAME);
        if ($state === null) {
            $state = new SpyOmsOrderItemState();
            $state->setName(self::STATE_NAME);
            $state->save();
        }

        return $state;
    }

    /**
     * @param SpySalesOrder $order
     * @param float $total
     */
    protected function updateTestOrderTotalPrice(SpySalesOrder $order, $total)
    {
        $order->setSubtotal($total);
        $order->setGrandTotal($total);
        $order->save();
    }

}
