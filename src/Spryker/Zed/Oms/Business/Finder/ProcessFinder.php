<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\Finder;

use Spryker\Zed\Oms\Business\Exception\StatemachineException;
use Symfony\Component\Finder\Finder as SymfonyFinder;
use Symfony\Component\Finder\SplFileInfo;

class ProcessFinder implements ProcessFinderInterface
{
    public function __construct(
        protected array|string $processDefinitionLocation,
    ) {
    }

    public function getProcessFilePath(string $processName): string
    {
        $splFileInfo = $this->locateProcessDefinition($processName . '.xml');

        /** @phpstan-var \Symfony\Component\Finder\SplFileInfo */
        return $splFileInfo->getRealPath();
    }

    public function locateProcessDefinition(string $fileName): SplFileInfo
    {
        $finder = $this->buildFinder($fileName);

        $splFileInfo = current(iterator_to_array($finder->getIterator()));

        if (!$splFileInfo instanceof SplFileInfo) {
            throw new StatemachineException(sprintf('Could not locate process definition file "%s".', $fileName));
        }

        return $splFileInfo;
    }

    protected function buildFinder(string $fileName): SymfonyFinder
    {
        $finder = $this->getFinder();
        $finder->in($this->processDefinitionLocation);

        $finder->name($fileName);

        if (strpos($fileName, '/') !== false) {
            $finder->path($this->createSubProcessPathPattern($fileName));
            $finder->name(basename($fileName));
        }

        $this->validateFinder($finder, $fileName);

        return $finder;
    }

    protected function validateFinder(SymfonyFinder $finder, string $fileName): void
    {
        if ($finder->count() > 1) {
            $foundFiles = [];
            foreach ($finder->getIterator() as $file) {
                $foundFiles[] = $file->getRealPath() ?: $file->getPathname();
            }

            $locations = $foundFiles ? implode(', ', array_unique($foundFiles)) : 'multiple locations';

            throw new StatemachineException(
                sprintf(
                    '"%s" found in more than one location: %s. Could not determine which one to choose. Please check your process definition location',
                    $fileName,
                    $locations,
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

    protected function createSubProcessPathPattern(string $fileName): string
    {
        return '/\b' . preg_quote(dirname($fileName), '/') . '\b/';
    }

    protected function getFinder(): SymfonyFinder
    {
        return new SymfonyFinder();
    }
}
