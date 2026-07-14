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
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\MethodInterface;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Creates a new user.
 *
 * Version 5 does not know about catalog filter groups, so it keeps a method of its own.
 */
final class UserCreate5Method implements MethodInterface
{
    public const string ACTION = 'user_create';

    public function __construct(
        private PrivilegeCheckerInterface $privilegeChecker,
        private StreamFactoryInterface $streamFactory,
        private UserRepositoryInterface $userRepository,
    ) {}

    /**
     * user_create
     * MINIMUM_API_VERSION=400001
     *
     * Create a new user.
     * Requires the username, password and email.
     *
     * username = (string) $username
     * fullname = (string) $fullname //optional
     * password = (string) hash('sha256', $password)
     * email = (string) $email
     * disable = (integer) 0,1 //optional, default = 0
     *
     * @param array{
     *     username: string,
     *     fullname?: string,
     *     password: string,
     *     email: string,
     *     disable?: int,
     *     group?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param 5 $apiVersion
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
                $user->id
            )
        ) {
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::ADMIN->value)
            );
        }

        foreach (['username', 'password', 'email'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        $username = $input['username'];
        $fullname = $input['fullname'] ?? $username;
        $email    = urldecode($input['email']);
        $password = $input['password'];
        $disable  = make_bool($input['disable'] ?? false);

        $user_id = User::create(
            $username,
            $fullname,
            $email,
            '',
            $password,
            AccessLevelEnum::USER,
            0,
            '',
            '',
            $disable,
            true
        );

        if ($user_id > 0) {
            $result = $output->success($apiVersion, 'successfully created: ' . $username);

            Catalog::count_table('user');

            return $response->withBody(
                $this->streamFactory->createStream($result)
            );
        }

        if ($this->userRepository->idByUsername($username) > 0) {
            return $this->writeBadRequest($response, $output, $apiVersion, $username, 'username');
        }

        if ($this->userRepository->idByEmail($email) > 0) {
            return $this->writeBadRequest($response, $output, $apiVersion, $email, 'email');
        }

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    'Bad Request',
                    self::ACTION,
                    'system'
                )
            )
        );
    }

    /**
     * @param 5 $apiVersion
     */
    private function writeBadRequest(
        ResponseInterface $response,
        ApiOutputInterface $output,
        int $apiVersion,
        string $value,
        string $type,
    ): ResponseInterface {
        return $response->withBody(
            $this->streamFactory->createStream(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    sprintf('Bad Request: %s', $value),
                    self::ACTION,
                    $type
                )
            )
        );
    }
}
