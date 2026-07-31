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
 * Create the `collection` and `collection_map` tables
 *
 * A curated list that, unlike `playlist_data`, is not restricted to playable types. A nullable `object_type`
 * means mixed, a set value pins the collection to one type.
 */
final class Migration800028 extends AbstractMigration
{
    /**
     * Tables whose `object_type` still carries the legacy `folder`/`tvshow` members.
     *
     * @var list<string>
     */
    private const array COUNTED_TABLES = ['image', 'object_count', 'cache_object_count', 'cache_object_count_run'];

    /**
     * The play-history consolidation tables, added after `folder` and never given those legacy members.
     *
     * @var list<string>
     */
    private const array SUMMARY_TABLES = ['object_count_summary', 'object_count_archive'];

    protected array $changelog = ['Create `collection` and `collection_map` tables for curating lists of any object type'];

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine,
        int $build,
    ): Generator {
        yield from parent::getTableMigrations($collation, $charset, $engine, $build);

        if ($build > 800028) {
            yield 'collection' => $this->collectionTableSql($collation, $charset, $engine);
            yield 'collection_map' => $this->collectionMapTableSql($collation, $charset, $engine);
        }
    }

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = (AmpConfig::get('database_engine', 'InnoDB'));

        // A partly-applied migration re-runs from the top, so every statement has to survive a second pass.
        $this->updateDatabase($this->collectionTableSql($collation, $charset, $engine));
        $this->updateDatabase($this->collectionMapTableSql($collation, $charset, $engine));

        // Re-stating the whole enum matches the earlier `folder` migration and keeps MODIFY idempotent
        foreach (self::COUNTED_TABLES as $table) {
            $this->updateDatabase(
                sprintf(
                    "ALTER TABLE `%s` MODIFY COLUMN `object_type` enum('album', 'album_disk', 'artist', 'catalog', 'collection', 'folder', 'tag', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'search', 'song', 'tvshow', 'tvshow_season', 'user', 'video') CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL;",
                    $table
                )
            );
        }

        // The consolidation tables were created later and never carried the legacy `folder`/`tvshow` members.
        foreach (self::SUMMARY_TABLES as $table) {
            $this->updateDatabase(
                sprintf(
                    "ALTER TABLE `%s` MODIFY COLUMN `object_type` enum('album', 'album_disk', 'artist', 'catalog', 'collection', 'tag', 'label', 'live_stream', 'playlist', 'podcast', 'podcast_episode', 'search', 'song', 'user', 'video') NOT NULL;",
                    $table
                )
            );
        }
    }

    private function collectionMapTableSql(string $collation, string $charset, string $engine): string
    {
        // The unique key is what makes `collection_add` idempotent rather than duplicating a member.
        return "CREATE TABLE IF NOT EXISTS `collection_map` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `collection` int(11) UNSIGNED NOT NULL DEFAULT 0, `object_id` int(11) UNSIGNED NOT NULL DEFAULT 0, `object_type` varchar(16) NOT NULL, `track` int(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`id`), UNIQUE KEY `unique_collection_map` (`collection`,`object_type`,`object_id`), KEY `collection_track_IDX` (`collection`,`track`), KEY `object_type_id_IDX` (`object_type`,`object_id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
    }

    private function collectionTableSql(string $collation, string $charset, string $engine): string
    {
        return "CREATE TABLE IF NOT EXISTS `collection` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `name` varchar(255) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `user` int(11) DEFAULT NULL, `username` varchar(128) CHARACTER SET $charset COLLATE $collation DEFAULT NULL, `type` enum('private','public') CHARACTER SET $charset COLLATE $collation DEFAULT 'private', `object_type` varchar(16) DEFAULT NULL, `date` int(11) UNSIGNED NOT NULL DEFAULT 0, `last_update` int(11) UNSIGNED NOT NULL DEFAULT 0, `last_count` int(11) DEFAULT NULL, `collaborate` varchar(255) DEFAULT NULL, PRIMARY KEY (`id`), KEY `name` (`name`), KEY `type` (`type`), KEY `user` (`user`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
    }
}
