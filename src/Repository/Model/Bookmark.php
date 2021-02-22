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

    public function getUserName(): string
    {
        $user = new User($this->user);

        return $user->username;
    }
}
