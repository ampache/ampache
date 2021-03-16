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
use Ampache\Module\Api\Gui\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Gui\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Gui\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Gui\Method\Lib\LocalPlayCommandMapperInterface;
use Ampache\Module\Api\Gui\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Playback\Localplay\LocalPlayControllerFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class LocalplayMethod implements MethodInterface
{
    public const ACTION = 'localplay';

    private StreamFactoryInterface $streamFactory;

    private ConfigContainerInterface $configContainer;

    private LocalPlayCommandMapperInterface $localPlayCommandMapper;

    private LocalPlayControllerFactoryInterface $localPlayControllerFactory;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ConfigContainerInterface $configContainer,
        LocalPlayCommandMapperInterface $localPlayCommandMapper,
        LocalPlayControllerFactoryInterface $localPlayControllerFactory
    ) {
        $this->streamFactory              = $streamFactory;
        $this->configContainer            = $configContainer;
        $this->localPlayCommandMapper     = $localPlayCommandMapper;
        $this->localPlayControllerFactory = $localPlayControllerFactory;
    }

    /**
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=5.0.0
     *
     * This is for controlling Localplay
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * command = (string) 'next', 'prev', 'stop', 'play', 'pause', 'add', 'volume_up', 'volume_down', 'volume_mute', 'delete_all', 'skip', 'status'
     * oid     = (integer) object_id //optional
     * type    = (string) 'Song', 'Video', 'Podcast_Episode', 'Channel', 'Broadcast', 'Democratic', 'Live_Stream' //optional
     * clear   = (integer) 0,1 Clear the current playlist before adding //optional
     *
     * @return ResponseInterface
     *
     * @throws RequestParamMissingException
     * @throws AccessDeniedException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        $command = $input['command'] ?? null;

        if ($command === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'command')
            );
        }

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

        $commandCallable = $this->localPlayCommandMapper->map($command);

        if ($commandCallable === null) {
            throw new RequestParamMissingException(
                T_('Bad Request')
            );
        }

        $result = $commandCallable(
            $localPlay,
            $object_id = (int) ($input['oid'] ?? 0),
            (string) ($input['type'] ?? 'Song'),
            (int) ($input['clear'] ?? 0)
        );

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->dict(
                    ['localplay' => ['command' => [$command => $result]]]
                )
            )
        );
    }
}
