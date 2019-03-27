<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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
 * User Class
 *
 * This class handles all of the user related functions includingn the creationg
 * and deletion of the user objects from the database by defualt you constrcut it
 * with a user_id from user.id
 *
 */
class User extends database_object
{
    //Basic Componets
    /**
     * @var int $id
     */
    public $id;
    /**
     * @var string $username
     */
    public $username;
    /**
     * @var string $fullname
     */
    public $fullname;
    /**
     * @var boolean $fullname_public
     */
    public $fullname_public;
    /**
     * @var int $access
     */
    public $access;
    /**
     * @var boolean $disabled
     */
    public $disabled;
    /**
     * @var string $email
     */
    public $email;
    /**
     * @var int $last_seen
     */
    public $last_seen;
    /**
     * @var int $create_date
     */
    public $create_date;
    /**
     * @var string $validation
     */
    public $validation;
    /**
     * @var string $website
     */
    public $website;
    /**
     * @var string $state
     */
    public $state;
    /**
     * @var string city
     */
    public $city;
    /**
     * @var string $apikey
     */
    public $apikey;

    // Constructed variables
    /**
     * @var array $prefs
     */
    public $prefs = array();

    /**
     * @var Tmp_Playlist $playlist
     */
    public $playlist;

    /**
     * @var string $f_name
     */
    public $f_name;
    /**
     * @var string $f_last_seen
     */
    public $f_last_seen;
    /**
     * @var string $f_create_date
     */
    public $f_create_date;
    /**
     * @var string $link
     */
    public $link;
    /**
     * @var string $f_link
     */
    public $f_link;
    /**
     * @var string $f_useage
     */
    public $f_useage;
    /**
     * @var string $ip_history
     */
    public $ip_history;
    /**
     * @var string $f_avatar
     */
    public $f_avatar;
    /**
     * @var string $f_avatar_mini
     */
    public $f_avatar_mini;
    /**
     * @var string $f_avatar_medium
     */
    public $f_avatar_medium;

    /**
     * Constructor
     * This function is the constructor object for the user
     * class, it currently takes a username
     */
    public function __construct($user_id=0)
    {
        if (!$user_id) {
            return false;
        }

        $this->id = intval($user_id);

        $info = $this->_get_info();

        foreach ($info as $key => $value) {
            // Let's not save the password in this object :S
            if ($key == 'password') {
                continue;
            }
            $this->$key = $value;
        }

        // Make sure the Full name is always filled
        if (strlen($this->fullname) < 1) {
            $this->fullname = $this->username;
        }
    } // Constructor

    /**
     * count
     *
     * This returns the number of user accounts that exist.
     */
    public static function count()
    {
        $sql              = 'SELECT COUNT(`id`) FROM `user`';
        $db_results       = Dba::read($sql);
        $data             = Dba::fetch_row($db_results);
        $results          = array();
        $results['users'] = $data[0];

        $time      = time();
        $last_seen = $time - 1200;
        $sql       = 'SELECT COUNT(DISTINCT `session`.`username`) FROM `session` ' .
            'INNER JOIN `user` ON `session`.`username` = `user`.`username` ' .
            'WHERE `session`.`expire` > ? and `user`.`last_seen` > ?';
        $db_results           = Dba::read($sql, array($time, $last_seen));
        $data                 = Dba::fetch_row($db_results);
        $results['connected'] = $data[0];

        return $results;
    }

    /**
     * _get_info
     * This function returns the information for this object
     */
    private function _get_info()
    {
        $id = intval($this->id);

        if (parent::is_cached('user', $id)) {
            return parent::get_from_cache('user', $id);
        }

        $data = array();
        // If the ID is -1 then
        if ($id == '-1') {
            $data['username'] = 'System';
            $data['fullname'] = 'Ampache User';
            $data['access']   = '25';

            return $data;
        }

        $sql        = "SELECT * FROM `user` WHERE `id`='$id'";
        $db_results = Dba::read($sql);

        $data = Dba::fetch_assoc($db_results);

        parent::add_to_cache('user', $id, $data);

        return $data;
    } // _get_info

    /**
     * load_playlist
     * This is called once per page load it makes sure that this session
     * has a tmp_playlist, creating it if it doesn't, then sets $this->playlist
     * as a tmp_playlist object that can be fiddled with later on
     */
    public function load_playlist()
    {
        $session_id = session_id();

        $this->playlist = Tmp_Playlist::get_from_session($session_id);
    } // load_playlist

    /**
     * get_valid_users
     * This returns all valid users in database.
     */
    public static function get_valid_users()
    {
        $users = array();

        $sql        = "SELECT `id` FROM `user` WHERE `disabled` = '0'";
        $db_results = Dba::read($sql);
        while ($results = Dba::fetch_assoc($db_results)) {
            $users[] = $results['id'];
        }

        return $users;
    } // get_valid_users

    /**
     * get_from_username
     * This returns a built user from a username. This is a
     * static function so it doesn't require an instance
     */
    public static function get_from_username($username)
    {
        $sql        = "SELECT `id` FROM `user` WHERE `username` = ?";
        $db_results = Dba::read($sql, array($username));
        $results    = Dba::fetch_assoc($db_results);

        $user = new User($results['id']);

        return $user;
    } // get_from_username

    /**
     * get_from_apikey
     * This returns a built user from an apikey. This is a
     * static function so it doesn't require an instance
     */
    public static function get_from_apikey($apikey)
    {
        $user   = null;
        $apikey = trim($apikey);
        if (!empty($apikey)) {
            $sql        = "SELECT `id` FROM `user` WHERE `apikey` = ?";
            $db_results = Dba::read($sql, array($apikey));
            $results    = Dba::fetch_assoc($db_results);

            if ($results['id']) {
                $user = new User($results['id']);
            }
        }

        return $user;
    } // get_from_apikey

    /**
     * get_from_email
     * This returns a built user from a email. This is a
     * static function so it doesn't require an instance
     */
    public static function get_from_email($email)
    {
        $user       = null;
        $sql        = "SELECT `id` FROM `user` WHERE `email` = ?";
        $db_results = Dba::read($sql, array($email));
        if ($results = Dba::fetch_assoc($db_results)) {
            $user = new User($results['id']);
        }

        return $user;
    } // get_from_email

    /**
     * get_from_website
     * This returns users list related to a website.
     */
    public static function get_from_website($website)
    {
        $website    = rtrim($website, "/");
        $sql        = "SELECT `id` FROM `user` WHERE `website` = ? LIMIT 1";
        $db_results = Dba::read($sql, array($website));
        $users      = array();
        while ($results = Dba::fetch_assoc($db_results)) {
            $users[] = $results['id'];
        }

        return $users;
    } // get_from_website

    /**
     * get_catalogs
     * This returns the catalogs as an array of ids that this user is allowed to access
     */
    public function get_catalogs()
    {
        if (parent::is_cached('user_catalog', $this->id)) {
            return parent::get_from_cache('user_catalog', $this->id);
        }

        $sql        = "SELECT * FROM `user_catalog` WHERE `user` = ?";
        $db_results = Dba::read($sql, array($this->id));

        $catalogs = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $catalogs[] = $row['catalog'];
        }

        parent::add_to_cache('user_catalog', $this->id, $catalogs);

        return $catalogs;
    } // get_catalogs

    /**
     * get_preferences
     * This is a little more complicate now that we've got many types of preferences
     * This funtions pulls all of them an arranges them into a spiffy little array
     * You can specify a type to limit it to a single type of preference
     * []['title'] = ucased type name
     * []['prefs'] = array(array('name','display','value'));
     * []['admin'] = t/f value if this is an admin only section
     */
    public function get_preferences($type = 0, $system = false)
    {
        // Fill out the user id
        $user_id = $system ? Dba::escape(-1) : Dba::escape($this->id);

        $user_limit = "";
        if (!$system) {
            $user_limit = "AND preference.catagory != 'system'";
        } else {
            if ($type != '0') {
                $user_limit = "AND preference.catagory = '" . Dba::escape($type) . "'";
            }
        }


        $sql = "SELECT preference.name, preference.description, preference.catagory, preference.subcatagory, preference.level, user_preference.value " .
            "FROM preference INNER JOIN user_preference ON user_preference.preference=preference.id " .
            "WHERE user_preference.user='$user_id' " . $user_limit .
            " ORDER BY preference.catagory, preference.subcatagory, preference.description";

        $db_results = Dba::read($sql);
        $results    = array();
        $type_array = array();
        /* Ok this is crapy, need to clean this up or improve the code FIXME */
        while ($r = Dba::fetch_assoc($db_results)) {
            $type  = $r['catagory'];
            $admin = false;
            if ($type == 'system') {
                $admin = true;
            }
            $type_array[$type][$r['name']] = array('name' => $r['name'],'level' => $r['level'],'description' => $r['description'],'value' => $r['value'],'subcategory' => $r['subcatagory']);
            $results[$type]                = array('title' => ucwords($type),'admin' => $admin,'prefs' => $type_array[$type]);
        } // end while

        return $results;
    } // get_preferences

    /**
     * set_preferences
     * sets the prefs for this specific user
     */
    public function set_preferences()
    {
        $user_id = Dba::escape($this->id);

        $sql = "SELECT preference.name,user_preference.value FROM preference,user_preference WHERE user_preference.user='$user_id' " .
            "AND user_preference.preference=preference.id AND preference.type != 'system'";
        $db_results = Dba::read($sql);

        while ($r = Dba::fetch_assoc($db_results)) {
            $key               = $r['name'];
            $this->prefs[$key] = $r['value'];
        }
    } // set_preferences

    /**
     * get_favorites
     * returns an array of your $type favorites
     */
    public function get_favorites($type)
    {
        $results = Stats::get_user(AmpConfig::get('popular_threshold'), $type, $this->id, 1);

        $items = array();

        foreach ($results as $r) {
            /* If its a song */
            if ($type == 'song') {
                $data        = new Song($r['object_id']);
                $data->count = $r['count'];
                $data->format();
                $data->f_link;
                $items[] = $data;
            }
            /* If its an album */
            elseif ($type == 'album') {
                $data = new Album($r['object_id']);
                //$data->count = $r['count'];
                $data->format();
                $items[] = $data;
            }
            /* If its an artist */
            elseif ($type == 'artist') {
                $data = new Artist($r['object_id']);
                //$data->count = $r['count'];
                $data->format();
                $data->f_name = $data->f_link;
                $items[]      = $data;
            }
            /* If it's a genre */
            elseif ($type == 'genre') {
                $data = new Genre($r['object_id']);
                //$data->count = $r['count'];
                $data->format();
                $data->f_name = $data->f_link;
                $items[]      = $data;
            }
        } // end foreach

        return $items;
    } // get_favorites

    /**
     * get_recommendations
     * This returns recommended objects of $type. The recommendations
     * are based on voodoo economics,the phase of the moon and my current BAL.
     */
    public function get_recommendations($type)
    {
        /* First pull all of your ratings of this type */
        $sql = "SELECT object_id,user_rating FROM ratings " .
            "WHERE object_type='" . Dba::escape($type) . "' AND user='" . Dba::escape($this->id) . "'";
        $db_results = Dba::read($sql);

        // Incase they only have one user
        $users   = array();
        $ratings = array();
        while ($r = Dba::fetch_assoc($db_results)) {
            /* Store the fact that you rated this */
            $key           = $r['object_id'];
            $ratings[$key] = true;

            /* Build a key'd array of users with this same rating */
            $sql = "SELECT user FROM ratings WHERE object_type='" . Dba::escape($type) . "' " .
                "AND user !='" . Dba::escape($this->id) . "' AND object_id='" . Dba::escape($r['object_id']) . "' " .
                "AND user_rating ='" . Dba::escape($r['user_rating']) . "'";
            $user_results = Dba::read($sql);

            while ($user_info = Dba::fetch_assoc($user_results)) {
                $key = $user_info['user'];
                $users[$key]++;
            }
        } // end while

        /* now we've got your ratings, and all users and the # of ratings that match your ratings
         * sort the users[$key] array by value and then find things they've rated high (4+) that you
         * haven't rated
         */
        $recommendations = array();
        asort($users);

        foreach ($users as $user_id => $score) {

            /* Find everything they've rated at 4+ */
            $sql = "SELECT object_id,user_rating FROM ratings " .
                "WHERE user='" . Dba::escape($user_id) . "' AND user_rating >='4' AND " .
                "object_type = '" . Dba::escape($type) . "' ORDER BY user_rating DESC";
            $db_results = Dba::read($sql);

            while ($r = Dba::fetch_assoc($db_results)) {
                $key = $r['object_id'];
                if (isset($ratings[$key])) {
                    continue;
                }

                /* Let's only get 5 total for now */
                if (count($recommendations) > 5) {
                    return $recommendations;
                }

                $recommendations[$key] = $r['user_rating'];
            } // end while
        } // end foreach users

        return $recommendations;
    } // get_recommendations

    /**
     * is_logged_in
     * checks to see if $this user is logged in returns their current IP if they
     * are logged in
     */
    public function is_logged_in()
    {
        $username = Dba::escape($this->username);

        $sql = "SELECT `id`,`ip` FROM `session` WHERE `username`='$username'" .
            " AND `expire` > " . time();
        $db_results = Dba::read($sql);

        if ($row = Dba::fetch_assoc($db_results)) {
            $ip = $row['ip'] ? $row['ip'] : null;

            return $ip;
        }

        return false;
    } // is_logged_in

    /**
     * has_access
     * this function checkes to see if this user has access
     * to the passed action (pass a level requirement)
     */
    public function has_access($needed_level)
    {
        if (AmpConfig::get('demo_mode')) {
            return true;
        }

        if ($this->access >= $needed_level) {
            return true;
        }

        return false;
    } // has_access

    /**
     * is_registered
     * Check if the user is registered
     * @return boolean
     */
    public static function is_registered()
    {
        if (!$GLOBALS['user']->id) {
            return false;
        }

        if (!AmpConfig::get('use_auth') && $GLOBALS['user']->access < 5) {
            return false;
        }

        return true;
    }

    /**
     * update
     * This function is an all encompasing update function that
     * calls the mini ones does all the error checking and all that
     * good stuff
     */
    public function update(array $data)
    {
        if (empty($data['username'])) {
            AmpError::add('username', T_('Error Username Required'));
        }

        if ($data['password1'] != $data['password2'] and !empty($data['password1'])) {
            AmpError::add('password', T_("Error Passwords don't match"));
        }

        if (AmpError::occurred()) {
            return false;
        }

        if (!isset($data['fullname_public'])) {
            $data['fullname_public'] = false;
        }

        foreach ($data as $name => $value) {
            if ($name == 'password1') {
                $name = 'password';
            } else {
                $value = scrub_in($value);
            }

            switch ($name) {
                case 'password':
                case 'access':
                case 'email':
                case 'username':
                case 'fullname':
                case 'fullname_public':
                case 'website':
                case 'state':
                case 'city':
                    if ($this->$name != $value) {
                        $function = 'update_' . $name;
                        $this->$function($value);
                    }
                break;
                case 'clear_stats':
                    Stats::clear($this->id);
                break;
                default:
                    // Rien a faire
                break;
            }
        }

        return $this->id;
    }

    /**
     * update_username
     * updates their username
     */
    public function update_username($new_username)
    {
        $sql            = "UPDATE `user` SET `username` = ? WHERE `id` = ?";
        $this->username = $new_username;
        Dba::write($sql, array($new_username, $this->id));
    } // update_username

    /**
     * update_validation
     * This is used by the registration mumbojumbo
     * Use this function to update the validation key
     * NOTE: crap this doesn't have update_item the humanity of it all
     */
    public function update_validation($new_validation)
    {
        $sql              = "UPDATE `user` SET `validation` = ?, `disabled`='1' WHERE `id` = ?";
        $db_results       = Dba::write($sql, array($new_validation, $this->id));
        $this->validation = $new_validation;

        return $db_results;
    } // update_validation

    /**
     * update_fullname
     * updates their fullname
     */
    public function update_fullname($new_fullname)
    {
        $sql = "UPDATE `user` SET `fullname` = ? WHERE `id` = ?";
        Dba::write($sql, array($new_fullname, $this->id));
    } // update_fullname

    /**
     * update_fullname_public
     * updates their fullname public
     */
    public function update_fullname_public($new_fullname_public)
    {
        $sql = "UPDATE `user` SET `fullname_public` = ? WHERE `id` = ?";
        Dba::write($sql, array($new_fullname_public ? '1' : '0', $this->id));
    } // update_fullname_public

    /**
     * update_email
     * updates their email address
     */
    public function update_email($new_email)
    {
        $sql = "UPDATE `user` SET `email` = ? WHERE `id` = ?";
        Dba::write($sql, array($new_email, $this->id));
    } // update_email

    /**
     * update_website
     * updates their website address
     */
    public function update_website($new_website)
    {
        $new_website = rtrim($new_website, "/");
        $sql         = "UPDATE `user` SET `website` = ? WHERE `id` = ?";
        Dba::write($sql, array($new_website, $this->id));
    } // update_website

    /**
     * update_state
     * updates their state
     */
    public function update_state($new_state)
    {
        $sql = "UPDATE `user` SET `state` = ? WHERE `id` = ?";
        Dba::write($sql, array($new_state, $this->id));
    } // update_state

    /**
     * update_city
     * updates their city
     */
    public function update_city($new_city)
    {
        $sql = "UPDATE `user` SET `city` = ? WHERE `id` = ?";
        Dba::write($sql, array($new_city, $this->id));
    } // update_city

    /**
     * update_apikey
     * Updates their api key
     */
    public function update_apikey($new_apikey)
    {
        $sql = "UPDATE `user` SET `apikey` = ? WHERE `id` = ?";
        Dba::write($sql, array($new_apikey, $this->id));
    } // update_website

    /**
     * generate_apikey
     * Generate a new user API key
     */
    public function generate_apikey()
    {
        $apikey = hash('md5', time() . $this->username . $this->get_password());
        $this->update_apikey($apikey);
    }

    /**
     * get_password
     * Get the current hashed user password from database.
     */
    public function get_password()
    {
        $sql        = 'SELECT * FROM `user` WHERE `id` = ?';
        $db_results = Dba::read($sql, array($this->id));
        $row        = Dba::fetch_assoc($db_results);

        return $row['password'];
    }

    /**
     * disable
     * This disables the current user
     */
    public function disable()
    {
        // Make sure we aren't disabling the last admin
        $sql        = "SELECT `id` FROM `user` WHERE `disabled` = '0' AND `id` != '" . $this->id . "' AND `access`='100'";
        $db_results = Dba::read($sql);

        if (!Dba::num_rows($db_results)) {
            return false;
        }

        $sql = "UPDATE `user` SET `disabled`='1' WHERE id='" . $this->id . "'";
        Dba::write($sql);

        // Delete any sessions they may have
        $sql = "DELETE FROM `session` WHERE `username`='" . Dba::escape($this->username) . "'";
        Dba::write($sql);

        return true;
    } // disable

    /**
      * enable
     * this enables the current user
     */
    public function enable()
    {
        $sql = "UPDATE `user` SET `disabled`='0' WHERE id='" . $this->id . "'";
        Dba::write($sql);

        return true;
    } // enable

    /**
     * update_access
     * updates their access level
     */
    public function update_access($new_access)
    {
        /* Prevent Only User accounts */
        if ($new_access < '100') {
            $sql        = "SELECT `id` FROM user WHERE `access`='100' AND `id` != '$this->id'";
            $db_results = Dba::read($sql);
            if (!Dba::num_rows($db_results)) {
                return false;
            }
        }

        $new_access = Dba::escape($new_access);
        $sql        = "UPDATE `user` SET `access`='$new_access' WHERE `id`='$this->id'";
        Dba::write($sql);
    } // update_access

    /*!
        @function update_last_seen
        @discussion updates the last seen data for this user
    */
    public function update_last_seen()
    {
        $sql = "UPDATE user SET last_seen='" . time() . "' WHERE `id`='$this->id'";
        Dba::write($sql);
    } // update_last_seen

    /**
     * update_user_stats
     * updates the playcount mojo for this specific user
     */
    public function update_stats($media_type, $media_id, $agent = '', $location = array(), $noscrobble = false)
    {
        debug_event('user.class.php', 'Updating stats for {' . $media_type . '/' . $media_id . '} {' . $agent . '}...', 5);
        $media = new $media_type($media_id);
        $media->format();
        $user = $this->id;

        // We shouldn't test on file only
        if (!strlen($media->file)) {
            return false;
        }

        if (!$noscrobble) {
            $this->set_preferences();
            // If pthreads available, we call save_songplay in a new thread to quickly return
            if (class_exists("Thread", false)) {
                debug_event('user.class.php', 'Calling save_mediaplay plugins in a new thread...', 5);
                $thread = new scrobbler_async($GLOBALS['user'], $media);
                if ($thread->start()) {
                    //$thread->join();
                } else {
                    debug_event('user.class.php', 'Error when starting the thread.', 1);
                }
            } else {
                User::save_mediaplay($GLOBALS['user'], $media);
            }
        } else {
            debug_event('user.class.php', 'Scrobbling explicitly skipped', 5);
        }

        $media->set_played($user, $agent, $location);

        return true;
    } // update_stats

    public static function save_mediaplay($user, $media)
    {
        debug_event('user.class.php', 'save_mediaplay...', 5);
        foreach (Plugin::get_plugins('save_mediaplay') as $plugin_name) {
            try {
                $plugin = new Plugin($plugin_name);
                if ($plugin->load($user)) {
                    $plugin->_plugin->save_mediaplay($media);
                }
            } catch (Exception $e) {
                debug_event('user.class.php', 'Stats plugin error: ' . $e->getMessage(), 1);
            }
        }
    }

    /**
     * insert_ip_history
     * This inserts a row into the IP History recording this user at this
     * address at this time in this place, doing this thing.. you get the point
     */
    public function insert_ip_history()
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $sip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            debug_event('User Ip', 'Login from ip adress: ' . $sip, '3');
        } else {
            $sip = $_SERVER['REMOTE_ADDR'];
            debug_event('User Ip', 'Login from ip adress: ' . $sip, '3');
        }
        
        // Remove port information if any
        if (!empty($sip)) {
            // Use parse_url to support easily ipv6
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === true) {
                $sipar = parse_url("http://" . $sip);
            } else {
                $sipar = parse_url("http://[" . $sip . "]");
            }
            $sip   = $sipar['host'];
        }

        $ip    = (!empty($sip)) ? Dba::escape(inet_pton(trim($sip, "[]"))) : '';
        $date  = time();
        $user  = $this->id;
        $agent = Dba::escape($_SERVER['HTTP_USER_AGENT']);

        $sql = "INSERT INTO `ip_history` (`ip`,`user`,`date`,`agent`) VALUES ('$ip','$user','$date','$agent')";
        Dba::write($sql);

        /* Clean up old records... sometimes  */
        if (rand(1, 100) > 60) {
            $date = time() - (86400 * AmpConfig::get('user_ip_cardinality'));
            $sql  = "DELETE FROM `ip_history` WHERE `date` < $date";
            Dba::write($sql);
        }

        return true;
    } // insert_ip_history

    /**
     * create
     * inserts a new user into ampache
     */
    public static function create($username, $fullname, $email, $website, $password, $access, $state = '', $city = '', $disabled = false)
    {
        $website     = rtrim($website, "/");
        $password    = hash('sha256', $password);
        $disabled    = $disabled ? 1 : 0;

        /* Now Insert this new user */
        $sql = "INSERT INTO `user` (`username`, `disabled`, " .
            "`fullname`, `email`, `password`, `access`, `create_date`";
        $params = array($username, $disabled, $fullname, $email, $password, $access, time());

        if (!empty($website)) {
            $sql .= ", `website`";
            $params[] = $website;
        }
        if (!empty($state)) {
            $sql .= ", `state`";
            $params[] = $state;
        }
        if (!empty($city)) {
            $sql .= ", `city`";
            $params[] = $city;
        }

        $sql .= ") VALUES(?, ?, ?, ?, ?, ?, ?";

        if (!empty($website)) {
            $sql .= ", ?";
        }
        if (!empty($state)) {
            $sql .= ", ?";
        }
        if (!empty($city)) {
            $sql .= ", ?";
        }

        $sql .= ")";
        $db_results = Dba::write($sql, $params);

        if (!$db_results) {
            return false;
        }

        // Get the insert_id
        $insert_id = Dba::insert_id();

        /* Populates any missing preferences, in this case all of them */
        self::fix_preferences($insert_id);

        return $insert_id;
    } // create

    /**
     * update_password
     * updates a users password
     */
    public function update_password($new_password)
    {
        $new_password = hash('sha256', $new_password);

        $new_password = Dba::escape($new_password);
        $sql          = "UPDATE `user` SET `password` = ? WHERE `id` = ?";
        $db_results   = Dba::write($sql, array($new_password, $this->id));

        // Clear this (temp fix)
        if ($db_results) {
            unset($_SESSION['userdata']['password']);
        }
    } // update_password

    /**
     * format
     * This function sets up the extra variables we need when we are displaying a
     * user for an admin, these should not be normally called when creating a
     * user object
     */
    public function format($details = true)
    {
        /* If they have a last seen date */
        if (!$this->last_seen) {
            $this->f_last_seen = T_('Never');
        } else {
            $this->f_last_seen = date("m\/d\/Y - H:i", $this->last_seen);
        }

        /* If they have a create date */
        if (!$this->create_date) {
            $this->f_create_date = T_('Unknown');
        } else {
            $this->f_create_date = date("m\/d\/Y - H:i", $this->create_date);
        }

        $this->f_name = ($this->fullname_public ? $this->fullname : $this->username);

        // Base link
        $this->link   = AmpConfig::get('web_path') . '/stats.php?action=show_user&user_id=' . $this->id;
        $this->f_link = '<a href="' . $this->link . '">' . $this->f_name . '</a>';

        if ($details) {
            /* Calculate their total Bandwidth Usage */
            $sql = "SELECT sum(`song`.`size`) as size FROM `song` LEFT JOIN `object_count` ON `song`.`id`=`object_count`.`object_id` " .
                "WHERE `object_count`.`user`='$this->id' AND `object_count`.`object_type`='song'";
            $db_results = Dba::read($sql);

            $result = Dba::fetch_assoc($db_results);
            $total  = $result['size'];

            $this->f_useage = UI::format_bytes($total);

            /* Get Users Last ip */
            if (count($data = $this->get_ip_history(1))) {
                $ip = $data['0']['ip'];
                if (!empty($ip)) {
                    $this->ip_history = inet_ntop($ip);
                }
            } else {
                $this->ip_history = T_('Not Enough Data');
            }
        }

        $avatar = $this->get_avatar();
        if (!empty($avatar['url'])) {
            $this->f_avatar = '<img src="' . $avatar['url'] . '" title="' . $avatar['title'] . '" />';
        }
        if (!empty($avatar['url_mini'])) {
            $this->f_avatar_mini = '<img src="' . $avatar['url_mini'] . '" title="' . $avatar['title'] . '" style="width: 32px; height: 32px;" />';
        }
        if (!empty($avatar['url_medium'])) {
            $this->f_avatar_medium = '<img src="' . $avatar['url_medium'] . '" title="' . $avatar['title'] . '" style="width: 64px; height: 64px;" />';
        }
    } // format_user

    /**
     * access_name_to_level
     * This takes the access name for the user and returns the level
     */
    public static function access_name_to_level($level)
    {
        switch ($level) {
            case 'admin':
                return '100';
            case 'user':
                return '25';
            case 'manager':
                return '75';
            case 'guest':
                return '5';
            default:
                return '0';
        }
    } // access_name_to_level

    /**
      * fix_preferences
     * This is the new fix_preferences function, it does the following
     * Remove Duplicates from user, add in missing
     * If -1 is passed it also removes duplicates from the `preferences`
     * table.
     */
    public static function fix_preferences($user_id)
    {
        $user_id = Dba::escape($user_id);

        /* Get All Preferences for the current user */
        $sql        = "SELECT * FROM `user_preference` WHERE `user`='$user_id'";
        $db_results = Dba::read($sql);

        $results = array();

        while ($r = Dba::fetch_assoc($db_results)) {
            $pref_id = $r['preference'];
            /* Check for duplicates */
            if (isset($results[$pref_id])) {
                $r['value'] = Dba::escape($r['value']);
                $sql        = "DELETE FROM `user_preference` WHERE `user`='$user_id' AND `preference`='" . $r['preference'] . "' AND" .
                    " `value`='" . Dba::escape($r['value']) . "'";
                Dba::write($sql);
            } // if its set
            else {
                $results[$pref_id] = 1;
            }
        } // end while

        /* If we aren't the -1 user before we continue grab the -1 users values */
        if ($user_id != '-1') {
            $sql = "SELECT `user_preference`.`preference`,`user_preference`.`value` FROM `user_preference`,`preference` " .
                "WHERE `user_preference`.`preference` = `preference`.`id` AND `user_preference`.`user`='-1' AND `preference`.`catagory` !='system'";
            $db_results = Dba::read($sql);
            /* While through our base stuff */
            $zero_results = array();
            while ($r = Dba::fetch_assoc($db_results)) {
                $key                = $r['preference'];
                $zero_results[$key] = $r['value'];
            }
        } // if not user -1

        // get me _EVERYTHING_
        $sql = "SELECT * FROM `preference`";

        // If not system, exclude system... *gasp*
        if ($user_id != '-1') {
            $sql .= " WHERE catagory !='system'";
        }
        $db_results = Dba::read($sql);

        while ($r = Dba::fetch_assoc($db_results)) {
            $key = $r['id'];

            /* Check if this preference is set */
            if (!isset($results[$key])) {
                if (isset($zero_results[$key])) {
                    $r['value'] = $zero_results[$key];
                }
                $value = Dba::escape($r['value']);
                $sql   = "INSERT INTO user_preference (`user`,`preference`,`value`) VALUES ('$user_id','$key','$value')";
                Dba::write($sql);
            }
        } // while preferences
    } // fix_preferences

    /**
     * delete
     * deletes this user and everything associated with it. This will affect
     * ratings and tottal stats
     */
    public function delete()
    {
        /*
          Before we do anything make sure that they aren't the last
          admin
        */
        if ($this->has_access(100)) {
            $sql        = "SELECT `id` FROM `user` WHERE `access`='100' AND id != ?";
            $db_results = Dba::read($sql, array($this->id));
            if (!Dba::num_rows($db_results)) {
                return false;
            }
        } // if this is an admin check for others

        // Delete their playlists
        $sql = "DELETE FROM `playlist` WHERE `user` = ?";
        Dba::write($sql, array($this->id));

        // Clean up the playlist data table
        $sql = "DELETE FROM `playlist_data` USING `playlist_data` " .
            "LEFT JOIN `playlist` ON `playlist`.`id`=`playlist_data`.`playlist` " .
            "WHERE `playlist`.`id` IS NULL";
        Dba::write($sql);

        // Delete any stats they have
        $sql = "DELETE FROM `object_count` WHERE `user` = ?";
        Dba::write($sql, array($this->id));

        // Clear the IP history for this user
        $sql = "DELETE FROM `ip_history` WHERE `user` = ?";
        Dba::write($sql, array($this->id));

        // Nuke any access lists that are specific to this user
        $sql = "DELETE FROM `access_list` WHERE `user` = ?";
        Dba::write($sql, array($this->id));

        // Delete their ratings
        $sql = "DELETE FROM `rating` WHERE `user` = ?";
        Dba::write($sql, array($this->id));

        // Delete their tags
        $sql = "DELETE FROM `tag_map` WHERE `user` = ?";
        Dba::write($sql, array($this->id));

        // Clean out the tags
        $sql = "DELETE FROM `tags` USING `tag_map` LEFT JOIN `tag_map` ON tag_map.id=tags.map_id AND tag_map.id IS NULL";
        Dba::write($sql);

        // Delete their preferences
        $sql = "DELETE FROM `user_preference` WHERE `user` = ?";
        Dba::write($sql, array($this->id));

        // Delete their voted stuff in democratic play
        $sql = "DELETE FROM `user_vote` WHERE `user` = ?";
        Dba::write($sql, array($this->id));

        // Delete their shoutbox posts
        $sql = "DELETE FROM `user_shout` WHERE `user` = ?";
        Dba::write($sql, array($this->id));

        // Delete their private messages posts
        $sql = "DELETE FROM `user_pvmsg` WHERE `from_user` = ? OR `to_user` = ?";
        Dba::write($sql, array($this->id, $this->id));

        // Delete their following/followers
        $sql = "DELETE FROM `user_follow` WHERE `user` = ? OR `follow_user` = ?";
        Dba::write($sql, array($this->id, $this->id));

        // Delete the user itself
        $sql = "DELETE FROM `user` WHERE `id` = ?";
        Dba::write($sql, array($this->id));

        $sql = "DELETE FROM `session` WHERE `username` = ?";
        Dba::write($sql, array($this->username));

        return true;
    } // delete

    /**
     * is_online
     * delay how long since last_seen in seconds default of 20 min
     * calcs difference between now and last_seen
     * if less than delay, we consider them still online
     */
    public function is_online($delay = 1200)
    {
        return time() - $this->last_seen <= $delay;
    } // is_online

    /**
     * get_user_validation
     *if user exists before activation can be done.
     */
    public static function get_validation($username)
    {
        $sql        = "SELECT `validation` FROM `user` WHERE `username` = ?";
        $db_results = Dba::read($sql, array($username));

        $row = Dba::fetch_assoc($db_results);

        return $row['validation'];
    } // get_validation

    /**
     * get_recently_played
     * This gets the recently played items for this user respecting
     * the limit passed
     */
    public function get_recently_played($limit, $type='')
    {
        if (!$type) {
            $type = 'song';
        }

        $sql = "SELECT * FROM `object_count` WHERE `object_type` = ? AND `user` = ? " .
            "ORDER BY `date` DESC LIMIT " . $limit;
        $db_results = Dba::read($sql, array($type, $this->id));

        $results = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['object_id'];
        }

        return $results;
    } // get_recently_played

    /**
     * get_ip_history
     * This returns the ip_history from the
     * last AmpConfig::get('user_ip_cardinality') days
     */
    public function get_ip_history($count='', $distinct='')
    {
        $username     = Dba::escape($this->id);
        $count        = $count ? intval($count) : intval(AmpConfig::get('user_ip_cardinality'));

        // Make sure it's something
        if ($count < 1) {
            $count = '1';
        }
        $limit_sql = "LIMIT " . intval($count);

        $group_sql = "";
        if ($distinct) {
            $group_sql = "GROUP BY `ip`";
        }

        /* Select ip history */
        $sql = "SELECT `ip`,`date` FROM `ip_history`" .
            " WHERE `user`='$username'" .
            " $group_sql ORDER BY `date` DESC $limit_sql";
        $db_results = Dba::read($sql);

        $results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row;
        }

        return $results;
    } // get_ip_history

    /**
     * Get item fullname.
     * @return string
     */
    public function get_fullname()
    {
        return $this->f_name;
    }

    /**
     * get_avatar
     * Get the user avatar
     */
    public function get_avatar($local = false)
    {
        $avatar = array();

        $avatar['title'] = T_('User avatar');
        $upavatar        = new Art($this->id, 'user');
        if ($upavatar->get_db()) {
            $avatar['url']        = ($local ? AmpConfig::get('local_web_path') : AmpConfig::get('web_path')) . '/image.php?object_type=user&object_id=' . $this->id;
            $avatar['url_mini']   = $avatar['url'];
            $avatar['url_medium'] = $avatar['url'];
            $avatar['url'] .= '&thumb=4';
            $avatar['url_mini'] .= '&thumb=5';
            $avatar['url_medium'] .= '&thumb=3';
        } else {
            foreach (Plugin::get_plugins('get_avatar_url') as $plugin_name) {
                $plugin = new Plugin($plugin_name);
                if ($plugin->load($GLOBALS['user'])) {
                    $avatar['url'] = $plugin->_plugin->get_avatar_url($this);
                    if (!empty($avatar['url'])) {
                        $avatar['url_mini']   = $plugin->_plugin->get_avatar_url($this, 32);
                        $avatar['url_medium'] = $plugin->_plugin->get_avatar_url($this, 64);
                        $avatar['title'] .= ' (' . $plugin->_plugin->name . ')';
                        break;
                    }
                }
            }
        }

        return $avatar;
    } // get_avatar

    public function update_avatar($data, $mime = '')
    {
        $art = new Art($this->id, 'user');
        $art->insert($data, $mime);
    }
    
    public function upload_avatar()
    {
        $upload = array();
        if (!empty($_FILES['avatar']['tmp_name']) && $_FILES['avatar']['size'] <= AmpConfig::get('max_upload_size')) {
            $path_info      = pathinfo($_FILES['avatar']['name']);
            $upload['file'] = $_FILES['avatar']['tmp_name'];
            $upload['mime'] = 'image/' . $path_info['extension'];
            $image_data     = Art::get_from_source($upload, 'user');

            if ($image_data) {
                $this->update_avatar($image_data, $upload['mime']);
            }
        }
    }

    public function delete_avatar()
    {
        $art = new Art($this->id, 'user');
        $art->reset();
    }

    /**
     * activate_user
     * the user from public_registration
     */
    public function activate_user($username)
    {
        $username = Dba::escape($username);

        $sql = "UPDATE `user` SET `disabled`='0' WHERE `username` = ?";
        Dba::write($sql, array($username));
    } // activate_user

    /**
     * get_artists
     * Get artists associated with the user
     */
    public function get_artists()
    {
        $sql        = "SELECT `id` FROM `artist` WHERE `user` = ?";
        $db_results = Dba::read($sql, array($this->id));

        $results = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        return $results;
    }

    /**
     * is_xmlrpc
     * checks to see if this is a valid xmlrpc user
     */
    public function is_xmlrpc()
    {
        /* If we aren't using XML-RPC return true */
        if (!AmpConfig::get('xml_rpc')) {
            return false;
        }

        //FIXME: Ok really what we will do is check the MD5 of the HTTP_REFERER
        //FIXME: combined with the song title to make sure that the REFERER
        //FIXME: is in the access list with full rights
        return true;
    } // is_xmlrpc

    /**
     * get_followers
     * Get users following this user
     * @return int[]
     */
    public function get_followers()
    {
        $sql        = "SELECT `user` FROM `user_follower` WHERE `follow_user` = ?";
        $db_results = Dba::read($sql, array($this->id));
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['user'];
        }

        return $results;
    }

    /**
     * get_following
     * Get users followed by this user
     * @return int[]
     */
    public function get_following()
    {
        $sql        = "SELECT `follow_user` FROM `user_follower` WHERE `user` = ?";
        $db_results = Dba::read($sql, array($this->id));
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['follow_user'];
        }

        return $results;
    }

    /**
     * is_followed_by
     * Get if an user is followed by this user
     * @param integer $user_id
     * @return boolean
     */
    public function is_followed_by($user_id)
    {
        $sql        = "SELECT `id` FROM `user_follower` WHERE `user` = ? AND `follow_user` = ?";
        $db_results = Dba::read($sql, array($user_id, $this->id));

        return (Dba::num_rows($db_results) > 0);
    }

    /**
     * is_following
     * Get if this user is following an user
     * @param integer $user_id
     * @return boolean
     */
    public function is_following($user_id)
    {
        $sql        = "SELECT `id` FROM `user_follower` WHERE `user` = ? AND `follow_user` = ?";
        $db_results = Dba::read($sql, array($this->id, $user_id));

        return (Dba::num_rows($db_results) > 0);
    }

    /**
     * toggle_follow
     * @param integer $user_id
     * @return boolean
     */
    public function toggle_follow($user_id)
    {
        if (!$user_id || $user_id === $this->id) {
            return false;
        }

        $params = array($this->id, $user_id);
        if ($this->is_following($user_id)) {
            $sql = "DELETE FROM `user_follower` WHERE `user` = ? AND `follow_user` = ?";
        } else {
            $sql      = "INSERT INTO `user_follower` (`user`, `follow_user`, `follow_date`) VALUES (?, ?, ?)";
            $params[] = time();
            
            Useractivity::post_activity($this->id, 'follow', 'user', $user_id);
        }

        return Dba::write($sql, $params);
    }

    /**
     * get_display_follow
     * Get html code to display the follow/unfollow link
     * @param int|null $display_user_id
     * @return string
     */
    public function get_display_follow($user_id = null)
    {
        if (!$user_id) {
            $user_id = $GLOBALS['user']->id;
        }

        if ($user_id === $this->id) {
            return "";
        }

        $followed = $this->is_followed_by($user_id);

        $html = "<span id='button_follow_" . $this->id . "' class='followbtn'>";
        $html .= Ajax::text('?page=user&action=flip_follow&user_id=' . $this->id, ($followed ? T_('Unfollow') : T_('Follow')) . ' (' . count($this->get_followers()) . ')', 'flip_follow_' . $this->id);
        $html .= "</span>";

        return $html;
    }

    /**
     * check_username
     * This checks to make sure the username passed doesn't already
     * exist in this instance of ampache
     */
    public static function check_username($username)
    {
        $username = Dba::escape($username);

        $sql        = "SELECT `id` FROM `user` WHERE `username`='$username'";
        $db_results = Dba::read($sql);

        if (Dba::num_rows($db_results)) {
            return false;
        }

        return true;
    } // check_username

    /**
     * rebuild_all_preferences
     * This rebuilds the user preferences for all installed users, called by the plugin functions
     */
    public static function rebuild_all_preferences()
    {
        // Clean out any preferences garbage left over
        $sql = "DELETE `user_preference`.* FROM `user_preference` " .
            "LEFT JOIN `user` ON `user_preference`.`user` = `user`.`id` " .
            "WHERE `user_preference`.`user` != -1 AND `user`.`id` IS NULL";
        Dba::write($sql);

        // Get only users who has less preferences than excepted
        // otherwise it would have significant performance issue with large user database
        $sql = "SELECT `user` FROM `user_preference` " .
            "GROUP BY `user` HAVING COUNT(*) < (" .
            "SELECT COUNT(`id`) FROM `preference` WHERE `catagory` != 'system')";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            User::fix_preferences($row['user']);
        }

        return true;
    } // rebuild_all_preferences

    /**
     * stream_control
     * Check all stream control plugins
     * @param array $media_ids
     * @param User $user
     * @return boolean
     */
    public static function stream_control($media_ids, User $user = null)
    {
        if ($user == null) {
            $user = $GLOBALS['user'];
        }

        foreach (Plugin::get_plugins('stream_control') as $plugin_name) {
            $plugin = new Plugin($plugin_name);
            if ($plugin->load($user)) {
                if (!$plugin->_plugin->stream_control($media_ids)) {
                    return false;
                }
            }
        }

        return true;
    }
} //end user class
