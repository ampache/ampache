<?php

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2019 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

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
     * constructor
     * This really isn't anything to do here, so it's private
     */
    private function __construct()
    {
    }

    /**
     * check_parameter
     * @param string $parameter
     */
    public static function check_parameter($input, $parameter, $addheader = false)
    {
        if (empty($input[$parameter])) {
            ob_end_clean();
            if ($addheader) {
                self::setHeader($input['f']);
            }
            self::apiOutput($input, Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_MISSINGPARAM, '', 'check_parameter'));

            return false;
        }

        return $input[$parameter];
    }

    public static function decrypt_password($password)
    {
        // Decode hex-encoded password
        $encpwd = strpos($password, "enc:");
        if ($encpwd !== false) {
            $hex    = substr($password, 4);
            $decpwd = '';
            for ($count = 0; $count < strlen($hex); $count += 2) {
                $decpwd .= chr((int) hexdec(substr($hex, $count, 2)));
            }
            $password = $decpwd;
        }

        return $password;
    }

    public static function output_body($curl, $data)
    {
        unset($curl);
        echo $data;
        ob_flush();

        return strlen($data);
    }

    public static function output_header($curl, $header)
    {
        $rheader = trim($header);
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

        return strlen($header);
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
            debug_event('subsonic_api.class', 'Stream proxy: ' . $url, 5);
            $curl = curl_init($url);
            curl_setopt_array($curl, array(
                CURLOPT_FAILONERROR => true,
                CURLOPT_HTTPHEADER => $reqheaders,
                CURLOPT_HEADER => false,
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_WRITEFUNCTION => array('Subsonic_Api', 'output_body'),
                CURLOPT_HEADERFUNCTION => array('Subsonic_Api', 'output_header'),
                // Ignore invalid certificate
                // Default trusted chain is crap anyway and currently no custom CA option
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => 0
            ));
            if (curl_exec($curl) === false) {
                debug_event('subsonic_api.class', 'Stream error: ' . curl_error($curl), 1);
            }
            curl_close($curl);
        } else {
            // Stream media using http redirect if no curl support
            // Bug fix for android clients looking for /rest/ in destination url
            // Warning: external catalogs will not work!
            $url = str_replace('/play/', '/rest/fake/', $url);
            header("Location: " . $url);
        }
    }

    public static function setHeader($filetype)
    {
        if (strtolower($filetype) == "json") {
            header("Content-type: application/json; charset=" . AmpConfig::get('site_charset'));
            Subsonic_XML_Data::$enable_json_checks = true;
        } elseif (strtolower($filetype) == "jsonp") {
            header("Content-type: text/javascript; charset=" . AmpConfig::get('site_charset'));
            Subsonic_XML_Data::$enable_json_checks = true;
        } else {
            header("Content-type: text/xml; charset=" . AmpConfig::get('site_charset'));
        }
        header("Access-Control-Allow-Origin: *");
    }

    /**
     * apiOutput
     * @param SimpleXMLElement $xml
     */
    public static function apiOutput($input, $xml, $alwaysArray = array('musicFolder', 'channel', 'artist', 'child', 'playlist', 'song', 'album', 'share'))
    {
        $type     = $input['f'];
        $callback = $input['callback'];
        self::apiOutput2(strtolower($type), $xml, $callback, $alwaysArray);
    }

    /**
     * apiOutput2
     * @param SimpleXMLElement $xml
     * @param string $outputtype
     */
    public static function apiOutput2($outputtype, $xml, $callback = '', $alwaysArray = array('musicFolder', 'channel', 'artist', 'child', 'playlist', 'song', 'album', 'share'))
    {
        $conf = array('alwaysArray' => $alwaysArray);
        if ($outputtype == "json") {
            $output = json_encode(self::xml2json($xml, $conf), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
        } else {
            if ($outputtype == "jsonp") {
                $output = $callback . '(' . json_encode(self::xml2json($xml, $conf), JSON_PRETTY_PRINT) . ')';
            } else {
                $xmlstr = $xml->asXml();
                //clean illegal XML characters.
                $clean_xml = preg_replace('/[^\x{0009}\x{000a}\x{000d}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}]+/u', '_', $xmlstr);               // Format xml output
                $dom       = new DOMDocument();
                $dom->loadXML($clean_xml, LIBXML_PARSEHUGE);
                $dom->formatOutput = true;
                $output            = $dom->saveXML();
            }
        }

        echo $output;
    }

    /**
     * xml2json
     * [based from http://outlandish.com/blog/xml-to-json/]
     * Because we cannot use only json_encode to respect JSON Subsonic API
     * @param SimpleXMLElement $xml
     */
    private static function xml2json($xml, $input_options = array())
    {
        $defaults = array(
            'namespaceSeparator' => ' :', //you may want this to be something other than a colon
            'attributePrefix' => '', //to distinguish between attributes and nodes with the same name
            'alwaysArray' => array('musicFolder', 'channel', 'artist', 'child', 'playlist', 'song', 'album', 'share'), //array of xml tag names which should always become arrays
            'autoArray' => true, //only create arrays for tags which appear more than once
            'textContent' => 'value', //key used for the text content of elements
            'autoText' => true, //skip textContent key if node has no attributes or child nodes
            'keySearch' => false, //optional search and replace on tag and attribute names
            'keyReplace' => false, //replace values for above search values (as passed to str_replace())
            'boolean' => true           //replace true and false string with boolean values
        );
        $options        = array_merge($defaults, $input_options);
        $namespaces     = $xml->getDocNamespaces();
        $namespaces[''] = null; //add base (empty) namespace
        //get attributes from all namespaces
        $attributesArray = array();
        foreach ($namespaces as $prefix => $namespace) {
            foreach ($xml->attributes($namespace) as $attributeName => $attribute) {
                //replace characters in attribute name
                if ($options['keySearch']) {
                    $attributeName = str_replace($options['keySearch'], $options['keyReplace'], $attributeName);
                }
                $attributeKey = $options['attributePrefix']
                        . ($prefix ? $prefix . $options['namespaceSeparator'] : '')
                        . $attributeName;
                $strattr = (string) $attribute;
                if ($options['boolean'] && ($strattr == "true" || $strattr == "false")) {
                    $vattr = ($strattr == "true");
                } else {
                    $vattr = $strattr;
                }
                $attributesArray[$attributeKey] = $vattr;
            }
        }

        // these children must be in an array.
        $forceArray = array('channel', 'share');
        //get child nodes from all namespaces
        $tagsArray = array();
        foreach ($namespaces as $prefix => $namespace) {
            foreach ($xml->children($namespace) as $childXml) {
                //recurse into child nodes
                $childArray = self::xml2json($childXml, $options);
                foreach ($childArray as $childTagName => $childProperties) {
                    //replace characters in tag name
                    if ($options['keySearch']) {
                        $childTagName = str_replace($options['keySearch'], $options['keyReplace'], $childTagName);
                    }
                    //add namespace prefix, if any
                    if ($prefix) {
                        $childTagName = $prefix . $options['namespaceSeparator'] . $childTagName;
                    }

                    if (!isset($tagsArray[$childTagName])) {
                        //only entry with this key
                        if (count($childProperties) === 0) {
                            $tagsArray[$childTagName] = (object) $childProperties;
                        } elseif (self::has_Nested_Array($childProperties) && !in_array($childTagName, $forceArray)) {
                            $tagsArray[$childTagName] = (object) $childProperties;
                        } else {

                            //test if tags of this type should always be arrays, no matter the element count
                            $tagsArray[$childTagName] = in_array($childTagName, $options['alwaysArray']) || !$options['autoArray'] ? array($childProperties) : $childProperties;
                        }
                    } elseif (
                            is_array($tagsArray[$childTagName]) && array_keys($tagsArray[$childTagName]) === range(0, count($tagsArray[$childTagName]) - 1)
                    ) {
                        //key already exists and is integer indexed array
                        $tagsArray[$childTagName][] = $childProperties;
                    } else {
                        //key exists so convert to integer indexed array with previous value in position 0
                        $tagsArray[$childTagName] = array($tagsArray[$childTagName], $childProperties);
                    }
                }
            } //REPLACING list($childTagName, $childProperties) = each($childArray);
        }

        //get text content of node
        $textContentArray = array();
        $plainText        = (string) $xml;
        if ($plainText !== '') {
            $textContentArray[$options['textContent']] = $plainText;
        }

        //stick it all together
        $propertiesArray = !$options['autoText'] || ! empty($attributesArray) || ! empty($tagsArray) || ($plainText === '') ? array_merge($attributesArray, $tagsArray, $textContentArray) : $plainText;

        if (isset($propertiesArray['xmlns'])) {
            unset($propertiesArray['xmlns']);
        }

        //return node as array
        return array(
            $xml->getName() => $propertiesArray
        );
    }

    /**
     * has_Nested_Array
     * Used for xml2json to detect a sub-array
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
     */
    public static function ping($input)
    {
        // Don't check client API version here. Some client give version 0.0.0 for ping command

        self::apiOutput($input, Subsonic_XML_Data::createSuccessResponse('ping'));
    }

    /**
     * getLicense
     * Get details about the software license. Always return a valid default license.
     * Takes no parameter.
     */
    public static function getlicense($input)
    {
        $response = Subsonic_XML_Data::createSuccessResponse('getlicense');
        Subsonic_XML_Data::addLicense($response);
        self::apiOutput($input, $response);
    }

    /**
     * getMusicFolders
     * Get all configured top-level music folders (= Ampache catalogs).
     * Takes no parameter.
     */
    public static function getmusicfolders($input)
    {
        $response = Subsonic_XML_Data::createSuccessResponse('getmusicfolders');
        Subsonic_XML_Data::addMusicFolders($response, Catalog::get_catalogs());
        self::apiOutput($input, $response);
    }

    /**
     * getIndexes
     * Get an indexed structure of all artists.
     * Takes optional musicFolderId and optional ifModifiedSince in parameters.
     */
    public static function getindexes($input)
    {
        set_time_limit(300);

        $musicFolderId   = $input['musicFolderId'];
        $ifModifiedSince = $input['ifModifiedSince'];

        $catalogs = array();
        if (!empty($musicFolderId) && $musicFolderId != '-1') {
            $catalogs[] = $musicFolderId;
        } else {
            $catalogs = Catalog::get_catalogs();
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

        $response = Subsonic_XML_Data::createSuccessResponse('getindexes');
        if (count($fcatalogs) > 0) {
            $artists = Catalog::get_artists($fcatalogs);
            Subsonic_XML_Data::addArtistsIndexes($response, $artists, $lastmodified);
        }
        self::apiOutput($input, $response);
    }

    /**
     * getMusicDirectory
     * Get a list of all files in a music directory.
     * Takes the directory id in parameters.
     */
    public static function getmusicdirectory($input)
    {
        $id = self::check_parameter($input, 'id');

        $response = Subsonic_XML_Data::createSuccessResponse('getmusicdirectory');
        if (Subsonic_XML_Data::isArtist($id)) {
            $artist = new Artist(Subsonic_XML_Data::getAmpacheId($id));
            Subsonic_XML_Data::addArtistDirectory($response, $artist);
        } else {
            if (Subsonic_XML_Data::isAlbum($id)) {
                $album = new Album(Subsonic_XML_Data::getAmpacheId($id));
                Subsonic_XML_Data::addAlbumDirectory($response, $album);
            }
        }
        self::apiOutput($input, $response);
    }

    /**
     * getGenres
     * Get all genres.
     * Takes no parameter.
     */
    public static function getgenres($input)
    {
        $response = Subsonic_XML_Data::createSuccessResponse('getgenres');
        Subsonic_XML_Data::addGenres($response, Tag::get_tags('song'));
        self::apiOutput($input, $response);
    }

    /**
     * getArtists
     * Get all artists.
     * Takes no parameter.
     */
    public static function getartists($input)
    {
        $response = Subsonic_XML_Data::createSuccessResponse('getartists');
        $artists  = Catalog::get_artists(Catalog::get_catalogs());
        Subsonic_XML_Data::addArtistsRoot($response, $artists, true);
        self::apiOutput($input, $response);
    }

    /**
     * getArtist
     * Get details for an artist, including a list of albums.
     * Takes the artist id in parameter.
     */
    public static function getartist($input)
    {
        $artistid = self::check_parameter($input, 'id');

        $artist = new Artist(Subsonic_XML_Data::getAmpacheId($artistid));
        if (empty($artist->name)) {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, "Artist not found.", 'getartist');
        } else {
            $response = Subsonic_XML_Data::createSuccessResponse('getartist');
            Subsonic_XML_Data::addArtist($response, $artist, true, true);
        }
        self::apiOutput($input, $response);
    }

    /**
     * getAlbum
     * Get details for an album, including a list of songs.
     * Takes the album id in parameter.
     */
    public static function getalbum($input)
    {
        $albumid = self::check_parameter($input, 'id');

        $addAmpacheInfo = ($input['ampache'] == "1");

        $album = new Album(Subsonic_XML_Data::getAmpacheId($albumid));
        if (empty($album->name)) {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, "Album not found.", 'getalbum');
        } else {
            $response = Subsonic_XML_Data::createSuccessResponse('getalbum');
            Subsonic_XML_Data::addAlbum($response, $album, true, $addAmpacheInfo);
        }

        self::apiOutput($input, $response);
    }

    /**
     * getVideos
     * Get all videos.
     * Takes no parameter.
     */
    public static function getvideos($input)
    {
        $response = Subsonic_XML_Data::createSuccessResponse('getvideos');
        $videos   = Catalog::get_videos();
        Subsonic_XML_Data::addVideos($response, $videos);
        self::apiOutput($input, $response);
    }

    /**
     * getAlbumList
     * Get a list of random, newest, highest rated etc. albums.
     * Takes the list type with optional size and offset in parameters.
     */
    public static function getalbumlist($input, $elementName = "albumList")
    {
        $type     = self::check_parameter($input, 'type');
        $username = self::check_parameter($input, 'u');

        $size          = $input['size'];
        $offset        = $input['offset'];
        $musicFolderId = $input['musicFolderId'] ?: 0;

        // Get albums from all catalogs by default
        // Catalog filter is not supported for all request type for now.
        $catalogs = null;
        if ($musicFolderId > 0) {
            $catalogs   = array();
            $catalogs[] = $musicFolderId;
        }

        $response     = Subsonic_XML_Data::createSuccessResponse('getalbumlist');
        $errorOccured = false;
        $albums       = array();
        $user         = User::get_from_username($username);

        switch ($type) {
            case "random":
                $albums = Album::get_random($size, false, $user->id);
                break;
            case "newest":
                $albums = Stats::get_newest("album", $size, $offset, $musicFolderId);
                break;
            case "highest":
                $albums = Rating::get_highest("album", $size, $offset);
                break;
            case "frequent":
                $albums = Stats::get_top("album", $size, '', $offset);
                break;
            case "recent":
                $albums = Stats::get_recent("album", $size, $offset);
                break;
            case "starred":
                $albums = Userflag::get_latest('album', null, $size);
                break;
            case "alphabeticalByName":
                $albums = Catalog::get_albums($size, $offset, $catalogs);
                break;
            case "alphabeticalByArtist":
                $albums = Catalog::get_albums_by_artist($size, $offset, $catalogs);
                break;
            case "byYear":
                $fromYear = $input['fromYear'] < $input['toYear'] ? $input['fromYear']: $input['toYear'] ;
                $toYear   = $input['toYear'] > $input['fromYear'] ? $input['toYear'] : $input['fromYear'];

                if ($fromYear || $toYear) {
                    $search = Search::year_search($fromYear, $toYear, $size, $offset);
                    $query  = new Search(null, 'album');
                    $albums = $query->run($search);
                }
                break;
            case "byGenre":
                $genre = self::check_parameter($input, 'genre');

                $tag_id = Tag::tag_exists($genre);
                if ($tag_id) {
                    $albums = Tag::get_tag_objects('album', $tag_id, $size, $offset);
                }
                break;
            default:
                $response     = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_GENERIC, "Invalid list type: " . scrub_out((string) $type), 'getalbumlist');
                $errorOccured = true;
        }

        if (!$errorOccured) {
            Subsonic_XML_Data::addAlbumList($response, $albums, $elementName);
        }
        self::apiOutput($input, $response);
    }

    /**
     * getAlbumList2
     * See getAlbumList.
     */
    public static function getalbumlist2($input)
    {
        self::getAlbumList($input, "albumList2");
    }

    /**
     * getRandomSongs
     * Get random songs matching the given criteria.
     * Takes the optional size, genre, fromYear, toYear and music folder id in parameters.
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
            if (Subsonic_XML_Data::isArtist($musicFolderId)) {
                $artist   = new Artist(Subsonic_XML_Data::getAmpacheId($musicFolderId));
                $finput   = $artist->name;
                $operator = 4;
                $ftype    = "artist";
            } else {
                if (Subsonic_XML_Data::isAlbum($musicFolderId)) {
                    $album    = new Album(Subsonic_XML_Data::getAmpacheId($musicFolderId));
                    $finput   = $album->name;
                    $operator = 4;
                    $ftype    = "artist";
                } else {
                    $finput   = (int) ($musicFolderId);
                    $operator = 0;
                    $ftype    = "catalog";
                }
            }
            $search['rule_' . $count . '_input']    = $finput;
            $search['rule_' . $count . '_operator'] = $operator;
            $search['rule_' . $count . '']          = $ftype;
            ++$count;
        }
        $user = User::get_from_username($username);
        if ($count > 0) {
            $songs = Random::advanced('song', $search);
        } else {
            $songs = Random::get_default($size, $user->id);
        }

        $response = Subsonic_XML_Data::createSuccessResponse('getrandomsongs');
        Subsonic_XML_Data::addRandomSongs($response, $songs);
        self::apiOutput($input, $response);
    }

    /**
     * getSong
     * Get details for a song
     * Takes the song id in parameter.
     */
    public static function getsong($input)
    {
        $songid   = self::check_parameter($input, 'id');
        $response = Subsonic_XML_Data::createSuccessResponse('getsong');
        $song     = Subsonic_XML_Data::getAmpacheId($songid);
        Subsonic_XML_Data::addSong($response, $song);
        self::apiOutput($input, $response);
    }

    /**
     * getTopSongs
     * Get most popular songs for a given artist.
     * Takes the genre with optional count and offset in parameters.
     */
    public static function gettopsongs($input)
    {
        $artist = Artist::get_from_name(urldecode(self::check_parameter($input, 'artist')));
        $count  = (int) $input['count'];
        if ($count <= 0) {
            $count = 50;
        }
        if ($artist->id) {
            $songs = Artist::get_top_songs($artist->id, $count);
        } else {
            $songs = array();
        }
        $response = Subsonic_XML_Data::createSuccessResponse('gettopsongs');
        Subsonic_XML_Data::addTopSongs($response, $songs);
        self::apiOutput($input, $response);
    }

    /**
     * getSongsByGenre
     * Get songs in a given genre.
     * Takes the genre with optional count and offset in parameters.
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
        $response = Subsonic_XML_Data::createSuccessResponse('getsongsbygenre');
        Subsonic_XML_Data::addSongsByGenre($response, $songs);
        self::apiOutput($input, $response);
    }

    /**
     * getNowPlaying
     * Get what is currently being played by all users.
     * Takes no parameter.
     */
    public static function getnowplaying($input)
    {
        $data     = Stream::get_now_playing();
        $response = Subsonic_XML_Data::createSuccessResponse('getnowplaying');
        Subsonic_XML_Data::addNowPlaying($response, $data);
        self::apiOutput($input, $response);
    }

    /**
     * search2
     * Get albums, artists and songs matching the given criteria.
     * Takes query with optional artist count, artist offset, album count, album offset, song count and song offset in parameters.
     */
    public static function search2($input, $elementName = "searchResult2")
    {
        $query    = self::check_parameter($input, 'query');
        $artists  = array();
        $albums   = array();
        $songs    = array();
        $operator = 0;

        if (strlen($query) > 1) {
            if (substr($query, -1) == "*") {
                $query    = substr($query, 0, -1);
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

        $response = Subsonic_XML_Data::createSuccessResponse('search2');
        Subsonic_XML_Data::addSearchResult($response, $artists, $albums, $songs, $elementName);
        self::apiOutput($input, $response);
    }

    /**
     * search3
     * See search2.
     */
    public static function search3($input)
    {
        self::search2($input, "searchResult3");
    }

    /**
     * getPlaylists
     * Get all playlists a user is allowed to play.
     * Takes optional user in parameter.
     */
    public static function getplaylists($input)
    {
        $response = Subsonic_XML_Data::createSuccessResponse('getplaylists');
        $username = $input['username'];
        $user     = User::get_from_username($username);

        // Don't allow playlist listing for another user
        if (empty($username) || $username == $input['u']) {
            Subsonic_XML_Data::addPlaylists($response, Playlist::get_playlists(true, $user->id), Search::get_searches(true, $user->id));
        } else {
            if ($user->id) {
                Subsonic_XML_Data::addPlaylists($response, Playlist::get_users($user->id));
            } else {
                Subsonic_XML_Data::addPlaylists($response, array());
            }
        }
        self::apiOutput($input, $response);
    }

    /**
     * getPlaylist
     * Get the list of files in a saved playlist.
     * Takes the playlist id in parameters.
     */
    public static function getplaylist($input)
    {
        $playlistid = self::check_parameter($input, 'id');

        $response = Subsonic_XML_Data::createSuccessResponse('getplaylist');
        if (Subsonic_XML_Data::isSmartPlaylist($playlistid)) {
            $playlist = new Search(Subsonic_XML_Data::getAmpacheId($playlistid), 'song');
            Subsonic_XML_Data::addSmartPlaylist($response, $playlist, true);
        } else {
            $playlist = new Playlist(Subsonic_XML_Data::getAmpacheId($playlistid));
            Subsonic_XML_Data::addPlaylist($response, $playlist, true);
        }
        self::apiOutput($input, $response);
    }

    /**
     * createPlaylist
     * Create (or updates) a playlist.
     * Takes playlist id in parameter if updating, name in parameter if creating and a list of song id for the playlist.
     */
    public static function createplaylist($input)
    {
        $playlistId = $input['playlistId'];
        $name       = $input['name'];
        $songId     = $input['songId'];

        if ($playlistId) {
            self::_updatePlaylist($playlistId, $name, $songId);
            $response = Subsonic_XML_Data::createSuccessResponse('createplaylist');
        } else {
            if (!empty($name)) {
                $playlistId = Playlist::create($name, 'private');
                if (count($songId) > 0) {
                    self::_updatePlaylist($playlistId, "", $songId);
                }
                $response = Subsonic_XML_Data::createSuccessResponse('createplaylist');
            } else {
                $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_MISSINGPARAM, '', 'createplaylist');
            }
        }
        self::apiOutput($input, $response);
    }

    private static function _updatePlaylist($id, $name, $songsIdToAdd = array(), $songIndexToRemove = array(), $public = true)
    {
        $playlist           = new Playlist($id);
        $songsIdToAdd_count = 0;

        $newdata            = array();
        $newdata['name']    = (!empty($name)) ? $name : $playlist->name;
        $newdata['pl_type'] = ($public) ? "public" : "private";
        $playlist->update($newdata);

        if (!empty($songsIdToAdd)) {
            if (!is_array($songsIdToAdd)) {
                $songsIdToAdd       = array($songsIdToAdd);
                $songsIdToAdd_count = count($songsIdToAdd);
            }
            if ($songsIdToAdd_count > 0) {
                for ($i = 0; $i < $songsIdToAdd_count; ++$i) {
                    $songsIdToAdd[$i] = Subsonic_XML_Data::getAmpacheId($songsIdToAdd[$i]);
                }
                $playlist->add_songs($songsIdToAdd);
            }
        }

        if (!empty($songIndexToRemove)) {
            if (!is_array($songIndexToRemove)) {
                $songIndexToRemove = array($songIndexToRemove);
            }
            if (count($songIndexToRemove) > 0) {
                foreach ($songIndexToRemove as $track) {
                    $playlist->delete_track_number($track);
                }
                $playlist->regenerate_track_numbers();
            }
        }
    }

    /**
     * updatePlaylist
     * Update a playlist.
     * Takes playlist id in parameter with optional name, comment, public level and a list of song id to add/remove.
     */
    public static function updateplaylist($input)
    {
        $playlistId = self::check_parameter($input, 'playlistId');
        $name       = $input['name'];
        // Not supported. $comment = $input['comment'];
        $public = ($input['public'] === "true");

        if (!Subsonic_XML_Data::isSmartPlaylist($playlistId)) {
            $songIdToAdd       = $input['songIdToAdd'];
            $songIndexToRemove = $input['songIndexToRemove'];

            self::_updatePlaylist(Subsonic_XML_Data::getAmpacheId($playlistId), $name, $songIdToAdd, $songIndexToRemove, $public);

            $response = Subsonic_XML_Data::createSuccessResponse('updateplaylist');
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, 'Cannot edit a smart playlist.', 'updateplaylist');
        }
        self::apiOutput($input, $response);
    }

    /**
     * deletePlaylist
     * Delete a saved playlist.
     * Takes playlist id in parameter.
     */
    public static function deleteplaylist($input)
    {
        $playlistId = self::check_parameter($input, 'id');

        if (Subsonic_XML_Data::isSmartPlaylist($playlistId)) {
            $playlist = new Search(Subsonic_XML_Data::getAmpacheId($playlistId), 'song');
            $playlist->delete();
        } else {
            $playlist = new Playlist(Subsonic_XML_Data::getAmpacheId($playlistId));
            $playlist->delete();
        }

        $response = Subsonic_XML_Data::createSuccessResponse('deleteplaylist');
        self::apiOutput($input, $response);
    }

    /**
     * stream
     * Streams a given media file.
     * Takes the file id in parameter with optional max bit rate, file format, time offset, size and estimate content length option.
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
        if ((int) $maxBitRate > 0) {
            $params .= '&bitrate=' . $maxBitRate;
        }
        if ($timeOffset) {
            $params .= '&frame=' . $timeOffset;
        }
        if (AmpConfig::get('subsonic_stream_scrobble') == 'false') {
            $params .= '&cache=1';
        }

        $url = '';
        if (Subsonic_XML_Data::isSong($fileid)) {
            $url = Song::play_url(Subsonic_XML_Data::getAmpacheId($fileid), $params, 'api', function_exists('curl_version'), $user_id);
        } elseif (Subsonic_XML_Data::isPodcastEp($fileid)) {
            $url = Podcast_Episode::play_url(Subsonic_XML_Data::getAmpacheId($fileid), $params, 'api', function_exists('curl_version'), $user_id);
        }

        if (!empty($url)) {
            self::follow_stream($url);
        }
    }

    /**
     * download
     * Downloads a given media file.
     * Takes the file id in parameter.
     */
    public static function download($input)
    {
        $fileid = self::check_parameter($input, 'id', true);

        $url = Song::play_url(Subsonic_XML_Data::getAmpacheId($fileid), '&action=download' . '&client=' . rawurlencode($input['c']) . '&cache=1', 'api', function_exists('curl_version'));
        self::follow_stream($url);
    }

    /**
     * hls
     * Create an HLS playlist.
     * Takes the file id in parameter with optional max bit rate.
     */
    public static function hls($input)
    {
        $fileid = self::check_parameter($input, 'id', true);

        $bitRate = $input['bitRate'];

        $media                = array();
        $media['object_type'] = 'song';
        $media['object_id']   = Subsonic_XML_Data::getAmpacheId($fileid);

        $medias            = array();
        $medias[]          = $media;
        $stream            = new Stream_Playlist();
        $additional_params = '';
        if ($bitRate) {
            $additional_params .= '&bitrate=' . $bitRate;
        }
        //$additional_params .= '&transcode_to=ts';
        $stream->add($medias, $additional_params);

        header('Content-Type: application/vnd.apple.mpegurl;');
        $stream->create_m3u();
    }

    /**
     * getCoverArt
     * Get a cover art image.
     * Takes the cover art id in parameter.
     */
    public static function getcoverart($input)
    {
        $id   = str_replace('al-', '', self::check_parameter($input, 'id', true));
        $id   = str_replace('pl-', '', $id);
        $size = $input['size'];

        $art = null;
        if (Subsonic_XML_Data::isArtist($id)) {
            $art = new Art(Subsonic_XML_Data::getAmpacheId($id), "artist");
        } elseif (Subsonic_XML_Data::isAlbum($id)) {
            $art = new Art(Subsonic_XML_Data::getAmpacheId($id), "album");
        } elseif (Subsonic_XML_Data::isSong($id)) {
            $art = new Art(Subsonic_XML_Data::getAmpacheId($id), "song");
            if ($art != null && $art->id == null) {
                // in most cases the song doesn't have a picture, but the album where it belongs to has
                // if this is the case, we take the album art
                $song = new Song(Subsonic_XML_Data::getAmpacheId(Subsonic_XML_Data::getAmpacheId($id)));
                $art  = new Art(Subsonic_XML_Data::getAmpacheId($song->album), "album");
            }
        } elseif (Subsonic_XML_Data::isPodcast($id)) {
            $art = new Art(Subsonic_XML_Data::getAmpacheId($id), "podcast");
        } else {
            $listitems = array();
            // playlists and smartlists
            if (Subsonic_XML_Data::isSmartPlaylist($id)) {
                $playlist  = new Search(Subsonic_XML_Data::getAmpacheId($id));
                $listitems = $playlist->get_items();
            } elseif (Subsonic_XML_Data::isPlaylist($id)) {
                $playlist  = new Playlist(Subsonic_XML_Data::getAmpacheId($id));
                $listitems = $playlist->get_items();
            }
            $item      = (!empty($listitems)) ? $listitems[array_rand($listitems)] : array();
            $art       = (!empty($item)) ? new Art($item['object_id'], $item['object_type']) : null;
            if ($art != null && $art->id == null) {
                $song = new Song($item['object_id']);
                $art  = new Art(Subsonic_XML_Data::getAmpacheId($song->album), "album");
            }
        }

        header("Access-Control-Allow-Origin: *");
        if ($art != null) {
            $art->has_db_info();
            if ($size && AmpConfig::get('resize_images')) {
                $dim           = array();
                $dim['width']  = $size;
                $dim['height'] = $size;
                $thumb         = $art->get_thumb($dim);
                if (!empty($thumb)) {
                    header('Content-type: ' . $thumb['thumb_mime']);
                    header('Content-Length: ' . strlen($thumb['thumb']));
                    echo $thumb['thumb'];

                    return;
                }
            }

            header('Content-type: ' . $art->raw_mime);
            header('Content-Length: ' . strlen($art->raw));
            echo $art->raw;
        }
    }

    /**
     * setRating
     * Sets the rating for a music file.
     * Takes the file id and rating in parameters.
     */
    public static function setrating($input)
    {
        $object_id = self::check_parameter($input, 'id');
        $rating    = $input['rating'];

        $robj = null;
        if (Subsonic_XML_Data::isArtist($object_id)) {
            $robj = new Rating(Subsonic_XML_Data::getAmpacheId($object_id), "artist");
        } else {
            if (Subsonic_XML_Data::isAlbum($object_id)) {
                $robj = new Rating(Subsonic_XML_Data::getAmpacheId($object_id), "album");
            } else {
                if (Subsonic_XML_Data::isSong($object_id)) {
                    $robj = new Rating(Subsonic_XML_Data::getAmpacheId($object_id), "song");
                }
            }
        }

        if ($robj != null) {
            $robj->set_rating($rating);

            $response = Subsonic_XML_Data::createSuccessResponse('setrating');
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, "Media not found.", 'setrating');
        }

        self::apiOutput($input, $response);
    }

    /**
     * getStarred
     * Get starred songs, albums and artists.
     * Takes no parameter.
     * Not supported.
     */
    public static function getstarred($input, $elementName = "starred")
    {
        $user_id = User::get_from_username($input['u'])->id;

        $response = Subsonic_XML_Data::createSuccessResponse('getstarred');
        Subsonic_XML_Data::addStarred($response, Userflag::get_latest('artist', $user_id, 10000), Userflag::get_latest('album', $user_id, 10000), Userflag::get_latest('song', $user_id, 10000), $elementName);
        self::apiOutput($input, $response);
    }

    /**
     * getStarred2
     * See getStarred.
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
     */
    public static function unstar($input)
    {
        self::_setStar($input, false);
    }

    /**
     * @param boolean $star
     */
    private static function _setStar($input, $star)
    {
        $object_id = $input['id'];
        $albumId   = $input['albumId'];
        $artistId  = $input['artistId'];

        // Normalize all in one array
        $ids = array();

        $response = Subsonic_XML_Data::createSuccessResponse('_setStar');
        if ($object_id) {
            if (!is_array($object_id)) {
                $object_id = array($object_id);
            }
            foreach ($object_id as $item) {
                $aid = Subsonic_XML_Data::getAmpacheId($item);
                if (Subsonic_XML_Data::isArtist($item)) {
                    $type = 'artist';
                } else {
                    if (Subsonic_XML_Data::isAlbum($item)) {
                        $type = 'album';
                    } else {
                        if (Subsonic_XML_Data::isSong($item)) {
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
                    $aid   = Subsonic_XML_Data::getAmpacheId($album);
                    $ids[] = array('id' => $aid, 'type' => 'album');
                }
            } else {
                if ($artistId) {
                    if (!is_array($artistId)) {
                        $artistId = array($artistId);
                    }
                    foreach ($artistId as $artist) {
                        $aid   = Subsonic_XML_Data::getAmpacheId($artist);
                        $ids[] = array('id' => $aid, 'type' => 'artist');
                    }
                } else {
                    $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_MISSINGPARAM, 'Missing parameter', '_setStar');
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
     */
    public static function getuser($input)
    {
        $username = self::check_parameter($input, 'username');
        $myuser   = User::get_from_username($input['u']);

        if ($myuser->access >= 100 || $myuser->username == $username) {
            $response = Subsonic_XML_Data::createSuccessResponse('getuser');
            if ($myuser->username == $username) {
                $user = $myuser;
            } else {
                $user = User::get_from_username($username);
            }
            Subsonic_XML_Data::addUser($response, $user);
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, $input['u'] . ' is not authorized to get details for other users.', 'getuser');
        }
        self::apiOutput($input, $response);
    }

    /**
     * getUsers
     * Get details about a given user.
     * Takes no parameter.
     * Not supported.
     */
    public static function getusers($input)
    {
        $myuser = User::get_from_username($input['u']);
        if ($myuser->access >= 100) {
            $response     = Subsonic_XML_Data::createSuccessResponse('getusers');
            $users        = User::get_valid_users();
            Subsonic_XML_Data::addUsers($response, $users);
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, $input['u'] . ' is not authorized to get details for other users.', 'getusers');
        }
        self::apiOutput($input, $response);
    }

    /**
     * getAvatar
     * Return the user avatar in bytes.
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
                $user = User::get_from_username($username);
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
                $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, '', 'getavatar');
            }
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, $input['u'] . ' is not authorized to get avatar for other users.', 'getavatar');
        }

        if ($response != null) {
            self::apiOutput($input, $response);
        }
    }

    /**
     * getInternetRadioStations
     * Get all internet radio stations
     * Takes no parameter.
     */
    public static function getinternetradiostations($input)
    {
        $response = Subsonic_XML_Data::createSuccessResponse('getinternetradiostations');
        $radios   = Live_Stream::get_all_radios();
        Subsonic_XML_Data::addRadios($response, $radios);
        self::apiOutput($input, $response);
    }

    /**
     * getShares
     * Get information about shared media this user is allowed to manage.
     * Takes no parameter.
     */
    public static function getshares($input)
    {
        $response = Subsonic_XML_Data::createSuccessResponse('getshares');
        $shares   = Share::get_share_list();
        Subsonic_XML_Data::addShares($response, $shares);
        self::apiOutput($input, $response);
    }

    /**
     * createShare
     * Create a public url that can be used by anyone to stream media.
     * Takes the file id with optional description and expires parameters.
     */
    public static function createshare($input)
    {
        $id          = self::check_parameter($input, 'id');
        $description = $input['description'];

        if (AmpConfig::get('share')) {
            if (isset($input['expires'])) {
                $expires = $input['expires'];
                // no limit expiry
                if ($expires == 0) {
                    $expire_days = 0;
                } else {
                    // Parse as a string to work on 32-bit computers
                    if (strlen($expires) > 3) {
                        $expires = (int) (substr($expires, 0, - 3));
                    }
                    $expire_days = round(($expires - time()) / 86400, 0, PHP_ROUND_HALF_EVEN);
                }
            } else {
                //fall back to config defaults
                $expire_days = AmpConfig::get('share_expire');
            }

            $object_id = Subsonic_XML_Data::getAmpacheId($id);
            if (Subsonic_XML_Data::isAlbum($id)) {
                $object_type = 'album';
            }
            if (Subsonic_XML_Data::isSong($id)) {
                $object_type = 'song';
            }
            if (Subsonic_XML_Data::isPlaylist($id)) {
                $object_type = 'playlist';
            }

            if (!empty($object_type)) {
                $response = Subsonic_XML_Data::createSuccessResponse('createshare');
                $shares   = array();
                $shares[] = Share::create_share($object_type, $object_id, true, Access::check_function('download'), $expire_days, Share::generate_secret(), 0, $description);
                Subsonic_XML_Data::addShares($response, $shares);
            } else {
                $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, '', 'createshare');
            }
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, '', 'createshare');
        }
        self::apiOutput($input, $response);
    }

    /**
     * deleteShare
     * Delete an existing share.
     * Takes the share id to delete in parameters.
     */
    public static function deleteshare($input)
    {
        $id = self::check_parameter($input, 'id');

        if (AmpConfig::get('share')) {
            if (Share::delete_share($id)) {
                $response = Subsonic_XML_Data::createSuccessResponse('deleteshare');
            } else {
                $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, '', 'deleteshare');
            }
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, '', 'deleteshare');
        }
        self::apiOutput($input, $response);
    }

    /**
     * updateShare
     * Update the description and/or expiration date for an existing share.
     * Takes the share id to update with optional description and expires parameters.
     * Not supported.
     */
    public static function updateshare($input)
    {
        $id          = self::check_parameter($input, 'id');
        $description = $input['description'];

        if (AmpConfig::get('share')) {
            $share = new Share($id);
            if ($share->id > 0) {
                $expires = $share->expire_days;
                if (isset($input['expires'])) {
                    // Parse as a string to work on 32-bit computers
                    $expires = $input['expires'];
                    if (strlen($expires) > 3) {
                        $expires = (int) (substr($expires, 0, - 3));
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
                if ($share->update($data)) {
                    $response = Subsonic_XML_Data::createSuccessResponse('updateshare');
                } else {
                    $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, '', 'updateshare');
                }
            } else {
                $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, '', 'updateshare');
            }
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, '', 'updateshare');
        }

        self::apiOutput($input, $response);
    }

    /**
     * createUser
     * Create a new user.
     * Takes the username, password and email with optional roles in parameters.
     */
    public static function createuser($input)
    {
        $username     = self::check_parameter($input, 'username');
        $password     = self::check_parameter($input, 'password');
        $email        = urldecode(self::check_parameter($input, 'email'));
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
            if ($adminRole) {
                $access = 100;
            } elseif ($coverArtRole) {
                $access = 75;
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
                $response = Subsonic_XML_Data::createSuccessResponse('createuser');
            } else {
                $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, '', 'createuser');
            }
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, '', 'createuser');
        }

        self::apiOutput($input, $response);
    }

    /**
     * updateUser
     * Update an existing user.
     * Takes the username with optional parameters.
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
            if ($adminRole) {
                $access = 100;
            } elseif ($coverArtRole) {
                $access = 75;
            }
            // identify the user to modify
            $user    = User::get_from_username($username);
            $user_id = $user->id;

            if ($user_id > 0) {
                // update password
                if ($password) {
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
                if ((int) $maxbitrate > 0) {
                    Preference::update('transcode_bitrate', $user_id, $maxbitrate);
                }
                $response = Subsonic_XML_Data::createSuccessResponse('updateuser');
            } else {
                $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, '', 'updateuser');
            }
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, '', 'updateuser');
        }

        self::apiOutput($input, $response);
    }

    /**
     * deleteUser
     * Delete an existing user.
     * Takes the username in parameter.
     */
    public static function deleteuser($input)
    {
        $username = self::check_parameter($input, 'username');
        if (Access::check('interface', 100)) {
            $user = User::get_from_username($username);
            if ($user->id) {
                $user->delete();
                $response = Subsonic_XML_Data::createSuccessResponse('deleteuser');
            } else {
                $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, '', 'deleteuser');
            }
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, '', 'deleteuser');
        }

        self::apiOutput($input, $response);
    }

    /**
     * change password
     * Change the password of an existing user.
     * Takes the username with new password in parameters.
     */
    public static function changepassword($input)
    {
        $username     = self::check_parameter($input, 'username');
        $inp_pass     = self::check_parameter($input, 'password');
        $password     = self::decrypt_password($inp_pass);
        $myuser       = User::get_from_username($input['u']);

        if ($myuser->username == $username || Access::check('interface', 100)) {
            $user = User::get_from_username($username);
            if ($user->id) {
                $user->update_password($password);
                $response = Subsonic_XML_Data::createSuccessResponse('changepassword');
            } else {
                $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, '', 'changepassword');
            }
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, '', 'changepassword');
        }
        self::apiOutput($input, $response);
    }

    /**
     * jukeboxControl
     * Control the jukebox.
     * Takes the action with optional index, offset, song id and volume gain in parameters.
     * Not supported.
     */
    public static function jukeboxcontrol($input)
    {
        $action = self::check_parameter($input, 'action');
        $id     = $input['id'];
        $gain   = $input['gain'];

        $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, '', 'jukeboxcontrol');
        debug_event('subsonic_api.class', 'Using Localplay controller: ' . AmpConfig::get('localplay_controller'), 5);
        $localplay = new Localplay(AmpConfig::get('localplay_controller'));

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
                        debug_event('subsonic_api.class', 'Skip with offset is not supported on JukeboxControl.', 5);
                    } else {
                        $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_MISSINGPARAM, '', 'jukeboxcontrol');
                    }
                    break;
                case 'set':
                    $localplay->delete_all();
                // Intentional break fall-through
                case 'add':
                    if ($id) {
                        if (!is_array($id)) {
                            $rid   = array();
                            $rid[] = $id;
                            $id    = $rid;
                        }

                        foreach ($id as $i) {
                            $url = null;
                            if (Subsonic_XML_Data::isSong($i)) {
                                $url = Song::generic_play_url('song', Subsonic_XML_Data::getAmpacheId($i), '', 'api');
                            }

                            if ($url !== null) {
                                debug_event('subsonic_api.class', 'Adding ' . $url, 5);
                                $stream        = array();
                                $stream['url'] = $url;
                                $ret           = $localplay->add_url(new Stream_URL($stream));
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
                        $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_MISSINGPARAM, '', 'jukeboxcontrol');
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
                $response = Subsonic_XML_Data::createSuccessResponse('jukeboxcontrol');
                if ($action == 'get') {
                    Subsonic_XML_Data::addJukeboxPlaylist($response, $localplay);
                } else {
                    Subsonic_XML_Data::createJukeboxStatus($response, $localplay);
                }
            }
        }

        self::apiOutput($input, $response);
    }

    /**
     * scrobble
     * Scrobbles a given music file on last.fm.
     * Takes the file id with optional time and submission parameters.
     */
    public static function scrobble($input)
    {
        $oid        = self::check_parameter($input, 'id');
        $submission = $input['submission'];
        $user       = User::get_from_username($input['u']);
        $time       = time();

        if (!is_array($oid)) {
            $rid   = array();
            $rid[] = $oid;
            $oid   = $rid;
        }

        $counter = 0;
        foreach ($oid as $object) {
            $aid   = Subsonic_XML_Data::getAmpacheId($object);
            $type  = Subsonic_XML_Data::getAmpacheType($object);
            $media = new $type($aid);
            $media->format();

            // internal scrobbling (user_activity and object_count tables)
            if (($submission === 'true' || $submission === '1') && $counter == 0) {
                $media->set_played($user->id, $input['c'], array(), time());
                $counter++;
            }
            //scrobble plugins
            if ($submission === 'true' || $submission === '1') {
                // stream has finished
                debug_event('subsonic_api.class', 'scrobble: ' . $media->id . ' for ' . $user->username . ' using ' . $input['c'] . ' ' . (string) time(), 5);
                User::save_mediaplay($user, $media);
            } elseif ($submission === 'false' || $submission === '0') {
                // stream is in progress
                debug_event('subsonic_api.class', 'now_playing: ' . $media->id . ' for ' . $user->username . ' using ' . $input['c'] . ' ' . (string) $time, 5);
                Stream::garbage_collection();
                Stream::insert_now_playing((int) $media->id, (int) $user->id, (int) $media->time, $user->username, $type);
            }
        }

        $response = Subsonic_XML_Data::createSuccessResponse('scrobble');
        self::apiOutput($input, $response);
    }

    /**
     * getLyrics
     * Searches and returns lyrics for a given song.
     * Takes the optional artist and title in parameters.
     */
    public static function getlyrics($input)
    {
        $artist = $input['artist'];
        $title  = $input['title'];

        if (!$artist && !$title) {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_MISSINGPARAM, '', 'getlyrics');
        } else {
            $search           = array();
            $search['limit']  = 1;
            $search['offset'] = 0;
            $search['type']   = "song";

            $count = 0;
            if ($artist) {
                $search['rule_' . $count . '_input']    = $artist;
                $search['rule_' . $count . '_operator'] = 5;
                $search['rule_' . $count . '']          = "artist";
                ++$count;
            }
            if ($title) {
                $search['rule_' . $count . '_input']    = $title;
                $search['rule_' . $count . '_operator'] = 5;
                $search['rule_' . $count . '']          = "title";
                ++$count;
            }

            $query = new Search(null, 'song');
            $songs = $query->run($search);

            $response = Subsonic_XML_Data::createSuccessResponse('getlyrics');
            if (count($songs) > 0) {
                Subsonic_XML_Data::addLyrics($response, $artist, $title, $songs[0]);
            }
        }

        self::apiOutput($input, $response);
    }

    /**
     * getArtistInfo
     * Returns artist info with biography, image URLs and similar artists, using data from last.fm.
     * Takes artist id in parameter with optional similar artist count and if not present similar artist should be returned.
     */
    public static function getartistinfo($input)
    {
        $id                = self::check_parameter($input, 'id');
        $count             = $input['count'] ?: 20;
        $includeNotPresent = ($input['includeNotPresent'] === "true");

        if (Subsonic_XML_Data::isArtist($id)) {
            $artist_id = Subsonic_XML_Data::getAmpacheId($id);
            $info      = Recommendation::get_artist_info($artist_id);
            $similars  = Recommendation::get_artists_like($artist_id, $count, !$includeNotPresent);
            $response  = Subsonic_XML_Data::createSuccessResponse('getartistinfo');
            Subsonic_XML_Data::addArtistInfo($response, $info, $similars);
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, '', 'getartistinfo');
        }

        self::apiOutput($input, $response);
    }

    /**
     * getArtistInfo2
     * See getArtistInfo.
     */
    public static function getartistinfo2($input)
    {
        return self::getartistinfo($input);
    }

    /**
     * getSimilarSongs
     * Returns a random collection of songs from the given artist and similar artists, using data from last.fm. Typically used for artist radio features.
     * Takes song/album/artist id in parameter with optional similar songs count.
     */
    public static function getsimilarsongs($input)
    {
        if (!AmpConfig::get('show_similar')) {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, "Show similar must be enabled", 'getsimilarsongs');
            self::apiOutput($input, $response);

            return;
        }

        $id    = self::check_parameter($input, 'id');
        $count = $input['count'] ?: 50;

        $songs = array();
        if (Subsonic_XML_Data::isArtist($id)) {
            $similars = Recommendation::get_artists_like(Subsonic_XML_Data::getAmpacheId($id));
            if (!empty($similars)) {
                debug_event('subsonic_api.class', 'Found: ' . count($similars) . ' similar artists', 4);
                foreach ($similars as $similar) {
                    debug_event('subsonic_api.class', $similar['name'] . ' (id=' . $similar['id'] . ')', 5);
                    if ($similar['id']) {
                        $artist = new Artist($similar['id']);
                        // get the songs in a random order for even more chaos
                        $artist_songs = $artist->get_random_songs();
                        foreach ($artist_songs as $song) {
                            $songs[] = array('id' => $song);
                        }
                    }
                }
            }
            // randomize and slice
            shuffle($songs);
            $songs = array_slice($songs, 0, $count);
        } elseif (Subsonic_XML_Data::isAlbum($id)) {
            // TODO: support similar songs for albums
        } elseif (Subsonic_XML_Data::isSong($id)) {
            $songs = Recommendation::get_songs_like(Subsonic_XML_Data::getAmpacheId($id), $count);
        }

        if (count($songs) == 0) {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, '', 'getsimilarsongs');
        } else {
            $response = Subsonic_XML_Data::createSuccessResponse('getsimilarsongs');
            Subsonic_XML_Data::addSimilarSongs($response, $songs);
        }

        self::apiOutput($input, $response);
    }

    /**
     * getSimilarSongs2
     * See getSimilarSongs.
     */
    public static function getsimilarsongs2($input)
    {
        self::getsimilarsongs($input);
    }

    /**
     * getPodcasts
     * Get all podcast channels.
     * Takes the optional includeEpisodes and channel id in parameters
     */
    public static function getpodcasts($input)
    {
        $id              = $input['id'];
        $includeEpisodes = isset($input['includeEpisodes']) ? $input['includeEpisodes'] : true;

        if (AmpConfig::get('podcast')) {
            if ($id) {
                $podcast = new Podcast(Subsonic_XML_Data::getAmpacheId($id));
                if ($podcast->id) {
                    $response = Subsonic_XML_Data::createSuccessResponse('getpodcasts');
                    Subsonic_XML_Data::addPodcasts($response, array($podcast), $includeEpisodes);
                } else {
                    $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, '', 'getpodcasts');
                }
            } else {
                $podcasts = Catalog::get_podcasts();
                $response = Subsonic_XML_Data::createSuccessResponse('getpodcasts');
                Subsonic_XML_Data::addPodcasts($response, $podcasts, $includeEpisodes);
            }
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, '', 'getpodcasts');
        }
        self::apiOutput($input, $response);
    }

    /**
     * getNewestPodcasts
     * Get the most recently published podcast episodes.
     * Takes the optional count in parameters
     */
    public static function getnewestpodcasts($input)
    {
        $count = $input['count'] ?: AmpConfig::get('podcast_new_download');

        if (AmpConfig::get('podcast')) {
            $response = Subsonic_XML_Data::createSuccessResponse('getnewestpodcasts');
            $episodes = Catalog::get_newest_podcasts($count);
            Subsonic_XML_Data::addNewestPodcastEpisodes($response, $episodes);
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, '', 'getnewestpodcasts');
        }
        self::apiOutput($input, $response);
    }

    /**
     * refreshPodcasts
     * Request the server to check for new podcast episodes.
     * Takes no parameters.
     */
    public static function refreshpodcasts($input)
    {
        if (AmpConfig::get('podcast') && Access::check('interface', 75)) {
            $podcasts = Catalog::get_podcasts();
            foreach ($podcasts as $podcast) {
                $podcast->sync_episodes(true);
            }
            $response = Subsonic_XML_Data::createSuccessResponse('refreshpodcasts');
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, '', 'refreshpodcasts');
        }
        self::apiOutput($input, $response);
    }

    /**
     * createPodcastChannel
     * Add a new podcast channel.
     * Takes the podcast url in parameter.
     */
    public static function createpodcastchannel($input)
    {
        $url = self::check_parameter($input, 'url');

        if (AmpConfig::get('podcast') && Access::check('interface', 75)) {
            $catalogs = Catalog::get_catalogs('podcast');
            if (count($catalogs) > 0) {
                $data            = array();
                $data['feed']    = $url;
                $data['catalog'] = $catalogs[0];
                if (Podcast::create($data)) {
                    $response = Subsonic_XML_Data::createSuccessResponse('createpodcastchannel');
                } else {
                    $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_GENERIC, '', 'createpodcastchannel');
                }
            } else {
                $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, '', 'createpodcastchannel');
            }
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, '', 'createpodcastchannel');
        }
        self::apiOutput($input, $response);
    }

    /**
     * deletePodcastChannel
     * Delete an existing podcast channel
     * Takes the podcast id in parameter.
     */
    public static function deletepodcastchannel($input)
    {
        $id = self::check_parameter($input, 'id');

        if (AmpConfig::get('podcast') && Access::check('interface', 75)) {
            $podcast = new Podcast(Subsonic_XML_Data::getAmpacheId($id));
            if ($podcast->id) {
                if ($podcast->remove()) {
                    $response = Subsonic_XML_Data::createSuccessResponse('deletepodcastchannel');
                } else {
                    $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_GENERIC, '', 'deletepodcastchannel');
                }
            } else {
                $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, '', 'deletepodcastchannel');
            }
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, '', 'deletepodcastchannel');
        }
        self::apiOutput($input, $response);
    }

    /**
     * deletePodcastEpisode
     * Delete a podcast episode
     * Takes the podcast episode id in parameter.
     */
    public static function deletepodcastepisode($input)
    {
        $id = self::check_parameter($input, 'id');

        if (AmpConfig::get('podcast') && Access::check('interface', 75)) {
            $episode = new Podcast_Episode(Subsonic_XML_Data::getAmpacheId($id));
            if ($episode->id !== null) {
                if ($episode->remove()) {
                    $response = Subsonic_XML_Data::createSuccessResponse('deletepodcastepisode');
                } else {
                    $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_GENERIC, '', 'deletepodcastepisode');
                }
            } else {
                $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, '', 'deletepodcastepisode');
            }
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, '', 'deletepodcastepisode');
        }
        self::apiOutput($input, $response);
    }

    /**
     * downloadPodcastEpisode
     * Request the server to download a podcast episode
     * Takes the podcast episode id in parameter.
     */
    public static function downloadpodcastepisode($input)
    {
        $id = self::check_parameter($input, 'id');

        if (AmpConfig::get('podcast') && Access::check('interface', 75)) {
            $episode = new Podcast_Episode(Subsonic_XML_Data::getAmpacheId($id));
            if ($episode->id !== null) {
                $episode->gather();
                $response = Subsonic_XML_Data::createSuccessResponse('downloadpodcastepisode');
            } else {
                $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, '', 'downloadpodcastepisode');
            }
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, '', 'downloadpodcastepisode');
        }
        self::apiOutput($input, $response);
    }

    /**
     * getBookmarks
     * Get all user bookmarks.
     * Takes no parameter.
     * Not supported.
     */
    public static function getbookmarks($input)
    {
        $response  = Subsonic_XML_Data::createSuccessResponse('getbookmarks');
        $bookmarks = Bookmark::get_bookmarks();
        Subsonic_XML_Data::addBookmarks($response, $bookmarks);
        self::apiOutput($input, $response);
    }

    /**
     * createBookmark
     * Creates or updates a bookmark.
     * Takes the file id and position with optional comment in parameters.
     * Not supported.
     */
    public static function createbookmark($input)
    {
        $id       = self::check_parameter($input, 'id');
        $position = self::check_parameter($input, 'position');
        $comment  = $input['comment'];
        $type     = Subsonic_XML_Data::getAmpacheType($id);

        if (!empty($type)) {
            $bookmark = new Bookmark(Subsonic_XML_Data::getAmpacheId($id), $type);
            if ($bookmark->id) {
                $bookmark->update($position);
            } else {
                Bookmark::create(array(
                    'object_id' => Subsonic_XML_Data::getAmpacheId($id),
                    'object_type' => $type,
                    'comment' => $comment,
                    'position' => $position
                ));
            }
            $response = Subsonic_XML_Data::createSuccessResponse('createbookmark');
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, '', 'createbookmark');
        }
        self::apiOutput($input, $response);
    }

    /**
     * deleteBookmark
     * Delete an existing bookmark.
     * Takes the file id in parameter.
     * Not supported.
     */
    public static function deletebookmark($input)
    {
        $id   = self::check_parameter($input, 'id');
        $type = Subsonic_XML_Data::getAmpacheType($id);

        $bookmark = new Bookmark(Subsonic_XML_Data::getAmpacheId($id), $type);
        if ($bookmark->id) {
            $bookmark->remove();
            $response = Subsonic_XML_Data::createSuccessResponse('deletebookmark');
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, '', 'deletebookmark');
        }
        self::apiOutput($input, $response);
    }

    /**
     * getChatMessages
     * Get the current chat messages.
     * Takes no parameter.
     * Not supported.
     */
    public static function getchatmessages($input)
    {
        $since     = (int) $input['since'];
        $messages  = PrivateMsg::get_chat_msgs($since);
        $response  = Subsonic_XML_Data::createSuccessResponse('getchatmessages');
        Subsonic_XML_Data::addMessages($response, $messages);
        self::apiOutput($input, $response);
    }

    /**
     * addChatMessages
     * Add a message to the chat.
     * Takes the message in parameter.
     * Not supported.
     */
    public static function addchatmessage($input)
    {
        $message = self::check_parameter($input, 'message');
        $user_id = User::get_from_username($input['u'])->id;
        if (PrivateMsg::send_chat_msg($message, $user_id) !== null) {
            $response = Subsonic_XML_Data::createSuccessResponse('addchatmessage');
        } else {
            $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, '', 'addChatMessage');
        }
        self::apiOutput($input, $response);
    }

    /*     * **   CURRENT UNSUPPORTED FUNCTIONS   *** */

    /**
     * getPlayQueue
     * Returns the state of the play queue for the authenticated user.
     * Takes no parameter.
     * Not supported.
     */
    public static function getplayqueue($input)
    {
        $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, '', 'getplayqueue');
        self::apiOutput($input, $response);
    }

    /**
     * savePlayQueue
     * Save the state of the play queue for the authenticated user.
     * Takes multiple song id in parameter with optional current id playing song and position.
     * Not supported.
     */
    public static function saveplayqueue($input)
    {
        $response = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, '', 'saveplayqueue');
        self::apiOutput($input, $response);
    }
}
