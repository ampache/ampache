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

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\User;

/**
 * The one place that mints a Jellyfin `AccessToken` and builds `AuthenticationResult`, shared by
 * `AuthenticateByNameMethod` and `AuthenticateWithQuickConnectMethod` so the two never drift.
 */
final class JellyfinSessionMinter
{
    /** Jellyfin clients cannot re-auth silently, so a token outlives any listening habit but stays in the sweep's reach */
    private const int SESSION_TTL_SECONDS = 70 * 24 * 60 * 60;

    public function __construct(
        private readonly DatabaseConnectionInterface $databaseConnection,
        private readonly JellyfinServerId $serverId,
    ) {}

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

        // a perpetual row (expire 0) is left alone, and a longer expiry is never shortened
        $expire = time() + self::SESSION_TTL_SECONDS;
        $this->databaseConnection->query(
            'UPDATE `session` SET `expire` = ? WHERE `id` = ? AND `expire` != 0 AND `expire` < ?',
            [$expire, $token, $expire]
        );

        $serverId = $this->serverId->get();

        return [
            'User' => [
                'Name' => $user->username,
                'ServerId' => $serverId,
                'Id' => JellyfinId::encode('user', $user->id),
                'HasPassword' => true,
                'HasConfiguredPassword' => true,
                'HasConfiguredEasyPassword' => false,
                'EnableAutoLogin' => false,
                'Policy' => JellyfinUserPolicy::build($user),
            ],
            'AccessToken' => $token,
            'ServerId' => $serverId,
        ];
    }
}
