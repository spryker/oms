<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\Util;

interface ActiveProcessFetcherInterface
{
    /**
     * @return array<\Spryker\Zed\Oms\Business\Process\StateInterface>
     */
    public function getReservedStatesFromAllActiveProcesses(): array;

    /**
     * @param string $processName
     *
     * @return array<string>
     */
    public function getReservedStateNamesForActiveProcessByProcessName(string $processName): array;
}
