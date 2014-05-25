<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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

    protected static function is_local()
    {
        $local = false;
        $local_auth = AmpConfig::get('plex_local_auth');
        if (!$local_auth) {
            $ip = $_SERVER['REMOTE_ADDR'];
            $lip = ip2long($ip);
            $rangs = array(
                array('127.0.0.1', '127.0.0.1'),
                array('10.0.0.1', '10.255.255.254'),
                array('172.16.0.1', '172.31.255.254'),
                array('192.168.0.1', '192.168.255.254'),
            );

            foreach ($rangs as $rang) {
                $ld = ip2long($rang[0]);
                $lu = ip2long($rang[1]);
                if ($lip <= $lu && $ld <= $lip) {
                    debug_event('Access Control', 'Local client ip address (' . $ip . '), bypass authentication.', '3');
                    $local = true;
                    break;
                }
            }
        }

        return $local;
    }

    public static function auth_user()
    {
        $isLocal = self::is_local();
        if (!$isLocal) {
            $match_users = AmpConfig::get('plex_match_email');

            $headers = apache_request_headers();
            $myplex_username = $headers['X-Plex-Username'];
            $myplex_token = $headers['X-Plex-Token'];
            if (empty($myplex_token)) {
                $myplex_token = $_REQUEST['X-Plex-Token'];
            }

            if (empty($myplex_token)) {
                // Never fail OPTIONS requests
                if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
                    self::setPlexHeader($headers);
                    exit();
                } else {
                    debug_event('Access Control', 'Authentication token is missing.', '3');
                    self::createError(401);
                }
            }

            $createSession = false;
            Session::gc();
            $email = Session::read((string) $myplex_token);
            if (empty($email)) {
                $createSession = true;
                $xml = self::get_server_authtokens();
                $validToken = false;
                foreach ($xml->access_token as $tk) {
                    if ((string) $tk['token'] == $myplex_token) {
                        $username = (string) $tk['username'];
                        // We should apply filter and access restriction to shared sections only, but that's not easily possible with current Ampache architecture
                        $validToken = true;
                        break;
                    }
                }

                if (!$validToken) {
                    debug_event('Access Control', 'Auth-Token ' . $myplex_token . ' invalid for this server.', '3');
                    self::createError(401);
                }
            }

            // Need to get a match between Plex and Ampache users
            if ($match_users) {
                if (!AmpConfig::get('access_control')) {
                    debug_event('Access Control', 'Error Attempted to use Plex with Access Control turned off and plex/ampache link enabled.','3');
                    self::createError(401);
                }

                if (empty($email)) {
                    $xml = self::get_users_account();
                    if ((string) $xml->username == $username) {
                        $email = (string) $xml->email;
                    } else {
                        $xml = self::get_server_friends();
                        foreach ($xml->user as $xuser) {
                            if ((string) $xml['username'] == $username) {
                                $email = (string) $xml['email'];
                            }
                        }
                    }
                }

                if (!empty($email)) {
                    $user = User::get_from_email($email);
                }
                if (!$user || !$user->id) {
                    debug_event('Access Denied', 'Unable to get an Ampache user match for email ' . $email, '3');
                    self::createError(401);
                }
                $username = $user->username;
                if (!Access::check_network('init-api', $username, 5)) {
                    debug_event('Access Denied', 'Unauthorized access attempt to Plex [' . $_SERVER['REMOTE_ADDR'] . ']', '3');
                    self::createError(401);
                }

                $GLOBALS['user'] = $user;
            } else {
                $email = $username;
                $username = null;
            }

            if ($createSession) {
                // Create an Ampache session from Plex authtoken
                Session::create(array(
                    'type' => 'api',
                    'sid' => $myplex_token,
                    'username' => $username,
                    'value' => $email
                ));
            }
        }
    }

    protected static function check_access($level)
    {
        if (!self::is_local() && $GLOBALS['user']->access < $level) {
            debug_event('plex', 'User ' . $GLOBALS['user']->username . ' is unauthorized to complete the action.', '3');
            self::createError(401);
        }
    }

    public static function setHeader($f)
    {
        header("HTTP/1.1 200 OK", true, 200);
        header("Connection: close", true);

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

    public static function setPlexHeader($reqheaders)
    {
        header("X-Plex-Protocol: 1.0");

        header('Access-Control-Allow-Origin: *');
        $acm = $reqheaders['Access-Control-Request-Method'];
        if ($acm) {
            header('Access-Control-Allow-Methods: POST, GET, OPTIONS, DELETE, PUT, HEAD');
        }
        $ach = $reqheaders['Access-Control-Request-Headers'];
        if ($ach) {
            $headers = self::getPlexHeaders(true, $acl);
            $headerkeys = array();
            foreach ($headers as $key => $value) {
                $headerkeys[] = strtolower($key);
            }
            header('Access-Control-Allow-Headers: ' . implode(',', $headerkeys));
        }

        if ($acm || $ach) {
            header('Access-Control-Max-Age: 1209600');
        } else {
            header('Access-Control-Expose-Headers: Location');
        }
    }
    public static function apiOutput($string)
    {
        if ($_SERVER['REQUEST_METHOD'] != 'OPTIONS') {
            ob_start('ob_gzhandler');
            echo $string;
            ob_end_flush();
            $reqheaders = getallheaders();
            if ($reqheaders['Accept-Encoding']) {
                header("X-Plex-Content-Compressed-Length: " . ob_get_length(), true);
                header("X-Plex-Content-Original-Length: " . strlen($string), true);
            }
        } else {
            header("Content-type: text/plain", true);
            header("Content-length: 0", true);
        }
    }

    public static function createError($code)
    {
        $error = "";
        switch ($code) {
            case 404:
                $error = "Not Found";
                break;

            case 401:
                $error = "Unauthorized";
                break;
        }
        header("Content-type: text/html", true);
        header("HTTP/1.0 ". $code . " " . $error, true, $code);

        $html = "<html><head><title>" . $error . "</title></head><body><h1>" . $code . " " . $error . "</h1></body></html>";
        self::apiOutput($html);
        exit();
    }

    public static function validateMyPlex($myplex_username, $myplex_password)
    {
        $options = array(
            CURLOPT_USERPWD => $myplex_username . ':' . $myplex_password,
            //CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_POST => true,
        );
        $headers = array(
            'Content-Length: 0'
        );
        $action = 'users/sign_in.xml';

        $res = self::myPlexRequest($action, $options, $headers);;
        return $res['xml']['authenticationToken'];
    }

    public static function getPublicIp()
    {
        $action = 'pms/:/ip';

        $res = self::myPlexRequest($action);
        return trim($res['raw']);
    }

    public static function registerMyPlex($authtoken)
    {
        $headers = array (
            'Content-Type: text/xml'
        );
        $action = 'servers.xml?auth_token=' . $authtoken;

        $r = Plex_XML_Data::createContainer();
        Plex_XML_Data::setServerInfo($r, Catalog::get_catalogs());
        Plex_XML_Data::setContainerSize($r);

        $curlopts = array(
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $r->asXML()
        );

        return self::myPlexRequest($action, $curlopts, $headers, true);
    }

    public static function publishDeviceConnection($authtoken)
    {
        $headers = array ();
        $action = 'devices/' . Plex_XML_Data::getMachineIdentifier() . '?Connection[][uri]=' . Plex_XML_Data::getServerUri() . '&X-Plex-Token=' . $authtoken;
        $curlopts = array(
            CURLOPT_CUSTOMREQUEST => "PUT"
        );

        return self::myPlexRequest($action, $curlopts, $headers);
    }

    public static function unregisterMyPlex($authtoken)
    {
        $headers = array (
            'Content-Type: text/xml'
        );
        $action = 'servers/' . Plex_XML_Data::getMachineIdentifier() . '.xml?auth_token=' . $authtoken;
        $curlopts = array(
            CURLOPT_CUSTOMREQUEST => "DELETE"
        );

        return self::myPlexRequest($action, $curlopts, $headers);
    }

    protected static function get_server_authtokens()
    {
        $action = 'servers/' . Plex_XML_Data::getMachineIdentifier() . '/access_tokens.xml?auth_token=' . Plex_XML_Data::getMyPlexAuthToken();

        $res = self::myPlexRequest($action);
        return $res['xml'];
    }

    protected static function get_server_friends()
    {
        $action = 'pms/friends/all?auth_token=' . Plex_XML_Data::getMyPlexAuthToken();

        $res = self::myPlexRequest($action);
        return $res['xml'];
    }

    protected static function getPlexHeaders($private = false, $filters = null)
    {
        $headers = array(
            'X-Plex-Client-Identifier' => Plex_XML_Data::getClientIdentifier(),
            'X-Plex-Product' => 'Plex Media Server',
            'X-Plex-Version' => Plex_XML_Data::getPlexVersion(),
            'X-Plex-Platform' => Plex_XML_Data::getPlexPlatform(),
            'X-Plex-Platform-Version' => Plex_XML_Data::getPlexPlatformVersion(),
            'X-Plex-Client-Platform' => Plex_XML_Data::getPlexPlatform(),
            'X-Plex-Protocol' => 1.0,
            'X-Plex-Device' => 'Ampache',
            'X-Plex-Device-Name' => 'Ampache',
            'X-Plex-Provides' => 'server'
        );

        if ($private) {
            if (Plex_XML_Data::getMyPlexUsername()) {
                $headers['X-Plex-Username'] = Plex_XML_Data::getMyPlexUsername();
            }
            if (Plex_XML_Data::getMyPlexUsername()) {
                $headers['X-Plex-Token'] = Plex_XML_Data::getMyPlexAuthToken();
            }
        }

        if ($filters) {
            $fheaders = array();
            foreach ($headers as $key => $value) {
                if (array_search(strtolower($key), $filters)) {
                    $fheaders[$key] = $value;
                }
            }
            $headers = $fheaders;
        }

        return $headers;
    }

    static $request_headers = array();
    public static function request_output_header($ch, $header)
    {
        self::$request_headers[] = $header;
        return strlen($header);
    }

    public static function replay_header($ch, $header)
    {
        $rheader = trim($header);
        $rhpart = explode(':', $rheader);
        if (!empty($rheader) && count($rhpart) > 1) {
            if ($rhpart[0] != "Transfer-Encoding") {
                header($rheader);
            }
        }
        return strlen($header);
    }

    public static function replay_body($ch, $data)
    {
        echo $data;
        ob_flush();

        return strlen($data);
    }

    protected static function myPlexRequest($action, $curlopts = array(), $headers = array(), $proxy = false)
    {
        $server = Plex_XML_Data::getServerUri();
        $allheaders = array();
        if (!$proxy) {
            $allheadersarr = self::getPlexHeaders();
            foreach ($allheadersarr as $key => $value) {
                $allheaders[] = $key . ': ' . $value;
            }
            $allheaders += array(
                'Origin: ' . $server,
                'Referer: ' . $server . '/web/index.html',
            );

            if (!$curlopts[CURLOPT_POST]) {
                $allheaders[] = 'Content-length: 0';
            }
        }
        $allheaders = array_merge($allheaders, $headers);

        $url = 'https://my.plexapp.com/' . $action;
        debug_event('plex', 'Calling ' . $url, '5');

        $options = array(
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADERFUNCTION => array('Plex_Api', 'request_output_header'),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => $allheaders,
        );
        $options += $curlopts;

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $r = curl_exec($ch);
        $res = array();
        $res['status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $res['headers'] = self::$request_headers;
        $res['raw'] = $r;
        try {
            $res['xml'] = simplexml_load_string($r);
        } catch (Exception $e) { }
        return $res;
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
        $r = Plex_XML_Data::createPluginContainer();
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

                // Replace 32400 request port with the real listening port
                // *** To `Plex Inc`: ***
                // Please allow listening port server configuration for your Plex server
                // and fix your clients to not request resources so hard-coded on 127.0.0.1:32400.
                // May be ok on Apple & UPnP world but that's really ugly for a server...
                // Yes, it's a little hack but it works.
                $localrs = "http://127.0.0.1:32400/";
                if (strpos($url, $localrs) !== false) {
                    $url = "http://127.0.0.1:" . Plex_XML_Data::getServerPort() . "/" . substr($url, strlen($localrs));
                }

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
        if (count($params) > 2) {
            if ($params[0] == ':' && $params[1] == 'transcode') {
                if (count($params) == 3) {
                    $format = $_REQUEST['format'] ?: pathinfo($params[2], PATHINFO_EXTENSION);
                    $url = $_REQUEST['url'];
                    $br = $_REQUEST['audioBitrate'];
                    if (preg_match("/\/parts\/([0-9]+)\//", $url, $matches)) {
                        $song_id = Plex_XML_Data::getAmpacheId($matches[1]);
                    }
                } elseif (count($params) == 4 && $params[2] == 'universal') {
                    $format = pathinfo($params[3], PATHINFO_EXTENSION);
                    $path = $_REQUEST['path'];
                    // Should be the maximal allowed bitrate, not necessary the bitrate used but Ampache doesn't support this kind of option yet
                    $br = $_REQUEST['maxAudioBitrate'];
                    if (preg_match("/\/metadata\/([0-9]+)/", $path, $matches)) {
                        $song_id = Plex_XML_Data::getAmpacheId($matches[1]);
                    }
                }

                if (!empty($format) && !empty($song_id)) {
                    $urlparams = '&transcode_to=' . $format;
                    if (!empty($br)) {
                        $urlparams .= '&bitrate=' . $br;
                    }

                    $url = Song::play_url($song_id, $urlparams);
                    self::stream_url($url);
                }
            }
        }
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
                    Plex_XML_Data::setCustomSectionView($r, $catalog, Stats::get_recent('album', 25, $key));
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
                // Should we check that files still exists here?
                $checkFiles = $_REQUEST['checkFiles'];

                if (Plex_XML_Data::isArtist($key)) {
                    $artist = new Artist($id);
                    $artist->format();
                    Plex_XML_Data::addArtist($r, $artist);
                } elseif (Plex_XML_Data::isAlbum($key)) {
                    $album = new Album($id);
                    $album->format();
                    Plex_XML_Data::addAlbum($r, $album);
                } elseif (Plex_XML_Data::isTrack($key)) {
                    $song = new Song($id);
                    $song->format();
                    Plex_XML_Data::addSong($r, $song);
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

    protected static function stream_url($url)
    {
        // header("Location: " . $url);
        set_time_limit(0);

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_HTTPHEADER => array("User-Agent: Plex"),
            CURLOPT_HEADER => false,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_WRITEFUNCTION => array('Plex_Api', 'replay_body'),
            CURLOPT_HEADERFUNCTION => array('Plex_Api', 'replay_header'),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 0
        ));
        curl_exec($ch);
        curl_close($ch);
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
                $url = Song::play_url($id);
                self::stream_url($url);
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

    public static function manage_frameworks_ekspinner_resources($params)
    {
        // Image file used to 'ping' the server
        if ($params[0] == "small_black_7.png") {
            header("Content-type: image/png", true);
            echo file_get_contents(AmpConfig::get('prefix') . '/plex/resources/small_black_7.png');
            exit;
        }
    }

    public static function myplex_account($params)
    {
        $r = Plex_XML_Data::createMyPlexAccount();
        self::apiOutput($r->asXML());
    }

    public static function system_agents($params)
    {
        $r = Plex_XML_Data::createSysContainer();
        $addcontributors = false;
        $mediaType = $_REQUEST['mediaType'];
        if (count($params) >= 3 && $params[1] == 'config') {
            $mediaType = $params[2];
            $addcontributors = true;
        }

        if ($mediaType) {
            switch ($mediaType) {
                case '1':
                    Plex_XML_Data::setSysMovieAgents($r);
                break;
                case '2':
                    Plex_XML_Data::setSysTVShowAgents($r);
                break;
                case '13':
                    Plex_XML_Data::setSysPhotoAgents($r);
                break;
                case '8':
                    Plex_XML_Data::setSysMusicAgents($r);
                break;
                case '9':
                    Plex_XML_Data::setSysMusicAgents($r, 'Albums');
                break;
                default:
                    self::createError(404);
                break;
            }
        } else {
            Plex_XML_Data::setSysAgents($r);
        }
        if ($addcontributors) {
            Plex_XML_Data::setAgentsContributors($r, $mediaType, 'com.plexapp.agents.none');
        }
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function system_agents_contributors($params)
    {
        $mediaType = $_REQUEST['mediaType'];
        $primaryAgent = $_REQUEST['primaryAgent'];

        $r = Plex_XML_Data::createSysContainer();
        Plex_XML_Data::setAgentsContributors($r, $mediaType, $primaryAgent);
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function system_agents_attribution($params)
    {
        $identifier = $_REQUEST['identifier'];

        self::createError(404);
    }

    public static function system_scanners($params)
    {
        if (count($params) > 0) {
            if ($params[0] == '8' || $params[0] == '9') {
                $r = Plex_XML_Data::createSysContainer();
                Plex_XML_Data::setMusicScanners($r);
                Plex_XML_Data::setContainerSize($r);
                self::apiOutput($r->asXML());
            }
        } else {
            self::createError(404);
        }
    }

    public static function system_appstore($params)
    {
        $r = Plex_XML_Data::createAppStore();
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function accounts($params)
    {
        $userid = '';
        if (isset($params[0])) {
            $userid = $params[0];
        }
        // Not supported yet
        if ($userid > 1) { self::createError(404); }

        $r = Plex_XML_Data::createAccountContainer();
        Plex_XML_Data::setAccounts($r, $userid);
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function status($params)
    {
        $r = Plex_XML_Data::createPluginContainer();
        Plex_XML_Data::setStatus($r);
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function status_sessions($params)
    {
        self::createError(403);
    }

    public static function prefs($params)
    {
        $r = Plex_XML_Data::createContainer();
        Plex_XML_Data::setPrefs($r);
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function help($params)
    {
        $r = Plex_XML_Data::createPluginContainer();
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function plexonline($params)
    {
        $r = Plex_XML_Data::createPluginContainer();
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function plugins($params)
    {
        $r = Plex_XML_Data::createPluginContainer();
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function services($params)
    {
        $r = Plex_XML_Data::createPluginContainer();
        Plex_XML_Data::setServices($r);
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function services_browse($params)
    {
        self::check_access(75);

        $r = Plex_XML_Data::createContainer();
        Plex_XML_Data::setBrowseService($r, $params[0]);
        Plex_XML_Data::setContainerSize($r);
        self::apiOutput($r->asXML());
    }

    public static function timeline($params)
    {
        $ratingKey = $_REQUEST['ratingKey'];
        $key = $_REQUEST['key'];
        $state = $_REQUEST['state'];
        $time = $_REQUEST['time'];
        $duration = $_REQUEST['duration'];

        // Not supported right now (maybe in a future for broadcast?)
        if ($_SERVER['REQUEST_METHOD'] != 'OPTIONS') {
            self::apiOutput('');
        } else {
            self::createError(400);
        }
    }

    public static function rate($params)
    {
        $id = $_REQUEST['key'];
        $identifier = $_REQUEST['identifier'];
        $rating = $_REQUEST['rating'];

        if ($identifier == 'com.plexapp.plugins.library') {
            if (Plex_XML_Data::isArtist($id)) {
                $robj = new Rating(Plex_XML_Data::getAmpacheId($id), "artist");
            } else if (Plex_XML_Data::isAlbum($id)) {
                $robj = new Rating(Plex_XML_Data::getAmpacheId($id), "album");
            } else if (Plex_XML_Data::isTrack($id)) {
                $robj = new Rating(Plex_XML_Data::getAmpacheId($id), "song");
            }

            if ($robj != null) {
                $robj->set_rating($rating / 2);
            }
        }
    }

    protected static function get_users_account($authtoken='')
    {
        if (empty($authtoken)) {
            $authtoken = Plex_XML_Data::getMyPlexAuthToken();
        }

        $action = 'users/account?auth_token=' . $authtoken;
        $res = self::myPlexRequest($action);
        return $res['xml'];
    }
}
