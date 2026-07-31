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

use Ampache\Module\System\Dba;
use Ampache\Module\System\Update\Migration\AbstractMigration;

/**
 * Add a maintained `last_played` column to every table that carries a play counter
 *
 * The date OpenSubsonic asks for spans three history tables, so `Stats::count()` stores it as it counts the play.
 */
final class Migration800029 extends AbstractMigration
{
    /**
     * The tables `Stats::count()` updates when a play is recorded.
     *
     * @var list<string>
     */
    private const array PLAY_TABLES = [
        'album',
        'album_disk',
        'artist',
        'podcast',
        'podcast_episode',
        'song',
        'video',
    ];

    protected array $changelog = ['Add a maintained `last_played` column to the tables carrying a play counter'];

    public function migrate(): void
    {
        foreach (self::PLAY_TABLES as $table) {
            // A partly-applied migration re-runs from the top, so the column and its key are only added when absent.
            if (!Dba::has_column($table, 'last_played')) {
                $this->updateDatabase(
                    sprintf('ALTER TABLE `%s` ADD COLUMN `last_played` int(11) UNSIGNED DEFAULT NULL;', $table)
                );
                $this->updateDatabase(
                    sprintf('ALTER TABLE `%1$s` ADD KEY `%1$s_last_played_IDX` (`last_played`);', $table)
                );
            }

            $this->backfill($table);
        }
    }

    /**
     * Seed the column from the play history that already exists.
     *
     * One guarded UPDATE per source; the `last_played <` predicate makes each pass idempotent.
     */
    private function backfill(string $table): void
    {
        foreach (['object_count', 'object_count_archive'] as $source) {
            $this->updateDatabase(
                sprintf(
                    'UPDATE `%1$s` INNER JOIN (SELECT `object_id`, MAX(`date`) AS `played` FROM `%2$s` WHERE `object_type` = \'%1$s\' AND `count_type` = \'stream\' GROUP BY `object_id`) AS `history` ON `history`.`object_id` = `%1$s`.`id` SET `%1$s`.`last_played` = `history`.`played` WHERE `%1$s`.`last_played` IS NULL OR `%1$s`.`last_played` < `history`.`played`;',
                    $table,
                    $source
                )
            );
        }

        // The summary rows carry a range, so `date_to` is the latest play they still account for
        $this->updateDatabase(
            sprintf(
                'UPDATE `%1$s` INNER JOIN (SELECT `object_id`, MAX(`date_to`) AS `played` FROM `object_count_summary` WHERE `object_type` = \'%1$s\' AND `count_type` = \'stream\' GROUP BY `object_id`) AS `history` ON `history`.`object_id` = `%1$s`.`id` SET `%1$s`.`last_played` = `history`.`played` WHERE `%1$s`.`last_played` IS NULL OR `%1$s`.`last_played` < `history`.`played`;',
                $table
            )
        );
    }
}
