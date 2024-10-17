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

use Ampache\Module\System\Update\Migration\AbstractMigration;
use Generator;

/**
 * We need a place to store actual playlist data for downloadable
 * playlist files.
 */
final class Migration360011 extends AbstractMigration
{
    protected array $changelog = ['Add table to store stream session playlist'];

    public function migrate(): void
    {
        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `stream_playlist` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `sid` varchar(64) NOT NULL, `url` text NOT NULL, `info_url` text DEFAULT NULL, `image_url` text DEFAULT NULL, `title` varchar(255) DEFAULT NULL, `author` varchar(255) DEFAULT NULL, `album` varchar(255) DEFAULT NULL, `type` varchar(255) DEFAULT NULL, `time` smallint(5) DEFAULT NULL, PRIMARY KEY (`id`), KEY `sid` (`sid`));");
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine,
        int $build
    ): Generator {
        if ($build > 360011) {
            yield 'stream_playlist' => "CREATE TABLE IF NOT EXISTS `stream_playlist` (`id` int(11) unsigned NOT NULL AUTO_INCREMENT, `sid` varchar(64) NOT NULL, `url` text NOT NULL, `info_url` text DEFAULT NULL, `image_url` text DEFAULT NULL, `title` varchar(255) DEFAULT NULL, `author` varchar(255) DEFAULT NULL, `album` varchar(255) DEFAULT NULL, `type` varchar(255) DEFAULT NULL, `time` smallint(5) DEFAULT NULL, PRIMARY KEY (`id`), KEY `sid` (`sid`));";
        }
    }
}
