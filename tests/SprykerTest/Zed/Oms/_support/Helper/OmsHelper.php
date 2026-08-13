<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

declare(strict_types = 1);

namespace SprykerTest\Zed\Oms\Helper;

use Codeception\Module;
use Codeception\TestInterface;
use DateInterval;
use Generated\Shared\DataBuilder\OmsEventTriggerResponseBuilder;
use Generated\Shared\DataBuilder\OmsProductReservationBuilder;
use Generated\Shared\Transfer\OmsEventTriggerResponseTransfer;
use Generated\Shared\Transfer\OmsOrderItemStateTransfer;
use Generated\Shared\Transfer\OmsProductReservationTransfer;
use Orm\Zed\Oms\Persistence\Map\SpyOmsOrderItemStateTableMap;
use Orm\Zed\Oms\Persistence\SpyOmsEventTimeout;
use Orm\Zed\Oms\Persistence\SpyOmsEventTimeoutQuery;
use Orm\Zed\Oms\Persistence\SpyOmsOrderItemState;
use Orm\Zed\Oms\Persistence\SpyOmsOrderItemStateQuery;
use Orm\Zed\Oms\Persistence\SpyOmsProductReservation;
use Orm\Zed\Sales\Persistence\Map\SpySalesOrderItemTableMap;
use Orm\Zed\Sales\Persistence\SpySalesOrderItemQuery;
use ReflectionProperty;
use Spryker\Shared\Oms\OmsConstants;
use Spryker\Zed\Oms\Business\OmsFacade;
use Spryker\Zed\Oms\Business\OmsFacadeInterface;
use Spryker\Zed\Oms\Business\OrderStateMachine\PersistenceManager;
use Spryker\Zed\Oms\Communication\Plugin\Oms\Command\CommandCollection;
use Spryker\Zed\Oms\Communication\Plugin\Oms\Condition\ConditionCollection;
use Spryker\Zed\Oms\OmsDependencyProvider;
use SprykerTest\Shared\Testify\Helper\ConfigHelper;
use SprykerTest\Shared\Testify\Helper\ConfigHelperTrait;
use SprykerTest\Shared\Testify\Helper\DataCleanupHelperTrait;
use SprykerTest\Zed\Testify\Helper\Business\DependencyProviderHelperTrait;

class OmsHelper extends Module
{
    use DataCleanupHelperTrait;
    use ConfigHelperTrait;
    use DependencyProviderHelperTrait;

    /**
     * @var string
     */
    protected const CONDITION_PLUGINS = 'conditions';

    /**
     * @var string
     */
    protected const COMMAND_PLUGINS = 'commands';

    /**
     * @var array<string, mixed>
     */
    protected array $config = [
        self::COMMAND_PLUGINS => [],
        self::CONDITION_PLUGINS => [],
    ];

    public function _before(TestInterface $test): void
    {
        parent::_before($test);

        $this->reloadCommands();
        $this->reloadConditions();
        $this->disableProcessCache();
    }

    protected function reloadCommands(): void
    {
        if ($this->config[static::COMMAND_PLUGINS] === []) {
            return;
        }

        $commandCollection = new CommandCollection();

        foreach ($this->config[static::COMMAND_PLUGINS] as $commandName => $commandPlugin) {
            $commandCollection->add(new $commandPlugin(), $commandName);
        }

        // CommandPlugins provided in the codeception.yml where the OmsHelper is enabled.
        // You can use `\SprykerTest\Zed\Oms\Helper\Mock\AlwaysTrueConditionPluginMock` and `\SprykerTest\Zed\Oms\Helper\Mock\AlwaysFalseConditionPluginMock` for testing purposes.
        $this->getDependencyProviderHelper()->setDependency(
            OmsDependencyProvider::COMMAND_PLUGINS,
            $commandCollection,
        );
    }

    protected function reloadConditions(): void
    {
        if ($this->config[static::CONDITION_PLUGINS] === []) {
            return;
        }

        $conditionCollection = new ConditionCollection();

        foreach ($this->config[static::CONDITION_PLUGINS] as $conditionName => $conditionPlugin) {
            $conditionCollection->add(new $conditionPlugin(), $conditionName);
        }

        // ConditionPlugins provided in the codeception.yml where the OmsHelper is enabled.
        // You can use `\SprykerTest\Zed\Oms\Helper\Mock\CommandByOrderPluginMock` and `\SprykerTest\Zed\Oms\Helper\Mock\CommandByItemPluginMock` for testing purposes.
        $this->getDependencyProviderHelper()->setDependency(
            OmsDependencyProvider::CONDITION_PLUGINS,
            $conditionCollection,
        );
    }

    /**
     * Disable the process cache to avoid caching issues.
     *
     * @return void
     */
    public function disableProcessCache(): void
    {
        if (!$this->hasModule(ConfigHelper::class)) {
            return;
        }

        $this->setConfig(OmsConstants::ENABLE_PROCESS_CACHE, false);
    }

    public function addCommand(string $commandName, string $commandPlugin): void
    {
        $this->config[static::COMMAND_PLUGINS][$commandName] = $commandPlugin;

        $this->reloadCommands();
    }

    public function addCondition(string $conditionName, string $conditionPlugin): void
    {
        $this->config[static::CONDITION_PLUGINS][$conditionName] = $conditionPlugin;

        $this->reloadConditions();
    }

    public function triggerEventForNewOrderItems(array $idSalesOrderItems): void
    {
        $omsFacade = new OmsFacade();
        $omsFacade->triggerEventForNewOrderItems($idSalesOrderItems);
    }

    public function moveItemAfterTimeOut(int $idSalesOrderItem, DateInterval $timeout): void
    {
        $omsEventTimeoutQuery = new SpyOmsEventTimeoutQuery();
        $omsEventTimeout = $omsEventTimeoutQuery->findOneByFkSalesOrderItem($idSalesOrderItem);
        $dateTime = clone $omsEventTimeout->getTimeout();
        $dateTime->sub($timeout);
        $omsEventTimeout->setTimeout($dateTime);
        $omsEventTimeout->save();
    }

    public function setItemState(int $idSalesOrderItem, string $stateName): void
    {
        $salesOrderItemQuery = new SpySalesOrderItemQuery();
        $salesOrderItemEntity = $salesOrderItemQuery->findOneByIdSalesOrderItem($idSalesOrderItem);

        $orderItemStateQuery = new SpyOmsOrderItemStateQuery();
        $orderItemStateEntity = $orderItemStateQuery->filterByName($stateName)->findOneOrCreate();
        $orderItemStateEntity->save();

        $salesOrderItemEntity->setState($orderItemStateEntity);
        $salesOrderItemEntity->save();
    }

    /**
     * @param array<string, string|int> $seed
     *
     * @return \Generated\Shared\Transfer\OmsOrderItemStateTransfer
     */
    public function haveOmsOrderItemState(array $seed): OmsOrderItemStateTransfer
    {
        $omsOrderItemStateTransfer = new OmsOrderItemStateTransfer();
        $omsOrderItemStateTransfer->fromArray($seed);

        $omsOrderItemStateEntity = (new SpyOmsOrderItemState());
        $omsOrderItemStateEntity->fromArray($omsOrderItemStateTransfer->toArray());
        $omsOrderItemStateEntity->save();

        $omsOrderItemStateTransfer->fromArray($omsOrderItemStateEntity->toArray(), true);

        $this->getDataCleanupHelper()->_addCleanup(function () use ($omsOrderItemStateEntity): void {
            $omsOrderItemStateEntity->delete();
        });

        return $omsOrderItemStateTransfer;
    }

    public function checkCondition(): void
    {
        $this->clearOrderItemInstancePool();

        $this->getOmsFacade()->checkConditions();
    }

    public function checkTimeout(): void
    {
        $this->clearOrderItemInstancePool();

        $this->getOmsFacade()->checkTimeouts();
    }

    public function clearLocks(): void
    {
        $this->getOmsFacade()->clearLocks();
    }

    /**
     * The state machine filters order items by state in SQL, then trusts the hydrated entity's state
     * to be one of the states it asked for. Propel never rehydrates an entity that is already in the
     * instance pool, so an item this process moved earlier still reports its old state and the state
     * machine looks it up in a transition map that has no such key. Running as a console subprocess
     * used to guarantee a cold pool; in-process it has to be cleared explicitly.
     */
    protected function clearOrderItemInstancePool(): void
    {
        SpySalesOrderItemTableMap::clearInstancePool();
        SpyOmsOrderItemStateTableMap::clearInstancePool();
    }

    /**
     * In-process, not a console subprocess: the subprocess discarded its exit code, so a state
     * machine that failed mid-transition left the test asserting against whatever state it reached.
     */
    protected function getOmsFacade(): OmsFacadeInterface
    {
        return new OmsFacade();
    }

    public function configureTestStateMachine(array $activeProcesses, ?string $xmlFolder = null): void
    {
        $this->clearPersistenceManagerCache();

        if (!$xmlFolder) {
            $xmlFolder = realpath(__DIR__ . '/../../../../../_data/state-machine/');
        }

        $this->setConfig(OmsConstants::PROCESS_LOCATION, $xmlFolder);
        $this->setConfig(OmsConstants::ACTIVE_PROCESSES, $activeProcesses);
    }

    public function haveOmsProductReservation(array $seed): OmsProductReservationTransfer
    {
        $omsProductReservationTransfer = new OmsProductReservationBuilder($seed);
        $omsProductReservationTransfer = $omsProductReservationTransfer->build();

        $omsProductReservationEntity = (new SpyOmsProductReservation());
        $omsProductReservationEntity->fromArray($omsProductReservationTransfer->toArray());
        $omsProductReservationEntity->save();

        $omsProductReservationTransfer->fromArray($omsProductReservationEntity->toArray(), true);

        $this->getDataCleanupHelper()->_addCleanup(function () use ($omsProductReservationEntity): void {
            $omsProductReservationEntity->delete();
        });

        return $omsProductReservationTransfer;
    }

    public function haveOmsOrderItemStateEntity(string $name): SpyOmsOrderItemState
    {
        $omsOrderItemState = SpyOmsOrderItemStateQuery::create()->filterByName($name)->findOneOrCreate();
        $omsOrderItemState->save();

        $this->getDataCleanupHelper()->_addCleanup(function () use ($omsOrderItemState): void {
            $omsOrderItemState->delete();
        });

        return $omsOrderItemState;
    }

    /**
     * @param array<mixed> $seedData
     *
     * @return \Generated\Shared\Transfer\OmsEventTriggerResponseTransfer
     */
    public function getOmsEventTriggerResponseTransfer(array $seedData): OmsEventTriggerResponseTransfer
    {
        return (new OmsEventTriggerResponseBuilder($seedData))->build();
    }

    public function haveOmsEventTimeoutEntity(array $seed): SpyOmsEventTimeout
    {
        $omsEventTimeoutQuery = SpyOmsEventTimeoutQuery::create();
        if (isset($seed['fk_oms_order_item_state'])) {
            $omsEventTimeoutQuery->filterByFkOmsOrderItemState($seed['fk_oms_order_item_state']);
        }
        if (isset($seed['fk_sales_order_item'])) {
            $omsEventTimeoutQuery->filterByFkSalesOrderItem($seed['fk_sales_order_item']);
        }
        if (isset($seed['event'])) {
            $omsEventTimeoutQuery->filterByEvent($seed['event']);
        }

        $omsEventTimeout = $omsEventTimeoutQuery->findOneOrCreate();

        if (isset($seed['timeout'])) {
            $omsEventTimeout->setTimeout($seed['timeout']);
        }

        $omsEventTimeout->save();

        $this->getDataCleanupHelper()->_addCleanup(function () use ($omsEventTimeout): void {
            $omsEventTimeout->delete();
        });

        return $omsEventTimeout;
    }

    protected function clearPersistenceManagerCache(): void
    {
        $stateCacheProperty = new ReflectionProperty(PersistenceManager::class, 'stateCache');
        $stateCacheProperty->setAccessible(true);
        $stateCacheProperty->setValue([]);
        $processCacheProperty = new ReflectionProperty(PersistenceManager::class, 'processCache');
        $processCacheProperty->setAccessible(true);
        $processCacheProperty->setValue([]);
    }
}
