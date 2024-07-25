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
 * This changes the tmp_browse table around.
 */
final class Migration360005 extends AbstractMigration
{
    protected array $changelog = ['Modify tmp_browse to allow caching of multiple browses per session'];

    public function migrate(): void
    {
        $this->updateDatabase("DROP TABLE IF EXISTS `tmp_browse`");
        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `tmp_browse` (`id` int(13) NOT NULL auto_increment, `sid` varchar(128) CHARACTER SET utf8 NOT NULL, `data` longtext NOT NULL, `object_data` longtext, PRIMARY KEY (`sid`, `id`)) ENGINE=MYISAM DEFAULT CHARSET=utf8;");
    }

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine,
        int $build
    ): Generator {
        if ($build > 360005) {
            yield 'tmp_browse' => "CREATE TABLE IF NOT EXISTS `tmp_browse` (`id` int(13) NOT NULL AUTO_INCREMENT, `sid` varchar(128) COLLATE utf8_unicode_ci NOT NULL, `data` longtext COLLATE utf8_unicode_ci NOT NULL, `object_data` longtext COLLATE utf8_unicode_ci DEFAULT NULL, PRIMARY KEY (`sid`, `id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        }
    }
}
