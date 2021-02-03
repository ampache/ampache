<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 *  LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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

namespace Ampache\Module\Api\Method;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\FunctionDisabledException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\User\Following\UserFollowTogglerInterface;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class ToggleFollowMethod implements MethodInterface
{
    public const ACTION = 'toggle_follow';

    private StreamFactoryInterface $streamFactory;

    private UserFollowTogglerInterface $userFollowToggler;

    private ConfigContainerInterface $configContainer;

    private UserRepositoryInterface $userRepository;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        UserFollowTogglerInterface $userFollowToggler,
        ConfigContainerInterface $configContainer,
        UserRepositoryInterface $userRepository
    ) {
        $this->streamFactory     = $streamFactory;
        $this->userFollowToggler = $userFollowToggler;
        $this->configContainer   = $configContainer;
        $this->userRepository    = $userRepository;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This will follow/unfollow a user
     *
     * @param array $input
     * username = (string) $username
     *
     * @return ResponseInterface
     *
     * @throws FunctionDisabledException
     * @throws RequestParamMissingException
     * @throws ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::SOCIABLE) === false) {
            throw new FunctionDisabledException(
                T_('Enable: sociable')
            );
        }

        $username = $input['username'] ?? null;

        if ($username === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'username')
            );
        }

        $userId = $this->userRepository->findByUsername((string) $username);
        if ($userId === null) {
            throw new ResultEmptyException((string) $username);
        }

        $this->userFollowToggler->toggle(
            $userId,
            $gatekeeper->getUser()->getId()
        );

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->success(
                    sprintf('follow toggled for: %d', $userId)
                )
            )
        );
    }
}
