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
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\Check\PrivilegeCheckerInterface;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Returns the list of songs in the localplay instance
 */
final class LocalplaySongsMethod implements MethodInterface
{
    public const string ACTION = 'localplay_songs';

    private ConfigContainerInterface $configContainer;
    private PrivilegeCheckerInterface $privilegeChecker;

    public function __construct(
        ConfigContainerInterface $configContainer,
        PrivilegeCheckerInterface $privilegeChecker,
    ) {
        $this->configContainer  = $configContainer;
        $this->privilegeChecker = $privilegeChecker;
    }

    /**
     * MINIMUM_API_VERSION=5.0.0
     *
     * get the list of songs in your localplay instance
     *
     * @param array{
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessFailedException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        // localplay is actually meant to be behind permissions
        $level = AccessLevelEnum::from(
            (int) ($this->configContainer->get('localplay_level') ?? AccessLevelEnum::ADMIN->value)
        );

        if (!$this->privilegeChecker->check(AccessTypeEnum::LOCALPLAY, $level, $user->getId())) {
            throw new AccessFailedException(
                sprintf('Require: %s', $level->value)
            );
        }

        // Load their Localplay instance
        $localplay = new LocalPlay((string) ($this->configContainer->get('localplay_controller') ?? ''));
        if (empty($localplay->type) || !$localplay->connect()) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    'Unable to connect to localplay controller',
                    self::ACTION,
                    'account'
                )
            );

            return $response;
        }

        // Pull the current playlist and return the objects
        $songs = $localplay->get();
        if (empty($songs)) {
            $response->getBody()->write(
                $output->writeEmpty($apiVersion, 'localplay_songs')
            );

            return $response;
        }

        $response->getBody()->write(
            $output->objectArray(
                $apiVersion,
                ['localplay_songs' => $songs],
                $songs,
                'localplay_songs'
            )
        );

        return $response;
    }
}
