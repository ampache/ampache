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
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\User;

final class PrivilegeChecker implements PrivilegeCheckerInterface
{
    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory
    ) {
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;
    }

    /**
     * This is the global 'has_access' function. it can check for any 'type'
     * of object.
     *
     * Everything uses the global 0,5,25,50,75,100 stuff. GLOBALS['user'] is used if no userid is provided
     */
    public function check(
        AccessTypeEnum $type,
        AccessLevelEnum $level,
        ?int $userId = null
    ): bool {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DEMO_MODE) === true) {
            return true;
        }

        /**
         * @todo drop usage of global constants
         * @deprecated
         */
        if (defined('INSTALL')) {
            return true;
        }

        $user = ($userId !== null)
            ? $this->modelFactory->createUser($userId)
            : Core::get_global('user');

        // an empty string is an empty global
        if (!$user instanceof User || $user == '' || $user->id === 0) {
            return false;
        }

        // Switch on the type
        switch ($type) {
            case AccessTypeEnum::LOCALPLAY:
                // Check their localplay_level
                return $this->configContainer->get(ConfigurationKeyEnum::LOCALPLAY_LEVEL) >= $level->value ||
                    $user->access >= AccessLevelEnum::ADMIN->value;
            case AccessTypeEnum::INTERFACE:
                // Check their standard user level
                return ($user->access >= $level->value);
            default:
                return false;
        }
    }
}
