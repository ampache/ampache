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
 */

namespace Ampache\Module\User\Tracking;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\IpHistoryRepositoryInterface;
use Ampache\Repository\Model\User;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

final class UserTracker implements UserTrackerInterface
{
    private IpHistoryRepositoryInterface $ipHistoryRepository;

    private LoggerInterface $logger;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer,
        IpHistoryRepositoryInterface $ipHistoryRepository,
        LoggerInterface $logger
    ) {
        $this->configContainer     = $configContainer;
        $this->ipHistoryRepository = $ipHistoryRepository;
        $this->logger              = $logger;
    }

    /**
     * Records the users ip in the ip history
     */
    public function trackIpAddress(
        User $user
    ): void {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::TRACK_USER_IP) === false) {
            return;
        }

        $ip = Core::get_user_ip();

        $this->logger->warning(
            sprintf('Login from IP address: %s', $ip),
            [
                LegacyLogger::CONTEXT_TYPE => self::class
            ]
        );

        // Remove port information if any
        if ($ip !== '') {
            // Use parse_url to support easily ipv6
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
                $sipar = parse_url(sprintf('http://[%s]', $ip));
            } else {
                $sipar = parse_url('http://' . $ip);
            }
            $ip = $sipar['host'] ?? '';
        }

        $this->ipHistoryRepository->create(
            $user,
            trim((string)$ip, '[]'),
            Core::get_server('HTTP_USER_AGENT'),
            new DateTimeImmutable()
        );

        /* Clean up old records... sometimes  */
        if (rand(1, 100) > 60) {
            $this->ipHistoryRepository->collectGarbage();
        }
    }
}
