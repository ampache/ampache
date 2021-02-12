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

namespace Ampache\Module\Api\Output;

use Ampache\Module\Api\Json_Data;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;

final class JsonOutput implements ApiOutputInterface
{
    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ModelFactoryInterface $modelFactory
    ) {
        $this->modelFactory = $modelFactory;
    }

    /**
     * At the moment, this method just acts as a proxy
     */
    public function error(int $code, string $message, string $action, string $type): string
    {
        return Json_Data::error(
            $code,
            $message,
            $action,
            $type
        );
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $albums
     * @param array $include
     * @param int|null $user_id
     * @param bool $encode
     * @param int $limit
     * @param int $offset
     *
     * @return array|string
     */
    public function albums(
        array $albums,
        array $include = [],
        ?int $user_id = null,
        bool $encode = true,
        int $limit = 0,
        int $offset = 0
    ) {
        Json_Data::set_offset($offset);
        Json_Data::set_limit($limit);

        return Json_Data::albums($albums, $include, $user_id, $encode);
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param string $type object type
     *
     * @return string return empty JSON message
     */
    public function emptyResult(string $type): string
    {
        return Json_Data::empty($type);
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $artists
     * @param array $include
     * @param null|int $user_id
     * @param boolean $encode
     * @param boolean $object (whether to return as a named object array or regular array)
     * @param int $limit
     * @param int $offset
     *
     * @return array|string JSON Object "artist"
     */
    public function artists(
        array $artists,
        array $include = [],
        ?int $user_id = null,
        bool $encode = true,
        bool $object = true,
        int $limit = 0,
        int $offset = 0
    ) {
        Json_Data::set_offset($offset);
        Json_Data::set_limit($limit);

        return Json_Data::artists(
            $artists,
            $include,
            $user_id,
            $encode,
            $object
        );
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $songs
     * @param int|null $user_id
     * @param boolean $encode
     * @param boolean $object (whether to return as a named object array or regular array)
     * @param boolean $full_xml
     * @param int $limit
     * @param int $offset
     *
     * @return array|string
     */
    public function songs(
        array $songs,
        ?int $user_id = null,
        bool $encode = true,
        bool $object = true,
        bool $full_xml = true,
        int $limit = 0,
        int $offset = 0
    ) {
        Json_Data::set_offset($offset);
        Json_Data::set_limit($limit);

        return Json_Data::songs(
            $songs,
            $user_id,
            $encode,
            $object
        );
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $users User identifier list
     *
     * @return string
     */
    public function users(array $users): string
    {
        return Json_Data::users($users);
    }

    /**
     * This handles creating a result for a shout list
     *
     * @param int[] $shoutIds Shout identifier list
     */
    public function shouts(array $shoutIds): string
    {
        $result = [];
        foreach ($shoutIds as $shoutId) {
            $shout = $this->modelFactory->createShoutbox($shoutId);
            $user  = $this->modelFactory->createUser((int) $shout->user);

            $result[] = [
                'id' => (string) $shoutId,
                'date' => $shout->date,
                'text' => $shout->text,
                'user' => [
                    'id' => (string) $shout->user,
                    'username' => $user->username
                ]
            ];
        }

        return json_encode(['shout' => $result], JSON_PRETTY_PRINT);
    }

    /**
     * This handles creating an JSON document for a user
     */
    public function user(User $user, bool $fullinfo): string
    {
        $user->format();
        if ($fullinfo) {
            $JSON = [
                'id' => (string) $user->id,
                'username' => $user->username,
                'auth' => $user->apikey,
                'email' => $user->email,
                'access' => (int) $user->access,
                'fullname_public' => (int) $user->fullname_public,
                'validation' => $user->validation,
                'disabled' => (int) $user->disabled,
                'create_date' => (int) $user->create_date,
                'last_seen' => (int) $user->last_seen,
                'website' => $user->website,
                'state' => $user->state,
                'city' => $user->city
            ];
        } else {
            $JSON = [
                'id' => (string) $user->id,
                'username' => $user->username,
                'create_date' => (int) $user->create_date,
                'last_seen' => (int) $user->last_seen,
                'website' => $user->website,
                'state' => $user->state,
                'city' => $user->city
            ];
        }

        if ($user->fullname_public) {
            $JSON['fullname'] = $user->fullname;
        }
        $output = ['user' => $JSON];

        return json_encode($output, JSON_PRETTY_PRINT);
    }

    /**
     * This returns genres to the user
     *
     * @param int[] $tagIds
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     */
    public function genres(
        array $tagIds,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        if ((count($tagIds) > $limit || $offset > 0) && $limit) {
            $tagIds = array_splice($tagIds, $offset, $limit);
        }

        $result = [];
        foreach ($tagIds as $tagId) {
            $tag    = $this->modelFactory->createTag($tagId);
            $counts = $tag->count();

            $result[] = [
                'id' => (string) $tagId,
                'name' => $tag->name,
                'albums' => (int) $counts['album'],
                'artists' => (int) $counts['artist'],
                'songs' => (int) $counts['song'],
                'videos' => (int) $counts['video'],
                'playlists' => (int) $counts['playlist'],
                'live_streams' => (int) $counts['live_stream']
            ];
        }
        $output = ($asObject) ? ['genre' => $result] : $result[0];

        return json_encode($output, JSON_PRETTY_PRINT);
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $videoIds
     * @param int|null $userId
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public function videos(array $videoIds, ?int $userId = null, $object = true): string
    {
        return Json_Data::videos($videoIds, $userId, $object);
    }

    public function success(string $string, array $return_data = []): string
    {
        $message = ['success' => $string];
        foreach ($return_data as $title => $data) {
            $message[$title] = $data;
        }

        return json_encode($message, JSON_PRETTY_PRINT);
    }

    /**
     * This returns licenses to the user
     *
     * @param int[] $licenseIds
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     */
    public function licenses(
        array $licenseIds,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        if ((count($licenseIds) > $limit || $offset > 0) && $limit) {
            $licenseIds = array_splice($licenseIds, $offset, $limit);
        }

        $result = [];

        foreach ($licenseIds as $licenseId) {
            $license = $this->modelFactory->createLicense($licenseId);

            $result[] = [
                'id' => (string) $licenseId,
                'name' => $license->getName(),
                'description' => $license->getDescription(),
                'external_link' => $license->getLink()
            ];
        }
        $output = ($asObject) ? ['license' => $result] : $result[0];

        return json_encode($output, JSON_PRETTY_PRINT);
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $labelIds
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     */
    public function labels(
        array $labelIds,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        return Json_Data::labels(
            $labelIds,
            $asObject,
            $limit,
            $offset
        );
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $podcasts
     * @param int $userId
     * @param bool $episodes include the episodes of the podcast
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     */
    public function podcasts(
        array $podcasts,
        int $userId,
        bool $episodes = false,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        return Json_Data::podcasts(
            $podcasts,
            $userId,
            $episodes,
            $asObject,
            $limit,
            $offset
        );
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $podcastEpisodeIds
     * @param int $userId
     * @param bool $simple just return the data as an array for pretty somewhere else
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     *
     * @return array|string
     */
    public function podcast_episodes(
        array $podcastEpisodeIds,
        int $userId,
        bool $simple = false,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ) {
        return Json_Data::podcast_episodes(
            $podcastEpisodeIds,
            $userId,
            $simple,
            $asObject,
            $limit,
            $offset
        );
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $playlistIds
     * @param bool $songs
     * @param bool $asObject
     * @param int $limit
     * @param int $offset
     */
    public function playlists(
        array $playlists,
        bool $songs = false,
        bool $asObject = true,
        int $limit = 0,
        int $offset = 0
    ): string {
        return Json_Data::playlists(
            $playlists,
            $songs,
            $asObject,
            $limit,
            $offset
        );
    }

    public function dict(
        array $data,
        bool $xmlOutput = true,
        ?string $tagName = null
    ): string {
        return json_encode(
            $data,
            JSON_PRETTY_PRINT
        );
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $catalogIds group of catalog id's
     * @param bool $asObject (whether to return as a named object array or regular array)
     */
    public function catalogs(array $catalogIds, bool $asObject = true): string
    {
        return Json_Data::catalogs($catalogIds, $asObject);
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $activityIds Activity identifier list
     */
    public function timeline(array $activityIds): string
    {
        return Json_Data::timeline($activityIds);
    }

    /**
     * This returns bookmarks to the user
     *
     * @param int[] $bookmarkIds
     * @param int $limit
     * @param int $offset
     */
    public function bookmarks(
        array $bookmarkIds,
        int $limit = 0,
        int $offset = 0
    ): string {
        if ((count($bookmarkIds) > $limit || $offset > 0) && $limit) {
            $bookmarkIds = array_splice($bookmarkIds, $offset, $limit);
        }

        $result = [];
        foreach ($bookmarkIds as $bookmarkId) {
            $bookmark               = $this->modelFactory->createBookmark($bookmarkId);
            $bookmark_user          = $bookmark->getUserName();
            $bookmark_object_type   = $bookmark->object_type;
            $bookmark_object_id     = $bookmark->object_id;
            $bookmark_position      = $bookmark->position;
            $bookmark_comment       = $bookmark->comment;
            $bookmark_creation_date = $bookmark->creation_date;
            $bookmark_update_date   = $bookmark->update_date;

            $result[] = [
                'id' => (string) $bookmarkId,
                'owner' => $bookmark_user,
                'object_type' => $bookmark_object_type,
                'object_id' => $bookmark_object_id,
                'position' => $bookmark_position,
                'client' => $bookmark_comment,
                'creation_date' => $bookmark_creation_date,
                'update_date' => $bookmark_update_date
            ];
        }

        return json_encode(['bookmark' => $result], JSON_PRETTY_PRINT);
    }

    /**
     * At the moment, this method just acts as a proxy
     *
     * @param int[] $shareIds Share id's to include
     * @param bool  $asAsOject
     */
    public function shares(array $shareIds, bool $asOject = true): string
    {
        return Json_Data::shares(
            $shareIds,
            $asOject
        );
    }
}
