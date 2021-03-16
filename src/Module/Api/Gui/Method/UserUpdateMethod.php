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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Preference\UserPreferenceUpdaterInterface;
use Ampache\Module\User\UserStateTogglerInterface;
use Ampache\Module\Util\Mailer;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class UserUpdateMethod implements MethodInterface
{
    public const ACTION = 'user_update';

    private UserStateTogglerInterface $userStateToggler;

    private StreamFactoryInterface $streamFactory;

    private UserRepositoryInterface $userRepository;

    private ModelFactoryInterface $modelFactory;

    private ConfigContainerInterface $configContainer;

    private UserPreferenceUpdaterInterface $userPreferenceUpdater;

    public function __construct(
        UserStateTogglerInterface $userStateToggler,
        StreamFactoryInterface $streamFactory,
        UserRepositoryInterface $userRepository,
        ModelFactoryInterface $modelFactory,
        ConfigContainerInterface $configContainer,
        UserPreferenceUpdaterInterface $userPreferenceUpdater
    ) {
        $this->userStateToggler         = $userStateToggler;
        $this->streamFactory            = $streamFactory;
        $this->userRepository           = $userRepository;
        $this->modelFactory             = $modelFactory;
        $this->configContainer          = $configContainer;
        $this->userPreferenceUpdater    = $userPreferenceUpdater;
    }

    /**
     * MINIMUM_API_VERSION=400001
     *
     * Update an existing user.
     * Takes the username with optional parameters.
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * username   = (string) $username
     * password   = (string) hash('sha256', $password)) //optional
     * fullname   = (string) $fullname //optional
     * email      = (string) $email //optional
     * website    = (string) $website //optional
     * state      = (string) $state //optional
     * city       = (string) $city //optional
     * disable    = (integer) 0,1 true to disable, false to enable //optional
     * maxbitrate = (integer) $maxbitrate //optional
     *
     * @return ResponseInterface
     *
     * @throws AccessDeniedException
     * @throws RequestParamMissingException
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

        $username = $input['username'] ?? null;

        if ($username === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'username')
            );
        }

        $userId = $this->userRepository->findByUsername($username);

        if ($userId === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), $username)
            );
        }
        $user = $this->modelFactory->createUser($userId);

        $fullname   = $input['fullname'] ?? null;
        $email      = $input['email'] ?? null;
        $website    = $input['website'] ?? null;
        $password   = $input['password'] ?? null;
        $state      = $input['state'] ?? null;
        $city       = $input['city'] ?? null;
        $disable    = $input['disable'] ?? null;
        $maxbitrate = $input['maxbitrate'] ?? null;

        if ($password && $user->access >= AccessLevelEnum::LEVEL_ADMIN) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), $username)
            );
        }

        if ($password && $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SIMPLE_USER_MODE) === false) {
            $user->update_password('', $password);
        }
        if ($fullname) {
            $user->update_fullname($fullname);
        }
        if (Mailer::validate_address($email)) {
            $user->update_email($email);
        }
        if ($website) {
            $user->update_website($website);
        }
        if ($state) {
            $user->update_state($state);
        }
        if ($city) {
            $user->update_city($city);
        }
        if ($disable === '1') {
            $this->userStateToggler->disable($user);
        } elseif ($disable === '0') {
            $this->userStateToggler->enable($user);
        }
        if ((int) $maxbitrate > 0) {
            $this->userPreferenceUpdater->update('transcode_bitrate', $userId, (int) $maxbitrate);
        }

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->success(
                    sprintf('successfully updated: %s', $username)
                )
            )
        );
    }
}
