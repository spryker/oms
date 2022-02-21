<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\OrderStateMachine;

use LogicException;
use SimpleXMLElement;
use Spryker\Zed\Oms\Business\Exception\StatemachineException;
use Spryker\Zed\Oms\Business\Process\EventInterface;
use Spryker\Zed\Oms\Business\Process\ProcessInterface;
use Spryker\Zed\Oms\Business\Process\StateInterface;
use Spryker\Zed\Oms\Business\Process\TransitionInterface;
use Symfony\Component\Finder\Finder as SymfonyFinder;

class Builder implements BuilderInterface
{
    /**
     * @var \SimpleXMLElement
     */
    protected $rootElement;

    /**
     * @var array<\Spryker\Zed\Oms\Business\Process\ProcessInterface>
     */
    protected static $processBuffer = [];

    /**
     * @var \Spryker\Zed\Oms\Business\Process\EventInterface
     */
    protected $event;

    /**
     * @var \Spryker\Zed\Oms\Business\Process\StateInterface
     */
    protected $state;

    /**
     * @var \Spryker\Zed\Oms\Business\Process\TransitionInterface
     */
    protected $transition;

    /**
     * @var \Spryker\Zed\Oms\Business\Process\ProcessInterface
     */
    protected $process;

    /**
     * @var array|string
     */
    protected $processDefinitionLocation;

    /**
     * @var string
     */
    protected $subProcessPrefixDelimiter;

    /**
     * @param \Spryker\Zed\Oms\Business\Process\EventInterface $event
     * @param \Spryker\Zed\Oms\Business\Process\StateInterface $state
     * @param \Spryker\Zed\Oms\Business\Process\TransitionInterface $transition
     * @param \Spryker\Zed\Oms\Business\Process\ProcessInterface $process
     * @param array|string $processDefinitionLocation
     * @param string $subProcessPrefixDelimiter
     */
    public function __construct(
        EventInterface $event,
        StateInterface $state,
        TransitionInterface $transition,
        ProcessInterface $process,
        $processDefinitionLocation,
        $subProcessPrefixDelimiter = ' - '
    ) {
        $this->event = $event;
        $this->state = $state;
        $this->transition = $transition;
        $this->process = $process;
        $this->subProcessPrefixDelimiter = $subProcessPrefixDelimiter;

        $this->setProcessDefinitionLocation($processDefinitionLocation);
    }

    /**
     * @param string $processName
     *
     * @return \Spryker\Zed\Oms\Business\Process\ProcessInterface
     */
    public function createProcess($processName)
    {
        if (!isset(static::$processBuffer[$processName])) {
            $this->rootElement = $this->loadXmlFromProcessName($processName);

            $this->mergeSubProcessFiles();

            /** @var array<\Spryker\Zed\Oms\Business\Process\ProcessInterface> $processMap */
            $processMap = [];

            [$processMap, $mainProcess] = $this->createSubProcess($processMap);

            $stateToProcessMap = $this->createStates($processMap);

            $this->createSubProcesses($processMap);

            $eventMap = $this->createEvents();

            $this->createTransitions($stateToProcessMap, $processMap, $eventMap);

            static::$processBuffer[$processName] = $mainProcess;
        }

        return static::$processBuffer[$processName];
    }

    /**
     * @return void
     */
    protected function mergeSubProcessFiles()
    {
        foreach ($this->rootElement->children() as $xmlProcess) {
            $processFile = $this->getAttributeString($xmlProcess, 'file');
            $processName = $this->getAttributeString($xmlProcess, 'name');
            $processPrefix = $this->getAttributeString($xmlProcess, 'prefix');

            if ($processFile) {
                $xmlSubProcess = $this->loadXmlFromFileName(str_replace(' ', '_', $processFile));

                if ($processName) {
                    $xmlSubProcess->children()->process[0]['name'] = $processName;
                }

                $this->recursiveMerge($xmlSubProcess, $this->rootElement, $processPrefix);
            }
        }
    }

    /**
     * @param \SimpleXMLElement $fromXmlElement
     * @param \SimpleXMLElement $intoXmlNode
     * @param string|null $prefix
     *
     * @return void
     */
    protected function recursiveMerge($fromXmlElement, $intoXmlNode, $prefix = null)
    {
        /** @var array<\SimpleXMLElement> $xmlElements */
        $xmlElements = $fromXmlElement->children();
        if (!$xmlElements) {
            return;
        }

        foreach ($xmlElements as $xmlElement) {
            $xmlElement = $this->prefixSubProcessElementValue($xmlElement, $prefix);
            $xmlElement = $this->prefixSubProcessElementAttributes($xmlElement, $prefix);

            $child = $intoXmlNode->addChild($xmlElement->getName(), $xmlElement);
            $attributes = $xmlElement->attributes();
            foreach ($attributes as $k => $v) {
                $child->addAttribute($k, $v);
            }

            $this->recursiveMerge($xmlElement, $child, $prefix);
        }
    }

    /**
     * @param \SimpleXMLElement $xmlElement
     * @param string|null $prefix
     *
     * @return \SimpleXMLElement
     */
    protected function prefixSubProcessElementValue(SimpleXMLElement $xmlElement, $prefix = null)
    {
        if ($prefix === null) {
            return $xmlElement;
        }

        $namespaceDependentElementNames = ['source', 'target', 'event'];

        if (in_array($xmlElement->getName(), $namespaceDependentElementNames)) {
            $xmlElement[0] = $prefix . $this->subProcessPrefixDelimiter . $xmlElement[0];
        }

        return $xmlElement;
    }

    /**
     * @param \SimpleXMLElement $xmlElement
     * @param string|null $prefix
     *
     * @return \SimpleXMLElement
     */
    protected function prefixSubProcessElementAttributes(SimpleXMLElement $xmlElement, $prefix = null)
    {
        if ($prefix === null) {
            return $xmlElement;
        }

        $namespaceDependentElementNames = ['state', 'event'];

        if (in_array($xmlElement->getName(), $namespaceDependentElementNames)) {
            $xmlElement->attributes()['name'] = $prefix . $this->subProcessPrefixDelimiter . $xmlElement->attributes()['name'];
        }

        return $xmlElement;
    }

    /**
     * @param string $fileName
     *
     * @return \SimpleXMLElement
     */
    protected function loadXmlFromFileName($fileName)
    {
        $definitionFile = $this->locateProcessDefinition($fileName);

        return $this->loadXml($definitionFile->getContents());
    }

    /**
     * @param string $fileName
     *
     * @return \Symfony\Component\Finder\SplFileInfo
     */
    private function locateProcessDefinition($fileName)
    {
        $finder = $this->buildFinder($fileName);

        /** @phpstan-var \Symfony\Component\Finder\SplFileInfo */
        return current(iterator_to_array($finder->getIterator()));
    }

    /**
     * @param string $processName
     *
     * @return \SimpleXMLElement
     */
    protected function loadXmlFromProcessName($processName)
    {
        return $this->loadXmlFromFileName($processName . '.xml');
    }

    /**
     * @param string $xml
     *
     * @return \SimpleXMLElement
     */
    protected function loadXml($xml)
    {
        return new SimpleXMLElement($xml);
    }

    /**
     * @return array
     */
    protected function createEvents()
    {
        $eventMap = [];

        foreach ($this->rootElement as $xmlProcess) {
            if (!isset($xmlProcess->events)) {
                continue;
            }

            $xmlEvents = $xmlProcess->events->children();
            foreach ($xmlEvents as $xmlEvent) {
                $event = clone $this->event;
                $eventId = $this->getAttributeString($xmlEvent, 'name');
                $event->setCommand($this->getAttributeString($xmlEvent, 'command'));
                $event->setManual($this->getAttributeBoolean($xmlEvent, 'manual'));
                $event->setOnEnter($this->getAttributeBoolean($xmlEvent, 'onEnter'));
                $event->setTimeout($this->getAttributeString($xmlEvent, 'timeout'));
                $event->setTimeoutProcessor($this->getAttributeString($xmlEvent, 'timeoutProcessor'));
                if ($eventId === null) {
                    continue;
                }

                $event->setName($eventId);
                $eventMap[$event->getName()] = $event;
            }
        }

        return $eventMap;
    }

    /**
     * @param array<\Spryker\Zed\Oms\Business\Process\ProcessInterface> $processMap
     *
     * @return array
     */
    protected function createSubProcess(array $processMap)
    {
        $mainProcess = null;
        $xmlProcesses = $this->rootElement->children();

        /** @var \SimpleXMLElement $xmlProcess */
        foreach ($xmlProcesses as $xmlProcess) {
            $process = clone $this->process;
            $processName = $this->getAttributeString($xmlProcess, 'name');
            $process->setName($processName);
            $processMap[$processName] = $process;
            $process->setIsMain($this->getAttributeBoolean($xmlProcess, 'main'));

            $process->setFile($this->getAttributeString($xmlProcess, 'file'));

            if ($process->getIsMain()) {
                $mainProcess = $process;
            }
        }

        return [$processMap, $mainProcess];
    }

    /**
     * @param array<\Spryker\Zed\Oms\Business\Process\ProcessInterface> $processMap
     *
     * @return void
     */
    protected function createSubProcesses(array $processMap)
    {
        foreach ($this->rootElement as $xmlProcess) {
            $processName = $this->getAttributeString($xmlProcess, 'name');

            $process = $processMap[$processName];

            if ($xmlProcess->subprocesses) {
                $xmlSubProcesses = $xmlProcess->subprocesses->children();

                foreach ($xmlSubProcesses as $xmlSubProcess) {
                    $subProcessName = (string)$xmlSubProcess;
                    $subProcess = $processMap[$subProcessName];
                    $process->addSubProcess($subProcess);
                }
            }
        }
    }

    /**
     * @param array<\Spryker\Zed\Oms\Business\Process\ProcessInterface> $processMap
     *
     * @return array<\Spryker\Zed\Oms\Business\Process\ProcessInterface>
     */
    protected function createStates(array $processMap)
    {
        $stateToProcessMap = [];

        $xmlProcesses = $this->rootElement->children();
        foreach ($xmlProcesses as $xmlProcess) {
            $processName = $this->getAttributeString($xmlProcess, 'name');
            $process = $processMap[$processName];

            if ($xmlProcess->states) {
                $xmlStates = $xmlProcess->states->children();
                /** @var \SimpleXMLElement $xmlState */
                foreach ($xmlStates as $xmlState) {
                    $state = clone $this->state;
                    $state->setName($this->getAttributeString($xmlState, 'name'));
                    $state->setDisplay($this->getAttributeString($xmlState, 'display'));
                    $state->setReserved($this->getAttributeBoolean($xmlState, 'reserved'));
                    $state->setProcess($process);

                    /** @var array $stateFlag */
                    $stateFlag = $xmlState->flag;
                    if ($stateFlag) {
                        $flags = $xmlState->children();
                        foreach ($flags->flag as $flag) {
                            $state->addFlag((string)$flag);
                        }
                    }

                    $process->addState($state);
                    $stateToProcessMap[$state->getName()] = $process;
                }
            }
        }

        return $stateToProcessMap;
    }

    /**
     * @param array<\Spryker\Zed\Oms\Business\Process\ProcessInterface> $stateToProcessMap
     * @param array<\Spryker\Zed\Oms\Business\Process\ProcessInterface> $processMap
     * @param array<\Spryker\Zed\Oms\Business\Process\EventInterface> $eventMap
     *
     * @throws \LogicException
     *
     * @return void
     */
    protected function createTransitions(array $stateToProcessMap, array $processMap, array $eventMap)
    {
        foreach ($this->rootElement as $xmlProcess) {
            if ($xmlProcess->transitions) {
                $xmlTransitions = $xmlProcess->transitions->children();

                $processName = $this->getAttributeString($xmlProcess, 'name');

                foreach ($xmlTransitions as $xmlTransition) {
                    $transition = clone $this->transition;

                    $transition->setCondition($this->getAttributeString($xmlTransition, 'condition'));

                    $transition->setHappy($this->getAttributeBoolean($xmlTransition, 'happy'));

                    $sourceName = (string)$xmlTransition->source;

                    if (!isset($stateToProcessMap[$sourceName])) {
                        throw new LogicException(sprintf('Source: %s does not exist.', $sourceName));
                    }

                    $sourceProcess = $stateToProcessMap[$sourceName];
                    $sourceState = $sourceProcess->getState($sourceName);
                    $transition->setSource($sourceState);
                    $sourceState->addOutgoingTransition($transition);

                    $targetName = (string)$xmlTransition->target;

                    if (!isset($stateToProcessMap[$targetName])) {
                        throw new LogicException('Target: "' . $targetName . '" does not exist from source: "' . $sourceName . '"');
                    }
                    $targetProcess = $stateToProcessMap[$targetName];
                    $targetState = $targetProcess->getState($targetName);
                    $transition->setTarget($targetState);
                    $targetState->addIncomingTransition($transition);

                    if (isset($xmlTransition->event)) {
                        $eventId = (string)$xmlTransition->event;

                        if (!isset($eventMap[$eventId])) {
                            throw new LogicException('Event: "' . $eventId . '" does not exist from source: "' . $sourceName . '"');
                        }

                        $event = $eventMap[$eventId];
                        $event->addTransition($transition);
                        $transition->setEvent($event);
                    }

                    $processMap[$processName]->addTransition($transition);
                }
            }
        }
    }

    /**
     * @param \SimpleXMLElement $xmlElement
     * @param string $attributeName
     *
     * @return string|null
     */
    protected function getAttributeString(SimpleXMLElement $xmlElement, $attributeName)
    {
        $string = (string)$xmlElement->attributes()[$attributeName];
        $string = ($string === '') ? null : $string;

        return $string;
    }

    /**
     * @param \SimpleXMLElement $xmlElement
     * @param string $attributeName
     *
     * @return bool
     */
    protected function getAttributeBoolean(SimpleXMLElement $xmlElement, $attributeName)
    {
        return (string)$xmlElement->attributes()[$attributeName] === 'true';
    }

    /**
     * @param array|string|null $processDefinitionLocation
     *
     * @return void
     */
    private function setProcessDefinitionLocation($processDefinitionLocation)
    {
        $this->processDefinitionLocation = $processDefinitionLocation;
    }

    /**
     * @param string $fileName
     *
     * @return \Symfony\Component\Finder\Finder
     */
    protected function buildFinder($fileName)
    {
        $finder = $this->getFinder();
        $finder->in($this->processDefinitionLocation);
        if (strpos($fileName, '/') !== false) {
            $finder->path($this->createSubProcessPathPattern($fileName));
            $finder->name(basename($fileName));
        } else {
            $finder->name($fileName);
        }

        $this->validateFinder($finder, $fileName);

        return $finder;
    }

    /**
     * @return \Symfony\Component\Finder\Finder
     */
    protected function getFinder()
    {
        return new SymfonyFinder();
    }

    /**
     * @param \Symfony\Component\Finder\Finder $finder
     * @param string $fileName
     *
     * @throws \Spryker\Zed\Oms\Business\Exception\StatemachineException
     *
     * @return void
     */
    protected function validateFinder(SymfonyFinder $finder, $fileName)
    {
        if ($finder->count() > 1) {
            throw new StatemachineException(
                sprintf(
                    '"%s" found in more then one location. Could not determine which one to choose. Please check your process definition location',
                    $fileName,
                ),
            );
        }

        if ($finder->count() === 0) {
            throw new StatemachineException(
                sprintf(
                    'Could not find "%s". Please check your process definition location',
                    $fileName,
                ),
            );
        }
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    protected function createSubProcessPathPattern($fileName)
    {
        return '/\b' . preg_quote(dirname($fileName), '/') . '\b/';
    }
}
