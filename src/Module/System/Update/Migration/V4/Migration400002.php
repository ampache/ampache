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

namespace Ampache\Module\System\Update\Migration\V4;

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Update disk to allow 1 instead of making it 0 by default
 * Add barcode catalog_number and original_year
 * Drop catalog_number from song_data
 */
final class Migration400002 extends AbstractMigration
{
    protected array $changelog = [
        'IMPORTANT UPDATE NOTES: This is part of a major update to how Ampache handles Albums, Artists and data migration during tag updates',
        'Update album disk support to allow 1 instead of 0 by default',
        'Add barcode catalog_number and original_year to albums',
        'Drop catalog_number from song_data and use album instead'
    ];

    public function migrate(): void
    {
        Dba::write("UPDATE `album` SET `album`.`disk` = 1 WHERE `album`.`disk` = 0;");

        Dba::write("ALTER TABLE `album` DROP COLUMN `original_year`;");
        $this->updateDatabase("ALTER TABLE `album` ADD COLUMN `original_year` INT(4) NULL;");
        Dba::write("ALTER TABLE `album` DROP COLUMN `barcode`;");
        $this->updateDatabase("ALTER TABLE `album` ADD COLUMN `barcode` varchar(64) NULL;");
        Dba::write("ALTER TABLE `album` DROP COLUMN `catalog_number`;");
        $this->updateDatabase("ALTER TABLE `album` ADD COLUMN `catalog_number` varchar(64) NULL;");

        $this->updateDatabase("ALTER TABLE `song_data` DROP COLUMN `catalog_number`");
    }
}
