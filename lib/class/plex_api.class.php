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
 * Plex Class
 *
 * This class wrap Ampache to Plex API library functions. See http://wiki.plexapp.com/index.php/HTTP_API
 * These are all static calls.
 *
 * @SuppressWarnings("unused")
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
        $local      = false;
        $local_auth = AmpConfig::get('plex_local_auth');
        if (!$local_auth) {
            $ip    = $_SERVER['REMOTE_ADDR'];
            $lip   = ip2long($ip);
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

        $headers      = apache_request_headers();
        $myplex_token = $headers['X-Plex-Token'];
        if (empty($myplex_token)) {
            $myplex_token = $_REQUEST['X-Plex-Token'];
        }

        if (!$isLocal) {
            $match_users = AmpConfig::get('plex_match_email');

            $myplex_username = $headers['X-Plex-Username'];

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
            $username = "";
            $email    = trim(Session::read((string) $myplex_token));

            if (empty($email)) {
                $createSession = true;
                $xml           = self::get_server_authtokens();
                $validToken    = false;
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
                    debug_event('Access Control', 'Error Attempted to use Plex with Access Control turned off and plex/ampache link enabled.', '3');
                    self::createError(401);
                }

                if (empty($email)) {
                    $xml = self::get_users_account();
                    if ((string) $xml->username == $username) {
                        $email = (string) $xml->email;
                    } else {
                        $xml = self::get_server_friends();
                        foreach ($xml->User as $xuser) {
                            if ((string) $xuser['username'] == $username) {
                                $email = (string) $xuser['email'];
                            }
                        }
                    }
                }

                if (!empty($email)) {
                    $user = User::get_from_email($email);
                }
                if (!isset($user) || !$user->id) {
                    debug_event('Access Denied', 'Unable to get an Ampache user match for email ' . $email, '3');
                    self::createError(401);
                } else {
                    $username = $user->username;
                    if (!Access::check_network('init-api', $username, 5)) {
                        debug_event('Access Denied', 'Unauthorized access attempt to Plex [' . $_SERVER['REMOTE_ADDR'] . ']', '3');
                        self::createError(401);
                    } else {
                        $GLOBALS['user'] = $user;
                        $GLOBALS['user']->load_playlist();
                    }
                }
            } else {
                $email    = $username;
                $username = null;

                $GLOBALS['user'] = new User();
                $GLOBALS['user']->load_playlist();
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
        } else {
            AmpConfig::set('cookie_path', '/', true);
            $sid = $_COOKIE[AmpConfig::get('session_name')];
            if (!$sid) {
                $sid = $myplex_token;
                if ($sid) {
                    session_id($sid);
                    Session::create_cookie();
                }
            }
            if (!empty($sid) && Session::exists('api', $sid)) {
                Session::check();
                $GLOBALS['user'] = User::get_from_username($_SESSION['userdata']['username']);
            } else {
                $GLOBALS['user'] = new User();
                $data            = array(
                    'type' => 'api',
                    'sid' => $sid,
                );
                Session::create($data);
                Session::check();
            }

            $GLOBALS['user']->load_playlist();
        }
    }

    protected static function check_access($level)
    {
        if (self::is_local()) {
            // Promote all users as content manager if local
            if ($GLOBALS['user']->access < 50) {
                $GLOBALS['user']->access = 50;
            }
        }

        if (($GLOBALS['user']->access < $level || AmpConfig::get('demo_mode'))) {
            debug_event('plex', 'User ' . $GLOBALS['user']->username . ' is unauthorized to complete the action.', '3');
            self::createError(401);
            exit;
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
            //$filter = explode(',', $ach);
            $filter     = null;
            $headers    = self::getPlexHeaders(true, $filter);
            $headerkeys = array();
            foreach ($headers as $key => $value) {
                $headerkeys[] = $key;
            }
            header('Access-Control-Allow-Headers: ' . implode(',', $headerkeys));
        }

        if ($acm || $ach) {
            header('Access-Control-Max-Age: 1209600');
        } else {
            header('Access-Control-Expose-Headers: Location');
        }
    }

    public static function apiOutputXml($xml)
    {
        // Format xml output
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $dom->formatOutput = true;
        self::apiOutput($dom->saveXML());
    }

    public static function apiOutput($string)
    {
        if ($_SERVER['REQUEST_METHOD'] != 'OPTIONS') {
            ob_clean();
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
            //header("Content-length: 0", true);
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
        header("HTTP/1.0 " . $code . " " . $error, true, $code);

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

        $res = self::myPlexRequest($action, $options, $headers);
        ;
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
        $headers = array(
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
        $headers  = array();
        $action   = 'devices/' . Plex_XML_Data::getMachineIdentifier() . '?Connection[][uri]=' . Plex_XML_Data::getServerUri() . '&X-Plex-Token=' . $authtoken;
        $curlopts = array(
            CURLOPT_CUSTOMREQUEST => "PUT"
        );

        return self::myPlexRequest($action, $curlopts, $headers);
    }

    public static function unregisterMyPlex($authtoken)
    {
        $headers = array(
            'Content-Type: text/xml'
        );
        $action   = 'servers/' . Plex_XML_Data::getMachineIdentifier() . '.xml?auth_token=' . $authtoken;
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

    public static $request_headers = array();
    public static function request_output_header($ch, $header)
    {
        self::$request_headers[] = $header;
        return strlen($header);
    }

    public static function replay_header($ch, $header)
    {
        $rheader = trim($header);
        $rhpart  = explode(':', $rheader);
        if (!empty($rheader) && count($rhpart) > 1) {
            if ($rhpart[0] != "Transfer-Encoding") {
                header($rheader);
            }
        }
        return strlen($header);
    }

    public static function replay_body($ch, $data)
    {
        if (connection_status() != 0) {
            curl_close($ch);
            debug_event('plex', 'Stream cancelled.', 5);
            exit;
        }

        echo $data;
        ob_flush();

        return strlen($data);
    }

    protected static function myPlexRequest($action, $curlopts = array(), $headers = array(), $proxy = false)
    {
        $server     = Plex_XML_Data::getServerUri();
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
        $r             = curl_exec($ch);
        $res           = array();
        $res['status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $res['headers'] = self::$request_headers;
        $res['raw']     = $r;
        try {
            $res['xml'] = simplexml_load_string($r);
        } catch (Exception $e) {
            // If exception, wrong data returned (Plex API changes?)
        }
        return $res;
    }

    public static function root()
    {
        $r = Plex_XML_Data::createContainer();
        Plex_XML_Data::setRootContent($r, Catalog::get_catalogs());
        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
    }

    public static function library($params)
    {
        $r = Plex_XML_Data::createLibContainer();
        Plex_XML_Data::setLibraryContent($r);
        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
    }

    public static function system($params)
    {
        $r = Plex_XML_Data::createSysContainer();
        Plex_XML_Data::setSystemContent($r);
        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
    }

    public static function clients($params)
    {
        $r = Plex_XML_Data::createContainer();
        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
    }

    public static function channels($params)
    {
        $r = Plex_XML_Data::createPluginContainer();
        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
    }

    public static function photos($params)
    {
        $r = Plex_XML_Data::createPluginContainer();
        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
    }

    public static function photo($params)
    {
        if (count($params) == 2) {
            if ($params[0] == ':' && $params[1] == 'transcode') {
                $width  = $_REQUEST['width'];
                $height = $_REQUEST['height'];
                $url    = $_REQUEST['url'];

                // Replace 32400 request port with the real listening port
                // *** To `Plex Inc`: ***
                // Please allow listening port server configuration for your Plex server
                // and fix your clients to not request resources so hard-coded on 127.0.0.1:32400.
                // May be ok on Apple & UPnP world but that's really ugly for a server...
                // Yes, it's a little hack but it works.
                $localrs = "http://127.0.0.1:32400/";
                $options = Core::requests_options();
                if (strpos($url, $localrs) !== false) {
                    $options = array(); // In case proxy is set, no proxy for local addresses
                    $url     = "http://127.0.0.1:" . Plex_XML_Data::getServerPort() . "/" . substr($url, strlen($localrs));
                }

                if ($width && $height && $url) {
                    $request = Requests::get($url, array(), $options);
                    if ($request->status_code == 200) {
                        ob_clean();
                        $mime = $request->headers['content-type'];
                        self::setHeader($mime);
                        $art      = new Art(0);
                        $art->raw = $request->body;
                        $thumb    = $art->generate_thumb($art->raw, array('width' => $width, 'height' => $height), $mime);
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
                    $url    = $_REQUEST['url'];
                    $br     = $_REQUEST['audioBitrate'];
                    if (preg_match("/\/parts\/([0-9]+)\//", $url, $matches)) {
                        $song_id = Plex_XML_Data::getAmpacheId($matches[1]);
                    }
                } elseif (count($params) == 4 && $params[2] == 'universal') {
                    $format = pathinfo($params[3], PATHINFO_EXTENSION);
                    $path   = $_REQUEST['path'];
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

                    $url = Song::play_url($song_id, $urlparams, 'api', true);
                    self::stream_url($url);
                }
            }
        }
    }

    public static function video($params)
    {
        if (count($params) > 1 && $params[0] == ':' && $params[1] == 'transcode') {
            array_shift($params);
            array_shift($params);
            self::video_transcode($params);
        } else {
            $r = Plex_XML_Data::createPluginContainer();
            Plex_XML_Data::setContainerSize($r);
            self::apiOutputXml($r->asXML());
        }
    }

    private static function video_transcode($params)
    {
        $n = count($params);
        if ($n == 2) {
            $transcode_to = $params[0];
            $action       = $params[1];
            $id           = '';

            $path     = $_GET['path'];
            $protocol = $_GET['protocol'];
            $offset   = $_GET['offset'];

            // Transcode arguments.
            $videoQuality    = $_GET['videoQuality'];
            $videoResolution = $_GET['videoResolution'];
            $maxVideoBitrate = $_GET['maxVideoBitrate'];
            $subtitleSize    = $_GET['subtitleSize'];
            $audioBoost      = $_GET['audioBoost'];

            $additional_params = '&vsettings=';
            if ($videoResolution) {
                $additional_params .= 'resolution-' . $videoResolution . '-';
            }
            if ($maxVideoBitrate) {
                $additional_params .= 'maxbitrate-' . $maxVideoBitrate . '-';
            }
            if ($videoQuality) {
                $additional_params .= 'quality-' . $videoQuality . '-';
            }

            if ($offset) {
                $additional_params .= '&frame=' . $offset;
            }

            // Several Media and Part per Video is not supported
            //$mediaIndex = $_GET['mediaIndex'];
            //$partIndex = $_GET['partIndex'];

            // What's that?
            //$fastSeek = $_GET['fastSeek'];
            //$directPlay = $_GET['directPlay'];
            //$directStream = $_GET['directStream'];

            $uriroot = '/library/metadata/';
            $upos    = strrpos($path, $uriroot);
            if ($upos !== false) {
                $id = substr($path, $upos + strlen($uriroot));
            }

            $session = $_GET['session'];
            if ($action == "stop") {
                // We should kill associated transcode session here
            } elseif (strpos($action, "start") === 0) {
                if (empty($protocol) || $protocol == "hls") {
                    header('Content-Type: application/vnd.apple.mpegurl');

                    $videoResolution = $_GET['videoResolution'];
                    $maxVideoBitrate = $_GET['maxVideoBitrate'];
                    if (!$maxVideoBitrate) {
                        $maxVideoBitrate = 8175;
                    }

                    echo "#EXTM3U\n";
                    echo "#EXT-X-STREAM-INF:PROGRAM-ID=1";
                    if ($maxVideoBitrate) {
                        echo ",BANDWIDTH=" . ($maxVideoBitrate * 1000);
                    }
                    if ($videoResolution) {
                        echo ",RESOLUTION=" . $videoResolution;
                    }
                    echo "\n";
                    echo "hls.m3u8?" . substr($_SERVER['QUERY_STRING'], strpos($_SERVER['QUERY_STRING'], '&') + 1);
                } elseif ($protocol == "http") {
                    $url = null;
                    if ($transcode_to == 'universal') {
                        $additional_params .= '&transcode_to=webm';
                        if (AmpConfig::get('encode_args_webm')) {
                            debug_event('plex', 'Universal transcoder requested but `webm` transcode settings not configured. This will probably failed.', 3);
                        }
                    }

                    if ($id) {
                        if (Plex_XML_Data::isSong($id)) {
                            $url = Song::play_url(Plex_XML_Data::getAmpacheId($id), $additional_params, 'api');
                        } elseif (Plex_XML_Data::isVideo($id)) {
                            $url = Video::play_url(Plex_XML_Data::getAmpacheId($id), $additional_params, 'api');
                        }

                        if ($url) {
                            self::stream_url($url);
                        }
                    }
                }
            } elseif ($action == "hls.m3u8") {
                if ($id) {
                    $pl = new Stream_Playlist();

                    $media = null;
                    if (Plex_XML_Data::isSong($id)) {
                        $media = array(
                            'object_type' => 'song',
                            'object_id' => Plex_XML_Data::getAmpacheId($id),
                        );
                    } elseif (Plex_XML_Data::isVideo($id)) {
                        $media = array(
                            'object_type' => 'video',
                            'object_id' => Plex_XML_Data::getAmpacheId($id),
                        );
                    }

                    if ($media != null) {
                        $pl->add(array($media), $additional_params);
                    }

                    $pl->generate_playlist('hls');
                }
            }
        }
    }

    public static function applications($params)
    {
        $r = Plex_XML_Data::createPluginContainer();
        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
    }

    public static function library_sections($params)
    {
        $r = Plex_XML_Data::createLibContainer();
        $n = count($params);
        if ($n == 0) {
            Plex_XML_Data::setSections($r, Catalog::get_catalogs());
        } else {
            $key     = $params[0];
            $catalog = Catalog::create_from_id($key);
            if (!$catalog) {
                self::createError(404);
            }
            if ($n == 1) {
                Plex_XML_Data::setSectionContent($r, $catalog);
            } elseif ($n == 2) {
                $view   = $params[1];
                $gtypes = $catalog->get_gather_types();
                if ($gtypes[0] == 'music') {
                    $type = 'artist';
                    if ($_GET['type']) {
                        $type = Plex_XML_Data::getAmpacheType($_GET['type']);
                    }

                    if ($view == "all") {
                        switch ($type) {
                            case 'artist':
                                Plex_XML_Data::setSectionAll_Artists($r, $catalog);
                                break;
                            case 'album':
                                Plex_XML_Data::setSectionAll_Albums($r, $catalog);
                                break;
                        }
                    } elseif ($view == "albums") {
                        Plex_XML_Data::setSectionAlbums($r, $catalog);
                    } elseif ($view == "recentlyadded") {
                        Plex_XML_Data::setCustomSectionView($r, $catalog, Stats::get_recent('album', 25, $key));
                    } elseif ($view == "genre") {
                        Plex_XML_Data::setSectionTags($r, $catalog, 'song');
                    }
                } elseif ($gtypes[0] == "tvshow") {
                    $type = 'tvshow';
                    if ($_GET['type']) {
                        $type = Plex_XML_Data::getAmpacheType($_GET['type']);
                    }

                    if ($view == "all") {
                        switch ($type) {
                            case 'tvshow':
                                Plex_XML_Data::setSectionAll_TVShows($r, $catalog);
                                break;
                            case 'season':
                                Plex_XML_Data::setSectionAll_Seasons($r, $catalog);
                                break;
                            case 'episode':
                                Plex_XML_Data::setSectionAll_Episodes($r, $catalog);
                                break;
                        }
                    } elseif ($view == "recentlyadded") {
                        Plex_XML_Data::setCustomSectionView($r, $catalog, Stats::get_recent('tvshow_episode', 25, $key));
                    } elseif ($view == "genre") {
                        Plex_XML_Data::setSectionTags($r, $catalog, 'video');
                    }
                } elseif ($gtypes[0] == "movie") {
                    $type = 'tvshow';
                    if ($_GET['type']) {
                        $type = Plex_XML_Data::getAmpacheType($_GET['type']);
                    }

                    if ($view == "all") {
                        Plex_XML_Data::setSectionAll_Movies($r, $catalog);
                    } elseif ($view == "recentlyadded") {
                        Plex_XML_Data::setCustomSectionView($r, $catalog, Stats::get_recent('movie', 25, $key));
                    } elseif ($view == "genre") {
                        Plex_XML_Data::setSectionTags($r, $catalog, 'video');
                    }
                }
            }
        }

        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
    }

    public static function library_metadata($params)
    {
        $r     = Plex_XML_Data::createLibContainer();
        $n     = count($params);
        $litem = null;

        $createMode = ($_SERVER['REQUEST_METHOD'] == 'POST');
        $editMode   = ($_SERVER['REQUEST_METHOD'] == 'PUT');

        if ($n > 0) {
            $key = $params[0];

            $id = Plex_XML_Data::getAmpacheId($key);
            if ($editMode) {
                self::check_access(50);
            }

            if ($n == 1) {
                // Should we check that files still exists here?
                $checkFiles = $_REQUEST['checkFiles'];
                $extra      = $_REQUEST['includeExtra'];

                if (Plex_XML_Data::isArtist($key)) {
                    $litem = new Artist($id);
                    $litem->format();
                    if ($editMode) {
                        $dmap = array(
                            'title' => 'name',
                            'summary' => null,
                        );
                        $litem->update(self::get_data_from_map($dmap));
                    }
                    Plex_XML_Data::addArtist($r, $litem);
                } elseif (Plex_XML_Data::isAlbum($key)) {
                    $litem = new Album($id);
                    $litem->format();
                    if ($editMode) {
                        $dmap = array(
                            'title' => 'name',
                            'year' => null,
                        );
                        $litem->update(self::get_data_from_map($dmap));
                    }
                    Plex_XML_Data::addAlbum($r, $litem);
                } elseif (Plex_XML_Data::isTrack($key)) {
                    $litem = new Song($id);
                    $litem->format();
                    if ($editMode) {
                        $dmap = array(
                            'title' => null,
                        );
                        $litem->update(self::get_data_from_map($dmap));
                    }
                    Plex_XML_Data::addSong($r, $litem);
                } elseif (Plex_XML_Data::isTVShow($key)) {
                    $litem = new TVShow($id);
                    $litem->format();
                    if ($editMode) {
                        $dmap = array(
                            'title' => 'name',
                            'year' => null,
                            'summary' => null,
                        );
                        $litem->update(self::get_data_from_map($dmap));
                    }
                    Plex_XML_Data::addTVShow($r, $litem);
                } elseif (Plex_XML_Data::isTVShowSeason($key)) {
                    $litem = new TVShow_Season($id);
                    $litem->format();
                    Plex_XML_Data::addTVShowSeason($r, $litem);
                } elseif (Plex_XML_Data::isVideo($key)) {
                    $litem = Video::create_from_id($id);

                    if ($editMode) {
                        $dmap = array(
                            'title' => null,
                            'year' => null,
                            'originallyAvailableAt' => 'release_date',
                            'originalTitle' => 'original_name',
                            'summary' => null,
                        );
                        $litem->update(self::get_data_from_map($dmap));
                    }
                    $litem->format();

                    $subtype = strtolower(get_class($litem));
                    if ($subtype == 'tvshow_episode') {
                        Plex_XML_Data::addEpisode($r, $litem, true);
                    } elseif ($subtype == 'movie') {
                        Plex_XML_Data::addMovie($r, $litem, true);
                    }
                } elseif (Plex_XML_Data::isPlaylist($key)) {
                    $litem = new Playlist($id);
                    $litem->format();
                    if ($editMode) {
                        $dmap = array(
                            'title' => 'name',
                        );
                        $litem->update(self::get_data_from_map($dmap));
                    }
                    Plex_XML_Data::addPlaylist($r, $litem);
                }
            } else {
                $subact = $params[1];
                if ($subact == "children") {
                    if (Plex_XML_Data::isArtist($key)) {
                        $litem = new Artist($id);
                        $litem->format();
                        Plex_XML_Data::setArtistRoot($r, $litem);
                    } else {
                        if (Plex_XML_Data::isAlbum($key)) {
                            $litem = new Album($id);
                            $litem->format();
                            Plex_XML_Data::setAlbumRoot($r, $litem);
                        } else {
                            if (Plex_XML_Data::isTVShow($key)) {
                                $litem = new TVShow($id);
                                $litem->format();
                                Plex_XML_Data::setTVShowRoot($r, $litem);
                            } else {
                                if (Plex_XML_Data::isTVShowSeason($key)) {
                                    $litem = new TVShow_Season($id);
                                    $litem->format();
                                    Plex_XML_Data::setTVShowSeasonRoot($r, $litem);
                                }
                            }
                        }
                    }
                } elseif ($subact == "thumbs" || $subact == "posters" || $subact == "arts" || $subact == 'backgrounds') {
                    $kind = Plex_XML_Data::getPhotoKind($subact);
                    if ($createMode) {
                        // Upload art
                        $litem = Plex_XML_Data::createLibraryItem($key);
                        if ($litem != null) {
                            $uri = Plex_XML_Data::getMetadataUri($key) . '/' . Plex_XML_Data::getPhotoPlexKind($kind) . '/' . $key;
                            if (is_a($litem, 'video')) {
                                $type = 'video';
                            } else {
                                $type = get_class($litem);
                            }

                            $art = new Art($litem->id, $type, $kind);
                            $raw = file_get_contents("php://input");
                            $art->insert($raw);

                            header('Content-Type: text/html');
                            echo $uri;
                            exit;
                        }
                    }
                    Plex_XML_Data::addPhotos($r, $key, $kind);
                } elseif ($subact == "thumb" || $subact == "poster" || $subact == "art" || $subact == "background") {
                    if ($n == 3) {
                        $kind = Plex_XML_Data::getPhotoKind($subact);
                        // Ignore art id as we can only have 1 thumb
                        $art = null;
                        if (Plex_XML_Data::isArtist($key)) {
                            $art = new Art($id, "artist", $kind);
                        } else {
                            if (Plex_XML_Data::isAlbum($key)) {
                                $art = new Art($id, "album", $kind);
                            } else {
                                if (Plex_XML_Data::isTrack($key)) {
                                    $art = new Art($id, "song", $kind);
                                } else {
                                    if (Plex_XML_Data::isTVShow($key)) {
                                        $art = new Art($id, "tvshow", $kind);
                                    } else {
                                        if (Plex_XML_Data::isTVShowSeason($key)) {
                                            $art = new Art($id, "tvshow_season", $kind);
                                        } else {
                                            if (Plex_XML_Data::isVideo($key)) {
                                                $art = new Art($id, "video", $kind);
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        if ($art != null) {
                            $art->get_db();

                            ob_clean();
                            if (!isset($size)) {
                                self::setHeader($art->raw_mime);
                                echo $art->raw;
                            } else {
                                $dim           = array();
                                $dim['width']  = $size;
                                $dim['height'] = $size;
                                $thumb         = $art->get_thumb($dim);
                                self::setHeader($art->thumb_mime);
                                echo $thumb['thumb'];
                            }
                            exit();
                        }
                    }
                }
            }
        }

        if ($litem != null) {
            $catalog_ids = $litem->get_catalogs();
            if (count($catalog_ids) > 0) {
                $catalog = Catalog::create_from_id($catalog_ids[0]);
                Plex_XML_Data::addCatalogIdentity($r, $catalog);
            }
        }

        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
    }

    protected static function stream_url($url)
    {
        set_time_limit(0);
        ob_end_clean();

        $headers    = apache_request_headers();
        $reqheaders = array();
        if (isset($headers['Range'])) {
            $reqheaders[] = "Range: " . $headers['Range'];
        }

        // Curl support, we stream transparently to avoid redirect. Redirect can fail on few clients
        debug_event('plex-api', 'Stream proxy: ' . $url, 5);
        // header("Location: " . $url);

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_HTTPHEADER => $reqheaders,
            CURLOPT_HEADER => false,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_WRITEFUNCTION => array('Plex_Api', 'replay_body'),
            CURLOPT_HEADERFUNCTION => array('Plex_Api', 'replay_header'),
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT => 0
        ));
        if (curl_exec($ch) === false) {
            debug_event('plex-api', 'Curl error: ' . curl_error($ch), 1);
        }
        curl_close($ch);
    }

    public static function library_parts($params)
    {
        $n = count($params);

        if ($n > 0) {
            $key = $params[0];
            if ($n == 2) {
                $file = $params[1];

                $id = Plex_XML_Data::getAmpacheId($key);
                if (Plex_XML_Data::isSong($key)) {
                    $media = new Song($id);
                    if ($media->id) {
                        $url = Song::play_url($id, '', 'api', true);
                        self::stream_url($url);
                    } else {
                        self::createError(404);
                    }
                } elseif (Plex_XML_Data::isVideo($key)) {
                    $media = new Video($id);
                    if ($media->id) {
                        $url = Video::play_url($id, '', 'api', true);
                        self::stream_url($url);
                    } else {
                        self::createError(404);
                    }
                }
            } elseif ($n == 1) {
                if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
                    if (isset($_GET['subtitleStreamID'])) {
                        $lang_code                      = dechex(hex2bin(substr($_GET['subtitleStreamID'], 0, 2)));
                        $_SESSION['iframe']['subtitle'] = $lang_code;
                    }
                }
            }
        }
    }

    public static function library_recentlyadded($params)
    {
        $data          = array();
        $data['album'] = Stats::get_newest('album', 25);
        $r             = Plex_XML_Data::createLibContainer();
        Plex_XML_Data::setCustomView($r, $data);
        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
    }

    public static function library_ondeck($params)
    {
        $data          = array();
        $data['album'] = Stats::get_recent('album', 25);
        $r             = Plex_XML_Data::createLibContainer();
        Plex_XML_Data::setCustomView($r, $data);
        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
    }

    public static function system_library_sections($params)
    {
        $r = Plex_XML_Data::createSysContainer();
        Plex_XML_Data::setSysSections($r, Catalog::get_catalogs());
        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
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
        self::apiOutputXml($r->asXML());
    }

    public static function system_agents($params)
    {
        $r               = Plex_XML_Data::createSysContainer();
        $addcontributors = false;
        $mediaType       = $_REQUEST['mediaType'];
        if (count($params) >= 3 && $params[1] == 'config') {
            $mediaType       = $params[2];
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
        self::apiOutputXml($r->asXML());
    }

    public static function system_agents_contributors($params)
    {
        $mediaType    = $_REQUEST['mediaType'];
        $primaryAgent = $_REQUEST['primaryAgent'];

        $r = Plex_XML_Data::createSysContainer();
        Plex_XML_Data::setAgentsContributors($r, $mediaType, $primaryAgent);
        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
    }

    public static function system_agents_attribution($params)
    {
        $identifier = $_REQUEST['identifier'];

        self::createError(404);
    }

    public static function system_scanners($params)
    {
        if (count($params) > 0) {
            $type = $params[0];
            $r    = Plex_XML_Data::createSysContainer();
            Plex_XML_Data::setScanners($r, $type);
            Plex_XML_Data::setContainerSize($r);
            self::apiOutputXml($r->asXML());
        } else {
            self::createError(404);
        }
    }

    public static function system_appstore($params)
    {
        $r = Plex_XML_Data::createAppStore();
        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
    }

    public static function accounts($params)
    {
        $userid = '';
        if (isset($params[0])) {
            $userid = $params[0];
        }
        // Not supported yet
        if ($userid > 1) {
            self::createError(404);
        }

        $r = Plex_XML_Data::createAccountContainer();
        Plex_XML_Data::setAccounts($r, $userid);
        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
    }

    public static function status($params)
    {
        $r = Plex_XML_Data::createPluginContainer();
        Plex_XML_Data::setStatus($r);
        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
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
        self::apiOutputXml($r->asXML());
    }

    public static function help($params)
    {
        $r = Plex_XML_Data::createPluginContainer();
        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
    }

    public static function plexonline($params)
    {
        $r = Plex_XML_Data::createPluginContainer();
        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
    }

    public static function plugins($params)
    {
        $r = Plex_XML_Data::createPluginContainer();
        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
    }

    public static function services($params)
    {
        $r = Plex_XML_Data::createPluginContainer();
        Plex_XML_Data::setServices($r);
        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
    }

    public static function services_browse($params)
    {
        self::check_access(75);

        $r = Plex_XML_Data::createContainer();
        Plex_XML_Data::setBrowseService($r, $params[0]);
        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
    }

    public static function timeline($params)
    {
        $ratingKey = $_REQUEST['ratingKey'];
        $key       = $_REQUEST['key'];
        $state     = $_REQUEST['state'];
        $time      = $_REQUEST['time'];
        $duration  = $_REQUEST['duration'];

        // Not supported right now (maybe in a future for broadcast?)
        header('Content-Type: text/html');
    }

    public static function rate($params)
    {
        $id         = $_REQUEST['key'];
        $identifier = $_REQUEST['identifier'];
        $rating     = $_REQUEST['rating'];

        if ($identifier == 'com.plexapp.plugins.library') {
            $robj = new Rating(Plex_XML_Data::getAmpacheId($id), Plex_XML_Data::getLibraryItemType($id));
            $robj->set_rating($rating / 2);
        }
    }

    protected static function get_users_account($authtoken='')
    {
        if (empty($authtoken)) {
            $authtoken = Plex_XML_Data::getMyPlexAuthToken();
        }

        $action = 'users/account?auth_token=' . $authtoken;
        $res    = self::myPlexRequest($action);
        return $res['xml'];
    }

    public static function users_account($params)
    {
        $xml = self::get_users_account();
        Plex_XML_Data::setMyPlexSubscription($xml);
        self::apiOutput($xml->asXML());
    }

    /**
     myPlex server function to grant plexpass access dynamically.
     Used for testing purpose only.
     */
    public static function users($params)
    {
        if ($params[0] == 'sign_in.xml') {
            $curlopts = array();
            $headers  = array();
            $res      = self::myPlexRequest('users/sign_in.xml', $curlopts, $headers, true);

            foreach ($res['headers'] as $header) {
                header($header);
            }

            if ($res['status'] == '201') {
                Plex_XML_Data::setMyPlexSubscription($res['xml']);
                self::apiOutput($res['xml']->asXML());
            } else {
                self::createError($res['status']);
            }
        }
    }

    public static function servers($params)
    {
        $r = Plex_XML_Data::createContainer();
        Plex_XML_Data::setLocalServerInfo($r);
        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
    }

    public static function playlists($params)
    {
        $r = Plex_XML_Data::createContainer();
        $n = count($params);

        $createMode = ($_SERVER['REQUEST_METHOD'] == 'POST');
        $editMode   = ($_SERVER['REQUEST_METHOD'] == 'PUT');
        $delMode    = ($_SERVER['REQUEST_METHOD'] == 'DELETE');
        if ($createMode || $editMode || $delMode) {
            self::check_access(50);
        }

        if ($n <= 1) {
            $plid = 0;
            if ($n == 0 && $createMode) {
                // Create a new playlist
                //$type = $_GET['type'];
                $title = $_GET['title'];
                //$smart = $_GET['smart'];
                //$summary = $_GET['summary'];
                $uri = $_GET['uri'];

                $plid     = Playlist::create($title, 'public');
                $playlist = new Playlist($plid);
                $key      = Plex_XML_Data::getKeyFromFullUri($uri);
                $id       = Plex_XML_Data::getKeyFromMetadataUri($key);
                if ($id) {
                    $item   = Plex_XML_Data::createLibraryItem($id);
                    $medias = $item->get_medias();
                    $playlist->add_medias($medias);
                }
                $plid = Plex_XML_Data::getPlaylistId($plid);
            } else {
                if ($n == 1 && $params[0] != "all") {
                    $plid = $params[0];
                }
            }

            if ($plid) {
                if (Plex_XML_Data::isPlaylist($plid)) {
                    $playlist = new Playlist(Plex_XML_Data::getAmpacheId($plid));
                    if ($playlist->id) {
                        if ($delMode) {
                            // Delete playlist
                            $playlist->delete();
                        } else {
                            // Display playlist information
                            Plex_XML_Data::addPlaylist($r, $playlist);
                        }
                    }
                }
            } else {
                // List all playlists
                Plex_XML_Data::setPlaylists($r);
            }
        } elseif ($n >= 2) {
            $plid = $params[0];
            if (Plex_XML_Data::isPlaylist($plid) && $params[1] == "items") {
                $playlist = new Playlist(Plex_XML_Data::getAmpacheId($plid));
                if ($playlist->id) {
                    if ($n == 2) {
                        if ($editMode) {
                            // Add a new item to playlist
                            $uri = $_GET['uri'];
                            $key = Plex_XML_Data::getKeyFromFullUri($uri);
                            $id  = Plex_XML_Data::getKeyFromMetadataUri($key);
                            if ($id) {
                                $item   = Plex_XML_Data::createLibraryItem($id);
                                $medias = $item->get_medias();
                                $playlist->add_medias($medias);
                                Plex_XML_Data::addPlaylist($r, $playlist);
                            }
                        } else {
                            Plex_XML_Data::setPlaylistItems($r, $playlist);
                        }
                    } elseif ($n == 3) {
                        $index = intval($params[2]);
                        if ($delMode) {
                            $playlist->delete_track_number($index);
                            $playlist->regenerate_track_numbers();
                            exit;
                        }
                    }
                }
            }
        }

        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
    }

    public static function playqueues($params)
    {
        $n = count($params);
        $r = Plex_XML_Data::createLibContainer();

        if ($n == 1) {
            $playlistID = $params[0];
            Plex_XML_Data::setTmpPlayQueue($r, $playlistID);
        } else {
            $type       = $_GET['type'];
            $playlistID = $_GET['playlistID'];
            $uri        = $_GET['uri'];
            $key        = $_GET['key'];
            $shuffle    = $_GET['shuffle'];

            Plex_XML_Data::setPlayQueue($r, $type, $playlistID, $uri, $key, $shuffle);
        }

        Plex_XML_Data::setContainerSize($r);
        self::apiOutputXml($r->asXML());
    }

    private static function get_data_from_map($dmap)
    {
        $data = array();

        foreach ($dmap as $key=>$value) {
            if (isset($_GET[$key])) {
                if ($value == null) {
                    $value = $key;
                }

                $data[$value] = $_GET[$key];
            }
        }

        if (isset($_GET['genre'])) {
            $data['edit_tags'] = implode(',', $_GET['genre']);
        }

        return $data;
    }
}
