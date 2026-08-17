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

namespace Ampache\Module\User\Tracking;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\IpHistoryRepositoryInterface;
use Ampache\Repository\Model\User;
use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserTrackerTest extends TestCase
{
    private ConfigContainerInterface&MockObject $configContainer;
    private IpHistoryRepositoryInterface&MockObject $ipHistoryRepository;
    private LoggerInterface&MockObject $logger;
    private ?string $previousRemoteAddr;
    private ?string $previousUserAgent;
    private UserTracker $subject;

    public function testTrackIpAddressDoesNothingIfFeatureIsDisabled(): void
    {
        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::TRACK_USER_IP)
            ->willReturn(false);

        $this->ipHistoryRepository->expects(static::never())
            ->method('create');

        $this->subject->trackIpAddress(
            $this->createMock(User::class),
            'login'
        );
    }

    // NOTE: Core::get_user_ip() reads REMOTE_ADDR/HTTP_X_FORWARDED_FOR via
    // filter_has_var(INPUT_SERVER, ...), which reflects a snapshot taken by
    // the SAPI at request start, not live writes to the $_SERVER superglobal
    // (confirmed: filter_has_var(INPUT_SERVER, 'REMOTE_ADDR') returns false
    // even immediately after assigning $_SERVER['REMOTE_ADDR'] in the same
    // process). Under the PHPUnit CLI runner this means get_user_ip() always
    // returns '', so the ip-stripping branch (parse_url()/IPv6 bracket
    // handling) cannot be exercised by mutating $_SERVER in a test. This
    // test asserts the real, observable behaviour instead: an empty ip is
    // recorded, while the user agent -- read via Core::get_server(), which
    // does use plain $_SERVER access -- is picked up correctly.
    public function testTrackIpAddressRecordsEmptyIpAndConfiguredUserAgent(): void
    {
        $user = $this->createMock(User::class);

        $_SERVER['REMOTE_ADDR']     = '203.0.113.42';
        $_SERVER['HTTP_USER_AGENT'] = 'SomeTestAgent/1.0';

        $this->configContainer->expects(static::once())
            ->method('isFeatureEnabled')
            ->with(ConfigurationKeyEnum::TRACK_USER_IP)
            ->willReturn(true);

        $this->logger->expects(static::once())
            ->method('warning')
            ->with(self::stringContains('Login from IP address:'));

        $this->ipHistoryRepository->expects(static::once())
            ->method('create')
            ->with(
                $user,
                '',
                'SomeTestAgent/1.0',
                self::isInstanceOf(DateTimeImmutable::class),
                'login',
            );

        $this->subject->trackIpAddress($user, 'login');
    }

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

        $this->previousRemoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;
        $this->previousUserAgent  = $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    protected function tearDown(): void
    {
        if ($this->previousRemoteAddr === null) {
            unset($_SERVER['REMOTE_ADDR']);
        } else {
            $_SERVER['REMOTE_ADDR'] = $this->previousRemoteAddr;
        }

        if ($this->previousUserAgent === null) {
            unset($_SERVER['HTTP_USER_AGENT']);
        } else {
            $_SERVER['HTTP_USER_AGENT'] = $this->previousUserAgent;
        }
    }
}
