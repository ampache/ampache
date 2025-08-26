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

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Ampache\Repository\Model\Preference;

/**
 * Add `song_map` table for mapping song id's to external data sources
 */
final class Migration770001 extends AbstractMigration
{
    protected array $changelog = ['Add `song_map` table for mapping song id\'s to external data sources'];

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = (AmpConfig::get('database_engine', 'InnoDB'));

        // create the table
        $this->updateDatabase("CREATE TABLE IF NOT EXISTS `song_map` (`song_id` int(11) UNSIGNED NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `object_type` varchar(16) CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT NULL, UNIQUE KEY `unique_song_map` (`object_id`, `object_type`, `song_id`), INDEX `object_id_index` (`object_id`), INDEX `song_id_type_index` (`song_id`, `object_type`), INDEX `object_id_type_index` (`object_id`, `object_type`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;");

    }
}
