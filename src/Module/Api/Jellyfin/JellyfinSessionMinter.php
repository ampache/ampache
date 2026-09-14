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

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\User;

/**
 * The one place that mints a Jellyfin `AccessToken` and builds `AuthenticationResult`, shared by
 * `AuthenticateByNameMethod` and `AuthenticateWithQuickConnectMethod` so the two never drift.
 */
final class JellyfinSessionMinter
{
    /**
     * Real `AccessToken`s expire only on sign-out and Finamp has no silent-reauth path (confirmed live), so
     * this row gets a long fixed TTL rather than `session_length`, without touching `perpetual_api_session`.
     */
    private const int SESSION_TTL_SECONDS = 10 * 365 * 24 * 60 * 60;

    public function __construct(private readonly DatabaseConnectionInterface $databaseConnection) {}

    /** @return array<string, mixed>|null null means `Session::create()` itself failed */
    public function mint(User $user): ?array
    {
        $token = Session::create([
            'username' => (string) $user->username,
            'type' => 'api',
            'apikey' => (string) $user->apikey,
            'value' => 1,
        ]);
        if ($token === '') {
            return null;
        }

        $this->databaseConnection->query(
            'UPDATE `session` SET `expire` = ? WHERE `id` = ?',
            [time() + self::SESSION_TTL_SECONDS, $token],
        );

        $serverId = JellyfinServerId::derive((string) AmpConfig::get('secret_key', ''));

        return [
            'User' => [
                'Name' => $user->username,
                'ServerId' => $serverId,
                'Id' => JellyfinId::encode('user', $user->id),
                'HasPassword' => true,
                'HasConfiguredPassword' => true,
                'HasConfiguredEasyPassword' => false,
                'EnableAutoLogin' => false,
                // real clients (confirmed: Feishin) read Policy.IsAdministrator directly off the login
                // response and crash on a missing Policy object entirely, not just a missing field on it
                'Policy' => [
                    'IsAdministrator' => $user->has_access(AccessLevelEnum::ADMIN),
                    'IsDisabled' => (bool) $user->disabled,
                    'AuthenticationProviderId' => 'Default',
                    'PasswordResetProviderId' => 'Default',
                ],
            ],
            'AccessToken' => $token,
            'ServerId' => $serverId,
        ];
    }
}
