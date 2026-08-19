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
use Ampache\Module\Art\Art;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessFunctionEnum;
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
use Ampache\Module\Share\ShareCreatorInterface;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\Statistics\Userflag;
use Ampache\Module\System\Core;
use Ampache\Module\System\Preference;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\Recommendation;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\ArtistRepositoryInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\LiveStreamRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\library_item;
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
use Ampache\Repository\PrivateMessageRepositoryInterface;
use Ampache\Repository\ShareRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use CurlHandle;
use DateTime;
use DOMDocument;
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
        '_addJsonResponse',
        '_addXmlResponse',
        '_albumList',
        '_check_parameter',
        '_errorOutput',
        '_follow_stream',
        '_getAmpacheIdArrays',
        '_jsonOutput',
        '_jsonpOutput',
        '_musicFolderId',
        '_musicFolders',
        '_output_body',
        '_output_header',
        '_responseOutput',
        '_search',
        '_setStar',
        '_updatePlaylist',
        '_xmlOutput',
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
    private BookmarkRepositoryInterface $bookmarkRepository;
    private FolderRepositoryInterface $folderRepository;
    private LiveStreamRepositoryInterface $liveStreamRepository;
    private PasswordGeneratorInterface $passwordGenerator;
    private PodcastCreatorInterface $podcastCreator;
    private PodcastDeleterInterface $podcastDeleter;
    private PodcastRepositoryInterface $podcastRepository;
    private PodcastSyncerInterface $podcastSyncer;
    private PrivateMessageRepositoryInterface $privateMessageRepository;
    private Random $random;
    private ShareCreatorInterface $shareCreator;
    private ShareRepositoryInterface $shareRepository;
    private SongRepositoryInterface $songRepository;
    private Subsonic_Json_Data $subsonicJsonData;
    private Subsonic_Xml_Data $subsonicXmlData;
    private UserRepositoryInterface $userRepository;

    public function __construct(
        AlbumRepositoryInterface $albumRepository,
        ArtistRepositoryInterface $artistRepository,
        BookmarkRepositoryInterface $bookmarkRepository,
        FolderRepositoryInterface $folderRepository,
        LiveStreamRepositoryInterface $liveStreamRepository,
        PasswordGeneratorInterface $passwordGenerator,
        PodcastCreatorInterface $podcastCreator,
        PodcastDeleterInterface $podcastDeleter,
        PodcastRepositoryInterface $podcastRepository,
        PodcastSyncerInterface $podcastSyncer,
        PrivateMessageRepositoryInterface $privateMessageRepository,
        Random $random,
        ShareCreatorInterface $shareCreator,
        ShareRepositoryInterface $shareRepository,
        SongRepositoryInterface $songRepository,
        Subsonic_Json_Data $subsonicJsonData,
        Subsonic_Xml_Data $subsonicXmlData,
        UserRepositoryInterface $userRepository,
    ) {
        $this->albumRepository          = $albumRepository;
        $this->artistRepository         = $artistRepository;
        $this->bookmarkRepository       = $bookmarkRepository;
        $this->folderRepository         = $folderRepository;
        $this->liveStreamRepository     = $liveStreamRepository;
        $this->passwordGenerator        = $passwordGenerator;
        $this->podcastCreator           = $podcastCreator;
        $this->podcastDeleter           = $podcastDeleter;
        $this->podcastRepository        = $podcastRepository;
        $this->podcastSyncer            = $podcastSyncer;
        $this->privateMessageRepository = $privateMessageRepository;
        $this->random                   = $random;
        $this->shareCreator             = $shareCreator;
        $this->shareRepository          = $shareRepository;
        $this->songRepository           = $songRepository;
        $this->subsonicJsonData         = $subsonicJsonData;
        $this->subsonicXmlData          = $subsonicXmlData;
        $this->userRepository           = $userRepository;
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
     * addChatMessage
     *
     * Adds a message to the chat log.
     * https://www.subsonic.org/pages/api.jsp#addchatmessage
     * @param array<string, mixed> $input
     */
    public function addchatmessage(array $input, User $user): void
    {
        $message = $this->_check_parameter($input, 'message', __FUNCTION__);
        if ($message === false) {
            return;
        }

        if (!AmpConfig::get('sociable')) {
            $this->_errorOutput($input, self::SSERROR_GENERIC, __FUNCTION__);

            return;
        }

        $this->privateMessageRepository->create(null, $user, '', trim($message));

        $this->_responseOutput($input, __FUNCTION__);
    }

    /**
     * changePassword
     *
     * Changes the password of an existing user on the server.
     * https://www.subsonic.org/pages/api.jsp#changepassword
     * @param array<string, mixed> $input
     */
    public function changepassword(array $input, User $user): void
    {
        $username = $this->_check_parameter($input, 'username', __FUNCTION__);
        if ($username === false) {
            return;
        }

        $inp_pass = $this->_check_parameter($input, 'password', __FUNCTION__);
        if ($inp_pass === false) {
            return;
        }

        $password = SubsonicApiApplication::decryptPassword($inp_pass);
        if ($user->username == $username || $user->access === 100) {
            $update_user = User::get_from_username((string) $username);
            if ($update_user instanceof User && !AmpConfig::get('simple_user_mode')) {
                $update_user->update_password($password);
                $this->_responseOutput($input, __FUNCTION__);
            } else {
                $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * createBookmark
     *
     * Creates or updates a bookmark.
     * https://www.subsonic.org/pages/api.jsp#createbookmark
     * @param array<string, mixed> $input
     */
    public function createbookmark(array $input, User $user): void
    {
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $position = $this->_check_parameter($input, 'position', __FUNCTION__);
        if ($position === false) {
            return;
        }

        $comment   = (string) ($input['comment'] ?? '');
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
                        'position' => (int) $position
                    ],
                    $user->id,
                    time()
                );
            } else {
                $this->bookmarkRepository->update($bookmark->getId(), (int) $position, new DateTime());
            }
            $this->_responseOutput($input, __FUNCTION__);
        } else {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
        }
    }

    /**
     * createInternetRadioStation
     *
     * Adds a new internet radio station.
     * https://www.subsonic.org/pages/api.jsp#createinternetradiostation
     * @param array<string, mixed> $input
     */
    public function createinternetradiostation(array $input, User $user): void
    {
        $url = $this->_check_parameter($input, 'streamUrl', __FUNCTION__);
        if ($url === false) {
            return;
        }

        $name = $this->_check_parameter($input, 'name', __FUNCTION__);
        if ($name === false) {
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
                $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

                return;
            }
            $this->_responseOutput($input, __FUNCTION__);
        } else {
            $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
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
                $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

                return;
            }

            if (!$playlist->has_access($user)) {
                $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);

                return;
            }

            $this->_updatePlaylist($playlistId, $name, $songIdList, [], true, true);
            $this->_responseOutput($input, __FUNCTION__);
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
                    $response = $this->_addXmlResponse(__FUNCTION__);
                    $response = $this->subsonicXmlData->addPlaylist($response, $playlist, true);
                } else {
                    $response = $this->_addJsonResponse(__FUNCTION__);
                    $response = $this->subsonicJsonData->addPlaylist($response, $playlist, true);
                }
                $this->_responseOutput($input, __FUNCTION__, $response);
            } else {
                $this->_errorOutput($input, self::SSERROR_GENERIC, __FUNCTION__);
            }
        } else {
            $this->_errorOutput($input, self::SSERROR_MISSINGPARAM, __FUNCTION__);
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
        $url = $this->_check_parameter($input, 'url', __FUNCTION__);
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

                    $this->_responseOutput($input, __FUNCTION__);
                } catch (PodcastCreationException) {
                    $this->_errorOutput($input, self::SSERROR_GENERIC, __FUNCTION__);
                }
            } else {
                $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
            }
        } else {
            $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * createShare
     *
     * Creates a public URL that can be used by anyone to stream music or video from the server.
     * https://www.subsonic.org/pages/api.jsp#createshare
     * @param array<string, mixed> $input
     */
    public function createshare(array $input, User $user): void
    {
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        if (is_array($sub_id)) {
            $object      = self::getAmpacheObject($sub_id[0]);
            $object_type = self::getAmpacheType($sub_id[0]);
        } else {
            $object      = self::getAmpacheObject($sub_id);
            $object_type = self::getAmpacheType($sub_id);
        }

        if (!$object instanceof library_item || !$object_type) {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $description = $input['description'] ?? null;
        if (AmpConfig::get('share')) {
            $share_expire = AmpConfig::get('share_expire', 7);
            $expire_days  = (isset($input['expires']))
                ? Share::get_expiry(((int) filter_var($input['expires'], FILTER_SANITIZE_NUMBER_INT)) / 1000)
                : $share_expire;
            if (is_array($sub_id) && $object_type === 'song') {
                debug_event(self::class, 'createShare: sharing song list (album)', 5);
                $song_id     = self::getAmpacheId($sub_id[0]);
                $tmp_song    = new Song($song_id);
                $sub_id      = self::getAlbumSubId($tmp_song->album);
                $object      = new Album($tmp_song->album);
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

            if (!empty($object_type) && !empty($sub_id) && !$object->isNew()) {
                $share = $this->shareCreator->create(
                    $user,
                    LibraryItemEnum::from($object_type),
                    $object->getId(),
                    true,
                    Access::check_function(AccessFunctionEnum::FUNCTION_DOWNLOAD),
                    (int) $expire_days,
                    $this->passwordGenerator->generate_token(),
                    0,
                    $description
                );
                if ($share === null) {
                    $this->_errorOutput($input, self::SSERROR_GENERIC, __FUNCTION__);

                    return;
                }

                $shares = [$share];
                $format = (string) ($input['f'] ?? 'xml');
                if ($format === 'xml') {
                    $response = $this->_addXmlResponse(__FUNCTION__);
                    $response = $this->subsonicXmlData->addShares($response, $shares);
                } else {
                    $response = $this->_addJsonResponse(__FUNCTION__);
                    $response = $this->subsonicJsonData->addShares($response, $shares);
                }
                $this->_responseOutput($input, __FUNCTION__, $response);
            } else {
                $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * createUser
     *
     * Creates a new user on the server.
     * https://www.subsonic.org/pages/api.jsp#createuser
     * @param array<string, mixed> $input
     */
    public function createuser(array $input, User $user): void
    {
        $username = $this->_check_parameter($input, 'username', __FUNCTION__);
        if ($username === false) {
            return;
        }

        $password = $this->_check_parameter($input, 'password', __FUNCTION__);
        if ($password === false) {
            return;
        }

        $email = $this->_check_parameter($input, 'email', __FUNCTION__);
        if ($email === false) {
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
            $password = SubsonicApiApplication::decryptPassword($password);
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
                $this->_responseOutput($input, __FUNCTION__);
            } else {
                $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * deleteBookmark
     *
     * Creates or updates a bookmark.
     * https://www.subsonic.org/pages/api.jsp#deletebookmark
     * @param array<string, mixed> $input
     */
    public function deletebookmark(array $input, User $user): void
    {
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $object_id = self::getAmpacheId($sub_id);
        $type      = self::getAmpacheType($sub_id);

        $bookmark = new Bookmark($object_id, $type, $user->id);
        if ($bookmark->isNew()) {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
        } else {
            $this->bookmarkRepository->delete($bookmark->getId());

            $this->_responseOutput($input, __FUNCTION__);
        }
    }

    /**
     * deleteInternetRadioStation
     *
     * Deletes an existing internet radio station.
     * https://www.subsonic.org/pages/api.jsp#deleteinternetradiostation
     * @param array<string, mixed> $input
     */
    public function deleteinternetradiostation(array $input, User $user): void
    {
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $liveStreamRepository = $this->liveStreamRepository;

        if (AmpConfig::get('live_stream') && $user->access >= AccessLevelEnum::MANAGER->value) {
            $radio_id   = self::getAmpacheId($sub_id);
            $liveStream = ($radio_id)
                ? $liveStreamRepository->findById($radio_id)
                : null;

            if ($liveStream === null) {
                $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            } else {
                $liveStreamRepository->delete($liveStream);

                $this->_responseOutput($input, __FUNCTION__);
            }
        } else {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
        }
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
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $playlist = self::getAmpacheObject($sub_id);
        if (
            (!($playlist instanceof Playlist || $playlist instanceof Search))
            || $playlist->isNew()
        ) {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        if (!$playlist->has_access($user)) {
            $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);

            return;
        }

        $playlist->delete();

        $this->_responseOutput($input, __FUNCTION__);
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
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        if (AmpConfig::get(ConfigurationKeyEnum::PODCAST) && $user->access >= AccessLevelEnum::MANAGER->value) {
            $podcast_id = self::getAmpacheId($sub_id);
            $podcast    = ($podcast_id)
                ? $this->podcastRepository->findById($podcast_id)
                : null;
            if ($podcast === null) {
                $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            } else {
                $this->podcastDeleter->delete($podcast);

                $this->_responseOutput($input, __FUNCTION__);
            }
        } else {
            $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
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
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        if (AmpConfig::get('podcast') && $user->access >= 75) {
            $episode = new Podcast_Episode(self::getAmpacheId($sub_id));
            if ($episode->isNew()) {
                $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            } elseif ($episode->remove()) {
                Catalog::count_table(CountableTableEnum::PODCAST_EPISODE);

                $this->_responseOutput($input, __FUNCTION__);
            } else {
                $this->_errorOutput($input, self::SSERROR_GENERIC, __FUNCTION__);
            }
        } else {
            $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * deleteShare
     *
     * Deletes an existing share.
     * https://www.subsonic.org/pages/api.jsp#deleteshare
     * @param array<string, mixed> $input
     */
    public function deleteshare(array $input, User $user): void
    {
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        if (AmpConfig::get('share')) {
            $shareRepository = $this->shareRepository;

            $share_id = self::getAmpacheId($sub_id);
            $share    = ($share_id)
                ? $shareRepository->findById($share_id)
                : null;

            if (
                $share === null
                || !$share->isAccessible($user)
            ) {
                $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            } else {
                $shareRepository->delete($share);

                $this->_responseOutput($input, __FUNCTION__);
            }
        } else {
            $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * deleteUser
     *
     * Deletes an existing user on the server.
     * https://www.subsonic.org/pages/api.jsp#deleteuser
     * @param array<string, mixed> $input
     */
    public function deleteuser(array $input, User $user): void
    {
        $username = $this->_check_parameter($input, 'username', __FUNCTION__);
        if ($username === false) {
            return;
        }

        if ($user->access === 100) {
            $update_user = User::get_from_username((string) $username);
            if ($update_user instanceof User) {
                $update_user->delete();

                $this->_responseOutput($input, __FUNCTION__);
            } else {
                $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
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
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $object = self::getAmpacheObject($sub_id);
        if (($object instanceof Song || $object instanceof Podcast_Episode) === false) {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

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
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        if (AmpConfig::get('podcast') && $user->access >= 75) {
            $episode = new Podcast_Episode(self::getAmpacheId($sub_id));
            if ($episode->isNew()) {
                $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            } else {
                $this->podcastSyncer->syncEpisode($episode);

                $this->_responseOutput($input, __FUNCTION__);
            }
        } else {
            $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * error
     * @param array<string, mixed> $input
     */
    public function error(array $input, int $errorCode, string $function): void
    {
        $this->_errorOutput($input, $errorCode, $function);
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
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $album = self::getAmpacheObject($sub_id);
        if (!$album instanceof Album || $album->isNew()) {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addAlbumID3($response, $album, true);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addAlbumID3($response, $album, true);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $album = self::getAmpacheObject($sub_id);
        if (!$album instanceof Album || $album->isNew()) {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $info   = Recommendation::get_album_info($album->getId());
        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addAlbumInfo($response, $info, $album);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addAlbumInfo($response, $info, $album);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
        $type = $this->_check_parameter($input, 'type', __FUNCTION__);
        if ($type === false) {
            return;
        }

        if ($type === 'byGenre' && !$this->_check_parameter($input, 'genre', __FUNCTION__)) {
            return;
        }

        $albums = $this->_albumList($input, $user, (string) $type);
        if ($albums === null) {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addAlbumList($response, $albums);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addAlbumList($response, $albums);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
        $type = $this->_check_parameter($input, 'type', __FUNCTION__);
        if ($type === false) {
            return;
        }

        if ($type === 'byGenre' && !$this->_check_parameter($input, 'genre', __FUNCTION__)) {
            return;
        }

        $albums = $this->_albumList($input, $user, (string) $type);
        if ($albums === null) {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addAlbumList2($response, $albums);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addAlbumList2($response, $albums);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $artist = new Artist(self::getAmpacheId($sub_id));
        if ($artist->isNew()) {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addArtistID3($response, $artist, true);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addArtistWithAlbumsID3($response, $artist);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $artist = self::getAmpacheObject($sub_id);
        if (!$artist instanceof Artist || $artist->isNew()) {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $count             = (int) ($input['count'] ?? 20);
        $includeNotPresent = make_bool($input['includeNotPresent'] ?? false);

        $info     = Recommendation::get_artist_info($artist->getId());
        $similars = Recommendation::get_artists_like($artist->getId(), $count, !$includeNotPresent);
        $format   = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addArtistInfo($response, $info, $artist, $similars);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addArtistInfo($response, $info, $artist, $similars);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $artist = self::getAmpacheObject($sub_id);
        if (!$artist instanceof Artist || $artist->isNew()) {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $count             = (int) ($input['count'] ?? 20);
        $includeNotPresent = make_bool($input['includeNotPresent'] ?? false);

        $info     = Recommendation::get_artist_info($artist->getId());
        $similars = Recommendation::get_artists_like($artist->getId(), $count, !$includeNotPresent);
        $format   = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addArtistInfo2($response, $info, $artist, $similars);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addArtistInfo2($response, $info, $artist, $similars);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
        $catalogs = $this->_musicFolders($input, $user);

        $user_id = $user->id;
        // an empty catalog list makes get_id_arrays return everything, so only ask when there is something to ask for
        $artists = ($catalogs === [])
            ? []
            : Artist::get_id_arrays($catalogs, ((bool) Preference::get_by_user($user_id, 'subsonic_force_album_artist') === true));
        $format  = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addArtists($response, $artists);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addArtists($response, $artists);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
        $username = $this->_check_parameter($input, 'username', __FUNCTION__);
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
                $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * getBookmarks
     *
     * Returns all bookmarks for this user.
     * https://www.subsonic.org/pages/api.jsp#getbookmarks
     * @param array<string, mixed> $input
     */
    public function getbookmarks(array $input, User $user): void
    {
        $bookmarks = [];

        $bookmarkRepository = $this->bookmarkRepository;
        foreach ($bookmarkRepository->getByUser($user) as $bookmarkId) {
            $bookmark = $bookmarkRepository->findById($bookmarkId);

            if ($bookmark !== null) {
                $bookmarks[] = $bookmark;
            }
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addBookmarks($response, $bookmarks);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addBookmarks($response, $bookmarks);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $video = self::getAmpacheObject($sub_id);
        if (!$video instanceof Video || $video->isNew()) {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

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
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

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
     * getChatMessages
     *
     * Returns the current visible (non-expired) chat messages.
     * https://www.subsonic.org/pages/api.jsp#getchatmessages
     * @param array<string, mixed> $input
     */
    public function getchatmessages(array $input, User $user): void
    {
        unset($user);
        $since        = (int) ($input['since'] ?? 0);
        $pmRepository = $this->privateMessageRepository;

        $pmRepository->cleanChatMessages();

        if (!AmpConfig::get('sociable')) {
            $messages = [];
        } else {
            $messages = $pmRepository->getChatMessages($since);
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addChatMessages($response, $messages);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addChatMessages($response, $messages);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
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
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

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
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $size = (isset($input['size']) && is_numeric($input['size'])) ? (int) $input['size'] : 'original';

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
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addGenres($response, Tag::get_tags('song'));
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addGenres($response, Tag::get_tags('song'));
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
        $catalogs        = $this->_musicFolders($input, $user);

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
            $response = $this->_addXmlResponse(__FUNCTION__);
            if (count($fcatalogs) > 0) {
                $children = $this->folderRepository->getCatalogRootChildren($fcatalogs, $user->getId());
                $response = $this->subsonicXmlData->addFolderIndexes($response, $children, $lastmodified);
            }
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            if (count($fcatalogs) > 0) {
                $children = $this->folderRepository->getCatalogRootChildren($fcatalogs, $user->getId());
                $response = $this->subsonicJsonData->addFolderIndexes($response, $children, $lastmodified);
            }
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getInternetRadioStations
     *
     * Returns all internet radio stations.
     * https://www.subsonic.org/pages/api.jsp#getinternetradiostations
     * @param array<string, mixed> $input
     */
    public function getinternetradiostations(array $input, User $user): void
    {
        $radios = $this->liveStreamRepository->findAll($user);
        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addInternetRadioStations($response, $radios);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addInternetRadioStations($response, $radios);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getLicense
     *
     * Get details about the software license.
     * https://www.subsonic.org/pages/api.jsp#getlicense
     * @param array<string, mixed> $input
     */
    public function getlicense(array $input, User $user): void
    {
        unset($user);

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addLicense($response);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addLicense($response);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
            $this->_errorOutput($input, self::SSERROR_MISSINGPARAM, __FUNCTION__);

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
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addLyrics($response, $artist, $title, $song);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addLyrics($response, $artist, $title, $song);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getLyricsBySongId [OS] REMOVED
     * @param array<string, mixed> $input
     */
    public function getlyricsbysongid(array $input, User $user): void
    {
        unset($user);

        $this->_errorOutput($input, self::SSERROR_APIVERSION_SERVER, __FUNCTION__);
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
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $object_id = self::getAmpacheId($sub_id);
        if (!$object_id) {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $object = self::getAmpacheObject($sub_id);
        if (!$object) {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        if ($object instanceof Album || $object instanceof Artist || $object instanceof Catalog || $object instanceof Folder) {
            $format = (string) ($input['f'] ?? 'xml');
            if ($format === 'xml') {
                $response = $this->_addXmlResponse(__FUNCTION__);
                $response = $this->subsonicXmlData->addDirectory($response, $object, $user->getId());
            } else {
                $response = $this->_addJsonResponse(__FUNCTION__);
                $response = $this->subsonicJsonData->addDirectory($response, $object, $user->getId());
            }
            $this->_responseOutput($input, __FUNCTION__, $response);
        } else {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
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
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addMusicFolders($response, $catalogs);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addMusicFolders($response, $catalogs);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $episodes = Catalog::get_newest_podcasts($count);
        $format   = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addNewestPodcasts($response, $episodes);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addNewestPodcasts($response, $episodes);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addNowPlaying($response, $data);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addNowPlaying($response, $data);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getOpenSubsonicExtensions [OS] REMOVED
     * @param array<string, mixed> $input
     */
    public function getopensubsonicextensions(array $input, User $user): void
    {
        unset($user);

        $this->_errorOutput($input, self::SSERROR_APIVERSION_SERVER, __FUNCTION__);
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
        unset($user);
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $playlist = self::getAmpacheObject($sub_id);
        if (
            (!($playlist instanceof Playlist || $playlist instanceof Search))
            || $playlist->isNew()
        ) {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addPlaylist($response, $playlist, true);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addPlaylist($response, $playlist, true);
        }

        $this->_responseOutput($input, __FUNCTION__, $response);
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
        $user = (isset($input['username']))
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
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addPlaylists($response, $user, $results);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addPlaylists($response, $user, $results);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addPlayQueue($response, $playQueue, (string) $user->username);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addPlayQueue($response, $playQueue, (string) $user->username);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getPlayQueueByIndex [OS] REMOVED
     * @param array<string, mixed> $input
     */
    public function getplayqueuebyindex(array $input, User $user): void
    {
        unset($user);

        $this->_errorOutput($input, self::SSERROR_APIVERSION_SERVER, __FUNCTION__);
    }

    /**
     * getPodcastEpisode [OS] REMOVED
     * @param array<string, mixed> $input
     */
    public function getpodcastepisode(array $input, User $user): void
    {
        unset($user);

        $this->_errorOutput($input, self::SSERROR_APIVERSION_SERVER, __FUNCTION__);
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
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
        }

        $podcast_id = ($sub_id)
            ? self::getAmpacheId($sub_id)
            : null;
        if ($podcast_id) {
            $podcast = $this->podcastRepository->findById($podcast_id);
            if ($podcast === null) {
                $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

                return;
            }

            $podcasts = [$podcast];
        } else {
            $podcasts = Catalog::get_podcasts(User::get_user_catalogs($user->id));
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addPodcasts($response, $podcasts, $includeEpisodes, $sub_id);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addPodcasts($response, $podcasts, $includeEpisodes, $sub_id);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
                $finput   = $this->_musicFolderId($input, $user);
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
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addRandomSongs($response, $songs);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addRandomSongs($response, $songs);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getScanStatus
     *
     * Returns the current status for media library scanning.
     * https://www.subsonic.org/pages/api.jsp#getscanstatus
     * @param array<string, mixed> $input
     */
    public function getscanstatus(array $input, User $user): void
    {
        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addScanStatus($response, $user);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addScanStatus($response, $user);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getShares
     *
     * Returns information about shared media this user is allowed to manage.
     * https://www.subsonic.org/pages/api.jsp#getshares
     * @param array<string, mixed> $input
     */
    public function getshares(array $input, User $user): void
    {
        $shares = $this->shareRepository->getIdsByUser($user);
        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addShares($response, $shares);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addShares($response, $shares);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
            $this->_errorOutput($input, self::SSERROR_GENERIC, __FUNCTION__);

            return;
        }

        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }
        $object_id = self::getAmpacheId($sub_id);
        if (!$object_id) {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

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
            $response = $this->_addXmlResponse(__FUNCTION__);
            switch ($elementName) {
                case 'similarSongs':
                    $response = $this->subsonicXmlData->addSimilarSongs($response, $songs);
                    break;
                case 'similarSongs2':
                    $response = $this->subsonicXmlData->addSimilarSongs2($response, $songs);
                    break;
            }
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            switch ($elementName) {
                case 'similarSongs':
                    $response = $this->subsonicJsonData->addSimilarSongs($response, $songs);
                    break;
                case 'similarSongs2':
                    $response = $this->subsonicJsonData->addSimilarSongs2($response, $songs);
                    break;
            }
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $song_id = self::getAmpacheId($sub_id);
        if (!$song_id) {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $song = new Song($song_id);
        if ($song->isNew() || !$song->enabled) {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addSong($response, $song);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addSong($response, $song_id);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
        $genre = $this->_check_parameter($input, 'genre', __FUNCTION__);
        if ($genre === false) {
            return;
        }

        $count         = (int) ($input['count'] ?? 0);
        $offset        = (int) ($input['offset'] ?? 0);
        $musicFolderId = $this->_musicFolderId($input, $user);

        $tag = Tag::construct_from_name($genre);
        if ($tag->isNew()) {
            $songs = [];
        } else {
            $songs = Tag::get_tag_objects("song", $tag->id, $count, $offset, $musicFolderId);
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addSongsByGenre($response, $songs);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addSongsByGenre($response, $songs);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getStarred
     *
     * Returns starred songs, albums and artists.
     * https://www.subsonic.org/pages/api.jsp#getstarred
     * @param array<string, mixed> $input
     */
    public function getstarred(array $input, User $user, string $elementName = 'starred'): void
    {
        // hide ratings and flags for other users if single user data is enabled
        $by_user     = (bool) Preference::get_by_user($user->id, 'subsonic_single_user_data') === true;
        $output_user = ($by_user)
            ? $user
            : null;

        $musicFolderId = $this->_musicFolderId($input, $user);
        $artists       = Userflag::get_latest('artist', $output_user, 10000, 0, 0, 0, $by_user, $musicFolderId);
        $albums        = Userflag::get_latest('album', $output_user, 10000, 0, 0, 0, $by_user, $musicFolderId);
        $songs         = Userflag::get_latest('song', $output_user, 10000, 0, 0, 0, $by_user, $musicFolderId);

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = ($elementName === 'starred2')
                ? $this->subsonicXmlData->addStarred2($response, $artists, $albums, $songs)
                : $this->subsonicXmlData->addStarred($response, $artists, $albums, $songs);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = ($elementName === 'starred2')
                ? $this->subsonicJsonData->addStarred2($response, $artists, $albums, $songs)
                : $this->subsonicJsonData->addStarred($response, $artists, $albums, $songs);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getStarred2
     *
     * Returns starred songs, albums and artists.
     * https://www.subsonic.org/pages/api.jsp#getstarred2
     * @param array<string, mixed> $input
     */
    public function getstarred2(array $input, User $user): void
    {
        $this->getstarred($input, $user, "starred2");
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
        $name = $this->_check_parameter($input, 'artist', __FUNCTION__);
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
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addTopSongs($response, $songs);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addTopSongs($response, $songs);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * getUser
     *
     * Get details about a given user, including which authorization roles and folder access it has.
     * https://www.subsonic.org/pages/api.jsp#getuser
     * @param array<string, mixed> $input
     */
    public function getuser(array $input, User $user): void
    {
        $username = $this->_check_parameter($input, 'username', __FUNCTION__);
        if ($username === false) {
            return;
        }

        if ($user->access === 100 || $user->username == $username) {
            if ($user->username == $username) {
                $update_user = $user;
            } else {
                $update_user = User::get_from_username((string) $username);
            }
            if (!$update_user) {
                $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            } else {
                $format = (string) ($input['f'] ?? 'xml');
                if ($format === 'xml') {
                    $response = $this->_addXmlResponse(__FUNCTION__);
                    $response = $this->subsonicXmlData->addUser($response, $update_user);
                } else {
                    $response = $this->_addJsonResponse(__FUNCTION__);
                    $response = $this->subsonicJsonData->addUser($response, $update_user);
                }
                $this->_responseOutput($input, __FUNCTION__, $response);
            }
        } else {
            $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * getUsers
     *
     * Get details about all users, including which authorization roles and folder access they have.
     * https://www.subsonic.org/pages/api.jsp#getusers
     * @param array<string, mixed> $input
     */
    public function getusers(array $input, User $user): void
    {
        if ($user->access !== 100) {
            $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);

            return;
        }

        $users  = $this->userRepository->getValid();
        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addUsers($response, $users);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addUsers($response, $users);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $video_id = self::getAmpacheId($sub_id);
        if (!$video_id) {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addVideoInfo($response, $video_id);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addVideoInfo($response, $video_id);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addVideos($response, $videos);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addVideos($response, $videos);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $object_id = self::getAmpacheId($sub_id);
        if (!$object_id) {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

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
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

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
        $action = $this->_check_parameter($input, 'action', __FUNCTION__);
        if ($action === false) {
            return;
        }

        $object_id  = $input['id'] ?? [];
        $controller = AmpConfig::get('localplay_controller', '');
        $localplay  = ($controller) ? new LocalPlay($controller) : null;
        $return     = false;
        if (empty($controller) || empty($localplay) || empty($localplay->type) || !$localplay->connect()) {
            debug_event(self::class, 'Error Localplay controller: ' . (empty($controller) ? 'Is not set' : $controller), 3);
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

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
                    $this->_errorOutput($input, self::SSERROR_MISSINGPARAM, __FUNCTION__);

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
                    $this->_errorOutput($input, self::SSERROR_MISSINGPARAM, __FUNCTION__);
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
                $response = $this->_addXmlResponse(__FUNCTION__);
                if ($action == 'get') {
                    $response = $this->subsonicXmlData->addJukeboxPlaylist($response, $localplay);
                } else {
                    $response = $this->subsonicXmlData->addJukeboxStatus($response, $localplay);
                }
            } else {
                $response = $this->_addJsonResponse(__FUNCTION__);
                if ($action == 'get') {
                    $response = $this->subsonicJsonData->addJukeboxPlaylist($response, $localplay);
                } else {
                    $response = $this->subsonicJsonData->addJukeboxStatus($response, $localplay);
                }
            }
            $this->_responseOutput($input, __FUNCTION__, $response);
        }
    }

    /**
     * ping
     *
     * Used to test connectivity with the server.
     * https://www.subsonic.org/pages/api.jsp#ping
     * @param array<string, mixed> $input
     */
    public function ping(array $input, User $user): void
    {
        unset($user);

        $this->_responseOutput($input, __FUNCTION__);
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
            $this->_responseOutput($input, __FUNCTION__);
        } else {
            $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
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
            $this->_errorOutput($input, self::SSERROR_MISSINGPARAM, __FUNCTION__);

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
                $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

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

        $this->_responseOutput($input, __FUNCTION__);
    }

    /**
     * savePlayQueueByIndex [OS] REMOVED
     * @param array<string, mixed> $input
     */
    public function saveplayqueuebyindex(array $input, User $user): void
    {
        unset($user);

        $this->_errorOutput($input, self::SSERROR_APIVERSION_SERVER, __FUNCTION__);
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
        $sub_ids = $this->_check_parameter($input, 'id', __FUNCTION__);
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

                // long pauses might cause your now_playing to hide
                Stream::garbage_collection();
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

        $this->_responseOutput($input, __FUNCTION__);
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
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addSearchResult($response, $results, $offset, $total);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addSearchResult($response, $results, $offset, $total);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addSearchResult2($response, $results['artists'], $results['albums'], $results['songs']);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addSearchResult2($response, $results['artists'], $results['albums'], $results['songs']);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
            $this->_errorOutput($input, self::SSERROR_MISSINGPARAM, __FUNCTION__);

            return;
        }
        $results = $this->_search($query, $input, $user);

        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addSearchResult3($response, $results['artists'], $results['albums'], $results['songs']);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addSearchResult3($response, $results['artists'], $results['albums'], $results['songs']);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
    }

    /**
     * setRating
     *
     * Sets the rating for a music file.
     * https://www.subsonic.org/pages/api.jsp#setrating
     * @param array<string, mixed> $input
     */
    public function setrating(array $input, User $user): void
    {
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $rating = $this->_check_parameter($input, 'rating', __FUNCTION__);
        if ($rating === false) {
            return;
        }

        $type = self::getAmpacheType($sub_id);
        // a rating that is not a number is refused rather than cast to 0, which would silently unrate the object
        $stars = (is_numeric($rating)) ? (int) $rating : -1;
        $robj  = (!empty($type))
            ? new Rating(self::getAmpacheId($sub_id), $type)
            : null;

        if ($robj != null && $stars >= 0 && $stars <= 5) {
            $robj->set_rating($stars, $user->id);

            $this->_responseOutput($input, __FUNCTION__);
        } else {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
        }
    }

    /**
     * star
     *
     * Attaches a star to a song, album or artist.
     * https://www.subsonic.org/pages/api.jsp#star
     * @param array<string, mixed> $input
     */
    public function star(array $input, User $user): void
    {
        $this->_setStar($input, $user, true);
    }

    /**
     * startScan
     *
     * Initiates a rescan of the media libraries.
     * https://www.subsonic.org/pages/api.jsp#startscan
     * @param array<string, mixed> $input
     */
    public function startscan(array $input, User $user): void
    {
        $format = (string) ($input['f'] ?? 'xml');
        if ($format === 'xml') {
            $response = $this->_addXmlResponse(__FUNCTION__);
            $response = $this->subsonicXmlData->addScanStatus($response, $user);
        } else {
            $response = $this->_addJsonResponse(__FUNCTION__);
            $response = $this->subsonicJsonData->addScanStatus($response, $user);
        }
        $this->_responseOutput($input, __FUNCTION__, $response);
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
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $object = self::getAmpacheObject($sub_id);
        if (($object instanceof Song || $object instanceof Podcast_Episode) === false) {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

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
     * tokenInfo [OS] REMOVED
     *
     * Returns information about an API key.
     * https://opensubsonic.netlify.app/docs/endpoints/tokeninfo/
     * @param array<string, mixed> $input
     */
    public function tokeninfo(array $input, User $user): void
    {
        unset($user);

        $this->_errorOutput($input, self::SSERROR_APIVERSION_SERVER, __FUNCTION__);
    }

    /**
     * unstar
     *
     * Attaches a star to a song, album or artist.
     * https://www.subsonic.org/pages/api.jsp#unstar
     * @param array<string, mixed> $input
     */
    public function unstar(array $input, User $user): void
    {
        $this->_setStar($input, $user, false);
    }

    /**
     * updateInternetRadioStation
     *
     * Updates an existing internet radio station.
     * https://www.subsonic.org/pages/api.jsp#updateinternetradiostation
     * @param array<string, mixed> $input
     */
    public function updateinternetradiostation(array $input, User $user): void
    {
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $url = $this->_check_parameter($input, 'streamUrl', __FUNCTION__);
        if ($url === false) {
            return;
        }

        $name = $this->_check_parameter($input, 'name', __FUNCTION__);
        if ($name === false) {
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
                    $this->_responseOutput($input, __FUNCTION__);
                } else {
                    $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
                }
            } else {
                $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
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
        $sub_id = $this->_check_parameter($input, 'playlistId', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        $name              = $input['name'] ?? '';
        $public            = make_bool($input['public'] ?? false);
        $songIdToAdd       = $input['songIdToAdd'] ?? [];
        $songIndexToRemove = $input['songIndexToRemove'] ?? [];

        $object = self::getAmpacheObject($sub_id);
        if (!$object) {
            $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        if ($object instanceof Playlist) {
            if (!$object->has_access($user)) {
                $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);

                return;
            }
            if (is_string($songIdToAdd)) {
                $songIdToAdd = explode(',', $songIdToAdd);
            }
            if (is_string($songIndexToRemove)) {
                $songIndexToRemove = explode(',', $songIndexToRemove);
            }
            $this->_updatePlaylist($object->getId(), $name, $songIdToAdd, $songIndexToRemove, $public);

            $this->_responseOutput($input, __FUNCTION__);
        } else {
            $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * updateShare
     *
     * Updates the description and/or expiration date for an existing share.
     * https://www.subsonic.org/pages/api.jsp#updateshare
     * @param array<string, mixed> $input
     */
    public function updateshare(array $input, User $user): void
    {
        $sub_id = $this->_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            return;
        }

        if (AmpConfig::get('share')) {
            $share = new Share(self::getAmpacheId($sub_id));
            if ($share->id > 0) {
                $expires = (isset($input['expires']))
                    ? Share::get_expiry(((int) filter_var($input['expires'], FILTER_SANITIZE_NUMBER_INT)) / 1000)
                    : $share->expire_days;
                $data = [
                    'max_counter' => $share->max_counter,
                    'expire' => $expires,
                    'allow_stream' => $share->allow_stream,
                    'allow_download' => $share->allow_download,
                    'description' => $input['description'] ?? $share->description,
                ];
                if ($share->update($data, $user)) {
                    $this->_responseOutput($input, __FUNCTION__);
                } else {
                    $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
                }
            } else {
                $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * updateUser
     *
     * Modifies an existing user on the server.
     * https://www.subsonic.org/pages/api.jsp#updateuser
     * @param array<string, mixed> $input
     */
    public function updateuser(array $input, User $user): void
    {
        $username = $this->_check_parameter($input, 'username', __FUNCTION__);
        if ($username === false) {
            return;
        }

        $password     = $input['password'] ?? false;
        $email        = (array_key_exists('email', $input)) ? urldecode($input['email']) : false;
        $adminRole    = (array_key_exists('adminRole', $input) && $input['adminRole'] == 'true');
        $downloadRole = (array_key_exists('downloadRole', $input) && $input['downloadRole'] == 'true');
        $uploadRole   = (array_key_exists('uploadRole', $input) && $input['uploadRole'] == 'true');
        $coverArtRole = (array_key_exists('coverArtRole', $input) && $input['coverArtRole'] == 'true');
        $shareRole    = (array_key_exists('shareRole', $input) && $input['shareRole'] == 'true');
        $maxbitrate   = (int) ($input['maxBitRate'] ?? 0);

        if ($user->access === 100) {
            $access = 25;
            if ($coverArtRole) {
                $access = 75;
            }
            if ($adminRole) {
                $access = 100;
            }
            // identify the user to modify
            $update_user = User::get_from_username((string) $username);
            if ($update_user instanceof User) {
                $user_id = $update_user->id;
                // update access level
                $update_user->update_access($access);
                // update password
                if ($password && !AmpConfig::get('simple_user_mode')) {
                    $password = SubsonicApiApplication::decryptPassword($password);
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
                    // Subsonic maxBitRate is kbps; transcode_bitrate is stored in bps
                    Preference::update('transcode_bitrate', $user_id, $maxbitrate * 1000);
                }
                $this->_responseOutput($input, __FUNCTION__);
            } else {
                $this->_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);
            }
        } else {
            $this->_errorOutput($input, self::SSERROR_UNAUTHORIZED, __FUNCTION__);
        }
    }

    /**
     * _addJsonResponse
     *
     * Generate a subsonic-response
     * @return array{'subsonic-response': array{'status': string, 'version': string}}
     */
    private function _addJsonResponse(string $function): array
    {
        return $this->subsonicJsonData->addResponse($function);
    }

    /**
     * _addXmlResponse
     *
     * Generate a subsonic-response
     */
    private function _addXmlResponse(string $function): SimpleXMLElement
    {
        return $this->subsonicXmlData->addResponse($function);
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
        $musicFolderId = $this->_musicFolderId($input, $user);
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
            $catalogs = $this->_musicFolders($input, $user);
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
     * check_parameter
     * @param array<string, mixed> $input
     * @return false|mixed
     */
    private function _check_parameter(array $input, string $parameter, string $function): mixed
    {
        if (!array_key_exists($parameter, $input) || $input[$parameter] === '') {
            ob_end_clean();
            $this->_errorOutput($input, self::SSERROR_MISSINGPARAM, $function);

            return false;
        }

        return $input[$parameter];
    }

    /**
     * _errorOutput
     * @param array<string, mixed> $input
     */
    private function _errorOutput(array $input, int $errorCode, string $function): void
    {
        $format = (string) ($input['f'] ?? 'xml');
        switch ($format) {
            case 'json':
                $this->_jsonOutput($this->subsonicJsonData->addError($errorCode, $function));
                break;
            case 'jsonp':
                $callback = (string) ($input['callback'] ?? 'jsonp');
                $this->_jsonpOutput($this->subsonicJsonData->addError($errorCode, $function), $callback);
                break;
            default:
                $this->_xmlOutput($this->subsonicXmlData->addError($errorCode, $function));
                break;
        }
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
     * _jsonOutput
     * @param array{'subsonic-response': array<string, mixed>} $json
     */
    private function _jsonOutput(array $json): void
    {
        $output = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if (!$output) {
            $output = json_encode($this->subsonicJsonData->addError(self::SSERROR_GENERIC, 'system'), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
        }

        header("Content-type: application/json; charset=" . AmpConfig::get('site_charset', 'UTF-8'));
        header("Access-Control-Allow-Origin: *");
        echo $output;
    }

    /**
     * _jsonpOutput
     * @param array{'subsonic-response': array<string, mixed>} $json
     */
    private function _jsonpOutput(array $json, string $callback): void
    {
        $output = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($output === false) {
            $output = json_encode($this->subsonicJsonData->addError(self::SSERROR_GENERIC, 'system'), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
        }

        header("Content-type: text/javascript; charset=" . AmpConfig::get('site_charset', 'UTF-8'));
        header("Access-Control-Allow-Origin: *");
        echo $callback . '(' . $output . ')';
    }

    /**
     * _musicFolderId
     *
     * Resolve a requested musicFolderId into a single catalog id to filter on.
     * 0 means no folder was requested; -1 can never match a catalog so a folder the user can't browse returns
     * nothing instead of everything.
     * @param array<string, mixed> $input
     */
    private function _musicFolderId(array $input, User $user): int
    {
        $sub_id = $input['musicFolderId'] ?? null;
        if ($sub_id === null || $sub_id === '') {
            return 0;
        }

        return $this->_musicFolders($input, $user)[0] ?? -1;
    }

    /**
     * _musicFolders
     *
     * Resolve the catalogs a browse request should be limited to.
     * A requested musicFolderId is always intersected with the catalogs the user may browse.
     * @param array<string, mixed> $input
     * @return int[]
     */
    private function _musicFolders(array $input, User $user): array
    {
        $catalogs = $user->get_catalogs('music');
        $sub_id   = $input['musicFolderId'] ?? null;
        if ($sub_id === null || $sub_id === '') {
            return $catalogs;
        }

        return array_values(array_intersect($catalogs, [(int) self::getAmpacheId((string) $sub_id)]));
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
     * _responseOutput
     *
     * Output a response or a default success response if no response is provided.
     * @param array<string, mixed> $input
     * @param array{'subsonic-response': array<string, mixed>}|SimpleXMLElement|null $response
     */
    private function _responseOutput(array $input, string $function, array|SimpleXMLElement|null $response = null): void
    {
        $format = (string) ($input['f'] ?? 'xml');
        switch ($format) {
            case 'json':
                $response = (is_array($response))
                    ? $response
                    : $this->_addJsonResponse($function);
                $this->_jsonOutput($response);
                break;
            case 'jsonp':
                $response = (is_array($response))
                    ? $response
                    : $this->_addJsonResponse($function);
                $callback = (string) ($input['callback'] ?? 'jsonp');
                $this->_jsonpOutput($response, $callback);
                break;
            default:
                $response = ($response instanceof SimpleXMLElement)
                    ? $response
                    : $this->_addXmlResponse($function);
                $this->_xmlOutput($response);
                break;
        }
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
        $musicFolderId = $this->_musicFolderId($input, $user);

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
     * _setStar
     * @param array<string, mixed> $input
     */
    private function _setStar(array $input, User $user, bool $star): void
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
            $this->_errorOutput($input, self::SSERROR_MISSINGPARAM, __FUNCTION__);

            return;
        }

        foreach ($objects as $object) {
            $flag = new Userflag($object['id'], $object['type']);
            $flag->set_flag($star, $user->id);
        }

        $this->_responseOutput($input, __FUNCTION__);
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

    /**
     * _xmlOutput
     */
    private function _xmlOutput(SimpleXMLElement $xml): void
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
            $output = "<subsonic-response status=\"failed\" " . "version=\"1.16.1\">"
                . "<error code=\"" . Subsonic_Api::SSERROR_GENERIC . "\" message=\"Error creating response.\"/>"
                . "</subsonic-response>";
        }

        header("Content-type: text/xml; charset=" . AmpConfig::get('site_charset', 'UTF-8'));
        header("Access-Control-Allow-Origin: *");
        echo $output;
    }
}
