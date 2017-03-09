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
 * Userflag class
 *
 * This user flag/unflag songs, albums, artists, videos, tvshows, movies ... as favorite.
 *
 */
class Useractivity extends database_object
{
    /* Variables from DB */
    public $id;
    public $user;
    public $object_type;
    public $object_id;
    public $action;
    public $activity_date;

    /**
     * Constructor
     * This is run every time a new object is created, and requires
     * the id and type of object that we need to pull the flag for
     */
    public function __construct($id)
    {
        if (!$id) {
            return false;
        }
        
        /* Get the information from the db */
        $info = $this->get_info($id, 'user_activity');

        foreach ($info as $key=>$value) {
            $this->$key = $value;
        } // foreach info

        return true;
    } // Constructor

    /**
     * this attempts to build a cache of the data from the passed activities all in one query
     * @param int[] $ids
     * @return boolean
     */
    public static function build_cache($ids)
    {
        if (!is_array($ids) or !count($ids)) {
            return false;
        }

        $idlist = '(' . implode(',', $ids) . ')';

        $sql        = "SELECT * FROM `user_activity` WHERE `id` IN $idlist";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('user_activity',$row['id'],$row);
        }
    }
    /**
     * gc
     *
     * Remove activities for items that no longer exist.
     */
    public static function gc($object_type = null, $object_id = null)
    {
        $types = array('song', 'album', 'artist', 'video', 'tvshow', 'tvshow_season');

        if ($object_type != null) {
            if (in_array($object_type, $types)) {
                $sql = "DELETE FROM `user_activity` WHERE `object_type` = ? AND `object_id` = ?";
                Dba::write($sql, array($object_type, $object_id));
            } else {
                debug_event('userflag', 'Garbage collect on type `' . $object_type . '` is not supported.', 1);
            }
        } else {
            foreach ($types as $type) {
                Dba::write("DELETE FROM `user_activity` USING `user_activity` LEFT JOIN `$type` ON `$type`.`id` = `user_activity`.`object_id` WHERE `object_type` = '$type' AND `$type`.`id` IS NULL");
            }
        }
    }
    
    /**
     * post_activity
     * @param int $user_id
     * @param string $activity
     * @param string $object_type
     * @param int $object_id
     */
    public static function post_activity($user_id, $action, $object_type, $object_id)
    {
        // Only save the activity if sociable
        if (!AmpConfig::get('sociable')) {
            return false;
        }
        
        $sql = "INSERT INTO `user_activity` (`user`, `action`, `object_type`, `object_id`, `activity_date`) VALUES (?, ?, ?, ?, ?)";
        return Dba::write($sql, array($user_id, $action, $object_type, $object_id, time()));
    }
    
    /**
     * get_activities
     * @param int $user_id
     * @param int $limit
     * @param int $since
     * @return int[]
     */
    public static function get_activities($user_id, $limit = 0, $since = 0)
    {
        if ($limit <= 0) {
            $limit = AmpConfig::get('popular_threshold');
        }
        
        $params = array($user_id);
        $sql    = "SELECT `id` FROM `user_activity` WHERE `user` = ?";
        if ($since > 0) {
            $sql .= " AND `activity_date` <= ?";
            $params[] = $since;
        }
        $sql .= " ORDER BY `activity_date` DESC LIMIT " . $limit;
        $db_results = Dba::read($sql, $params);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }
        return $results;
    }
    
    /**
     * get_friends_activities
     * @param int $user_id
     * @param int $limit
     * @param int $since
     * @return int[]
     */
    public static function get_friends_activities($user_id, $limit = 0, $since = 0)
    {
        if ($limit <= 0) {
            $limit = AmpConfig::get('popular_threshold');
        }
        
        $params = array($user_id);
        $sql    = "SELECT `user_activity`.`id` FROM `user_activity`" .
                " INNER JOIN `user_follower` ON `user_follower`.`follow_user` = `user_activity`.`user`"
                . " WHERE `user_follower`.`user` = ?";
        if ($since > 0) {
            $sql .= " AND `user_activity`.`activity_date` <= ?";
            $params[] = $since;
        }
        $sql .= " ORDER BY `user_activity`.`activity_date` DESC LIMIT " . $limit;
        $db_results = Dba::read($sql, $params);
        $results    = array();
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = $row['id'];
        }
        return $results;
    }

    /**
     * show
     * Show the activity entry.
     */
    public function show()
    {
        // If user flags aren't enabled don't do anything
        if (!AmpConfig::get('userflags') || !$this->id) {
            return false;
        }
        
        $user = new User($this->user);
        $user->format();
        $libitem = new $this->object_type($this->object_id);
        $libitem->format();
        
        echo '<div>';
        $fdate = date('m/d/Y H:i:s', $this->activity_date);
        echo '<div class="shoutbox-date">';
        if ($user->f_avatar_mini) {
            echo '<a href="' . $user->link . '">' . $user->f_avatar_mini . '</a> ';
        }
        echo $fdate;
        echo '</div>';
                      
        $descr = $user->f_link . ' ';
        switch ($this->action) {
            case 'shout':
                $descr .= T_('commented on');
                break;
            case 'upload':
                $descr .= T_('uploaded');
                break;
            case 'play':
                $descr .= T_('played');
                break;
            case 'userflag':
                $descr .= T_('favorited');
                break;
            case 'follow':
                $descr .= T_('started to follow');
                break;
            default:
                $descr .= T_('did something on');
                break;
        }
        $descr .= ' ' . $libitem->f_link;
        echo '<div>';
        echo $descr;
        
        if (Core::is_library_item($this->object_type)) {
            echo ' ';
            $libitem->display_art(10);
        }
        echo '</div>';
        
        echo '</div><br />';
    } // show
} //end useractivity class
