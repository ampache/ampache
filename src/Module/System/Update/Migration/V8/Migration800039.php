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
 * Drop the folder rows the scanner recorded under a bare directory name instead of its path.
 */
final class Migration800039 extends AbstractMigration
{
    protected array $changelog = [
        'Remove `folder` rows whose `path_name` is a bare directory name; the next catalog scan recreates them with their real path',
    ];

    public function migrate(): void
    {
        // a real path always carries a separator, so a value without one is the basename the scanner used to store
        $broken = "SELECT `id` FROM `folder` WHERE `path_name` IS NULL OR (`path_name` NOT LIKE '%/%' AND `path_name` NOT LIKE '%\\\\\\\\%')";

        $this->updateDatabase(sprintf('DELETE FROM `folder_map` WHERE `folder_id` IN (%s);', $broken));
        $this->updateDatabase(sprintf("DELETE FROM `folder_map` WHERE `object_type` = 'folder' AND `object_id` IN (%s);", $broken));
        $this->updateDatabase(sprintf('DELETE FROM `folder` WHERE `id` IN (SELECT `id` FROM (%s) AS `broken`);', $broken));

        // a folder that lost its parent has to be rebuilt from the top, so the ancestry is cleared with them
        $this->updateDatabase('UPDATE `folder` SET `parent` = NULL, `path` = NULL WHERE `parent` IS NOT NULL AND `parent` NOT IN (SELECT `id` FROM (SELECT `id` FROM `folder`) AS `kept`);');
    }
}
