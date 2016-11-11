<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Unit\Spryker\Zed\Oms\Business\Util;

use Spryker\Shared\Graph\GraphInterface;
use Spryker\Zed\Oms\Business\Util\Drawer;
use Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandCollection;
use Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandCollectionInterface;
use Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandInterface;
use Spryker\Zed\Oms\Communication\Plugin\Oms\Condition\ConditionCollection;
use Spryker\Zed\Oms\Communication\Plugin\Oms\Condition\ConditionCollectionInterface;
use Spryker\Zed\Oms\Communication\Plugin\Oms\Condition\ConditionInterface;
use Spryker\Zed\Oms\Dependency\Facade\OmsToUtilTextInterface;

/**
 * @group Unit
 * @group Spryker
 * @group Zed
 * @group Oms
 * @group Business
 * @group Util
 * @group DrawerTest
 */
class DrawerTest extends \PHPUnit_Framework_TestCase
{

    const CONDITION_NAME = 'conditionName';
    const COMMAND_NAME = 'commandName';

    /**
     * @return void
     */
    public function testInstantiationConditionsArrayShouldConvertedToCollection()
    {
        $drawer = new Drawer(
            [],
            [self::CONDITION_NAME => $this->getConditionMock()],
            $this->getGraphMock(),
            $this->getOmsToUtilTextFacadeMock()
        );
        $reflection = new \ReflectionClass(Drawer::class);
        $reflectionProperty = $reflection->getProperty('conditions');
        $reflectionProperty->setAccessible(true);
        $conditions = $reflectionProperty->getValue($drawer);

        $this->assertInstanceOf(ConditionCollectionInterface::class, $conditions);
        $this->assertInstanceOf(ConditionInterface::class, $conditions->get(self::CONDITION_NAME));
    }

    /**
     * @return void
     */
    public function testInstantiationWithConditionCollection()
    {
        $conditionCollection = new ConditionCollection();
        $conditionCollection->add($this->getConditionMock(), self::CONDITION_NAME);

        $drawer = new Drawer(
            [],
            $conditionCollection,
            $this->getGraphMock(),
            $this->getOmsToUtilTextFacadeMock()
        );
        $reflection = new \ReflectionClass(Drawer::class);
        $reflectionProperty = $reflection->getProperty('conditions');
        $reflectionProperty->setAccessible(true);
        $conditions = $reflectionProperty->getValue($drawer);

        $this->assertInstanceOf(ConditionCollectionInterface::class, $conditions);
        $this->assertInstanceOf(ConditionInterface::class, $conditions->get(self::CONDITION_NAME));
    }

    /**
     * @return void
     */
    public function testInstantiationCommandsArrayShouldConvertedToCollection()
    {
        $drawer = new Drawer(
            [self::COMMAND_NAME => $this->getCommandMock()],
            [],
            $this->getGraphMock(),
            $this->getOmsToUtilTextFacadeMock()
        );
        $reflection = new \ReflectionClass(Drawer::class);
        $reflectionProperty = $reflection->getProperty('commands');
        $reflectionProperty->setAccessible(true);
        $commands = $reflectionProperty->getValue($drawer);

        $this->assertInstanceOf(CommandCollectionInterface::class, $commands);
        $this->assertInstanceOf(CommandInterface::class, $commands->get(self::COMMAND_NAME));
    }

    /**
     * @return void
     */
    public function testInstantiationWithCommandCollection()
    {
        $commandCollection = new CommandCollection();
        $commandCollection->add($this->getCommandMock(), self::COMMAND_NAME);

        $drawer = new Drawer(
            $commandCollection,
            [],
            $this->getGraphMock(),
            $this->getOmsToUtilTextFacadeMock()
        );
        $reflection = new \ReflectionClass(Drawer::class);
        $reflectionProperty = $reflection->getProperty('commands');
        $reflectionProperty->setAccessible(true);
        $commands = $reflectionProperty->getValue($drawer);

        $this->assertInstanceOf(CommandCollectionInterface::class, $commands);
        $this->assertInstanceOf(CommandInterface::class, $commands->get(self::COMMAND_NAME));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Spryker\Zed\Oms\Communication\Plugin\Oms\Condition\ConditionInterface
     */
    private function getConditionMock()
    {
        return $this->getMockBuilder(ConditionInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandInterface
     */
    private function getCommandMock()
    {
        return $this->getMockBuilder(CommandInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Spryker\Shared\Graph\GraphInterface
     */
    private function getGraphMock()
    {
        return $this->getMockBuilder(GraphInterface::class)->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Spryker\Zed\Oms\Dependency\Facade\OmsToUtilTextInterface
     */
    private function getOmsToUtilTextFacadeMock()
    {
        return $this->getMockBuilder(OmsToUtilTextInterface::class)->getMock();
    }

}
