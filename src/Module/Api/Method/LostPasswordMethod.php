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
use Ampache\Module\System\Core;
use Ampache\Module\User\NewPasswordSenderInterface;
use Ampache\Module\Util\Mailer;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Allows a non-admin user to reset their password without web access to the main site
 */
final class LostPasswordMethod implements MethodInterface
{
    public const string ACTION = 'lost_password';

    private ConfigContainerInterface $configContainer;
    private ModelFactoryInterface $modelFactory;
    private NewPasswordSenderInterface $newPasswordSender;
    private UserRepositoryInterface $userRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
        NewPasswordSenderInterface $newPasswordSender,
        UserRepositoryInterface $userRepository,
    ) {
        $this->configContainer   = $configContainer;
        $this->modelFactory      = $modelFactory;
        $this->newPasswordSender = $newPasswordSender;
        $this->userRepository    = $userRepository;
    }

    /**
     * MINIMUM_API_VERSION=6.1.0
     *
     * Allows a non-admin user to reset their password without web access to the main site.
     * It requires a reset token hash using your username and email
     *
     * auth = (string) (
     *   $username;
     *   $key = hash('sha256', 'email');
     *   auth = hash('sha256', $username . $key);
     * )
     *
     * @param array{
     *     api_format: string,
     *     auth?: string,
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
        if (!Mailer::is_mail_enabled()) {
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

        if ($this->configContainer->get(ConfigurationKeyEnum::SIMPLE_USER_MODE)) {
            throw new AccessDeniedException(
                'simple_user_mode'
            );
        }

        if (!array_key_exists('auth', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'auth')
            );
        }

        // identify the user to modify
        $userId = $this->userRepository->idByResetToken($input['auth']);
        if ($userId <= 0) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    'Bad Request',
                    self::ACTION,
                    'input'
                )
            );

            return $response;
        }

        $updateUser = $this->modelFactory->createUser($userId);

        // no resets for admin users
        if ($updateUser->access === AccessLevelEnum::ADMIN->value) {
            return $this->writeBadRequest($response, $output, $apiVersion, $userId, 'system');
        }

        if (empty($updateUser->email)) {
            return $this->writeBadRequest($response, $output, $apiVersion, $userId, 'email');
        }

        // Do not acknowledge a password has been sent or failed
        $this->newPasswordSender->send($updateUser->email, Core::get_user_ip());

        $response->getBody()->write(
            $output->success($apiVersion, 'success')
        );

        return $response;
    }

    private function writeBadRequest(
        ResponseInterface $response,
        ApiOutputInterface $output,
        int $apiVersion,
        int $userId,
        string $type,
    ): ResponseInterface {
        $response->getBody()->write(
            $output->error(
                $apiVersion,
                ErrorCodeEnum::BAD_REQUEST,
                sprintf('Bad Request: %s', $userId),
                self::ACTION,
                $type
            )
        );

        return $response;
    }
}
