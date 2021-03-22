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
 * User Class
 *
 * This class handles all of the user related functions including the creation
 * and deletion of the user objects from the database by default you construct it
 * with a user_id from user.id
 *
 */
class User extends database_object
{
    // Basic Components
    /**
     * @var integer $id
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
     * @var integer $access
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
     * @var integer $last_seen
     */
    public $last_seen;
    /**
     * @var integer $create_date
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
    /**
     * @var string $rsstoken
     */
    public $rsstoken;

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
     * @var string $f_usage
     */
    public $f_usage;
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
     * @param integer $user_id
     */
    public function __construct($user_id = 0)
    {
        if (!$user_id) {
            return false;
        }

        $this->id = (int) ($user_id);

        $info = $this->has_info();

        foreach ($info as $key => $value) {
            // Let's not save the password in this object :S
            if ($key == 'password') {
                continue;
            }
            $this->$key = $value;
        }

        // Make sure the Full name is always filled
        if (strlen((string) $this->fullname) < 1) {
            $this->fullname = $this->username;
        }

        return true;
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
        $sql       = "SELECT COUNT(DISTINCT `session`.`username`) FROM `session` " .
            "INNER JOIN `user` ON `session`.`username` = `user`.`username` " .
            "WHERE `session`.`expire` > ? AND `user`.`last_seen` > ?";
        $db_results           = Dba::read($sql, array($time, $last_seen));
        $data                 = Dba::fetch_row($db_results);
        $results['connected'] = $data[0];

        return $results;
    }

    /**
     * has_info
     * This function returns the information for this object
     * @return array
     */
    private function has_info()
    {
        $user_id = (int) ($this->id);

        if (User::is_cached('user', $user_id)) {
            return User::get_from_cache('user', $user_id);
        }

        $data = array();
        // If the ID is -1 then
        if ($user_id == '-1') {
            $data['username'] = 'System';
            $data['fullname'] = 'Ampache User';
            $data['access']   = '25';

            return $data;
        }

        $sql        = "SELECT * FROM `user` WHERE `id`='$user_id'";
        $db_results = Dba::read($sql);

        $data = Dba::fetch_assoc($db_results);

        User::add_to_cache('user', $user_id, $data);

        return $data;
    } // has_info

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
     * @param boolean $include_disabled
     * @return array
     */
    public static function get_valid_users($include_disabled = false)
    {
        $users = array();
        $sql   = ($include_disabled)
            ? "SELECT `id` FROM `user`"
            : "SELECT `id` FROM `user` WHERE `disabled` = '0'";

        $db_results = Dba::read($sql);
        while ($results = Dba::fetch_assoc($db_results)) {
            $users[] = (int) $results['id'];
        }

        return $users;
    } // get_valid_users

    /**
     * get_valid_usernames
     * This returns all valid users in database.
     * @param boolean $include_disabled
     * @return array
     */
    public static function get_valid_usernames($include_disabled = false)
    {
        $users = array();
        $sql   = ($include_disabled)
            ? "SELECT `username` FROM `user`"
            : "SELECT `username` FROM `user` WHERE `disabled` = '0'";

        $db_results = Dba::read($sql);
        while ($results = Dba::fetch_assoc($db_results)) {
            $users[] = $results['username'];
        }

        return $users;
    } // get_valid_users

    /**
     * get_from_username
     * This returns a built user from a username. This is a
     * static function so it doesn't require an instance
     * @param string $username
     * @return User $user
     */
    public static function get_from_username($username)
    {
        $sql        = "SELECT `id` FROM `user` WHERE `username` = ? OR `fullname` = ?";
        $db_results = Dba::read($sql, array($username, $username));
        $results    = Dba::fetch_assoc($db_results);

        return new User($results['id']);
    } // get_from_username

    /**
     * get_from_apikey
     * This returns a built user from an apikey. This is a
     * static function so it doesn't require an instance
     * @param $apikey
     * @return User|null
     */
    public static function get_from_apikey($apikey)
    {
        $apikey    = trim((string) $apikey);
        if (!empty($apikey)) {
            // check for legacy unencrypted apikey
            $sql        = "SELECT `id` FROM `user` WHERE `apikey` = ?";
            $db_results = Dba::read($sql, array($apikey));
            $results    = Dba::fetch_assoc($db_results);

            if ($results['id']) {
                return new User($results['id']);
            }
            // check for api sessions
            $sql        = "SELECT `username` FROM `session` WHERE `id` = ? AND `expire` > ? AND type = 'api'";
            $db_results = Dba::read($sql, array($apikey, time()));
            $results    = Dba::fetch_assoc($db_results);

            if ($results['username']) {
                return User::get_from_username($results['username']);
            }
            // check for sha256 hashed apikey for client
            // http://ampache.org/api/
            $sql        = "SELECT `id`, `apikey`, `username` FROM `user`";
            $db_results = Dba::read($sql);
            while ($row = Dba::fetch_assoc($db_results)) {
                if ($row['apikey'] && $row['username']) {
                    $key        = hash('sha256', $row['apikey']);
                    $passphrase = hash('sha256', $row['username'] . $key);
                    if ($passphrase == $apikey) {
                        return new User($row['id']);
                    }
                }
            }
        }

        return null;
    } // get_from_apikey

    /**
     * get_from_email
     * This returns a built user from a email. This is a
     * static function so it doesn't require an instance
     * @param $email
     * @return User|null
     */
    public static function get_from_email($email)
    {
        $user_id    = null;
        $sql        = "SELECT `id` FROM `user` WHERE `email` = ?";
        $db_results = Dba::read($sql, array($email));
        if ($results = Dba::fetch_assoc($db_results)) {
            $user_id = new User($results['id']);
        }

        return $user_id;
    } // get_from_email

    /**
     * get_from_website
     * This returns users list related to a website.
     * @param $website
     * @return array
     */
    public static function get_from_website($website)
    {
        $website    = rtrim((string) $website, "/");
        $sql        = "SELECT `id` FROM `user` WHERE `website` = ? LIMIT 1";
        $db_results = Dba::read($sql, array($website));
        $users      = array();
        while ($results = Dba::fetch_assoc($db_results)) {
            $users[] = $results['id'];
        }

        return $users;
    } // get_from_website

    /**
     * get_from_rsstoken
     * This returns a built user from a rsstoken. This is a
     * static function so it doesn't require an instance
     * @param $rsstoken
     * @return User|null
     */
    public static function get_from_rsstoken($rsstoken)
    {
        $user_id    = null;
        $sql        = "SELECT `id` FROM `user` WHERE `rsstoken` = ?";
        $db_results = Dba::read($sql, array($rsstoken));
        if ($results = Dba::fetch_assoc($db_results)) {
            $user_id = new User($results['id']);
        }

        return $user_id;
    } // get_from_rsstoken

    /**
     * get_catalogs
     * This returns the catalogs as an array of ids that this user is allowed to access
     * @return integer[]
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
            $catalogs[] = (int) $row['catalog'];
        }

        parent::add_to_cache('user_catalog', $this->id, $catalogs);

        return $catalogs;
    } // get_catalogs

    /**
     * get_preferences
     * This is a little more complicate now that we've got many types of preferences
     * This function pulls all of them an arranges them into a spiffy little array
     * You can specify a type to limit it to a single type of preference
     * []['title'] = ucased type name
     * []['prefs'] = array(array('name', 'display', 'value'));
     * []['admin'] = t/f value if this is an admin only section
     * @param integer $type
     * @param boolean $system
     * @return array
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

        $sql = "SELECT `preference`.`name`, `preference`.`description`, `preference`.`catagory`, `preference`.`subcatagory`, preference.level, user_preference.value " .
            "FROM `preference` INNER JOIN `user_preference` ON `user_preference`.`preference` = `preference`.`id` " .
            "WHERE `user_preference`.`user` = '$user_id' " . $user_limit .
            " ORDER BY `preference`.`catagory`, `preference`.`subcatagory`, `preference`.`description`";

        $db_results = Dba::read($sql);
        $results    = array();
        $type_array = array();
        /* Ok this is crappy, need to clean this up or improve the code FIXME */
        while ($row = Dba::fetch_assoc($db_results)) {
            $type  = $row['catagory'];
            $admin = false;
            if ($type == 'system') {
                $admin = true;
            }
            $type_array[$type][$row['name']] = array('name' => $row['name'], 'level' => $row['level'], 'description' => $row['description'], 'value' => $row['value'], 'subcategory' => $row['subcatagory']);
            $results[$type]                  = array('title' => ucwords((string) $type), 'admin' => $admin, 'prefs' => $type_array[$type]);
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

        $sql = "SELECT `preference`.`name`, `user_preference`.`value` " .
                " FROM `preference`, `user_preference` WHERE `user_preference`.`user` = '$user_id' " .
            "AND `user_preference`.`preference` = `preference`.`id` AND `preference`.`type` != 'system'";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $key               = $row['name'];
            $this->prefs[$key] = $row['value'];
        }
    } // set_preferences

    /**
     * get_favorites
     * returns an array of your $type favorites
     * @param string $type
     * @return array
     */
    public function get_favorites($type)
    {
        $count   = AmpConfig::get('popular_threshold', 10);
        $results = Stats::get_user($count, $type, $this->id, 1);

        $items = array();

        foreach ($results as $row) {
            // If its a song
            if ($type == 'song') {
                $data        = new Song($row['object_id']);
                $data->count = $row['count'];
                $data->format();
                $data->f_link;
                $items[] = $data;
            } elseif ($type == 'album') {
                // If its an album
                $data = new Album($row['object_id']);
                $data->format();
                $items[] = $data;
            } elseif ($type == 'artist') {
                // If its an artist
                $data = new Artist($row['object_id']);
                $data->format();
                $data->f_name = $data->f_link;
                $items[]      = $data;
            } elseif (($type == 'genre' || $type == 'tag')) {
                // If it's a genre
                $data    = new Tag($row['object_id']);
                $items[] = $data;
            }
        } // end foreach

        return $items;
    } // get_favorites

    /**
     * get_recommendations
     * This returns recommended objects of $type. The recommendations
     * are based on voodoo economics,the phase of the moon and my current BAL.
     * @param $type
     * @return array
     */
    public function get_recommendations($type)
    {
        /* First pull all of your ratings of this type */
        $sql = "SELECT `object_id`, `user_rating` FROM `ratings` " .
            "WHERE `object_type` = '" . Dba::escape($type) . "' AND `user` = '" . Dba::escape($this->id) . "'";
        $db_results = Dba::read($sql);

        // Incase they only have one user
        $users   = array();
        $ratings = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            /* Store the fact that you rated this */
            $key           = $row['object_id'];
            $ratings[$key] = true;

            /* Build a key'd array of users with this same rating */
            $sql = "SELECT `user` FROM `ratings` WHERE `object_type` = '" . Dba::escape($type) . "' " .
                "AND `user` !='" . Dba::escape($this->id) . "' AND `object_id` = '" . Dba::escape($row['object_id']) . "' " .
                "AND `user_rating` ='" . Dba::escape($row['user_rating']) . "'";
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
            $sql = "SELECT `object_id`, `user_rating` FROM `ratings` " .
                "WHERE `user` = '" . Dba::escape($user_id) . "' AND `user_rating` >='4' AND " .
                "`object_type` = '" . Dba::escape($type) . "' ORDER BY `user_rating` DESC";
            $db_results = Dba::read($sql);

            while ($row = Dba::fetch_assoc($db_results)) {
                $key = $row['object_id'];
                if (isset($ratings[$key])) {
                    continue;
                }

                /* Let's only get 5 total for now */
                if (count($recommendations) > 5) {
                    return $recommendations;
                }

                $recommendations[$key] = $row['user_rating'];
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

        $sql = "SELECT `id`, `ip` FROM `session` WHERE `username`='$username'" .
            " AND `expire` > " . time();
        $db_results = Dba::read($sql);

        if ($row = Dba::fetch_assoc($db_results)) {
            return $row['ip'] ? $row['ip'] : null;
        }

        return false;
    } // is_logged_in

    /**
     * has_access
     * this function checks to see if this user has access
     * to the passed action (pass a level requirement)
     * @param integer $needed_level
     * @return boolean
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
        if (!Core::get_global('user')->id) {
            return false;
        }

        if (!AmpConfig::get('use_auth') && Core::get_global('user')->access < 5) {
            return false;
        }

        return true;
    }

    /**
     * update
     * This function is an all encompassing update function that
     * calls the mini ones does all the error checking and all that
     * good stuff
     * @param array $data
     * @return boolean|int
     */
    public function update(array $data)
    {
        if (empty($data['username'])) {
            AmpError::add('username', T_('Username is required'));
        }

        if ($data['password1'] != $data['password2'] && !empty($data['password1'])) {
            AmpError::add('password', T_("Passwords do not match"));
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
                    break;
            }
        }

        return $this->id;
    }

    /**
     * update_username
     * updates their username
     * @param $new_username
     */
    public function update_username($new_username)
    {
        $sql            = "UPDATE `user` SET `username` = ? WHERE `id` = ?";
        $this->username = $new_username;

        debug_event(self::class, 'Updating username', 4);

        Dba::write($sql, array($new_username, $this->id));
    } // update_username

    /**
     * update_validation
     * This is used by the registration mumbojumbo
     * Use this function to update the validation key
     * NOTE: crap this doesn't have update_item the humanity of it all
     * @param $new_validation
     * @return PDOStatement|boolean
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
     * @param $new_fullname
     */
    public function update_fullname($new_fullname)
    {
        $sql = "UPDATE `user` SET `fullname` = ? WHERE `id` = ?";

        debug_event(self::class, 'Updating fullname', 4);

        Dba::write($sql, array($new_fullname, $this->id));
    } // update_fullname

    /**
     * update_fullname_public
     * updates their fullname public
     * @param $new_fullname_public
     */
    public function update_fullname_public($new_fullname_public)
    {
        $sql = "UPDATE `user` SET `fullname_public` = ? WHERE `id` = ?";

        debug_event(self::class, 'Updating fullname public', 4);

        Dba::write($sql, array($new_fullname_public ? '1' : '0', $this->id));
    } // update_fullname_public

    /**
     * update_email
     * updates their email address
     * @param string $new_email
     */
    public function update_email($new_email)
    {
        $sql = "UPDATE `user` SET `email` = ? WHERE `id` = ?";

        debug_event(self::class, 'Updating email', 4);

        Dba::write($sql, array($new_email, $this->id));
    } // update_email

    /**
     * update_website
     * updates their website address
     * @param $new_website
     */
    public function update_website($new_website)
    {
        $new_website = rtrim((string) $new_website, "/");
        $sql         = "UPDATE `user` SET `website` = ? WHERE `id` = ?";

        debug_event(self::class, 'Updating website', 4);

        Dba::write($sql, array($new_website, $this->id));
    } // update_website

    /**
     * update_state
     * updates their state
     * @param $new_state
     */
    public function update_state($new_state)
    {
        $sql = "UPDATE `user` SET `state` = ? WHERE `id` = ?";

        debug_event(self::class, 'Updating state', 4);

        Dba::write($sql, array($new_state, $this->id));
    } // update_state

    /**
     * update_city
     * updates their city
     * @param $new_city
     */
    public function update_city($new_city)
    {
        $sql = "UPDATE `user` SET `city` = ? WHERE `id` = ?";

        debug_event(self::class, 'Updating city', 4);

        Dba::write($sql, array($new_city, $this->id));
    } // update_city

    /**
     * update_apikey
     * Updates their api key
     * @param string $new_apikey
     */
    public function update_apikey($new_apikey)
    {
        $sql = "UPDATE `user` SET `apikey` = ? WHERE `id` = ?";

        debug_event(self::class, 'Updating apikey for ' . $this->id, 4);

        Dba::write($sql, array($new_apikey, $this->id));
    } // update_apikey

    /**
     * update_rsstoken
     * Updates their RSS token
     * @param string $new_rsstoken
     */
    public function update_rsstoken($new_rsstoken)
    {
        $sql = "UPDATE `user` SET `rsstoken` = ? WHERE `id` = ?";

        debug_event(self::class, 'Updating rsstoken for ' . $this->id, 4);

        Dba::write($sql, array($new_rsstoken, $this->id));
    } // update_rsstoken

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
     * generate_rsstoken
     * Generate a new user RSS token
     */
    public function generate_rsstoken()
    {
        try {
            $rsstoken = bin2hex(random_bytes(32));
            $this->update_rsstoken($rsstoken);
        } catch (Exception $error) {
            debug_event(self::class, 'Could not generate random_bytes: ' . $error, 3);
        }
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
     * @param $new_access
     * @return boolean
     */
    public function update_access($new_access)
    {
        /* Prevent Only User accounts */
        if ($new_access < '100') {
            $sql        = "SELECT `id` FROM `user` WHERE `access`='100' AND `id` != '$this->id'";
            $db_results = Dba::read($sql);
            if (!Dba::num_rows($db_results)) {
                return false;
            }
        }

        $new_access = Dba::escape($new_access);
        $sql        = "UPDATE `user` SET `access`='$new_access' WHERE `id`='$this->id'";

        debug_event(self::class, 'Updating access level for ' . $this->id, 4);

        Dba::write($sql);

        return true;
    } // update_access

    /**
     * update_last_seen
     * updates the last seen data for this user
     */
    public function update_last_seen()
    {
        $sql = "UPDATE user SET last_seen='" . time() . "' WHERE `id`='$this->id'";
        Dba::write($sql);
    } // update_last_seen

    /**
     * save_mediaplay
     * @param User $user
     * @param Song $media
     */
    public static function save_mediaplay($user, $media)
    {
        foreach (Plugin::get_plugins('save_mediaplay') as $plugin_name) {
            try {
                $plugin = new Plugin($plugin_name);
                if ($plugin->load($user)) {
                    debug_event(self::class, 'save_mediaplay... ' . $plugin->_plugin->name, 5);
                    $plugin->_plugin->save_mediaplay($media);
                }
            } catch (Exception $error) {
                debug_event(self::class, 'save_mediaplay plugin error: ' . $error->getMessage(), 1);
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
        if (filter_has_var(INPUT_SERVER, 'HTTP_X_FORWARDED_FOR')) {
            $sip = filter_var(Core::get_server('HTTP_X_FORWARDED_FOR'), FILTER_VALIDATE_IP);
            debug_event(self::class, 'Login from IP address: ' . (string) $sip, 3);
        } else {
            $sip = filter_var(Core::get_server('REMOTE_ADDR'), FILTER_VALIDATE_IP);
            debug_event(self::class, 'Login from IP address: ' . (string) $sip, 3);
        }

        // Remove port information if any
        if (!empty($sip)) {
            // Use parse_url to support easily ipv6
            if (filter_var($sip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === true) {
                $sipar = parse_url("http://" . $sip);
            } else {
                $sipar = parse_url("http://[" . $sip . "]");
            }
            $sip   = $sipar['host'];
        }

        $uip     = (!empty($sip)) ? Dba::escape(inet_pton(trim((string) $sip, "[]"))) : '';
        $date    = time();
        $user_id = (int) $this->id;
        $agent   = Dba::escape(Core::get_server('HTTP_USER_AGENT'));

        $sql = "INSERT INTO `ip_history` (`ip`, `user`, `date`, `agent`) VALUES ('$uip', '$user_id', '$date', '$agent')";
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
     * inserts a new user into Ampache
     * @param string $username
     * @param string $fullname
     * @param string $email
     * @param string $website
     * @param string $password
     * @param integer $access
     * @param string $state
     * @param string $city
     * @param boolean $disabled
     * @param boolean $encrypted
     * @return integer
     */
    public static function create($username, $fullname, $email, $website, $password, $access, $state = '', $city = '', $disabled = false, $encrypted = false)
    {
        $website = rtrim((string) $website, "/");
        if (!$encrypted) {
            $password = hash('sha256', $password);
        }
        $disabled = $disabled ? 1 : 0;

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
            return null;
        }

        // Get the insert_id
        $insert_id = (int) Dba::insert_id();

        // Populates any missing preferences, in this case all of them
        self::fix_preferences($insert_id);

        return (int) $insert_id;
    } // create

    /**
     * update_password
     * updates a users password
     * @param string $new_password
     * @param string $hashed_password
     */
    public function update_password($new_password, $hashed_password = null)
    {
        debug_event(self::class, 'Updating password', 1);
        if (!$hashed_password) {
            $hashed_password = hash('sha256', $new_password);
        }

        $escaped_password = Dba::escape($hashed_password);
        $sql              = "UPDATE `user` SET `password` = ? WHERE `id` = ?";
        $db_results       = Dba::write($sql, array($escaped_password, $this->id));

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
     * @param boolean $details
     */
    public function format($details = true)
    {
        if (!$this->id) {
            return;
        }
        $time_format = AmpConfig::get('custom_datetime') ? (string) AmpConfig::get('custom_datetime') : 'm/d/Y H:i';
        /* If they have a last seen date */
        if (!$this->last_seen) {
            $this->f_last_seen = T_('Never');
        } else {
            $this->f_last_seen = get_datetime($time_format, (int) $this->last_seen);
        }

        /* If they have a create date */
        if (!$this->create_date) {
            $this->f_create_date = T_('Unknown');
        } else {
            $this->f_create_date = get_datetime($time_format, (int) $this->create_date);
        }

        $this->f_name = ($this->fullname_public ? $this->fullname : $this->username);

        // Base link
        $this->link   = AmpConfig::get('web_path') . '/stats.php?action=show_user&user_id=' . $this->id;
        $this->f_link = '<a href="' . $this->link . '">' . $this->f_name . '</a>';

        if ($details) {
            /* Calculate their total Bandwidth Usage */
            $sql = "SELECT sum(`song`.`size`) as size FROM `song` LEFT JOIN `object_count` ON `song`.`id`=`object_count`.`object_id` " .
                "WHERE `object_count`.`user`=" . $this->id . " AND `object_count`.`object_type`='song'";
            $db_results = Dba::read($sql);

            $result = Dba::fetch_assoc($db_results);
            $total  = $result['size'];

            $this->f_usage = UI::format_bytes($total);

            /* Get Users Last ip */
            if (count($data = $this->get_ip_history())) {
                $user_ip          = inet_ntop($data['0']['ip']);
                $this->ip_history = (!empty($user_ip) && filter_var($user_ip, FILTER_VALIDATE_IP)) ? $user_ip : T_('Invalid');
            } else {
                $this->ip_history = T_('Not Enough Data');
            }
        }

        $avatar = $this->get_avatar();
        if (!empty($avatar['url'])) {
            $this->f_avatar = '<img src="' . $avatar['url'] . '" title="' . $avatar['title'] . '"' . ' width="256px" height="auto" />';
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
     * @param string $name
     * @return integer
     */
    public static function access_name_to_level($name)
    {
        switch ($name) {
            case 'admin':
                return 100;
            case 'user':
                return 25;
            case 'manager':
                return 75;
            // FIXME why is content manager not here?
            //case 'manager':
            //    return 50;
            case 'guest':
                return 5;
            default:
                return 0;
        }
    } // access_name_to_level

    /**
     * access_level_to_name
     * This takes the access level for the user and returns the translated name for that level
     * @param string $level
     * @return string
     */
    public static function access_level_to_name($level)
    {
        switch ($level) {
            case '100':
                return T_('Admin');
            case '75':
                return T_('Catalog Manager');
            case '50':
                return T_('Content Manager');
            case '25':
                return T_('User');
            case '5':
                return T_('Guest');
            default:
                return T_('Unknown');
        }
    } // access_level_to_name

    /**
     * fix_preferences
     * This is the new fix_preferences function, it does the following
     * Remove Duplicates from user, add in missing
     * If -1 is passed it also removes duplicates from the `preferences`
     * table.
     * @param integer $user_id
     */
    public static function fix_preferences($user_id)
    {
        $user_id = Dba::escape($user_id);

        // Delete that system pref that's not a user pref...
        if ($user_id > 0) {
            // TODO, remove before next release. ('custom_login_logo' needs to be here a while at least so 5.0.0+1)
            $sql = "DELETE FROM `user_preference` WHERE `preference` IN (SELECT `id` from `preference` where `name` IN ('custom_login_background', 'custom_login_logo')) AND `user` = $user_id";
            Dba::write($sql);
        }

        /* Get All Preferences for the current user */
        $sql        = "SELECT * FROM `user_preference` WHERE `user`='$user_id'";
        $db_results = Dba::read($sql);

        $results      = array();
        $zero_results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $pref_id = $row['preference'];
            /* Check for duplicates */
            if (isset($results[$pref_id])) {
                $row['value'] = Dba::escape($row['value']);
                $sql          = "DELETE FROM `user_preference` WHERE `user`='$user_id' AND `preference`='" . $row['preference'] . "' AND" .
                    " `value`='" . Dba::escape($row['value']) . "'";
                Dba::write($sql);
            } // if its set
            else {
                $results[$pref_id] = 1;
            }
        } // end while

        /* If we aren't the -1 user before we continue grab the -1 users values */
        if ($user_id != '-1') {
            $sql = "SELECT `user_preference`.`preference`, `user_preference`.`value` FROM `user_preference`, `preference` " .
                "WHERE `user_preference`.`preference` = `preference`.`id` AND `user_preference`.`user`='-1' AND " .
                "`preference`.`catagory` !='system' AND `preference`.`name` NOT IN ('custom_login_background', 'custom_login_logo') ";
            $db_results = Dba::read($sql);
            /* While through our base stuff */
            while ($row = Dba::fetch_assoc($db_results)) {
                $key                = $row['preference'];
                $zero_results[$key] = $row['value'];
            }
        } // if not user -1

        // get me _EVERYTHING_
        $sql = "SELECT * FROM `preference`";

        // If not system, exclude system... *gasp*
        if ($user_id != '-1') {
            $sql .= " WHERE catagory !='system'";
            $sql .= " AND `preference`.`name` NOT IN ('custom_login_background', 'custom_login_logo')";
        }
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $key = $row['id'];

            /* Check if this preference is set */
            if (!isset($results[$key])) {
                if (isset($zero_results[$key])) {
                    $row['value'] = $zero_results[$key];
                }
                $value = Dba::escape($row['value']);
                $sql   = "INSERT INTO user_preference (`user`, `preference`, `value`) VALUES ('$user_id', '$key', '$value')";
                Dba::write($sql);
            }
        } // while preferences
    } // fix_preferences

    /**
     * delete
     * deletes this user and everything associated with it. This will affect
     * ratings and total stats
     * @return boolean
     */
    public function delete()
    {
        // Before we do anything make sure that they aren't the last admin
        if ($this->has_access(100)) {
            $sql        = "SELECT `id` FROM `user` WHERE `access`='100' AND id != ?";
            $db_results = Dba::read($sql, array($this->id));
            if (!Dba::num_rows($db_results)) {
                return false;
            }
        } // if this is an admin check for others

        // simple deletion queries.
        $user_tables = array('playlist', 'object_count', 'ip_history',
            'access_list', 'rating', 'tag_map',
            'user_preference', 'user_vote');
        foreach ($user_tables as $table_id) {
            $sql = "DELETE FROM `" . $table_id . "` WHERE `user` = ?";
            Dba::write($sql, array($this->id));
        }
        // Clean up the playlist data table
        $sql = "DELETE FROM `playlist_data` USING `playlist_data` " .
            "LEFT JOIN `playlist` ON `playlist`.`id`=`playlist_data`.`playlist` " .
            "WHERE `playlist`.`id` IS NULL";
        Dba::write($sql);

        // Clean out the tags
        $sql = "DELETE FROM `tag` WHERE `tag`.`id` NOT IN (SELECT `tag_id` FROM `tag_map`)";
        Dba::write($sql);

        // Delete their following/followers
        $sql = "DELETE FROM `user_follower` WHERE `user` = ? OR `follow_user` = ?";
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
     * @param integer $delay
     * @return boolean
     */
    public function is_online($delay = 1200)
    {
        return time() - $this->last_seen <= $delay;
    } // is_online

    /**
     * get_user_validation
     * if user exists before activation can be done.
     * @param string $username
     * @return mixed
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
     * the limit passed. ger recent by default or oldest if $newest is false.
     * @param string $type
     * @param string $count
     * @param integer $offset
     * @param boolean $newest
     * @return integer[]
     */
    public function get_recently_played($type, $count, $offset = 0, $newest = true)
    {
        $limit    = ($offset < 1) ? $count : $offset . "," . $count;
        $ordersql = ($newest === true) ? 'DESC' : 'ASC';

        $sql = "SELECT `object_id`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_type` = ? AND `user` = ? " .
            "GROUP BY `object_id` ORDER BY `date` " . $ordersql .
            " LIMIT " . $limit . " ";
        $db_results = Dba::read($sql, array($type, $this->id));

        $results = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['object_id'];
        }

        return $results;
    } // get_recently_played

    /**
     * get_ip_history
     * This returns the ip_history from the last AmpConfig::get('user_ip_cardinality') days
     * @param integer $count
     * @param boolean $distinct
     * @return array
     */
    public function get_ip_history($count = 1, $distinct = false)
    {
        $username  = Dba::escape($this->id);
        $count     = $count ? (int) ($count) : (int) (AmpConfig::get('user_ip_cardinality'));
        $limit_sql = ($count > 0) ? " LIMIT " . (string) ($count) : '';

        $group_sql = "";
        if ($distinct) {
            $group_sql = "GROUP BY `ip`, `date`";
        }

        /* Select ip history */
        $sql = "SELECT `ip`, `date` FROM `ip_history` " .
            "WHERE `user`='$username' " .
            "$group_sql ORDER BY `date` DESC$limit_sql";
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
     * @param boolean $local
     * @param array $session
     * @return array
     */
    public function get_avatar($local = false, $session = array())
    {
        $avatar = array();
        $auth   = '';
        if ($session['t'] && $session['s']) {
            $auth = '&t=' . $session['t'] . '&s=' . $session['s'];
        } elseif ($session['auth']) {
            $auth = '&auth=' . $session['auth'];
        }

        $avatar['title'] = T_('User avatar');
        $upavatar        = new Art($this->id, 'user');
        if ($upavatar->has_db_info()) {
            $avatar['url']        = ($local ? AmpConfig::get('local_web_path') : AmpConfig::get('web_path')) . '/image.php?object_type=user&object_id=' . $this->id . $auth;
            $avatar['url_mini']   = $avatar['url'];
            $avatar['url_medium'] = $avatar['url'];
            $avatar['url'] .= '&thumb=4';
            $avatar['url_mini'] .= '&thumb=5';
            $avatar['url_medium'] .= '&thumb=3';
        } else {
            foreach (Plugin::get_plugins('get_avatar_url') as $plugin_name) {
                $plugin = new Plugin($plugin_name);
                if ($plugin->load(Core::get_global('user'))) {
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

        if ($avatar['url'] === null) {
            $avatar['url']        = ($local ? AmpConfig::get('local_web_path') : AmpConfig::get('web_path')) . '/images/blankuser.png';
            $avatar['url_mini']   = $avatar['url'];
            $avatar['url_medium'] = $avatar['url'];
        }

        return $avatar;
    } // get_avatar

    /**
     * @param string $data
     * @param string $mime
     * @return boolean
     */
    public function update_avatar($data, $mime = '')
    {
        debug_event(self::class, 'Updating avatar for ' . $this->id, 4);

        $art = new Art($this->id, 'user');

        return $art->insert($data, $mime);
    }

    /**
     *
     * @return boolean
     */
    public function upload_avatar()
    {
        $upload = array();
        if (!empty($_FILES['avatar']['tmp_name']) && $_FILES['avatar']['size'] <= AmpConfig::get('max_upload_size')) {
            $path_info      = pathinfo($_FILES['avatar']['name']);
            $upload['file'] = $_FILES['avatar']['tmp_name'];
            $upload['mime'] = 'image/' . $path_info['extension'];
            $image_data     = Art::get_from_source($upload, 'user');

            if ($image_data !== '') {
                return $this->update_avatar($image_data, $upload['mime']);
            }
        }

        return true; // only worry about failed uploads
    }

    public function delete_avatar()
    {
        $art = new Art($this->id, 'user');
        $art->reset();
    }

    /**
     * activate_user
     * the user from public_registration
     * @param string $username
     */
    public static function activate_user($username)
    {
        $username = Dba::escape($username);

        $sql = "UPDATE `user` SET `disabled`='0' WHERE `username` = ?";
        Dba::write($sql, array($username));
    } // activate_user

    /**
     * get_artists
     * Get artists associated with the user
     * @return array
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
     * @return boolean
     */
    public function is_xmlrpc()
    {
        /* If we aren't using XML-RPC return true */
        if (!AmpConfig::get('xml_rpc')) {
            return false;
        }

        // FIXME: Ok really what we will do is check the MD5 of the HTTP_REFERER
        // FIXME: combined with the song title to make sure that the REFERER
        // FIXME: is in the access list with full rights
        return true;
    } // is_xmlrpc

    /**
     * get_followers
     * Get users following this user
     * @return integer[]
     */
    public function get_followers()
    {
        $sql        = "SELECT `user` FROM `user_follower` WHERE `follow_user` = ?";
        $db_results = Dba::read($sql, array($this->id));
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['user'];
        }

        return $results;
    }

    /**
     * get_following
     * Get users followed by this user
     * @return integer[]
     */
    public function get_following()
    {
        $sql        = "SELECT `follow_user` FROM `user_follower` WHERE `user` = ?";
        $db_results = Dba::read($sql, array($this->id));
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['follow_user'];
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
     * @return PDOStatement|boolean
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

            Useractivity::post_activity($this->id, 'follow', 'user', $user_id, time());
        }

        return Dba::write($sql, $params);
    }

    /**
     * get_display_follow
     * Get html code to display the follow/unfollow link
     * @param integer $user_id
     * @return string
     */
    public function get_display_follow($user_id = null)
    {
        if ($user_id === null) {
            $user_id = Core::get_global('user')->id;
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
     * exist in this instance of Ampache
     *
     * @param string $username
     * @return boolean
     */
    public static function check_username($username)
    {
        $user = Dba::escape($username);

        $sql        = "SELECT `id` FROM `user` WHERE `username`='$user'";
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
            self::fix_preferences($row['user']);
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
        if ($user === null) {
            $user = Core::get_global('user');
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
} // end user.class
