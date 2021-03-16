<?php

/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Module\Api\Gui\Method;

use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\User\Management\Exception\UserCreationFailedException;
use Ampache\Module\User\Management\UserCreatorInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class UserCreateMethod implements MethodInterface
{
    public const ACTION = 'user_create';

    private StreamFactoryInterface $streamFactory;

    private UserCreatorInterface $userCreator;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        UserCreatorInterface $userCreator
    ) {
        $this->streamFactory = $streamFactory;
        $this->userCreator   = $userCreator;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * Create a new user.
     * Requires the username, password and email.
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * username = (string) $username
     * fullname = (string) $fullname //optional
     * password = (string) hash('sha256', $password))
     * email    = (string) $email
     * disable  = (integer) 0,1 //optional, default = 0
     *
     * @return ResponseInterface
     * @throws RequestParamMissingException
     * @throws AccessDeniedException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN) === false) {
            throw new AccessDeniedException(
                T_('Require: 100')
            );
        }

        foreach (['username', 'password', 'email'] as $key) {
            if (!array_key_exists($key, $input)) {
                throw new RequestParamMissingException(
                    sprintf(T_('Bad Request: %s'), $key)
                );
            }
        }

        $username = $input['username'];
        $fullname = $input['fullname'] ?? $username;
        $email    = $input['email'];
        $password = $input['password'];

        try {
            $this->userCreator->create(
                $username,
                $fullname,
                $email,
                '',
                $password,
                AccessLevelEnum::LEVEL_USER,
                '',
                '',
                (bool) ($input['disable'] ?? 0),
                true
            );

            return $response->withBody(
                $this->streamFactory->createStream(
                    $output->success(
                        sprintf(T_('successfully created: %s'), $username)
                    )
                )
            );
        } catch (UserCreationFailedException $e) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), $username)
            );
        }
    }
}
