<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\Process;

use Generated\Shared\Transfer\ProcessCriteriaTransfer;
use Generated\Shared\Transfer\ProcessDataTransfer;
use Spryker\Zed\Oms\Business\Finder\ProcessFinderInterface;
use Spryker\Zed\Oms\Dependency\Plugin\Command\CollectionInterface;
use Spryker\Zed\Oms\Dependency\Plugin\Command\CommandByOrderInterface;
use Spryker\Zed\Oms\Dependency\Plugin\Command\CommandCollectionInterface;
use Spryker\Zed\Oms\Dependency\Plugin\Condition\ConditionCollectionInterface;

class ProcessDataProvider implements ProcessDataProviderInterface
{
    public function __construct(
        protected ProcessFinderInterface $processFinder,
        protected CommandCollectionInterface|array $commands,
        protected ConditionCollectionInterface|array $conditions,
    ) {
    }

    public function getProcessData(ProcessCriteriaTransfer $processCriteriaTransfer): ProcessDataTransfer
    {
        return (new ProcessDataTransfer())
            ->setProcessFilePath($this->processFinder->getProcessFilePath((string)$processCriteriaTransfer->getProcessName()))
            ->setCommands($this->buildCommands())
            ->setConditions($this->buildConditions());
    }

    /**
     * @return array<string, string>
     */
    protected function buildConditions(): array
    {
        $conditions = $this->conditions instanceof CollectionInterface ? $this->conditions->getAll() : (array)$this->conditions;

        foreach ($conditions as $conditionName => $condition) {
            $conditions[$conditionName] = '';
        }

        return $conditions;
    }

    /**
     * @return array<string, string>
     */
    protected function buildCommands(): array
    {
        $commands = $this->commands instanceof CollectionInterface ? $this->commands->getAll() : (array)$this->commands;

        foreach ($commands as $commandName => $command) {
            $commands[$commandName] = $command instanceof CommandByOrderInterface ? '(by order)' : '(by item)';
        }

        return $commands;
    }
}
