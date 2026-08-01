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
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\System\Core;
use Ampache\Module\User\Registration;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Registers a new user through the public registration flow
 */
final class RegisterMethod implements MethodInterface
{
    public const string ACTION = 'register';

    private ConfigContainerInterface $configContainer;
    private ModelFactoryInterface $modelFactory;
    private UserRepositoryInterface $userRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
        UserRepositoryInterface $userRepository,
    ) {
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;
        $this->userRepository  = $userRepository;
    }

    /**
     * MINIMUM_API_VERSION=6.0.0
     *
     * Register a new user.
     * Requires the username, password and email.
     *
     * username = (string) $username
     * fullname = (string) $fullname //optional
     * password = (string) hash('sha256', $password)
     * email    = (string) $email
     *
     * @param array{
     *     username?: string,
     *     fullname?: string,
     *     password?: string,
     *     email?: string,
     *     api_format: string,
     * } $input
     * @throws AccessDeniedException|RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!$this->configContainer->get(ConfigurationKeyEnum::ALLOW_PUBLIC_REGISTRATION)) {
            throw new AccessDeniedException(
                'Enable: allow_public_registration'
            );
        }

        foreach (['username', 'password', 'email'] as $parameter) {
            if (!array_key_exists($parameter, $input)) {
                throw new RequestParamMissingException(
                    sprintf('Bad Request: %s', $parameter)
                );
            }
        }

        $username           = (string) $input['username'];
        $fullname           = $input['fullname'] ?? $username;
        $email              = urldecode((string) $input['email']);
        $password           = (string) $input['password'];
        $adminRequired      = (bool) $this->configContainer->get(ConfigurationKeyEnum::ADMIN_ENABLE_REQUIRED);
        $access             = AccessLevelEnum::fromTextual((string) ($this->configContainer->get(ConfigurationKeyEnum::AUTO_USER) ?? 'guest'));
        $catalogFilterGroup = 0;

        $userId = User::create(
            $username,
            $fullname,
            $email,
            '',
            $password,
            $access,
            $catalogFilterGroup,
            '',
            '',
            $adminRequired,
            true
        );

        if ($userId > 0) {
            if (!$this->configContainer->get(ConfigurationKeyEnum::USER_NO_EMAIL_CONFIRM)) {
                $client     = $this->modelFactory->createUser($userId);
                $validation = Core::generate_random_key();
                $client->update_validation($validation);

                // Notify user and/or admins
                Registration::send_confirmation($username, $fullname, $email, '', $validation);
            }

            $text = ($adminRequired)
                ? 'Please wait for an administrator to activate your account'
                : 'successfully created: ' . $username;

            Catalog::count_table('user');

            $response->getBody()->write(
                $output->success($apiVersion, $text)
            );

            return $response;
        }

        if ($this->userRepository->idByUsername($username) > 0) {
            return $this->writeBadRequest($response, $output, $apiVersion, $username, 'username');
        }

        if ($this->userRepository->idByEmail($email) > 0) {
            return $this->writeBadRequest($response, $output, $apiVersion, $email, 'email');
        }

        $response->getBody()->write(
            $output->error(
                $apiVersion,
                ErrorCodeEnum::BAD_REQUEST,
                'Bad Request',
                self::ACTION,
                'system'
            )
        );

        return $response;
    }

    private function writeBadRequest(
        ResponseInterface $response,
        ApiOutputInterface $output,
        int $apiVersion,
        string $value,
        string $type,
    ): ResponseInterface {
        $response->getBody()->write(
            $output->error(
                $apiVersion,
                ErrorCodeEnum::BAD_REQUEST,
                sprintf('Bad Request: %s', $value),
                self::ACTION,
                $type
            )
        );

        return $response;
    }
}
