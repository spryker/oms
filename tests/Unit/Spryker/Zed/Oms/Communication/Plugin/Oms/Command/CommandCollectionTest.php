<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Unit\Spryker\Zed\Oms\Communication\Plugin\Oms\Command;

use Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandCollection;
use Spryker\Zed\Oms\Dependency\Plugin\Command\CommandCollectionInterface;
use Spryker\Zed\Oms\Dependency\Plugin\Command\CommandInterface;
use Spryker\Zed\Oms\Exception\CommandNotFoundException;

/**
 * @group Unit
 * @group Spryker
 * @group Zed
 * @group Oms
 * @group Communication
 * @group Plugin
 * @group Oms
 * @group Command
 * @group CommandCollectionTest
 */
class CommandCollectionTest extends \PHPUnit_Framework_TestCase
{

    const COMMAND_NAME = 'commandName';

    /**
     * @return void
     */
    public function testAddShouldReturnInstance()
    {
        $commandCollection = new CommandCollection();
        $result = $commandCollection->add($this->getCommandMock(), self::COMMAND_NAME);

        $this->assertInstanceOf(CommandCollectionInterface::class, $result);
    }

    /**
     * @return void
     */
    public function testHasShouldReturnFalse()
    {
        $commandCollection = new CommandCollection();

        $this->assertFalse($commandCollection->has(self::COMMAND_NAME));
    }

    /**
     * @return void
     */
    public function testHasShouldReturnTrue()
    {
        $commandCollection = new CommandCollection();
        $command = $this->getCommandMock();
        $commandCollection->add($command, self::COMMAND_NAME);

        $this->assertTrue($commandCollection->has(self::COMMAND_NAME));
    }

    /**
     * @return void
     */
    public function testGetShouldReturnCommand()
    {
        $commandCollection = new CommandCollection();
        $command = $this->getCommandMock();
        $commandCollection->add($command, self::COMMAND_NAME);

        $this->assertSame($command, $commandCollection->get(self::COMMAND_NAME));
    }

    /**
     * @return void
     */
    public function testGetShouldThrowException()
    {
        $commandCollection = new CommandCollection();

        $this->expectException(CommandNotFoundException::class);

        $commandCollection->get(self::COMMAND_NAME);
    }

    /**
     * @return void
     */
    public function testArrayAccess()
    {
        $commandCollection = new CommandCollection();
        $this->assertFalse(isset($commandCollection[self::COMMAND_NAME]));

        $condition = $this->getCommandMock();
        $commandCollection[self::COMMAND_NAME] = $condition;

        $this->assertTrue(isset($commandCollection[self::COMMAND_NAME]));
        $this->assertSame($condition, $commandCollection[self::COMMAND_NAME]);

        unset($commandCollection[self::COMMAND_NAME]);
        $this->assertFalse(isset($commandCollection[self::COMMAND_NAME]));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandInterface
     */
    private function getCommandMock()
    {
        return $this->getMockBuilder(CommandInterface::class)->getMock();
    }

}
