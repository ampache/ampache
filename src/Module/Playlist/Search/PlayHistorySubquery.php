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

namespace Ampache\Module\Playlist\Search;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigurationKeyEnum;

/**
 * Builds the `object_count` play-history subqueries used by the search/smartlist play rules.
 *
 * When play-history consolidation is enabled (`stats_consolidate_threshold` > 0) the detail rows
 * older than the retention window are moved out of `object_count` into `object_count_summary`.
 * These helpers transparently merge the summary back in so per-user play counts, last-play dates
 * and "played by me" style rules keep seeing the full history. The config gate is the single point
 * that decides which tables a rule reads: when consolidation is off the summary is empty and unused,
 * and the emitted SQL is byte-identical to the pre-consolidation form (no extra join, no overhead).
 *
 * Note: `recent_played` cannot be served this way - it needs the individual play timestamps that
 * consolidation discards (the summary keeps only min/max), so it is intentionally not covered here.
 */
final class PlayHistorySubquery
{
    /**
     * COUNT of plays grouped per object/user. Columns: object_id, object_type, user, total.
     *
     * @param list<string> $countTypes
     */
    public static function count(string $type, array $countTypes, ?int $userId): string
    {
        $oc = "SELECT `object_id`, `object_type`, `user`, COUNT(`object_id`) AS `total` FROM `object_count` WHERE `object_count`.`object_type` = '" . $type . "' AND " . self::countTypeSql('object_count', $countTypes) . self::userSql('object_count', $userId) . " GROUP BY `object_id`, `object_type`, `user`";
        if (!self::usesSummary()) {
            return '(' . $oc . ')';
        }

        $summary = "SELECT `object_id`, `object_type`, `user`, `count` AS `total` FROM `object_count_summary` WHERE `object_count_summary`.`object_type` = '" . $type . "' AND " . self::countTypeSql('object_count_summary', $countTypes) . self::userSql('object_count_summary', $userId);

        return "(SELECT `object_id`, `object_type`, `user`, SUM(`total`) AS `total` FROM (" . $oc . " UNION ALL " . $summary . ") AS `combined` GROUP BY `object_id`, `object_type`, `user`)";
    }

    /**
     * Existence of a play grouped per object/user. Columns: object_id, object_type, user.
     *
     * @param list<string> $countTypes
     */
    public static function exists(string $type, array $countTypes, ?int $userId): string
    {
        $oc = "SELECT `object_id`, `object_type`, `user` FROM `object_count` WHERE `object_count`.`object_type` = '" . $type . "' AND " . self::countTypeSql('object_count', $countTypes) . self::userSql('object_count', $userId) . " GROUP BY `object_id`, `object_type`, `user`";
        if (!self::usesSummary()) {
            return '(' . $oc . ')';
        }

        $summary = "SELECT `object_id`, `object_type`, `user` FROM `object_count_summary` WHERE `object_count_summary`.`object_type` = '" . $type . "' AND " . self::countTypeSql('object_count_summary', $countTypes) . self::userSql('object_count_summary', $userId);

        return "(SELECT `object_id`, `object_type`, `user` FROM (" . $oc . " UNION ALL " . $summary . ") AS `combined` GROUP BY `object_id`, `object_type`, `user`)";
    }

    /**
     * Existence of a 'stream' play resolved to its artist through `artist_map`. Columns: artist_id, user.
     * $songsOnly restricts the source to 'song' plays (the global "Played" artist rule); otherwise any
     * object type mapped to the artist counts (the per-user "Played by Me" artist rule). $userId null = global.
     */
    public static function existsViaArtistMap(?int $userId, bool $songsOnly): string
    {
        if (!self::usesSummary()) {
            $typeFilter = $songsOnly ? "`object_count`.`object_type` = 'song' AND " : '';

            return "(SELECT DISTINCT `artist_map`.`artist_id`, `object_count`.`user` FROM `object_count` LEFT JOIN `artist_map` ON `object_count`.`object_type` = `artist_map`.`object_type` AND `artist_map`.`object_id` = `object_count`.`object_id` WHERE " . $typeFilter . "`object_count`.`count_type` = 'stream'" . (($userId === null) ? '' : " AND `object_count`.`user` = " . $userId) . " GROUP BY `artist_map`.`artist_id`, `user`)";
        }

        $filter = ($songsOnly ? "`object_type` = 'song' AND " : '') . "`count_type` = 'stream'" . (($userId === null) ? '' : " AND `user` = " . $userId);
        $source = "SELECT `object_type`, `object_id`, `user` FROM `object_count` WHERE " . $filter . " UNION ALL SELECT `object_type`, `object_id`, `user` FROM `object_count_summary` WHERE " . $filter;

        return "(SELECT DISTINCT `artist_map`.`artist_id`, `plays`.`user` FROM (" . $source . ") AS `plays` LEFT JOIN `artist_map` ON `plays`.`object_type` = `artist_map`.`object_type` AND `artist_map`.`object_id` = `plays`.`object_id` GROUP BY `artist_map`.`artist_id`, `plays`.`user`)";
    }

    /**
     * MAX play date grouped per object/user. Columns: object_id, object_type, user, date.
     * The summary's `date_to` is the newest date it consolidated, so GREATEST(detail, summary) is exact.
     *
     * @param list<string> $countTypes
     */
    public static function lastDate(string $type, array $countTypes, ?int $userId): string
    {
        $oc = "SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = '" . $type . "' AND " . self::countTypeSql('object_count', $countTypes) . self::userSql('object_count', $userId) . " GROUP BY `object_id`, `object_type`, `user`";
        if (!self::usesSummary()) {
            return '(' . $oc . ')';
        }

        $summary = "SELECT `object_id`, `object_type`, `user`, MAX(`date_to`) AS `date` FROM `object_count_summary` WHERE `object_count_summary`.`object_type` = '" . $type . "' AND " . self::countTypeSql('object_count_summary', $countTypes) . self::userSql('object_count_summary', $userId) . " GROUP BY `object_id`, `object_type`, `user`";

        return "(SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM (" . $oc . " UNION ALL " . $summary . ") AS `combined` GROUP BY `object_id`, `object_type`, `user`)";
    }

    /**
     * The consolidation gate: the summary table only holds data while this threshold is set, so it
     * is the flag for whether the play-history rules read `object_count` alone or both tables.
     */
    public static function usesSummary(): bool
    {
        return (int) AmpConfig::get(ConfigurationKeyEnum::STATS_CONSOLIDATE_THRESHOLD, 0) > 0;
    }

    /**
     * @param list<string> $countTypes
     */
    private static function countTypeSql(string $table, array $countTypes): string
    {
        return (count($countTypes) === 1)
            ? "`" . $table . "`.`count_type` = '" . $countTypes[0] . "'"
            : "`" . $table . "`.`count_type` IN ('" . implode("', '", $countTypes) . "')";
    }

    private static function userSql(string $table, ?int $userId): string
    {
        return ($userId === null)
            ? ''
            : " AND `" . $table . "`.`user` = " . $userId;
    }
}
