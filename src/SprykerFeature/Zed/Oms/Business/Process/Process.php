<?php

namespace SprykerFeature\Zed\Oms\Business\Process;

use SprykerFeature\Zed\Oms\Business\Util\DrawerInterface;
use Exception;

class Process implements ProcessInterface
{

    protected $name;

    /**
     * @var StateInterface[]
     */
    protected $states = array();

    /**
     * @var TransitionInterface[]
     */
    protected $transitions = array();

    protected $main;

    protected $file;

    /**
     * @var DrawerInterface
     */
    protected $drawer;

    /**
     * @var ProcessInterface[]
     */
    protected $subprocesses = array();

    /**
     * @param DrawerInterface $drawer
     */
    public function __construct(DrawerInterface $drawer)
    {
        $this->drawer = $drawer;
    }

    /**
     * @param bool $highlightState
     * @param string $format
     * @param int $fontsize
     *
     * @return bool
     */
    public function draw($highlightState = false, $format = null, $fontsize = null)
    {
        return $this->drawer->draw($this, $highlightState, $format, $fontsize);
    }

    /**
     * @param ProcessInterface[] $subprocesses
     */
    public function setSubprocesses($subprocesses)
    {
        $this->subprocesses = $subprocesses;
    }

    /**
     * @return ProcessInterface[]
     */
    public function getSubprocesses()
    {
        return $this->subprocesses;
    }

    /**
     * @return bool
     */
    public function hasSubprocesses()
    {
        return count($this->subprocesses) > 0;
    }

    /**
     * @param ProcessInterface $subprocess
     */
    public function addSubprocess(ProcessInterface $subprocess)
    {
        $this->subprocesses[] = $subprocess;
    }

    /**
     * @param mixed $main
     */
    public function setMain($main)
    {
        $this->main = $main;
    }

    /**
     * @return mixed
     */
    public function getMain()
    {
        return $this->main;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param StateInterface[] $states
     */
    public function setStates($states)
    {
        $this->states = $states;
    }

    /**
     * @param StateInterface $state
     */
    public function addState(StateInterface $state)
    {
        $this->states[$state->getName()] = $state;
    }

    /**
     * @param string $stateId
     *
     * @return StateInterface
     */
    public function getState($stateId)
    {
        return $this->states[$stateId];
    }

    /**
     * @param string $stateId
     *
     * @return bool
     */
    public function hasState($stateId)
    {
        return array_key_exists($stateId, $this->states);
    }

    /**
     * @param string $stateId
     *
     * @return StateInterface
     * @throws Exception
     */
    public function getStateFromAllProcesses($stateId)
    {
        $processes = $this->getAllProcesses();
        foreach ($processes as $process) {
            if ($process->hasState($stateId)) {
                return $process->getState($stateId);
            }
        }
        throw new Exception('Unknown state: ' . $stateId);
    }

    /**
     * @return StateInterface[]
     */
    public function getStates()
    {
        return $this->states;
    }

    /**
     * @return bool
     */
    public function hasStates()
    {
        return !empty($this->states);
    }

    /**
     * @param TransitionInterface $transition
     */
    public function addTransition(TransitionInterface $transition)
    {
        $this->transitions[] = $transition;
    }

    /**
     * @param TransitionInterface[] $transitions
     */
    public function setTransitions($transitions)
    {
        $this->transitions = $transitions;
    }

    /**
     * @return TransitionInterface[]
     */
    public function getTransitions()
    {
        return $this->transitions;
    }

    /**
     * @return bool
     */
    public function hasTransitions()
    {
        return !empty($this->transitions);
    }

    /**
     * @return StateInterface[]
     */
    public function getAllStates()
    {
        $states = array();
        if ($this->hasStates()) {
            $states = $this->getStates();
        }
        if ($this->hasSubprocesses()) {
            foreach ($this->getSubprocesses() as $subProcess) {
                if ($subProcess->hasStates()) {
                    $states = array_merge($states, $subProcess->getStates());
                }
            }
        }

        return $states;
    }

    /**
     * @return StateInterface[]
     */
    public function getAllReservedStates()
    {
        $reservedStates = array();
        $states = $this->getAllStates();
        foreach ($states as $state) {
            if ($state->isReserved()) {
                $reservedStates[] = $state;
            }
        }

        return $reservedStates;
    }

    /**
     * @return TransitionInterface[]
     */
    public function getAllTransitions()
    {
        $transitions = array();
        if ($this->hasTransitions()) {
            $transitions = $this->getTransitions();
        }
        foreach ($this->getSubprocesses() as $subProcess) {
            if ($subProcess->hasTransitions()) {
                $transitions = array_merge($transitions, $subProcess->getTransitions());
            }
        }

        return $transitions;
    }

    /**
     * @return TransitionInterface[]
     */
    public function getAllTransitionsWithoutEvent()
    {
        $transitions = array();
        $allTransitions = $this->getAllTransitions();
        foreach ($allTransitions as $transition) {
            if (false === $transition->hasEvent()) {
                $transitions[] = $transition;
            }
        }

        return $transitions;
    }

    /**
     * @return EventInterface[]
     */
    public function getManualEvents()
    {
        $manuallyExecuteableEventList = array();
        $transitions = $this->getAllTransitions();
        foreach ($transitions as $transition) {
            if ($transition->hasEvent()) {
                $event = $transition->getEvent();
                if ($event->isManual()) {
                    $manuallyExecuteableEventList[] = $event;
                }
            }
        }

        return $manuallyExecuteableEventList;
    }

    /**
     * @return EventInterface[]
     */
    public function getManualEventsBySource()
    {
        $events = $this->getManualEvents();

        $eventsBySource = array();
        foreach ($events as $event) {
            $transitions = $event->getTransitions();
            foreach ($transitions as $transition) {
                $sourceName = $transition->getSource()->getName();
                if (!isset($eventsBySource[$sourceName])) {
                    $eventsBySource[$sourceName] = array();
                }
                if (!in_array($event->getName(), $eventsBySource[$sourceName])) {
                    $eventsBySource[$sourceName][] = $event->getName();
                }
            }
        }

        return $eventsBySource;
    }

    /**
     * @return ProcessInterface[]
     */
    public function getAllProcesses()
    {
        $processes = array();
        $processes[] = $this;
        $processes = array_merge($processes, $this->getSubprocesses());

        return $processes;
    }
    /**
     * @param mixed $file
     */
    public function setFile($file)
    {
        $this->file = $file;
    }

    /**
     * @return bool
     */
    public function hasFile()
    {
       return isset($this->file);
    }

    /**
     * @return mixed
     */
    public function getFile()
    {
        return $this->file;
    }

}
