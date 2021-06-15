<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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

declare(strict_types=0);

namespace Ampache\Repository\Model;

use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use PDOStatement;

/**
 * This manage bookmark on playable items
 */
class Bookmark extends database_object
{
    protected const DB_TABLENAME = 'bookmark';

    // Public variables
    public $id;
    public $user;
    public $object_id;
    public $object_type;
    public $position;
    public $comment;
    public $creation_date;
    public $update_date;

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

    public function getId(): int
    {
        return (int) $this->id;
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
     * @param integer $userId
     * @param integer $updateDate
     * @return PDOStatement|boolean
     */
    public static function create(array $data, int $userId, int $updateDate)
    {
        $sql = "INSERT INTO `bookmark` (`user`, `position`, `comment`, `object_type`, `object_id`, `creation_date`, `update_date`) VALUES (?, ?, ?, ?, ?, ?, ?)";

        return Dba::write($sql, array($userId, $data['position'], scrub_in($data['comment']), $data['object_type'], $data['object_id'], time(), $updateDate));
    }

    /**
     * edit
     * @param array $data
     * @param integer $userId
     * @param integer $updateDate
     * @return PDOStatement|boolean
     */
    public static function edit($data, int $userId, int $updateDate)
    {
        $sql      = "UPDATE `bookmark` SET `position` = ?, `update_date` = ? WHERE `user` = ? AND `comment` = ? AND `object_type` = ? AND `object_id` = ?";

        return Dba::write($sql, array($data['position'], $updateDate, $userId, scrub_in($data['comment']),  $data['object_type'], $data['object_id']));
    }

    public function getUserName(): string
    {
        $user = new User($this->user);

        return $user->username;
    }
}
