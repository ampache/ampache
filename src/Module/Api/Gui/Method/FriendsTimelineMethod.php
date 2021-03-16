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
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Repository\UserActivityRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class FriendsTimelineMethod implements MethodInterface
{
    public const ACTION = 'friends_timeline';

    private StreamFactoryInterface $streamFactory;

    private UserActivityRepositoryInterface $userActivityRepository;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        UserActivityRepositoryInterface $userActivityRepository,
        ConfigContainerInterface $configContainer
    ) {
        $this->streamFactory          = $streamFactory;
        $this->userActivityRepository = $userActivityRepository;
        $this->configContainer        = $configContainer;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This get current user friends timeline
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * limit = (integer) //optional
     * since = (integer) UNIXTIME() //optional
     *
     * @return ResponseInterface
     *
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

        $activityIds = $this->userActivityRepository->getFriendsActivities(
            $gatekeeper->getUser()->getId(),
            (int) ($input['limit'] ?? 0),
            (int) ($input['since'] ?? 0)
        );

        if ($activityIds === []) {
            $result = $output->emptyResult('activity');
        } else {
            $result = $output->timeline($activityIds);
        }

        return $response->withBody(
            $this->streamFactory->createStream($result)
        );
    }
}
