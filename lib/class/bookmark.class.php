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
     * @param integer $object_id
     * @param string $object_type
     * @param integer $user_id
     */
    public function __construct($object_id, $object_type = null, $user_id = null)
    {
        if (!$object_id) {
            return false;
        }

        if ($object_type === null) {
            $info = $this->get_info($object_id);
        } else {
            if ($user_id === null) {
                $user_id = Core::get_global('user')->id;
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
     * garbage_collection
     *
     * Remove bookmark for items that no longer exist.
     * @param string $object_type
     * @param integer $object_id
     */
    public static function garbage_collection($object_type = null, $object_id = null)
    {
        $types = array('song', 'video', 'podcast_episode');

        if ($object_type) {
            if (in_array($object_type, $types)) {
                $sql = "DELETE FROM `bookmark` WHERE `object_type` = ? AND `object_id` = ?";
                Dba::write($sql, array($object_type, $object_id));
            } else {
                debug_event(self::class, 'Garbage collect on type `' . $object_type . '` is not supported.', 3);
            }
        } else {
            foreach ($types as $type) {
                Dba::write("DELETE FROM `bookmark` USING `bookmark` LEFT JOIN `$type` ON `$type`.`id` = `bookmark`.`object_id` WHERE `bookmark`.`object_type` = '$type' AND `$type`.`id` IS NULL");
            }
        }
    }

    /**
     * get_bookmark_ids
     *
     * @param User $user
     * @return array
     */
    public static function get_bookmark_ids($user)
    {
        $bookmarks  = array();
        $sql        = "SELECT `id` FROM `bookmark` WHERE `user` = ?";
        $db_results = Dba::read($sql, array($user->id));
        while ($results = Dba::fetch_assoc($db_results)) {
            $bookmarks[] = $results['id'];
        }

        return $bookmarks;
    }

    /**
     * get_bookmarks
     * @param User $user
     * @return array
     */
    public static function get_bookmarks($user)
    {
        $bookmarks = array();
        $ids       = self::get_bookmark_ids($user);
        foreach ($ids as $bookmarkid) {
            $bookmarks[] = new Bookmark($bookmarkid);
        }

        return $bookmarks;
    }

    /**
     * get_bookmark
     * @param array $data
     * @return integer[]
     */
    public static function get_bookmark($data)
    {
        $bookmarks   = array();
        $comment_sql = isset($data['comment']) ? "AND `comment` = '" . scrub_in($data['comment']) . "'" : "";
        $sql         = "SELECT `id` FROM `bookmark` WHERE `user` = ? AND `object_type` = ? AND `object_id` = ? " . $comment_sql;
        $db_results  = Dba::read($sql, array($data['user'], $data['object_type'], $data['object_id']));
        while ($results = Dba::fetch_assoc($db_results)) {
            $bookmarks[] = (int) $results['id'];
        }

        return $bookmarks;
    }

    /**
     * create
     * @param array $data
     * @return PDOStatement|boolean
     */
    public static function create(array $data)
    {
        $user     = $data['user'] ?: Core::get_global('user')->id;
        $position = $data['position'] ?: 0;
        $comment  = scrub_in($data['comment']);
        $updated  = $data['update_date'] ? (int) $data['update_date'] : time();

        $sql = "INSERT INTO `bookmark` (`user`, `position`, `comment`, `object_type`, `object_id`, `creation_date`, `update_date`) VALUES (?, ?, ?, ?, ?, ?, ?)";

        return Dba::write($sql, array($user, $position, $comment, $data['object_type'], $data['object_id'], time(), $updated));
    }

    /**
     * edit
     * @param array $data
     * @return PDOStatement|boolean
     */
    public static function edit($data)
    {
        $user     = $data['user'] ?: Core::get_global('user')->id;
        $position = $data['position'] ?: 0;
        $comment  = scrub_in($data['comment']);
        $updated  = $data['update_date'] ? (int) $data['update_date'] : time();
        $sql      = "UPDATE `bookmark` SET `position` = ?, `update_date` = ? " .
               "WHERE `user` = ? AND `comment` = ? AND `object_type` = ? AND `object_id` = ?";

        return Dba::write($sql, array($position, $updated, $user, $comment,  $data['object_type'], $data['object_id']));
    }

    /**
     * update
     * @param integer $position
     * @return PDOStatement|boolean
     */
    public function update($position)
    {
        $sql = "UPDATE `bookmark` SET `position` = ?, `update_date` = ? WHERE `id` = ?";

        return Dba::write($sql, array($position, time(), $this->id));
    }

    /**
     * remove
     * @return PDOStatement|boolean
     */
    public function remove()
    {
        $sql = "DELETE FROM `bookmark` WHERE `id` = ?";

        return Dba::write($sql, array($this->id));
    }

    /**
     * delete
     *
     * Delete the bookmark when you're done
     *
     * @param array $data
     * @return PDOStatement|boolean
     */
    public static function delete(array $data)
    {
        $sql = "DELETE FROM `bookmark` WHERE `user` = ? AND `comment` = ? AND `object_type` = ? AND `object_id` = ?";

        return Dba::write($sql, array($data['user'], $data['comment'], $data['object_type'], $data['object_id']));
    }

    public function format()
    {
        $user         = new User($this->user);
        $this->f_user = $user->username;
    }
} // end bookmark.class
