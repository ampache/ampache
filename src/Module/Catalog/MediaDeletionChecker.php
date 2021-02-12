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

namespace Ampache\Module\Catalog;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Repository\Model\library_item;

final class MediaDeletionChecker implements MediaDeletionCheckerInterface
{
    private ConfigContainerInterface $configContainer;

    private PrivilegeCheckerInterface $privilegeChecker;

    public function __construct(
        ConfigContainerInterface $configContainer,
        PrivilegeCheckerInterface $privilegeChecker
    ) {
        $this->configContainer  = $configContainer;
        $this->privilegeChecker = $privilegeChecker;
    }

    public function mayDelete(library_item $libItem, int $userId): bool
    {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::DELETE_FROM_DISK) === false) {
            return false;
        }

        return $this->privilegeChecker->check(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_MANAGER) ||
            (
                $libItem->get_user_owner() == $userId &&
                $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::UPLOAD_ALLOW_REMOVE)
            );
    }
}
