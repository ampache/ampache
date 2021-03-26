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
 * API Class
 *
 * This handles functions relating to the API written for Ampache, initially
 * this is very focused on providing functionality for Amarok so it can
 * integrate with Ampache.
 *
 */
class Api
{
    /**
     *  @var string $auth_version
     */
    public static $auth_version = '350001';

    /**
     *  @var string $version
     */
    public static $version = '441000';

    /**
     *  @var Browse $browse
     */
    private static $browse = null;

    /**
     * constructor
     * This really isn't anything to do here, so it's private
     */
    private function __construct()
    {
        // Rien a faire
    } // constructor

    /**
     * _auto_init
     * Automatically called when this class is loaded.
     */
    public static function _auto_init()
    {
        if (self::$browse === null) {
            self::$browse = new Browse(null, false);
        }
    }

    /**
     * message
     * call the correct error / success message depending on format
     * @param string $type
     * @param string $message
     * @param string $error_code
     * @param string $format
     */
    public static function message($type, $message, $error_code = null, $format = 'xml')
    {
        if ($type === 'error') {
            switch ($format) {
                case 'json':
                    echo JSON_Data::error($error_code, $message);
                    break;
                default:
                    echo XML_Data::error($error_code, $message);
            }
        }
        if ($type === 'success') {
            switch ($format) {
                case 'json':
                    echo JSON_Data::success($message);
                    break;
                default:
                    echo XML_Data::success($message);
            }
        }
    } // message

    /**
     * set_filter
     * MINIMUM_API_VERSION=380001
     *
     * This is a play on the browse function, it's different as we expose
     * the filters in a slightly different and vastly simpler way to the
     * end users--so we have to do a little extra work to make them work
     * internally.
     * @param string $filter
     * @param integer|string|boolean|null $value
     * @return boolean
     */
    public static function set_filter($filter, $value)
    {
        if (!strlen((string) $value)) {
            return false;
        }

        switch ($filter) {
            case 'add':
                // Check for a range, if no range default to gt
                if (strpos((string) $value, '/')) {
                    $elements = explode('/', (string) $value);
                    self::$browse->set_filter('add_lt', strtotime((string) $elements['1']));
                    self::$browse->set_filter('add_gt', strtotime((string) $elements['0']));
                } else {
                    self::$browse->set_filter('add_gt', strtotime((string) $value));
                }
                break;
            case 'update':
                // Check for a range, if no range default to gt
                if (strpos((string) $value, '/')) {
                    $elements = explode('/', (string) $value);
                    self::$browse->set_filter('update_lt', strtotime((string) $elements['1']));
                    self::$browse->set_filter('update_gt', strtotime((string) $elements['0']));
                } else {
                    self::$browse->set_filter('update_gt', strtotime((string) $value));
                }
                break;
            case 'alpha_match':
                self::$browse->set_filter('alpha_match', $value);
                break;
            case 'exact_match':
                self::$browse->set_filter('exact_match', $value);
                break;
            case 'enabled':
                self::$browse->set_filter('enabled', $value);
                break;
            default:
                break;
        } // end filter

        return true;
    } // set_filter

    /**
     * check_parameter
     *
     * This function checks the $input actually has the parameter.
     * Parameters must be an array of required elements as a string
     *
     * @param array $input
     * @param string[] $parameters e.g. array('auth', type')
     * @param string $method
     * @return boolean
     */
    private static function check_parameter($input, $parameters, $method = '')
    {
        foreach ($parameters as $parameter) {
            if ($input[$parameter] === 0 || $input[$parameter] === '0') {
                continue;
            }
            if (empty($input[$parameter])) {
                debug_event(self::class, "'" . $parameter . "' required on " . $method . " function call.", 2);
                self::message('error', T_('Missing mandatory parameter') . " '" . $parameter . "'", '401', $input['api_format']);

                return false;
            }
        }

        return true;
    } // check_parameter

    /**
     * check_access
     *
     * This function checks the user can perform the function requested
     * 'interface', 100, User::get_from_username(Session::username($input['auth']))->id)
     *
     * @param string $type
     * @param integer $level
     * @param integer $user_id
     * @param string $method
     * @param string $format
     * @return boolean
     */
    private static function check_access($type, $level, $user_id, $method = '', $format = 'xml')
    {
        if (!Access::check($type, $level, $user_id)) {
            debug_event(self::class, $type . " '" . $level . "' required on " . $method . " function call.", 2);
            self::message('error', 'User does not have access to this function', '400', $format);

            return false;
        }

        return true;
    } // check_access

    /**
     * handshake
     * MINIMUM_API_VERSION=380001
     *
     * This is the function that handles verifying a new handshake
     * Takes a timestamp, auth key, and username.
     *
     * @param array $input
     * auth      = (string) $passphrase
     * user      = (string) $username //optional
     * timestamp = (integer) UNIXTIME() //Required if login/password authentication
     * version   = (string) $version //optional
     * @return boolean
     */
    public static function handshake($input)
    {
        $timestamp  = preg_replace('/[^0-9]/', '', $input['timestamp']);
        $passphrase = $input['auth'];
        if (empty($passphrase)) {
            $passphrase = Core::get_post('auth');
        }
        $username = trim((string) $input['user']);
        $user_ip  = Core::get_user_ip();
        $version  = (isset($input['version'])) ? (string) $input['version'] : self::$version;

        // Log the attempt
        debug_event(self::class, "Handshake Attempt, IP:$user_ip User:$username Version:$version", 5);

        // Version check shouldn't be soo restrictive... only check with initial version to not break clients compatibility
        if ((int) ($version) < self::$auth_version) {
            debug_event(self::class, 'Login Failed: Version too old', 1);
            AmpError::add('api', T_('Login failed, API version is too old'));

            return false;
        }

        $user_id = -1;
        // Grab the correct userid
        if (!$username) {
            $client = User::get_from_apikey($passphrase);
            if ($client) {
                $user_id = $client->id;
            }
        } else {
            $client  = User::get_from_username($username);
            $user_id = $client->id;
        }

        // Log this attempt
        debug_event(self::class, "Login Attempt, IP:$user_ip Time: $timestamp User:$username ($user_id) Auth:$passphrase", 1);

        if ($user_id > 0 && Access::check_network('api', $user_id, 5)) {
            // Authentication with user/password, we still need to check the password
            if ($username) {
                // If the timestamp isn't within 30 minutes sucks to be them
                if (($timestamp < (time() - 1800)) ||
                    ($timestamp > (time() + 1800))) {
                    debug_event(self::class, 'Login failed, timestamp is out of range ' . $timestamp . '/' . time(), 1);
                    AmpError::add('api', T_('Login failed, timestamp is out of range'));
                    self::message('error', T_('Received Invalid Handshake') . ' - ' . T_('Login failed, timestamp is out of range'), '401', $input['api_format']);

                    return false;
                }

                // Now we're sure that there is an ACL line that matches
                // this user or ALL USERS, pull the user's password and
                // then see what we come out with
                $realpwd = $client->get_password();

                if (!$realpwd) {
                    debug_event(self::class, 'Unable to find user with userid of ' . $user_id, 1);
                    AmpError::add('api', T_('Incorrect username or password'));
                    self::message('error', T_('Received Invalid Handshake') . ' - ' . T_('Login failed, timestamp is out of range'), '401', $input['api_format']);

                    return false;
                }

                $sha1pass = hash('sha256', $timestamp . $realpwd);

                if ($sha1pass !== $passphrase) {
                    $client = null;
                }
            } else {
                $timestamp = time();
            }

            if ($client) {
                // Create the session
                $data             = array();
                $data['username'] = $client->username;
                $data['type']     = 'api';
                $data['apikey']   = $client->apikey;
                $data['value']    = $timestamp;
                if (isset($input['client'])) {
                    $data['agent'] = $input['client'];
                }
                if (isset($input['geo_latitude'])) {
                    $data['geo_latitude'] = $input['geo_latitude'];
                }
                if (isset($input['geo_longitude'])) {
                    $data['geo_longitude'] = $input['geo_longitude'];
                }
                if (isset($input['geo_name'])) {
                    $data['geo_name'] = $input['geo_name'];
                }
                //Session might not exist or has expired
                //
                if (!Session::read($data['apikey'])) {
                    Session::destroy($data['apikey']);
                    $token = Session::create($data);
                } else {
                    Session::extend($data['apikey']);
                    $token = $data['apikey'];
                }

                debug_event(self::class, 'Login Success, passphrase matched', 1);


                // We need to also get the 'last update' of the catalog information in an RFC 2822 Format
                $sql        = 'SELECT MAX(`last_update`) AS `update`, MAX(`last_add`) AS `add`, MAX(`last_clean`) AS `clean` FROM `catalog`';
                $db_results = Dba::read($sql);
                $row        = Dba::fetch_assoc($db_results);

                // Now we need to quickly get the totals
                $counts = Catalog::count_server(true);

                // send the totals
                $outarray = array('auth' => $token,
                                  'api' => self::$version,
                                  'session_expire' => date("c", time() + AmpConfig::get('session_length') - 60),
                                  'update' => date("c", (int) $row['update']),
                                  'add' => date("c", (int) $row['add']),
                                  'clean' => date("c", (int) $row['clean']),
                                  'songs' => (int) $counts['song'],
                                  'albums' => (int) $counts['album'],
                                  'artists' => (int) $counts['artist'],
                                  'playlists' => ((int) $counts['playlist'] + (int) $counts['search']),
                                  'videos' => (int) $counts['video'],
                                  'catalogs' => (int) $counts['catalog'],
                                  'users' => (int) $counts['user'],
                                  'tags' => (int) $counts['tag'],
                                  'podcasts' => (int) $counts['podcast'],
                                  'podcast_episodes' => (int) $counts['podcast_episode'],
                                  'shares' => (int) $counts['share'],
                                  'licenses' => (int) $counts['license'],
                                  'live_streams' => (int) $counts['live_stream'],
                                  'labels' => (int) $counts['label']);
                switch ($input['api_format']) {
                    case 'json':
                        echo json_encode($outarray, JSON_PRETTY_PRINT);
                    break;
                    default:
                        echo XML_Data::keyed_array($outarray);
                }

                return true;
            } // match
        } // end while

        debug_event(self::class, 'Login Failed, unable to match passphrase', 1);
        self::message('error', T_('Received Invalid Handshake') . ' - ' . T_('Incorrect username or password'), '401', $input['api_format']);

        return false;
    } // handshake

    /**
     * ping
     * MINIMUM_API_VERSION=380001
     *
     * This can be called without being authenticated, it is useful for determining if what the status
     * of the server is, and what version it is running/compatible with
     *
     * @param array $input
     * auth = (string) //optional
     */
    public static function ping($input)
    {
        $token   = (string) $input['auth'];
        $xmldata = array('server' => AmpConfig::get('version'), 'version' => self::$version, 'compatible' => '350001');

        // Check and see if we should extend the api sessions (done if valid session is passed)
        if (Session::exists('api', $token)) {
            Session::extend($token);
            // We need to also get the 'last update' of the catalog information in an RFC 2822 Format
            $sql        = 'SELECT MAX(`last_update`) AS `update`, MAX(`last_add`) AS `add`, MAX(`last_clean`) AS `clean` FROM `catalog`';
            $db_results = Dba::read($sql);
            $row        = Dba::fetch_assoc($db_results);
            // Now we need to quickly get the totals
            $counts = Catalog::count_server(true);
            // now add it all together
            $countarray = array('api' => self::$version,
                'session_expire' => date("c", time() + AmpConfig::get('session_length') - 60),
                'update' => date("c", (int) $row['update']),
                'add' => date("c", (int) $row['add']),
                'clean' => date("c", (int) $row['clean']),
                'songs' => (int) $counts['song'],
                'albums' => (int) $counts['album'],
                'artists' => (int) $counts['artist'],
                'playlists' => ((int) $counts['playlist'] + (int) $counts['search']),
                'videos' => (int) $counts['video'],
                'catalogs' => (int) $counts['catalog'],
                'users' => (int) $counts['user'],
                'tags' => (int) $counts['tag'],
                'podcasts' => (int) $counts['podcast'],
                'podcast_episodes' => (int) $counts['podcast_episode'],
                'shares' => (int) $counts['share'],
                'licenses' => (int) $counts['license'],
                'live_streams' => (int) $counts['live_stream'],
                'labels' => (int) $counts['label']);
            $xmldata = array_merge(array('session_expire' => date("c", time() + (int) AmpConfig::get('session_length') - 60)), $xmldata, $countarray);
        }

        debug_event(self::class, 'Ping Received from ' . Core::get_server('REMOTE_ADDR') . ' :: ' . $token, 5);

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo json_encode($xmldata, JSON_PRETTY_PRINT);
            break;
            default:
                echo XML_Data::keyed_array($xmldata);
        }
    } // ping

    /**
     * goodbye
     * MINIMUM_API_VERSION=400001
     *
     * Destroy session for auth key.
     *
     * @param array $input
     * auth = (string))
     * @return boolean
     */
    public static function goodbye($input)
    {
        if (!self::check_parameter($input, array('auth'), 'goodbye')) {
            return false;
        }
        // Check and see if we should destroy the api session (done if valid session is passed)
        if (Session::exists('api', $input['auth'])) {
            $sql = 'DELETE FROM `session` WHERE `id` = ?';
            $sql .= " AND `type` = 'api'";
            Dba::write($sql, array($input['auth']));

            debug_event(self::class, 'Goodbye Received from ' . Core::get_server('REMOTE_ADDR') . ' :: ' . $input['auth'], 5);
            ob_end_clean();
            self::message('success', 'goodbye: ' . $input['auth'], null, $input['api_format']);

            return true;
        }
        ob_end_clean();
        self::message('error', 'failed to end session: ' . $input['auth'], '400', $input['api_format']);

        return false;
    } // goodbye

    /**
     * url_to_song
     * MINIMUM_API_VERSION=380001
     *
     * This takes a url and returns the song object in question
     *
     * @param array $input
     * url = (string) $url
     * @return boolean
     */
    public static function url_to_song($input)
    {
        if (!self::check_parameter($input, array('url'), 'url_to_song')) {
            return false;
        }
        // Don't scrub, the function needs her raw and juicy
        $data = Stream_URL::parse($input['url']);
        $user = User::get_from_username(Session::username($input['auth']));
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::songs(array($data['id']), $user->id);
            break;
            default:
                echo XML_Data::songs(array($data['id']), $user->id);
        }

        return true;
    } // url_to_song

    /**
     * get_indexes
     * MINIMUM_API_VERSION=400001
     *
     * This takes a collection of inputs and returns ID + name for the object type
     *
     * @param array $input
     * type        = (string) 'song', 'album', 'artist', 'album_artist', 'playlist', 'podcast', 'podcast_episode', 'share', 'video'
     * filter      = (string) //optional
     * exact       = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * add         = self::set_filter(date) //optional
     * update      = self::set_filter(date) //optional
     * include     = (integer) 0,1 include songs if available for that object //optional
     * offset      = (integer) //optional
     * limit       = (integer) //optional
     * hide_search = (integer) 0,1, if true do not include searches/smartlists in the result //optional
     * @return boolean
     */
    public static function get_indexes($input)
    {
        if (!self::check_parameter($input, array('type'), 'get_indexes')) {
            return false;
        }
        $type = ((string) $input['type'] == 'album_artist') ? 'artist' : (string) $input['type'];
        if (!AmpConfig::get('allow_video') && $type == 'video') {
            self::message('error', T_('Access Denied: allow_video is not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!AmpConfig::get('podcast') && ($type == 'podcast' || $type == 'podcast_episode')) {
            self::message('error', T_('Access Denied: podcast features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!AmpConfig::get('share') && $type == 'share') {
            self::message('error', T_('Access Denied: sharing features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        $user    = User::get_from_username(Session::username($input['auth']));
        $include = (int) $input['include'] == 1;
        $hide    = ((int) $input['hide_search'] == 1) || AmpConfig::get('hide_search', false);
        // confirm the correct data
        if (!in_array($type, array('song', 'album', 'artist', 'album_artist', 'playlist', 'podcast', 'podcast_episode', 'share', 'video'))) {
            self::message('error', T_('Incorrect object type') . ' ' . $type, '401', $input['api_format']);

            return false;
        }
        self::$browse->reset_filters();
        self::$browse->set_type($type);
        self::$browse->set_sort('name', 'ASC');

        $method = ($input['exact']) ? 'exact_match' : 'alpha_match';
        self::set_filter($method, $input['filter']);
        self::set_filter('add', $input['add']);
        self::set_filter('update', $input['update']);
        // set the album_artist filter (if enabled)
        if ((string) $input['type'] == 'album_artist') {
            self::set_filter('album_artist', true);
        }

        if ($type == 'playlist') {
            self::$browse->set_filter('playlist_type', $user->id);
            if (!$hide) {
                $objects = array_merge(self::$browse->get_objects(), Playlist::get_smartlists($user->id));
            } else {
                $objects = self::$browse->get_objects();
            }
        } else {
            $objects = self::$browse->get_objects();
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::indexes($objects, $type, $user->id, $include);
                break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::indexes($objects, $type, $user->id, true, $include);
        }
        Session::extend($input['auth']);

        return true;
    } // get_indexes

    /**
     * get_similar
     * MINIMUM_API_VERSION=420000
     *
     * Return similar artist id's or similar song ids compared to the input filter
     *
     * @param array $input
     * type   = (string) 'song'|'artist'
     * filter = (integer) artist id or song id
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function get_similar($input)
    {
        if (!self::check_parameter($input, array('type', 'filter'), 'get_similar')) {
            return false;
        }
        $type   = (string) $input['type'];
        $filter = (int) $input['filter'];
        // confirm the correct data
        if (!in_array($type, array('song', 'artist'))) {
            self::message('error', T_('Incorrect object type') . ' ' . $type, '401', $input['api_format']);

            return false;
        }

        $user    = User::get_from_username(Session::username($input['auth']));
        $objects = array();
        $similar = array();
        switch ($type) {
            case 'artist':
                $similar = Recommendation::get_artists_like($filter);
                break;
            case 'song':
                $similar = Recommendation::get_songs_like($filter);
        }
        foreach ($similar as $child) {
            $objects[] = $child['id'];
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::indexes($objects, $type, $user->id);
                break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::indexes($objects, $type, $user->id);
        }
        Session::extend($input['auth']);

        return true;
    } // get_similar

    /**
     * advanced_search
     * MINIMUM_API_VERSION=380001
     *
     * Perform an advanced search given passed rules. This works in a similar way to the web/UI search pages.
     * You can pass multiple rules as well as joins to create in depth search results
     *
     * Rules must be sent in groups of 3 using an int (starting from 1) to designate which rules are combined.
     * Use operator ('and'|'or') to choose whether to join or separate each rule when searching.
     *
     * Rule arrays must contain the following:
     *   * rule name (e.g. rule_1, rule_2)
     *   * rule operator (e.g. rule_1_operator, rule_2_operator)
     *   * rule input (e.g. rule_1_input, rule_2_input)
     *
     * Refer to the wiki for further information on rule_* types and data
     * https://github.com/ampache/ampache/wiki/XML-methods
     *
     * @param array $input
     * operator        = (string) 'and'|'or' (whether to match one rule or all)
     * rule_1          = (string)
     * rule_1_operator = (integer) 0,1|2|3|4|5|6
     * rule_1_input    = (mixed) The string, date, integer you are searching for
     * type            = (string) 'song', 'album', 'artist', 'playlist', 'label', 'user', 'video' (song by default)
     * offset          = (integer)
     * limit           = (integer))
     */
    public static function advanced_search($input)
    {
        ob_end_clean();

        $user    = User::get_from_username(Session::username($input['auth']));
        $results = Search::run($input, $user);

        $type = 'song';
        if (isset($input['type'])) {
            $type = $input['type'];
        }

        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                switch ($type) {
                    case 'artist':
                        echo JSON_Data::artists($results, array(), $user->id);
                        break;
                    case 'album':
                        echo JSON_Data::albums($results, array(), $user->id);
                        break;
                    case 'playlist':
                        echo JSON_Data::playlists($results, $user->id);
                        break;
                    case 'user':
                        echo JSON_Data::users($results);
                        break;
                    case 'video':
                        echo JSON_Data::videos($results, $user->id);
                        break;
                    default:
                        echo JSON_Data::songs($results, $user->id);
                        break;
                }
            break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                switch ($type) {
                    case 'artist':
                        echo Xml_Data::artists($results, array(), $user->id);
                        break;
                    case 'album':
                        echo Xml_Data::albums($results, array(), $user->id);
                        break;
                    case 'playlist':
                        echo Xml_Data::playlists($results, $user->id);
                        break;
                    case 'user':
                        echo Xml_Data::users($results);
                        break;
                    case 'video':
                        echo Xml_Data::videos($results, $user->id);
                        break;
                    default:
                        echo Xml_Data::songs($results, $user->id);
                        break;
                }
        }
        Session::extend($input['auth']);
    } // advanced_search

    /**
     * artists
     * MINIMUM_API_VERSION=380001
     *
     * This takes a collection of inputs and returns
     * artist objects. This function is deprecated!
     *
     * @param array $input
     * filter  = (string) Alpha-numeric search term //optional
     * exact   = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * add     = self::set_filter(date) //optional
     * update  = self::set_filter(date) //optional
     * offset  = (integer) //optional
     * limit   = (integer) //optional
     * include = (array) 'albums'|'songs' //optional
     */
    public static function artists($input)
    {
        self::$browse->reset_filters();
        self::$browse->set_type('artist');
        self::$browse->set_sort('name', 'ASC');

        $method = ($input['exact']) ? 'exact_match' : 'alpha_match';
        self::set_filter($method, $input['filter']);
        self::set_filter('add', $input['add']);
        self::set_filter('update', $input['update']);


        $artists = self::$browse->get_objects();
        $user    = User::get_from_username(Session::username($input['auth']));
        $include = (is_array($input['include'])) ? $input['include'] : explode(',', (string) $input['include']);

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::artists($artists, $include, $user->id);
            break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::artists($artists, $include, $user->id);
        }
        Session::extend($input['auth']);
    } // artists

    /**
     * artist
     * MINIMUM_API_VERSION=380001
     *
     * This returns a single artist based on the UID of said artist
     *
     * @param array $input
     * filter  = (string) Alpha-numeric search term
     * include = (array) 'albums'|'songs' //optional
     * @return boolean
     */
    public static function artist($input)
    {
        if (!self::check_parameter($input, array('filter'), 'artist')) {
            return false;
        }
        $uid     = scrub_in($input['filter']);
        $user    = User::get_from_username(Session::username($input['auth']));
        $include = (is_array($input['include'])) ? $input['include'] : explode(',', (string) $input['include']);
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::artists(array($uid), $include, $user->id);
            break;
            default:
                echo XML_Data::artists(array($uid), $include, $user->id);
        }
        Session::extend($input['auth']);

        return true;
    } // artist

    /**
     * artist_albums
     * MINIMUM_API_VERSION=380001
     *
     * This returns the albums of an artist
     *
     * @param array $input
     * filter = (string) UID of artist
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function artist_albums($input)
    {
        if (!self::check_parameter($input, array('filter'), 'artist_albums')) {
            return false;
        }
        $artist = new Artist($input['filter']);
        $albums = $artist->get_albums();
        $user   = User::get_from_username(Session::username($input['auth']));

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::albums($albums, array(), $user->id);
            break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::albums($albums, array(), $user->id);
        }
        Session::extend($input['auth']);

        return true;
    } // artist_albums

    /**
     * artist_songs
     * MINIMUM_API_VERSION=380001
     *
     * This returns the songs of the specified artist
     *
     * @param array $input
     * filter = (string) UID of Artist
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function artist_songs($input)
    {
        if (!self::check_parameter($input, array('filter'), 'artist_songs')) {
            return false;
        }
        $artist = new Artist($input['filter']);
        $songs  = $artist->get_songs();
        $user   = User::get_from_username(Session::username($input['auth']));

        if (!empty($songs)) {
            ob_end_clean();
            switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::songs($songs, $user->id);
            break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::songs($songs, $user->id);
            }
        }
        Session::extend($input['auth']);

        return true;
    } // artist_songs

    /**
     * albums
     * MINIMUM_API_VERSION=380001
     *
     * This returns albums based on the provided search filters
     *
     * @param array $input
     * filter  = (string) Alpha-numeric search term //optional
     * exact   = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * add     = self::set_filter(date) //optional
     * update  = self::set_filter(date) //optional
     * offset  = (integer) //optional
     * limit   = (integer) //optional
     * include = (array) 'songs' //optional
     */
    public static function albums($input)
    {
        self::$browse->reset_filters();
        self::$browse->set_type('album');
        self::$browse->set_sort('name', 'ASC');
        $method = ($input['exact']) ? 'exact_match' : 'alpha_match';
        self::set_filter($method, $input['filter']);
        self::set_filter('add', $input['add']);
        self::set_filter('update', $input['update']);

        $albums  = self::$browse->get_objects();
        $user    = User::get_from_username(Session::username($input['auth']));
        $include = (is_array($input['include'])) ? $input['include'] : explode(',', (string) $input['include']);

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::albums($albums, $include, $user->id);
            break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::albums($albums, $include, $user->id);
        }
        Session::extend($input['auth']);
    } // albums

    /**
     * album
     * MINIMUM_API_VERSION=380001
     *
     * This returns a single album based on the UID provided
     *
     * @param array $input
     * filter  = (string) UID of Album
     * include = (array) 'songs' //optional
     * @return boolean
     */
    public static function album($input)
    {
        if (!self::check_parameter($input, array('filter'), 'album')) {
            return false;
        }
        $uid     = (int) scrub_in($input['filter']);
        $user    = User::get_from_username(Session::username($input['auth']));
        $include = (is_array($input['include'])) ? $input['include'] : explode(',', (string) $input['include']);
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::albums(array($uid), $include, $user->id);
            break;
            default:
                echo XML_Data::albums(array($uid), $include, $user->id);
        }
        Session::extend($input['auth']);

        return true;
    } // album

    /**
     * album_songs
     * MINIMUM_API_VERSION=380001
     *
     * This returns the songs of a specified album
     *
     * @param array $input
     * filter = (string) UID of Album
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function album_songs($input)
    {
        if (!self::check_parameter($input, array('filter'), 'album_songs')) {
            return false;
        }
        $album = new Album($input['filter']);
        $songs = array();
        $user  = User::get_from_username(Session::username($input['auth']));

        ob_end_clean();

        // songs for all disks
        if (AmpConfig::get('album_group')) {
            $disc_ids = $album->get_group_disks_ids();
            foreach ($disc_ids as $discid) {
                $disc     = new Album($discid);
                $allsongs = $disc->get_songs();
                foreach ($allsongs as $songid) {
                    $songs[] = $songid;
                }
            }
        } else {
            // songs for just this disk
            $songs = $album->get_songs();
        }
        if (!empty($songs)) {
            switch ($input['api_format']) {
                case 'json':
                    JSON_Data::set_offset($input['offset']);
                    JSON_Data::set_limit($input['limit']);
                    echo JSON_Data::songs($songs, $user->id);
                break;
                default:
                    XML_Data::set_offset($input['offset']);
                    XML_Data::set_limit($input['limit']);
                    echo XML_Data::songs($songs, $user->id);
            }
        }
        Session::extend($input['auth']);

        return true;
    } // album_songs

    /**
     * licenses
     * MINIMUM_API_VERSION=420000
     *
     * This returns the licenses  based on the specified filter
     *
     * @param array $input
     * filter = (string) Alpha-numeric search term //optional
     * exact  = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function licenses($input)
    {
        if (!AmpConfig::get('licensing')) {
            self::message('error', T_('Access Denied: licensing features are not enabled.'), '400', $input['api_format']);

            return false;
        }

        self::$browse->reset_filters();
        self::$browse->set_type('license');
        self::$browse->set_sort('name', 'ASC');

        $method = ($input['exact']) ? 'exact_match' : 'alpha_match';
        self::set_filter($method, $input['filter']);
        $licenses = self::$browse->get_objects();

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::licenses($licenses);
                break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::licenses($licenses);
        }
        Session::extend($input['auth']);

        return true;
    } // licenses

    /**
     * license
     * MINIMUM_API_VERSION=420000
     *
     * This returns a single license based on UID
     *
     * @param array $input
     * filter = (string) UID of license
     * @return boolean
     */
    public static function license($input)
    {
        if (!AmpConfig::get('licensing')) {
            self::message('error', T_('Access Denied: licensing features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!self::check_parameter($input, array('filter'), 'license')) {
            return false;
        }
        $uid = array((int) scrub_in($input['filter']));
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::licenses($uid);
                break;
            default:
                echo XML_Data::licenses($uid);
        }
        Session::extend($input['auth']);

        return true;
    } // license

    /**
     * license_songs
     * MINIMUM_API_VERSION=420000
     *
     * This returns all songs attached to a license ID
     *
     * @param array $input
     * filter = (string) UID of license
     * @return boolean
     */
    public static function license_songs($input)
    {
        if (!AmpConfig::get('licensing')) {
            self::message('error', T_('Access Denied: licensing features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!self::check_parameter($input, array('filter'), 'license_songs')) {
            return false;
        }
        $user     = User::get_from_username(Session::username($input['auth']));
        $song_ids = License::get_license_songs((int) scrub_in($input['filter']));
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::songs($song_ids, $user->id);
                break;
            default:
                echo XML_Data::songs($song_ids, $user->id);
        }
        Session::extend($input['auth']);

        return true;
    } // license_songs

    /**
     * tags
     * MINIMUM_API_VERSION=380001
     *
     * This returns the tags (Genres) based on the specified filter
     *
     * @param array $input
     * filter = (string) Alpha-numeric search term //optional
     * exact  = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     */
    public static function tags($input)
    {
        self::$browse->reset_filters();
        self::$browse->set_type('tag');
        self::$browse->set_sort('name', 'ASC');

        $method = ($input['exact']) ? 'exact_match' : 'alpha_match';
        self::set_filter($method, $input['filter']);
        $tags = self::$browse->get_objects();

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::tags($tags);
            break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::tags($tags);
        }
        Session::extend($input['auth']);
    } // tags

    /**
     * tag
     * MINIMUM_API_VERSION=380001
     *
     * This returns a single tag based on UID
     *
     * @param array $input
     * filter = (string) UID of Tag
     * @return boolean
     */
    public static function tag($input)
    {
        if (!self::check_parameter($input, array('filter'), 'tag')) {
            return false;
        }
        $uid = scrub_in($input['filter']);
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::tags(array($uid));
            break;
            default:
                echo XML_Data::tags(array($uid));
        }
        Session::extend($input['auth']);

        return true;
    } // tag

    /**
     * tag_artists
     * MINIMUM_API_VERSION=380001
     *
     * This returns the artists associated with the tag in question as defined by the UID
     *
     * @param array $input
     * filter = (string) UID of Album
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function tag_artists($input)
    {
        if (!self::check_parameter($input, array('filter'), 'tag_artists')) {
            return false;
        }
        $artists = Tag::get_tag_objects('artist', $input['filter']);
        if (!empty($artists)) {
            $user = User::get_from_username(Session::username($input['auth']));

            ob_end_clean();
            switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::artists($artists, array(), $user->id);
            break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::artists($artists, array(), $user->id);
            }
        }
        Session::extend($input['auth']);

        return true;
    } // tag_artists

    /**
     * tag_albums
     * MINIMUM_API_VERSION=380001
     *
     * This returns the albums associated with the tag in question
     *
     * @param array $input
     * filter = (string) UID of Tag
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function tag_albums($input)
    {
        if (!self::check_parameter($input, array('filter'), 'tag_albums')) {
            return false;
        }
        $albums = Tag::get_tag_objects('album', $input['filter']);
        if (!empty($albums)) {
            $user = User::get_from_username(Session::username($input['auth']));
            XML_Data::set_offset($input['offset']);
            XML_Data::set_limit($input['limit']);

            ob_end_clean();
            switch ($input['api_format']) {
                case 'json':
                    JSON_Data::set_offset($input['offset']);
                    JSON_Data::set_limit($input['limit']);
                    echo JSON_Data::albums($albums, array(), $user->id);
                break;
                default:
                    XML_Data::set_offset($input['offset']);
                    XML_Data::set_limit($input['limit']);
                    echo XML_Data::albums($albums, array(), $user->id);
            }
        }
        Session::extend($input['auth']);

        return true;
    } // tag_albums

    /**
     * tag_songs
     * MINIMUM_API_VERSION=380001
     *
     * returns the songs for this tag
     *
     * @param array $input
     * filter = (string) UID of Tag
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function tag_songs($input)
    {
        if (!self::check_parameter($input, array('filter'), 'tag_songs')) {
            return false;
        }
        $songs = Tag::get_tag_objects('song', $input['filter']);
        $user  = User::get_from_username(Session::username($input['auth']));

        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);

        ob_end_clean();
        if (!empty($songs)) {
            switch ($input['api_format']) {
                case 'json':
                    JSON_Data::set_offset($input['offset']);
                    JSON_Data::set_limit($input['limit']);
                    echo JSON_Data::songs($songs, $user->id);
                break;
                default:
                    XML_Data::set_offset($input['offset']);
                    XML_Data::set_limit($input['limit']);
                    echo XML_Data::songs($songs, $user->id);
            }
        }
        Session::extend($input['auth']);

        return true;
    } // tag_songs

    /**
     * songs
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=420000
     *
     * Returns songs based on the specified filter
     * All calls that return songs now include <playlisttrack> which can be used to identify track order.
     *
     * @param array $input
     * filter = (string) Alpha-numeric search term //optional
     * exact  = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * add    = self::set_filter(date) //optional
     * update = self::set_filter(date) //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     */
    public static function songs($input)
    {
        self::$browse->reset_filters();
        self::$browse->set_type('song');
        self::$browse->set_sort('title', 'ASC');

        $method = ($input['exact']) ? 'exact_match' : 'alpha_match';
        self::set_filter($method, $input['filter']);
        self::set_filter('add', $input['add']);
        self::set_filter('update', $input['update']);
        // Filter out disabled songs
        self::set_filter('enabled', '1');

        $songs = self::$browse->get_objects();
        $user  = User::get_from_username(Session::username($input['auth']));

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::songs($songs, $user->id);
            break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::songs($songs, $user->id);
        }
        Session::extend($input['auth']);
    } // songs

    /**
     * song
     * MINIMUM_API_VERSION=380001
     *
     * return a single song
     *
     * @param array $input
     * filter = (string) UID of song
     * @return boolean
     */
    public static function song($input)
    {
        if (!self::check_parameter($input, array('filter'), 'song')) {
            return false;
        }
        $song_id = scrub_in($input['filter']);
        $user    = User::get_from_username(Session::username($input['auth']));

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::songs(array((int) $song_id), $user->id);
            break;
            default:
                echo XML_Data::songs(array((int) $song_id), $user->id);
        }
        Session::extend($input['auth']);

        return true;
    } // song

    /**
     * playlists
     * MINIMUM_API_VERSION=380001
     *
     * This returns playlists based on the specified filter
     *
     * @param array $input
     * filter      = (string) Alpha-numeric search term (match all if missing) //optional
     * exact       = (integer) 0,1, if true filter is exact rather then fuzzy //optional
     * add         = self::set_filter(date) //optional
     * update      = self::set_filter(date) //optional
     * offset      = (integer) //optional
     * limit       = (integer) //optional
     * hide_search = (integer) 0,1, if true do not include searches/smartlists in the result //optional
     */
    public static function playlists($input)
    {
        $user     = User::get_from_username(Session::username($input['auth']));
        $like     = ((int) $input['exact'] == 1) ? false : true;
        $hide     = ((int) $input['hide_search'] == 1) || AmpConfig::get('hide_search', false);

        // regular playlists
        $playlist_ids = Playlist::get_playlists($user->id, (string) $input['filter'], $like);
        // merge with the smartlists
        if (!$hide) {
            $playlist_ids = array_merge($playlist_ids, Playlist::get_smartlists($user->id, (string) $input['filter'], $like));
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::playlists($playlist_ids, $user->id);
            break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::playlists($playlist_ids, $user->id);
        }
        Session::extend($input['auth']);
    } // playlists

    /**
     * playlist
     * MINIMUM_API_VERSION=380001
     *
     * This returns a single playlist
     *
     * @param array $input
     * filter = (string) UID of playlist
     * @return boolean
     */
    public static function playlist($input)
    {
        if (!self::check_parameter($input, array('filter'), 'playlist')) {
            return false;
        }
        $user    = User::get_from_username(Session::username($input['auth']));
        $list_id = scrub_in($input['filter']);

        if (str_replace('smart_', '', $list_id) === $list_id) {
            // Playlists
            $playlist = new Playlist((int) $list_id);
        } else {
            // Smartlists
            $playlist = new Search((int) str_replace('smart_', '', $list_id), 'song', $user);
        }
        if (!$playlist->type == 'public' && (!$playlist->has_access($user->id) && !Access::check('interface', 100, $user->id))) {
            self::message('error', T_('Access denied to this playlist'), '401', $input['api_format']);

            return false;
        }
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::playlists(array($list_id), $user->id);
            break;
            default:
                echo XML_Data::playlists(array($list_id), $user->id);
        }
        Session::extend($input['auth']);

        return true;
    } // playlist

    /**
     * playlist_songs
     * MINIMUM_API_VERSION=380001
     *
     * This returns the songs for a playlist
     *
     * @param array $input
     * filter = (string) UID of playlist
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function playlist_songs($input)
    {
        if (!self::check_parameter($input, array('filter'), 'playlist_songs')) {
            return false;
        }
        $user = User::get_from_username(Session::username($input['auth']));
        $uid  = scrub_in($input['filter']);
        debug_event(self::class, 'User ' . $user->id . ' loading playlist: ' . $input['filter'], 5);
        if (str_replace('smart_', '', $uid) === $uid) {
            // Playlists
            $playlist = new Playlist((int) $uid);
        } else {
            // Smartlists
            $playlist = new Search((int) str_replace('smart_', '', $uid), 'song', $user);
        }
        if (!$playlist->type == 'public' && (!$playlist->has_access($user->id) && !Access::check('interface', 100, $user->id))) {
            self::message('error', T_('Access denied to this playlist'), '401', $input['api_format']);

            return false;
        }

        $items = $playlist->get_items();
        $songs = array();
        foreach ($items as $object) {
            if ($object['object_type'] == 'song') {
                $songs[] = $object['object_id'];
            }
        } // end foreach

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::songs($songs, $user->id);
            break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::songs($songs, $user->id);
        }
        Session::extend($input['auth']);

        return true;
    } // playlist_songs

    /**
     * playlist_create
     * MINIMUM_API_VERSION=380001
     *
     * This create a new playlist and return it
     *
     * @param array $input
     * name = (string) Alpha-numeric search term
     * type = (string) 'public'|'private'
     * @return boolean
     */
    public static function playlist_create($input)
    {
        if (!self::check_parameter($input, array('name', 'type'), 'playlist_create')) {
            return false;
        }
        $name    = $input['name'];
        $type    = $input['type'];
        $user    = User::get_from_username(Session::username($input['auth']));
        $user_id = $user->id;
        if ($type != 'private') {
            $type = 'public';
        }

        $uid = Playlist::create($name, $type, $user->id);
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::playlists(array($uid), $user_id);
            break;
            default:
                echo XML_Data::playlists(array($uid), $user_id);
        }
        Session::extend($input['auth']);

        return true;
    } // playlist_create

    /**
     * playlist_edit
     * MINIMUM_API_VERSION=400001
     * CHANGED_IN_API_VERSION=400003
     *
     * This modifies name and type of playlist.
     * Changed name and type to optional and the playlist id is mandatory
     *
     * @param array $input
     * filter = (string) UID of playlist
     * name   = (string) 'new playlist name' //optional
     * type   = (string) 'public', 'private' //optional
     * items  = (string) comma-separated song_id's (replace existing items with a new object_id) //optional
     * tracks = (string) comma-separated playlisttrack numbers matched to items in order //optional
     * sort   = (integer) 0,1 sort the playlist by 'Artist, Album, Song' //optional
     * @return boolean
     */
    public static function playlist_edit($input)
    {
        if (!self::check_parameter($input, array('filter'), 'playlist_edit')) {
            return false;
        }
        $name  = $input['name'];
        $type  = $input['type'];
        $items = explode(',', (string) $input['items']);
        $order = explode(',', (string) $input['tracks']);
        $sort  = (int) $input['sort'];
        // calculate whether we are editing the track order too
        $playlist_edit = array();
        if (count($items) == count($order) && count($items) > 0) {
            $playlist_edit = array_combine($order, $items);
        }

        $user = User::get_from_username(Session::username($input['auth']));
        ob_end_clean();
        $playlist = new Playlist($input['filter']);

        // don't continue if you didn't actually get a playlist or the access level
        if (!$playlist->id || (!$playlist->has_access($user->id) && !Access::check('interface', 100, $user->id))) {
            self::message('error', T_('Access denied to this playlist'), '401', $input['api_format']);

            return false;
        }
        // update name/type
        if ($name || $type) {
            $array = [
                "name" => $name,
                "pl_type" => $type,
            ];
            $playlist->update($array);
        }
        $change_made = false;
        // update track order with new id's
        if (!empty($playlist_edit)) {
            foreach ($playlist_edit as $track => $song) {
                if ($song > 0 && $track > 0) {
                    $playlist->set_by_track_number((int) $song, (int) $track);
                    $change_made = true;
                }
            }
        }
        if ($sort > 0) {
            $playlist->sort_tracks();
            $change_made = true;
        }
        Session::extend($input['auth']);
        // if you didn't make any changes; tell me
        if (!($name || $type) && !$change_made) {
            self::message('error', T_('Nothing was changed'), '401', $input['api_format']);

            return false;
        }
        self::message('success', 'playlist changes saved', null, $input['api_format']);

        return true;
    } // playlist_edit

    /**
     * playlist_delete
     * MINIMUM_API_VERSION=380001
     *
     * This deletes a playlist
     *
     * @param array $input
     * filter = (string) UID of playlist
     * @return boolean
     */
    public static function playlist_delete($input)
    {
        if (!self::check_parameter($input, array('filter'), 'playlist_delete')) {
            return false;
        }
        $user = User::get_from_username(Session::username($input['auth']));
        ob_end_clean();
        $playlist = new Playlist($input['filter']);
        if (!$playlist->has_access($user->id) && !Access::check('interface', 100, $user->id)) {
            self::message('error', T_('Access denied to this playlist'), '401', $input['api_format']);
        } else {
            $playlist->delete();
            self::message('success', 'playlist deleted', null, $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    } // playlist_delete

    /**
     * playlist_add_song
     * MINIMUM_API_VERSION=380001
     *
     * This adds a song to a playlist
     *
     * @param array $input
     * filter = (string) UID of playlist
     * song   = (string) UID of song to add to playlist
     * check  = (integer) 0,1 Check for duplicates //optional, default = 0
     * @return boolean
     */
    public static function playlist_add_song($input)
    {
        if (!self::check_parameter($input, array('filter', 'song'), 'playlist_add_song')) {
            return false;
        }
        $user = User::get_from_username(Session::username($input['auth']));
        ob_end_clean();
        $playlist = new Playlist($input['filter']);
        $song     = (int) $input['song'];
        if (!$playlist->has_access($user->id) && !Access::check('interface', 100, $user->id)) {
            self::message('error', T_('Access denied to this playlist'), '401', $input['api_format']);

            return false;
        }
        $unique = ((bool) AmpConfig::get('unique_playlist') || (int) $input['check'] == 1);
        if (($unique) && in_array($song, $playlist->get_songs())) {
            self::message('error', T_("Can't add a duplicate item when check is enabled"), '400', $input['api_format']);

            return false;
        }
        $playlist->add_songs(array($song), $unique);
        self::message('success', 'song added to playlist', null, $input['api_format']);
        Session::extend($input['auth']);

        return true;
    } // playlist_add_song

    /**
     * playlist_remove_song
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=400001
     * CHANGED_IN_API_VERSION=420000
     *
     * This removes a song from a playlist using track number in the list or song ID.
     * Pre-400001 the api required 'track' instead of 'song'.
     * 420000+: added clear to allow you to clear a playlist without getting all the tracks.
     *
     * @param array $input
     * filter = (string) UID of playlist
     * song   = (string) UID of song to remove from the playlist //optional
     * track  = (string) track number to remove from the playlist //optional
     * clear  = (integer) 0,1 Clear the whole playlist //optional, default = 0
     * @return boolean
     */
    public static function playlist_remove_song($input)
    {
        if (!self::check_parameter($input, array('filter'), 'playlist_remove_song')) {
            return false;
        }
        $user = User::get_from_username(Session::username($input['auth']));
        ob_end_clean();
        $playlist = new Playlist($input['filter']);
        if (!$playlist->has_access($user->id) && !Access::check('interface', 100, $user->id)) {
            self::message('error', T_('Access denied to this playlist'), '401', $input['api_format']);
        } else {
            if ((int) $input['clear'] === 1) {
                $playlist->delete_all();
                self::message('success', 'all songs removed from playlist', null, $input['api_format']);
            } elseif ($input['song']) {
                $track = (int) scrub_in($input['song']);
                if (!$playlist->has_item($track)) {
                    self::message('error', T_('Song not found in playlist'), '404', $input['api_format']);

                    return false;
                }
                $playlist->delete_song($track);
                $playlist->regenerate_track_numbers();
                self::message('success', 'song removed from playlist', null, $input['api_format']);
            } elseif ($input['track']) {
                $track = (int) scrub_in($input['track']);
                if (!$playlist->has_item(null, $track)) {
                    self::message('error', T_('Track ID not found in playlist'), '404', $input['api_format']);

                    return false;
                }
                $playlist->delete_track_number($track);
                $playlist->regenerate_track_numbers();
                self::message('success', 'song removed from playlist', null, $input['api_format']);
            }
        }
        Session::extend($input['auth']);

        return true;
    } // playlist_remove_song

    /**
     * playlist_generate
     * MINIMUM_API_VERSION=400001
     * CHANGED_IN_API_VERSION=400002
     *
     * Get a list of song xml, indexes or id's based on some simple search criteria
     * 'recent' will search for tracks played after 'Statistics Day Threshold' days
     * 'forgotten' will search for tracks played before 'Statistics Day Threshold' days
     * 'unplayed' added in 400002 for searching unplayed tracks.
     *
     * @param array $input
     * mode   = (string)  'recent'|'forgotten'|'unplayed'|'random' //optional, default = 'random'
     * filter = (string)  $filter                       //optional, LIKE matched to song title
     * album  = (integer) $album_id                     //optional
     * artist = (integer) $artist_id                    //optional
     * flag   = (integer) 0,1                           //optional, default = 0
     * format = (string)  'song'|'index'|'id'           //optional, default = 'song'
     * offset = (integer)                               //optional
     * limit  = (integer)                               //optional
     */
    public static function playlist_generate($input)
    {
        // parameter defaults
        $mode   = (!in_array($input['mode'], array('forgotten', 'recent', 'unplayed', 'random'), true)) ? 'random' : $input['mode'];
        $format = (!in_array($input['format'], array('song', 'index', 'id'), true)) ? 'song' : $input['format'];
        $user   = User::get_from_username(Session::username($input['auth']));
        $array  = array();

        // count for search rules
        $rule_count = 1;

        $array['type'] = 'song';
        if (in_array($mode, array('forgotten', 'recent'), true)) {
            debug_event(self::class, 'playlist_generate ' . $mode, 5);
            // played songs
            $array['rule_' . $rule_count]               = 'myplayed';
            $array['rule_' . $rule_count . '_operator'] = 0;
            $rule_count++;

            // not played for a while or played recently
            $array['rule_' . $rule_count]               = 'last_play';
            $array['rule_' . $rule_count . '_input']    = AmpConfig::get('stats_threshold');
            $array['rule_' . $rule_count . '_operator'] = ($mode == 'recent') ? 0 : 1;
            $rule_count++;
        } elseif ($mode == 'unplayed') {
            debug_event(self::class, 'playlist_generate unplayed', 5);
            // unplayed songs
            $array['rule_' . $rule_count]               = 'myplayed';
            $array['rule_' . $rule_count . '_operator'] = 1;
            $rule_count++;
        } else {
            debug_event(self::class, 'playlist_generate random', 5);
            // random / anywhere
            $array['rule_' . $rule_count]               = 'anywhere';
            $array['rule_' . $rule_count . '_input']    = '%';
            $array['rule_' . $rule_count . '_operator'] = 0;
            $rule_count++;
        }
        // additional rules
        if ((int) $input['flag'] == 1) {
            debug_event(self::class, 'playlist_generate flagged', 5);
            $array['rule_' . $rule_count]               = 'favorite';
            $array['rule_' . $rule_count . '_input']    = '%';
            $array['rule_' . $rule_count . '_operator'] = 0;
            $rule_count++;
        }
        if (array_key_exists('filter', $input)) {
            $array['rule_' . $rule_count]               = 'title';
            $array['rule_' . $rule_count . '_input']    = (string) $input['filter'];
            $array['rule_' . $rule_count . '_operator'] = 0;
            $rule_count++;
        }
        $album = new Album((int) $input['album']);
        if ((array_key_exists('album', $input)) && ($album->id == $input['album'])) {
            // set rule
            $array['rule_' . $rule_count]               = 'album';
            $array['rule_' . $rule_count . '_input']    = $album->full_name;
            $array['rule_' . $rule_count . '_operator'] = 4;
            $rule_count++;
        }
        $artist = new Artist((int) $input['artist']);
        if ((array_key_exists('artist', $input)) && ($artist->id == $input['artist'])) {
            // set rule
            $array['rule_' . $rule_count]               = 'artist';
            $array['rule_' . $rule_count . '_input']    = trim(trim((string) $artist->prefix) . ' ' . trim((string) $artist->name));
            $array['rule_' . $rule_count . '_operator'] = 4;
        }

        ob_end_clean();
        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);

        // get db data
        $song_ids = Search::run($array, $user);
        shuffle($song_ids);

        //slice the array if there is a limit
        if ((int) $input['limit'] > 0) {
            $song_ids = array_slice($song_ids, 0, (int) $input['limit']);
        }

        // output formatted XML
        switch ($format) {
            case 'id':
                switch ($input['api_format']) {
                    case 'json':
                        echo json_encode($song_ids, JSON_PRETTY_PRINT);
                    break;
                    default:
                        echo XML_Data::keyed_array($song_ids, false, 'id');
                }
                break;
            case 'index':
                switch ($input['api_format']) {
                    case 'json':
                        echo JSON_Data::indexes($song_ids, 'song', $user->id);
                    break;
                    default:
                        echo XML_Data::indexes($song_ids, 'song', $user->id);
                }
                break;
            case 'song':
            default:
                switch ($input['api_format']) {
                    case 'json':
                        echo JSON_Data::songs($song_ids, $user->id);
                    break;
                    default:
                        echo XML_Data::songs($song_ids, $user->id);
                }
        }
        Session::extend($input['auth']);
    } // playlist_generate

    /**
     * search_songs
     * MINIMUM_API_VERSION=380001
     *
     * This searches the songs and returns... songs
     *
     * @param array $input
     * filter = (string) Alpha-numeric search term
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function search_songs($input)
    {
        if (!self::check_parameter($input, array('filter'), 'search_songs')) {
            return false;
        }
        $array                    = array();
        $array['type']            = 'song';
        $array['rule_1']          = 'anywhere';
        $array['rule_1_input']    = $input['filter'];
        $array['rule_1_operator'] = 0;

        $results = Search::run($array);
        $user    = User::get_from_username(Session::username($input['auth']));

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::songs($results, $user->id);
            break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::songs($results, $user->id);
        }
        Session::extend($input['auth']);

        return true;
    } // search_songs

    /**
     * shares
     * MINIMUM_API_VERSION=420000
     *
     * Get information about shared media this user is allowed to manage.
     *
     * @param array $input
     * filter = (string) Alpha-numeric search term //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function shares($input)
    {
        if (!AmpConfig::get('share')) {
            self::message('error', T_('Access Denied: sharing features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        self::$browse->reset_filters();
        self::$browse->set_type('share');
        self::$browse->set_sort('title', 'ASC');

        $method = ($input['exact']) ? 'exact_match' : 'alpha_match';
        self::set_filter($method, $input['filter']);
        self::set_filter('add', $input['add']);
        self::set_filter('update', $input['update']);

        $shares = self::$browse->get_objects();

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::shares($shares);
            break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::shares($shares);
        }
        Session::extend($input['auth']);

        return true;
    } // shares

    /**
     * share
     * MINIMUM_API_VERSION=420000
     *
     * Get the share from it's id.
     *
     * @param array $input
     * filter = (integer) Share ID number
     * @return boolean
     */
    public static function share($input)
    {
        if (!AmpConfig::get('share')) {
            self::message('error', T_('Access Denied: sharing features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!self::check_parameter($input, array('filter'), 'share')) {
            return false;
        }
        $share = array((int) $input['filter']);

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::shares($share);
            break;
            default:
                echo XML_Data::shares($share);
        }
        Session::extend($input['auth']);

        return true;
    } // share

    /**
     * share_create
     * MINIMUM_API_VERSION=420000
     * Create a public url that can be used by anyone to stream media.
     * Takes the file id with optional description and expires parameters.
     *
     * @param array $input
     * filter      = (string) object_id
     * type        = (string) object_type
     * description = (string) description (will be filled for you if empty) //optional
     * expires     = (integer) days to keep active //optional
     * @return boolean
     */
    public static function share_create($input)
    {
        if (!AmpConfig::get('share')) {
            self::message('error', T_('Access Denied: sharing features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!self::check_parameter($input, array('type', 'filter'), 'share_create')) {
            return false;
        }
        $description = $input['description'];
        $object_id   = $input['filter'];
        $object_type = $input['type'];
        $download    = Access::check_function('download');
        $expire_days = Share::get_expiry($input['expires']);
        // confirm the correct data
        if (!in_array($object_type, array('song', 'album', 'artist'))) {
            self::message('error', T_('Wrong object type ' . $object_type), '401', $input['api_format']);

            return false;
        }
        $share = array();
        if (!Core::is_library_item($object_type) || !$object_id) {
            self::message('error', T_('Wrong library item type'), '401', $input['api_format']);
        } else {
            $item = new $object_type($object_id);
            if (!$item->id) {
                self::message('error', T_('Library item not found'), '404', $input['api_format']);

                return false;
            }
            $share[] = Share::create_share($object_type, $object_id, true, $download, $expire_days, generate_password(8), 0, $description);
        }
        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::shares($share);
                break;
            default:
                echo XML_Data::shares($share);
        }
        Session::extend($input['auth']);

        return true;
    } // share_create

    /**
     * share_delete
     *
     * MINIMUM_API_VERSION=420000
     *
     * Delete an existing share.
     *
     * @param array $input
     * filter = (string) UID of share to delete
     * @return boolean
     */
    public static function share_delete($input)
    {
        if (!AmpConfig::get('share')) {
            self::message('error', T_('Access Denied: sharing features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!self::check_parameter($input, array('filter'), 'share_delete')) {
            return false;
        }
        $user      = User::get_from_username(Session::username($input['auth']));
        $object_id = $input['filter'];
        if (in_array($object_id, Share::get_share_list())) {
            if (Share::delete_share($object_id, $user)) {
                self::message('success', 'share ' . $object_id . ' deleted', null, $input['api_format']);
            } else {
                self::message('error', 'share ' . $object_id . ' was not deleted', '401', $input['api_format']);
            }
        } else {
            self::message('error', 'share ' . $object_id . ' was not found', '404', $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    } // share_delete

    /**
     * share_edit
     * MINIMUM_API_VERSION=420000
     * Update the description and/or expiration date for an existing share.
     * Takes the share id to update with optional description and expires parameters.
     *
     * @param array $input
     * filter      = (string) Alpha-numeric search term
     * stream      = (boolean) 0,1 // optional
     * download    = (boolean) 0,1 // optional
     * expires     = (integer) number of whole days before expiry // optional
     * description = (string) update description // optional
     * @return boolean
     */
    public static function share_edit($input)
    {
        if (!AmpConfig::get('share')) {
            self::message('error', T_('Access Denied: sharing features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!self::check_parameter($input, array('filter'), 'share_edit')) {
            return false;
        }
        $share_id = $input['filter'];
        if (in_array($share_id, Share::get_share_list())) {
            $share       = new Share($share_id);
            $description = isset($input['description']) ? $input['description'] : $share->description;
            $stream      = isset($input['stream']) ? $input['stream'] : $share->allow_stream;
            $download    = isset($input['download']) ? $input['download'] : $share->allow_download;
            $expires     = isset($input['expires']) ? Share::get_expiry($input['expires']) : $share->expire_days;
            $user        = User::get_from_username(Session::username($input['auth']));

            $data = array(
                'max_counter' => $share->max_counter,
                'expire' => $expires,
                'allow_stream' => $stream,
                'allow_download' => $download,
                'description' => $description
            );
            if ($share->update($data, $user)) {
                self::message('success', 'share ' . $share_id . ' updated', null, $input['api_format']);
            } else {
                self::message('error', 'share ' . $share_id . ' was not updated', '401', $input['api_format']);
            }
        } else {
            self::message('error', 'share ' . $share_id . ' was not found', '404', $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    } // share_edit

    /**
     * videos
     * This returns video objects!
     *
     * @param array $input
     * filter = (string) Alpha-numeric search term //optional
     * exact  = (integer) 0,1, Whether to match the exact term or not //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     */
    public static function videos($input)
    {
        self::$browse->reset_filters();
        self::$browse->set_type('video');
        self::$browse->set_sort('title', 'ASC');

        $method = ($input['exact']) ? 'exact_match' : 'alpha_match';
        self::set_filter($method, $input['filter']);

        $video_ids = self::$browse->get_objects();
        $user      = User::get_from_username(Session::username($input['auth']));

        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::videos($video_ids, $user->id);
            break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::videos($video_ids, $user->id);
        }
        Session::extend($input['auth']);
    } // videos

    /**
     * video
     * This returns a single video
     *
     * @param array $input
     * filter = (string) UID of video
     * @return boolean
     */
    public static function video($input)
    {
        if (!self::check_parameter($input, array('filter'), 'video')) {
            return false;
        }
        $video_id = scrub_in($input['filter']);
        $user     = User::get_from_username(Session::username($input['auth']));

        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::videos(array($video_id), $user->id);
            break;
            default:
                echo XML_Data::videos(array($video_id), $user->id);
        }
        Session::extend($input['auth']);

        return true;
    } // video

    /**
     * stats
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=400001
     *
     * Get some items based on some simple search types and filters
     * This method has partial backwards compatibility with older api versions
     * but should be updated to follow the current input values
     *
     * @param array $input
     * type     = (string)  'song'|'album'|'artist'
     * filter   = (string)  'newest'|'highest'|'frequent'|'recent'|'forgotten'|'flagged'|'random'
     * user_id  = (integer) //optional
     * username = (string)  //optional
     * offset   = (integer) //optional
     * limit    = (integer) //optional
     * @return boolean
     */
    public static function stats($input)
    {
        if (!self::check_parameter($input, array('type', 'filter'), 'stats')) {
            return false;
        }
        // set a default user
        $user    = User::get_from_username(Session::username($input['auth']));
        $user_id = $user->id;
        // override your user if you're looking at others
        if ($input['username']) {
            $user    = User::get_from_username($input['username']);
            $user_id = $user->id;
        } elseif ($input['user_id']) {
            $user_id = (int) $input['user_id'];
            $user    = new User($user_id);
        }
        // moved type to filter and allowed multiple type selection
        $type   = $input['type'];
        $offset = (int) $input['offset'];
        $limit  = (int) $input['limit'];
        // original method only searched albums and had poor method inputs
        if (in_array($type, array('newest', 'highest', 'frequent', 'recent', 'forgotten', 'flagged'))) {
            $type            = 'album';
            $input['filter'] = $type;
        }
        if ($limit < 1) {
            $limit = (int) AmpConfig::get('popular_threshold', 10);
        }

        switch ($input['filter']) {
            case 'newest':
                debug_event(self::class, 'stats newest', 5);
                $results = Stats::get_newest($type, $limit, $offset);
                break;
            case 'highest':
                debug_event(self::class, 'stats highest', 4);
                $results = Rating::get_highest($type, $limit, $offset);
                break;
            case 'frequent':
                debug_event(self::class, 'stats frequent', 4);
                $threshold = AmpConfig::get('stats_threshold');
                $results   = Stats::get_top($type, $limit, $threshold, $offset);
                break;
            case 'recent':
            case 'forgotten':
                debug_event(self::class, 'stats ' . $input['filter'], 4);
                $newest = $input['filter'] == 'recent';
                if ($user->id) {
                    $results = $user->get_recently_played($type, $limit, $offset, $newest);
                } else {
                    $results = Stats::get_recent($type, $limit, $offset, $newest);
                }
                break;
            case 'flagged':
                debug_event(self::class, 'stats flagged', 4);
                $results = Userflag::get_latest($type, $user_id, $limit, $offset);
                break;
            case 'random':
            default:
                debug_event(self::class, 'stats random ' . $type, 4);
                switch ($type) {
                    case 'song':
                        $results = Random::get_default($limit, $user_id);
                        break;
                    case 'artist':
                        $results = Artist::get_random($limit, false, $user_id);
                        break;
                    case 'album':
                        $results = Album::get_random($limit, false, $user_id);
                    }
        }

        ob_end_clean();
        if (!isset($results)) {
            self::message('error', 'No Results', '404', $input['api_format']);

            return false;
        }

        debug_event(self::class, 'stats found results searching for ' . $type, 5);
        if ($type === 'song') {
            switch ($input['api_format']) {
                case 'json':
                    echo JSON_Data::songs($results, $user->id);
                break;
                default:
                    echo XML_Data::songs($results, $user->id);
            }
        }
        if ($type === 'artist') {
            switch ($input['api_format']) {
                case 'json':
                    echo JSON_Data::artists($results, array(), $user->id);
                break;
                default:
                    echo XML_Data::artists($results, array(), $user->id);
            }
        }
        if ($type === 'album') {
            switch ($input['api_format']) {
                case 'json':
                    echo JSON_Data::albums($results, array(), $user->id);
                break;
                default:
                    echo XML_Data::albums($results, array(), $user->id);
            }
        }
        Session::extend($input['auth']);

        return true;
    } // stats

    /**
     * podcasts
     * MINIMUM_API_VERSION=420000
     *
     * Get information about podcasts.
     *
     * @param array $input
     * filter  = (string) Alpha-numeric search term
     * include = (string) 'episodes' (include episodes in the response) // optional
     * offset  = (integer) //optional
     * limit   = (integer) //optional
     * @return boolean
     */
    public static function podcasts($input)
    {
        if (!AmpConfig::get('podcast')) {
            self::message('error', T_('Access Denied: podcast features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        self::$browse->reset_filters();
        self::$browse->set_type('podcast');
        self::$browse->set_sort('title', 'ASC');

        $method = ($input['exact']) ? 'exact_match' : 'alpha_match';
        self::set_filter($method, $input['filter']);
        self::set_filter('add', $input['add']);
        self::set_filter('update', $input['update']);

        $podcasts = self::$browse->get_objects();
        $episodes = $input['include'] == 'episodes';
        $user     = User::get_from_username(Session::username($input['auth']));
        $user_id  = $user->id;

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::podcasts($podcasts, $user_id, $episodes);
                break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::podcasts($podcasts, $user_id, $episodes);
        }
        Session::extend($input['auth']);

        return true;
    } // podcasts

    /**
     * podcast
     * MINIMUM_API_VERSION=420000
     *
     * Get the podcast from it's id.
     *
     * @param array $input
     * filter  = (integer) Podcast ID number
     * include = (string) 'episodes' (include episodes in the response) // optional
     * @return boolean
     */
    public static function podcast($input)
    {
        if (!AmpConfig::get('podcast')) {
            self::message('error', T_('Access Denied: podcast features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!self::check_parameter($input, array('filter'), 'podcast')) {
            return false;
        }
        $object_id = (int) $input['filter'];
        $podcast   = new Podcast($object_id);
        if ($podcast->id > 0) {
            $user     = User::get_from_username(Session::username($input['auth']));
            $user_id  = $user->id;
            $episodes = $input['include'] == 'episodes';

            ob_end_clean();
            switch ($input['api_format']) {
                case 'json':
                    echo JSON_Data::podcasts(array($object_id), $user_id, $episodes);
                    break;
                default:
                    echo XML_Data::podcasts(array($object_id), $user_id, $episodes);
            }
        } else {
            self::message('error', 'podcast ' . $object_id . ' was not found', '404', $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    } // podcast

    /**
     * podcast_create
     * MINIMUM_API_VERSION=420000
     * Create a public url that can be used by anyone to stream media.
     * Takes the file id with optional description and expires parameters.
     *
     * @param array $input
     * url     = (string) rss url for podcast
     * catalog = (string) podcast catalog
     * @return boolean
     */
    public static function podcast_create($input)
    {
        if (!AmpConfig::get('podcast')) {
            self::message('error', T_('Access Denied: podcast features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!self::check_access('interface', 75, User::get_from_username(Session::username($input['auth']))->id, 'update_podcast', $input['api_format'])) {
            return false;
        }
        if (!self::check_parameter($input, array('url', 'catalog'), 'podcast_create')) {
            return false;
        }
        $data            = array();
        $data['feed']    = $input['url'];
        $data['catalog'] = $input['catalog'];
        $podcast         = Podcast::create($data, true);
        if ($podcast) {
            $user    = User::get_from_username(Session::username($input['auth']));
            $user_id = $user->id;
            ob_end_clean();
            switch ($input['api_format']) {
                case 'json':
                    echo JSON_Data::podcasts(array($podcast), $user_id);
                    break;
                default:
                    echo XML_Data::podcasts(array($podcast), $user_id);
            }
        } else {
            self::message('error', T_('Failed: podcast was not created.'), '401', $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    } // podcast_create

    /**
     * podcast_delete
     *
     * MINIMUM_API_VERSION=420000
     *
     * Delete an existing podcast.
     *
     * @param array $input
     * filter = (string) UID of podcast to delete
     * @return boolean
     */
    public static function podcast_delete($input)
    {
        if (!AmpConfig::get('podcast')) {
            self::message('error', T_('Access Denied: podcast features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!self::check_access('interface', 75, User::get_from_username(Session::username($input['auth']))->id, 'update_podcast', $input['api_format'])) {
            return false;
        }
        if (!self::check_parameter($input, array('filter'), 'podcast_delete')) {
            return false;
        }
        $object_id = (int) $input['filter'];
        $podcast   = new Podcast($object_id);
        if ($podcast->id > 0) {
            if ($podcast->remove()) {
                self::message('success', 'podcast ' . $object_id . ' deleted', null, $input['api_format']);
            } else {
                self::message('error', 'podcast ' . $object_id . ' was not deleted', '401', $input['api_format']);
            }
        } else {
            self::message('error', 'podcast ' . $object_id . ' was not found', '404', $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    } // podcast_delete

    /**
     * podcast_edit
     * MINIMUM_API_VERSION=420000
     * Update the description and/or expiration date for an existing podcast.
     * Takes the podcast id to update with optional description and expires parameters.
     *
     * @param array $input
     * filter      = (string) Alpha-numeric search term
     * feed        = (string) feed url (xml!) //optional
     * title       = (string) title string //optional
     * website     = (string) source website url //optional
     * description = (string) //optional
     * generator   = (string) //optional
     * copyright   = (string) //optional
     * @return boolean
     */
    public static function podcast_edit($input)
    {
        if (!AmpConfig::get('podcast')) {
            self::message('error', T_('Access Denied: podcast features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        $user = User::get_from_username(Session::username($input['auth']));
        if (!self::check_access('interface', 50, $user->id, 'edit_podcast', $input['api_format'])) {
            return false;
        }
        if (!self::check_parameter($input, array('filter'), 'podcast_edit')) {
            return false;
        }
        $podcast_id = $input['filter'];
        $podcast    = new Podcast($podcast_id);
        if (!$podcast->id) {
            self::message('error', 'podcast ' . $podcast_id . ' was not found', '404', $input['api_format']);

            return false;
        }

        $feed           = filter_var($input['feed'], FILTER_VALIDATE_URL) ? $input['feed'] : $podcast->feed;
        $title          = isset($input['title']) ? scrub_in($input['title']) : $podcast->title;
        $website        = filter_var($input['website'], FILTER_VALIDATE_URL) ? scrub_in($input['website']) : $podcast->website;
        $description    = isset($input['description']) ? scrub_in($input['description']) : $podcast->description;
        $generator      = isset($input['generator']) ? scrub_in($input['generator']) : $podcast->generator;
        $copyright      = isset($input['copyright']) ? scrub_in($input['copyright']) : $podcast->copyright;
        $data           = array(
            'feed' => $feed,
            'title' => $title,
            'website' => $website,
            'description' => $description,
            'generator' => $generator,
            'copyright' => $copyright
        );
        if ($podcast->update($data)) {
            self::message('success', 'podcast ' . $podcast_id . ' updated', null, $input['api_format']);
        } else {
            self::message('error', 'podcast ' . $podcast_id . ' was not updated', '401', $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    } // podcast_edit

    /**
     * podcast_episodes
     * MINIMUM_API_VERSION=420000
     *
     * This returns the episodes for a podcast
     *
     * @param array $input
     * filter = (string) UID of podcast
     * offset = (integer) //optional
     * limit  = (integer) //optional
     * @return boolean
     */
    public static function podcast_episodes($input)
    {
        if (!AmpConfig::get('podcast')) {
            self::message('error', T_('Access Denied: podcast features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!self::check_parameter($input, array('filter'), 'podcast_episodes')) {
            return false;
        }
        $user       = User::get_from_username(Session::username($input['auth']));
        $user_id    = $user->id;
        $podcast_id = (int) scrub_in($input['filter']);
        debug_event(self::class, 'User ' . $user->id . ' loading podcast: ' . $podcast_id, 5);
        $podcast = new Podcast($podcast_id);
        $items   = $podcast->get_episodes();

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::podcast_episodes($items, $user_id);
                break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::podcast_episodes($items, $user_id);
        }
        Session::extend($input['auth']);

        return true;
    } // podcast_episodes

    /**
     * podcast_episode
     * MINIMUM_API_VERSION=420000
     *
     * Get the podcast_episode from it's id.
     *
     * @param array $input
     * filter  = (integer) podcast_episode ID number
     * @return boolean
     */
    public static function podcast_episode($input)
    {
        if (!AmpConfig::get('podcast')) {
            self::message('error', T_('Access Denied: podcast features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!self::check_parameter($input, array('filter'), 'podcast')) {
            return false;
        }
        $user      = User::get_from_username(Session::username($input['auth']));
        $user_id   = $user->id;
        $object_id = (int) $input['filter'];
        $episode   = new Podcast_Episode($object_id);
        if ($episode->id > 0) {
            ob_end_clean();
            switch ($input['api_format']) {
                case 'json':
                    echo JSON_Data::podcast_episodes(array($object_id), $user_id);
                    break;
                default:
                    echo XML_Data::podcast_episodes(array($object_id), $user_id);
            }
        } else {
            self::message('error', 'podcast_episode ' . $object_id . ' was not found', '404', $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    } // podcast_episode

    /**
     * podcast_episode_delete
     *
     * MINIMUM_API_VERSION=420000
     *
     * Delete an existing podcast_episode.
     *
     * @param array $input
     * filter = (string) UID of podcast_episode to delete
     * @return boolean
     */
    public static function podcast_episode_delete($input)
    {
        if (!AmpConfig::get('podcast')) {
            self::message('error', T_('Access Denied: podcast features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!self::check_access('interface', 75, User::get_from_username(Session::username($input['auth']))->id, 'update_podcast', $input['api_format'])) {
            return false;
        }
        if (!self::check_parameter($input, array('filter'), 'podcast_episode_delete')) {
            return false;
        }
        $object_id = (int) $input['filter'];
        $episode   = new Podcast_Episode($object_id);
        if ($episode->id > 0) {
            if ($episode->remove()) {
                self::message('success', 'podcast_episode ' . $object_id . ' deleted', null, $input['api_format']);
            } else {
                self::message('error', 'podcast_episode ' . $object_id . ' was not deleted', '401', $input['api_format']);
            }
        } else {
            self::message('error', 'podcast_episode ' . $object_id . ' was not found', '404', $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    } // podcast_episode_delete

    /**
     * users
     * MINIMUM_API_VERSION=440000
     *
     * Get ids and usernames for your site
     *
     * @param array $input
     * @return boolean
     */
    public static function users(array $input)
    {
        $users = User::get_valid_users();
        if (empty($users)) {
            self::message('error', 'No Results', '404', $input['api_format']);

            return false;
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::users($users);
                break;
            default:
                echo XML_Data::users($users);
        }
        Session::extend($input['auth']);

        return true;
    }

    /**
     * user
     * MINIMUM_API_VERSION=380001
     *
     * This get a user's public information
     *
     * @param array $input
     * username = (string) $username)
     * @return boolean
     */
    public static function user($input)
    {
        if (!self::check_parameter($input, array('username'), 'user')) {
            return false;
        }
        $username = (string) $input['username'];
        $user     = User::get_from_username($username);
        if ($user->id) {
            $apiuser  = User::get_from_username(Session::username($input['auth']));
            $fullinfo = false;
            // get full info when you're an admin or searching for yourself
            if (($user->id == $apiuser->id) || (Access::check('interface', 100, $apiuser->id))) {
                $fullinfo = true;
            }
            ob_end_clean();
            switch ($input['api_format']) {
                case 'json':
                    echo JSON_Data::user($user, $fullinfo);
                break;
                default:
                    echo XML_Data::user($user, $fullinfo);
            }
        } else {
            self::message('error', T_('User_id not found'), '404', $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    } // user

    /**
     * user_create
     * MINIMUM_API_VERSION=400001
     *
     * Create a new user.
     * Requires the username, password and email.
     *
     * @param array $input
     * username = (string) $username
     * fullname = (string) $fullname //optional
     * password = (string) hash('sha256', $password))
     * email    = (string) $email
     * disable  = (integer) 0,1 //optional, default = 0
     * @return boolean
     */
    public static function user_create($input)
    {
        if (!self::check_access('interface', 100, User::get_from_username(Session::username($input['auth']))->id, 'user_create', $input['api_format'])) {
            return false;
        }
        if (!self::check_parameter($input, array('username', 'password', 'email'), 'user_create')) {
            return false;
        }
        $username = $input['username'];
        $fullname = $input['fullname'] ?: $username;
        $email    = $input['email'];
        $password = $input['password'];
        $disable  = (bool) $input['disable'];
        $access   = 25;
        $user_id  = User::create($username, $fullname, $email, null, $password, $access, null, null, $disable, true);

        if ($user_id > 0) {
            self::message('success', 'successfully created: ' . $username, null, $input['api_format']);

            return true;
        }
        self::message('error', 'failed to create: ' . $username, '400', $input['api_format']);
        Session::extend($input['auth']);

        return false;
    } // user_create

    /**
     * user_update
     * MINIMUM_API_VERSION=400001
     *
     * Update an existing user.
     * Takes the username with optional parameters.
     *
     * @param array $input
     * username   = (string) $username
     * password   = (string) hash('sha256', $password)) //optional
     * fullname   = (string) $fullname //optional
     * email      = (string) $email //optional
     * website    = (string) $website //optional
     * state      = (string) $state //optional
     * city       = (string) $city //optional
     * disable    = (integer) 0,1 true to disable, false to enable //optional
     * maxbitrate = (integer) $maxbitrate //optional
     * @return boolean
     */
    public static function user_update($input)
    {
        if (!self::check_access('interface', 100, User::get_from_username(Session::username($input['auth']))->id, 'user_update', $input['api_format'])) {
            return false;
        }
        if (!self::check_parameter($input, array('username'), 'user_update')) {
            return false;
        }
        $username   = $input['username'];
        $fullname   = $input['fullname'];
        $email      = $input['email'];
        $website    = $input['website'];
        $password   = $input['password'];
        $state      = $input['state'];
        $city       = $input['city'];
        $disable    = $input['disable'];
        $maxbitrate = $input['maxbitrate'];

        // identify the user to modify
        $user    = User::get_from_username($username);
        $user_id = $user->id;

        if ($password && Access::check('interface', 100, $user_id)) {
            self::message('error', 'Do not update passwords for admin users! ' . $username, '400', $input['api_format']);

            return false;
        }

        if ($user_id > 0) {
            if ($password && !AmpConfig::get('simple_user_mode')) {
                $user->update_password('', $password);
            }
            if ($fullname) {
                $user->update_fullname($fullname);
            }
            if (Mailer::validate_address($email)) {
                $user->update_email($email);
            }
            if ($website) {
                $user->update_website($website);
            }
            if ($state) {
                $user->update_state($state);
            }
            if ($city) {
                $user->update_city($city);
            }
            if ($disable === '1') {
                $user->disable();
            } elseif ($disable === '0') {
                $user->enable();
            }
            if ((int) $maxbitrate > 0) {
                Preference::update('transcode_bitrate', $user_id, $maxbitrate);
            }
            self::message('success', 'successfully updated: ' . $username, null, $input['api_format']);

            return true;
        }
        self::message('error', 'failed to update: ' . $username, '400', $input['api_format']);
        Session::extend($input['auth']);

        return false;
    } // user_update

    /**
     * user_delete
     * MINIMUM_API_VERSION=400001
     *
     * Delete an existing user.
     * Takes the username in parameter.
     *
     * @param array $input
     * username = (string) $username)
     * @return boolean
     */
    public static function user_delete($input)
    {
        if (!self::check_access('interface', 100, User::get_from_username(Session::username($input['auth']))->id, 'user_delete', $input['api_format'])) {
            return false;
        }
        if (!self::check_parameter($input, array('username'), 'user_delete')) {
            return false;
        }
        $username = $input['username'];
        $user     = User::get_from_username($username);
        // don't delete yourself or admins
        if ($user->id && Session::username($input['auth']) != $username && !Access::check('interface', 100, $user->id)) {
            $user->delete();
            self::message('success', 'successfully deleted: ' . $username, null, $input['api_format']);

            return true;
        }
        self::message('error', 'failed to delete: ' . $username, '400', $input['api_format']);
        Session::extend($input['auth']);

        return false;
    } // user_delete

    /**
     * followers
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=400004
     *
     * This gets followers of the user
     * Error when user not found or no followers
     *
     * @param array $input
     * username = (string) $username
     * @return boolean
     */
    public static function followers($input)
    {
        if (!AmpConfig::get('sociable')) {
            self::message('error', T_('Access Denied: social features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!self::check_parameter($input, array('username'), 'followers')) {
            return false;
        }
        $username = $input['username'];
        if (!empty($username)) {
            $user = User::get_from_username($username);
            if ($user !== null) {
                $users    = $user->get_followers();
                if (!count($users)) {
                    self::message('error', 'User `' . $username . '` has no followers.', '400', $input['api_format']);
                } else {
                    ob_end_clean();
                    switch ($input['api_format']) {
                        case 'json':
                            echo JSON_Data::users($users);
                        break;
                        default:
                            echo XML_Data::users($users);
                    }
                }
            } else {
                debug_event(self::class, 'User `' . $username . '` cannot be found.', 1);
                self::message('error', 'User `' . $username . '` cannot be found.', '400', $input['api_format']);
            }
        }
        Session::extend($input['auth']);

        return true;
    } // followers

    /**
     * following
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=400004
     *
     * Get users followed by the user
     * Error when user not found or no followers
     *
     * @param array $input
     * username = (string) $username
     * @return boolean
     */
    public static function following($input)
    {
        if (!AmpConfig::get('sociable')) {
            self::message('error', T_('Access Denied: social features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!self::check_parameter($input, array('username'), 'following')) {
            return false;
        }
        $username = $input['username'];
        if (!empty($username)) {
            $user = User::get_from_username($username);
            if ($user !== null) {
                $users = $user->get_following();
                if (!count($users)) {
                    self::message('error', 'User `' . $username . '` does not follow anyone.', '400', $input['api_format']);
                } else {
                    debug_event(self::class, 'User is following:  ' . (string) count($users), 1);
                    ob_end_clean();
                    switch ($input['api_format']) {
                        case 'json':
                            echo JSON_Data::users($users);
                        break;
                        default:
                            echo XML_Data::users($users);
                    }
                }
            } else {
                debug_event(self::class, 'User `' . $username . '` cannot be found.', 1);
                self::message('error', 'User `' . $username . '` cannot be found.', '400', $input['api_format']);
            }
        }
        Session::extend($input['auth']);

        return true;
    } // following

    /**
     * toggle_follow
     * MINIMUM_API_VERSION=380001
     *
     * This will follow/unfollow a user
     *
     * @param array $input
     * username = (string) $username
     * @return boolean
     */
    public static function toggle_follow($input)
    {
        if (!AmpConfig::get('sociable')) {
            self::message('error', T_('Access Denied: social features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!self::check_parameter($input, array('username'), 'toggle_follow')) {
            return false;
        }
        $username = $input['username'];
        if (!empty($username)) {
            $user = User::get_from_username($username);
            if ($user !== null) {
                User::get_from_username(Session::username($input['auth']))->toggle_follow($user->id);
                ob_end_clean();
                self::message('success', 'follow toggled for: ' . $user->id, null, $input['api_format']);
            }
        }
        Session::extend($input['auth']);

        return true;
    } // toggle_follow


    /**
     * last_shouts
     * MINIMUM_API_VERSION=380001
     *
     * This get the latest posted shouts
     *
     * @param array $input
     * username = (string) $username //optional
     * limit = (integer) $limit //optional
     * @return boolean
     */
    public static function last_shouts($input)
    {
        if (!AmpConfig::get('sociable')) {
            self::message('error', T_('Access Denied: social features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!self::check_parameter($input, array('username'), 'last_shouts')) {
            return false;
        }
        $limit = (int) ($input['limit']);
        if ($limit < 1) {
            $limit = AmpConfig::get('popular_threshold', 10);
        }
        $username = $input['username'];
        if (!empty($username)) {
            $shouts = Shoutbox::get_top($limit, $username);
        } else {
            $shouts = Shoutbox::get_top($limit);
        }

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::shouts($shouts);
            break;
            default:
                echo XML_Data::shouts($shouts);
        }
        Session::extend($input['auth']);

        return true;
    } // last_shouts

    /**
     * rate
     * MINIMUM_API_VERSION=380001
     *
     * This rates a library item
     *
     * @param array $input
     * type   = (string) 'song', 'album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'video', 'tvshow', 'tvshow_season' $type
     * id     = (integer) $object_id
     * rating = (integer) 0,1|2|3|4|5 $rating
     * @return boolean|void
     */
    public static function rate($input)
    {
        if (!AmpConfig::get('ratings')) {
            self::message('error', T_('Access Denied: Rating features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!self::check_parameter($input, array('type', 'id', 'rating'), 'rate')) {
            return false;
        }
        ob_end_clean();
        $type      = (string) $input['type'];
        $object_id = $input['id'];
        $rating    = $input['rating'];
        $user      = User::get_from_username(Session::username($input['auth']));
        // confirm the correct data
        if (!in_array($type, array('song', 'album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'video', 'tvshow', 'tvshow_season'))) {
            self::message('error', T_('Incorrect object type') . ' ' . $type, '401', $input['api_format']);

            return false;
        }
        if (!in_array($rating, array('0', '1', '2', '3', '4', '5'))) {
            self::message('error', T_('Ratings must be between [0-5]. ' . $rating . ' is invalid'), '401', $input['api_format']);

            return false;
        }

        if (!Core::is_library_item($type) || !$object_id) {
            self::message('error', T_('Wrong library item type'), '401', $input['api_format']);
        } else {
            $item = new $type($object_id);
            if (!$item->id) {
                self::message('error', T_('Library item not found'), '404', $input['api_format']);

                return false;
            }
            $rate = new Rating($object_id, $type);
            $rate->set_rating($rating, $user->id);
            self::message('success', 'rating set to ' . $rating . ' for ' . $object_id, null, $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    } // rate

    /**
     * flag
     * MINIMUM_API_VERSION=400001
     *
     * This flags a library item as a favorite
     * Setting flag to true (1) will set the flag
     * Setting flag to false (0) will remove the flag
     *
     * @param array $input
     * type = (string) 'song', 'album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'video', 'tvshow', 'tvshow_season' $type
     * id   = (integer) $object_id
     * flag = (integer) 0,1 $flag
     * @return boolean
     */
    public static function flag($input)
    {
        if (!AmpConfig::get('userflags')) {
            self::message('error', T_('Access Denied: UserFlag features are not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!self::check_parameter($input, array('type', 'id', 'flag'), 'flag')) {
            return false;
        }
        ob_end_clean();
        $type      = $input['type'];
        $object_id = $input['id'];
        $flag      = (bool) $input['flag'];
        $user      = User::get_from_username(Session::username($input['auth']));
        $user_id   = null;
        if ((int) $user->id > 0) {
            $user_id = $user->id;
        }
        // confirm the correct data
        if (!in_array($type, array('song', 'album', 'artist', 'playlist', 'podcast', 'podcast_episode', 'video', 'tvshow', 'tvshow_season'))) {
            self::message('error', T_('Incorrect object type') . ' ' . $type, '401', $input['api_format']);

            return false;
        }

        if (!Core::is_library_item($type) || !$object_id) {
            self::message('error', T_('Wrong library item type'), '401', $input['api_format']);
        } else {
            $item = new $type($object_id);
            if (!$item->id) {
                self::message('error', T_('Library item not found'), '404', $input['api_format']);

                return false;
            }
            $userflag = new Userflag($object_id, $type);
            if ($userflag->set_flag($flag, $user_id)) {
                $message = ($flag) ? 'flag ADDED to ' : 'flag REMOVED from ';
                self::message('success', $message . $object_id, null, $input['api_format']);

                return true;
            }
            self::message('error', 'flag failed ' . $object_id, '400', $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    } // flag

    /**
     * record_play
     * MINIMUM_API_VERSION=400001
     *
     * Take a song_id and update the object_count and user_activity table with a play
     * This allows other sources to record play history to Ampache.
     * Require 100 (Admin) permission to change other user's play history
     *
     * @param array $input
     * id     = (integer) $object_id
     * user   = (integer|string) $user_id OR $username //optional
     * client = (string) $agent //optional
     * date   = (integer) UNIXTIME() //optional
     * @return boolean
     */
    public static function record_play($input)
    {
        if (!self::check_parameter($input, array('id', 'user'), 'record_play')) {
            return false;
        }
        $api_user  = User::get_from_username(Session::username($input['auth']));
        $play_user = (isset($input['user']) && (int) $input['user'] > 0)
            ? new User((int) $input['user'])
            : User::get_from_username((string) $input['user']);

        // If you are setting plays for other users make sure we have an admin
        if ($play_user->id !== $api_user->id && !self::check_access('interface', 100, $api_user->id, 'record_play', $input['api_format'])) {
            return false;
        }
        ob_end_clean();
        $object_id = (int) $input['id'];
        $valid     = in_array($play_user->id, User::get_valid_users());
        $date      = (is_numeric(scrub_in($input['date']))) ? (int) scrub_in($input['date']) : time(); //optional

        // validate supplied user
        if ($valid === false) {
            self::message('error', T_('User_id not found'), '404', $input['api_format']);

            return false;
        }

        // validate client string or fall back to 'api'
        $agent = ($input['client'])
            ? $input['client']
            : 'api';

        $media = new Song($object_id);
        if (!$media->id) {
            self::message('error', T_('Library item not found'), '404', $input['api_format']);

            return false;
        }
        debug_event(self::class, 'record_play: ' . $media->id . ' for ' . $play_user->username . ' using ' . $agent . ' ' . (string) time(), 5);

        // internal scrobbling (user_activity and object_count tables)
        if ($media->set_played($play_user->id, $agent, array(), $date)) {
            // scrobble plugins
            User::save_mediaplay($play_user, $media);
        }

        self::message('success', 'successfully recorded play: ' . $media->id . ' for: ' . $play_user->username, null, $input['api_format']);
        Session::extend($input['auth']);

        return true;
    } // record_play

    /**
     * scrobble
     * MINIMUM_API_VERSION=400001
     *
     * Search for a song using text info and then record a play if found.
     * This allows other sources to record play history to Ampache
     *
     * @param array $input
     * song       = (string)  $song_name
     * artist     = (string)  $artist_name
     * album      = (string)  $album_name
     * songmbid   = (string)  $song_mbid //optional
     * artistmbid = (string)  $artist_mbid //optional
     * albummbid  = (string)  $album_mbid //optional
     * date       = (integer) UNIXTIME() //optional
     * client     = (string)  $agent //optional
     * @return boolean
     */
    public static function scrobble($input)
    {
        if (!self::check_parameter($input, array('song', 'artist', 'album'), 'scrobble')) {
            return false;
        }
        ob_end_clean();
        $charset     = AmpConfig::get('site_charset');
        $song_name   = (string) html_entity_decode(scrub_out($input['song']), ENT_QUOTES, $charset);
        $artist_name = (string) html_entity_decode(scrub_in((string) $input['artist']), ENT_QUOTES, $charset);
        $album_name  = (string) html_entity_decode(scrub_in((string) $input['album']), ENT_QUOTES, $charset);
        $song_mbid   = (string) scrub_in($input['song_mbid']); //optional
        $artist_mbid = (string) scrub_in($input['artist_mbid']); //optional
        $album_mbid  = (string) scrub_in($input['album_mbid']); //optional
        $date        = (is_numeric(scrub_in($input['date']))) ? (int) scrub_in($input['date']) : time(); //optional
        $user        = User::get_from_username(Session::username($input['auth']));
        $user_id     = $user->id;
        $valid       = in_array($user->id, User::get_valid_users());

        // validate supplied user
        if ($valid === false) {
            self::message('error', T_('User_id not found'), '404', $input['api_format']);

            return false;
        }

        // validate minimum required options
        debug_event(self::class, 'scrobble searching for:' . $song_name . ' - ' . $artist_name . ' - ' . $album_name, 4);
        if (!$song_name || !$album_name || !$artist_name) {
            self::message('error', T_('Invalid input options'), '401', $input['api_format']);

            return false;
        }

        // validate client string or fall back to 'api'
        if ($input['client']) {
            $agent = $input['client'];
        } else {
            $agent = 'api';
        }
        $scrobble_id = Song::can_scrobble($song_name, $artist_name, $album_name, (string) $song_mbid, (string) $artist_mbid, (string) $album_mbid);

        if ($scrobble_id === '') {
            self::message('error', T_('Failed to scrobble: No item found!'), '401', $input['api_format']);
        } else {
            $item = new Song((int) $scrobble_id);
            if (!$item->id) {
                self::message('error', T_('Library item not found'), '404', $input['api_format']);

                return false;
            }
            debug_event(self::class, 'scrobble: ' . $item->id . ' for ' . $user->username . ' using ' . $agent . ' ' . (string) time(), 5);

            // internal scrobbling (user_activity and object_count tables)
            $item->set_played($user_id, $agent, array(), $date);

            // scrobble plugins
            User::save_mediaplay($user, $item);

            self::message('success', 'successfully scrobbled: ' . $scrobble_id, null, $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    } // scrobble

    /**
     * catalogs
     * MINIMUM_API_VERSION=420000
     *
     * Get information about catalogs this user is allowed to manage.
     *
     * @param array $input
     * filter = (string) set $filter_type //optional
     * offset = (integer) //optional
     * limit  = (integer) //optional
     */
    public static function catalogs($input)
    {
        // filter for specific catalog types
        $filter   = (in_array($input['filter'], array('music', 'clip', 'tvshow', 'movie', 'personal_video', 'podcast'))) ? $input['filter'] : '';
        $catalogs = Catalog::get_catalogs($filter);

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                JSON_Data::set_offset($input['offset']);
                JSON_Data::set_limit($input['limit']);
                echo JSON_Data::catalogs($catalogs);
                break;
            default:
                XML_Data::set_offset($input['offset']);
                XML_Data::set_limit($input['limit']);
                echo XML_Data::catalogs($catalogs);
        }
        Session::extend($input['auth']);
    } // catalogs

    /**
     * catalog
     * MINIMUM_API_VERSION=420000
     *
     * Get the catalogs from it's id.
     *
     * @param array $input
     * filter = (integer) Catalog ID number
     * @return boolean
     */
    public static function catalog($input)
    {
        if (!self::check_parameter($input, array('filter'), 'catalog')) {
            return false;
        }
        $catalog = array((int) $input['filter']);

        ob_end_clean();
        switch ($input['api_format']) {
            case 'json':
                echo JSON_Data::catalogs($catalog);
                break;
            default:
                echo XML_Data::catalogs($catalog);
        }
        Session::extend($input['auth']);

        return true;
    } // catalog

    /**
     * catalog_action
     * MINIMUM_API_VERSION=400001
     * CHANGED_IN_API_VERSION=420000
     *
     * Kick off a catalog update or clean for the selected catalog
     * Added 'verify_catalog', 'gather_art'
     *
     * @param array $input
     * task    = (string) 'add_to_catalog'|'clean_catalog'
     * catalog = (integer) $catalog_id)
     * @return boolean
     */
    public static function catalog_action($input)
    {
        if (!self::check_parameter($input, array('catalog', 'task'), 'catalog_action')) {
            return false;
        }
        if (!self::check_access('interface', 75, User::get_from_username(Session::username($input['auth']))->id, 'catalog_action', $input['api_format'])) {
            return false;
        }
        $task = (string) $input['task'];
        // confirm the correct data
        if (!in_array($task, array('add_to_catalog', 'clean_catalog', 'verify_catalog', 'gather_art'))) {
            self::message('error', T_('Incorrect catalog task') . ' ' . $task, '401', $input['api_format']);

            return false;
        }
        $catalog = Catalog::create_from_id((int) $input['catalog']);

        if ($catalog) {
            define('API', true);
            unset($SSE_OUTPUT);
            switch ($task) {
                case 'clean_catalog':
                    $catalog->clean_catalog_proc();
                    Catalog::clean_empty_albums();
                    break;
                case 'verify_catalog':
                    $catalog->verify_catalog_proc();
                    break;
                case 'gather_art':
                    $catalog->gather_art();
                    break;
                case 'add_to_catalog':
                    $options = array(
                        'gather_art' => false,
                        'parse_playlist' => false
                    );
                    $catalog->add_to_catalog($options);
                    break;
            }
            self::message('success', 'successfully started: ' . $task, null, $input['api_format']);
        } else {
            self::message('error', T_('The requested item was not found'), '404', $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    } // catalog_action

    /**
     * catalog_file
     * MINIMUM_API_VERSION=420000
     *
     * Perform actions on local catalog files.
     * Single file versions of catalog add, clean and verify.
     * Make sure you remember to urlencode those file names!
     *
     * @param array $input
     * file    = (string) urlencode(FULL path to local file)
     * task    = (string) 'add'|'clean'|'verify'|'remove'
     * catalog = (integer) $catalog_id)
     * @return boolean
     */
    public static function catalog_file($input)
    {
        $task = (string) $input['task'];
        if (!AmpConfig::get('delete_from_disk') && $task == 'remove') {
            self::message('error', T_('Access Denied: delete from disk is not enabled.'), '400', $input['api_format']);

            return false;
        }
        if (!self::check_access('interface', 50, User::get_from_username(Session::username($input['auth']))->id, 'catalog_file', $input['api_format'])) {
            return false;
        }
        if (!self::check_parameter($input, array('catalog', 'file', 'task'), 'catalog_action')) {
            return false;
        }
        $file = (string) html_entity_decode($input['file']);
        // confirm the correct data
        if (!in_array($task, array('add', 'clean', 'verify', 'remove'))) {
            self::message('error', T_('Incorrect file task') . ' ' . $task, '401', $input['api_format']);

            return false;
        }
        if (!file_exists($file) && $task !== 'clean') {
            self::message('error', T_('File not found') . ' ' . $file, '404', $input['api_format']);

            return false;
        }
        $catalog_id = (int) $input['catalog'];
        $catalog    = Catalog::create_from_id($catalog_id);
        if ($catalog->id < 1) {
            self::message('error', T_('Catalog not found') . ' ' . $catalog_id, '404', $input['api_format']);

            return false;
        }
        switch ($catalog->gather_types) {
            case 'podcast':
                $type  = 'podcast_episode';
                $media = new Podcast_Episode(Catalog::get_id_from_file($file, $type));
                break;
            case 'clip':
            case 'tvshow':
            case 'movie':
            case 'personal_video':
                $type  = 'video';
                $media = new Video(Catalog::get_id_from_file($file, $type));
                break;
            case 'music':
            default:
                $type  = 'song';
                $media = new Song(Catalog::get_id_from_file($file, $type));
                break;
        }

        if ($catalog->catalog_type == 'local') {
            define('API', true);
            unset($SSE_OUTPUT);
            switch ($task) {
                case 'clean':
                    $catalog->clean_file($file, $type);
                    break;
                case 'verify':
                    Catalog::update_media_from_tags($media, array($type));
                    break;
                case 'add':
                    $catalog->add_file($file);
                    break;
                case 'remove':
                    $media->remove();
                    break;
            }
            self::message('success', 'successfully started: ' . $task . ' for ' . $file, null, $input['api_format']);
        } else {
            self::message('error', T_('The requested catalog was not found'), '404', $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    } // catalog_file

    /**
     * timeline
     * MINIMUM_API_VERSION=380001
     *
     * This gets a user timeline from their username
     *
     * @param array $input
     * username = (string)
     * limit    = (integer) //optional
     * since    = (integer) UNIXTIME() //optional
     * @return boolean
     */
    public static function timeline($input)
    {
        if (AmpConfig::get('sociable')) {
            if (!self::check_parameter($input, array('username'), 'timeline')) {
                return false;
            }
            $username = $input['username'];
            $limit    = (int) ($input['limit']);
            $since    = (int) ($input['since']);

            if (!empty($username)) {
                $user = User::get_from_username($username);
                if ($user !== null) {
                    if (Preference::get_by_user($user->id, 'allow_personal_info_recent')) {
                        $activities = Useractivity::get_activities($user->id, $limit, $since);
                        ob_end_clean();
                        switch ($input['api_format']) {
                            case 'json':
                                echo JSON_Data::timeline($activities);
                            break;
                            default:
                                echo XML_Data::timeline($activities);
                        }
                    }
                }
            }
        } else {
            debug_event(self::class, 'Sociable feature is not enabled.', 3);
        }
        Session::extend($input['auth']);

        return true;
    } // timeline

    /**
     * friends_timeline
     * MINIMUM_API_VERSION=380001
     *
     * This get current user friends timeline
     *
     * @param array $input
     * limit = (integer) //optional
     * since = (integer) UNIXTIME() //optional
     */
    public static function friends_timeline($input)
    {
        if (AmpConfig::get('sociable')) {
            $limit = (int) ($input['limit']);
            $since = (int) ($input['since']);
            $user  = User::get_from_username(Session::username($input['auth']))->id;

            if ($user > 0) {
                $activities = Useractivity::get_friends_activities($user, $limit, $since);
                ob_end_clean();
                switch ($input['api_format']) {
                    case 'json':
                        echo JSON_Data::timeline($activities);
                    break;
                    default:
                        echo XML_Data::timeline($activities);
                }
            }
        } else {
            debug_event(self::class, 'Sociable feature is not enabled.', 3);
        }
        Session::extend($input['auth']);
    } // friends_timeline

    /**
     * update_from_tags
     * MINIMUM_API_VERSION=400001
     *
     * updates a single album, artist, song from the tag data
     *
     * @param array $input
     * type = (string) 'artist'|'album'|'song'
     * id   = (integer) $artist_id, $album_id, $song_id)
     * @return boolean
     */
    public static function update_from_tags($input)
    {
        if (!self::check_parameter($input, array('type', 'id'), 'update_from_tags')) {
            return false;
        }
        $type   = (string) $input['type'];
        $object = (int) $input['id'];

        // confirm the correct data
        if (!in_array($type, array('artist', 'album', 'song'))) {
            self::message('error', T_('Incorrect object type') . ' ' . $type, '401', $input['api_format']);

            return false;
        }
        $item = new $type($object);
        if (!$item->id) {
            self::message('error', T_('The requested item was not found'), '404', $input['api_format']);

            return false;
        }
        // update your object
        Catalog::update_single_item($type, $object, true);

        self::message('success', 'Updated tags for: ' . (string) $object . ' (' . $type . ')', null, $input['api_format']);
        Session::extend($input['auth']);

        return true;
    } // update_from_tags

    /**
     * update_artist_info
     * MINIMUM_API_VERSION=400001
     *
     * Update artist information and fetch similar artists from last.fm
     * Make sure lastfm_api_key is set in your configuration file
     *
     * @param array $input
     * id   = (integer) $artist_id)
     * @return boolean
     */
    public static function update_artist_info($input)
    {
        if (!self::check_parameter($input, array('id'), 'update_artist_info')) {
            return false;
        }
        if (!self::check_access('interface', 75, User::get_from_username(Session::username($input['auth']))->id, 'update_artist_info', $input['api_format'])) {
            return false;
        }
        $object = (int) $input['id'];
        $item   = new Artist($object);
        if (!$item->id) {
            self::message('error', T_('The requested item was not found'), '404', $input['api_format']);

            return false;
        }
        // update your object
        // need at least catalog_manager access to the db
        if (!empty(Recommendation::get_artist_info($object) || !empty(Recommendation::get_artists_like($object)))) {
            self::message('success', 'Updated artist info: ' . (string) $object, null, $input['api_format']);

            return true;
        }
        self::message('error', T_('Failed to update_artist_info or recommendations for ' . (string) $object), '400', $input['api_format']);
        Session::extend($input['auth']);

        return true;
    } // update_artist_info

    /**
     * update_art
     * MINIMUM_API_VERSION=400001
     *
     * updates a single album, artist, song running the gather_art process
     * Doesn't overwrite existing art by default.
     *
     * @param array $input
     * type      = (string) 'artist'|'album'
     * id        = (integer) $artist_id, $album_id)
     * overwrite = (integer) 0,1 //optional
     * @return boolean
     */
    public static function update_art($input)
    {
        if (!self::check_parameter($input, array('type', 'id'), 'update_art')) {
            return false;
        }
        if (!self::check_access('interface', 75, User::get_from_username(Session::username($input['auth']))->id, 'update_art', $input['api_format'])) {
            return false;
        }
        $type      = (string) $input['type'];
        $object    = (int) $input['id'];
        $overwrite = (int) $input['overwrite'] == 0;

        // confirm the correct data
        if (!in_array($type, array('artist', 'album'))) {
            self::message('error', T_('Incorrect object type') . ' ' . $type, '401', $input['api_format']);

            return true;
        }
        $item = new $type($object);
        if (!$item->id) {
            self::message('error', T_('The requested item was not found'), '404', $input['api_format']);

            return true;
        }
        // update your object
        if (Catalog::gather_art_item($type, $object, $overwrite, true)) {
            self::message('success', 'Gathered new art for: ' . (string) $object . ' (' . $type . ')', null, $input['api_format']);

            return true;
        }
        self::message('error', T_('Failed to update_art for ' . (string) $object), '400', $input['api_format']);
        Session::extend($input['auth']);

        return true;
    } // update_art

    /**
     * update_podcast
     * MINIMUM_API_VERSION=420000
     *
     * Sync and download new podcast episodes
     *
     * @param array $input
     * filter = (string) UID of podcast
     * @return boolean
     */
    public static function update_podcast($input)
    {
        if (!self::check_parameter($input, array('filter'), 'update_podcast')) {
            return false;
        }
        if (!self::check_access('interface', 50, User::get_from_username(Session::username($input['auth']))->id, 'update_podcast', $input['api_format'])) {
            return false;
        }
        $object_id = (int) scrub_in($input['filter']);
        $podcast   = new Podcast($object_id);
        if ($podcast->id > 0) {
            if ($podcast->sync_episodes(true)) {
                self::message('success', 'Synced episodes for podcast: ' . (string) $object_id, null, $input['api_format']);
                Session::extend($input['auth']);
            } else {
                self::message('error', T_('Failed to sync episodes for podcast: ' . (string) $object_id), '400', $input['api_format']);
            }
        } else {
            self::message('error', 'podcast ' . $object_id . ' was not found', '404', $input['api_format']);
        }
        Session::extend($input['auth']);

        return true;
    } // update_podcast

    /**
     * stream
     * MINIMUM_API_VERSION=400001
     *
     * Streams a given media file.
     * Takes the file id in parameter with optional max bit rate, file format, time offset, size and estimate content length option.
     *
     * @param array $input
     * id      = (string) $song_id|$podcast_episode_id
     * type    = (string) 'song'|'podcast'
     * bitrate = (integer) max bitrate for transcoding
     * format  = (string) 'mp3'|'ogg', etc use 'raw' to skip transcoding SONG ONLY
     * offset  = (integer) time offset in seconds
     * length  = (integer) 0,1
     * @return boolean
     */
    public static function stream($input)
    {
        if (!self::check_parameter($input, array('id', 'type'), 'stream')) {
            return false;
        }
        $fileid  = $input['id'];
        $type    = $input['type'];
        $user_id = User::get_from_username(Session::username($input['auth']))->id;

        $maxBitRate    = $input['bitrate'];
        $format        = $input['format']; // mp3, flv or raw
        $original      = $format && $format != 'raw';
        $timeOffset    = $input['offset'];
        $contentLength = (int) $input['length']; // Force content-length guessing if transcode

        $params = '&client=api';
        if ($contentLength == 1) {
            $params .= '&content_length=required';
        }
        if ($original && $type == 'song') {
            $params .= '&transcode_to=' . $format;
        }
        if ((int) $maxBitRate > 0 && $type == 'song') {
            $params .= '&bitrate=' . $maxBitRate;
        }
        if ($timeOffset) {
            $params .= '&frame=' . $timeOffset;
        }

        $url = '';
        if ($type == 'song') {
            $media = new Song($fileid);
            $url   = $media->play_url($params, 'api', function_exists('curl_version'), $user_id);
        }
        if ($type == 'podcast') {
            $media = new Podcast_Episode($fileid);
            $url   = $media->play_url($params, 'api', function_exists('curl_version'), $user_id);
        }
        if (!empty($url)) {
            Session::extend($input['auth']);
            header('Location: ' . str_replace(':443/play', '/play', $url));

            return true;
        }
        self::message('error', 'failed to create: ' . $url, '400', $input['api_format']);
        Session::extend($input['auth']);

        return true;
    } // stream

    /**
     * download
     * MINIMUM_API_VERSION=400001
     *
     * Downloads a given media file. set format=raw to download the full file
     *
     * @param array $input
     * id     = (string) $song_id| $podcast_episode_id
     * type   = (string) 'song'|'podcast'
     * format = (string) 'mp3'|'ogg', etc //optional SONG ONLY
     * @return boolean
     */
    public static function download($input)
    {
        if (!self::check_parameter($input, array('id', 'type'), 'download')) {
            return false;
        }
        $fileid   = $input['id'];
        $type     = $input['type'];
        $format   = $input['format'];
        $original = $format && $format != 'raw';
        $user_id  = User::get_from_username(Session::username($input['auth']))->id;

        $url    = '';
        $params = '&action=download' . '&client=api' . '&cache=1';
        if ($original && $type == 'song') {
            $params .= '&transcode_to=' . $format;
        }
        if ($format && $type == 'song') {
            $params .= '&format=' . $format;
        }
        if ($type == 'song') {
            $media = new Song($fileid);
            $url   = $media->play_url($params, 'api', function_exists('curl_version'), $user_id);
        }
        if ($type == 'podcast_episode' || $type == 'podcast') {
            $media = new Podcast_Episode($fileid);
            $url   = $media->play_url($params, 'api', function_exists('curl_version'), $user_id);
        }
        if (!empty($url)) {
            header('Location: ' . str_replace(':443/play', '/play', $url));

            return true;
        }
        self::message('error', 'failed to create: ' . $url, '400', $input['api_format']);
        Session::extend($input['auth']);

        return true;
    } // download

    /**
     * get_art
     * MINIMUM_API_VERSION=400001
     *
     * Get an art image.
     *
     * @param array $input
     * id   = (string) $object_id
     * type = (string) 'song'|'artist'|'album'|'playlist'|'search'|'podcast')
     * @return boolean
     */
    public static function get_art($input)
    {
        if (!self::check_parameter($input, array('id', 'type'), 'get_art')) {
            return false;
        }
        $object_id = (int) $input['id'];
        $type      = (string) $input['type'];
        $size      = $input['size'];
        $user      = User::get_from_username(Session::username($input['auth']));

        // confirm the correct data
        if (!in_array($type, array('song', 'album', 'artist', 'playlist', 'search', 'podcast'))) {
            self::message('error', T_('Incorrect object type') . ' ' . $type, '401', $input['api_format']);

            return false;
        }

        $art = null;
        if ($type == 'artist') {
            $art = new Art($object_id, 'artist');
        } elseif ($type == 'album') {
            $art = new Art($object_id, 'album');
        } elseif ($type == 'song') {
            $art = new Art($object_id, 'song');
            if ($art != null && $art->id == null) {
                // in most cases the song doesn't have a picture, but the album where it belongs to has
                // if this is the case, we take the album art
                $song = new Song($object_id);
                $art  = new Art($song->album, 'album');
            }
        } elseif ($type == 'podcast') {
            $art = new Art($object_id, 'podcast');
        } elseif ($type == 'search') {
            $smartlist = new Search($object_id, 'song', $user);
            $listitems = $smartlist->get_items();
            $item      = $listitems[array_rand($listitems)];
            $art       = new Art($item['object_id'], $item['object_type']);
            if ($art != null && $art->id == null) {
                $song = new Song($item['object_id']);
                $art  = new Art($song->album, 'album');
            }
        } elseif ($type == 'playlist') {
            $playlist  = new Playlist($object_id);
            $listitems = $playlist->get_items();
            $item      = $listitems[array_rand($listitems)];
            $art       = new Art($item['object_id'], $item['object_type']);
            if ($art != null && $art->id == null) {
                $song = new Song($item['object_id']);
                $art  = new Art($song->album, 'album');
            }
        }

        header('Access-Control-Allow-Origin: *');
        if ($art != null) {
            if ($art->has_db_info() && $size && AmpConfig::get('resize_images')) {
                $dim           = array();
                $dim['width']  = $size;
                $dim['height'] = $size;
                $thumb         = $art->get_thumb($dim);
                if (!empty($thumb)) {
                    header('Content-type: ' . $thumb['thumb_mime']);
                    header('Content-Length: ' . strlen((string) $thumb['thumb']));
                    echo $thumb['thumb'];

                    return true;
                }
            }

            header('Content-type: ' . $art->raw_mime);
            header('Content-Length: ' . strlen((string) $art->raw));
            echo $art->raw;
        }
        Session::extend($input['auth']);

        return true;
    } // get_art

    /**
     * localplay
     * MINIMUM_API_VERSION=380001
     *
     * This is for controlling Localplay
     *
     * @param array $input
     * command = (string) 'next', 'prev', 'stop', 'play', 'pause', 'add', 'volume_up', 'volume_down', 'volume_mute', 'delete_all', 'skip', 'status'
     * oid     = (integer) object_id //optional
     * type    = (string) 'Song', 'Video', 'Podcast_Episode', 'Channel', 'Broadcast', 'Democratic', 'Live_Stream' //optional
     * clear   = (integer) 0,1 Clear the current playlist before adding //optional
     * @return boolean
     */
    public static function localplay($input)
    {
        if (!self::check_parameter($input, array('command'), 'localplay')) {
            return false;
        }
        // Load their Localplay instance
        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        if (!$localplay->connect() || !$localplay->status()) {
            self::message('error', T_('Error Unable to connect to localplay controller'), '405', $input['api_format']);

            return false;
        }

        $result = false;
        $status = false;
        switch ($input['command']) {
            case 'add':
                // for add commands get the object details
                $object_id   = (int) $input['oid'];
                $type        = $input['type'] ? (string) $input['type'] : 'Song';
                if (!AmpConfig::get('allow_video') && $type == 'Video') {
                    self::message('error', T_('Access Denied: allow_video is not enabled.'), '400', $input['api_format']);

                    return false;
                }
                $clear       = (int) $input['clear'];
                // clear before the add
                if ($clear == 1) {
                    $localplay->delete_all();
                }
                $media = array(
                    'object_type' => $type,
                    'object_id' => $object_id,
                );
                $playlist = new Stream_Playlist();
                $playlist->add(array($media));
                foreach ($playlist->urls as $streams) {
                    $result = $localplay->add_url($streams);
                }
                break;
            case 'next':
            case 'skip':
                $result = $localplay->next();
                break;
            case 'prev':
                $result = $localplay->prev();
                break;
            case 'stop':
                $result = $localplay->stop();
                break;
            case 'play':
                $result = $localplay->play();
                break;
            case 'pause':
                $result = $localplay->pause();
                break;
            case 'volume_up':
                $result = $localplay->volume_up();
                break;
            case 'volume_down':
                $result = $localplay->volume_down();
                break;
            case 'volume_mute':
                $result = $localplay->volume_mute();
                break;
            case 'delete_all':
                $result = $localplay->delete_all();
                break;
            case 'status':
                $status = $localplay->status();
                break;
            default:
                // They are doing it wrong
                self::message('error', T_('Invalid request'), '405', $input['api_format']);

                return false;
        } // end switch on command
        $output_array = (!empty($status))
            ? array('localplay' => array('command' => array($input['command'] => $status)))
            : array('localplay' => array('command' => array($input['command'] => make_bool($result))));
        switch ($input['api_format']) {
            case 'json':
                echo json_encode($output_array, JSON_PRETTY_PRINT);
                break;
            default:
                echo XML_Data::keyed_array($output_array);
        }
        Session::extend($input['auth']);

        return true;
    } // localplay

    /**
     * democratic
     * MINIMUM_API_VERSION=380001
     *
     * This is for controlling democratic play
     *
     * @param array $input
     * method = (string) 'vote', 'devote', 'playlist', 'play'
     * oid    = (integer) //optional
     * @return boolean
     */
    public static function democratic($input)
    {
        if (!self::check_parameter($input, array('method'), 'democratic')) {
            return false;
        }
        // Load up democratic information
        $democratic = Democratic::get_current_playlist();
        $democratic->set_parent();

        switch ($input['method']) {
            case 'vote':
                $type      = 'song';
                $object_id = (int) $input['oid'];
                $media     = new Song($object_id);
                if (!$media->id) {
                    self::message('error', T_('Media object invalid or not specified'), '400', $input['api_format']);
                    break;
                }
                $democratic->add_vote(array(
                    array(
                        'object_type' => $type,
                        'object_id' => $media->id
                    )
                ));

                // If everything was ok
                $xml_array = array('method' => $input['method'], 'result' => true);
                switch ($input['api_format']) {
                    case 'json':
                        echo json_encode($xml_array, JSON_PRETTY_PRINT);
                        break;
                    default:
                        echo XML_Data::keyed_array($xml_array);
                }
                break;
            case 'devote':
                $type      = 'song';
                $object_id = (int) $input['oid'];
                $media     = new Song($object_id);
                if (!$media->id) {
                    self::message('error', T_('Media object invalid or not specified'), '400', $input['api_format']);
                    break;
                }

                $object_id = $democratic->get_uid_from_object_id($media->id, $type);
                $democratic->remove_vote($object_id);

                // Everything was ok
                $xml_array = array('method' => $input['method'], 'result' => true);
                switch ($input['api_format']) {
                    case 'json':
                        echo json_encode($xml_array, JSON_PRETTY_PRINT);
                        break;
                    default:
                        echo XML_Data::keyed_array($xml_array);
                }
                break;
            case 'playlist':
                $objects = $democratic->get_items();
                $user    = User::get_from_username(Session::username($input['auth']));
                Song::build_cache($democratic->object_ids);
                Democratic::build_vote_cache($democratic->vote_ids);
                switch ($input['api_format']) {
                    case 'json':
                        echo JSON_Data::democratic($objects, $user->id);
                        break;
                    default:
                        echo XML_Data::democratic($objects, $user->id);
                }
                break;
            case 'play':
                $url       = $democratic->play_url();
                $xml_array = array('url' => $url);
                switch ($input['api_format']) {
                    case 'json':
                        echo json_encode($xml_array, JSON_PRETTY_PRINT);
                        break;
                    default:
                        echo XML_Data::keyed_array($xml_array);
                }
                break;
            default:
                self::message('error', T_('Invalid request'), '405', $input['api_format']);
                break;
        } // switch on method
        Session::extend($input['auth']);

        return true;
    } // democratic
} // end api.class
