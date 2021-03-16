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
use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Playback\Localplay\LocalPlayControllerFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class LocalplaySongsMethod implements MethodInterface
{
    public const ACTION = 'localplay_songs';

    private StreamFactoryInterface $streamFactory;

    private ConfigContainerInterface $configContainer;

    private LocalPlayControllerFactoryInterface $localPlayControllerFactory;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer,
        LocalPlayControllerFactoryInterface $localPlayControllerFactory
    ) {
        $this->streamFactory              = $streamFactory;
        $this->configContainer            = $configContainer;
        $this->localPlayControllerFactory = $localPlayControllerFactory;
    }

    /**
     * MINIMUM_API_VERSION=5.0.0
     *
     * get the list of songs in your localplay instance
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
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
        // localplay is actually meant to be behind permissions
        $level = $this->configContainer->getLocalplayLevel();

        if ($gatekeeper->mayAccess(AccessLevelEnum::TYPE_LOCALPLAY, $level) === false) {
            throw new AccessDeniedException(
                T_('Require: 100')
            );
        }

        // Load their Localplay instance
        $localPlay = $this->localPlayControllerFactory->create();
        if (!$localPlay->connect()) {
            throw new RequestParamMissingException(
                T_('Unable to connect to localplay controller')
            );
        }

        // Pull the current playlist and return the objects
        $songs = $localPlay->get();
        if ($songs === []) {
            $result = $output->emptyResult('localplay_songs');
        } else {
            $result = $output->object_array($songs, 'localplay_songs');
        }

        return $response->withBody(
            $this->streamFactory->createStream($result)
        );
    }
}
