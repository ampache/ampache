<?php

declare(strict_types=1);

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
 */

namespace Ampache\Module\System\Update\Migration\V7;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Update\Migration\AbstractMigration;

final class Migration700004 extends AbstractMigration
{
    protected array $changelog = ['Drop and recreate `tmp_browse` to allow InnoDB conversion'];

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = ($charset == 'utf8mb4') ? 'InnoDB' : 'MYISAM';

        $this->updateDatabase('DROP TABLE IF EXISTS `tmp_browse`;');
        $this->updateDatabase(
            sprintf(
                'CREATE TABLE `tmp_browse` (`id` int(13) NOT NULL AUTO_INCREMENT, `sid` varchar(128) NOT NULL, `data` longtext NOT NULL, `object_data` longtext DEFAULT NULL, PRIMARY KEY (`id`), KEY `tmp_browse_id_sid_IDX` (`sid`, `id`) USING BTREE) ENGINE=%s DEFAULT CHARSET=%s COLLATE=%s ;',
                $engine,
                $charset,
                $collation
            )
        );
    }
}
