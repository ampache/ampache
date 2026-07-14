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

namespace Ampache\Module\Api\Method\Api6;

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Updates an existing user
 *
 * This is the api version 6 alias of user_edit. Unlike user_edit it insists on `username`, so that
 * guard stays here and the rest is handed straight over.
 */
final class UserUpdate6Method implements MethodInterface
{
    public const string ACTION = 'user_update';

    private UserEdit6Method $userEditMethod;

    public function __construct(
        UserEdit6Method $userEditMethod,
    ) {
        $this->userEditMethod = $userEditMethod;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * Update an existing user.
     * Takes the username with optional parameters.
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

        return $this->userEditMethod->handle(
            $gatekeeper,
            $response,
            $output,
            $input,
            $user,
            $apiVersion
        );
    }
}
