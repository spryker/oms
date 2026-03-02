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
    /**
     * @var \Spryker\Zed\Oms\OmsConfig
     */
    protected OmsConfig $omsConfig;

    public function __construct(OmsConfig $omsConfig)
    {
        $this->omsConfig = $omsConfig;
    }

    public function hasProcess(string $processName): bool
    {
        $fullFilename = $this->getFullFilename($processName);

        return file_exists($fullFilename) && filesize($fullFilename) > 0;
    }

    public function getProcess(string $processName): ProcessInterface
    {
        /** @var \Spryker\Zed\Oms\Business\Process\ProcessInterface $process */
        $process = unserialize((string)file_get_contents($this->getFullFilename($processName)));

        return $process;
    }

    public function getFullFilename(string $processName): string
    {
        return sprintf('%s%s', $this->omsConfig->getProcessCachePath(), $processName);
    }
}
