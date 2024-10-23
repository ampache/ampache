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

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Index album tables. `catalog`, `album_artist`, `original_year`, `release_type`, `release_status`, `mbid`, `mbid_group`
 */
final class Migration540001 extends AbstractMigration
{
    protected array $changelog = [
        'Index `album` table columns',
        '`catalog`, `album_artist`, `original_year`, `release_type`, `release_status`, `mbid`, `mbid_group`',
    ];

    public function migrate(): void
    {
        Dba::write("ALTER TABLE `album` DROP KEY `catalog_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `catalog_IDX` USING BTREE ON `album` (`catalog`);");

        Dba::write("ALTER TABLE `album` DROP KEY `album_artist_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `album_artist_IDX` USING BTREE ON `album` (`album_artist`);");

        Dba::write("ALTER TABLE `album` DROP KEY `original_year_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `original_year_IDX` USING BTREE ON `album` (`original_year`);");

        Dba::write("ALTER TABLE `album` DROP KEY `release_type_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `release_type_IDX` USING BTREE ON `album` (`release_type`);");

        Dba::write("ALTER TABLE `album` DROP KEY `release_status_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `release_status_IDX` USING BTREE ON `album` (`release_status`);");

        Dba::write("ALTER TABLE `album` DROP KEY `mbid_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `mbid_IDX` USING BTREE ON `album` (`mbid`);");

        Dba::write("ALTER TABLE `album` DROP KEY `mbid_group_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `mbid_group_IDX` USING BTREE ON `album` (`mbid_group`);");
    }
}
