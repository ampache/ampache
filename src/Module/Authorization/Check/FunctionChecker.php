<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Module\Authorization\Check;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\User;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Psr\Log\LoggerInterface;

final class FunctionChecker implements FunctionCheckerInterface
{
    private ConfigContainerInterface $configContainer;

    private LoggerInterface $logger;

    public function __construct(
        ConfigContainerInterface $configContainer,
        LoggerInterface $logger
    ) {
        $this->configContainer = $configContainer;
        $this->logger          = $logger;
    }

    /**
     * This checks if specific functionality is enabled.
     */
    public function check(string $type): bool
    {
        switch ($type) {
            case AccessLevelEnum::FUNCTION_DOWNLOAD:
                return $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DOWNLOAD);
            case AccessLevelEnum::FUNCTION_BATCH_DOWNLOAD:
                if (!function_exists('gzcompress')) {
                    $this->logger->warning(
                        'ZLIB extension not loaded, batch download disabled',
                        [LegacyLogger::CONTEXT_TYPE => __CLASS__]
                    );

                    return false;
                }

                /** @var User $user */
                $user = Core::get_global('user');

                if (!$user) {
                    return false;
                }
                if (
                    $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_ZIP_DOWNLOAD) === true &&
                    $user->has_access(AccessLevelEnum::LEVEL_GUEST)
                ) {
                    return $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DOWNLOAD);
                }
                break;
        }

        return false;
    }
}
