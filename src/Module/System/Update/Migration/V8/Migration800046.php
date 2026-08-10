<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\System\Update\Migration\V8;

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Add `bpm` to the `song_data` table
 *
 * Tagged beats per minute, which OpenSubsonic exposes on a Child and Ampache had no column for. Decimal because
 * detection tools write a fraction (`133.4`) and an integer column would silently drop it on every scan.
 */
final class Migration800046 extends AbstractMigration
{
    protected array $changelog = ['Add `bpm` to `song_data` table'];

    public function migrate(): void
    {
        Dba::write('ALTER TABLE `song_data` DROP COLUMN `bpm`;', [], true);
        $this->updateDatabase('ALTER TABLE `song_data` ADD COLUMN `bpm` decimal(6,2) NULL DEFAULT NULL;');
    }
}
