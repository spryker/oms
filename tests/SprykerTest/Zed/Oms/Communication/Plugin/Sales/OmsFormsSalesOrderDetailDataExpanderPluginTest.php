<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Oms\Communication\Plugin\Sales;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\OrderTransfer;
use Spryker\Zed\Kernel\Communication\AbstractCommunicationFactory;
use Spryker\Zed\Oms\Communication\Plugin\Sales\OmsFormsSalesOrderDetailDataExpanderPlugin;
use SprykerTest\Zed\Oms\OmsCommunicationTester;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Oms
 * @group Communication
 * @group Plugin
 * @group Sales
 * @group OmsFormsSalesOrderDetailDataExpanderPluginTest
 * Add your own group annotations below this line
 */
class OmsFormsSalesOrderDetailDataExpanderPluginTest extends Unit
{
    protected const string REDIRECT_URL = '/some/redirect/url';

    protected const string EVENT_NAME = 'pay';

    protected const string DEFAULT_OMS_PROCESS_NAME = 'Test01';

    protected OmsCommunicationTester $tester;

    public function getSalesOrderDetailDataExpanderPlugin(): OmsFormsSalesOrderDetailDataExpanderPlugin
    {
        return new OmsFormsSalesOrderDetailDataExpanderPlugin();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->setDependency(AbstractCommunicationFactory::FORM_FACTORY, $this->getFormFactoryMock());
    }

    public function testExpandDoesNotAddOmsTriggerFormCollectionWithoutRedirectUrl(): void
    {
        // Arrange
        $plugin = $this->getSalesOrderDetailDataExpanderPlugin();
        $orderTransfer = new OrderTransfer();

        // Act
        $result = $plugin->expand($orderTransfer, ['events' => [static::EVENT_NAME]]);

        // Assert
        $this->assertArrayNotHasKey('omsTriggerFormCollection', $result);
    }

    public function testExpandDoesNotAddOmsTriggerFormCollectionWithoutEvents(): void
    {
        // Arrange
        $plugin = $this->getSalesOrderDetailDataExpanderPlugin();
        $orderTransfer = new OrderTransfer();

        // Act
        $result = $plugin->expand($orderTransfer, ['changeStatusRedirectUrl' => static::REDIRECT_URL]);

        // Assert
        $this->assertArrayNotHasKey('omsTriggerFormCollection', $result);
    }

    public function testExpandDoesNotAddOmsTriggerFormCollectionWithEmptyEvents(): void
    {
        // Arrange
        $plugin = $this->getSalesOrderDetailDataExpanderPlugin();
        $orderTransfer = new OrderTransfer();

        // Act
        $result = $plugin->expand($orderTransfer, [
            'changeStatusRedirectUrl' => static::REDIRECT_URL,
            'events' => [],
        ]);

        // Assert
        $this->assertArrayNotHasKey('omsTriggerFormCollection', $result);
    }

    public function testExpandAddsOmsTriggerFormCollectionWhenBothRedirectUrlAndEventsArePresent(): void
    {
        // Arrange
        $this->tester->configureTestStateMachine([static::DEFAULT_OMS_PROCESS_NAME]);
        $saveOrderTransfer = $this->tester->haveOrder([], static::DEFAULT_OMS_PROCESS_NAME);

        $plugin = $this->getSalesOrderDetailDataExpanderPlugin();
        $orderTransfer = (new OrderTransfer())->setIdSalesOrder($saveOrderTransfer->getIdSalesOrder());

        // Act
        $result = $plugin->expand($orderTransfer, [
            'changeStatusRedirectUrl' => static::REDIRECT_URL,
            'events' => [static::EVENT_NAME],
        ]);

        // Assert
        $this->assertArrayHasKey('omsTriggerFormCollection', $result);
        $this->assertIsArray($result['omsTriggerFormCollection']);
        $this->assertArrayHasKey(static::EVENT_NAME, $result['omsTriggerFormCollection']);
    }

    protected function getFormFactoryMock(): FormFactoryInterface
    {
        $formMock = $this->getMockBuilder(FormInterface::class)->getMock();
        $formMock->method('createView')->willReturn(new FormView());

        $formFactoryMock = $this->getMockBuilder(FormFactoryInterface::class)->getMock();
        $formFactoryMock->method('create')->willReturn($formMock);
        $formFactoryMock->method('createNamed')->willReturn($formMock);

        return $formFactoryMock;
    }
}
