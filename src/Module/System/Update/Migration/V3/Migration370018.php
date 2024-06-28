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

namespace Ampache\Module\System\Update\Migration\V3;

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Generator;

/**
 * Enhance tag persistent merge reference.
 */
final class Migration370018 extends AbstractMigration
{
    protected array $changelog = ['Enhance tag persistent merge reference'];

    public function migrate(): void
    {
        $this->updateDatabase("DROP TABLE IF EXISTS `tag_merge`;");
        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `tag_merge` (`tag_id` int(11) NOT NULL, `merged_to` int(11) NOT NULL, PRIMARY KEY (`tag_id`,`merged_to`), KEY `merged_to` (`merged_to`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;");

        $sql        = "DESCRIBE `tag`";
        $db_results = Dba::read($sql);
        $is_hidden  = false;
        while ($row = Dba::fetch_assoc($db_results)) {
            if ($row['Field'] == 'merged_to') {
                $this->updateDatabase("INSERT INTO `tag_merge` (`tag_id`, `merged_to`) SELECT `tag`.`id`, `tag`.`merged_to` FROM `tag` WHERE `merged_to` IS NOT NULL");

                // don't drop until you've confirmed a merge
                $this->updateDatabase("ALTER TABLE `tag` DROP COLUMN `merged_to`");
            }
            if ($row['Field'] == 'is_hidden') {
                $is_hidden = true;
            }
        }
        if (!$is_hidden) {
            $this->updateDatabase("ALTER TABLE `tag` ADD COLUMN `is_hidden` TINYINT(1) NOT NULL DEFAULT 0");
        }
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine
    ): Generator {
        yield 'tag_merge' => "CREATE TABLE IF NOT EXISTS `tag_merge` (`tag_id` int(11) NOT NULL, `merged_to` int(11) NOT NULL, PRIMARY KEY (`tag_id`, `merged_to`), KEY `merged_to` (`merged_to`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
    }
}
