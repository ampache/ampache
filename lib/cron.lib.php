<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * set_cron_date
 * Record when the cron has finished.
 */
function set_cron_date()
{
    $name       = Dba::escape('cron_date');
    $update_sql = "REPLACE INTO `update_info` " .
                  "SET `key`='$name', `value`=UNIX_TIMESTAMP()";
    Dba::write($update_sql);
} // set_cron_date

/**
 * get_cron_date
 * This returns the date cron has finished.
 * @return integer
 */
function get_cron_date()
{
    $name = Dba::escape('cron_date');

    $sql        = "SELECT * FROM `update_info` WHERE `key` = ?";
    $db_results = Dba::read($sql, array($name));

    if ($results = Dba::fetch_assoc($db_results)) {
        return $results['value'];
    }

    return 0;
} // get_cron_date
