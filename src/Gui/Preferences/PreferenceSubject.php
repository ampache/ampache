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

namespace Ampache\Gui\Preferences;

use Ampache\Repository\Model\User;

/**
 * Whose preferences the screen is editing: the visitor's own, the server's, or another account's.
 */
final readonly class PreferenceSubject
{
    private function __construct(
        public User $user,
        public int $userId,
        public string $label,
        public bool $isServer,
        public bool $isSelf,
    ) {}

    public static function otherUser(User $target, User $operator): self
    {
        return ($target->getId() === $operator->getId())
            ? self::ownPreferences($target)
            : new self($target, $target->getId(), (string) $target->fullname, false, false);
    }

    public static function ownPreferences(User $user): self
    {
        return new self($user, $user->getId(), (string) $user->fullname, false, true);
    }

    /**
     * The server has no account of its own, so choice lists are resolved against the operator.
     */
    public static function serverPreferences(User $operator): self
    {
        return new self($operator, User::INTERNAL_SYSTEM_USER_ID, T_('Server'), true, false);
    }
}
