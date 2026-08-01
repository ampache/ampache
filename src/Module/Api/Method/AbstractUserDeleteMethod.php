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
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Deletes an existing user
 *
 * The two live api versions only differ in how they name the user: version 6 reports it as
 * `username` and version 8 as `filter`, each accepting the other as an alias. The version classes
 * supply that pair of names; everything else is shared.
 */
abstract class AbstractUserDeleteMethod implements MethodInterface
{
    public const string ACTION = 'user_delete';

    public const string REST_ACTION = 'users_delete';

    // the alias the version prefers when both names are supplied; overridden per version
    protected const string FILTER_ALIAS = 'username';

    // the name the version reports the user under; overridden per version
    protected const string FILTER_KEY = 'filter';

    private PrivilegeCheckerInterface $privilegeChecker;

    public function __construct(
        PrivilegeCheckerInterface $privilegeChecker,
    ) {
        $this->privilegeChecker = $privilegeChecker;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * Delete an existing user.
     * Takes the username in parameter.
     *
     * filter   = (integer|string) filter by user id OR username //optional
     * username = (string) $username
     *
     * @param array{
     *     filter?: int|string,
     *     username?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessFailedException|RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (
            !$this->privilegeChecker->check(
                AccessTypeEnum::INTERFACE,
                AccessLevelEnum::ADMIN,
                $user->getId()
            )
        ) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::ADMIN->value)
            );
        }

        $username = $input[static::FILTER_ALIAS] ?? $input[static::FILTER_KEY] ?? null;
        if ($username === null) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', static::FILTER_KEY)
            );
        }

        $deleteUser = (is_numeric($username))
            ? User::get_from_id((int) $username)
            : User::get_from_username((string) $username);

        // don't delete yourself or admins
        if (
            $deleteUser === null
            || $deleteUser->username === $user->username
            || $deleteUser->access >= AccessLevelEnum::ADMIN->value
            || !$deleteUser->delete()
        ) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $username),
                    static::ACTION,
                    'system'
                )
            );

            return $response;
        }

        Catalog::count_table('user');

        $response->getBody()->write(
            $output->success($apiVersion, 'successfully deleted: ' . $username)
        );

        return $response;
    }
}
