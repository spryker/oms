<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Communication\Factory;

use Spryker\Zed\Oms\Communication\Form\DataProvider\OrderItemOmsTriggerFormDataProvider;
use Spryker\Zed\Oms\Communication\Form\DataProvider\OrderOmsTriggerFormDataProvider;
use Symfony\Component\Form\FormInterface;

interface OmsTriggerFormFactoryInterface
{
    /**
     * @return \Spryker\Zed\Oms\Communication\Form\DataProvider\OrderOmsTriggerFormDataProvider
     */
    public function createOrderOmsTriggerFormDataProvider(): OrderOmsTriggerFormDataProvider;

    /**
     * @return \Spryker\Zed\Oms\Communication\Form\DataProvider\OrderItemOmsTriggerFormDataProvider
     */
    public function createOrderItemOmsTriggerFormDataProvider(): OrderItemOmsTriggerFormDataProvider;

    /**
     * @param string $redirectUrl
     * @param string $event
     * @param int $idSalesOrder
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function getOrderOmsTriggerForm(string $redirectUrl, string $event, int $idSalesOrder): FormInterface;

    /**
     * @param string $redirectUrl
     * @param string $event
     * @param int $idSalesOrderItem
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function getOrderItemOmsTriggerForm(string $redirectUrl, string $event, int $idSalesOrderItem): FormInterface;

    /**
     * @param mixed|null $data
     * @param array<string, mixed> $options
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function createOmsTriggerForm($data = null, array $options = []): FormInterface;

    /**
     * @param string $redirectUrl
     * @param string $event
     * @param array<int> $salesOrderItemIds
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function getOrderItemsOmsTriggerForm(string $redirectUrl, string $event, array $salesOrderItemIds): FormInterface;
}
