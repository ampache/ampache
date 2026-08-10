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
 * Create the `playlist_folder` and `playlist_folder_map` tables
 *
 * A private tree per user for organising playlists, smartlists and collections. Placement belongs to the
 * (user, list) pair rather than to the list, so filing another user's public playlist affects nobody else.
 */
final class Migration800045 extends AbstractMigration
{
    protected array $changelog = ['Create `playlist_folder` and `playlist_folder_map` tables for organising lists into a per-user tree'];

    public function getTableMigrations(
        string $collation,
        string $charset,
        string $engine,
        int $build,
    ): Generator {
        yield from parent::getTableMigrations($collation, $charset, $engine, $build);

        if ($build > 800045) {
            yield 'playlist_folder' => $this->playlistFolderTableSql($collation, $charset, $engine);
            yield 'playlist_folder_map' => $this->playlistFolderMapTableSql($collation, $charset, $engine);
        }
    }

    public function migrate(): void
    {
        $collation = (AmpConfig::get('database_collation', 'utf8mb4_unicode_ci'));
        $charset   = (AmpConfig::get('database_charset', 'utf8mb4'));
        $engine    = (AmpConfig::get('database_engine', 'InnoDB'));

        // A partly-applied migration re-runs from the top, so every statement has to survive a second pass.
        $this->updateDatabase($this->playlistFolderTableSql($collation, $charset, $engine));
        $this->updateDatabase($this->playlistFolderMapTableSql($collation, $charset, $engine));
    }

    /**
     * One row per (user, list): a list belongs to exactly one of that user's folders, and moving it is an update
     *
     * `folder` is 0 rather than NULL at the root so it can be compared and indexed like any other parent.
     */
    private function playlistFolderMapTableSql(string $collation, string $charset, string $engine): string
    {
        return "CREATE TABLE IF NOT EXISTS `playlist_folder_map` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `user` int(11) UNSIGNED NOT NULL, `folder` int(11) UNSIGNED NOT NULL DEFAULT 0, `object_id` int(11) UNSIGNED NOT NULL, `object_type` varchar(16) NOT NULL, `sort_order` int(11) NOT NULL DEFAULT 0, PRIMARY KEY (`id`), UNIQUE KEY `unique_playlist_folder_map` (`user`,`object_type`,`object_id`), KEY `user_folder_IDX` (`user`,`folder`,`sort_order`), KEY `object_type_id_IDX` (`object_type`,`object_id`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
    }

    /**
     * `parent` is 0 at the root rather than NULL, because a unique key does not constrain NULL
     *
     * A nullable parent would allow two root folders with the same name, which makes a path ambiguous.
     */
    private function playlistFolderTableSql(string $collation, string $charset, string $engine): string
    {
        return "CREATE TABLE IF NOT EXISTS `playlist_folder` (`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, `user` int(11) UNSIGNED NOT NULL, `parent` int(11) UNSIGNED NOT NULL DEFAULT 0, `name` varchar(255) CHARACTER SET $charset COLLATE $collation NOT NULL, `sort_order` int(11) NOT NULL DEFAULT 0, `date` int(11) UNSIGNED NOT NULL DEFAULT 0, `last_update` int(11) UNSIGNED NOT NULL DEFAULT 0, PRIMARY KEY (`id`), UNIQUE KEY `unique_playlist_folder` (`user`,`parent`,`name`), KEY `user_parent_IDX` (`user`,`parent`,`sort_order`)) ENGINE=$engine DEFAULT CHARSET=$charset COLLATE=$collation;";
    }
}
