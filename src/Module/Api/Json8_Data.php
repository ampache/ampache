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
use Ampache\Module\System\Dba;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\LabelRepositoryInterface;
use Ampache\Repository\LicenseRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\Democratic;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\library_item;
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
use DateMalformedStringException;

/**
 * Json8_Data Class
 *
 * This class takes care of all of the JSON document stuff in Ampache these
 * are all static calls
 *
 */
class Json8_Data
{
    // Types whose populated response is a bare {type: []} with no total_count/md5, so their
    // empty response must not invent one either (users, timeline, last_shouts, now_playing)
    private const array BARE_ENVELOPE_TYPES = [
        'activity',
        'now_playing',
        'shout',
        'user',
    ];

    private static int $count  = 0;
    private static ?int $limit = 5000;
    private static int $offset = 0;

    /**
     * @param array<int|string> $objects AlbumDisk id's to include
     * @param string[] $include
     * @param bool $object (whether to return as a named object array or regular array)
     * @return string JSON Object "album_disk"
     */
    public static function album_disks(array $objects, array $include, User $user, string $auth, bool $encode = true, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $md5         = md5(serialize($objects));
        $JSON        = self::album_disks_array($objects, $include, $user, $auth, $encode);

        if ($object) {
            $output = [
                "total_count" => self::$count,
                "md5" => $md5,
                "album_disk" => $JSON
            ];
        } else {
            $output = $JSON[0] ?? [];
        }

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * album_disks_array
     *
     * A disk is a child of an album, so it carries the album reference and its own disk identity
     * instead of the album-level `diskcount`.
     *
     * @param array<int|string> $objects AlbumDisk id's to include
     * @param string[] $include
     * @return array<int, array{
     *     "id": string,
     *     "name": string,
     *     "prefix": string|null,
     *     "basename": string|null,
     *     "album": array{
     *         id: string,
     *         name: string,
     *         prefix: string|null,
     *         basename: string|null
     *     },
     *     "artist"?: array{
     *         id: string,
     *         name: string,
     *         prefix: string|null,
     *         basename: string
     *     },
     *     "artists"?: array<int, array{id: string, name: string, prefix: string|null, basename: string}>,
     *     "songartists"?: array<int, array{id: string, name: string, prefix: string|null, basename: string}>,
     *     "disk": int,
     *     "disksubtitle": string|null,
     *     "time": int,
     *     "year": int,
     *     "tracks": array<int, mixed>,
     *     "songcount": int,
     *     "type": null|string,
     *     "genre": array<int, array{id: string, name: string}>,
     *     "art": null|string,
     *     "has_art": bool,
     *     "flag": bool,
     *     "rating": int|null,
     *     "averagerating": float|null,
     *     "mbid": null|string,
     *     "mbid_group": null|string,
     *     "catalog": string,
     * }> JSON Object "album_disk"
     */
    public static function album_disks_array(array $objects, array $include, User $user, string $auth, bool $encode = true): array
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit, $encode);

        // original year (fall back to regular year)
        $original_year = AmpConfig::get('use_original_year');

        Rating::build_cache('album_disk', $objects);
        $JSON = [];
        foreach ($objects as $album_disk_id) {
            $album_disk = new AlbumDisk((int) $album_disk_id);
            if ($album_disk->isNew()) {
                continue;
            }

            $rating      = new Rating($album_disk->id, 'album_disk');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($album_disk->id, 'album_disk');
            $year        = ($original_year && $album_disk->original_year)
                ? $album_disk->original_year
                : $album_disk->year;

            // Build the Art URL, include session
            $art_url = Art::url($album_disk->id, 'album_disk', $auth);

            $objArray = [];

            $objArray['id']       = (string) $album_disk->id;
            $objArray['name']     = $album_disk->get_fullname();
            $objArray['prefix']   = $album_disk->prefix;
            $objArray['basename'] = $album_disk->name;
            // the simple fullname is the album name without the disk suffix, so the parent needs no extra lookup
            $objArray['album'] = [
                "id" => (string) $album_disk->album_id,
                "name" => $album_disk->get_fullname(true),
                "prefix" => $album_disk->prefix,
                "basename" => $album_disk->name
            ];
            if ($album_disk->get_parent_fullname() != "") {
                $objArray['artist'] = Artist::get_name_array_by_id((int) $album_disk->album_artist);
                $album_artists      = [];
                foreach ($album_disk->get_artists() as $artist_id) {
                    $album_artists[] = Artist::get_name_array_by_id($artist_id);
                }
                $objArray['artists'] = $album_artists;
                $song_artists        = [];
                foreach ($album_disk->get_song_artists() as $artist_id) {
                    $song_artists[] = Artist::get_name_array_by_id($artist_id);
                }
                $objArray['songartists'] = $song_artists;
            }

            // Handle includes (get_songs() is already scoped to the disk and honours catalog_disable)
            $songs = (in_array("songs", $include))
                ? self::songs_array($album_disk->get_songs(), $user, $auth)
                : [];

            $objArray['disk']          = $album_disk->disk;
            $objArray['disksubtitle']  = $album_disk->disksubtitle;
            $objArray['time']          = (int) $album_disk->time;
            $objArray['year']          = (int) $year;
            $objArray['tracks']        = $songs;
            $objArray['songcount']     = $album_disk->song_count;
            $objArray['type']          = $album_disk->release_type;
            $objArray['genre']         = self::_genre_array($album_disk->get_tags());
            $objArray['art']           = $art_url;
            $objArray['has_art']       = $album_disk->has_art();
            $objArray['flag']          = (bool) $flag->get_flag($user->getId());
            $objArray['rating']        = $user_rating;
            $objArray['averagerating'] = $rating->get_average_rating();
            $objArray['mbid']          = $album_disk->mbid;
            $objArray['mbid_group']    = $album_disk->mbid_group;
            $objArray['catalog']       = (string) $album_disk->getCatalogId();

            $JSON[] = $objArray;
        }

        return $JSON;
    }

    /**
     * albums
     *
     * This echos out a standard albums JSON document, it pays attention to the limit
     *
     * @param array<int|string> $objects Album id's to include
     * @param string[] $include
     * @param bool $object (whether to return as a named object array or regular array)
     * @return string JSON Object "album"
     */
    public static function albums(array $objects, array $include, User $user, string $auth, bool $encode = true, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $md5         = md5(serialize($objects));
        $JSON        = self::albums_array($objects, $include, $user, $auth, $encode);

        if ($object) {
            $output = [
                "total_count" => self::$count,
                "md5" => $md5,
                "album" => $JSON
            ];
        } else {
            $output = $JSON[0] ?? [];
        }

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
     *     "prefix": null|string,
     *     "basename": null|string,
     *     "artist"?: array{
     *         "id": string,
     *         "name": null|string,
     *         "prefix": null|string,
     *         "basename": null|string,
     *     }|null,
     *     "artists"?: array<int, array{
     *         "id": string,
     *         "name": null|string,
     *         "prefix": null|string,
     *         "basename": null|string,
     *     }>,
     *     "songartists"?: array<int, array{
     *         "id": string,
     *         "name": null|string,
     *         "prefix": null|string,
     *         "basename": null|string,
     *     }>,
     *     "time": int,
     *     "year": int,
     *     "tracks": array<int, array{
     *         id: string,
     *         title: string|null,
     *         name: string|null,
     *         artist: array{
     *             id: string,
     *             name: string|null,
     *             prefix: string|null,
     *             basename: string|null
     *         },
     *         artists: array<int, array{
     *             id: string,
     *             name: string|null,
     *             prefix: string|null,
     *             basename: string|null
     *         }>,
     *         album: array{
     *             id: string,
     *             name: string|null,
     *             prefix: string|null,
     *             basename: string|null
     *         },
     *         albumartist?: array{
     *             id: string,
     *             name: string|null,
     *             prefix: string|null,
     *             basename: string|null
     *         },
     *         disk: int,
     *         disksubtitle: string|null,
     *         track: int,
     *         filename: string|null,
     *         genre: array<int, array{id: string, name: string}>,
     *         playlisttrack: int,
     *         time: int,
     *         year: int,
     *         format: string|null,
     *         stream_format: string|null,
     *         bitrate: int|null,
     *         stream_bitrate: int|null,
     *         rate: int,
     *         mode: string|null,
     *         mime: string|null,
     *         stream_mime: string|null,
     *         url: string,
     *         size: int,
     *         mbid: string|null,
     *         art: string|null,
     *         has_art: bool,
     *         flag: bool,
     *         rating: int|null,
     *         averagerating: float|null,
     *         playcount: int,
     *         last_played: string|null,
     *         catalog: string,
     *         composer: string|null,
     *         channels: int|null,
     *         comment: string|null,
     *         license: string|null,
     *         publisher: string|null,
     *         language: string|null,
     *         lyrics: string|null,
     *         replaygain_album_gain: float|null,
     *         replaygain_album_peak: float|null,
     *         replaygain_track_gain: float|null,
     *         replaygain_track_peak: float|null,
     *         r128_album_gain: float|null,
     *         r128_track_gain: float|null,
     *         metadata?: array<string, string>
     *     }>,
     *     "songcount": int,
     *     "diskcount": int,
     *     "type": null|string,
     *     "genre": array<int, array{id: string, name: string}>,
     *     "art": null|string,
     *     "has_art": bool,
     *     "flag": bool,
     *     "rating": int|null,
     *     "averagerating": float|null,
     *     "mbid": null|string,
     *     "mbid_group": null|string,
     *     "catalog": string,
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

            $objArray['id']       = (string) $album->id;
            $objArray['name']     = $album->get_fullname();
            $objArray['prefix']   = $album->prefix;
            $objArray['basename'] = $album->name;
            if ($album->get_parent_fullname() != "") {
                $objArray['artist'] = [
                    "id" => (string) $album->findAlbumArtist(),
                    "name" => $album->get_parent_fullname(),
                    "prefix" => $album->artist_prefix,
                    "basename" => $album->artist_name
                ];
                $album_artists = [];
                foreach ($album->get_artists() as $artist_id) {
                    $album_artists[] = Artist::get_name_array_by_id($artist_id);
                }
                $objArray['artists'] = $album_artists;
                $song_artists        = [];
                foreach ($album->get_song_artists() as $artist_id) {
                    $song_artists[] = Artist::get_name_array_by_id($artist_id);
                }
                $objArray['songartists'] = $song_artists;
            }

            // Handle includes
            $songs = (in_array("songs", $include))
                ? self::songs_array(self::getSongRepository()->getByAlbum($album->id), $user, $auth)
                : [];

            $objArray['time']          = (int) $album->time;
            $objArray['year']          = (int) $year;
            $objArray['tracks']        = $songs;
            $objArray['songcount']     = $album->song_count;
            $objArray['diskcount']     = $album->disk_count;
            $objArray['type']          = $album->release_type;
            $objArray['genre']         = self::_genre_array($album->get_tags());
            $objArray['art']           = $art_url;
            $objArray['has_art']       = $album->has_art();
            $objArray['flag']          = (bool) $flag->get_flag($user->getId());
            $objArray['rating']        = $user_rating;
            $objArray['averagerating'] = $rating->get_average_rating();
            $objArray['mbid']          = $album->mbid;
            $objArray['mbid_group']    = $album->mbid_group;
            $objArray['catalog']       = (string) $album->getCatalogId();

            $JSON[] = $objArray;
        }

        return $JSON;
    }

    /**
     * @param array<int|string> $objects Artist id's to include
     * @param string[] $include
     * @param bool $object (whether to return as a named object array or regular array)
     * @return string JSON Object "artist"
     */
    public static function artists(array $objects, array $include, User $user, string $auth, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $md5         = md5(serialize($objects));
        $JSON        = self::artists_array($objects, $include, $user, $auth);

        if ($object) {
            $output = [
                "total_count" => self::$count,
                "md5" => $md5,
                "artist" => $JSON
            ];
        } else {
            $output = $JSON[0] ?? [];
        }

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
     *     "prefix": null|string,
     *     "basename": null|string,
     *     "albums": array<int, array{
     *         "id": string,
     *         "name": null|string,
     *         "prefix": null|string,
     *         "basename": null|string,
     *         "artist"?: array{
     *             "id": string,
     *             "name": null|string,
     *             "prefix": null|string,
     *             "basename": null|string,
     *         }|null,
     *         "artists"?: array<int, array{
     *             "id": string,
     *             "name": null|string,
     *             "prefix": null|string,
     *             "basename": null|string,
     *         }>,
     *         "songartists"?: array<int, array{
     *             "id": string,
     *             "name": null|string,
     *             "prefix": null|string,
     *             "basename": null|string,
     *         }>,
     *         "time": int,
     *         "year": int,
     *         "tracks": array<int, array{
     *             id: string,
     *             title: string|null,
     *             name: string|null,
     *             artist: array{
     *                 id: string,
     *                 name: string|null,
     *                 prefix: string|null,
     *                 basename: string|null
     *             },
     *             artists: array<int, array{
     *                 id: string,
     *                 name: string|null,
     *                 prefix: string|null,
     *                 basename: string|null
     *             }>,
     *             album: array{
     *                 id: string,
     *                 name: string|null,
     *                 prefix: string|null,
     *                 basename: string|null
     *             },
     *             albumartist?: array{
     *                 id: string,
     *                 name: string|null,
     *                 prefix: string|null,
     *                 basename: string|null
     *             },
     *             disk: int,
     *             disksubtitle: string|null,
     *             track: int,
     *             filename: string|null,
     *             genre: array<int, array{id: string, name: string}>,
     *             playlisttrack: int,
     *             time: int,
     *             year: int,
     *             format: string|null,
     *             stream_format: string|null,
     *             bitrate: int|null,
     *             stream_bitrate: int|null,
     *             rate: int,
     *             mode: string|null,
     *             mime: string|null,
     *             stream_mime: string|null,
     *             url: string,
     *             size: int,
     *             mbid: string|null,
     *             art: string|null,
     *             has_art: bool,
     *             flag: bool,
     *             rating: int|null,
     *             averagerating: float|null,
     *             playcount: int,
     *             last_played: string|null,
     *             catalog: string,
     *             composer: string|null,
     *             channels: int|null,
     *             comment: string|null,
     *             license: string|null,
     *             publisher: string|null,
     *             language: string|null,
     *             lyrics: string|null,
     *             replaygain_album_gain: float|null,
     *             replaygain_album_peak: float|null,
     *             replaygain_track_gain: float|null,
     *             replaygain_track_peak: float|null,
     *             r128_album_gain: float|null,
     *             r128_track_gain: float|null,
     *             metadata?: array<string, string>
     *         }>,
     *         "songcount": int,
     *         "diskcount": int,
     *         "type": null|string,
     *         "genre": array<int, array{id: string, name: string}>,
     *         "art": null|string,
     *         "has_art": bool,
     *         "flag": bool,
     *         "rating": int|null,
     *         "averagerating": float|null,
     *         "mbid": null|string,
     *         "mbid_group": null|string,
     *         "catalog": string,
     *     }>,
     *     "albumcount": int,
     *     "songs": array<int, array{
     *         id: string,
     *         title: string|null,
     *         name: string|null,
     *         artist: array{
     *             id: string,
     *             name: string|null,
     *             prefix: string|null,
     *             basename: string|null
     *         },
     *         artists: array<int, array{
     *             id: string,
     *             name: string|null,
     *             prefix: string|null,
     *             basename: string|null
     *         }>,
     *         album: array{
     *             id: string,
     *             name: string|null,
     *             prefix: string|null,
     *             basename: string|null
     *         },
     *         albumartist?: array{
     *             id: string,
     *             name: string|null,
     *             prefix: string|null,
     *             basename: string|null
     *         },
     *         disk: int,
     *         disksubtitle: string|null,
     *         track: int,
     *         filename: string|null,
     *         genre: array<int, array{id: string, name: string}>,
     *         playlisttrack: int,
     *         time: int,
     *         year: int,
     *         format: string|null,
     *         stream_format: string|null,
     *         bitrate: int|null,
     *         stream_bitrate: int|null,
     *         rate: int,
     *         mode: string|null,
     *         mime: string|null,
     *         stream_mime: string|null,
     *         url: string,
     *         size: int,
     *         mbid: string|null,
     *         art: string|null,
     *         has_art: bool,
     *         flag: bool,
     *         rating: int|null,
     *         averagerating: float|null,
     *         playcount: int,
     *         last_played: string|null,
     *         catalog: string,
     *         composer: string|null,
     *         channels: int|null,
     *         comment: string|null,
     *         license: string|null,
     *         publisher: string|null,
     *         language: string|null,
     *         lyrics: string|null,
     *         replaygain_album_gain: float|null,
     *         replaygain_album_peak: float|null,
     *         replaygain_track_gain: float|null,
     *         replaygain_track_peak: float|null,
     *         r128_album_gain: float|null,
     *         r128_track_gain: float|null,
     *         metadata?: array<string, string>
     *     }>,
     *     "songcount": int,
     *     "genre": array<int, array{id: string, name: string}>,
     *     "art": null|string,
     *     "has_art": bool,
     *     "flag": bool,
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
                ? self::songs_array(self::getSongRepository()->getByArtist($artist->id), $user, $auth)
                : [];

            $JSON[] = [
                "id" => (string) $artist->id,
                "name" => $artist->get_fullname(),
                "prefix" => $artist->prefix,
                "basename" => $artist->name,
                "albums" => $albums,
                "albumcount" => $artist->album_count,
                "songs" => $songs,
                "songcount" => $artist->song_count,
                "genre" => self::_genre_array($artist->get_tags()),
                "art" => $art_url,
                "has_art" => $artist->has_art(),
                "flag" => (bool) $flag->get_flag($user->getId()),
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
     * @param bool $include if true include the object in the bookmark
     * @param bool $object (whether to return as a named object array or regular array)
     * @return string JSON Object "bookmark"
     */
    public static function bookmarks(array $objects, string $auth, bool $include = false, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $md5         = md5(serialize($objects));
        $JSON        = self::bookmarks_array($objects, $auth, $include);

        if ($object) {
            $output = [
                "total_count" => self::$count,
                "md5" => $md5,
                "bookmark" => $JSON
            ];
        } else {
            $output = $JSON[0] ?? [];
        }

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * bookmarks_array
     *
     * @param int[] $objects Bookmark id's to include
     * @param bool $include if true include the object in the bookmark
     * @return array<int, array{
     *     id: string,
     *     owner: string,
     *     object_type: null|string,
     *     object_id: string,
     *     position: int,
     *     client: null|string,
     *     creation_date: int,
     *     update_date: int,
     *     song?: array<int, array<string, mixed>>,
     *     podcast_episode?: array<int, array<string, mixed>>,
     *     video?: array<int, array<string, mixed>>
     * }>
     */
    public static function bookmarks_array(array $objects, string $auth, bool $include = false): array
    {
        self::$count = self::$count ?: count($objects);
        $total_count = self::$count;
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
            $element = [
                "id" => (string) $bookmark_id,
                "owner" => $bookmark_username,
                "object_type" => $bookmark_object_type,
                "object_id" => $bookmark_object_id,
                "position" => $bookmark_position,
                "client" => $bookmark_comment,
                "creation_date" => $bookmark_creation_date,
                "update_date" => $bookmark_update_date
            ];

            $user = User::get_from_username($bookmark_username);
            if (
                $include
                && $user !== null
            ) {
                switch ($bookmark_object_type) {
                    case 'song':
                        $element['song'] = self::songs_array([(int) $bookmark_object_id], $user, $auth);
                        break;
                    case 'podcast_episode':
                        $element['podcast_episode'] = self::podcast_episodes_array([(int) $bookmark_object_id], $user, $auth, false);
                        break;
                    case 'video':
                        $element['video'] = self::videos_array([(int) $bookmark_object_id], $user, $auth);
                        break;
                }
            }
            $JSON[] = $element;
        }
        // The nested *_array builders above overwrite self::$count; restore the real total for the wrapper.
        self::$count = $total_count;

        return $JSON;
    }

    /**
     * browses
     *
     * This takes a name array of objects and return the data in JSON browse object
     *
     * @param array<int, array{id: int|string, name: string}> $objects Name array from `Catalog::get_name_array()`
     * @return string JSON Object "browse"
     */
    public static function browses(array $objects, string $parent_type, string $child_type, ?int $parent_id = null, ?int $catalog_id = null): string
    {
        self::$count = self::$count ?: count($objects);
        $md5         = md5(serialize($objects));
        $JSON        = self::browses_array($objects);

        $output = [
            "total_count" => self::$count,
            "md5" => $md5,
            "catalog_id" => (string) $catalog_id,
            "parent_id" => (string) $parent_id,
            "parent_type" => $parent_type,
            "child_type" => $child_type,
            "browse" => $JSON,
        ];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * browses_array
     *
     * @param array<int, array{id: int|string, name: string}> $objects Name array from `Catalog::get_name_array()`
     * @return array<int, array{
     *     id: string,
     *     name: string,
     *     prefix: null|string,
     *     basename: string
     * }>
     */
    public static function browses_array(array $objects): array
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $pattern = '/^(' . implode('\\s|', explode('|', AmpConfig::get('catalog_prefix_pattern', 'The|An|A|Die|Das|Ein|Eine|Les|Le|La'))) . '\\s)(.*)/i';

        $JSON = [];
        foreach ($objects as $object) {
            $trimmed  = Catalog::trim_prefix(trim((string) $object['name']), $pattern);
            $prefix   = $trimmed['prefix'];
            $basename = $trimmed['string'];
            $JSON[]   = [
                "id" => (string) $object['id'],
                "name" => $object['name'],
                "prefix" => $prefix,
                "basename" => $basename
            ];
        }

        return $JSON;
    }

    /**
     * catalogs
     *
     * This returns catalogs to the user, in a pretty json document with the information
     *
     * @param array<int|string> $objects group of catalog id's
     * @param bool $object (whether to return as a named object array or regular array)
     * @return string JSON Object "catalog"
     */
    public static function catalogs(array $objects, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $md5         = md5(serialize($objects));
        $JSON        = self::catalogs_array($objects);

        if ($object) {
            $output = [
                "total_count" => self::$count,
                "md5" => $md5,
                "catalog" => $JSON
            ];
        } else {
            $output = $JSON[0] ?? [];
        }

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * catalogs_array
     *
     * @param array<int|string> $objects group of catalog id's
     * @return array<int, array{
     *     id: string,
     *     name: null|string,
     *     type: null|string,
     *     gather_types: null|string,
     *     enabled: bool,
     *     last_add: int,
     *     last_clean: int|null,
     *     last_update: int,
     *     path: string,
     *     rename_pattern: null|string,
     *     sort_pattern: null|string
     * }>
     */
    public static function catalogs_array(array $objects): array
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $JSON = [];
        foreach ($objects as $catalog_id) {
            $catalog = Catalog::create_from_id((int) $catalog_id);
            if ($catalog === null) {
                break;
            }
            // Build this element
            $JSON[] = [
                "id" => (string) $catalog_id,
                "name" => $catalog->name,
                "type" => $catalog->catalog_type,
                "gather_types" => $catalog->gather_types,
                "enabled" => $catalog->enabled,
                "last_add" => $catalog->last_add,
                "last_clean" => $catalog->last_clean,
                "last_update" => $catalog->last_update,
                "path" => $catalog->get_f_info(),
                "rename_pattern" => $catalog->rename_pattern,
                "sort_pattern" => $catalog->sort_pattern
            ];
        }

        return $JSON;
    }

    /**
     * collection_items
     *
     * One collection's contents, grouped by object type.
     *
     * @return string JSON Object "collection"
     */
    public static function collection_items(Collection $collection, User $user, string $auth, bool $object = true): string
    {
        $ordered = $collection->get_ordered_items();

        self::$count = self::$count ?: count($ordered);
        $ordered     = Api::filter_objects($ordered, self::$count, self::$offset, self::$limit);

        // `contents` rather than `items`, which the collection row already uses for the member count
        $JSON = self::collection_row($collection) + [
            'contents' => self::collection_contents($ordered, $user, $auth),
        ];

        $output = ($object) ? ["collection" => $JSON] : $JSON;

        return json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
    }

    /**
     * collections
     *
     * A list of collections, without their contents.
     *
     * @param list<int> $objects
     * @return string JSON Object "collection"
     */
    public static function collections(array $objects, User $user, string $auth, bool $object = true): string
    {
        unset($auth);
        $JSON = self::collections_array($objects, $user);

        $output = ($object) ? ["collection" => $JSON] : $JSON;

        return json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
    }

    /**
     * collections_array
     *
     * @param list<int> $objects
     * @return array<int, array{
     *     id: string,
     *     name: string,
     *     owner: null|string,
     *     type: null|string,
     *     object_type: null|string,
     *     items: int,
     *     has_art: bool
     * }>
     */
    public static function collections_array(array $objects, User $user): array
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $JSON = [];
        foreach ($objects as $collectionId) {
            $collection = new Collection((int) $collectionId);
            if ($collection->isNew() || !$collection->isVisible($user)) {
                continue;
            }

            $JSON[] = self::collection_row($collection);
        }

        return $JSON;
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
        $md5         = md5(serialize($objects));
        $JSON        = self::deleted_array($object_type, $objects);

        $output = [
            "total_count" => self::$count,
            "md5" => $md5,
        ];
        $output["deleted_" . $object_type] = $JSON;

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * deleted_array
     *
     * The element shape depends on $object_type: deleted songs carry
     * update_time/album/artist, deleted podcast episodes carry podcast, deleted
     * videos carry neither.
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
     * @return array<int, array{
     *     id: string,
     *     addition_time: int,
     *     delete_time: int,
     *     title: string|null,
     *     file: string,
     *     catalog: string,
     *     total_count: int,
     *     total_skip: int,
     *     update_time: int,
     *     album: string,
     *     artist: string
     * }|array{
     *     id: string,
     *     addition_time: int,
     *     delete_time: int,
     *     title: string|null,
     *     file: string,
     *     catalog: string,
     *     total_count: int,
     *     total_skip: int,
     *     podcast: string
     * }|array{
     *     id: string,
     *     addition_time: int,
     *     delete_time: int,
     *     title: string|null,
     *     file: string,
     *     catalog: string,
     *     total_count: int,
     *     total_skip: int
     * }>
     */
    public static function deleted_array(string $object_type, array $objects): array
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
                            "catalog" => (string) $row['catalog'],
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
                            "catalog" => (string) $row['catalog'],
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
                        "catalog" => (string) $row['catalog'],
                        "total_count" => $row['total_count'],
                        "total_skip" => $row['total_skip']
                    ];
                    $JSON[] = $objArray;
            }
        }

        return $JSON;
    }

    /**
     * democratic
     *
     * This handles creating an JSON document for democratic items, this can be a little complicated
     * due to the votes and all of that
     *
     * @param array<int, array{
     *    object_type: LibraryItemEnum,
     *    object_id: int,
     *    track_id: int,
     *    track: int
     * }> $object_ids Object IDs
     * @param bool $object (whether to return as a named object array or regular array)
     */
    public static function democratic(array $object_ids, User $user, string $auth, bool $object = true): string
    {
        $JSON   = self::democratic_array($object_ids, $user, $auth);
        $output = ($object) ? ["song" => $JSON] : $JSON[0] ?? [];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * democratic_array
     *
     * This builds the democratic playlist items; a reduced song shape carrying the current vote count
     *
     * @param array<int, array{
     *    object_type: LibraryItemEnum,
     *    object_id: int,
     *    track_id: int,
     *    track: int
     * }> $object_ids Object IDs
     * @return array<int, array{
     *     id: string,
     *     title: string|null,
     *     artist: array{
     *         id: string,
     *         name: string|null,
     *         prefix: string|null,
     *         basename: string|null
     *     },
     *     album: array{
     *         id: string,
     *         name: string|null,
     *         prefix: string|null,
     *         basename: string|null
     *     },
     *     genre: array<int, array{id: string, name: string}>,
     *     track: int,
     *     time: int,
     *     format: string|null,
     *     bitrate: int|null,
     *     mime: string|null,
     *     url: string,
     *     size: int,
     *     art: string|null,
     *     has_art: bool,
     *     rating: int|null,
     *     averagerating: float|null,
     *     playcount: int,
     *     vote: int
     * }>
     */
    public static function democratic_array(array $object_ids, User $user, string $auth): array
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
            $songType    = $song->type;
            $songMime    = $song->mime;
            $songBitrate = $song->bitrate;
            $play_url    = $song->play_url('', 'api', false, $user->id, $user->streamtoken);
            $song_album  = self::getAlbumRepository()->getNames($song->album);
            $song_artist = Artist::get_name_array_by_id($song->artist);

            $JSON[] = [
                "id" => (string) $song->id,
                "title" => $song->get_fullname(),
                "artist" => [
                    "id" => (string) $song->artist,
                    "name" => $song_artist['name'],
                    "prefix" => $song_artist['prefix'],
                    "basename" => $song_artist['basename']
                ],
                "album" => [
                    "id" => (string) $song->album,
                    "name" => $song_album['name'],
                    "prefix" => $song_album['prefix'],
                    "basename" => $song_album['basename']
                ],
                "genre" => self::_genre_array($song->get_tags()),
                "track" => (int) $song->track,
                "time" => $song->time,
                "format" => $songType,
                "bitrate" => $songBitrate,
                "mime" => $songMime,
                "url" => $play_url,
                "size" => $song->size,
                "art" => $art_url,
                "has_art" => $song->has_art(),
                "rating" => $user_rating,
                "averagerating" => ($rating->get_average_rating() ?? null),
                "playcount" => $song->total_count,
                "vote" => $democratic->get_vote($data['track_id'])
            ];
        }

        return $JSON;
    }

    /**
     * empty
     *
     * This generates a JSON empty object
     * nothing fancy here...
     *
     * @param string|null $type object type
     */
    public static function empty(?string $type = null): string
    {
        http_response_code(404);

        if (empty($type)) {
            return json_encode([], JSON_PRETTY_PRINT) ?: '';
        }

        if (in_array($type, self::BARE_ENVELOPE_TYPES, true)) {
            return json_encode([$type => []], JSON_PRETTY_PRINT) ?: '';
        }

        return json_encode(
            [
                "total_count" => 0,
                "md5" => md5(serialize([])),
                $type => []
            ],
            JSON_PRETTY_PRINT
        ) ?: '';
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
        http_response_code(Api::getHttpCode($code));

        $message = [
            "error" => [
                "errorCode" => (string) $code,
                "errorAction" => $action,
                "errorType" => $type,
                "errorMessage" => $string,
            ]
        ];

        return json_encode($message, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * folders
     *
     * This returns an array of folders and their contents.
     * @param array<int|string> $objects
     * @return string JSON Object "folder"
     */
    public static function folders(array $objects, Folder $folder, User $user, string $auth): string
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $JSON = [
            "id" => (string) $folder->getId(),
            "title" => $folder->get_fullname(),
            "parent" => $folder->parent,
            "path" => $folder->path_name,
            "catalog" => $folder->catalog,
            "items" => []
        ];
        foreach ($objects as $item) {
            preg_match('/([a-z_]+)-([0-9]+)/', (string) $item, $matches);
            $object_type = $matches[1] ?? null;
            $object_id   = (int) ($matches[2] ?? 0);
            $libitem     = null;
            switch ($object_type) {
                case 'folder':
                    $libitem = new Folder($object_id);
                    break;
                case 'podcast_episode':
                    $libitem = new Podcast_Episode($object_id);
                    break;
                case 'song':
                    $libitem = new Song($object_id);
                    break;
                case 'video':
                    $libitem = new Video($object_id);
                    break;
            }

            if ($libitem === null || $libitem->isNew() || $object_type === null) {
                continue;
            }

            $rating      = new Rating($libitem->getId(), $object_type);
            $user_rating = $rating->get_user_rating($user->getId());
            $art_url     = Art::url($libitem->getId(), $object_type, $auth);
            $play_url    = ($libitem instanceof Folder) ? '' : $libitem->play_url('', 'api', false, $user->id, $user->streamtoken);
            if (property_exists($libitem, 'file')) {
                $p_info   = pathinfo((string) $libitem->file);
                $filename = $p_info['basename'];
                $dirname  = $p_info['dirname'] ?? '';
            } else {
                /** @var Folder $libitem */
                $filename = $libitem->get_fullname();
                $dirname  = $libitem->path_name;
            }

            $JSON["items"][] = [
                "id" => (string) $libitem->id,
                "object_type" => $object_type,
                "title" => $filename,
                "parent" => $folder->getId(),
                "path" => $dirname,
                "art" => $art_url,
                "has_art" => $libitem->has_art(),
                "play_url" => $play_url,
                "rating" => $user_rating,
                "averagerating" => ($rating->get_average_rating() ?? null),
            ];
        }

        $output = [
            "total_count" => self::$count,
            "md5" => md5(serialize($objects)),
            "folder" => $JSON
        ];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * folders_array
     *
     * A folder as a standalone object, for a caller listing folders rather than walking into one. The nested
     * `items` of the `folders` method are deliberately absent: a member of a list is a reference, not a tree.
     *
     * @param array<int|string> $objects Folder id's to include
     * @return array<int, array{
     *     "id": string,
     *     "name": null|string,
     *     "parent": null|int,
     *     "path": null|string,
     *     "catalog": int,
     *     "items": int,
     *     "playable": bool,
     *     "art": null|string,
     *     "has_art": bool,
     *     "flag": bool,
     *     "rating": null|int,
     *     "averagerating": null|float
     * }>
     */
    public static function folders_array(array $objects, User $user, string $auth): array
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $JSON = [];
        foreach ($objects as $folder_id) {
            $folder = new Folder((int) $folder_id);
            if ($folder->isNew()) {
                continue;
            }

            $rating      = new Rating($folder->getId(), 'folder');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag($folder->getId(), 'folder');

            $JSON[] = [
                "id" => (string) $folder->getId(),
                "name" => $folder->get_fullname(),
                "parent" => $folder->parent,
                "path" => $folder->path_name,
                "catalog" => $folder->catalog,
                "items" => (int) $folder->object_count,
                "playable" => $folder->playable,
                "art" => Art::url($folder->getId(), 'folder', $auth),
                "has_art" => $folder->has_art(),
                "flag" => (bool) $flag->get_flag($user->getId()),
                "rating" => $user_rating,
                "averagerating" => $rating->get_average_rating(),
            ];
        }

        return $JSON;
    }

    /**
     * genres_string
     *
     * This returns genres to the user, in a pretty JSON document with the information
     *
     * @param array<int|string> $objects Genre id's to include
     * @param bool $object (whether to return as a named object array or regular array)
     * @return string JSON Object "label"
     */
    public static function genres(array $objects, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $md5         = md5(serialize($objects));
        $JSON        = self::genres_array($objects);

        if ($object) {
            $output = [
                "total_count" => self::$count,
                "md5" => $md5,
                "genre" => $JSON
            ];
        } else {
            $output = $JSON[0] ?? [];
        }

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * genres_array
     *
     * @param array<int|string> $objects Genre id's to include
     * @return array<int, array{
     *     "id": string,
     *     "name": null|string,
     *     "albums": int,
     *     "artists": int,
     *     "songs": int,
     *     "videos": int,
     *     "playlists": int,
     *     "live_streams": int,
     *     "is_hidden": bool,
     *     "merge": array<int, array{
     *         id: string,
     *         name: string,
     *     }>
     * }> JSON Object "genre"
     */
    public static function genres_array(array $objects): array
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $JSON = [];
        foreach ($objects as $tag_id) {
            $tag    = new Tag((int) $tag_id);
            $merged = $tag->get_merged_tags();
            $merge  = [];
            foreach ($merged as $mergedTag) {
                $merge[] = [
                    "id" => (string) $mergedTag['id'],
                    "name" => $mergedTag['name'],
                ];
            }
            $JSON[] = [
                "id" => (string) $tag_id,
                "name" => $tag->name,
                "albums" => $tag->album,
                "artists" => $tag->artist,
                "songs" => $tag->song,
                "videos" => $tag->video,
                "playlists" => 0,
                "live_streams" => 0,
                "is_hidden" => (bool) $tag->is_hidden,
                "merge" => $merge,
            ];
        }

        return $JSON;
    }

    /**
     * index
     *
     * This takes an array of object_ids and return JSON based on the type of object
     *
     * @param array<int|string> $objects Array of object_ids (Mixed string|int)
     * @param string $type 'album_artist'|'album'|'artist'|'catalog'|'live_stream'|'playlist'|'podcast_episode'|'podcast'|'share'|'song_artist'|'song'|'video'
     * @param bool $include (add child id's of the object (in sub array by type))
     * @return string JSON Object "catalog"|"artist"|"album"|"song"|"playlist"|"share"|"podcast"|"podcast_episode"|"video"|"live_stream"
     */
    public static function index(array $objects, string $type, User $user, bool $include = false): string
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $output = [];

        if ($include) {
            switch ($type) {
                case 'album_artist':
                    foreach ($objects as $object_id) {
                        $output[$object_id] = [];

                        $sql        = "SELECT DISTINCT `album_map`.`album_id` FROM `album_map` WHERE `album_map`.`object_id` = ? AND `album_map`.`object_type` = 'album';";
                        $db_results = Dba::read($sql, [$object_id]);
                        while ($row = Dba::fetch_assoc($db_results)) {
                            $output[$object_id][] = [
                                "id" => (string) $row['album_id'],
                                "type" => 'album'
                            ];
                        }
                    }
                    break;
                case 'song_artist':
                    foreach ($objects as $object_id) {
                        $output[$object_id] = [];

                        $sql        = "SELECT DISTINCT `album_map`.`album_id` FROM `album_map` WHERE `album_map`.`object_id` = ? AND `album_map`.`object_type` = 'song';";
                        $db_results = Dba::read($sql, [$object_id]);
                        while ($row = Dba::fetch_assoc($db_results)) {
                            $output[$object_id][] = [
                                "id" => (string) $row['album_id'],
                                "type" => 'album'
                            ];
                        }
                    }
                    break;
                case 'artist':
                    foreach ($objects as $object_id) {
                        $output[$object_id] = [];

                        $sql        = "SELECT DISTINCT `album_map`.`album_id` FROM `album_map` WHERE `album_map`.`object_id` = ?;";
                        $db_results = Dba::read($sql, [$object_id]);
                        while ($row = Dba::fetch_assoc($db_results)) {
                            $output[$object_id][] = [
                                "id" => (string) $row['album_id'],
                                "type" => 'album'
                            ];
                        }
                    }
                    break;
                case 'album':
                    foreach ($objects as $object_id) {
                        $output[$object_id] = [];

                        $sql        = "SELECT DISTINCT `song`.`id` FROM `song` WHERE `song`.`album` = ?;";
                        $db_results = Dba::read($sql, [$object_id]);
                        while ($row = Dba::fetch_assoc($db_results)) {
                            $output[$object_id][] = [
                                "id" => (string) $row['id'],
                                "type" => 'song'
                            ];
                        }
                    }
                    break;
                case 'album_disk':
                    foreach ($objects as $object_id) {
                        $output[$object_id] = [];

                        $sql        = "SELECT DISTINCT `song`.`id` FROM `song` INNER JOIN `album_disk` ON `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk` WHERE `album_disk`.`id` = ?;";
                        $db_results = Dba::read($sql, [$object_id]);
                        while ($row = Dba::fetch_assoc($db_results)) {
                            $output[$object_id][] = [
                                "id" => (string) $row['id'],
                                "type" => 'song'
                            ];
                        }
                    }
                    break;
                case 'playlist':
                    foreach ($objects as $object_id) {
                        $output[$object_id] = [];

                        /**
                         * Strip smart_ from playlist id and compare to original
                         * smartlist = 'smart_1'
                         * playlist = 1000000
                         */
                        if ((int) $object_id === 0) {
                            $playlist = new Search((int) str_replace('smart_', '', (string) $object_id), 'song', $user);
                            foreach ($playlist->get_items() as $song) {
                                $output[$object_id][] = [
                                    "id" => (string) $song['object_id'],
                                    "type" => 'song'
                                ];
                            }
                        } else {
                            $sql        = "SELECT `playlist_data`.`id`, `playlist_data`.`object_id`, `playlist_data`.`object_type` FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? ORDER BY `playlist_data`.`track`;";
                            $db_results = Dba::read($sql, [$object_id]);
                            while ($row = Dba::fetch_assoc($db_results)) {
                                $output[$object_id][] = [
                                    "id" => (string) $row['object_id'],
                                    "type" => $row['object_type']
                                ];
                            }
                        }
                    }
                    break;
                case 'podcast':
                    foreach ($objects as $object_id) {
                        $output[$object_id] = [];

                        $sql        = "SELECT DISTINCT `podcast_episode`.`id` FROM `podcast_episode` WHERE `podcast_episode`.`podcast` = ?;";
                        $db_results = Dba::read($sql, [$object_id]);
                        while ($row = Dba::fetch_assoc($db_results)) {
                            $output[$object_id][] = [
                                "id" => (string) $row['id'],
                                "type" => 'podcast_episode'
                            ];
                        }
                    }
                    break;
                case 'catalog':
                case 'live_stream':
                case 'podcast_episode':
                case 'share':
                case 'song':
                case 'video':
                    // These objects don't have children
                    $output = array_map('strval', $objects);
                    break;
            }
        } else {
            $output = array_map('strval', $objects);
        }
        $output = json_encode([$type => $output], JSON_PRETTY_PRINT);
        if ($output !== false) {
            return $output;
        }

        return self::error('4710', sprintf('Bad Request: %s', $type), 'indexes', 'type');
    }

    /**
     * indexes
     *
     * This takes an array of object_ids and return JSON based on the type of object
     *
     * Each type is handed to that type's own list method, so the response is the full object envelope
     * ({total_count, md5, <key>: [...]}) you would get from calling that method directly.
     * 'album_artist' and 'song_artist' are both returned under the "artist" key.
     *
     * @param array<int|string> $objects Array of object_ids (Mixed string|int)
     * @param string $type 'album_artist'|'album'|'artist'|'catalog'|'live_stream'|'playlist'|'podcast_episode'|'podcast'|'share'|'song_artist'|'song'|'video'
     * @param bool $include (add the extra songs details if a playlist or podcast_episodes if a podcast)
     * @return string JSON Object "catalog"|"song"|"album"|"artist"|"playlist"|"share"|"podcast"|"podcast_episode"|"video"|"live_stream"
     * @throws DateMalformedStringException
     */
    public static function indexes(array $objects, string $type, User $user, string $auth, bool $include = false): string
    {
        // here is where we call the object type
        switch ($type) {
            case 'catalog':
                $results = self::catalogs($objects);
                break;
            case 'song':
                $results = self::songs($objects, $user, $auth);
                break;
            case 'album':
                $include_array = ($include) ? ['songs'] : [];
                $results       = self::albums($objects, $include_array, $user, $auth);
                break;
            case 'album_artist':
            case 'artist':
            case 'song_artist':
                $include_array = ($include) ? ['songs', 'albums'] : [];
                $results       = self::artists($objects, $include_array, $user, $auth);
                break;
            case 'playlist':
                $results = self::playlists($objects, $user, $auth, $include);
                break;
            case 'share':
                $results = self::shares($objects, $user);
                break;
            case 'podcast':
                $results = self::podcasts($objects, $user, $auth, $include);
                break;
            case 'podcast_episode':
                $results = self::podcast_episodes($objects, $user, $auth);
                break;
            case 'video':
                $results = self::videos($objects, $user, $auth);
                break;
            case 'live_stream':
                $results = self::live_streams($objects);
                break;
            default:
                $results = self::error('4710', sprintf('Bad Request: %s', $type), 'indexes', 'type');
        }

        return $results;
    }

    /**
     * labels_string
     *
     * @param array<int|string> $objects
     * @param bool $object (whether to return as a named object array or regular array)
     * @return string JSON Object "label"
     */
    public static function labels(array $objects, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $md5         = md5(serialize($objects));
        $JSON        = self::labels_array($objects);

        if ($object) {
            $output = [
                "total_count" => self::$count,
                "md5" => $md5,
                "label" => $JSON
            ];
        } else {
            $output = $JSON[0] ?? [];
        }

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

        $JSON = [];

        $labelRepository = self::getLabelRepository();

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
     * @return string JSON Object "license"
     */
    public static function licenses(array $objects, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $md5         = md5(serialize($objects));
        $JSON        = self::licenses_array($objects);

        if ($object) {
            $output = [
                "total_count" => self::$count,
                "md5" => $md5,
                "license" => $JSON
            ];
        } else {
            $output = $JSON[0] ?? [];
        }

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * licenses_array
     *
     * @param array<int|string> $objects Licence id's assigned to songs and artists
     * @return array<int, array{
     *     id: string,
     *     name: string,
     *     description: string,
     *     external_link: string
     * }>
     */
    public static function licenses_array(array $objects): array
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $licenseRepository = self::getLicenseRepository();

        $JSON = [];
        foreach ($objects as $license_id) {
            $license = $licenseRepository->findById((int) $license_id);

            if ($license !== null) {
                $JSON[] = [
                    "id" => (string) $license_id,
                    "name" => $license->getName(),
                    "description" => $license->getDescription(),
                    "external_link" => $license->getExternalLink()
                ];
            }
        }

        return $JSON;
    }

    /**
     * lists
     *
     * This takes a name array of objects and return the data in JSON list object
     *
     * @param array{id: int|string, name: string}[] $objects Array of object_ids ["id" => 1, "name" => 'Artist Name']
     * @return string JSON Object "list"
     */
    public static function lists(array $objects): string
    {
        self::$count = self::$count ?: count($objects);
        $md5         = md5(serialize($objects));
        $JSON        = self::lists_array($objects);

        $output = [
            "total_count" => self::$count,
            "md5" => $md5,
            "list" => $JSON,
        ];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * lists_array
     *
     * @param array{id: int|string, name: string}[] $objects Array of object_ids ["id" => 1, "name" => 'Artist Name']
     * @return array<int, array{
     *     id: string,
     *     name: string,
     *     prefix: null|string,
     *     basename: string
     * }>
     */
    public static function lists_array(array $objects): array
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $JSON    = [];
        $pattern = '/^(' . implode('\\s|', explode('|', AmpConfig::get('catalog_prefix_pattern', 'The|An|A|Die|Das|Ein|Eine|Les|Le|La'))) . '\\s)(.*)/i';
        foreach ($objects as $object) {
            $trimmed  = Catalog::trim_prefix(trim((string) $object['name']), $pattern);
            $prefix   = $trimmed['prefix'] ?? null;
            $basename = $trimmed['string'];
            $JSON[]   = [
                "id" => (string) $object['id'],
                "name" => $object['name'],
                "prefix" => $prefix,
                "basename" => $basename,
            ];
        }

        return $JSON;
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
        $md5         = md5(serialize($objects));
        $JSON        = self::live_streams_array($objects);

        if ($object) {
            $output = [
                "total_count" => self::$count,
                "md5" => $md5,
                "live_stream" => $JSON
            ];
        } else {
            $output = $JSON[0] ?? [];
        }

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
     * now_playing
     *
     * This handles creating an JSON document for a now_playing list
     *
     * @param array<int, array{
     *     media: library_item,
     *     client: User,
     *     agent: string,
     *     expire: int
     * }> $results
     */
    public static function now_playing(array $results): string
    {
        $output = ["now_playing" => self::now_playing_array($results)];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * now_playing_array
     *
     * @param array<int, array{
     *     media: library_item,
     *     client: User,
     *     agent: string,
     *     expire: int
     * }> $results
     * @return array<int, array{
     *     id: string,
     *     type: string,
     *     client: string,
     *     expire: int,
     *     user: array{id: string, username: string}
     * }>
     */
    public static function now_playing_array(array $results): array
    {
        $JSON = [];
        foreach ($results as $now_playing) {
            $user = $now_playing['client'];
            if ($user->isNew()) {
                continue;
            }
            $media = $now_playing['media'];

            $JSON[] = [
                "id" => (string) $media->getId(),
                "type" => $media->getMediaType()->value,
                "client" => $now_playing['agent'],
                "expire" => (int) $now_playing['expire'],
                "user" => [
                    "id" => (string) $user->getId(),
                    "username" => $user->getUsername()
                ]
            ];
        }

        return $JSON;
    }

    /**
     * playlists_string
     *
     * This takes an array of playlist ids and then returns a nice pretty JSON document
     *
     * @param array<int|string> $objects Playlist id's to include
     * @param bool $object (whether to return as a named object array or regular array)
     * @return string JSON Object "playlist"
     */
    public static function playlists(array $objects, User $user, string $auth, bool $songs = false, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $md5         = md5(serialize($objects));
        $JSON        = self::playlists_array($objects, $user, $auth, $songs);

        if ($object) {
            $output = [
                "total_count" => self::$count,
                "md5" => $md5,
                "playlist" => $JSON
            ];
        } else {
            $output = $JSON[0] ?? [];
        }

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
     *     "user": array{"id": string, "username": null|string},
     *     "items": array<int, array{"id": string, "playlisttrack": int}>|int,
     *     "type": null|string,
     *     "art": null|string,
     *     "has_access": bool,
     *     "has_collaborate": bool,
     *     "has_art": bool,
     *     "flag": bool,
     *     "rating": int|null,
     *     "averagerating": float|null,
     *     "md5": null|string,
     *     "last_update": int|null,
     *     "time": int,
     * }>
     */
    public static function playlists_array(array $objects, User $user, string $auth, bool $songs = false): array
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $JSON = [];
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
            $art_url           = Art::url($playlist->id, $object_type, $auth);
            $playlist_name     = $playlist->get_fullname();
            $playlist_user     = $playlist->user;
            $playlist_username = $playlist->username;
            $playlist_type     = $playlist->type;
            $last_update       = $playlist->last_update;
            $last_duration     = (int) $playlist->last_duration;
            $duration          = 0;

            if ($songs) {
                $items          = [];
                $playlisttracks = array_values(
                    array_filter(
                        $playlist->get_items(),
                        static fn(array $track): bool => $track['object_type'] === LibraryItemEnum::SONG
                    )
                );
                foreach ($playlisttracks as $track) {
                    $duration += (int) $track['time'];

                    $items[] = [
                        "id" => (string) $track['object_id'],
                        "playlisttrack" => $track['track'],
                    ];
                }

                // hash the results
                $md5 = md5(serialize($playlisttracks));
            } else {
                $items = $playitem_total ?? 0;
                $md5   = null;
            }

            $rating          = new Rating($playlist->id, $object_type);
            $user_rating     = $rating->get_user_rating($user->getId());
            $flag            = new Userflag($playlist->id, $object_type);
            $has_access      = $playlist->has_access($user);
            $has_collaborate = $has_access ?: $playlist->has_collaborate($user);

            // Build this element
            $JSON[] = [
                "id" => (string) $playlist_id,
                "name" => $playlist_name,
                "owner" => $playlist_username,
                "user" => [
                    "id" => (string) $playlist_user,
                    "username" => $playlist_username
                ],
                "items" => $items,
                "type" => $playlist_type,
                "art" => $art_url,
                "has_access" => $has_access,
                "has_collaborate" => $has_collaborate,
                "has_art" => $playlist->has_art(),
                "flag" => (bool) $flag->get_flag($user->getId()),
                "rating" => $user_rating,
                "averagerating" => $rating->get_average_rating(),
                "md5" => $md5,
                "last_update" => $last_update,
                "time" => $duration ?: $last_duration,
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
    public static function podcast_episodes(array $objects, User $user, string $auth, bool $encode = true, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $md5         = md5(serialize($objects));
        $JSON        = self::podcast_episodes_array($objects, $user, $auth, $encode);

        if ($object) {
            $output = [
                "total_count" => self::$count,
                "md5" => $md5,
                "podcast_episode" => $JSON
            ];
        } else {
            $output = $JSON[0] ?? [];
        }

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
     *     "podcast": array{"id": string, "name": string},
     *     "description": string,
     *     "category": null|string,
     *     "author": null|string,
     *     "author_full": null|string,
     *     "website": string,
     *     "pubdate": null|string,
     *     "state": string,
     *     "filelength": string,
     *     "filesize": string,
     *     "filename": string,
     *     "mime": null|string,
     *     "time": int,
     *     "size": int,
     *     "bitrate": int,
     *     "stream_bitrate": int,
     *     "rate": int,
     *     "mode": null|string,
     *     "channels": int|null,
     *     "public_url": string,
     *     "url": string,
     *     "catalog": string,
     *     "art": null|string,
     *     "has_art": bool,
     *     "flag": bool,
     *     "rating": int|null,
     *     "averagerating": float|null,
     *     "playcount": int,
     *     "last_played": string|null,
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
                "podcast" => [
                    "id" => (string) $episode->podcast,
                    "name" => $episode->getPodcastName()
                ],
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
                "bitrate" => $episode->bitrate,
                "stream_bitrate" => $episode->bitrate,
                "rate" => $episode->rate,
                "mode" => $episode->mode,
                "channels" => $episode->channels,
                "public_url" => $episode->get_link(),
                "url" => $episode->play_url('', 'api', false, $user->getId(), $user->streamtoken),
                "catalog" => (string) $episode->catalog,
                "art" => $art_url,
                "has_art" => $episode->has_art(),
                "flag" => (bool) $flag->get_flag($user->getId()),
                "rating" => $user_rating,
                "averagerating" => $rating->get_average_rating(),
                "playcount" => $episode->total_count,
                "last_played" => ($episode->last_played) ? date(DATE_ATOM, $episode->last_played) : null,
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
     * @return string JSON Object "podcast"
     * @throws DateMalformedStringException
     */
    public static function podcasts(array $objects, User $user, string $auth, bool $episodes = false, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $md5         = md5(serialize($objects));
        $JSON        = self::podcasts_array($objects, $user, $auth, $episodes);

        if ($object) {
            $output = [
                "total_count" => self::$count,
                "md5" => $md5,
                "podcast" => $JSON
            ];
        } else {
            $output = $JSON[0] ?? [];
        }

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
     *     "has_art": bool,
     *     "flag": bool,
     *     "rating": int|null,
     *     "averagerating": float|null,
     *     "catalog": string,
     *     "podcast_episode": array<int, array{
     *         "id": string,
     *         "title": null|string,
     *         "name": null|string,
     *         "podcast": array{"id": string, "name": string},
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
     *         "bitrate": int,
     *         "stream_bitrate": int,
     *         "rate": int,
     *         "mode": null|string,
     *         "channels": int|null,
     *         "public_url": string,
     *         "url": string,
     *         "catalog": string,
     *         "art": null|string,
     *         "has_art": bool,
     *         "flag": bool,
     *         "rating": int|null,
     *         "averagerating": float|null,
     *         "playcount": int,
     *         "last_played": string|null,
     *         "played": string
     *     }>
     * }>
     * @throws DateMalformedStringException
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
                "has_art" => $podcast->has_art(),
                "flag" => (bool) $flag->get_flag($user->getId()),
                "rating" => $user_rating,
                "averagerating" => $rating->get_average_rating(),
                "catalog" => (string) $podcast->getCatalogId(),
                "podcast_episode" => $podcast_episodes
            ];
        }

        return $JSON;
    }

    /**
     * set_count
     *
     * Set the total count of returned objects
     *
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
        $md5         = md5(serialize($objects));
        $JSON        = self::shares_array($objects, $user);

        if ($object) {
            $output = [
                "total_count" => self::$count,
                "md5" => $md5,
                "share" => $JSON
            ];
        } else {
            $output = $JSON[0] ?? [];
        }

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * shares_array
     *
     * @param array<int|string> $objects Share id's to include
     * @return array<int, array{
     *     id: string,
     *     name: string,
     *     owner: string,
     *     allow_stream: bool,
     *     allow_download: bool,
     *     creation_date: int,
     *     lastvisit_date: int,
     *     object_type: null|string,
     *     object_id: string,
     *     expire_days: int,
     *     max_counter: int,
     *     counter: int,
     *     secret: null|string,
     *     public_url: null|string,
     *     description: null|string
     * }>
     */
    public static function shares_array(array $objects, User $user): array
    {
        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit);

        $JSON = [];
        foreach ($objects as $share_id) {
            $share = new Share((int) $share_id);
            if ($share->isNew() || !$share->isAccessible($user)) {
                continue;
            }

            // Build this element
            $JSON[] = [
                "id" => (string) $share_id,
                "name" => $share->getObjectName(),
                "owner" => $share->getUserName(),
                "allow_stream" => $share->allow_stream,
                "allow_download" => $share->allow_download,
                "creation_date" => $share->creation_date,
                "lastvisit_date" => $share->lastvisit_date,
                "object_type" => $share->object_type,
                "object_id" => (string) $share->object_id,
                "expire_days" => $share->expire_days,
                "max_counter" => $share->max_counter,
                "counter" => $share->counter,
                "secret" => $share->secret,
                "public_url" => $share->public_url,
                "description" => $share->description
            ];
        }

        return $JSON;
    }

    /**
     * shouts
     *
     * This handles creating an JSON document for a shout list
     *
     * @param array<Shoutbox> $shouts Shout id list
     * @param bool $object (whether to return as a named object array or regular array)
     * @return string JSON Object "shout"
     */
    public static function shouts(array $shouts, bool $object = true): string
    {
        $JSON   = self::shouts_array($shouts);
        $output = ($object) ? ["shout" => $JSON] : $JSON[0] ?? [];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * shouts_array
     *
     * @param array<Shoutbox> $shouts Shout id list
     * @return array<int, array{
     *     id: string,
     *     date: int,
     *     text: string,
     *     object_type: LibraryItemEnum,
     *     object_id: string,
     *     user: array{id: string, username: string}
     * }>
     */
    public static function shouts_array(array $shouts): array
    {
        $JSON = [];

        foreach ($shouts as $shout) {
            $user = $shout->getUser();

            $JSON[] = [
                "id" => (string) $shout->getId(),
                "date" => $shout->getDate()->getTimestamp(),
                "text" => $shout->getText(),
                "object_type" => $shout->getObjectType(),
                "object_id" => (string) $shout->getObjectId(),
                "user" => [
                    "id" => (string) ($user?->getId() ?? 0),
                    "username" => $user?->getUsername() ?? '',
                ]
            ];
        }

        return $JSON;
    }

    /**
     * song_tags
     *
     * This returns an array of song tags populated from an array of song ids.
     *
     * @param array<int|string> $objects
     * @param bool $object (whether to return as a named object array or regular array)
     * @return string JSON Object "song_tag"
     */
    public static function song_tags(array $objects, string $auth, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $md5         = md5(serialize($objects));
        $JSON        = self::song_tags_array($objects, $auth);

        if ($object) {
            $output = [
                "total_count" => self::$count,
                "md5" => $md5,
                "song_tag" => $JSON
            ];
        } else {
            $output = $JSON[0] ?? [];
        }

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * song_tags_array
     *
     * Raw file tag (vainfo) metadata read from the catalog for each song via
     * `Catalog::get_media_tags()`. Every key is always present, but any value may
     * be null when the tag is absent from the file (the builder reads each with
     * `?? null`), so every field except `id` is nullable.
     *
     * @param array<int|string> $objects
     * @return array<int, array{
     *     id: string,
     *     albumartist: null|string,
     *     album: null|string,
     *     artist: null|string,
     *     artists: null|array<string>,
     *     art: null|string,
     *     audio_codec: null|string,
     *     barcode: null|string,
     *     bitrate: null|int,
     *     catalog: null|int,
     *     catalog_number: null|string,
     *     channels: null|int,
     *     comment: null|string,
     *     composer: null|string,
     *     description: null|string,
     *     disk: null|int,
     *     disksubtitle: null|string,
     *     display_x: null|int,
     *     display_y: null|int,
     *     encoding: null|string,
     *     file: null|string,
     *     frame_rate: null|float,
     *     genre: null|array<string>,
     *     isrc: null|string,
     *     language: null|string,
     *     lyrics: null|string,
     *     mb_albumartistid: null|string,
     *     mb_albumartistid_array: null|array<string>,
     *     mb_albumid_group: null|string,
     *     mb_albumid: null|string,
     *     mb_artistid: null|string,
     *     mb_artistid_array: null|array<string>,
     *     mb_trackid: null|string,
     *     mime: null|string,
     *     mode: null|string,
     *     original_name: null|string,
     *     original_year: null|string,
     *     publisher: null|string,
     *     r128_album_gain: null|int,
     *     r128_track_gain: null|int,
     *     rate: null|int,
     *     rating: null|float,
     *     release_date: null|string,
     *     release_status: null|string,
     *     release_type: null|string,
     *     replaygain_album_gain: null|float,
     *     replaygain_album_peak: null|float,
     *     replaygain_track_gain: null|float,
     *     replaygain_track_peak: null|float,
     *     size: null|int,
     *     version: null|string,
     *     summary: null|string,
     *     time: null|int,
     *     title: null|string,
     *     totaldisks: null|int,
     *     totaltracks: null|int,
     *     track: null|int,
     *     year: null|int
     * }>
     */
    public static function song_tags_array(array $objects, string $auth): array
    {
        self::$count = self::$count ?: count($objects);

        Stream::set_session($auth);

        $JSON = [];
        foreach ($objects as $song_id) {
            $song = new Song((int) $song_id);
            // If the song id is invalid/null
            if ($song->isNew()) {
                continue;
            }
            $catalog = Catalog::create_from_id($song->catalog);
            if (!$catalog) {
                continue;
            }
            $results  = $catalog->get_media_tags($song, ['music'], '', '');
            $objArray = [
                'id' => (string) $song_id,
                'albumartist' => $results['albumartist'] ?? null,
                'album' => $results['album'] ?? null,
                'artist' => $results['artist'] ?? null,
                'artists' => $results['artists'] ?? null,
                'art' => $results['art'] ?? null,
                'audio_codec' => $results['audio_codec'] ?? null,
                'barcode' => $results['barcode'] ?? null,
                'bitrate' => $results['bitrate'] ?? null,
                'catalog' => $results['catalog'] ?? null,
                'catalog_number' => $results['catalog_number'] ?? null,
                'channels' => $results['channels'] ?? null,
                'comment' => $results['comment'] ?? null,
                'composer' => $results['composer'] ?? null,
                'description' => $results['description'] ?? null,
                'disk' => $results['disk'] ?? null,
                'disksubtitle' => $results['disksubtitle'] ?? null,
                'display_x' => $results['display_x'] ?? null,
                'display_y' => $results['display_y'] ?? null,
                'encoding' => $results['encoding'] ?? null,
                'file' => $results['file'] ?? null,
                'frame_rate' => $results['frame_rate'] ?? null,
                'genre' => $results['genre'] ?? null,
                'isrc' => $results['isrc'] ?? null,
                'language' => $results['language'] ?? null,
                'lyrics' => $results['lyrics'] ?? null,
                'mb_albumartistid' => $results['mb_albumartistid'] ?? null,
                'mb_albumartistid_array' => $results['mb_albumartistid_array'] ?? null,
                'mb_albumid_group' => $results['mb_albumid_group'] ?? null,
                'mb_albumid' => $results['mb_albumid'] ?? null,
                'mb_artistid' => $results['mb_artistid'] ?? null,
                'mb_artistid_array' => $results['mb_artistid_array'] ?? null,
                'mb_trackid' => $results['mb_trackid'] ?? null,
                'mime' => $results['mime'] ?? null,
                'mode' => $results['mode'] ?? null,
                'original_name' => $results['original_name'] ?? null,
                'original_year' => $results['original_year'] ?? null,
                'publisher' => $results['publisher'] ?? null,
                'r128_album_gain' => $results['r128_album_gain'] ?? null,
                'r128_track_gain' => $results['r128_track_gain'] ?? null,
                'rate' => $results['rate'] ?? null,
                'rating' => $results['rating'] ?? null,
                'release_date' => $results['release_date'] ?? null,
                'release_status' => $results['release_status'] ?? null,
                'release_type' => $results['release_type'] ?? null,
                'replaygain_album_gain' => $results['replaygain_album_gain'] ?? null,
                'replaygain_album_peak' => $results['replaygain_album_peak'] ?? null,
                'replaygain_track_gain' => $results['replaygain_track_gain'] ?? null,
                'replaygain_track_peak' => $results['replaygain_track_peak'] ?? null,
                'size' => $results['size'] ?? null,
                'version' => $results['version'] ?? null,
                'summary' => $results['summary'] ?? null,
                'time' => $results['time'] ?? null,
                'title' => $results['title'] ?? null,
                'totaldisks' => $results['totaldisks'] ?? null,
                'totaltracks' => $results['totaltracks'] ?? null,
                'track' => $results['track'] ?? null,
                'year' => $results['year'] ?? null,
            ];

            $JSON[] = $objArray;
        }

        return $JSON;
    }

    /**
     * songs_string
     *
     * This returns an array of songs populated from an array of song ids.
     * (Spiffy isn't it!)
     * @param array<int|string> $objects
     * @param bool $object (whether to return as a named object array or regular array)
     * @return string JSON Object "song"
     */
    public static function songs(array $objects, User $user, string $auth, bool $encode = true, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $md5         = md5(serialize($objects));
        $JSON        = self::songs_array($objects, $user, $auth, $encode);

        $output      = [
            "total_count" => self::$count,
            "md5" => $md5,
        ];

        if ($object) {
            $output["song"] = $JSON;
        } else {
            $output = $JSON[0] ?? [];
        }

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * songs_array
     *
     * @param array<int|string> $objects
     * @return array<int, array{
     *     id: string,
     *     title: string|null,
     *     name: string|null,
     *     artist: array{
     *         id: string,
     *         name: string|null,
     *         prefix: string|null,
     *         basename: string|null
     *     },
     *     artists: array<int, array{
     *         id: string,
     *         name: string|null,
     *         prefix: string|null,
     *         basename: string|null
     *     }>,
     *     album: array{
     *         id: string,
     *         name: string|null,
     *         prefix: string|null,
     *         basename: string|null
     *     },
     *     albumartist?: array{
     *         id: string,
     *         name: string|null,
     *         prefix: string|null,
     *         basename: string|null
     *     },
     *     disk: int,
     *     disksubtitle: string|null,
     *     track: int,
     *     filename: string|null,
     *     genre: array<int, array{id: string, name: string}>,
     *     playlisttrack: int,
     *     time: int,
     *     year: int,
     *     format: string|null,
     *     stream_format: string|null,
     *     bitrate: int|null,
     *     stream_bitrate: int|null,
     *     rate: int,
     *     mode: string|null,
     *     mime: string|null,
     *     stream_mime: string|null,
     *     url: string,
     *     size: int,
     *     mbid: string|null,
     *     art: string|null,
     *     has_art: bool,
     *     flag: bool,
     *     rating: int|null,
     *     averagerating: float|null,
     *     playcount: int,
     *     last_played: string|null,
     *     catalog: string,
     *     composer: string|null,
     *     channels: int|null,
     *     comment: string|null,
     *     license: string|null,
     *     publisher: string|null,
     *     language: string|null,
     *     lyrics: string|null,
     *     replaygain_album_gain: float|null,
     *     replaygain_album_peak: float|null,
     *     replaygain_track_gain: float|null,
     *     replaygain_track_peak: float|null,
     *     r128_album_gain: float|null,
     *     r128_track_gain: float|null,
     *     metadata?: array<string, string>
     * }>
     */
    public static function songs_array(array $objects, User $user, string $auth, bool $encode = true): array
    {
        Stream::set_session($auth);
        $playlist_track = 0;

        self::$count = self::$count ?: count($objects);
        $objects     = Api::filter_objects($objects, self::$count, self::$offset, self::$limit, $encode);

        Song::build_cache($objects);

        $JSON = [];
        foreach ($objects as $song_id) {
            $song = new Song((int) $song_id);
            // If the song id is invalid/null
            if ($song->isNew()) {
                continue;
            }
            $song->fill_ext_info();
            $rating      = new Rating((int) $song_id, 'song');
            $user_rating = $rating->get_user_rating($user->getId());
            $flag        = new Userflag((int) $song_id, 'song');
            $art_url     = Art::url($song->album, 'album', $auth);
            $songType    = $song->type;
            $songMime    = $song->mime;
            $songBitrate = $song->bitrate;
            $play_url    = $song->play_url('', 'api', false, $user->id, $user->streamtoken);
            $song_album  = self::getAlbumRepository()->getNames($song->album);
            $song_artist = Artist::get_name_array_by_id($song->artist);
            /** @var array<int, array{id: string, name: string, prefix: string, basename: string}> $song_artists */
            $song_artists = [];
            foreach ($song->get_artists() as $artist_id) {
                $artist = Artist::get_name_array_by_id($artist_id);

                $song_artists[] = [
                    'id' => $artist['id'],
                    'name' => $artist['name'],
                    'prefix' => $artist['prefix'],
                    'basename' => $artist['basename'],
                ];
            }

            $license     = $song->getLicense();
            $licenseLink = $license?->getExternalLink() ?: null;

            $playlist_track++;

            $objArray = [
                "id" => (string) $song->id,
                "title" => $song->get_fullname(),
                "name" => $song->get_fullname(),
                "artist" => [
                    "id" => (string) $song->artist,
                    "name" => $song_artist['name'],
                    "prefix" => $song_artist['prefix'],
                    "basename" => $song_artist['basename']
                ],
                "artists" => $song_artists,
                "album" => [
                    "id" => (string) $song->album,
                    "name" => $song_album['name'],
                    "prefix" => $song_album['prefix'],
                    "basename" => $song_album['basename']
                ]
            ];
            if ($song->get_album_artist_fullname() != "") {
                $album_artist = ($song->artist !== $song->albumartist)
                    ? Artist::get_name_array_by_id($song->albumartist)
                    : $song_artist;
                $objArray['albumartist'] = [
                    "id" => (string) $song->albumartist,
                    "name" => $album_artist['name'],
                    "prefix" => $album_artist['prefix'],
                    "basename" => $album_artist['basename']
                ];
            }

            $objArray['disk']                  = (int) $song->disk;
            $objArray['disksubtitle']          = $song->disksubtitle;
            $objArray['track']                 = (int) $song->track;
            $objArray['filename']              = $song->file;
            $objArray['genre']                 = self::_genre_array($song->get_tags());
            $objArray['playlisttrack']         = $playlist_track;
            $objArray['time']                  = $song->time;
            $objArray['year']                  = $song->year;
            $objArray['format']                = $songType;
            $objArray['stream_format']         = $song->type;
            $objArray['bitrate']               = $songBitrate;
            $objArray['stream_bitrate']        = $song->bitrate;
            $objArray['rate']                  = $song->rate;
            $objArray['mode']                  = $song->mode;
            $objArray['mime']                  = $songMime;
            $objArray['stream_mime']           = $song->mime;
            $objArray['url']                   = $play_url;
            $objArray['size']                  = $song->size;
            $objArray['mbid']                  = $song->mbid;
            $objArray['art']                   = $art_url;
            $objArray['has_art']               = $song->has_art();
            $objArray['flag']                  = (bool) $flag->get_flag($user->getId());
            $objArray['rating']                = $user_rating;
            $objArray['averagerating']         = $rating->get_average_rating();
            $objArray['playcount']             = $song->total_count;
            $objArray['last_played']           = ($song->last_played) ? date(DATE_ATOM, $song->last_played) : null;
            $objArray['catalog']               = (string) $song->getCatalogId();
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
                    if (!isset($objArray['metadata'])) {
                        $objArray['metadata'] = [];
                    }
                    $meta_name = (string) str_replace(
                        [' ', '(', ')', '/', '\\', '#'],
                        '_',
                        $field->getName()
                    );
                    $objArray['metadata'][$meta_name] = $metadata->getData();
                }
            }
            $JSON[] = $objArray;
        }

        return $JSON;
    }

    /**
     * sonic_matches
     *
     * Songs that sound like a query song, each carrying its similarity score.
     *
     * The score shares the OpenSubsonic `sonicMatch` scale so a client sees the same number from either API: 1.0 is
     * the same recording, 0.0 the most different, and -1 when the analysis backend gives no comparable score.
     *
     * @param list<array{'id': int, 'similarity': float}> $matches
     */
    public static function sonic_matches(array $matches, User $user, string $auth, bool $object = true): string
    {
        $similarity = [];
        foreach ($matches as $match) {
            $similarity[(int) $match['id']] = (float) $match['similarity'];
        }

        $ids = array_keys($similarity);

        self::$count = self::$count ?: count($ids);

        // songs_array() already applies the offset/limit window, so the scores are attached to whatever it returns
        // rather than to the full id list.
        $songs = self::songs_array($ids, $user, $auth);
        foreach ($songs as $index => $song) {
            $songs[$index]['similarity'] = $similarity[(int) $song['id']] ?? -1.0;
        }

        $output = ($object) ? ["sonic_match" => $songs] : $songs;

        return json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '';
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
        $message = ["success" => $string];
        foreach ($return_data as $title => $data) {
            $message[$title] = $data;
        }

        return json_encode($message, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * timeline
     *
     * This handles creating an JSON document for an activity list
     *
     * @param int[] $activities Activity id list
     * @param bool $object (whether to return as a named object array or regular array)
     * @return string JSON Object "activity"
     */
    public static function timeline(array $activities, bool $object = true): string
    {
        $JSON   = self::timeline_array($activities);
        $output = ($object) ? ["activity" => $JSON] : $JSON[0] ?? [];

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * timeline_array
     *
     * @param int[] $activities Activity id list
     * @return array<int, array{
     *     id: string,
     *     date: int,
     *     object_type: null|string,
     *     object_id: string,
     *     action: string,
     *     user: array{id: string, username: null|string}
     * }>
     */
    public static function timeline_array(array $activities): array
    {
        $JSON = [];
        foreach ($activities as $activity_id) {
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

        return $JSON;
    }

    /**
     * user
     *
     * This handles creating an JSON document for a user
     */
    public static function user(User $user, bool $fullinfo, string $auth, ?bool $object = true): string
    {
        $JSON   = self::user_array($user, $fullinfo, $auth);
        $output = ($object) ? ["user" => $JSON] : $JSON;

        return json_encode($output, JSON_PRETTY_PRINT) ?: '';
    }

    /**
     * user_array
     *
     * The extended fields (auth, email, access, ...) are only returned when
     * $fullinfo is true; fullname only when the user made it public.
     *
     * @return array{
     *     id: string,
     *     username: null|string,
     *     create_date: int|null,
     *     last_seen: int,
     *     link: string,
     *     website: null|string,
     *     state: null|string,
     *     city: null|string,
     *     art: null|string,
     *     has_art: bool,
     *     auth?: null|string,
     *     email?: null|string,
     *     access?: int,
     *     streamtoken?: null|string,
     *     fullname_public?: bool,
     *     validation?: null|string,
     *     disabled?: bool,
     *     fullname?: null|string
     * }
     */
    public static function user_array(User $user, bool $fullinfo, string $auth): array
    {
        $art_url = Art::url($user->id, 'user', $auth);
        if ($fullinfo) {
            $JSON = [
                "id" => (string) $user->id,
                "username" => $user->username,
                "auth" => $user->apikey,
                "email" => $user->email,
                "access" => $user->access,
                "streamtoken" => $user->streamtoken,
                "fullname_public" => $user->fullname_public,
                "validation" => $user->validation,
                "disabled" => $user->disabled,
                "create_date" => (int) $user->create_date,
                "last_seen" => $user->last_seen,
                "link" => $user->get_link(),
                "website" => $user->website,
                "state" => $user->state,
                "city" => $user->city,
                "art" => $art_url,
                "has_art" => $user->has_art()
            ];
        } else {
            $JSON = [
                "id" => (string) $user->id,
                "username" => $user->username,
                "create_date" => $user->create_date,
                "last_seen" => $user->last_seen,
                "link" => $user->get_link(),
                "website" => $user->website,
                "state" => $user->state,
                "city" => $user->city,
                "art" => $art_url,
                "has_art" => $user->has_art()
            ];
        }
        if ($user->fullname_public || $fullinfo) {
            $JSON['fullname'] = $user->fullname;
        }

        return $JSON;
    }

    /**
     * users
     *
     * This handles creating an JSON document for a user list
     *
     * @param array<int|string> $objects User id list
     * @param bool $encode return the array and don't json_encode the data
     * @param bool $object (whether to return as a named object array or regular array)
     * @return string JSON Object "user"
     */
    public static function users(array $objects, bool $encode = true, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $JSON        = self::users_array($objects, $encode);

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
     * videos_string
     *
     * @param array<int|string> $objects Video id's to include
     * @param bool $object (whether to return as a named object array or regular array)
     * @return string JSON Object "video"
     */
    public static function videos(array $objects, User $user, string $auth, bool $object = true): string
    {
        self::$count = self::$count ?: count($objects);
        $md5         = md5(serialize($objects));
        $JSON        = self::videos_array($objects, $user, $auth);

        if ($object) {
            $output = [
                "total_count" => self::$count,
                "md5" => $md5,
                "video" => $JSON
            ];
        } else {
            $output = $JSON[0] ?? [];
        }

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
     *     "has_art": bool,
     *     "flag": bool,
     *     "rating": int|null,
     *     "averagerating": float|null,
     *     "playcount": int,
     *     "last_played": string|null,
     *     "catalog": string
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
                "has_art" => $video->has_art(),
                "flag" => (bool) $flag->get_flag($user->getId()),
                "rating" => $user_rating,
                "averagerating" => $rating->get_average_rating(),
                "playcount" => $video->total_count,
                "last_played" => ($video->last_played) ? date(DATE_ATOM, $video->last_played) : null,
                "catalog" => (string) $video->getCatalogId()
            ];
        }

        return $JSON;
    }

    /**
     * genre_array
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
     * The members of a collection as one ordered list, each entry tagged with its position and type.
     *
     * A collection is heterogeneous, so every entry names its own type and nests the object under that key --
     * a client walks the list in the order it is given and never has to re-sort. Objects are still built one
     * batch per type, then looked back up by id, so the curated order costs no extra queries.
     *
     * @param array<int, array{object_type: string, object_id: int, track: int, track_id: int}> $items
     * @return list<array<string, mixed>>
     */
    private static function collection_contents(array $items, User $user, string $auth): array
    {
        // Keyed by id rather than appended, so a repeated member asks its builder for one row, not two
        $idsByType = [];
        foreach ($items as $item) {
            $idsByType[$item['object_type']][$item['object_id']] = $item['object_id'];
        }

        // Indexed by type and id, so the walk below can pick each member out in curated order
        $rendered = [];
        foreach ($idsByType as $objectType => $ids) {
            foreach (self::collection_group($objectType, array_values($ids), $user, $auth) ?? [] as $row) {
                if (array_key_exists('id', $row)) {
                    $rendered[$objectType][(int) $row['id']] = $row;
                }
            }
        }

        $contents = [];
        foreach ($items as $item) {
            $objectType = $item['object_type'];
            // A member the builder skipped (unreadable, or filtered for this user) drops out rather than
            // leaving a hole the client has to guess at
            if (!isset($rendered[$objectType][$item['object_id']])) {
                continue;
            }

            $contents[] = [
                'track' => $item['track'],
                'track_id' => $item['track_id'],
                'object_type' => $objectType,
                $objectType => $rendered[$objectType][$item['object_id']],
            ];
        }

        return $contents;
    }

    /**
     * Render every member of one type through that type's own builder. Null when the type has no builder.
     *
     * @param list<int> $ids
     * @return array<int, array<string, mixed>>|null
     */
    private static function collection_group(string $objectType, array $ids, User $user, string $auth): ?array
    {
        // Each group hands to that type's existing array builder; the key is the API spelling, so `genre` stays here.
        return match ($objectType) {
            'album' => self::albums_array($ids, [], $user, $auth),
            'album_disk' => self::album_disks_array($ids, [], $user, $auth),
            'artist' => self::artists_array($ids, [], $user, $auth),
            'folder' => self::folders_array($ids, $user, $auth),
            'genre' => self::genres_array($ids),
            'label' => self::labels_array($ids),
            'live_stream' => self::live_streams_array($ids),
            'playlist' => self::playlists_array($ids, $user, $auth),
            'podcast' => self::podcasts_array($ids, $user, $auth),
            'podcast_episode' => self::podcast_episodes_array($ids, $user, $auth),
            'song' => self::songs_array($ids, $user, $auth),
            'video' => self::videos_array($ids, $user, $auth),
            default => null,
        };
    }

    /**
     * The scalar fields of a collection, shared by the list and the single-collection responses.
     *
     * @return array{
     *     id: string,
     *     name: string,
     *     owner: null|string,
     *     type: null|string,
     *     object_type: null|string,
     *     items: int,
     *     has_art: bool
     * }
     */
    private static function collection_row(Collection $collection): array
    {
        return [
            'id' => (string) $collection->getId(),
            'name' => (string) $collection->get_fullname(),
            'owner' => $collection->username,
            'type' => $collection->type,
            'object_type' => $collection->object_type,
            'items' => $collection->get_item_count(),
            'has_art' => $collection->has_art(),
        ];
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
