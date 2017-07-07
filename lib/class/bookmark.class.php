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
 * Bookmark class
 *
 * This manage bookmark on playable items
 *
 */
class Bookmark extends database_object
{
    // Public variables
    public $id;
    public $user;
    public $object_id;
    public $object_type;
    public $position;
    public $comment;
    public $creation_date;
    public $update_date;
    
    public $f_user;

    /**
     * Constructor
     * This is run every time a new object is created, and requires
     * the id and type of object that we need to pull for
     */
    public function __construct($object_id, $object_type = null, $user_id = null)
    {
        if (!$object_id) {
            return false;
        }
        
        if (!$object_type) {
            $info = $this->get_info($object_id);
        } else {
            if ($user_id == null) {
                $user_id = $GLOBALS['user']->id;
            }

            $sql        = "SELECT * FROM `bookmark` WHERE `object_type` = ? AND `object_id` = ? AND `user` = ?";
            $db_results = Dba::read($sql, array($object_type, $object_id, $user_id));

            if (!$db_results) {
                return false;
            }

            $info = Dba::fetch_assoc($db_results);
        }
        
        // Foreach what we've got
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
            
        return true;
    }

    /**
     * gc
     *
     * Remove bookmark for items that no longer exist.
     */
    public static function gc($object_type = null, $object_id = null)
    {
        $types = array('song', 'video', 'podcast_episode');

        if ($object_type != null) {
            if (in_array($object_type, $types)) {
                $sql = "DELETE FROM `bookmark` WHERE `object_type` = ? AND `object_id` = ?";
                Dba::write($sql, array($object_type, $object_id));
            } else {
                debug_event('bookmark', 'Garbage collect on type `' . $object_type . '` is not supported.', 1);
            }
        } else {
            foreach ($types as $type) {
                Dba::write("DELETE FROM `bookmark` USING `bookmark` LEFT JOIN `$type` ON `$type`.`id` = `bookmark`.`object_id` WHERE `bookmark`.`object_type` = '$type' AND `$type`.`id` IS NULL");
            }
        }
    }
    
    public static function get_bookmarks_ids($user = null)
    {
        $ids = array();
        if ($user == null) {
            $user = $GLOBALS['user'];
        }
        
        $sql        = "SELECT `id` FROM `bookmark` WHERE `user` = ?";
        $db_results = Dba::read($sql, array($user->id));
        while ($results = Dba::fetch_assoc($db_results)) {
            $ids[] = $results['id'];
        }
        
        return $ids;
    }
    
    public static function get_bookmarks($user = null)
    {
        $bookmarks = array();
        $ids       = self::get_bookmarks_ids($user);
        foreach ($ids as $id) {
            $bookmarks[] = new Bookmark($id);
        }

        return $bookmarks;
    }
    
    public static function create(array $data)
    {
        $user     = $data['user'] ?: $GLOBALS['user']->id;
        $position = $data['position'] ?: 0;
        $comment  = scrub_in($data['comment']);
        
        $sql = "INSERT INTO `bookmark` (`user`, `position`, `comment`, `object_type`, `object_id`, `creation_date`, `update_date`) VALUES (?, ?, ?, ?, ?, ?, ?)";

        return Dba::write($sql, array($user, $position, $comment, $data['object_type'], $data['object_id'], time(), time()));
    }
    
    public function update($position)
    {
        $sql = "UPDATE `bookmark` SET `position` = ?, `update_date` = ? WHERE `id` = ?";

        return Dba::write($sql, array($position, time(), $this->id));
    }
    
    public function remove()
    {
        $sql = "DELETE FROM `bookmark` WHERE `id` = ?";

        return Dba::write($sql, array($this->id));
    }

    public function format()
    {
        $user   = new User($this->user);
        $f_user = $user->username;
    }
} //end bookmark class
