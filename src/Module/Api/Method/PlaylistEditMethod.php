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

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Method\Exception\AccessDeniedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class PlaylistEditMethod implements MethodInterface
{
    public const ACTION = 'playlist_edit';

    private StreamFactoryInterface $streamFactory;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        ModelFactoryInterface $modelFactory
    ) {
        $this->streamFactory = $streamFactory;
        $this->modelFactory  = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=400001
     * CHANGED_IN_API_VERSION=400003
     *
     * This modifies name and type of playlist.
     * Changed name and type to optional and the playlist id is mandatory
     *
     * @param GatekeeperInterface $gatekeeper
     * @param ResponseInterface $response
     * @param ApiOutputInterface $output
     * @param array $input
     * filter = (string) UID of playlist
     * name   = (string) 'new playlist name' //optional
     * type   = (string) 'public', 'private' //optional
     * items  = (string) comma-separated song_id's (replace existing items with a new object_id) //optional
     * tracks = (string) comma-separated playlisttrack numbers matched to items in order //optional
     * sort   = (integer) 0,1 sort the playlist by 'Artist, Album, Song' //optional
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
        $filter = $input['filter'] ?? null;

        if ($filter === null) {
            throw new RequestParamMissingException(
                sprintf(T_('Bad Request: %s'), 'filter')
            );
        }

        $playlist = $this->modelFactory->createPlaylist((int) $filter);

        // don't continue if you didn't actually get a playlist or the access level
        if (
            $playlist->isNew() || (
                !$playlist->has_access($gatekeeper->getUser()->getId()) &&
                !$gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN)
            )
        ) {
            throw new AccessDeniedException(
                T_('Require: 100')
            );
        }

        $name  = $input['name'] ?? '';
        $type  = $input['type'] ?? '';
        $items = explode(',', (string) ($input['items'] ?? ''));
        $order = explode(',', (string) ($input['tracks'] ?? ''));
        $sort  = (int) ($input['sort'] ?? 0);
        // calculate whether we are editing the track order too
        $playlist_edit = [];
        if (count($items) == count($order) && $items !== []) {
            $playlist_edit = array_combine($order, $items);
        }

        // update name/type
        if ($name || $type) {
            $playlist->update([
                'name' => $name,
                'pl_type' => $type,
            ]);
        }
        $change_made = false;
        // update track order with new id's
        foreach ($playlist_edit as $track => $song) {
            if ($song > 0 && $track > 0) {
                $playlist->set_by_track_number((int) $song, (int) $track);
                $change_made = true;
            }
        }
        if ($sort > 0) {
            $playlist->sort_tracks();
            $change_made = true;
        }
        // if you didn't make any changes; tell me
        if (!($name || $type) && !$change_made) {
            throw new RequestParamMissingException(T_('Bad Request'));
        }

        return $response->withBody(
            $this->streamFactory->createStream(
                $output->success(T_('playlist changes saved'))
            )
        );
    }
}
