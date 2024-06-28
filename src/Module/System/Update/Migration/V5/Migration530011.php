<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\System\Update\Migration\V5;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Compact `user` columns and enum `object_count`.`count_type`
 */
final class Migration530011 extends AbstractMigration
{
    protected array $changelog = [
        'Compact some `user` columns',
        'enum `object_count`.`count_type`'
    ];

    public function migrate(): void
    {
        $collation  = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset    = (AmpConfig::get('database_charset', 'utf8mb4'));
        $rsstoken   = false;
        $sql        = "DESCRIBE `user`";
        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            if ($row['Field'] == 'rsstoken') {
                $rsstoken = true;
            }
        }
        if (!$rsstoken) {
            $this->updateDatabase("ALTER TABLE `user` ADD COLUMN `rsstoken` varchar(255) NULL;");
        }
        $this->updateDatabase("ALTER TABLE `user` MODIFY COLUMN `rsstoken` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;");

        $this->updateDatabase("ALTER TABLE `user` MODIFY COLUMN `validation` varchar(128) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;");

        $this->updateDatabase("ALTER TABLE `user` MODIFY COLUMN `password` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;");

        $this->updateDatabase("ALTER TABLE `user` MODIFY COLUMN `apikey` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL NULL;");

        $this->updateDatabase("ALTER TABLE `user` MODIFY COLUMN `username` varchar(128) CHARACTER SET $charset COLLATE $collation DEFAULT NULL NULL;");
    }
}
