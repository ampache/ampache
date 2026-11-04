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

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Generator;

/**
 * Create the `tmp_stream_control` table, so a stream control plugin can cap a source IP without touching `object_count`
 */
final class Migration840001 extends AbstractMigration
{
    protected array $changelog = ['Create `tmp_stream_control` table for the stream control plugins\' per-IP limits'];

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine,
        int $build,
    ): Generator {
        yield from parent::getTableMigrations($collation, $charset, $engine, $build);

        if ($build > 840001) {
            yield 'tmp_stream_control' => $this->tableSql($engine, $charset, $collation);
        }
    }

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = (AmpConfig::get('database_engine', 'InnoDB'));

        $this->updateDatabase($this->tableSql($engine, $charset, $collation));
    }

    private function tableSql(string $engine, string $charset, string $collation): string
    {
        return "CREATE TABLE IF NOT EXISTS `tmp_stream_control` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `ip` varchar(45) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL, `date` int(11) UNSIGNED NOT NULL DEFAULT 0, `hits` int(11) UNSIGNED NOT NULL DEFAULT 0, `size` bigint(20) UNSIGNED NOT NULL DEFAULT 0, `time` int(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`id`), KEY `tmp_stream_control_IDX` (`ip`,`date`), KEY `tmp_stream_control_date_IDX` (`date`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
    }
}
