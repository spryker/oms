<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Communication\Builder;

interface OmsTriggerFormCollectionBuilderInterface
{
    /**
     * @param string $redirectUrl
     * @param array<string> $events
     * @param int $idSalesOrder
     *
     * @return array<\Symfony\Component\Form\FormView>
     */
    public function buildOrderOmsTriggerFormCollection(string $redirectUrl, array $events, int $idSalesOrder): array;

    /**
     * @param string $redirectUrl
     * @param array $eventsGroupedByItem
     * @param int $idSalesOrderItem
     *
     * @return array
     */
    public function buildOrderItemOmsTriggerFormCollection(string $redirectUrl, array $eventsGroupedByItem, int $idSalesOrderItem): array;

    /**
     * @param string $redirectUrl
     * @param array<string> $events
     * @param array<int> $salesOrderItemIds
     *
     * @return array
     */
    public function buildOrderItemsOmsTriggerFormCollection(string $redirectUrl, array $events, array $salesOrderItemIds): array;
}
