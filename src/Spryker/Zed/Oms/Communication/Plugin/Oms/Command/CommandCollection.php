<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Communication\Plugin\Oms\Command;

use Spryker\Zed\Oms\Exception\CommandNotFoundException;

class CommandCollection implements CommandCollectionInterface, \ArrayAccess
{

    /**
     * @var \Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandInterface[]
     */
    protected $commands = [];

    /**
     * @param \Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandInterface $command
     * @param string $name
     *
     * @return $this
     */
    public function add(CommandInterface $command, $name)
    {
        $this->commands[$name] = $command;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return isset($this->commands[$name]);
    }

    /**
     * @param string $name
     *
     * @throws \Spryker\Zed\Oms\Exception\CommandNotFoundException
     *
     * @return \Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandInterface
     */
    public function get($name)
    {
        if (empty($this->commands[$name])) {
            throw new CommandNotFoundException(
                sprintf('Could not find command "%s". You need to add the needed commands within your DependencyInjector.', $name)
            );
        }

        return $this->commands[$name];
    }

    /**
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * @param string $offset
     *
     * @return \Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandInterface
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @param string $offset
     * @param \Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandInterface $value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->add($value, $offset);
    }

    /**
     * @param string $offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->commands[$offset]);
    }

}
