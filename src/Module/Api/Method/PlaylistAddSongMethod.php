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
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\DuplicateItemException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class PlaylistAddSongMethod implements MethodInterface
{
    public const ACTION = 'playlist_add_song';

    private StreamFactoryInterface $streamFactory;

    private ModelFactoryInterface $modelFactory;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ModelFactoryInterface $modelFactory,
        ConfigContainerInterface $configContainer
    ) {
        $this->streamFactory   = $streamFactory;
        $this->modelFactory    = $modelFactory;
        $this->configContainer = $configContainer;
    }

    /**
     * MINIMUM_API_VERSION=380001
     *
     * This adds a song to a playlist
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter = (string) UID of playlist
     * song   = (string) UID of song to add to playlist
     * check  = (integer) 0,1 Check for duplicates //optional, default = 0
     *
     * @return ResponseInterface
     *
     * @throws DuplicateItemException
     * @throws AccessDeniedException
     * @throws RequestParamMissingException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input
    ): ResponseInterface {
        $playlistId = $input['filter'] ?? null;
        $songId     = $input['song'] ?? null;

        if ($playlistId === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'filter')
            );
        }
        if ($songId === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'song')
            );
        }

        $playlist = $this->modelFactory->createPlaylist((int) $playlistId);
        if (
            !$playlist->has_access($gatekeeper->getUser()->getId()) &&
            !$gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
        ) {
            throw new AccessDeniedException(T_('Require: 100'));
        }
        if (
            (
                $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::UNIQUE_PLAYLIST) ||
                (int) ($input['check'] ?? 0) == 1
            ) &&
            in_array($songId, $playlist->get_songs())
        ) {
            throw new DuplicateItemException(
                sprintf(T_('Bad Request: %s'), $songId)
            );
        }
        $playlist->add_songs([(int) $songId], true);

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->success('song added to playlist')
            )
        );
    }
}
