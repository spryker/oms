<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace Spryker\Zed\Oms\Business\Process;

use Spryker\Zed\Oms\Business\Util\DrawerInterface;
use Exception;

class Process implements ProcessInterface
{

    protected $name;

    /**
     * @var StateInterface[]
     */
    protected $states = [];

    /**
     * @var TransitionInterface[]
     */
    protected $transitions = [];

    /**
     * @var bool
     */
    protected $main;

    /**
     * @var string
     */
    protected $file;

    /**
     * @var \Spryker\Zed\Oms\Business\Util\DrawerInterface
     */
    protected $drawer;

    /**
     * @var ProcessInterface[]
     */
    protected $subProcesses = [];

    /**
     * @param \Spryker\Zed\Oms\Business\Util\DrawerInterface $drawer
     */
    public function __construct(DrawerInterface $drawer)
    {
        $this->drawer = $drawer;
    }

    /**
     * @param string|null $highlightState
     * @param string|null $format
     * @param int|null $fontSize
     *
     * @return bool
     */
    public function draw($highlightState = null, $format = null, $fontSize = null)
    {
        return $this->drawer->draw($this, $highlightState, $format, $fontSize);
    }

    /**
     * @param ProcessInterface[] $subProcesses
     *
     * @return void
     */
    public function setSubProcesses($subProcesses)
    {
        $this->subProcesses = $subProcesses;
    }

    /**
     * @return ProcessInterface[]
     */
    public function getSubProcesses()
    {
        return $this->subProcesses;
    }

    /**
     * @return bool
     */
    public function hasSubProcesses()
    {
        return count($this->subProcesses) > 0;
    }

    /**
     * @param ProcessInterface $subProcess
     *
     * @return void
     */
    public function addSubProcess(ProcessInterface $subProcess)
    {
        $this->subProcesses[] = $subProcess;
    }

    /**
     * @param mixed $main
     *
     * @return void
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
     *
     * @return void
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
     *
     * @return void
     */
    public function setStates($states)
    {
        $this->states = $states;
    }

    /**
     * @param StateInterface $state
     *
     * @return void
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
     * @throws Exception
     *
     * @return StateInterface
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
     *
     * @return void
     */
    public function addTransition(TransitionInterface $transition)
    {
        $this->transitions[] = $transition;
    }

    /**
     * @param TransitionInterface[] $transitions
     *
     * @return void
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
        $states = [];
        if ($this->hasStates()) {
            $states = $this->getStates();
        }
        if ($this->hasSubProcesses()) {
            foreach ($this->getSubProcesses() as $subProcess) {
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
        $reservedStates = [];
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
        $transitions = [];
        if ($this->hasTransitions()) {
            $transitions = $this->getTransitions();
        }
        foreach ($this->getSubProcesses() as $subProcess) {
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
        $transitions = [];
        $allTransitions = $this->getAllTransitions();
        foreach ($allTransitions as $transition) {
            if ($transition->hasEvent() === false) {
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
        $manuallyExecutableEventList = [];
        $transitions = $this->getAllTransitions();
        foreach ($transitions as $transition) {
            if ($transition->hasEvent()) {
                $event = $transition->getEvent();
                if ($event->isManual()) {
                    $manuallyExecutableEventList[] = $event;
                }
            }
        }

        return $manuallyExecutableEventList;
    }

    /**
     * @return EventInterface[]
     */
    public function getManualEventsBySource()
    {
        $events = $this->getManualEvents();

        $eventsBySource = [];
        foreach ($events as $event) {
            $transitions = $event->getTransitions();
            foreach ($transitions as $transition) {
                $sourceName = $transition->getSource()->getName();
                if (!isset($eventsBySource[$sourceName])) {
                    $eventsBySource[$sourceName] = [];
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
        $processes = [];
        $processes[] = $this;
        $processes = array_merge($processes, $this->getSubProcesses());

        return $processes;
    }

    /**
     * @param mixed $file
     *
     * @return void
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
