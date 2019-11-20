<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Oms\Communication\Plugin\Oms\Condition;

use Codeception\Test\Unit;
use Spryker\Zed\Oms\Communication\Plugin\Oms\Condition\ConditionCollection;
use Spryker\Zed\Oms\Dependency\Plugin\Condition\ConditionCollectionInterface;
use Spryker\Zed\Oms\Dependency\Plugin\Condition\ConditionInterface;
use Spryker\Zed\Oms\Exception\ConditionNotFoundException;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Oms
 * @group Communication
 * @group Plugin
 * @group Oms
 * @group Condition
 * @group ConditionCollectionTest
 * Add your own group annotations below this line
 */
class ConditionCollectionTest extends Unit
{
    public const CONDITION_NAME = 'conditionName';

    /**
     * @return void
     */
    public function testAddShouldReturnInstance(): void
    {
        $conditionCollection = new ConditionCollection();
        $result = $conditionCollection->add($this->getConditionMock(), self::CONDITION_NAME);

        $this->assertInstanceOf(ConditionCollectionInterface::class, $result);
    }

    /**
     * @return void
     */
    public function testGetShouldReturnCommand(): void
    {
        $conditionCollection = new ConditionCollection();
        $condition = $this->getConditionMock();
        $conditionCollection->add($condition, self::CONDITION_NAME);

        $this->assertSame($condition, $conditionCollection->get(self::CONDITION_NAME));
    }

    /**
     * @return void
     */
    public function testHasShouldReturnFalse(): void
    {
        $conditionCollection = new ConditionCollection();

        $this->assertFalse($conditionCollection->has(self::CONDITION_NAME));
    }

    /**
     * @return void
     */
    public function testHasShouldReturnTrue(): void
    {
        $conditionCollection = new ConditionCollection();
        $condition = $this->getConditionMock();
        $conditionCollection->add($condition, self::CONDITION_NAME);

        $this->assertTrue($conditionCollection->has(self::CONDITION_NAME));
    }

    /**
     * @return void
     */
    public function testGetShouldThrowException(): void
    {
        $conditionCollection = new ConditionCollection();

        $this->expectException(ConditionNotFoundException::class);

        $conditionCollection->get(self::CONDITION_NAME);
    }

    /**
     * @return void
     */
    public function testArrayAccess(): void
    {
        $conditionCollection = new ConditionCollection();
        $this->assertFalse(isset($conditionCollection[self::CONDITION_NAME]));

        $condition = $this->getConditionMock();
        $conditionCollection[self::CONDITION_NAME] = $condition;

        $this->assertTrue(isset($conditionCollection[self::CONDITION_NAME]));
        $this->assertSame($condition, $conditionCollection[self::CONDITION_NAME]);

        unset($conditionCollection[self::CONDITION_NAME]);
        $this->assertFalse(isset($conditionCollection[self::CONDITION_NAME]));
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Oms\Dependency\Plugin\Condition\ConditionInterface
     */
    private function getConditionMock()
    {
        return $this->getMockBuilder(ConditionInterface::class)->getMock();
    }
}
