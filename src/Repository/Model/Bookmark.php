<?php

/**
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

use Ampache\Config\AmpConfig;
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
    public int $id = 0;
    public int $user;
    public int $position;
    public ?string $comment;
    public ?string $object_type;
    public int $object_id;
    public int $creation_date;
    public int $update_date;

    /**
     * Constructor
     * This is run every time a new object is created, and requires
     * the id and type of object that we need to pull for
     * @param int|null $object_id
     * @param string $object_type
     * @param int $user_id
     */
    public function __construct($object_id = 0, $object_type = null, $user_id = null)
    {
        if (!$object_id) {
            return;
        }

        if ($object_type === null) {
            $info = $this->get_info($object_id, static::DB_TABLENAME);
        } else {
            if ($user_id === null) {
                $user    = Core::get_global('user');
                $user_id = $user->id ?? 0;
            }
            if ($user_id === 0) {
                return;
            }

            $sql        = "SELECT * FROM `bookmark` WHERE `object_type` = ? AND `object_id` = ? AND `user` = ?";
            $db_results = Dba::read($sql, array($object_type, $object_id, $user_id));

            if (!$db_results) {
                return;
            }

            $info = Dba::fetch_assoc($db_results);
        }
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }
    }

    public function getId(): int
    {
        return (int)($this->id ?? 0);
    }

    /**
     * getBookmarks
     * @param array{
     *  object_type: string,
     *  object_id: int,
     *  comment: null|string,
     *  user: int
     * } $data
     * @return list<int>
     */
    public static function getBookmarks(array $data): array
    {
        $bookmarks = array();
        if ($data['object_type'] !== 'bookmark') {
            $comment_sql = (!empty($data['comment'])) ? "AND `comment` = '" . scrub_in($data['comment']) . "'" : "";
            $sql         = "SELECT `id` FROM `bookmark` WHERE `user` = ? AND `object_type` = ? AND `object_id` = ? " . $comment_sql . ' ORDER BY `update_date` DESC;';
            $db_results  = Dba::read($sql, array($data['user'], $data['object_type'], $data['object_id']));
        } else {
            // bookmarks are per user
            $sql        = "SELECT `id` FROM `bookmark` WHERE `user` = ? AND `id` = ?;";
            $db_results = Dba::read($sql, array($data['user'], $data['object_id']));
        }
        while ($results = Dba::fetch_assoc($db_results)) {
            $bookmarks[] = (int) $results['id'];
        }

        return $bookmarks;
    }

    /**
     * create
     * @param array{
     *  comment: null|string,
     *  object_type: string,
     *  object_id: int,
     *  position: int
     * } $data
     * @param int $userId
     * @param int $updateDate
     * @return PDOStatement|bool
     */
    public static function create(array $data, int $userId, int $updateDate)
    {
        $comment = scrub_in((string) $data['comment']);
        if (AmpConfig::get('bookmark_latest', false)) {
            // delete duplicates first
            $sql = "DELETE FROM `bookmark` WHERE `user` = ? AND `comment` = ? AND `object_type` = ? AND `object_id` = ?;";
            Dba::write($sql, array($userId, $comment, $data['object_type'], $data['object_id']));
        }

        //insert the new bookmark
        $sql = "INSERT INTO `bookmark` (`user`, `position`, `comment`, `object_type`, `object_id`, `creation_date`, `update_date`) VALUES (?, ?, ?, ?, ?, ?, ?)";

        return Dba::write($sql, array($userId, $data['position'], $comment, $data['object_type'], $data['object_id'], $updateDate, $updateDate));
    }

    /**
     * edit
     * @param int $bookmarkId
     * @param array{
     *  position: int,
     *  comment: null|string
     * } $data
     * @param int $updateDate
     * @return PDOStatement|bool
     */
    public static function edit(int $bookmarkId, array $data, int $updateDate)
    {
        $sql = "UPDATE `bookmark` SET `position` = ?, `comment` = ?, `update_date` = ? WHERE `id` = ?";

        return Dba::write($sql, array($data['position'], scrub_in((string) $data['comment']), $updateDate, $bookmarkId));
    }

    /**
     * Migrate an object associate stats to a new object
     * @param string $object_type
     * @param int $old_object_id
     * @param int $new_object_id
     * @return PDOStatement|bool
     */
    public static function migrate($object_type, $old_object_id, $new_object_id)
    {
        $sql = "UPDATE IGNORE `bookmark` SET `object_id` = ? WHERE `object_id` = ? AND `object_type` = ?;";

        return Dba::write($sql, array($new_object_id, $old_object_id, ucfirst($object_type)));
    }

    public function getUserName(): string
    {
        return User::get_username($this->user);
    }
}
