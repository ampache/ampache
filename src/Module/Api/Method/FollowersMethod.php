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
use Ampache\Module\Database\Query\BrowseFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * This gets the followers of a user.
 *
 * The parameters and checks are identical for api versions 6 and 8, so a single method serves
 * both. Only the output data is version specific and that is resolved by the ApiOutputInterface.
 */
final class FollowersMethod implements MethodInterface
{
    public const string ACTION = 'followers';

    public function __construct(
        private ConfigContainerInterface $configContainer,
        private BrowseFactoryInterface $browseFactory,
    ) {}

    /**
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=400004
     *
     * This gets followers of the user
     * Error when user not found or no followers
     *
     * filter   = (integer|string) filter by user id OR username //optional
     * username = (string) $username //optional
     * offset   = (integer) //optional
     * limit    = (integer) //optional
     * cond     = (string) Apply additional filters to the browse using ';' separated comma string pairs //optional
     * sort     = (string) sort name or comma separated key pair. Order default 'ASC' (name, ASC) //optional
     *
     * @param array{
     *     filter?: int|string,
     *     username?: string,
     *     offset?: int,
     *     limit?: int,
     *     cond?: string,
     *     sort?: string,
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

        $leadUser = (is_numeric($username))
            ? User::get_from_id((int) $username)
            : User::get_from_username((string) $username);
        if ($leadUser === null) {
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

        $browse = $this->browseFactory->create(null, false);

        $browse->set_user_id($user);

        $browse->set_type('follower');

        $browse->set_sort_order(html_entity_decode((string) ($input['sort'] ?? '')), ['follow_date', 'DESC']);

        $browse->set_filter('user', $leadUser->getId());

        $browse->set_conditions(html_entity_decode((string) ($input['cond'] ?? '')));

        $results = $browse->get_objects();
        if ($results === []) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, 'user')
            );

            return $response;
        }

        $output->setOffset($apiVersion, $input['offset'] ?? 0);
        $output->setLimit($apiVersion, $input['limit'] ?? 0);

        $response->getBody()->write(
            $output->users($apiVersion, $results)
        );

        return $response;
    }
}
