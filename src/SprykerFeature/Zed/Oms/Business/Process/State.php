<?php

namespace SprykerFeature\Zed\Oms\Business\Process;

use Exception;

class State implements StateInterface
{

    /** @var string */
    protected $name;

    /** @var string */
    protected $display;

    /** @var bool */
    protected $reserved;

    /** @var ProcessInterface */
    protected $process;

    /** @var array */
    protected $flags = array();

    /** @var TransitionInterface[] */
    protected $outgoingTransitions = array();

    /** @var TransitionInterface[] */
    protected $incomingTransitions = array();

    /**
     * @param TransitionInterface[] $incomingTransitions
     */
    public function setIncomingTransitions(array $incomingTransitions)
    {
        $this->incomingTransitions = $incomingTransitions;
    }

    /**
     * @return TransitionInterface[]
     */
    public function getIncomingTransitions()
    {
        return $this->incomingTransitions;
    }

    /**
     * @return bool
     */
    public function hasIncomingTransitions()
    {
        return !empty($this->incomingTransitions);
    }

    /**
     * @param TransitionInterface[] $outgoingTransitions
     */
    public function setOutgoingTransitions(array $outgoingTransitions)
    {
        $this->outgoingTransitions = $outgoingTransitions;
    }

    /**
     * @return TransitionInterface[]
     */
    public function getOutgoingTransitions()
    {
        return $this->outgoingTransitions;
    }

    /**
     * @return bool
     */
    public function hasOutgoingTransitions()
    {
        return !empty($this->outgoingTransitions);
    }

    /**
     * @param EventInterface $event
     *
     * @return TransitionInterface[]
     */
    public function getOutgoingTransitionsByEvent(EventInterface $event)
    {
        $transitions = array();
        foreach ($this->outgoingTransitions as $transition) {
            if ($transition->hasEvent()) {
                if ($transition->getEvent()->getName() === $event->getName()) {
                    $transitions[] = $transition;
                }
            }
        }

        return $transitions;
    }

    /**
     * @return EventInterface[]
     */
    public function getEvents()
    {
        $events = array();
        foreach ($this->outgoingTransitions as $transition) {
            if ($transition->hasEvent()) {
                $events[$transition->getEvent()->getName()] = $transition->getEvent();
            }
        }

        return $events;
    }

    /**
     * @param string $id
     *
     * @return EventInterface
     * @throws Exception
     */
    public function getEvent($id)
    {
        foreach ($this->outgoingTransitions as $transition) {
            if ($transition->hasEvent()) {
                $event = $transition->getEvent();
                if ($event->getName() === $id) {
                    return $event;
                }
            }
        }
        throw new Exception('Event '.$id.' not found.');
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function hasEvent($id)
    {
        foreach ($this->outgoingTransitions as $transition) {
            if ($transition->hasEvent()) {
                $event = $transition->getEvent();
                if ($event->getName() === $id) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function hasAnyEvent()
    {
        foreach ($this->outgoingTransitions as $transition) {
            if ($transition->hasEvent()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param TransitionInterface $transition
     */
    public function addIncomingTransition(TransitionInterface $transition)
    {
        $this->incomingTransitions[] = $transition;
    }

    /**
     * @param TransitionInterface $transition
     */
    public function addOutgoingTransition(TransitionInterface $transition)
    {
        $this->outgoingTransitions[] = $transition;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param ProcessInterface $process
     */
    public function setProcess($process)
    {
        $this->process = $process;
    }

    /**
     * @return ProcessInterface
     */
    public function getProcess()
    {
        return $this->process;
    }

    /**
     * @param bool $reserved
     */
    public function setReserved($reserved)
    {
        $this->reserved = $reserved;
    }

    /**
     * @return bool
     */
    public function isReserved()
    {
        return $this->reserved;
    }

    /**
     * @return bool
     */
    public function hasOnEnterEvent()
    {
        $transitions = $this->getOutgoingTransitions();
        foreach ($transitions as $transition) {
            if ($transition->hasEvent()) {
                if (true === $transition->getEvent()->isOnEnter()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return EventInterface
     *
     * @throws Exception
     */
    public function getOnEnterEvent()
    {
        $transitions = $this->getOutgoingTransitions();
        foreach ($transitions as $transition) {
            if ($transition->hasEvent()) {
                if (true === $transition->getEvent()->isOnEnter()) {
                    return $transition->getEvent();
                }
            }
        }
        throw new Exception('There is no onEnter event for state '.$this->getName());
    }

    /**
     * @return bool
     */
    public function hasTimeoutEvent()
    {
        $transitions = $this->getOutgoingTransitions();
        foreach ($transitions as $transition) {
            if ($transition->hasEvent()) {
                if (true === $transition->getEvent()->hasTimeout()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return EventInterface[]
     * @throws Exception
     */
    public function getTimeoutEvents()
    {
        $events = array();

        $transitions = $this->getOutgoingTransitions();
        foreach ($transitions as $transition) {
            if ($transition->hasEvent()) {
                if (true === $transition->getEvent()->hasTimeout()) {
                    $events[] = $transition->getEvent();
                }
            }
        }

        return $events;
    }

    /**
     * @param string $flag
     */
    public function addFlag($flag)
    {
        $this->flags[] = $flag;
    }

    /**
     * @param string $flag
     *
     * @return bool
     */
    public function hasFlag($flag)
    {
        return in_array($flag, $this->flags);
    }

    /**
     * @return bool
     */
    public function hasFlags()
    {
        return count($this->flags) > 0;
    }

    /**
     * @return array
     */
    public function getFlags()
    {
        return $this->flags;
    }

    /**
     * @return string
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * @param string $display
     */
    public function setDisplay($display)
    {
        $this->display = $display;
    }

}
