<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

/**
 * Plex Class
 *
 * This class wrap Ampache to Plex API library functions. See http://wiki.plexapp.com/index.php/HTTP_API
 * These are all static calls.
 *
 */
class Plex_Api
{
    /**
     * constructor
     * This really isn't anything to do here, so it's private
     */
    private function __construct()
    {
    }

    public static function setHeader($f)
    {
        header("HTTP/1.0 200 OK", true, 200);
        header("Connection: close", true);
        header("X-Plex-Protocol: 1.0");

        header_remove("x-powered-by");

        if (strtolower($f) == "xml") {
            header("Cache-Control: no-cache", true);
            header("Content-type: text/xml; charset=" . AmpConfig::get('site_charset'), true);
        } elseif (substr(strtolower($f), 0, 6) == "image/") {
            header("Cache-Control: public, max-age=604800", true);
            header("Content-type: " . $f, true);
        } else {
            header("Content-type: " . $f, true);
        }
    }

    public static function apiOutput($string)
    {
        ob_start('ob_gzhandler');
        echo $string;
        ob_end_flush();
        header("X-Plex-Compressed-Content-Length: " . ob_get_length());
        header("X-Plex-Original-Content-Length: " . strlen($string));
    }

    public static function createError($code)
    {
        $error = "";
        switch ($code) {
            case 404:
                $error = "Not Found";
                break;
        }
        header("Content-type: text/html", true);
        header("HTTP/1.0 ". $code . " " . $error, true, $code);

        $html = "<html><head><title>" . $error . "</title></head><body><h1>" . $code . " " . $error . "</h1></body></html>";
        self::apiOutput($html);
        exit();
    }
    
    public static function validateMyPlex($myplex_username, $myplex_password, $clientUDID)
    {
        $options = array(
            CURLOPT_USERPWD => $myplex_username . ':' . $myplex_password,
            //CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_POST => true,
        );
        
        $headers = array(
            'Content-Length: 0'
        );
        
        $req = array(
            'action' => 'users/sign_in.xml',
            'udid' => $clientUDID,
        );
        
        $xml = self::myPlexRequest($req, $options, $headers);
        return $xml['authenticationToken'];
    }
    
    public static function registerMyPlex($clientUDID, $authtoken)
    {
        $headers = array (
            
        );
        
        $req = array(
            'action' => 'servers.xml?auth_token=' . $authtoken,
            'udid' => $clientUDID,
        );
        
        $xml = self::myPlexRequest($req, array(), $headers, true);
        print_r($xml);
        exit;
    }
    
    protected static function myPlexRequest($req, $curlopts = array(), $headers = array(), $debug = false)
    {
        $server = 'http://' . $req['address'] . ':' . $req['port'];
        $allheaders = array(
            'X-Plex-Client-Identifier: ' . $req['udid'],
            'Content-length: 0',
            'X-Plex-Product: Plex Media Server',
            'X-Plex-Version: ' . Plex_XML_Data::getPlexVersion(),
            'X-Plex-Platform: ' . PHP_OS,
            'X-Plex-Client-Platform: ' . PHP_OS,
            'X-Plex-Protocol: 1.0',
            'X-Plex-Device: Ampache',
            'X-Plex-Provides: server',
            'Origin: ' . $server,
            'Referer: ' . $server . '/web/index.html',
        );
        $allheaders = array_merge($allheaders, $headers);
        
        $url = 'https://my.plexapp.com/' . $req['action'];

        $options = array(
            CURLOPT_HEADER => $debug,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => $allheaders,
        );
        $options += $curlopts;
        
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $r = curl_exec($ch);
        curl_close($ch);
        if ($debug) print_r($r);
        return simplexml_load_string($r);
    }

    public static function root()
    {
        $r = Plex_XML_Data::createContainer();
        Plex_XML_Data::setRootContent($r, Catalog::get_catalogs());
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function library($params)
    {
        $r = Plex_XML_Data::createLibContainer();
        Plex_XML_Data::setLibraryContent($r);
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function system($params)
    {
        $r = Plex_XML_Data::createSysContainer();
        Plex_XML_Data::setSystemContent($r);
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function clients($params)
    {
        $r = Plex_XML_Data::createContainer();
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function channels($params)
    {
        $r = Plex_XML_Data::createContainer();
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function photos($params)
    {
        $r = Plex_XML_Data::createPluginContainer();
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function photo($params)
    {
        if (count($params) == 2) {
            if ($params[0] == ':' && $params[1] == 'transcode') {

                $width = $_REQUEST['width'];
                $height = $_REQUEST['height'];
                $url = $_REQUEST['url'];

                if ($width && $height && $url) {
                    $request = Requests::get($url);
                    if ($request->status_code == 200) {
                        $mime = $request->headers['content-type'];
                        self::setHeader($mime);
                        $art = new Art(0);
                        $art->raw = $request->body;
                        $thumb = $art->generate_thumb($art->raw, array('width' => $width, 'height' => $height), $mime);
                        echo $thumb['thumb'];
                        exit();
                    }
                }
            }
        }
    }

    public static function music($params)
    {
        $r = Plex_XML_Data::createPluginContainer();
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function video($params)
    {
        $r = Plex_XML_Data::createPluginContainer();
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function applications($params)
    {
        $r = Plex_XML_Data::createPluginContainer();
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function library_sections($params)
    {
        $r = Plex_XML_Data::createLibContainer();
        $n = count($params);
        if ($n == 0) {
            Plex_XML_Data::setSections($r, Catalog::get_catalogs());
        } else {
            $key = $params[0];
            $catalog = Catalog::create_from_id($key);
            if (!$catalog) {
                self::createError(404);
            }
            if ($n == 1) {
                Plex_XML_Data::setSectionContent($r, $catalog);
            } elseif ($n == 2) {
                $view = $params[1];
                if ($view == "all") {
                    Plex_XML_Data::setSectionAll($r, $catalog);
                } elseif ($view == "albums") {
                    Plex_XML_Data::setSectionAlbums($r, $catalog);
                } elseif ($view == "recentlyadded") {
                    Plex_XML_Data::setCustomSectionView($r, $catalog, Stats::get_recent('album', 25, 0, $key));
                }
            }
        }

        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function library_metadata($params)
    {
        $r = Plex_XML_Data::createLibContainer();
        $n = count($params);
        if ($n > 0) {
            $key = $params[0];

            $id = Plex_XML_Data::getAmpacheId($key);

            if ($n == 1) {
                if (Plex_XML_Data::isArtist($key)) {
                    $artist = new Artist($id);
                    $artist->format();
                    Plex_XML_Data::addArtist($r, $artist);
                } elseif (Plex_XML_Data::isAlbum($key)) {
                    $album = new Album($id);
                    $album->format();
                    Plex_XML_Data::addAlbum($r, $album);
                }
            } else {
                $subact = $params[1];
                if ($subact == "children") {
                    if (Plex_XML_Data::isArtist($key)) {
                        $artist = new Artist($id);
                        $artist->format();
                        Plex_XML_Data::setArtistRoot($r, $artist);
                    } else if (Plex_XML_Data::isAlbum($key)) {
                        $album = new Album($id);
                        $album->format();
                        Plex_XML_Data::setAlbumRoot($r, $album);
                    } else if (Plex_XML_Data::isTrack($key)) {
                        /*$song = new Song($id);
                        $song->format();
                        Plex_XML_Data::setSongRoot($r, $song);*/
                    }
                } elseif ($subact == "thumb") {
                    if ($n == 3) {
                        // Ignore thumb id as we can only have 1 thumb
                        $art = null;
                        if (Plex_XML_Data::isArtist($key)) {
                            $art = new Art($id, "artist");
                        } else if (Plex_XML_Data::isAlbum($key)) {
                            $art = new Art($id, "album");
                        } else if (Plex_XML_Data::isSong($key)) {
                            $art = new Art($id, "song");
                        }

                        if ($art != null) {
                            $art->get_db();

                            if (!$size) {
                                self::setHeader($art->raw_mime);
                                echo $art->raw;
                            } else {
                                $dim = array();
                                $dim['width'] = $size;
                                $dim['height'] = $size;
                                $thumb = $art->get_thumb($dim);
                                self::setHeader($art->thumb_mime);
                                echo $thumb['thumb'];
                            }
                            exit();
                        }
                    }
                }
            }
        }
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function library_parts($params)
    {
        $n = count($params);

        if ($n == 2) {
            $key = $params[0];
            $file = $params[1];

            $id = Plex_XML_Data::getAmpacheId($key);
            $song = new Song($id);
            if ($song->id) {
                $url = Song::play_url($id, '&client=Plex');
                header("Location: " . $url);
            } else {
                self::createError(404);
            }
        }
    }

    public static function library_recentlyadded($params)
    {
        $data = array();
        $data['album'] = Stats::get_newest('album', 25);
        $r = Plex_XML_Data::createLibContainer();
        Plex_XML_Data::setCustomView($r, $data);
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function library_ondeck($params)
    {
        $data = array();
        $data['album'] = Stats::get_recent('album', 25);
        $r = Plex_XML_Data::createLibContainer();
        Plex_XML_Data::setCustomView($r, $data);
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function system_library_sections($params)
    {
        $r = Plex_XML_Data::createSysContainer();
        Plex_XML_Data::setSysSections($r, Catalog::get_catalogs());
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }
}
