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

namespace Ampache\Module\Api\Jellyfin;

use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\User;

/**
 * The `Policy` object real Jellyfin nests on every `UserDto`, wherever one appears — the login response's
 * `User`, `GET /Users/Me`, and `GET /Users/{userId}` all carry it. Shared so the three can't drift the way
 * a missing `Policy.IsAdministrator` broke a real client's login (it reads the field directly and crashes
 * on a missing object entirely, not just a missing field) — `AuthenticationProviderId`/`PasswordResetProviderId`
 * are the two fields the real spec's own `UserPolicy` schema marks required; everything else there is
 * `nullable: true` and, per that same client's own source, never read.
 */
final class JellyfinUserPolicy
{
    /** @return array<string, mixed> */
    public static function build(User $user): array
    {
        return [
            'IsAdministrator' => $user->has_access(AccessLevelEnum::ADMIN),
            'IsDisabled' => (bool) $user->disabled,
            'AuthenticationProviderId' => 'Default',
            'PasswordResetProviderId' => 'Default',
        ];
    }
}
