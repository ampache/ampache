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

use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Widen `song_preview`.`file` so a signed preview url fits.
 *
 * Preview providers hand back an expiring signed url several hundred characters long, which no longer fits the
 * original varchar(255). Every other file/url column in the schema is already varchar(4096); this brings the last
 * one into line. Nothing for Ampache7 to roll back: a wider column holds everything the narrow one did.
 */
final class Migration800042 extends AbstractMigration
{
    protected array $changelog = [
        'Widen `song_preview`.`file` to varchar(4096) so signed preview urls are not truncated',
    ];

    public function migrate(): void
    {
        $this->updateDatabase('ALTER TABLE `song_preview` MODIFY COLUMN `file` varchar(4096) DEFAULT NULL;');
    }
}
