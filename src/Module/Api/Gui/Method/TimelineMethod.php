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
use Ampache\Module\Api\Gui\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Preference\UserPreferenceRetrieverInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class TimelineMethod implements MethodInterface
{
    public const ACTION = 'timeline';

    private ConfigContainerInterface $configContainer;

    private UserRepositoryInterface $userRepository;

    private UserActivityRepositoryInterface $userActivityRepository;

    private UserPreferenceRetrieverInterface $userPreferenceRetriever;

    private StreamFactoryInterface $streamFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UserRepositoryInterface $userRepository,
        UserActivityRepositoryInterface $userActivityRepository,
        UserPreferenceRetrieverInterface $userPreferenceRetriever,
        StreamFactoryInterface $streamFactory
    ) {
        $this->configContainer         = $configContainer;
        $this->userRepository          = $userRepository;
        $this->userActivityRepository  = $userActivityRepository;
        $this->userPreferenceRetriever = $userPreferenceRetriever;
        $this->streamFactory           = $streamFactory;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This gets a user timeline from their username
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * username = (string)
     * limit    = (integer) //optional
     * since    = (integer) UNIXTIME() //optional
     *
     * @return ResponseInterface
     *
     * @throws AccessDeniedException
     * @throws ResultEmptyException
     * @throws RequestParamMissingException
     * @throws FunctionDisabledException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SOCIABLE) === false) {
            throw new FunctionDisabledException(T_('Enable: sociable'));
        }

        $username = $input['username'] ?? null;

        if ($username === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'username')
            );
        }


        $userId = $this->userRepository->findByUsername($username);
        if ($userId === null) {
            throw new ResultEmptyException($username);
        }
        if (!$this->userPreferenceRetriever->retrieve($userId, 'allow_personal_info_recent')) {
            throw new AccessDeniedException();
        }
        $activityIds = $this->userActivityRepository->getActivities(
            $userId,
            (int) ($input['limit'] ?? 0),
            (int) ($input['since'] ?? 0)
        );

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->timeline($activityIds)
            )
        );
    }
}
