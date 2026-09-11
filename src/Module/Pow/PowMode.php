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

namespace Ampache\Module\Pow;

/**
 * Who has to solve a proof-of-work challenge on a protected scope.
 */
enum PowMode: string
{
    /** Everybody has to solve a challenge, logged in or not. */
    case ALL = 'all';

    /** Only visitors who are not logged in have to solve a challenge. */
    case GUEST = 'guest';
    /** Protection entirely disabled: no challenge is ever issued or checked. */
    case OFF = 'off';

    /**
     * An unknown or missing setting falls back to the safest useful default rather than to an error,
     * because a typo in the config must not take the whole page down.
     */
    public static function fromConfig(mixed $value): self
    {
        return self::tryFrom(strtolower(trim((string) $value))) ?? self::OFF;
    }
}
