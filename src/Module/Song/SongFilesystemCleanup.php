<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

declare(strict_types=0);

namespace Ampache\Module\Song;

use Ampache\Module\System\Dba;

final class SongFilesystemCleanup implements SongFilesystemCleanupInterface
{
    /**
     * @param bool $dryRun
     * @return string[] List of files which will be/got deleted
     */
    public function cleanup(bool $dryRun = true): array
    {
        /* Get a list of filenames */
        $sql        = "SELECT `id`, `file` FROM `song` WHERE `enabled` = 0";
        $db_results = Dba::read($sql);
        $result     = [];

        while ($row = Dba::fetch_assoc($db_results)) {
            $result[] = $row['file'];
            if ($dryRun === false) {
                $sql = 'DELETE FROM `song` WHERE `id` = ?;';

                Dba::write($sql, array($row['id']));

                if (file_exists($row['file'])) {
                    unlink($row['file']);
                }
            }
        }

        return $result;
    }
}
