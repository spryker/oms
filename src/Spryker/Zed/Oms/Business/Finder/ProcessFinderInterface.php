<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\Finder;

use Symfony\Component\Finder\SplFileInfo;

interface ProcessFinderInterface
{
    public function getProcessFilePath(string $processName): string;

    public function locateProcessDefinition(string $fileName): SplFileInfo;
}
