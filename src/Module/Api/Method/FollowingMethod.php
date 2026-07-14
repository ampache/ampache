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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserFollowerRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Get the users followed by a user.
 *
 * The parameters and checks are identical for api versions 6 and 8, so a single method serves
 * both. Only the output data is version specific and that is resolved by the ApiOutputInterface.
 */
final class FollowingMethod implements MethodInterface
{
    public const string ACTION = 'following';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private UserFollowerRepositoryInterface $userFollowerRepository,
    ) {}

    /**
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=400004
     *
     * Get users followed by the user
     * Error when user not found or no followers
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
        if (!$this->configContainer->get(ConfigurationKeyEnum::SOCIABLE)) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::ACCESS_DENIED,
                    'Enable: sociable',
                    self::ACTION,
                    'system'
                )
            );

            return $response;
        }

        $username = $input['filter'] ?? $input['username'] ?? null;
        if (empty($username)) {
            $username = $user->username;
        }

        $leader = (is_numeric($username))
            ? User::get_from_id((int) $username)
            : User::get_from_username((string) $username);
        if ($leader === null || $leader->id < 1) {
            debug_event(self::class, 'User `' . $username . '` cannot be found.', 1);

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

        $results = $this->userFollowerRepository->getFollowing($leader);
        if ($results === []) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, 'user')
            );

            return $response;
        }

        $response->getBody()->write(
            $output->users($apiVersion, $results)
        );

        return $response;
    }
}
