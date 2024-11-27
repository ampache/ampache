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

namespace Ampache\Module\System\Update\Migration\V7;

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Add `total_skip` to podcast table
 */
final class Migration710005 extends AbstractMigration
{
    protected array $changelog = [
        'Add `album_disk` to the song table',
        'Fill the `album_disk` column in the song table',
        'Create an `album_disk` index on the song table',
    ];

    protected bool $warning = true;

    public function migrate(): void
    {
        // create total_skip column in album table
        if (!Dba::read('SELECT `album_disk` FROM `song` LIMIT 1;', [], true)) {
            $this->updateDatabase("ALTER TABLE `song` ADD COLUMN `album_disk` int(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `album`;");
        }

        $this->updateDatabase("UPDATE `song`, (SELECT `id`, `album_id`, `disk`, `disksubtitle` FROM `album_disk`) AS `album_disk` SET `song`.`album_disk` = `album_disk`.`id` WHERE `song`.`album_disk` != `album_disk`.`id` AND `song`.`album` = `album_disk`.`album_id` AND `song`.`disk` = `album_disk`.`disk`;");

        Dba::write("ALTER TABLE `song` DROP KEY `album_disk_IDX`;", [], true);
        $this->updateDatabase("CREATE INDEX `album_disk_IDX` USING BTREE ON `song` (`album_disk`);");
    }
}
