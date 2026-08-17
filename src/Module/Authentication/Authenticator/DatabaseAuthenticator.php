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

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\System\Crypto\SymmetricEncrypterInterface;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\User;

final readonly class DatabaseAuthenticator implements AuthenticatorInterface
{
    public function __construct(
        private SymmetricEncrypterInterface $symmetricEncrypter,
        private DatabaseConnectionInterface $databaseConnection,
    ) {}

    /**
     * @return array{
     *     success: bool,
     *     type?: string,
     *     username?: string,
     *     error?: string
     * }
     */
    public function auth(string $username, string $password): array
    {
        if (strlen($password) && strlen($username)) {
            $row = $this->databaseConnection->fetchRow(
                'SELECT `password` FROM `user` WHERE `username` = ?',
                [$username]
            );

            if (is_array($row) && $row !== []) {
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
                if (isset($hashed_password[1]) && hash_equals($row['password'], $hashed_password[1]) && $hashed_password[0] != $hashed_password[1]) {
                    $user = User::get_from_username($username);
                    if ($user instanceof User) {
                        $user->update_password($password);
                    }
                }

                foreach ($hashed_password as $candidate) {
                    if (hash_equals($row['password'], $candidate)) {
                        return [
                            'success' => true,
                            'type' => 'mysql',
                            'username' => $username
                        ];
                    }
                }
            }

            // Subsonic sends the credential as a plaintext `p=` password when the client does not do token auth, so the
            // dedicated Subsonic secret and the legacy api key are both accepted here as well.
            $row = $this->databaseConnection->fetchRow(
                'SELECT `apikey`, `subsonic_secret` FROM `user` WHERE `username` = ?',
                [$username]
            );
            if (!is_array($row)) {
                $row = [];
            }

            $secret = (empty($row['subsonic_secret']))
                ? null
                : $this->symmetricEncrypter->decrypt((string) $row['subsonic_secret']);

            $api_key = (string) ($row['apikey'] ?? '');
            if (
                ($secret !== null && hash_equals($secret, $password))
                || ($api_key !== '' && hash_equals($api_key, $password))
            ) {
                return [
                    'success' => true,
                    'type' => 'mysql',
                    'username' => $username
                ];
            }
        }

        return [
            'success' => false,
            'error' => 'MySQL login attempt failed',
        ];
    }

    public function postAuth(): ?array
    {
        return null;
    }
}
