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

namespace Ampache\Module\Authentication;

interface AuthenticationManagerInterface
{
    public function login(
        string $username,
        string $password,
        bool $allow_ui = false
    ): array;

    public function postAuth(string $method): ?array;

    public function tokenLogin(
        string $username,
        string $token,
        string $salt
    ): array;

    /**
     * This is called when you want to log out and nuke your session.
     * This is the function used for the Ajax logouts, if no id is passed
     * it tries to find one from the session,
     */
    public function logout(string $key = '', bool $relogin = true): void;
}
