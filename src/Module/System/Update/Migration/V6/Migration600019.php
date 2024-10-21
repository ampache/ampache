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

namespace Ampache\Module\System\Update\Migration\V6;

use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * During migration some album_disk data may be missing it's object type
 */
final class Migration600019 extends AbstractMigration
{
    protected array $changelog = ['During migration some album_disk data may be missing it\'s object type'];

    public function migrate(): void
    {
        $this->updateDatabase('UPDATE IGNORE `rating` SET `object_type` = \'album_disk\' WHERE `object_type` = \'\';');
        $this->updateDatabase('DELETE FROM `rating` WHERE `object_type` = \'\';');
        $this->updateDatabase('UPDATE IGNORE `object_count` SET `object_type` = \'album_disk\' WHERE `object_type` = \'\';');
        $this->updateDatabase('DELETE FROM `object_count` WHERE `object_type` = \'\';');

        // rating (id, `user`, object_type, object_id, rating)
        $this->updateDatabase('INSERT IGNORE INTO `rating` (`object_type`, `object_id`, `user`, `rating`) SELECT DISTINCT \'album_disk\', `album_disk`.`id`, `rating`.`user`, `rating`.`rating` FROM `rating` LEFT JOIN `album` ON `rating`.`object_type` = \'album\' AND `rating`.`object_id` = `album`.`id` LEFT JOIN `album_disk` ON `album`.`id` = `album_disk`.`album_id` LEFT JOIN `rating` AS `album_rating` ON `album_rating`.`object_type` = \'album\' AND `rating`.`rating` = `album_rating`.`rating` AND `rating`.`user` = `album_rating`.`user` WHERE `rating`.`object_type` = \'album\' AND `album_disk`.`id` IS NOT NULL;');

        // user_flag (id, `user`, object_id, object_type, `date`)
        $this->updateDatabase('INSERT IGNORE INTO `user_flag` (`object_type`, `object_id`, `user`, `date`) SELECT DISTINCT \'album_disk\', `album_disk`.`id`, `user_flag`.`user`, `user_flag`.`date` FROM `user_flag` LEFT JOIN `album` ON `user_flag`.`object_type` = \'album\' AND `user_flag`.`object_id` = `album`.`id` LEFT JOIN `album_disk` ON `album`.`id` = `album_disk`.`album_id` LEFT JOIN `user_flag` AS `album_flag` ON `album_flag`.`object_type` = \'album\' AND `user_flag`.`date` = `album_flag`.`date` AND `user_flag`.`user` = `album_flag`.`user` WHERE `user_flag`.`object_type` = \'album\' AND `album_disk`.`id` IS NOT NULL;');
    }
}
