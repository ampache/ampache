<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\Podcast\PodcastDeleterInterface;
use Ampache\Module\Podcast\PodcastEpisodeDownloaderInterface;
use Ampache\Module\Podcast\PodcastSyncerInterface;
use Ampache\Module\Podcast\Exception\PodcastCreationException;
use Ampache\Module\Podcast\PodcastCreatorInterface;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Core;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\Recommendation;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\LiveStreamRepositoryInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Live_Stream;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Random;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\User_Playlist;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\PodcastRepositoryInterface;
use Ampache\Repository\PrivateMessageRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use DOMDocument;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use WpOrg\Requests\Requests;
use SimpleXMLElement;

/**
 * Subsonic Class
 *
 * This class wrap Ampache to Subsonic API functions. See http://www.subsonic.org/pages/api.jsp
 *
 * @SuppressWarnings("unused")
 */
class Subsonic_Api
{
    // List of internal functions that should be skipped when called from SubsonicApiApplication
    public const SYSTEM_LIST = [
        '_albumList',
        '_apiOutput',
        '_apiOutput2',
        '_check_parameter',
        '_decrypt_password',
        '_follow_stream',
        '_hasNestedArray',
        '_output_body',
        '_output_header',
        '_setHeader',
        '_setStar',
        '_updatePlaylist',
        '_xml2json'
    ];

    private const ALWAYS_ARRAY = [
        'musicFolder',
        'channel',
        'artist',
        'child',
        'song',
        'album',
        'share',
        'entry'
    ];

    /**
     * check_parameter
     * @param array $input
     * @param string $parameter
     * @param bool $addheader
     * @return false|mixed
     */
    private static function _check_parameter($input, $parameter, $addheader = false)
    {
        if (empty($input[$parameter])) {
            ob_end_clean();
            if ($addheader) {
                self::_setHeader((string)($input['f'] ?? 'xml'));
            }
            self::_apiOutput(
                $input,
                Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_MISSINGPARAM, 'check_parameter')
            );

            return false;
        }

        return $input[$parameter];
    }

    /**
     * @param $password
     */
    public static function _decryptPassword($password): string
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
     * _output_body
     */
    public static function _output_body($curl, $data): int
    {
        unset($curl);
        echo $data;
        ob_flush();

        return strlen((string)$data);
    }

    /**
     * _output_header
     */
    public static function _output_header($curl, $header): int
    {
        $rheader = trim((string)$header);
        $rhpart  = explode(':', $rheader);
        if (!empty($rheader) && count($rhpart) > 1) {
            if ($rhpart[0] != "Transfer-Encoding") {
                header($rheader);
            }
        } elseif (substr($header, 0, 5) === "HTTP/") {
            // if $header starts with HTTP/ assume it's the status line
            http_response_code(curl_getinfo($curl, CURLINFO_HTTP_CODE));
        }

        return strlen((string)$header);
    }

    /**
     * _follow_stream
     * @param string $url
     */
    private static function _follow_stream($url): void
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
            $reqheaders   = array();
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
                    array(
                        CURLOPT_FAILONERROR => true,
                        CURLOPT_HTTPHEADER => $reqheaders,
                        CURLOPT_HEADER => false,
                        CURLOPT_RETURNTRANSFER => false,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_WRITEFUNCTION => array(
                            'Ampache\Module\Api\Subsonic_Api',
                            '_output_body'
                        ),
                        CURLOPT_HEADERFUNCTION => array(
                            'Ampache\Module\Api\Subsonic_Api',
                            '_output_header'
                        ),
                        // Ignore invalid certificate
                        // Default trusted chain is crap anyway and currently no custom CA option
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                        CURLOPT_TIMEOUT => 0
                    )
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
     * @param string $filetype
     */
    public static function _setHeader($filetype): void
    {
        if (strtolower((string)$filetype) == "json") {
            header("Content-type: application/json; charset=" . AmpConfig::get('site_charset'));
            Subsonic_Xml_Data::$enable_json_checks = true;
        } elseif (strtolower((string)$filetype) == "jsonp") {
            header("Content-type: text/javascript; charset=" . AmpConfig::get('site_charset'));
            Subsonic_Xml_Data::$enable_json_checks = true;
        } else {
            header("Content-type: text/xml; charset=" . AmpConfig::get('site_charset'));
        }
        header("Access-Control-Allow-Origin: *");
    }

    /**
     * _apiOutput
     * @param array $input
     * @param SimpleXMLElement $xml
     * @param array $alwaysArray
     */

    private static function _apiOutput($input, $xml, $alwaysArray = self::ALWAYS_ARRAY): void
    {
        $format   = strtolower($input['f'] ?? 'xml');
        $callback = $input['callback'] ?? $format;
        self::_apiOutput2($format, $xml, $callback, $alwaysArray);
    }

    /**
     * _apiOutput2
     * @param string $format
     * @param SimpleXMLElement $xml
     * @param string $callback
     * @param array $alwaysArray
     */
    public static function _apiOutput2($format, $xml, $callback = '', $alwaysArray = self::ALWAYS_ARRAY): void
    {
        $conf = array('alwaysArray' => $alwaysArray);
        if ($format == "json") {
            echo json_encode(self::_xml2Json($xml, $conf), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            return;
        }
        if ($format == "jsonp") {
            echo $callback . '(' . json_encode(self::_xml2Json($xml, $conf), JSON_PRETTY_PRINT) . ')';

            return;
        }
        $output = false;
        $xmlstr = $xml->asXml();
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
            $output = "<subsonic-response status=\"failed\" " . "version=\"1.16.1\" " . "type=\"ampache\" " . "serverVersion=\"" . Api::$version . "\"" . ">" .
                "<error code=\"0\" message=\"Error creating response.\"/>" .
                "</subsonic-response>";
        }
        echo $output;
    }

    /**
     * xml2json
     * [based from http://outlandish.com/blog/xml-to-json/]
     * Because we cannot use only json_encode to respect JSON Subsonic API
     * @param SimpleXMLElement $xml
     * @param array $input_options
     * @return array
     */
    private static function _xml2Json($xml, $input_options = array())
    {
        $defaults = array(
            'namespaceSeparator' => ' :', // you may want this to be something other than a colon
            'attributePrefix' => '', // to distinguish between attributes and nodes with the same name
            'alwaysArray' => array('musicFolder', 'channel', 'artist', 'child', 'song', 'album', 'share'), // array of xml tag names which should always become arrays
            'alwaysDouble' => array('averageRating'),
            'alwaysInteger' => array('albumCount', 'audioTrackId', 'bitRate', 'bookmarkPosition', 'code',
                'count', 'current', 'currentIndex', 'discNumber', 'duration', 'folder',
                'lastModified', 'maxBitRate', 'minutesAgo', 'offset', 'originalHeight',
                'originalWidth', 'playCount', 'playerId', 'position', 'size', 'songCount',
                'time', 'totalHits', 'track', 'userRating', 'visitCount', 'year'), // array of xml tag names which should always become integers
            'autoArray' => true, // only create arrays for tags which appear more than once
            'textContent' => 'value', // key used for the text content of elements
            'autoText' => true, // skip textContent key if node has no attributes or child nodes
            'keySearch' => false, // optional search and replace on tag and attribute names
            'keyReplace' => false, // replace values for above search values (as passed to str_replace())
            'boolean' => true // replace true and false string with boolean values
        );
        $options        = array_merge($defaults, $input_options);
        $namespaces     = $xml->getDocNamespaces();
        $namespaces[''] = null; // add base (empty) namespace
        // get attributes from all namespaces
        $attributesArray = array();
        foreach ($namespaces as $prefix => $namespace) {
            foreach ($xml->attributes($namespace) as $attributeName => $attribute) {
                // replace characters in attribute name
                if ($options['keySearch']) {
                    $attributeName = str_replace($options['keySearch'], $options['keyReplace'], $attributeName);
                }
                $attributeKey = $options['attributePrefix'] . ($prefix ? $prefix . $options['namespaceSeparator'] : '') . $attributeName;
                $strattr      = trim((string)$attribute);
                if ($options['boolean'] && ($strattr == "true" || $strattr == "false")) {
                    $vattr = ($strattr == "true");
                } else {
                    $vattr = $strattr;
                    if (in_array($attributeName, $options['alwaysInteger'])) {
                        $vattr = (int) $strattr;
                    }
                    if (in_array($attributeName, $options['alwaysDouble'])) {
                        $vattr = (float) $strattr;
                    }
                }
                $attributesArray[$attributeKey] = $vattr;
            }
        }

        // these children must be in an array.
        $forceArray = array('channel', 'share');
        // get child nodes from all namespaces
        $tagsArray = array();
        foreach ($namespaces as $prefix => $namespace) {
            foreach ($xml->children($namespace) as $childXml) {
                // recurse into child nodes
                $childArray = self::_xml2Json($childXml, $options);
                foreach ($childArray as $childTagName => $childProperties) {
                    // replace characters in tag name
                    if ($options['keySearch']) {
                        $childTagName = str_replace($options['keySearch'], $options['keyReplace'], $childTagName);
                    }
                    // add namespace prefix, if any
                    if ($prefix) {
                        $childTagName = $prefix . $options['namespaceSeparator'] . $childTagName;
                    }

                    if (!isset($tagsArray[$childTagName])) {
                        // plain strings aren't countable/nested
                        if (!is_string($childProperties)) {
                            // only entry with this key
                            if (count($childProperties) === 0) {
                                $tagsArray[$childTagName] = (object)$childProperties;
                            } elseif (self::_hasNestedArray($childProperties) && !in_array($childTagName, $forceArray)) {
                                $tagsArray[$childTagName] = (object)$childProperties;
                            } else {
                                // test if tags of this type should always be arrays, no matter the element count
                                $tagsArray[$childTagName] = in_array(
                                    $childTagName,
                                    $options['alwaysArray']
                                ) || !$options['autoArray'] ? array($childProperties) : $childProperties;
                            }
                        } else {
                            // test if tags of this type should always be arrays, no matter the element count
                            $tagsArray[$childTagName] = in_array(
                                $childTagName,
                                $options['alwaysArray']
                            ) || !$options['autoArray'] ? array($childProperties) : $childProperties;
                        }
                    } elseif (is_array($tagsArray[$childTagName]) && array_keys($tagsArray[$childTagName]) === range(0, count($tagsArray[$childTagName]) - 1)) {
                        //key already exists and is integer indexed array
                        $tagsArray[$childTagName][] = $childProperties;
                    } else {
                        //key exists so convert to integer indexed array with previous value in position 0
                        $tagsArray[$childTagName] = array($tagsArray[$childTagName], $childProperties);
                    }
                }
            } // REPLACING list($childTagName, $childProperties) = each($childArray);
        }

        // get text content of node
        $textContentArray = array();
        $plainText        = (string)$xml;
        if ($plainText !== '') {
            $textContentArray[$options['textContent']] = $plainText;
        }

        // stick it all together
        $propertiesArray = !$options['autoText'] || !empty($attributesArray) || !empty($tagsArray) || ($plainText === '') ? array_merge(
            $attributesArray,
            $tagsArray,
            $textContentArray
        ) : $plainText;

        if (isset($propertiesArray['xmlns'])) {
            unset($propertiesArray['xmlns']);
        }

        // return node as array
        return array(
            $xml->getName() => $propertiesArray
        );
    }

    /**
     * has_Nested_Array
     * Used for xml2json to detect a sub-array
     * @param $properties
     */
    private static function _hasNestedArray($properties): bool
    {
        foreach ($properties as $property) {
            if (is_array($property)) {
                return true;
            }
        }

        return false;
    }

    /**
     * ping
     * Used to test connectivity with the server.
     * http://www.subsonic.org/pages/api.jsp#ping
     * @param array $input
     * @param User $user
     */
    public static function ping($input, $user): void
    {
        unset($user);
        // Don't check client API version here. Some client give version 0.0.0 for ping command
        self::_apiOutput($input, Subsonic_Xml_Data::addSubsonicResponse('ping'));
    }

    /**
     * getLicense
     * Get details about the software license. (Always return a valid default license.)
     * Returns a <subsonic-response> element with a nested <license> element on success.
     * http://www.subsonic.org/pages/api.jsp#getLicense
     * @param array $input
     * @param User $user
     */
    public static function getlicense($input, $user): void
    {
        unset($user);
        $response = Subsonic_Xml_Data::addSubsonicResponse('getlicense');
        Subsonic_Xml_Data::addLicense($response);
        self::_apiOutput($input, $response);
    }

    /**
     * getMusicFolders
     * Returns all configured top-level music folders (Ampache catalogs).
     * Returns a <subsonic-response> element with a nested <musicFolders> element on success.
     * http://www.subsonic.org/pages/api.jsp#getMusicFolders
     * @param array $input
     * @param User $user
     */
    public static function getmusicfolders($input, $user): void
    {
        $catalogs = $user->get_catalogs('music');
        $response = Subsonic_Xml_Data::addSubsonicResponse('getmusicfolders');

        Subsonic_Xml_Data::addMusicFolders($response, $catalogs);
        self::_apiOutput($input, $response);
    }

    /**
     * getIndexes
     * Returns an indexed structure of all artists.
     * Returns a <subsonic-response> element with a nested <indexes> element on success.
     * http://www.subsonic.org/pages/api.jsp#getIndexes
     * @param array $input
     * @param User $user
     */
    public static function getindexes($input, $user): void
    {
        set_time_limit(300);

        $musicFolderId   = $input['musicFolderId'] ?? '-1';
        $ifModifiedSince = $input['ifModifiedSince'] ?? '';

        $catalogs = array();
        if (!empty($musicFolderId) && $musicFolderId != '-1') {
            $catalogs[] = $musicFolderId;
        } else {
            $catalogs = $user->get_catalogs('music');
        }

        $lastmodified = 0;
        $fcatalogs    = array();

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
                $fcatalogs[] = $catalogid;
            }
        }
        if (empty($ifModifiedSince)) {
            $fcatalogs = $catalogs;
        }

        $response = Subsonic_Xml_Data::addSubsonicResponse('getindexes');
        if (count($fcatalogs) > 0) {
            $artists = Catalog::get_artist_arrays($fcatalogs);
            Subsonic_Xml_Data::addIndexes($response, $artists, $lastmodified);
        }
        self::_apiOutput($input, $response);
    }

    /**
     * getMusicDirectory
     * Returns a listing of all files in a music directory. Typically used to get list of albums for an artist, or list of songs for an album.
     * Returns a <subsonic-response> element with a nested <directory> element on success.
     * http://www.subsonic.org/pages/api.jsp#getMusicDirectory
     * @param array $input
     * @param User $user
     */
    public static function getmusicdirectory($input, $user): void
    {
        unset($user);
        $object_id = $input['id'] ?? 0;
        $response  = Subsonic_Xml_Data::addSubsonicResponse('getmusicdirectory');
        if ((int)$object_id === 0) {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'getmusicdirectory');
        } elseif (Subsonic_Xml_Data::_isArtist($object_id)) {
            Subsonic_Xml_Data::addDirectory($response, $object_id, 'artist');
        } elseif (Subsonic_Xml_Data::_isAlbum($object_id)) {
            Subsonic_Xml_Data::addDirectory($response, $object_id, 'album');
        } elseif (Catalog::create_from_id($object_id)) {
            Subsonic_Xml_Data::addDirectory($response, $object_id, 'catalog');
        } else {
            debug_event(self::class, 'getmusicdirectory: Directory not found ' . $object_id, 4);
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'getmusicdirectory');
        }
        self::_apiOutput($input, $response);
    }

    /**
     * getGenres
     * Returns all genres.
     * Returns a <subsonic-response> element with a nested <genres> element on success.
     * http://www.subsonic.org/pages/api.jsp#getGenres
     * @param array $input
     * @param User $user
     */
    public static function getgenres($input, $user): void
    {
        unset($user);
        $response = Subsonic_Xml_Data::addSubsonicResponse('getgenres');
        Subsonic_Xml_Data::addGenres($response, Tag::get_tags('song'));
        self::_apiOutput($input, $response);
    }

    /**
     * getArtists
     * See self::getIndexes()
     * Returns a <subsonic-response> element with a nested <artists> element on success.
     * http://www.subsonic.org/pages/api.jsp#getArtists
     * @param array $input
     * @param User $user
     */
    public static function getartists($input, $user): void
    {
        unset($user);
        $musicFolderId = $input['musicFolderId'] ?? '';
        $catalogs      = array();
        if (!empty($musicFolderId) && $musicFolderId != '-1') {
            $catalogs[] = $musicFolderId;
        }
        $response = Subsonic_Xml_Data::addSubsonicResponse('getartists');
        $artists  = Artist::get_id_arrays($catalogs);
        Subsonic_Xml_Data::addArtists($response, $artists);
        self::_apiOutput($input, $response);
    }

    /**
     * getArtist
     * Returns details for an artist, including a list of albums. This method organizes music according to ID3 tags.
     * Returns a <subsonic-response> element with a nested <artist> element on success.
     * http://www.subsonic.org/pages/api.jsp#getArtist
     * @param array $input
     * @param User $user
     */
    public static function getartist($input, $user): void
    {
        unset($user);
        $artistid = self::_check_parameter($input, 'id');
        if (!$artistid) {
            return;
        }
        $artist = new Artist(Subsonic_Xml_Data::_getAmpacheId($artistid));
        if ($artist->isNew()) {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'getartist');
        } else {
            $response = Subsonic_Xml_Data::addSubsonicResponse('getartist');
            Subsonic_Xml_Data::addArtist($response, $artist, true, true);
        }
        self::_apiOutput($input, $response, array('album'));
    }

    /**
     * getAlbum
     * Returns details for an album, including a list of songs. This method organizes music according to ID3 tags.
     * Returns a <subsonic-response> element with a nested <album> element on success.
     * http://www.subsonic.org/pages/api.jsp#getAlbum
     * @param array $input
     * @param User $user
     */
    public static function getalbum($input, $user): void
    {
        unset($user);
        $albumid = self::_check_parameter($input, 'id');
        if (!$albumid) {
            return;
        }
        $album = new Album(Subsonic_Xml_Data::_getAmpacheId($albumid));
        if ($album->isNew()) {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'getalbum');
        } else {
            $response = Subsonic_Xml_Data::addSubsonicResponse('getalbum');
            Subsonic_Xml_Data::addAlbum($response, $album, true);
        }

        self::_apiOutput($input, $response, array('song'));
    }

    /**
     * getSong
     * Returns details for a song.
     * Returns a <subsonic-response> element with a nested <song> element on success.
     * http://www.subsonic.org/pages/api.jsp#getSong
     * id = (string) The album ID.
     * @param array $input
     * @param User $user
     */
    public static function getsong($input, $user): void
    {
        unset($user);
        $songid = self::_check_parameter($input, 'id');
        if (!$songid) {
            return;
        }
        $response = Subsonic_Xml_Data::addSubsonicResponse('getsong');
        $song     = Subsonic_Xml_Data::_getAmpacheId($songid);
        Subsonic_Xml_Data::addSong($response, $song);
        self::_apiOutput($input, $response, array());
    }

    /**
     * getVideos
     * Returns all video files.
     * Returns a <subsonic-response> element with a nested <videos> element on success.
     * http://www.subsonic.org/pages/api.jsp#getVideos
     * @param array $input
     * @param User $user
     */
    public static function getvideos($input, $user): void
    {
        unset($user);
        $response = Subsonic_Xml_Data::addSubsonicResponse('getvideos');
        $videos   = Catalog::get_videos();
        Subsonic_Xml_Data::addVideos($response, $videos);
        self::_apiOutput($input, $response);
    }

    /**
     * Returns details for a video, including information about available audio tracks, subtitles (captions) and conversions.
     * @param array $input
     * @param User $user
     */
    public static function getvideoinfo($input, $user): void
    {
        unset($user);
        $video_id = self::_check_parameter($input, 'id');
        if (!$video_id) {
            return;
        }
        $response = Subsonic_Xml_Data::addSubsonicResponse('getvideoinfo');
        Subsonic_Xml_Data::addVideoInfo($response, (int)$video_id);
        self::_apiOutput($input, $response, array());
    }

    /**
     * getArtistInfo
     * Returns artist info with biography, image URLs and similar artists, using data from last.fm.
     * Returns a <subsonic-response> element with a nested <artistInfo> element on success.
     * http://www.subsonic.org/pages/api.jsp#getArtistInfo
     * @param array $input
     * @param User $user
     * @param string $elementName
     */
    public static function getartistinfo($input, $user, $elementName = "artistInfo"): void
    {
        unset($user);
        $object_id = self::_check_parameter($input, 'id');
        if (!$object_id) {
            return;
        }
        $count             = $input['count'] ?? 20;
        $includeNotPresent = (array_key_exists('includeNotPresent', $input) && $input['includeNotPresent'] === "true");

        if (Subsonic_Xml_Data::_isArtist($object_id)) {
            $artist_id = Subsonic_Xml_Data::_getAmpacheId($object_id);
            $info      = Recommendation::get_artist_info($artist_id);
            $similars  = Recommendation::get_artists_like($artist_id, $count, !$includeNotPresent);
            $response  = Subsonic_Xml_Data::addSubsonicResponse($elementName);
            switch ($elementName) {
                case 'artistInfo':
                    Subsonic_Xml_Data::addArtistInfo($response, $info, $similars);
                    break;
                case 'artistInfo2':
                    Subsonic_Xml_Data::addArtistInfo2($response, $info, $similars);
                    break;
            }
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'getartistinfo');
        }

        self::_apiOutput($input, $response);
    }

    /**
     * getArtistInfo2
     * See self::getArtistInfo()
     * Returns a <subsonic-response> element with a nested <artistInfo2> element on success.
     * http://www.subsonic.org/pages/api.jsp#getArtistInfo2
     * @param array $input
     * @param User $user
     */
    public static function getartistinfo2($input, $user): void
    {
        self::getartistinfo($input, $user, 'artistInfo2');
    }

    /**
     * getAlbumInfo
     * @param array $input
     * @param User $user
     */
    public static function getalbuminfo($input, $user): void
    {
        unset($user);
        $object_id = self::_check_parameter($input, 'id');
        if (!$object_id) {
            return;
        }

        if (Subsonic_Xml_Data::_isAlbum($object_id)) {
            $album_id = Subsonic_Xml_Data::_getAmpacheId($object_id);
            $info     = Recommendation::get_album_info($album_id);
            $response = Subsonic_Xml_Data::addSubsonicResponse('albumInfo');
            Subsonic_Xml_Data::addAlbumInfo($response, $info);
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'getalbuminfo');
        }

        self::_apiOutput($input, $response);
    }

    /**
     * getAlbumInfo2
     * @param array $input
     * @param User $user
     */
    public static function getalbuminfo2($input, $user): void
    {
        self::getalbuminfo($input, $user);
    }

    /**
     * getSimilarSongs
     * Returns a random collection of songs from the given artist and similar artists, using data from last.fm. Typically used for artist radio features.
     * Returns a <subsonic-response> element with a nested <similarSongs> element on success.
     * http://www.subsonic.org/pages/api.jsp#getSimilarSongs
     * @param array $input
     * @param User $user
     * @param string $elementName
     */
    public static function getsimilarsongs($input, $user, $elementName = "similarSongs"): void
    {
        unset($user);
        if (!AmpConfig::get('show_similar')) {
            debug_event(self::class, $elementName . ': Enable: show_similar', 4);
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'getsimilarsongs');
            self::_apiOutput($input, $response);

            return;
        }

        $object_id = self::_check_parameter($input, 'id');
        if (!$object_id) {
            return;
        }
        $count = $input['count'] ?? 50;
        $songs = array();
        if (Subsonic_Xml_Data::_isArtist($object_id)) {
            $similars = Recommendation::get_artists_like(Subsonic_Xml_Data::_getAmpacheId($object_id));
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
                        $artist_songs = static::getSongRepository()->getRandomByArtist($artist);
                        foreach ($artist_songs as $song) {
                            $songs[] = array('id' => $song);
                        }
                    }
                }
            }
            // randomize and slice
            shuffle($songs);
            $songs = array_slice($songs, 0, $count);
        } elseif (Subsonic_Xml_Data::_isAlbum($object_id)) {
            // TODO: support similar songs for albums
            debug_event(self::class, $elementName . ': album is unsupported', 4);
        } elseif (Subsonic_Xml_Data::_isSong($object_id)) {
            $songs = Recommendation::get_songs_like(Subsonic_Xml_Data::_getAmpacheId($object_id), $count);
        }

        if (count($songs) == 0) {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, $elementName);
        } else {
            $response = Subsonic_Xml_Data::addSubsonicResponse($elementName);
            switch ($elementName) {
                case 'similarSongs':
                    Subsonic_Xml_Data::addSimilarSongs($response, $songs, $elementName);
                    break;
                case 'similarSongs2':
                    Subsonic_Xml_Data::addSimilarSongs2($response, $songs, $elementName);
                    break;
            }
        }

        self::_apiOutput($input, $response);
    }

    /**
     * getSimilarSongs2
     * See self::getSimilarSongs()
     * Returns a <subsonic-response> element with a nested <similarSongs2> element on success.
     * http://www.subsonic.org/pages/api.jsp#getSimilarSongs2
     * @param array $input
     * @param User $user
     */
    public static function getsimilarsongs2($input, $user): void
    {
        self::getsimilarsongs($input, $user, "similarSongs2");
    }

    /**
     * getTopSongs
     * Returns top songs for the given artist, using data from last.fm.
     * Returns a <subsonic-response> element with a nested <topSongs> element on success.
     * http://www.subsonic.org/pages/api.jsp#getTopSongs
     * @param array $input
     * @param User $user
     */
    public static function gettopsongs($input, $user): void
    {
        unset($user);
        $name   = self::_check_parameter($input, 'artist');
        $artist = Artist::get_from_name(urldecode((string)$name));
        $count  = (int)($input['count'] ?? 50);
        $songs  = array();
        if ($count < 1) {
            $count = 50;
        }
        if ($artist) {
            $songs = static::getSongRepository()->getTopSongsByArtist(
                $artist,
                $count
            );
        }
        $response = Subsonic_Xml_Data::addSubsonicResponse('gettopsongs');
        Subsonic_Xml_Data::addTopSongs($response, $songs);
        self::_apiOutput($input, $response);
    }

    /**
     * getAlbumList
     * Returns a list of random, newest, highest rated etc. albums. Similar to the album lists on the home page of the Subsonic web interface.
     * Returns a <subsonic-response> element with a nested <albumList> element on success.
     * http://www.subsonic.org/pages/api.jsp#getAlbumList
     * @param array $input
     * @param User $user
     * @param string $elementName
     */
    public static function getalbumlist($input, $user, $elementName = "albumList"): void
    {
        $type     = self::_check_parameter($input, 'type');
        $response = Subsonic_Xml_Data::addSubsonicResponse($elementName);
        if ($type) {
            $albums = self::_albumList($input, $user, (string)$type);
            if ($albums === false) {
                $response     = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_GENERIC, $elementName);
            } else {
                switch ($elementName) {
                    case 'albumList':
                        Subsonic_Xml_Data::addAlbumList($response, $albums);
                        break;
                    case 'albumList2':
                        Subsonic_Xml_Data::addAlbumList2($response, $albums);
                        break;
                }
            }
        }
        self::_apiOutput($input, $response);
    }

    /**
     * getAlbumList2
     * See self::getAlbumList()
     * Returns a <subsonic-response> element with a nested <albumList2> element on success.
     * http://www.subsonic.org/pages/api.jsp#getAlbumList2
     * @param array $input
     * @param User $user
     */
    public static function getalbumlist2($input, $user): void
    {
        self::getAlbumList($input, $user, "albumList2");
    }

    /**
     * getRandomSongs
     * Returns random songs matching the given criteria.
     * Returns a <subsonic-response> element with a nested <randomSongs> element on success.
     * http://www.subsonic.org/pages/api.jsp#getRandomSongs
     * @param array $input
     * @param User $user
     */
    public static function getrandomsongs($input, $user): void
    {
        $size = (int)($input['size'] ?? 10);

        $genre         = $input['genre'] ?? '';
        $fromYear      = $input['fromYear'] ?? null;
        $toYear        = $input['toYear'] ?? null;
        $musicFolderId = $input['musicFolderId'] ?? 0;

        $data           = array();
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
            if (Subsonic_Xml_Data::_isArtist($musicFolderId)) {
                $artist   = new Artist(Subsonic_Xml_Data::_getAmpacheId($musicFolderId));
                $finput   = $artist->get_fullname();
                $operator = 4;
                $ftype    = "artist";
            } else {
                if (Subsonic_Xml_Data::_isAlbum($musicFolderId)) {
                    $album    = new Album(Subsonic_Xml_Data::_getAmpacheId($musicFolderId));
                    $finput   = $album->get_fullname(true);
                    $operator = 4;
                    $ftype    = "artist";
                } else {
                    $finput   = (int)($musicFolderId);
                    $operator = 0;
                    $ftype    = "catalog";
                }
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

        $response = Subsonic_Xml_Data::addSubsonicResponse('getrandomsongs');
        Subsonic_Xml_Data::addRandomSongs($response, $songs);
        self::_apiOutput($input, $response);
    }

    /**
     * getSongsByGenre
     * Returns songs in a given genre.
     * Returns a <subsonic-response> element with a nested <songsByGenre> element on success.
     * http://www.subsonic.org/pages/api.jsp#getSongsByGenre
     * @param array $input
     * @param User $user
     */
    public static function getsongsbygenre($input, $user): void
    {
        unset($user);
        $genre  = self::_check_parameter($input, 'genre');
        $count  = (int)($input['count'] ?? 0);
        $offset = (int)($input['offset'] ?? 0);

        $tag = Tag::construct_from_name($genre);
        if ($tag->isNew()) {
            $songs = array();
        } else {
            $songs = Tag::get_tag_objects("song", $tag->id, $count, $offset);
        }
        $response = Subsonic_Xml_Data::addSubsonicResponse('getsongsbygenre');
        Subsonic_Xml_Data::addSongsByGenre($response, $songs);
        self::_apiOutput($input, $response);
    }

    /**
     * getNowPlaying
     * Get what is currently being played by all users.
     * Returns a <subsonic-response> element with a nested <nowPlaying> element on success.
     * http://www.subsonic.org/pages/api.jsp#getNowPlaying
     * @param array $input
     * @param User $user
     */
    public static function getnowplaying($input, $user): void
    {
        unset($user);
        $data     = Stream::get_now_playing();
        $response = Subsonic_Xml_Data::addSubsonicResponse('getnowplaying');
        Subsonic_Xml_Data::addNowPlaying($response, $data);
        self::_apiOutput($input, $response);
    }

    /**
     * getStarred
     * Get starred songs, albums and artists.
     * Returns a <subsonic-response> element with a nested <starred> element on success.
     * http://www.subsonic.org/pages/api.jsp#getStarred
     * @param array $input
     * @param User $user
     * @param string $elementName
     */
    public static function getstarred($input, $user, $elementName = "starred"): void
    {
        $response = Subsonic_Xml_Data::addSubsonicResponse($elementName);
        switch ($elementName) {
            case 'starred':
                Subsonic_Xml_Data::addStarred(
                    $response,
                    Userflag::get_latest('artist', $user->id, 10000),
                    Userflag::get_latest('album', $user->id, 10000),
                    Userflag::get_latest('song', $user->id, 10000)
                );
                break;
            case 'starred2':
                Subsonic_Xml_Data::addStarred2(
                    $response,
                    Userflag::get_latest('artist', $user->id, 10000),
                    Userflag::get_latest('album', $user->id, 10000),
                    Userflag::get_latest('song', $user->id, 10000)
                );
                break;
        }
        self::_apiOutput($input, $response);
    }

    /**
     * getStarred2
     * See self::getStarred()
     * Returns a <subsonic-response> element with a nested <starred2> element on success.
     * http://www.subsonic.org/pages/api.jsp#getStarred2
     * @param array $input
     * @param User $user
     */
    public static function getstarred2($input, $user): void
    {
        self::getStarred($input, $user, "starred2");
    }

    /**
     * search2
     * Returns albums, artists and songs matching the given search criteria. Supports paging through the result.
     * Returns a <subsonic-response> element with a nested <searchResult2> element on success.
     * http://www.subsonic.org/pages/api.jsp#search2
     * @param array $input
     * @param User $user
     * @param string $elementName
     */
    public static function search2($input, $user, $elementName = "searchResult2"): void
    {
        $operator = 0; // contains
        $original = unhtmlentities((string)self::_check_parameter($input, 'query'));
        $query    = $original;
        if (substr($original, 0, 1) == '"' && (substr($original, -1) == '"')) {
            $query = substr($original, 1, -1);
            // query is non-optional, but some clients send empty queries to fetch
            // all items. Fall back on default contains in such cases.
            if (strlen($query) > 0) {
                $operator = 4; // equals
            }
        }
        if (substr($original, 0, 1) == '"' && substr($original, -2, 2) == '"*') {
            $query    = substr($original, 1, -2);
            $operator = 4; // equals
        }
        $artists = array();
        $albums  = array();
        $songs   = array();

        if (strlen($query) > 1) {
            // if we didn't catch a "wrapped" query it might just be a starts with
            if (substr($original, -1) == "*" && $operator == 0) {
                $query    = substr($query, 0, -1);
                $operator = 2; // Starts with
            }
        }

        $artistCount  = $input['artistCount'] ?? 20;
        $artistOffset = $input['artistOffset'] ?? 0;
        $albumCount   = $input['albumCount'] ?? 20;
        $albumOffset  = $input['albumOffset'] ?? 0;
        $songCount    = $input['songCount'] ?? 20;
        $songOffset   = $input['songOffset'] ?? 0;

        $data          = array();
        $data['limit'] = $artistCount;
        if ($artistOffset) {
            $data['offset'] = $artistOffset;
        }
        $data['rule_1_input']    = $query;
        $data['rule_1_operator'] = $operator;
        $data['rule_1']          = 'title';
        $data['type']            = 'artist';
        if ($artistCount > 0) {
            $artists = Search::run($data, $user);
        }

        $data          = array();
        $data['limit'] = $albumCount;
        if ($albumOffset) {
            $data['offset'] = $albumOffset;
        }
        $data['rule_1_input']    = $query;
        $data['rule_1_operator'] = $operator;
        $data['rule_1']          = 'title';
        $data['type']            = 'album';
        if ($albumCount > 0) {
            $albums = Search::run($data, $user);
        }

        $data          = array();
        $data['limit'] = $songCount;
        if ($songOffset) {
            $data['offset'] = $songOffset;
        }
        $data['rule_1_input']    = $query;
        $data['rule_1_operator'] = $operator;
        $data['rule_1']          = 'title';
        $data['type']            = 'song';
        if ($songCount > 0) {
            $songs = Search::run($data, $user);
        }

        $response = Subsonic_Xml_Data::addSubsonicResponse($elementName);
        switch ($elementName) {
            case 'searchResult2':
                Subsonic_Xml_Data::addSearchResult2($response, $artists, $albums, $songs);
                break;
            case 'searchResult3':
                Subsonic_Xml_Data::addSearchResult3($response, $artists, $albums, $songs);
                break;
        }
        self::_apiOutput($input, $response);
    }

    /**
     * search3
     * See self::search2()
     * Returns a <subsonic-response> element with a nested <searchResult3> element on success.
     * http://www.subsonic.org/pages/api.jsp#search3
     * @param array $input
     * @param User $user
     */
    public static function search3($input, $user): void
    {
        self::search2($input, $user, "searchResult3");
    }

    /**
     * getPlaylists
     * Returns all playlists a user is allowed to play.
     * Returns a <subsonic-response> element with a nested <playlists> element on success.
     * http://www.subsonic.org/pages/api.jsp#getPlaylists
     * @param array $input
     * @param User $user
     */
    public static function getplaylists($input, $user): void
    {
        $user = (isset($input['username']))
            ? User::get_from_username($input['username'])
            : $user;
        $user_id   = $user->id ?? 0;
        $response  = Subsonic_Xml_Data::addSubsonicResponse('getplaylists');
        $playlists = Playlist::get_playlists($user_id, '', true, true, false);
        $searches  = Playlist::get_smartlists($user_id, '', true, false);
        // allow skipping dupe search names when used as refresh searches
        $hide_dupe_searches = (bool)Preference::get_by_user($user_id, 'api_hide_dupe_searches');

        Subsonic_Xml_Data::addPlaylists($response, $user_id, $playlists, $searches, $hide_dupe_searches);
        self::_apiOutput($input, $response);
    }

    /**
     * getPlaylist
     * Returns a listing of files in a saved playlist.
     * Returns a <subsonic-response> element with a nested <playlist> element on success.
     * http://www.subsonic.org/pages/api.jsp#getPlaylist
     * @param array $input
     * @param User $user
     */
    public static function getplaylist($input, $user): void
    {
        $playlistId = self::_check_parameter($input, 'id');
        if (!$playlistId) {
            return;
        }
        $response = Subsonic_Xml_Data::addSubsonicResponse('getplaylist');
        if (Subsonic_Xml_Data::_isSmartPlaylist($playlistId)) {
            $playlist = new Search(Subsonic_Xml_Data::_getAmpacheId($playlistId), 'song', $user);
            Subsonic_Xml_Data::addPlaylist($response, $playlist, true);
        } else {
            $playlist = new Playlist(Subsonic_Xml_Data::_getAmpacheId($playlistId));
            Subsonic_Xml_Data::addPlaylist($response, $playlist, true);
        }
        self::_apiOutput($input, $response);
    }

    /**
     * createPlaylist
     * Creates (or updates) a playlist.
     * Since 1.14.0 the newly created/updated playlist is returned.
     * In earlier versions an empty <subsonic-response> element is returned.
     * http://www.subsonic.org/pages/api.jsp#createPlaylist
     * @param array $input
     * @param User $user
     */
    public static function createplaylist($input, $user): void
    {
        $playlistId = $input['playlistId'] ?? null;
        $name       = $input['name'] ?? '';
        $songIdList = $input['songId'] ?? array();
        if (isset($input['songId']) && is_string($input['songId'])) {
            $songIdList = explode(',', $input['songId']);
        }

        if ($playlistId !== null) {
            self::_updatePlaylist((string)$playlistId, $name, $songIdList, array(), true, true);
            $response = Subsonic_Xml_Data::addSubsonicResponse('createplaylist');
        } elseif (!empty($name)) {
            $playlistId = Playlist::create($name, 'public', $user->id);
            if ($playlistId !== null) {
                if (count($songIdList) > 0) {
                    self::_updatePlaylist($playlistId, "", $songIdList, array(), true, true);
                }
                $response = Subsonic_Xml_Data::addSubsonicResponse('createplaylist');
                $playlist = new Playlist($playlistId);
                Subsonic_Xml_Data::addPlaylist($response, $playlist, true);
            } else {
                $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_GENERIC, 'createplaylist');
            }
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_MISSINGPARAM, 'createplaylist');
        }
        self::_apiOutput($input, $response);
    }

    /**
     * updatePlaylist
     * Updates a playlist. Only the owner of a playlist is allowed to update it.
     * http://www.subsonic.org/pages/api.jsp#updatePlaylist
     * @param array $input
     * @param User $user
     */
    public static function updateplaylist($input, $user): void
    {
        unset($user);
        $playlistId        = self::_check_parameter($input, 'playlistId');
        $name              = $input['name'] ?? '';
        $public            = (array_key_exists('public', $input) && $input['public'] === "true");
        $songIdToAdd       = $input['songIdToAdd'] ?? array();
        $songIndexToRemove = $input['songIndexToRemove'] ?? array();

        if ($playlistId === false) {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'playlistId');
            self::_apiOutput($input, $response);

            return;
        }
        if (Subsonic_Xml_Data::_isPlaylist((string)$playlistId)) {
            if (is_string($songIdToAdd)) {
                $songIdToAdd = explode(',', $songIdToAdd);
            }
            if (is_string($songIndexToRemove)) {
                $songIndexToRemove = explode(',', $songIndexToRemove);
            }
            self::_updatePlaylist(Subsonic_Xml_Data::_getAmpacheId((string)$playlistId), $name, $songIdToAdd, $songIndexToRemove, $public);

            $response = Subsonic_Xml_Data::addSubsonicResponse('updateplaylist');
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, 'updateplaylist');
        }
        self::_apiOutput($input, $response);
    }

    /**
     * deletePlaylist
     * Deletes a saved playlist.
     * http://www.subsonic.org/pages/api.jsp#deletePlaylist
     * @param array $input
     * @param User $user
     */
    public static function deleteplaylist($input, $user): void
    {
        $playlistId = self::_check_parameter($input, 'id');
        if (!$playlistId) {
            return;
        }
        if (Subsonic_Xml_Data::_isSmartPlaylist($playlistId)) {
            $playlist = new Search(Subsonic_Xml_Data::_getAmpacheId($playlistId), 'song', $user);
        } else {
            $playlist = new Playlist(Subsonic_Xml_Data::_getAmpacheId($playlistId));
        }
        $playlist->delete();

        $response = Subsonic_Xml_Data::addSubsonicResponse('deleteplaylist');
        self::_apiOutput($input, $response);
    }

    /**
     * stream
     * Streams a given media file.
     * Returns binary data on success, or an XML document on error (in which case the HTTP content type will start with "text/xml").
     * http://www.subsonic.org/pages/api.jsp#stream
     * @param array $input
     * @param User $user
     */
    public static function stream($input, $user): void
    {
        $fileid = self::_check_parameter($input, 'id', true);

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
        if ((int)$maxBitRate > 0) {
            $params .= '&bitrate=' . $maxBitRate;
        }
        if ($timeOffset) {
            $params .= '&frame=' . $timeOffset;
        }

        $url = '';
        if (Subsonic_Xml_Data::_isSong($fileid)) {
            if (AmpConfig::get('subsonic_always_download')) {
                $params .= '&cache=1';
            }
            $object = new Song(Subsonic_Xml_Data::_getAmpacheId($fileid));
            $url    = $object->play_url($params, 'api', function_exists('curl_version'), $user->id, $user->streamtoken);
        } elseif (Subsonic_Xml_Data::_isPodcastEpisode($fileid)) {
            $object = new Podcast_episode((int) Subsonic_Xml_Data::_getAmpacheId($fileid));
            $url    = $object->play_url($params, 'api', function_exists('curl_version'), $user->id, $user->streamtoken);
        }

        // return an error on missing files
        if (empty($url)) {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'download');
            self::_apiOutput($input, $response);

            return;
        }
        self::_follow_stream($url);
    }

    /**
     * download
     * Downloads a given media file. Similar to stream, but this method returns the original media data without transcoding or downsampling.
     * Returns binary data on success, or an XML document on error (in which case the HTTP content type will start with "text/xml").
     * http://www.subsonic.org/pages/api.jsp#download
     * @param array $input
     * @param User $user
     */
    public static function download($input, $user): void
    {
        $fileid = self::_check_parameter($input, 'id', true);
        $client = scrub_in((string) ($input['c'] ?? 'Subsonic'));
        $params = '&client=' . rawurlencode($client) . '&cache=1';
        $url    = '';
        if (Subsonic_Xml_Data::_isSong($fileid)) {
            $object = new Song(Subsonic_Xml_Data::_getAmpacheId($fileid));
            $url    = $object->play_url($params, 'api', function_exists('curl_version'), $user->id, $user->streamtoken);
        } elseif (Subsonic_Xml_Data::_isPodcastEpisode($fileid)) {
            $object = new Podcast_episode((int) Subsonic_Xml_Data::_getAmpacheId($fileid));
            $url    = $object->play_url($params, 'api', function_exists('curl_version'), $user->id, $user->streamtoken);
        }
        // return an error on missing files
        if (empty($url)) {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'download');
            self::_apiOutput($input, $response);

            return;
        }
        self::_follow_stream($url);
    }

    /**
     * hls
     * Creates an HLS (HTTP Live Streaming) playlist used for streaming video or audio.
     * Returns an M3U8 playlist on success (content type "application/vnd.apple.mpegurl"), or an XML document on error (in which case the HTTP content type will start with "text/xml").
     * http://www.subsonic.org/pages/api.jsp#hls
     * @param array $input
     * @param User $user
     */
    public static function hls($input, $user): void
    {
        unset($user);
        $fileid  = self::_check_parameter($input, 'id', true);
        $bitRate = $input['bitRate'] ?? false;
        $media   = array();
        if (Subsonic_Xml_Data::_isSong($fileid)) {
            $media['object_type'] = 'song';
        } elseif (Subsonic_Xml_Data::_isVideo($fileid)) {
            $media['object_type'] = 'video';
        } else {
            self::_apiOutput(
                $input,
                Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'hls')
            );

            return;
        }
        $media['object_id'] = Subsonic_Xml_Data::_getAmpacheId($fileid);
        $medias             = array();
        $medias[]           = $media;
        $stream             = new Stream_Playlist();
        $additional_params  = '';
        if ($bitRate) {
            $additional_params .= '&bitrate=' . $bitRate;
        }
        //$additional_params .= '&transcode_to=ts';
        $stream->add($medias, $additional_params);

        // vlc won't work if we use application/vnd.apple.mpegurl, but works fine with this. this is
        // also an allowed header by the standard
        header('Content-Type: audio/mpegurl;');
        $stream->create_m3u();
    }

    /**
     * getCaptions
     * @param array $input
     * @param User $user
     */
    public static function getcaptions($input, $user): void
    {
        // Ampache doesn't support srt/subtitles and probably won't ever support them but the function is required
    }

    /**
     * getCoverArt
     * Returns a cover art image.
     * Returns the cover art image in binary form.
     * http://www.subsonic.org/pages/api.jsp#getCoverArt
     * @param array $input
     * @param User $user
     */
    public static function getcoverart($input, $user): void
    {
        $sub_id = self::_check_parameter($input, 'id');
        if (!$sub_id) {
            self::_setHeader((string)($input['f'] ?? 'xml'));
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'getcoverart');
            self::_apiOutput($input, $response);

            return;
        }
        $sub_id = str_replace('al-', '', (string)$sub_id);
        $sub_id = str_replace('ar-', '', $sub_id);
        $sub_id = str_replace('pl-', '', $sub_id);
        $sub_id = str_replace('pod-', '', $sub_id);
        // sometimes we're sent a full art url...
        preg_match('/\/artist\/([0-9]*)\//', $sub_id, $matches);
        if (!empty($matches)) {
            $sub_id = (string)(100000000 + (int)$matches[1]);
        }
        if (!is_string($sub_id)) {
            return;
        }
        $size = $input['size'] ?? false;
        $type = Subsonic_Xml_Data::_getAmpacheType($sub_id);
        if ($type == "") {
            self::_setHeader((string)($input['f'] ?? 'xml'));
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'getcoverart');
            self::_apiOutput($input, $response);

            return;
        }

        $art = null;

        if ($type == 'artist') {
            $art = new Art(Subsonic_Xml_Data::_getAmpacheId($sub_id), "artist");
        }
        if ($type == 'album') {
            $art = new Art(Subsonic_Xml_Data::_getAmpacheId($sub_id), "album");
        }
        if (($type == 'song')) {
            $song_id = Subsonic_Xml_Data::_getAmpacheId($sub_id);
            $art     = new Art(Subsonic_Xml_Data::_getAmpacheId($sub_id), "song");
            if (!AmpConfig::get('show_song_art', false) || !Art::has_db($song_id, 'song')) {
                // in most cases the song doesn't have a picture, but the album does
                $song = new Song($song_id);
                $art  = new Art($song->album, 'album');
            }
        }
        if (($type == 'podcast')) {
            $art = new Art(Subsonic_Xml_Data::_getAmpacheId($sub_id), "podcast");
        }
        if (($type == 'playlist')) {
            $art = new Art(Subsonic_Xml_Data::_getAmpacheId($sub_id), "playlist");
        }
        if ($type == 'search') {
            $playlist  = new Search(Subsonic_Xml_Data::_getAmpacheId($sub_id), 'song', $user);
            $listitems = $playlist->get_items();
            $item      = (!empty($listitems)) ? $listitems[array_rand($listitems)] : array();
            $art       = (!empty($item)) ? new Art($item['object_id'], $item['object_type']) : null;
            if ($art != null && $art->id == null) {
                $song = new Song($item['object_id']);
                $art  = new Art($song->album, "album");
            }
        }
        if (!$art || $art->get(false, true) == '') {
            self::_setHeader((string)($input['f'] ?? 'xml'));
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'getcoverart');
            self::_apiOutput($input, $response);

            return;
        }
        // we have the art so lets show it
        header("Access-Control-Allow-Origin: *");
        if ($size && AmpConfig::get('resize_images')) {
            $dim           = array();
            $dim['width']  = $size;
            $dim['height'] = $size;
            $thumb         = $art->get_thumb($dim);
            if (!empty($thumb)) {
                header('Content-type: ' . $thumb['thumb_mime']);
                header('Content-Length: ' . strlen((string) $thumb['thumb']));
                echo $thumb['thumb'];

                return;
            }
        }
        $image = $art->get(true, true);
        header('Content-type: ' . $art->raw_mime);
        header('Content-Length: ' . strlen((string) $image));
        echo $image;
    }

    /**
     * getLyrics
     * Searches for and returns lyrics for a given song.
     * Returns a <subsonic-response> element with a nested <lyrics> element on success.
     * The <lyrics> element is empty if no matching lyrics was found.
     * http://www.subsonic.org/pages/api.jsp#getLyrics
     * @param array $input
     * @param User $user
     */
    public static function getlyrics($input, $user): void
    {
        $artist = (string)($input['artist'] ?? '');
        $title  = (string)($input['title'] ?? '');

        if (empty($artist) || empty($title)) {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_MISSINGPARAM, 'getlyrics');
        } else {
            $data           = array();
            $data['limit']  = 1;
            $data['offset'] = 0;
            $data['type']   = "song";

            $data['rule_0_input']    = $artist;
            $data['rule_0_operator'] = 4;
            $data['rule_0']          = "artist";
            $data['rule_1_input']    = $title;
            $data['rule_1_operator'] = 4;
            $data['rule_1']          = "title";

            $songs    = Search::run($data, $user);
            $response = Subsonic_Xml_Data::addSubsonicResponse('getlyrics');
            if (count($songs) > 0) {
                Subsonic_Xml_Data::addLyrics($response, $artist, $title, $songs[0]);
            }
        }

        self::_apiOutput($input, $response);
    }

    /**
     * getAvatar
     * Returns the avatar (personal image) for a user.
     * Returns the avatar image in binary form.
     * http://www.subsonic.org/pages/api.jsp#getAvatar
     * @param array $input
     * @param User $user
     */
    public static function getavatar($input, $user): void
    {
        $username = self::_check_parameter($input, 'username');
        $response = null;
        if ($user->access === 100 || $user->username == $username) {
            if ($user->username == $username) {
                $update_user = $user;
            } else {
                $update_user = User::get_from_username((string)$username);
            }

            if ($update_user instanceof User) {
                // Get Session key
                $avatar = $update_user->get_avatar(true);
                if (isset($avatar['url']) && !empty($avatar['url'])) {
                    $request = Requests::get($avatar['url'], array(), Core::requests_options());
                    header("Content-Type: " . $request->headers['Content-Type']);
                    echo $request->body;
                }
            } else {
                $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'getavatar');
            }
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, 'getavatar');
        }

        if ($response != null) {
            self::_apiOutput($input, $response);
        }
    }

    /**
     * star
     * Attaches a star to a song, album or artist.
     * http://www.subsonic.org/pages/api.jsp#star
     * @param array $input
     * @param User $user
     */
    public static function star($input, $user): void
    {
        self::_setStar($input, $user, true);
    }

    /**
     * unstar
     * Removes the star from a song, album or artist.
     * http://www.subsonic.org/pages/api.jsp#unstar
     * @param array $input
     * @param User $user
     */
    public static function unstar($input, $user): void
    {
        self::_setStar($input, $user, false);
    }

    /**
     * setRating
     * Sets the rating for a music file.
     * http://www.subsonic.org/pages/api.jsp#setRating
     * @param array $input
     * @param User $user
     */
    public static function setrating($input, $user): void
    {
        $object_id = self::_check_parameter($input, 'id');
        if (!$object_id) {
            return;
        }
        $rating = (int)($input['rating'] ?? -1);
        $robj   = null;
        if (Subsonic_Xml_Data::_isArtist($object_id)) {
            $robj = new Rating(Subsonic_Xml_Data::_getAmpacheId($object_id), "artist");
        } elseif (Subsonic_Xml_Data::_isAlbum($object_id)) {
            $robj = new Rating(Subsonic_Xml_Data::_getAmpacheId($object_id), "album");
        } elseif (Subsonic_Xml_Data::_isSong($object_id)) {
            $robj = new Rating(Subsonic_Xml_Data::_getAmpacheId($object_id), "song");
        }

        if ($robj != null && ($rating >= 0 && $rating <= 5)) {
            $robj->set_rating($rating, $user->id);

            $response = Subsonic_Xml_Data::addSubsonicResponse('setrating');
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'setrating');
        }

        self::_apiOutput($input, $response);
    }

    /**
     * scrobble
     * Registers the local playback of one or more media files. Typically used when playing media that is cached on the client.
     * http://www.subsonic.org/pages/api.jsp#scrobble
     * @param array $input
     * @param User $user
     */
    public static function scrobble($input, $user): void
    {
        $object_ids = self::_check_parameter($input, 'id');
        if (!$object_ids) {
            return;
        }
        $submission = (array_key_exists('submission', $input) && ($input['submission'] === 'true' || $input['submission'] === '1'));
        $client     = scrub_in((string) ($input['c'] ?? 'Subsonic'));

        if (!is_array($object_ids)) {
            $rid        = array();
            $rid[]      = $object_ids;
            $object_ids = $rid;
        }
        $playqueue_time = (int)User::get_user_data($user->id, 'playqueue_time', 0)['playqueue_time'];
        $now_time       = time();
        // don't scrobble after setting the play queue too quickly
        if ($playqueue_time < ($now_time - 2)) {
            foreach ($object_ids as $subsonic_id) {
                $time      = isset($input['time']) ? (int)(((int)$input['time']) / 1000) : time();
                $previous  = Stats::get_last_play($user->id, $client, $time);
                $prev_obj  = $previous['object_id'] ?? 0;
                $prev_date = $previous['date'] ?? 0;
                $type      = Subsonic_Xml_Data::_getAmpacheType((string)$subsonic_id);
                $media     = Subsonic_Xml_Data::_getAmpacheObject((string)$subsonic_id);
                if ($media === null || $media->isNew()) {
                    continue;
                }
                $media->format();

                // long pauses might cause your now_playing to hide
                Stream::garbage_collection();
                Stream::insert_now_playing((int)$media->id, (int)$user->id, ((int)$media->time), (string)$user->username, $type, ((int)$time));
                // submission is true: go to scrobble plugins (Plugin::get_plugins('save_mediaplay'))
                if ($submission && get_class($media) == Song::class && ($prev_obj != $media->id) && (($time - $prev_date) > 5)) {
                    // stream has finished
                    debug_event(self::class, $user->username . ' scrobbled: {' . $media->id . '} at ' . $time, 5);
                    User::save_mediaplay($user, $media);
                }
                // Submission is false and not a repeat. let repeats go through to saveplayqueue
                if ((!$submission) && $media->id && ($prev_obj != $media->id) && (($time - $prev_date) > 5)) {
                    $media->set_played($user->id, $client, array(), $time);
                }
            }
        }

        $response = Subsonic_Xml_Data::addSubsonicResponse('scrobble');
        self::_apiOutput($input, $response);
    }

    /**
     * getShares
     * Returns information about shared media this user is allowed to manage. Takes no extra parameters.
     * Returns a <subsonic-response> element with a nested <shares> element on success.
     * http://www.subsonic.org/pages/api.jsp#getShares
     * @param array $input
     * @param User $user
     */
    public static function getshares($input, $user): void
    {
        $response = Subsonic_Xml_Data::addSubsonicResponse('getshares');
        $shares   = Share::get_share_list($user);
        Subsonic_Xml_Data::addShares($response, $shares);
        self::_apiOutput($input, $response);
    }

    /**
     * createShare
     * Creates a public URL that can be used by anyone to stream music or video from the Subsonic server.
     * Returns a <subsonic-response> element with a nested <shares> element on success, which in turns contains a single <share> element for the newly created share.
     * http://www.subsonic.org/pages/api.jsp#createShare
     * @param array $input
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function createshare($input, $user): void
    {
        $object_id = self::_check_parameter($input, 'id');
        if (!$object_id) {
            return;
        }
        $description = $input['description'] ?? '';
        if (AmpConfig::get('share')) {
            $share_expire = AmpConfig::get('share_expire', 7);
            $expire_days  = (isset($input['expires']))
                ? Share::get_expiry(((int)filter_var($input['expires'], FILTER_SANITIZE_NUMBER_INT)) / 1000)
                : $share_expire;
            $object_type = null;
            if (is_array($object_id) && Subsonic_Xml_Data::_isSong($object_id[0])) {
                debug_event(self::class, 'createShare: sharing song list (album)', 5);
                $song_id     = Subsonic_Xml_Data::_getAmpacheId($object_id[0]);
                $tmp_song    = new Song($song_id);
                $object_id   = $tmp_song->album;
                $object_type = 'album';
            } else {
                if (Subsonic_Xml_Data::_isAlbum($object_id)) {
                    $object_type = 'album';
                } elseif (Subsonic_Xml_Data::_isSong($object_id)) {
                    $object_type = 'song';
                } elseif (Subsonic_Xml_Data::_isPlaylist($object_id)) {
                    $object_type = 'playlist';
                }
                $object_id = Subsonic_Xml_Data::_getAmpacheId($object_id);
            }
            debug_event(self::class, 'createShare: sharing ' . $object_type . ' ' . $object_id, 4);

            if (!empty($object_type) && !empty($object_id)) {
                global $dic; // @todo remove after refactoring
                $passwordGenerator = $dic->get(PasswordGeneratorInterface::class);

                $response = Subsonic_Xml_Data::addSubsonicResponse('createshare');
                $shares   = array();
                $shares[] = Share::create_share(
                    $user->id,
                    $object_type,
                    $object_id,
                    true,
                    Access::check_function('download'),
                    $expire_days,
                    $passwordGenerator->generate_token(),
                    0,
                    $description
                );
                Subsonic_Xml_Data::addShares($response, $shares);
            } else {
                $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'createshare');
            }
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, 'createshare');
        }
        self::_apiOutput($input, $response);
    }

    /**
     * updateShare
     * Updates the description and/or expiration date for an existing share.
     * http://www.subsonic.org/pages/api.jsp#updateShare
     * @param array $input
     * @param User $user
     */
    public static function updateshare($input, $user): void
    {
        $share_id = self::_check_parameter($input, 'id');
        if (!$share_id) {
            return;
        }
        $description = $input['description'] ?? '';

        if (AmpConfig::get('share')) {
            $share = new Share(Subsonic_Xml_Data::_getAmpacheId($share_id));
            if ($share->id > 0) {
                $expires = (isset($input['expires']))
                    ? Share::get_expiry(((int)filter_var($input['expires'], FILTER_SANITIZE_NUMBER_INT)) / 1000)
                    : $share->expire_days;
                $data = array(
                    'max_counter' => $share->max_counter,
                    'expire' => $expires,
                    'allow_stream' => $share->allow_stream,
                    'allow_download' => $share->allow_download,
                    'description' => $description ?? $share->description,
                );
                if ($share->update($data, $user)) {
                    $response = Subsonic_Xml_Data::addSubsonicResponse('updateshare');
                } else {
                    $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, 'updateshare');
                }
            } else {
                $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'updateshare');
            }
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, 'updateshare');
        }

        self::_apiOutput($input, $response);
    }

    /**
     * deleteShare
     * Deletes an existing share.
     * http://www.subsonic.org/pages/api.jsp#deleteShare
     * @param array $input
     * @param User $user
     */
    public static function deleteshare($input, $user): void
    {
        $share_id = self::_check_parameter($input, 'id');
        if (!$share_id) {
            return;
        }
        if (AmpConfig::get('share')) {
            if (Share::delete_share((int)$share_id, $user)) {
                $response = Subsonic_Xml_Data::addSubsonicResponse('deleteshare');
            } else {
                $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'deleteshare');
            }
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, 'deleteshare');
        }
        self::_apiOutput($input, $response);
    }

    /**
     * getPodcasts
     * Returns all Podcast channels the server subscribes to, and (optionally) their episodes.
     * Returns a <subsonic-response> element with a nested <podcasts> element on success.
     * http://www.subsonic.org/pages/api.jsp#getPodcasts
     * @param array $input
     * @param User $user
     */
    public static function getpodcasts($input, $user): void
    {
        $podcast_id      = $input['id'] ?? null;
        $includeEpisodes = !isset($input['includeEpisodes']) || $input['includeEpisodes'] === "true";

        if (AmpConfig::get(ConfigurationKeyEnum::PODCAST)) {
            if ($podcast_id) {
                $podcast = self::getPodcastRepository()->findById(
                    Subsonic_Xml_Data::_getAmpacheId($podcast_id)
                );
                if ($podcast === null) {
                    $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'getpodcasts');
                } else {
                    $response = Subsonic_Xml_Data::addSubsonicResponse('getpodcasts');
                    Subsonic_Xml_Data::addPodcasts($response, array($podcast), $includeEpisodes);
                }
            } else {
                $podcasts = Catalog::get_podcasts(User::get_user_catalogs($user->id));
                $response = Subsonic_Xml_Data::addSubsonicResponse('getpodcasts');
                Subsonic_Xml_Data::addPodcasts($response, $podcasts, $includeEpisodes);
            }
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'getpodcasts');
        }
        self::_apiOutput($input, $response);
    }

    /**
     * getNewestPodcasts
     * Returns the most recently published Podcast episodes.
     * Returns a <subsonic-response> element with a nested <newestPodcasts> element on success.
     * http://www.subsonic.org/pages/api.jsp#getNewestPodcasts
     * @param array $input
     * @param User $user
     */
    public static function getnewestpodcasts($input, $user): void
    {
        unset($user);
        $count = $input['count'] ?? AmpConfig::get('podcast_new_download');
        if (AmpConfig::get('podcast')) {
            $response = Subsonic_Xml_Data::addSubsonicResponse('getnewestpodcasts');
            $episodes = Catalog::get_newest_podcasts($count);
            Subsonic_Xml_Data::addNewestPodcasts($response, $episodes);
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'getnewestpodcasts');
        }
        self::_apiOutput($input, $response);
    }

    /**
     * refreshPodcasts
     * Requests the server to check for new Podcast episodes.
     * http://www.subsonic.org/pages/api.jsp#refreshPodcasts
     * @param array $input
     * @param User $user
     */
    public static function refreshpodcasts($input, $user): void
    {
        if (AmpConfig::get('podcast') && $user->access >= 75) {
            $podcasts = Catalog::get_podcasts(User::get_user_catalogs($user->id));

            $podcastSyncer = self::getPodcastSyncer();

            foreach ($podcasts as $podcast) {
                $podcastSyncer->sync($podcast, true);
            }
            $response = Subsonic_Xml_Data::addSubsonicResponse('refreshpodcasts');
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, 'refreshpodcasts');
        }
        self::_apiOutput($input, $response);
    }

    /**
     * createPodcastChannel
     * Adds a new Podcast channel.
     * http://www.subsonic.org/pages/api.jsp#createPodcastChannel
     * @param array $input
     * @param User $user
     */
    public static function createpodcastchannel($input, $user): void
    {
        $url = self::_check_parameter($input, 'url');
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

                    $response = Subsonic_Xml_Data::addSubsonicResponse('createpodcastchannel');
                } catch (PodcastCreationException $e) {
                    $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_GENERIC, 'createpodcastchannel');
                }
            } else {
                $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, 'createpodcastchannel');
            }
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, 'createpodcastchannel');
        }
        self::_apiOutput($input, $response);
    }

    /**
     * deletePodcastChannel
     * Deletes a Podcast channel.
     * http://www.subsonic.org/pages/api.jsp#deletePodcastChannel
     * @param array $input
     * @param User $user
     */
    public static function deletepodcastchannel($input, $user): void
    {
        $podcast_id = self::_check_parameter($input, 'id');
        if (!$podcast_id) {
            return;
        }

        if (AmpConfig::get(ConfigurationKeyEnum::PODCAST) && $user->access >= AccessLevelEnum::LEVEL_MANAGER) {
            $podcast = self::getPodcastRepository()->findById(Subsonic_Xml_Data::_getAmpacheId($podcast_id));
            if ($podcast === null) {
                $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'deletepodcastchannel');
            } else {
                self::getPodcastDeleter()->delete($podcast);
                $response = Subsonic_Xml_Data::addSubsonicResponse('deletepodcastchannel');
            }
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, 'deletepodcastchannel');
        }
        self::_apiOutput($input, $response);
    }

    /**
     * deletePodcastEpisode
     * Deletes a Podcast episode.
     * http://www.subsonic.org/pages/api.jsp#deletePodcastEpisode
     * @param array $input
     * @param User $user
     */
    public static function deletepodcastepisode($input, $user): void
    {
        $episode_id = self::_check_parameter($input, 'id');
        if (!$episode_id) {
            return;
        }

        if (AmpConfig::get('podcast') && $user->access >= 75) {
            $episode = new Podcast_Episode(Subsonic_Xml_Data::_getAmpacheId($episode_id));
            if ($episode->isNew()) {
                $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'deletepodcastepisode');
            } else {
                if ($episode->remove()) {
                    $response = Subsonic_Xml_Data::addSubsonicResponse('deletepodcastepisode');
                    Catalog::count_table('podcast_episode');
                } else {
                    $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_GENERIC, 'deletepodcastepisode');
                }
            }
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, 'deletepodcastepisode');
        }
        self::_apiOutput($input, $response);
    }

    /**
     * downloadPodcastEpisode
     * Request the server to start downloading a given Podcast episode.
     * http://www.subsonic.org/pages/api.jsp#downloadPodcastEpisode
     * @param array $input
     * @param User $user
     */
    public static function downloadpodcastepisode($input, $user): void
    {
        $episode_id = self::_check_parameter($input, 'id');
        if (!$episode_id) {
            return;
        }

        if (AmpConfig::get('podcast') && $user->access >= 75) {
            $episode = new Podcast_Episode(Subsonic_Xml_Data::_getAmpacheId($episode_id));
            if ($episode->isNew()) {
                $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'downloadpodcastepisode');
            } else {
                self::getPodcastEpisodeDownloader()->fetch($episode);
                $response = Subsonic_Xml_Data::addSubsonicResponse('downloadpodcastepisode');
            }
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, 'downloadpodcastepisode');
        }
        self::_apiOutput($input, $response);
    }

    /**
     * jukeboxControl
     * Controls the jukebox, i.e., playback directly on the server's audio hardware.
     * Returns a <jukeboxStatus> element on success, unless the get action is used, in which case a nested <jukeboxPlaylist> element is returned.
     * http://www.subsonic.org/pages/api.jsp#jukeboxControl
     * @param array $input
     * @param User $user
     */
    public static function jukeboxcontrol($input, $user): void
    {
        $action    = self::_check_parameter($input, 'action');
        $object_id = $input['id'] ?? array();
        $localplay = new LocalPlay(AmpConfig::get('localplay_controller'));
        $response  = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'jukeboxcontrol');
        $return    = false;
        if (empty($localplay->type) || !$localplay->connect()) {
            debug_event(__CLASS__, 'Error Localplay controller: ' . AmpConfig::get('localplay_controller', 'Is not set'), 3);
            self::_apiOutput($input, $response);

            return;
        }

        debug_event(__CLASS__, 'Using Localplay controller: ' . AmpConfig::get('localplay_controller'), 5);
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
                    if ($localplay->skip($input['index'])) {
                        $return = $localplay->play();
                    }
                } elseif (isset($input['offset'])) {
                    debug_event(self::class, 'Skip with offset is not supported on JukeboxControl.', 5);
                } else {
                    $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_MISSINGPARAM, 'jukeboxcontrol');
                }
                break;
            case 'set':
                $localplay->delete_all();
                // Intentional break fall-through
            case 'add':
                if ($object_id) {
                    if (!is_array($object_id)) {
                        $rid       = array();
                        $rid[]     = $object_id;
                        $object_id = $rid;
                    }

                    foreach ($object_id as $song_id) {
                        $url = null;

                        if (Subsonic_Xml_Data::_isSong($song_id)) {
                            $media = new Song(Subsonic_Xml_Data::_getAmpacheId($song_id));
                            $url   = $media->play_url('&client=' . $localplay->type, 'api', function_exists('curl_version'), $user->id, $user->streamtoken);
                        }

                        if ($url !== null) {
                            debug_event(self::class, 'Adding ' . $url, 5);
                            $stream        = array();
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
                    $return = $localplay->delete_track($input['index']);
                } else {
                    $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_MISSINGPARAM, 'jukeboxcontrol');
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
            $response = Subsonic_Xml_Data::addSubsonicResponse('jukeboxcontrol');
            if ($action == 'get') {
                Subsonic_Xml_Data::addJukeboxPlaylist($response, $localplay);
            } else {
                Subsonic_Xml_Data::addJukeboxStatus($response, $localplay);
            }
        }

        self::_apiOutput($input, $response);
    }

    /**
     * getInternetRadioStations
     * Returns all internet radio stations.
     * Returns a <subsonic-response> element with a nested <internetRadioStations> element on success.
     * http://www.subsonic.org/pages/api.jsp#getInternetRadioStations
     * @param array $input
     * @param User $user
     */
    public static function getinternetradiostations($input, $user): void
    {
        unset($user);
        $response = Subsonic_Xml_Data::addSubsonicResponse('getinternetradiostations');
        $radios   = static::getLiveStreamRepository()->getAll();
        Subsonic_Xml_Data::addInternetRadioStations($response, $radios);
        self::_apiOutput($input, $response);
    }

    /**
     * createInternetRadioStation
     * Creates a public URL that can be used by anyone to stream music or video from the Subsonic server.
     * Returns a <subsonic-response> element with a nested <internetradiostations> element on success, which in turns contains a single <internetradiostation> element for the newly created internetradiostation.
     * http://www.subsonic.org/pages/api.jsp#createInternetRadioStation
     * @param array $input
     * @param User $user
     */
    public static function createinternetradiostation($input, $user): void
    {
        $url = self::_check_parameter($input, 'streamUrl');
        if (!$url) {
            return;
        }
        $name = self::_check_parameter($input, 'name');
        if (!$name) {
            return;
        }
        $site_url = filter_var(urldecode($input['homepageUrl']), FILTER_VALIDATE_URL) ?: '';
        $catalogs = User::get_user_catalogs($user->id, 'music');
        if (AmpConfig::get('live_stream') && $user->access >= 75) {
            $data = array(
                "name" => $name,
                "url" => $url,
                "codec" => 'mp3',
                "catalog" => $catalogs[0],
                "site_url" => $site_url
            );
            if (!Live_Stream::create($data)) {
                $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'createinternetradiostation');
                self::_apiOutput($input, $response);

                return;
            }
            $response = Subsonic_Xml_Data::addSubsonicResponse('createinternetradiostation');
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, 'createinternetradiostation');
        }
        self::_apiOutput($input, $response);
    }

    /**
     * updateInternetRadioStation
     * Updates the description and/or expiration date for an existing internetradiostation.
     * http://www.subsonic.org/pages/api.jsp#updateInternetRadioStation
     * @param array $input
     * @param User $user
     */
    public static function updateinternetradiostation($input, $user): void
    {
        $internetradiostation_id = self::_check_parameter($input, 'id');
        if (!$internetradiostation_id) {
            return;
        }
        $url = self::_check_parameter($input, 'streamUrl');
        if (!$url) {
            return;
        }
        $name = self::_check_parameter($input, 'name');
        if (!$name) {
            return;
        }
        $site_url = filter_var(urldecode($input['homepageUrl']), FILTER_VALIDATE_URL) ?: '';

        if (AmpConfig::get('live_stream') && $user->access >= 75) {
            $internetradiostation = new Live_Stream(Subsonic_Xml_Data::_getAmpacheId($internetradiostation_id));
            if ($internetradiostation->id > 0) {
                $data = array(
                    "name" => $name,
                    "url" => $url,
                    "codec" => 'mp3',
                    "site_url" => $site_url
                );
                if ($internetradiostation->update($data)) {
                    $response = Subsonic_Xml_Data::addSubsonicResponse('updateinternetradiostation');
                } else {
                    $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, 'updateinternetradiostation');
                }
            } else {
                $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'updateinternetradiostation');
            }
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, 'updateinternetradiostation');
        }

        self::_apiOutput($input, $response);
    }

    /**
     * deleteInternetRadioStation
     * Deletes an existing internetradiostation.
     * http://www.subsonic.org/pages/api.jsp#deleteInternetRadioStation
     * @param array $input
     * @param User $user
     */
    public static function deleteinternetradiostation($input, $user): void
    {
        $stream_id = self::_check_parameter($input, 'id');
        if (!$stream_id) {
            return;
        }
        if (AmpConfig::get('live_stream') && $user->access >= 75) {
            if (static::getLiveStreamRepository()->delete($stream_id)) {
                $response = Subsonic_Xml_Data::addSubsonicResponse('deleteinternetradiostation');
            } else {
                $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'deleteinternetradiostation');
            }
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, 'deleteinternetradiostation');
        }
        self::_apiOutput($input, $response);
    }

    /**
     * getChatMessages
     * Returns the current visible (non-expired) chat messages.
     * Returns a <subsonic-response> element with a nested <chatMessages> element on success.
     * http://www.subsonic.org/pages/api.jsp#getChatMessages
     * @param array $input
     * @param User $user
     */
    public static function getchatmessages($input, $user): void
    {
        unset($user);
        $since                    = (int)($input['since'] ?? 0);
        $privateMessageRepository = static::getPrivateMessageRepository();

        $privateMessageRepository->cleanChatMessages();

        $messages = $privateMessageRepository->getChatMessages($since);
        $response = Subsonic_Xml_Data::addSubsonicResponse('getchatmessages');
        Subsonic_Xml_Data::addChatMessages($response, $messages);
        self::_apiOutput($input, $response);
    }

    /**
     * addChatMessage
     * Adds a message to the chat log.
     * http://www.subsonic.org/pages/api.jsp#addChatMessage
     * @param array $input
     * @param User $user
     */
    public static function addchatmessage($input, $user): void
    {
        $message = self::_check_parameter($input, 'message');
        if (!$message) {
            return;
        }

        if (static::getPrivateMessageRepository()->sendChatMessage(trim($message), $user->id) !== null) {
            $response = Subsonic_Xml_Data::addSubsonicResponse('addchatmessage');
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'addChatMessage');
        }
        self::_apiOutput($input, $response);
    }

    /**
     * getUser
     * Get details about a given user, including which authorization roles and folder access it has.
     * Returns a <subsonic-response> element with a nested <user> element on success.
     * http://www.subsonic.org/pages/api.jsp#getUser
     * @param array $input
     * @param User $user
     */
    public static function getuser($input, $user): void
    {
        $username = self::_check_parameter($input, 'username');
        if ($user->access === 100 || $user->username == $username) {
            $response = Subsonic_Xml_Data::addSubsonicResponse('getuser');
            if ($user->username == $username) {
                $update_user = $user;
            } else {
                $update_user = User::get_from_username((string)$username);
            }
            if (!$update_user) {
                $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'getUser');
            } else {
                Subsonic_Xml_Data::addUser($response, $update_user);
            }
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, 'getuser');
        }
        self::_apiOutput($input, $response);
    }

    /**
     * getUsers
     * Get details about all users, including which authorization roles and folder access they have.
     * Returns a <subsonic-response> element with a nested <users> element on success.
     * http://www.subsonic.org/pages/api.jsp#getUsers
     * @param array $input
     * @param User $user
     */
    public static function getusers($input, $user): void
    {
        if ($user->access === 100) {
            $response = Subsonic_Xml_Data::addSubsonicResponse('getusers');
            $users    = static::getUserRepository()->getValid();
            Subsonic_Xml_Data::addUsers($response, $users);
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, 'getusers');
        }
        self::_apiOutput($input, $response);
    }

    /**
     * createUser
     * Creates a new Subsonic user.
     * http://www.subsonic.org/pages/api.jsp#createUser
     * @param array $input
     * @param User $user
     */
    public static function createuser($input, $user): void
    {
        $username     = self::_check_parameter($input, 'username');
        $password     = self::_check_parameter($input, 'password');
        $email        = urldecode((string)self::_check_parameter($input, 'email'));
        $adminRole    = (array_key_exists('adminRole', $input) && $input['adminRole'] == 'true');
        $downloadRole = (array_key_exists('downloadRole', $input) && $input['downloadRole'] == 'true');
        $uploadRole   = (array_key_exists('uploadRole', $input) && $input['uploadRole'] == 'true');
        $coverArtRole = (array_key_exists('coverArtRole', $input) && $input['coverArtRole'] == 'true');
        $shareRole    = (array_key_exists('shareRole', $input) && $input['shareRole'] == 'true');
        //$ldapAuthenticated = $input['ldapAuthenticated'];
        //$settingsRole = $input['settingsRole'];
        //$streamRole = $input['streamRole'];
        //$jukeboxRole = $input['jukeboxRole'];
        //$playlistRole = $input['playlistRole'];
        //$commentRole = $input['commentRole'];
        //$podcastRole = $input['podcastRole'];
        if ($email) {
            $email = urldecode($email);
        }

        if ($user->access >= AccessLevelEnum::LEVEL_ADMIN) {
            $access = AccessLevelEnum::LEVEL_USER;
            if ($coverArtRole) {
                $access = AccessLevelEnum::LEVEL_MANAGER;
            }
            if ($adminRole) {
                $access = AccessLevelEnum::LEVEL_ADMIN;
            }
            $password = self::_decryptPassword($password);
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
                $response = Subsonic_Xml_Data::addSubsonicResponse('createuser');
            } else {
                $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'createuser');
            }
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, 'createuser');
        }

        self::_apiOutput($input, $response);
    }

    /**
     * updateUser
     * Modifies an existing Subsonic user.
     * http://www.subsonic.org/pages/api.jsp#updateUser
     * @param array $input
     * @param User $user
     */
    public static function updateuser($input, $user): void
    {
        $username = self::_check_parameter($input, 'username');
        $password = $input['password'] ?? false;
        $email    = urldecode((string)self::_check_parameter($input, 'email'));
        //$ldapAuthenticated = $input['ldapAuthenticated'];
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
                    $password = self::_decryptPassword($password);
                    $update_user->update_password($password);
                }
                // update e-mail
                if (Mailer::validate_address($email)) {
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
                $response = Subsonic_Xml_Data::addSubsonicResponse('updateuser');
            } else {
                $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'updateuser');
            }
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, 'updateuser');
        }

        self::_apiOutput($input, $response);
    }

    /**
     * deleteUser
     * Deletes an existing Subsonic user.
     * http://www.subsonic.org/pages/api.jsp#deleteUser
     * @param array $input
     * @param User $user
     */
    public static function deleteuser($input, $user): void
    {
        $username = self::_check_parameter($input, 'username');
        if ($user->access === 100) {
            $update_user = User::get_from_username((string)$username);
            if ($update_user instanceof User) {
                $update_user->delete();
                $response = Subsonic_Xml_Data::addSubsonicResponse('deleteuser');
            } else {
                $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'deleteuser');
            }
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, 'deleteuser');
        }

        self::_apiOutput($input, $response);
    }

    /**
     * changePassword
     * Changes the password of an existing Subsonic user.
     * http://www.subsonic.org/pages/api.jsp#changePassword
     * @param array $input
     * @param User $user
     */
    public static function changepassword($input, $user): void
    {
        $username = self::_check_parameter($input, 'username');
        $inp_pass = self::_check_parameter($input, 'password');
        $password = self::_decryptPassword($inp_pass);
        if ($user->username == $username || $user->access === 100) {
            $update_user = User::get_from_username((string) $username);
            if ($update_user instanceof User && !AmpConfig::get('simple_user_mode')) {
                $update_user->update_password($password);
                $response = Subsonic_Xml_Data::addSubsonicResponse('changepassword');
            } else {
                $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'changepassword');
            }
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, 'changepassword');
        }
        self::_apiOutput($input, $response);
    }

    /**
     * getBookmarks
     * Returns all bookmarks for this user. A bookmark is a position within a certain media file.
     * Returns a <subsonic-response> element with a nested <bookmarks> element on success.
     * http://www.subsonic.org/pages/api.jsp#getBookmarks
     * @param array $input
     * @param User $user
     */
    public static function getbookmarks($input, $user): void
    {
        $response  = Subsonic_Xml_Data::addSubsonicResponse('getbookmarks');
        $bookmarks = [];
        foreach (static::getBookmarkRepository()->getBookmarks($user->id) as $bookmarkId) {
            $bookmarks[] = new Bookmark($bookmarkId);
        }

        Subsonic_Xml_Data::addBookmarks($response, $bookmarks);
        self::_apiOutput($input, $response, array('bookmark'));
    }

    /**
     * createBookmark
     * Creates or updates a bookmark (a position within a media file). Bookmarks are personal and not visible to other users.
     * http://www.subsonic.org/pages/api.jsp#createBookmark
     * @param array $input
     * @param User $user
     */
    public static function createbookmark($input, $user): void
    {
        $object_id = self::_check_parameter($input, 'id');
        $position  = self::_check_parameter($input, 'position');
        if (!$object_id || !$position) {
            return;
        }
        $comment = $input['comment'] ?? '';
        $type    = Subsonic_Xml_Data::_getAmpacheType((string)$object_id);

        if (!empty($type)) {
            $bookmark = new Bookmark(Subsonic_Xml_Data::_getAmpacheId($object_id), $type);
            if ($bookmark->isNew()) {
                Bookmark::create(
                    [
                        'object_id' => Subsonic_Xml_Data::_getAmpacheId($object_id),
                        'object_type' => $type,
                        'comment' => $comment,
                        'position' => $position
                    ],
                    $user->id,
                    time()
                );
            } else {
                static::getBookmarkRepository()->update($bookmark->getId(), (int)$position, time());
            }
            $response = Subsonic_Xml_Data::addSubsonicResponse('createbookmark');
        } else {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'createbookmark');
        }
        self::_apiOutput($input, $response);
    }

    /**
     * deleteBookmark
     * Deletes the bookmark for a given file.
     * http://www.subsonic.org/pages/api.jsp#deleteBookmark
     * @param array $input
     * @param User $user
     */
    public static function deletebookmark($input, $user): void
    {
        $object_id = self::_check_parameter($input, 'id');
        if (!$object_id) {
            return;
        }
        $type = Subsonic_Xml_Data::_getAmpacheType((string)$object_id);

        $bookmark = new Bookmark(Subsonic_Xml_Data::_getAmpacheId($object_id), $type, $user->id);
        if ($bookmark->isNew()) {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'deletebookmark');
        } else {
            static::getBookmarkRepository()->delete($bookmark->getId());
            $response = Subsonic_Xml_Data::addSubsonicResponse('deletebookmark');
        }
        self::_apiOutput($input, $response);
    }

    /**
     * getPlayQueue
     * Returns the state of the play queue for this user (as set by savePlayQueue).
     * Returns a <subsonic-response> element with a nested <playQueue> element on success
     * or an empty <subsonic-response> if no play queue has been saved.
     * http://www.subsonic.org/pages/api.jsp#getPlayQueue
     * @param array $input
     * @param User $user
     */
    public static function getplayqueue($input, $user): void
    {
        $response  = Subsonic_Xml_Data::addSubsonicResponse('getplayqueue');
        $client    = scrub_in((string) ($input['c'] ?? 'Subsonic'));
        $playQueue = new User_Playlist($user->id, $client);
        Subsonic_Xml_Data::addPlayQueue($response, $playQueue, (string)$user->username);
        self::_apiOutput($input, $response);
    }

    /**
     * savePlayQueue
     * Saves the state of the play queue for this user.
     * http://www.subsonic.org/pages/api.jsp#savePlayQueue
     * @param array $input
     * @param User $user
     */
    public static function saveplayqueue($input, $user): void
    {
        $current = (string)($input['current'] ?? '0');
        $media   = Subsonic_Xml_Data::_getAmpacheObject((string)$current);
        if ($media === null || $media->isNew()) {
            $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, 'saveplayqueue');
        } else {
            $response = Subsonic_Xml_Data::addSubsonicResponse('saveplayqueue');
            $position = (array_key_exists('position', $input))
                ? (int)(((int)$input['position']) / 1000)
                : 0;
            $client         = scrub_in((string) ($input['c'] ?? 'Subsonic'));
            $user_id        = $user->id;
            $playqueue_time = (int)User::get_user_data($user->id, 'playqueue_time', 0)['playqueue_time'];
            $time           = time();
            // wait a few seconds before smashing out play times
            if ($playqueue_time < ($time - 2)) {
                $previous = Stats::get_last_play($user_id, $client);
                $type     = Subsonic_Xml_Data::_getAmpacheType($current);
                // long pauses might cause your now_playing to hide
                Stream::garbage_collection();
                Stream::insert_now_playing((int)$media->id, (int)$user_id, ((int)$media->time - $position), (string)$user->username, $type, ($time - $position));

                if (array_key_exists('object_id', $previous) && $previous['object_id'] == $media->id) {
                    $time_diff = $time - $previous['date'];
                    $old_play  = $time_diff > $media->time * 5;
                    // shift the start time if it's an old play or has been pause/played
                    if ($position >= 1 || $old_play) {
                        Stats::shift_last_play($user_id, $client, $previous['date'], ($time - $position));
                    }
                    // track has just started. repeated plays aren't called by scrobble so make sure we call this too
                    if (($position < 1 && $time_diff > 5) && !$old_play) {
                        $media->set_played((int)$user_id, $client, array(), $time);
                    }
                }
                $playQueue = new User_Playlist($user_id, $client);
                $sub_ids   = (is_array($input['id']))
                    ? $input['id']
                    : array($input['id']);
                $playlist = Subsonic_Xml_Data::_getAmpacheIdArrays($sub_ids);
                $playQueue->set_items($playlist, $type, $media->id, $position, $time);
            }
        }

        self::_apiOutput($input, $response);
    }

    /**
     * _albumList
     * @param array $input
     * @param User $user
     * @param string $type
     * @return array|false
     */
    private static function _albumList($input, $user, $type)
    {
        $size          = (int)($input['size'] ?? 10);
        $offset        = (int)($input['offset'] ?? 0);
        $musicFolderId = (int)($input['musicFolderId'] ?? 0);

        // Get albums from all catalogs by default Catalog filter is not supported for all request types for now.
        $catalogs = null;
        if ($musicFolderId > 0) {
            $catalogs   = array();
            $catalogs[] = $musicFolderId;
        }
        $albums = false;
        switch ($type) {
            case "random":
                $albums = static::getAlbumRepository()->getRandom(
                    $user->id,
                    $size
                );
                break;
            case "newest":
                $albums = Stats::get_newest("album", $size, $offset, $musicFolderId, $user->id);
                break;
            case "highest":
                $albums = Rating::get_highest("album", $size, $offset, $user->id);
                break;
            case "frequent":
                $albums = Stats::get_top("album", $size, 0, $offset);
                break;
            case "recent":
                $albums = Stats::get_recent("album", $size, $offset);
                break;
            case "starred":
                $albums = Userflag::get_latest('album', 0, $size, $offset);
                break;
            case "alphabeticalByName":
                $albums = Catalog::get_albums($size, $offset, $catalogs);
                break;
            case "alphabeticalByArtist":
                $albums = Catalog::get_albums_by_artist($size, $offset, $catalogs);
                break;
            case "byYear":
                $fromYear = (int)min($input['fromYear'], $input['toYear']);
                $toYear   = (int)max($input['fromYear'], $input['toYear']);

                if ($fromYear || $toYear) {
                    $data   = Search::year_search($fromYear, $toYear, $size, $offset);
                    $albums = Search::run($data, $user);
                }
                break;
            case "byGenre":
                $genre  = self::_check_parameter($input, 'genre');
                $tag_id = Tag::tag_exists($genre);
                if ($tag_id > 0) {
                    $albums = Tag::get_tag_objects('album', $tag_id, $size, $offset);
                }
                break;
        }

        return $albums;
    }

    /**
     * _updatePlaylist
     * @param int|string $playlist_id
     * @param string $name
     * @param array $songsIdToAdd
     * @param array $songIndexToRemove
     * @param bool $public
     * @param bool $clearFirst
     */
    private static function _updatePlaylist(
        $playlist_id,
        $name,
        $songsIdToAdd = array(),
        $songIndexToRemove = array(),
        $public = true,
        $clearFirst = false
    ): void {
        // If it's a string it probably needs a clean up
        if (is_string($playlist_id)) {
            $playlist_id = Subsonic_Xml_Data::_getAmpacheId($playlist_id);
        }
        $playlist           = new Playlist($playlist_id);
        $songsIdToAdd_count = count($songsIdToAdd);
        $newdata            = array();
        $newdata['name']    = (!empty($name)) ? $name : $playlist->name;
        $newdata['pl_type'] = ($public) ? "public" : "private";
        $playlist->update($newdata);
        if ($clearFirst) {
            $playlist->delete_all();
        }

        if ($songsIdToAdd_count > 0) {
            for ($i = 0; $i < $songsIdToAdd_count; ++$i) {
                $songsIdToAdd[$i] = Subsonic_Xml_Data::_getAmpacheId($songsIdToAdd[$i]);
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
     * _setStar
     * @param array $input
     * @param User $user
     * @param bool $star
     */
    private static function _setStar($input, $user, $star): void
    {
        $object_id = $input['id'] ?? null;
        $albumId   = $input['albumId'] ?? null;
        $artistId  = $input['artistId'] ?? null;

        // Normalize all in one array
        $ids = array();

        $response = Subsonic_Xml_Data::addSubsonicResponse('_setStar');
        if ($object_id) {
            if (!is_array($object_id)) {
                $object_id = array($object_id);
            }
            foreach ($object_id as $item) {
                $aid = Subsonic_Xml_Data::_getAmpacheId($item);
                if (Subsonic_Xml_Data::_isArtist($item)) {
                    $type = 'artist';
                } else {
                    if (Subsonic_Xml_Data::_isAlbum($item)) {
                        $type = 'album';
                    } else {
                        if (Subsonic_Xml_Data::_isSong($item)) {
                            $type = 'song';
                        } else {
                            $type = "";
                        }
                    }
                }
                $ids[] = array('id' => $aid, 'type' => $type);
            }
        } else {
            if ($albumId) {
                if (!is_array($albumId)) {
                    $albumId = array($albumId);
                }
                foreach ($albumId as $album) {
                    $aid   = Subsonic_Xml_Data::_getAmpacheId($album);
                    $ids[] = array('id' => $aid, 'type' => 'album');
                }
            } else {
                if ($artistId) {
                    if (!is_array($artistId)) {
                        $artistId = array($artistId);
                    }
                    foreach ($artistId as $artist) {
                        $aid   = Subsonic_Xml_Data::_getAmpacheId($artist);
                        $ids[] = array('id' => $aid, 'type' => 'artist');
                    }
                } else {
                    $response = Subsonic_Xml_Data::addError(Subsonic_Xml_Data::SSERROR_MISSINGPARAM, '_setStar');
                }
            }
        }

        foreach ($ids as $object_id) {
            $flag = new Userflag($object_id['id'], $object_id['type']);
            $flag->set_flag($star, $user->id);
        }
        self::_apiOutput($input, $response);
    }

    /**
     * startScan
     * @param array $input
     * @param User $user
     */
    public static function startscan($input, $user): void
    {
        $response = Subsonic_Xml_Data::addSubsonicResponse('startscan');
        Subsonic_Xml_Data::addScanStatus($response, $user);
        self::_apiOutput($input, $response);
    }

    /**
     * getscanstatus
     * @param array $input
     * @param User $user
     */
    public static function getscanstatus($input, $user): void
    {
        $response = Subsonic_Xml_Data::addSubsonicResponse('getscanstatus');
        Subsonic_Xml_Data::addScanStatus($response, $user);
        self::_apiOutput($input, $response);
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
    private static function getSongRepository(): SongRepositoryInterface
    {
        global $dic;

        return $dic->get(SongRepositoryInterface::class);
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
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
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
    private static function getPodcastSyncer(): PodcastSyncerInterface
    {
        global $dic;

        return $dic->get(PodcastSyncerInterface::class);
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
    private static function getPodcastEpisodeDownloader(): PodcastEpisodeDownloaderInterface
    {
        global $dic;

        return $dic->get(PodcastEpisodeDownloaderInterface::class);
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
}
