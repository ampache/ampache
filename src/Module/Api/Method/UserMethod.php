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

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns a single user.
 *
 * The parameters and checks are identical for api versions 6 and 8, so a single method serves
 * both. Only the output data is version specific and that is resolved by the ApiOutputInterface.
 */
final class UserMethod implements MethodInterface
{
    public const string ACTION = 'user';

    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This gets a user's public information
     *
     * filter   = (integer|string) filter by user id OR username //optional
     * username = (string) $username //optional
     *
     * @param array{
     *     filter?: int|string,
     *     username?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 6|8 $apiVersion
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        $username = $input['filter'] ?? $input['username'] ?? null;

        // if the username is omitted, use the current users context to retrieve its own data
        if ($username === null) {
            $checkUser = $user;
            $fullInfo  = true;
        } else {
            $checkUser = (is_numeric($username))
                ? $this->userRepository->findById((int) $username)
                : $this->userRepository->findByUsername((string) $username);

            if (
                $checkUser === null
                || !in_array($checkUser->getId(), $this->userRepository->getValid(true))
            ) {
                $response->getBody()->write(
                    $output->error(
                        $apiVersion,
                        ErrorCodeEnum::NOT_FOUND,
                        sprintf('Not Found: %s', $username),
                        self::ACTION,
                        'username'
                    )
                );

                return $response;
            }

            // get full info when you're an admin or searching for yourself
            $fullInfo = $checkUser->getId() === $user->getId()
                || $user->access === AccessLevelEnum::ADMIN->value;
        }

        $response->getBody()->write(
            $output->user($apiVersion, $checkUser, $fullInfo, $input['auth'], false)
        );

        return $response;
    }
}
