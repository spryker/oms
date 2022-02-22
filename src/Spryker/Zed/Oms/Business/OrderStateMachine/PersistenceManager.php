<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Oms\Business\OrderStateMachine;

use Orm\Zed\Oms\Persistence\SpyOmsOrderItemState;
use Orm\Zed\Oms\Persistence\SpyOmsOrderItemStateQuery;
use Orm\Zed\Oms\Persistence\SpyOmsOrderProcess;
use Orm\Zed\Oms\Persistence\SpyOmsOrderProcessQuery;
use Spryker\Zed\Kernel\Persistence\EntityManager\TransactionTrait;
use Spryker\Zed\Oms\Business\Exception\ProcessNotActiveException;
use Spryker\Zed\Oms\OmsConfig;

class PersistenceManager implements PersistenceManagerInterface
{
    use TransactionTrait;

    /**
     * @var \Spryker\Zed\Oms\OmsConfig
     */
    protected $omsConfig;

    /**
     * @var array<string, \Orm\Zed\Oms\Persistence\SpyOmsOrderItemState>
     */
    protected static $stateCache = [];

    /**
     * @var array<string, \Orm\Zed\Oms\Persistence\SpyOmsOrderProcess>
     */
    protected static $processCache = [];

    /**
     * @param \Spryker\Zed\Oms\OmsConfig $omsConfig
     */
    public function __construct(OmsConfig $omsConfig)
    {
        $this->omsConfig = $omsConfig;
    }

    /**
     * @param string $stateName
     *
     * @return \Orm\Zed\Oms\Persistence\SpyOmsOrderItemState
     */
    protected function getStateEntity($stateName)
    {
        if (isset(static::$stateCache[$stateName])) {
            return static::$stateCache[$stateName];
        }

        $stateEntity = $this->getTransactionHandler()->handleTransaction(function () use ($stateName): SpyOmsOrderItemState {
            $stateEntity = SpyOmsOrderItemStateQuery::create()
                ->filterByName($stateName)
                ->findOneOrCreate();

            if ($stateEntity->isNew()) {
                $stateEntity->save();
            }

            return $stateEntity;
        });

        static::$stateCache[$stateName] = $stateEntity;

        return $stateEntity;
    }

    /**
     * @param string $processName
     *
     * @throws \Spryker\Zed\Oms\Business\Exception\ProcessNotActiveException
     *
     * @return \Orm\Zed\Oms\Persistence\SpyOmsOrderProcess
     */
    public function getProcessEntity($processName)
    {
        if (!$this->isProcessActive($processName)) {
            throw new ProcessNotActiveException(sprintf(
                'Process with name "%s" is not in active process list. You can add it by modifying your "OmsConstants::ACTIVE_PROCESSES" environment variable constant.',
                $processName,
            ));
        }

        if (isset(static::$processCache[$processName])) {
            return static::$processCache[$processName];
        }

        $processEntity = $this->getTransactionHandler()->handleTransaction(function () use ($processName): SpyOmsOrderProcess {
            $processEntity = SpyOmsOrderProcessQuery::create()
                ->filterByName($processName)
                ->findOneOrCreate();

            if ($processEntity->isNew()) {
                $processEntity->save();
            }

            return $processEntity;
        });

        static::$processCache[$processName] = $processEntity;

        return $processEntity;
    }

    /**
     * @param string $processName
     *
     * @return bool
     */
    protected function isProcessActive($processName)
    {
        return in_array($processName, $this->omsConfig->getActiveProcesses());
    }

    /**
     * @return \Orm\Zed\Oms\Persistence\SpyOmsOrderItemState
     */
    public function getInitialStateEntity()
    {
        return $this->getStateEntity($this->omsConfig->getInitialStatus());
    }
}
