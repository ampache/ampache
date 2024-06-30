<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Authorization;

/**
 * Contains all known access levels
 */
enum AccessLevelEnum: int
{
    case DEFAULT         = 0;
    case GUEST           = 5;
    case USER            = 25;
    case CONTENT_MANAGER = 50;
    case MANAGER         = 75;
    case ADMIN           = 100;

    /**
     * This takes the access-level text representation and returns the level
     */
    public static function fromTextual(string $name): AccessLevelEnum
    {
        return match ($name) {
            'admin' => AccessLevelEnum::ADMIN,
            'user' => AccessLevelEnum::USER,
            'manager' => AccessLevelEnum::MANAGER,
            'content_manager' => AccessLevelEnum::CONTENT_MANAGER,
            'guest' => AccessLevelEnum::GUEST,
            default => AccessLevelEnum::DEFAULT,
        };
    }

    /**
     * Returns the translated description for an access-level
     */
    public function toDescription(): string
    {
        return match ($this) {
            AccessLevelEnum::ADMIN => T_('Admin'),
            AccessLevelEnum::MANAGER => T_('Catalog Manager'),
            AccessLevelEnum::CONTENT_MANAGER => T_('Content Manager'),
            AccessLevelEnum::USER => T_('User'),
            AccessLevelEnum::GUEST => T_('Guest'),
            default => T_('Unknown'),
        };
    }
}
