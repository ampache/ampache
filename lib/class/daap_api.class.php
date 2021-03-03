<?php
declare(strict_types=0);
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

/**
 * DAAP Class
 *
 * This class wrap Ampache to DAAP API functions. See https://github.com/andytinycat/daapdocs/blob/master/daapdocs.txt
 * These are all static calls.
 */
class Daap_Api
{
    const AMPACHEID_SMARTPL = 400000000;
    const BASE_LIBRARY      = 0;

    public static $metas = array(
        'dmap.itemid',
        'dmap.itemname',
        'dmap.itemkind',
        'dmap.persistentid',
        'daap.songalbum',
        'daap.songartist',
        'daap.songbitrate',
        'daap.songbeatsperminute',
        'daap.songcomment',
        'daap.songcompilation',
        'daap.songcomposer',
        'daap.songdateadded',
        'daap.songdatemodified',
        'daap.songdisccount',
        'daap.songdiscnumber',
        'daap.songdisabled',
        'daap.songeqpreset',
        'daap.songformat',
        'daap.songgenre',
        'daap.songdescription',
        'daap.songrelativevolume',
        'daap.songsamplerate',
        'daap.songsize',
        'daap.songstarttime',
        'daap.songstoptime',
        'daap.songtime',
        'daap.songtrackcount',
        'daap.songtracknumber',
        'daap.songuserrating',
        'daap.songyear',
        'daap.songdatakind',
        'daap.songdataurl',
        'com.apple.itunes.norm-volume'
    );

    public static $tags = array();

    /**
     * constructor
     * This really isn't anything to do here, so it's private
     */
    private function __construct()
    {
    }

    /**
     * follow_stream
     * @param string $url
     */
    public static function follow_stream($url)
    {
        set_time_limit(0);
        ob_end_clean();
        if (function_exists('curl_version')) {
            $headers      = apache_request_headers();
            $reqheaders   = array();
            $reqheaders[] = "User-Agent: " . urlencode(preg_replace('/[\s\/]+/', '_', $headers['User-Agent']));
            if (isset($headers['Range'])) {
                $reqheaders[] = "Range: " . $headers['Range'];
            }
            // Curl support, we stream transparently to avoid redirect. Redirect can fail on few clients
            $curl = curl_init($url);
            if ($curl) {
                curl_setopt_array($curl, array(
                    CURLOPT_HTTPHEADER => $reqheaders,
                    CURLOPT_HEADER => false,
                    CURLOPT_RETURNTRANSFER => false,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_WRITEFUNCTION => array(
                        'Daap_Api',
                        'output_body'
                    ),
                    CURLOPT_HEADERFUNCTION => array(
                        'Daap_Api',
                        'output_header'
                    ),
                    // Ignore invalid certificate
                    // Default trusted chain is crap anyway and currently no custom CA option
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                    CURLOPT_TIMEOUT => 0
                ));
                curl_exec($curl);
                curl_close($curl);
            }
        } else {
            // Stream media using http redirect if no curl support
            header("Location: " . $url);
        }
    }

    /**
     * @param $curl
     * @param $data
     * @return integer
     */
    public static function output_body($curl, $data)
    {
        echo $data;
        ob_flush();

        return strlen((string) $data);
    }

    /**
     * @param $curl
     * @param $header
     * @return integer
     */
    public static function output_header($curl, $header)
    {
        $rheader = trim((string) $header);
        $rhpart  = explode(':', $rheader);
        if (! empty($rheader) && count($rhpart) > 1) {
            if ($rhpart[0] != "Transfer-Encoding") {
                header($rheader);
            }
        }

        return strlen((string) $header);
    }

    /**
     * server_info (Based on the server_info part of the forkedd-daapd project)
     * @param $input
     */
    public static function server_info($input)
    {
        $output = self::tlv('dmap.status', 200);
        $output .= self::tlv('dmap.protocolversion', '0.2.0.0');
        $output .= self::tlv('dmap.itemname', 'Ampache');
        $output .= self::tlv('daap.protocolversion', '0.3.0.0');
        $output .= self::tlv('daap.supportsextradata', 0); // daap.supportsextradata
        $output .= self::tlv('daap.supportsgroups', 0);
        $output .= self::tlv('daap.aeMQ', 1); // unknown - used by iTunes
        $output .= self::tlv('daap.aeTr', 1); // unknown - used by iTunes
        $output .= self::tlv('daap.aeSL', 1); // unknown - used by iTunes
        $output .= self::tlv('daap.aeSR', 1); // unknown - used by iTunes
        $output .= self::tlv('dmap.supportsedit', 0);

        if (AmpConfig::get('daap_pass')) {
            $output .= self::tlv('dmap.loginrequired', 1);
        } else {
            $output .= self::tlv('dmap.loginrequired', 0);
        }
        $output .= self::tlv('dmap.timeoutinterval', 1800);
        $output .= self::tlv('dmap.supportsautologout', 1);

        $output .= self::tlv('dmap.authenticationmethod', 2); // FIXME im not shre about this value "2"?
        $output .= self::tlv('dmap.supportsupdate', 1);
        $output .= self::tlv('dmap.supportspersistentids', 1); // FIXME im not sure if ampache supports it
        $output .= self::tlv('dmap.supportsextensions', 0);
        $output .= self::tlv('dmap.supportsbrowse', 0);
        $output .= self::tlv('dmap.supportsquery', 0);
        $output .= self::tlv('dmap.supportsindex', 0);
        $output .= self::tlv('dmap.databasescount', 1);

        $output = self::tlv('dmap.serverinforesponse', $output);
        self::apiOutput($output);
    }

    /**
     * content_codes
     * @param $input
     */
    public static function content_codes($input)
    {
        $output = self::tlv('dmap.status', 200);
        foreach (self::$tags as $name => $tag) {
            $entry = self::tlv('dmap.contentcodesname', $name);
            $pcode = str_split($tag['code']);
            $icode = (ord($pcode[0]) << 24) + (ord($pcode[1]) << 16) + (ord($pcode[2]) << 8) + ord($pcode[3]);
            $entry .= self::tlv('dmap.contentcodesnumber', $icode);
            $entry .= self::tlv('dmap.contentcodestype', self::get_type_id($tag['type']));
            $output .= self::tlv('dmap.dictionary', $entry);
        }

        $output = self::tlv('dmap.contentcodesresponse', $output);
        self::apiOutput($output);
    }

    /**
     * login
     * @param $input
     */
    public static function login($input)
    {
        self::check_auth('dmap.loginresponse');

        // Create a new daap session
        $sql = "INSERT INTO `daap_session` (`creationdate`) VALUES (?)";
        Dba::write($sql, array(
            time()
        ));
        $sid = Dba::insert_id();

        $output = self::tlv('dmap.status', 200);
        $output .= self::tlv('dmap.sessionid', $sid);

        $output = self::tlv('dmap.loginresponse', $output);
        self::apiOutput($output);
    }

    /**
     * @param string $code
     */
    private static function check_session($code)
    {
        // Purge expired sessions
        $sql = "DELETE FROM `daap_session` WHERE `creationdate` < ?";
        Dba::write($sql, array(
            time() - 1800
        ));

        self::check_auth($code);

        if (!filter_has_var(INPUT_GET, 'session-id')) {
            debug_event(self::class, 'Missing session id.', 2);
        } else {
            $sql        = "SELECT * FROM `daap_session` WHERE `id` = ?";
            $db_results = Dba::read($sql, array(
                Core::get_get('session-id')
            ));

            if (Dba::num_rows($db_results) == 0) {
                debug_event(self::class, 'Unknown session id `' . Core::get_get('session-id') . '`.',4);
            }
        }
    }

    /**
     * @param string $code
     */
    private static function check_auth($code = '')
    {
        $authenticated = false;
        $pass          = AmpConfig::get('daap_pass');
        // DAAP password specified, need to authenticate the client
        if (! empty($pass)) {
            $headers = apache_request_headers();
            $auth    = $headers['Authorization'];
            if (strpos(strtolower((string) $auth), 'basic') === 0) {
                $decauth  = base64_decode(substr($auth, 6));
                $userpass = explode(':', (string) $decauth);
                if (count($userpass) == 2) {
                    if ($userpass[1] == $pass) {
                        $authenticated = true;
                    }
                }
            }
        } else {
            $authenticated = true;
        }

        if (! $authenticated) {
            debug_event(self::class, 'Authentication failed. Wrong DAAP password?', 3);
            if (! empty($code)) {
                self::createApiError($code, 403);
            }
        }
    }

    /**
     * logout
     * @param $input
     */
    public static function logout($input)
    {
        self::check_auth();

        $sql = "DELETE FROM `daap_session` WHERE `id` = ?";
        Dba::write($sql, array(
            $input['session-id']
        ));

        self::setHeaders();
        header("HTTP/1.0 204 Logout Successful", true, 204);
    }

    /**
     * update
     * @param $input
     */
    public static function update($input)
    {
        self::check_session('dmap.updateresponse');

        $output = self::tlv('dmap.serverrevision', Catalog::getLastUpdate());
        $output .= self::tlv('dmap.status', 200);

        $output = self::tlv('dmap.updateresponse', $output);
        self::apiOutput($output);
    }

    /**
     * @return string
     */
    private static function catalog_songs()
    {
        // $type = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $meta      = explode(',', strtolower(Core::get_get('meta')));
        $output    = self::tlv('dmap.status', 200);
        $output .= self::tlv('dmap.updatetype', 0);

        $songs    = array();
        $catalogs = Catalog::get_catalogs();
        foreach ($catalogs as $catalog_id) {
            $catalog = Catalog::create_from_id($catalog_id);
            $songs   = array_merge($songs, $catalog->get_songs());
        }

        $output .= self::tlv('dmap.specifiedtotalcount', count($songs));
        $output .= self::tlv('dmap.returnedcount', count($songs));
        $output .= self::tlv('dmap.listing', self::tlv_songs($songs, $meta));

        return $output;
    }

    /**
     * databases
     * @param array $input
     * @return boolean
     */
    public static function databases($input)
    {
        $output = '';
        // Database list
        if (count($input) == 0) {
            self::check_session('daap.serverdatabases');

            $output = self::tlv('dmap.status', 200);
            $output .= self::tlv('dmap.updatetype', 0);
            $output .= self::tlv('dmap.specifiedtotalcount', 1);
            $output .= self::tlv('dmap.returnedcount', 1);

            $tlv = self::tlv('dmap.itemid', 1);
            $tlv .= self::tlv('dmap.persistentid', 1);
            $tlv .= self::tlv('dmap.itemname', 'Ampache');
            $counts = Catalog::count_server();
            $tlv .= self::tlv('dmap.itemcount', $counts['song']);
            $tlv .= self::tlv('dmap.containercount', count(Playlist::get_playlists()));
            $tlv = self::tlv('dmap.listingitem', $tlv);
            $output .= self::tlv('dmap.listing', $tlv);

            $output = self::tlv('daap.serverdatabases', $output);
        } elseif (count($input) == 2) {
            if ($input[1] == 'items') {
                // Songs list
                self::check_session('daap.databasesongs');

                $output = self::catalog_songs();
                $output = self::tlv('daap.databasesongs', $output);
            } elseif ($input[1] == 'containers') {
                // Playlist list
                self::check_session('daap.databaseplaylists');

                $output = self::tlv('dmap.status', 200);
                $output .= self::tlv('dmap.updatetype', 0);

                $playlists = Playlist::get_playlists();
                $searches  = Search::get_searches();
                $output .= self::tlv('dmap.specifiedtotalcount', count($playlists) + count($searches) + 1);
                $output .= self::tlv('dmap.returnedcount', count($playlists) + count($searches) + 1);

                $library = self::base_library();
                foreach ($playlists as $playlist_id) {
                    $playlist = new Playlist($playlist_id);
                    $playlist->format();
                    $library .= self::tlv_playlist($playlist);
                }
                foreach ($searches as $search_id) {
                    $playlist = new Search($search_id);
                    $playlist->format();
                    $library .= self::tlv_playlist($playlist);
                }
                $output .= self::tlv('dmap.listing', $library);

                $output = self::tlv('daap.databaseplaylists', $output);
            }
        } elseif (count($input) == 3) {
            // Stream
            if ($input[1] == 'items') {
                $finfo = explode('.', (string) $input[2]);
                if (count($finfo) == 2) {
                    $object_id = (int) ($finfo[0]);
                    $type      = $finfo[1];

                    $params  = '';
                    $headers = apache_request_headers();
                    $client  = $headers['User-Agent'];
                    if (! empty($client)) {
                        $client = urlencode(preg_replace('/[\s\/]/', '_', $client));
                        $params .= '&client=' . $client;
                    }
                    $params .= '&transcode_to=' . $type;
                    $media = new $type($object_id);
                    $url   = $media->play_url($params, 'api', true);
                    self::follow_stream($url);

                    return false;
                }
            }
        } elseif (count($input) == 4) {
            // Playlist
            if ($input[1] == 'containers' && $input[3] == 'items') {
                $object_id = (int) ($input[2]);

                self::check_session('daap.playlistsongs');

                if ($object_id == Daap_Api::BASE_LIBRARY) {
                    $output = self::catalog_songs();
                    $output = self::tlv('daap.playlistsongs', $output);
                } else {
                    if ($object_id > Daap_Api::AMPACHEID_SMARTPL) {
                        $object_id -= Daap_Api::AMPACHEID_SMARTPL;
                        $playlist = new Search($object_id);
                    } else {
                        $playlist = new Playlist($object_id);
                    }

                    if ($playlist->id) {
                        $meta    = explode(',', strtolower(Core::get_get('meta')));
                        $output  = self::tlv('dmap.status', 200);
                        $output .= self::tlv('dmap.updatetype', 0);
                        $items    = $playlist->get_items();
                        $song_ids = array();
                        foreach ($items as $item) {
                            if ($item['object_type'] == 'song') {
                                $song_ids[] = $item['object_id'];
                            }
                        }
                        if (AmpConfig::get('memory_cache')) {
                            Song::build_cache($song_ids);
                        }
                        $songs = array();
                        foreach ($song_ids as $song_id) {
                            $songs[] = new Song($song_id);
                        }
                        $output .= self::tlv('dmap.specifiedtotalcount', count($songs));
                        $output .= self::tlv('dmap.returnedcount', count($songs));
                        $output .= self::tlv('dmap.listing', self::tlv_songs($songs, $meta));

                        $output = self::tlv('daap.playlistsongs', $output);
                    } else {
                        self::createApiError('daap.playlistsongs', 500, 'Invalid playlist id: ' . $object_id);
                    }
                }
            }
        }
        self::apiOutput($output);

        return true;
    }

    /**
     * @param $songs
     * @param $meta
     * @return string
     */
    private static function tlv_songs($songs, $meta)
    {
        if (array_search('all', $meta) > - 1) {
            $meta = self::$metas;
        }
        $tlv_out = '';
        foreach ($songs as $song) {
            $song->format();
            $output = self::tlv('dmap.itemkind', 2);
            $output .= self::tlv('dmap.itemid', $song->id);

            foreach ($meta as $tag) {
                switch ($tag) {
                    case 'dmap.itemname':
                        $output .= self::tlv($tag, $song->f_title);
                        break;
                    case 'dmap.containeritemid':
                    /* case 'dmap.persistentid': */
                        $output .= self::tlv($tag, $song->id);
                        break;
                    case 'daap.songalbum':
                        $output .= self::tlv($tag, $song->f_album);
                        break;
                    case 'daap.songartist':
                        $output .= self::tlv($tag, $song->f_artist);
                        break;
                    case 'daap.songcomposer':
                        $output .= self::tlv($tag, $song->f_composer);
                        break;
                    case 'daap.songbitrate':
                        $output .= self::tlv($tag, (int) ($song->bitrate / 1000));
                        break;
                    case 'daap.songcomment':
                        $output .= self::tlv($tag, $song->comment);
                        break;
                    case 'daap.songdateadded':
                        $output .= self::tlv($tag, $song->addition_time);
                        break;
                    case 'daap.songdatemodified':
                        if ($song->update_time) {
                            $output .= self::tlv($tag, $song->update_time);
                        }
                        break;
                    case 'daap.songdiscnumber':
                        $album = new Album($song->album);
                        $output .= self::tlv($tag, $album->disk);
                        break;
                    case 'daap.songformat':
                        $output .= self::tlv($tag, $song->type);
                        break;
                    case 'daap.songgenre':
                        $output .= self::tlv($tag, Tag::get_display($song->tags, false, 'song'));
                        break;
                    case 'daap.songsamplerate':
                        $output .= self::tlv($tag, $song->rate);
                        break;
                    case 'daap.songsize':
                        $output .= self::tlv($tag, $song->size);
                        break;
                    case 'daap.songtime':
                        $output .= self::tlv($tag, $song->time * 1000);
                        break;
                    case 'daap.songtracknumber':
                        $output .= self::tlv($tag, $song->track);
                        break;
                    case 'daap.songuserrating':
                        $rating       = new Rating($song->id, "song");
                        $rating_value = $rating->get_average_rating();
                        $output .= self::tlv($tag, $rating_value);
                        break;
                    case 'daap.songyear':
                        $output .= self::tlv($tag, $song->year);
                        break;
                }
            }
            $tlv_out .= self::tlv('dmap.listingitem', $output);
        }

        return $tlv_out;
    }

    /**
     * @return string
     */
    public static function base_library()
    {
        $library = self::tlv('dmap.itemid', Daap_Api::BASE_LIBRARY);
        $library .= self::tlv('dmap.persistentid', Daap_Api::BASE_LIBRARY);
        $library .= self::tlv('dmap.itemname', 'Music');
        $library .= self::tlv('daap.baseplaylist', 1);
        $counts = Catalog::count_server();
        $library .= self::tlv('dmap.itemcount', $counts['song']);

        return self::tlv('dmap.listingitem', $library);
    }

    /**
     * @param Playlist|Search $playlist
     * @return string
     */
    public static function tlv_playlist($playlist)
    {
        $isSmart = false;
        if (get_class($playlist) == 'Search') {
            $isSmart = true;
        }
        $pl_id  = (($isSmart) ? Daap_Api::AMPACHEID_SMARTPL : 0) + $playlist->id;
        $plist  = self::tlv('dmap.itemid', $pl_id);
        $plist .= self::tlv('dmap.persistentid', $pl_id);
        $plist .= self::tlv('dmap.itemname', $playlist->f_name);
        $plist .= self::tlv('dmap.itemcount', count($playlist->get_items()));
        if ($isSmart) {
            $plist .= self::tlv('com.apple.itunes.smart-playlist', 1);
        }

        return self::tlv('dmap.listingitem', $plist);
    }

    /**
     * @param $tag
     * @param $value
     * @return string
     */
    private static function tlv($tag, $value)
    {
        if (array_key_exists($tag, self::$tags)) {
            $code = self::$tags[$tag]['code'];
            switch (self::$tags[$tag]['type']) {
                case 'byte':
                    return self::tlv_byte($code, $value);
                case 'short':
                    return self::tlv_short($code, $value);
                case 'int':
                    return self::tlv_int($code, $value);
                case 'long':
                    return self::tlv_long($code, $value);
                case 'string':
                    return self::tlv_string($code, $value);
                case 'date':
                    return self::tlv_date($code, $value);
                case 'version':
                    return self::tlv_version($code, $value);
                case 'list':
                    return self::tlv_list($code, $value);
                default:
                    debug_event(self::class, 'Unsupported tag type `' . self::$tags[$tag]['type'] . '`.', 3);
                    break;
            }

            return $code . pack("N", strlen((string) $value)) . $value;
        } else {
            debug_event(self::class, 'Unknown DAAP tag `' . $tag . '`.', 3);
        }

        return '';
    }

    /**
     * @param $tag
     * @param $value
     * @return string
     */
    private static function tlv_string($tag, $value)
    {
        return $tag . pack("N", strlen((string) $value)) . $value;
    }

    /**
     * @param $tag
     * @param $value
     * @return string
     */
    private static function tlv_long($tag, $value)
    {
        // Really?! PHP...
        // Need to split value into two 32-bit integer because php pack function doesn't support 64-bit integer...
        $highMap = 0xffffffff00000000;
        $lowMap  = 0x00000000ffffffff;
        $higher  = ($value & $highMap) >> 32;
        $lower   = $value & $lowMap;

        return $tag . "\x00\x00\x00\x08" . pack("NN", $higher, $lower);
    }

    /**
     * @param $tag
     * @param $value
     * @return string
     */
    private static function tlv_int($tag, $value)
    {
        return $tag . "\x00\x00\x00\x04" . pack("N", $value);
    }

    /**
     * @param $tag
     * @param $value
     * @return string
     */
    private static function tlv_short($tag, $value)
    {
        return $tag . "\x00\x00\x00\x02" . pack("n", $value);
    }

    /**
     * @param $tag
     * @param $value
     * @return string
     */
    private static function tlv_byte($tag, $value)
    {
        return $tag . "\x00\x00\x00\x01" . pack("C", $value);
    }

    /**
     * @param $tag
     * @param $value
     * @return string
     */
    private static function tlv_version($tag, $value)
    {
        $values = explode('.', $value);
        if (count($values) == 4) {
            return $tag . "\x00\x00\x00\x04" . pack("C", $values[0]) . pack("C", $values[1]) . pack("C", $values[2]) . pack("C", $values[3]);
        } else {
            debug_event(self::class, 'Malformed `' . $tag . '` version `' . $value . '`.', 3);
        }

        return '';
    }

    /**
     * @param $tag
     * @param $value
     * @return string
     */
    private static function tlv_date($tag, $value)
    {
        return self::tlv_int($tag, $value);
    }

    /**
     * @param $tag
     * @param $value
     * @return string
     */
    private static function tlv_list($tag, $value)
    {
        return self::tlv_string($tag, $value);
    }

    public static function create_dictionary()
    {
        self::add_dict('mdcl', 'list', 'dmap.dictionary'); // a dictionary entry
        self::add_dict('mstt', 'int', 'dmap.status'); // the response status code, these appear to be http status codes
        self::add_dict('miid', 'int', 'dmap.itemid'); // an item's id
        self::add_dict('minm', 'string', 'dmap.itemname'); // an items name
        self::add_dict('mikd', 'byte', 'dmap.itemkind'); // the kind of item. So far, only '2' has been seen, an audio file?
        self::add_dict('mper', 'long', 'dmap.persistentid'); // a persistent id
        self::add_dict('mcon', 'list', 'dmap.container'); // an arbitrary container
        self::add_dict('mcti', 'int', 'dmap.containeritemid'); // the id of an item in its container
        self::add_dict('mpco', 'int', 'dmap.parentcontainerid');
        self::add_dict('msts', 'string', 'dmap.statusstring');
        self::add_dict('mimc', 'int', 'dmap.itemcount'); // number of items in a container
        self::add_dict('mrco', 'int', 'dmap.returnedcount'); // number of items returned in a request
        self::add_dict('mtco', 'int', 'dmap.specifiedtotalcount'); // number of items in response to a request
        self::add_dict('mctc', 'int', 'dmap.containercount');
        self::add_dict('mlcl', 'list', 'dmap.listing'); // a list
        self::add_dict('mlit', 'list', 'dmap.listingitem'); // a single item in said list
        self::add_dict('mbcl', 'list', 'dmap.bag');
        self::add_dict('mdcl', 'list', 'dmap.dictionary');
        self::add_dict('msrv', 'list', 'dmap.serverinforesponse'); // response to a /server-info
        self::add_dict('msau', 'byte', 'dmap.authenticationmethod');
        self::add_dict('mslr', 'byte', 'dmap.loginrequired');
        self::add_dict('mpro', 'version', 'dmap.protocolversion');
        self::add_dict('apro', 'version', 'daap.protocolversion');
        self::add_dict('msal', 'byte', 'dmap.supportsautologout');
        self::add_dict('msup', 'byte', 'dmap.supportsupdate');
        self::add_dict('mspi', 'byte', 'dmap.supportspersistentids');
        self::add_dict('msex', 'byte', 'dmap.supportsextensions');
        self::add_dict('msbr', 'byte', 'dmap.supportsbrowse');
        self::add_dict('msqy', 'byte', 'dmap.supportsquery');
        self::add_dict('msix', 'byte', 'dmap.supportsindex');
        self::add_dict('msrs', 'byte', 'dmap.supportsresolve');
        self::add_dict('mstm', 'int', 'dmap.timeoutinterval');
        self::add_dict('msdc', 'int', 'dmap.databasescount');
        self::add_dict('mccr', 'list', 'dmap.contentcodesresponse'); // response to a /content-codes
        self::add_dict('mcnm', 'int', 'dmap.contentcodesnumber'); // the four letter code
        self::add_dict('mcna', 'string', 'dmap.contentcodesname'); // the full name of the code
        self::add_dict('mcty', 'short', 'dmap.contentcodestype'); // the type of the code
        self::add_dict('mlog', 'list', 'dmap.loginresponse'); // response to a /login
        self::add_dict('mlid', 'int', 'dmap.sessionid'); // the session id for the login session
        self::add_dict('mupd', 'list', 'dmap.updateresponse'); // response to a /update
        self::add_dict('musr', 'int', 'dmap.serverrevision'); // revision to use for requests
        self::add_dict('muty', 'byte', 'dmap.updatetype');
        self::add_dict('mudl', 'list', 'dmap.deletedidlisting'); // used in updates?
        self::add_dict('avdb', 'list', 'daap.serverdatabases'); // response to a /databases
        self::add_dict('abpro', 'list', 'daap.databasebrowse');
        self::add_dict('abal', 'list', 'daap.browsealbumlisting');
        self::add_dict('abar', 'list', 'daap.browseartistlisting');
        self::add_dict('abcp', 'list', 'daap.browsecomposerlisting');
        self::add_dict('abgn', 'list', 'daap.browsegenrelisting');
        self::add_dict('adbs', 'list', 'daap.databasesongs'); // response to a /databases/id/items
        self::add_dict('asal', 'string', 'daap.songalbum');
        self::add_dict('asar', 'string', 'daap.songartist');
        self::add_dict('ascp', 'string', 'daap.songcomposer');
        self::add_dict('asbt', 'short', 'daap.songsbeatsperminute');
        self::add_dict('asbr', 'short', 'daap.songbitrate');
        self::add_dict('ascm', 'string', 'daap.songcomment');
        self::add_dict('asco', 'byte', 'daap.songcompilation');
        self::add_dict('asda', 'date', 'daap.songdateadded');
        self::add_dict('asdm', 'date', 'daap.songdatemodified');
        self::add_dict('asdc', 'short', 'daap.songdiscount');
        self::add_dict('asdn', 'short', 'daap.songdiscnumber');
        self::add_dict('asdb', 'byte', 'daap.songdisabled');
        self::add_dict('aseq', 'string', 'daap.songqpreset');
        self::add_dict('asfm', 'string', 'daap.songformat');
        self::add_dict('asgn', 'string', 'daap.songgenre');
        self::add_dict('asdt', 'string', 'daap.songdescription');
        self::add_dict('asrv', 'byte', 'daap.songrelativevolume');
        self::add_dict('assr', 'int', 'daap.songsamplerate');
        self::add_dict('assz', 'int', 'daap.songsize');
        self::add_dict('asst', 'int', 'daap.songstarttime'); // in milliseconds
        self::add_dict('assp', 'int', 'daap.songstoptime'); // in milliseconds
        self::add_dict('astm', 'int', 'daap.songtime'); // in milliseconds
        self::add_dict('astc', 'short', 'daap.songtrackcount');
        self::add_dict('astn', 'short', 'daap.songtracknumber');
        self::add_dict('asur', 'byte', 'daap.songuserrating');
        self::add_dict('asyr', 'short', 'daap.songyear');
        self::add_dict('asdk', 'byte', 'daap.songdatakind');
        self::add_dict('asul', 'string', 'daap.songdataurl');
        self::add_dict('aply', 'list', 'daap.databaseplaylists'); // response to a /databases/id/containers
        self::add_dict('abpl', 'byte', 'daap.baseplaylist');
        self::add_dict('apso', 'list', 'daap.playlistsongs'); // response to a /databases/id/containers/id/items
        self::add_dict('prsv', 'list', 'daap.resolve');
        self::add_dict('arif', 'list', 'daap.resolveinfo');
        self::add_dict('ated', 'short', 'daap.supportsextradata');
        self::add_dict('asgr', 'short', 'daap.supportsgroups');
        self::add_dict('aeMQ', 'byte', 'daap.aeMQ');
        self::add_dict('aeTr', 'byte', 'daap.aeTr');
        self::add_dict('aeSL', 'byte', 'daap.aeSL');
        self::add_dict('aeSR', 'byte', 'daap.aeSR');
        self::add_dict('msed', 'byte', 'dmap.supportsedit');
        self::add_dict('aeNV', 'int', 'com.apple.itunes.norm-volume');
        self::add_dict('aeSP', 'byte', 'com.apple.itunes.smart-playlist');
    }

    /**
     * @param string $code
     * @param string $type
     * @param string $name
     */
    private static function add_dict($code, $type, $name)
    {
        self::$tags[$name] = array(
            'type' => $type,
            'code' => $code
        );
    }

    /**
     * @param $type
     * @return integer
     */
    private static function get_type_id($type)
    {
        switch ($type) {
            case 'byte':
                return 1;
            case 'unsigned byte':
                return 2;
            case 'short':
                return 3;
            case 'unsigned short':
                return 4;
            case 'int':
                return 5;
            case 'unsigned int':
                return 6;
            case 'long':
                return 7;
            case 'unsigned long':
                return 8;
            case 'string':
                return 9;
            case 'date': // represented as a 4 byte integer
                return 10;
            case 'version': // represented as a 4 singles bytes, e.g. 0.1.0.0 or as two shorts, e.g. 1.0
                return 11;
            case 'list':
                return 12;
            default:
                return 0;
        }
    }

    private static function setHeaders()
    {
        header("Content-Type: application/x-dmap-tagged");
        header("DAAP-Server: Ampache");
        header("Accept-Ranges: bytes");
        header("Cache-Control: no-cache");
        header("Expires: -1");
    }

    /**
     * @param string $string
     */
    public static function apiOutput($string)
    {
        self::setHeaders();

        if (Core::get_server('REQUEST_METHOD') != 'OPTIONS') {
            header("Content-length: " . strlen((string) $string));
            echo $string;
        } else {
            header("Content-type: text/plain");
            header("Content-length: 0");
        }
    }

    /**
     * @param $code
     * @return boolean
     */
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
        header("Content-type: text/html");
        header("HTTP/1.0 " . $code . " " . $error, true, $code);

        $html = "<html><head><title>" . $error . "</title></head><body><h1>" . $code . " " . $error . "</h1></body></html>";
        self::apiOutput($html);

        return false;
    }

    /**
     * @param string $tag
     * @param integer $code
     * @param string $msg
     * @return boolean
     */
    public static function createApiError($tag, $code, $msg = '')
    {
        $output = self::tlv('dmap.status', $code);
        if (! empty($msg)) {
            $output .= self::tlv('dmap.statusstring', $msg);
        }
        $output = self::tlv($tag, $output);
        self::apiOutput($output);

        return false;
    }
} // end daap_api.class
