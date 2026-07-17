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

use Ampache\Module\Api\Authentication\GatekeeperInterface;
use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Module\Api\Method\Exception\AccessFailedException;
use Ampache\Module\Api\Method\Exception\RequestParamMissingException;
use Ampache\Module\Api\Method\Exception\ResultEmptyException;
use Ampache\Module\Api\Output\ApiOutputInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ResponseInterface;

/**
 * Modifies the name, type, owner and track order of a playlist
 */
final class PlaylistEditMethod implements MethodInterface
{
    public const string ACTION = 'playlist_edit';

    public const string REST_ACTION = 'playlists_edit';

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory,
    ) {
        $this->modelFactory = $modelFactory;
    }

    /**
     * MINIMUM_API_VERSION=400001
     * CHANGED_IN_API_VERSION=400003
     *
     * This modifies name and type of playlist.
     * Changed name and type to optional and the playlist id is mandatory
     *
     * filter = (string) UID of playlist
     * name   = (string) 'new playlist name' //optional
     * type   = (string) 'public', 'private' //optional
     * owner  = (integer) Change playlist owner to the user id (-1 = System playlist) //optional
     * items  = (string) comma-separated song_id's (replace existing items with a new object_id) //optional
     * tracks = (string) comma-separated playlisttrack numbers matched to items in order //optional
     * sort   = (integer) 0,1 sort the playlist by 'Artist, Album, Song' //optional
     *
     * @param array{
     *     filter?: string,
     *     name?: string,
     *     type?: string,
     *     owner?: int|string,
     *     items?: string,
     *     tracks?: string,
     *     sort?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @throws AccessFailedException|RequestParamMissingException|ResultEmptyException
     */
    public function handle(
        GatekeeperInterface $gatekeeper,
        ResponseInterface $response,
        ApiOutputInterface $output,
        array $input,
        User $user,
        int $apiVersion,
    ): ResponseInterface {
        if (!array_key_exists('filter', $input)) {
            throw new RequestParamMissingException(
                sprintf('Bad Request: %s', 'filter')
            );
        }

        $items = explode(',', html_entity_decode((string) ($input['items'] ?? '')));
        $order = explode(',', html_entity_decode((string) ($input['tracks'] ?? '')));
        $sort  = (int) ($input['sort'] ?? 0);

        // calculate whether we are editing the track order too
        $playlistEdit = [];
        if (count($items) === count($order)) {
            $playlistEdit = array_combine($order, $items);
        }

        $objectId = (int) $input['filter'];
        $playlist = $this->modelFactory->createPlaylist($objectId);

        if ($playlist->isNew()) {
            throw new ResultEmptyException(
                (string) $objectId
            );
        }

        $hasAccess = $playlist->has_access($user);
        $hasCollab = $playlist->has_collaborate($user);

        $changeMade = false;
        if (
            $hasCollab
            && $playlistEdit !== []
        ) {
            foreach ($playlistEdit as $track => $song) {
                if ($song > 0 && $track > 0) {
                    $playlist->set_by_track_number((int) $song, (int) $track);
                    $changeMade = true;
                }
            }
        }

        // don't continue if you don't actually have the access level to edit
        if (!$hasAccess) {
            if ($changeMade) {
                // has_collaborate allows playlist track editing
                $response->getBody()->write(
                    $output->success($apiVersion, 'playlist track changes saved')
                );

                return $response;
            }

            // you didn't have edit access
            throw new AccessFailedException(
                sprintf('Require: %s', AccessLevelEnum::ADMIN->value)
            );
        }

        $name  = $input['name'] ?? $playlist->name;
        $type  = $input['type'] ?? $playlist->type;
        $owner = $input['owner'] ?? $playlist->user;
        if ((int) $owner === 0) {
            $lookup = User::get_from_username((string) $owner);
            $owner  = $lookup->id ?? $playlist->user;
        }

        // update name/type
        if (
            $name !== $playlist->name
            || $type !== $playlist->type
            || $owner !== $playlist->user
        ) {
            $playlist->update([
                'name' => $name,
                'playlist_type' => $type,
                'playlist_user' => (int) $owner,
            ]);
            $changeMade = true;
        }

        if ($sort > 0) {
            $playlist->sort_tracks();
            $changeMade = true;
        }

        // if you didn't make any changes; tell me
        if (!$changeMade) {
            $response->getBody()->write(
                $output->error(
                    $apiVersion,
                    ErrorCodeEnum::BAD_REQUEST,
                    'Bad Request',
                    self::ACTION,
                    'input'
                )
            );

            return $response;
        }

        $response->getBody()->write(
            $output->success($apiVersion, 'playlist changes saved')
        );

        return $response;
    }
}
