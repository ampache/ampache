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

use Ampache\Module\System\Dba;

/**
 * This is a general object that is extended by all of the basic
 * database based objects in ampache. It attempts to do some standard
 * caching for all of the objects to cut down on the database calls
 */
abstract class database_object
{
    protected const DB_TABLENAME = null;

    /**
     * get_info
     * retrieves the info from the database and puts it in the cache
     * @param integer $object_id
     * @param string $table_name
     * @return array
     */
    public function get_info($object_id, $table_name = '')
    {
        $table     = $this->getTableName($table_name);
        $object_id = (int)$object_id;

        // Make sure we've got a real id
        if ($object_id < 1) {
            return array();
        }

        $params     = array($object_id);
        $sql        = "SELECT * FROM `$table` WHERE `id`= ?";
        $db_results = Dba::read($sql, $params);

        if (!$db_results) {
            return array();
        }

        return Dba::fetch_assoc($db_results);
    } // get_info

    private function getTableName($table_name): string
    {
        if (!$table_name) {
            $table_name = static::DB_TABLENAME;

            if ($table_name === null) {
                $table_name = Dba::escape(strtolower(get_class($this)));
            }
        }

        return Dba::escape($table_name);
    }
}
