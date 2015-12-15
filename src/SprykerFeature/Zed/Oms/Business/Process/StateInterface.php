<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace Spryker\Zed\Oms\Business\Process;

interface StateInterface
{

    /**
     * @param TransitionInterface[] $incomingTransitions
     *
     * @return void
     */
    public function setIncomingTransitions(array $incomingTransitions);

    /**
     * @return TransitionInterface[]
     */
    public function getIncomingTransitions();

    /**
     * @return bool
     */
    public function hasIncomingTransitions();

    /**
     * @param TransitionInterface[] $outgoingTransitions
     *
     * @return void
     */
    public function setOutgoingTransitions(array $outgoingTransitions);

    /**
     * @return TransitionInterface[]
     */
    public function getOutgoingTransitions();

    /**
     * @return bool
     */
    public function hasOutgoingTransitions();

    /**
     * @param EventInterface $event
     *
     * @return TransitionInterface[]
     */
    public function getOutgoingTransitionsByEvent(EventInterface $event);

    /**
     * @return EventInterface[]
     */
    public function getEvents();

    /**
     * @param string $id
     *
     * @throws \Exception
     *
     * @return EventInterface
     */
    public function getEvent($id);

    /**
     * @param string $id
     *
     * @return bool
     */
    public function hasEvent($id);

    /**
     * @return bool
     */
    public function hasAnyEvent();

    /**
     * @param TransitionInterface $transition
     *
     * @return void
     */
    public function addIncomingTransition(TransitionInterface $transition);

    /**
     * @param TransitionInterface $transition
     *
     * @return void
     */
    public function addOutgoingTransition(TransitionInterface $transition);

    /**
     * @param string $name
     *
     * @return void
     */
    public function setName($name);

    /**
     * @return string
     */
    public function getName();

    /**
     * @param ProcessInterface $process
     *
     * @return void
     */
    public function setProcess($process);

    /**
     * @return ProcessInterface
     */
    public function getProcess();

    /**
     * @param bool $reserved
     *
     * @return void
     */
    public function setReserved($reserved);

    /**
     * @return bool
     */
    public function isReserved();

    /**
     * @return bool
     */
    public function hasOnEnterEvent();

    /**
     * @throws \Exception
     *
     * @return EventInterface
     */
    public function getOnEnterEvent();

    /**
     * @return bool
     */
    public function hasTimeoutEvent();

    /**
     * @throws \Exception
     *
     * @return EventInterface[]
     */
    public function getTimeoutEvents();

    /**
     * @param string $flag
     *
     * @return void
     */
    public function addFlag($flag);

    /**
     * @param string $flag
     *
     * @return bool
     */
    public function hasFlag($flag);

    /**
     * @return bool
     */
    public function hasFlags();

    /**
     * @return array
     */
    public function getFlags();

    /**
     * @return string
     */
    public function getDisplay();

    /**
     * @param string $display
     *
     * @return void
     */
    public function setDisplay($display);

}
