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

namespace Ampache\Module\Api\Method\Api4;

use Ampache\Module\Api\Api4;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Returns a single user by name.
 */
final class User4Method implements MethodInterface
{
    public const string ACTION = 'user';

    public function __construct(
        private StreamFactoryInterface $streamFactory,
        private UserRepositoryInterface $userRepository,
    ) {}

    /**
     * @param array<string, mixed> $input
     * @param 4 $apiVersion
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!Api4::check_parameter($input, ['username'], self::ACTION)) {
            return $response;
        }

        $username   = (string) ($input['username'] ?? '');
        $check_user = User::get_from_username($username);

        if (
            !$check_user instanceof User
            || $check_user->isNew()
            || !in_array($check_user->id, $this->userRepository->getValid(true))
        ) {
            Api4::message('error', 'User_id not found', '404', $input['api_format']);

            return $response;
        }

        // full info is for an admin, or for a user looking at their own record
        $fullinfo = ($check_user->id == $user->id) || ($user->access === 100);

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->user($apiVersion, $check_user, $fullinfo, $input['auth'])
            )
        );
    }
}
