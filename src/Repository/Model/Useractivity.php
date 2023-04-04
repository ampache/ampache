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

use Ampache\Module\System\Dba;
use PDOStatement;

class Useractivity extends database_object
{
    protected const DB_TABLENAME = 'user_activity';

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
     * @param integer $useract_id
     */
    public function __construct($useract_id)
    {
        if (!$useract_id) {
            return false;
        }

        $info = $this->get_info($useract_id, 'user_activity');
        foreach ($info as $key => $value) {
            $this->$key = $value;
        }

        return true;
    } // Constructor

    public function getId(): int
    {
        return (int)$this->id;
    }

    /**
     * this attempts to build a cache of the data from the passed activities all in one query
     * @param integer[] $ids
     * @return boolean
     */
    public static function build_cache($ids)
    {
        if (empty($ids)) {
            return false;
        }

        $idlist     = '(' . implode(',', $ids) . ')';
        $sql        = "SELECT * FROM `user_activity` WHERE `id` IN $idlist";
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            parent::add_to_cache('user_activity', $row['id'], $row);
        }

        return true;
    }

    /**
     * Migrate an object associate stats to a new object
     * @param string $object_type
     * @param integer $old_object_id
     * @param integer $new_object_id
     * @return PDOStatement|boolean
     */
    public static function migrate($object_type, $old_object_id, $new_object_id)
    {
        $sql = "UPDATE `user_activity` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";

        return Dba::write($sql, array($new_object_id, $object_type, $old_object_id));
    }
}
