<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\Writer;

use Spryker\Zed\Oms\Business\Process\ProcessInterface;

interface ProcessCacheWriterInterface
{
    public function cacheProcess(ProcessInterface $process, ?string $processName = null): string;
}
