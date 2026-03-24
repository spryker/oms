<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Communication\Plugin\Sales;

use Generated\Shared\Transfer\OrderTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\SalesExtension\Dependency\Plugin\SalesOrderDetailDataExpanderPluginInterface;

/**
 * @method \Spryker\Zed\Oms\Communication\OmsCommunicationFactory getFactory()
 * @method \Spryker\Zed\Oms\Business\OmsFacadeInterface getFacade()
 * @method \Spryker\Zed\Oms\OmsConfig getConfig()
 * @method \Spryker\Zed\Oms\Persistence\OmsQueryContainerInterface getQueryContainer()
 */
class OmsFormsSalesOrderDetailDataExpanderPlugin extends AbstractPlugin implements SalesOrderDetailDataExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Expands order detail data with OMS trigger forms.
     * - Builds order-level OMS trigger form collection.
     * - Adds `omsTriggerFormCollection` for order-level forms.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\OrderTransfer $orderTransfer
     * @param array<string, mixed> $orderDetailData
     *
     * @return array<string, mixed>
     */
    public function expand(OrderTransfer $orderTransfer, array $orderDetailData): array
    {
        return $this->getFactory()->createOmsOrderDetailDataExpander()->expand($orderTransfer, $orderDetailData);
    }
}
