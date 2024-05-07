<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\Reader;

use Spryker\Zed\Oms\Business\Process\ProcessInterface;
use Spryker\Zed\Oms\OmsConfig;

class ProcessCacheReader implements ProcessCacheReaderInterface
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
     * @param string $processName
     *
     * @return \Spryker\Zed\Oms\Business\Process\ProcessInterface
     */
    public function getProcess(string $processName): ProcessInterface
    {
        /** @var \Spryker\Zed\Oms\Business\Process\ProcessInterface $process */
        $process = unserialize(file_get_contents($this->getFullFilename($processName)));

        return $process;
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
}
