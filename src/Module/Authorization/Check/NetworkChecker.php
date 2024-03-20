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

namespace Ampache\Module\Authorization\Check;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Core;
use Ampache\Repository\AccessRepositoryInterface;

final class NetworkChecker implements NetworkCheckerInterface
{
    private ConfigContainerInterface $configContainer;

    private AccessRepositoryInterface $accessRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        AccessRepositoryInterface $accessRepository
    ) {
        $this->configContainer  = $configContainer;
        $this->accessRepository = $accessRepository;
    }

    /**
     * This takes a type, ip, user, level and key and then returns whether they
     * are allowed. The IP is passed as a dotted quad.
     */
    public function check(
        AccessTypeEnum $type,
        ?int $userId = null,
        AccessLevelEnum $level = AccessLevelEnum::USER
    ): bool {
        if (!$this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ACCESS_CONTROL)) {
            return match ($type) {
                AccessTypeEnum::INTERFACE, AccessTypeEnum::STREAM => true,
                default => false,
            };
        }

        switch ($type) {
            case AccessTypeEnum::API:
            case AccessTypeEnum::NETWORK:
            case AccessTypeEnum::INTERFACE:
            case AccessTypeEnum::STREAM:
                break;
            default:
                return false;
        }

        return $this->accessRepository->findByIp(
            Core::get_user_ip(),
            $level,
            $type,
            $userId
        );
    }
}
