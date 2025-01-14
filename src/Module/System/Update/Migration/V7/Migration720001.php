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

namespace Ampache\Module\System\Update\Migration\V7;

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

final class Migration720001 extends AbstractMigration
{
    protected array $changelog = [
        'Add `artist`, `album`, `song` and `video` counts to the tag table.',
        'Update `object_type` to an enum on the tag_map table.',
    ];

    public function migrate(): void
    {
        if (!Dba::read('SELECT `artist` FROM `tag` LIMIT 1;', [], true)) {
            $this->updateDatabase("ALTER TABLE `tag` ADD COLUMN `artist` int(11) UNSIGNED DEFAULT 0 NOT NULL;");
            $this->updateDatabase("UPDATE `tag`, (SELECT `tag_id`, COUNT(`tag_id`) AS `tag_count` FROM `tag_map` WHERE `object_type` = 'artist' GROUP BY `tag_id`) AS `tag_count` SET `tag`.`artist` = `tag_count`.`tag_count` WHERE `tag`.`artist` != `tag_count`.`tag_count` AND `tag_count`.`tag_id` = `tag`.`id`;");
        }
        if (!Dba::read('SELECT `album` FROM `tag` LIMIT 1;', [], true)) {
            $this->updateDatabase("ALTER TABLE `tag` ADD COLUMN `album` int(11) UNSIGNED DEFAULT 0 NOT NULL;");
            $this->updateDatabase("UPDATE `tag`, (SELECT `tag_id`, COUNT(`tag_id`) AS `tag_count` FROM `tag_map` WHERE `object_type` = 'album' GROUP BY `tag_id`) AS `tag_count` SET `tag`.`album` = `tag_count`.`tag_count` WHERE `tag`.`album` != `tag_count`.`tag_count` AND `tag_count`.`tag_id` = `tag`.`id`;");
        }
        if (!Dba::read('SELECT `song` FROM `tag` LIMIT 1;', [], true)) {
            $this->updateDatabase("ALTER TABLE `tag` ADD COLUMN `song` int(11) UNSIGNED DEFAULT 0 NOT NULL;");
            $this->updateDatabase("UPDATE `tag`, (SELECT `tag_id`, COUNT(`tag_id`) AS `tag_count` FROM `tag_map` WHERE `object_type` = 'song' GROUP BY `tag_id`) AS `tag_count` SET `tag`.`song` = `tag_count`.`tag_count` WHERE `tag`.`song` != `tag_count`.`tag_count` AND `tag_count`.`tag_id` = `tag`.`id`;");
        }
        if (!Dba::read('SELECT `video` FROM `tag` LIMIT 1;', [], true)) {
            $this->updateDatabase("ALTER TABLE `tag` ADD COLUMN `video` int(11) UNSIGNED DEFAULT 0 NOT NULL;");
            $this->updateDatabase("UPDATE `tag`, (SELECT `tag_id`, COUNT(`tag_id`) AS `tag_count` FROM `tag_map` WHERE `object_type` = 'video' GROUP BY `tag_id`) AS `tag_count` SET `tag`.`video` = `tag_count`.`tag_count` WHERE `tag`.`video` != `tag_count`.`tag_count` AND `tag_count`.`tag_id` = `tag`.`id`;");
        }

        $this->updateDatabase("DELETE FROM `tag_map` WHERE `object_type` NOT IN ('album', 'album_disk', 'artist', 'catalog', 'tag', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'search', 'song', 'user', 'video');");
        $this->updateDatabase("ALTER TABLE `tag_map` MODIFY COLUMN `object_type` enum('album', 'album_disk', 'artist', 'catalog', 'tag', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'search', 'song', 'user', 'video') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL;");
    }
}
