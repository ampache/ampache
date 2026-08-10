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

namespace Ampache\Module\Api\Method\Api5;

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Updates an existing user.
 *
 * Version 5 is an alias of `user_update` and hands the request straight over to it.
 */
final class UserEdit5Method implements MethodInterface
{
    public const string ACTION = 'user_edit';

    public function __construct(
        private UserUpdate5Method $userUpdateMethod,
    ) {}

    /**
     * user_edit
     * MINIMUM_API_VERSION=6.0.0
     *
     * Update an existing user.
     * Takes the username with optional parameters.
     *
     * username = (string) $username
     * password = (string) hash('sha256', $password)) //optional
     * fullname = (string) $fullname //optional
     * email = (string) $email //optional
     * website = (string) $website //optional
     * state = (string) $state //optional
     * city = (string) $city //optional
     * disable = (integer) 0,1 true to disable, false to enable //optional
     * group = (integer) Catalog filter group for the new user //optional, default = 0
     * maxbitrate = (integer) $maxbitrate in kbps //optional
     * fullname_public = (integer) 0,1 true to enable, false to disable using fullname in public display //optional
     * reset_apikey = (integer) 0,1 true to reset a user Api Key //optional
     * reset_streamtoken = (integer) 0,1 true to reset a user Stream Token //optional
     * clear_stats = (integer) 0,1 true reset all stats for this user //optional
     *
     * @param array{
     *     username?: string,
     *     fullname?: string,
     *     password?: string,
     *     email?: string,
     *     website?: string,
     *     state?: string,
     *     city?: string,
     *     disable?: int,
     *     group?: int,
     *     maxbitrate?: int,
     *     fullname_public?: int,
     *     reset_apikey?: int,
     *     reset_streamtoken?: int,
     *     clear_stats?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 5 $apiVersion
     * @throws RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!array_key_exists('username', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'username')
            );
        }

        return $this->userUpdateMethod->handle(
            $gatekeeper,
            $response,
            $output,
            $input,
            $user,
            $apiVersion
        );
    }
}
