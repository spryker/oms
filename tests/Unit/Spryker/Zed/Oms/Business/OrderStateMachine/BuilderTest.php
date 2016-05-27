<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Unit\Spryker\Zed\Oms\Business\OrderStateMachine;

use Spryker\Zed\Oms\Business\Exception\StatemachineException;
use Spryker\Zed\Oms\Business\OrderStateMachine\Builder;
use Spryker\Zed\Oms\Business\Process\EventInterface;
use Spryker\Zed\Oms\Business\Process\Process;
use Spryker\Zed\Oms\Business\Process\ProcessInterface;
use Spryker\Zed\Oms\Business\Process\StateInterface;
use Spryker\Zed\Oms\Business\Process\TransitionInterface;
use Spryker\Zed\Oms\Business\Util\DrawerInterface;

/**
 * @group Spryker
 * @group Zed
 * @group Oms
 * @group Business
 * @group Builder
 */
class BuilderTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @return void
     */
    public function tearDown()
    {
        $processACopyTarget = $this->getProcessLocationB() . DIRECTORY_SEPARATOR . 'process-a.xml';
        if (file_exists($processACopyTarget)) {
            unlink($processACopyTarget);
        }
    }

    /**
     * This test can be removed when optional argument `$processDefinitionLocation` is mandatory
     *
     * @return void
     */
    public function testInstantiationWithoutXmlFolder()
    {
        $eventMock = $this->getEventMock();
        $stateMock = $this->getStateMock();
        $transitionMock = $this->getTransitionMock();
        $process = $this->getProcess();
        $builder = new Builder($eventMock, $stateMock, $transitionMock, $process, null);

        $this->assertInstanceOf(Builder::class, $builder);
    }

    /**
     * @return void
     */
    public function testInstantiationWithXmlFolderAsString()
    {
        $eventMock = $this->getEventMock();
        $stateMock = $this->getStateMock();
        $transitionMock = $this->getTransitionMock();
        $process = $this->getProcess();
        $builder = new Builder($eventMock, $stateMock, $transitionMock, $process, '');

        $this->assertInstanceOf(Builder::class, $builder);
    }

    /**
     * @return void
     */
    public function testInstantiationWithXmlFolderAsArray()
    {
        $eventMock = $this->getEventMock();
        $stateMock = $this->getStateMock();
        $transitionMock = $this->getTransitionMock();
        $process = $this->getProcess();
        $builder = new Builder($eventMock, $stateMock, $transitionMock, $process, []);

        $this->assertInstanceOf(Builder::class, $builder);
    }

    /**
     * @return void
     */
    public function testGetProcessShouldThrowExceptionWhenProcessFoundInMoreThenOneLocation()
    {
        $eventMock = $this->getEventMock();
        $stateMock = $this->getStateMock();
        $transitionMock = $this->getTransitionMock();
        $process = $this->getProcess();
        $builder = new Builder($eventMock, $stateMock, $transitionMock, $process, [$this->getProcessLocationA(), $this->getProcessLocationB()]);

        $processACopyTarget = $this->getProcessLocationB() . DIRECTORY_SEPARATOR . 'process-a.xml';
        copy($this->getProcessLocationA() . DIRECTORY_SEPARATOR . 'process-a.xml', $processACopyTarget);
        $this->setExpectedException(StatemachineException::class, '"process-a.xml" found in more then one location. Could not determine which one to choose. Please check your process definition location');
        $builder->createProcess('process-a');
    }

    /**
     * @return void
     */
    public function testGetProcessShouldThrowExceptionWhenNoProcessFound()
    {
        $eventMock = $this->getEventMock();
        $stateMock = $this->getStateMock();
        $transitionMock = $this->getTransitionMock();
        $process = $this->getProcess();
        $builder = new Builder($eventMock, $stateMock, $transitionMock, $process, [$this->getProcessLocationB()]);

        $this->setExpectedException(StatemachineException::class, 'Could not find "process-a.xml". Please check your process definition location');
        $builder->createProcess('process-a');
    }

    /**
     * @return void
     */
    public function testGetProcess()
    {
        $eventMock = $this->getEventMock();
        $stateMock = $this->getStateMock();
        $transitionMock = $this->getTransitionMock();
        $process = $this->getProcess();

        $builder = new Builder($eventMock, $stateMock, $transitionMock, $process, [$this->getProcessLocationA(), $this->getProcessLocationB()]);

        $result = $builder->createProcess('process-a');
        $this->assertInstanceOf(ProcessInterface::class, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Spryker\Zed\Oms\Business\Process\EventInterface
     */
    private function getEventMock()
    {
        return $this->getMock(EventInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Spryker\Zed\Oms\Business\Process\StateInterface
     */
    private function getStateMock()
    {
        return $this->getMock(StateInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Spryker\Zed\Oms\Business\Process\TransitionInterface
     */
    private function getTransitionMock()
    {
        return $this->getMock(TransitionInterface::class);
    }

    /**
     * @return \Spryker\Zed\Oms\Business\Process\ProcessInterface
     */
    private function getProcess()
    {
        $drawerMock = $this->getDrawerMock();

        return new Process($drawerMock);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|\Spryker\Zed\Oms\Business\Util\DrawerInterface
     */
    private function getDrawerMock()
    {
        return $this->getMock(DrawerInterface::class);
    }

    /**
     * @return string
     */
    private function getProcessLocationA()
    {
        return __DIR__ . '/Builder/Fixtures/DefinitionLocationA';
    }

    /**
     * @return string
     */
    private function getProcessLocationB()
    {
        return __DIR__ . '/Builder/Fixtures/DefinitionLocationB';
    }

}
