<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\Ui;
use Ampache\Repository\IpHistoryRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use Exception;
use PDOStatement;

/**
 * This class handles all of the user related functions including the creation
 * and deletion of the user objects from the database by default you construct it
 * with a user_id from user.id
 */
class User extends database_object
{
    protected const DB_TABLENAME = 'user';

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
     * @var string $city
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
     * @var int $catalog_filter_group;
     */
    public $catalog_filter_group;

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

        $this->id = (int)($user_id);

        $info = $this->has_info();

        foreach ($info as $key => $value) {
            // Let's not save the password in this object :S
            if ($key == 'password') {
                continue;
            }
            $this->$key = $value;
        }

        // Make sure the Full name is always filled
        if (strlen((string)$this->fullname) < 1) {
            $this->fullname = $this->username;
        }

        return true;
    } // Constructor

    public function getId(): int
    {
        return (int)$this->id;
    }

    /**
     * count
     *
     * This returns the number of user accounts that exist.
     */
    public static function count()
    {
        $sql              = 'SELECT COUNT(`id`) FROM `user`';
        $db_results       = Dba::read($sql);
        $row              = Dba::fetch_row($db_results);
        $results          = array();
        $results['users'] = $row[0] ?? 0;

        $time                 = time();
        $last_seen            = $time - 1200;
        $sql                  = "SELECT COUNT(DISTINCT `session`.`username`) FROM `session` INNER JOIN `user` ON `session`.`username` = `user`.`username` WHERE `session`.`expire` > ? AND `user`.`last_seen` > ?";
        $db_results           = Dba::read($sql, array($time, $last_seen));
        $row                  = Dba::fetch_row($db_results);
        $results['connected'] = $row[0] ?? 0;

        return $results;
    }

    /**
     * has_info
     * This function returns the information for this object
     * @return array
     */
    private function has_info()
    {
        $user_id = (int)($this->id);

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
     * get_from_username
     * This returns a built user from a username. This is a
     * static function so it doesn't require an instance
     * @param string $username
     * @return User|null $user
     */
    public static function get_from_username($username)
    {
        return static::getUserRepository()->findByUsername($username);
    } // get_from_username

    /**
     * get_from_apikey
     * This returns a built user from a username. This is a
     * static function so it doesn't require an instance
     * @param string $apikey
     * @return User|null
     */
    public static function get_from_apikey($apikey)
    {
        return static::getUserRepository()->findByApiKey($apikey);
    } // get_from_apikey

    /**
     * get_from_email
     * This returns a built user from an email address. This is a
     * static function so it doesn't require an instance
     * @param string $emailAddress
     * @return User|null $user
     */
    public static function get_from_email($emailAddress)
    {
        return static::getUserRepository()->findByEmail($emailAddress);
    } // get_from_email

    /**
     * id_from_username
     * This returns a built user from a username. This is a
     * static function so it doesn't require an instance
     * @param string $username
     * @return int
     */
    public static function id_from_username($username)
    {
        return static::getUserRepository()->idByUsername($username);
    } // id_from_username

    /**
     * id_from_email
     * This returns a built user from an email address. This is a
     * static function so it doesn't require an instance
     * @param string $emailAddress
     * @return int
     */
    public static function id_from_email($emailAddress)
    {
        return static::getUserRepository()->idByEmail($emailAddress);
    } // id_from_email

    /**
     * get_user_catalogs
     * This returns the catalogs as an array of ids that this user is allowed to access
     * @return integer[]
     */
    public static function get_user_catalogs($userid)
    {
        if (parent::is_cached('user_catalog', $userid)) {
            return parent::get_from_cache('user_catalog', $userid);
        }

        $sql        = "SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id`= ? AND `catalog_filter_group_map`.`enabled` = 1 ORDER BY `catalog_filter_group_map`.`catalog_id`";
        $db_results = Dba::read($sql, array($userid));

        $catalogs = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $catalogs[] = (int)$row['catalog_id'];
        }

        parent::add_to_cache('user_catalog', $userid, $catalogs);

        return $catalogs;
    } // get_catalogs


    /**
     * get_catalogs
     * This returns the catalogs as an array of ids that this user is allowed to access
     * @return integer[]
     */
    public function get_catalogs()
    {
        return self::get_user_catalogs($this->id);
    } // get_catalogs


    /**
     * get_preferences
     * This is a little more complicate now that we've got many types of preferences
     * This function pulls all of them an arranges them into a spiffy little array
     * You can specify a type to limit it to a single type of preference
     * []['title'] = uppercase type name
     * []['prefs'] = array(array('name', 'display', 'value'));
     * []['admin'] = t/f value if this is an admin only section
     * @param integer $type
     * @param boolean $system
     * @return array
     */
    public function get_preferences($type = 0, $system = false)
    {
        $user_limit = "";
        if (!$system) {
            $user_id    = $this->id;
            $user_limit = "AND preference.catagory != 'system'";
        } else {
            $user_id =  -1;
            if ($type != '0') {
                $user_limit = "AND preference.catagory = '" . Dba::escape($type) . "'";
            }
        }

        $sql        = "SELECT `preference`.`name`, `preference`.`description`, `preference`.`catagory`, `preference`.`subcatagory`, preference.level, user_preference.value FROM `preference` INNER JOIN `user_preference` ON `user_preference`.`preference` = `preference`.`id` WHERE `user_preference`.`user` = ? " . $user_limit . " ORDER BY `preference`.`catagory`, `preference`.`subcatagory`, `preference`.`description`";
        $db_results = Dba::read($sql, array($user_id));
        $results    = array();
        $type_array = array();
        /* Ok this is crappy, need to clean this up or improve the code FIXME */
        while ($row = Dba::fetch_assoc($db_results)) {
            $type  = $row['catagory'];
            $admin = false;
            if ($type == 'system') {
                $admin = true;
            }
            $type_array[$type][$row['name']] = array(
                'name' => $row['name'],
                'level' => $row['level'],
                'description' => $row['description'],
                'value' => $row['value'],
                'subcategory' => $row['subcatagory']
            );
            $results[$type] = array(
                'title' => ucwords((string)$type),
                'admin' => $admin,
                'prefs' => $type_array[$type]
            );
        } // end while

        return $results;
    } // get_preferences

    /**
     * set_preferences
     * sets the prefs for this specific user
     */
    public function set_preferences()
    {
        $user_id    = Dba::escape($this->id);
        $sql        = "SELECT `preference`.`name`, `user_preference`.`value` FROM `preference`, `user_preference` WHERE `user_preference`.`user` = ? AND `user_preference`.`preference` = `preference`.`id` AND `preference`.`type` != 'system';";
        $db_results = Dba::read($sql, array($user_id));

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
     * is_logged_in
     * checks to see if $this user is logged in returns their current IP if they are logged in
     */
    public function is_logged_in()
    {
        $sql        = "SELECT `id`, `ip` FROM `session` WHERE `username`= ? AND `expire` > ?;";
        $db_results = Dba::read($sql, array($this->username, time()));

        if ($row = Dba::fetch_assoc($db_results)) {
            return $row['ip'] ?? null;
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
        if (empty(Core::get_global('user'))) {
            return false;
        }
        if (!Core::get_global('user')->id) {
            return false;
        }

        if (!AmpConfig::get('use_auth') && Core::get_global('user')->access < 5) {
            return false;
        }

        return true;
    }

    /**
     * set_user_data
     * This updates some background data for user specific function
     * @param int $user_id
     * @param string $key
     * @param string|integer $value
     */
    public static function set_user_data(int $user_id, string $key, $value)
    {
        Dba::write("REPLACE INTO `user_data` SET `user`= ?, `key`= ?, `value`= ?;", array($user_id, $key, $value));
    } // set_user_data

    /**
     * get_user_data
     * This updates some background data for user specific function
     * @param int $user_id
     * @param string $key
     * @return array
     */
    public static function get_user_data($user_id, $key = null)
    {
        $sql    = "SELECT `key`, `value` FROM `user_data` WHERE `user` = ?";
        $params = array($user_id);
        if ($key) {
            $sql .= " AND `key` = ?";
            $params[] = $key;
        }

        $db_results = Dba::read($sql, $params);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['key']] = $row['value'];
        }

        return $results;
    } // get_user_data

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
                case 'catalog_filter_group':
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
     * update_catalog_filter_group
     * updates their catalog filter
     * @param $new_filter
     */
    public function update_catalog_filter_group($new_filter)
    {
        $sql = "UPDATE `user` SET `catalog_filter_group` = ? WHERE `id` = ?";

        debug_event(self::class, 'Updating catalog access group', 4);

        Dba::write($sql, array($new_filter, $this->id));
    } // update_catalog_filter_group

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
        $new_website = rtrim((string)$new_website, "/");
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
     * update_counts for individual users
     */
    public static function update_counts()
    {
        $catalog_disable = AmpConfig::get('catalog_disable');
        $catalog_filter  = AmpConfig::get('catalog_filter');
        $sql             = "SELECT `id` FROM `user`";
        $db_results      = Dba::read($sql);
        $user_list       = array();
        while ($results  = Dba::fetch_assoc($db_results)) {
            $user_list[] = (int)$results['id'];
        }
        if (!$catalog_filter) {
            // no filter means no need for filtering or counting per user
            $count_array   = array('song', 'video', 'podcast_episode', 'artist', 'album', 'search', 'playlist', 'live_stream', 'podcast', 'user', 'catalog', 'label', 'tag', 'share', 'license', 'album_group', 'items', 'time', 'size');
            $server_counts = Catalog::get_server_counts(0);
            foreach ($user_list as $user_id) {
                debug_event(self::class, 'Update counts for ' . $user_id, 5);
                foreach ($server_counts as $table => $count) {
                    if (in_array($table, $count_array)) {
                        self::set_user_data($user_id, $table, $count);
                    }
                }
            }

            return;
        }

        $count_array = array('song', 'video', 'podcast_episode', 'artist', 'album', 'search', 'playlist', 'live_stream', 'podcast', 'user', 'catalog', 'label', 'tag', 'share', 'license');
        foreach ($user_list as $user_id) {
            debug_event(self::class, 'Update counts for ' . $user_id, 5);
            // get counts per user (filtered catalogs aren't counted)
            foreach ($count_array as $table) {
                $sql        = (in_array($table, array('search', 'user', 'license')))
                    ? "SELECT COUNT(`id`) FROM `$table`"
                    : "SELECT COUNT(`id`) FROM `$table` WHERE" . Catalog::get_user_filter($table, $user_id);
                $db_results = Dba::read($sql);
                $row        = Dba::fetch_row($db_results);

                self::set_user_data($user_id, $table, (int)($row[0] ?? 0));
            }
            // tables with media items to count, song-related tables and the rest
            $media_tables = array('song', 'video', 'podcast_episode');
            $items        = 0;
            $time         = 0;
            $size         = 0;
            foreach ($media_tables as $table) {
                $enabled_sql = ($catalog_disable && $table !== 'podcast_episode')
                    ? " WHERE `$table`.`enabled`='1' AND"
                    : ' WHERE';
                $sql        = "SELECT COUNT(`id`), IFNULL(SUM(`time`), 0), IFNULL(SUM(`size`), 0) FROM `$table`" . $enabled_sql . Catalog::get_user_filter($table, $user_id);
                $db_results = Dba::read($sql);
                $row        = Dba::fetch_row($db_results);
                // save the object and add to the current size
                $items += (int)($row[0] ?? 0);
                $time += (int)($row[1] ?? 0);
                $size += (int)($row[2] ?? 0);
                self::set_user_data($user_id, $table, (int)($row[0] ?? 0));
            }
            self::set_user_data($user_id, 'items', $items);
            self::set_user_data($user_id, 'time', $time);
            self::set_user_data($user_id, 'size', $size);
            // grouped album counts
            $sql        = "SELECT COUNT(DISTINCT(`album`.`id`)) AS `count` FROM `album` WHERE `id` in (SELECT MIN(`id`) FROM `album` GROUP BY `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`release_type`, `album`.`release_status`, `album`.`mbid`, `album`.`year`, `album`.`original_year`, `album`.`mbid_group`) AND" . Catalog::get_user_filter('album', $user_id);
            $db_results = Dba::read($sql);
            $row        = Dba::fetch_row($db_results);
            self::set_user_data($user_id, 'album_group', (int)($row[0] ?? 0));
        }
    } // update_counts

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
        $sql        = "UPDATE `user` SET `access` = ? WHERE `id` = ?;";

        debug_event(self::class, 'Updating access level for ' . $this->id, 4);

        Dba::write($sql, array($new_access, $this->id));

        return true;
    } // update_access

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
        $sip = (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            ? filter_var($_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP)
            : filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);
        debug_event(self::class, 'Login from IP address: ' . (string) $sip, 3);

        // Remove port information if any
        if (!empty($sip)) {
            // Use parse_url to support easily ipv6
            if (filter_var($sip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === true) {
                $sipar = parse_url("http://" . $sip);
            } else {
                $sipar = parse_url("http://[" . $sip . "]");
            }
            $sip = $sipar['host'];
        }

        $uip     = (!empty($sip)) ? Dba::escape(inet_pton(trim((string)$sip, "[]"))) : '';
        $date    = time();
        $user_id = (int)$this->id;
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
    public static function create(
        $username,
        $fullname,
        $email,
        $website,
        $password,
        $access,
        $catalog_filter_group = 0,
        $state = '',
        $city = '',
        $disabled = false,
        $encrypted = false
    ) {
        // don't try to overwrite users that already exist
        if (static::getUserRepository()->idByUsername($username) > 0 || static::getUserRepository()->idByEmail($email) > 0) {
            return 0;
        }
        $website = rtrim((string)$website, "/");
        if (!$encrypted) {
            $password = hash('sha256', $password);
        }
        $disabled = $disabled ? 1 : 0;

        // Just in case a zero value slipped in from upper layers...
        $catalog_filter_group = $catalog_filter_group ?? 0;

        /* Now Insert this new user */
        $sql    = "INSERT INTO `user` (`username`, `disabled`, `fullname`, `email`, `password`, `access`, `catalog_filter_group`, `create_date`";
        $params = array($username, $disabled, $fullname, $email, $password, $access, $catalog_filter_group, time());

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

        $sql .= ") VALUES(?, ?, ?, ?, ?, ?, ?, ?";

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
            return 0;
        }
        // Get the insert_id
        $insert_id = (int)Dba::insert_id();

        // Populates any missing preferences, in this case all of them
        self::fix_preferences($insert_id);

        Catalog::count_table('user');

        return (int)$insert_id;
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
        /* If they have a last seen date */
        if (!$this->last_seen) {
            $this->f_last_seen = T_('Never');
        } else {
            $this->f_last_seen = get_datetime((int)$this->last_seen);
        }

        /* If they have a create date */
        if (!$this->create_date) {
            $this->f_create_date = T_('Unknown');
        } else {
            $this->f_create_date = get_datetime((int)$this->create_date);
        }

        // Base link
        $this->f_link = '<a href="' . $this->get_link() . '">' . scrub_out($this->get_fullname()) . '</a>';

        if ($details) {
            $user_data = self::get_user_data($this->id);
            if (!isset($user_data['play_size'])) {
                // Calculate their total Bandwidth Usage
                $sql        = "SELECT SUM(`song`.`size`) AS `play_size` FROM `object_count` LEFT JOIN `song` ON `song`.`id`=`object_count`.`object_id` WHERE `object_count`.`user` = ? AND `object_count`.`object_type` IN ('song', 'video', 'podcast_episode') GROUP BY `user`;";
                $db_results = Dba::read($sql, array($this->id));
                $result     = Dba::fetch_assoc($db_results);
                $play_size  = $result['play_size'] ?? 0;
                // set the value for next time
                self::set_user_data($this->id, 'play_size', (int)$play_size);
                $user_data['play_size'] = $play_size;
            }

            $this->f_usage = Ui::format_bytes((int)$user_data['play_size']);

            // Get Users Last ip
            if (count($data = $this->getIpHistoryRepository()->getHistory($this->getId()))) {
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
                return AccessLevelEnum::LEVEL_ADMIN;
            case 'user':
                return AccessLevelEnum::LEVEL_USER;
            case 'manager':
                return AccessLevelEnum::LEVEL_MANAGER;
            // FIXME why is content manager not here?
            //case 'manager':
                //return AccessLevelEnum::LEVEL_CONTENT_MANAGER;
            case 'guest':
                return AccessLevelEnum::LEVEL_GUEST;
            default:
                return AccessLevelEnum::LEVEL_DEFAULT;
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
        // Check default group (autoincrement starts at 1 so force it to be 0)
        $sql        = "SELECT `id`, `name` FROM `catalog_filter_group` WHERE `name` = 'DEFAULT';";
        $db_results = Dba::read($sql);
        $row        = Dba::fetch_assoc($db_results);
        if (!array_key_exists('id', $row) || ($row['id'] ?? '') != 0) {
            debug_event(self::class, 'fix_preferences restore DEFAULT catalog_filter_group', 2);
            // reinsert missing default group
            $sql = "INSERT IGNORE INTO `catalog_filter_group` (`name`) VALUES ('DEFAULT');";
            Dba::write($sql);
            $sql = "UPDATE `catalog_filter_group` SET `id` = 0 WHERE `name` = 'DEFAULT';";
            Dba::write($sql);
            $sql        = "SELECT MAX(`id`) AS `filter_count` FROM `catalog_filter_group`;";
            $db_results = Dba::read($sql);
            $row        = Dba::fetch_assoc($db_results);
            $increment  = (int)($row['filter_count'] ?? 0) + 1;
            $sql        = "ALTER TABLE `catalog_filter_group` AUTO_INCREMENT = $increment;";
            Dba::write($sql);
        }

        /* Get All Preferences for the current user */
        $sql          = "SELECT * FROM `user_preference` WHERE `user` = ?";
        $db_results   = Dba::read($sql, array($user_id));
        $results      = array();
        $zero_results = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $pref_id = $row['preference'];
            // Check for duplicates
            if (isset($results[$pref_id])) {
                $sql = "DELETE FROM `user_preference` WHERE `user` = ? AND `preference`= ? AND `value` = ?;";
                Dba::write($sql, array($user_id, $row['preference'], $row['value']));
            } else {
                // if its set
                $results[$pref_id] = 1;
            }
        } // end while

        // If your user is missing preferences we copy the value from system (Except for plugins and system prefs)
        if ($user_id != '-1') {
            $sql        = "SELECT `user_preference`.`preference`, `user_preference`.`value` FROM `user_preference`, `preference` WHERE `user_preference`.`preference` = `preference`.`id` AND `user_preference`.`user`='-1' AND `preference`.`catagory` NOT IN ('plugins', 'system');";
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
            $sql .= " WHERE catagory !='system';";
        }
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $key = $row['id'];

            // Check if this preference is set
            if (!isset($results[$key])) {
                if (isset($zero_results[$key])) {
                    $row['value'] = $zero_results[$key];
                }
                $sql   = "INSERT INTO user_preference (`user`, `preference`, `value`) VALUES (?, ?, ?)";
                Dba::write($sql, array($user_id, $key, $row['value']));
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

        // Delete the user itself
        $sql = "DELETE FROM `user` WHERE `id` = ?";
        Dba::write($sql, array($this->id));

        // Delete custom access settings
        $sql = "DELETE FROM `access_list` WHERE `user` = ?";
        Dba::write($sql, array($this->id));

        $sql = "DELETE FROM `session` WHERE `username` = ?";
        Dba::write($sql, array($this->username));

        Catalog::count_table('user');
        static::getUserRepository()->collectGarbage();

        return true;
    } // delete

    /**
     * is_online
     * delay how long since last_seen in seconds default of 20 min
     * calculates difference between now and last_seen
     * if less than delay, we consider them still online
     * @param integer $delay
     * @return boolean
     */
    public function is_online($delay = 1200)
    {
        return time() - $this->last_seen <= $delay;
    } // is_online

    /**
     * get_recently_played
     * This gets the recently played items for this user respecting
     * the limit passed. ger recent by default or oldest if $newest is false.
     * @param string $type
     * @param integer $count
     * @param integer $offset
     * @param boolean $newest
     * @return array
     */
    public function get_recently_played($type, $count, $offset = 0, $newest = true)
    {
        $ordersql = ($newest === true) ? 'DESC' : 'ASC';
        $limit    = ($offset < 1) ? $count : $offset . "," . $count;

        $sql        = "SELECT `object_id`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_type` = ? AND `user` = ? GROUP BY `object_id` ORDER BY `date` " . $ordersql . " LIMIT " . $limit . " ";
        $db_results = Dba::read($sql, array($type, $this->id));

        $results = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['object_id'];
        }

        return $results;
    } // get_recently_played

    /**
     * Get item fullname.
     * @return string
     */
    public function get_fullname()
    {
        if (!isset($this->f_name)) {
            $this->f_name = ($this->fullname_public)
                ? $this->fullname
                : $this->username;
        }

        return $this->f_name;
    }

    /**
     * Get item link.
     * @return string
     */
    public function get_link()
    {
        // don't do anything if it's formatted
        if (!isset($this->link)) {
            $web_path   = AmpConfig::get('web_path');
            $this->link = $web_path . '/stats.php?action=show_user&user_id=' . $this->id;
        }

        return $this->link;
    }

    /**
     * Get item name based on whether they allow public fullname access.
     * @param int $user_id
     * @return string
     */
    public static function get_username($user_id)
    {
        $users = static::getUserRepository()->getValidArray(true);

        return (isset($users[$user_id]))
            ? $users[$user_id]
            : T_('System');
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
        if (array_key_exists('t', $session) && $session['s']) {
            $auth = '&t=' . $session['t'] . '&s=' . $session['s'];
        } elseif (array_key_exists('auth', $session)) {
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

        if (!array_key_exists('url', $avatar)) {
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
     * rebuild_all_preferences
     * This rebuilds the user preferences for all installed users, called by the plugin functions
     */
    public static function rebuild_all_preferences()
    {
        // Garbage collection
        $sql = "DELETE `user_preference`.* FROM `user_preference` LEFT JOIN `user` ON `user_preference`.`user` = `user`.`id` WHERE `user_preference`.`user` != -1 AND `user`.`id` IS NULL;";
        Dba::write($sql);
        // delete system prefs from users
        $sql = "DELETE `user_preference`.* FROM `user_preference` LEFT JOIN `preference` ON `user_preference`.`preference` = `preference`.`id` WHERE `user_preference`.`user` != -1 AND `preference`.`catagory` = 'system';";
        Dba::write($sql);

        // How many preferences should we have?
        $sql        = "SELECT COUNT(`id`) AS `pref_count` FROM `preference` WHERE `catagory` != 'system';";
        $db_results = Dba::read($sql);
        $row        = Dba::fetch_assoc($db_results);
        $pref_count = (int)$row['pref_count'];
        // Get only users who have less preferences than excepted otherwise it would have significant performance issue with large user database
        $sql        = "SELECT `user` FROM `user_preference` GROUP BY `user` HAVING COUNT(*) < $pref_count";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            self::fix_preferences($row['user']);
        }
        // Fix the system user preferences
        self::fix_preferences(-1);

        return true;
    } // rebuild_all_preferences

    /**
     * stream_control
     * Check all stream control plugins
     * @param array $media_ids
     * @param User|null $user
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

    /**
     * @deprecated inject dependency
     */
    private function getIpHistoryRepository(): IpHistoryRepositoryInterface
    {
        global $dic;

        return $dic->get(IpHistoryRepositoryInterface::class);
    }

    /**
     * @deprecated inject dependency
     */
    private static function getUserRepository(): UserRepositoryInterface
    {
        global $dic;

        return $dic->get(UserRepositoryInterface::class);
    }
}
