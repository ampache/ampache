<?php
/*
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

declare(strict_types=0);

namespace Ampache\Module\Authentication\Authenticator;

final class PamAuthenticator implements AuthenticatorInterface
{
    public function auth(string $username, string $password): array
    {
        $results = [];
        if (!function_exists('pam_auth')) {
            $results['success'] = false;
            $results['error']   = 'The PAM PHP module is not installed';

            return $results;
        }

        $password = scrub_in($password);

        if (pam_auth($username, $password)) {
            $results['success']  = true;
            $results['type']     = 'pam';
            $results['username'] = $username;
        } else {
            $results['success'] = false;
            $results['error']   = 'PAM login attempt failed';
        }

        return $results;
    }

    public function postAuth(): ?array
    {
        return null;
    }
}
