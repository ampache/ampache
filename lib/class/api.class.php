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
 * API Class
 *
 * This handles functions relating to the API written for ampache, initially
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
    public static $version = '400001';

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
        if (!strlen($value)) {
            return false;
        }

        switch ($filter) {
            case 'add':
                // Check for a range, if no range default to gt
                if (strpos($value, '/')) {
                    $elements = explode('/', $value);
                    self::$browse->set_filter('add_lt', strtotime($elements['1']));
                    self::$browse->set_filter('add_gt', strtotime($elements['0']));
                } else {
                    self::$browse->set_filter('add_gt', strtotime($value));
                }
            break;
            case 'update':
                // Check for a range, if no range default to gt
                if (strpos($value, '/')) {
                    $elements = explode('/', $value);
                    self::$browse->set_filter('update_lt', strtotime($elements['1']));
                    self::$browse->set_filter('update_gt', strtotime($elements['0']));
                } else {
                    self::$browse->set_filter('update_gt', strtotime($value));
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
                // Rien a faire
            break;
        } // end filter

        return true;
    } // set_filter

    /**
     * handshake
     * MINIMUM_API_VERSION=380001
     *
     * This is the function that handles verifying a new handshake
     * Takes a timestamp, auth key, and username.
     *
     * @param array $input
     * $input = array(user      = (string) $username
     *                auth      = (string) $passphrase
     *                timestamp = (int) UNIXTIME()
     *                version   = (string) $version //optional)
     * @return boolean
     */
    public static function handshake($input)
    {
        $timestamp  = preg_replace('/[^0-9]/', '', $input['timestamp']);
        $passphrase = $input['auth'];
        if (empty($passphrase)) {
            $passphrase = Core::get_post('auth');
        }
        $username = trim($input['user']);
        $user_ip  = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP);
        if (isset($input['version'])) {
            // If version is provided, use it
            $version = $input['version'];
        } else {
            // Else, just use the latest version available
            $version = self::$version;
        }

        // Log the attempt
        debug_event('api.class', "Handshake Attempt, IP:$user_ip User:$username Version:$version", 5);

        // Version check shouldn't be soo restrictive... only check with initial version to not break clients compatibility
        if ((int) ($version) < self::$auth_version) {
            debug_event('api.class', 'Login Failed: version too old', 1);
            AmpError::add('api', T_('Login Failed: version too old'));

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
        debug_event('api.class', "Login Attempt, IP:$user_ip Time: $timestamp User:$username ($user_id) Auth:$passphrase", 1);

        if ($user_id > 0 && Access::check_network('api', $user_id, 5, $user_ip)) {

            // Authentication with user/password, we still need to check the password
            if ($username) {

                // If the timestamp isn't within 30 minutes sucks to be them
                if (($timestamp < (time() - 1800)) ||
                    ($timestamp > (time() + 1800))) {
                    debug_event('api.class', 'Login Failed: timestamp out of range ' . $timestamp . '/' . time(), 1);
                    AmpError::add('api', T_('Login Failed: timestamp out of range'));
                    echo XML_Data::error('401', T_('Error Invalid Handshake - ') . T_('Login Failed: timestamp out of range'));

                    return false;
                }

                // Now we're sure that there is an ACL line that matches
                // this user or ALL USERS, pull the user's password and
                // then see what we come out with
                $realpwd = $client->get_password();

                if (!$realpwd) {
                    debug_event('api.class', 'Unable to find user with userid of ' . $user_id, 1);
                    AmpError::add('api', T_('Invalid Username/Password'));
                    echo XML_Data::error('401', T_('Error Invalid Handshake - ') . T_('Invalid Username/Password'));

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

                debug_event('api.class', 'Login Success, passphrase matched', 1);

                // We need to also get the 'last update' of the
                // catalog information in an RFC 2822 Format
                $sql        = 'SELECT MAX(`last_update`) AS `update`, MAX(`last_add`) AS `add`, MAX(`last_clean`) AS `clean` FROM `catalog`';
                $db_results = Dba::read($sql);
                $row        = Dba::fetch_assoc($db_results);

                // Now we need to quickly get the song totals
                $sql        = "SELECT COUNT(`id`) AS `song` FROM `song` WHERE `song`.`enabled`='1'";
                $db_results = Dba::read($sql);
                $song       = Dba::fetch_assoc($db_results);

                $sql        = "SELECT COUNT(`id`) AS `album` FROM `album`";
                $db_results = Dba::read($sql);
                $album      = Dba::fetch_assoc($db_results);

                $sql        = "SELECT COUNT(`id`) AS `artist` FROM `artist`";
                $db_results = Dba::read($sql);
                $artist     = Dba::fetch_assoc($db_results);

                // Next the video counts
                $sql        = "SELECT COUNT(`id`) AS `video` FROM `video`";
                $db_results = Dba::read($sql);
                $vcounts    = Dba::fetch_assoc($db_results);

                $sql        = "SELECT COUNT(`id`) AS `playlist` FROM `playlist`";
                $db_results = Dba::read($sql);
                $playlist   = Dba::fetch_assoc($db_results);

                $sql        = "SELECT COUNT(`id`) AS `catalog` FROM `catalog` WHERE `catalog_type`='local'";
                $db_results = Dba::read($sql);
                $catalog    = Dba::fetch_assoc($db_results);

                echo XML_Data::keyed_array(array('auth' => $token,
                    'api' => self::$version,
                    'session_expire' => date("c", time() + AmpConfig::get('session_length') - 60),
                    'update' => date("c", $row['update']),
                    'add' => date("c", $row['add']),
                    'clean' => date("c", $row['clean']),
                    'songs' => $song['song'],
                    'albums' => $album['album'],
                    'artists' => $artist['artist'],
                    'playlists' => $playlist['playlist'],
                    'videos' => $vcounts['video'],
                    'catalogs' => $catalog['catalog']));

                return true;
            } // match
        } // end while

        debug_event('api.class', 'Login Failed, unable to match passphrase', 1);
        echo XML_Data::error('401', T_('Error Invalid Handshake - ') . T_('Invalid Username/Password'));

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
     * $input = array(auth = (string))
     */
    public static function ping($input)
    {
        $xmldata = array('server' => AmpConfig::get('version'), 'version' => self::$version, 'compatible' => '350001');

        // Check and see if we should extend the api sessions (done if valid sess is passed)
        if (Session::exists('api', $input['auth'])) {
            Session::extend($input['auth']);
            $xmldata = array_merge(array('session_expire' => date("c", time() + AmpConfig::get('session_length') - 60)), $xmldata);
        }

        debug_event('api.class', 'Ping Received from ' . filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP) . ' :: ' . $input['auth'], 5);

        ob_end_clean();
        echo XML_Data::keyed_array($xmldata);
    } // ping

    /**
     * artists
     * MINIMUM_API_VERSION=380001
     *
     * This takes a collection of inputs and returns
     * artist objects. This function is deprecated!
     *
     * @param array $input
     */
    public static function artists($input)
    {
        self::$browse->reset_filters();
        self::$browse->set_type('artist');
        self::$browse->set_sort('name', 'ASC');

        $method = $input['exact'] ? 'exact_match' : 'alpha_match';
        self::set_filter($method, $input['filter']);
        self::set_filter('add', $input['add']);
        self::set_filter('update', $input['update']);

        // Set the offset
        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);

        $artists = self::$browse->get_objects();
        // echo out the resulting xml document
        ob_end_clean();
        echo XML_Data::artists($artists, $input['include']);
    } // artists

    /**
     * artist
     * MINIMUM_API_VERSION=380001
     *
     * This returns a single artist based on the UID of said artist
     *
     * @param array $input
     */
    public static function artist($input)
    {
        $uid = scrub_in($input['filter']);
        echo XML_Data::artists(array($uid), $input['include']);
    } // artist

    /**
     * artist_albums
     * MINIMUM_API_VERSION=380001
     *
     * This returns the albums of an artist
     *
     * @param array $input
     */
    public static function artist_albums($input)
    {
        $artist = new Artist($input['filter']);

        $albums = $artist->get_albums(null, true);

        // Set the offset
        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);
        ob_end_clean();
        echo XML_Data::albums($albums);
    } // artist_albums

    /**
     * artist_songs
     * MINIMUM_API_VERSION=380001
     *
     * This returns the songs of the specified artist
     *
     * @param array $input
     */
    public static function artist_songs($input)
    {
        $artist = new Artist($input['filter']);
        $songs  = $artist->get_songs();

        // Set the offset
        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);
        ob_end_clean();
        echo XML_Data::songs($songs);
    } // artist_songs

    /**
     * albums
     * MINIMUM_API_VERSION=380001
     *
     * This returns albums based on the provided search filters
     *
     * @param array $input
     */
    public static function albums($input)
    {
        self::$browse->reset_filters();
        self::$browse->set_type('album');
        self::$browse->set_sort('name', 'ASC');
        $method = $input['exact'] ? 'exact_match' : 'alpha_match';
        self::set_filter($method, $input['filter']);
        self::set_filter('add', $input['add']);
        self::set_filter('update', $input['update']);

        $albums = self::$browse->get_objects();

        // Set the offset
        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);
        ob_end_clean();
        echo XML_Data::albums($albums, $input['include']);
    } // albums

    /**
     * album
     * MINIMUM_API_VERSION=380001
     *
     * This returns a single album based on the UID provided
     *
     * @param array $input
     */
    public static function album($input)
    {
        $uid = scrub_in($input['filter']);
        echo XML_Data::albums(array($uid), $input['include']);
    } // album

    /**
     * album_songs
     * MINIMUM_API_VERSION=380001
     *
     * This returns the songs of a specified album
     *
     * @param array $input
     */
    public static function album_songs($input)
    {
        $album = new Album($input['filter']);
        $songs = $album->get_songs();

        // Set the offset
        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);

        ob_end_clean();
        echo XML_Data::songs($songs);
    } // album_songs

    /**
     * tags
     * MINIMUM_API_VERSION=380001
     *
     * This returns the tags (Genres) based on the specified filter
     *
     * @param array $input
     */
    public static function tags($input)
    {
        self::$browse->reset_filters();
        self::$browse->set_type('tag');
        self::$browse->set_sort('name', 'ASC');

        $method = $input['exact'] ? 'exact_match' : 'alpha_match';
        self::set_filter($method, $input['filter']);
        $tags = self::$browse->get_objects();

        // Set the offset
        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);

        ob_end_clean();
        echo XML_Data::tags($tags);
    } // tags

    /**
     * tag
     * MINIMUM_API_VERSION=380001
     *
     * This returns a single tag based on UID
     *
     * @param array $input
     */
    public static function tag($input)
    {
        $uid = scrub_in($input['filter']);
        ob_end_clean();
        echo XML_Data::tags(array($uid));
    } // tag

    /**
     * tag_artists
     * MINIMUM_API_VERSION=380001
     *
     * This returns the artists associated with the tag in question as defined by the UID
     *
     * @param array $input
     */
    public static function tag_artists($input)
    {
        $artists = Tag::get_tag_objects('artist', $input['filter']);
        if ($artists) {
            XML_Data::set_offset($input['offset']);
            XML_Data::set_limit($input['limit']);

            ob_end_clean();
            echo XML_Data::artists($artists);
        }
    } // tag_artists

    /**
     * tag_albums
     * MINIMUM_API_VERSION=380001
     *
     * This returns the albums associated with the tag in question
     *
     * @param array $input
     */
    public static function tag_albums($input)
    {
        $albums = Tag::get_tag_objects('album', $input['filter']);
        if ($albums) {
            XML_Data::set_offset($input['offset']);
            XML_Data::set_limit($input['limit']);

            ob_end_clean();
            echo XML_Data::albums($albums);
        }
    } // tag_albums

    /**
     * tag_songs
     * MINIMUM_API_VERSION=380001
     *
     * returns the songs for this tag
     *
     * @param array $input
     */
    public static function tag_songs($input)
    {
        $songs = Tag::get_tag_objects('song', $input['filter']);

        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);

        ob_end_clean();
        echo XML_Data::songs($songs);
    } // tag_songs

    /**
     * songs
     * MINIMUM_API_VERSION=380001
     *
     * Returns songs based on the specified filter
     *
     * @param array $input
     */
    public static function songs($input)
    {
        self::$browse->reset_filters();
        self::$browse->set_type('song');
        self::$browse->set_sort('title', 'ASC');

        $method = $input['exact'] ? 'exact_match' : 'alpha_match';
        self::set_filter($method, $input['filter']);
        self::set_filter('add', $input['add']);
        self::set_filter('update', $input['update']);
        // Filter out disabled songs
        self::set_filter('enabled', '1');

        $songs = self::$browse->get_objects();

        // Set the offset
        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);

        ob_end_clean();
        echo XML_Data::songs($songs);
    } // songs

    /**
     * song
     * MINIMUM_API_VERSION=380001
     *
     * return a single song
     *
     * @param array $input
     */
    public static function song($input)
    {
        $uid = scrub_in($input['filter']);

        ob_end_clean();
        echo XML_Data::songs(array($uid));
    } // song

    /**
     * url_to_song
     * MINIMUM_API_VERSION=380001
     *
     * This takes a url and returns the song object in question
     *
     * @param array $input
     */
    public static function url_to_song($input)
    {
        // Don't scrub, the function needs her raw and juicy
        $data = Stream_URL::parse($input['url']);
        ob_end_clean();
        echo XML_Data::songs(array($data['id']));
    }

    /**
     * playlists
     * MINIMUM_API_VERSION=380001
     *
     * This returns playlists based on the specified filter
     *
     * @param array $input
     */
    public static function playlists($input)
    {
        self::$browse->reset_filters();
        self::$browse->set_type('playlist');
        self::$browse->set_sort('name', 'ASC');

        $method = $input['exact'] ? 'exact_match' : 'alpha_match';
        self::set_filter($method, $input['filter']);
        self::$browse->set_filter('playlist_type', '1');

        $playlist_ids = array_merge(self::$browse->get_objects(), Playlist::get_smartlists());
        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);

        ob_end_clean();
        echo XML_Data::playlists($playlist_ids);
    } // playlists

    /**
     * playlist
     * MINIMUM_API_VERSION=380001
     *
     * This returns a single playlist
     *
     * @param array $input
     */
    public static function playlist($input)
    {
        $uid = scrub_in($input['filter']);

        ob_end_clean();
        echo XML_Data::playlists(array($uid));
    } // playlist

    /**
     * playlist_songs
     * MINIMUM_API_VERSION=380001
     *
     * This returns the songs for a playlist
     *
     * @param array $input
     */
    public static function playlist_songs($input)
    {
        debug_event('api.class', 'Loading playlist: ' . $input['filter'] . ' ' .
                    (str_replace('smart_', '', (string) $input['filter']) === (string) $input['filter']), '5');
        if (str_replace('smart_', '', (string) $input['filter']) === (string) $input['filter']) {
            // Playlists
            $playlist = new Playlist($input['filter']);
            $items    = $playlist->get_items();
        } else {
            //Smartlists
            $playlist = new Search(str_replace('smart_', '', $input['filter']));
            $items    = $playlist->get_items();
        }

        $songs = array();
        foreach ($items as $object) {
            if ($object['object_type'] == 'song') {
                $songs[] = $object['object_id'];
            }
        } // end foreach

        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);
        ob_end_clean();
        echo XML_Data::songs($songs, $items);
    } // playlist_songs

    /**
     * playlist_create
     * MINIMUM_API_VERSION=380001
     *
     * This create a new playlist and return it
     *
     * @param array $input
     */
    public static function playlist_create($input)
    {
        $name = $input['name'];
        $type = $input['type'];
        if ($type != 'private') {
            $type = 'public';
        }

        $uid = Playlist::create($name, $type);
        echo XML_Data::playlists(array($uid));
    } // playlist_create

    /**
     * playlist_edit
     * MINIMUM_API_VERSION=400001
     *
     * This modifies name and type of playlist
     *
     * @param array $input
     */
    public static function playlist_edit($input)
    {
        $name = $input['name'];
        $type = $input['type'];
        ob_end_clean();
        $playlist = new Playlist($input['filter']);

        if (!$playlist->has_access()) {
            echo XML_Data::error('401', T_('Access denied to this playlist.'));
        } else {
            $array = [
                "name" => $name,
                "pl_type" => $type,
            ];
            $playlist->update($array);
            echo XML_Data::single_string('success');
        }
    } // playlist_edit

    /**
     * playlist_delete
     * MINIMUM_API_VERSION=380001
     *
     * This deletes a playlist
     *
     * @param array $input
     */
    public static function playlist_delete($input)
    {
        ob_end_clean();
        $playlist = new Playlist($input['filter']);
        if (!$playlist->has_access()) {
            echo XML_Data::error('401', T_('Access denied to this playlist.'));
        } else {
            $playlist->delete();
            echo XML_Data::single_string('success');
        }
    } // playlist_delete

    /**
     * playlist_add_song
     * MINIMUM_API_VERSION=380001
     *
     * This adds a song to a playlist
     *
     * @param array $input
     */
    public static function playlist_add_song($input)
    {
        ob_end_clean();
        $playlist = new Playlist($input['filter']);
        $song     = $input['song'];
        if (!$playlist->has_access()) {
            echo XML_Data::error('401', T_('Access denied to this playlist.'));
        } else {
            $playlist->add_songs(array($song), true);
            echo XML_Data::single_string('success');
        }
    } // playlist_add_song

    /**
     * playlist_remove_song
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=400001
     *
     * This removes a song from a playlist.
     * Pre-400001 the api required 'track' instead of 'song'.
     *
     * @param array $input
     */
    public static function playlist_remove_song($input)
    {
        ob_end_clean();
        $playlist = new Playlist($input['filter']);
        if ($input['song']) {
            $track = scrub_in($input['song']);
        } else {
            $track = scrub_in($input['track']);
        }
        if (!$playlist->has_access()) {
            echo XML_Data::error('401', T_('Access denied to this playlist.'));
        } else {
            $playlist->delete_track_number($track);
            $playlist->regenerate_track_numbers();
            echo XML_Data::single_string('success');
        }
    } // playlist_remove_song

    /**
     * search_songs
     * MINIMUM_API_VERSION=380001
     *
     * This searches the songs and returns... songs
     *
     * @param array $input
     */
    public static function search_songs($input)
    {
        $array                    = array();
        $array['type']            = 'song';
        $array['rule_1']          = 'anywhere';
        $array['rule_1_input']    = $input['filter'];
        $array['rule_1_operator'] = 0;

        ob_end_clean();

        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);

        $results = Search::run($array);

        echo XML_Data::songs($results);
    } // search_songs

    /**
     * advanced_search
     * MINIMUM_API_VERSION=380001
     *
     * Perform an advanced search given passed rules
     *
     * @param array $input
     */
    public static function advanced_search($input)
    {
        ob_end_clean();

        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);

        $results = Search::run($input);

        $type = 'song';
        if (isset($input['type'])) {
            $type = $input['type'];
        }

        switch ($type) {
            case 'artist':
                echo XML_Data::artists($results);
                break;
            case 'album':
                echo XML_Data::albums($results);
                break;
            default:
                echo XML_Data::songs($results);
                break;
        }
    } // advanced_search

    /**
     * videos
     * This returns video objects!
     *
     * @param array $input
     */
    public static function videos($input)
    {
        self::$browse->reset_filters();
        self::$browse->set_type('video');
        self::$browse->set_sort('title', 'ASC');

        $method = $input['exact'] ? 'exact_match' : 'alpha_match';
        Api::set_filter($method, $input['filter']);

        $video_ids = self::$browse->get_objects();

        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);

        echo XML_Data::videos($video_ids);
    } // videos

    /**
     * video
     * This returns a single video
     * @param array $input
     */
    public static function video($input)
    {
        $video_id = scrub_in($input['filter']);

        echo XML_Data::videos(array($video_id));
    } // video

    /**
     * localplay
     * MINIMUM_API_VERSION=380001
     *
     * This is for controlling localplay
     *
     * @param array $input
     */
    public static function localplay($input)
    {
        // Load their localplay instance
        $localplay = new Localplay(AmpConfig::get('localplay_controller'));
        $localplay->connect();

        switch ($input['command']) {
            case 'next':
            case 'prev':
            case 'play':
            case 'stop':
                $result_status = $localplay->$input['command']();
                $xml_array     = array('localplay' => array('command' => array($input['command'] => make_bool($result_status))));
                echo XML_Data::keyed_array($xml_array);
            break;
            default:
                // They are doing it wrong
                echo XML_Data::error('405', T_('Invalid Request'));
            break;
        } // end switch on command
    } // localplay

    /**
     * democratic
     * MINIMUM_API_VERSION=380001
     *
     * This is for controlling democratic play
     *
     * @param array $input
     */
    public static function democratic($input)
    {
        // Load up democratic information
        $democratic = Democratic::get_current_playlist();
        $democratic->set_parent();

        switch ($input['method']) {
            case 'vote':
                $type  = 'song';
                $media = new $type($input['oid']);
                if (!$media->id) {
                    echo XML_Data::error('400', T_('Media Object Invalid or Not Specified'));
                    break;
                }
                $democratic->add_vote(array(
                    array(
                        'object_type' => 'song',
                        'object_id' => $media->id
                    )
                ));

                // If everything was ok
                $xml_array = array('action' => $input['action'], 'method' => $input['method'], 'result' => true);
                echo XML_Data::keyed_array($xml_array);
            break;
            case 'devote':
                $type  = 'song';
                $media = new $type($input['oid']);
                if (!$media->id) {
                    echo XML_Data::error('400', T_('Media Object Invalid or Not Specified'));
                }

                $uid = $democratic->get_uid_from_object_id($media->id, $type);
                $democratic->remove_vote($uid);

                // Everything was ok
                $xml_array = array('action' => $input['action'], 'method' => $input['method'], 'result' => true);
                echo XML_Data::keyed_array($xml_array);
            break;
            case 'playlist':
                $objects = $democratic->get_items();
                Song::build_cache($democratic->object_ids);
                Democratic::build_vote_cache($democratic->vote_ids);
                echo XML_Data::democratic($objects);
            break;
            case 'play':
                $url       = $democratic->play_url();
                $xml_array = array('url' => $url);
                echo XML_Data::keyed_array($xml_array);
            break;
            default:
                echo XML_Data::error('405', T_('Invalid Request'));
            break;
        } // switch on method
    } // democratic

    /**
     * stats
     * MINIMUM_API_VERSION=380001
     * CHANGED_IN_API_VERSION=400001
     *
     * This get library stats for different object types.
     * When filter is null get some random items instead
     *
     * @param array $input
     * $input = array(type     = (string) 'song'|'album'|'artist'
     *                filter   = (string) 'newest'|'highest'|'frequent'|'recent'|'flagged'|null
     *                offset   = (integer) //optional
     *                limit    = (integer) //optional
     *                user_id  = (integer) //optional
     *                username = (string) //optional
     */
    public static function stats($input)
    {
        // moved type to filter and allowed multipe type selection
        $type   = $input['type'];
        $filter = $input['filter'];
        $offset = $input['offset'];
        $limit  = $input['limit'];
        // original method only searched albums and had poor method inputs
        if (in_array($input['type'], array('newest', 'highest', 'frequent', 'recent', 'flagged'))) {
            $type   = 'album';
            $filter = $input['type'];
        }
        if ($input['username']) {
            $username = $input['username'];
            $user_id  = User::get_from_username($username);
        } else {
            $user_id  = $input['user_id'];
        }
        if (!$limit) {
            $limit = AmpConfig::get('popular_threshold');
        }
        if (!$offset) {
            $offset = '';
        }

        $results = null;
        if ($filter == "newest") {
            debug_event('api.class', 'stats newest', 5);
            $results = Stats::get_newest($type, $limit, $offset);
        } else {
            if ($filter == "highest") {
                debug_event('api.class', 'stats highest', 4);
                $results = Rating::get_highest($type, $limit, $offset);
            } else {
                if ($filter == "frequent") {
                    debug_event('api.class', 'stats frequent', 4);
                    $results = Stats::get_top($type, $limit, '', $offset);
                } else {
                    if ($filter == "recent") {
                        debug_event('api.class', 'stats recent', 4);
                        if ($user_id !== null) {
                            $results = $user_id->get_recently_played($limit, $type);
                        } else {
                            $results = Stats::get_recent($type, $limit, $offset);
                        }
                    } else {
                        if ($filter == "flagged") {
                            debug_event('api.class', 'stats flagged', 4);
                            $results = Userflag::get_latest($type, $user_id);
                        } else {
                            debug_event('api.class', 'stats random ' . $type, 4);
                            if ($type === 'song') {
                                $results = Random::get_default($limit);
                            }
                            if ($type === 'artist') {
                                $results = Artist::get_random($limit);
                            }
                            if ($type === 'album') {
                                $results = Album::get_random($limit);
                            }
                        }
                    }
                }
            }
        }

        if ($results !== null) {
            ob_end_clean();
            debug_event('api.class', 'stats found results searching for ' . $type, 5);
            if ($type === 'song') {
                echo XML_Data::songs($results);
            }
            if ($type === 'artist') {
                echo XML_Data::artists($results);
            }
            if ($type === 'album') {
                echo XML_Data::albums($results);
            }
        }
    } // stats

    /**
     * user
     * MINIMUM_API_VERSION=380001
     *
     * This get an user public information
     *
     * @param array $input
     */
    public static function user($input)
    {
        $username = $input['username'];
        if (!empty($username)) {
            $user = User::get_from_username($username);
            if ($user !== null) {
                ob_end_clean();
                echo XML_Data::user($user);
            } else {
                debug_event('api.class', 'User `' . $username . '` cannot be found.', 1);
            }
        } else {
            debug_event('api.class', 'Username required on user function call.', 1);
        }
    } // user

    /**
     * followers
     * MINIMUM_API_VERSION=380001
     *
     * This get an user followers
     *
     * @param array $input
     */
    public static function followers($input)
    {
        if (AmpConfig::get('sociable')) {
            $username = $input['username'];
            if (!empty($username)) {
                $user = User::get_from_username($username);
                if ($user !== null) {
                    $users = $user->get_followers();
                    ob_end_clean();
                    echo XML_Data::users($users);
                } else {
                    debug_event('api.class', 'User `' . $username . '` cannot be found.', 1);
                }
            } else {
                debug_event('api.class', 'Username required on followers function call.', 1);
            }
        } else {
            debug_event('api.class', 'Sociable feature is not enabled.', 3);
        }
    } // followers

    /**
     * following
     * MINIMUM_API_VERSION=380001
     *
     * This get the user list followed by an user
     *
     * @param array $input
     */
    public static function following($input)
    {
        if (AmpConfig::get('sociable')) {
            $username = $input['username'];
            if (!empty($username)) {
                $user = User::get_from_username($username);
                if ($user !== null) {
                    $users = $user->get_following();
                    debug_event('api.class', 'User is following:  ' . print_r($users), 1);
                    ob_end_clean();
                    echo XML_Data::users([(int) $user]);
                } else {
                    debug_event('api.class', 'User `' . $username . '` cannot be found.', 1);
                }
            } else {
                debug_event('api.class', 'Username required on following function call.', 1);
            }
        } else {
            debug_event('api.class', 'Sociable feature is not enabled.', 3);
        }
    } // following

    /**
     * toggle_follow
     * MINIMUM_API_VERSION=380001
     *
     * This follow/unfollow an user
     *
     * @param array $input
     */
    public static function toggle_follow($input)
    {
        if (AmpConfig::get('sociable')) {
            $username = $input['username'];
            if (!empty($username)) {
                $user = User::get_from_username($username);
                if ($user !== null) {
                    Core::get_global('user')->toggle_follow($user->id);
                    ob_end_clean();
                    echo XML_Data::single_string('success');
                }
            } else {
                debug_event('api.class', 'Username to toggle required on follow function call.', 1);
            }
        } else {
            debug_event('api.class', 'Sociable feature is not enabled.', 3);
        }
    } // toggle_follow

    /**
     * last_shouts
     * MINIMUM_API_VERSION=380001
     *
     * This get the latest posted shouts
     *
     * @param array $input
     */
    public static function last_shouts($input)
    {
        $limit = (int) ($input['limit']);
        if ($limit < 1) {
            $limit = AmpConfig::get('popular_threshold');
        }
        if (AmpConfig::get('sociable')) {
            $username = $input['username'];
            if (!empty($username)) {
                $shouts = Shoutbox::get_top($limit, $username);
            } else {
                $shouts = Shoutbox::get_top($limit);
            }

            ob_end_clean();
            echo XML_Data::shouts($shouts);
        } else {
            debug_event('api.class', 'Sociable feature is not enabled.', 3);
        }
    } // last_shouts

    /**
     * rate
     * MINIMUM_API_VERSION=380001
     *
     * This rates a library item
     *
     * @param array $input
     */
    public static function rate($input)
    {
        ob_end_clean();
        $type      = $input['type'];
        $object_id = $input['id'];
        $rating    = $input['rating'];

        if (!Core::is_library_item($type) || !$object_id) {
            echo XML_Data::error('401', T_('Wrong library item type.'));
        } else {
            $item = new $type($object_id);
            if (!$item->id) {
                echo XML_Data::error('404', T_('Library item not found.'));
            } else {
                $rate = new Rating($object_id, $type);
                $rate->set_rating($rating);
                echo XML_Data::single_string('success');
            }
        }
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
     * $input = array(type = (string) 'song'|'album'|'artist'
     *                id   = (int) $object_id
     *                flag = (bool) 0|1)
     */
    public static function flag($input)
    {
        ob_end_clean();
        $type      = $input['type'];
        $object_id = $input['id'];
        $flag      = $input['flag'];
        $client    = User::get_from_apikey($input['auth']);
        $user_id   = null;
        if ($client) {
            $user_id = $client->id;
        }

        if (!Core::is_library_item($type) || !$object_id) {
            echo XML_Data::error('401', T_('Wrong library item type.'));
        } else {
            $item = new $type($object_id);
            if (!$item->id) {
                echo XML_Data::error('404', T_('Library item not found.'));
            } else {
                $userflag = new Userflag($object_id, $type);
                if ($userflag->set_flag($flag, $user_id)) {
                    echo XML_Data::single_string('success');
                } else {
                    echo XML_Data::single_string('failure');
                }
            }
        }
    } // flag

    /**
     * record_play
     * MINIMUM_API_VERSION=400001
     *
     * Take a song_id and update the object_count and user_activity table with a play
     * This allows other sources to record play history to ampache
     *
     * @param array $input
     * $input = array(id     = (int) $object_id
     *                user   = (int) $user_id
     *                client = (string) $agent (optional))
     */
    public static function record_play($input)
    {
        ob_end_clean();
        $object_id = $input['id'];
        $user_id   = (int) $input['user'];
        $type      = 'song';
        $user      = new User($user_id);
        $valid     = in_array($user->id, User::get_valid_users());

        // validate supplied user
        if (!$valid) {
            echo XML_Data::error('404', T_('User_id not found.'));

            return;
        }

        // validate client string or fall back to 'api'
        if ($input['client']) {
            $agent = $input['client'];
        } else {
            $agent = 'api';
        }

        if (!Core::is_library_item($type) || !$object_id) {
            echo XML_Data::error('401', T_('Wrong library item type.'));
        } else {
            $item = new $type($object_id);
            if (!$item->id) {
                echo XML_Data::error('404', T_('Library item not found.'));
            } elseif ($valid) {
                $user->update_stats($type, $object_id, $agent);
                echo XML_Data::single_string('success');
            }
        }
    } // record_play

    /**
     * scrobble
     * MINIMUM_API_VERSION=400001
     *
     * Search for a song using text info and then record a play if found.
     * This allows other sources to record play history to ampache
     *
     * @param array $input
     * $input = array(song       = (string) $song_name
     *                artist     = (string) $artist_name
     *                album      = (string) $album_name
     *                songmbid   = (string) $song_mbid //optional
     *                artistmbid = (string) $artist_mbid //optional
     *                albummbid  = (string) $album_mbid //optional
     *                date       = (int) UNIXTIME() //optional
     *                client     = (string) $agent //optional)
     */
    public static function scrobble($input)
    {
        ob_end_clean();
        $song_name   = scrub_in($input['song']);
        $artist_name = scrub_in($input['artist']);
        $album_name  = scrub_in($input['album']);
        $song_mbid   = scrub_in($input['song_mbid']); //optional
        $artist_mbid = scrub_in($input['artist_mbid']); //optional
        $album_mbid  = scrub_in($input['album_mbid']); //optional
        $date        = scrub_in($input['date']); //optional
        $user_id     = Core::get_global('user')->id;
        $user        = new User($user_id);
        $valid       = in_array($user->id, User::get_valid_users());

        // set time to now if not included
        if (!$date) {
            $date = time();
        }
        // validate supplied user
        if (!$valid) {
            echo XML_Data::error('404', T_('User_id not found.'));

            return;
        }
        
        //validate minimum required options
        debug_event('api.class', 'scrobble searching for:' . $song_name . ' - ' . $artist_name . ' - ' . $album_name, 4);
        if (!$song_name || !$album_name || !$artist_name) {
            echo XML_Data::error('401', T_('Invalid input options.'));

            return;
        }

        // validate client string or fall back to 'api'
        if ($input['client']) {
            $agent = $input['client'];
        } else {
            $agent = 'api';
        }
        $scrobble_id = Song::can_scrobble($song_name, $artist_name, $album_name, $song_mbid, $artist_mbid, $album_mbid);

        if ($scrobble_id === '') {
            echo XML_Data::error('401', T_('failed to scrobble: no item found!'));
        } else {
            $item = new Song($scrobble_id);
            if (!$item->id) {
                echo XML_Data::error('404', T_('Library item not found.'));
            } elseif ($valid) {
                $user->update_stats('song', $scrobble_id, $agent, array(), false, $date);
                echo XML_Data::single_string('successfully scrobbled: ' . $scrobble_id);
            }
        }
    } // scrobble

    /**
     * timeline
     * MINIMUM_API_VERSION=380001
     *
     * This gets a user timeline from their username
     *
     * @param array $input
     * $input = array(username = (string)
     *                limit    = (int)
     *                since    = (int) UNIXTIME())
     */
    public static function timeline($input)
    {
        if (AmpConfig::get('sociable')) {
            $username = $input['username'];
            $limit    = (int) ($input['limit']);
            $since    = (int) ($input['since']);

            if (!empty($username)) {
                $user = User::get_from_username($username);
                if ($user !== null) {
                    if (Preference::get_by_user($user->id, 'allow_personal_info_recent')) {
                        $activities = Useractivity::get_activities($user->id, $limit, $since);
                        ob_end_clean();
                        echo XML_Data::timeline($activities);
                    }
                }
            } else {
                debug_event('api.class', 'Username required on timeline function call.', 1);
            }
        } else {
            debug_event('api.class', 'Sociable feature is not enabled.', 3);
        }
    } // timeline

    /**
     * friends_timeline
     * MINIMUM_API_VERSION=380001
     *
     * This get current user friends timeline
     *
     * @param array $input
     * $input = array(limit = (int)
     *                since = (int) UNIXTIME())
     */
    public static function friends_timeline($input)
    {
        if (AmpConfig::get('sociable')) {
            $limit = (int) ($input['limit']);
            $since = (int) ($input['since']);
            $user  = Core::get_global('user')->id;

            if ($user > 0) {
                $activities = Useractivity::get_friends_activities($user, $limit, $since);
                ob_end_clean();
                echo XML_Data::timeline($activities);
            }
        } else {
            debug_event('api.class', 'Sociable feature is not enabled.', 3);
        }
    } // friends_timeline

    /**
     * catalog_action
     * MINIMUM_API_VERSION=400001
     *
     * Kick off a catalog update or clean for the selected catalog
     *
     * @param array $input
     * $input = array(task    = (string) 'add_to_catalog'|'clean_catalog'
     *                catalog = (int) $catalog_id)
     */
    public static function catalog_action($input)
    {
        $catalog = Catalog::create_from_id((int) $input['catalog']);

        if ($catalog && ((string) $input['task'] === 'add_to_catalog' || (string) $input['task'] === 'clean_catalog')) {
            $catalog->process_action($input['task'], (int) $input['catalog']);
            echo XML_Data::single_string('successfull started: ' . (string) $input['task']);
        } else {
            echo XML_Data::error('401', T_('Bad information in the call to catalog_action.'));
        }
    }
} // API class
