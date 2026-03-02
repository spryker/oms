<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\Reader;

use Spryker\Zed\Oms\Business\Process\ProcessInterface;

interface ProcessCacheReaderInterface
{
    public function hasProcess(string $processName): bool;

    public function getProcess(string $processName): ProcessInterface;

    public function getFullFilename(string $processName): string;
}
