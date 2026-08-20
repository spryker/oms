<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerTest\Zed\Oms\Helper;

use Spryker\Zed\Oms\Business\OmsFacade;
use Spryker\Zed\Oms\OmsDependencyProvider;
use SprykerTest\Shared\Testify\Helper\AbstractHelper;
use SprykerTest\Shared\Testify\Helper\DependencyHelperTrait;
use SprykerTest\Zed\Testify\Helper\ResolvedBusinessFactoryTrait;

/**
 * Lets an OMS state-machine transition run on the host lane, where there is no Redis.
 *
 * The project locks OMS triggers through {@see \Spryker\Zed\Lock\Communication\Plugin\Oms\StorageRedisOmsLockPlugin},
 * so any resource whose request triggers a transition — placing an order, creating a return — dies
 * on the missing Redis connection. Dropping the plugin falls back to the OMS default, the
 * `spy_oms_state_machine_lock` table, so the transition itself still runs for real against the
 * database; only the lock storage changes.
 */
class OmsTriggerLockHelper extends AbstractHelper
{
    use DependencyHelperTrait;
    use ResolvedBusinessFactoryTrait;

    public function useDatabaseBackedOmsTriggerLock(): void
    {
        $this->getDependencyHelper()->setDependency(
            OmsDependencyProvider::PLUGIN_LOCK,
            null,
            $this->getBusinessFactoryClassNameFor(OmsFacade::class),
        );
    }
}
