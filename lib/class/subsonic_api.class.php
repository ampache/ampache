<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
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

    public static function check_version($input, $version = "1.0.0", $addheader = false)
    {
        // We cannot check client version unfortunately. Most Subsonic client sent a dummy client version...
        /*if (version_compare($input['v'], $version) < 0) {
            ob_end_clean();
            if ($addheader) self::setHeader($input['f']);
            self::apiOutput($input, Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_APIVERSION_CLIENT));
            exit;
        }*/
    }

    public static function check_parameter($input, $parameter, $addheader = false)
    {
        if (empty($input[$parameter])) {
            ob_end_clean();
            if ($addheader) {
                self::setHeader($input['f']);
            }
            self::apiOutput($input, Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_MISSINGPARAM));
            exit;
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
            for ($i=0; $i<strlen($hex); $i+=2) {
                $decpwd .= chr(hexdec(substr($hex, $i, 2)));
            }
            $password = $decpwd;
        }
        return $password;
    }

    public static function output_body($ch, $data)
    {
        echo $data;
        ob_flush();

        return strlen($data);
    }

    public static function output_header($ch, $header)
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
                http_response_code(curl_getinfo($ch, CURLINFO_HTTP_CODE));
            }
        }
        return strlen($header);
    }

    public static function follow_stream($url)
    {
        set_time_limit(0);
        ob_end_clean();

        header("Access-Control-Allow-Origin: *");
        if (function_exists('curl_version')) {
            // Here, we use curl from the ampache server to download data from
            // the ampache server, which can be a bit counter-intuitive.
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
            debug_event('subsonic', 'Stream proxy: ' . $url, 5);

            $ch = curl_init($url);
            curl_setopt_array($ch, array(
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
            if (curl_exec($ch) === false) {
                debug_event('subsonic', 'Stream error: ' . curl_error($ch), 1);
            }
            curl_close($ch);
        } else {
            // Stream media using http redirect if no curl support

            // Bug fix for android clients looking for /rest/ in destination url
            // Warning: external catalogs will not work!
            $url = str_replace('/play/', '/rest/fake/', $url);
            header("Location: " . $url);
        }
    }

    public static function setHeader($f)
    {
        if (strtolower($f) == "json") {
            header("Content-type: application/json; charset=" . AmpConfig::get('site_charset'));
            Subsonic_XML_Data::$enable_json_checks = true;
        } elseif (strtolower($f) == "jsonp") {
            header("Content-type: text/javascript; charset=" . AmpConfig::get('site_charset'));
            Subsonic_XML_Data::$enable_json_checks = true;
        } else {
            header("Content-type: text/xml; charset=" . AmpConfig::get('site_charset'));
        }
        header("Access-Control-Allow-Origin: *");
    }

    public static function apiOutput($input, $xml)
    {
        $f        = $input['f'];
        $callback = $input['callback'];
        self::apiOutput2(strtolower($f), $xml, $callback);
    }

    public static function apiOutput2($f, $xml, $callback='')
    {
        if ($f == "json") {
            $output = json_encode(self::xml2json($xml), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK);
        } else {
            if ($f == "jsonp") {
                $output = $callback . '(' . json_encode(self::xml2json($xml), JSON_PRETTY_PRINT) . ')';
            } else {
                $xmlstr = $xml->asXml();
                // Format xml output
                $dom = new DOMDocument();
                $dom->loadXML($xmlstr);
                $dom->formatOutput = true;
                $output            = $dom->saveXML();
            }
        }
        
        echo $output;
    }

    /**
     * xml2json based from http://outlandish.com/blog/xml-to-json/
     * Because we cannot use only json_encode to respect JSON Subsonic API
     */
    private static function xml2json($xml, $options = array())
    {
        $defaults = array(
            'namespaceSeparator' => ':',//you may want this to be something other than a colon
            'attributePrefix' => '',   //to distinguish between attributes and nodes with the same name
            'alwaysArray' => array(),   //array of xml tag names which should always become arrays
            'autoArray' => true,        //only create arrays for tags which appear more than once
            'textContent' => 'value',       //key used for the text content of elements
            'autoText' => true,         //skip textContent key if node has no attributes or child nodes
            'keySearch' => false,       //optional search and replace on tag and attribute names
            'keyReplace' => false,      //replace values for above search values (as passed to str_replace())
            'boolean' => true           //replace true and false string with boolean values
        );
        $options        = array_merge($defaults, $options);
        $namespaces     = $xml->getDocNamespaces();
        $namespaces[''] = null; //add base (empty) namespace

        //get attributes from all namespaces
        $attributesArray = array();
        foreach ($namespaces as $prefix => $namespace) {
            foreach ($xml->attributes($namespace) as $attributeName => $attribute) {
                //replace characters in attribute name
                if ($options['keySearch']) {
                    $attributeName =
                        str_replace($options['keySearch'], $options['keyReplace'], $attributeName);
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

        //get child nodes from all namespaces
        $tagsArray = array();
        foreach ($namespaces as $prefix => $namespace) {
            foreach ($xml->children($namespace) as $childXml) {
                //recurse into child nodes
                $childArray                           = self::xml2json($childXml, $options);
                list($childTagName, $childProperties) = each($childArray);

                //replace characters in tag name
                if ($options['keySearch']) {
                    $childTagName =
                        str_replace($options['keySearch'], $options['keyReplace'], $childTagName);
                }
                //add namespace prefix, if any
                if ($prefix) {
                    $childTagName = $prefix . $options['namespaceSeparator'] . $childTagName;
                }

                if (!isset($tagsArray[$childTagName])) {
                    //only entry with this key
                    //test if tags of this type should always be arrays, no matter the element count
                    $tagsArray[$childTagName] =
                            in_array($childTagName, $options['alwaysArray']) || !$options['autoArray']
                            ? array($childProperties) : $childProperties;
                } elseif (
                    is_array($tagsArray[$childTagName]) && array_keys($tagsArray[$childTagName])
                    === range(0, count($tagsArray[$childTagName]) - 1)
                ) {
                    //key already exists and is integer indexed array
                    $tagsArray[$childTagName][] = $childProperties;
                } else {
                    //key exists so convert to integer indexed array with previous value in position 0
                    $tagsArray[$childTagName] = array($tagsArray[$childTagName], $childProperties);
                }
            }
        }

        //get text content of node
        $textContentArray = array();
        $plainText        = trim((string) $xml);
        if ($plainText !== '') {
            $textContentArray[$options['textContent']] = $plainText;
        }

        //stick it all together
        $propertiesArray = !$options['autoText'] || $attributesArray || $tagsArray || ($plainText === '')
                ? array_merge($attributesArray, $tagsArray, $textContentArray) : $plainText;

        if (isset($propertiesArray['xmlns'])) {
            unset($propertiesArray['xmlns']);
        }
        //return node as array
        return array(
            $xml->getName() => $propertiesArray
        );
    }


    /**
     * ping
     * Simple server ping to test connectivity with the server.
     * Takes no parameter.
     */
    public static function ping($input)
    {
        // Don't check client API version here. Some client give version 0.0.0 for ping command

        self::apiOutput($input, Subsonic_XML_Data::createSuccessResponse());
    }

    /**
     * getLicense
     * Get details about the software license. Always return a valid default license.
     * Takes no parameter.
     */
    public static function getlicense($input)
    {
        self::check_version($input);

        $r = Subsonic_XML_Data::createSuccessResponse();
        Subsonic_XML_Data::addLicense($r);
        self::apiOutput($input, $r);
    }

    /**
     * getMusicFolders
     * Get all configured top-level music folders (= ampache catalogs).
     * Takes no parameter.
     */
     public static function getmusicfolders($input)
     {
         self::check_version($input);

         $r = Subsonic_XML_Data::createSuccessResponse();
         Subsonic_XML_Data::addMusicFolders($r, Catalog::get_catalogs());
         self::apiOutput($input, $r);
     }

    /**
     * getIndexes
     * Get an indexed structure of all artists.
     * Takes optional musicFolderId and optional ifModifiedSince in parameters.
     */
     public static function getindexes($input)
     {
         self::check_version($input);
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

         foreach ($catalogs as $id) {
             $clastmodified = 0;
             $catalog       = Catalog::create_from_id($id);

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
                 $fcatalogs[] = $id;
             }
         }
         if (empty($ifModifiedSince)) {
             $fcatalogs = $catalogs;
         }

         $r = Subsonic_XML_Data::createSuccessResponse();
         if (count($fcatalogs) > 0) {
             $artists = Catalog::get_artists($fcatalogs);
             Subsonic_XML_Data::addArtistsIndexes($r, $artists, $lastmodified);
         }
         self::apiOutput($input, $r);
     }

    /**
     * getMusicDirectory
     * Get a list of all files in a music directory.
     * Takes the directory id in parameters.
     */
     public static function getmusicdirectory($input)
     {
         self::check_version($input);

         $id = self::check_parameter($input, 'id');

         $r = Subsonic_XML_Data::createSuccessResponse();
         if (Subsonic_XML_Data::isArtist($id)) {
             $artist = new Artist(Subsonic_XML_Data::getAmpacheId($id));
             Subsonic_XML_Data::addArtistDirectory($r, $artist);
         } else {
             if (Subsonic_XML_Data::isAlbum($id)) {
                 $album = new Album(Subsonic_XML_Data::getAmpacheId($id));
                 Subsonic_XML_Data::addAlbumDirectory($r, $album);
             }
         }
         self::apiOutput($input, $r);
     }

    /**
     * getGenres
     * Get all genres.
     * Takes no parameter.
     */
    public static function getgenres($input)
    {
        self::check_version($input, "1.9.0");

        $r = Subsonic_XML_Data::createSuccessResponse();
        Subsonic_XML_Data::addGenres($r, Tag::get_tags('song'));
        self::apiOutput($input, $r);
    }

    /**
     * getArtists
     * Get all artists.
     * Takes no parameter.
     */
    public static function getartists($input)
    {
        self::check_version($input, "1.7.0");

        $r       = Subsonic_XML_Data::createSuccessResponse();
        $artists = Catalog::get_artists(Catalog::get_catalogs());
        Subsonic_XML_Data::addArtistsRoot($r, $artists, true);
        self::apiOutput($input, $r);
    }

    /**
     * getArtist
     * Get details fro an artist, including a list of albums.
     * Takes the artist id in parameter.
     */
    public static function getartist($input)
    {
        self::check_version($input, "1.7.0");

        $artistid = self::check_parameter($input, 'id');

        $artist = new Artist(Subsonic_XML_Data::getAmpacheId($artistid));
        if (empty($artist->name)) {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, "Artist not found.");
        } else {
            $r = Subsonic_XML_Data::createSuccessResponse();
            Subsonic_XML_Data::addArtist($r, $artist, true, true);
        }
        self::apiOutput($input, $r);
    }

    /**
     * getAlbum
     * Get details for an album, including a list of songs.
     * Takes the album id in parameter.
     */
    public static function getalbum($input)
    {
        self::check_version($input, "1.7.0");

        $albumid = self::check_parameter($input, 'id');

        $addAmpacheInfo = ($input['ampache'] == "1");

        $album = new Album(Subsonic_XML_Data::getAmpacheId($albumid));
        if (empty($album->name)) {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, "Album not found.");
        } else {
            $r = Subsonic_XML_Data::createSuccessResponse();
            Subsonic_XML_Data::addAlbum($r, $album, true, $addAmpacheInfo);
        }

        self::apiOutput($input, $r);
    }

    /**
     * getVideos
     * Get all videos.
     * Takes no parameter.
     */
    public static function getvideos($input)
    {
        self::check_version($input, "1.7.0");

        $r      = Subsonic_XML_Data::createSuccessResponse();
        $videos = Catalog::get_videos();
        Subsonic_XML_Data::addVideos($r, $videos);
        self::apiOutput($input, $r);
    }

    /**
     * getAlbumList
     * Get a list of random, newest, highest rated etc. albums.
     * Takes the list type with optional size and offset in parameters.
     */
    public static function getalbumlist($input, $elementName="albumList")
    {
        self::check_version($input, "1.2.0");

        $type = self::check_parameter($input, 'type');

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
        
        $r            = Subsonic_XML_Data::createSuccessResponse();
        $errorOccured = false;
        $albums       = array();
        
        if ($type == "random") {
            $albums = Album::get_random($size);
        } else {
            if ($type == "newest") {
                $albums = Stats::get_newest("album", $size, $offset, $musicFolderId);
            } else {
                if ($type == "highest") {
                    $albums = Rating::get_highest("album", $size, $offset);
                } else {
                    if ($type == "frequent") {
                        $albums = Stats::get_top("album", $size, '', $offset);
                    } else {
                        if ($type == "recent") {
                            $albums = Stats::get_recent("album", $size, $offset);
                        } else {
                            if ($type == "starred") {
                                $albums = Userflag::get_latest('album');
                            } else {
                                if ($type == "alphabeticalByName") {
                                    $albums = Catalog::get_albums($size, $offset, $catalogs);
                                } else {
                                    if ($type == "alphabeticalByArtist") {
                                        $albums = Catalog::get_albums_by_artist($size, $offset, $catalogs);
                                    } else {
                                        if ($type == "byYear") {
                                            $fromYear = $input['fromYear'];
                                            $toYear   = $input['toYear'];

                                            if ($fromYear || $toYear) {
                                                $search           = array();
                                                $search['limit']  = $size;
                                                $search['offset'] = $offset;
                                                $search['type']   = "album";
                                                $i                = 0;
                                                if ($fromYear) {
                                                    $search['rule_' . $i . '_input']    = $fromYear;
                                                    $search['rule_' . $i . '_operator'] = 0;
                                                    $search['rule_' . $i . '']          = "year";
                                                    ++$i;
                                                }
                                                if ($toYear) {
                                                    $search['rule_' . $i . '_input']    = $toYear;
                                                    $search['rule_' . $i . '_operator'] = 1;
                                                    $search['rule_' . $i . '']          = "year";
                                                    ++$i;
                                                }

                                                $query  = new Search(null, 'album');
                                                $albums = $query->run($search);
                                            }
                                        } else {
                                            if ($type == "byGenre") {
                                                $genre = self::check_parameter($input, 'genre');

                                                $tag_id = Tag::tag_exists($genre);
                                                if ($tag_id) {
                                                    $albums = Tag::get_tag_objects('album', $tag_id, $size, $offset);
                                                }
                                            } else {
                                                $r            = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_GENERIC, "Invalid list type: " . scrub_out($type));
                                                $errorOccured = true;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!$errorOccured) {
            Subsonic_XML_Data::addAlbumList($r, $albums, $elementName);
        }
        self::apiOutput($input, $r);
    }

    /**
     * getAlbumList2
     * See getAlbumList.
     */
    public static function getalbumlist2($input)
    {
        self::check_version($input, "1.7.0");
        self::getAlbumList($input, "albumList2");
    }

    /**
     * getRandomSongs
     * Get random songs matching the given criteria.
     * Takes the optional size, genre, fromYear, toYear and music folder id in parameters.
     */
    public static function getrandomsongs($input)
    {
        self::check_version($input, "1.2.0");

        $size = $input['size'];
        if (!$size) {
            $size = 10;
        }
        $genre         = $input['genre'];
        $fromYear      = $input['fromYear'];
        $toYear        = $input['toYear'];
        $musicFolderId = $input['musicFolderId'];

        $search           = array();
        $search['limit']  = $size;
        $search['random'] = $size;
        $search['type']   = "song";
        $i                = 0;
        if ($genre) {
            $search['rule_' . $i . '_input']    = $genre;
            $search['rule_' . $i . '_operator'] = 0;
            $search['rule_' . $i . '']          = "tag";
            ++$i;
        }
        if ($fromYear) {
            $search['rule_' . $i . '_input']    = $fromYear;
            $search['rule_' . $i . '_operator'] = 0;
            $search['rule_' . $i . '']          = "year";
            ++$i;
        }
        if ($toYear) {
            $search['rule_' . $i . '_input']    = $toYear;
            $search['rule_' . $i . '_operator'] = 1;
            $search['rule_' . $i . '']          = "year";
            ++$i;
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
                    $finput   = intval($musicFolderId);
                    $operator = 0;
                    $ftype    = "catalog";
                }
            }
            $search['rule_' . $i . '_input']    = $finput;
            $search['rule_' . $i . '_operator'] = $operator;
            $search['rule_' . $i . '']          = $ftype;
            ++$i;
        }
        if ($i > 0) {
            $songs = Random::advanced("song", $search);
        } else {
            $songs = Random::get_default($size);
        }

        $r = Subsonic_XML_Data::createSuccessResponse();
        Subsonic_XML_Data::addRandomSongs($r, $songs);
        self::apiOutput($input, $r);
    }

    /**
     * getSong
     * Get details for a song
     * Takes the song id in parameter.
     */
    public static function getsong($input)
    {
        self::check_version($input, "1.7.0");

        $songid = self::check_parameter($input, 'id');
        $r      = Subsonic_XML_Data::createSuccessResponse();
        $song   = new Song(Subsonic_XML_Data::getAmpacheId($songid));
        Subsonic_XML_Data::addSong($r, $song);
        self::apiOutput($input, $r);
    }

    /**
     * getSongsByGenre
     * Get songs in a given genre.
     * Takes the genre with optional count and offset in parameters.
     */
    public static function getsongsbygenre($input)
    {
        self::check_version($input, "1.9.0");

        $genre  = self::check_parameter($input, 'genre');
        $count  = $input['count'];
        $offset = $input['offset'];

        $tag = Tag::construct_from_name($genre);
        if ($tag->id) {
            $songs = Tag::get_tag_objects("song", $tag->id, $count, $offset);
        } else {
            $songs = array();
        }
        $r = Subsonic_XML_Data::createSuccessResponse();
        Subsonic_XML_Data::addSongsByGenre($r, $songs);
        self::apiOutput($input, $r);
    }

    /**
     * getNowPlaying
     * Get what is currently being played by all users.
     * Takes no parameter.
     */
    public static function getnowplaying($input)
    {
        self::check_version($input);

        $data = Stream::get_now_playing();
        $r    = Subsonic_XML_Data::createSuccessResponse();
        Subsonic_XML_Data::addNowPlaying($r, $data);
        self::apiOutput($input, $r);
    }

    /**
     * search2
     * Get albums, artists and songs matching the given criteria.
     * Takes query with optional artist count, artist offset, album count, album offset, song count and song offset in parameters.
     */
    public static function search2($input, $elementName="searchResult2")
    {
        self::check_version($input, "1.2.0");

        $query = self::check_parameter($input, 'query');

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

        $r = Subsonic_XML_Data::createSuccessResponse();
        Subsonic_XML_Data::addSearchResult($r, $artists, $albums, $songs, $elementName);
        self::apiOutput($input, $r);
    }

    /**
     * search3
     * See search2.
     */
    public static function search3($input)
    {
        self::check_version($input, "1.7.0");
        self::search2($input, "searchResult3");
    }

    /**
     * getPlaylists
     * Get all playlists a user is allowed to play.
     * Takes optional user in parameter.
     */
    public static function getplaylists($input)
    {
        self::check_version($input);

        $r        = Subsonic_XML_Data::createSuccessResponse();
        $username = $input['username'];

        // Don't allow playlist listing for another user
        if (empty($username) || $username == $GLOBALS['user']->username) {
            Subsonic_XML_Data::addPlaylists($r, Playlist::get_playlists(), Search::get_searches());
        } else {
            $user = User::get_from_username($username);
            if ($user->id) {
                Subsonic_XML_Data::addPlaylists($r, Playlist::get_users($user->id));
            } else {
                Subsonic_XML_Data::addPlaylists($r, array());
            }
        }
        self::apiOutput($input, $r);
    }

    /**
     * getPlaylist
     * Get the list of files in a saved playlist.
     * Takes the playlist id in parameters.
     */
    public static function getplaylist($input)
    {
        self::check_version($input);

        $playlistid = self::check_parameter($input, 'id');

        $r = Subsonic_XML_Data::createSuccessResponse();
        if (Subsonic_XML_Data::isSmartPlaylist($playlistid)) {
            $playlist = new Search(Subsonic_XML_Data::getAmpacheId($playlistid), 'song');
            Subsonic_XML_Data::addSmartPlaylist($r, $playlist, true);
        } else {
            $playlist = new Playlist($playlistid);
            Subsonic_XML_Data::addPlaylist($r, $playlist, true);
        }
        self::apiOutput($input, $r);
    }

    /**
     * createPlaylist
     * Create (or updates) a playlist.
     * Takes playlist id in parameter if updating, name in parameter if creating and a list of song id for the playlist.
     */
    public static function createplaylist($input)
    {
        self::check_version($input, "1.2.0");

        $playlistId = $input['playlistId'];
        $name       = $input['name'];
        $songId     = $input['songId'];

        if ($playlistId) {
            self::_updatePlaylist($playlistId, $name, $songId);
            $r = Subsonic_XML_Data::createSuccessResponse();
        } else {
            if (!empty($name)) {
                $playlistId = Playlist::create($name, 'private');
                if (count($songId) > 0) {
                    self::_updatePlaylist($playlistId, "", $songId);
                }
                $r = Subsonic_XML_Data::createSuccessResponse();
            } else {
                $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_MISSINGPARAM);
            }
        }
        self::apiOutput($input, $r);
    }

    private static function _updatePlaylist($id, $name, $songsIdToAdd = array(), $songIndexToRemove = array(), $public = true)
    {
        $playlist = new Playlist($id);

        $newdata            = array();
        $newdata['name']    = (!empty($name)) ? $name : $playlist->name;
        $newdata['pl_type'] = ($public) ? "public" : "private";
        $playlist->update($newdata);

        if ($songsIdToAdd) {
            if (!is_array($songsIdToAdd)) {
                $songsIdToAdd = array($songsIdToAdd);
            }
            if (count($songsIdToAdd) > 0) {
                for ($i = 0; $i < count($songsIdToAdd); ++$i) {
                    $songsIdToAdd[$i] = Subsonic_XML_Data::getAmpacheId($songsIdToAdd[$i]);
                }
                $playlist->add_songs($songsIdToAdd);
            }
        }

        if ($songIndexToRemove) {
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
        self::check_version($input, "1.7.0");

        $playlistId = self::check_parameter($input, 'playlistId');

        $name    = $input['name'];
        $comment = $input['comment'];   // Not supported.
        $public  = ($input['public'] === "true");

        if (!Subsonic_XML_Data::isSmartPlaylist($playlistId)) {
            $songIdToAdd       = $input['songIdToAdd'];
            $songIndexToRemove = $input['songIndexToRemove'];

            self::_updatePlaylist(Subsonic_XML_Data::getAmpacheId($playlistId), $name, $songIdToAdd, $songIndexToRemove, $public);

            $r = Subsonic_XML_Data::createSuccessResponse();
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, 'Cannot edit a smart playlist.');
        }
        self::apiOutput($input, $r);
    }

    /**
     * deletePlaylist
     * Delete a saved playlist.
     * Takes playlist id in parameter.
     */
    public static function deleteplaylist($input)
    {
        self::check_version($input, "1.2.0");

        $playlistId = self::check_parameter($input, 'id');

        if (Subsonic_XML_Data::isSmartPlaylist($playlistId)) {
            $playlist = new Search(Subsonic_XML_Data::getAmpacheId($playlistId), 'song');
            $playlist->delete();
        } else {
            $playlist = new Playlist(Subsonic_XML_Data::getAmpacheId($playlistId));
            $playlist->delete();
        }

        $r = Subsonic_XML_Data::createSuccessResponse();
        self::apiOutput($input, $r);
    }

    /**
     * stream
     * Streams a given media file.
     * Takes the file id in parameter with optional max bit rate, file format, time offset, size and estimate content length option.
     */
    public static function stream($input)
    {
        self::check_version($input, "1.0.0", true);

        $fileid = self::check_parameter($input, 'id', true);

        $maxBitRate            = $input['maxBitRate'];
        $format                = $input['format']; // mp3, flv or raw
        $timeOffset            = $input['timeOffset'];
        $size                  = $input['size']; // For video streaming. Not supported.
        $estimateContentLength = $input['estimateContentLength']; // Force content-length guessing if transcode

        $params = '&client=' . rawurlencode($input['c']) . '&noscrobble=1';
        if ($estimateContentLength == 'true') {
            $params .= '&content_length=required';
        }
        if ($format && $format != "raw") {
            $params .= '&transcode_to=' . $format;
        }
        if ($maxBitRate) {
            $params .= '&bitrate=' . $maxBitRate;
        }
        if ($timeOffset) {
            $params .= '&frame=' . $timeOffset;
        }

        $url = '';
        if (Subsonic_XML_Data::isVideo($fileid)) {
            $url = Video::play_url(Subsonic_XML_Data::getAmpacheId($fileid), $params, 'api', function_exists('curl_version'));
        } elseif (Subsonic_XML_Data::isSong($fileid)) {
            $url = Song::play_url(Subsonic_XML_Data::getAmpacheId($fileid), $params, 'api', function_exists('curl_version'));
        } elseif (Subsonic_XML_Data::isPodcastEp($fileid)) {
            $url = Podcast_Episode::play_url(Subsonic_XML_Data::getAmpacheId($fileid), $params, 'api', function_exists('curl_version'));
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
        self::check_version($input, "1.0.0", true);

        $fileid = self::check_parameter($input, 'id', true);

        $url = Song::play_url(Subsonic_XML_Data::getAmpacheId($fileid), '&action=download' . '&client=' . rawurlencode($input['c']) . '&noscrobble=1', 'api', function_exists('curl_version'));
        self::follow_stream($url);
    }

    /**
     * hls
     * Create an HLS playlist.
     * Takes the file id in parameter with optional max bit rate.
     */
    public static function hls($input)
    {
        self::check_version($input, "1.7.0", true);

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
        self::check_version($input, "1.0.0", true);

        $id   = self::check_parameter($input, 'id', true);
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
        }

        header("Access-Control-Allow-Origin: *");
        if ($art != null) {
            $art->get_db();
            if ($size && AmpConfig::get('resize_images')) {
                $dim           = array();
                $dim['width']  = $size;
                $dim['height'] = $size;
                $thumb         = $art->get_thumb($dim);
                if ($thumb) {
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
        self::check_version($input, "1.6.0");

        $id     = self::check_parameter($input, 'id');
        $rating = $input['rating'];

        $robj = null;
        if (Subsonic_XML_Data::isArtist($id)) {
            $robj = new Rating(Subsonic_XML_Data::getAmpacheId($id), "artist");
        } else {
            if (Subsonic_XML_Data::isAlbum($id)) {
                $robj = new Rating(Subsonic_XML_Data::getAmpacheId($id), "album");
            } else {
                if (Subsonic_XML_Data::isSong($id)) {
                    $robj = new Rating(Subsonic_XML_Data::getAmpacheId($id), "song");
                }
            }
        }

        if ($robj != null) {
            $robj->set_rating($rating);

            $r = Subsonic_XML_Data::createSuccessResponse();
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND, "Media not found.");
        }

        self::apiOutput($input, $r);
    }

    /**
     * getStarred
     * Get starred songs, albums and artists.
     * Takes no parameter.
     * Not supported.
     */
    public static function getstarred($input, $elementName="starred")
    {
        self::check_version($input, "1.7.0");

        $r = Subsonic_XML_Data::createSuccessResponse();
        Subsonic_XML_Data::addStarred($r, Userflag::get_latest('artist', null, 99999), Userflag::get_latest('album', null, 99999), Userflag::get_latest('song', null, 99999), $elementName);
        self::apiOutput($input, $r);
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
        self::check_version($input, "1.7.0");

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
        self::check_version($input, "1.7.0");

        self::_setStar($input, false);
    }

    private static function _setStar($input, $star)
    {
        $id       = $input['id'];
        $albumId  = $input['albumId'];
        $artistId = $input['artistId'];

        // Normalize all in one array
        $ids = array();

        $r = Subsonic_XML_Data::createSuccessResponse();
        if ($id) {
            if (!is_array($id)) {
                $id = array($id);
            }
            foreach ($id as $i) {
                $aid = Subsonic_XML_Data::getAmpacheId($i);
                if (Subsonic_XML_Data::isArtist($i)) {
                    $type = 'artist';
                } else {
                    if (Subsonic_XML_Data::isAlbum($i)) {
                        $type = 'album';
                    } else {
                        if (Subsonic_XML_Data::isSong($i)) {
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
                foreach ($albumId as $i) {
                    $aid   = Subsonic_XML_Data::getAmpacheId($i);
                    $ids[] = array('id' => $aid, 'album');
                }
            } else {
                if ($artistId) {
                    if (!is_array($artistId)) {
                        $artistId = array($artistId);
                    }
                    foreach ($artistId as $i) {
                        $aid   = Subsonic_XML_Data::getAmpacheId($i);
                        $ids[] = array('id' => $aid, 'artist');
                    }
                } else {
                    $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_MISSINGPARAM);
                }
            }
        }

        foreach ($ids as $i) {
            $flag = new Userflag($i['id'], $i['type']);
            $flag->set_flag($star);
        }
        self::apiOutput($input, $r);
    }

    /**
     * getUser
     * Get details about a given user.
     * Takes the username in parameter.
     * Not supported.
     */
    public static function getuser($input)
    {
        self::check_version($input, "1.3.0");

        $username = self::check_parameter($input, 'username');

        if ($GLOBALS['user']->access >= 100 || $GLOBALS['user']->username == $username) {
            $r = Subsonic_XML_Data::createSuccessResponse();
            if ($GLOBALS['user']->username == $username) {
                $user = $GLOBALS['user'];
            } else {
                $user = User::get_from_username($username);
            }
            Subsonic_XML_Data::addUser($r, $user);
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, $GLOBALS['user']->username . ' is not authorized to get details for other users.');
        }
        self::apiOutput($input, $r);
    }

    /**
     * getUsers
     * Get details about a given user.
     * Takes no parameter.
     * Not supported.
     */
    public static function getusers($input)
    {
        self::check_version($input, "1.7.0");

        if ($GLOBALS['user']->access >= 100) {
            $r     = Subsonic_XML_Data::createSuccessResponse();
            $users = User::get_valid_users();
            Subsonic_XML_Data::addUsers($r, $users);
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, $GLOBALS['user']->username . ' is not authorized to get details for other users.');
        }
        self::apiOutput($input, $r);
    }

    /**
     * getAvatar
     * Return the user avatar in bytes.
     */
    public static function getavatar($input)
    {
        $username = self::check_parameter($input, 'username');

        $r = null;
        if ($GLOBALS['user']->access >= 100 || $GLOBALS['user']->username == $username) {
            if ($GLOBALS['user']->username == $username) {
                $user = $GLOBALS['user'];
            } else {
                $user = User::get_from_username($username);
            }

            if ($user !== null) {
                $avatar = $user->get_avatar(true);
                if (isset($avatar['url']) && !empty($avatar['url'])) {
                    $request = Requests::get($avatar['url'], array(), Core::requests_options());
                    header("Content-Type: " . $request->headers['Content-Type']);
                    echo $request->body;
                }
            } else {
                $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
            }
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED, $GLOBALS['user']->username . ' is not authorized to get avatar for other users.');
        }

        if ($r != null) {
            self::apiOutput($input, $r);
        }
    }

    /**
     * getInternetRadioStations
     * Get all internet radio stations
     * Takes no parameter.
     */
    public static function getinternetradiostations($input)
    {
        self::check_version($input, "1.9.0");

        $r      = Subsonic_XML_Data::createSuccessResponse();
        $radios = Live_Stream::get_all_radios();
        Subsonic_XML_Data::addRadios($r, $radios);
        self::apiOutput($input, $r);
    }

    /**
     * getShares
     * Get information about shared media this user is allowed to manage.
     * Takes no parameter.
     */
    public static function getshares($input)
    {
        self::check_version($input, "1.6.0");

        $r      = Subsonic_XML_Data::createSuccessResponse();
        $shares = Share::get_share_list();
        Subsonic_XML_Data::addShares($r, $shares);
        self::apiOutput($input, $r);
    }

    /**
     * createShare
     * Create a public url that can be used by anyone to stream media.
     * Takes the file id with optional description and expires parameters.
     */
    public static function createshare($input)
    {
        self::check_version($input, "1.6.0");

        $id          = self::check_parameter($input, 'id');
        $description = $input['description'];

        if (AmpConfig::get('share')) {
            if (isset($input['expires'])) {
                $expires = $input['expires'];
                // Parse as a string to work on 32-bit computers
                if (strlen($expires) > 3) {
                    $expires = intval(substr($expires, 0, - 3));
                }
                $expire_days = round(($expires - time()) / 86400, 0, PHP_ROUND_HALF_EVEN);
            } else {
                $expire_days = AmpConfig::get('share_expire');
            }

            $object_id = Subsonic_XML_Data::getAmpacheId($id);
            if (Subsonic_XML_Data::isAlbum($id)) {
                $object_type = 'album';
            } else {
                if (Subsonic_XML_Data::isSong($id)) {
                    $object_type = 'song';
                }
            }

            if (!empty($object_type)) {
                $r        = Subsonic_XML_Data::createSuccessResponse();
                $shares   = array();
                $shares[] = Share::create_share($object_type, $object_id, true, Access::check_function('download'), $expire_days, Share::generate_secret(), 0, $description);
                Subsonic_XML_Data::addShares($r, $shares);
            } else {
                $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
            }
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED);
        }
        self::apiOutput($input, $r);
    }

    /**
     * deleteShare
     * Delete an existing share.
     * Takes the share id to delete in parameters.
     */
    public static function deleteshare($input)
    {
        self::check_version($input, "1.6.0");

        $id = self::check_parameter($input, 'id');

        if (AmpConfig::get('share')) {
            if (Share::delete_share($id)) {
                $r = Subsonic_XML_Data::createSuccessResponse();
            } else {
                $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
            }
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED);
        }
        self::apiOutput($input, $r);
    }

    /**
     * updateShare
     * Update the description and/or expiration date for an existing share.
     * Takes the share id to update with optional description and expires parameters.
     * Not supported.
     */
    public static function updateshare($input)
    {
        self::check_version($input, "1.6.0");

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
                        $expires = intval(substr($expires, 0, - 3));
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
                    $r = Subsonic_XML_Data::createSuccessResponse();
                } else {
                    $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED);
                }
            } else {
                $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
            }
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED);
        }

        self::apiOutput($input, $r);
    }

    /**
     * createUser
     * Create a new user.
     * Takes the username, password and email with optional roles in parameters.
     */
    public static function createuser($input)
    {
        self::check_version($input, "1.1.0");

        $username          = self::check_parameter($input, 'username');
        $password          = self::check_parameter($input, 'password');
        $email             = self::check_parameter($input, 'email');
        $ldapAuthenticated = $input['ldapAuthenticated'];
        $adminRole         = ($input['adminRole'] == 'true');
        //$settingsRole = $input['settingsRole'];
        //$streamRole = $input['streamRole'];
        //$jukeboxRole = $input['jukeboxRole'];
        $downloadRole = ($input['downloadRole'] == 'true');
        $uploadRole   = ($input['uploadRole'] == 'true');
        //$playlistRole = $input['playlistRole'];
        $coverArtRole = ($input['coverArtRole'] == 'true');
        //$commentRole = $input['commentRole'];
        //$podcastRole = $input['podcastRole'];
        $shareRole = ($input['shareRole'] == 'true');

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
                    Preference::update('download', $user_id, '1');
                }
                if ($uploadRole) {
                    Preference::update('allow_upload', $user_id, '1');
                }
                if ($shareRole) {
                    Preference::update('share', $user_id, '1');
                }
                $r = Subsonic_XML_Data::createSuccessResponse();
            } else {
                $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
            }
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED);
        }

        self::apiOutput($input, $r);
    }

    /**
     * deleteUser
     * Delete an existing user.
     * Takes the username in parameter.
     */
    public static function deleteuser($input)
    {
        self::check_version($input, "1.3.0");

        $username = self::check_parameter($input, 'username');
        if (Access::check('interface', 100)) {
            $user = User::get_from_username($username);
            if ($user->id) {
                $user->delete();
                $r = Subsonic_XML_Data::createSuccessResponse();
            } else {
                $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
            }
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED);
        }

        self::apiOutput($input, $r);
    }

    /**
     * changePassword
     * Change the password of an existing user.
     * Takes the username with new password in parameters.
     */
    public static function changepassword($input)
    {
        self::check_version($input, "1.1.0");

        $username = self::check_parameter($input, 'username');
        $password = self::check_parameter($input, 'password');
        $password = self::decrypt_password($password);

        if ($GLOBALS['user']->username == $username || Access::check('interface', 100)) {
            $user = User::get_from_username($username);
            if ($user->id) {
                $user->update_password($password);
                $r = Subsonic_XML_Data::createSuccessResponse();
            } else {
                $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
            }
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED);
        }
        self::apiOutput($input, $r);
    }

    /**
     * jukeboxControl
     * Control the jukebox.
     * Takes the action with optional index, offset, song id and volume gain in parameters.
     * Not supported.
     */
    public static function jukeboxcontrol($input)
    {
        self::check_version($input, "1.2.0");
        $action = self::check_parameter($input, 'action');
        $id     = $input['id'];
        $gain   = $input['gain'];

        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        debug_event('subsonic', 'Using Localplay controller: ' . AmpConfig::get('localplay_controller'), 5);
        $localplay = new Localplay(AmpConfig::get('localplay_controller'));

        if ($localplay->connect()) {
            $ret = false;
            switch ($action) {
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
                        debug_event('subsonic', 'Skip with offset is not supported on JukeboxControl.', 5);
                    } else {
                        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_MISSINGPARAM);
                    }
                    break;
                case 'set':
                    $localplay->delete_all();
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
                            } elseif (Subsonic_XML_Data::isVideo($i)) {
                                $url = Song::generic_play_url('video', Subsonic_XML_Data::getAmpacheId($i), '', 'api');
                            }

                            if ($url) {
                                debug_event('subsonic', 'Adding ' . $url, 5);
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
                        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_MISSINGPARAM);
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
                $r = Subsonic_XML_Data::createSuccessResponse();
                if ($action == 'get') {
                    Subsonic_XML_Data::addJukeboxPlaylist($r, $localplay);
                } else {
                    Subsonic_XML_Data::createJukeboxStatus($r, $localplay);
                }
            }
        }

        self::apiOutput($input, $r);
    }

    /**
     * scrobble
     * Scrobbles a given music file on last.fm.
     * Takes the file id with optional time and submission parameters.
     */
    public static function scrobble($input)
    {
        self::check_version($input, "1.5.0");

        $id         = self::check_parameter($input, 'id');
        $submission = $input['submission'];
        //$time = $input['time'];

        if ($submission === 'false' || $submission === '0') {
            $r = Subsonic_XML_Data::createSuccessResponse();
            self::apiOutput($input, $r);
        } else {
            if (!is_array($id)) {
                $rid   = array();
                $rid[] = $id;
                $id    = $rid;
            }

            foreach ($id as $i) {
                $aid = Subsonic_XML_Data::getAmpacheId($i);
                if (Subsonic_XML_Data::isVideo($i)) {
                    $type = 'video';
                } else {
                    $type = 'song';
                }

                $media = new $type($aid);
                $media->format();
                User::save_mediaplay($GLOBALS['user'], $media);
            }

            $r = Subsonic_XML_Data::createSuccessResponse();
            self::apiOutput($input, $r);
        }
    }

    /**
     * getLyrics
     * Searches and returns lyrics for a given song.
     * Takes the optional artist and title in parameters.
     */
    public static function getlyrics($input)
    {
        self::check_version($input, "1.2.0");

        $artist = $input['artist'];
        $title  = $input['title'];

        if (!$artist && !$title) {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_MISSINGPARAM);
        } else {
            $search           = array();
            $search['limit']  = 1;
            $search['offset'] = 0;
            $search['type']   = "song";

            $i = 0;
            if ($artist) {
                $search['rule_' . $i . '_input']    = $artist;
                $search['rule_' . $i . '_operator'] = 5;
                $search['rule_' . $i . '']          = "artist";
                ++$i;
            }
            if ($title) {
                $search['rule_' . $i . '_input']    = $title;
                $search['rule_' . $i . '_operator'] = 5;
                $search['rule_' . $i . '']          = "title";
                ++$i;
            }

            $query = new Search(null, 'song');
            $songs = $query->run($search);

            $r = Subsonic_XML_Data::createSuccessResponse();
            if (count($songs) > 0) {
                Subsonic_XML_Data::addLyrics($r, $artist, $title, $songs[0]);
            }
        }

        self::apiOutput($input, $r);
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
            $r         = Subsonic_XML_Data::createSuccessResponse();
            Subsonic_XML_Data::addArtistInfo($r, $info, $similars);
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        }

        self::apiOutput($input, $r);
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
        $id    = self::check_parameter($input, 'id');
        $count = $input['count'] ?: 50;

        $songs = null;
        if (Subsonic_XML_Data::isArtist($id)) {
            // TODO: support similar songs for artists
        } elseif (Subsonic_XML_Data::isAlbum($id)) {
            // TODO: support similar songs for albums
        } elseif (Subsonic_XML_Data::isSong($id)) {
            if (AmpConfig::get('show_similar')) {
                $songs = Recommendation::get_songs_like(Subsonic_XML_Data::getAmpacheId($id));
            }
        }

        if ($songs === null) {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        } else {
            $r = Subsonic_XML_Data::createSuccessResponse();
            Subsonic_XML_Data::addSimilarSongs($r, $songs);
        }

        self::apiOutput($input, $r);
    }

    /**
     * getSimilarSongs2
     * See getSimilarSongs.
     */
    public static function getsimilarsongs2($input)
    {
        return self::getsimilarsongs($input);
    }

    /**
     * getPodcasts
     * Get all podcast channels.
     * Takes the optional includeEpisodes and channel id in parameters
     */
    public static function getpodcasts($input)
    {
        self::check_version($input, "1.6.0");
        $id              = $input['id'];
        $includeEpisodes = isset($input['includeEpisodes']) ? $input['includeEpisodes'] : true;

        if (AmpConfig::get('podcast')) {
            if ($id) {
                $podcast = new Podcast(Subsonic_XML_Data::getAmpacheId($id));
                if ($podcast->id) {
                    $r = Subsonic_XML_Data::createSuccessResponse();
                    Subsonic_XML_Data::addPodcasts($r, array($podcast), $includeEpisodes);
                } else {
                    $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
                }
            } else {
                $podcasts = Catalog::get_podcasts();
                $r        = Subsonic_XML_Data::createSuccessResponse();
                Subsonic_XML_Data::addPodcasts($r, $podcasts, $includeEpisodes);
            }
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED);
        }
        self::apiOutput($input, $r);
    }
    
    /**
     * getNewestPodcasts
     * Get the most recently published podcast episodes.
     * Takes the optional count in parameters
     */
    public static function getnewestpodcasts($input)
    {
        self::check_version($input, "1.13.0");
        $count = $input['count'] ?: 20;

        if (AmpConfig::get('podcast')) {
            $r        = Subsonic_XML_Data::createSuccessResponse();
            $episodes = Catalog::get_newest_podcasts($count);
            Subsonic_XML_Data::addNewestPodcastEpisodes($r, $episodes);
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED);
        }
        self::apiOutput($input, $r);
    }

    /**
     * refreshPodcasts
     * Request the server to check for new podcast episodes.
     * Takes no parameters.
     */
    public static function refreshpodcasts($input)
    {
        self::check_version($input, "1.9.0");

        if (AmpConfig::get('podcast') && Access::check('interface', 75)) {
            $podcasts = Catalog::get_podcasts();
            foreach ($podcasts as $podcast) {
                $podcast->sync_episodes(true);
            }
            $r = Subsonic_XML_Data::createSuccessResponse();
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED);
        }
        self::apiOutput($input, $r);
    }

    /**
     * createPodcastChannel
     * Add a new podcast channel.
     * Takes the podcast url in parameter.
     */
    public static function createpodcastchannel($input)
    {
        self::check_version($input, "1.9.0");
        $url = self::check_parameter($input, 'url');
        
        if (AmpConfig::get('podcast') && Access::check('interface', 75)) {
            $catalogs = Catalog::get_catalogs('podcast');
            if (count($catalogs) > 0) {
                $data            = array();
                $data['feed']    = $url;
                $data['catalog'] = $catalogs[0];
                if (Podcast::create($data)) {
                    $r = Subsonic_XML_Data::createSuccessResponse();
                } else {
                    $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_GENERIC);
                }
            } else {
                $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED);
            }
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED);
        }
        self::apiOutput($input, $r);
    }

    /**
     * deletePodcastChannel
     * Delete an existing podcast channel
     * Takes the podcast id in parameter.
     */
    public static function deletepodcastchannel($input)
    {
        self::check_version($input, "1.9.0");
        $id = self::check_parameter($input, 'id');
        
        if (AmpConfig::get('podcast') && Access::check('interface', 75)) {
            $podcast = new Podcast(Subsonic_XML_Data::getAmpacheId($id));
            if ($podcast->id) {
                if ($podcast->remove()) {
                    $r = Subsonic_XML_Data::createSuccessResponse();
                } else {
                    $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_GENERIC);
                }
            } else {
                $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
            }
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED);
        }
        self::apiOutput($input, $r);
    }

    /**
     * deletePodcastEpisode
     * Delete a podcast episode
     * Takes the podcast episode id in parameter.
     */
    public static function deletepodcastepisode($input)
    {
        self::check_version($input, "1.9.0");
        $id = self::check_parameter($input, 'id');
        
        if (AmpConfig::get('podcast') && Access::check('interface', 75)) {
            $episode = new Podcast_Episode(Subsonic_XML_Data::getAmpacheId($id));
            if ($episode->id) {
                if ($episode->remove()) {
                    $r = Subsonic_XML_Data::createSuccessResponse();
                } else {
                    $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_GENERIC);
                }
            } else {
                $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
            }
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED);
        }
        self::apiOutput($input, $r);
    }

    /**
     * downloadPodcastEpisode
     * Request the server to download a podcast episode
     * Takes the podcast episode id in parameter.
     */
    public static function downloadpodcastepisode($input)
    {
        self::check_version($input, "1.9.0");
        $id = self::check_parameter($input, 'id');
        
        if (AmpConfig::get('podcast') && Access::check('interface', 75)) {
            $episode = new Podcast_Episode(Subsonic_XML_Data::getAmpacheId($id));
            if ($episode->id) {
                $episode->gather();
                $r = Subsonic_XML_Data::createSuccessResponse();
            } else {
                $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
            }
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_UNAUTHORIZED);
        }
        self::apiOutput($input, $r);
    }
    
    /**
     * getBookmarks
     * Get all user bookmarks.
     * Takes no parameter.
     * Not supported.
     */
    public static function getbookmarks($input)
    {
        self::check_version($input, "1.9.0");

        $r         = Subsonic_XML_Data::createSuccessResponse();
        $bookmarks = Bookmark::get_bookmarks();
        Subsonic_XML_Data::addBookmarks($r, $bookmarks);
        self::apiOutput($input, $r);
    }

    /**
     * createBookmark
     * Creates or updates a bookmark.
     * Takes the file id and position with optional comment in parameters.
     * Not supported.
     */
    public static function createbookmark($input)
    {
        self::check_version($input, "1.9.0");
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
                    'object_id' =>  Subsonic_XML_Data::getAmpacheId($id),
                    'object_type' => $type,
                    'comment' => $comment,
                    'position' => $position
                ));
            }
            $r = Subsonic_XML_Data::createSuccessResponse();
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        }
        self::apiOutput($input, $r);
    }

    /**
     * deleteBookmark
     * Delete an existing bookmark.
     * Takes the file id in parameter.
     * Not supported.
     */
    public static function deletebookmark($input)
    {
        self::check_version($input, "1.9.0");
        $id   = self::check_parameter($input, 'id');
        $type = Subsonic_XML_Data::getAmpacheType($id);
        
        $bookmark = new Bookmark(Subsonic_XML_Data::getAmpacheId($id), $type);
        if ($bookmark->id) {
            $bookmark->remove();
            $r = Subsonic_XML_Data::createSuccessResponse();
        } else {
            $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        }
        self::apiOutput($input, $r);
    }
    
    /****   CURRENT UNSUPPORTED FUNCTIONS   ****/

    /**
     * getChatMessages
     * Get the current chat messages.
     * Takes no parameter.
     * Not supported.
     */
    public static function getchatmessages($input)
    {
        self::check_version($input, "1.2.0");

        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        self::apiOutput($input, $r);
    }

    /**
     * addChatMessages
     * Add a message to the chat.
     * Takes the message in parameter.
     * Not supported.
     */
    public static function addchatmessages($input)
    {
        self::check_version($input, "1.2.0");

        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        self::apiOutput($input, $r);
    }
    
    /**
     * getPlayQueue
     * Geturns the state of the play queue for the authenticated user.
     * Takes no parameter.
     * Not supported.
     */
    public static function getplayqueue($input)
    {
        self::check_version($input, "1.12.0");

        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        self::apiOutput($input, $r);
    }
    
    /**
     * savePlayQueue
     * Save the state of the play queue for the authenticated user.
     * Takes multiple song id in parameter with optional current id playing sond and position.
     * Not supported.
     */
    public static function saveplayqueue($input)
    {
        self::check_version($input, "1.12.0");

        $r = Subsonic_XML_Data::createError(Subsonic_XML_Data::SSERROR_DATA_NOTFOUND);
        self::apiOutput($input, $r);
    }
}
