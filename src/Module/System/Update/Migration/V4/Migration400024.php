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
 * Add song_count, album_count and album_group_count to artist
 */
final class Migration400024 extends AbstractMigration
{
    protected array $changelog = [
        '**IMPORTANT UPDATE NOTES** These columns will fill dynamically in the web UI but you should do a catalog \'add\' as soon as possible to fill them. It will take a while for large libraries but will help API and SubSonic clients',
        'Add \'song_count\', \'album_count\' and \'album_group_count\' to artist.',
    ];

    protected bool $warning = true;

    public function migrate(): void
    {
        Dba::write("ALTER TABLE `artist` DROP COLUMN `song_count`;");
        $this->updateDatabase("ALTER TABLE `artist` ADD COLUMN `song_count` smallint(5) unsigned DEFAULT 0 NULL;");
        Dba::write("ALTER TABLE `artist` DROP COLUMN `album_count`;");
        $this->updateDatabase("ALTER TABLE `artist` ADD COLUMN `album_count` smallint(5) unsigned DEFAULT 0 NULL;");
        Dba::write("ALTER TABLE `artist` DROP COLUMN `album_group_count`;");
        $this->updateDatabase("ALTER TABLE `artist` ADD COLUMN `album_group_count` smallint(5) unsigned DEFAULT 0 NULL;");
    }
}
