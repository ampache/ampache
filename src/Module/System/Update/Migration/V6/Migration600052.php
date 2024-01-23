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
use Ampache\Repository\Model\Playlist;

final class Migration600052 extends AbstractMigration
{
    protected array $changelog = ['Add unique constraint `playlist_track_UN` on `playlist_data` table'];

    public function migrate(): void
    {
        # fix up duplicate playlist track numbers
        $sql        = 'SELECT DISTINCT `playlist` from `playlist_data` GROUP BY `playlist`, `track` having COUNT(`playlist`) > 1;';
        $db_results = Dba::read($sql);
        // get the base album you will migrate into
        while ($row = Dba::fetch_assoc($db_results)) {
            $playlist = new Playlist($row['playlist']);
            $playlist->regenerate_track_numbers();
        }
        Dba::write("ALTER TABLE `playlist_data` DROP KEY `playlist_track_UN`;");
        $sql = 'ALTER TABLE `playlist_data` ADD CONSTRAINT `playlist_track_UN` UNIQUE KEY (`playlist`,`track`);';
        $this->updateDatabase($sql);
    }
}
