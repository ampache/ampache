<?php

declare(strict_types=0);

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

namespace Ampache\Module\Util;

use Ampache\Module\System\Dba;

final class Cron
{
    /**
     * set_cron_date
     * Record when the cron has finished.
     */
    public static function set_cron_date(): void
    {
        Dba::write(
            sprintf(
                'REPLACE INTO `update_info` SET `key`= \'%s\', `value`=UNIX_TIMESTAMP()',
                Dba::escape('cron_date')
            )
        );
    }

    /**
     * get_cron_date
     * This returns the date cron has finished.
     */
    public static function get_cron_date(): int
    {
        $name = Dba::escape('cron_date');

        $db_results = Dba::read(
            'SELECT `key`, `value` FROM `update_info` WHERE `key` = ?',
            [$name]
        );

        if ($results = Dba::fetch_assoc($db_results)) {
            return (int) $results['value'];
        }

        return 0;
    }
}
