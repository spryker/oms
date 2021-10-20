<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Communication\Controller;

use Spryker\Zed\Kernel\Communication\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method \Spryker\Zed\Oms\Communication\OmsCommunicationFactory getFactory()
 * @method \Spryker\Zed\Oms\Persistence\OmsQueryContainerInterface getQueryContainer()
 * @method \Spryker\Zed\Oms\Persistence\OmsRepositoryInterface getRepository()
 * @method \Spryker\Zed\Oms\Business\OmsFacadeInterface getFacade()
 */
class RenderFormController extends AbstractController
{
    /**
     * @var string
     */
    protected const KEY_ID_SALES_ORDER = 'idSalesOrder';

    /**
     * @var string
     */
    protected const KEY_EVENTS = 'events';

    /**
     * @var string
     */
    protected const KEY_REDIRECT_URL = 'redirectUrl';

    /**
     * @var string
     */
    protected const KEY_EVENTS_GROUPED_BY_ITEM = 'eventsGroupedByItem';

    /**
     * @var string
     */
    protected const KEY_ID_SALES_ORDER_ITEM = 'idSalesOrderItem';

    /**
     * @var string
     */
    protected const PARAM_SALES_ORDER_ITEM_IDS = 'salesOrderItemIds';

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array
     */
    public function orderAction(Request $request): array
    {
        $idSalesOrder = $request->attributes->get(static::KEY_ID_SALES_ORDER);
        $events = $request->attributes->get(static::KEY_EVENTS);
        $redirectUrl = $request->attributes->get(static::KEY_REDIRECT_URL);
        $orderOmsTriggerFormCollection = $this->getFactory()
            ->createOmsTriggerFormCollectionBuilder()
            ->buildOrderOmsTriggerFormCollection($redirectUrl, $events, $idSalesOrder);

        return $this->viewResponse([
            'formCollection' => $orderOmsTriggerFormCollection,
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array
     */
    public function orderItemAction(Request $request): array
    {
        $redirectUrl = $request->attributes->get(static::KEY_REDIRECT_URL);
        $eventsGroupedByItem = $request->attributes->get(static::KEY_EVENTS_GROUPED_BY_ITEM);
        $idSalesOrderItem = $request->attributes->get(static::KEY_ID_SALES_ORDER_ITEM);
        $orderItemOmsTriggerFormCollection = $this->getFactory()
            ->createOmsTriggerFormCollectionBuilder()
            ->buildOrderItemOmsTriggerFormCollection($redirectUrl, $eventsGroupedByItem, $idSalesOrderItem);

        return $this->viewResponse([
            'formCollection' => $orderItemOmsTriggerFormCollection,
        ]);
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return array
     */
    public function orderItemsAction(Request $request): array
    {
        $redirectUrl = $request->attributes->get(static::KEY_REDIRECT_URL);
        $events = $request->attributes->get(static::KEY_EVENTS);
        $salesOrderItemIds = $request->attributes->get(static::PARAM_SALES_ORDER_ITEM_IDS);

        $orderItemOmsTriggerFormCollection = $this->getFactory()
            ->createOmsTriggerFormCollectionBuilder()
            ->buildOrderItemsOmsTriggerFormCollection($redirectUrl, $events, $salesOrderItemIds);

        return $this->viewResponse([
            'formCollection' => $orderItemOmsTriggerFormCollection,
        ]);
    }
}
