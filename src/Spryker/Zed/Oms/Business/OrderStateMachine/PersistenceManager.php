<?php

/**
 * (c) Spryker Systems GmbH copyright protected
 */

namespace Spryker\Zed\Oms\Business\OrderStateMachine;

use Propel\Runtime\Exception\PropelException;
use Spryker\Shared\Oms\OmsConstants;
use Spryker\Zed\Oms\OmsConfig;
use Orm\Zed\Oms\Persistence\SpyOmsOrderItemState;
use Orm\Zed\Oms\Persistence\SpyOmsOrderProcess;
use Orm\Zed\Oms\Persistence\SpyOmsOrderProcessQuery;
use Orm\Zed\Oms\Persistence\SpyOmsOrderItemStateQuery;

class PersistenceManager implements PersistenceManagerInterface
{

    protected static $stateEntityBuffer = [];

    protected static $processEntityBuffer = [];

    /**
     * @param string $stateName
     *
     * @throws PropelException
     *
     * @return SpyOmsOrderItemState
     */
    public function getStateEntity($stateName)
    {
        if (array_key_exists($stateName, self::$stateEntityBuffer)) {
            return self::$stateEntityBuffer[$stateName];
        }

        $stateEntity = SpyOmsOrderItemStateQuery::create()->findOneByName($stateName);

        if (!isset($stateEntity)) {
            $stateEntity = new SpyOmsOrderItemState();
            $stateEntity->setName($stateName);
            $stateEntity->save();
        }

        $stateBuffer[$stateName] = $stateEntity;

        return $stateEntity;
    }

    /**
     * @param string $processName
     *
     * @throws PropelException
     *
     * @return SpyOmsOrderProcess
     */
    public function getProcessEntity($processName)
    {
        if (array_key_exists($processName, self::$processEntityBuffer)) {
            return self::$processEntityBuffer[$processName];
        }

        $processEntity = SpyOmsOrderProcessQuery::create()->findOneByName($processName);

        if (!isset($processEntity)) {
            $processEntity = new SpyOmsOrderProcess();
            $processEntity->setName($processName);
            $processEntity->save();
        }

        $processBuffer[$processName] = $processEntity;

        return $processEntity;
    }

    /**
     * @return SpyOmsOrderItemState
     */
    public function getInitialStateEntity()
    {
        return $this->getStateEntity(OmsConstants::INITIAL_STATUS);
    }

}
