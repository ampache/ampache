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
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class LastShoutsMethod implements MethodInterface
{
    public const ACTION = 'last_shouts';

    private const LIMIT = 10;

    private UserRepositoryInterface $userRepository;

    private ShoutRepositoryInterface $shoutRepository;

    private StreamFactoryInterface $streamFactory;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        UserRepositoryInterface $userRepository,
        ShoutRepositoryInterface $shoutRepository,
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer
    ) {
        $this->userRepository  = $userRepository;
        $this->shoutRepository = $shoutRepository;
        $this->streamFactory   = $streamFactory;
        $this->configContainer = $configContainer;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This get the latest posted shouts
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * username = (string) $username //optional
     * limit = (integer) $limit //optional
     *
     * @return ResponseInterface
     *
     * @throws FunctionDisabledException
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
        $limit = (int) ($input['limit'] ?? 0);
        if ($limit < 1) {
            $limit = $this->configContainer->getPopularThreshold(static::LIMIT);
        }
        $shouts = $this->shoutRepository->getTop(
            $limit,
            $this->userRepository->findByUsername($input['username'])
        );

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->shouts($shouts)
            )
        );
    }
}
