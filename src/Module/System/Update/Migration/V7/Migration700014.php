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
 * Add `disksubtitle` to `song_data` and `album_disk` table
 */
final class Migration700014 extends AbstractMigration
{
    protected array $changelog = ['Add `name` to `user_preference` table'];

    public function migrate(): void
    {
        if (!Dba::read('SELECT `name` FROM `user_preference` LIMIT 1;', [], true)) {
            $this->updateDatabase('ALTER TABLE `user_preference` ADD COLUMN `name` varchar(128) DEFAULT NULL AFTER `preference`;');
        }
        $this->updateDatabase('UPDATE `user_preference`, (SELECT `preference`.`name`, `preference`.`id` FROM `preference`) AS `preference` SET `user_preference`.`name` = `preference`.`name` WHERE `preference`.`id` = `user_preference`.`preference`;');

    }
}
