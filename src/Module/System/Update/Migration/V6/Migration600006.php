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

namespace Ampache\Module\System\Update\Migration\V6;

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Add `disk_count` to album table
 */
final class Migration600006 extends AbstractMigration
{
    protected array $changelog = ['Add `disk_count` to album table'];

    public function migrate(): void
    {
        Dba::write("ALTER TABLE `album` DROP COLUMN `disk_count`;");
        $this->updateDatabase("ALTER TABLE `album` ADD COLUMN `disk_count` int(11) UNSIGNED NOT NULL DEFAULT '0' AFTER `disk`");
    }
}
