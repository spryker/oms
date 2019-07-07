<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Communication\Controller;

use Propel\Runtime\ActiveQuery\Criteria;
use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Spryker\Zed\Oms\Business\OmsFacadeInterface getFacade()
 * @method \Spryker\Zed\Oms\Persistence\OmsQueryContainerInterface getQueryContainer()
 * @method \Spryker\Zed\Oms\Communication\OmsCommunicationFactory getFactory()
 * @method \Spryker\Zed\Oms\Persistence\OmsRepositoryInterface getRepository()
 */
class TriggerController extends AbstractController
{
    /**
     * @deprecated Exists for Backward Compatibility reasons only. Use static::REQUEST_PARAMETER_ITEMS instead.
     */
    protected const REQUEST_PARAMETER_ID_SALES_ORDER_ITEM = 'id-sales-order-item';
    protected const REQUEST_PARAMETER_ID_SALES_ORDER = 'id-sales-order';
    protected const REQUEST_PARAMETER_ITEMS = 'items';
    protected const REQUEST_PARAMETER_EVENT = 'event';
    protected const REQUEST_PARAMETER_REDIRECT = 'redirect';

    protected const MESSAGE_STATUS_CHANGED_SUCCESSFULLY = 'Status change triggered successfully.';

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function triggerEventForOrderItemsAction(Request $request)
    {
        $idOrderItems = $request->query->get(static::REQUEST_PARAMETER_ITEMS);

        /**
         * Exists for Backward Compatibility reasons only.
         */
        $idOrderItem = $request->query->get(static::REQUEST_PARAMETER_ID_SALES_ORDER_ITEM);
        if ($idOrderItems === null && $idOrderItem !== null) {
            $idOrderItems = [$idOrderItem];
        }

        $event = $request->query->get(static::REQUEST_PARAMETER_EVENT);
        $redirect = $request->query->get(static::REQUEST_PARAMETER_REDIRECT, '/');

        $this->getFacade()->triggerEventForOrderItems($event, $idOrderItems);
        $this->addInfoMessage(static::MESSAGE_STATUS_CHANGED_SUCCESSFULLY);

        return $this->redirectResponse($redirect);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function triggerEventForOrderAction(Request $request)
    {
        $idOrder = $this->castId($request->query->getInt(static::REQUEST_PARAMETER_ID_SALES_ORDER));
        $event = $request->query->get(static::REQUEST_PARAMETER_EVENT);
        $redirect = $request->query->get(static::REQUEST_PARAMETER_REDIRECT, '/');
        $itemsList = $request->query->get(static::REQUEST_PARAMETER_ITEMS);

        $orderItems = $this->getOrderItemsToTriggerAction($idOrder, $itemsList);

        $this->getFacade()->triggerEvent($event, $orderItems, []);
        $this->addInfoMessage(static::MESSAGE_STATUS_CHANGED_SUCCESSFULLY);

        return $this->redirectResponse($redirect);
    }

    /**
     * @param int $idOrder
     * @param array|null $itemsList
     *
     * @return \Orm\Zed\Sales\Persistence\SpySalesOrderItem[]|\Propel\Runtime\Collection\ObjectCollection
     */
    protected function getOrderItemsToTriggerAction($idOrder, $itemsList = null)
    {
        $query = $this->getQueryContainer()->querySalesOrderItemsByIdOrder($idOrder);

        if (is_array($itemsList) && count($itemsList) > 0) {
            $query->filterByIdSalesOrderItem($itemsList, Criteria::IN);
        }

        $orderItems = $query->find();

        return $orderItems;
    }
}
