<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

namespace Ampache\Module\User\Tracking;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\IpHistoryRepositoryInterface;
use Ampache\Repository\Model\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * @todo extend tests once the static calls are gone
 */
class UserTrackerTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;

    private IpHistoryRepositoryInterface&MockObject $ipHistoryRepository;

    private LoggerInterface&MockObject $logger;

    private UserTracker $subject;

    protected function setUp(): void
    {
        $this->configContainer     = $this->createMock(ConfigContainerInterface::class);
        $this->ipHistoryRepository = $this->createMock(IpHistoryRepositoryInterface::class);
        $this->logger              = $this->createMock(LoggerInterface::class);

        $this->subject = new UserTracker(
            $this->configContainer,
            $this->ipHistoryRepository,
            $this->logger
        );
    }

    public function testTrackIpAddressDoesNothingIfFeatureIsDisabled(): void
    {
        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::TRACK_USER_IP)
            ->willReturn(false);

        $this->subject->trackIpAddress(
            $this->createMock(User::class)
        );
    }
}
