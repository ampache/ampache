<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

use Ampache\Module\Playback\Stream;
use Ampache\Module\Playback\Stream_Url;
use Ampache\Module\Statistics\Stats;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use PDOStatement;

/**
 * This class handles democratic play, which is a fancy
 * name for voting based playback.
 */
class Democratic extends Tmp_Playlist
{
    protected const DB_TABLENAME = 'democratic';

    public $name;
    public $cooldown;
    public $level;
    public $user;
    public $primary;
    public $base_playlist;

    public $f_cooldown;
    public $f_primary;
    public $f_level;

    // Build local, buy local
    public $tmp_playlist;
    public $object_ids = array();
    public $vote_ids   = array();
    public $user_votes = array();

    /**
     * constructor
     * We need a constructor for this class. It does it's own thing now
     * @param $democratic_id
     */
    public function __construct($democratic_id)
    {
        parent::__construct($democratic_id);

        $info = $this->get_info($democratic_id, static::DB_TABLENAME);
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
    } // constructor

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    /**
     * build_vote_cache
     * This builds a vote cache of the objects we've got in the playlist
     * @param $ids
     * @return bool
     */
    public static function build_vote_cache($ids)
    {
        if (!is_array($ids) || !count($ids)) {
            return false;
        }

        $idlist = '(' . implode(',', $ids) . ')';
        $sql    = "SELECT `object_id`, COUNT(`user`) AS `count` FROM `user_vote` WHERE `object_id` IN $idlist GROUP BY `object_id`";

        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('democratic_vote', $row['object_id'], array($row['count']));
        }

        return true;
    } // build_vote_cache

    /**
     * is_enabled
     * This function just returns true / false if the current democratic
     * playlist is currently enabled / configured
     */
    public function is_enabled()
    {
        if ($this->tmp_playlist) {
            return true;
        }

        return false;
    } // is_enabled

    /**
     * set_parent
     * This returns the Tmp_Playlist for this democratic play instance
     */
    public function set_parent()
    {
        $sql        = "SELECT * FROM `tmp_playlist` WHERE `session` = ?";
        $db_results = Dba::read($sql, array($this->id));
        $row        = Dba::fetch_assoc($db_results);
        if (!empty($row)) {
            $this->tmp_playlist = $row['id'] ?? null;
        }
    } // set_parent

    /**
     * format
     * This makes the variables pretty so that they can be displayed
     */
    public function format()
    {
        $this->f_cooldown = $this->cooldown . ' ' . T_('minutes');
        $this->f_primary  = $this->primary ? T_('Primary') : '';
        $this->f_level    = User::access_level_to_name($this->level);
    } // format

    /**
     * get_playlists
     * This returns all of the current valid 'Democratic' Playlists that have been created.
     */
    public static function get_playlists()
    {
        $sql = "SELECT `id` FROM `democratic` ORDER BY `name`";

        $db_results = Dba::read($sql);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int)$row['id'];
        }

        return $results;
    } // get_playlists

    /**
     * get_current_playlist
     * This returns the current users current playlist, or if specified
     * this current playlist of the user
     */
    public static function get_current_playlist($user = false)
    {
        if (!$user) {
            $user = Core::get_global('user');
        }
        $democratic_id = AmpConfig::get('democratic_id', false);
        if (!$democratic_id) {
            $sql           = "SELECT `id` FROM `democratic` WHERE `level` <= ? ORDER BY `level` DESC,`primary` DESC";
            $db_results    = Dba::read($sql, array($user->access ?? 0));
            $row           = Dba::fetch_assoc($db_results);
            if (!empty($row)) {
                $democratic_id = $row['id'];
            }
        }

        return new Democratic($democratic_id);
    } // get_current_playlist

    /**
     * get_items
     * This returns a sorted array of all object_ids in this Tmp_Playlist.
     * The array is multidimensional; the inner array needs to contain the
     * keys 'id', 'object_type' and 'object_id'.
     *
     * Sorting is highest to lowest vote count, then by oldest to newest
     * vote activity.
     * @param int $limit
     * @return array
     */
    public function get_items($limit = null)
    {
        // Remove 'unconnected' users votes
        if (AmpConfig::get('demo_clear_sessions')) {
            $sql = 'DELETE FROM `user_vote` WHERE `user_vote`.`sid` NOT IN (SELECT `session`.`id` FROM `session`)';
            Dba::write($sql);
        }

        $sql = "SELECT `tmp_playlist_data`.`object_type`, `tmp_playlist_data`.`object_id`, `tmp_playlist_data`.`id` FROM `tmp_playlist_data` INNER JOIN `user_vote` ON `user_vote`.`object_id` = `tmp_playlist_data`.`id` WHERE `tmp_playlist_data`.`tmp_playlist` = ? GROUP BY 1, 2, 3 ORDER BY COUNT(*) DESC, MAX(`user_vote`.`date`), MAX(`tmp_playlist_data`.`id`) ";

        if ($limit !== null) {
            $sql .= 'LIMIT ' . (string)($limit);
        }

        $db_results = Dba::read($sql, array($this->tmp_playlist));
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            if ($row['id']) {
                $results[] = $row;
            }
        }

        return $results;
    } // get_items

    /**
     * play_url
     * This returns the special play URL for democratic play, only open to ADMINs
     * @param User|null $user
     * @return string
     */
    public function play_url($user = null)
    {
        if (empty($user)) {
            $user = Core::get_global('user');
        }
        $link = Stream::get_base_url(false, $user->streamtoken) . 'uid=' . scrub_out($user->id) . '&demo_id=' . scrub_out($this->id);

        return Stream_Url::format($link);
    } // play_url

    /**
     * get_next_object
     * This returns the next object in the tmp_playlist.
     * Most of the time this will just be the top entry, but if there is a
     * base_playlist and no items in the playlist then it returns a random
     * entry from the base_playlist
     * @param int $offset
     * @return int|null
     */
    public function get_next_object($offset = 0)
    {
        // FIXME: Shouldn't this return object_type?

        $offset     = (int)($offset);
        $items      = $this->get_items($offset + 1);
        $use_search = AmpConfig::get('demo_use_search');

        if (count($items) > $offset) {
            return $items[$offset]['object_id'];
        }

        // If nothing was found and this is a voting playlist then get from base_playlist
        if ($this->base_playlist) {
            $base_playlist = ($use_search)
                ? new Search($this->base_playlist)
                : new Playlist($this->base_playlist);
            $data          = $base_playlist->get_random_items(1);

            return $data[0]['object_id'];
        } else {
            $sql        = "SELECT `id` FROM `song` WHERE `enabled`='1'";
            if (AmpConfig::get('catalog_filter') && !empty(Core::get_global('user')) && Core::get_global('user')->id > 0) {
                $sql .= " AND" . Catalog::get_user_filter("song", Core::get_global('user')->id);
            }
            $sql .= " ORDER BY RAND() LIMIT 1";
            $db_results = Dba::read($sql);
            $results    = Dba::fetch_assoc($db_results);

            return $results['id'];
        }
    } // get_next_object

    /**
     * get_uid_from_object_id
     * This takes an object_id and an object type and returns the ID for the row
     * @param int $object_id
     * @param string $object_type
     * @return int|null
     */
    public function get_uid_from_object_id($object_id, $object_type = 'song')
    {
        if (!$object_id) {
            return null;
        }
        $sql        = "SELECT `id` FROM `tmp_playlist_data` WHERE `object_type` = ? AND `tmp_playlist` = ? AND `object_id` = ?;";
        $db_results = Dba::read($sql, array($object_type, $this->tmp_playlist, $object_id));
        $row        = Dba::fetch_assoc($db_results);
        if (empty($row)) {
            return null;
        }

        return (int)$row['id'];
    } // get_uid_from_object_id

    /**
     * get_cool_songs
     * This returns all of the song_ids for songs that have happened within
     * the last 'cooldown' for this user.
     */
    public function get_cool_songs()
    {
        // Convert cooldown time to a timestamp in the past
        $cool_time = time() - ($this->cooldown * 60);

        return Stats::get_object_history(Core::get_global('user')->id, $cool_time);
    } // get_cool_songs

    /**
     * vote
     * This function is called by users to vote on a system wide playlist
     * This adds the specified objects to the tmp_playlist and adds a 'vote'
     * by this user, naturally it checks to make sure that the user hasn't
     * already voted on any of these objects
     * @param $items
     */
    public function add_vote($items)
    {
        /* Iterate through the objects if no vote, add to playlist and vote */
        foreach ($items as $element) {
            $type      = array_shift($element);
            $object_id = array_shift($element);
            if (!$this->has_vote($object_id, $type)) {
                $this->_add_vote($object_id, $type);
            }
        } // end foreach
    } // vote

    /**
     * has_vote
     * This checks to see if the current user has already voted on this object
     * @param int $object_id
     * @param string $type
     * @return bool
     */
    public function has_vote($object_id, $type = 'song')
    {
        $params = array($type, $object_id, $this->tmp_playlist);

        /* Query vote table */
        $sql = "SELECT `tmp_playlist_data`.`object_id` FROM `user_vote` INNER JOIN `tmp_playlist_data` ON `tmp_playlist_data`.`id`=`user_vote`.`object_id` WHERE `tmp_playlist_data`.`object_type` = ? AND `tmp_playlist_data`.`object_id` = ? AND `tmp_playlist_data`.`tmp_playlist` = ? ";
        if (Core::get_global('user')->id > 0) {
            $sql .= "AND `user_vote`.`user` = ? ";
            $params[] = Core::get_global('user')->id;
        } else {
            $sql .= "AND `user_vote`.`sid` = ? ";
            $params[] = session_id();
        }
        $db_results = Dba::read($sql, $params);

        /* If we find  row, they've voted!! */
        if (Dba::num_rows($db_results)) {
            return true;
        }

        return false;
    } // has_vote

    /**
     * _add_vote
     * This takes a object id and user and actually inserts the row
     * @param int $object_id
     * @param string $object_type
     * @return bool
     */
    private function _add_vote($object_id, $object_type = 'song')
    {
        if (!$this->tmp_playlist) {
            return false;
        }

        $class_name = ObjectTypeToClassNameMapper::map($object_type);
        $media      = new $class_name($object_id);
        $track      = isset($media->track) ? (int)($media->track) : null;

        /* If it's on the playlist just vote */
        $sql        = "SELECT `id` FROM `tmp_playlist_data` WHERE `tmp_playlist_data`.`object_id` = ? AND `tmp_playlist_data`.`tmp_playlist` = ?";
        $db_results = Dba::write($sql, array($object_id, $this->tmp_playlist));

        /* If it's not there, add it and pull ID */
        if (!$results = Dba::fetch_assoc($db_results)) {
            $sql = "INSERT INTO `tmp_playlist_data` (`tmp_playlist`, `object_id`, `object_type`, `track`) VALUES (?, ?, ?, ?)";
            Dba::write($sql, array($this->tmp_playlist, $object_id, $object_type, $track));
            $results       = array();
            $results['id'] = Dba::insert_id();
        }

        /* Vote! */
        $time = time();
        $sql  = "INSERT INTO user_vote (`user`, `object_id`, `date`, `sid`) VALUES (?, ?, ?, ?)";
        Dba::write($sql, array(Core::get_global('user')->id, $results['id'], $time, session_id()));

        return true;
    }

    /**
     * remove_vote
     * This is called to remove a vote by a user for an object, it uses the object_id
     * As that's what we'll have most the time, no need to check if they've got an existing
     * vote for this, just remove anything that is there
     * @param $row_id
     * @return bool
     */
    public function remove_vote($row_id)
    {
        $sql    = "DELETE FROM `user_vote` WHERE `object_id` = ? ";
        $params = array($row_id);
        if (Core::get_global('user')->id > 0) {
            $sql .= "AND `user` = ?";
            $params[] = Core::get_global('user')->id;
        } else {
            $sql .= "AND `user_vote`.`sid` = ? ";
            $params[] = session_id();
        }
        Dba::write($sql, $params);

        /* Clean up anything that has no votes */
        self::prune_tracks();

        return true;
    } // remove_vote

    /**
     * delete_votes
     * This removes the votes for the specified object on the current playlist
     * @param $row_id
     * @return bool
     */
    public function delete_votes($row_id)
    {
        $sql = "DELETE FROM `user_vote` WHERE `object_id` = ?";
        Dba::write($sql, array($row_id));

        $sql = "DELETE FROM `tmp_playlist_data` WHERE `id` = ?";
        Dba::write($sql, array($row_id));

        return true;
    } // delete_votes

    /**
     * delete_from_oid
     * This takes an OID and type and removes the object from the democratic playlist
     * @param int $object_id
     * @param string $object_type
     * @return bool
     */
    public function delete_from_oid($object_id, $object_type)
    {
        $row_id = $this->get_uid_from_object_id($object_id, $object_type);
        if ($row_id) {
            debug_event(self::class, 'Removing Votes for ' . $object_id . ' of type ' . $object_type, 5);
            $this->delete_votes($row_id);
        } else {
            debug_event(self::class, 'Unable to find Votes for ' . $object_id . ' of type ' . $object_type, 3);
        }

        return true;
    } // delete_from_oid

    /**
     * delete
     * This deletes a democratic playlist
     * @param int $democratic_id
     * @return bool
     */
    public static function delete($democratic_id)
    {
        $sql = "DELETE FROM `democratic` WHERE `id` = ?;";
        Dba::write($sql, array($democratic_id));

        $sql = "DELETE FROM `tmp_playlist` WHERE `session` = ?;";
        Dba::write($sql, array($democratic_id));

        self::prune_tracks();

        return true;
    } // delete

    /**
     * update
     * This updates an existing democratic playlist item. It takes a key'd array just like create
     * @param array $data
     * @return bool
     */
    public function update(array $data)
    {
        $name    = $data['name'] ?? $this->name;
        $base    = (int)$data['democratic'];
        $cool    = (int)$data['cooldown'];
        $level   = (int)$data['level'];
        $default = (int)$data['make_default'];
        $demo_id = $this->id;

        // no negative ints, this also gives you over 2 million days...
        if ($cool < 0 || $cool > 3000000000) {
            return false;
        }

        $sql = "UPDATE `democratic` SET `name` = ?, `base_playlist` = ?,`cooldown` = ?, `primary` = ?, `level` = ? WHERE `id` = ?";
        Dba::write($sql, array($name, $base, $cool, $default, $level, $demo_id));

        return true;
    } // update

    /**
     * create
     * This is the democratic play create function it inserts this into the democratic table
     * @param array $data
     * @return PDOStatement|bool
     */
    public static function create($data)
    {
        // Clean up the input
        $name    = $data['name'];
        $base    = (int)$data['democratic'];
        $cool    = (int)$data['cooldown'];
        $level   = (int)$data['level'];
        $default = (int)$data['make_default'];
        $user    = (int)Core::get_global('user')->id;

        $sql        = "INSERT INTO `democratic` (`name`, `base_playlist`, `cooldown`, `level`, `user`, `primary`) VALUES (?, ?, ?, ?, ?, ?)";
        $db_results = Dba::write($sql, array($name, $base, $cool, $level, $user, $default));

        if ($db_results) {
            $insert_id = Dba::insert_id();
            parent::create(array(
                'session_id' => $insert_id,
                'type' => 'vote',
                'object_type' => 'song'
            ));
        }

        return $db_results;
    } // create

    /**
     * prune_tracks
     * This replaces the normal prune tracks and correctly removes the votes
     * as well
     */
    public static function prune_tracks()
    {
        // This deletes data without votes, if it's a voting democratic playlist
        $sql = "DELETE FROM `tmp_playlist_data` USING `tmp_playlist_data` LEFT JOIN `user_vote` ON `tmp_playlist_data`.`id`=`user_vote`.`object_id` LEFT JOIN `tmp_playlist` ON `tmp_playlist`.`id`=`tmp_playlist_data`.`tmp_playlist` WHERE `user_vote`.`object_id` IS NULL AND `tmp_playlist`.`type` = 'vote'";
        Dba::write($sql);

        return true;
    } // prune_tracks

    /**
     * clear
     * This is really just a wrapper function, it clears the entire playlist
     * including all votes etc.
     * @return bool
     */
    public function clear()
    {
        if (!$this->tmp_playlist) {
            return false;
        }
        // Clear all votes then prune
        $sql = "DELETE FROM `user_vote` USING `user_vote` LEFT JOIN `tmp_playlist_data` ON `user_vote`.`object_id` = `tmp_playlist_data`.`id` WHERE `tmp_playlist_data`.`tmp_playlist` = ?;";
        Dba::write($sql, array($this->tmp_playlist));

        // Prune!
        self::prune_tracks();

        // Clean the votes
        $this->clear_votes();

        return true;
    } // clear_playlist

    /**
     * clean_votes
     * This removes in left over garbage in the votes table
     * @return bool
     */
    public function clear_votes()
    {
        $sql = "DELETE FROM `user_vote` USING `user_vote` LEFT JOIN `tmp_playlist_data` ON `user_vote`.`object_id`=`tmp_playlist_data`.`id` WHERE `tmp_playlist_data`.`id` IS NULL";
        Dba::write($sql);

        return true;
    } // clear_votes

    /**
     * get_vote
     * This returns the current count for a specific song
     * @param int $id
     * @return int
     */
    public function get_vote($id)
    {
        if (parent::is_cached('democratic_vote', $id)) {
            return (int)(parent::get_from_cache('democratic_vote', $id))[0];
        }

        $sql        = "SELECT COUNT(`user`) AS `count` FROM `user_vote` WHERE `object_id` = ?";
        $db_results = Dba::read($sql, array($id));

        $results = Dba::fetch_assoc($db_results);
        parent::add_to_cache('democratic_vote', $id, array($results['count']));

        return (int)$results['count'];
    } // get_vote

    /**
     * show_playlist_select
     * This one is for playlists!
     * @param string $name
     * @param string $selected
     * @param string $style
     * @return string
     */
    public static function show_playlist_select($name, $selected = '', $style = '')
    {
        $user             = Core::get_global('user');
        $string           = "<select name=\"$name\" style=\"$style\">\n\t<option value=\"\">" . T_('None') . "</option>\n";
        $already_selected = false;
        $index            = 1;
        $use_search       = AmpConfig::get('demo_use_search');
        $playlists        = ($use_search)
            ? Search::get_search_array($user->id)
            : Playlist::get_playlist_array($user->id);
        $nb_items         = count($playlists);

        foreach ($playlists as $key => $value) {
            $select_txt = '';
            if (!$already_selected && ($key == $selected || $index == $nb_items)) {
                $select_txt       = 'selected="selected"';
                $already_selected = true;
            }

            $string .= "\t<option value=\"" . $key . "\" $select_txt>" . scrub_out($value) . "</option>\n";
            ++$index;
        } // end while users

        $string .= "</select>\n";

        return $string;
    } // show_playlist_select
}
