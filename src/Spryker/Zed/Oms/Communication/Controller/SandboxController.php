<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace Spryker\Zed\Oms\Communication\Controller;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\Exception\PropelException;
use Spryker\Zed\Application\Communication\Controller\AbstractController;
use Orm\Zed\Country\Persistence\SpyCountryQuery;
use Orm\Zed\Country\Persistence\SpyCountry;
use Orm\Zed\Customer\Persistence\SpyCustomer;
use Spryker\Zed\Oms\Business\OmsFacade;
use Orm\Zed\Oms\Persistence\Base\SpyOmsOrderProcessQuery;
use Orm\Zed\Oms\Persistence\SpyOmsOrderItemState;
use Orm\Zed\Oms\Persistence\SpyOmsOrderItemStateQuery;
use Orm\Zed\Oms\Persistence\SpyOmsOrderProcess;
use Orm\Zed\Sales\Persistence\SpySalesOrderQuery;
use Orm\Zed\Sales\Persistence\SpySalesExpense;
use Orm\Zed\Sales\Persistence\SpySalesOrder;
use Orm\Zed\Sales\Persistence\SpySalesOrderAddress;
use Orm\Zed\Sales\Persistence\SpySalesOrderItem;
use Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
     *
     * @return void
     */
    protected function createOrderItem()
    {
        $country = SpyCountryQuery::create()->findOne();

        $state = $this->saveTestState();

        $customer = $this->generateCustomer();

        $cities = [
            'Berlin',
            'Hamburg',
            'Dresden',
            'Muenchen',
        ];

        $address = $this->saveTestAddress($cities, $country);

        $order = $this->saveTestOrder($address, $customer);

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
     *
     * @return void
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

        $orderExpense = new SpySalesExpense();
        $orderExpense->setFkSalesOrder($order->getIdSalesOrder());
        $orderExpense->setFkSalesOrderItem($orderItem->getIdSalesOrderItem());
        $orderExpense->setType('sale');
        $orderExpense->setName('Expense Demo');
        $orderExpense->setPriceToPay($price);
        $orderExpense->setGrossPrice($price);
        $orderExpense->save();
    }

    /**
     * @param bool $isLast
     *
     * @return string
     */
    protected function generateCustomerName($isLast = false)
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
        $address->setFirstName($this->generateCustomerName());
        $address->setLastName($this->generateCustomerName(true));
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
    protected function saveTestOrder(SpySalesOrderAddress $address, SpyCustomer $customer)
    {
        $order = new SpySalesOrder();
        $order->setIsTest(true);
        $order->setFkCustomer($customer->getIdCustomer());
        $order->setEmail($customer->getEmail());
        $order->setFirstName($address->getFirstName());
        $order->setLastName($address->getLastName());
        $order->setShippingAddress($address);
        $order->setBillingAddress($address);
        $order->setOrderReference(uniqid());
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
     *
     * @return void
     */
    protected function updateTestOrderTotalPrice(SpySalesOrder $order, $total)
    {
        $order->setSubtotal($total);
        $order->setGrandTotal($total);
        $order->save();
    }

    /**
     * @return SpyCustomer
     */
    protected function generateCustomer()
    {
        $email = sprintf('customer_%d@spryker.com', rand(0, 1000));

        $customer = new SpyCustomer();
        $customer->setFirstName($this->generateCustomerName());
        $customer->setLastName($this->generateCustomerName(true));
        $customer->setEmail($email);
        $customer->setCustomerReference(uniqid());

        $customer->save();

        return $customer;
    }

}
