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
 */

namespace Ampache\Repository\Model\Metadata\Repository;

use Ampache\Module\System\Dba;
use Ampache\Repository\Repository;
use PDOStatement;

class Metadata extends Repository
{
    protected $modelClassName = \Ampache\Repository\Model\Metadata\Model\Metadata::class;

    public static function garbage_collection()
    {
        Dba::write('DELETE FROM `metadata` USING `metadata` LEFT JOIN `song` ON `song`.`id` = `metadata`.`object_id` WHERE `song`.`id` IS NULL');
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
        $sql = "UPDATE IGNORE `metadata` SET `object_id` = ? WHERE `object_id` = ? AND `type` = ?";

        return Dba::write($sql, array($new_object_id, $old_object_id, ucfirst($object_type)));
    }
}
