<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\Util;

interface ActiveProcessFetcherInterface
{
    /**
     * @return \Spryker\Zed\Oms\Business\Process\StateInterface[]
     */
    public function getReservedStatesFromAllActiveProcesses(): array;

    /**
     * @return string[][]
     */
    public function getReservedStateNamesWithMainActiveProcessNames(): array;
}
