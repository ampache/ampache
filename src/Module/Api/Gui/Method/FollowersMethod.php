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
use Ampache\Module\Api\Gui\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\UserFollowerRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final class FollowersMethod implements MethodInterface
{
    public const ACTION = 'followers';

    private StreamFactoryInterface $streamFactory;

    private UserFollowerRepositoryInterface $userFollowerRepository;

    private ConfigContainerInterface $configContainer;

    private UserRepositoryInterface $userRepository;

    private LoggerInterface $logger;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        UserFollowerRepositoryInterface $userFollowerRepository,
        ConfigContainerInterface $configContainer,
        UserRepositoryInterface $userRepository,
        LoggerInterface $logger
    ) {
        $this->streamFactory          = $streamFactory;
        $this->userFollowerRepository = $userFollowerRepository;
        $this->configContainer        = $configContainer;
        $this->userRepository         = $userRepository;
        $this->logger                 = $logger;
    }

    /**
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=400004
     *
     * This gets followers of the user
     * Error when user not found or no followers
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * username = (string) $username
     *
     * @return ResponseInterface
     *
     * @throws FunctionDisabledException
     * @throws ResultEmptyException
     * @throws RequestParamMissingException
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

        $userId = $this->userRepository->findByUsername((string) $username);
        if ($userId === null) {
            $this->logger->critical(
                sprintf(
                    'User `%s` cannot be found.',
                    $username
                ),
                [LegacyLogger::CONTEXT_TYPE => __CLASS__]
            );

            throw new ResultEmptyException(
                sprintf(T_('Not Found: %s'), $username)
            );
        }

        $users = $this->userFollowerRepository->getFollowers($userId);
        if ($users === []) {
            $result = $output->emptyResult('user');
        } else {
            $result = $output->users($users);
        }

        return $response->withBody(
            $this->streamFactory->createStream($result)
        );
    }
}
