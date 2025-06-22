<?php

/** @noinspection PhpUnused */

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

namespace Ampache\Module\Api;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\Podcast\Exception\PodcastCreationException;
use Ampache\Module\Podcast\PodcastCreatorInterface;
use Ampache\Module\Podcast\PodcastDeleterInterface;
use Ampache\Module\Podcast\PodcastSyncerInterface;
use Ampache\Module\Share\ShareCreatorInterface;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Core;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\Recommendation;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\LiveStreamRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Media;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\PrivateMsg;
use Ampache\Repository\Model\Random;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\User_Playlist;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\Model\Video;
use Ampache\Repository\PodcastRepositoryInterface;
use Ampache\Repository\PrivateMessageRepositoryInterface;
use Ampache\Repository\ShareRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use CurlHandle;
use DateTime;
use DOMDocument;
use Psr\Container\ContainerExceptionInterface;
use SimpleXMLElement;
use WpOrg\Requests\Requests;

/**
 * Subsonic Class
 *
 * This class wraps Ampache to Subsonic API functions. See https://www.subsonic.org/pages/api.jsp
 *
 * @SuppressWarnings("unused")
 */
class Subsonic_Api
{
    // TODO remove openSubsonic extensions in Ampache 8.0
    public const API_VERSION = "1.16.1";

    /**
     * List of internal functions that should be skipped when called from SubsonicApiApplication
     * @var string[]
     */
    public const SYSTEM_LIST = [
        '_addJsonResponse',
        '_addXmlResponse',
        '_albumList',
        '_check_parameter',
        '_errorOutput',
        '_follow_stream',
        '_getAmpacheIdArrays',
        '_jsonOutput',
        '_jsonpOutput',
        '_output_body',
        '_output_header',
        '_responseOutput',
        '_search',
        '_setStar',
        '_updatePlaylist',
        '_xmlOutput',
        'decryptPassword',
        'error',
        'getAlbumSubId',
        'getAmpacheId',
        'getAmpacheObject',
        'getAmpacheType',
        'getArtistSubId',
        'getBookmarkSubId',
        'getCatalogSubId',
        'getChatSubId',
        'getGenreSubId',
        'getLiveStreamSubId',
        'getPlaylistSubId',
        'getPodcastEpisodeSubId',
        'getPodcastSubId',
        'getShareSubId',
        'getSmartPlaylistSubId',
        'getSongSubId',
        'getUserSubId',
        'getVideoSubId',
    ];

    public const SSERROR_GENERIC = 0; // A generic error.

    public const SSERROR_MISSINGPARAM = 10; // Required parameter is missing.

    public const SSERROR_APIVERSION_CLIENT = 20; // Incompatible Subsonic REST protocol version. Client must upgrade.

    public const SSERROR_APIVERSION_SERVER = 30; // Incompatible Subsonic REST protocol version. Server must upgrade.

    public const SSERROR_BADAUTH = 40; // Wrong username or password.

    public const SSERROR_TOKENAUTHNOTSUPPORTED = 41; // Token authentication not supported for LDAP users.

    public const SSERROR_AUTHMETHODNOTSUPPORTED = 42; // TODO remove for pure subsonic (openSubsonic only)

    public const SSERROR_AUTHMETHODCONFLICT = 43; // TODO remove for pure subsonic (openSubsonic only)

    public const SSERROR_BADAPIKEY = 44; // TODO remove for pure subsonic (openSubsonic only)

    public const SSERROR_UNAUTHORIZED = 50; // User is not authorized for the given operation.

    public const SSERROR_TRIAL = 60; // The trial period for the Subsonic server is over. Please upgrade to Subsonic Premium. Visit subsonic.org for details.

    public const SSERROR_DATA_NOTFOUND = 70; // The requested data was not found.

    /**
     * Ampache doesn't have a global unique id but items are unique per category. We use id prefixes to identify item category.
     */

    public const OLD_SUBID_ALBUM = 200000000;

    public const OLD_SUBID_ARTIST = 100000000;

    public const OLD_SUBID_PLAYLIST = 800000000;

    public const OLD_SUBID_PODCAST = 600000000;

    public const OLD_SUBID_PODCASTEP = 700000000;

    public const OLD_SUBID_SMARTPL = 400000000;

    public const OLD_SUBID_SONG = 300000000;

    public const OLD_SUBID_VIDEO = 500000000;

    public const SUBID_ALBUM = 'al-';

    public const SUBID_ARTIST = 'ar-';

    public const SUBID_BOOKMARK = 'bo-';

    public const SUBID_CATALOG = 'mf-';

    public const SUBID_CHAT = 'pm-';

    public const SUBID_GENRE = 'ta-';

    public const SUBID_LIVESTREAM = 'li-';

    public const SUBID_PLAYLIST = 'pl-';

    public const SUBID_PODCASTEP = 'pe-';

    public const SUBID_PODCAST = 'po-';

    public const SUBID_SHARE = 'sh-';

    public const SUBID_SMARTPL = 'sp-';

    public const SUBID_SONG = 'so-';

    public const SUBID_USER = 'us-';

    public const SUBID_VIDEO = 'vi-';

    public static function getAlbumSubId(int $ampache_id): string
    {
        return (string)(self::OLD_SUBID_ALBUM + $ampache_id);
    }

    public static function getArtistSubId(int $ampache_id): string
    {
        return (string)(self::OLD_SUBID_ARTIST + $ampache_id);
    }

    public static function getBookmarkSubId(int|string $ampache_id): string
    {
        return self::SUBID_BOOKMARK . $ampache_id;
    }

    public static function getCatalogSubId(int|string $ampache_id): string
    {
        return self::SUBID_CATALOG . $ampache_id;
    }

    public static function getChatSubId(int|string $ampache_id): string
    {
        return self::SUBID_CHAT . $ampache_id;
    }

    public static function getGenreSubId(int|string $ampache_id): string
    {
        return self::SUBID_GENRE . $ampache_id;
    }

    public static function getLiveStreamSubId(int|string $ampache_id): string
    {
        return self::SUBID_LIVESTREAM . $ampache_id;
    }

    public static function getPlaylistSubId(int $ampache_id): string
    {
        return (string)(self::OLD_SUBID_PLAYLIST + $ampache_id);
    }

    public static function getPodcastSubId(int $ampache_id): string
    {
        return (string)(self::OLD_SUBID_PODCAST + $ampache_id);
    }

    public static function getPodcastEpisodeSubId(int $ampache_id): string
    {
        return (string)(self::OLD_SUBID_PODCASTEP + $ampache_id);
    }
    public static function getShareSubId(int|string $ampache_id): string
    {
        return self::SUBID_SHARE . $ampache_id;
    }

    public static function getSmartPlaylistSubId(int $ampache_id): string
    {
        return (string)(self::OLD_SUBID_SMARTPL + $ampache_id);
    }

    public static function getSongSubId(int $ampache_id): string
    {
        return (string)(self::OLD_SUBID_SONG + $ampache_id);
    }

    public static function getUserSubId(int|string $ampache_id): string
    {
        return self::SUBID_USER . $ampache_id;
    }

    public static function getVideoSubId(int $ampache_id): string
    {
        return (string)(self::OLD_SUBID_VIDEO + $ampache_id);
    }

    /**
     * getAmpacheObject
     * Return the Ampache media object
     */
    public static function getAmpacheObject(string $sub_id): ?object
    {
        // keep oldstyle subsonic ids for compatibility (TODO REMOVE IN AMPACHE 8.0)
        if (is_numeric($sub_id)) {
            $int_id = (int)$sub_id;
            if ($int_id >= self::OLD_SUBID_ARTIST && $int_id < self::OLD_SUBID_ALBUM) {
                return new Artist($int_id - self::OLD_SUBID_ARTIST);
            }
            if ($int_id >= self::OLD_SUBID_ALBUM && $int_id < self::OLD_SUBID_SONG) {
                return new Album($int_id - self::OLD_SUBID_ALBUM);
            }
            if ($int_id >= self::OLD_SUBID_SONG && $int_id < self::OLD_SUBID_SMARTPL) {
                return new Song($int_id - self::OLD_SUBID_SONG);
            }
            if ($int_id >= self::OLD_SUBID_SMARTPL && $int_id < self::OLD_SUBID_VIDEO) {
                return new Search($int_id - self::OLD_SUBID_SMARTPL);
            }
            if ($int_id >= self::OLD_SUBID_VIDEO && $int_id < self::OLD_SUBID_PODCAST) {
                return new Video($int_id - self::OLD_SUBID_VIDEO);
            }
            if ($int_id >= self::OLD_SUBID_PODCAST && $int_id < self::OLD_SUBID_PODCASTEP) {
                return new Artist($int_id - self::OLD_SUBID_PODCAST);
            }
            if ($int_id >= self::OLD_SUBID_PODCASTEP && $int_id < self::OLD_SUBID_PLAYLIST) {
                return new Podcast_Episode($int_id - self::OLD_SUBID_PODCASTEP);
            }
            if ($int_id >= self::OLD_SUBID_PLAYLIST && $int_id < 900000000) {
                return new Playlist($int_id - self::OLD_SUBID_PLAYLIST);
            }
        }

        // everything else is a string prefix
        $ampache_id = substr($sub_id, 3) ?: null;
        if (!$ampache_id) {
            return null;
        }

        $ampache_id = (int)$ampache_id;
        switch (substr($sub_id, 0, 3)) {
            case self::SUBID_ALBUM:
                return new Album($ampache_id);
            case self::SUBID_ARTIST:
                return new Artist($ampache_id);
            case self::SUBID_BOOKMARK:
                return new Bookmark($ampache_id);
            case self::SUBID_CATALOG:
                return Catalog::create_from_id($ampache_id);
            case self::SUBID_CHAT:
                return new PrivateMsg($ampache_id);
            case self::SUBID_GENRE:
                return new Tag($ampache_id);
            case self::SUBID_LIVESTREAM:
                return new Live_Stream($ampache_id);
            case self::SUBID_PLAYLIST:
                return new Playlist($ampache_id);
            case self::SUBID_PODCAST:
                return new Podcast($ampache_id);
            case self::SUBID_PODCASTEP:
                return new Podcast_Episode($ampache_id);
            case self::SUBID_SHARE:
                return new Share($ampache_id);
            case self::SUBID_SMARTPL:
                return new Search($ampache_id);
            case self::SUBID_SONG:
                return new Song($ampache_id);
            case self::SUBID_USER:
                return new User($ampache_id);
            case self::SUBID_VIDEO:
                return new Video($ampache_id);
        }
        debug_event(self::class, 'Couldn\'t identify Ampache object from ' . $sub_id, 5);

        return null;
    }

    /**
     * getAmpacheId
     */
    public static function getAmpacheId(string $sub_id): ?int
    {
        // keep oldstyle subsonic ids for compatibility (TODO REMOVE IN AMPACHE 8.0)
        if (is_numeric($sub_id)) {
            $int_id = (int)$sub_id;
            if ($int_id >= self::OLD_SUBID_ARTIST && $int_id < self::OLD_SUBID_ALBUM) {
                return $int_id - self::OLD_SUBID_ARTIST;
            }
            if ($int_id >= self::OLD_SUBID_ALBUM && $int_id < self::OLD_SUBID_SONG) {
                return $int_id - self::OLD_SUBID_ALBUM;
            }
            if ($int_id >= self::OLD_SUBID_SONG && $int_id < self::OLD_SUBID_SMARTPL) {
                return $int_id - self::OLD_SUBID_SONG;
            }
            if ($int_id >= self::OLD_SUBID_SMARTPL && $int_id < self::OLD_SUBID_VIDEO) {
                return $int_id - self::OLD_SUBID_SMARTPL;
            }
            if ($int_id >= self::OLD_SUBID_VIDEO && $int_id < self::OLD_SUBID_PODCAST) {
                return $int_id - self::OLD_SUBID_VIDEO;
            }
            if ($int_id >= self::OLD_SUBID_PODCAST && $int_id < self::OLD_SUBID_PODCASTEP) {
                return $int_id - self::OLD_SUBID_PODCAST;
            }
            if ($int_id >= self::OLD_SUBID_PODCASTEP && $int_id < self::OLD_SUBID_PLAYLIST) {
                return $int_id - self::OLD_SUBID_PODCASTEP;
            }
            if ($int_id >= self::OLD_SUBID_PLAYLIST && $int_id < 900000000) {
                return $int_id - self::OLD_SUBID_PLAYLIST;
            }
        }

        // everything else is a string prefix
        $ampache_id = substr($sub_id, 3) ?: null;
        if (!$ampache_id) {
            return null;
        }

        switch (substr($sub_id, 0, 3)) {
            case self::SUBID_ALBUM:
            case self::SUBID_ARTIST:
            case self::SUBID_BOOKMARK:
            case self::SUBID_CATALOG:
            case self::SUBID_CHAT:
            case self::SUBID_GENRE:
            case self::SUBID_LIVESTREAM:
            case self::SUBID_PLAYLIST:
            case self::SUBID_PODCAST:
            case self::SUBID_SHARE:
            case self::SUBID_SMARTPL:
            case self::SUBID_SONG:
            case self::SUBID_USER:
            case self::SUBID_VIDEO:
                return (int)$ampache_id;
        }

        return null;
    }

    /**
     * getAmpacheType
     */
    public static function getAmpacheType(string $sub_id): string
    {
        // keep oldstyle subsonic ids for compatibility (TODO REMOVE IN AMPACHE 8.0)
        if (is_numeric($sub_id)) {
            $int_id = (int)$sub_id;
            if ($int_id >= self::OLD_SUBID_ARTIST && $int_id < self::OLD_SUBID_ALBUM) {
                return "artist";
            }
            if ($int_id >= self::OLD_SUBID_ALBUM && $int_id < self::OLD_SUBID_SONG) {
                return "album";
            }
            if ($int_id >= self::OLD_SUBID_SONG && $int_id < self::OLD_SUBID_SMARTPL) {
                return "song";
            }
            if ($int_id >= self::OLD_SUBID_SMARTPL && $int_id < self::OLD_SUBID_VIDEO) {
                return "search";
            }
            if ($int_id >= self::OLD_SUBID_VIDEO && $int_id < self::OLD_SUBID_PODCAST) {
                return "video";
            }
            if ($int_id >= self::OLD_SUBID_PODCAST && $int_id < self::OLD_SUBID_PODCASTEP) {
                return "podcast";
            }
            if ($int_id >= self::OLD_SUBID_PODCASTEP && $int_id < self::OLD_SUBID_PLAYLIST) {
                return "podcast_episode";
            }
            if ($int_id >= self::OLD_SUBID_PLAYLIST && $int_id < 900000000) {
                return "playlist";
            }
        }

        // everything else is a string prefix
        $ampache_id = substr($sub_id, 3) ?: null;
        if (!$ampache_id) {
            return "";
        }

        switch (substr($sub_id, 0, 3)) {
            case self::SUBID_ARTIST:
                return "artist";
            case self::SUBID_ALBUM:
                return "album";
            case self::SUBID_SONG:
                return "song";
            case self::SUBID_SMARTPL:
                return "search";
            case self::SUBID_VIDEO:
                return "video";
            case self::SUBID_PODCAST:
                return "podcast";
            case self::SUBID_PODCASTEP:
                return "podcast_episode";
            case self::SUBID_PLAYLIST:
                return "playlist";
            case self::SUBID_BOOKMARK:
                return "bookmark";
            case self::SUBID_CATALOG:
                return "catalog";
            case self::SUBID_CHAT:
                return "private_message";
            case self::SUBID_GENRE:
                return "genre";
            case self::SUBID_LIVESTREAM:
                return "live_stream";
            case self::SUBID_SHARE:
                return "share";
            case self::SUBID_USER:
                return "user";
        }

        return "";
    }

    /**
     * _albumList
     * @param array<string, mixed> $input
     * @param User $user
     * @param string $type
     * @return int[]|null
     */
    private static function _albumList(array $input, User $user, string $type): ?array
    {
        $size          = (int)($input['size'] ?? 10);
        $offset        = (int)($input['offset'] ?? 0);
        $musicFolderId = (int)($input['musicFolderId'] ?? 0);
        $catalogFilter = (AmpConfig::get('catalog_disable') || AmpConfig::get('catalog_filter'));

        // Get albums from all catalogs by default Catalog filter is not supported for all request types for now.
        $catalogs = ($catalogFilter)
            ? $user->get_catalogs('music')
            : null;
        if ($musicFolderId > 0) {
            $catalogs   = [];
            $catalogs[] = $musicFolderId;
        }
        $albums = null;
        switch ($type) {
            case 'random':
                $albums = self::getAlbumRepository()->getRandom(
                    $user->id,
                    $size
                );
                break;
            case 'newest':
                $albums = Stats::get_newest('album', $size, $offset, $musicFolderId, $user);
                break;
            case 'highest':
                $albums = Rating::get_highest('album', $size, $offset, $user->id);
                break;
            case 'frequent':
                $albums = Stats::get_top('album', $size, 0, $offset);
                break;
            case 'recent':
                $albums = Stats::get_recent('album', $size, $offset);
                break;
            case 'starred':
                $albums = Userflag::get_latest('album', null, $size, $offset);
                break;
            case 'alphabeticalByName':
                $albums = ($catalogFilter && empty($catalogs) && $musicFolderId == 0)
                    ? []
                    : Catalog::get_albums($size, $offset, $catalogs);
                break;
            case 'alphabeticalByArtist':
                $albums = ($catalogFilter && empty($catalogs) && $musicFolderId == 0)
                    ? []
                    : Catalog::get_albums_by_artist($size, $offset, $catalogs);
                break;
            case 'byYear':
                $fromYear = (int)min($input['fromYear'], $input['toYear']);
                $toYear   = (int)max($input['fromYear'], $input['toYear']);

                if ($fromYear || $toYear) {
                    $data   = Search::year_search($fromYear, $toYear, $size, $offset);
                    $albums = Search::run($data, $user);
                }
                break;
            case 'byGenre':
                $genre  = $input['genre'];
                $tag_id = Tag::tag_exists($genre);
                if ($tag_id > 0) {
                    $albums = Tag::get_tag_objects('album', $tag_id, $size, $offset);
                }
                break;
        }

        return $albums;
    }

    /**
     * _setStar
     * @param array<string, mixed> $input
     * @param User $user
     * @param bool $star
     */
    private static function _setStar(array $input, User $user, bool $star): void
    {
        $sub_ids  = $input['id'] ?? null;
        $albumId  = $input['albumId'] ?? null;
        $artistId = $input['artistId'] ?? null;

        // Normalize all in one array
        $objects = [];

        if ($sub_ids) {
            if (!is_array($sub_ids)) {
                $sub_ids = [$sub_ids];
            }
            foreach ($sub_ids as $item) {
                $object_id   = self::getAmpacheId($item);
                $object_type = self::getAmpacheType($item);
                $objects[]   = [
                    'id' => $object_id,
                    'type' => $object_type
                ];
            }
        } elseif ($albumId) {
            if (!is_array($albumId)) {
                $albumId = [$albumId];
            }
            foreach ($albumId as $album) {
                $object_id = self::getAmpacheId($album);
                $objects[] = [
                    'id' => $object_id,
                    'type' => 'album'
                ];
            }
        } elseif ($artistId) {
            if (!is_array($artistId)) {
                $artistId = [$artistId];
            }
            foreach ($artistId as $artist) {
                $object_id = self::getAmpacheId($artist);
                $objects[] = [
                    'id' => $object_id,
                    'type' => 'artist'
                ];
            }
        } else {
            self::_errorOutput($input, self::SSERROR_MISSINGPARAM, __FUNCTION__);

            return;
        }

        foreach ($objects as $object) {
            $flag = new Userflag($object['id'], $object['type']);
            $flag->set_flag($star, $user->id);
        }

        self::_responseOutput($input, __FUNCTION__);
    }

    /**
     * _search
     * @param array<string, mixed> $input
     * @return array<string, int[]>
     */
    private static function _search(string $query, array $input, User $user): array
    {
        $operator = 0; // contains
        $original = unhtmlentities($query);
        $query    = $original;
        if (str_starts_with($original, '"') && (str_ends_with($original, '"'))) {
            $query = substr($original, 1, -1);
            // query is non-optional, but some clients send empty queries to fetch
            // all items. Fall back on default contains in such cases.
            if (strlen($query) > 0) {
                $operator = 4; // equals
            }
        }
        if (str_starts_with($original, '"') && str_ends_with($original, '"*')) {
            $query    = substr($original, 1, -2);
            $operator = 4; // equals
        }
        $artists = [];
        $albums  = [];
        $songs   = [];

        if (strlen($query) > 1) {
            // if we didn't catch a "wrapped" query it might just be a starts with
            if (str_ends_with($original, "*") && $operator == 0) {
                $query    = substr($query, 0, -1);
                $operator = 2; // Starts with
            }
        }

        $artistCount   = $input['artistCount'] ?? 20;
        $artistOffset  = $input['artistOffset'] ?? 0;
        $albumCount    = $input['albumCount'] ?? 20;
        $albumOffset   = $input['albumOffset'] ?? 0;
        $songCount     = $input['songCount'] ?? 20;
        $songOffset    = $input['songOffset'] ?? 0;
        $musicFolderId = $input['musicFolderId'] ?? 0;

        if ($artistCount > 0) {
            $data                    = [];
            $data['limit']           = $artistCount;
            $data['offset']          = $artistOffset;
            $data['type']            = 'artist';
            $data['rule_1_input']    = $query;
            $data['rule_1_operator'] = $operator;
            $data['rule_1']          = 'title';
            if ($musicFolderId > 0) {
                $data['rule_2_input']    = $musicFolderId;
                $data['rule_2_operator'] = 0;
                $data['rule_2']          = 'catalog';
            }
            $artists = Search::run($data, $user);
        }

        if ($albumCount > 0) {
            $data                    = [];
            $data['limit']           = $albumCount;
            $data['offset']          = $albumOffset;
            $data['type']            = 'album';
            $data['rule_1_input']    = $query;
            $data['rule_1_operator'] = $operator;
            $data['rule_1']          = 'title';
            if ($musicFolderId > 0) {
                $data['rule_2_input']    = $musicFolderId;
                $data['rule_2_operator'] = 0;
                $data['rule_2']          = 'catalog';
            }
            $albums = Search::run($data, $user);
        }

        if ($songCount > 0) {
            $data                    = [];
            $data['limit']           = $songCount;
            $data['offset']          = $songOffset;
            $data['type']            = 'song';
            $data['rule_1_input']    = $query;
            $data['rule_1_operator'] = $operator;
            $data['rule_1']          = 'title';
            if ($musicFolderId > 0) {
                $data['rule_2_input']    = $musicFolderId;
                $data['rule_2_operator'] = 0;
                $data['rule_2']          = 'catalog';
            }
            $songs = Search::run($data, $user);
        }

        return [
            'artists' => $artists,
            'albums' => $albums,
            'songs' => $songs,
        ];
    }

    /**
     * check_parameter
     * @param array<string, mixed> $input
     * @param string $parameter
     * @param string $function
     * @return false|mixed
     */
    private static function _check_parameter(array $input, string $parameter, string $function): mixed
    {
        if (!array_key_exists($parameter, $input) || $input[$parameter] === '') {
            ob_end_clean();
            self::_errorOutput($input, self::SSERROR_MISSINGPARAM, $function);

            return false;
        }

        return $input[$parameter];
    }

    public static function decryptPassword(string $password): string
    {
        // Decode hex-encoded password
        $encpwd = strpos($password, "enc:");
        if ($encpwd !== false) {
            $hex    = substr($password, 4);
            $decpwd = '';
            for ($count = 0; $count < strlen($hex); $count += 2) {
                $decpwd .= chr((int)hexdec(substr($hex, $count, 2)));
            }
            $password = $decpwd;
        }

        return $password;
    }

    /**
     * _getAmpacheIdArrays
     * @param string[] $sub_ids
     * @return list<array{
     *     object_id: int,
     *     object_type: string,
     *     track: int
     * }>
     */
    private static function _getAmpacheIdArrays(array $sub_ids): array
    {
        $ampidarrays = [];
        $track       = 1;
        foreach ($sub_ids as $sub_id) {
            $ampacheId   = self::getAmpacheId($sub_id);
            $ampacheType = self::getAmpacheType($sub_id);
            if ($ampacheId) {
                $ampidarrays[] = [
                    'object_id' => $ampacheId,
                    'object_type' => $ampacheType,
                    'track' => $track
                ];
                $track++;
            }
        }

        return $ampidarrays;
    }

    /**
     * _output_body
     */
    private static function _output_body(CurlHandle $curl, string $data): int
    {
        unset($curl);

        echo $data;
        ob_flush();

        return strlen($data);
    }

    /**
     * _output_header
     */
    private static function _output_header(CurlHandle $curl, string $header): int
    {
        $rheader = trim($header);
        $rhpart  = explode(':', $rheader);
        if (!empty($rheader) && count($rhpart) > 1) {
            if ($rhpart[0] != "Transfer-Encoding") {
                header($rheader);
            }
        } elseif (str_starts_with($header, "HTTP/")) {
            // if $header starts with HTTP/ assume it's the status line
            http_response_code(curl_getinfo($curl, CURLINFO_HTTP_CODE));
        }

        return strlen($header);
    }

    /**
     * _follow_stream
     */
    private static function _follow_stream(string $url): void
    {
        set_time_limit(0);
        ob_end_clean();
        header("Access-Control-Allow-Origin: *");
        if (function_exists('curl_version')) {
            // Here, we use curl from the Ampache server to download data from
            // the Ampache server, which can be a bit counter-intuitive.
            // We use the curl `writefunction` and `headerfunction` callbacks
            // to write the fetched data back to the open stream from the
            // client.
            $headers      = apache_request_headers();
            $reqheaders   = [];
            $reqheaders[] = "User-Agent: " . $headers['User-Agent'];
            if (isset($headers['Range'])) {
                $reqheaders[] = "Range: " . $headers['Range'];
            }
            $reqheaders[] = "X-Forwarded-For: " . Core::get_user_ip();
            // Curl support, we stream transparently to avoid redirect. Redirect can fail on few clients
            debug_event(self::class, 'Stream proxy: ' . $url, 5);
            $curl = curl_init($url);
            if ($curl) {
                curl_setopt_array(
                    $curl,
                    [
                        CURLOPT_FAILONERROR => true,
                        CURLOPT_HTTPHEADER => $reqheaders,
                        CURLOPT_HEADER => false,
                        CURLOPT_RETURNTRANSFER => false,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_WRITEFUNCTION => [
                            'Ampache\Module\Api\Subsonic_Api',
                            '_output_body'
                        ],
                        CURLOPT_HEADERFUNCTION => [
                            'Ampache\Module\Api\Subsonic_Api',
                            '_output_header'
                        ],
                        // Ignore invalid certificate
                        // Default trusted chain is crap anyway and currently no custom CA option
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_TIMEOUT => 0
                    ]
                );
                if (curl_exec($curl) === false) {
                    debug_event(self::class, 'Stream error: ' . curl_error($curl), 1);
                }
                curl_close($curl);
            }
        } else {
            // Stream media using http redirect if no curl support
            // Bug fix for android clients looking for /rest/ in destination url
            // Warning: external catalogs will not work!
            $url = str_replace('/play/', '/rest/fake/', $url);
            header("Location: " . $url);
        }
    }

    /**
     * _updatePlaylist
     * @param int $playlist_id
     * @param string $name
     * @param int[]|string[] $songsIdToAdd
     * @param int[]|string[] $songIndexToRemove
     * @param bool $public
     * @param bool $clearFirst
     */
    private static function _updatePlaylist(
        int $playlist_id,
        string $name,
        array $songsIdToAdd = [],
        array $songIndexToRemove = [],
        bool $public = true,
        bool $clearFirst = false
    ): void {
        $playlist           = new Playlist((int)$playlist_id);
        $songsIdToAdd_count = count($songsIdToAdd);
        $newdata            = [];
        $newdata['name']    = (!empty($name)) ? $name : $playlist->name;
        $newdata['pl_type'] = ($public) ? "public" : "private";
        $playlist->update($newdata);
        if ($clearFirst) {
            $playlist->delete_all();
        }

        if ($songsIdToAdd_count > 0) {
            for ($count = 0; $count < $songsIdToAdd_count; ++$count) {
                $ampacheId = self::getAmpacheId((string)$songsIdToAdd[$count]);
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
                $playlist->delete_track_number(((int)$track + 1));
            }
            $playlist->set_items();
            $playlist->regenerate_track_numbers(); // reorder now that the tracks are removed
        }
    }

    /**
     * _xmlOutput
     * @param SimpleXMLElement $xml
     */
    private static function _xmlOutput(SimpleXMLElement $xml): void
    {
        $output = false;
        $xmlstr = $xml->asXML();
        if (is_string($xmlstr)) {
            // clean illegal XML characters.
            $clean_xml = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '_', $xmlstr);
            if (is_string($clean_xml)) {
                $dom = new DOMDocument();
                $dom->loadXML($clean_xml, LIBXML_PARSEHUGE);
                $dom->formatOutput = true;
                $output            = $dom->saveXML();
            }
        }

        // saving xml can fail
        if (!$output) {
            $output = "<subsonic-response status=\"failed\" " . "version=\"1.16.1\" " . "type=\"ampache\" " . "serverVersion=\"" . Api::$version . "\" " . "openSubsonic=\"1\" " . ">" .
                "<error code=\"" . Subsonic_Api::SSERROR_GENERIC . "\" message=\"Error creating response.\" helpUrl=\"https://ampache.org/api/subsonic\"/>" .
                "</subsonic-response>";
        }

        header("Content-type: text/xml; charset=" . AmpConfig::get('site_charset'));
        header("Access-Control-Allow-Origin: *");
        echo $output;
    }

    /**
     * _jsonOutput
     * @param array{'subsonic-response': array<string, mixed>} $json
     */
    private static function _jsonOutput(array $json): void
    {
        $output = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!$output) {
            $output = json_encode(Subsonic_Json_Data::addError(self::SSERROR_GENERIC, 'system'), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
        }

        header("Content-type: application/json; charset=" . AmpConfig::get('site_charset'));
        header("Access-Control-Allow-Origin: *");
        echo $output;
    }

    /**
     * _jsonpOutput
     * @param array{'subsonic-response': array<string, mixed>} $json
     * @param string $callback
     */
    private static function _jsonpOutput(array $json, string $callback): void
    {
        $output = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($output === false) {
            $output = json_encode(Subsonic_Json_Data::addError(self::SSERROR_GENERIC, 'system'), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
        }

        header("Content-type: text/javascript; charset=" . AmpConfig::get('site_charset'));
        header("Access-Control-Allow-Origin: *");
        echo $callback . '(' . $output . ')';
    }

    /**
     * _errorOutput
     * @param array<string, mixed> $input
     */
    private static function _errorOutput(array $input, int $errorCode, string $function): void
    {
        $format = (string)($input['f'] ?? 'xml');
        switch ($format) {
            case 'json':
                self::_jsonOutput(Subsonic_Json_Data::addError($errorCode, $function));
                break;
            case 'jsonp':
                $callback = (string)($input['callback'] ?? 'jsonp');
                self::_jsonpOutput(Subsonic_Json_Data::addError($errorCode, $function), $callback);
                break;
            default:
                self::_xmlOutput(Subsonic_Xml_Data::addError($errorCode, $function));
                break;
        }
    }

    /**
     * _addJsonResponse
     *
     * Generate a subsonic-response
     * @return array{'subsonic-response': array{'status': string, 'version': string, 'type': string, 'serverVersion': string, 'openSubsonic': bool}}
     */
    private static function _addJsonResponse(string $function): array
    {
        return Subsonic_Json_Data::addResponse($function);
    }

    /**
     * _addXmlResponse
     *
     * Generate a subsonic-response
     */
    private static function _addXmlResponse(string $function): SimpleXMLElement
    {
        return Subsonic_Xml_Data::addResponse($function);
    }

    /**
     * _responseOutput
     *
     * Output a response or a default success response if no response is provided.
     * @param array<string, mixed> $input
     * @param array{'subsonic-response': array<string, mixed>}|SimpleXMLElement|null $response
     */
    private static function _responseOutput(array $input, string $function, array|SimpleXMLElement|null $response = null): void
    {
        $format = (string)($input['f'] ?? 'xml');
        switch ($format) {
            case 'json':
                $response = (is_array($response))
                    ? $response
                    : self::_addJsonResponse($function);
                self::_jsonOutput($response);
                break;
            case 'jsonp':
                $response = (is_array($response))
                    ? $response
                    : self::_addJsonResponse($function);
                $callback = (string)($input['callback'] ?? 'jsonp');
                self::_jsonpOutput($response, $callback);
                break;
            default:
                $response = ($response instanceof SimpleXMLElement)
                    ? $response
                    : self::_addXmlResponse($function);
                self::_xmlOutput($response);
                break;
        }
    }

    /**
     * error
     * @param array<string, mixed> $input
     */
    public static function error(array $input, int $errorCode, string $function): void
    {
        self::_errorOutput($input, $errorCode, $function);
    }

    /**
     * addChatMessage
     *
     * Adds a message to the chat log.
     * https://www.subsonic.org/pages/api.jsp#addchatmessage
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function addchatmessage(array $input, User $user): void
    {
        $message = self::_check_parameter($input, 'message', __FUNCTION__);
        if (!$message) {
            return;
        }

        if (!AmpConfig::get('sociable')) {
            self::_errorOutput($input, self::SSERROR_GENERIC, __FUNCTION__);

            return;
        }

        self::getPrivateMessageRepository()->create(null, $user, '', trim($message));

        self::_responseOutput($input, __FUNCTION__);
    }

    /**
     * changePassword
     *
     * Changes the password of an existing user on the server.
     * https://www.subsonic.org/pages/api.jsp#changepassword
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function changepassword(array $input, User $user): void
    {
        $username = self::_check_parameter($input, 'username', __FUNCTION__);
        if (!$username) {
            return;
        }

        $inp_pass = self::_check_parameter($input, 'password', __FUNCTION__);
        if (!$inp_pass) {
            return;
        }

        $password = self::decryptPassword($inp_pass);
        if ($user->username == $username || $user->access === 100) {
            $update_user = User::get_from_username((string) $username);
            if ($update_user instanceof User && !AmpConfig::get('simple_user_mode')) {
                $update_user->update_password($password);
                self::_responseOutput($input, __FUNCTION__);
            } else {
                self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * createBookmark
     *
     * Creates or updates a bookmark.
     * https://www.subsonic.org/pages/api.jsp#createbookmark
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function createbookmark(array $input, User $user): void
    {
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        $position = self::_check_parameter($input, 'position', __FUNCTION__);
        if (!$position) {
            return;
        }

        $comment   = $input['comment'] ?? '';
        $object_id = self::getAmpacheId($sub_id);
        $type      = self::getAmpacheType($sub_id);

        if (!empty($object_id) && !empty($type)) {
            $bookmark = new Bookmark($object_id, $type);
            if ($bookmark->isNew()) {
                Bookmark::create(
                    [
                        'object_id' => $object_id,
                        'object_type' => $type,
                        'comment' => $comment,
                        'position' => $position
                    ],
                    $user->id,
                    time()
                );
            } else {
                self::getBookmarkRepository()->update($bookmark->getId(), (int)$position, new DateTime());
            }
            self::_responseOutput($input, __FUNCTION__);
        } else {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
        }
    }

    /**
     * createInternetRadioStation
     *
     * Adds a new internet radio station.
     * https://www.subsonic.org/pages/api.jsp#createinternetradiostation
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function createinternetradiostation(array $input, User $user): void
    {
        $url = self::_check_parameter($input, 'streamUrl', __FUNCTION__);
        if (!$url) {
            return;
        }

        $name = self::_check_parameter($input, 'name', __FUNCTION__);
        if (!$name) {
            return;
        }

        $site_url = filter_var(urldecode($input['homepageUrl']), FILTER_VALIDATE_URL) ?: '';
        $catalogs = User::get_user_catalogs($user->id, 'music');
        if (AmpConfig::get('live_stream') && $user->access >= 75) {
            $data = [
                "name" => $name,
                "url" => $url,
                "codec" => 'mp3',
                "catalog" => $catalogs[0],
                "site_url" => $site_url
            ];
            if (!Live_Stream::create($data)) {
                self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

                return;
            }
            self::_responseOutput($input, __FUNCTION__);
        } else {
            self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * createPlaylist
     *
     * Creates (or updates) a playlist.
     * https://www.subsonic.org/pages/api.jsp#createplaylist
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function createplaylist(array $input, User $user): void
    {
        $playlistId = self::getAmpacheId($input['playlistId'] ?? '');
        $name       = $input['name'] ?? '';
        $songIdList = $input['songId'] ?? [];
        if (isset($input['songId']) && is_string($input['songId'])) {
            $songIdList = explode(',', $input['songId']);
        }

        if ($playlistId !== null) {
            self::_updatePlaylist($playlistId, $name, $songIdList, [], true, true);
            self::_responseOutput($input, __FUNCTION__);
        } elseif (!empty($name)) {
            $playlistId = Playlist::create($name, 'public', $user->id);
            if ($playlistId !== null) {
                if (count($songIdList) > 0) {
                    self::_updatePlaylist($playlistId, "", $songIdList, [], true, true);
                }

                // output the new playlist
                $format   = (string)($input['f'] ?? 'xml');
                $playlist = new Playlist($playlistId);
                if ($format === 'xml') {
                    $response = self::_addXmlResponse(__FUNCTION__);
                    $response = Subsonic_Xml_Data::addPlaylist($response, $playlist, true);
                } else {
                    $response = self::_addJsonResponse(__FUNCTION__);
                    $response = Subsonic_Json_Data::addPlaylist($response, $playlist, true);
                }
                self::_responseOutput($input, __FUNCTION__, $response);
            } else {
                self::_errorOutput($input, self::SSERROR_GENERIC, __FUNCTION__);
            }
        } else {
            self::_errorOutput($input, self::SSERROR_MISSINGPARAM, __FUNCTION__);
        }
    }

    /**
     * createPodcastChannel
     *
     * Adds a new Podcast channel.
     * https://www.subsonic.org/pages/api.jsp#createpodcastchannel
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function createpodcastchannel(array $input, User $user): void
    {
        $url = self::_check_parameter($input, 'url', __FUNCTION__);
        if (!$url) {
            return;
        }

        if (AmpConfig::get('podcast') && $user->access >= 75) {
            $catalogs = $user->get_catalogs('podcast');
            if (count($catalogs) > 0) {
                /** @var Catalog $catalog */
                $catalog = Catalog::create_from_id($catalogs[0]);

                try {
                    self::getPodcastCreator()->create($url, $catalog);

                    self::_responseOutput($input, __FUNCTION__);
                } catch (PodcastCreationException) {
                    self::_errorOutput($input, self::SSERROR_GENERIC, __FUNCTION__);
                }
            } else {
                self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
            }
        } else {
            self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * createShare
     *
     * Creates a public URL that can be used by anyone to stream music or video from the server.
     * https://www.subsonic.org/pages/api.jsp#createshare
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function createshare(array $input, User $user): void
    {
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        $object = self::getAmpacheObject((string)$sub_id);
        if (!$object instanceof library_item) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $description = $input['description'] ?? null;
        if (AmpConfig::get('share')) {
            $share_expire = AmpConfig::get('share_expire', 7);
            $expire_days  = (isset($input['expires']))
                ? Share::get_expiry(((int)filter_var($input['expires'], FILTER_SANITIZE_NUMBER_INT)) / 1000)
                : $share_expire;
            $object_type = self::getAmpacheType($sub_id);
            if (is_array($sub_id) && $object_type === 'song') {
                debug_event(self::class, 'createShare: sharing song list (album)', 5);
                $song_id     = self::getAmpacheId($sub_id[0]);
                $tmp_song    = new Song($song_id);
                $sub_id      = self::getAlbumSubId($tmp_song->album);
                $object_type = 'album';
            }
            debug_event(self::class, 'createShare: sharing ' . $object_type . ' ' . $sub_id, 4);
            if (
                !in_array(
                    $object_type,
                    [
                        'album',
                        'album_disk',
                        'artist',
                        'playlist',
                        'podcast',
                        'podcast_episode',
                        'search',
                        'song',
                        'video',
                    ]
                )
            ) {
                $object_type = '';
            }

            if (!empty($object_type) && !empty($sub_id)) {
                try {
                    global $dic; // @todo remove after refactoring

                    $passwordGenerator = $dic->get(PasswordGeneratorInterface::class);
                    $shareCreator      = $dic->get(ShareCreatorInterface::class);
                } catch (ContainerExceptionInterface $error) {
                    debug_event(self::class, 'createShare: Dependency injection error: ' . $error->getMessage(), 1);
                    self::_errorOutput($input, self::SSERROR_GENERIC, __FUNCTION__);

                    return;
                }

                $shares   = [];
                $shares[] = $shareCreator->create(
                    $user,
                    LibraryItemEnum::from($object_type),
                    $object->getId(),
                    true,
                    Access::check_function(AccessFunctionEnum::FUNCTION_DOWNLOAD),
                    $expire_days,
                    $passwordGenerator->generate_token(),
                    0,
                    $description
                );

                $format = (string)($input['f'] ?? 'xml');
                if ($format === 'xml') {
                    $response = self::_addXmlResponse(__FUNCTION__);
                    $response = Subsonic_Xml_Data::addShares($response, $shares);
                } else {
                    $response = self::_addJsonResponse(__FUNCTION__);
                    $response = Subsonic_Json_Data::addShares($response, $shares);
                }
                self::_responseOutput($input, __FUNCTION__, $response);
            } else {
                self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * createUser
     *
     * Creates a new user on the server.
     * https://www.subsonic.org/pages/api.jsp#createuser
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function createuser(array $input, User $user): void
    {
        $username = self::_check_parameter($input, 'username', __FUNCTION__);
        if (!$username) {
            return;
        }

        $password = self::_check_parameter($input, 'password', __FUNCTION__);
        if (!$password) {
            return;
        }

        $email = self::_check_parameter($input, 'email', __FUNCTION__);
        if (!$email) {
            return;
        }

        $email        = urldecode($email);
        $adminRole    = (array_key_exists('adminRole', $input) && $input['adminRole'] == 'true');
        $downloadRole = (array_key_exists('downloadRole', $input) && $input['downloadRole'] == 'true');
        $uploadRole   = (array_key_exists('uploadRole', $input) && $input['uploadRole'] == 'true');
        $coverArtRole = (array_key_exists('coverArtRole', $input) && $input['coverArtRole'] == 'true');
        $shareRole    = (array_key_exists('shareRole', $input) && $input['shareRole'] == 'true');

        if ($user->access >= AccessLevelEnum::ADMIN->value) {
            $access = AccessLevelEnum::USER;
            if ($coverArtRole) {
                $access = AccessLevelEnum::MANAGER;
            }
            if ($adminRole) {
                $access = AccessLevelEnum::ADMIN;
            }
            $password = self::decryptPassword($password);
            $user_id  = User::create($username, $username, $email, '', $password, $access);
            if ($user_id > 0) {
                if ($downloadRole) {
                    Preference::update('download', $user_id, 1);
                }
                if ($uploadRole) {
                    Preference::update('allow_upload', $user_id, 1);
                }
                if ($shareRole) {
                    Preference::update('share', $user_id, 1);
                }
                self::_responseOutput($input, __FUNCTION__);
            } else {
                self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * deleteBookmark
     *
     * Creates or updates a bookmark.
     * https://www.subsonic.org/pages/api.jsp#deletebookmark
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function deletebookmark(array $input, User $user): void
    {
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        $object_id = self::getAmpacheId($sub_id);
        $type      = self::getAmpacheType($sub_id);

        $bookmark = new Bookmark($object_id, $type, $user->id);
        if ($bookmark->isNew()) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
        } else {
            self::getBookmarkRepository()->delete($bookmark->getId());

            self::_responseOutput($input, __FUNCTION__);
        }
    }

    /**
     * deleteInternetRadioStation
     *
     * Deletes an existing internet radio station.
     * https://www.subsonic.org/pages/api.jsp#deleteinternetradiostation
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function deleteinternetradiostation(array $input, User $user): void
    {
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        $liveStreamRepository = self::getLiveStreamRepository();

        if (AmpConfig::get('live_stream') && $user->access >= AccessLevelEnum::MANAGER->value) {
            $radio_id   = self::getAmpacheId($sub_id);
            $liveStream = ($radio_id)
                ? $liveStreamRepository->findById($radio_id)
                : null;

            if ($liveStream === null) {
                self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            } else {
                $liveStreamRepository->delete($liveStream);

                self::_responseOutput($input, __FUNCTION__);
            }
        } else {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
        }
    }

    /**
     * deletePlaylist
     *
     * Deletes a saved playlist.
     * https://www.subsonic.org/pages/api.jsp#deleteplaylist
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function deleteplaylist(array $input, User $user): void
    {
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        $playlist = self::getAmpacheObject($sub_id);
        if (
            (!($playlist instanceof Playlist || $playlist instanceof Search)) ||
            $playlist->isNew()
        ) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        if (!$playlist->has_access($user)) {
            self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);

            return;
        }

        $playlist->delete();

        self::_responseOutput($input, __FUNCTION__);
    }

    /**
     * deletePodcastChannel
     *
     * Deletes a Podcast channel.
     * https://www.subsonic.org/pages/api.jsp#deletepodcastchannel
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function deletepodcastchannel(array $input, User $user): void
    {
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        if (AmpConfig::get(ConfigurationKeyEnum::PODCAST) && $user->access >= AccessLevelEnum::MANAGER->value) {
            $podcast_id = self::getAmpacheId($sub_id);
            $podcast    = ($podcast_id)
                ? self::getPodcastRepository()->findById($podcast_id)
                : null;
            if ($podcast === null) {
                self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            } else {
                self::getPodcastDeleter()->delete($podcast);

                self::_responseOutput($input, __FUNCTION__);
            }
        } else {
            self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * deletePodcastEpisode
     *
     * Deletes a Podcast episode.
     * https://www.subsonic.org/pages/api.jsp#deletepodcastepisode
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function deletepodcastepisode(array $input, User $user): void
    {
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        if (AmpConfig::get('podcast') && $user->access >= 75) {
            $episode = new Podcast_Episode(self::getAmpacheId($sub_id));
            if ($episode->isNew()) {
                self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            } elseif ($episode->remove()) {
                Catalog::count_table('podcast_episode');

                self::_responseOutput($input, __FUNCTION__);
            } else {
                self::_errorOutput($input, self::SSERROR_GENERIC, __FUNCTION__);
            }
        } else {
            self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * deleteShare
     *
     * Deletes an existing share.
     * https://www.subsonic.org/pages/api.jsp#deleteshare
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function deleteshare(array $input, User $user): void
    {
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        if (AmpConfig::get('share')) {
            $shareRepository = self::getShareRepository();

            $share_id = self::getAmpacheId($sub_id);
            $share    = ($share_id)
                ? $shareRepository->findById($share_id)
                : null;

            if (
                $share === null ||
                !$share->isAccessible($user)
            ) {
                self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            } else {
                $shareRepository->delete($share);

                self::_responseOutput($input, __FUNCTION__);
            }
        } else {
            self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * deleteUser
     *
     * Deletes an existing user on the server.
     * https://www.subsonic.org/pages/api.jsp#deleteuser
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function deleteuser(array $input, User $user): void
    {
        $username = self::_check_parameter($input, 'username', __FUNCTION__);
        if (!$username) {
            return;
        }

        if ($user->access === 100) {
            $update_user = User::get_from_username((string)$username);
            if ($update_user instanceof User) {
                $update_user->delete();

                self::_responseOutput($input, __FUNCTION__);
            } else {
                self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * download
     *
     * Downloads a given media file.
     * https://www.subsonic.org/pages/api.jsp#download
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function download(array $input, User $user): void
    {
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        $object = self::getAmpacheObject($sub_id);
        if (($object instanceof Song || $object instanceof Podcast_Episode) === false) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $client = scrub_in((string) ($input['c'] ?? 'Subsonic'));
        $params = '&client=' . rawurlencode($client) . '&cache=1';

        self::_follow_stream($object->play_url($params, 'api', function_exists('curl_version'), $user->id, $user->streamtoken));
    }

    /**
     * downloadPodcastEpisode
     *
     * Request the server to start downloading a given Podcast episode.
     * https://www.subsonic.org/pages/api.jsp#downloadpodcastepisode
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function downloadpodcastepisode(array $input, User $user): void
    {
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        if (AmpConfig::get('podcast') && $user->access >= 75) {
            $episode = new Podcast_Episode(self::getAmpacheId($sub_id));
            if ($episode->isNew()) {
                self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            } else {
                self::getPodcastSyncer()->syncEpisode($episode);

                self::_responseOutput($input, __FUNCTION__);
            }
        } else {
            self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * getAlbum
     *
     * Returns details for an album.
     * https://www.subsonic.org/pages/api.jsp#getalbum
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getalbum(array $input, User $user): void
    {
        unset($user);
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        $album = self::getAmpacheObject($sub_id);
        if (!$album instanceof Album || $album->isNew()) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addAlbumID3($response, $album, true);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addAlbumID3($response, $album, true);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getAlbumInfo
     *
     * Returns album info.
     * https://www.subsonic.org/pages/api.jsp#getalbuminfo
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getalbuminfo(array $input, User $user): void
    {
        unset($user);
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        $album = self::getAmpacheObject($sub_id);
        if (!$album instanceof Album || $album->isNew()) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $info   = Recommendation::get_album_info($album->getId());
        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addAlbumInfo($response, $info, $album);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addAlbumInfo($response, $info, $album);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getAlbumInfo2
     *
     * Returns album info.
     * https://www.subsonic.org/pages/api.jsp#getalbuminfo2
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getalbuminfo2(array $input, User $user): void
    {
        self::getalbuminfo($input, $user);
    }

    /**
     * getAlbumList
     *
     * Returns a list of random, newest, highest rated etc. albums.
     * https://www.subsonic.org/pages/api.jsp#getalbumlist
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getalbumlist(array $input, User $user): void
    {
        $type = self::_check_parameter($input, 'type', __FUNCTION__);
        if (!$type) {
            return;
        }

        if ($type === 'byGenre' && !self::_check_parameter($input, 'genre', __FUNCTION__)) {
            return;
        }

        $albums = self::_albumList($input, $user, (string)$type);
        if ($albums === null) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addAlbumList($response, $albums);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addAlbumList($response, $albums);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getAlbumList2
     *
     * Returns a list of random, newest, highest rated etc. albums.
     * https://www.subsonic.org/pages/api.jsp#getalbumlist2
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getalbumlist2(array $input, User $user): void
    {
        $type = self::_check_parameter($input, 'type', __FUNCTION__);
        if (!$type) {
            return;
        }

        if ($type === 'byGenre' && !self::_check_parameter($input, 'genre', __FUNCTION__)) {
            return;
        }

        $albums = self::_albumList($input, $user, (string)$type);
        if ($albums === null) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addAlbumList2($response, $albums);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addAlbumList2($response, $albums);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getArtist
     *
     * Returns details for an artist.
     * https://www.subsonic.org/pages/api.jsp#getartist
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getartist(array $input, User $user): void
    {
        unset($user);
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        $artist = new Artist(self::getAmpacheId($sub_id));
        if ($artist->isNew()) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addArtist($response, $artist, true);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addArtistWithAlbumsID3($response, $artist);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getArtistInfo
     *
     * Returns artist info.
     * https://www.subsonic.org/pages/api.jsp#getartistinfo
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getartistinfo(array $input, User $user): void
    {
        unset($user);
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        $artist = self::getAmpacheObject($sub_id);
        if (!$artist instanceof Artist || $artist->isNew()) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $count             = $input['count'] ?? 20;
        $includeNotPresent = make_bool($input['includeNotPresent'] ?? false);

        $info     = Recommendation::get_artist_info($artist->getId());
        $similars = Recommendation::get_artists_like($artist->getId(), $count, !$includeNotPresent);
        $format   = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addArtistInfo($response, $info, $artist, $similars);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addArtistInfo($response, $info, $artist, $similars);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getArtistInfo2
     *
     * Returns artist info.
     * https://www.subsonic.org/pages/api.jsp#getartistinfo2
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getartistinfo2(array $input, User $user): void
    {
        unset($user);
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        $artist = self::getAmpacheObject($sub_id);
        if (!$artist instanceof Artist || $artist->isNew()) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $count             = $input['count'] ?? 20;
        $includeNotPresent = make_bool($input['includeNotPresent'] ?? false);

        $info     = Recommendation::get_artist_info($artist->getId());
        $similars = Recommendation::get_artists_like($artist->getId(), $count, !$includeNotPresent);
        $format   = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addArtistInfo2($response, $info, $artist, $similars);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addArtistInfo2($response, $info, $artist, $similars);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getArtists
     *
     * Returns all artists.
     * https://www.subsonic.org/pages/api.jsp#getartists
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getartists(array $input, User $user): void
    {
        unset($user);
        $musicFolderId = $input['musicFolderId'] ?? '';
        $catalogs      = [];
        if (!empty($musicFolderId) && $musicFolderId != '-1') {
            $catalogs[] = $musicFolderId;
        }

        $artists = Artist::get_id_arrays($catalogs);
        $format  = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addArtists($response, $artists);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addArtists($response, $artists);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getAvatar
     *
     * Returns the avatar (personal image) for a user.
     * https://www.subsonic.org/pages/api.jsp#getavatar
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getavatar(array $input, User $user): void
    {
        $username = self::_check_parameter($input, 'username', __FUNCTION__);
        if (!$username) {
            return;
        }

        if ($user->access === 100 || $user->username == $username) {
            if ($user->username == $username) {
                $update_user = $user;
            } else {
                $update_user = User::get_from_username((string)$username);
            }

            if ($update_user instanceof User) {
                // Get Session key
                $avatar = $update_user->get_avatar(true);
                if (!empty($avatar['url'])) {
                    $request = Requests::get($avatar['url'], [], Core::requests_options());
                    header("Content-Type: " . $request->headers['Content-Type']);
                    echo $request->body;
                }
            } else {
                self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * getBookmarks
     *
     * Returns all bookmarks for this user.
     * https://www.subsonic.org/pages/api.jsp#getbookmarks
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getbookmarks(array $input, User $user): void
    {
        $bookmarks = [];

        $bookmarkRepository = self::getBookmarkRepository();
        foreach ($bookmarkRepository->getByUser($user) as $bookmarkId) {
            $bookmark = $bookmarkRepository->findById($bookmarkId);

            if ($bookmark !== null) {
                $bookmarks[] = $bookmark;
            }
        }

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addBookmarks($response, $bookmarks);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addBookmarks($response, $bookmarks);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getCaptions
     *
     * Returns captions (subtitles) for a video.
     * https://www.subsonic.org/pages/api.jsp#getcaptions
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getcaptions(array $input, User $user): void
    {
        unset($user);

        self::_errorOutput($input, self::SSERROR_GENERIC, __FUNCTION__);
    }

    /**
     * getChatMessages
     *
     * Returns the current visible (non-expired) chat messages.
     * https://www.subsonic.org/pages/api.jsp#getchatmessages
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getchatmessages(array $input, User $user): void
    {
        unset($user);
        $since                    = (int)($input['since'] ?? 0);
        $privateMessageRepository = self::getPrivateMessageRepository();

        $privateMessageRepository->cleanChatMessages();

        if (!AmpConfig::get('sociable')) {
            $messages = [];
        } else {
            $messages = $privateMessageRepository->getChatMessages($since);
        }

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addChatMessages($response, $messages);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addChatMessages($response, $messages);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getCoverArt
     *
     * Returns a cover art image.
     * https://www.subsonic.org/pages/api.jsp#getcoverart
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getcoverart(array $input, User $user): void
    {
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        // replace additional prefixes
        $sub_id = preg_replace('/^[a-z]+-([a-z]{2}-)/', '$1', $sub_id);

        $object_id   = self::getAmpacheId($sub_id);
        $object_type = self::getAmpacheType($sub_id);
        if (
            !$object_id ||
            empty($object_type)
        ) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $art = null;
        if (($object_type == 'song')) {
            if (AmpConfig::get('show_song_art', false) && Art::has_db($object_id, 'song')) {
                $art = new Art($object_id, 'song');
            } else {
                // in most cases the song doesn't have a picture, but the album does
                $song = new Song($object_id);
                $art  = new Art($song->album, 'album');
            }
        } elseif ($object_type == 'artist' || $object_type == 'album' || $object_type == 'podcast' || $object_type == 'playlist') {
            $art = new Art($object_id, $object_type);
        } elseif ($object_type == 'search') {
            $playlist  = new Search($object_id, 'song', $user);
            $listitems = $playlist->get_items();
            $item      = (!empty($listitems)) ? $listitems[array_rand($listitems)] : [];
            $art       = (!empty($item)) ? new Art($item['object_id'], $item['object_type']->value) : null;
            if ($art != null && $art->id == null) {
                $song = new Song($item['object_id']);
                $art  = new Art($song->album, 'album');
            }
        }

        if (!$art || !$art->has_db_info('original', true)) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $size = (isset($input['size']) && is_numeric($input['size'])) ? (int)$input['size'] : 'original';

        // we have the art so lets show it
        header("Access-Control-Allow-Origin: *");
        if (is_int($size) && AmpConfig::get('resize_images')) {
            $out_size           = [];
            $out_size['width']  = $size;
            $out_size['height'] = $size;
            $thumb              = $art->get_thumb($out_size);
            if (!empty($thumb)) {
                header('Content-type: ' . $thumb['thumb_mime']);
                header('Content-Length: ' . strlen((string) $thumb['thumb']));
                echo $thumb['thumb'];

                return;
            }
        }
        $image = $art->get('original', true);
        header('Content-type: ' . $art->raw_mime);
        header('Content-Length: ' . strlen($image));
        echo $image;
    }

    /**
     * getGenres
     *
     * Returns all genres.
     * https://www.subsonic.org/pages/api.jsp#getgenres
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getgenres(array $input, User $user): void
    {
        unset($user);

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addGenres($response, Tag::get_tags('song'));
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addGenres($response, Tag::get_tags('song'));
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getIndexes
     *
     * Returns an indexed structure of all artists.
     * https://www.subsonic.org/pages/api.jsp#getindexes
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getindexes(array $input, User $user): void
    {
        set_time_limit(300);

        $musicFolderId   = $input['musicFolderId'] ?? '-1';
        $ifModifiedSince = $input['ifModifiedSince'] ?? '';

        $catalogs = [];
        if (!empty($musicFolderId) && $musicFolderId != '-1') {
            $catalogs[] = (int)$musicFolderId;
        } else {
            $catalogs = $user->get_catalogs('music');
        }

        $lastmodified = 0;
        $fcatalogs    = [];

        foreach ($catalogs as $catalogid) {
            $clastmodified = 0;
            $catalog       = Catalog::create_from_id($catalogid);
            if ($catalog === null) {
                break;
            }
            if ($catalog->last_update > $clastmodified) {
                $clastmodified = $catalog->last_update;
            }
            if ($catalog->last_add > $clastmodified) {
                $clastmodified = $catalog->last_add;
            }
            if ($catalog->last_clean > $clastmodified) {
                $clastmodified = $catalog->last_clean;
            }

            if ($clastmodified > $lastmodified) {
                $lastmodified = $clastmodified;
            }
            if (!empty($ifModifiedSince) && $clastmodified > (((int)$ifModifiedSince) / 1000)) {
                $fcatalogs[] = (int)$catalogid;
            }
        }
        if (empty($ifModifiedSince)) {
            $fcatalogs = $catalogs;
        }

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            if (count($fcatalogs) > 0) {
                $artists  = Catalog::get_artist_arrays($fcatalogs);
                $response = Subsonic_Xml_Data::addIndexes($response, $artists, $lastmodified);
            }
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            if (count($fcatalogs) > 0) {
                $artists  = Catalog::get_artist_arrays($fcatalogs);
                $response = Subsonic_Json_Data::addIndexes($response, $artists, $lastmodified);
            }
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getInternetRadioStations
     *
     * Returns all internet radio stations.
     * https://www.subsonic.org/pages/api.jsp#getinternetradiostations
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getinternetradiostations(array $input, User $user): void
    {
        $radios = self::getLiveStreamRepository()->findAll($user);
        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addInternetRadioStations($response, $radios);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addInternetRadioStations($response, $radios);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getLicense
     *
     * Get details about the software license.
     * https://www.subsonic.org/pages/api.jsp#getlicense
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getlicense(array $input, User $user): void
    {
        unset($user);

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addLicense($response);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addLicense($response);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getLyrics
     *
     * Searches for and returns lyrics for a given song.
     * https://www.subsonic.org/pages/api.jsp#getlyrics
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getlyrics(array $input, User $user): void
    {
        $artist = (string)($input['artist'] ?? '');
        $title  = (string)($input['title'] ?? '');

        if (empty($artist) && empty($title)) {
            self::_errorOutput($input, self::SSERROR_MISSINGPARAM, __FUNCTION__);

            return;
        }

        $data           = [];
        $data['limit']  = 1;
        $data['offset'] = 0;
        $data['type']   = "song";

        if ($artist) {
            $data['rule_0_input']    = $artist;
            $data['rule_0_operator'] = 4;
            $data['rule_0']          = "artist";
        }
        if ($title) {
            $data['rule_1_input']    = $title;
            $data['rule_1_operator'] = 4;
            $data['rule_1']          = "title";
        }

        $songs = Search::run($data, $user);
        if (count($songs) > 0) {
            $song = new Song($songs[0]);
        } else {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addLyrics($response, $artist, $title, $song);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addLyrics($response, $artist, $title, $song);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getLyricsBySongId [OS]
     *
     * Add support for synchronized lyrics, multiple languages, and retrieval by song ID
     * https://opensubsonic.netlify.app/docs/endpoints/getlyricsbysongid/
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getlyricsbysongid(array $input, User $user): void
    {
        unset($user);
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }
        $song = self::getAmpacheObject($sub_id);
        if (!$song instanceof Song || $song->isNew()) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addLyricsList($response, $song);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addLyricsList($response, $song);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getMusicDirectory
     *
     * Returns a listing of all files in a music directory.
     * https://www.subsonic.org/pages/api.jsp#getmusicdirectory
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getmusicdirectory(array $input, User $user): void
    {
        unset($user);
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        $object_id = self::getAmpacheId($sub_id);
        if (!$object_id) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $object = self::getAmpacheObject($sub_id);
        if (!$object) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        if ($object instanceof Album || $object instanceof Artist || $object instanceof Catalog) {
            $format = (string)($input['f'] ?? 'xml');
            if ($format === 'xml') {
                $response = self::_addXmlResponse(__FUNCTION__);
                $response = Subsonic_Xml_Data::addDirectory($response, $object);
            } else {
                $response = self::_addJsonResponse(__FUNCTION__);
                $response = Subsonic_Json_Data::addDirectory($response, $object);
            }
            self::_responseOutput($input, __FUNCTION__, $response);
        } else {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
        }
    }

    /**
     * getMusicFolders
     *
     * Returns all configured top-level music folders.
     * https://www.subsonic.org/pages/api.jsp#getmusicfolders
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getmusicfolders(array $input, User $user): void
    {
        $catalogs = $user->get_catalogs('music');
        $format   = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addMusicFolders($response, $catalogs);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addMusicFolders($response, $catalogs);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getNewestPodcasts
     *
     * Returns the most recently published Podcast episodes.
     * https://www.subsonic.org/pages/api.jsp#getnewestpodcasts
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getnewestpodcasts(array $input, User $user): void
    {
        unset($user);
        $count = $input['count'] ?? AmpConfig::get('podcast_new_download');
        if (!AmpConfig::get('podcast')) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $episodes = Catalog::get_newest_podcasts($count);
        $format   = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addNewestPodcasts($response, $episodes);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addNewestPodcasts($response, $episodes);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getNowPlaying
     *
     * Returns what is currently being played by all users.
     * https://www.subsonic.org/pages/api.jsp#getnowplaying
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getnowplaying(array $input, User $user): void
    {
        unset($user);
        $data   = Stream::get_now_playing();
        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addNowPlaying($response, $data);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addNowPlaying($response, $data);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getOpenSubsonicExtensions [OS]
     *
     * List the Subsonic extensions supported by this server.
     * https://opensubsonic.netlify.app/docs/endpoints/getopensubsonicextensions/
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getOpenSubsonicExtensions(array $input, User $user): void
    {
        unset($user);

        $extensions = [
            'apiKeyAuthentication' => [1],
            'getPodcastEpisode' => [1],
            'indexBasedQueue' => [1],
            'formPost' => [1],
            'songLyrics' => [1],
            'transcodeOffset' => [1],
        ];

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addSubsonicExtensions($response, $extensions);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addSubsonicExtensions($response, $extensions);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getPlaylist
     *
     * Returns a listing of files in a saved playlist.
     * https://www.subsonic.org/pages/api.jsp#getplaylist
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getplaylist(array $input, User $user): void
    {
        unset($user);
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        $playlist = self::getAmpacheObject($sub_id);
        if (
            (!($playlist instanceof Playlist || $playlist instanceof Search)) ||
            $playlist->isNew()
        ) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addPlaylist($response, $playlist, true);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addPlaylist($response, $playlist, true);
        }

        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getPlaylists
     *
     * Returns all playlists a user is allowed to play.
     * https://www.subsonic.org/pages/api.jsp#getplaylists
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getplaylists(array $input, User $user): void
    {
        $user = (isset($input['username']))
            ? User::get_from_username($input['username']) ?? $user
            : $user;

        $user_id = $user->id ?? 0;

        $browse = Api::getBrowse($user);
        $browse->set_type('playlist_search');
        $browse->set_sort('name', 'ASC');
        $browse->set_filter('playlist_open', $user_id);

        // hide duplicate searches that match name and user (if enabled)
        if ((bool)Preference::get_by_user($user_id, 'api_hide_dupe_searches') === true) {
            $browse->set_filter('hide_dupe_smartlist', 1);
        }
        // hide playlists starting with the user string (if enabled)
        $hide_string = str_replace('%', '\%', str_replace('_', '\_', (string)Preference::get_by_user($user_id, 'api_hidden_playlists')));
        if (!empty($hide_string)) {
            $browse->set_filter('not_starts_with', $hide_string);
        }

        $results = $browse->get_objects();
        $format  = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addPlaylists($response, $user, $results);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addPlaylists($response, $user, $results);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getPlayQueue
     *
     * Returns the state of the play queue for this user.
     * https://www.subsonic.org/pages/api.jsp#getplayqueue
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getplayqueue(array $input, User $user): void
    {
        $client    = scrub_in((string) ($input['c'] ?? 'Subsonic'));
        $playQueue = new User_Playlist($user->id, $client);

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addPlayQueue($response, $playQueue, (string)$user->username);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addPlayQueue($response, $playQueue, (string)$user->username);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getPlayQueueByIndex [OS]
     *
     * Returns the state of the play queue for this user.
     * https://opensubsonic.netlify.app/docs/endpoints/getplayqueuebyindex/
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getplayqueuebyindex(array $input, User $user): void
    {
        $client    = scrub_in((string) ($input['c'] ?? 'Subsonic'));
        $playQueue = new User_Playlist($user->id, $client);

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addPlayQueueByIndex($response, $playQueue, (string)$user->username);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addPlayQueueByIndex($response, $playQueue, (string)$user->username);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getPodcastEpisode
     *
     * Returns details for a podcast episode.
     * https://www.subsonic.org/pages/api.jsp#getpodcastepisode
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getpodcastepisode(array $input, User $user): void
    {
        unset($user);
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        $episode_id = self::getAmpacheId($sub_id);
        if (!$episode_id) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }
        $episode = new Podcast_Episode($episode_id);
        if ($episode->isNew()) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addPodcastEpisode($response, $episode);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addPodcastEpisode($response, $episode);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getPodcasts
     *
     * Returns all Podcast channels the server subscribes to, and (optionally) their episodes.
     * https://www.subsonic.org/pages/api.jsp#getpodcasts
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getpodcasts(array $input, User $user): void
    {
        $sub_id          = $input['id'] ?? null;
        $includeEpisodes = make_bool($input['includeEpisodes'] ?? false);

        if (!AmpConfig::get(ConfigurationKeyEnum::PODCAST)) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
        }

        $podcast_id = ($sub_id)
            ? self::getAmpacheId($sub_id)
            : null;
        if ($podcast_id) {
            $podcast = self::getPodcastRepository()->findById($podcast_id);
            if ($podcast === null) {
                self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

                return;
            }

            $podcasts = [$podcast];
        } else {
            $podcasts = Catalog::get_podcasts(User::get_user_catalogs($user->id));
        }

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addPodcasts($response, $podcasts, $includeEpisodes);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addPodcasts($response, $podcasts, $includeEpisodes);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getRandomSongs
     *
     * Returns random songs matching the given criteria.
     * https://www.subsonic.org/pages/api.jsp#getrandomsongs
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getrandomsongs(array $input, User $user): void
    {
        $size = (int)($input['size'] ?? 10);

        $genre         = $input['genre'] ?? '';
        $fromYear      = $input['fromYear'] ?? null;
        $toYear        = $input['toYear'] ?? null;
        $musicFolderId = $input['musicFolderId'] ?? 0;

        $data           = [];
        $data['limit']  = $size;
        $data['random'] = 1;
        $data['type']   = "song";
        $count          = 0;
        if ($genre) {
            $data['rule_' . $count . '_input']    = $genre;
            $data['rule_' . $count . '_operator'] = 0;
            $data['rule_' . $count]               = "tag";
            ++$count;
        }
        if ($fromYear) {
            $data['rule_' . $count . '_input']    = $fromYear;
            $data['rule_' . $count . '_operator'] = 0;
            $data['rule_' . $count]               = "year";
            ++$count;
        }
        if ($toYear) {
            $data['rule_' . $count . '_input']    = $toYear;
            $data['rule_' . $count . '_operator'] = 1;
            $data['rule_' . $count]               = "year";
            ++$count;
        }
        if ($musicFolderId > 0) {
            $type = self::getAmpacheType($musicFolderId);
            if ($type === 'artist') {
                $artist   = new Artist(self::getAmpacheId($musicFolderId));
                $finput   = $artist->get_fullname();
                $operator = 4;
                $ftype    = "artist";
            } elseif ($type === 'album') {
                $album    = new Album(self::getAmpacheId($musicFolderId));
                $finput   = $album->get_fullname(true);
                $operator = 4;
                $ftype    = "artist";
            } else {
                $finput   = (int)($musicFolderId);
                $operator = 0;
                $ftype    = "catalog";
            }

            $data['rule_' . $count . '_input']    = $finput;
            $data['rule_' . $count . '_operator'] = $operator;
            $data['rule_' . $count]               = $ftype;
            ++$count;
        }
        if ($count > 0) {
            $songs = Random::advanced('song', $data);
        } else {
            $songs = Random::get_default($size, $user);
        }

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addRandomSongs($response, $songs);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addRandomSongs($response, $songs);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getScanStatus
     *
     * Returns the current status for media library scanning.
     * https://www.subsonic.org/pages/api.jsp#getscanstatus
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getscanstatus(array $input, User $user): void
    {
        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addScanStatus($response, $user);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addScanStatus($response, $user);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getShares
     *
     * Returns information about shared media this user is allowed to manage.
     * https://www.subsonic.org/pages/api.jsp#getshares
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getshares(array $input, User $user): void
    {
        $shares = self::getShareRepository()->getIdsByUser($user);
        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addShares($response, $shares);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addShares($response, $shares);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getSimilarSongs
     *
     * Returns a random collection of songs from the given artist and similar artists.
     * https://www.subsonic.org/pages/api.jsp#getsimilarsongs
     * @param array<string, mixed> $input
     */
    public static function getsimilarsongs(array $input, User $user, string $elementName = 'similarSongs'): void
    {
        unset($user);
        if (!AmpConfig::get('show_similar')) {
            debug_event(self::class, $elementName . ': Enable: show_similar', 4);
            self::_errorOutput($input, self::SSERROR_MISSINGPARAM, __FUNCTION__);

            return;
        }

        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }
        $object_id = self::getAmpacheId($sub_id);
        if (!$object_id) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $count = $input['count'] ?? 50;
        $songs = [];
        $type  = self::getAmpacheType($sub_id);
        if ($type === 'artist') {
            $similars = Recommendation::get_artists_like($object_id);
            if (!empty($similars)) {
                debug_event(self::class, 'Found: ' . count($similars) . ' similar artists', 4);
                foreach ($similars as $similar) {
                    debug_event(self::class, $similar['name'] . ' (id=' . $similar['id'] . ')', 5);
                    if ($similar['id']) {
                        $artist = new Artist($similar['id']);
                        if ($artist->isNew()) {
                            continue;
                        }
                        // get the songs in a random order for even more chaos
                        $artist_songs = self::getSongRepository()->getRandomByArtist($artist);
                        foreach ($artist_songs as $song) {
                            $songs[] = ['id' => $song];
                        }
                    }
                }
            }
            // randomize and slice
            shuffle($songs);
            $songs = array_slice($songs, 0, $count);
        } elseif ($type === 'album') {
            // TODO: support similar songs for albums
            debug_event(self::class, $elementName . ': album is unsupported', 4);
        } elseif ($type === 'song') {
            $songs = Recommendation::get_songs_like($object_id, $count);
        }

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            switch ($elementName) {
                case 'similarSongs':
                    $response = Subsonic_Xml_Data::addSimilarSongs($response, $songs);
                    break;
                case 'similarSongs2':
                    $response = Subsonic_Xml_Data::addSimilarSongs2($response, $songs);
                    break;
            }
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            switch ($elementName) {
                case 'similarSongs':
                    $response = Subsonic_Json_Data::addSimilarSongs($response, $songs);
                    break;
                case 'similarSongs2':
                    $response = Subsonic_Json_Data::addSimilarSongs2($response, $songs);
                    break;
            }
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getSimilarSongs2
     *
     * Returns a random collection of songs from the given artist and similar artists.
     * https://www.subsonic.org/pages/api.jsp#getsimilarsongs2
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getsimilarsongs2(array $input, User $user): void
    {
        self::getsimilarsongs($input, $user, "similarSongs2");
    }

    /**
     * getSong
     *
     * Returns details for a song.
     * https://www.subsonic.org/pages/api.jsp#getsong
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getsong(array $input, User $user): void
    {
        unset($user);
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        $song_id = self::getAmpacheId($sub_id);
        if (!$song_id) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addSong($response, $song_id);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addSong($response, $song_id);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getSongsByGenre
     *
     * Returns songs in a given genre.
     * https://www.subsonic.org/pages/api.jsp#getsongsbygenre
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getsongsbygenre(array $input, User $user): void
    {
        unset($user);
        $genre = self::_check_parameter($input, 'genre', __FUNCTION__);
        if (!$genre) {
            return;
        }

        $count  = (int)($input['count'] ?? 0);
        $offset = (int)($input['offset'] ?? 0);

        $tag = Tag::construct_from_name($genre);
        if ($tag->isNew()) {
            $songs = [];
        } else {
            $songs = Tag::get_tag_objects("song", $tag->id, $count, $offset);
        }

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addSongsByGenre($response, $songs);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addSongsByGenre($response, $songs);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getStarred
     *
     * Returns starred songs, albums and artists.
     * https://www.subsonic.org/pages/api.jsp#getstarred
     * @param array<string, mixed> $input
     */
    public static function getstarred(array $input, User $user, string $elementName = 'starred'): void
    {
        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            switch ($elementName) {
                case 'starred':
                    $response = Subsonic_Xml_Data::addStarred(
                        $response,
                        Userflag::get_latest('artist', $user, 10000),
                        Userflag::get_latest('album', $user, 10000),
                        Userflag::get_latest('song', $user, 10000)
                    );
                    break;
                case 'starred2':
                    $response = Subsonic_Xml_Data::addStarred2(
                        $response,
                        Userflag::get_latest('artist', $user, 10000),
                        Userflag::get_latest('album', $user, 10000),
                        Userflag::get_latest('song', $user, 10000)
                    );
                    break;
            }
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            switch ($elementName) {
                case 'starred':
                    $response = Subsonic_Json_Data::addStarred(
                        $response,
                        Userflag::get_latest('artist', $user, 10000),
                        Userflag::get_latest('album', $user, 10000),
                        Userflag::get_latest('song', $user, 10000)
                    );
                    break;
                case 'starred2':
                    $response = Subsonic_Json_Data::addStarred2(
                        $response,
                        Userflag::get_latest('artist', $user, 10000),
                        Userflag::get_latest('album', $user, 10000),
                        Userflag::get_latest('song', $user, 10000)
                    );
                    break;
            }
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getStarred2
     *
     * Returns starred songs, albums and artists.
     * https://www.subsonic.org/pages/api.jsp#getstarred2
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getstarred2(array $input, User $user): void
    {
        self::getstarred($input, $user, "starred2");
    }

    /**
     * getTopSongs
     *
     * Returns top songs for the given artist.
     * https://www.subsonic.org/pages/api.jsp#gettopsongs
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function gettopsongs(array $input, User $user): void
    {
        unset($user);
        $name = self::_check_parameter($input, 'artist', __FUNCTION__);
        if (!$name) {
            return;
        }

        $artist = self::getArtistRepository()->findByName(urldecode((string)$name));
        $count  = (int)($input['count'] ?? 50);
        $songs  = [];
        if ($count < 1) {
            $count = 50;
        }
        if ($artist) {
            $songs = self::getSongRepository()->getTopSongsByArtist(
                $artist,
                $count
            );
        }

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addTopSongs($response, $songs);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addTopSongs($response, $songs);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getUser
     *
     * Get details about a given user, including which authorization roles and folder access it has.
     * https://www.subsonic.org/pages/api.jsp#getuser
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function geuser(array $input, User $user): void
    {
        $username = self::_check_parameter($input, 'username', __FUNCTION__);
        if (!$username) {
            return;
        }

        if ($user->access === 100 || $user->username == $username) {
            if ($user->username == $username) {
                $update_user = $user;
            } else {
                $update_user = User::get_from_username((string)$username);
            }
            if (!$update_user) {
                self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

                return;
            }

            $format = (string)($input['f'] ?? 'xml');
            if ($format === 'xml') {
                $response = self::_addXmlResponse(__FUNCTION__);
                $response = Subsonic_Xml_Data::addUser($response, $update_user);
            } else {
                $response = self::_addJsonResponse(__FUNCTION__);
                $response = Subsonic_Json_Data::addUser($response, $update_user);
            }
            self::_responseOutput($input, __FUNCTION__, $response);
        }

        self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
    }

    /**
     * getUser
     *
     * Get details about all users, including which authorization roles and folder access they have.
     * https://www.subsonic.org/pages/api.jsp#getuser
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getuser(array $input, User $user): void
    {
        $username = self::_check_parameter($input, 'username', __FUNCTION__);
        if (!$username) {
            return;
        }

        if ($user->access === 100 || $user->username == $username) {
            if ($user->username == $username) {
                $update_user = $user;
            } else {
                $update_user = User::get_from_username((string)$username);
            }
            if (!$update_user) {
                self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            } else {
                $format = (string)($input['f'] ?? 'xml');
                if ($format === 'xml') {
                    $response = self::_addXmlResponse(__FUNCTION__);
                    $response = Subsonic_Xml_Data::addUser($response, $update_user);
                } else {
                    $response = self::_addJsonResponse(__FUNCTION__);
                    $response = Subsonic_Json_Data::addUser($response, $update_user);
                }
                self::_responseOutput($input, __FUNCTION__, $response);
            }
        } else {
            self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }


    /**
     * getUsers
     *
     * Get details about all users, including which authorization roles and folder access they have.
     * https://www.subsonic.org/pages/api.jsp#getusers
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getusers(array $input, User $user): void
    {
        if ($user->access !== 100) {
            self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);

            return;
        }

        $users  = self::getUserRepository()->getValid();
        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addUsers($response, $users);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addUsers($response, $users);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getVideoInfo
     *
     * Returns details for a video.
     * https://www.subsonic.org/pages/api.jsp#getvideoinfo
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getvideoinfo(array $input, User $user): void
    {
        unset($user);
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        $video_id = self::getAmpacheId($sub_id);
        if (!$video_id) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addVideoInfo($response, $video_id);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addVideoInfo($response, $video_id);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getVideos
     *
     * Returns all video files.
     * https://www.subsonic.org/pages/api.jsp#getvideos
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getvideos(array $input, User $user): void
    {
        unset($user);

        $videos = Catalog::get_videos();
        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addVideos($response, $videos);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addVideos($response, $videos);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * hls
     *
     * Downloads a given media file.
     * https://www.subsonic.org/pages/api.jsp#hls
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function hls(array $input, User $user): void
    {
        unset($user);
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        $object_id = self::getAmpacheId($sub_id);
        if (!$object_id) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $bitRate = $input['bitRate'] ?? false;
        $media   = [];
        $type    = self::getAmpacheType($sub_id);
        if ($type === 'song') {
            $media['object_type'] = LibraryItemEnum::SONG;
        } elseif ($type === 'video') {
            $media['object_type'] = LibraryItemEnum::VIDEO;
        } else {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $media['object_id'] = $object_id;
        $medias             = [];
        $medias[]           = $media;
        $stream             = new Stream_Playlist();
        $additional_params  = '';
        if ($bitRate) {
            $additional_params .= '&bitrate=' . $bitRate;
        }

        $stream->add($medias, $additional_params);

        // vlc won't work if we use application/vnd.apple.mpegurl, but works fine with this. this is
        // also an allowed header by the standard
        header('Content-Type: audio/mpegurl;');
        echo $stream->create_m3u();
    }

    /**
     * jukeboxControl
     *
     * Controls the jukebox, i.e., playback directly on the server’s audio hardware.
     * https://www.subsonic.org/pages/api.jsp#jukeboxcontrol
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function jukeboxcontrol(array $input, User $user): void
    {
        $action = self::_check_parameter($input, 'action', __FUNCTION__);
        if (!$action) {
            return;
        }

        $object_id  = $input['id'] ?? [];
        $controller = AmpConfig::get('localplay_controller', '');
        $localplay  = new LocalPlay($controller);
        $return     = false;
        if (empty($controller) || empty($localplay->type) || !$localplay->connect()) {
            debug_event(self::class, 'Error Localplay controller: ' . (empty($controller) ? 'Is not set' : $controller), 3);
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        debug_event(self::class, 'Using Localplay controller: ' . $controller, 5);
        switch ($action) {
            case 'get':
            case 'status':
                $return = true;
                break;
            case 'start':
                $return = $localplay->play();
                break;
            case 'stop':
                $return = $localplay->stop();
                break;
            case 'skip':
                if (isset($input['index'])) {
                    if ($localplay->skip((int)$input['index'])) {
                        $return = $localplay->play();
                    }
                } elseif (isset($input['offset'])) {
                    debug_event(self::class, 'Skip with offset is not supported on JukeboxControl.', 5);
                } else {
                    self::_errorOutput($input, self::SSERROR_MISSINGPARAM, __FUNCTION__);

                    return;
                }
                break;
            case 'set':
                $localplay->delete_all();
                // Intentional break fall-through
            case 'add':
                if ($object_id) {
                    if (!is_array($object_id)) {
                        $rid       = [];
                        $rid[]     = $object_id;
                        $object_id = $rid;
                    }

                    foreach ($object_id as $sub_id) {
                        $song_id = self::getAmpacheId($sub_id);
                        if (!$song_id) {
                            continue;
                        }

                        $url = null;
                        if (self::getAmpacheType($sub_id) === 'song') {
                            $media = new Song($song_id);
                            $url   = ($media->isNew() === false)
                                ? $media->play_url('&client=' . $localplay->type, 'api', function_exists('curl_version'), $user->id, $user->streamtoken)
                                : null;
                        }

                        if ($url !== null) {
                            debug_event(self::class, 'Adding ' . $url, 5);
                            $stream        = [];
                            $stream['url'] = $url;
                            $return        = $localplay->add_url(new Stream_Url($stream));
                        }
                    }
                }
                break;
            case 'clear':
                $return = $localplay->delete_all();
                break;
            case 'remove':
                if (isset($input['index'])) {
                    $return = $localplay->delete_track((int)$input['index']);
                } else {
                    self::_errorOutput($input, self::SSERROR_MISSINGPARAM, __FUNCTION__);
                }
                break;
            case 'shuffle':
                $return = $localplay->random(true);
                break;
            case 'setGain':
                $return = $localplay->volume_set(((float)$input['gain']) * 100);
                break;
        }

        if ($return) {
            $format = (string)($input['f'] ?? 'xml');
            if ($format === 'xml') {
                $response = self::_addXmlResponse(__FUNCTION__);
                $response = Subsonic_Xml_Data::addScanStatus($response, $user);
                if ($action == 'get') {
                    $response = Subsonic_Xml_Data::addJukeboxPlaylist($response, $localplay);
                } else {
                    $response = Subsonic_Xml_Data::addJukeboxStatus($response, $localplay);
                }
            } else {
                $response = self::_addJsonResponse(__FUNCTION__);
                $response = Subsonic_Json_Data::addScanStatus($response, $user);
                if ($action == 'get') {
                    $response = Subsonic_Json_Data::addJukeboxPlaylist($response, $localplay);
                } else {
                    $response = Subsonic_Json_Data::addJukeboxStatus($response, $localplay);
                }
            }
            self::_responseOutput($input, __FUNCTION__, $response);
        }
    }

    /**
     * ping
     *
     * Used to test connectivity with the server.
     * https://www.subsonic.org/pages/api.jsp#ping
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function ping(array $input, User $user): void
    {
        unset($user);

        self::_responseOutput($input, __FUNCTION__);
    }

    /**
     * refreshPodcasts
     *
     * Requests the server to check for new Podcast episodes.
     * https://www.subsonic.org/pages/api.jsp#refreshpodcasts
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function refreshpodcasts(array $input, User $user): void
    {
        if (AmpConfig::get('podcast') && $user->access >= 75) {
            $podcasts = Catalog::get_podcasts(User::get_user_catalogs($user->id));

            $podcastSyncer = self::getPodcastSyncer();

            foreach ($podcasts as $podcast) {
                $podcastSyncer->sync($podcast, true);
            }
            self::_responseOutput($input, __FUNCTION__);
        } else {
            self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * savePlayQueue
     *
     * Saves the state of the play queue for this user.
     * https://www.subsonic.org/pages/api.jsp#saveplayqueue
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function saveplayqueue(array $input, User $user): void
    {
        $id_list  = $input['id'] ?? '';
        $current  = (string)($input['current'] ?? '');
        $position = (array_key_exists('position', $input))
            ? (int)(((int)$input['position']) / 1000)
            : 0;
        $client    = scrub_in((string) ($input['c'] ?? 'Subsonic'));
        $user_id   = $user->id;
        $time      = time();
        $playQueue = new User_Playlist($user_id, $client);
        if (empty($id_list)) {
            $playQueue->clear();
        } else {
            $media = (!empty($current))
                ? self::getAmpacheObject($current)
                : null;
            if (
                $media instanceof library_item &&
                $media instanceof Media &&
                $media->isNew() === false &&
                isset($media->time)
            ) {
                $playqueue_time = (int)User::get_user_data($user->id, 'playqueue_time', 0)['playqueue_time'];
                // wait a few seconds before smashing out play times
                if ($playqueue_time < ($time - 2)) {
                    $previous = Stats::get_last_play($user_id, $client);
                    $type     = self::getAmpacheType($current);
                    // long pauses might cause your now_playing to hide
                    Stream::garbage_collection();
                    Stream::insert_now_playing($media->getId(), $user_id, ($media->time - $position), (string)$user->username, $type, ($time - $position));

                    if (array_key_exists('object_id', $previous) && $previous['object_id'] == $media->getId()) {
                        $time_diff = $time - $previous['date'];
                        $old_play  = $time_diff > $media->time * 5;
                        // shift the start time if it's an old play or has been pause/played
                        if ($position >= 1 || $old_play) {
                            Stats::shift_last_play($user_id, $client, $previous['date'], ($time - $position));
                        }
                        // track has just started. repeated plays aren't called by scrobble so make sure we call this too
                        if (($position < 1 && $time_diff > 5) && !$old_play) {
                            $media->set_played($user_id, $client, [], $time);
                        }
                    }
                }
            } else {
                self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

                return;
            }

            $sub_ids = (is_array($id_list))
                ? $id_list
                : [$id_list];
            $playlist = self::_getAmpacheIdArrays($sub_ids);

            // clear the old list
            $playQueue->clear();
            // set the new items
            $playQueue->add_items($playlist, $time);

            if (
                isset($type) &&
                isset($media->id)
            ) {
                $playQueue->set_current_object($type, $media->id, $position);
            }

            // subsonic cares about queue dates so set them (and set them together)
            User::set_user_data($user_id, 'playqueue_time', $time);
            User::set_user_data($user_id, 'playqueue_client', $client);
        }

        self::_responseOutput($input, __FUNCTION__);
    }

    /**
     * savePlayQueueByIndex
     *
     * Saves the state of the play queue for this user.
     * https://www.subsonic.org/pages/api.jsp#saveplayqueuebyindex
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function saveplayqueuebyindex(array $input, User $user): void
    {
        $id_list = $input['id'] ?? '';
        $sub_ids = (is_array($id_list))
            ? $id_list
            : [$id_list];
        $index    = (int)($input['currentIndex'] ?? 0);
        if ($index < 0 || $index >= count($sub_ids)) {
            self::_errorOutput($input, self::SSERROR_MISSINGPARAM, __FUNCTION__);

            return;
        }

        $current  = $sub_ids[$index];
        $position = (array_key_exists('position', $input))
            ? (int)(((int)$input['position']) / 1000)
            : 0;
        $client    = scrub_in((string) ($input['c'] ?? 'Subsonic'));
        $user_id   = $user->id;
        $time      = time();
        $playQueue = new User_Playlist($user_id, $client);
        if (empty($id_list)) {
            $playQueue->clear();
        } else {
            $media = (!empty($current))
                ? self::getAmpacheObject($current)
                : null;
            if (
                $media instanceof library_item &&
                $media instanceof Media &&
                $media->isNew() === false &&
                isset($media->time)
            ) {
                $playqueue_time = (int)User::get_user_data($user->id, 'playqueue_time', 0)['playqueue_time'];
                // wait a few seconds before smashing out play times
                if ($playqueue_time < ($time - 2)) {
                    $previous = Stats::get_last_play($user_id, $client);
                    $type     = self::getAmpacheType($current);
                    // long pauses might cause your now_playing to hide
                    Stream::garbage_collection();
                    Stream::insert_now_playing($media->getId(), $user_id, ($media->time - $position), (string)$user->username, $type, ($time - $position));

                    if (array_key_exists('object_id', $previous) && $previous['object_id'] == $media->getId()) {
                        $time_diff = $time - $previous['date'];
                        $old_play  = $time_diff > $media->time * 5;
                        // shift the start time if it's an old play or has been pause/played
                        if ($position >= 1 || $old_play) {
                            Stats::shift_last_play($user_id, $client, $previous['date'], ($time - $position));
                        }
                        // track has just started. repeated plays aren't called by scrobble so make sure we call this too
                        if (($position < 1 && $time_diff > 5) && !$old_play) {
                            $media->set_played($user_id, $client, [], $time);
                        }
                    }
                }
            } else {
                self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

                return;
            }

            $playlist = self::_getAmpacheIdArrays($sub_ids);

            // clear the old list
            $playQueue->clear();
            // set the new items
            $playQueue->add_items($playlist, $time);

            if (
                isset($type) &&
                isset($media->id)
            ) {
                $playQueue->set_current_object($type, $media->id, $position);
            }

            // subsonic cares about queue dates so set them (and set them together)
            User::set_user_data($user_id, 'playqueue_time', $time);
            User::set_user_data($user_id, 'playqueue_client', $client);
        }

        self::_responseOutput($input, __FUNCTION__);
    }

    /**
     * scrobble
     *
     * Registers the local playback of one or more media files.
     * https://www.subsonic.org/pages/api.jsp#scrobble
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function scrobble(array $input, User $user): void
    {
        $sub_ids = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_ids) {
            return;
        }

        $submission = (array_key_exists('submission', $input) && ($input['submission'] === 'true' || $input['submission'] === '1'));
        $client     = scrub_in((string) ($input['c'] ?? 'Subsonic'));

        if (!is_array($sub_ids)) {
            $rid     = [];
            $rid[]   = $sub_ids;
            $sub_ids = $rid;
        }
        $playqueue_time = (int)User::get_user_data($user->id, 'playqueue_time', 0)['playqueue_time'];
        $now_time       = time();
        // don't scrobble after setting the play queue too quickly
        if ($playqueue_time < ($now_time - 2)) {
            foreach ($sub_ids as $sub_id) {
                $time = (isset($input['time']))
                    ? (int)(((int)$input['time']) / 1000)
                    : time();
                $previous  = Stats::get_last_play($user->id, $client, $time);
                $prev_obj  = $previous['object_id'] ?: 0;
                $prev_date = $previous['date'];
                $type      = self::getAmpacheType($sub_id);
                $media     = self::getAmpacheObject((string)$sub_id);
                if (!$media instanceof Media || !isset($media->time) || !isset($media->id)) {
                    continue;
                }


                // long pauses might cause your now_playing to hide
                Stream::garbage_collection();
                Stream::insert_now_playing((int)$media->id, $user->id, $media->time, (string)$user->username, $type, $time);
                // submission is true: go to scrobble plugins (Plugin::get_plugins(PluginTypeEnum::SAVE_MEDIAPLAY))
                if ($submission && get_class($media) == Song::class && ($prev_obj != $media->id) && (($time - $prev_date) > 5)) {
                    // stream has finished
                    debug_event(self::class, $user->username . ' scrobbled: {' . $media->id . '} at ' . $time, 5);
                    User::save_mediaplay($user, $media);
                }
                // Submission is false and not a repeat. let repeats go through to saveplayqueue
                if ((!$submission) && $media->id && ($prev_obj != $media->id) && (($time - $prev_date) > 5)) {
                    $media->set_played($user->id, $client, [], $time);
                }
            }
        }

        self::_responseOutput($input, __FUNCTION__);
    }

    /**
     * search
     *
     * NOT IMPLEMENTED
     * https://www.subsonic.org/pages/api.jsp#search
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function search(array $input, User $user): void
    {
        unset($user);
        self::_errorOutput($input, self::SSERROR_GENERIC, __FUNCTION__);
    }

    /**
     * search2
     *
     * Returns a listing of files matching the given search criteria. Supports paging through the result.
     * https://www.subsonic.org/pages/api.jsp#search2
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function search2(array $input, User $user): void
    {
        $query = $input['query'] ?? '';

        $results = self::_search($query, $input, $user);

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addSearchResult2($response, $results['artists'], $results['albums'], $results['songs']);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addSearchResult2($response, $results['artists'], $results['albums'], $results['songs']);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * search3
     *
     * Returns albums, artists and songs matching the given search criteria. Supports paging through the result.
     * https://www.subsonic.org/pages/api.jsp#search3
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function search3(array $input, User $user): void
    {
        $query = self::_check_parameter($input, 'query', __FUNCTION__);
        if ($query === false) {
            return;
        }

        $results = self::_search($query, $input, $user);

        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addSearchResult3($response, $results['artists'], $results['albums'], $results['songs']);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addSearchResult3($response, $results['artists'], $results['albums'], $results['songs']);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * setRating
     *
     * Sets the rating for a music file.
     * https://www.subsonic.org/pages/api.jsp#setrating
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function setrating(array $input, User $user): void
    {
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        $rating = self::_check_parameter($input, 'rating', __FUNCTION__);
        if (!$rating) {
            return;
        }

        $type = self::getAmpacheType($sub_id);
        $robj = (!empty($type))
            ? new Rating(self::getAmpacheId($sub_id), $type)
            : null;

        if ($robj != null && ($rating >= 0 && $rating <= 5)) {
            $robj->set_rating($rating, $user->id);

            self::_responseOutput($input, __FUNCTION__);
        } else {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
        }
    }

    /**
     * star
     *
     * Attaches a star to a song, album or artist.
     * https://www.subsonic.org/pages/api.jsp#star
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function star(array $input, User $user): void
    {
        self::_setStar($input, $user, true);
    }

    /**
     * startScan
     *
     * Initiates a rescan of the media libraries.
     * https://www.subsonic.org/pages/api.jsp#startscan
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function startscan(array $input, User $user): void
    {
        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addScanStatus($response, $user);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addScanStatus($response, $user);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * stream
     *
     * Streams a given media file.
     * https://www.subsonic.org/pages/api.jsp#stream
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function stream(array $input, User $user): void
    {
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        $object = self::getAmpacheObject($sub_id);
        if (($object instanceof Song || $object instanceof Podcast_Episode) === false) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $maxBitRate    = (int)($input['maxBitRate'] ?? 0);
        $format        = $input['format'] ?? null; // mp3, flv or raw
        $timeOffset    = $input['timeOffset'] ?? false;
        $contentLength = $input['estimateContentLength'] ?? false; // Force content-length guessing if transcode
        $client        = scrub_in((string) ($input['c'] ?? 'Subsonic'));

        $params = '&client=' . rawurlencode($client);
        if ($contentLength == 'true') {
            $params .= '&content_length=required';
        }
        if ($format && $format != "raw") {
            $params .= '&transcode_to=' . $format;
        }
        if ($maxBitRate > 0) {
            $params .= '&bitrate=' . $maxBitRate;
        }
        if ($timeOffset) {
            $params .= '&frame=' . $timeOffset;
        }

        // No scrobble for streams using open subsonic https://www.subsonic.org/pages/api.jsp#stream/
        $params .= '&cache=1';

        self::_follow_stream($object->play_url($params, 'api', function_exists('curl_version'), $user->id, $user->streamtoken));
    }

    /**
     * tokenInfo [OS]
     *
     * Returns information about an API key.
     * https://opensubsonic.netlify.app/docs/endpoints/tokeninfo/
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function tokeninfo(array $input, User $user): void
    {
        $format = (string)($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = self::_addXmlResponse(__FUNCTION__);
            $response = Subsonic_Xml_Data::addTokenInfo($response, $user);
        } else {
            $response = self::_addJsonResponse(__FUNCTION__);
            $response = Subsonic_Json_Data::addTokenInfo($response, $user);
        }
        self::_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * unstar
     *
     * Attaches a star to a song, album or artist.
     * https://www.subsonic.org/pages/api.jsp#unstar
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function unstar(array $input, User $user): void
    {
        self::_setStar($input, $user, false);
    }

    /**
     * updateInternetRadioStation
     *
     * Updates an existing internet radio station.
     * https://www.subsonic.org/pages/api.jsp#updateinternetradiostation
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function updateinternetradiostation(array $input, User $user): void
    {
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        $url = self::_check_parameter($input, 'streamUrl', __FUNCTION__);
        if (!$url) {
            return;
        }

        $name = self::_check_parameter($input, 'name', __FUNCTION__);
        if (!$name) {
            return;
        }

        $site_url = filter_var(urldecode($input['homepageUrl']), FILTER_VALIDATE_URL) ?: '';

        if (AmpConfig::get('live_stream') && $user->access >= 75) {
            $internetradiostation = new Live_Stream(self::getAmpacheId($sub_id));
            if ($internetradiostation->id > 0) {
                $data = [
                    "name" => $name,
                    "url" => $url,
                    "codec" => 'mp3',
                    "site_url" => $site_url
                ];
                if ($internetradiostation->update($data)) {
                    self::_responseOutput($input, __FUNCTION__);
                } else {
                    self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
                }
            } else {
                self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * updatePlaylist
     *
     * Updates a playlist. Only the owner of a playlist is allowed to update it.
     * https://www.subsonic.org/pages/api.jsp#updateplaylist
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function updateplaylist(array $input, User $user): void
    {
        unset($user);
        $sub_id = self::_check_parameter($input, 'playlistId', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        $name              = $input['name'] ?? '';
        $public            = make_bool($input['public'] ?? false);
        $songIdToAdd       = $input['songIdToAdd'] ?? [];
        $songIndexToRemove = $input['songIndexToRemove'] ?? [];

        $object = self::getAmpacheObject($sub_id);
        if (!$object) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        if ($object instanceof Playlist) {
            if (is_string($songIdToAdd)) {
                $songIdToAdd = explode(',', $songIdToAdd);
            }
            if (is_string($songIndexToRemove)) {
                $songIndexToRemove = explode(',', $songIndexToRemove);
            }
            self::_updatePlaylist($object->getId(), $name, $songIdToAdd, $songIndexToRemove, $public);

            self::_responseOutput($input, __FUNCTION__);
        } else {
            self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * updateShare
     *
     * Updates the description and/or expiration date for an existing share.
     * https://www.subsonic.org/pages/api.jsp#updateshare
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function updateshare(array $input, User $user): void
    {
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if (!$sub_id) {
            return;
        }

        if (AmpConfig::get('share')) {
            $share = new Share(self::getAmpacheId($sub_id));
            if ($share->id > 0) {
                $expires = (isset($input['expires']))
                    ? Share::get_expiry(((int)filter_var($input['expires'], FILTER_SANITIZE_NUMBER_INT)) / 1000)
                    : $share->expire_days;
                $data = [
                    'max_counter' => $share->max_counter,
                    'expire' => $expires,
                    'allow_stream' => $share->allow_stream,
                    'allow_download' => $share->allow_download,
                    'description' => $input['description'] ?? $share->description,
                ];
                if ($share->update($data, $user)) {
                    self::_responseOutput($input, __FUNCTION__);
                } else {
                    self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
                }
            } else {
                self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * updateUser
     *
     * Modifies an existing user on the server.
     * https://www.subsonic.org/pages/api.jsp#updateuser
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function updateuser(array $input, User $user): void
    {
        $username = self::_check_parameter($input, 'username', __FUNCTION__);
        if (!$username) {
            return;
        }

        $password     = $input['password'] ?? false;
        $email        = (array_key_exists('email', $input)) ? urldecode($input['email']) : false;
        $adminRole    = (array_key_exists('adminRole', $input) && $input['adminRole'] == 'true');
        $downloadRole = (array_key_exists('downloadRole', $input) && $input['downloadRole'] == 'true');
        $uploadRole   = (array_key_exists('uploadRole', $input) && $input['uploadRole'] == 'true');
        $coverArtRole = (array_key_exists('coverArtRole', $input) && $input['coverArtRole'] == 'true');
        $shareRole    = (array_key_exists('shareRole', $input) && $input['shareRole'] == 'true');
        $maxbitrate   = (int)($input['maxBitRate'] ?? 0);

        if ($user->access === 100) {
            $access = 25;
            if ($coverArtRole) {
                $access = 75;
            }
            if ($adminRole) {
                $access = 100;
            }
            // identify the user to modify
            $update_user = User::get_from_username((string)$username);
            if ($update_user instanceof User) {
                $user_id = $update_user->id;
                // update access level
                $update_user->update_access($access);
                // update password
                if ($password && !AmpConfig::get('simple_user_mode')) {
                    $password = self::decryptPassword($password);
                    $update_user->update_password($password);
                }
                // update e-mail
                if ($email && Mailer::validate_address($email)) {
                    $update_user->update_email($email);
                }
                // set preferences
                if ($downloadRole) {
                    Preference::update('download', $user_id, 1);
                }
                if ($uploadRole) {
                    Preference::update('allow_upload', $user_id, 1);
                }
                if ($shareRole) {
                    Preference::update('share', $user_id, 1);
                }
                if ($maxbitrate > 0) {
                    Preference::update('transcode_bitrate', $user_id, $maxbitrate);
                }
                self::_responseOutput($input, __FUNCTION__);
            } else {
                self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            self::_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * @deprecated inject dependency
     */
    private static function getPrivateMessageRepository(): PrivateMessageRepositoryInterface
    {
        global $dic;

        return $dic->get(PrivateMessageRepositoryInterface::class);
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
     * @deprecated inject dependency
     */
    private static function getArtistRepository(): ArtistRepositoryInterface
    {
        global $dic;

        return $dic->get(ArtistRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getBookmarkRepository(): BookmarkRepositoryInterface
    {
        global $dic;

        return $dic->get(BookmarkRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getPodcastSyncer(): PodcastSyncerInterface
    {
        global $dic;

        return $dic->get(PodcastSyncerInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getLiveStreamRepository(): LiveStreamRepositoryInterface
    {
        global $dic;

        return $dic->get(LiveStreamRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getPodcastCreator(): PodcastCreatorInterface
    {
        global $dic;

        return $dic->get(PodcastCreatorInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getPodcastDeleter(): PodcastDeleterInterface
    {
        global $dic;

        return $dic->get(PodcastDeleterInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getPodcastRepository(): PodcastRepositoryInterface
    {
        global $dic;

        return $dic->get(PodcastRepositoryInterface::class);
    }

    /**
     * @deprecated Inject dependency
     */
    private static function getShareRepository(): ShareRepositoryInterface
    {
        global $dic;

        return $dic->get(ShareRepositoryInterface::class);
    }

    /**
     * @deprecated Inject by constructor
     */
    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }
}
