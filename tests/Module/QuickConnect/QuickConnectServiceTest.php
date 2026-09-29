<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Ampache\Module\QuickConnect;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\QuickConnectRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QuickConnectServiceTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private QuickConnectRepositoryInterface&MockObject $repository;
    private UserRepositoryInterface&MockObject $userRepository;

    /**
     * Telling the owner their device was approved, while the row still points at whoever bound it first,
     * is what would keep a stolen pairing from ever being noticed.
     */
    public function testApprovingAnAlreadyBoundPairingReportsFailure(): void
    {
        $this->repository->method('findByCode')->willReturn(['id' => 7]);
        $this->repository->method('incrementAuthorizeAttempts')->willReturn(1);
        $this->repository->method('markAuthorized')->willReturn(false);

        self::assertFalse($this->subject()->authorize('123456', $this->caller(), null)['success']);
    }

    public function testApprovingAPendingPairingReportsSuccess(): void
    {
        $this->repository->method('findByCode')->willReturn(['id' => 7]);
        $this->repository->method('incrementAuthorizeAttempts')->willReturn(1);
        $this->repository->method('markAuthorized')->willReturn(true);

        self::assertTrue($this->subject()->authorize('123456', $this->caller(), null)['success']);
    }

    /**
     * The owner double-clicking their own approval is the same approval, not a stolen pairing.
     */
    public function testApprovingOwnPairingTwiceStillReportsSuccess(): void
    {
        $this->repository->method('findByCode')->willReturn(['id' => 7]);
        $this->repository->method('incrementAuthorizeAttempts')->willReturn(1);
        $this->repository->method('markAuthorized')->willReturn(true, true);

        $subject = $this->subject();
        self::assertTrue($subject->authorize('123456', $this->caller(), null)['success']);
        self::assertTrue($subject->authorize('123456', $this->caller(), null)['success']);
    }

    /**
     * The window is counted per device, so accepting a caller that names none leaves it uncounted.
     */
    public function testInitiatingWithoutADeviceIdIsRefused(): void
    {
        $this->repository->expects(static::never())->method('create');

        self::assertNull($this->subject()->initiate('', 'a device', 'an app', '1.0'));
    }

    protected function setUp(): void
    {
        $this->configContainer = $this->createMock(ConfigContainerInterface::class);
        $this->repository      = $this->createMock(QuickConnectRepositoryInterface::class);
        $this->userRepository  = $this->createMock(UserRepositoryInterface::class);
    }

    private function caller(): User&MockObject
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(42);

        return $user;
    }

    private function subject(): QuickConnectService
    {
        return new QuickConnectService($this->configContainer, $this->repository, $this->userRepository);
    }
}
