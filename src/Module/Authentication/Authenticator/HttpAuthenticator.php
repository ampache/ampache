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

namespace Ampache\Module\Authentication\Authenticator;

use Ampache\Module\System\Core;

final class HttpAuthenticator implements AuthenticatorInterface
{
    /**
     * @return array{
     *     success: bool,
     *     type?: string,
     *     username?: string,
     *     name?: string,
     *     email?: string,
     *     error?: string
     * }
     */
    public function auth(string $username, string $password): array
    {
        unset($password);
        $results = [];
        // REMOTE_USER is only ever set by the web server or an authenticating module, never derived from a
        // client request header -- HTTP_REMOTE_USER is client-controlled and must never be trusted here.
        if (Core::get_server('REMOTE_USER') === $username) {
            $results['success']  = true;
            $results['type']     = 'http';
            $results['username'] = $username;
            $results['name']     = $username;
            $results['email']    = '';
        } else {
            $results['success'] = false;
            $results['error']   = 'HTTP auth login attempt failed';
        }

        return $results;
    }

    public function postAuth(): ?array
    {
        return null;
    }
}
