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

namespace Ampache\Module\Api;

use Ampache\Config\AmpConfig;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Democratic;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Metadata;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Shoutbox;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Useractivity;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\Model\Video;
use Ampache\Repository\PodcastRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Traversable;

/**
 * Json5_Data Class
 *
 * This class takes care of all of the JSON document stuff in Ampache these
 * are all static calls
 *
 */
class Json5_Data
{
    private static int $count  = 0;
    private static ?int $limit = 5000;
    private static int $offset = 0;

    /**
     * albums
     *
     * This echos out a standard albums JSON document, it pays attention to the limit
     *
     * @param array<int|string> $objects Album id's to include
     * @param string[] $include
     * @return string JSON Object "album"
     */
    public static function albums(array $objects, array $include, User $user, string $auth): string
    {
        self::$count = self::$count ?: count($objects);
        $JSON        = self::albums_array($objects, $include, $user, $auth);

        $output = ["album" => $JSON];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * albums_array
     *
     * @param array<int|string> $objects Album id's to include
     * @param string[] $include
     * @return array<int, array{
     *     "id": string,
     *     "name": null|string,
     *     "artist"?: array{
     *         "id": string,
     *         "name": null|string,
     *     }|null,
     *     "artists"?: array<int, array{
     *         "id": string,
     *         "name": null|string,
     *     }>,
     *     "songartists"?: array<int, array{
     *         "id": string,
     *         "name": null|string,
     *     }>,
     *     "time": int,
     *     "year": int,
     *     "tracks": array<int, array<string, mixed>>|string,
     *     "songcount": int,
     *     "diskcount": int,
     *     "type": null|string,
     *     "genre": array<int, array{id: string, name: string}>,
     *     "art": null|string,
     *     "flag": int,
     *     "preciserating": int|null,
     *     "rating": int|null,
     *     "averagerating": float|null,
     *     "mbid": null|string,
     * }> JSON Object "album"
     */
    public static function albums_array(array $objects, array $include, User $user, string $auth, bool $encode = true): array
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit, $encode);

        // original year (fall back to regular year)
        $original_year = AmpConfig::get('use_original_year');

        Rating::build_cache('album', $objects);
        $JSON = [];
        foreach ($objects as $album_id) {
            $album = new Album((int) $album_id);
            if ($album->isNew()) {
                continue;
            }

            $rating      = new Rating($album->id, 'album');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($album->id, 'album');
            $year        = ($original_year && $album->original_year)
                ? $album->original_year
                : $album->year;

            // Build the Art URL, include session
            $art_url = Art::url($album->id, 'album', $auth);

            $objArray = [];

            $objArray["id"]   = (string) $album->id;
            $objArray["name"] = $album->get_fullname();

            if ($album->get_parent_fullname() != "") {
                $objArray['artist'] = [
                    "id" => (string) $album->findAlbumArtist(),
                    "name" => $album->get_parent_fullname()
                ];
            }

            // Handle includes
            $songs = (in_array("songs", $include))
                ? self::songs_array(self::getSongRepository()->getByAlbum($album->id), $user, $auth, false)
                : [];

            $objArray['time']          = (int) $album->time;
            $objArray['year']          = (int) $year;
            $objArray['tracks']        = $songs;
            $objArray['songcount']     = $album->song_count;
            $objArray['diskcount']     = $album->disk_count;
            $objArray['type']          = $album->release_type;
            $objArray['genre']         = self::_genre_array($album->get_tags());
            $objArray['art']           = $art_url;
            $objArray['flag']          = (!$flag->get_flag($user->getId()) ? 0 : 1);
            $objArray['preciserating'] = $user_rating;
            $objArray['rating']        = $user_rating;
            $objArray['averagerating'] = $rating->get_average_rating();
            $objArray['mbid']          = $album->mbid;

            $JSON[] = $objArray;
        }

        return $JSON;
    }

    /**
     * artists
     *
     * This takes an array of artists and then returns a pretty JSON document with the information
     * we want
     *
     * @param array<int|string> $objects Artist id's to include
     * @param string[] $include
     * @param bool $object (whether to return as a named object array or regular array)
     * @return string JSON Object "artist"
     */
    public static function artists(array $objects, array $include, User $user, string $auth, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $JSON        = self::artists_array($objects, $include, $user, $auth);

        $output = ($object) ? ["artist" => $JSON] : $JSON[0] ?? [];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * artists_array
     *
     * @param array<int|string> $objects Artist id's to include
     * @param string[] $include
     * @return array <int, array{
     *     "id": string,
     *     "name": null|string,
     *     "albums": array<int, array{
     *         "id": string,
     *         "name": null|string,
     *         "artist"?: array{
     *             "id": string,
     *             "name": null|string,
     *         }|null,
     *         "artists"?: array<int, array{
     *             "id": string,
     *             "name": null|string,
     *         }>,
     *         "songartists"?: array<int, array{
     *             "id": string,
     *             "name": null|string,
     *         }>,
     *         "time": int,
     *         "year": int,
     *         "tracks": array<int, array<string, mixed>>|string,
     *         "songcount": int,
     *         "diskcount": int,
     *         "type": null|string,
     *         "genre": array<int, array{id: string, name: string}>,
     *         "art": null|string,
     *         "flag": int,
     *         "preciserating": int|null,
     *         "rating": int|null,
     *         "averagerating": float|null,
     *         "mbid": null|string,
     *     }>,
     *     "albumcount": int,
     *     "songs": array<int, array<string, mixed>>,
     *     "songcount": int,
     *     "genre": array<int, array{id: string, name: string}>,
     *     "art": null|string,
     *     "flag": int,
     *     "preciserating": int|null,
     *     "rating": int|null,
     *     "averagerating": float|null,
     *     "mbid": null|string,
     *     "summary": null|string,
     *     "time": int,
     *     "yearformed": int,
     *     "placeformed": null|string,
     * }>
     */
    public static function artists_array(array $objects, array $include, User $user, string $auth, bool $encode = true): array
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit, $encode);

        Rating::build_cache('artist', $objects);
        $JSON = [];
        foreach ($objects as $artist_id) {
            $artist = new Artist((int) $artist_id);
            if ($artist->isNew()) {
                continue;
            }

            $rating      = new Rating($artist->id, 'artist');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($artist->id, 'artist');

            // Build the Art URL, include session
            $art_url = Art::url($artist->id, 'artist', $auth);

            // Handle includes
            $albums = (in_array("albums", $include))
                ? self::albums_array(self::getAlbumRepository()->getAlbumByArtist($artist->id), [], $user, $auth, false)
                : [];
            $songs = (in_array("songs", $include))
                ? self::songs_array(self::getSongRepository()->getByArtist($artist->id), $user, $auth, false)
                : [];

            $JSON[] = [
                "id" => (string) $artist->id,
                "name" => $artist->get_fullname(),
                "albums" => $albums,
                "albumcount" => $artist->album_count,
                "songs" => $songs,
                "songcount" => $artist->song_count,
                "genre" => self::_genre_array($artist->get_tags()),
                "art" => $art_url,
                "flag" => (!$flag->get_flag($user->getId()) ? 0 : 1),
                "preciserating" => $user_rating,
                "rating" => $user_rating,
                "averagerating" => $rating->get_average_rating(),
                "mbid" => $artist->mbid,
                "summary" => $artist->summary,
                "time" => (int) $artist->time,
                "yearformed" => (int) $artist->yearformed,
                "placeformed" => $artist->placeformed
            ];
        }

        return $JSON;
    }

    /**
     * bookmarks
     *
     * This returns bookmarks to the user, in a pretty json document with the information
     *
     * @param int[] $objects Bookmark id's to include
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public static function bookmarks(array $objects, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $bookmarkRepository = self::getBookmarkRepository();

        $JSON = [];
        foreach ($objects as $bookmark_id) {
            $bookmark = $bookmarkRepository->findById($bookmark_id);
            if ($bookmark === null) {
                continue;
            }

            $bookmark_username      = $bookmark->getUserName();
            $bookmark_object_type   = $bookmark->object_type;
            $bookmark_object_id     = (string) $bookmark->object_id;
            $bookmark_position      = $bookmark->position;
            $bookmark_comment       = $bookmark->comment;
            $bookmark_creation_date = $bookmark->creation_date;
            $bookmark_update_date   = $bookmark->update_date;
            // Build this element
            $JSON[] = [
                "id" => (string) $bookmark_id,
                "owner" => $bookmark_username,
                "object_type" => $bookmark_object_type,
                "object_id" => $bookmark_object_id,
                "position" => $bookmark_position,
                "client" => $bookmark_comment,
                "creation_date" => $bookmark_creation_date,
                "update_date" => $bookmark_update_date
            ];
        }
        $output = ($object) ? ["bookmark" => $JSON] : $JSON[0] ?? [];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * catalogs
     *
     * This returns catalogs to the user, in a pretty json document with the information
     *
     * @param int[] $objects group of catalog id's
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public static function catalogs(array $objects, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $JSON = [];
        foreach ($objects as $catalog_id) {
            $catalog = Catalog::create_from_id($catalog_id);
            if ($catalog === null) {
                break;
            }
            $catalog_name           = $catalog->name;
            $catalog_type           = $catalog->catalog_type;
            $catalog_gather_types   = $catalog->gather_types;
            $catalog_enabled        = (int) $catalog->enabled;
            $catalog_last_add       = $catalog->last_add;
            $catalog_last_clean     = $catalog->last_clean;
            $catalog_last_update    = $catalog->last_update;
            $catalog_path           = $catalog->get_f_info();
            $catalog_rename_pattern = $catalog->rename_pattern;
            $catalog_sort_pattern   = $catalog->sort_pattern;
            // Build this element
            $JSON[] = [
                "id" => (string) $catalog_id,
                "name" => $catalog_name,
                "type" => $catalog_type,
                "gather_types" => $catalog_gather_types,
                "enabled" => $catalog_enabled,
                "last_add" => $catalog_last_add,
                "last_clean" => $catalog_last_clean,
                "last_update" => $catalog_last_update,
                "path" => $catalog_path,
                "rename_pattern" => $catalog_rename_pattern,
                "sort_pattern" => $catalog_sort_pattern
            ];
        }
        $output = ($object) ? ["catalog" => $JSON] : $JSON[0] ?? [];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * deleted
     *
     * This handles creating a JSON document for deleted items
     *
     * @param string $object_type ('song', 'podcast_episode', 'video')
     * @param array<int, array{
     *     id: int,
     *     addition_time: int,
     *     delete_time: int,
     *     title: string,
     *     file: string,
     *     catalog: int,
     *     total_count: int,
     *     total_skip: int,
     *     update_time?: int,
     *     album?: int,
     *     artist?: int,
     *     podcast?: int,
     * }> $objects deleted object list
     */
    public static function deleted(string $object_type, array $objects): string
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $JSON = [];
        foreach ($objects as $row) {
            switch ($object_type) {
                case 'song':
                    if (isset($row['album']) && isset($row['artist']) && isset($row['update_time'])) {
                        $objArray = [
                            "id" => (string) $row['id'],
                            "addition_time" => $row['addition_time'],
                            "delete_time" => $row['delete_time'],
                            "title" => $row['title'],
                            "file" => $row['file'],
                            "catalog" => $row['catalog'],
                            "total_count" => $row['total_count'],
                            "total_skip" => $row['total_skip'],
                            "update_time" => $row['update_time'],
                            "album" => (string) $row['album'],
                            "artist" => (string) $row['artist']
                        ];
                        $JSON[] = $objArray;
                    }
                    break;
                case 'podcast_episode':
                    if (isset($row['podcast'])) {
                        $objArray = [
                            "id" => (string) $row['id'],
                            "addition_time" => $row['addition_time'],
                            "delete_time" => $row['delete_time'],
                            "title" => $row['title'],
                            "file" => $row['file'],
                            "catalog" => $row['catalog'],
                            "total_count" => $row['total_count'],
                            "total_skip" => $row['total_skip'],
                            "podcast" => (string) $row['podcast']
                        ];
                        $JSON[] = $objArray;
                    }
                    break;
                case 'video':
                    $objArray = [
                        "id" => (string) $row['id'],
                        "addition_time" => $row['addition_time'],
                        "delete_time" => $row['delete_time'],
                        "title" => $row['title'],
                        "file" => $row['file'],
                        "catalog" => $row['catalog'],
                        "total_count" => $row['total_count'],
                        "total_skip" => $row['total_skip']
                    ];
                    $JSON[] = $objArray;
            }
        }
        $output = ["deleted_" . $object_type => $JSON];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * democratic
     *
     * This handles creating an JSON document for democratic items, this can be a little complicated
     * due to the votes and all of that
     *
     * @param array<int, array{
     *     object_type: LibraryItemEnum,
     *     object_id: int,
     *     track_id: int,
     *     track: int
     * }> $object_ids Object IDs
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public static function democratic(array $object_ids, User $user, string $auth, bool $object = true): string
    {
        $democratic = Democratic::get_current_playlist($user);

        $JSON = [];
        foreach ($object_ids as $data) {
            $className = ObjectTypeToClassNameMapper::map($data['object_type']->value);
            /** @var Song $song */
            $song = new $className($data['object_id']);
            if ($song->isNew()) {
                continue;
            }
            $song->fill_ext_info();

            $rating      = new Rating($song->id, 'song');
            $user_rating = $rating->get_user_rating($user->getId());
            $art_url     = Art::url($song->album, 'album', $auth);
            $songMime    = $song->mime;
            $play_url    = $song->play_url('', 'api', false, $user->id, $user->streamtoken);

            $JSON[] = [
                "id" => (string) $song->id,
                "title" => $song->get_fullname(),
                "artist" => [
                    "id" => (string) $song->artist,
                    "name" => $song->get_parent_fullname()
                ],
                "album" => [
                    "id" => (string) $song->album,
                    "name" => $song->get_album_fullname()
                ],
                "genre" => self::_genre_array($song->get_tags()),
                "track" => (int) $song->track,
                "time" => $song->time,
                "mime" => $songMime,
                "url" => $play_url,
                "size" => $song->size,
                "art" => $art_url,
                "preciserating" => $user_rating,
                "rating" => $user_rating,
                "averagerating" => ($rating->get_average_rating() ?? null),
                "playcount" => $song->total_count,
                "vote" => $democratic->get_vote($data['track_id'])
            ];
        }
        $output = ($object) ? ["song" => $JSON] : $JSON[0] ?? [];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * empty
     *
     * This generates a JSON empty object
     * nothing fancy here...
     *
     * @param string $type object type
     */
    public static function empty(string $type): string
    {
        return json_encode([$type => []], JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * error
     *
     * This generates a JSON Error message
     * nothing fancy here...
     *
     * @param int|string $code Error code
     * @param string $string Error message
     * @param string $action Error method
     * @param string $type Error type
     */
    public static function error(int|string $code, string $string, string $action, string $type): string
    {
        $output = [
            "error" => [
                "errorCode" => (string) $code,
                "errorAction" => $action,
                "errorType" => $type,
                "errorMessage" => $string
            ]
        ];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * genres
     *
     * This returns genres to the user, in a pretty JSON document with the information
     *
     * @param array<int|string> $objects Genre id's to include
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public static function genres(array $objects, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $JSON        = self::genres_array($objects);

        $output = ($object) ? ["genre" => $JSON] : $JSON[0] ?? [];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * genres_array
     *
     * @param array<int|string> $objects
     * @return array<int, array{
     *     "id": string,
     *     "name": null|string,
     *     "albums": int,
     *     "artists": int,
     *     "songs": int,
     *     "videos": int,
     *     "playlists": int,
     *     "live_streams": int
     * }> JSON Object "genre"
     */
    public static function genres_array(array $objects): array
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $JSON = [];
        foreach ($objects as $tag_id) {
            $tag    = new Tag((int) $tag_id);
            $JSON[] = [
                "id" => (string) $tag_id,
                "name" => $tag->name,
                "albums" => $tag->album,
                "artists" => $tag->artist,
                "songs" => $tag->song,
                "videos" => $tag->video,
                "playlists" => 0,
                "live_streams" => 0,
            ];
        }

        return $JSON;
    }

    /**
     * indexes
     *
     * This takes an array of object_ids and return JSON based on the type of object
     *
     * @param array<int|string> $objects Array of object_ids (Mixed string|int)
     * @param string $type 'artist'|'album'|'song'|'playlist'|'share'|'podcast'|'podcast_episode'|'video'|'live_stream'
     * @param bool $include (add the extra songs details if a playlist or podcast_episodes if a podcast)
     * @return string JSON Object "artist"|"album"|"song"|"playlist"|"share"|"podcast"|"podcast_episode"|"video"|"live_stream"
     */
    public static function indexes(array $objects, string $type, User $user, string $auth, bool $include = false): string
    {
        // here is where we call the object type
        switch ($type) {
            case 'song':
                /** @var string $results */
                $results = self::songs($objects, $user, $auth);
                break;
            case 'album':
                $include_array = ($include) ? ['songs'] : [];

                /** @var string $results */
                $results = self::albums($objects, $include_array, $user, $auth);
                break;
            case 'artist':
                $include_array = ($include) ? ['songs', 'albums'] : [];

                /** @var string $results */
                $results = self::artists($objects, $include_array, $user, $auth);
                break;
            case 'playlist':
                /** @var string $results */
                $results = self::playlists($objects, $user, $auth, $include);
                break;
            case 'share':
                /** @var string $results */
                $results = self::shares($objects, $user);
                break;
            case 'podcast':
                /** @var string $results */
                $results = self::podcasts($objects, $user, $auth, $include);
                break;
            case 'podcast_episode':
                /** @var string $results */
                $results = self::podcast_episodes($objects, $user, $auth);
                break;
            case 'video':
                /** @var string $results */
                $results = self::videos($objects, $user, $auth);
                break;
            case 'live_stream':
                /** @var string $results */
                $results = self::live_streams($objects);
                break;
            default:
                return self::error('4710', sprintf('Bad Request: %s', $type), 'indexes', 'type');
        }

        return $results;
    }

    /**
     * labels
     *
     * This returns labels to the user, in a pretty JSON document with the information
     *
     * @param array<int|string> $objects
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public static function labels(array $objects, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $JSON        = self::labels_array($objects);

        $output = ($object) ? ["label" => $JSON] : $JSON[0] ?? [];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * labels_array
     *
     * @param array<int|string> $objects
     * @return array<int, array{
     *     "id": string,
     *     "name": null|string,
     *     "artists": int,
     *     "summary": null|string,
     *     "external_link": string,
     *     "address": null|string,
     *     "category": null|string,
     *     "email": null|string,
     *     "website": null|string,
     *     "user": string,
     * }>
     */
    public static function labels_array(array $objects): array
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $labelRepository = self::getLabelRepository();

        $JSON = [];
        foreach ($objects as $label_id) {
            $label = $labelRepository->findById((int) $label_id);
            if ($label === null) {
                continue;
            }

            $JSON[] = [
                "id" => (string) $label_id,
                "name" => $label->get_fullname(),
                "artists" => $label->get_artist_count(),
                "summary" => $label->summary,
                "external_link" => $label->get_link(),
                "address" => $label->address,
                "category" => $label->category,
                "email" => $label->email,
                "website" => $label->website,
                "user" => (string) $label->user,
            ];
        }

        return $JSON;
    }

    /**
     * licenses
     *
     * This returns licenses to the user, in a pretty JSON document with the information
     *
     * @param array<int|string> $objects Licence id's assigned to songs and artists
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public static function licenses(array $objects, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $licenseRepository = self::getLicenseRepository();

        $JSON = [];
        foreach ($objects as $license_id) {
            $license = $licenseRepository->findById((int) $license_id);

            if ($license !== null) {
                $JSON[] = [
                    'id' => (string) $license->getId(),
                    'name' => $license->getName(),
                    'description' => $license->getDescription(),
                    'external_link' => $license->getExternalLink()
                ];
            }
        }
        $output = ($object) ? ["license" => $JSON] : $JSON[0] ?? [];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * live_streams
     *
     * This returns live_streams to the user, in a pretty JSON document with the information
     *
     * @param array<int|string> $objects
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public static function live_streams(array $objects, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $JSON        = self::live_streams_array($objects);

        $output = ($object) ? ["live_stream" => $JSON] : $JSON[0] ?? [];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * live_streams_array
     *
     * @param array<int|string> $objects
     * @return array<int, array{
     *     "id": string,
     *     "name": null|string,
     *     "url": null|string,
     *     "codec": null|string,
     *     "catalog": string,
     *     "site_url": null|string
     * }>
     */
    public static function live_streams_array(array $objects): array
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $JSON = [];
        foreach ($objects as $live_stream_id) {
            $live_stream = new Live_Stream((int) $live_stream_id);
            if ($live_stream->isNew()) {
                continue;
            }

            $JSON[] = [
                "id" => (string) $live_stream_id,
                "name" => $live_stream->get_fullname(),
                "url" => $live_stream->url,
                "codec" => $live_stream->codec,
                "catalog" => (string) $live_stream->catalog,
                "site_url" => $live_stream->site_url
            ];
        }

        return $JSON;
    }

    /**
     * playlists
     *
     * This takes an array of playlist ids and then returns a nice pretty JSON document
     *
     * @param array<int|string> $objects Playlist id's to include
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public static function playlists(array $objects, User $user, string $auth, bool $songs = false, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $JSON        = self::playlists_array($objects, $user, $auth, $songs);

        $output = ($object) ? ["playlist" => $JSON] : $JSON[0] ?? [];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * playlists_array
     *
     * @param array<int|string> $objects Playlist id's to include
     * @return array<int, array{
     *     "id": string,
     *     "name": null|string,
     *     "owner": null|string,
     *     "items": array<int, array<string, int|string>>|int,
     *     "type": null|string,
     *     "art": null|string,
     *     "flag": int,
     *     "preciserating": int|null,
     *     "rating": int|null,
     *     "averagerating": float|null
     * }>
     */
    public static function playlists_array(array $objects, User $user, string $auth, bool $songs = false): array
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $JSON = [];
        // Foreach the playlist ids
        foreach ($objects as $playlist_id) {
            /**
             * Strip smart_ from playlist id and compare to original
             * smartlist = 'smart_1'
             * playlist = 1000000
             */
            if ((int) $playlist_id === 0) {
                $playlist = new Search((int) str_replace('smart_', '', (string) $playlist_id), 'song', $user);
                if ($playlist->isNew()) {
                    continue;
                }
                $object_type    = 'search';
                $playitem_total = $playlist->last_count;
            } else {
                $playlist = new Playlist((int) $playlist_id);
                if ($playlist->isNew()) {
                    continue;
                }
                $object_type    = 'playlist';
                $playitem_total = $playlist->get_media_count('song');
            }
            $art_url       = Art::url($playlist->id, $object_type, $auth);
            $playlist_name = $playlist->get_fullname();
            $playlist_user = $playlist->username;
            $playlist_type = $playlist->type;

            if ($songs) {
                $items          = [];
                $trackcount     = 1;
                $playlisttracks = $playlist->get_items();
                foreach ($playlisttracks as $track) {
                    $items[] = [
                        "id" => (string) $track['object_id'],
                        "playlisttrack" => $trackcount
                    ];
                    $trackcount++;
                }
            } else {
                $items = $playitem_total ?? 0;
            }
            $rating      = new Rating($playlist->id, $object_type);
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($playlist->id, $object_type);

            // Build this element
            $JSON[] = [
                "id" => (string) $playlist_id,
                "name" => $playlist_name,
                "owner" => $playlist_user,
                "items" => $items,
                "type" => $playlist_type,
                "art" => $art_url,
                "flag" => (!$flag->get_flag($user->getId()) ? 0 : 1),
                "preciserating" => $user_rating,
                "rating" => $user_rating,
                "averagerating" => $rating->get_average_rating()
            ];
        }

        return $JSON;
    }

    /**
     * podcast_episodes
     *
     * This returns podcasts to the user, in a pretty json document with the information
     *
     * @param array<int|string> $objects Podcast_Episode id's to include
     * @param bool $object (whether to return as a named object array or regular array)
     * @return string JSON Object "podcast_episode"
     */
    public static function podcast_episodes(array $objects, User $user, string $auth, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $JSON        = self::podcast_episodes_array($objects, $user, $auth);

        $output = ($object) ? ["podcast_episode" => $JSON] : $JSON[0] ?? [];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * podcast_episodes_array
     *
     * @param array<int|string> $objects Podcast_Episode id's to include
     * @return array<int, array{
     *     "id": string,
     *     "title": null|string,
     *     "name": null|string,
     *     "description": null|string,
     *     "category": null|string,
     *     "author": null|string,
     *     "author_full": null|string,
     *     "website": null|string,
     *     "pubdate": null|string,
     *     "state": string,
     *     "filelength": string,
     *     "filesize": string,
     *     "filename": string,
     *     "mime": null|string,
     *     "time": int,
     *     "size": int,
     *     "public_url": string,
     *     "url": string,
     *     "catalog": string,
     *     "art": null|string,
     *     "flag": int,
     *     "preciserating": int|null,
     *     "rating": int|null,
     *     "averagerating": float|null,
     *     "playcount": int,
     *     "played": string
     * }>
     */
    public static function podcast_episodes_array(array $objects, User $user, string $auth, bool $encode = true): array
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit, $encode);

        $JSON = [];

        foreach ($objects as $episode_id) {
            $episode = new Podcast_Episode((int) $episode_id);
            if ($episode->isNew()) {
                continue;
            }

            $rating      = new Rating($episode->id, 'podcast_episode');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($episode->id, 'podcast_episode');
            $art_url     = Art::url($episode->podcast, 'podcast', $auth);
            $JSON[]      = [
                "id" => (string) $episode_id,
                "title" => $episode->get_fullname(),
                "name" => $episode->get_fullname(),
                "description" => $episode->get_description(),
                "category" => $episode->getCategory(),
                "author" => $episode->getAuthor(),
                "author_full" => $episode->getAuthor(),
                "website" => $episode->getWebsite(),
                "pubdate" => $episode->getPubDate()->format(DATE_ATOM),
                "state" => $episode->getState()->toDescription(),
                "filelength" => $episode->get_f_time(true),
                "filesize" => $episode->getSizeFormatted(),
                "filename" => $episode->getFileName(),
                "mime" => $episode->mime,
                "time" => $episode->time,
                "size" => $episode->size,
                "public_url" => $episode->get_link(),
                "url" => $episode->play_url('', 'api', false, $user->getId(), $user->streamtoken),
                "catalog" => (string) $episode->catalog,
                "art" => $art_url,
                "flag" => (!$flag->get_flag($user->getId()) ? 0 : 1),
                "preciserating" => $user_rating,
                "rating" => $user_rating,
                "averagerating" => $rating->get_average_rating(),
                "playcount" => $episode->total_count,
                "played" => (string) $episode->played
            ];
        }

        return $JSON;
    }

    /**
     * podcasts
     *
     * This returns podcasts to the user, in a pretty json document with the information
     *
     * @param array<int|string> $objects Podcast id's to include
     * @param bool $episodes include the episodes of the podcast
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public static function podcasts(array $objects, User $user, string $auth, bool $episodes = false, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $JSON        = self::podcasts_array($objects, $user, $auth, $episodes);

        $output = ($object) ? ["podcast" => $JSON] : $JSON[0] ?? [];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * podcasts_array
     *
     * @param array<int|string> $objects Podcast id's to include
     * @param bool $episodes include the episodes of the podcast
     * @return array<int, array{
     *     "id": string,
     *     "name": null|string,
     *     "description": string,
     *     "language": string,
     *     "copyright": string,
     *     "feed_url": string,
     *     "generator": string,
     *     "website": string,
     *     "build_date": string,
     *     "sync_date": string,
     *     "public_url": string,
     *     "art": null|string,
     *     "flag": int,
     *     "preciserating": int|null,
     *     "rating": int|null,
     *     "averagerating": float|null,
     *     "podcast_episode": array<int, array{
     *         "id": string,
     *         "title": null|string,
     *         "name": null|string,
     *         "description": null|string,
     *         "category": null|string,
     *         "author": null|string,
     *         "author_full": null|string,
     *         "website": null|string,
     *         "pubdate": null|string,
     *         "state": string,
     *         "filelength": string,
     *         "filesize": string,
     *         "filename": string,
     *         "mime": null|string,
     *         "time": int,
     *         "size": int,
     *         "public_url": string,
     *         "url": string,
     *         "catalog": string,
     *         "art": null|string,
     *         "flag": int,
     *         "preciserating": int|null,
     *         "rating": int|null,
     *         "averagerating": float|null,
     *         "playcount": int,
     *         "played": string
     *     }>
     * }>
     */
    public static function podcasts_array(array $objects, User $user, string $auth, bool $episodes = false): array
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $podcastRepository = self::getPodcastRepository();

        $JSON = [];
        foreach ($objects as $podcast_id) {
            $podcast = $podcastRepository->findById((int) $podcast_id);
            if ($podcast === null) {
                continue;
            }

            $rating              = new Rating((int) $podcast_id, 'podcast');
            $user_rating         = $rating->get_user_rating($user->getId());
            $flag                = new Userflag((int) $podcast_id, 'podcast');
            $art_url             = Art::url((int) $podcast_id, 'podcast', $auth);
            $podcast_name        = $podcast->get_fullname();
            $podcast_description = $podcast->get_description();
            $podcast_language    = scrub_out($podcast->getLanguage());
            $podcast_copyright   = scrub_out($podcast->getCopyright());
            $podcast_feed_url    = $podcast->getFeedUrl();
            $podcast_generator   = scrub_out($podcast->getGenerator());
            $podcast_website     = scrub_out($podcast->getWebsite());
            $podcast_build_date  = $podcast->getLastBuildDate()->format(DATE_ATOM);
            $podcast_sync_date   = $podcast->getLastSyncDate()->format(DATE_ATOM);
            $podcast_public_url  = $podcast->get_link();
            $podcast_episodes    = [];
            if ($episodes) {
                $results          = $podcast->getEpisodeIds();
                $podcast_episodes = self::podcast_episodes_array($results, $user, $auth, false);
            }
            // Build this element
            $JSON[] = [
                "id" => (string) $podcast_id,
                "name" => $podcast_name,
                "description" => $podcast_description,
                "language" => $podcast_language,
                "copyright" => $podcast_copyright,
                "feed_url" => $podcast_feed_url,
                "generator" => $podcast_generator,
                "website" => $podcast_website,
                "build_date" => $podcast_build_date,
                "sync_date" => $podcast_sync_date,
                "public_url" => $podcast_public_url,
                "art" => $art_url,
                "flag" => (!$flag->get_flag($user->getId()) ? 0 : 1),
                "preciserating" => $user_rating,
                "rating" => $user_rating,
                "averagerating" => $rating->get_average_rating(),
                "podcast_episode" => $podcast_episodes
            ];
        }

        return $JSON;
    }

    /**
     * set_count
     *
     * This sets the total count for any ampache transactions
     *
     * @param int|string $count Set the total count of your results
     */
    public static function set_count(int|string $count): void
    {
        self::$count = (int) $count;
    }

    /**
     * set_limit
     *
     * This sets the limit for any ampache transactions
     *
     * @param int|string $limit Set a limit on your results
     */
    public static function set_limit(int|string $limit): bool
    {
        if (!$limit) {
            return false;
        }

        self::$limit = (strtolower((string) $limit) == "none") ? null : (int) $limit;

        return true;
    }

    /**
     * set_offset
     *
     * This takes an int and changes the offset
     *
     * @param int|string $offset Change the starting position of your results. (e.g 5001 when selecting in groups of 5000)
     */
    public static function set_offset(int|string $offset): void
    {
        self::$offset = (int) $offset;
    }

    /**
     * shares
     *
     * This returns shares to the user, in a pretty json document with the information
     *
     * @param array<int|string> $objects Share id's to include
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public static function shares(array $objects, User $user, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $JSON = [];
        foreach ($objects as $share_id) {
            $share = new Share((int) $share_id);
            if ($share->isNew() || !$share->isAccessible($user)) {
                continue;
            }

            $share_name           = $share->getObjectName();
            $share_user           = $share->getUserName();
            $share_allow_stream   = (int) $share->allow_stream;
            $share_allow_download = (int) $share->allow_download;
            $share_creation_date  = $share->creation_date;
            $share_lastvisit_date = $share->lastvisit_date;
            $share_object_type    = $share->object_type;
            $share_object_id      = (string) $share->object_id;
            $share_expire_days    = $share->expire_days;
            $share_max_counter    = $share->max_counter;
            $share_counter        = $share->counter;
            $share_secret         = $share->secret;
            $share_public_url     = $share->public_url;
            $share_description    = $share->description;
            // Build this element
            $JSON[] = [
                "id" => (string) $share_id,
                "name" => $share_name,
                "owner" => $share_user,
                "allow_stream" => $share_allow_stream,
                "allow_download" => $share_allow_download,
                "creation_date" => $share_creation_date,
                "lastvisit_date" => $share_lastvisit_date,
                "object_type" => $share_object_type,
                "object_id" => $share_object_id,
                "expire_days" => $share_expire_days,
                "max_counter" => $share_max_counter,
                "counter" => $share_counter,
                "secret" => $share_secret,
                "public_url" => $share_public_url,
                "description" => $share_description
            ];
        }
        $output = ($object) ? ["share" => $JSON] : $JSON[0] ?? [];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * shouts
     *
     * This handles creating an JSON document for a shout list
     *
     * @param Traversable<Shoutbox> $objects Shout id list
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public static function shouts(Traversable $objects, bool $object = true): string
    {
        $JSON = [];

        /** @var Shoutbox $shout */
        foreach ($objects as $shout) {
            $user = $shout->getUser();

            $JSON[] = [
                "id" => (string) $shout->getId(),
                "date" => $shout->getDate()->getTimestamp(),
                "text" => $shout->getText(),
                "user" => [
                    "id" => (string) ($user?->getId() ?? 0),
                    "username" => $user?->getUsername() ?? '',
                ]
            ];
        }
        $output = ($object) ? ["shout" => $JSON] : $JSON[0] ?? [];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * songs
     *
     * This returns an array of songs populated from an array of song ids.
     * (Spiffy isn't it!)
     * @param array<int|string> $objects
     * @param bool $object (whether to return as a named object array or regular array)
     * @return string JSON Object "song"
     */
    public static function songs(array $objects, User $user, string $auth, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $JSON        = self::songs_array($objects, $user, $auth);

        $output = ($object) ? ["song" => $JSON] : $JSON[0] ?? [];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * songs_array
     *
     * @param array<int|string> $objects
     * @return array<int, array<string, mixed>>
     */
    public static function songs_array(array $objects, User $user, string $auth, bool $encode = true): array
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit, $encode);

        Song::build_cache($objects);
        Stream::set_session($auth);

        $JSON           = [];
        $playlist_track = 0;

        // Foreach the ids!
        foreach ($objects as $song_id) {
            $song = new Song((int) $song_id);
            // If the song id is invalid/null
            if ($song->isNew()) {
                continue;
            }
            $song->fill_ext_info();
            $rating      = new Rating($song->id, 'song');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($song->id, 'song');
            $art_url     = Art::url($song->album, 'album', $auth);
            $songMime    = $song->mime;
            $songBitrate = $song->bitrate;
            $play_url    = $song->play_url('', 'api', false, $user->id, $user->streamtoken);
            $license     = $song->getLicense();
            $licenseLink = $license?->getExternalLink() ?: null;

            $playlist_track++;

            $objArray = [
                "id" => (string) $song->id,
                "title" => $song->get_fullname(),
                "name" => $song->get_fullname(),
                "artist" => [
                    "id" => (string) $song->artist,
                    "name" => $song->get_parent_fullname()],
                "album" => [
                    "id" => (string) $song->album,
                    "name" => $song->get_album_fullname()],
                'albumartist' => [
                    "id" => (string) $song->albumartist,
                    "name" => $song->get_album_artist_fullname()
                ]
            ];

            $objArray['disk']                  = (int) $song->disk;
            $objArray['track']                 = (int) $song->track;
            $objArray['filename']              = $song->file;
            $objArray['genre']                 = self::_genre_array($song->get_tags());
            $objArray['playlisttrack']         = $playlist_track;
            $objArray['time']                  = $song->time;
            $objArray['year']                  = $song->year;
            $objArray['bitrate']               = $songBitrate;
            $objArray['rate']                  = $song->rate;
            $objArray['mode']                  = $song->mode;
            $objArray['mime']                  = $songMime;
            $objArray['url']                   = $play_url;
            $objArray['size']                  = $song->size;
            $objArray['mbid']                  = $song->mbid;
            $objArray['album_mbid']            = $song->get_album_mbid();
            $objArray['artist_mbid']           = $song->get_artist_mbid();
            $objArray['albumartist_mbid']      = $song->get_album_mbid();
            $objArray['art']                   = $art_url;
            $objArray['flag']                  = (!$flag->get_flag($user->getId()) ? 0 : 1);
            $objArray['preciserating']         = $user_rating;
            $objArray['rating']                = $user_rating;
            $objArray['averagerating']         = $rating->get_average_rating();
            $objArray['playcount']             = $song->total_count;
            $objArray['catalog']               = $song->getCatalogId();
            $objArray['composer']              = $song->composer;
            $objArray['channels']              = $song->channels;
            $objArray['comment']               = $song->comment;
            $objArray['license']               = $licenseLink;
            $objArray['publisher']             = $song->label;
            $objArray['language']              = $song->language;
            $objArray['lyrics']                = ($song->lyrics) ? html_entity_decode($song->lyrics) : null;
            $objArray['replaygain_album_gain'] = $song->replaygain_album_gain;
            $objArray['replaygain_album_peak'] = $song->replaygain_album_peak;
            $objArray['replaygain_track_gain'] = $song->replaygain_track_gain;
            $objArray['replaygain_track_peak'] = $song->replaygain_track_peak;
            $objArray['r128_album_gain']       = $song->r128_album_gain;
            $objArray['r128_track_gain']       = $song->r128_track_gain;

            /** @var Metadata $metadata */
            foreach ($song->getMetadata() as $metadata) {
                $field = $metadata->getField();

                if ($field !== null) {
                    $meta_name = str_replace(
                        [' ', '(', ')', '/', '\\', '#'],
                        '_',
                        $field->getName()
                    );
                    $objArray[$meta_name] = $metadata->getData();
                }
            }
            $JSON[] = $objArray;
        }

        return $JSON;
    }

    /**
     * success
     *
     * This generates a standard JSON Success message
     * nothing fancy here...
     *
     * @param string $string success message
     * @param array<string, string> $return_data
     */
    public static function success(string $string, array $return_data = []): string
    {
        $output = ["success" => $string];
        foreach ($return_data as $title => $data) {
            $output[$title] = $data;
        }

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * timeline
     *
     * This handles creating an JSON document for an activity list
     *
     * @param int[] $objects Activity id list
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public static function timeline(array $objects, bool $object = true): string
    {
        $JSON = [];
        foreach ($objects as $activity_id) {
            $activity = new Useractivity($activity_id);
            $user     = new User($activity->user);
            $objArray = [
                "id" => (string) $activity_id,
                "date" => $activity->activity_date,
                "object_type" => $activity->object_type,
                "object_id" => (string) $activity->object_id,
                "action" => $activity->action,
                "user" => [
                    "id" => (string) $activity->user,
                    "username" => $user->username
                ]
            ];
            $JSON[] = $objArray;
        }
        $output = ($object) ? ["activity" => $JSON] : $JSON[0] ?? [];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * user
     *
     * This handles creating an JSON document for a user
     */
    public static function user(User $user, bool $fullinfo, ?bool $object = true): string
    {
        if ($fullinfo) {
            $JSON = [
                "id" => (string) $user->id,
                "username" => $user->username,
                "auth" => $user->apikey,
                "email" => $user->email,
                "access" => $user->access,
                "fullname_public" => (int) $user->fullname_public,
                "validation" => $user->validation,
                "disabled" => (int) $user->disabled,
                "create_date" => (int) $user->create_date,
                "last_seen" => $user->last_seen,
                "website" => $user->website,
                "state" => $user->state,
                "city" => $user->city
            ];
        } else {
            $JSON = [
                "id" => (string) $user->id,
                "username" => $user->username,
                "create_date" => $user->create_date,
                "last_seen" => $user->last_seen,
                "website" => $user->website,
                "state" => $user->state,
                "city" => $user->city
            ];
        }

        if ($user->fullname_public) {
            $JSON['fullname'] = $user->fullname;
        }
        $output = ($object) ? ["user" => $JSON] : $JSON;

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * users
     *
     * This handles creating an JSON document for a user list
     *
     * @param int[] $objects User id list
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public static function users(array $objects, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $JSON        = self::users_array($objects);

        if ($object) {
            $output = ["user" => $JSON];
        } else {
            $output = $JSON[0] ?? [];
        }

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * users_array
     *
     * @param array<int|string> $objects User id list
     * @return array<int, array{id: string, username: null|string}>
     */
    public static function users_array(array $objects, bool $encode = true): array
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit, $encode);

        $JSON = [];
        foreach ($objects as $user_id) {
            $user = new User((int) $user_id);
            if ($user->isNew()) {
                continue;
            }
            $JSON[] = [
                "id" => (string) $user_id,
                "username" => $user->username
            ];
        }

        return $JSON;
    }

    /**
     * videos
     *
     * This builds the JSON document for displaying video objects
     *
     * @param array<int|string> $objects Video id's to include
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public static function videos(array $objects, User $user, string $auth, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $JSON        = self::videos_array($objects, $user, $auth);

        $output = ($object) ? ["video" => $JSON] : $JSON[0] ?? [];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * videos_array
     *
     * @param array<int|string> $objects Video id's to include
     * @return array<int, array{
     *     "id": string,
     *     "title": null|string,
     *     "mime": null|string,
     *     "resolution": null|string,
     *     "size": int,
     *     "genre": array<int, array{id: string, name: string}>,
     *     "time": int,
     *     "url": string,
     *     "art": null|string,
     *     "flag": int,
     *     "preciserating": int|null,
     *     "rating": int|null,
     *     "averagerating": float|null,
     *     "playcount": int
     * }>
     */
    public static function videos_array(array $objects, User $user, string $auth): array
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $JSON = [];
        foreach ($objects as $video_id) {
            $video = new Video((int) $video_id);
            if ($video->isNew()) {
                continue;
            }
            $rating      = new Rating($video->id, 'video');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($video->id, 'video');
            $art_url     = Art::url($video->id, 'video', $auth);
            $JSON[]      = [
                "id" => (string) $video->id,
                "title" => $video->title,
                "mime" => $video->mime,
                "resolution" => $video->get_f_resolution(),
                "size" => $video->size,
                "genre" => self::_genre_array($video->get_tags()),
                "time" => $video->time,
                "url" => $video->play_url('', 'api', false, $user->getId(), $user->streamtoken),
                "art" => $art_url,
                "flag" => (!$flag->get_flag($user->getId()) ? 0 : 1),
                "preciserating" => $user_rating,
                "rating" => $user_rating,
                "averagerating" => $rating->get_average_rating(),
                "playcount" => $video->total_count
            ];
        }

        return $JSON;
    }

    /**
     * _genre_array
     *
     * @param array<int, array{id: int, name: string, is_hidden: int, count: int}> $tags
     * @return array<int, array{id: string, name: string}>
     */
    private static function _genre_array(array $tags): array
    {
        $JSON = [];

        if (!empty($tags)) {
            $atags = [];
            foreach ($tags as $tag) {
                if (array_key_exists($tag['id'], $atags)) {
                    $atags[$tag['id']]['count']++;
                } else {
                    $atags[$tag['id']] = [
                        "name" => $tag['name'],
                        "count" => 1
                    ];
                }
            }

            foreach ($atags as $tag_id => $data) {
                $JSON[] = [
                    "id" => (string) $tag_id,
                    "name" => $data['name']
                ];
            }
        }

        return $JSON;
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getAlbumRepository(): AlbumRepositoryInterface
    {
        global $dic;

        return $dic->get(AlbumRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getBookmarkRepository(): BookmarkRepositoryInterface
    {
        global $dic;

        return $dic->get(BookmarkRepositoryInterface::class);
    }

    /**
     * @deprecated Inject dependency
     */
    private static function getLabelRepository(): LabelRepositoryInterface
    {
        global $dic;

        return $dic->get(LabelRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getLicenseRepository(): LicenseRepositoryInterface
    {
        global $dic;

        return $dic->get(LicenseRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getPodcastRepository(): PodcastRepositoryInterface
    {
        global $dic;

        return $dic->get(PodcastRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }
}
