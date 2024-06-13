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

namespace Ampache\Module\Util\OAuth;

/**
 * Class OAuthDataStore
 *
 * @deprecated not in use
 */
class OAuthDataStore
{
    /**
     * @param $consumer_key
     */
    public function lookup_consumer($consumer_key)
    {
        // implement me
    }

    /**
     * @param $consumer
     * @param $token_type
     * @param $token
     */
    public function lookup_token($consumer, $token_type, $token)
    {
        // implement me
    }

    /**
     * @param $consumer
     * @param $token
     * @param $nonce
     * @param $timestamp
     */
    public function lookup_nonce($consumer, $token, $nonce, $timestamp)
    {
        // implement me
    }

    /**
     * @param $consumer
     * @param $callback
     */
    public function new_request_token($consumer, $callback = null)
    {
        // return a new token attached to this consumer
    }

    /**
     * @param $token
     * @param $consumer
     * @param $verifier
     */
    public function new_access_token($token, $consumer, $verifier = null)
    {
        // return a new access token attached to this consumer
        // for the user associated with this token if the request token
        // is authorized
        // should also invalidate the request token
    }
}
