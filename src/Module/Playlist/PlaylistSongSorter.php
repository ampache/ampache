<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

namespace Ampache\Module\Playlist;

use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Playlist;

final class PlaylistSongSorter implements PlaylistSongSorterInterface
{
    /**
     * Sort the tracks and save the new position
     */
    public function sort(Playlist $playlist): void
    {
        /* First get all of the songs in order of their tracks */
        $sql = "SELECT `list`.`id` FROM `playlist_data` AS `list` LEFT JOIN `song` ON `list`.`object_id` = `song`.`id` LEFT JOIN `album` ON `song`.`album` = `album`.`id` LEFT JOIN `artist` ON `album`.`album_artist` = `artist`.`id` WHERE `list`.`playlist` = ? ORDER BY `artist`.`name` ASC, `album`.`name` ASC, `album`.`year` ASC, `album`.`disk` ASC, `song`.`track` ASC, `song`.`title` ASC, `song`.`track` ASC";


        $count      = 1;
        $db_results = Dba::query($sql, array($playlist->getId()));
        $results    = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $new_data          = array();
            $new_data['id']    = $row['id'];
            $new_data['track'] = $count;
            $results[]         = $new_data;
            $count++;
        } // end while results
        if (!empty($results)) {
            $sql = "INSERT INTO `playlist_data` (`id`, `track`) VALUES ";
            foreach ($results as $data) {
                $sql .= "(" . Dba::escape($data['id']) . ", " . Dba::escape($data['track']) . "), ";
            } // foreach re-ordered results

            //replace the last comma
            $sql = substr_replace($sql, "", -2);
            $sql .= "ON DUPLICATE KEY UPDATE `track`=VALUES(`track`)";

            // do this in one go
            Dba::write($sql);
        }
        $playlist->update_last_update();
    }
}
