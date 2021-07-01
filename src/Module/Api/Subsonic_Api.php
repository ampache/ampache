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

declare(strict_types=0);

namespace Ampache\Module\Api;

use Ampache\Module\System\Session;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Random;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\User\PasswordGenerator;
use Ampache\Module\User\PasswordGeneratorInterface;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\Recommendation;
use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Bookmark;
use Ampache\Repository\Model\Catalog;
use Ampache\Module\System\Core;
use Ampache\Repository\AlbumRepositoryInterface;
use Ampache\Repository\BookmarkRepositoryInterface;
use Ampache\Repository\LiveStreamRepositoryInterface;
use Ampache\Repository\Model\User_Playlist;
use Ampache\Repository\PrivateMessageRepositoryInterface;
use Ampache\Repository\SongRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use DOMDocument;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Rating;
use Requests;
use Ampache\Repository\Model\Search;
use Ampache\Repository\Model\Share;
use SimpleXMLElement;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;

/**
 * Subsonic Class
 *
 * This class wrap Ampache to Subsonic API functions. See http://www.subsonic.org/pages/api.jsp
 * These are all static calls.
 *
 * @SuppressWarnings("unused")
 */
class Subsonic_Api
{

    /**
     * check_parameter
     * @param array $input
     * @param string $parameter
     * @param boolean $addheader
     * @return boolean|mixed
     */
    public static function check_parameter($input, $parameter, $addheader = false)
    {
        if (empty($input[$parameter])) {
            ob_end_clean();
            if ($addheader) {
                self::setHeader($input['f']);
            }
            self::apiOutput($input,
                Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_MISSINGPARAM, '', 'check_parameter'));

            return false;
        }

        return $input[$parameter];
    }

    /**
     * @param $password
     * @return string
     */
    public static function decrypt_password($password)
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
     * @param $curl
     * @param $data
     * @return integer
     */
    public static function output_body($curl, $data)
    {
        unset($curl);
        echo $data;
        ob_flush();

        return strlen((string)$data);
    }

    /**
     * @param $curl
     * @param $header
     * @return integer
     */
    public static function output_header($curl, $header)
    {
        $rheader = trim((string)$header);
        $rhpart  = explode(':', $rheader);
        if (!empty($rheader) && count($rhpart) > 1) {
            if ($rhpart[0] != "Transfer-Encoding") {
                header($rheader);
            }
        } else {
            if (substr($header, 0, 5) === "HTTP/") {
                // if $header starts with HTTP/ assume it's the status line
                http_response_code(curl_getinfo($curl, CURLINFO_HTTP_CODE));
            }
        }

        return strlen((string)$header);
    }

    /**
     * follow_stream
     * @param string $url
     */
    public static function follow_stream($url)
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
            // Curl support, we stream transparently to avoid redirect. Redirect can fail on few clients
            debug_event(self::class, 'Stream proxy: ' . $url, 5);
            $curl = curl_init($url);
            if ($curl) {
                curl_setopt_array($curl, array(
                    CURLOPT_FAILONERROR => true,
                    CURLOPT_HTTPHEADER => $reqheaders,
                    CURLOPT_HEADER => false,
                    CURLOPT_RETURNTRANSFER => false,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_WRITEFUNCTION => array('Ampache\Module\Api\Subsonic_Api', 'output_body'),
                    CURLOPT_HEADERFUNCTION => array('Ampache\Module\Api\Subsonic_Api', 'output_header'),
                    // Ignore invalid certificate
                    // Default trusted chain is crap anyway and currently no custom CA option
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_TIMEOUT => 0
                ));
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
     * @param $filetype
     */
    public static function setHeader($filetype)
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
     * apiOutput
     * @param array $input
     * @param SimpleXMLElement $xml
     * @param array $alwaysArray
     */

    public static function apiOutput($input, $xml, $alwaysArray = array('musicFolder', 'channel', 'artist', 'child', 'song', 'album', 'share', 'entry'))
    {
        $format   = ($input['f']) ? strtolower((string) $input['f']) : 'xml';
        $callback = $input['callback'];
        self::apiOutput2($format, $xml, $callback, $alwaysArray);
    }

    /**
     * apiOutput2
     * @param string $format
     * @param SimpleXMLElement $xml
     * @param string $callback
     * @param array $alwaysArray
     */
    public static function apiOutput2($format, $xml, $callback = '', $alwaysArray = array('musicFolder', 'channel', 'artist', 'child', 'song', 'album', 'share', 'entry'))
    {
        $conf = array('alwaysArray' => $alwaysArray);
        if ($format == "json") {
            echo json_encode(self::xml2json($xml, $conf), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            return;
        }
        if ($format == "jsonp") {
            echo $callback . '(' . json_encode(self::xml2json($xml, $conf), JSON_PRETTY_PRINT) . ')';

            return;
        }
        $xmlstr = $xml->asXml();
        // clean illegal XML characters.
        $clean_xml = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '_', $xmlstr);
        $dom       = new DOMDocument();
        $dom->loadXML($clean_xml, LIBXML_PARSEHUGE);
        $dom->formatOutput = true;
        $output            = $dom->saveXML();
        // saving xml can fail
        if (!$output) {
            $output = "<subsonic-response status=\"failed\" version=\"1.13.0\"><error code=\"0\" message=\"Error creating response.\"/></subsonic-response>";
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
    private static function xml2json($xml, $input_options = array())
    {
        $defaults = array(
            'namespaceSeparator' => ' :', // you may want this to be something other than a colon
            'attributePrefix' => '', // to distinguish between attributes and nodes with the same name
            'alwaysArray' => array('musicFolder', 'channel', 'artist', 'child', 'song', 'album', 'share'), // array of xml tag names which should always become arrays
            'alwaysDouble' => array('AverageRating'),
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
                        $vattr = (double) $strattr;
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
                $childArray = self::xml2json($childXml, $options);
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
                            } elseif (self::has_Nested_Array($childProperties) && !in_array($childTagName, $forceArray)) {
                                $tagsArray[$childTagName] = (object)$childProperties;
                            } else {
                                // test if tags of this type should always be arrays, no matter the element count
                                $tagsArray[$childTagName] = in_array($childTagName,
                                    $options['alwaysArray']) || !$options['autoArray'] ? array($childProperties) : $childProperties;
                            }
                        } else {
                            // test if tags of this type should always be arrays, no matter the element count
                            $tagsArray[$childTagName] = in_array($childTagName,
                                $options['alwaysArray']) || !$options['autoArray'] ? array($childProperties) : $childProperties;
                        }
                    } elseif (is_array($tagsArray[$childTagName]) && array_keys($tagsArray[$childTagName]) === range(0,
                            count($tagsArray[$childTagName]) - 1)) {
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
        $propertiesArray = !$options['autoText'] || !empty($attributesArray) || !empty($tagsArray) || ($plainText === '') ? array_merge($attributesArray,
            $tagsArray, $textContentArray) : $plainText;

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
     * @return boolean
     */
    private static function has_Nested_Array($properties)
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
     * Simple server ping to test connectivity with the server.
     * Takes no parameter.
     * @param array $input
     */
    public static function ping($input)
    {
        // Don't check client API version here. Some client give version 0.0.0 for ping command

        self::apiOutput($input, Subsonic_Xml_Data::createSuccessResponse('ping'));
    }

    /**
     * getLicense
     * Get details about the software license. Always return a valid default license.
     * Takes no parameter.
     * @param array $input
     */
    public static function getlicense($input)
    {
        $response = Subsonic_Xml_Data::createSuccessResponse('getlicense');
        Subsonic_Xml_Data::addLicense($response);
        self::apiOutput($input, $response);
    }

    /**
     * getMusicFolders
     * Get all configured top-level music folders (= Ampache catalogs).
     * Takes no parameter.
     * @param array $input
     */
    public static function getmusicfolders($input)
    {
        $username = $input['u'];
        $user     = User::get_from_username((string)$username);
        $catalogs = Catalog::get_catalogs('music', $user->id);
        $response = Subsonic_Xml_Data::createSuccessResponse('getmusicfolders');
        Subsonic_Xml_Data::addMusicFolders($response, $catalogs);
        self::apiOutput($input, $response);
    }

    /**
     * getIndexes
     * Get an indexed structure of all artists.
     * Takes optional musicFolderId and optional ifModifiedSince in parameters.
     * @param array $input
     */
    public static function getindexes($input)
    {
        set_time_limit(300);

        $username         = self::check_parameter($input, 'u');
        $user             = User::get_from_username((string)$username);
        $musicFolderId    = $input['musicFolderId'];
        $ifModifiedSince  = $input['ifModifiedSince'];

        $catalogs = array();
        if (!empty($musicFolderId) && $musicFolderId != '-1') {
            $catalogs[] = $musicFolderId;
        } else {
            $catalogs = Catalog::get_catalogs('', $user->id);
        }

        $lastmodified = 0;
        $fcatalogs    = array();

        foreach ($catalogs as $catalogid) {
            $clastmodified = 0;
            $catalog       = Catalog::create_from_id($catalogid);

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
            if (!empty($ifModifiedSince) && $clastmodified > ($ifModifiedSince / 1000)) {
                $fcatalogs[] = $catalogid;
            }
        }
        if (empty($ifModifiedSince)) {
            $fcatalogs = $catalogs;
        }

        $response = Subsonic_Xml_Data::createSuccessResponse('getindexes');
        if (count($fcatalogs) > 0) {
            $artists = Catalog::get_artist_arrays($fcatalogs);
            Subsonic_Xml_Data::addArtistsIndexes($response, $artists, $lastmodified, $fcatalogs);
        }
        self::apiOutput($input, $response);
    }

    /**
     * getMusicDirectory
     * Get a list of all files in a music directory.
     * Takes the directory id in parameters.
     * @param array $input
     */
    public static function getmusicdirectory($input)
    {
        $object_id = self::check_parameter($input, 'id');
        $response  = Subsonic_Xml_Data::createSuccessResponse('getmusicdirectory');
        if (Subsonic_Xml_Data::isArtist($object_id)) {
            Subsonic_Xml_Data::addArtistDirectory($response, $object_id);
        } elseif (Subsonic_Xml_Data::isAlbum($object_id)) {
            Subsonic_Xml_Data::addAlbumDirectory($response, $object_id);
        }
        self::apiOutput($input, $response);
    }

    /**
     * getGenres
     * Get all genres.
     * Takes no parameter.
     * @param array $input
     */
    public static function getgenres($input)
    {
        $response = Subsonic_Xml_Data::createSuccessResponse('getgenres');
        Subsonic_Xml_Data::addGenres($response, Tag::get_tags('song'));
        self::apiOutput($input, $response);
    }

    /**
     * getArtists
     * Get all artists.
     * @param array $input
     */
    public static function getartists($input)
    {
        $musicFolderId = $input['musicFolderId'];
        $catalogs      = array();
        if (!empty($musicFolderId) && $musicFolderId != '-1') {
            $catalogs[] = $musicFolderId;
        }
        $response = Subsonic_Xml_Data::createSuccessResponse('getartists');
        $artists  = Artist::get_id_arrays($catalogs);
        Subsonic_Xml_Data::addArtistsRoot($response, $artists);
        self::apiOutput($input, $response);
    }

    /**
     * getArtist
     * Get details for an artist, including a list of albums.
     * Takes the artist id in parameter.
     * @param array $input
     */
    public static function getartist($input)
    {
        $artistid = self::check_parameter($input, 'id');

        $artist = new Artist(Subsonic_Xml_Data::getAmpacheId($artistid));
        if (empty($artist->name)) {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, "Artist not found.",
                'getartist');
        } else {
            $response = Subsonic_Xml_Data::createSuccessResponse('getartist');
            Subsonic_Xml_Data::addArtist($response, $artist, true, true);
        }
        self::apiOutput($input, $response);
    }

    /**
     * getAlbum
     * Get details for an album, including a list of songs.
     * Takes the album id in parameter.
     * @param array $input
     */
    public static function getalbum($input)
    {
        $albumid = self::check_parameter($input, 'id');

        $addAmpacheInfo = ($input['ampache'] == "1");

        $album = new Album(Subsonic_Xml_Data::getAmpacheId($albumid));
        if (!$album->id) {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, "Album not found.",
                'getalbum');
        } else {
            $response = Subsonic_Xml_Data::createSuccessResponse('getalbum');
            Subsonic_Xml_Data::addAlbum($response, $album, true, $addAmpacheInfo);
        }

        self::apiOutput($input, $response);
    }

    /**
     * getVideos
     * Get all videos.
     * Takes no parameter.
     * @param array $input
     */
    public static function getvideos($input)
    {
        $response = Subsonic_Xml_Data::createSuccessResponse('getvideos');
        $videos   = Catalog::get_videos();
        Subsonic_Xml_Data::addVideos($response, $videos);
        self::apiOutput($input, $response);
    }

    /**
     * _albumList
     * @param array $input
     * @param string $type
     * @return array|false
     */
    private static function _albumList($input, $type)
    {
        $size          = $input['size'] ?: 10;
        $offset        = $input['offset'] ?: 0;
        $musicFolderId = $input['musicFolderId'] ?: 0;

        // Get albums from all catalogs by default Catalog filter is not supported for all request types for now.
        $catalogs = null;
        if ($musicFolderId > 0) {
            $catalogs   = array();
            $catalogs[] = $musicFolderId;
        }
        $albums = false;
        switch ($type) {
            case "random":
                $username = self::check_parameter($input, 'u');
                $user     = User::get_from_username((string)$username);
                $albums   = static::getAlbumRepository()->getRandom(
                    $user->id,
                    $size
                );
                break;
            case "newest":
                $username = self::check_parameter($input, 'u');
                $user     = User::get_from_username((string)$username);
                $albums   = Stats::get_newest("album", $size, $offset, $musicFolderId, $user->id);
                break;
            case "highest":
                $username = self::check_parameter($input, 'u');
                $user     = User::get_from_username((string)$username);
                $albums   = Rating::get_highest("album", $size, $offset, $user->id);
                break;
            case "frequent":
                $albums = Stats::get_top("album", $size, 0, $offset);
                break;
            case "recent":
                $albums = Stats::get_recent("album", $size, $offset);
                break;
            case "starred":
                $albums   = Userflag::get_latest('album', 0, $size, $offset);
                break;
            case "alphabeticalByName":
                $albums = Catalog::get_albums($size, $offset, $catalogs);
                break;
            case "alphabeticalByArtist":
                $albums = Catalog::get_albums_by_artist($size, $offset, $catalogs);
                break;
            case "byYear":
                $fromYear = $input['fromYear'] < $input['toYear'] ? $input['fromYear'] : $input['toYear'];
                $toYear   = $input['toYear'] > $input['fromYear'] ? $input['toYear'] : $input['fromYear'];

                if ($fromYear || $toYear) {
                    $search = Search::year_search($fromYear, $toYear, $size, $offset);
                    $albums = Search::run($search);
                }
                break;
            case "byGenre":
                $genre  = self::check_parameter($input, 'genre');
                $tag_id = Tag::tag_exists($genre);
                if ($tag_id > 0) {
                    $albums = Tag::get_tag_objects('album', $tag_id, $size, $offset);
                }
                break;
            default:
                $albums = false;
        }

        return $albums;
    }

    /**
     * getAlbumList
     * Get a list of random, newest, highest rated etc. albums.
     * Takes the list type with optional size and offset in parameters.
     * @param array $input
     * @param string $elementName
     */
    public static function getalbumlist($input, $elementName = "albumList")
    {
        $type = self::check_parameter($input, 'type');
        if ($type) {
            $response     = Subsonic_Xml_Data::createSuccessResponse('getalbumlist');
            $errorOccured = false;
            $albums       = self::_albumList($input, $type);
            if ($albums === false) {
                $response     = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_GENERIC, "Invalid list type: " . scrub_out((string)$type), $elementName);
                $errorOccured = true;
            }
            if (!$errorOccured) {
                Subsonic_Xml_Data::addAlbumList($response, $albums, $elementName);
            }
        }
        self::apiOutput($input, $response);
    }

    /**
     * getAlbumList2
     * See getAlbumList.
     * @param array $input
     */
    public static function getalbumlist2($input)
    {
        self::getAlbumList($input, "albumList2");
    }

    /**
     * getRandomSongs
     * Get random songs matching the given criteria.
     * Takes the optional size, genre, fromYear, toYear and music folder id in parameters.
     * @param array $input
     */
    public static function getrandomsongs($input)
    {
        $size = $input['size'];
        if (!$size) {
            $size = 10;
        }

        $username      = self::check_parameter($input, 'u');
        $genre         = $input['genre'];
        $fromYear      = $input['fromYear'];
        $toYear        = $input['toYear'];
        $musicFolderId = $input['musicFolderId'];

        $search           = array();
        $search['limit']  = $size;
        $search['random'] = $size;
        $search['type']   = "song";
        $count            = 0;
        if ($genre) {
            $search['rule_' . $count . '_input']    = $genre;
            $search['rule_' . $count . '_operator'] = 0;
            $search['rule_' . $count . '']          = "tag";
            ++$count;
        }
        if ($fromYear) {
            $search['rule_' . $count . '_input']    = $fromYear;
            $search['rule_' . $count . '_operator'] = 0;
            $search['rule_' . $count . '']          = "year";
            ++$count;
        }
        if ($toYear) {
            $search['rule_' . $count . '_input']    = $toYear;
            $search['rule_' . $count . '_operator'] = 1;
            $search['rule_' . $count . '']          = "year";
            ++$count;
        }
        if ($musicFolderId) {
            if (Subsonic_Xml_Data::isArtist($musicFolderId)) {
                $artist   = new Artist(Subsonic_Xml_Data::getAmpacheId($musicFolderId));
                $finput   = $artist->f_name;
                $operator = 4;
                $ftype    = "artist";
            } else {
                if (Subsonic_Xml_Data::isAlbum($musicFolderId)) {
                    $album    = new Album(Subsonic_Xml_Data::getAmpacheId($musicFolderId));
                    $finput   = $album->f_name;
                    $operator = 4;
                    $ftype    = "artist";
                } else {
                    $finput   = (int)($musicFolderId);
                    $operator = 0;
                    $ftype    = "catalog";
                }
            }
            $search['rule_' . $count . '_input']    = $finput;
            $search['rule_' . $count . '_operator'] = $operator;
            $search['rule_' . $count . '']          = $ftype;
            ++$count;
        }
        $user = User::get_from_username((string)$username);
        if ($count > 0) {
            $songs = Random::advanced('song', $search);
        } else {
            $songs = Random::get_default($size, $user->id);
        }

        $response = Subsonic_Xml_Data::createSuccessResponse('getrandomsongs');
        Subsonic_Xml_Data::addRandomSongs($response, $songs);
        self::apiOutput($input, $response);
    }

    /**
     * getSong
     * Get details for a song
     * Takes the song id in parameter.
     * @param array $input
     */
    public static function getsong($input)
    {
        $songid   = self::check_parameter($input, 'id');
        $response = Subsonic_Xml_Data::createSuccessResponse('getsong');
        $song     = Subsonic_Xml_Data::getAmpacheId($songid);
        Subsonic_Xml_Data::addSong($response, $song);
        self::apiOutput($input, $response);
    }

    /**
     * getTopSongs
     * Get most popular songs for a given artist.
     * Takes the genre with optional count and offset in parameters.
     * @param array $input
     */
    public static function gettopsongs($input)
    {
        $artist = self::check_parameter($input, 'artist');
        $count  = (int)$input['count'];
        $songs  = array();
        if ($count < 1) {
            $count = 50;
        }
        if ($artist) {
            $songs = static::getSongRepository()->getTopSongsByArtist(
                Artist::get_from_name(urldecode($artist)),
                $count
            );
        }
        $response = Subsonic_Xml_Data::createSuccessResponse('gettopsongs');
        Subsonic_Xml_Data::addTopSongs($response, $songs);
        self::apiOutput($input, $response);
    }

    /**
     * getSongsByGenre
     * Get songs in a given genre.
     * Takes the genre with optional count and offset in parameters.
     * @param array $input
     */
    public static function getsongsbygenre($input)
    {
        $genre  = self::check_parameter($input, 'genre');
        $count  = $input['count'];
        $offset = $input['offset'];

        $tag = Tag::construct_from_name($genre);
        if ($tag->id) {
            $songs = Tag::get_tag_objects("song", $tag->id, $count, $offset);
        } else {
            $songs = array();
        }
        $response = Subsonic_Xml_Data::createSuccessResponse('getsongsbygenre');
        Subsonic_Xml_Data::addSongsByGenre($response, $songs);
        self::apiOutput($input, $response);
    }

    /**
     * getNowPlaying
     * Get what is currently being played by all users.
     * Takes no parameter.
     * @param array $input
     */
    public static function getnowplaying($input)
    {
        $data     = Stream::get_now_playing();
        $response = Subsonic_Xml_Data::createSuccessResponse('getnowplaying');
        Subsonic_Xml_Data::addNowPlaying($response, $data);
        self::apiOutput($input, $response);
    }

    /**
     * search2
     * Get albums, artists and songs matching the given criteria.
     * Takes query with optional artist count, artist offset, album count, album offset, song count and song offset in parameters.
     * @param array $input
     * @param string $elementName
     */
    public static function search2($input, $elementName = "searchResult2")
    {
        $query    = self::check_parameter($input, 'query');
        $artists  = array();
        $albums   = array();
        $songs    = array();
        $operator = 0;

        if (strlen((string)$query) > 1) {
            if (substr($query, -1) == "*") {
                $query    = substr((string)$query, 0, -1);
                $operator = 2; // Start with
            }
        }

        $artistCount  = isset($input['artistCount']) ? $input['artistCount'] : 20;
        $artistOffset = $input['artistOffset'];
        $albumCount   = isset($input['albumCount']) ? $input['albumCount'] : 20;
        $albumOffset  = $input['albumOffset'];
        $songCount    = isset($input['songCount']) ? $input['songCount'] : 20;
        $songOffset   = $input['songOffset'];

        $sartist          = array();
        $sartist['limit'] = $artistCount;
        if ($artistOffset) {
            $sartist['offset'] = $artistOffset;
        }
        $sartist['rule_1_input']    = $query;
        $sartist['rule_1_operator'] = $operator;
        $sartist['rule_1']          = "name";
        $sartist['type']            = "artist";
        if ($artistCount > 0) {
            $artists = Search::run($sartist);
        }

        $salbum          = array();
        $salbum['limit'] = $albumCount;
        if ($albumOffset) {
            $salbum['offset'] = $albumOffset;
        }
        $salbum['rule_1_input']    = $query;
        $salbum['rule_1_operator'] = $operator;
        $salbum['rule_1']          = "title";
        $salbum['type']            = "album";
        if ($albumCount > 0) {
            $albums = Search::run($salbum);
        }

        $ssong          = array();
        $ssong['limit'] = $songCount;
        if ($songOffset) {
            $ssong['offset'] = $songOffset;
        }
        $ssong['rule_1_input']    = $query;
        $ssong['rule_1_operator'] = $operator;
        $ssong['rule_1']          = "anywhere";
        $ssong['type']            = "song";
        if ($songCount > 0) {
            $songs = Search::run($ssong);
        }

        $response = Subsonic_Xml_Data::createSuccessResponse('search2');
        Subsonic_Xml_Data::addSearchResult($response, $artists, $albums, $songs, $elementName);
        self::apiOutput($input, $response);
    }

    /**
     * search3
     * See search2.
     * @param array $input
     */
    public static function search3($input)
    {
        self::search2($input, "searchResult3");
    }

    /**
     * getPlaylists
     * Get all playlists a user is allowed to play.
     * Takes optional user in parameter.
     * @param array $input
     */
    public static function getplaylists($input)
    {
        $response = Subsonic_Xml_Data::createSuccessResponse('getplaylists');
        $username = $input['username'] ?: $input['u'];
        $user     = User::get_from_username((string)$username);

        // Don't allow playlist listing for another user
        Subsonic_Xml_Data::addPlaylists($response, Playlist::get_playlists($user->id),
            Playlist::get_smartlists($user->id));
        self::apiOutput($input, $response);
    }

    /**
     * getPlaylist
     * Get the list of files in a saved playlist.
     * Takes the playlist id in parameters.
     * @param array $input
     */
    public static function getplaylist($input)
    {
        $playlistid = self::check_parameter($input, 'id');

        $response = Subsonic_Xml_Data::createSuccessResponse('getplaylist');
        if (Subsonic_Xml_Data::isSmartPlaylist($playlistid)) {
            $playlist = new Search(Subsonic_Xml_Data::getAmpacheId($playlistid), 'song');
            Subsonic_Xml_Data::addSmartPlaylist($response, $playlist, true);
        } else {
            $playlist = new Playlist(Subsonic_Xml_Data::getAmpacheId($playlistid));
            Subsonic_Xml_Data::addPlaylist($response, $playlist, true);
        }
        self::apiOutput($input, $response);
    }

    /**
     * createPlaylist
     * Create (or updates) a playlist.
     * Takes playlist id in parameter if updating, name in parameter if creating and a list of song id for the playlist.
     * @param array $input
     */
    public static function createplaylist($input)
    {
        $playlistId = $input['playlistId'];
        $name       = $input['name'];
        $songIdList = array();
        if (is_array($input['songId'])) {
            $songIdList = $input['songId'];
        } elseif (is_string($input['songId'])) {
            $songIdList = explode(',', $input['songId']);
        }

        if ($playlistId) {
            self::_updatePlaylist($playlistId, $name, $songIdList, array(), true, true);
            $response = Subsonic_Xml_Data::createSuccessResponse('createplaylist');
        } else {
            if (!empty($name)) {
                $playlistId = Playlist::create($name, 'private');
                if (count($songIdList) > 0) {
                    self::_updatePlaylist($playlistId, "", $songIdList, array(), true, true);
                }
                $response = Subsonic_Xml_Data::createSuccessResponse('createplaylist');
            } else {
                $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_MISSINGPARAM, '',
                    'createplaylist');
            }
        }
        self::apiOutput($input, $response);
    }

    /**
     * @param $playlist_id
     * @param string $name
     * @param array $songsIdToAdd
     * @param array $songIndexToRemove
     * @param boolean $public
     * @param boolean $clearFirst
     */
    private static function _updatePlaylist(
        $playlist_id,
        $name,
        $songsIdToAdd = array(),
        $songIndexToRemove = array(),
        $public = true,
        $clearFirst = false
    ) {
        $playlist           = new Playlist(Subsonic_Xml_Data::getAmpacheId($playlist_id));
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
                $songsIdToAdd[$i] = Subsonic_Xml_Data::getAmpacheId($songsIdToAdd[$i]);
            }
            $playlist->add_songs($songsIdToAdd, (bool) AmpConfig::get('unique_playlist'));
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
     * updatePlaylist
     * Update a playlist.
     * Takes playlist id in parameter with optional name, comment, public level and a list of song id to add/remove.
     * @param array $input
     */
    public static function updateplaylist($input)
    {
        $playlistId = self::check_parameter($input, 'playlistId');
        $name       = $input['name'];
        $public     = ($input['public'] === "true");

        if (!Subsonic_Xml_Data::isSmartPlaylist($playlistId)) {
            $songIdToAdd = array();
            if (is_array($input['songIdToAdd'])) {
                $songIdToAdd = $input['songIdToAdd'];
            } elseif (is_string($input['songIdToAdd'])) {
                $songIdToAdd = explode(',', $input['songIdToAdd']);
            }
            $songIndexToRemove = array();
            if (is_array($input['songIndexToRemove'])) {
                $songIndexToRemove = $input['songIndexToRemove'];
            } elseif (is_string($input['songIndexToRemove'])) {
                $songIndexToRemove = explode(',', $input['songIndexToRemove']);
            }
            self::_updatePlaylist(Subsonic_Xml_Data::getAmpacheId($playlistId), $name, $songIdToAdd, $songIndexToRemove,
                $public);

            $response = Subsonic_Xml_Data::createSuccessResponse('updateplaylist');
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED,
                'Cannot edit a smart playlist.', 'updateplaylist');
        }
        self::apiOutput($input, $response);
    }

    /**
     * deletePlaylist
     * Delete a saved playlist.
     * Takes playlist id in parameter.
     * @param array $input
     */
    public static function deleteplaylist($input)
    {
        $playlistId = self::check_parameter($input, 'id');

        if (Subsonic_Xml_Data::isSmartPlaylist($playlistId)) {
            $playlist = new Search(Subsonic_Xml_Data::getAmpacheId($playlistId), 'song');
            $playlist->delete();
        } else {
            $playlist = new Playlist(Subsonic_Xml_Data::getAmpacheId($playlistId));
            $playlist->delete();
        }

        $response = Subsonic_Xml_Data::createSuccessResponse('deleteplaylist');
        self::apiOutput($input, $response);
    }

    /**
     * stream
     * Streams a given media file.
     * Takes the file id in parameter with optional max bit rate, file format, time offset, size and estimate content length option.
     * @param array $input
     */
    public static function stream($input)
    {
        $fileid = self::check_parameter($input, 'id', true);

        $maxBitRate    = $input['maxBitRate'];
        $format        = $input['format']; // mp3, flv or raw
        $timeOffset    = $input['timeOffset'];
        $contentLength = $input['estimateContentLength']; // Force content-length guessing if transcode
        $user_id       = User::get_from_username($input['u'])->id;

        $params = '&client=' . rawurlencode($input['c']);
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
        if (AmpConfig::get('subsonic_stream_scrobble') == 'false') {
            $params .= '&cache=1';
        }

        $url = '';
        if (Subsonic_Xml_Data::isSong($fileid)) {
            $object = new Song(Subsonic_Xml_Data::getAmpacheId($fileid));
            $url    = $object->play_url($params, 'api', function_exists('curl_version'), $user_id);
        } elseif (Subsonic_Xml_Data::isPodcastEp($fileid)) {
            $object = new Podcast_Episode(Subsonic_Xml_Data::getAmpacheId($fileid));
            $url    = $object->play_url($params, 'api', function_exists('curl_version'), $user_id);
        }

        // return an error on missing files
        if (empty($url)) {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, '', 'download');
            self::apiOutput($input, $response);

            return;
        }
        self::follow_stream($url);
    }

    /**
     * download
     * Downloads a given media file.
     * Takes the file id in parameter.
     * @param array $input
     */
    public static function download($input)
    {
        $fileid  = self::check_parameter($input, 'id', true);
        $user_id = User::get_from_username($input['u'])->id;
        $params  = '&action=download' . '&client=' . rawurlencode($input['c']);
        $url     = '';
        if (Subsonic_Xml_Data::isSong($fileid)) {
            $object = new Song(Subsonic_Xml_Data::getAmpacheId($fileid));
            $url    = $object->play_url($params, 'api', function_exists('curl_version'), $user_id);
        } elseif (Subsonic_Xml_Data::isPodcastEp($fileid)) {
            $object = new Podcast_Episode(Subsonic_Xml_Data::getAmpacheId($fileid));
            $url    = $object->play_url($params, 'api', function_exists('curl_version'), $user_id);
        }
        // return an error on missing files
        if (empty($url)) {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, '', 'download');
            self::apiOutput($input, $response);

            return;
        }
        self::follow_stream($url);
    }

    /**
     * hls
     * Create an HLS playlist.
     * Takes the file id in parameter with optional max bit rate.
     * @param array $input
     */
    public static function hls($input)
    {
        $fileid = self::check_parameter($input, 'id', true);

        $bitRate = $input['bitRate'];

        $media                = array();
        if (Subsonic_Xml_Data::isSong($fileid)) {
            $media['object_type'] = 'song';
        } elseif (Subsonic_Xml_Data::isVideo($fileid)) {
            $media['object_type'] = 'video';
        } else {
            self::apiOutput(
                $input,
                Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND,
                                               'Invalid id',
                                               'hls'));
        }
        $media['object_id']   = Subsonic_Xml_Data::getAmpacheId($fileid);

        $medias            = array();
        $medias[]          = $media;
        $stream            = new Stream_Playlist();
        $additional_params = '';
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
     * getCoverArt
     * Get a cover art image.
     * Takes the cover art id in parameter.
     * @param array $input
     */
    public static function getcoverart($input)
    {
        $sub_id = str_replace('al-', '', self::check_parameter($input, 'id'));
        $sub_id = str_replace('ar-', '', $sub_id);
        $sub_id = str_replace('pl-', '', $sub_id);
        $sub_id = str_replace('pod-', '', $sub_id);
        // sometimes we're sent a full art url...
        preg_match('/\/artist\/([0-9]*)\//', $sub_id, $matches);
        if (!empty($matches)) {
            $sub_id = (string)(100000000 + (int)$matches[1]);
        }
        $size   = $input['size'];
        $type   = Subsonic_Xml_Data::getAmpacheType($sub_id);
        if ($type == "") {
            self::setHeader($input['f']);
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, "Media not found.", 'getcoverart');
            self::apiOutput($input, $response);

            return;
        }

        $art = null;

        if ($type == 'artist') {
            $art = new Art(Subsonic_Xml_Data::getAmpacheId($sub_id), "artist");
        }
        if ($type == 'album') {
            $art = new Art(Subsonic_Xml_Data::getAmpacheId($sub_id), "album");
        }
        if (($type == 'song')) {
            $art = new Art(Subsonic_Xml_Data::getAmpacheId($sub_id), "song");
            if ($art != null && $art->id == null) {
                // in most cases the song doesn't have a picture, but the album does
                $song          = new Song(Subsonic_Xml_Data::getAmpacheId(Subsonic_Xml_Data::getAmpacheId($sub_id)));
                $show_song_art = AmpConfig::get('show_song_art', false);
                $art_object    = ($show_song_art) ? $song->id : $song->album;
                $art_type      = ($show_song_art) ? 'song' : 'album';
                $art           = new Art($art_object, $art_type);
            }
        }
        if (($type == 'podcast')) {
            $art = new Art(Subsonic_Xml_Data::getAmpacheId($sub_id), "podcast");
        }
        if ($type == 'search' || $type == 'playlist') {
            $listitems = array();
            // playlists and smartlists
            if (($type == 'search')) {
                $playlist  = new Search(Subsonic_Xml_Data::getAmpacheId($sub_id));
                $listitems = $playlist->get_items();
            } elseif (($type == 'playlist')) {
                $playlist  = new Playlist(Subsonic_Xml_Data::getAmpacheId($sub_id));
                $listitems = $playlist->get_items();
            }
            $item = (!empty($listitems)) ? $listitems[array_rand($listitems)] : array();
            $art  = (!empty($item)) ? new Art($item['object_id'], $item['object_type']) : null;
            if ($art != null && $art->id == null) {
                $song = new Song($item['object_id']);
                $art  = new Art($song->album, "album");
            }
        }
        if (!$art || $art->get() == '') {
            self::setHeader($input['f']);
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, "Media not found.", 'getcoverart');
            self::apiOutput($input, $response);

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
        $image = $art->get(true);
        header('Content-type: ' . $art->raw_mime);
        header('Content-Length: ' . strlen((string) $image));
        echo $image;
    }

    /**
     * setRating
     * Sets the rating for a music file.
     * Takes the file id and rating in parameters.
     * @param array $input
     */
    public static function setrating($input)
    {
        $object_id = self::check_parameter($input, 'id');
        $rating    = $input['rating'];

        $robj = null;
        if (Subsonic_Xml_Data::isArtist($object_id)) {
            $robj = new Rating(Subsonic_Xml_Data::getAmpacheId($object_id), "artist");
        } else {
            if (Subsonic_Xml_Data::isAlbum($object_id)) {
                $robj = new Rating(Subsonic_Xml_Data::getAmpacheId($object_id), "album");
            } else {
                if (Subsonic_Xml_Data::isSong($object_id)) {
                    $robj = new Rating(Subsonic_Xml_Data::getAmpacheId($object_id), "song");
                }
            }
        }

        if ($robj != null) {
            $robj->set_rating($rating);

            $response = Subsonic_Xml_Data::createSuccessResponse('setrating');
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, "Media not found.",
                'setrating');
        }

        self::apiOutput($input, $response);
    }

    /**
     * getStarred
     * Get starred songs, albums and artists.
     * Takes no parameter.
     * Not supported.
     * @param array $input
     * @param string $elementName
     */
    public static function getstarred($input, $elementName = "starred")
    {
        $user_id = User::get_from_username($input['u'])->id;

        $response = Subsonic_Xml_Data::createSuccessResponse('getstarred');
        Subsonic_Xml_Data::addStarred($response, Userflag::get_latest('artist', $user_id, 10000),
            Userflag::get_latest('album', $user_id, 10000), Userflag::get_latest('song', $user_id, 10000),
            $elementName);
        self::apiOutput($input, $response);
    }

    /**
     * getStarred2
     * See getStarred.
     * @param array $input
     */
    public static function getstarred2($input)
    {
        self::getStarred($input, "starred2");
    }

    /**
     * star
     * Attaches a star to a song, album or artist.
     * Takes the optional file id, album id or artist id in parameters.
     * Not supported.
     * @param array $input
     */
    public static function star($input)
    {
        self::_setStar($input, true);
    }

    /**
     * unstar
     * Removes the star from a song, album or artist.
     * Takes the optional file id, album id or artist id in parameters.
     * Not supported.
     * @param array $input
     */
    public static function unstar($input)
    {
        self::_setStar($input, false);
    }

    /**
     * @param array $input
     * @param boolean $star
     */
    private static function _setStar($input, $star)
    {
        $object_id = $input['id'];
        $albumId   = $input['albumId'];
        $artistId  = $input['artistId'];

        // Normalize all in one array
        $ids = array();

        $response = Subsonic_Xml_Data::createSuccessResponse('_setStar');
        if ($object_id) {
            if (!is_array($object_id)) {
                $object_id = array($object_id);
            }
            foreach ($object_id as $item) {
                $aid = Subsonic_Xml_Data::getAmpacheId($item);
                if (Subsonic_Xml_Data::isArtist($item)) {
                    $type = 'artist';
                } else {
                    if (Subsonic_Xml_Data::isAlbum($item)) {
                        $type = 'album';
                    } else {
                        if (Subsonic_Xml_Data::isSong($item)) {
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
                    $aid   = Subsonic_Xml_Data::getAmpacheId($album);
                    $ids[] = array('id' => $aid, 'type' => 'album');
                }
            } else {
                if ($artistId) {
                    if (!is_array($artistId)) {
                        $artistId = array($artistId);
                    }
                    foreach ($artistId as $artist) {
                        $aid   = Subsonic_Xml_Data::getAmpacheId($artist);
                        $ids[] = array('id' => $aid, 'type' => 'artist');
                    }
                } else {
                    $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_MISSINGPARAM,
                        'Missing parameter', '_setStar');
                }
            }
        }

        foreach ($ids as $object_id) {
            $flag = new Userflag($object_id['id'], $object_id['type']);
            $flag->set_flag($star);
        }
        self::apiOutput($input, $response);
    }

    /**
     * getUser
     * Get details about a given user.
     * Takes the username in parameter.
     * Not supported.
     * @param array $input
     */
    public static function getuser($input)
    {
        $username = self::check_parameter($input, 'username');
        $myuser   = User::get_from_username($input['u']);

        if ($myuser->access >= 100 || $myuser->username == $username) {
            $response = Subsonic_Xml_Data::createSuccessResponse('getuser');
            if ($myuser->username == $username) {
                $user = $myuser;
            } else {
                $user = User::get_from_username((string)$username);
            }
            Subsonic_Xml_Data::addUser($response, $user);
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED,
                $input['u'] . ' is not authorized to get details for other users.', 'getuser');
        }
        self::apiOutput($input, $response);
    }

    /**
     * getUsers
     * Get details about a given user.
     * Takes no parameter.
     * Not supported.
     * @param array $input
     */
    public static function getusers($input)
    {
        $myuser = User::get_from_username($input['u']);
        if ($myuser->access >= 100) {
            $response = Subsonic_Xml_Data::createSuccessResponse('getusers');
            $users    = static::getUserRepository()->getValid();
            Subsonic_Xml_Data::addUsers($response, $users);
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED,
                $input['u'] . ' is not authorized to get details for other users.', 'getusers');
        }
        self::apiOutput($input, $response);
    }

    /**
     * getAvatar
     * Return the user avatar in bytes.
     * @param array $input
     */
    public static function getavatar($input)
    {
        $username = self::check_parameter($input, 'username');
        $myuser   = User::get_from_username($input['u']);

        $response = null;
        if ($myuser->access >= 100 || $myuser->username == $username) {
            if ($myuser->username == $username) {
                $user = $myuser;
            } else {
                $user = User::get_from_username((string)$username);
            }

            if ($user !== null) {
                // Get Session key
                $avatar = $user->get_avatar(true, $input);
                if (isset($avatar['url']) && !empty($avatar['url'])) {
                    $request = Requests::get($avatar['url'], array(), Core::requests_options());
                    header("Content-Type: " . $request->headers['Content-Type']);
                    echo $request->body;
                }
            } else {
                $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, '', 'getavatar');
            }
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED,
                $input['u'] . ' is not authorized to get avatar for other users.', 'getavatar');
        }

        if ($response != null) {
            self::apiOutput($input, $response);
        }
    }

    /**
     * getInternetRadioStations
     * Get all internet radio stations
     * Takes no parameter.
     * @param array $input
     */
    public static function getinternetradiostations($input)
    {
        $response = Subsonic_Xml_Data::createSuccessResponse('getinternetradiostations');
        $radios   = static::getLiveStreamRepository()->getAll();
        Subsonic_Xml_Data::addRadios($response, $radios);
        self::apiOutput($input, $response);
    }

    /**
     * getShares
     * Get information about shared media this user is allowed to manage.
     * Takes no parameter.
     * @param array $input
     */
    public static function getshares($input)
    {
        $user     = User::get_from_username($input['u']);
        $response = Subsonic_Xml_Data::createSuccessResponse('getshares');
        $shares   = Share::get_share_list($user);
        Subsonic_Xml_Data::addShares($response, $shares);
        self::apiOutput($input, $response);
    }

    /**
     * createShare
     * Create a public url that can be used by anyone to stream media.
     * Takes the file id with optional description and expires parameters.
     * @param array $input
     */
    public static function createshare($input)
    {
        $libitem_id  = self::check_parameter($input, 'id');
        $description = $input['description'];
        if (AmpConfig::get('share')) {
            $expire_days = (isset($input['expires']))
                ? (int) $input['expires']
                : Share::get_expiry($input['expires']);
            $object_type = null;
            if (is_array($libitem_id) && Subsonic_Xml_Data::isSong($libitem_id[0])) {
                $song_id     = Subsonic_Xml_Data::getAmpacheId($libitem_id[0]);
                $tmp_song    = new Song($song_id);
                $object_id   = Subsonic_Xml_Data::getAmpacheId($tmp_song->album);
                $object_type = 'album';
            } else {
                $object_id = Subsonic_Xml_Data::getAmpacheId($libitem_id);
                if (Subsonic_Xml_Data::isAlbum($libitem_id)) {
                    $object_type = 'album';
                }
                if (Subsonic_Xml_Data::isSong($libitem_id)) {
                    $object_type = 'song';
                }
                if (Subsonic_Xml_Data::isPlaylist($libitem_id)) {
                    $object_type = 'playlist';
                }
            }
            debug_event(self::class, 'createShare: sharing ' . $object_type . ' ' . $object_id, 4);

            if (!empty($object_type) && !empty($object_id)) {
                // @todo remove after refactoring
                global $dic;
                $passwordGenerator = $dic->get(PasswordGeneratorInterface::class);

                $response = Subsonic_Xml_Data::createSuccessResponse('createshare');
                $shares   = array();
                $shares[] = Share::create_share(
                    $object_type,
                    $object_id,
                    true,
                    Access::check_function('download'),
                    $expire_days,
                    $passwordGenerator->generate(PasswordGenerator::DEFAULT_LENGTH),
                    0,
                    $description
                );
                Subsonic_Xml_Data::addShares($response, $shares);
            } else {
                $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, '', 'createshare');
            }
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, '', 'createshare');
        }
        self::apiOutput($input, $response);
    }

    /**
     * deleteShare
     * Delete an existing share.
     * Takes the share id to delete in parameters.
     * @param array $input
     */
    public static function deleteshare($input)
    {
        $username = self::check_parameter($input, 'u');
        $user     = User::get_from_username((string)$username);
        $id       = self::check_parameter($input, 'id');
        if (AmpConfig::get('share')) {
            if (Share::delete_share($id, $user)) {
                $response = Subsonic_Xml_Data::createSuccessResponse('deleteshare');
            } else {
                $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, '', 'deleteshare');
            }
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, '', 'deleteshare');
        }
        self::apiOutput($input, $response);
    }

    /**
     * updateShare
     * Update the description and/or expiration date for an existing share.
     * Takes the share id to update with optional description and expires parameters.
     * Not supported.
     * @param array $input
     */
    public static function updateshare($input)
    {
        $username    = self::check_parameter($input, 'u');
        $share_id    = self::check_parameter($input, 'id');
        $user        = User::get_from_username((string)$username);
        $description = $input['description'];

        if (AmpConfig::get('share')) {
            $share = new Share(Subsonic_Xml_Data::getAmpacheId($share_id));
            if ($share->id > 0) {
                $expires = $share->expire_days;
                if (isset($input['expires'])) {
                    // Parse as a string to work on 32-bit computers
                    $expires = $input['expires'];
                    if (strlen((string)$expires) > 3) {
                        $expires = (int)(substr($expires, 0, -3));
                    }
                    if ($expires > 0) {
                        $expires = ($expires - $share->creation_date) / 86400;
                        $expires = ceil($expires);
                    }
                }

                $data = array(
                    'max_counter' => $share->max_counter,
                    'expire' => $expires,
                    'allow_stream' => $share->allow_stream,
                    'allow_download' => $share->allow_download,
                    'description' => $description ?: $share->description,
                );
                if ($share->update($data, $user)) {
                    $response = Subsonic_Xml_Data::createSuccessResponse('updateshare');
                } else {
                    $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, '',
                        'updateshare');
                }
            } else {
                $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, '', 'updateshare');
            }
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, '', 'updateshare');
        }

        self::apiOutput($input, $response);
    }

    /**
     * createUser
     * Create a new user.
     * Takes the username, password and email with optional roles in parameters.
     * @param array $input
     */
    public static function createuser($input)
    {
        $username     = self::check_parameter($input, 'username');
        $password     = self::check_parameter($input, 'password');
        $email        = urldecode((string)self::check_parameter($input, 'email'));
        $adminRole    = ($input['adminRole'] == 'true');
        $downloadRole = ($input['downloadRole'] == 'true');
        $uploadRole   = ($input['uploadRole'] == 'true');
        $coverArtRole = ($input['coverArtRole'] == 'true');
        $shareRole    = ($input['shareRole'] == 'true');
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

        if (Access::check('interface', 100)) {
            $access = 25;
            if ($coverArtRole) {
                $access = 75;
            }
            if ($adminRole) {
                $access = 100;
            }
            $password = self::decrypt_password($password);
            $user_id  = User::create($username, $username, $email, null, $password, $access);
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
                $response = Subsonic_Xml_Data::createSuccessResponse('createuser');
            } else {
                $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, '', 'createuser');
            }
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, '', 'createuser');
        }

        self::apiOutput($input, $response);
    }

    /**
     * updateUser
     * Update an existing user.
     * Takes the username with optional parameters.
     * @param array $input
     */
    public static function updateuser($input)
    {
        $username = self::check_parameter($input, 'username');
        $password = $input['password'];
        $email    = urldecode($input['email']);
        //$ldapAuthenticated = $input['ldapAuthenticated'];
        $adminRole    = ($input['adminRole'] == 'true');
        $downloadRole = ($input['downloadRole'] == 'true');
        $uploadRole   = ($input['uploadRole'] == 'true');
        $coverArtRole = ($input['coverArtRole'] == 'true');
        $shareRole    = ($input['shareRole'] == 'true');
        //$musicfolderid = $input['musicFolderId'];
        $maxbitrate = $input['maxBitRate'];

        if (Access::check('interface', 100)) {
            $access = 25;
            if ($coverArtRole) {
                $access = 75;
            }
            if ($adminRole) {
                $access = 100;
            }
            // identify the user to modify
            $user    = User::get_from_username((string)$username);
            $user_id = $user->id;

            if ($user_id > 0) {
                // update access level
                $user->update_access($access);
                // update password
                if ($password && !AmpConfig::get('simple_user_mode')) {
                    $password = self::decrypt_password($password);
                    $user->update_password($password);
                }
                // update e-mail
                if (Mailer::validate_address($email)) {
                    $user->update_email($email);
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
                if ((int)$maxbitrate > 0) {
                    Preference::update('transcode_bitrate', $user_id, $maxbitrate);
                }
                $response = Subsonic_Xml_Data::createSuccessResponse('updateuser');
            } else {
                $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, '', 'updateuser');
            }
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, '', 'updateuser');
        }

        self::apiOutput($input, $response);
    }

    /**
     * deleteUser
     * Delete an existing user.
     * Takes the username in parameter.
     * @param array $input
     */
    public static function deleteuser($input)
    {
        $username = self::check_parameter($input, 'username');
        if (Access::check('interface', 100)) {
            $user = User::get_from_username((string)$username);
            if ($user->id) {
                $user->delete();
                $response = Subsonic_Xml_Data::createSuccessResponse('deleteuser');
            } else {
                $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, '', 'deleteuser');
            }
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, '', 'deleteuser');
        }

        self::apiOutput($input, $response);
    }

    /**
     * change password
     * Change the password of an existing user.
     * Takes the username with new password in parameters.
     * @param array $input
     */
    public static function changepassword($input)
    {
        $username = self::check_parameter($input, 'username');
        $inp_pass = self::check_parameter($input, 'password');
        $password = self::decrypt_password($inp_pass);
        $myuser   = User::get_from_username($input['u']);

        if ($myuser->username == $username || Access::check('interface', 100)) {
            $user = User::get_from_username((string) $username);
            if ($user->id && !AmpConfig::get('simple_user_mode')) {
                $user->update_password($password);
                $response = Subsonic_Xml_Data::createSuccessResponse('changepassword');
            } else {
                $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, '',
                    'changepassword');
            }
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, '', 'changepassword');
        }
        self::apiOutput($input, $response);
    }

    /**
     * jukeboxControl
     * Control the jukebox.
     * Takes the action with optional index, offset, song id and volume gain in parameters.
     * Not supported.
     * @param array $input
     */
    public static function jukeboxcontrol($input)
    {
        $action = self::check_parameter($input, 'action');
        $id     = $input['id'];
        $gain   = $input['gain'];

        $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, '', 'jukeboxcontrol');
        debug_event(__CLASS__, 'Using Localplay controller: ' . AmpConfig::get('localplay_controller'), 5);
        $localplay = new LocalPlay(AmpConfig::get('localplay_controller'));

        if ($localplay->connect()) {
            $ret = false;
            switch ($_REQUEST['action']) {
                case 'get':
                case 'status':
                    $ret = true;
                    break;
                case 'start':
                    $ret = $localplay->play();
                    break;
                case 'stop':
                    $ret = $localplay->stop();
                    break;
                case 'skip':
                    if (isset($input['index'])) {
                        if ($localplay->skip($input['index'])) {
                            $ret = $localplay->play();
                        }
                    } elseif (isset($input['offset'])) {
                        debug_event(self::class, 'Skip with offset is not supported on JukeboxControl.', 5);
                    } else {
                        $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_MISSINGPARAM, '',
                            'jukeboxcontrol');
                    }
                    break;
                case 'set':
                    $localplay->delete_all();
                    // Intentional break fall-through
                case 'add':
                    $user = User::get_from_username($input['u']);
                    if ($id) {
                        if (!is_array($id)) {
                            $rid   = array();
                            $rid[] = $id;
                            $id    = $rid;
                        }

                        foreach ($id as $song_id) {
                            $url = null;

                            if (Subsonic_Xml_Data::isSong($song_id)) {
                                $media = new Song(Subsonic_Xml_Data::getAmpacheId($song_id));
                                $url   = $media->play_url('', 'api', function_exists('curl_version'), $user->id);
                            }

                            if ($url !== null) {
                                debug_event(self::class, 'Adding ' . $url, 5);
                                $stream        = array();
                                $stream['url'] = $url;
                                $ret           = $localplay->add_url(new Stream_Url($stream));
                            }
                        }
                    }
                    break;
                case 'clear':
                    $ret = $localplay->delete_all();
                    break;
                case 'remove':
                    if (isset($input['index'])) {
                        $ret = $localplay->delete_track($input['index']);
                    } else {
                        $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_MISSINGPARAM, '',
                            'jukeboxcontrol');
                    }
                    break;
                case 'shuffle':
                    $ret = $localplay->random(true);
                    break;
                case 'setGain':
                    $ret = $localplay->volume_set($gain * 100);
                    break;
            }

            if ($ret) {
                $response = Subsonic_Xml_Data::createSuccessResponse('jukeboxcontrol');
                if ($action == 'get') {
                    Subsonic_Xml_Data::addJukeboxPlaylist($response, $localplay);
                } else {
                    Subsonic_Xml_Data::createJukeboxStatus($response, $localplay);
                }
            }
        }

        self::apiOutput($input, $response);
    }

    /**
     * scrobble
     * Scrobbles a given music file on last.fm.
     * Takes the file id with optional time and submission parameters.
     * @param array $input
     */
    public static function scrobble($input)
    {
        $object_ids = self::check_parameter($input, 'id');
        $submission = ($input['submission'] === 'true' || $input['submission'] === '1');
        $user       = User::get_from_username($input['u']);
        $client     = (string) $input['c'];

        if (!is_array($object_ids)) {
            $rid        = array();
            $rid[]      = $object_ids;
            $object_ids = $rid;
        }
        $get_time = User::get_user_data($user->id, 'playqueue_time');
        $now_time = time();
        // don't scrobble after setting the play queue too quickly
        if ($get_time < $now_time - 2) {
            foreach ($object_ids as $subsonic_id) {
                $time     = isset($input['time']) ? (int)$input['time'] / 1000 : $now_time;
                $previous = Stats::get_last_play($user->id, $client, $time);
                $media    = Subsonic_Xml_Data::getAmpacheObject($subsonic_id);
                $media->format();

                // submission is true: go to scrobble plugins (Plugin::get_plugins('save_mediaplay'))
                if ($submission && get_class($media) == Song::class && ($previous['object_id'] != $media->id) && (($time - $previous['time']) > 5)) {
                    // stream has finished
                    debug_event(self::class, $user->username . ' scrobbled: {' . $media->id . '} at ' . $time, 5);
                    User::save_mediaplay($user, $media);
                }
                // Submission is false and not a repeat. let repeats go though to saveplayqueue
                if ((!$submission) && $media->id && ($previous['object_id'] != $media->id) && (($time - $previous['time']) > 5)) {
                    $media->set_played($user->id, $client, array(), $time);
                }
            }
        }

        $response = Subsonic_Xml_Data::createSuccessResponse('scrobble');
        self::apiOutput($input, $response);
    }

    /**
     * getLyrics
     * Searches and returns lyrics for a given song.
     * Takes the optional artist and title in parameters.
     * @param array $input
     */
    public static function getlyrics($input)
    {
        $artist = $input['artist'];
        $title  = $input['title'];

        if (!$artist && !$title) {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_MISSINGPARAM, '', 'getlyrics');
        } else {
            $search           = array();
            $search['limit']  = 1;
            $search['offset'] = 0;
            $search['type']   = "song";

            $count = 0;
            if ($artist) {
                $search['rule_' . $count . '_input']    = $artist;
                $search['rule_' . $count . '_operator'] = 4;
                $search['rule_' . $count . '']          = "artist";
                ++$count;
            }
            if ($title) {
                $search['rule_' . $count . '_input']    = $title;
                $search['rule_' . $count . '_operator'] = 4;
                $search['rule_' . $count . '']          = "title";
            }

            $songs    = Search::run($search);
            $response = Subsonic_Xml_Data::createSuccessResponse('getlyrics');
            if (count($songs) > 0) {
                Subsonic_Xml_Data::addLyrics($response, $artist, $title, $songs[0]);
            }
        }

        self::apiOutput($input, $response);
    }

    /**
     * getArtistInfo
     * Returns artist info with biography, image URLs and similar artists, using data from last.fm.
     * Takes artist id in parameter with optional similar artist count and if not present similar artist should be returned.
     * @param array $input
     * @param string $child
     */
    public static function getartistinfo($input, $child = "artistInfo")
    {
        $id                = self::check_parameter($input, 'id');
        $count             = $input['count'] ?: 20;
        $includeNotPresent = ($input['includeNotPresent'] === "true");

        if (Subsonic_Xml_Data::isArtist($id)) {
            $artist_id = Subsonic_Xml_Data::getAmpacheId($id);
            $info      = Recommendation::get_artist_info($artist_id);
            $similars  = Recommendation::get_artists_like($artist_id, $count, !$includeNotPresent);
            $response  = Subsonic_Xml_Data::createSuccessResponse('getartistinfo');
            Subsonic_Xml_Data::addArtistInfo($response, $info, $similars, $child);
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, '', 'getartistinfo');
        }

        self::apiOutput($input, $response);
    }

    /**
     * getArtistInfo2
     * See getArtistInfo.
     * @param array $input
     */
    public static function getartistinfo2($input)
    {
        self::getartistinfo($input, 'artistInfo2');
    }

    /**
     * getSimilarSongs
     * Returns a random collection of songs from the given artist and similar artists, using data from last.fm. Typically used for artist radio features.
     * Takes song/album/artist id in parameter with optional similar songs count.
     * @param array $input
     * @param string $child
     */
    public static function getsimilarsongs($input, $child = "similarSongs")
    {
        if (!AmpConfig::get('show_similar')) {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND,
                "Show similar must be enabled", 'getsimilarsongs');
            self::apiOutput($input, $response);

            return;
        }

        $id    = self::check_parameter($input, 'id');
        $count = $input['count'] ?: 50;

        $songs = array();
        if (Subsonic_Xml_Data::isArtist($id)) {
            $similars = Recommendation::get_artists_like(Subsonic_Xml_Data::getAmpacheId($id));
            if (!empty($similars)) {
                debug_event(self::class, 'Found: ' . count($similars) . ' similar artists', 4);
                foreach ($similars as $similar) {
                    debug_event(self::class, $similar['name'] . ' (id=' . $similar['id'] . ')', 5);
                    if ($similar['id']) {
                        $artist = new Artist($similar['id']);
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
        //} elseif (Ampache\Module\Api\Subsonic_Xml_Data::isAlbum($id)) {
            //    // TODO: support similar songs for albums
        } elseif (Subsonic_Xml_Data::isSong($id)) {
            $songs = Recommendation::get_songs_like(Subsonic_Xml_Data::getAmpacheId($id), $count);
        }

        if (count($songs) == 0) {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, '', 'getsimilarsongs');
        } else {
            $response = Subsonic_Xml_Data::createSuccessResponse('getsimilarsongs');
            Subsonic_Xml_Data::addSimilarSongs($response, $songs, $child);
        }

        self::apiOutput($input, $response);
    }

    /**
     * getSimilarSongs2
     * See getSimilarSongs.
     * @param array $input
     */
    public static function getsimilarsongs2($input)
    {
        self::getsimilarsongs($input, "similarSongs2");
    }

    /**
     * getPodcasts
     * Get all podcast channels.
     * Takes the optional includeEpisodes and channel id in parameters
     * @param array $input
     */
    public static function getpodcasts($input)
    {
        $podcast_id      = $input['id'];
        $includeEpisodes = !isset($input['includeEpisodes']) || $input['includeEpisodes'] === "true";

        if (AmpConfig::get('podcast')) {
            if ($podcast_id) {
                $podcast = new Podcast(Subsonic_Xml_Data::getAmpacheId($podcast_id));
                if ($podcast->id) {
                    $response = Subsonic_Xml_Data::createSuccessResponse('getpodcasts');
                    Subsonic_Xml_Data::addPodcasts($response, array($podcast), $includeEpisodes);
                } else {
                    $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, '',
                        'getpodcasts');
                }
            } else {
                $podcasts = Catalog::get_podcasts();
                $response = Subsonic_Xml_Data::createSuccessResponse('getpodcasts');
                Subsonic_Xml_Data::addPodcasts($response, $podcasts, $includeEpisodes);
            }
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, '', 'getpodcasts');
        }
        self::apiOutput($input, $response);
    }

    /**
     * getNewestPodcasts
     * Get the most recently published podcast episodes.
     * Takes the optional count in parameters
     * @param array $input
     */
    public static function getnewestpodcasts($input)
    {
        $count = $input['count'] ?: AmpConfig::get('podcast_new_download');

        if (AmpConfig::get('podcast')) {
            $response = Subsonic_Xml_Data::createSuccessResponse('getnewestpodcasts');
            $episodes = Catalog::get_newest_podcasts($count);
            Subsonic_Xml_Data::addNewestPodcastEpisodes($response, $episodes);
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, '',
                'getnewestpodcasts');
        }
        self::apiOutput($input, $response);
    }

    /**
     * refreshPodcasts
     * Request the server to check for new podcast episodes.
     * Takes no parameters.
     * @param array $input
     */
    public static function refreshpodcasts($input)
    {
        if (AmpConfig::get('podcast') && Access::check('interface', 75)) {
            $podcasts = Catalog::get_podcasts();
            foreach ($podcasts as $podcast) {
                $podcast->sync_episodes(true);
            }
            $response = Subsonic_Xml_Data::createSuccessResponse('refreshpodcasts');
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, '', 'refreshpodcasts');
        }
        self::apiOutput($input, $response);
    }

    /**
     * createPodcastChannel
     * Add a new podcast channel.
     * Takes the podcast url in parameter.
     * @param array $input
     */
    public static function createpodcastchannel($input)
    {
        $url      = self::check_parameter($input, 'url');
        $username = self::check_parameter($input, 'u');
        $user     = User::get_from_username((string)$username);

        if (AmpConfig::get('podcast') && Access::check('interface', 75)) {
            $catalogs = Catalog::get_catalogs('podcast', $user->id);
            if (count($catalogs) > 0) {
                $data            = array();
                $data['feed']    = $url;
                $data['catalog'] = $catalogs[0];
                if (Podcast::create($data)) {
                    $response = Subsonic_Xml_Data::createSuccessResponse('createpodcastchannel');
                } else {
                    $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_GENERIC, '',
                        'createpodcastchannel');
                }
            } else {
                $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, '',
                    'createpodcastchannel');
            }
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, '',
                'createpodcastchannel');
        }
        self::apiOutput($input, $response);
    }

    /**
     * deletePodcastChannel
     * Delete an existing podcast channel
     * Takes the podcast id in parameter.
     * @param array $input
     */
    public static function deletepodcastchannel($input)
    {
        $podcast_id = (int)self::check_parameter($input, 'id');

        if (AmpConfig::get('podcast') && Access::check('interface', 75)) {
            $podcast = new Podcast(Subsonic_Xml_Data::getAmpacheId($podcast_id));
            if ($podcast->id) {
                if ($podcast->remove()) {
                    $response = Subsonic_Xml_Data::createSuccessResponse('deletepodcastchannel');
                } else {
                    $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_GENERIC, '',
                        'deletepodcastchannel');
                }
            } else {
                $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, '',
                    'deletepodcastchannel');
            }
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, '',
                'deletepodcastchannel');
        }
        self::apiOutput($input, $response);
    }

    /**
     * deletePodcastEpisode
     * Delete a podcast episode
     * Takes the podcast episode id in parameter.
     * @param array $input
     */
    public static function deletepodcastepisode($input)
    {
        $id = self::check_parameter($input, 'id');

        if (AmpConfig::get('podcast') && Access::check('interface', 75)) {
            $episode = new Podcast_Episode(Subsonic_Xml_Data::getAmpacheId($id));
            if ($episode->id !== null) {
                if ($episode->remove()) {
                    $response = Subsonic_Xml_Data::createSuccessResponse('deletepodcastepisode');
                } else {
                    $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_GENERIC, '',
                        'deletepodcastepisode');
                }
            } else {
                $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, '',
                    'deletepodcastepisode');
            }
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, '',
                'deletepodcastepisode');
        }
        self::apiOutput($input, $response);
    }

    /**
     * downloadPodcastEpisode
     * Request the server to download a podcast episode
     * Takes the podcast episode id in parameter.
     * @param array $input
     */
    public static function downloadpodcastepisode($input)
    {
        $id = self::check_parameter($input, 'id');

        if (AmpConfig::get('podcast') && Access::check('interface', 75)) {
            $episode = new Podcast_Episode(Subsonic_Xml_Data::getAmpacheId($id));
            if ($episode->id !== null) {
                $episode->gather();
                $response = Subsonic_Xml_Data::createSuccessResponse('downloadpodcastepisode');
            } else {
                $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, '',
                    'downloadpodcastepisode');
            }
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_UNAUTHORIZED, '',
                'downloadpodcastepisode');
        }
        self::apiOutput($input, $response);
    }

    /**
     * getBookmarks
     * Get all user bookmarks.
     * Takes no parameter.
     * Not supported.
     * @param array $input
     */
    public static function getbookmarks($input)
    {
        $user_id   = User::get_from_username($input['u'])->getId();
        $response  = Subsonic_Xml_Data::createSuccessResponse('getbookmarks');
        $bookmarks = [];

        foreach (static::getBookmarkRepository()->getBookmarks($user_id) as $bookmarkId) {
            $bookmarks[] = new Bookmark($bookmarkId);
        }

        Subsonic_Xml_Data::addBookmarks($response, $bookmarks);
        self::apiOutput($input, $response);
    }

    /**
     * createBookmark
     * Creates or updates a bookmark.
     * Takes the file id and position with optional comment in parameters.
     * Not supported.
     * @param array $input
     */
    public static function createbookmark($input)
    {
        $object_id = self::check_parameter($input, 'id');
        $position  = self::check_parameter($input, 'position');
        $comment   = $input['comment'];
        $type      = Subsonic_Xml_Data::getAmpacheType($object_id);

        if (!empty($type)) {
            $bookmark = new Bookmark(Subsonic_Xml_Data::getAmpacheId($object_id), $type);
            if ($bookmark->id) {
                static::getBookmarkRepository()->update($bookmark->getId(), (int) $position);
            } else {
                Bookmark::create(
                    [
                        'object_id' => Subsonic_Xml_Data::getAmpacheId($object_id),
                        'object_type' => $type,
                        'comment' => $comment,
                        'position' => $position
                    ],
                    Core::get_global('user')->id,
                    time()
                );
            }
            $response = Subsonic_Xml_Data::createSuccessResponse('createbookmark');
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, '', 'createbookmark');
        }
        self::apiOutput($input, $response);
    }

    /**
     * deleteBookmark
     * Delete an existing bookmark.
     * Takes the file id in parameter.
     * Not supported.
     * @param array $input
     */
    public static function deletebookmark($input)
    {
        $id   = self::check_parameter($input, 'id');
        $type = Subsonic_Xml_Data::getAmpacheType($id);

        $bookmark = new Bookmark(Subsonic_Xml_Data::getAmpacheId($id), $type);
        if ($bookmark->id) {
            static::getBookmarkRepository()->delete($bookmark->getId());
            $response = Subsonic_Xml_Data::createSuccessResponse('deletebookmark');
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, '', 'deletebookmark');
        }
        self::apiOutput($input, $response);
    }

    /**
     * getChatMessages
     * Get the current chat messages.
     * Takes no parameter.
     * Not supported.
     * @param array $input
     */
    public static function getchatmessages($input)
    {
        $since                    = (int) $input['since'];
        $privateMessageRepository = static::getPrivateMessageRepository();

        $privateMessageRepository->cleanChatMessages();

        $messages = $privateMessageRepository->getChatMessages($since);

        $response = Subsonic_Xml_Data::createSuccessResponse('getchatmessages');
        Subsonic_Xml_Data::addMessages($response, $messages);
        self::apiOutput($input, $response);
    }

    /**
     * addChatMessages
     * Add a message to the chat.
     * Takes the message in parameter.
     * Not supported.
     * @param array $input
     */
    public static function addchatmessage($input)
    {
        $message = self::check_parameter($input, 'message');

        $message = trim(
            strip_tags(
                filter_var(
                    $message,
                    FILTER_SANITIZE_STRING,
                    FILTER_FLAG_NO_ENCODE_QUOTES
                )
            )
        );

        $user_id = User::get_from_username($input['u'])->getId();
        if (static::getPrivateMessageRepository()->sendChatMessage($message, $user_id) !== null) {
            $response = Subsonic_Xml_Data::createSuccessResponse('addchatmessage');
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, '', 'addChatMessage');
        }
        self::apiOutput($input, $response);
    }

    /**
     * savePlayQueue
     * Save the state of the play queue for the authenticated user.
     * Takes multiple song id in parameter with optional current id playing song and position.
     * @param array $input
     */
    public static function saveplayqueue($input)
    {
        $current = (int)$input['current'];
        $media   = Subsonic_Xml_Data::getAmpacheObject($current);
        if ($media->id) {
            $response = Subsonic_Xml_Data::createSuccessResponse('saveplayqueue');
            $position = (int) $input['position'] / 1000;
            $username = (string) $input['u'];
            $client   = (string) $input['c'];
            $user_id  = User::get_from_username($username)->id;
            $get_time = User::get_user_data($user_id, 'playqueue_time');
            $time     = time();
            // wait a few seconds before smashing out play times
            if ($get_time < $time - 2) {
                $previous = Stats::get_last_play($user_id, $client);
                $type     = Subsonic_Xml_Data::getAmpacheType($current);
                // long pauses might cause your now_playing to hide
                Stream::garbage_collection();
                Stream::insert_now_playing((int) $media->id, (int) $user_id, ((int)$media->time - $position), $username, $type, ((int)$time - $position));
                // track has just started. repeated plays aren't called by scrobble so make sure we call this too
                if ($position < 1 && $previous['object_id'] == $media->id && ($time - $previous['date']) > 5) {
                    $media->set_played((int) $user_id, $client, array(), $time);
                }
                // paused or played after 5 seconds so shift the start time
                if ($position > 5 && $previous['object_id'] == $media->id) {
                    Stats::shift_last_play($user_id, $client, $previous['date'], ($time - $position));
                }
                $playQueue = new User_Playlist($user_id);
                $sub_ids   = (is_array($input['id']))
                    ? $input['id']
                    : array($input['id']);
                $playlist  = Subsonic_Xml_Data::getAmpacheIdArrays($sub_ids);
                $playQueue->set_items($playlist, $type, $media->id, $position, $time, $client);
            }
        } else {
            $response = Subsonic_Xml_Data::createError(Subsonic_Xml_Data::SSERROR_DATA_NOTFOUND, '', 'saveplayqueue');
        }

        self::apiOutput($input, $response);
    }

    /**
     * getPlayQueue
     * Returns the state of the play queue for the authenticated user.
     * Takes no parameter.
     * Not supported.
     * @param array $input
     */
    public static function getplayqueue($input)
    {
        $username = (string) $input['u'];
        $user_id  = User::get_from_username($username)->id;
        $response = Subsonic_Xml_Data::createSuccessResponse('getplayqueue');
        User::set_user_data($user_id, 'playqueue_time', time());

        Subsonic_Xml_Data::addPlayQueue($response, $user_id, $username);
        self::apiOutput($input, $response);
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
}
