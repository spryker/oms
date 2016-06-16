<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Communication\Plugin\Oms\Command;

interface CommandCollectionInterface
{

    /**
     * @param \Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandInterface $command
     * @param string $name
     *
     * @return $this
     */
    public function add(CommandInterface $command, $name);

    /**
     * @param string $name
     *
     * @throws \Spryker\Zed\Oms\Exception\CommandNotFoundException
     *
     * @return \Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandInterface
     */
    public function get($name);

}
