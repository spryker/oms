<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\OrderStateMachine;

use DateTime;
use Exception;
use LogicException;
use Orm\Zed\Oms\Persistence\SpyOmsOrderItemState;
use Orm\Zed\Oms\Persistence\SpyOmsOrderItemStateQuery;
use Propel\Runtime\Propel;
use Spryker\Zed\Oms\Business\Process\ProcessInterface;
use Spryker\Zed\Oms\Business\Process\StateInterface;
use Spryker\Zed\Oms\Business\Util\ReadOnlyArrayObject;
use Spryker\Zed\Oms\Business\Util\TransitionLogInterface;
use Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandByItemInterface;
use Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandByOrderInterface;
use Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandCollection;
use Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandInterface;
use Spryker\Zed\Oms\Communication\Plugin\Oms\Condition\ConditionCollection;
use Spryker\Zed\Oms\Persistence\OmsQueryContainerInterface;

class OrderStateMachine implements OrderStateMachineInterface
{

    const BY_ITEM = 'byItem';
    const BY_ORDER = 'byOrder';
    const MAX_EVENT_REPEATS = 10;
    const MAX_ON_ENTER = 50;

    /**
     * @var array
     */
    protected $eventCounter = [];

    /**
     * @var array
     */
    protected $returnData = [];

    /**
     * @var array
     */
    protected $processBuffer = [];

    /**
     * @var array
     */
    protected $states = [];

    /**
     * @var \Spryker\Zed\Oms\Persistence\OmsQueryContainerInterface
     */
    protected $queryContainer;

    /**
     * @var \Spryker\Zed\Oms\Business\OrderStateMachine\TimeoutInterface
     */
    protected $timeout;

    /**
     * @var \Spryker\Zed\Oms\Business\OrderStateMachine\BuilderInterface
     */
    protected $builder;

    /**
     * @var \Spryker\Zed\Oms\Business\Util\TransitionLogInterface
     */
    protected $transitionLog;

    /**
     * @var \Spryker\Zed\Oms\Business\Util\ReadOnlyArrayObject
     */
    protected $activeProcesses;

    /**
     * @var \Spryker\Zed\Oms\Communication\Plugin\Oms\Condition\ConditionCollectionInterface
     */
    protected $conditions;

    /**
     * @var \Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandCollectionInterface
     */
    protected $commands;

    /**
     * @param \Spryker\Zed\Oms\Persistence\OmsQueryContainerInterface $queryContainer
     * @param \Spryker\Zed\Oms\Business\OrderStateMachine\BuilderInterface $builder
     * @param \Spryker\Zed\Oms\Business\Util\TransitionLogInterface $transitionLog
     * @param \Spryker\Zed\Oms\Business\OrderStateMachine\TimeoutInterface $timeout
     * @param \Spryker\Zed\Oms\Business\Util\ReadOnlyArrayObject $activeProcesses
     * @param \Spryker\Zed\Oms\Communication\Plugin\Oms\Condition\ConditionCollectionInterface|array $conditions
     * @param \Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandCollectionInterface|array $commands
     */
    public function __construct(
        OmsQueryContainerInterface $queryContainer,
        BuilderInterface $builder,
        TransitionLogInterface $transitionLog,
        TimeoutInterface $timeout,
        ReadOnlyArrayObject $activeProcesses,
        $conditions,
        $commands
    ) {
        $this->queryContainer = $queryContainer;
        $this->builder = $builder;
        $this->transitionLog = $transitionLog;
        $this->timeout = $timeout;
        $this->activeProcesses = $activeProcesses;
        $this->setConditions($conditions);
        $this->setCommands($commands);
    }

    /**
     * Converts array to collection for BC
     *
     * @param \Spryker\Zed\Oms\Communication\Plugin\Oms\Condition\ConditionCollectionInterface|array $conditions
     *
     * @return void
     */
    protected function setConditions($conditions)
    {
        $this->conditions = $conditions;

        if (is_array($conditions)) {
            $conditionCollection = new ConditionCollection();
            foreach ($conditions as $name => $condition) {
                $conditionCollection->add($condition, $name);
            }

            $this->conditions = $conditionCollection;
        }
    }

    /**
     * Converts array to collection for BC
     *
     * @param \Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandCollectionInterface|array $commands
     *
     * @return void
     */
    protected function setCommands($commands)
    {
        $this->commands = $commands;

        if (is_array($commands)) {
            $commandCollection = new CommandCollection();
            foreach ($commands as $name => $command) {
                $commandCollection->add($command, $name);
            }

            $this->commands = $commandCollection;
        }
    }

    /**
     * @param string $eventId
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem[] $orderItems
     * @param array|\Spryker\Zed\Oms\Business\Util\ReadOnlyArrayObject $data
     *
     * @return array
     */
    public function triggerEvent($eventId, array $orderItems, $data)
    {
        if ($this->checkForEventRepetitions($eventId) === false) {
            return [];
        }

        $data = $this->makeDataReadOnly($data);

        $processes = $this->getProcesses($orderItems);

        $orderItems = $this->filterAffectedOrderItems($eventId, $orderItems, $processes);

        $log = $this->initTransitionLog($orderItems);

        $orderGroup = $this->groupByOrderAndState($eventId, $orderItems, $processes);
        $sourceStateBuffer = [];
        foreach ($orderGroup as $groupedOrderItems) {
            $this->logSourceState($groupedOrderItems, $log);

            $this->runCommand($eventId, $groupedOrderItems, $processes, $data, $log);
            $sourceStateBuffer = $this->updateStateByEvent($eventId, $groupedOrderItems, $sourceStateBuffer, $log);
            $this->saveOrderItems($groupedOrderItems, $log, $processes, $sourceStateBuffer);
        }

        $orderItemsWithOnEnterEvent = $this->filterItemsWithOnEnterEvent($orderItems, $processes, $sourceStateBuffer);

        $log->saveAll();

        $this->triggerOnEnterEvents($orderItemsWithOnEnterEvent, $data);

        return $this->returnData;
    }

    /**
     * @param string $eventId
     * @param array $orderItemIds
     * @param array $data
     *
     * @return array
     */
    public function triggerEventForOrderItems($eventId, array $orderItemIds, $data)
    {
        $orderItems = $this->queryContainer
            ->querySalesOrderItems($orderItemIds)
            ->find()
            ->getData();

        return $this->triggerEvent($eventId, $orderItems, $data);
    }

    /**
     * @param string $eventId
     * @param int $orderItemId
     * @param array $data
     *
     * @return array
     */
    public function triggerEventForOneOrderItem($eventId, $orderItemId, $data)
    {
        $orderItems = $this->queryContainer
            ->querySalesOrderItems([$orderItemId])
            ->find()
            ->getData();

        return $this->triggerEvent($eventId, $orderItems, $data);
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem[] $orderItems
     * @param array $data
     *
     * @return array
     */
    public function triggerEventForNewItem(array $orderItems, $data)
    {
        $data = $this->makeDataReadOnly($data);
        $sourceStateBuffer = [];
        $processes = $this->getProcesses($orderItems);
        $orderItemsWithOnEnterEvent = $this->filterItemsWithOnEnterEvent($orderItems, $processes, $sourceStateBuffer);
        $this->triggerOnEnterEvents($orderItemsWithOnEnterEvent, $data);

        return $this->returnData;
    }

    /**
     * @param int[] $orderItemIds
     * @param array $data
     *
     * @return array
     */
    public function triggerEventForNewOrderItems(array $orderItemIds, $data)
    {
        $orderItems = $this->queryContainer
            ->querySalesOrderItems($orderItemIds)
            ->find()
            ->getData();

        return $this->triggerEventForNewItem($orderItems, $data);
    }

    /**
     * @param array $logContext
     *
     * @return int
     */
    public function checkConditions(array $logContext = [])
    {
        $affectedOrderItems = 0;
        foreach ($this->activeProcesses as $processName) {
            $process = $this->builder->createProcess($processName);
            $orderStateMachine = clone $this;
            $affectedOrderItems += $orderStateMachine->checkConditionsForProcess($process);
        }

        return $affectedOrderItems;
    }

    /**
     * @param \Spryker\Zed\Oms\Business\Process\ProcessInterface $process
     *
     * @return int
     */
    protected function checkConditionsForProcess(ProcessInterface $process)
    {
        $transitions = $process->getAllTransitionsWithoutEvent();

        $stateToTransitionsMap = $this->createStateToTransitionMap($transitions);

        $orderItems = $this->getOrderItemsByState(array_keys($stateToTransitionsMap), $process);

        $countAffectedItems = count($orderItems);

        if (count($orderItems) === 0) {
            return 0;
        }

        $log = $this->initTransitionLog($orderItems);

        $sourceStateBuffer = $this->updateStateByTransition($stateToTransitionsMap, $orderItems, [], $log);

        $processes = [$process->getName() => $process];

        $this->saveOrderItems($orderItems, $log, $processes, $sourceStateBuffer);

        $orderItemsWithOnEnterEvent = $this->filterItemsWithOnEnterEvent($orderItems, $processes, $sourceStateBuffer);

        $data = $this->makeDataReadOnly([]);

        $this->triggerOnEnterEvents($orderItemsWithOnEnterEvent, $data);

        return $countAffectedItems;
    }

    /**
     * @param \Spryker\Zed\Oms\Business\Process\TransitionInterface[] $transitions
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem $orderItem
     * @param \Spryker\Zed\Oms\Business\Process\StateInterface $sourceState
     * @param \Spryker\Zed\Oms\Business\Util\TransitionLogInterface $log
     *
     * @throws \Exception
     *
     * @return \Spryker\Zed\Oms\Business\Process\StateInterface
     */
    protected function checkCondition(array $transitions, $orderItem, StateInterface $sourceState, TransitionLogInterface $log)
    {
        $possibleTransitions = [];

        foreach ($transitions as $transition) {
            if ($transition->hasCondition()) {
                $conditionString = $transition->getCondition();
                $conditionModel = $this->getCondition($conditionString);

                try {
                    $conditionCheck = $conditionModel->check($orderItem);
                } catch (Exception $e) {
                    $log->setIsError(true);
                    $log->setErrorMessage(get_class($e) . ' - ' . $e->getMessage());
                    $log->saveAll();
                    throw $e;
                }

                if ($conditionCheck === true) {
                    $log->addCondition($orderItem, $conditionModel);
                    array_unshift($possibleTransitions, $transition);
                }
            } else {
                array_push($possibleTransitions, $transition);
            }
        }

        if (count($possibleTransitions) > 0) {
            /** @var \Spryker\Zed\Oms\Business\Process\TransitionInterface $selectedTransition */
            $selectedTransition = array_shift($possibleTransitions);
            $targetState = $selectedTransition->getTarget();
        } else {
            $targetState = $sourceState;
        }

        return $targetState;
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem[] $orderItems
     *
     * @return \Spryker\Zed\Oms\Business\Process\ProcessInterface[]
     */
    protected function getProcesses(array $orderItems)
    {
        $processes = [];
        foreach ($orderItems as $orderItem) {
            $processName = $orderItem->getProcess()->getName();
            if (array_key_exists($processName, $processes) === false) {
                $processes[$processName] = $this->builder->createProcess($processName);
            }
        }

        return $processes;
    }

    /**
     * Filters out all items that are not affected by the current event
     *
     * @param string $eventId
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem[] $orderItems
     * @param \Spryker\Zed\Oms\Business\Process\ProcessInterface[] $processes
     *
     * @return \Orm\Zed\Sales\Persistence\SpySalesOrderItem[]
     */
    protected function filterAffectedOrderItems($eventId, array $orderItems, $processes)
    {
        $orderItemsFiltered = [];
        foreach ($orderItems as $orderItem) {
            $stateId = $orderItem->getState()->getName();
            $processId = $orderItem->getProcess()->getName();
            $process = $processes[$processId];

            $state = $process->getStateFromAllProcesses($stateId);

            if ($state->hasEvent($eventId)) {
                $orderItemsFiltered[] = $orderItem;
            }
        }

        return $orderItemsFiltered;
    }

    /**
     * @param string $eventId
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem[] $orderItems
     * @param \Spryker\Zed\Oms\Business\Process\ProcessInterface[] $processes
     *
     * @return array
     */
    protected function groupByOrderAndState($eventId, array $orderItems, $processes)
    {
        $orderEventGroup = [];
        foreach ($orderItems as $orderItem) {
            $stateId = $orderItem->getState()->getName();
            $processId = $orderItem->getProcess()->getName();
            $process = $processes[$processId];
            $orderId = $orderItem->getOrder()->getIdSalesOrder();

            $state = $process->getStateFromAllProcesses($stateId);

            if ($state->hasEvent($eventId)) {
                $key = $orderId . '-' . $stateId;
                if (!isset($orderEventGroup[$key])) {
                    $orderEventGroup[$key] = [];
                }
                $orderEventGroup[$key][] = $orderItem;
            }
        }

        return $orderEventGroup;
    }

    /**
     * @param \Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandInterface $command
     *
     * @throws \LogicException
     *
     * @return string
     */
    protected function getCommandType(CommandInterface $command)
    {
        if ($command instanceof CommandByOrderInterface) {
            return self::BY_ORDER;
        }
        if ($command instanceof CommandByItemInterface) {
            return self::BY_ITEM;
        }
        throw new LogicException('Unknown type of command: ' . get_class($command));
    }

    /**
     * @param string $eventId
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem[] $orderItems
     * @param \Spryker\Zed\Oms\Business\Process\ProcessInterface[] $processes
     * @param \Spryker\Zed\Oms\Business\Util\ReadOnlyArrayObject $data
     * @param \Spryker\Zed\Oms\Business\Util\TransitionLogInterface $log
     *
     * @throws \Exception
     *
     * @return void
     */
    protected function runCommand($eventId, array $orderItems, array $processes, ReadOnlyArrayObject $data, TransitionLogInterface $log)
    {
        $orderEntity = current($orderItems)->getOrder();
        foreach ($orderItems as $orderItem) {
            $stateId = $orderItem->getState()->getName();
            $processId = $orderItem->getProcess()->getName();
            $process = $processes[$processId];
            $state = $process->getStateFromAllProcesses($stateId);
            $event = $state->getEvent($eventId);

            $log->setEvent($event);

            if (!$event->hasCommand()) {
                continue;
            }

            $command = $this->getCommand($event->getCommand());
            $type = $this->getCommandType($command);

            $log->addCommand($orderItem, $command);

            try {
                if ($type === self::BY_ITEM) {
                    $returnData = $command->run($orderItem, $data);
                    $this->returnData = array_merge($this->returnData, $returnData);
                } else {
                    $returnData = $command->run($orderItems, $orderEntity, $data);
                    if (is_array($returnData)) {
                        $this->returnData = array_merge($this->returnData, $returnData);
                    }
                    break;
                }
            } catch (Exception $e) {
                $log->setIsError(true);
                $log->setErrorMessage(get_class($e) . ' - ' . $e->getMessage());
                $log->saveAll();
                throw $e;
            }
        }
    }

    /**
     * @param string $eventId
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem[] $orderItems
     * @param array $sourceStateBuffer
     * @param \Spryker\Zed\Oms\Business\Util\TransitionLogInterface $log
     *
     * @return array
     */
    protected function updateStateByEvent($eventId, array $orderItems, array $sourceStateBuffer, TransitionLogInterface $log)
    {
        if ($sourceStateBuffer === null) {
            $sourceStateBuffer = [];
        }

        $targetStateMap = [];
        foreach ($orderItems as $i => $orderItem) {
            $stateId = $orderItem->getState()->getName();
            $sourceStateBuffer[$orderItem->getIdSalesOrderItem()] = $stateId;

            $process = $this->builder->createProcess($orderItem->getProcess()->getName());
            $sourceState = $process->getStateFromAllProcesses($stateId);

            $log->addSourceState($orderItem, $sourceState->getName());

            $targetState = $sourceState;
            if (isset($eventId) && $sourceState->hasEvent($eventId)) {
                $transitions = $sourceState->getEvent($eventId)->getTransitionsBySource($sourceState);
                $targetState = $this->checkCondition($transitions, $orderItem, $sourceState, $log);
                $log->addTargetState($orderItem, $targetState->getName());
            }

            $targetStateMap[$i] = $targetState->getName();
        }

        foreach ($orderItems as $i => $orderItem) {
            $this->setState($orderItems[$i], $targetStateMap[$i]);
        }

        return $sourceStateBuffer;
    }

    /**
     * @param array $stateToTransitionsMap
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem[] $orderItems
     * @param array $sourceStateBuffer
     * @param \Spryker\Zed\Oms\Business\Util\TransitionLogInterface $log
     *
     * @return array
     */
    protected function updateStateByTransition($stateToTransitionsMap, array $orderItems, array $sourceStateBuffer, TransitionLogInterface $log)
    {
        if ($sourceStateBuffer === null) {
            $sourceStateBuffer = [];
        }
        $targetStateMap = [];
        foreach ($orderItems as $i => $orderItem) {
            $stateId = $orderItem->getState()->getName();
            $sourceStateBuffer[$orderItem->getIdSalesOrderItem()] = $stateId;
            $process = $this->builder->createProcess($orderItem->getProcess()->getName());
            $sourceState = $process->getStateFromAllProcesses($stateId);

            $log->addSourceState($orderItem, $sourceState->getName());

            $transitions = $stateToTransitionsMap[$orderItem->getState()->getName()];

            $targetState = $sourceState;
            if (count($transitions) > 0) {
                $targetState = $this->checkCondition($transitions, $orderItem, $sourceState, $log);
            }

            $log->addTargetState($orderItem, $targetState->getName());

            $targetStateMap[$i] = $targetState->getName();
        }

        foreach ($orderItems as $i => $orderItem) {
            $this->setState($orderItems[$i], $targetStateMap[$i]);
        }

        return $sourceStateBuffer;
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem $orderItem
     * @param string $stateName
     *
     * @return void
     */
    protected function setState($orderItem, $stateName)
    {
        if (isset($this->states[$stateName])) {
            $state = $this->states[$stateName];
        } else {
            $state = SpyOmsOrderItemStateQuery::create()->findOneByName($stateName);
            if (!isset($state)) {
                $state = new SpyOmsOrderItemState();
                $state->setName($stateName);
                $state->save();
            }
            $this->states[$stateName] = $state;
        }
        $orderItem->setState($state);
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem[] $orderItems
     * @param \Spryker\Zed\Oms\Business\Process\ProcessInterface[] $processes
     * @param array $sourceStateBuffer
     *
     * @throws \LogicException
     *
     * @return array
     */
    protected function filterItemsWithOnEnterEvent(array $orderItems, array $processes, array $sourceStateBuffer)
    {
        $orderItemsWithOnEnterEvent = [];
        foreach ($orderItems as $orderItem) {
            $stateId = $orderItem->getState()->getName();
            $processId = $orderItem->getProcess()->getName();

            if (!isset($processes[$processId])) {
                throw new LogicException("Unknown process $processId");
            }

            $process = $processes[$processId];
            $targetState = $process->getStateFromAllProcesses($stateId);

            if (isset($sourceStateBuffer[$orderItem->getIdSalesOrderItem()])) {
                $sourceState = $sourceStateBuffer[$orderItem->getIdSalesOrderItem()];
            } else {
                $sourceState = $process->getStateFromAllProcesses($orderItem->getState()->getName());
            }

            if ($sourceState !== $targetState->getName()
                && $targetState->hasOnEnterEvent()
            ) {
                $event = $targetState->getOnEnterEvent();
                if (array_key_exists($event->getName(), $orderItemsWithOnEnterEvent) === false) {
                    $orderItemsWithOnEnterEvent[$event->getName()] = [];
                }
                $orderItemsWithOnEnterEvent[$event->getName()][] = $orderItem;
            }
        }

        return $orderItemsWithOnEnterEvent;
    }

    /**
     * @param array $data
     *
     * @return \Spryker\Zed\Oms\Business\Util\ReadOnlyArrayObject
     */
    protected function makeDataReadOnly($data)
    {
        if (is_array($data)) {
            $data = new ReadOnlyArrayObject($data);
        }

        return $data;
    }

    /**
     * To protect of loops, every event can only be used some times
     *
     * @param string $eventId
     *
     * @return bool
     */
    protected function checkForEventRepetitions($eventId)
    {
        if (array_key_exists($eventId, $this->eventCounter) === false) {
            $this->eventCounter[$eventId] = 0;
        }
        $this->eventCounter[$eventId]++;

        return $this->eventCounter[$eventId] < self::MAX_EVENT_REPEATS;
    }

    /**
     * @param array $orderItemsWithOnEnterEvent
     * @param \Spryker\Zed\Oms\Business\Util\ReadOnlyArrayObject $data
     *
     * @return void
     */
    protected function triggerOnEnterEvents(array $orderItemsWithOnEnterEvent, ReadOnlyArrayObject $data)
    {
        if (count($orderItemsWithOnEnterEvent) > 0) {
            foreach ($orderItemsWithOnEnterEvent as $eventId => $orderItems) {
                $this->triggerEvent($eventId, $orderItems, $data);
            }
        }
    }

    /**
     * @param \Spryker\Zed\Oms\Business\Process\TransitionInterface[] $transitions
     *
     * @return array
     */
    protected function createStateToTransitionMap(array $transitions)
    {
        $stateToTransitionsMap = [];
        foreach ($transitions as $transition) {
            $sourceId = $transition->getSource()->getName();
            if (array_key_exists($sourceId, $stateToTransitionsMap) === false) {
                $stateToTransitionsMap[$sourceId] = [];
            }
            $stateToTransitionsMap[$sourceId][] = $transition;
        }

        return $stateToTransitionsMap;
    }

    /**
     * @param array $states
     * @param \Spryker\Zed\Oms\Business\Process\ProcessInterface $process
     *
     * @return \Orm\Zed\Sales\Persistence\SpySalesOrderItem[]
     */
    protected function getOrderItemsByState(array $states, ProcessInterface $process)
    {
        return $this->queryContainer
            ->querySalesOrderItemsByState($states, $process->getName())
            ->find()
            ->getData();
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem[] $orderItems
     * @param \Spryker\Zed\Oms\Business\Util\TransitionLogInterface $log
     * @param \Spryker\Zed\Oms\Business\Process\ProcessInterface[] $processes
     * @param array $sourceStateBuffer
     *
     * @return void
     */
    protected function saveOrderItems(array $orderItems, TransitionLogInterface $log, array $processes, array $sourceStateBuffer)
    {
        $connection = Propel::getConnection();
        $connection->beginTransaction();

        $currentTime = new DateTime('now');

        $timeoutModel = clone $this->timeout;

        foreach ($orderItems as $orderItem) {
            $process = $processes[$orderItem->getProcess()->getName()];

            $sourceState = $sourceStateBuffer[$orderItem->getIdSalesOrderItem()];
            $targetState = $orderItem->getState()->getName();

            if ($sourceState !== $targetState) {
                $timeoutModel->dropOldTimeout($process, $sourceState, $orderItem);
                $timeoutModel->setNewTimeout($process, $orderItem, $currentTime);
            }

            if ($orderItem->isModified()) {
                $orderItem->save();
                $log->save($orderItem);
            }
        }

        $connection->commit();
    }

    /**
     * @param string $command
     *
     * @throws \LogicException
     *
     * @return \Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandByOrderInterface|\Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandByItemInterface
     */
    protected function getCommand($command)
    {
        return $this->commands->get($command);
    }

    /**
     * @param string $condition
     *
     * @return \Spryker\Zed\Oms\Communication\Plugin\Oms\Condition\ConditionInterface
     */
    protected function getCondition($condition)
    {
        return $this->conditions->get($condition);
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem[] $orderItems
     *
     * @return \Spryker\Zed\Oms\Business\Util\TransitionLogInterface
     */
    protected function initTransitionLog(array $orderItems)
    {
        $log = clone $this->transitionLog;

        $log->init($orderItems);

        return $log;
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem[] $orderItems
     * @param \Spryker\Zed\Oms\Business\Util\TransitionLogInterface $log
     *
     * @return void
     */
    protected function logSourceState($orderItems, TransitionLogInterface $log)
    {
        foreach ($orderItems as $orderItem) {
            $stateName = $orderItem->getState()->getName();
            $log->addSourceState($orderItem, $stateName);
        }
    }

}
