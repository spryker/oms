<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Oms\Business\Util;

use Codeception\Test\Unit;
use Generated\Shared\Transfer\ReservationRequestTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use ReflectionProperty;
use Spryker\DecimalObject\Decimal;
use Spryker\Zed\Oms\Business\Reader\ReservationReaderInterface;
use Spryker\Zed\Oms\Business\Util\Reservation;
use Spryker\Zed\Oms\Dependency\Facade\OmsToStoreFacadeInterface;
use Spryker\Zed\Oms\Persistence\OmsEntityManagerInterface;
use Spryker\Zed\Oms\Persistence\OmsRepositoryInterface;
use Spryker\Zed\OmsExtension\Dependency\Plugin\ReservationRequestExpanderPluginInterface;
use SprykerTest\Zed\Oms\OmsBusinessTester;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Oms
 * @group Business
 * @group Util
 * @group ReservationTest
 * Add your own group annotations below this line
 */
class ReservationTest extends Unit
{
    /**
     * @var \SprykerTest\Zed\Oms\OmsBusinessTester
     */
    protected OmsBusinessTester $tester;

    public function testUpdateReservationQuantityGetsAllStores(): void
    {
        // Arrange
        $savedReservationRequestTransfers = [];
        $storeTransfers = $this->tester->getLocator()->store()->facade()->getAllStores();
        $reservationReaderMock = $this->createReservationReaderMock();
        $reservationReaderMock->expects($this->once())
            ->method('sumReservedProductQuantitiesForSku')
            ->willReturn(new Decimal(1));

        $reservation = new Reservation(
            $reservationReaderMock,
            [],
            $this->createStoreFacadeMock($storeTransfers),
            $this->createOmsRepositoryMock(count($storeTransfers)),
            $this->createOmsEntityManagerMock(count($storeTransfers), $savedReservationRequestTransfers),
            [],
            [],
        );

        // Act
        $reservation->updateReservationQuantity($this->tester::FAKE_SKU);

        // Assert
        $this->assertEqualsCanonicalizing(
            array_map(static fn (StoreTransfer $storeTransfer): ?string => $storeTransfer->getName(), $storeTransfers),
            array_map(static fn (ReservationRequestTransfer $reservationRequestTransfer): ?string => $reservationRequestTransfer->getStore()?->getName(), $savedReservationRequestTransfers),
            'A reservation must be saved for every store returned by the store facade.',
        );
    }

    public function testUpdateReservationGetsAllStores(): void
    {
        // Arrange
        $this->resetAllStoreTransfersCache();
        $savedReservationRequestTransfers = [];
        $reservationRequestTransfer = new ReservationRequestTransfer();
        $storeTransfers = $this->tester->getLocator()->store()->facade()->getAllStores();
        $reservationReaderMock = $this->createReservationReaderMock();
        $reservationReaderMock->expects($this->exactly(count($storeTransfers)))
            ->method('sumReservedProductQuantities')
            ->willReturn(new Decimal(1));

        $reservation = new Reservation(
            $reservationReaderMock,
            [],
            $this->createStoreFacadeMock($storeTransfers),
            $this->createOmsRepositoryMock(count($storeTransfers)),
            $this->createOmsEntityManagerMock(count($storeTransfers), $savedReservationRequestTransfers),
            [],
            [],
        );

        // Act
        $reservation->updateReservation($reservationRequestTransfer);

        // Assert
        $this->assertEqualsCanonicalizing(
            array_map(static fn (StoreTransfer $storeTransfer): ?string => $storeTransfer->getName(), $storeTransfers),
            array_map(static fn (ReservationRequestTransfer $savedReservationRequestTransfer): ?string => $savedReservationRequestTransfer->getStore()?->getName(), $savedReservationRequestTransfers),
            'A reservation must be saved for every store returned by the store facade.',
        );
    }

    public function testExpandReservationRequestRunsTheHighestPriorityApplicablePlugin(): void
    {
        // Arrange
        $this->resetAllStoreTransfersCache();

        $expandedReservationRequestTransfer = null;

        $lowPriorityPlugin = $this->createApplicableExpanderPluginMock(priority: 100, expectsExpand: false);
        $midPriorityPlugin = $this->createApplicableExpanderPluginMock(priority: 200, expectsExpand: false);
        $highPriorityPlugin = $this->createCapturingApplicableExpanderPluginMock(300, $expandedReservationRequestTransfer);

        $reservation = $this->createReservationWithExpanderPlugins([
            $lowPriorityPlugin,
            $midPriorityPlugin,
            $highPriorityPlugin,
        ]);

        // Act
        $reservation->updateReservation(new ReservationRequestTransfer());

        // Assert
        $this->assertInstanceOf(
            ReservationRequestTransfer::class,
            $expandedReservationRequestTransfer,
            'The highest-priority applicable plugin must be the one that expands the reservation request.',
        );
        $this->assertSame(
            'DE',
            $expandedReservationRequestTransfer->getStoreOrFail()->getName(),
            'The winning plugin must receive the per-store reservation request.',
        );
    }

    public function testExpandReservationRequestIgnoresHigherPriorityPluginThatIsNotApplicable(): void
    {
        // Arrange
        $this->resetAllStoreTransfersCache();

        $expandedReservationRequestTransfer = null;

        $declinedHighPriorityPlugin = $this->createExpanderPluginMock(
            isApplicable: false,
            priority: 300,
            expectsExpand: false,
        );
        $applicableLowerPriorityPlugin = $this->createCapturingApplicableExpanderPluginMock(200, $expandedReservationRequestTransfer);

        $reservation = $this->createReservationWithExpanderPlugins([
            $declinedHighPriorityPlugin,
            $applicableLowerPriorityPlugin,
        ]);

        // Act
        $reservation->updateReservation(new ReservationRequestTransfer());

        // Assert
        $this->assertInstanceOf(
            ReservationRequestTransfer::class,
            $expandedReservationRequestTransfer,
            'The applicable lower-priority plugin must run when the higher-priority plugin declines.',
        );
        $this->assertSame(
            'DE',
            $expandedReservationRequestTransfer->getStoreOrFail()->getName(),
            'The applicable plugin must receive the per-store reservation request.',
        );
    }

    public function testExpandReservationRequestDoesNothingWhenNoPluginIsApplicable(): void
    {
        // Arrange
        $this->resetAllStoreTransfersCache();

        $savedReservationRequestTransfers = [];

        $firstPlugin = $this->createExpanderPluginMock(isApplicable: false, priority: 100, expectsExpand: false);
        $secondPlugin = $this->createExpanderPluginMock(isApplicable: false, priority: 200, expectsExpand: false);

        $reservation = $this->createReservationWithExpanderPlugins([$firstPlugin, $secondPlugin], $savedReservationRequestTransfers);

        // Act
        $reservation->updateReservation(new ReservationRequestTransfer());

        // Assert
        $this->assertCount(
            1,
            $savedReservationRequestTransfers,
            'The reservation must still be written once per store when no expander plugin applies.',
        );
        $this->assertSame(
            'DE',
            $savedReservationRequestTransfers[0]->getStore()?->getName(),
            'The un-expanded per-store reservation request must still be persisted.',
        );
    }

    /**
     * @param array<\Spryker\Zed\OmsExtension\Dependency\Plugin\ReservationRequestExpanderPluginInterface> $expanderPlugins
     * @param array<\Generated\Shared\Transfer\ReservationRequestTransfer> $savedReservationRequestTransfers
     */
    protected function createReservationWithExpanderPlugins(array $expanderPlugins, array &$savedReservationRequestTransfers = []): Reservation
    {
        $reservationReader = $this->createReservationReaderMock();
        $reservationReader->method('sumReservedProductQuantities')->willReturn(new Decimal(0));

        return new Reservation(
            $reservationReader,
            [],
            $this->createStoreFacadeMock([$this->createSingleStoreTransfer()]),
            $this->createOmsRepositoryMock(1),
            $this->createOmsEntityManagerMock(1, $savedReservationRequestTransfers),
            [],
            [],
            $expanderPlugins,
        );
    }

    protected function createApplicableExpanderPluginMock(
        int $priority,
        bool $expectsExpand
    ): ReservationRequestExpanderPluginInterface {
        return $this->createExpanderPluginMock(isApplicable: true, priority: $priority, expectsExpand: $expectsExpand);
    }

    /**
     * @param \Generated\Shared\Transfer\ReservationRequestTransfer|null $capturedReservationRequestTransfer
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\OmsExtension\Dependency\Plugin\ReservationRequestExpanderPluginInterface
     */
    protected function createCapturingApplicableExpanderPluginMock(
        int $priority,
        ?ReservationRequestTransfer &$capturedReservationRequestTransfer
    ): ReservationRequestExpanderPluginInterface {
        $expanderPluginMock = $this->createMock(ReservationRequestExpanderPluginInterface::class);
        $expanderPluginMock->method('isApplicable')->willReturn(true);
        $expanderPluginMock->method('getPriority')->willReturn($priority);
        $expanderPluginMock->expects($this->once())
            ->method('expand')
            ->willReturnCallback(function (ReservationRequestTransfer $reservationRequestTransfer) use (&$capturedReservationRequestTransfer) {
                $capturedReservationRequestTransfer = $reservationRequestTransfer;

                return $reservationRequestTransfer;
            });

        return $expanderPluginMock;
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\OmsExtension\Dependency\Plugin\ReservationRequestExpanderPluginInterface
     */
    protected function createExpanderPluginMock(
        bool $isApplicable,
        int $priority,
        bool $expectsExpand
    ): ReservationRequestExpanderPluginInterface {
        $expanderPluginMock = $this->createMock(ReservationRequestExpanderPluginInterface::class);
        $expanderPluginMock->method('isApplicable')->willReturn($isApplicable);
        $expanderPluginMock->method('getPriority')->willReturn($priority);

        $expanderPluginMock->expects($expectsExpand ? $this->once() : $this->never())
            ->method('expand')
            ->willReturnArgument(0);

        return $expanderPluginMock;
    }

    protected function resetAllStoreTransfersCache(): void
    {
        $reflectionProperty = new ReflectionProperty(Reservation::class, 'allStoreTransfersCache');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue(null, []);
    }

    protected function createSingleStoreTransfer(): StoreTransfer
    {
        return (new StoreTransfer())
            ->setIdStore(1)
            ->setName('DE');
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Oms\Business\Reader\ReservationReaderInterface
     */
    protected function createReservationReaderMock(): ReservationReaderInterface
    {
        return $this->createMock(ReservationReaderInterface::class);
    }

    /**
     * @param array<\Generated\Shared\Transfer\StoreTransfer> $storeTransfers
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Oms\Dependency\Facade\OmsToStoreFacadeInterface
     */
    protected function createStoreFacadeMock(array $storeTransfers): OmsToStoreFacadeInterface
    {
        $storeFacadeMock = $this->createMock(OmsToStoreFacadeInterface::class);
        $storeFacadeMock->expects($this->once())
            ->method('getAllStores')
            ->willReturn($storeTransfers);

        return $storeFacadeMock;
    }

    /**
     * @param int $storesCount
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Oms\Persistence\OmsRepositoryInterface
     */
    protected function createOmsRepositoryMock(int $storesCount): OmsRepositoryInterface
    {
        return $this->createMock(OmsRepositoryInterface::class);
    }

    /**
     * @param int $storesCount
     * @param array<\Generated\Shared\Transfer\ReservationRequestTransfer> $savedReservationRequestTransfers
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\Oms\Persistence\OmsEntityManagerInterface
     */
    protected function createOmsEntityManagerMock(int $storesCount, array &$savedReservationRequestTransfers = []): OmsEntityManagerInterface
    {
        $omsEntityManagerMock = $this->createMock(OmsEntityManagerInterface::class);
        $omsEntityManagerMock->expects($this->exactly($storesCount))
            ->method('saveReservation')
            ->willReturnCallback(function (ReservationRequestTransfer $reservationRequestTransfer) use (&$savedReservationRequestTransfers): void {
                $savedReservationRequestTransfers[] = $reservationRequestTransfer;
            });

        return $omsEntityManagerMock;
    }
}
