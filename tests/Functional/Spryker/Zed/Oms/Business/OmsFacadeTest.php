<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Functional\Spryker\Zed\Oms\Business;

use Codeception\TestCase\Test;
use Orm\Zed\Oms\Persistence\SpyOmsStateMachineLock;
use Orm\Zed\Oms\Persistence\SpyOmsStateMachineLockQuery;
use Spryker\Zed\Oms\Business\OmsBusinessFactory;
use Spryker\Zed\Oms\Business\OmsFacade;
use Spryker\Zed\Oms\OmsConfig;

/**
 * @group Functional
 * @group Spryker
 * @group Zed
 * @group Oms
 * @group Business
 * @group OmsFacadeTest
 */
class OmsFacadeTest extends Test
{

    /**
     * @return void
     */
    public function testClearLocksShouldEmptyDatabaseFromExpiredLocks()
    {
        $identifier = '1-2-3';

        $omsFacade = $this->createOmsFacade();

        $omsLockEntity = new SpyOmsStateMachineLock();
        $omsLockEntity->setIdentifier($identifier);
        $omsLockEntity->setExpires(new \DateTime('Yesterday'));
        $omsLockEntity->save();

        $omsFacade->clearLocks();

        $numberOfItems = SpyOmsStateMachineLockQuery::create()->filterByIdentifier($identifier)->count();

        $this->assertEquals(0, $numberOfItems);
    }

    /**
     * @return void
     */
    public function testOrderMatrixCreation()
    {
        $omsFacade = $this->createOmsFacade();

        $matrix = $omsFacade->getOrderItemMatrix();

        $this->assertNotEmpty($matrix);
        $this->assertSame('', $matrix[0]['COL_STATE']);
    }

    /**
     * @return \Spryker\Zed\Oms\Business\OmsFacade
     */
    protected function createOmsFacade()
    {
        $omsBusinessFactory = new OmsBusinessFactory();
        $omsConfig = new OmsConfig();
        $omsBusinessFactory->setConfig($omsConfig);

        $omsFacade = new OmsFacade();
        $omsFacade->setFactory($omsBusinessFactory);

        return $omsFacade;
    }

    /**
     * @return void
     */
    public function testReservedItemsByNonExistentSku()
    {
        $omsFacade = $this->createOmsFacade();
        $items = $omsFacade->getReservedOrderItemsForSku('non-existent-sku');

        $this->assertSame(0, $items->count());
    }

}
