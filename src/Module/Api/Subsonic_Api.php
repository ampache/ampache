<?php

/** @noinspection PhpUnused */

declare(strict_types=0);

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
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Api\Subsonic\Handler\BookmarkHandlerInterface;
use Ampache\Module\Api\Subsonic\Handler\ChatHandlerInterface;
use Ampache\Module\Api\Subsonic\Handler\InternetRadioHandlerInterface;
use Ampache\Module\Api\Subsonic\Handler\RatingHandlerInterface;
use Ampache\Module\Api\Subsonic\Handler\ShareHandlerInterface;
use Ampache\Module\Api\Subsonic\Handler\SystemHandlerInterface;
use Ampache\Module\Api\Subsonic\Handler\UserHandlerInterface;
use Ampache\Module\Api\Subsonic\MusicFolderResolverInterface;
use Ampache\Module\Api\Subsonic\SubsonicResponseHandlerInterface;
use Ampache\Module\Art\Art;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Catalog\CountableTableEnum;
use Ampache\Module\Database\Query\Random;
use Ampache\Module\Database\Query\Search;
use Ampache\Module\Database\Query\Smartlist;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\Playback\User_Playlist;
use Ampache\Module\Podcast\Exception\PodcastCreationException;
use Ampache\Module\Podcast\PodcastCreatorInterface;
use Ampache\Module\Podcast\PodcastDeleterInterface;
use Ampache\Module\Podcast\PodcastSyncerInterface;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\System\Core;
use Ampache\Module\System\Preference;
use Ampache\Module\Util\Recommendation;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Media;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\PrivateMsg;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Ampache\Repository\PodcastRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use CurlHandle;
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
    public const string API_VERSION = "1.16.1";

    /**
     * Ampache doesn't have a global unique id but items are unique per category. We use id prefixes to identify item category.
     */

    public const int OLD_SUBID_ALBUM = 200000000;

    public const int OLD_SUBID_ARTIST = 100000000;

    public const int OLD_SUBID_PLAYLIST = 800000000;

    public const int OLD_SUBID_PODCAST = 600000000;

    public const int OLD_SUBID_PODCASTEP = 700000000;

    public const int OLD_SUBID_SMARTPL = 400000000;

    public const int OLD_SUBID_SONG = 300000000;

    public const int OLD_SUBID_VIDEO = 500000000;

    public const int SSERROR_APIVERSION_CLIENT = 20; // Incompatible Subsonic REST protocol version. Client must upgrade.

    public const int SSERROR_APIVERSION_SERVER = 30; // Incompatible Subsonic REST protocol version. Server must upgrade.

    public const int SSERROR_BADAUTH = 40; // Wrong username or password.

    public const int SSERROR_DATA_NOTFOUND = 70; // The requested data was not found.

    public const int SSERROR_GENERIC = 0; // A generic error.

    public const int SSERROR_MISSINGPARAM = 10; // Required parameter is missing.

    public const int SSERROR_TOKENAUTHNOTSUPPORTED = 41; // Token authentication not supported for LDAP users.

    public const int SSERROR_TRIAL = 60; // The trial period for the Subsonic server is over. Please upgrade to Subsonic Premium. Visit subsonic.org for details.

    public const int SSERROR_UNAUTHORIZED = 50; // User is not authorized for the given operation.

    public const string SUBID_ALBUM = 'al-';

    public const string SUBID_ARTIST = 'ar-';

    public const string SUBID_BOOKMARK = 'bo-';

    public const string SUBID_CATALOG = 'mf-';

    public const string SUBID_CHAT = 'pm-';

    public const string SUBID_FOLDER = 'fo-';

    public const string SUBID_GENRE = 'ta-';

    public const string SUBID_LIVESTREAM = 'li-';

    public const string SUBID_PLAYLIST = 'pl-';

    public const string SUBID_PODCAST = 'po-';

    public const string SUBID_PODCASTEP = 'pe-';

    public const string SUBID_SHARE = 'sh-';

    public const string SUBID_SMARTPL = 'sp-';

    public const string SUBID_SONG = 'so-';

    public const string SUBID_USER = 'us-';

    public const string SUBID_VIDEO = 'vi-';

    /**
     * List of internal functions that should be skipped when called from SubsonicApiApplication
     * @var string[]
     */
    public const array SYSTEM_LIST = [
        '__construct',
        '_albumList',
        '_follow_stream',
        '_getAmpacheIdArrays',
        '_output_body',
        '_output_header',
        '_search',
        '_updatePlaylist',
        'error',
        'getAlbumSubId',
        'getAmpacheId',
        'getAmpacheObject',
        'getAmpacheType',
        'getArtistSubId',
        'getBookmarkSubId',
        'getCatalogSubId',
        'getChatSubId',
        'getFolderSubId',
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

    private AlbumRepositoryInterface $albumRepository;
    private ArtistRepositoryInterface $artistRepository;
    private BookmarkHandlerInterface $bookmarkHandler;
    private ChatHandlerInterface $chatHandler;
    private FolderRepositoryInterface $folderRepository;
    private InternetRadioHandlerInterface $internetRadioHandler;
    private MusicFolderResolverInterface $musicFolderResolver;
    private PodcastCreatorInterface $podcastCreator;
    private PodcastDeleterInterface $podcastDeleter;
    private PodcastRepositoryInterface $podcastRepository;
    private PodcastSyncerInterface $podcastSyncer;
    private Random $random;
    private RatingHandlerInterface $ratingHandler;
    private SubsonicResponseHandlerInterface $responseHandler;
    private ShareHandlerInterface $shareHandler;
    private SongRepositoryInterface $songRepository;
    private Subsonic_Json_Data $subsonicJsonData;
    private Subsonic_Xml_Data $subsonicXmlData;
    private SystemHandlerInterface $systemHandler;
    private UserHandlerInterface $userHandler;

    public function __construct(
        AlbumRepositoryInterface $albumRepository,
        ArtistRepositoryInterface $artistRepository,
        BookmarkHandlerInterface $bookmarkHandler,
        ChatHandlerInterface $chatHandler,
        FolderRepositoryInterface $folderRepository,
        InternetRadioHandlerInterface $internetRadioHandler,
        MusicFolderResolverInterface $musicFolderResolver,
        PodcastCreatorInterface $podcastCreator,
        PodcastDeleterInterface $podcastDeleter,
        PodcastRepositoryInterface $podcastRepository,
        PodcastSyncerInterface $podcastSyncer,
        Random $random,
        RatingHandlerInterface $ratingHandler,
        ShareHandlerInterface $shareHandler,
        SongRepositoryInterface $songRepository,
        SubsonicResponseHandlerInterface $responseHandler,
        Subsonic_Json_Data $subsonicJsonData,
        Subsonic_Xml_Data $subsonicXmlData,
        SystemHandlerInterface $systemHandler,
        UserHandlerInterface $userHandler,
    ) {
        $this->albumRepository          = $albumRepository;
        $this->artistRepository         = $artistRepository;
        $this->bookmarkHandler          = $bookmarkHandler;
        $this->chatHandler              = $chatHandler;
        $this->folderRepository         = $folderRepository;
        $this->internetRadioHandler     = $internetRadioHandler;
        $this->musicFolderResolver      = $musicFolderResolver;
        $this->podcastCreator           = $podcastCreator;
        $this->podcastDeleter           = $podcastDeleter;
        $this->podcastRepository        = $podcastRepository;
        $this->podcastSyncer            = $podcastSyncer;
        $this->random                   = $random;
        $this->ratingHandler            = $ratingHandler;
        $this->shareHandler             = $shareHandler;
        $this->songRepository           = $songRepository;
        $this->responseHandler          = $responseHandler;
        $this->subsonicJsonData         = $subsonicJsonData;
        $this->subsonicXmlData          = $subsonicXmlData;
        $this->systemHandler            = $systemHandler;
        $this->userHandler              = $userHandler;
    }

    public static function getAlbumSubId(int $ampache_id): string
    {
        return (string) (self::OLD_SUBID_ALBUM + $ampache_id);
    }

    /**
     * getAmpacheId
     */
    public static function getAmpacheId(string $sub_id): ?int
    {
        // keep oldstyle subsonic ids for compatibility (TODO REMOVE IN AMPACHE 8.0)
        if (is_numeric($sub_id)) {
            $int_id = (int) $sub_id;
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

            return $int_id;
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
            case self::SUBID_FOLDER:
            case self::SUBID_GENRE:
            case self::SUBID_LIVESTREAM:
            case self::SUBID_PLAYLIST:
            case self::SUBID_PODCAST:
            case self::SUBID_PODCASTEP:
            case self::SUBID_SHARE:
            case self::SUBID_SMARTPL:
            case self::SUBID_SONG:
            case self::SUBID_USER:
            case self::SUBID_VIDEO:
                return (int) $ampache_id;
        }

        return null;
    }

    /**
     * getAmpacheObject
     * Return the Ampache media object
     */
    public static function getAmpacheObject(string $sub_id): ?object
    {
        // keep oldstyle subsonic ids for compatibility (TODO REMOVE IN AMPACHE 8.0)
        if (is_numeric($sub_id)) {
            $int_id = (int) $sub_id;
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
                return new Smartlist($int_id - self::OLD_SUBID_SMARTPL);
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

            return Catalog::create_from_id($int_id);
        }

        // everything else is a string prefix
        $ampache_id = substr($sub_id, 3) ?: null;
        if (!$ampache_id) {
            return null;
        }

        $ampache_id = (int) $ampache_id;
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
            case self::SUBID_FOLDER:
                return new Folder($ampache_id);
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
                return new Smartlist($ampache_id);
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
     * getAmpacheType
     */
    public static function getAmpacheType(string $sub_id): string
    {
        // keep oldstyle subsonic ids for compatibility (TODO REMOVE IN AMPACHE 8.0)
        if (is_numeric($sub_id)) {
            $int_id = (int) $sub_id;
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

            return "catalog";
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
            case self::SUBID_FOLDER:
                return "folder";
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

    public static function getArtistSubId(int $ampache_id): string
    {
        return (string) (self::OLD_SUBID_ARTIST + $ampache_id);
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

    public static function getFolderSubId(int|string $ampache_id): string
    {
        return self::SUBID_FOLDER . $ampache_id;
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
        return (string) (self::OLD_SUBID_PLAYLIST + $ampache_id);
    }

    public static function getPodcastEpisodeSubId(int $ampache_id): string
    {
        return (string) (self::OLD_SUBID_PODCASTEP + $ampache_id);
    }

    public static function getPodcastSubId(int $ampache_id): string
    {
        return (string) (self::OLD_SUBID_PODCAST + $ampache_id);
    }

    public static function getShareSubId(int|string $ampache_id): string
    {
        return self::SUBID_SHARE . $ampache_id;
    }

    public static function getSmartPlaylistSubId(int $ampache_id): string
    {
        return (string) (self::OLD_SUBID_SMARTPL + $ampache_id);
    }

    public static function getSongSubId(int $ampache_id): string
    {
        return (string) (self::OLD_SUBID_SONG + $ampache_id);
    }

    public static function getUserSubId(int|string $ampache_id): string
    {
        return self::SUBID_USER . $ampache_id;
    }

    public static function getVideoSubId(int $ampache_id): string
    {
        return (string) (self::OLD_SUBID_VIDEO + $ampache_id);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function addchatmessage(array $input, User $user): void
    {
        $this->chatHandler->addchatmessage($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function changepassword(array $input, User $user): void
    {
        $this->userHandler->changepassword($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function createbookmark(array $input, User $user): void
    {
        $this->bookmarkHandler->createbookmark($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function createinternetradiostation(array $input, User $user): void
    {
        $this->internetRadioHandler->createinternetradiostation($input, $user);
    }

    /**
     * createPlaylist
     *
     * Creates (or updates) a playlist.
     * https://www.subsonic.org/pages/api.jsp#createplaylist
     * @param array<string, mixed> $input
     */
    public function createplaylist(array $input, User $user): void
    {
        $playlistId = self::getAmpacheId($input['playlistId'] ?? '');
        $name       = $input['name'] ?? '';
        $songIdList = $input['songId'] ?? [];
        if (isset($input['songId']) && is_string($input['songId'])) {
            $songIdList = explode(',', $input['songId']);
        }

        if ($playlistId !== null) {
            // creating over an existing id rewrites that playlist, so it needs the same owner gate as updateplaylist
            $playlist = new Playlist($playlistId);
            if ($playlist->isNew()) {
                $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

                return;
            }

            if (!$playlist->has_access($user)) {
                $this->responseHandler->errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);

                return;
            }

            $this->_updatePlaylist($playlistId, $name, $songIdList, [], true, true);
            $this->responseHandler->responseOutput($input, __FUNCTION__);
        } elseif (!empty($name)) {
            $playlistId = Playlist::create($name, 'public', $user->id);
            if ($playlistId !== null) {
                if (count($songIdList) > 0) {
                    $this->_updatePlaylist($playlistId, "", $songIdList, [], true, true);
                }

                // output the new playlist
                $format   = (string) ($input['f'] ?? 'xml');
                $playlist = new Playlist($playlistId);
                if ($format === 'xml') {
                    $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
                    $response = $this->subsonicXmlData->addPlaylist($response, $playlist, true);
                } else {
                    $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
                    $response = $this->subsonicJsonData->addPlaylist($response, $playlist, true);
                }
                $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
            } else {
                $this->responseHandler->errorOutput($input, self::SSERROR_GENERIC, __FUNCTION__);
            }
        } else {
            $this->responseHandler->errorOutput($input, self::SSERROR_MISSINGPARAM, __FUNCTION__);
        }
    }

    /**
     * createPodcastChannel
     *
     * Adds a new Podcast channel.
     * https://www.subsonic.org/pages/api.jsp#createpodcastchannel
     * @param array<string, mixed> $input
     */
    public function createpodcastchannel(array $input, User $user): void
    {
        $url = $this->responseHandler->checkParameter($input, 'url', __FUNCTION__);
        if ($url === false) {
            return;
        }

        if (AmpConfig::get('podcast') && $user->access >= 75) {
            $catalogs = $user->get_catalogs('podcast');
            if (count($catalogs) > 0) {
                /** @var Catalog $catalog */
                $catalog = Catalog::create_from_id($catalogs[0]);

                try {
                    $this->podcastCreator->create($url, $catalog);

                    $this->responseHandler->responseOutput($input, __FUNCTION__);
                } catch (PodcastCreationException) {
                    $this->responseHandler->errorOutput($input, self::SSERROR_GENERIC, __FUNCTION__);
                }
            } else {
                $this->responseHandler->errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
            }
        } else {
            $this->responseHandler->errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * @param array<string, mixed> $input
     */
    public function createshare(array $input, User $user): void
    {
        $this->shareHandler->createshare($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function createuser(array $input, User $user): void
    {
        $this->userHandler->createuser($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function deletebookmark(array $input, User $user): void
    {
        $this->bookmarkHandler->deletebookmark($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function deleteinternetradiostation(array $input, User $user): void
    {
        $this->internetRadioHandler->deleteinternetradiostation($input, $user);
    }

    /**
     * deletePlaylist
     *
     * Deletes a saved playlist.
     * https://www.subsonic.org/pages/api.jsp#deleteplaylist
     * @param array<string, mixed> $input
     */
    public function deleteplaylist(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $playlist = self::getAmpacheObject($sub_id);
        if (
            (!($playlist instanceof Playlist || $playlist instanceof Search))
            || $playlist->isNew()
        ) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        if (!$playlist->has_access($user)) {
            $this->responseHandler->errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);

            return;
        }

        $playlist->delete();

        $this->responseHandler->responseOutput($input, __FUNCTION__);
    }

    /**
     * deletePodcastChannel
     *
     * Deletes a Podcast channel.
     * https://www.subsonic.org/pages/api.jsp#deletepodcastchannel
     * @param array<string, mixed> $input
     */
    public function deletepodcastchannel(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        if (AmpConfig::get(ConfigurationKeyEnum::PODCAST) && $user->access >= AccessLevelEnum::MANAGER->value) {
            $podcast_id = self::getAmpacheId($sub_id);
            $podcast    = ($podcast_id)
                ? $this->podcastRepository->findById($podcast_id)
                : null;
            if ($podcast === null) {
                $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            } else {
                $this->podcastDeleter->delete($podcast);

                $this->responseHandler->responseOutput($input, __FUNCTION__);
            }
        } else {
            $this->responseHandler->errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * deletePodcastEpisode
     *
     * Deletes a Podcast episode.
     * https://www.subsonic.org/pages/api.jsp#deletepodcastepisode
     * @param array<string, mixed> $input
     */
    public function deletepodcastepisode(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        if (AmpConfig::get('podcast') && $user->access >= 75) {
            $episode = new Podcast_Episode(self::getAmpacheId($sub_id));
            if ($episode->isNew()) {
                $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            } elseif ($episode->remove()) {
                Catalog::count_table(CountableTableEnum::PODCAST_EPISODE);

                $this->responseHandler->responseOutput($input, __FUNCTION__);
            } else {
                $this->responseHandler->errorOutput($input, self::SSERROR_GENERIC, __FUNCTION__);
            }
        } else {
            $this->responseHandler->errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * @param array<string, mixed> $input
     */
    public function deleteshare(array $input, User $user): void
    {
        $this->shareHandler->deleteshare($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function deleteuser(array $input, User $user): void
    {
        $this->userHandler->deleteuser($input, $user);
    }

    /**
     * download
     *
     * Downloads a given media file.
     * https://www.subsonic.org/pages/api.jsp#download
     * @param array<string, mixed> $input
     */
    public function download(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $object = self::getAmpacheObject($sub_id);
        if (($object instanceof Song || $object instanceof Podcast_Episode) === false) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $client = scrub_in((string) ($input['c'] ?? 'Subsonic'));
        $params = '&client=' . rawurlencode($client) . '&cache=1';

        $this->_follow_stream($object->play_url($params, 'api', function_exists('curl_version'), $user->id, $user->streamtoken));
    }

    /**
     * downloadPodcastEpisode
     *
     * Request the server to start downloading a given Podcast episode.
     * https://www.subsonic.org/pages/api.jsp#downloadpodcastepisode
     * @param array<string, mixed> $input
     */
    public function downloadpodcastepisode(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        if (AmpConfig::get('podcast') && $user->access >= 75) {
            $episode = new Podcast_Episode(self::getAmpacheId($sub_id));
            if ($episode->isNew()) {
                $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            } else {
                $this->podcastSyncer->syncEpisode($episode);

                $this->responseHandler->responseOutput($input, __FUNCTION__);
            }
        } else {
            $this->responseHandler->errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * error
     * @param array<string, mixed> $input
     */
    public function error(array $input, int $errorCode, string $function): void
    {
        $this->responseHandler->errorOutput($input, $errorCode, $function);
    }

    /**
     * getAlbum
     *
     * Returns details for an album.
     * https://www.subsonic.org/pages/api.jsp#getalbum
     * @param array<string, mixed> $input
     */
    public function getalbum(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $album = self::getAmpacheObject($sub_id);
        if (!$album instanceof Album || $album->isNew()) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addAlbumID3($response, $album, true);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addAlbumID3($response, $album, true);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getAlbumInfo
     *
     * Returns album info.
     * https://www.subsonic.org/pages/api.jsp#getalbuminfo
     * @param array<string, mixed> $input
     */
    public function getalbuminfo(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $album = self::getAmpacheObject($sub_id);
        if (!$album instanceof Album || $album->isNew()) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $info   = Recommendation::get_album_info($album->getId());
        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addAlbumInfo($response, $info, $album);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addAlbumInfo($response, $info, $album);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getAlbumInfo2
     *
     * Returns album info.
     * https://www.subsonic.org/pages/api.jsp#getalbuminfo2
     * @param array<string, mixed> $input
     */
    public function getalbuminfo2(array $input, User $user): void
    {
        $this->getalbuminfo($input, $user);
    }

    /**
     * getAlbumList
     *
     * Returns a list of random, newest, highest rated etc. albums.
     * https://www.subsonic.org/pages/api.jsp#getalbumlist
     * @param array<string, mixed> $input
     */
    public function getalbumlist(array $input, User $user): void
    {
        $type = $this->responseHandler->checkParameter($input, 'type', __FUNCTION__);
        if ($type === false) {
            return;
        }

        if ($type === 'byGenre' && !$this->responseHandler->checkParameter($input, 'genre', __FUNCTION__)) {
            return;
        }

        $albums = $this->_albumList($input, $user, (string) $type);
        if ($albums === null) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addAlbumList($response, $albums);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addAlbumList($response, $albums);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getAlbumList2
     *
     * Returns a list of random, newest, highest rated etc. albums.
     * https://www.subsonic.org/pages/api.jsp#getalbumlist2
     * @param array<string, mixed> $input
     */
    public function getalbumlist2(array $input, User $user): void
    {
        $type = $this->responseHandler->checkParameter($input, 'type', __FUNCTION__);
        if ($type === false) {
            return;
        }

        if ($type === 'byGenre' && !$this->responseHandler->checkParameter($input, 'genre', __FUNCTION__)) {
            return;
        }

        $albums = $this->_albumList($input, $user, (string) $type);
        if ($albums === null) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addAlbumList2($response, $albums);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addAlbumList2($response, $albums);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getArtist
     *
     * Returns details for an artist.
     * https://www.subsonic.org/pages/api.jsp#getartist
     * @param array<string, mixed> $input
     */
    public function getartist(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $artist = new Artist(self::getAmpacheId($sub_id));
        if ($artist->isNew()) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addArtistID3($response, $artist, true);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addArtistWithAlbumsID3($response, $artist);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getArtistInfo
     *
     * Returns artist info.
     * https://www.subsonic.org/pages/api.jsp#getartistinfo
     * @param array<string, mixed> $input
     */
    public function getartistinfo(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $artist = self::getAmpacheObject($sub_id);
        if (!$artist instanceof Artist || $artist->isNew()) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $count             = (int) ($input['count'] ?? 20);
        $includeNotPresent = make_bool($input['includeNotPresent'] ?? false);

        $info     = Recommendation::get_artist_info($artist->getId());
        $similars = Recommendation::get_artists_like($artist->getId(), $count, !$includeNotPresent);
        $format   = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addArtistInfo($response, $info, $artist, $similars);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addArtistInfo($response, $info, $artist, $similars);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getArtistInfo2
     *
     * Returns artist info.
     * https://www.subsonic.org/pages/api.jsp#getartistinfo2
     * @param array<string, mixed> $input
     */
    public function getartistinfo2(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $artist = self::getAmpacheObject($sub_id);
        if (!$artist instanceof Artist || $artist->isNew()) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $count             = (int) ($input['count'] ?? 20);
        $includeNotPresent = make_bool($input['includeNotPresent'] ?? false);

        $info     = Recommendation::get_artist_info($artist->getId());
        $similars = Recommendation::get_artists_like($artist->getId(), $count, !$includeNotPresent);
        $format   = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addArtistInfo2($response, $info, $artist, $similars);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addArtistInfo2($response, $info, $artist, $similars);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getArtists
     *
     * Returns all artists.
     * https://www.subsonic.org/pages/api.jsp#getartists
     * @param array<string, mixed> $input
     */
    public function getartists(array $input, User $user): void
    {
        $catalogs = $this->musicFolderResolver->musicFolders($input, $user);

        $user_id = $user->id;
        // an empty catalog list makes get_id_arrays return everything, so only ask when there is something to ask for
        $artists = ($catalogs === [])
            ? []
            : Artist::get_id_arrays($catalogs, ((bool) Preference::get_by_user($user_id, 'subsonic_force_album_artist') === true));

        // one flag read for the whole index instead of one per artist
        Userflag::build_cache('artist', array_column($artists, 'id'));

        $format  = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addArtists($response, $artists);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addArtists($response, $artists);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getAvatar
     *
     * Returns the avatar (personal image) for a user.
     * https://www.subsonic.org/pages/api.jsp#getavatar
     * @param array<string, mixed> $input
     */
    public function getavatar(array $input, User $user): void
    {
        $username = $this->responseHandler->checkParameter($input, 'username', __FUNCTION__);
        if ($username === false) {
            return;
        }

        if ($user->access === 100 || $user->username == $username) {
            if ($user->username == $username) {
                $update_user = $user;
            } else {
                $update_user = User::get_from_username((string) $username);
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
                $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            $this->responseHandler->errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * @param array<string, mixed> $input
     */
    public function getbookmarks(array $input, User $user): void
    {
        $this->bookmarkHandler->getbookmarks($input, $user);
    }

    /**
     * getCaptions
     *
     * Returns captions (subtitles) for a video.
     * https://www.subsonic.org/pages/api.jsp#getcaptions
     * @param array<string, mixed> $input
     */
    public function getcaptions(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $video = self::getAmpacheObject($sub_id);
        if (!$video instanceof Video || $video->isNew()) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        // Captions are .srt files sitting beside the video file; the one with no language suffix is the default
        $captions = null;
        foreach ($video->get_subtitles() as $subtitle) {
            if ($captions === null || $subtitle['lang_code'] === '__') {
                $captions = $subtitle;
            }
            if ($subtitle['lang_code'] === '__') {
                break;
            }
        }

        $body = ($captions !== null && is_readable($captions['file']))
            ? file_get_contents($captions['file'])
            : false;
        if ($body === false) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        // What is stored is always SubRip, so `vtt` is answered by converting it rather than by finding another file
        if (strtolower((string) ($input['format'] ?? 'srt')) === 'vtt') {
            header('Content-Type: text/vtt; charset=UTF-8');
            echo $this->_srtToVtt($body);

            return;
        }

        header('Content-Type: application/x-subrip; charset=UTF-8');
        echo $body;
    }

    /**
     * @param array<string, mixed> $input
     */
    public function getchatmessages(array $input, User $user): void
    {
        $this->chatHandler->getchatmessages($input, $user);
    }

    /**
     * getCoverArt
     *
     * Returns a cover art image.
     * https://www.subsonic.org/pages/api.jsp#getcoverart
     * @param array<string, mixed> $input
     */
    public function getcoverart(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        // replace additional prefixes
        $sub_id = preg_replace('/^[a-z]+-([a-z]{2}-)/', '$1', $sub_id);

        $object_id   = self::getAmpacheId($sub_id);
        $object_type = self::getAmpacheType($sub_id);
        if (
            !$object_id
            || empty($object_type)
        ) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

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
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        // clients each pick their own pixel count, and every distinct one is stored and kept, so snap
        // onto a size the interface already makes. Larger than anything we make serves the original.
        $size = (isset($input['size']) && is_numeric($input['size']))
            ? (Art::canonical_size((int) $input['size']) ?? 'original')
            : 'original';

        // we have the art so lets show it
        header("Access-Control-Allow-Origin: *");
        if (is_int($size) && AmpConfig::get('resize_images')) {
            $out_size           = [];
            $out_size['width']  = $size;
            $out_size['height'] = $size;
            $thumb              = $art->get_thumb($out_size);
            if (!empty($thumb) && isset($thumb['thumb']) && isset($thumb['thumb_mime'])) {
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
     */
    public function getgenres(array $input, User $user): void
    {
        unset($user);

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addGenres($response, Tag::get_tags('song'));
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addGenres($response, Tag::get_tags('song'));
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getIndexes
     *
     * Returns an indexed structure of all artists.
     * https://www.subsonic.org/pages/api.jsp#getindexes
     * @param array<string, mixed> $input
     */
    public function getindexes(array $input, User $user): void
    {
        set_time_limit(300);

        $ifModifiedSince = $input['ifModifiedSince'] ?? '';
        $catalogs        = $this->musicFolderResolver->musicFolders($input, $user);

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
            if (!empty($ifModifiedSince) && $clastmodified > (((int) $ifModifiedSince) / 1000)) {
                $fcatalogs[] = (int) $catalogid;
            }
        }
        if (empty($ifModifiedSince)) {
            $fcatalogs = $catalogs;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            if (count($fcatalogs) > 0) {
                $children = $this->folderRepository->getCatalogRootChildren($fcatalogs, $user->getId());
                $response = $this->subsonicXmlData->addFolderIndexes($response, $children, $lastmodified);
            }
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            if (count($fcatalogs) > 0) {
                $children = $this->folderRepository->getCatalogRootChildren($fcatalogs, $user->getId());
                $response = $this->subsonicJsonData->addFolderIndexes($response, $children, $lastmodified);
            }
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function getinternetradiostations(array $input, User $user): void
    {
        $this->internetRadioHandler->getinternetradiostations($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function getlicense(array $input, User $user): void
    {
        $this->systemHandler->getlicense($input, $user);
    }

    /**
     * getLyrics
     *
     * Searches for and returns lyrics for a given song.
     * https://www.subsonic.org/pages/api.jsp#getlyrics
     * @param array<string, mixed> $input
     */
    public function getlyrics(array $input, User $user): void
    {
        $artist = (string) ($input['artist'] ?? '');
        $title  = (string) ($input['title'] ?? '');

        if (empty($artist) && empty($title)) {
            $this->responseHandler->errorOutput($input, self::SSERROR_MISSINGPARAM, __FUNCTION__);

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
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addLyrics($response, $artist, $title, $song);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addLyrics($response, $artist, $title, $song);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getLyricsBySongId [OS] REMOVED
     * @param array<string, mixed> $input
     */
    public function getlyricsbysongid(array $input, User $user): void
    {
        unset($user);

        $this->responseHandler->errorOutput($input, self::SSERROR_APIVERSION_SERVER, __FUNCTION__);
    }

    /**
     * getMusicDirectory
     *
     * Returns a listing of all files in a music directory.
     * https://www.subsonic.org/pages/api.jsp#getmusicdirectory
     * @param array<string, mixed> $input
     */
    public function getmusicdirectory(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $object_id = self::getAmpacheId($sub_id);
        if (!$object_id) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $object = self::getAmpacheObject($sub_id);
        if (!$object) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        if ($object instanceof Album || $object instanceof Artist || $object instanceof Catalog || $object instanceof Folder) {
            $format = (string) ($input['f'] ?? 'xml');
            if ($format === 'xml') {
                $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
                $response = $this->subsonicXmlData->addDirectory($response, $object, $user->getId());
            } else {
                $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
                $response = $this->subsonicJsonData->addDirectory($response, $object, $user->getId());
            }
            $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
        } else {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
        }
    }

    /**
     * getMusicFolders
     *
     * Returns all configured top-level music folders.
     * https://www.subsonic.org/pages/api.jsp#getmusicfolders
     * @param array<string, mixed> $input
     */
    public function getmusicfolders(array $input, User $user): void
    {
        $catalogs = $user->get_catalogs('music');
        $format   = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addMusicFolders($response, $catalogs);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addMusicFolders($response, $catalogs);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getNewestPodcasts
     *
     * Returns the most recently published Podcast episodes.
     * https://www.subsonic.org/pages/api.jsp#getnewestpodcasts
     * @param array<string, mixed> $input
     */
    public function getnewestpodcasts(array $input, User $user): void
    {
        unset($user);
        $count = (int) ($input['count'] ?? AmpConfig::get('podcast_new_download'));
        if (!AmpConfig::get('podcast')) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $episodes = Catalog::get_newest_podcasts($count);
        $format   = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addNewestPodcasts($response, $episodes);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addNewestPodcasts($response, $episodes);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getNowPlaying
     *
     * Returns what is currently being played by all users.
     * https://www.subsonic.org/pages/api.jsp#getnowplaying
     * @param array<string, mixed> $input
     */
    public function getnowplaying(array $input, User $user): void
    {
        unset($user);
        $data   = Stream::get_now_playing();
        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addNowPlaying($response, $data);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addNowPlaying($response, $data);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function getopensubsonicextensions(array $input, User $user): void
    {
        $this->systemHandler->getopensubsonicextensions($input, $user);
    }

    /**
     * getPlaylist
     *
     * Returns a listing of files in a saved playlist.
     * https://www.subsonic.org/pages/api.jsp#getplaylist
     * @param array<string, mixed> $input
     */
    public function getplaylist(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $playlist = self::getAmpacheObject($sub_id);
        if (
            (!($playlist instanceof Playlist || $playlist instanceof Search))
            || $playlist->isNew()
        ) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        // a private list you neither own nor collaborate on is not yours to read
        if ($playlist->type !== 'public' && !$playlist->has_collaborate($user)) {
            $this->responseHandler->errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addPlaylist($response, $playlist, true);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addPlaylist($response, $playlist, true);
        }

        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getPlaylists
     *
     * Returns all playlists a user is allowed to play.
     * https://www.subsonic.org/pages/api.jsp#getplaylists
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

        // the serializer reads each playlist row and its art, so warm both in one pass
        Playlist::build_cache(Playlist::split_mixed_ids($results)['playlist']);

        $format  = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addPlaylists($response, $user, $results);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addPlaylists($response, $user, $results);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getPlayQueue
     *
     * Returns the state of the play queue for this user.
     * https://www.subsonic.org/pages/api.jsp#getplayqueue
     * @param array<string, mixed> $input
     */
    public function getplayqueue(array $input, User $user): void
    {
        $client    = scrub_in((string) ($input['c'] ?? 'Subsonic'));
        $playQueue = new User_Playlist($user->id, $client);

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addPlayQueue($response, $playQueue, (string) $user->username);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addPlayQueue($response, $playQueue, (string) $user->username);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getPlayQueueByIndex [OS] REMOVED
     * @param array<string, mixed> $input
     */
    public function getplayqueuebyindex(array $input, User $user): void
    {
        unset($user);

        $this->responseHandler->errorOutput($input, self::SSERROR_APIVERSION_SERVER, __FUNCTION__);
    }

    /**
     * getPodcastEpisode [OS] REMOVED
     * @param array<string, mixed> $input
     */
    public function getpodcastepisode(array $input, User $user): void
    {
        unset($user);

        $this->responseHandler->errorOutput($input, self::SSERROR_APIVERSION_SERVER, __FUNCTION__);
    }

    /**
     * getPodcasts
     *
     * Returns all Podcast channels the server subscribes to, and (optionally) their episodes.
     * https://www.subsonic.org/pages/api.jsp#getpodcasts
     * @param array<string, mixed> $input
     */
    public function getpodcasts(array $input, User $user): void
    {
        $sub_id          = $input['id'] ?? null;
        $includeEpisodes = make_bool($input['includeEpisodes'] ?? true);

        if (!AmpConfig::get(ConfigurationKeyEnum::PODCAST)) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
        }

        $podcast_id = ($sub_id)
            ? self::getAmpacheId($sub_id)
            : null;
        if ($podcast_id) {
            $podcast = $this->podcastRepository->findById($podcast_id);
            if ($podcast === null) {
                $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

                return;
            }

            $podcasts = [$podcast];
        } else {
            $podcasts = Catalog::get_podcasts(User::get_user_catalogs($user->id));
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addPodcasts($response, $podcasts, $includeEpisodes, $sub_id);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addPodcasts($response, $podcasts, $includeEpisodes, $sub_id);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getRandomSongs
     *
     * Returns random songs matching the given criteria.
     * https://www.subsonic.org/pages/api.jsp#getrandomsongs
     * @param array<string, mixed> $input
     */
    public function getrandomsongs(array $input, User $user): void
    {
        $size = (int) ($input['size'] ?? 10);

        $genre         = $input['genre'] ?? '';
        $fromYear      = $input['fromYear'] ?? null;
        $toYear        = $input['toYear'] ?? null;
        $sub_id        = $input['musicFolderId'] ?? null;
        $musicFolderId = ($sub_id) ? (int) self::getAmpacheId($sub_id) : 0;

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
            $type = self::getAmpacheType($sub_id);
            if ($type === 'artist') {
                $artist   = new Artist($musicFolderId);
                $finput   = $artist->get_fullname();
                $operator = 4;
                $ftype    = "artist";
            } elseif ($type === 'album') {
                $album    = new Album($musicFolderId);
                $finput   = $album->get_fullname(true);
                $operator = 4;
                $ftype    = "artist";
            } else {
                // a real music folder must be one the user can browse
                $finput   = $this->musicFolderResolver->musicFolderId($input, $user);
                $operator = 0;
                $ftype    = "catalog";
            }

            $data['rule_' . $count . '_input']    = $finput;
            $data['rule_' . $count . '_operator'] = $operator;
            $data['rule_' . $count]               = $ftype;
            ++$count;
        }
        if ($count > 0) {
            $songs = $this->random->advanced('song', $data);
        } else {
            $songs = Random::get_default($size, $user);
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addRandomSongs($response, $songs);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addRandomSongs($response, $songs);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function getscanstatus(array $input, User $user): void
    {
        $this->systemHandler->getscanstatus($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function getshares(array $input, User $user): void
    {
        $this->shareHandler->getshares($input, $user);
    }

    /**
     * getSimilarSongs
     *
     * Returns a random collection of songs from the given artist and similar artists.
     * https://www.subsonic.org/pages/api.jsp#getsimilarsongs
     * @param array<string, mixed> $input
     */
    public function getsimilarsongs(array $input, User $user, string $elementName = 'similarSongs'): void
    {
        unset($user);
        if (!AmpConfig::get('show_similar')) {
            debug_event(self::class, $elementName . ': Enable: show_similar', 4);
            $this->responseHandler->errorOutput($input, self::SSERROR_GENERIC, __FUNCTION__);

            return;
        }

        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }
        $object_id = self::getAmpacheId($sub_id);
        if (!$object_id) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $count = (int) ($input['count'] ?? 50);
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
                        $artist_songs = $this->songRepository->getRandomByArtist($artist);
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

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            switch ($elementName) {
                case 'similarSongs':
                    $response = $this->subsonicXmlData->addSimilarSongs($response, $songs);
                    break;
                case 'similarSongs2':
                    $response = $this->subsonicXmlData->addSimilarSongs2($response, $songs);
                    break;
            }
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            switch ($elementName) {
                case 'similarSongs':
                    $response = $this->subsonicJsonData->addSimilarSongs($response, $songs);
                    break;
                case 'similarSongs2':
                    $response = $this->subsonicJsonData->addSimilarSongs2($response, $songs);
                    break;
            }
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getSimilarSongs2
     *
     * Returns a random collection of songs from the given artist and similar artists.
     * https://www.subsonic.org/pages/api.jsp#getsimilarsongs2
     * @param array<string, mixed> $input
     */
    public function getsimilarsongs2(array $input, User $user): void
    {
        $this->getsimilarsongs($input, $user, "similarSongs2");
    }

    /**
     * getSong
     *
     * Returns details for a song.
     * https://www.subsonic.org/pages/api.jsp#getsong
     * @param array<string, mixed> $input
     */
    public function getsong(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $song_id = self::getAmpacheId($sub_id);
        if (!$song_id) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $song = new Song($song_id);
        if ($song->isNew() || !$song->enabled) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addSong($response, $song);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addSong($response, $song_id);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getSongsByGenre
     *
     * Returns songs in a given genre.
     * https://www.subsonic.org/pages/api.jsp#getsongsbygenre
     * @param array<string, mixed> $input
     */
    public function getsongsbygenre(array $input, User $user): void
    {
        $genre = $this->responseHandler->checkParameter($input, 'genre', __FUNCTION__);
        if ($genre === false) {
            return;
        }

        $count         = (int) ($input['count'] ?? 0);
        $offset        = (int) ($input['offset'] ?? 0);
        $musicFolderId = $this->musicFolderResolver->musicFolderId($input, $user);

        $tag = Tag::construct_from_name($genre);
        if ($tag->isNew()) {
            $songs = [];
        } else {
            $songs = Tag::get_tag_objects("song", $tag->id, $count, $offset, $musicFolderId);
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addSongsByGenre($response, $songs);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addSongsByGenre($response, $songs);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function getstarred(array $input, User $user, string $elementName = 'starred'): void
    {
        $this->ratingHandler->getstarred($input, $user, $elementName);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function getstarred2(array $input, User $user): void
    {
        $this->ratingHandler->getstarred2($input, $user);
    }

    /**
     * getTopSongs
     *
     * Returns top songs for the given artist.
     * https://www.subsonic.org/pages/api.jsp#gettopsongs
     * @param array<string, mixed> $input
     */
    public function gettopsongs(array $input, User $user): void
    {
        unset($user);
        $name = $this->responseHandler->checkParameter($input, 'artist', __FUNCTION__);
        if ($name === false) {
            return;
        }

        $artist = $this->artistRepository->findByName(urldecode((string) $name));
        $count  = (int) ($input['count'] ?? 50);
        $songs  = [];
        if ($count < 1) {
            $count = 50;
        }
        if ($artist) {
            $songs = $this->songRepository->getTopSongsByArtist(
                $artist,
                $count
            );
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addTopSongs($response, $songs);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addTopSongs($response, $songs);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function getuser(array $input, User $user): void
    {
        $this->userHandler->getuser($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function getusers(array $input, User $user): void
    {
        $this->userHandler->getusers($input, $user);
    }

    /**
     * getVideoInfo
     *
     * Returns details for a video.
     * https://www.subsonic.org/pages/api.jsp#getvideoinfo
     * @param array<string, mixed> $input
     */
    public function getvideoinfo(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $video_id = self::getAmpacheId($sub_id);
        if (!$video_id) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addVideoInfo($response, $video_id);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addVideoInfo($response, $video_id);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getVideos
     *
     * Returns all video files.
     * https://www.subsonic.org/pages/api.jsp#getvideos
     * @param array<string, mixed> $input
     */
    public function getvideos(array $input, User $user): void
    {
        unset($user);

        $videos = Catalog::get_videos();
        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addVideos($response, $videos);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addVideos($response, $videos);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * hls
     *
     * Downloads a given media file.
     * https://www.subsonic.org/pages/api.jsp#hls
     * @param array<string, mixed> $input
     */
    public function hls(array $input, User $user): void
    {
        unset($user);
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $object_id = self::getAmpacheId($sub_id);
        if (!$object_id) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

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
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $media['object_id'] = $object_id;
        $medias             = [];
        $medias[]           = $media;
        $stream             = new Stream_Playlist();
        $additional_params  = '';
        if ($bitRate) {
            // Subsonic bitRate is kbps, convert to bps
            $additional_params .= '&bitrate=' . ($bitRate * 1000);
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
     */
    public function jukeboxcontrol(array $input, User $user): void
    {
        $action = $this->responseHandler->checkParameter($input, 'action', __FUNCTION__);
        if ($action === false) {
            return;
        }

        // driving the server's own playback is gated like the native localplay method, nothing checked it here
        if (
            !AmpConfig::get('allow_localplay_playback')
            || $user->access < (int) (AmpConfig::get('localplay_level') ?? AccessLevelEnum::ADMIN->value)
        ) {
            $this->responseHandler->errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);

            return;
        }

        $object_id  = $input['id'] ?? [];
        $controller = AmpConfig::get('localplay_controller', '');
        $localplay  = ($controller) ? new LocalPlay($controller) : null;
        $return     = false;
        if (empty($controller) || empty($localplay) || empty($localplay->type) || !$localplay->connect()) {
            debug_event(self::class, 'Error Localplay controller: ' . (empty($controller) ? 'Is not set' : $controller), 3);
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

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
                    if ($localplay->skip((int) $input['index'])) {
                        $return = $localplay->play();
                    }
                } elseif (isset($input['offset'])) {
                    debug_event(self::class, 'Skip with offset is not supported on JukeboxControl.', 5);
                } else {
                    $this->responseHandler->errorOutput($input, self::SSERROR_MISSINGPARAM, __FUNCTION__);

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
                    $return = $localplay->delete_track((int) $input['index']);
                } else {
                    $this->responseHandler->errorOutput($input, self::SSERROR_MISSINGPARAM, __FUNCTION__);
                }
                break;
            case 'shuffle':
                $return = $localplay->random(true);
                break;
            case 'setGain':
                $return = $localplay->volume_set(((float) $input['gain']) * 100);
                break;
        }

        if ($return) {
            $format = (string) ($input['f'] ?? 'xml');
            if ($format === 'xml') {
                $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
                if ($action == 'get') {
                    $response = $this->subsonicXmlData->addJukeboxPlaylist($response, $localplay);
                } else {
                    $response = $this->subsonicXmlData->addJukeboxStatus($response, $localplay);
                }
            } else {
                $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
                if ($action == 'get') {
                    $response = $this->subsonicJsonData->addJukeboxPlaylist($response, $localplay);
                } else {
                    $response = $this->subsonicJsonData->addJukeboxStatus($response, $localplay);
                }
            }
            $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
        }
    }

    /**
     * @param array<string, mixed> $input
     */
    public function ping(array $input, User $user): void
    {
        $this->systemHandler->ping($input, $user);
    }

    /**
     * refreshPodcasts
     *
     * Requests the server to check for new Podcast episodes.
     * https://www.subsonic.org/pages/api.jsp#refreshpodcasts
     * @param array<string, mixed> $input
     */
    public function refreshpodcasts(array $input, User $user): void
    {
        if (AmpConfig::get('podcast') && $user->access >= 75) {
            $podcasts = Catalog::get_podcasts(User::get_user_catalogs($user->id));

            $podcastSyncer = $this->podcastSyncer;

            foreach ($podcasts as $podcast) {
                $podcastSyncer->sync($podcast, true);
            }
            $this->responseHandler->responseOutput($input, __FUNCTION__);
        } else {
            $this->responseHandler->errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * savePlayQueue
     *
     * Saves the state of the play queue for this user.
     * https://www.subsonic.org/pages/api.jsp#saveplayqueue
     * @param array<string, mixed> $input
     */
    public function saveplayqueue(array $input, User $user): void
    {
        // current required by Subsonic https://opensubsonic.netlify.app/docs/endpoints/saveplayqueue/
        if (isset($input['current'])) {
            $current = (string) $input['current'];
        } else {
            $this->responseHandler->errorOutput($input, self::SSERROR_MISSINGPARAM, __FUNCTION__);

            return;
        }
        $id_list  = $input['id'] ?? '';
        $position = (array_key_exists('position', $input))
            ? (int) (((int) $input['position']) / 1000)
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
                $media instanceof Media
                && $media->isNew() === false
                && isset($media->time)
            ) {
                // a client can send an out-of-range resume position; keep the now_playing row garbage-collectable
                $position       = max(0, min($position, (int) $media->time));
                $playqueue_time = (int) User::get_user_data($user->id, 'playqueue_time', 0)['playqueue_time'];
                // wait a few seconds before smashing out play times
                if ($playqueue_time < ($time - 2)) {
                    $previous = Stats::get_last_play($user_id, $client);
                    $type     = self::getAmpacheType($current);
                    // long pauses might cause your now_playing to hide
                    Stream::garbage_collection();
                    Stream::insert_now_playing($media->getId(), $user_id, ($media->time - $position), (string) $user->username, $type, ($time - $position));

                    if ($previous['object_id'] && $previous['object_id'] == $media->getId()) {
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
                $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

                return;
            }

            $sub_ids = (is_array($id_list))
                ? $id_list
                : [$id_list];
            $playlist = $this->_getAmpacheIdArrays($sub_ids);

            // clear the old list
            $playQueue->clear();
            // set the new items
            $playQueue->add_items($playlist, $time);

            if (
                isset($type)
                && isset($media->id)
            ) {
                $playQueue->set_current_object($type, $media->id, $position);
            }

            // subsonic cares about queue dates so set them (and set them together)
            User::set_user_data($user_id, 'playqueue_time', $time);
            User::set_user_data($user_id, 'playqueue_client', $client);
        }

        $this->responseHandler->responseOutput($input, __FUNCTION__);
    }

    /**
     * savePlayQueueByIndex [OS] REMOVED
     * @param array<string, mixed> $input
     */
    public function saveplayqueuebyindex(array $input, User $user): void
    {
        unset($user);

        $this->responseHandler->errorOutput($input, self::SSERROR_APIVERSION_SERVER, __FUNCTION__);
    }

    /**
     * scrobble
     *
     * Registers the local playback of one or more media files.
     * https://www.subsonic.org/pages/api.jsp#scrobble
     * @param array<string, mixed> $input
     */
    public function scrobble(array $input, User $user): void
    {
        $sub_ids = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_ids === false) {
            return;
        }

        $submission = (array_key_exists('submission', $input) && (strtolower($input['submission']) === 'true' || $input['submission'] === '1'));
        $client     = scrub_in((string) ($input['c'] ?? 'Subsonic'));

        if (!is_array($sub_ids)) {
            $rid     = [];
            $rid[]   = $sub_ids;
            $sub_ids = $rid;
        }
        $playqueue_time = (int) User::get_user_data($user->id, 'playqueue_time', 0)['playqueue_time'];
        $now_time       = time();
        // don't scrobble after setting the play queue too quickly
        if ($playqueue_time < ($now_time - 2)) {
            // long pauses might cause your now_playing to hide, and the sweep is the same for every id
            Stream::garbage_collection();
            foreach ($sub_ids as $sub_id) {
                $time = (isset($input['time']))
                    ? (int) (((int) $input['time']) / 1000)
                    : time();
                $previous  = Stats::get_last_play($user->id, $client, $time);
                $prev_obj  = $previous['object_id'] ?: 0;
                $prev_date = $previous['date'];
                $type      = self::getAmpacheType($sub_id);
                $media     = self::getAmpacheObject((string) $sub_id);
                if (!$media instanceof Media || !isset($media->time) || !isset($media->id)) {
                    continue;
                }

                Stream::insert_now_playing((int) $media->id, $user->id, $media->time, (string) $user->username, $type, $time);
                // submission is true: stream finished. Record the play locally
                // (set_played is dedup-guarded) and notify scrobble plugins.
                if ($submission && $media->id && ($prev_obj != $media->id) && (($time - $prev_date) > 5)) {
                    debug_event(self::class, $user->username . ' scrobbled: {' . $media->id . '} at ' . $time, 5);
                    if ($media->set_played($user->id, $client, [], $time) && get_class($media) == Song::class) {
                        User::save_mediaplay($user, $media);
                    }
                }
                // Submission is false and not a repeat. let repeats go through to saveplayqueue
                if ((!$submission) && $media->id && ($prev_obj != $media->id) && (($time - $prev_date) > 5)) {
                    $media->set_played($user->id, $client, [], $time);
                }
            }
        }

        $this->responseHandler->responseOutput($input, __FUNCTION__);
    }

    /**
     * search
     *
     * https://www.subsonic.org/pages/api.jsp#search
     * @param array<string, mixed> $input
     */
    public function search(array $input, User $user): void
    {
        $data = [
            'type' => 'song',
            'operator' => 'and'
        ];

        $rule_count = 1;

        $artist = $input['artist'] ?? '';
        if ($artist) {
            $data['rule_' . $rule_count]               = 'artist';
            $data['rule_' . $rule_count . '_operator'] = 2; // starts with
            $data['rule_' . $rule_count . '_input']    = $artist;
            $rule_count++;
        }

        $album = $input['album'] ?? '';
        if ($album) {
            $data['rule_' . $rule_count]               = 'album';
            $data['rule_' . $rule_count . '_operator'] = 2; // starts with
            $data['rule_' . $rule_count . '_input']    = $album;
            $rule_count++;
        }

        $title = $input['title'] ?? '';
        if ($title) {
            $data['rule_' . $rule_count]               = 'title';
            $data['rule_' . $rule_count . '_operator'] = 2; // starts with
            $data['rule_' . $rule_count . '_input']    = $title;
            $rule_count++;
        }

        $anywhere = $input['any'] ?? '';
        if ($anywhere) {
            $data['rule_' . $rule_count]               = 'anywhere';
            $data['rule_' . $rule_count . '_operator'] = 2; // starts with
            $data['rule_' . $rule_count . '_input']    = $anywhere;
            $rule_count++;
        }

        $newerThan = (int) ($input['newerThan'] ?? 0);
        if ($newerThan > 0) {
            $data['rule_' . $rule_count]               = 'added';
            $data['rule_' . $rule_count . '_operator'] = 1; // after
            $data['rule_' . $rule_count . '_input']    = date('Y-m-d\TH:i', (int) ($newerThan / 1000)); // e.g. 2025-08-12T10:15
        }

        $search_sql = Search::prepare($data, $user);
        $query      = Search::query($search_sql);
        $results    = $query['results'];
        $total      = $query['count'];

        $offset  = (int) ($input['offset'] ?? 0);
        $count   = (int) ($input['count'] ?? 20);
        $results = array_slice($results, $offset, $count);

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addSearchResult($response, $results, $offset, $total);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addSearchResult($response, $results, $offset, $total);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * search2
     *
     * Returns a listing of files matching the given search criteria. Supports paging through the result.
     * https://www.subsonic.org/pages/api.jsp#search2
     * @param array<string, mixed> $input
     */
    public function search2(array $input, User $user): void
    {
        $query   = $input['query'] ?? '';
        $results = $this->_search($query, $input, $user);

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addSearchResult2($response, $results['artists'], $results['albums'], $results['songs']);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addSearchResult2($response, $results['artists'], $results['albums'], $results['songs']);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * search3
     *
     * Returns albums, artists and songs matching the given search criteria. Supports paging through the result.
     * https://www.subsonic.org/pages/api.jsp#search3
     * @param array<string, mixed> $input
     */
    public function search3(array $input, User $user): void
    {
        // query required by Subsonic https://opensubsonic.netlify.app/docs/endpoints/search3/
        if (isset($input['query'])) {
            $query = (string) $input['query'];
        } else {
            $this->responseHandler->errorOutput($input, self::SSERROR_MISSINGPARAM, __FUNCTION__);

            return;
        }
        $results = $this->_search($query, $input, $user);

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->responseHandler->addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addSearchResult3($response, $results['artists'], $results['albums'], $results['songs']);
        } else {
            $response = $this->responseHandler->addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addSearchResult3($response, $results['artists'], $results['albums'], $results['songs']);
        }
        $this->responseHandler->responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function setrating(array $input, User $user): void
    {
        $this->ratingHandler->setrating($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function star(array $input, User $user): void
    {
        $this->ratingHandler->star($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function startscan(array $input, User $user): void
    {
        $this->systemHandler->startscan($input, $user);
    }

    /**
     * stream
     *
     * Streams a given media file.
     * https://www.subsonic.org/pages/api.jsp#stream
     * @param array<string, mixed> $input
     */
    public function stream(array $input, User $user): void
    {
        $sub_id = $this->responseHandler->checkParameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $object = self::getAmpacheObject($sub_id);
        if (($object instanceof Song || $object instanceof Podcast_Episode) === false) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $maxBitRate    = (int) ($input['maxBitRate'] ?? 0);
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
            $params .= '&bitrate=' . ($maxBitRate * 1000); // Subsonic uses kbps, convert to bps
        }
        if ($timeOffset) {
            $params .= '&frame=' . $timeOffset;
        }

        // No scrobble for streams using opensubsonic https://www.subsonic.org/pages/api.jsp#stream/
        if (AmpConfig::get('subsonic_always_download')) {
            $params .= '&cache=1';
        }

        $this->_follow_stream($object->play_url($params, 'api', function_exists('curl_version'), $user->id, $user->streamtoken));
    }

    /**
     * @param array<string, mixed> $input
     */
    public function tokeninfo(array $input, User $user): void
    {
        $this->systemHandler->tokeninfo($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function unstar(array $input, User $user): void
    {
        $this->ratingHandler->unstar($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function updateinternetradiostation(array $input, User $user): void
    {
        $this->internetRadioHandler->updateinternetradiostation($input, $user);
    }

    /**
     * updatePlaylist
     *
     * Updates a playlist. Only the owner of a playlist is allowed to update it.
     * https://www.subsonic.org/pages/api.jsp#updateplaylist
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

        $object = self::getAmpacheObject($sub_id);
        if (!$object) {
            $this->responseHandler->errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        if ($object instanceof Playlist) {
            if (!$object->has_access($user)) {
                $this->responseHandler->errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);

                return;
            }
            if (is_string($songIdToAdd)) {
                $songIdToAdd = explode(',', $songIdToAdd);
            }
            if (is_string($songIndexToRemove)) {
                $songIndexToRemove = explode(',', $songIndexToRemove);
            }
            $this->_updatePlaylist($object->getId(), $name, $songIdToAdd, $songIndexToRemove, $public);

            $this->responseHandler->responseOutput($input, __FUNCTION__);
        } else {
            $this->responseHandler->errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * @param array<string, mixed> $input
     */
    public function updateshare(array $input, User $user): void
    {
        $this->shareHandler->updateshare($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function updateuser(array $input, User $user): void
    {
        $this->userHandler->updateuser($input, $user);
    }

    /**
     * _albumList
     * @param array<string, mixed> $input
     * @return int[]|null
     */
    private function _albumList(array $input, User $user, string $type): ?array
    {
        $size          = (int) ($input['size'] ?? 10);
        $offset        = (int) ($input['offset'] ?? 0);
        $musicFolderId = $this->musicFolderResolver->musicFolderId($input, $user);
        $catalogFilter = (AmpConfig::get('catalog_disable') || AmpConfig::get('catalog_filter'));

        // hide ratings and flags for other users if single user data is enabled
        $by_user     = (bool) Preference::get_by_user($user->id, 'subsonic_single_user_data') === true;
        $output_user = ($by_user)
            ? $user
            : null;

        // Get albums from all catalogs by default
        $catalogs = ($catalogFilter)
            ? $user->get_catalogs('music')
            : null;
        if ($musicFolderId !== 0) {
            $catalogs = $this->musicFolderResolver->musicFolders($input, $user);
        }
        $albums = null;
        switch ($type) {
            case 'random':
                $albums = $this->albumRepository->getRandom(
                    $user->id,
                    $size,
                    $musicFolderId
                );
                break;
            case 'newest':
                $albums = Stats::get_newest('album', $size, $offset, $musicFolderId, $user);
                break;
            case 'highest':
                $albums = Rating::get_highest('album', $size, $offset, $output_user?->id, $by_user, $musicFolderId);
                break;
            case 'frequent':
                $albums = Stats::get_top('album', $size, 0, $offset, $output_user, false, 0, 0, $by_user, $musicFolderId);
                break;
            case 'recent':
                $albums = Stats::get_recent('album', $size, $offset, $output_user, true, $musicFolderId);
                break;
            case 'starred':
                $albums = Userflag::get_latest('album', $output_user, $size, $offset, 0, 0, $by_user, $musicFolderId);
                break;
            case 'alphabeticalByName':
                // an empty catalog list means everything to these calls, so a filtered request must bail out first
                $albums = (empty($catalogs) && ($catalogFilter || $musicFolderId !== 0))
                    ? []
                    : Catalog::get_albums($size, $offset, $catalogs);
                break;
            case 'alphabeticalByArtist':
                $albums = (empty($catalogs) && ($catalogFilter || $musicFolderId !== 0))
                    ? []
                    : Catalog::get_albums_by_artist($size, $offset, $catalogs);
                break;
            case 'byYear':
                $fromYear = (int) min(($input['fromYear'] ?? 0), ($input['toYear'] ?? 0));
                $toYear   = (int) max(($input['fromYear'] ?? 0), ($input['toYear'] ?? 0));

                if ($fromYear || $toYear) {
                    $data = Search::year_search($fromYear, $toYear, $size, $offset);
                    if ($musicFolderId !== 0) {
                        $data['catalog_id'] = $musicFolderId;
                    }

                    $albums = Search::run($data, $user);
                }
                break;
            case 'byGenre':
                $genre  = $input['genre'];
                $tag_id = Tag::tag_exists($genre);
                if ($tag_id > 0) {
                    $albums = Tag::get_tag_objects('album', $tag_id, $size, $offset, $musicFolderId);
                }
                break;
        }

        return $albums;
    }

    /**
     * _follow_stream
     */
    private function _follow_stream(string $url): void
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
            $headers    = apache_request_headers();
            $reqheaders = [];
            if (isset($headers['User-Agent'])) {
                $reqheaders[] = "User-Agent: " . $headers['User-Agent'];
            }
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
                        CURLOPT_WRITEFUNCTION => $this->_output_body(...),
                        CURLOPT_HEADERFUNCTION => $this->_output_header(...),
                        // Ignore invalid certificate
                        // Default trusted chain is crap anyway and currently no custom CA option
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => 0,
                        CURLOPT_TIMEOUT => 0
                    ]
                );
                if (curl_exec($curl) === false) {
                    debug_event(self::class, 'Stream error: ' . curl_error($curl), 1);
                }
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
     * _getAmpacheIdArrays
     * @param string[] $sub_ids
     * @return array<int, array{
     *     object_id: int,
     *     object_type: string,
     *     track: int
     * }>
     */
    private function _getAmpacheIdArrays(array $sub_ids): array
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
    private function _output_body(CurlHandle $curl, string $data): int
    {
        unset($curl);

        echo $data;
        ob_flush();

        return strlen($data);
    }

    /**
     * _output_header
     */
    private function _output_header(CurlHandle $curl, string $header): int
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
     * _search
     * @param array<string, mixed> $input
     * @return array<string, int[]>
     */
    private function _search(string $query, array $input, User $user): array
    {
        $artists = [];
        $albums  = [];
        $songs   = [];

        $artistCount   = $input['artistCount'] ?? 20;
        $artistOffset  = $input['artistOffset'] ?? 0;
        $albumCount    = $input['albumCount'] ?? 20;
        $albumOffset   = $input['albumOffset'] ?? 0;
        $songCount     = $input['songCount'] ?? 20;
        $songOffset    = $input['songOffset'] ?? 0;
        $musicFolderId = $this->musicFolderResolver->musicFolderId($input, $user);

        $original = unhtmlentities($query);
        $query    = SubsonicApiApplication::parseSearchQuery($original);
        if ($artistCount > 0) {
            $data           = [];
            $data['limit']  = $artistCount;
            $data['offset'] = $artistOffset;
            $data['type']   = 'artist';
            $ruleCount      = 1;
            foreach ($query as $token) {
                $data['rule_' . $ruleCount . '_input']    = $token['value'];
                $data['rule_' . $ruleCount . '_operator'] = $token['operator'];
                $data['rule_' . $ruleCount]               = 'title';
                $ruleCount++;
            }
            if ($musicFolderId !== 0) {
                $data['catalog_id'] = $musicFolderId;
            }
            $artists = Search::run($data, $user);
        }

        if ($albumCount > 0) {
            $data           = [];
            $data['limit']  = $albumCount;
            $data['offset'] = $albumOffset;
            $data['type']   = 'album';
            $ruleCount      = 1;
            foreach ($query as $token) {
                $data['rule_' . $ruleCount . '_input']    = $token['value'];
                $data['rule_' . $ruleCount . '_operator'] = $token['operator'];
                $data['rule_' . $ruleCount]               = 'title';
                $ruleCount++;
            }
            if ($musicFolderId !== 0) {
                $data['catalog_id'] = $musicFolderId;
            }
            $albums = Search::run($data, $user);
        }

        if ($songCount > 0) {
            $data           = [];
            $data['limit']  = $songCount;
            $data['offset'] = $songOffset;
            $data['type']   = 'song';
            $ruleCount      = 1;
            foreach ($query as $token) {
                $data['rule_' . $ruleCount . '_input']    = $token['value'];
                $data['rule_' . $ruleCount . '_operator'] = $token['operator'];
                $data['rule_' . $ruleCount]               = 'title';
                $ruleCount++;
            }
            if ($musicFolderId !== 0) {
                $data['catalog_id'] = $musicFolderId;
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
     * Convert a SubRip caption body to WebVTT.
     *
     * The two formats differ only in the header line and in the decimal separator of a cue's timestamps, so the
     * cue text itself is passed through untouched.
     */
    private function _srtToVtt(string $srt): string
    {
        $body = str_replace(["\r\n", "\r"], "\n", $srt);
        $body = (string) preg_replace('/^\xEF\xBB\xBF/', '', $body);
        $body = (string) preg_replace(
            '/(\d{2}:\d{2}:\d{2}),(\d{3})\s*-->\s*(\d{2}:\d{2}:\d{2}),(\d{3})/',
            '$1.$2 --> $3.$4',
            $body
        );

        return "WEBVTT\n\n" . ltrim($body, "\n");
    }

    /**
     * _updatePlaylist
     * @param int[]|string[] $songsIdToAdd
     * @param int[]|string[] $songIndexToRemove
     */
    private function _updatePlaylist(
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
                $ampacheId = self::getAmpacheId((string) $songsIdToAdd[$count]);
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
