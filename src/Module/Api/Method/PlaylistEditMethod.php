<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

use Ampache\Module\Api\Exception\ErrorCodeEnum;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Ampache\Module\Api\Api;

/**
 * Class PlaylistEditMethod
 * @package Lib\ApiMethods
 */
final class PlaylistEditMethod
{
    public const ACTION = 'playlist_edit';

    /**
     * playlist_edit
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
     *     filter: string,
     *     name?: string,
     *     type?: string,
     *     owner?: int|string,
     *     items?: string,
     *     tracks?: string,
     *     sort?: int,
     *     api_format: string,
     *     auth: string,
     * } $input
     * @param User $user
     * @return bool
     */
    public static function playlist_edit(array $input, User $user): bool
    {
        if (!Api::check_parameter($input, ['filter'], self::ACTION)) {
            return false;
        }
        $items = explode(',', html_entity_decode((string)($input['items'] ?? '')));
        $order = explode(',', html_entity_decode((string)($input['tracks'] ?? '')));
        $sort  = (int)($input['sort'] ?? 0);
        // calculate whether we are editing the track order too
        $playlist_edit = [];
        if (!empty($items) && (count($items) == count($order))) {
            $playlist_edit = array_combine($order, $items);
        }

        ob_end_clean();
        $object_id = (int)$input['filter'];
        $playlist  = new Playlist($object_id);

        if ($playlist->isNew()) {
            /* HINT: Requested object string/id/type ("album", "myusername", "some song title", 1298376) */
            Api::error(sprintf('Not Found: %s', $object_id), ErrorCodeEnum::NOT_FOUND, self::ACTION, 'filter', $input['api_format']);

            return false;
        }

        $has_access = $playlist->has_access($user);
        $has_collab = $playlist->has_collaborate($user);

        $change_made = false;
        if (
            $has_collab &&
            !empty($playlist_edit)
        ) {
            foreach ($playlist_edit as $track => $song) {
                if ($song > 0 && $track > 0) {
                    $playlist->set_by_track_number((int) $song, (int) $track);
                    $change_made = true;
                }
            }
        }

        // don't continue if you don't actually have the access level to edit
        if (!$has_access) {
            if ($change_made) {
                // has_collaborate allows playlist track editing
                Api::message('playlist track changes saved', $input['api_format']);

                return true;
            } else {
                // you didn't have edit access
                Api::error('Require: 100', ErrorCodeEnum::FAILED_ACCESS_CHECK, self::ACTION, 'account', $input['api_format']);

                return false;
            }
        }

        $name  = $input['name'] ?? $playlist->name;
        $type  = $input['type'] ?? $playlist->type;
        $owner = $input['owner'] ?? $playlist->user;
        if ((int)$owner === 0) {
            $lookup = User::get_from_username((string)$owner);
            $owner  = $lookup->id ?? $playlist->user;
        }

        // update name/type
        if (
            $name !== $playlist->name ||
            $type !== $playlist->type ||
            $owner !== $playlist->user
        ) {
            $array = [
                "name" => $name,
                "pl_type" => $type,
                "pl_user" => $owner,
            ];
            $playlist->update($array);
            $change_made = true;
        }

        if ($sort > 0) {
            $playlist->sort_tracks();
            $change_made = true;
        }

        // if you didn't make any changes; tell me
        if (!$change_made) {
            Api::error('Bad Request', ErrorCodeEnum::BAD_REQUEST, self::ACTION, 'input', $input['api_format']);

            return false;
        }

        Api::message('playlist changes saved', $input['api_format']);

        return true;
    }
}
