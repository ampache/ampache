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

namespace Ampache\Repository\Model;

use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;

/**
 * The two answers an item holding its own `enabled` column gives, for the items that hold one.
 *
 * `AlbumDisk` carries no column and reads its album's, so it answers both for itself rather than using this.
 */
trait WithdrawableTrait
{
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isVisible(?User $user = null): bool
    {
        return $this->enabled
            || ($user instanceof User && Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER, $user->getId()));
    }
}
