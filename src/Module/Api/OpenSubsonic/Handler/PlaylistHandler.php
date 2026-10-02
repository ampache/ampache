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

namespace Ampache\Module\Api\OpenSubsonic\Handler;

use Ampache\Module\Api\Api;
use Ampache\Module\Api\OpenSubsonic\OpenSubsonicResponseHandlerInterface;
use Ampache\Module\Api\OpenSubsonic_Api;
use Ampache\Module\Api\OpenSubsonic_Json_Data;
use Ampache\Module\Api\OpenSubsonic_Xml_Data;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Database\Query\Search;
use Ampache\Module\System\Preference;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;

final class PlaylistHandler implements PlaylistHandlerInterface
{
    public function __construct(
        private readonly OpenSubsonicResponseHandlerInterface $responseHandler,
        private readonly OpenSubsonic_Json_Data $openSubsonicJsonData,
        private readonly OpenSubsonic_Xml_Data $openSubsonicXmlData,
    ) {}

    /**
     * createPlaylist
     *
     * Creates (or updates) a playlist.
     * https://opensubsonic.netlify.app/docs/endpoints/createplaylist/
     * @param array<string, mixed> $input
     */
    public function createplaylist(array $input, User $user): void
    {
        $playlistId = OpenSubsonic_Api::getAmpacheId($input['playlistId'] ?? '');
        $name       = $input['name'] ?? '';
        $songIdList = $input['songId'] ?? [];
        if (isset($input['songId']) && is_string($input['songId'])) {
            $songIdList = explode(',', $input['songId']);
        }

        if ($playlistId !== null) {
            // creating over an existing id rewrites that playlist, so it needs the same owner gate as updateplaylist
            $playlist = new Playlist($playlistId);
            if ($playlist->isNew()) {
                $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

                return;
            }

            if (!$playlist->has_access($user)) {
                $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);

                return;
            }

            $this->applyPlaylistUpdate($playlistId, $name, $songIdList, [], true, true);
            $this->responseHandler->responseOutput($input, __FUNCTION__);
        } elseif (!empty($name)) {
            $playlistId = Playlist::create($name, 'public', $user->id);
            if ($playlistId !== null) {
                if (count($songIdList) > 0) {
                    $this->applyPlaylistUpdate($playlistId, "", $songIdList, [], true, true);
                }

                // output the new playlist
                $format   = (string) ($input['f'] ?? 'xml');
                $playlist = new Playlist($playlistId);
                if ($format === 'xml') {
                    $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
                    $response = $this->openSubsonicXmlData->addPlaylist($response, $playlist, $user, true);
                } else {
                    $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
                    $response = $this->openSubsonicJsonData->addPlaylist($response, $playlist, $user, true);
                }
                $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
            } else {
                $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_GENERIC, __FUNCTION__);
            }
        } else {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_MISSINGPARAM, __FUNCTION__);
        }
    }

    /**
     * deletePlaylist
     *
     * Deletes a saved playlist.
     * https://opensubsonic.netlify.app/docs/endpoints/deleteplaylist/
     * @param array<string, mixed> $input
     */
    public function deleteplaylist(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $playlist = OpenSubsonic_Api::getAmpacheObject($sub_id);
        if (
            (!($playlist instanceof Playlist || $playlist instanceof Search))
            || $playlist->isNew()
        ) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        if (!$playlist->has_access($user)) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);

            return;
        }

        $playlist->delete();

        $this->responseHandler->responseOutput($input, __FUNCTION__);
    }

    /**
     * getPlaylist
     *
     * Returns a listing of files in a saved playlist.
     * https://opensubsonic.netlify.app/docs/endpoints/getplaylist/
     * @param array<string, mixed> $input
     */
    public function getplaylist(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $playlist = OpenSubsonic_Api::getAmpacheObject($sub_id);
        if (
            (!($playlist instanceof Playlist || $playlist instanceof Search))
            || $playlist->isNew()
        ) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        // a private list you neither own nor collaborate on is not yours to read
        if ($playlist->type !== 'public' && !$playlist->has_collaborate($user)) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addPlaylist($response, $playlist, $user, true);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addPlaylist($response, $playlist, $user, true);
        }

        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getPlaylists
     *
     * Returns all playlists a user is allowed to play.
     * https://opensubsonic.netlify.app/docs/endpoints/getplaylists/
     * @param array<string, mixed> $input
     */
    public function getplaylists(array $input, User $user): void
    {
        // only an admin may list another user's playlists; their private ones are not public
        $user = (isset($input['username']) && $user->access >= AccessLevelEnum::ADMIN->value)
            ? User::get_from_username($input['username']) ?? $user
            : $user;

        $user_id = $user->id;

        $browse = Api::getBrowse($user);
        $browse->set_type('playlist_search');
        $browse->set_sort('name', 'ASC', false);
        $browse->set_filter('playlist_open', $user_id);

        // hide duplicate searches that match name and user (if enabled)
        if ((bool) Preference::get_by_user($user_id, 'api_hide_dupe_searches') === true) {
            $browse->set_filter('hide_dupe_smartlist', 1);
        }
        // hide playlists starting with the user string (if enabled)
        $hide_string = str_replace('%', '\%', str_replace('_', '\_', (string) Preference::get_by_user($user_id, 'api_hidden_playlists')));
        if (!empty($hide_string)) {
            $browse->set_filter('not_starts_with', $hide_string);
        }

        $results = $browse->get_objects();


        $format  = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->openSubsonicXmlData->addPlaylists($response, $user, $results);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->openSubsonicJsonData->addPlaylists($response, $user, $results);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * updatePlaylist
     *
     * Updates a playlist. Only the owner of a playlist is allowed to update it.
     * https://opensubsonic.netlify.app/docs/endpoints/updateplaylist/
     * @param array<string, mixed> $input
     */
    public function updateplaylist(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'playlistId', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $name              = $input['name'] ?? '';
        $public            = make_bool($input['public'] ?? false);
        $songIdToAdd       = $input['songIdToAdd'] ?? [];
        $songIndexToRemove = $input['songIndexToRemove'] ?? [];

        $object = OpenSubsonic_Api::getAmpacheObject($sub_id);
        if (!$object) {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        if ($object instanceof Playlist) {
            if (!$object->has_access($user)) {
                $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);

                return;
            }
            if (is_string($songIdToAdd)) {
                $songIdToAdd = explode(',', $songIdToAdd);
            }
            if (is_string($songIndexToRemove)) {
                $songIndexToRemove = explode(',', $songIndexToRemove);
            }
            $this->applyPlaylistUpdate($object->getId(), $name, $songIdToAdd, $songIndexToRemove, $public);

            $this->responseHandler->responseOutput($input, __FUNCTION__);
        } else {
            $this->responseHandler->errorOutput($input, OpenSubsonic_Api::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * @param int[]|string[] $songsIdToAdd
     * @param int[]|string[] $songIndexToRemove
     */
    private function applyPlaylistUpdate(
        int $playlist_id,
        string $name,
        array $songsIdToAdd = [],
        array $songIndexToRemove = [],
        bool $public = true,
        bool $clearFirst = false,
    ): void {
        $playlist                 = new Playlist($playlist_id);
        $songsIdToAdd_count       = count($songsIdToAdd);
        $newdata                  = [];
        $newdata['name']          = (!empty($name)) ? $name : $playlist->name;
        $newdata['playlist_type'] = ($public) ? "public" : "private";
        $playlist->update($newdata);
        if ($clearFirst) {
            $playlist->delete_all();
        }

        if ($songsIdToAdd_count > 0) {
            for ($count = 0; $count < $songsIdToAdd_count; ++$count) {
                $ampacheId = OpenSubsonic_Api::getAmpacheId((string) $songsIdToAdd[$count]);
                if ($ampacheId) {
                    $songsIdToAdd[$count] = $ampacheId;
                }
            }
            $playlist->add_songs($songsIdToAdd);
        }
        if (count($songIndexToRemove) > 0) {
            $playlist->regenerate_track_numbers(); // make sure track indexes are in order
            rsort($songIndexToRemove);
            foreach ($songIndexToRemove as $track) {
                $playlist->delete_track_number(((int) $track + 1));
            }
            $playlist->set_items();
            $playlist->regenerate_track_numbers(); // reorder now that the tracks are removed
        }
    }
}
