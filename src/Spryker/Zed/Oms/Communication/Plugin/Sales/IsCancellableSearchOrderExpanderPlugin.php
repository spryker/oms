<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Communication\Plugin\Sales;

use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\SalesExtension\Dependency\Plugin\SearchOrderExpanderPluginInterface;

/**
 * @method \Spryker\Zed\Oms\Business\OmsFacadeInterface getFacade()
 * @method \Spryker\Zed\Oms\Persistence\OmsQueryContainerInterface getQueryContainer()
 * @method \Spryker\Zed\Oms\OmsConfig getConfig()
 * @method \Spryker\Zed\Oms\Communication\OmsCommunicationFactory getFactory()
 */
class IsCancellableSearchOrderExpanderPlugin extends AbstractPlugin implements SearchOrderExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Checks for cancellable flag for each order item.
     * - If all items are applicable for cancel, sets `Order::isCancellable=true`, false otherwise.
     *
     * @api
     *
     * @param array<\Generated\Shared\Transfer\OrderTransfer> $orderTransfers
     *
     * @return array<\Generated\Shared\Transfer\OrderTransfer>
     */
    public function expand(array $orderTransfers): array
    {
        return $this->getFacade()->setOrderIsCancellableByItemState($orderTransfers);
    }
}
