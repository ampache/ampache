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

namespace Ampache\Module\Api\Method;

use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\System\Session;
use Ampache\Repository\Model\User;

/**
 * Mints or extends the native API session `handshake` hands back, factored out of
 * `AbstractHandshakeMethod` so `QuickConnectStatus8Method` can hand back the exact same shape of
 * session without going through a password/timestamp handshake of its own.
 */
final class ApiSessionMinter
{
    public function extend(string $auth): string
    {
        Session::extend($auth, AccessTypeEnum::API->value);

        return $auth;
    }

    /**
     * @param array{client?: string, geo_latitude?: float, geo_longitude?: float, geo_name?: string} $input
     */
    public function mint(User $client, int $dataVersion, array $input = []): string
    {
        $data = [
            'username' => (string) $client->username,
            'type' => 'api',
            'apikey' => (string) $client->apikey,
            'streamtoken' => (string) $client->streamtoken,
            'value' => $dataVersion,
        ];

        if (isset($input['client'])) {
            $data['agent'] = scrub_in($input['client']);
        }

        if (isset($input['geo_latitude'])) {
            $data['geo_latitude'] = $input['geo_latitude'];
        }

        if (isset($input['geo_longitude'])) {
            $data['geo_longitude'] = $input['geo_longitude'];
        }

        if (isset($input['geo_name'])) {
            $data['geo_name'] = $input['geo_name'];
        }

        // Session might not exist or has expired
        if (!Session::read($data['apikey'])) {
            Session::destroy($data['apikey']);

            return Session::create($data);
        }

        Session::extend($data['apikey'], AccessTypeEnum::API->value);

        return $data['apikey'];
    }
}
