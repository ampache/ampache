<?php

declare(strict_types=0);

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

namespace Ampache\Module\Authentication\Authenticator;

use Ampache\Repository\Model\User;
use Ampache\Module\System\Dba;

final class DatabaseAuthenticator implements AuthenticatorInterface
{
    public function auth(string $username, string $password): array
    {
        if (strlen($password) && strlen($username)) {
            $sql        = 'SELECT `password` FROM `user` WHERE `username` = ?';
            $db_results = Dba::read($sql, [$username]);

            if ($row = Dba::fetch_assoc($db_results)) {
                // Use SHA2 now... cooking with fire.
                // For backwards compatibility we hash a couple of different
                // variations of the password. Increases collision chances, but
                // doesn't break things.
                // FIXME: Break things in the future.
                $hashed_password   = [];
                $hashed_password[] = hash('sha256', $password);
                $escaped_password  = Dba::escape(stripslashes(htmlspecialchars(strip_tags($password))));
                if ($escaped_password) {
                    $hashed_password[] = hash('sha256', $escaped_password);
                }

                // Automagically update the password if it's old and busted.
                if (isset($hashed_password[1]) && $row['password'] == $hashed_password[1] && $hashed_password[0] != $hashed_password[1]) {
                    $user = User::get_from_username($username);
                    if ($user instanceof User) {
                        $user->update_password($password);
                    }
                }

                if (in_array($row['password'], $hashed_password)) {
                    return [
                        'success' => true,
                        'type' => 'mysql',
                        'username' => $username
                    ];
                }
            }
            // subsonic password fallback for auth with apikey
            $sub_sql = 'SELECT `apikey` FROM `user` WHERE `username` = ?';
            $results = Dba::read($sub_sql, [$username]);
            $row     = Dba::fetch_assoc($results);
            $api_key = $row['apikey'] ?? '';
            if ($password == $api_key) {
                return [
                    'success' => true,
                    'type' => 'mysql',
                    'username' => $username
                ];
            }
        }

        return [
            'success' => false,
            'error' => 'MySQL login attempt failed'
        ];
    }

    public function postAuth(): ?array
    {
        return null;
    }
}
