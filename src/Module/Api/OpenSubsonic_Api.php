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

use Ampache\Module\Api\OpenSubsonic\Handler\BookmarkHandlerInterface;
use Ampache\Module\Api\OpenSubsonic\Handler\BrowsingHandlerInterface;
use Ampache\Module\Api\OpenSubsonic\Handler\ChatHandlerInterface;
use Ampache\Module\Api\OpenSubsonic\Handler\InternetRadioHandlerInterface;
use Ampache\Module\Api\OpenSubsonic\Handler\PlaybackStateHandlerInterface;
use Ampache\Module\Api\OpenSubsonic\Handler\PlaylistHandlerInterface;
use Ampache\Module\Api\OpenSubsonic\Handler\PodcastHandlerInterface;
use Ampache\Module\Api\OpenSubsonic\Handler\RatingHandlerInterface;
use Ampache\Module\Api\OpenSubsonic\Handler\SearchHandlerInterface;
use Ampache\Module\Api\OpenSubsonic\Handler\ShareHandlerInterface;
use Ampache\Module\Api\OpenSubsonic\Handler\StreamingHandlerInterface;
use Ampache\Module\Api\OpenSubsonic\Handler\SystemHandlerInterface;
use Ampache\Module\Api\OpenSubsonic\Handler\UserHandlerInterface;
use Ampache\Module\Api\OpenSubsonic\OpenSubsonicResponseHandlerInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\Query\Smartlist;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\PrivateMsg;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;

/**
 * OpenSubsonic Class
 *
 * This class wraps Ampache to OpenSubsonic API functions. See https://opensubsonic.netlify.app/
 *
 * @SuppressWarnings("unused")
 */
class OpenSubsonic_Api
{
    public const string API_VERSION = "1.16.1";

    /**
     * Ampache doesn't have a global unique id but items are unique per category. We use id prefixes to identify item category.
     * TODO remove old subsonic ids in Ampache 8.0
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

    public const int SSERROR_AUTHMETHODCONFLICT = 43; // [OPENSUBSONIC] Multiple conflicting authentication mechanisms provided.

    public const int SSERROR_AUTHMETHODNOTSUPPORTED = 42; // [OPENSUBSONIC] Provided authentication mechanism not supported.

    public const int SSERROR_BADAPIKEY = 44; // [OPENSUBSONIC] Invalid API key.

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

    private BookmarkHandlerInterface $bookmarkHandler;
    private BrowsingHandlerInterface $browsingHandler;
    private ChatHandlerInterface $chatHandler;
    private InternetRadioHandlerInterface $internetRadioHandler;
    private PlaybackStateHandlerInterface $playbackStateHandler;
    private PlaylistHandlerInterface $playlistHandler;
    private PodcastHandlerInterface $podcastHandler;
    private RatingHandlerInterface $ratingHandler;
    private OpenSubsonicResponseHandlerInterface $responseHandler;
    private SearchHandlerInterface $searchHandler;
    private ShareHandlerInterface $shareHandler;
    private StreamingHandlerInterface $streamingHandler;
    private SystemHandlerInterface $systemHandler;
    private UserHandlerInterface $userHandler;

    public function __construct(
        BookmarkHandlerInterface $bookmarkHandler,
        BrowsingHandlerInterface $browsingHandler,
        ChatHandlerInterface $chatHandler,
        InternetRadioHandlerInterface $internetRadioHandler,
        PlaybackStateHandlerInterface $playbackStateHandler,
        PlaylistHandlerInterface $playlistHandler,
        PodcastHandlerInterface $podcastHandler,
        RatingHandlerInterface $ratingHandler,
        OpenSubsonicResponseHandlerInterface $responseHandler,
        SearchHandlerInterface $searchHandler,
        ShareHandlerInterface $shareHandler,
        StreamingHandlerInterface $streamingHandler,
        SystemHandlerInterface $systemHandler,
        UserHandlerInterface $userHandler,
    ) {
        $this->bookmarkHandler      = $bookmarkHandler;
        $this->browsingHandler      = $browsingHandler;
        $this->chatHandler          = $chatHandler;
        $this->internetRadioHandler = $internetRadioHandler;
        $this->playbackStateHandler = $playbackStateHandler;
        $this->playlistHandler      = $playlistHandler;
        $this->podcastHandler       = $podcastHandler;
        $this->ratingHandler        = $ratingHandler;
        $this->responseHandler      = $responseHandler;
        $this->searchHandler        = $searchHandler;
        $this->shareHandler         = $shareHandler;
        $this->streamingHandler     = $streamingHandler;
        $this->systemHandler        = $systemHandler;
        $this->userHandler          = $userHandler;
    }

    public static function getAlbumSubId(int $ampache_id): string
    {
        return self::SUBID_ALBUM . $ampache_id;
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
        return self::SUBID_ARTIST . $ampache_id;
    }

    public static function getBookmarkSubId(int $ampache_id): string
    {
        return self::SUBID_BOOKMARK . $ampache_id;
    }

    public static function getCatalogSubId(int $ampache_id): string
    {
        return self::SUBID_CATALOG . $ampache_id;
    }

    public static function getChatSubId(int $ampache_id): string
    {
        return self::SUBID_CHAT . $ampache_id;
    }

    public static function getFolderSubId(int $ampache_id): string
    {
        return self::SUBID_FOLDER . $ampache_id;
    }

    public static function getGenreSubId(int $ampache_id): string
    {
        return self::SUBID_GENRE . $ampache_id;
    }

    public static function getLiveStreamSubId(int $ampache_id): string
    {
        return self::SUBID_LIVESTREAM . $ampache_id;
    }

    public static function getPlaylistSubId(int $ampache_id): string
    {
        return self::SUBID_PLAYLIST . $ampache_id;
    }

    public static function getPodcastEpisodeSubId(int $ampache_id): string
    {
        return self::SUBID_PODCASTEP . $ampache_id;
    }

    public static function getPodcastSubId(int $ampache_id): string
    {
        return self::SUBID_PODCAST . $ampache_id;
    }

    public static function getShareSubId(int $ampache_id): string
    {
        return self::SUBID_SHARE . $ampache_id;
    }

    public static function getSmartPlaylistSubId(int $ampache_id): string
    {
        return self::SUBID_SMARTPL . $ampache_id;
    }

    public static function getSongSubId(int $ampache_id): string
    {
        return self::SUBID_SONG . $ampache_id;
    }

    public static function getUserSubId(int $ampache_id): string
    {
        return self::SUBID_USER . $ampache_id;
    }

    public static function getVideoSubId(int $ampache_id): string
    {
        return self::SUBID_VIDEO . $ampache_id;
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
     * @param array<string, mixed> $input
     */
    public function createplaylist(array $input, User $user): void
    {
        $this->playlistHandler->createplaylist($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function createpodcastchannel(array $input, User $user): void
    {
        $this->podcastHandler->createpodcastchannel($input, $user);
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
     * @param array<string, mixed> $input
     */
    public function deleteplaylist(array $input, User $user): void
    {
        $this->playlistHandler->deleteplaylist($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function deletepodcastchannel(array $input, User $user): void
    {
        $this->podcastHandler->deletepodcastchannel($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function deletepodcastepisode(array $input, User $user): void
    {
        $this->podcastHandler->deletepodcastepisode($input, $user);
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
     * https://opensubsonic.netlify.app/docs/endpoints/download/
     * @param array<string, mixed> $input
     */
    public function download(array $input, User $user): void
    {
        $this->streamingHandler->download($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function downloadpodcastepisode(array $input, User $user): void
    {
        $this->podcastHandler->downloadpodcastepisode($input, $user);
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
     * findSonicPath [OS] //TODO
     * https://opensubsonic.netlify.app/docs/endpoints/findsonicpath/
     * @param array<string, mixed> $input
     */
    public function findSonicPath(array $input, User $user): void
    {
        $this->browsingHandler->findSonicPath($input, $user);
    }

    /**
     * getAlbum
     *
     * Returns details for an album.
     * https://opensubsonic.netlify.app/docs/endpoints/getalbum/
     * @param array<string, mixed> $input
     */
    public function getalbum(array $input, User $user): void
    {
        $this->browsingHandler->getalbum($input, $user);
    }

    /**
     * getAlbumInfo
     *
     * Returns album info.
     * https://opensubsonic.netlify.app/docs/endpoints/getalbuminfo/
     * @param array<string, mixed> $input
     */
    public function getalbuminfo(array $input, User $user): void
    {
        $this->browsingHandler->getalbuminfo($input, $user);
    }

    /**
     * getAlbumInfo2
     *
     * Returns album info.
     * https://opensubsonic.netlify.app/docs/endpoints/getalbuminfo2/
     * @param array<string, mixed> $input
     */
    public function getalbuminfo2(array $input, User $user): void
    {
        $this->browsingHandler->getalbuminfo2($input, $user);
    }

    /**
     * getAlbumList
     *
     * Returns a list of random, newest, highest rated etc. albums.
     * https://opensubsonic.netlify.app/docs/endpoints/getalbumlist/
     * @param array<string, mixed> $input
     */
    public function getalbumlist(array $input, User $user): void
    {
        $this->browsingHandler->getalbumlist($input, $user);
    }

    /**
     * getAlbumList2
     *
     * Returns a list of random, newest, highest rated etc. albums.
     * https://opensubsonic.netlify.app/docs/endpoints/getalbumlist2/
     * @param array<string, mixed> $input
     */
    public function getalbumlist2(array $input, User $user): void
    {
        $this->browsingHandler->getalbumlist2($input, $user);
    }

    /**
     * getArtist
     *
     * Returns details for an artist.
     * https://opensubsonic.netlify.app/docs/endpoints/getartist/
     * @param array<string, mixed> $input
     */
    public function getartist(array $input, User $user): void
    {
        $this->browsingHandler->getartist($input, $user);
    }

    /**
     * getArtistInfo
     *
     * Returns artist info.
     * https://opensubsonic.netlify.app/docs/endpoints/getartistinfo/
     * @param array<string, mixed> $input
     */
    public function getartistinfo(array $input, User $user): void
    {
        $this->browsingHandler->getartistinfo($input, $user);
    }

    /**
     * getArtistInfo2
     *
     * Returns artist info.
     * https://opensubsonic.netlify.app/docs/endpoints/getartistinfo2/
     * @param array<string, mixed> $input
     */
    public function getartistinfo2(array $input, User $user): void
    {
        $this->browsingHandler->getartistinfo2($input, $user);
    }

    /**
     * getArtists
     *
     * Returns all artists.
     * https://opensubsonic.netlify.app/docs/endpoints/getartists/
     * @param array<string, mixed> $input
     */
    public function getartists(array $input, User $user): void
    {
        $this->browsingHandler->getartists($input, $user);
    }

    /**
     * getAvatar
     *
     * Returns the avatar (personal image) for a user.
     * https://opensubsonic.netlify.app/docs/endpoints/getavatar/
     * @param array<string, mixed> $input
     */
    public function getavatar(array $input, User $user): void
    {
        $this->streamingHandler->getavatar($input, $user);
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
     * https://opensubsonic.netlify.app/docs/endpoints/getcaptions/
     * @param array<string, mixed> $input
     */
    public function getcaptions(array $input, User $user): void
    {
        $this->streamingHandler->getcaptions($input, $user);
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
     * https://opensubsonic.netlify.app/docs/endpoints/getcoverart/
     * @param array<string, mixed> $input
     */
    public function getcoverart(array $input, User $user): void
    {
        $this->streamingHandler->getcoverart($input, $user);
    }

    /**
     * getGenres
     *
     * Returns all genres.
     * https://opensubsonic.netlify.app/docs/endpoints/getgenres/
     * @param array<string, mixed> $input
     */
    public function getgenres(array $input, User $user): void
    {
        $this->browsingHandler->getgenres($input, $user);
    }

    /**
     * getIndexes
     *
     * Returns an indexed structure of all artists.
     * https://opensubsonic.netlify.app/docs/endpoints/getindexes/
     * @param array<string, mixed> $input
     */
    public function getindexes(array $input, User $user): void
    {
        $this->browsingHandler->getindexes($input, $user);
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
     * https://opensubsonic.netlify.app/docs/endpoints/getlyrics/
     * @param array<string, mixed> $input
     */
    public function getlyrics(array $input, User $user): void
    {
        $this->streamingHandler->getlyrics($input, $user);
    }

    /**
     * getLyricsBySongId
     *
     * Add support for synchronized lyrics, multiple languages, and retrieval by song ID
     * https://opensubsonic.netlify.app/docs/endpoints/getlyricsbysongid/
     * @param array<string, mixed> $input
     */
    public function getlyricsbysongid(array $input, User $user): void
    {
        $this->streamingHandler->getlyricsbysongid($input, $user);
    }

    /**
     * getMusicDirectory
     *
     * Returns a listing of all files in a music directory.
     * https://opensubsonic.netlify.app/docs/endpoints/getmusicdirectory/
     * @param array<string, mixed> $input
     */
    public function getmusicdirectory(array $input, User $user): void
    {
        $this->browsingHandler->getmusicdirectory($input, $user);
    }

    /**
     * getMusicFolders
     *
     * Returns all configured top-level music folders.
     * https://opensubsonic.netlify.app/docs/endpoints/getmusicfolders/
     * @param array<string, mixed> $input
     */
    public function getmusicfolders(array $input, User $user): void
    {
        $this->browsingHandler->getmusicfolders($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function getnewestpodcasts(array $input, User $user): void
    {
        $this->podcastHandler->getnewestpodcasts($input, $user);
    }

    /**
     * getNowPlaying
     *
     * Returns what is currently being played by all users.
     * https://opensubsonic.netlify.app/docs/endpoints/getnowplaying/
     * @param array<string, mixed> $input
     */
    public function getnowplaying(array $input, User $user): void
    {
        $this->playbackStateHandler->getnowplaying($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function getopensubsonicextensions(array $input, User $user): void
    {
        $this->systemHandler->getopensubsonicextensions($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function getplaylist(array $input, User $user): void
    {
        $this->playlistHandler->getplaylist($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function getplaylists(array $input, User $user): void
    {
        $this->playlistHandler->getplaylists($input, $user);
    }

    /**
     * getPlayQueue
     *
     * Returns the state of the play queue for this user.
     * https://opensubsonic.netlify.app/docs/endpoints/getplayqueue/
     * @param array<string, mixed> $input
     */
    public function getplayqueue(array $input, User $user): void
    {
        $this->playbackStateHandler->getplayqueue($input, $user);
    }

    /**
     * getPlayQueueByIndex
     *
     * Returns the state of the play queue for this user.
     * https://opensubsonic.netlify.app/docs/endpoints/getplayqueue/
     * @param array<string, mixed> $input
     */
    public function getplayqueuebyindex(array $input, User $user): void
    {
        $this->playbackStateHandler->getplayqueuebyindex($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function getpodcastepisode(array $input, User $user): void
    {
        $this->podcastHandler->getpodcastepisode($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function getpodcasts(array $input, User $user): void
    {
        $this->podcastHandler->getpodcasts($input, $user);
    }

    /**
     * getRandomSongs
     *
     * Returns random songs matching the given criteria.
     * https://opensubsonic.netlify.app/docs/endpoints/getrandomsongs/
     * @param array<string, mixed> $input
     */
    public function getrandomsongs(array $input, User $user): void
    {
        $this->browsingHandler->getrandomsongs($input, $user);
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
     * https://opensubsonic.netlify.app/docs/endpoints/getsimilarsongs/
     * @param array<string, mixed> $input
     */
    public function getsimilarsongs(array $input, User $user, string $elementName = 'similarSongs'): void
    {
        $this->browsingHandler->getsimilarsongs($input, $user, $elementName);
    }

    /**
     * getSimilarSongs2
     *
     * Returns a random collection of songs from the given artist and similar artists.
     * https://opensubsonic.netlify.app/docs/endpoints/getsimilarsongs2/
     * @param array<string, mixed> $input
     */
    public function getsimilarsongs2(array $input, User $user): void
    {
        $this->browsingHandler->getsimilarsongs2($input, $user);
    }

    /**
     * getSong
     *
     * Returns details for a song.
     * https://opensubsonic.netlify.app/docs/endpoints/getsong/
     * @param array<string, mixed> $input
     */
    public function getsong(array $input, User $user): void
    {
        $this->browsingHandler->getsong($input, $user);
    }

    /**
     * getSongsByGenre
     *
     * Returns songs in a given genre.
     * https://opensubsonic.netlify.app/docs/endpoints/getsongsbygenre/
     * @param array<string, mixed> $input
     */
    public function getsongsbygenre(array $input, User $user): void
    {
        $this->browsingHandler->getsongsbygenre($input, $user);
    }

    /**
     * getSonicSimilarTracks [OS] //TODO
     * https://opensubsonic.netlify.app/docs/endpoints/getsonicsimilartracks/
     * @param array<string, mixed> $input
     */
    public function getSonicSimilarTracks(array $input, User $user): void
    {
        $this->browsingHandler->getSonicSimilarTracks($input, $user);
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
     * https://opensubsonic.netlify.app/docs/endpoints/gettopsongs/
     * @param array<string, mixed> $input
     */
    public function gettopsongs(array $input, User $user): void
    {
        $this->browsingHandler->gettopsongs($input, $user);
    }

    /**
     * getTranscodeDecision [OS]
     *
     * OpenSubsonic extension `transcoding`. Reports whether a client can play a media item as it stands, and what it
     * would be given instead. POST only, because the client capabilities arrive as a JSON body.
     * https://opensubsonic.netlify.app/docs/endpoints/gettranscodedecision/
     * @param array<string, mixed> $input
     */
    public function getTranscodeDecision(array $input, User $user): void
    {
        $this->streamingHandler->getTranscodeDecision($input, $user);
    }

    /**
     * getTranscodeStream [OS]
     *
     * OpenSubsonic extension `transcoding`. Streams a media item using the settings getTranscodeDecision resolved,
     * carried in the opaque `transcodeParams` token.
     * https://opensubsonic.netlify.app/docs/endpoints/gettranscodestream/
     * @param array<string, mixed> $input
     */
    public function getTranscodeStream(array $input, User $user): void
    {
        $this->streamingHandler->getTranscodeStream($input, $user);
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
     * https://opensubsonic.netlify.app/docs/endpoints/getvideoinfo/
     * @param array<string, mixed> $input
     */
    public function getvideoinfo(array $input, User $user): void
    {
        $this->browsingHandler->getvideoinfo($input, $user);
    }

    /**
     * getVideos
     *
     * Returns all video files.
     * https://opensubsonic.netlify.app/docs/endpoints/getvideos/
     * @param array<string, mixed> $input
     */
    public function getvideos(array $input, User $user): void
    {
        $this->browsingHandler->getvideos($input, $user);
    }

    /**
     * hls
     *
     * Downloads a given media file.
     * https://opensubsonic.netlify.app/docs/endpoints/hls/
     * @param array<string, mixed> $input
     */
    public function hls(array $input, User $user): void
    {
        $this->streamingHandler->hls($input, $user);
    }

    /**
     * jukeboxControl
     *
     * Controls the jukebox, i.e., playback directly on the server’s audio hardware.
     * https://opensubsonic.netlify.app/docs/endpoints/jukeboxcontrol/
     * @param array<string, mixed> $input
     */
    public function jukeboxcontrol(array $input, User $user): void
    {
        $this->playbackStateHandler->jukeboxcontrol($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function ping(array $input, User $user): void
    {
        $this->systemHandler->ping($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function refreshpodcasts(array $input, User $user): void
    {
        $this->podcastHandler->refreshpodcasts($input, $user);
    }

    /**
     * reportPlayback [OS] //TODO
     * https://opensubsonic.netlify.app/docs/endpoints/reportplayback/
     * @param array<string, mixed> $input
     */
    public function reportPlayback(array $input, User $user): void
    {
        $this->playbackStateHandler->reportPlayback($input, $user);
    }

    /**
     * savePlayQueue [OS]
     *
     * Saves the state of the play queue for this user.
     * https://opensubsonic.netlify.app/docs/endpoints/saveplayqueue/
     * @param array<string, mixed> $input
     */
    public function saveplayqueue(array $input, User $user): void
    {
        $this->playbackStateHandler->saveplayqueue($input, $user);
    }

    /**
     * savePlayQueueByIndex [OS]
     *
     * Saves the state of the play queue for this user.
     * https://opensubsonic.netlify.app/docs/endpoints/saveplayqueuebyindex/
     * @param array<string, mixed> $input
     */
    public function saveplayqueuebyindex(array $input, User $user): void
    {
        $this->playbackStateHandler->saveplayqueuebyindex($input, $user);
    }

    /**
     * scrobble
     *
     * Registers the local playback of one or more media files.
     * https://opensubsonic.netlify.app/docs/endpoints/scrobble/
     * @param array<string, mixed> $input
     */
    public function scrobble(array $input, User $user): void
    {
        $this->playbackStateHandler->scrobble($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function search(array $input, User $user): void
    {
        $this->searchHandler->search($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function search2(array $input, User $user): void
    {
        $this->searchHandler->search2($input, $user);
    }

    /**
     * @param array<string, mixed> $input
     */
    public function search3(array $input, User $user): void
    {
        $this->searchHandler->search3($input, $user);
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
     * stream [OS]
     *
     * Streams a given media file.
     * https://opensubsonic.netlify.app/docs/endpoints/stream/
     * @param array<string, mixed> $input
     */
    public function stream(array $input, User $user): void
    {
        $this->streamingHandler->stream($input, $user);
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
     * @param array<string, mixed> $input
     */
    public function updateplaylist(array $input, User $user): void
    {
        $this->playlistHandler->updateplaylist($input, $user);
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
}
