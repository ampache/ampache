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
use Ampache\Module\Authorization\Access;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use PDOStatement;

/**
 * This class handles playlists in ampache. it references the playlist* tables
 */
class Playlist extends playlist_object
{
    protected const DB_TABLENAME = 'playlist';

    /* Variables from the database */
    public $genre;
    public $date;
    public $last_update;
    public $last_duration;

    public $f_date;
    public $f_last_update;

    /* Generated Elements */
    public $items = array();

    /**
     * Constructor
     * This takes a playlist_id as an optional argument and gathers the information
     * if not playlist_id is passed returns false (or if it isn't found
     * @param integer $object_id
     */
    public function __construct($object_id)
    {
        $info = $this->get_info($object_id);
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
    } // Playlist

    public function getId(): int
    {
        return (int)$this->id;
    }

    /**
     * garbage_collection
     *
     * Clean dead items out of playlists
     */
    public static function garbage_collection()
    {
        foreach (array('song', 'podcast_episode', 'video') as $object_type) {
            Dba::write("DELETE FROM `playlist_data` USING `playlist_data` LEFT JOIN `" . $object_type . "` ON `" . $object_type . "`.`id` = `playlist_data`.`object_id` WHERE `" . $object_type . "`.`file` IS NULL AND `playlist_data`.`object_type`='" . $object_type . "';");
        }
        Dba::write("DELETE FROM `playlist_data` USING `playlist_data` LEFT JOIN `live_stream` ON `live_stream`.`id` = `playlist_data`.`object_id` WHERE `live_stream`.`id` IS NULL AND `playlist_data`.`object_type`='live_stream';");
        Dba::write("DELETE FROM `playlist` USING `playlist` LEFT JOIN `playlist_data` ON `playlist_data`.`playlist` = `playlist`.`id` WHERE `playlist_data`.`object_id` IS NULL;");
    }

    /**
     * build_cache
     * This is what builds the cache from the objects
     * @param array $ids
     */
    public static function build_cache($ids)
    {
        if (!empty($ids)) {
            $idlist     = '(' . implode(',', $ids) . ')';
            $sql        = "SELECT * FROM `playlist` WHERE `id` IN $idlist";
            $db_results = Dba::read($sql);

            while ($row = Dba::fetch_assoc($db_results)) {
                parent::add_to_cache('playlist', $row['id'], $row);
            }
        }
    } // build_cache

    /**
     * get_playlists
     * Returns a list of playlists accessible by the user.
     * @param integer $user_id
     * @param string $playlist_name
     * @param boolean $like
     * @param boolean $includePublic
     * @param boolean $includeHidden
     * @return integer[]
     */
    public static function get_playlists($user_id = null, $playlist_name = '', $like = true, $includePublic = true, $includeHidden = true)
    {
        if (!$user_id) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
        }
        $key = ($includePublic)
            ? 'playlistids'
            : 'accessibleplaylistids';
        if (empty($playlist_name)) {
            if (parent::is_cached($key, $user_id)) {
                return parent::get_from_cache($key, $user_id);
            }
        }
        $is_admin = (Access::check('interface', 100, $user_id) || $user_id == -1);
        $sql      = "SELECT `id` FROM `playlist` ";
        $params   = array();
        $join     = 'WHERE';

        if (!$is_admin) {
            $sql .= ($includePublic)
                ? "$join (`user` = ? OR `type` = 'public') "
                : "$join (`user` = ?) ";
            $params[] = $user_id;
            $join     = 'AND';
        }
        if ($playlist_name !== '') {
            $playlist_name = (!$like) ? "= '" . $playlist_name . "'" : "LIKE '%" . $playlist_name . "%' ";
            $sql .= "$join `name` " . $playlist_name;
            $join = 'AND';
        }
        if (!$includeHidden) {
            $hide_string = str_replace('%', '\%', str_replace('_', '\_', Preference::get_by_user($user_id, 'api_hidden_playlists')));
            if (!empty($hide_string)) {
                $sql .= "$join `name` NOT LIKE '" . Dba::escape($hide_string) . "%' ";
            }
        }
        $sql .= "ORDER BY `name`";
        //debug_event(self::class, 'get_playlists query: ' . $sql, 5);

        $db_results = Dba::read($sql, $params);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        if (empty($playlist_name)) {
            parent::add_to_cache($key, $user_id, $results);
        }

        return $results;
    } // get_playlists

    /**
     * get_playlist_array
     * Returns a list of playlists accessible by the user with formatted name.
     * @param integer $user_id
     * @return integer[]
     */
    public static function get_playlist_array($user_id = null)
    {
        if (!$user_id) {
            $user_id = Core::get_global('user')->id ?? 0;
        }
        $key = 'playlistarray';
        if (parent::is_cached($key, $user_id)) {
            return parent::get_from_cache($key, $user_id);
        }
        $is_admin = (Access::check('interface', 100, $user_id) || $user_id == -1);
        $sql      = "SELECT `id`, IF(`user` = ?, `name`, CONCAT(`name`, ' (', `username`, ')')) AS `name` FROM `playlist` ";
        $params   = array($user_id);

        if (!$is_admin) {
            $sql .= "WHERE (`user` = ? OR `type` = 'public') ";
            $params[] = $user_id;
        }
        $sql .= "ORDER BY `name`";
        //debug_event(self::class, 'get_playlists query: ' . $sql, 5);

        $db_results = Dba::read($sql, $params);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['id']] = $row['name'];
        }

        parent::add_to_cache($key, $user_id, $results);

        return $results;
    } // get_playlist_array

    /**
     * get_details
     * Returns a keyed array of playlist id and name accessible by the user.
     * @param string $type
     * @param integer $user_id
     * @return array
     */
    public static function get_details($type = 'playlist', $user_id = null)
    {
        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? -1;
        }

        $sql        = "SELECT `id`, `name` FROM `$type` WHERE (`user` = ? OR `type` = 'public') ORDER BY `name`";
        $results    = array();
        $db_results = Dba::read($sql, array($user_id));
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[$row['id']] = $row['name'];
        }

        return $results;
    } // get_playlists

    /**
     * get_smartlists
     * Returns a list of searches accessible by the user.
     * @param integer $user_id
     * @param string $playlist_name
     * @param boolean $like
     * @param boolean $includeHidden
     * @return array
     */
    public static function get_smartlists($user_id = null, $playlist_name = '', $like = true, $includeHidden = true)
    {
        if (!$user_id) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? 0;
        }
        $key  = 'smartlists';
        if (empty($playlist_name)) {
            if (parent::is_cached($key, $user_id)) {
                return parent::get_from_cache($key, $user_id);
            }
        }
        $is_admin = (Access::check('interface', 100, $user_id) || $user_id == -1);
        $sql      = "SELECT CONCAT('smart_', `id`) AS `id` FROM `search` ";
        $params   = array();
        $join     = 'WHERE';

        if (!$is_admin) {
            $sql .= "$join (`user` = ? OR `type` = 'public') ";
            $params[] = $user_id;
            $join     = 'AND';
        }
        if ($playlist_name !== '') {
            $playlist_name = (!$like) ? "= '" . $playlist_name . "'" : "LIKE '%" . $playlist_name . "%' ";
            $sql .= "$join `name` " . $playlist_name;
            $join = 'AND';
        }
        if (!$includeHidden) {
            $hide_string = str_replace('%', '\%', str_replace('_', '\_', Preference::get_by_user($user_id, 'api_hidden_playlists')));
            if (!empty($hide_string)) {
                $sql .= "$join `name` NOT LIKE '" . Dba::escape($hide_string) . "%' ";
            }
        }
        $sql .= "ORDER BY `name`";
        //debug_event(self::class, 'get_smartlists ' . $sql, 5);

        $db_results = Dba::read($sql, $params);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }

        if (empty($playlist_name)) {
            parent::add_to_cache($key, $user_id, $results);
        }

        return $results;
    } // get_smartlists

    /**
     * format
     * This takes the current playlist object and gussies it up a little
     * bit so it is presentable to the users
     * @param boolean $details
     */
    public function format($details = true)
    {
        parent::format($details);
        $this->f_date        = $this->date ? get_datetime((int)$this->date) : T_('Unknown');
        $this->f_last_update = $this->last_update ? get_datetime((int)$this->last_update) : T_('Unknown');
    } // format

    /**
     * get_items
     * This returns an array of playlist medias that are in this playlist.
     * Because the same media can be on the same playlist twice they are
     * keyed by the uid from playlist_data
     * @return array
     */
    public function get_items()
    {
        $results = array();
        $user    = Core::get_global('user');
        $user_id = $user->id ?? 0;

        // Iterate over the object types
        $sql              ='SELECT DISTINCT `object_type` FROM `playlist_data`';
        $db_object_types  = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_object_types)) {
            $object_type = $row['object_type'];
            $params      = array($this->id);

            switch ($object_type) {
                case "song":
                    $sql = 'SELECT `playlist_data`.`id`, `object_id`, `object_type`, `playlist_data`.`track` FROM `playlist_data` INNER JOIN `song` ON `playlist_data`.`object_id` = `song`.`id` WHERE `playlist_data`.`playlist` = ? AND `object_id` IS NOT NULL ';
                    if (AmpConfig::get('catalog_filter') && $user_id > 0) {
                        $sql .= 'AND `playlist_data`.`object_type`="song" AND `song`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id`= ? AND `catalog_filter_group_map`.`enabled`=1) ';
                        $params[] = $user_id;
                    }
                    $sql .= 'ORDER BY `playlist_data`.`track`';
                    break;
                case "podcast_episode":
                    $sql = 'SELECT `playlist_data`.`id`, `object_id`, `object_type`, `playlist_data`.`track` FROM `playlist_data` INNER JOIN `podcast_episode` ON `playlist_data`.`object_id` = `podcast_episode`.`id` WHERE `playlist_data`.`playlist` = ? AND `object_id` IS NOT NULL ';
                    if (AmpConfig::get('catalog_filter') && $user_id > 0) {
                        $sql .= 'AND `playlist_data`.`object_type`="podcast_episode" AND `podcast_episode`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id`= ? AND `catalog_filter_group_map`.`enabled`=1) ';
                        $params[] = $user_id;
                    }
                    $sql .= 'ORDER BY `playlist_data`.`track`';
                    break;
                default:
                    $sql = "SELECT `id`, `object_id`, `object_type`, `track` FROM `playlist_data` WHERE `playlist`= ? AND `playlist_data`.`object_type` != 'song' AND `playlist_data`.`object_type` != 'podcast_episode' ORDER BY `track`";
                    debug_event(__CLASS__, "get_items(): $object_type not handled", 5);
            }
            $db_results  = Dba::read($sql, $params);

            while ($row = Dba::fetch_assoc($db_results)) {
                $results[] = array(
                    'object_type' => $row['object_type'],
                    'object_id' => (int)$row['object_id'],
                    'track' => (int)$row['track'],
                    'track_id' => $row['id']
                );
            }
        }
        //	debug_event(__CLASS__, "get_items(): Results:\n" . print_r($results,true) , 5);

        return $results;
    } // get_items

    /**
     * get_random_items
     * This is the same as before but we randomize the buggers!
     * @param string $limit
     * @return array
     */
    public function get_random_items($limit = '')
    {
        $limit_sql = (!empty($limit))
            ? ' LIMIT ' . (string)($limit)
            : '';
        $results = array();
        $user    = Core::get_global('user');
        $user_id = $user->id ?? 0;

        // Iterate over the object types
        $sql              ='SELECT DISTINCT `object_type` FROM `playlist_data`';
        $db_object_types  = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_object_types)) {
            $object_type = $row['object_type'];
            $params      = array($this->id);

            switch ($object_type) {
                case "song":
                case "live_stream":
                case "podcast_episode":
                case "video":
                    $sql = "SELECT `playlist_data`.`id`, `object_id`, `object_type`, `playlist_data`.`track` FROM `playlist_data` INNER JOIN `$object_type` ON `playlist_data`.`object_id` = `$object_type`.`id` WHERE `playlist_data`.`playlist` = ? AND `object_type` = '$object_type' ";
                    if (AmpConfig::get('catalog_filter') && $user_id > 0) {
                        $sql .= "AND `playlist_data`.`object_type`='$object_type' AND `$object_type`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id`= ? AND `catalog_filter_group_map`.`enabled`=1) ";
                        $params[] = $user_id;
                    }
                    $sql .= 'ORDER BY RAND()';
                    break;
                default:
                    $sql = "SELECT `id`, `object_id`, `object_type`, `track` FROM `playlist_data` WHERE `playlist`= ? AND `playlist_data`.`object_type` != 'song' AND `playlist_data`.`object_type` != 'podcast_episode' AND `playlist_data`.`object_type` != 'live_stream' ORDER BY `track`";
                    debug_event(__CLASS__, "get_items(): $object_type not handled", 5);
            }
            $db_results  = Dba::read($sql . $limit_sql, $params);
            while ($row = Dba::fetch_assoc($db_results)) {
                $results[] = array(
                    'object_type' => $row['object_type'],
                    'object_id' => (int)$row['object_id'],
                    'track' => (int)$row['track'],
                    'track_id' => $row['id']
                );
            }
        } // end while
        //debug_event(__CLASS__, "get_random_items(): " . $sql . $limit_sql, 5);

        return $results;
    } // get_random_items

    /**
     * get_songs
     * This is called by the batch script, because we can't pass in Dynamic objects they pulled once and then their
     * target song.id is pushed into the array
     */
    public function get_songs()
    {
        $results = array();
        $user    = Core::get_global('user');
        $user_id = $user->id ?? 0;
        $params  = array($this->id);

        $sql = 'SELECT `playlist_data`.`id`, `object_id`, `object_type`, `playlist_data`.`track` FROM `playlist_data` INNER JOIN `song` ON `playlist_data`.`object_id` = `song`.`id` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_type`="song" AND `object_id` IS NOT NULL ';
        if (AmpConfig::get('catalog_filter') && $user_id > 0) {
            $sql .= 'AND `playlist_data`.`object_type`="song" AND `song`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id`= ? AND `catalog_filter_group_map`.`enabled`=1) ';
            $params[] = $user_id;
        }
        $sql .= "ORDER BY `playlist_data`.`track`";
        $db_results  = Dba::read($sql, $params);
        //	debug_event(__CLASS__, "get_songs(): " . $sql, 5);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['object_id'];
        } // end while

        return $results;
    } // get_songs

    /**
     * get_media_count
     * This simply returns a int of how many media elements exist in this playlist
     * For now let's consider a dyn_media a single entry
     * @param string $type
     * @return integer
     */
    public function get_media_count($type = '')
    {
        $user    = Core::get_global('user');
        $user_id = $user->id ?? 0;
        $params  = array($this->id);

        $sql = 'SELECT COUNT(`playlist_data`.`id`) AS `list_count` FROM `playlist_data` INNER JOIN `song` ON `playlist_data`.`object_id` = `song`.`id` WHERE `playlist_data`.`playlist` = ? AND `object_id` IS NOT NULL ';
        // NEED TO REVIST FOR ALL MEDIA TYPES;
        if (!empty($type)) {
            $sql .= 'AND `playlist_data`.`object_type` = ? ';
            $params[] = $type;
        }
        if (AmpConfig::get('catalog_filter') && $user_id > 0) {
            $sql .= 'AND `playlist_data`.`object_type`="song" AND `song`.`catalog` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id`= ? AND `catalog_filter_group_map`.`enabled`=1) ';
            $params[] = $user_id;
        }

        $sql .= "GROUP BY `playlist_data`.`playlist`;";

        //debug_event(__CLASS__, "get_media_count(): " . $sql . ' ' . print_r($params, true), 5);

        $db_results = Dba::read($sql, $params);
        $row        = Dba::fetch_assoc($db_results);
        if (empty($row)) {
            return 0;
        }

        return (int)$row['list_count'];
    } // get_media_count

    /**
    * get_total_duration
    * Get the total duration of all songs.
    * @return integer
    */
    public function get_total_duration()
    {
        $songs  = $this->get_songs();
        $idlist = '(' . implode(',', $songs) . ')';
        if ($idlist == '()') {
            return 0;
        }
        $sql        = "SELECT SUM(`time`) FROM `song` WHERE `id` IN $idlist";
        $db_results = Dba::read($sql);
        $row        = Dba::fetch_row($db_results);
        if (empty($row)) {
            return 0;
        }

        //	debug_event(__CLASS__, "get_total_duration(): " . $sql, 5);

        return (int) $row[0];
    } // get_total_duration

    /**
     * update
     * This function takes a key'd array of data and runs updates
     * @param array $data
     * @return integer
     */
    public function update(array $data)
    {
        if (isset($data['name']) && $data['name'] != $this->name) {
            $this->update_name($data['name']);
        }
        if (isset($data['pl_type']) && $data['pl_type'] != $this->type) {
            $this->update_type($data['pl_type']);
        }
        if (isset($data['pl_user']) && $data['pl_user'] != $this->user) {
            $this->update_user($data['pl_user']);
        }
        // reformat after an update
        $this->format();

        return $this->id;
    } // update

    /**
     * update_type
     * This updates the playlist type, it calls the generic update_item function
     * @param string $new_type
     */
    private function update_type($new_type)
    {
        if ($this->_update_item('type', $new_type)) {
            $this->type = $new_type;
        }
    } // update_type

    /**
     * update_user
     * This updates the playlist type, it calls the generic update_item function
     * @param int $new_user
     */
    private function update_user($new_user)
    {
        if ($this->_update_item('user', $new_user)) {
            $this->user     = $new_user;
            $this->username = User::get_username($new_user);
            $sql            = "UPDATE `playlist` SET `user` = ?, `username` = ? WHERE `playlist`.`user` = ?;";
            Dba::write($sql, array($this->user, $this->username, $this->user));
        }
    } // update_type

    /**
     * update_name
     * This updates the playlist name, it calls the generic update_item function
     * @param string $new_name
     */
    private function update_name($new_name)
    {
        if ($this->_update_item('name', $new_name)) {
            $this->name = $new_name;
        }
    } // update_name

    /**
     * update_last_update
     * This updates the playlist last update, it calls the generic update_item function
     */
    private function update_last_update()
    {
        $last_update = time();
        if ($this->_update_item('last_update', $last_update)) {
            $this->last_update = $last_update;
        }
        $this->set_last($this->get_total_duration(), 'last_duration');
    } // update_last_update

    /**
     * _update_item
     * This is the generic update function, it does the escaping and error checking
     * @param string $field
     * @param string|int $value
     * @return PDOStatement|boolean
     */
    private function _update_item($field, $value)
    {
        if (Core::get_global('user')->id != $this->user && !Access::check('interface', 50)) {
            return false;
        }

        $sql = "UPDATE `playlist` SET `$field` = ? WHERE `id` = ?";

        return Dba::write($sql, array($value, $this->id));
    } // update_item

    /**
     * update_track_number
     * This takes a playlist_data.id and a track (int) and updates the track value
     * @param integer $track_id
     * @param integer $index
     */
    public function update_track_number($track_id, $index)
    {
        $sql = "UPDATE `playlist_data` SET `track` = ? WHERE `id` = ?";
        Dba::write($sql, array($index, $track_id));
    } // update_track_number

    /**
     * Regenerate track numbers to fill gaps.
     */
    public function regenerate_track_numbers()
    {
        $items = $this->get_items();
        $index = 1;
        foreach ($items as $item) {
            $this->update_track_number($item['track_id'], $index);
            $index++;
        }

        $this->update_last_update();
    }

    /**
     * add_songs
     * @param array $song_ids
     * This takes an array of song_ids and then adds it to the playlist
     */
    public function add_songs($song_ids = array())
    {
        $medias = array();
        foreach ($song_ids as $song_id) {
            $medias[] = array(
                'object_type' => 'song',
                'object_id' => $song_id,
            );
        }
        $this->add_medias($medias);
        Catalog::update_mapping('playlist');
    } // add_songs

    /**
     * add_medias
     * @param array $medias
     * @return bool
     */
    public function add_medias($medias)
    {
        if (empty($medias)) {
            return false;
        }
        /* We need to pull the current 'end' track and then use that to
         * append, rather then integrate take end track # and add it to
         * $song->track add one to make sure it really is 'next'
         */
        debug_event(self::class, "add_medias to: " . $this->id, 5);
        $unique     = (bool) AmpConfig::get('unique_playlist');
        $track_data = $this->get_items();
        $base_track = count($track_data);
        $count      = 0;
        $sql        = "INSERT INTO `playlist_data` (`playlist`, `object_id`, `object_type`, `track`) VALUES ";
        $values     = array();
        foreach ($medias as $data) {
            if ($unique && in_array($data['object_id'], $track_data)) {
                debug_event(self::class, "Can't add a duplicate " . $data['object_type'] . " (" . $data['object_id'] . ") when unique_playlist is enabled", 3);
            } else {
                $count++;
                $track = $base_track + $count;
                $sql .= "(?, ?, ?, ?), ";
                $values[] = $this->id;
                $values[] = $data['object_id'];
                $values[] = $data['object_type'];
                $values[] = $track;
            } // if valid id
        } // end foreach medias
        Dba::write(rtrim($sql, ', '), $values);
        debug_event(self::class, "Added $count tracks to playlist: " . $this->id, 5);
        $this->update_last_update();

        return true;
    }

    /**
     * check
     * This function creates an empty playlist, gives it a name and type
     * @param string $name
     * @param string $type
     * @param integer $user_id
     * @return int
     */
    public static function check($name, $type, $user_id = null)
    {
        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? -1;
        }
        $results    = array();
        $sql        = "SELECT `id` FROM `playlist` WHERE `name` = ? AND `user` = ? AND `type` = ?";
        $db_results = Dba::read($sql, array($name, $user_id, $type));

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }
        // return the duplicate ID
        if (!empty($results)) {
            return $results[0];
        }

        return 0;
    } // check

    /**
     * create
     * This function creates an empty playlist, gives it a name and type
     * @param string $name
     * @param string $type
     * @param int $user_id
     * @param bool $existing
     * @return int|null
     */
    public static function create($name, $type, $user_id = null, $existing = true)
    {
        if ($user_id === null) {
            $user    = Core::get_global('user');
            $user_id = $user->id ?? -1;
        }
        // check for duplicates
        $existing_id = self::check($name, $type, $user_id);
        if ($existing_id > 0) {
            if (!$existing) {
                return null;
            } else {
                return $existing_id;
            }
        }

        // get the public_name/username
        $username = User::get_username($user_id);

        $date = time();
        $sql  = "INSERT INTO `playlist` (`name`, `user`, `username`, `type`, `date`, `last_update`) VALUES (?, ?, ?, ?, ?, ?)";
        Dba::write($sql, array($name, $user_id, $username, $type, $date, $date));
        $insert_id = Dba::insert_id();

        if (empty($insert_id)) {
            return null;
        }

        Catalog::count_table('playlist');

        return (int)$insert_id;
    } // create

    /**
     * set_items
     * This calls the get_items function and sets it to $this->items which is an array in this object
     */
    public function set_items()
    {
        $this->items = $this->get_items();
    } // set_items

    /**
     * set_last
     *
     * @param integer $count
     * @param string $column
     */
    private function set_last($count, $column)
    {
        if ($this->id && in_array($column, array('last_count', 'last_duration')) && $count >= 0) {
            $sql = "UPDATE `playlist` SET `" . Dba::escape($column) . "` = " . $count . " WHERE `id` = " . Dba::escape($this->id);
            Dba::write($sql);
        }
    }

    /**
     * delete_all
     *
     * this deletes all tracks from a playlist, you specify the playlist.id here
     * @return boolean
     */
    public function delete_all()
    {
        $sql = "DELETE FROM `playlist_data` WHERE `playlist_data`.`playlist` = ?";
        Dba::write($sql, array($this->id));
        debug_event(self::class, 'Delete all tracks from: ' . $this->id, 5);

        $this->update_last_update();

        return true;
    } // delete_all

    /**
     * delete_song
     * @param integer $object_id
     * this deletes a single track, you specify the playlist_data.id here
     * @return boolean
     */
    public function delete_song($object_id)
    {
        $sql = "DELETE FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_id` = ? LIMIT 1";
        Dba::write($sql, array($this->id, $object_id));
        debug_event(self::class, 'Delete object_id: ' . $object_id . ' from ' . $this->id, 5);

        $this->update_last_update();

        return true;
    } // delete_track

    /**
     * delete_track
     * this deletes a single track, you specify the playlist_data.id here
     * @param integer $object_id
     * @return boolean
     */
    public function delete_track($object_id)
    {
        $sql = "DELETE FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`id` = ? LIMIT 1";
        Dba::write($sql, array($this->id, $object_id));
        debug_event(self::class, 'Delete item_id: ' . $object_id . ' from ' . $this->id, 5);

        $this->update_last_update();

        return true;
    } // delete_track

    /**
     * delete_track_number
     * this deletes a single track by it's track #, you specify the playlist_data.track here
     * @param integer $track
     * @return boolean
     */
    public function delete_track_number($track)
    {
        $sql = "DELETE FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`track` = ? LIMIT 1";
        Dba::write($sql, array($this->id, $track));
        debug_event(self::class, 'Delete track: ' . $track . ' from ' . $this->id, 5);

        $this->update_last_update();

        return true;
    } // delete_track_number

    /**
     * set_by_track_number
     * this deletes a single track by it's track #, you specify the playlist_data.track here
     * @param integer $object_id
     * @param integer $track
     * @return boolean
     */
    public function set_by_track_number($object_id, $track)
    {
        $sql = "UPDATE `playlist_data` SET `object_id` = ? WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`track` = ?";
        Dba::write($sql, array($object_id, $this->id, $track));
        debug_event(self::class, 'Set track ' . $track . ' to ' . $object_id . ' for playlist: ' . $this->id, 5);

        $this->update_last_update();

        return true;
    } // delete_track_number

    /**
     * has_item
     * look for the track id or the object id in a playlist
     * @param integer $object
     * @param integer $track
     * @return boolean
     */
    public function has_item($object = null, $track = null)
    {
        $results = array();
        if ($object) {
            $sql        = "SELECT `object_id` FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_id` = ? LIMIT 1";
            $db_results = Dba::read($sql, array($this->id, $object));
            $results    = Dba::fetch_assoc($db_results);
        } elseif ($track) {
            $sql        = "SELECT `track` FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`track` = ? LIMIT 1";
            $db_results = Dba::read($sql, array($this->id, $track));
            $results    = Dba::fetch_assoc($db_results);
        }
        if (isset($results['object_id']) || isset($results['track'])) {
            debug_event(self::class, 'has_item results: ' . ($results['object_id'] ?? $results['track']), 5);

            return true;
        }

        return false;
    } // has_item

    /**
     * has_search
     * Look for a saved smartlist with the same name as this playlist that the user can access
     * @param int $playlist_user
     * @return int
     */
    public function has_search($playlist_user): int
    {
        // search for your own playlist
        $sql        = "SELECT `id`, `name` FROM `search` WHERE `user` = ?";
        $db_results = Dba::read($sql, array($playlist_user));
        while ($row = Dba::fetch_assoc($db_results)) {
            if ($row['name'] == $this->name) {
                return (int)$row['id'];
            }
        }
        // look for public ones
        $sql        = "SELECT `id`, `name` FROM `search` WHERE (`type`='public' OR `user` = ?)";
        $db_results = Dba::read($sql, array(Core::get_global('user')->id));
        while ($row = Dba::fetch_assoc($db_results)) {
            if ($row['name'] == $this->name) {
                return (int)$row['id'];
            }
        }

        return 0;
    } // has_search

    /**
     * delete
     * This deletes the current playlist and all associated data
     */
    public function delete()
    {
        $sql = "DELETE FROM `playlist_data` WHERE `playlist` = ?";
        Dba::write($sql, array($this->id));

        $sql = "DELETE FROM `playlist` WHERE `id` = ?";
        Dba::write($sql, array($this->id));

        $sql = "DELETE FROM `object_count` WHERE `object_type`='playlist' AND `object_id` = ?";
        Dba::write($sql, array($this->id));
        Catalog::count_table('playlist');

        return true;
    } // delete

    /**
     * Sort the tracks and save the new position
     */
    public function sort_tracks()
    {
        /* First get all of the songs in order of their tracks */
        $sql = "SELECT `list`.`id` FROM `playlist_data` AS `list` LEFT JOIN `song` ON `list`.`object_id` = `song`.`id` LEFT JOIN `album` ON `song`.`album` = `album`.`id` LEFT JOIN `artist` ON `album`.`album_artist` = `artist`.`id` WHERE `list`.`playlist` = ? ORDER BY `artist`.`name`, `album`.`name`, `album`.`year`, `song`.`disk`, `song`.`track`, `song`.`title`";

        $count      = 1;
        $db_results = Dba::query($sql, array($this->id));
        $results    = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = array(
                'id' => $row['id'],
                'track' => $count
            );
            $count++;
        } // end while results
        if (!empty($results)) {
            $sql = "INSERT INTO `playlist_data` (`id`, `track`) VALUES ";
            foreach ($results as $data) {
                $sql .= "(" . Dba::escape($data['id']) . ", " . Dba::escape($data['track']) . "), ";
            } // foreach re-ordered results

            //replace the last comma
            $sql = substr_replace($sql, "", -2);
            $sql .= "ON DUPLICATE KEY UPDATE `track`=VALUES(`track`)";

            // do this in one go
            Dba::write($sql);
        }
        $this->update_last_update();

        return true;
    } // sort_tracks

    /**
     * Migrate an object associate stats to a new object
     * @param string $object_type
     * @param integer $old_object_id
     * @param integer $new_object_id
     * @return PDOStatement|boolean
     */
    public static function migrate($object_type, $old_object_id, $new_object_id)
    {
        $sql    = "UPDATE `playlist_data` SET `object_id` = ? WHERE `object_id` = ? AND `object_type` = ?;";
        $params = array($new_object_id, $old_object_id, $object_type);

        return Dba::write($sql, $params);
    }
}
