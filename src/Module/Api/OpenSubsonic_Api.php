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

namespace Ampache\Module\Api;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Playback\Stream;
use Ampache\Module\System\Core;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\PrivateMsg;
use Ampache\Repository\Model\Random;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use DOMDocument;
use SimpleXMLElement;
use WpOrg\Requests\Requests;

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
     * List of internal functions that should be skipped when called from SubsonicApiApplication
     * @var string[]
     */
    public const array SYSTEM_LIST = [
        '_albumList',
        '_apiOutput',
        '_apiOutput2',
        '_check_parameter',
        '_decryptPassword',
        '_errorOutput',
        '_follow_stream',
        '_getAlbumId',
        '_getAmpacheId',
        '_getAmpacheObject',
        '_getAmpacheType',
        '_getArtistId',
        '_getBookmarkId',
        '_getCatalogId',
        '_getChatId',
        '_getGenreId',
        '_getLiveStreamId',
        '_getPlaylistId',
        '_getPodcastEpisodeId',
        '_getPodcastId',
        '_getShareId',
        '_getSmartPlaylistId',
        '_getSongId',
        '_getUserId',
        '_getVideoId',
        '_jsonOutput',
        '_jsonpOutput',
        '_output_body',
        '_output_header',
        '_setStar',
        '_updatePlaylist',
        '_xml2json',
        '_xmlOutput',
        'error',
    ];

    public const int SSERROR_GENERIC                = 0; // A generic error.
    public const int SSERROR_MISSINGPARAM           = 10; // Required parameter is missing.
    public const int SSERROR_APIVERSION_CLIENT      = 20; // Incompatible Subsonic REST protocol version. Client must upgrade.
    public const int SSERROR_APIVERSION_SERVER      = 30; // Incompatible Subsonic REST protocol version. Server must upgrade.
    public const int SSERROR_BADAUTH                = 40; // Wrong username or password.
    public const int SSERROR_TOKENAUTHNOTSUPPORTED  = 41; // Token authentication not supported for LDAP users.
    public const int SSERROR_AUTHMETHODNOTSUPPORTED = 42; // [OPENSUBSONIC] Provided authentication mechanism not supported.
    public const int SSERROR_AUTHMETHODCONFLICT     = 43; // [OPENSUBSONIC] Multiple conflicting authentication mechanisms provided.
    public const int SSERROR_BADAPIKEY              = 44; // [OPENSUBSONIC] Invalid API key.
    public const int SSERROR_UNAUTHORIZED           = 50; // User is not authorized for the given operation.
    public const int SSERROR_TRIAL                  = 60; // The trial period for the Subsonic server is over. Please upgrade to Subsonic Premium. Visit subsonic.org for details.
    public const int SSERROR_DATA_NOTFOUND          = 70; // The requested data was not found.

    /**
     * Ampache doesn't have a global unique id but items are unique per category. We use id prefixes to identify item category.
     */
    public const string SUBID_ALBUM      = 'al-';
    public const string SUBID_ARTIST     = 'ar-';
    public const string SUBID_BOOKMARK   = 'bo-';
    public const string SUBID_CATALOG    = 'mf-';
    public const string SUBID_CHAT       = 'pm-';
    public const string SUBID_GENRE      = 'ta-';
    public const string SUBID_LIVESTREAM = 'li-';
    public const string SUBID_PLAYLIST   = 'pl-';
    public const string SUBID_PODCAST    = 'po-';
    public const string SUBID_PODCASTEP  = 'pe-';
    public const string SUBID_SHARE      = 'sh-';
    public const string SUBID_SMARTPL    = 'sp-';
    public const string SUBID_SONG       = 'so-';
    public const string SUBID_USER       = 'us-';
    public const string SUBID_VIDEO      = 'vi-';

    public static function _getAlbumId(int|string $ampache_id): string
    {
        return self::SUBID_ALBUM . $ampache_id;
    }

    public static function _getArtistId(int|string $ampache_id): string
    {
        return self::SUBID_ARTIST . $ampache_id;
    }

    public static function _getBookmarkId(int|string $ampache_id): string
    {
        return self::SUBID_BOOKMARK . $ampache_id;
    }

    public static function _getCatalogId(int|string $ampache_id): string
    {
        return self::SUBID_CATALOG . $ampache_id;
    }

    public static function _getChatId(int|string $ampache_id): string
    {
        return self::SUBID_CHAT . $ampache_id;
    }

    public static function _getGenreId(int|string $ampache_id): string
    {
        return self::SUBID_GENRE . $ampache_id;
    }

    public static function _getLiveStreamId(int|string $ampache_id): string
    {
        return self::SUBID_LIVESTREAM . $ampache_id;
    }

    public static function _getPlaylistId(int|string $ampache_id): string
    {
        return self::SUBID_PLAYLIST . $ampache_id;
    }

    public static function _getPodcastId(int|string $ampache_id): string
    {
        return self::SUBID_PODCAST . $ampache_id;
    }

    public static function _getPodcastEpisodeId(int|string $ampache_id): string
    {
        return self::SUBID_PODCASTEP . $ampache_id;
    }
    public static function _getShareId(int|string $ampache_id): string
    {
        return self::SUBID_SHARE . $ampache_id;
    }

    public static function _getSmartPlaylistId(int|string $ampache_id): string
    {
        return self::SUBID_SMARTPL . $ampache_id;
    }

    public static function _getSongId(int|string $ampache_id): string
    {
        return self::SUBID_SONG . $ampache_id;
    }

    public static function _getUserId(int|string $ampache_id): string
    {
        return self::SUBID_USER . $ampache_id;
    }

    public static function _getVideoId(int|string $ampache_id): string
    {
        return self::SUBID_VIDEO . $ampache_id;
    }

    /**
     * _getAmpacheObject
     * Return the Ampache media object
     */
    public static function _getAmpacheObject(string $object_id): ?object
    {
        switch (substr($object_id, 0, 3)) {
            case self::SUBID_ALBUM:
                return new Album((int)self::_getAmpacheId($object_id));
            case self::SUBID_ARTIST:
                return new Artist((int)self::_getAmpacheId($object_id));
            case self::SUBID_BOOKMARK:
                return new Bookmark((int)self::_getAmpacheId($object_id));
            case self::SUBID_CATALOG:
                return Catalog::create_from_id((int)self::_getAmpacheId($object_id));
            case self::SUBID_CHAT:
                return new PrivateMsg((int)self::_getAmpacheId($object_id));
            case self::SUBID_GENRE:
                return new Tag((int)self::_getAmpacheId($object_id));
            case self::SUBID_LIVESTREAM:
                return new Live_Stream((int)self::_getAmpacheId($object_id));
            case self::SUBID_PLAYLIST:
                return new Playlist((int)self::_getAmpacheId($object_id));
            case self::SUBID_PODCAST:
                return new Podcast((int)self::_getAmpacheId($object_id));
            case self::SUBID_PODCASTEP:
                return new Podcast_Episode((int)self::_getAmpacheId($object_id));
            case self::SUBID_SHARE:
                return new Share((int)self::_getAmpacheId($object_id));
            case self::SUBID_SMARTPL:
                return new Search((int)self::_getAmpacheId($object_id));
            case self::SUBID_SONG:
                return new Song((int)self::_getAmpacheId($object_id));
            case self::SUBID_USER:
                return new User((int)self::_getAmpacheId($object_id));
            case self::SUBID_VIDEO:
                return new Video((int)self::_getAmpacheId($object_id));
        }
        debug_event(self::class, 'Couldn\'t identify Ampache object from ' . $object_id, 5);

        return null;
    }

    /**
     * _getAmpacheId
     */
    public static function _getAmpacheId(string $object_id): ?int
    {
        $ampache_id = substr($object_id, 3) ?: null;
        if (is_numeric($ampache_id)) {
            return (int)$ampache_id;
        }

        return null;
    }

    /**
     * _getAmpacheType
     */
    public static function _getAmpacheType(string $object_id): string
    {
        switch (substr($object_id, 0, 3)) {
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
            default:
                return "";
        }
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

    public static function _decryptPassword(string $password): string
    {
        // Decode hex-encoded password
        $encpwd = strpos($password, "enc:");
        if ($encpwd !== false) {
            $hex    = substr($password, 4);
            $decpwd = '';
            for ($count = 0; $count < strlen((string)$hex); $count += 2) {
                $decpwd .= chr((int)hexdec(substr($hex, $count, 2)));
            }
            $password = $decpwd;
        }

        return $password;
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
                "<error code=\"" . OpenSubsonic_Api::SSERROR_GENERIC . "\" message=\"Error creating response.\"/>" .
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
            $output = json_encode(OpenSubsonic_Json_Data::addError(self::SSERROR_GENERIC, 'system'), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
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
            $output = json_encode(OpenSubsonic_Json_Data::addError(self::SSERROR_GENERIC, 'system'), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '';
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
                self::_jsonOutput(OpenSubsonic_Json_Data::addError($errorCode, $function));
                break;
            case 'jsonp':
                $callback = (string)($input['callback'] ?? 'jsonp');
                self::_jsonpOutput(OpenSubsonic_Json_Data::addError($errorCode, $function), $callback);
                break;
            default:
                self::_xmlOutput(OpenSubsonic_Xml_Data::addError($errorCode, $function));
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

    ///**
    // * addChatMessage
    // *
    // * Adds a message to the chat log.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function addChatMessage(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * changePassword
    // *
    // * Changes the password of an existing user on the server.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function changePassword(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * createBookmark
    // *
    // * Creates or updates a bookmark.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function createBookmark(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * createInternetRadioStation
    // *
    // * Adds a new internet radio station.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function createInternetRadioStation(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * createPlaylist
    // *
    // * Creates (or updates) a playlist.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function createPlaylist(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * createPodcastChannel
    // *
    // * Adds a new Podcast channel.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function createPodcastChannel(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * createShare
    // *
    // * Creates a public URL that can be used by anyone to stream music or video from the server.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function createShare(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * createUser
    // *
    // * Creates a new user on the server.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function createUser(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * deleteBookmark
    // *
    // * Creates or updates a bookmark.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function deleteBookmark(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * deleteInternetRadioStation
    // *
    // * Deletes an existing internet radio station.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function deleteInternetRadioStation(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * deletePlaylist
    // *
    // * Deletes a saved playlist.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function deletePlaylist(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * deletePodcastChannel
    // *
    // * Deletes a Podcast channel.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function deletePodcastChannel(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * deletePodcastEpisode
    // *
    // * Deletes a Podcast episode.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function deletePodcastEpisode(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * deleteShare
    // *
    // * Deletes an existing share.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function deleteShare(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * deleteUser
    // *
    // * Deletes an existing user on the server.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function deleteUser(array $input, User $user): void
    //{
    //}

    /**
     * download
     *
     * Downloads a given media file.
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function download(array $input, User $user): void
    {
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            self::_errorOutput($input, self::SSERROR_MISSINGPARAM, __FUNCTION__);

            return;
        }

        $object = self::_getAmpacheObject($sub_id);
        if (($object instanceof Song || $object instanceof Podcast_episode) === false) {
            self::_errorOutput($input, self::SSERROR_DATA_NOTFOUND, __FUNCTION__);

            return;
        }

        $client = scrub_in((string) ($input['c'] ?? 'Subsonic'));
        $params = '&client=' . rawurlencode($client) . '&cache=1';

        self::_follow_stream($object->play_url($params, 'api', function_exists('curl_version'), $user->id, $user->streamtoken));
    }

    ///**
    // * downloadPodcastEpisode
    // *
    // * Request the server to start downloading a given Podcast episode.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function downloadPodcastEpisode(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getAlbum
    // *
    // * Returns details for an album.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getAlbum(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getAlbumInfo
    // *
    // * Returns album info.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getAlbumInfo(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getAlbumInfo2
    // *
    // * Returns album info.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getAlbumInfo2(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getAlbumList
    // *
    // * Returns a list of random, newest, highest rated etc. albums.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getAlbumList(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getAlbumList2
    // *
    // * Returns a list of random, newest, highest rated etc. albums.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getAlbumList2(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getArtist
    // *
    // * Returns details for an artist.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getArtist(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getArtistInfo
    // *
    // * Returns artist info.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getArtistInfo(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getArtistInfo2
    // *
    // * Returns artist info.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getArtistInfo2(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getArtists
    // *
    // * Returns all artists.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getArtists(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getAvatar
    // *
    // * Returns the avatar (personal image) for a user.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getAvatar(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getBookmarks
    // *
    // * Returns all bookmarks for this user.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getBookmarks(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getCaptions
    // *
    // * Returns captions (subtitles) for a video.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getCaptions(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getChatMessages
    // *
    // * Returns the current visible (non-expired) chat messages.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getChatMessages(array $input, User $user): void
    //{
    //}
    //
    /**
     * getCoverArt
     *
     * Returns a cover art image.
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function getcoverart(array $input, User $user): void
    {
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        if ($sub_id === false) {
            self::_errorOutput($input, self::SSERROR_MISSINGPARAM, __FUNCTION__);

            return;
        }

        $object_id   = self::_getAmpacheId($sub_id);
        $object_type = self::_getAmpacheType($sub_id);
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
        header('Content-Length: ' . strlen((string) $image));
        echo $image;
    }

    ///**
    // * getGenres
    // *
    // * Returns all genres.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getGenres(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getIndexes
    // *
    // * Returns an indexed structure of all artists.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getIndexes(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getInternetRadioStations
    // *
    // * Returns all internet radio stations.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getInternetRadioStations(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getLicense
    // *
    // * Get details about the software license.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getLicense(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getLyrics
    // *
    // * Searches for and returns lyrics for a given song.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getLyrics(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getLyricsBySongId
    // *
    // * Add support for synchronized lyrics, multiple languages, and retrieval by song ID
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getLyricsBySongId(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getMusicDirectory
    // *
    // * Returns a listing of all files in a music directory.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getMusicDirectory(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getMusicFolders
    // *
    // * Returns all configured top-level music folders.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getMusicFolders(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getNewestPodcasts
    // *
    // * Returns the most recently published Podcast episodes.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getNewestPodcasts(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getNowPlaying
    // *
    // * Returns what is currently being played by all users.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getNowPlaying(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getOpenSubsonicExtensions
    // *
    // * List the OpenSubsonic extensions supported by this server.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getOpenSubsonicExtensions(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getPlaylist
    // *
    // * Returns a listing of files in a saved playlist.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getPlaylist(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getPlaylists
    // *
    // * Returns all playlists a user is allowed to play.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getPlaylists(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getPlayQueue
    // *
    // * Returns the state of the play queue for this user.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getPlayQueue(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getPodcastEpisode
    // *
    // * Returns details for a podcast episode.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getPodcastEpisode(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getPodcasts
    // *
    // * Returns all Podcast channels the server subscribes to, and (optionally) their episodes.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getPodcasts(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getRandomSongs
    // *
    // * Returns random songs matching the given criteria.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getRandomSongs(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getScanStatus
    // *
    // * Returns the current status for media library scanning.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getScanStatus(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getShares
    // *
    // * Returns information about shared media this user is allowed to manage.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getShares(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getSimilarSongs
    // *
    // * Returns a random collection of songs from the given artist and similar artists.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getSimilarSongs(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getSimilarSongs2
    // *
    // * Returns a random collection of songs from the given artist and similar artists.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getSimilarSongs2(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getSong
    // *
    // * Returns details for a song.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getSong(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getSongsByGenre
    // *
    // * Returns songs in a given genre.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getSongsByGenre(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getStarred
    // *
    // * Returns starred songs, albums and artists.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getStarred(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getStarred2
    // *
    // * Returns starred songs, albums and artists.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getStarred2(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getTopSongs
    // *
    // * Returns top songs for the given artist.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getTopSongs(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getUser
    // *
    // * Get details about a given user, including which authorization roles and folder access it has.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getUser(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getUsers
    // *
    // * Get details about all users, including which authorization roles and folder access they have.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getUsers(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getVideoInfo
    // *
    // * Returns details for a video.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getVideoInfo(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * getVideos
    // *
    // * Returns all video files.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function getVideos(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * hls
    // *
    // * Downloads a given media file.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function hls(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * jukeboxControl
    // *
    // * Controls the jukebox, i.e., playback directly on the server’s audio hardware.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function jukeboxControl(array $input, User $user): void
    //{
    //}

    /**
     * ping
     *
     * Used to test connectivity with the server.
     *  https://opensubsonic.netlify.app/docs/endpoints/ping/
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function ping(array $input, User $user): void
    {
        unset($user);

        $format = (string)($input['f'] ?? 'xml');
        switch ($format) {
            case 'json':
                self::_jsonOutput(OpenSubsonic_Json_Data::addResponse(__FUNCTION__));
                break;
            case 'jsonp':
                $callback = (string)($input['callback'] ?? 'jsonp');
                self::_jsonpOutput(OpenSubsonic_Json_Data::addResponse(__FUNCTION__), $callback);
                break;
            default:
                self::_xmlOutput(OpenSubsonic_Xml_Data::addResponse(__FUNCTION__));
                break;
        }
    }

    ///**
    // * refreshPodcasts
    // *
    // * Requests the server to check for new Podcast episodes.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function refreshPodcasts(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * savePlayQueue
    // *
    // * Saves the state of the play queue for this user.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function savePlayQueue(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * scrobble
    // *
    // * Registers the local playback of one or more media files.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function scrobble(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * search
    // *
    // * Returns a listing of files matching the given search criteria. Supports paging through the result.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function search(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * search2
    // *
    // * Returns a listing of files matching the given search criteria. Supports paging through the result.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function search2(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * search3
    // *
    // * Returns albums, artists and songs matching the given search criteria. Supports paging through the result.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function search3(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * setRating
    // *
    // * Sets the rating for a music file.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function setRating(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * star
    // *
    // * Attaches a star to a song, album or artist.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function star(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * startScan
    // *
    // * Initiates a rescan of the media libraries.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function startScan(array $input, User $user): void
    //{
    //}

    /**
     * stream
     *
     * Streams a given media file.
     * @param array<string, mixed> $input
     * @param User $user
     */
    public static function stream(array $input, User $user): void
    {
        $sub_id = self::_check_parameter($input, 'id', __FUNCTION__);
        $object = self::_getAmpacheObject($sub_id);
        if (($object instanceof Song || $object instanceof Podcast_episode) === false) {
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

        // No scrobble for streams using open subsonic https://opensubsonic.netlify.app/docs/endpoints/stream/
        $params .= '&cache=1';

        self::_follow_stream($object->play_url($params, 'api', function_exists('curl_version'), $user->id, $user->streamtoken));
    }

    ///**
    // * tokenInfo
    // *
    // * Returns information about an API key.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function tokenInfo(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * unstar
    // *
    // * Attaches a star to a song, album or artist.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function unstar(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * updateInternetRadioStation
    // *
    // * Updates an existing internet radio station.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function updateInternetRadioStation(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * updatePlaylist
    // *
    // * Updates a playlist. Only the owner of a playlist is allowed to update it.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function updatePlaylist(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * updateShare
    // *
    // * Updates the description and/or expiration date for an existing share.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function updateShare(array $input, User $user): void
    //{
    //}
    //
    ///**
    // * updateUser
    // *
    // * Modifies an existing user on the server.
    // * @param array<string, mixed> $input
    // * @param User $user
    // */
    //public static function updateUser(array $input, User $user): void
    //{
    //}
}
