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

use Ampache\Config\AmpConfig;

final class ExternalAuthenticator implements AuthenticatorInterface
{
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
        $authenticator = AmpConfig::get('external_authenticator');
        if (!$authenticator) {
            return [
                'success' => false,
                'error' => 'No external authenticator configured'
            ];
        }

        // FIXME: should we do input sanitization?
        $proc = proc_open($authenticator, [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ], $pipes);

        if (is_resource($proc)) {
            fwrite($pipes[0], $username . "\n" . $password . "\n");
            fclose($pipes[0]);
            fclose($pipes[1]);
            if ($stderr = fread($pipes[2], 8192)) {
                debug_event(self::class, "external_auth fread error: " . $stderr, 3);
            }
            fclose($pipes[2]);
        } else {
            return [
                'success' => false,
                'error' => 'Failed to run external authenticator'
            ];
        }

        if (proc_close($proc) == 0) {
            return [
                'success' => true,
                'type' => 'external',
                'username' => $username
            ];
        }

        return [
            'success' => false,
            'error' => 'The external authenticator did not accept the login',
        ];
    }

    public function postAuth(): ?array
    {
        return null;
    }
}
