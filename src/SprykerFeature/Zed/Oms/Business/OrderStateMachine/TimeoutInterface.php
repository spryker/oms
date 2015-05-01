<?php

namespace SprykerFeature\Zed\Oms\Business\OrderStateMachine;

use SprykerFeature\Zed\Oms\Business\Process\ProcessInterface;
use SprykerFeature\Zed\Sales\Persistence\Propel\SpySalesOrderItem;
use DateTime;
use Exception;
use Propel\Runtime\Exception\PropelException;

interface TimeoutInterface
{
    /**
     * @param OrderStateMachineInterface $orderStateMachine
     *
     * @return int
     */
    public function checkTimeouts(OrderStateMachineInterface $orderStateMachine);

    /**
     * @param ProcessInterface $process
     * @param SpySalesOrderItem $orderItem
     * @param DateTime $currentTime
     *
     * @throws Exception
     * @throws PropelException
     */
    public function setNewTimeout(ProcessInterface $process, SpySalesOrderItem $orderItem, DateTime $currentTime);

    /**
     * @param ProcessInterface $process
     * @param string $stateId
     * @param $orderItem
     *
     * @throws Exception
     * @throws PropelException
     */
    public function dropOldTimeout(ProcessInterface $process, $stateId, SpySalesOrderItem $orderItem);
}
