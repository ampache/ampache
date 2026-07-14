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
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Returns a user's public information.
 *
 * Version 5 requires the `username` and only ever looks a user up by name, so it keeps a method
 * of its own.
 */
final class User5Method implements MethodInterface
{
    public const string ACTION = 'user';

    public function __construct(
        private StreamFactoryInterface $streamFactory,
        private UserRepositoryInterface $userRepository,
    ) {}

    /**
     * user
     * MINIMUM_API_VERSION=380001
     *
     * This get a user's public information
     *
     * username = (string) $username
     *
     * @param array{
     *     username?: string,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 5 $apiVersion
     * @throws RequestParamMissingException|ResultEmptyException
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

        $username = (string) $input['username'];
        if (empty($username)) {
            debug_event(self::class, 'User `' . $username . '` cannot be found.', 1);

            throw new ResultEmptyException($username, 'username');
        }

        $check_user = User::get_from_username($username);
        $valid      = ($check_user instanceof User && $check_user->isNew() === false && in_array($check_user->id, $this->userRepository->getValid(true)));
        if (!$check_user instanceof User || !$valid) {
            throw new ResultEmptyException($username, 'username');
        }

        // get full info when you're an admin or searching for yourself
        $fullinfo = (($check_user->id == $user->id) || ($user->access === 100));

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->user($apiVersion, $check_user, $fullinfo, $input['auth'], false)
            )
        );
    }
}
