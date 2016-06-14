<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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
    public static $version = '380001';

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
        if (is_null(self::$browse)) {
            self::$browse = new Browse(null, false);
        }
    }

    /**
     * set_filter
     * This is a play on the browse function, it's different as we expose
     * the filters in a slightly different and vastly simpler way to the
     * end users--so we have to do a little extra work to make them work
     * internally.
     * @param string $filter
     * @param int|string|boolean|null $value
     * @return boolean
     */
    public static function set_filter($filter,$value)
    {
        if (!strlen($value)) {
            return false;
        }

        switch ($filter) {
            case 'add':
                // Check for a range, if no range default to gt
                if (strpos($value,'/')) {
                    $elements = explode('/',$value);
                    self::$browse->set_filter('add_lt',strtotime($elements['1']));
                    self::$browse->set_filter('add_gt',strtotime($elements['0']));
                } else {
                    self::$browse->set_filter('add_gt',strtotime($value));
                }
            break;
            case 'update':
                // Check for a range, if no range default to gt
                if (strpos($value,'/')) {
                    $elements = explode('/',$value);
                    self::$browse->set_filter('update_lt',strtotime($elements['1']));
                    self::$browse->set_filter('update_gt',strtotime($elements['0']));
                } else {
                    self::$browse->set_filter('update_gt',strtotime($value));
                }
            break;
            case 'alpha_match':
                self::$browse->set_filter('alpha_match',$value);
            break;
            case 'exact_match':
                self::$browse->set_filter('exact_match',$value);
            break;
            case 'enabled':
                self::$browse->set_filter('enabled',$value);
            break;
            default:
                // Rien a faire
            break;
        } // end filter

        return true;
    } // set_filter

    /**
     * handshake
     *
     * This is the function that handles verifying a new handshake
     * Takes a timestamp, auth key, and username.
     * @param array
     * @return boolean
     */
    public static function handshake($input)
    {
        $timestamp  = preg_replace('/[^0-9]/', '', $input['timestamp']);
        $passphrase = $input['auth'];
        if (empty($passphrase)) {
            $passphrase = $_POST['auth'];
        }
        $username = trim($input['user']);
        $ip       = $_SERVER['REMOTE_ADDR'];
        if (isset($input['version'])) {
            // If version is provided, use it
            $version = $input['version'];
        } else {
            // Else, just use the latest version available
            $version = self::$version;
        }

        // Log the attempt
        debug_event('API', "Handshake Attempt, IP:$ip User:$username Version:$version", 5);

        // Version check shouldn't be soo restrictive... only check with initial version to not break clients compatibility
        if (intval($version) < self::$auth_version) {
            debug_event('API', 'Login Failed: version too old', 1);
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
        debug_event('API', "Login Attempt, IP:$ip Time: $timestamp User:$username ($user_id) Auth:$passphrase", 1);

        if ($user_id > 0 && Access::check_network('api', $user_id, 5, $ip)) {

            // Authentication with user/password, we still need to check the password
            if ($username) {

                // If the timestamp isn't within 30 minutes sucks to be them
                if (($timestamp < (time() - 1800)) ||
                    ($timestamp > (time() + 1800))) {
                    debug_event('API', 'Login Failed: timestamp out of range ' . $timestamp . '/' . time(), 1);
                    AmpError::add('api', T_('Login Failed: timestamp out of range'));
                    echo XML_Data::error('401', T_('Error Invalid Handshake - ') . T_('Login Failed: timestamp out of range'));
                    return false;
                }

                // Now we're sure that there is an ACL line that matches
                // this user or ALL USERS, pull the user's password and
                // then see what we come out with
                $realpwd = $client->get_password();

                if (!$realpwd) {
                    debug_event('API', 'Unable to find user with userid of ' . $user_id, 1);
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
                $token = Session::create($data);

                debug_event('API', 'Login Success, passphrase matched', 1);

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

                echo XML_Data::keyed_array(array('auth'=>$token,
                    'api'=>self::$version,
                    'session_expire'=>date("c",time()+AmpConfig::get('session_length')-60),
                    'update'=>date("c",$row['update']),
                    'add'=>date("c",$row['add']),
                    'clean'=>date("c",$row['clean']),
                    'songs'=>$song['song'],
                    'albums'=>$album['album'],
                    'artists'=>$artist['artist'],
                    'playlists'=>$playlist['playlist'],
                    'videos'=>$vcounts['video'],
                    'catalogs'=>$catalog['catalog']));
                return true;
            } // match
        } // end while

        debug_event('API','Login Failed, unable to match passphrase','1');
        echo XML_Data::error('401', T_('Error Invalid Handshake - ') . T_('Invalid Username/Password'));

        return false;
    } // handshake

    /**
      * ping
     * This can be called without being authenticated, it is useful for determining if what the status
     * of the server is, and what version it is running/compatible with
     * @param array $input
     */
    public static function ping($input)
    {
        $xmldata = array('server'=>AmpConfig::get('version'),'version'=>Api::$version,'compatible'=>'350001');

        // Check and see if we should extend the api sessions (done if valid sess is passed)
        if (Session::exists('api', $input['auth'])) {
            Session::extend($input['auth']);
            $xmldata = array_merge(array('session_expire'=>date("c",time()+AmpConfig::get('session_length')-60)),$xmldata);
        }

        debug_event('API','Ping Received from ' . $_SERVER['REMOTE_ADDR'] . ' :: ' . $input['auth'],'5');

        ob_end_clean();
        echo XML_Data::keyed_array($xmldata);
    } // ping

    /**
     * artists
     * This takes a collection of inputs and returns
     * artist objects. This function is deprecated!
     * //DEPRECATED
     * @param array $input
     */
    public static function artists($input)
    {
        self::$browse->reset_filters();
        self::$browse->set_type('artist');
        self::$browse->set_sort('name','ASC');

        $method = $input['exact'] ? 'exact_match' : 'alpha_match';
        Api::set_filter($method,$input['filter']);
        Api::set_filter('add',$input['add']);
        Api::set_filter('update',$input['update']);

        // Set the offset
        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);

        $artists = self::$browse->get_objects();
        // echo out the resulting xml document
        ob_end_clean();
        echo XML_Data::artists($artists);
    } // artists

    /**
     * artist
     * This returns a single artist based on the UID of said artist
     * //DEPRECATED
     * @param array $input
     */
    public static function artist($input)
    {
        $uid = scrub_in($input['filter']);
        echo XML_Data::artists(array($uid));
    } // artist

    /**
     * artist_albums
     * This returns the albums of an artist
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
     * This returns the songs of the specified artist
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
     * This returns albums based on the provided search filters
     * @param array $input
     */
    public static function albums($input)
    {
        self::$browse->reset_filters();
        self::$browse->set_type('album');
        self::$browse->set_sort('name','ASC');
        $method = $input['exact'] ? 'exact_match' : 'alpha_match';
        Api::set_filter($method,$input['filter']);
        Api::set_filter('add',$input['add']);
        Api::set_filter('update',$input['update']);

        $albums = self::$browse->get_objects();

        // Set the offset
        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);
        ob_end_clean();
        echo XML_Data::albums($albums);
    } // albums

    /**
     * album
     * This returns a single album based on the UID provided
     * @param array $input
     */
    public static function album($input)
    {
        $uid = scrub_in($input['filter']);
        echo XML_Data::albums(array($uid));
    } // album

    /**
     * album_songs
     * This returns the songs of a specified album
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
     * This returns the tags based on the specified filter
     * @param array $input
     */
    public static function tags($input)
    {
        self::$browse->reset_filters();
        self::$browse->set_type('tag');
        self::$browse->set_sort('name','ASC');

        $method = $input['exact'] ? 'exact_match' : 'alpha_match';
        Api::set_filter($method,$input['filter']);
        $tags = self::$browse->get_objects();

        // Set the offset
        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);

        ob_end_clean();
        echo XML_Data::tags($tags);
    } // tags

    /**
     * tag
     * This returns a single tag based on UID
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
     * This returns the artists associated with the tag in question as defined by the UID
     * @param array $input
     */
    public static function tag_artists($input)
    {
        $artists = Tag::get_tag_objects('artist',$input['filter']);
        if ($artists) {
            XML_Data::set_offset($input['offset']);
            XML_Data::set_limit($input['limit']);

            ob_end_clean();
            echo XML_Data::artists($artists);
        }
    } // tag_artists

    /**
     * tag_albums
     * This returns the albums associated with the tag in question
     * @param array $input
     */
    public static function tag_albums($input)
    {
        $albums = Tag::get_tag_objects('album',$input['filter']);
        if ($albums) {
            XML_Data::set_offset($input['offset']);
            XML_Data::set_limit($input['limit']);

            ob_end_clean();
            echo XML_Data::albums($albums);
        }
    } // tag_albums

    /**
     * tag_songs
     * returns the songs for this tag
     * @param array $input
     */
    public static function tag_songs($input)
    {
        $songs = Tag::get_tag_objects('song',$input['filter']);

        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);

        ob_end_clean();
        echo XML_Data::songs($songs);
    } // tag_songs

    /**
     * songs
     * Returns songs based on the specified filter
     * @param array $input
     */
    public static function songs($input)
    {
        self::$browse->reset_filters();
        self::$browse->set_type('song');
        self::$browse->set_sort('title','ASC');

        $method = $input['exact'] ? 'exact_match' : 'alpha_match';
        Api::set_filter($method,$input['filter']);
        Api::set_filter('add',$input['add']);
        Api::set_filter('update',$input['update']);
        // Filter out disabled songs
        Api::set_filter('enabled','1');

        $songs = self::$browse->get_objects();

        // Set the offset
        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);

        ob_end_clean();
        echo XML_Data::songs($songs);
    } // songs

    /**
     * song
     * returns a single song
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
     *
     * This takes a url and returns the song object in question
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
     * This returns playlists based on the specified filter
     * @param array $input
     */
    public static function playlists($input)
    {
        self::$browse->reset_filters();
        self::$browse->set_type('playlist');
        self::$browse->set_sort('name','ASC');

        $method = $input['exact'] ? 'exact_match' : 'alpha_match';
        Api::set_filter($method,$input['filter']);
        self::$browse->set_filter('playlist_type', '1');

        $playlist_ids = self::$browse->get_objects();
        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);

        ob_end_clean();
        echo XML_Data::playlists($playlist_ids);
    } // playlists

    /**
     * playlist
     * This returns a single playlist
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
     * This returns the songs for a playlist
     * @param array $input
     */
    public static function playlist_songs($input)
    {
        $playlist = new Playlist($input['filter']);
        $items    = $playlist->get_items();

        $songs = array();
        foreach ($items as $object) {
            if ($object['object_type'] == 'song') {
                $songs[] = $object['object_id'];
            }
        } // end foreach

        XML_Data::set_offset($input['offset']);
        XML_Data::set_limit($input['limit']);
        ob_end_clean();
        echo XML_Data::songs($songs,$items);
    } // playlist_songs

    /**
     * playlist_create
     * This create a new playlist and return it
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
    }

    /**
     * playlist_delete
     * This delete a playlist
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
     * This add a song to a playlist
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
            $playlist->add_songs(array($song));
            echo XML_Data::single_string('success');
        }
    } // playlist_add_song

    /**
     * playlist_remove_song
     * This remove a song from a playlist
     * @param array $input
     */
    public static function playlist_remove_song($input)
    {
        ob_end_clean();
        $playlist = new Playlist($input['filter']);
        $track    = scrub_in($input['track']);
        if (!$playlist->has_access()) {
            echo XML_Data::error('401', T_('Access denied to this playlist.'));
        } else {
            $playlist->delete_track_number($track);
            echo XML_Data::single_string('success');
        }
    } // playlist_remove_song

    /**
     * search_songs
     * This searches the songs and returns... songs
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
     * Perform an advanced search given passed rules
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
     * @param array $input
     */
    public static function videos($input)
    {
        self::$browse->reset_filters();
        self::$browse->set_type('video');
        self::$browse->set_sort('title','ASC');

        $method = $input['exact'] ? 'exact_match' : 'alpha_match';
        Api::set_filter($method,$input['filter']);

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
     * This is for controling localplay
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
                $xml_array     = array('localplay'=>array('command'=>array($input['command']=>make_bool($result_status))));
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
     * This is for controlling democratic play
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
                $xml_array = array('action'=>$input['action'],'method'=>$input['method'],'result'=>true);
                echo XML_Data::keyed_array($xml_array);
            break;
            case 'devote':
                $type  = 'song';
                $media = new $type($input['oid']);
                if (!$media->id) {
                    echo XML_Data::error('400', T_('Media Object Invalid or Not Specified'));
                }

                $uid = $democratic->get_uid_from_object_id($media->id,$type);
                $democratic->remove_vote($uid);

                // Everything was ok
                $xml_array = array('action'=>$input['action'],'method'=>$input['method'],'result'=>true);
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
                $xml_array = array('url'=>$url);
                echo XML_Data::keyed_array($xml_array);
            break;
            default:
                echo XML_Data::error('405', T_('Invalid Request'));
            break;
        } // switch on method
    } // democratic

    /**
     * This get library stats.
     * @param array $input
     */
    public static function stats($input)
    {
        $type     = $input['type'];
        $offset   = $input['offset'];
        $limit    = $input['limit'];
        $username = $input['username'];

        $albums = null;
        if ($type == "newest") {
            $albums = Stats::get_newest("album", $limit, $offset);
        } else {
            if ($type == "highest") {
                $albums = Rating::get_highest("album", $limit, $offset);
            } else {
                if ($type == "frequent") {
                    $albums = Stats::get_top("album", $limit, '', $offset);
                } else {
                    if ($type == "recent") {
                        if (!empty($username)) {
                            $user = User::get_from_username($username);
                            if ($user !== null) {
                                $albums = $user->get_recently_played($limit, 'album');
                            } else {
                                debug_event('api', 'User `' . $username . '` cannot be found.', 1);
                            }
                        } else {
                            $albums = Stats::get_recent("album", $limit, $offset);
                        }
                    } else {
                        if ($type == "flagged") {
                            $albums = Userflag::get_latest('album');
                        } else {
                            if (!$limit) {
                                $limit = AmpConfig::get('popular_threshold');
                            }
                            $albums = Album::get_random($limit);
                        }
                    }
                }
            }
        }

        if ($albums !== null) {
            ob_end_clean();
            echo XML_Data::albums($albums);
        }
    } // stats

    /**
     * user
     * This get an user public information
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
                debug_event('api', 'User `' . $username . '` cannot be found.', 1);
            }
        } else {
            debug_event('api', 'Username required on user function call.', 1);
        }
    } // user

    /**
     * followers
     * This get an user followers
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
                    echo XML_Data::users($user);
                } else {
                    debug_event('api', 'User `' . $username . '` cannot be found.', 1);
                }
            } else {
                debug_event('api', 'Username required on followers function call.', 1);
            }
        } else {
            debug_event('api', 'Sociable feature is not enabled.', 3);
        }
    } // followers

    /**
     * following
     * This get the user list followed by an user
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
                    ob_end_clean();
                    echo XML_Data::users($user);
                } else {
                    debug_event('api', 'User `' . $username . '` cannot be found.', 1);
                }
            } else {
                debug_event('api', 'Username required on following function call.', 1);
            }
        } else {
            debug_event('api', 'Sociable feature is not enabled.', 3);
        }
    } // following

    /**
     * toggle_follow
     * This follow/unfollow an user
     * @param array $input
     */
    public static function toggle_follow($input)
    {
        if (AmpConfig::get('sociable')) {
            $username = $input['username'];
            if (!empty($username)) {
                $user = User::get_from_username($username);
                if ($user !== null) {
                    $GLOBALS['user']->toggle_follow($user->id);
                    ob_end_clean();
                    echo XML_Data::single_string('success');
                }
            } else {
                debug_event('api', 'Username to toggle required on follow function call.', 1);
            }
        } else {
            debug_event('api', 'Sociable feature is not enabled.', 3);
        }
    } // toggle_follow

    /**
     * last_shouts
     * This get the latest posted shouts
     * @param array $input
     */
    public static function last_shouts($input)
    {
        $limit = intval($input['limit']);
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
            debug_event('api', 'Sociable feature is not enabled.', 3);
        }
    } // last_shouts

    /**
     * rate
     * This rate a library item
     * @param array $input
     */
    public static function rate($input)
    {
        ob_end_clean();
        $type   = $input['type'];
        $id     = $input['id'];
        $rating = $input['rating'];
        
        if (!Core::is_library_item($type) || !$id) {
            echo XML_Data::error('401', T_('Wrong library item type.'));
        } else {
            $item = new $type($id);
            if (!$item->id) {
                echo XML_Data::error('404', T_('Library item not found.'));
            } else {
                $r = new Rating($id, $type);
                $r->set_rating($rating);
                echo XML_Data::single_string('success');
            }
        }
    } // rate

    /**
     * timeline
     * This get an user timeline
     * @param array $input
     */
    public static function timeline($input)
    {
        if (AmpConfig::get('sociable')) {
            $username = $input['username'];
            $limit    = intval($input['limit']);
            $since    = intval($input['since']);
            
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
                debug_event('api', 'Username required on timeline function call.', 1);
            }
        } else {
            debug_event('api', 'Sociable feature is not enabled.', 3);
        }
    } // timeline

    /**
     * timeline
     * This get current user friends timeline
     * @param array $input
     */
    public static function friends_timeline($input)
    {
        if (AmpConfig::get('sociable')) {
            $limit = intval($input['limit']);
            $since = intval($input['since']);
            
            if ($GLOBALS['user']->id > 0) {
                $activities = Useractivity::get_friends_activities($GLOBALS['user']->id, $limit, $since);
                ob_end_clean();
                echo XML_Data::timeline($activities);
            }
        } else {
            debug_event('api', 'Sociable feature is not enabled.', 3);
        }
    } // friends_timeline
} // API class
