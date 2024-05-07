<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\Writer;

use Spryker\Zed\Oms\Business\Process\ProcessInterface;
use Spryker\Zed\Oms\OmsConfig;

class ProcessCacheWriter implements ProcessCacheWriterInterface
{
    protected OmsConfig $omsConfig;

    /**
     * @param \Spryker\Zed\Oms\OmsConfig $omsConfig
     */
    public function __construct(OmsConfig $omsConfig)
    {
        $this->omsConfig = $omsConfig;
    }

    /**
     * @param string $processName
     *
     * @return bool
     */
    public function hasProcess(string $processName): bool
    {
        return file_exists($this->getFullFilename($processName));
    }

    /**
     * @param \Spryker\Zed\Oms\Business\Process\ProcessInterface $process
     * @param string|null $processName
     *
     * @return string
     */
    public function cacheProcess(ProcessInterface $process, ?string $processName = null): string
    {
        $this->createCacheDirectory();

        if (!$processName) {
            $processName = $process->getName();
        }

        file_put_contents($this->getFullFilename($processName), serialize($process));

        return $this->getFullFilename($processName);
    }

    /**
     * @param string $processName
     *
     * @return string
     */
    protected function getFullFilename(string $processName): string
    {
        return $this->omsConfig->getProcessCachePath() . $processName;
    }

    /**
     * @return void
     */
    protected function createCacheDirectory(): void
    {
        if (file_exists($this->omsConfig->getProcessCachePath())) {
            return;
        }

        mkdir($this->omsConfig->getProcessCachePath(), 0777, true);
    }
}
