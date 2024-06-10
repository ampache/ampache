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

namespace Ampache\Module\System\Update\Migration\V5;

use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Make sure the object_count table has all the correct primary artist/album rows
 */
final class Migration530006 extends AbstractMigration
{
    protected array $changelog = ['Make sure `object_count` table has all the correct primary artist/album rows'];

    public function migrate(): void
    {
        $this->updateDatabase("INSERT INTO `object_count` (object_type, object_id, `date`, `user`, agent, geo_latitude, geo_longitude, geo_name, count_type) SELECT 'album', `song`.`album`, `object_count`.`date`, `object_count`.`user`, `object_count`.`agent`, `object_count`.`geo_latitude`, `object_count`.`geo_longitude`, `object_count`.`geo_name`, `object_count`.`count_type` FROM `object_count` LEFT JOIN `song` ON `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' AND `object_count`.`object_id` = `song`.`id` LEFT JOIN `object_count` AS `album_count` ON `album_count`.`object_type` = 'album' AND `object_count`.`date` = `album_count`.`date` AND `object_count`.`user` = `album_count`.`user` AND `object_count`.`agent` = `album_count`.`agent` AND `object_count`.`count_type` = `album_count`.`count_type` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' AND `album_count`.`id` IS NULL AND `song`.`album` IS NOT NULL;");
        $this->updateDatabase("INSERT INTO `object_count` (object_type, object_id, `date`, `user`, agent, geo_latitude, geo_longitude, geo_name, count_type) SELECT 'artist', `song`.`artist`, `object_count`.`date`, `object_count`.`user`, `object_count`.`agent`, `object_count`.`geo_latitude`, `object_count`.`geo_longitude`, `object_count`.`geo_name`, `object_count`.`count_type` FROM `object_count` LEFT JOIN `song` ON `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' AND `object_count`.`object_id` = `song`.`id` LEFT JOIN `object_count` AS `artist_count` ON `artist_count`.`object_type` = 'artist' AND `object_count`.`date` = `artist_count`.`date` AND `object_count`.`user` = `artist_count`.`user` AND `object_count`.`agent` = `artist_count`.`agent` AND `object_count`.`count_type` = `artist_count`.`count_type` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' AND `artist_count`.`id` IS NULL AND `song`.`artist` IS NOT NULL;");
    }
}
