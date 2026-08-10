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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\System\Update\Migration\AbstractMigration;
use Generator;

/**
 * Add the `mood` and `mood_map` tables
 *
 * OpenSubsonic asks for a list of moods on a Child and an AlbumID3 and Ampache had nowhere to keep one.
 *
 * Values are read from the file tags (id3v2 `TMOO`, vorbis/APE `MOOD`), so the tables stay empty until a catalog is scanned.
 */
final class Migration800047 extends AbstractMigration
{
    private const string MOOD_MAP_TABLE = "CREATE TABLE IF NOT EXISTS `mood_map` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `mood_id` int(11) UNSIGNED NOT NULL, `object_id` int(11) UNSIGNED NOT NULL, `object_type` enum('album','album_disk','artist','podcast','podcast_episode','song','video') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL, `user` int(11) NOT NULL, PRIMARY KEY (`id`), UNIQUE KEY `unique_mood_map` (`object_id`,`object_type`,`user`,`mood_id`), KEY `mood_id_index` (`mood_id`)) ENGINE=%s DEFAULT CHARSET=%s COLLATE=%s;";
    private const string MOOD_TABLE     = "CREATE TABLE IF NOT EXISTS `mood` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL, `artist` int(11) UNSIGNED NOT NULL DEFAULT 0, `album` int(11) UNSIGNED NOT NULL DEFAULT 0, `song` int(11) UNSIGNED NOT NULL DEFAULT 0, `video` int(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`id`), UNIQUE KEY `name` (`name`)) ENGINE=%s DEFAULT CHARSET=%s COLLATE=%s;";

    protected array $changelog = [
        'Add `mood` table',
        'Add `mood_map` table',
        'Add `show_mood` preference to show/hide the Moods sidebar link',
    ];

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine,
        int $build,
    ): Generator {
        yield from parent::getTableMigrations($collation, $charset, $engine, $build);

        if ($build > 800047) {
            yield 'mood' => sprintf(self::MOOD_TABLE, $engine, $charset, $collation);
            yield 'mood_map' => sprintf(self::MOOD_MAP_TABLE, $engine, $charset, $collation);
        }
    }

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = (AmpConfig::get('database_engine', 'InnoDB'));

        $this->updateDatabase(sprintf(self::MOOD_TABLE, $engine, $charset, $collation));
        $this->updateDatabase(sprintf(self::MOOD_MAP_TABLE, $engine, $charset, $collation));

        // the same per-user sidebar opt-out `show_folder` and `show_collection` have
        $this->updatePreferences(
            'show_mood',
            'Show \'Moods\' link in the main sidebar',
            '1',
            AccessLevelEnum::USER->value,
            'boolean',
            'interface',
            'sidebar'
        );
    }
}
