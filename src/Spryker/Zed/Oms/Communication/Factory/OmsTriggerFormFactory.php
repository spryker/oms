<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Communication\Factory;

use Spryker\Zed\Oms\Communication\Form\DataProvider\OrderItemOmsTriggerFormDataProvider;
use Spryker\Zed\Oms\Communication\Form\DataProvider\OrderItemsOmsTriggerFormDataProvider;
use Spryker\Zed\Oms\Communication\Form\DataProvider\OrderOmsTriggerFormDataProvider;
use Spryker\Zed\Oms\Communication\Form\OmsTriggerForm;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class OmsTriggerFormFactory implements OmsTriggerFormFactoryInterface
{
    /**
     * @var \Symfony\Component\Form\FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @param \Symfony\Component\Form\FormFactoryInterface $formFactory
     */
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * @return \Spryker\Zed\Oms\Communication\Form\DataProvider\OrderOmsTriggerFormDataProvider
     */
    public function createOrderOmsTriggerFormDataProvider(): OrderOmsTriggerFormDataProvider
    {
        return new OrderOmsTriggerFormDataProvider();
    }

    /**
     * @return \Spryker\Zed\Oms\Communication\Form\DataProvider\OrderItemOmsTriggerFormDataProvider
     */
    public function createOrderItemOmsTriggerFormDataProvider(): OrderItemOmsTriggerFormDataProvider
    {
        return new OrderItemOmsTriggerFormDataProvider();
    }

    /**
     * @return \Spryker\Zed\Oms\Communication\Form\DataProvider\OrderItemsOmsTriggerFormDataProvider
     */
    public function createOrderItemsOmsTriggerFormDataProvider(): OrderItemsOmsTriggerFormDataProvider
    {
        return new OrderItemsOmsTriggerFormDataProvider();
    }

    /**
     * @param string $redirectUrl
     * @param string $event
     * @param array<int> $salesOrderItemIds
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function getOrderItemsOmsTriggerForm(string $redirectUrl, string $event, array $salesOrderItemIds): FormInterface
    {
        return $this->formFactory->create(
            OmsTriggerForm::class,
            null,
            $this->createOrderItemsOmsTriggerFormDataProvider()->getOptions($redirectUrl, $event, $salesOrderItemIds),
        );
    }

    /**
     * @param string $redirectUrl
     * @param string $event
     * @param int $idSalesOrder
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function getOrderOmsTriggerForm(string $redirectUrl, string $event, int $idSalesOrder): FormInterface
    {
        $options = $this->createOrderOmsTriggerFormDataProvider()
            ->getOptions($redirectUrl, $event, $idSalesOrder);

        return $this->formFactory->create(OmsTriggerForm::class, null, $options);
    }

    /**
     * @param string $redirectUrl
     * @param string $event
     * @param int $idSalesOrderItem
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function getOrderItemOmsTriggerForm(string $redirectUrl, string $event, int $idSalesOrderItem): FormInterface
    {
        $options = $this->createOrderItemOmsTriggerFormDataProvider()
            ->getOptions($redirectUrl, $event, $idSalesOrderItem);

        return $this->formFactory->create(OmsTriggerForm::class, null, $options);
    }

    /**
     * @param mixed|null $data
     * @param array<string, mixed> $options
     *
     * @return \Symfony\Component\Form\FormInterface
     */
    public function createOmsTriggerForm($data = null, array $options = []): FormInterface
    {
        return $this->formFactory->create(OmsTriggerForm::class, $data, $options);
    }
}
