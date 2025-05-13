<?php

declare(strict_types=0);

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
 *
 */

namespace Ampache\Module\Playlist\Search;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Search;

final class AlbumDiskSearch implements SearchInterface
{
    /**
     * Handles the generation of the SQL for albumDisk searches.
     * @param Search $search
     * @return array{
     *     base: string,
     *     join: array<string, bool>,
     *     where: string[],
     *     where_sql: string,
     *     table: array<string, string>,
     *     table_sql: string,
     *     group_sql: string,
     *     having_sql: string,
     *     parameters: array<int, mixed>,
     * }
     */
    public function getSql(
        Search $search
    ): array {
        $search_user_id     = $search->search_user->getId();
        $sql_logic_operator = $search->logic_operator;
        $catalog_disable    = AmpConfig::get('catalog_disable');
        $catalog_filter     = AmpConfig::get('catalog_filter');

        $where      = [];
        $table      = [];
        $join       = [];
        $group      = [];
        $having     = [];
        $parameters = [];

        foreach ($search->rules as $rule) {
            $type     = $search->get_rule_type($rule[0]);
            $operator = [];
            if ($type === null) {
                continue;
            }
            foreach ($search->basetypes[$type] as $baseOperator) {
                if ($baseOperator['name'] == $rule[1]) {
                    $operator = $baseOperator;
                    break;
                }
            }
            $input        = $search->filter_data((string)$rule[2], $type, $operator);
            $operator_sql = $operator['sql'] ?? '';
            $group[]      = "`album_disk`.`id`";
            $group[]      = "`album`.`name`";
            switch ($rule[0]) {
                case 'title':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "(NOT (`album`.`name` SOUNDS LIKE ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) SOUNDS LIKE ?))";
                    } else {
                        $where[] = "(`album`.`name` $operator_sql ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) $operator_sql ?)";
                    }
                    $parameters = array_merge($parameters, [$input, $input]);
                    break;
                case 'release_type':
                case 'release_status':
                case 'barcode':
                case 'catalog_number':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "NOT (`album`.`" . $rule[0] . "` SOUNDS LIKE ?)";
                    } else {
                        $where[] = "`album`.`" . $rule[0] . "` $operator_sql ?";
                    }
                    $parameters[] = $input;
                    break;
                case 'catalog':
                case 'id':
                case 'year':
                case 'version':
                    $where[]      = "`album`.`" . $rule[0] . "` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'original_year':
                    $where[]    = "(`album`.`original_year` $operator_sql ? OR (`album`.`original_year` IS NULL AND `album`.`year` $operator_sql ?))";
                    $parameters = array_merge($parameters, [$input, $input]);
                    break;
                case 'time':
                    $input        = ((int)$input) * 60;
                    $where[]      = "`album_disk`.`time` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'rating':
                    // average ratings only
                    $where[]          = "IFNULL(`average_rating`.`avg`, 0) $operator_sql ?";
                    $parameters[]     = $input;
                    $table['average'] = "LEFT JOIN (SELECT `object_id`, ROUND(AVG(IFNULL(`rating`.`rating`,0))) AS `avg` FROM `rating` WHERE `rating`.`object_type`='album' GROUP BY `object_id`) AS `average_rating` ON `average_rating`.`object_id` = `album`.`id` ";
                    break;
                case 'favorite':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "NOT ((`album`.`name` SOUNDS LIKE ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) SOUNDS LIKE ?) AND `favorite_album_" . $search_user_id . "`.`user` = " . $search_user_id . " AND `favorite_album_" . $search_user_id . "`.`object_type` = 'album')";
                    } else {
                        $where[] = "(`album`.`name` $operator_sql ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) $operator_sql ?) AND `favorite_album_" . $search_user_id . "`.`user` = " . $search_user_id . " AND `favorite_album_" . $search_user_id . "`.`object_type` = 'album'";
                    }
                    $parameters = array_merge($parameters, [$input, $input]);
                    // flag once per user
                    if (!array_key_exists('favorite', $table)) {
                        $table['favorite'] = '';
                    }
                    $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_album_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = " . $search_user_id . ") AS `favorite_album_" . $search_user_id . "` ON `album`.`id` = `favorite_album_" . $search_user_id . "`.`object_id` AND `favorite_album_" . $search_user_id . "`.`object_type` = 'album'"
                        : "";
                    break;
                case 'myrating':
                case 'albumrating':
                case 'artistrating':
                    $albumString = (AmpConfig::get('album_group'))
                        ? 'album'
                        : 'album_disk';
                    // combine these as they all do the same thing just different tables
                    $looking = str_replace('rating', '', $rule[0]);
                    $column  = ($looking == 'my' || $looking == 'album') ? '`' . $albumString . '`.`id`' : '`album_map`.`object_id`';
                    $my_type = ($looking == 'my' || $looking == 'album') ? $albumString : $looking;
                    if ($input == 0 && $operator_sql == '>=') {
                        break;
                    }
                    if ($input == 0 && $operator_sql == '<') {
                        $input        = -1;
                        $operator_sql = '<=>';
                    }
                    if ($input == 0 && $operator_sql == '<>') {
                        $input        = 1;
                        $operator_sql = '>=';
                    }
                    $where[]      = "IFNULL(`rating_" . $my_type . "_" . $search_user_id . "`.`rating`, 0) $operator_sql ?";
                    $parameters[] = $input;
                    // rating once per user
                    if (!array_key_exists('rating', $table)) {
                        $table['rating'] = '';
                    }
                    $table['rating'] .= (!strpos((string) $table['rating'], "rating_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `rating` FROM `rating` WHERE `user` = " . $search_user_id . " AND `object_type` = '$my_type') AS `rating_" . $my_type . "_" . $search_user_id . "` ON `rating_" . $my_type . "_" . $search_user_id . "`.`object_id` = $column"
                        : "";
                    if ($my_type == 'artist') {
                        $join['album_map'] = true;
                    }
                    break;
                case 'songrating':
                    if ($input == 0 && $operator_sql == '>=') {
                        break;
                    }
                    if ($input == 0 && $operator_sql == '<') {
                        $input        = -1;
                        $operator_sql = '<=>';
                    }
                    if ($input == 0 && $operator_sql == '<>') {
                        $input        = 1;
                        $operator_sql = '>=';
                    }
                    if (($input == 0 && $operator_sql != '>') || ($input == 1 && $operator_sql == '<')) {
                        $where[] = "`album`.`id` IN (SELECT `id` FROM `album` WHERE `id` IN (SELECT `album` FROM `song` WHERE `id` NOT IN (SELECT `object_id` FROM `rating` WHERE `user` = " . $search_user_id . " AND `object_type`='song')))";
                    } elseif (in_array($operator_sql, ['<>', '<', '<=', '!='])) {
                        $where[]      = "`album`.`id` IN (SELECT `id` FROM `album` WHERE `id` IN (SELECT `album` FROM `song` WHERE `id` IN (SELECT `object_id` FROM `rating` WHERE `user` = " . $search_user_id . " AND `object_type`='song' AND `rating` $operator_sql ?))) OR `album`.`id` NOT IN (SELECT `id` FROM `album` WHERE `id` IN (SELECT `album` FROM `song` WHERE `id` IN (SELECT `object_id` FROM `rating` WHERE `user` = " . $search_user_id . " AND `object_type`='song')))";
                        $parameters[] = $input;
                    } else {
                        $where[]      = "`album`.`id` IN (SELECT `id` FROM `album` WHERE `id` IN (SELECT `album` FROM `song` WHERE `id` IN (SELECT `object_id` FROM `rating` WHERE `user` = " . $search_user_id . " AND `object_type`='song' AND `rating` $operator_sql ?)))";
                        $parameters[] = $input;
                    }
                    break;
                case 'myplayed':
                case 'myplayedartist':
                    // combine these as they all do the same thing just different tables
                    $looking      = str_replace('myplayed', '', $rule[0]);
                    $column       = ($looking == 'artist') ? 'album_artist' : 'id';
                    $my_type      = ($looking == 'artist') ? 'artist' : 'album';
                    $operator_sql = ((int)$operator_sql == 0) ? 'IS NULL' : 'IS NOT NULL';
                    // played once per user
                    if (!array_key_exists('myplayed', $table)) {
                        $table['myplayed'] = '';
                    }
                    $table['myplayed'] .= (!strpos((string) $table['myplayed'], "myplayed_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `myplayed_" . $my_type . "_" . $search_user_id . "` ON `album`.`$column` = `myplayed_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `myplayed_" . $my_type . "_" . $search_user_id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[] = "`myplayed_" . $my_type . "_" . $search_user_id . "`.`object_id` $operator_sql";
                    break;
                case 'played':
                    $column       = 'id';
                    $my_type      = 'album';
                    $operator_sql = ((int)$operator_sql == 0) ? 'IS NULL' : 'IS NOT NULL';
                    // played once per user
                    if (!array_key_exists('played', $table)) {
                        $table['played'] = '';
                    }
                    $table['played'] .= (!strpos((string) $table['played'], "played_" . $my_type))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' GROUP BY `object_id`, `object_type`, `user`) AS `played_" . $my_type . "` ON `album`.`$column` = `played_" . $my_type . "`.`object_id` AND `played_" . $my_type . "`.`object_type` = '$my_type'"
                        : "";
                    $where[] = "`played_" . $my_type . "`.`object_id` $operator_sql";
                    break;
                case 'last_play':
                    $my_type = 'album';
                    if (!array_key_exists('last_play', $table)) {
                        $table['last_play'] = '';
                    }
                    $table['last_play'] .= (!strpos((string) $table['last_play'], "last_play_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `last_play_" . $my_type . "_" . $search_user_id . "` ON `album`.`id` = `last_play_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `last_play_" . $my_type . "_" . $search_user_id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[]      = "`last_play_" . $my_type . "_" . $search_user_id . "`.`date` $operator_sql (UNIX_TIMESTAMP() - (? * 86400))";
                    $parameters[] = $input;
                    break;
                case 'last_skip':
                    $my_type = 'album';
                    if (!array_key_exists('last_skip', $table)) {
                        $table['last_skip'] = '';
                    }
                    $table['last_skip'] .= (!strpos((string) $table['last_skip'], "last_skip_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'skip' AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `last_skip_" . $my_type . "_" . $search_user_id . "` ON `song`.`id` = `last_skip_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `last_skip_" . $my_type . "_" . $search_user_id . "`.`object_type` = 'song'"
                        : "";
                    $where[]      = "`last_skip_" . $my_type . "_" . $search_user_id . "`.`date` $operator_sql (UNIX_TIMESTAMP() - (? * 86400))";
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'last_play_or_skip':
                    $my_type = 'album';
                    if (!array_key_exists('last_play_or_skip', $table)) {
                        $table['last_play_or_skip'] = '';
                    }
                    $table['last_play_or_skip'] .= (!strpos((string) $table['last_play_or_skip'], "last_play_or_skip_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` IN ('stream', 'skip') AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `last_play_or_skip_" . $my_type . "_" . $search_user_id . "` ON `song`.`id` = `last_play_or_skip_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `last_play_or_skip_" . $my_type . "_" . $search_user_id . "`.`object_type` = 'song'"
                        : "";
                    $where[]      = "`last_play_or_skip_" . $my_type . "_" . $search_user_id . "`.`date` $operator_sql (UNIX_TIMESTAMP() - (? * 86400))";
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'played_times':
                    $where[]      = "`album_disk`.`total_count` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'skipped_times':
                    $where[]      = "(`album_disk`.`total_skip` $operator_sql ?)";
                    $parameters[] = $input;
                    break;
                case 'played_or_skipped_times':
                    $where[]      = "((`album_disk`.`total_count` + `album_disk`.`total_skip`) $operator_sql ?)";
                    $parameters[] = $input;
                    break;
                case 'myplayed_times':
                    $my_type = 'album';
                    if (!array_key_exists('myplayed', $table)) {
                        $table['myplayed'] = '';
                    }
                    $table['myplayed'] .= (!strpos((string) $table['myplayed'], "myplayed_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, COUNT(`object_id`) AS `total` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `myplayed_" . $my_type . "_" . $search_user_id . "` ON `album`.`id` = `myplayed_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `myplayed_" . $my_type . "_" . $search_user_id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[]      = "`myplayed_" . $my_type . "_" . $search_user_id . "`.`total` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'myskipped_times':
                    $my_type = 'album';
                    if (!array_key_exists('myskipped', $table)) {
                        $table['myskipped'] = '';
                    }
                    $table['myskipped'] .= (!strpos((string) $table['myskipped'], "myskipped_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, COUNT(`object_id`) AS `total` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'skip' AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `myskipped_" . $my_type . "_" . $search_user_id . "` ON `song`.`id` = `myskipped_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `myskipped_" . $my_type . "_" . $search_user_id . "`.`object_type` = 'song'"
                        : "";
                    $where[]      = "`myskipped_" . $my_type . "_" . $search_user_id . "`.`total` $operator_sql ?";
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'myplayed_or_skipped_times':
                    $my_type = 'album';
                    if (!array_key_exists('myplayed_or_skip', $table)) {
                        $table['myplayed_or_skip'] = '';
                    }
                    $table['myplayed_or_skip'] .= (!strpos((string) $table['myplayed_or_skip'], "myplayed_or_skip_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, COUNT(`object_id`) AS `total` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` IN ('stream', 'skip') AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `myplayed_or_skip_" . $my_type . "_" . $search_user_id . "` ON `song`.`id` = `myplayed_or_skip_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `myplayed_or_skip_" . $my_type . "_" . $search_user_id . "`.`object_type` = 'song'"
                        : "";
                    $where[]      = "`myplayed_or_skip_" . $my_type . "_" . $search_user_id . "`.`total` $operator_sql ?";
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'disk_count':
                    $where[]      = "`album_disk`.`disk_count` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'song_count':
                    $where[]      = "`album_disk`.`song_count` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'other_user':
                    $other_userid = $input;
                    if ($operator_sql == 'userflag') {
                        $where[] = "`favorite_album_$other_userid`.`user` = $other_userid AND `favorite_album_$other_userid`.`object_type` = 'album'";
                        // flag once per user
                        if (!array_key_exists('favorite', $table)) {
                            $table['favorite'] = '';
                        }
                        $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_album_$other_userid"))
                            ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = $other_userid) AS `favorite_album_$other_userid` ON `album`.`id` = `favorite_album_$other_userid`.`object_id` AND `favorite_album_$other_userid`.`object_type` = 'album'"
                            : "";
                    } else {
                        $column  = 'id';
                        $my_type = 'album';
                        $where[] = "`rating_album_" . $other_userid . '`.' . $operator_sql . " AND `rating_album_$other_userid`.`user` = $other_userid AND `rating_album_$other_userid`.`object_type` = 'album'";
                        // rating once per user
                        if (!array_key_exists('rating', $table)) {
                            $table['rating'] = '';
                        }
                        $table['rating'] .= (!strpos((string) $table['rating'], "rating_" . $my_type . "_" . $search_user_id))
                            ? "LEFT JOIN `rating` AS `rating_" . $my_type . "_" . $search_user_id . "` ON `rating_" . $my_type . "_" . $search_user_id . "`.`object_type` = '$my_type' AND `rating_" . $my_type . "_" . $search_user_id . "`.`object_id` = `$my_type`.`$column` AND `rating_" . $my_type . "_" . $search_user_id . "`.`user` = " . $search_user_id
                            : "";
                    }
                    break;
                case 'recent_played':
                    $key                     = md5($input . $operator_sql);
                    $where[]                 = "`played_$key`.`object_id` IS NOT NULL";
                    $table['played_' . $key] = "LEFT JOIN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'album' ORDER BY $operator_sql DESC LIMIT " . (int)$input . ") AS `played_$key` ON `album`.`id` = `played_$key`.`object_id`";
                    break;
                case 'recent_added':
                    $key                       = md5($input . $operator_sql);
                    $where[]                   = "`addition_time_$key`.`id` IS NOT NULL";
                    $table['addition_' . $key] = "LEFT JOIN (SELECT `id` FROM `album` ORDER BY $operator_sql DESC LIMIT " . (int)$input . ") AS `addition_time_$key` ON `album`.`id` = `addition_time_$key`.`id`";
                    break;
                case 'genre':
                    $where[] = ($operator_sql == "NOT LIKE")
                        ? "`album`.`id` NOT IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` LIKE ? WHERE `tag_map`.`object_type`='album' AND `tag`.`id` IS NOT NULL)"
                        : "`album`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` $operator_sql ? WHERE `tag_map`.`object_type`='album' AND `tag`.`id` IS NOT NULL)";
                    $parameters[] = $input;
                    break;
                case 'genre_count_album':
                    $where[]       = "`album`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`album` $operator_sql ? WHERE `tag_map`.`object_type`='album' AND `tag`.`id` IS NOT NULL)";
                    $parameters[]  = $input;
                    break;
                case 'genre_count_artist':
                    $where[]           = "`artist`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`artist` $operator_sql ? WHERE `tag_map`.`object_type`='artist' AND `tag`.`id` IS NOT NULL)";
                    $parameters[]      = $input;
                    $join['album_map'] = true;
                    break;
                case 'genre_count_song':
                    $where[]      = "`song`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`song` $operator_sql ? WHERE `tag_map`.`object_type`='song' AND `tag`.`id` IS NOT NULL)";
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'no_genre':
                    $where[] = "`album`.`id` NOT IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 WHERE `tag_map`.`object_type`='album' AND `tag`.`id` IS NOT NULL)";
                    break;
                case 'song_genre':
                    $where[] = ($operator_sql == "NOT LIKE")
                        ? "`song`.`id` NOT IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` LIKE ? WHERE `tag_map`.`object_type`='song' AND `tag`.`id` IS NOT NULL)"
                        : "`song`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` $operator_sql ? WHERE `tag_map`.`object_type`='song' AND `tag`.`id` IS NOT NULL)";
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'playlist_name':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "NOT (`album`.`id` IN (SELECT `song`.`album` FROM `playlist_data` LEFT JOIN `playlist` ON `playlist_data`.`playlist` = `playlist`.`id` LEFT JOIN `song` ON `song`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type` = 'song' WHERE `playlist`.`name` SOUNDS LIKE ?))";
                    } else {
                        $where[] = "`album`.`id` IN (SELECT `song`.`album` FROM `playlist_data` LEFT JOIN `playlist` ON `playlist_data`.`playlist` = `playlist`.`id` LEFT JOIN `song` ON `song`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type` = 'song' WHERE `playlist`.`name` $operator_sql ?)";
                    }
                    $parameters[] = $input;
                    break;
                case 'playlist':
                    $where[]      = "`album`.`id` $operator_sql IN (SELECT `song`.`album` FROM `playlist_data` LEFT JOIN `song` ON `song`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type` = 'song' WHERE `playlist_data`.`playlist` = ?)";
                    $parameters[] = $input;
                    break;
                case 'file':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "NOT (`song`.`file` SOUNDS LIKE ?)";
                    } else {
                        $where[] = "`song`.`file` $operator_sql ?";
                    }
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'has_image':
                    $where[]            = ($operator_sql == '1') ? "`has_image`.`object_id` IS NOT NULL" : "`has_image`.`object_id` IS NULL";
                    $table['has_image'] = "LEFT JOIN (SELECT `object_id` FROM `image` WHERE `object_type` = 'album') AS `has_image` ON `album`.`id` = `has_image`.`object_id`";
                    break;
                case 'image_height':
                case 'image_width':
                    $looking       = str_replace('image_', '', $rule[0]);
                    $where[]       = "`image`.`$looking` $operator_sql ?";
                    $parameters[]  = $input;
                    $join['image'] = true;
                    break;
                case 'artist':
                case 'album_artist':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "(NOT ((`artist`.`name` SOUNDS LIKE ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) SOUNDS LIKE ?) AND `album_map`.`object_type` = 'album'))";
                    } else {
                        $where[] = "((`artist`.`name` $operator_sql ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) $operator_sql ?) AND `album_map`.`object_type` = 'album')";
                    }
                    $parameters        = array_merge($parameters, [$input, $input]);
                    $join['album_map'] = true;
                    break;
                case 'song':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "NOT (`song`.`title` SOUNDS LIKE ?)";
                    } else {
                        $where[] = "`song`.`title` $operator_sql ?";
                    }
                    $parameters   = array_merge($parameters, [$input]);
                    $join['song'] = true;
                    break;
                case 'song_artist':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "(NOT ((`artist`.`name` SOUNDS LIKE ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) SOUNDS LIKE ?) AND `album_map`.`object_type` = 'song'))";
                    } else {
                        $where[] = "((`artist`.`name` $operator_sql ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) $operator_sql ?) AND `album_map`.`object_type` = 'song')";
                    }
                    $parameters        = array_merge($parameters, [$input, $input]);
                    $join['album_map'] = true;
                    break;
                case 'mbid':
                    if (!$input || $input == '%%' || $input == '%') {
                        if (in_array($operator_sql, ['=', 'LIKE', 'SOUNDS LIKE'])) {
                            $where[] = "`album`.`mbid` IS NULL";
                            break;
                        }
                        if (in_array($operator_sql, ['!=', 'NOT LIKE', 'NOT SOUNDS LIKE'])) {
                            $where[] = "`album`.`mbid` IS NOT NULL";
                            break;
                        }
                    }
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "NOT (`album`.`mbid` SOUNDS LIKE ?)";
                    } else {
                        $where[] = "`album`.`mbid` $operator_sql ?";
                    }
                    $parameters[] = $input;
                    break;
                case 'mbid_song':
                    if (!$input || $input == '%%' || $input == '%') {
                        if (in_array($operator_sql, ['=', 'LIKE', 'SOUNDS LIKE'])) {
                            $where[] = "`song`.`mbid` IS NULL";
                            break;
                        }
                        if (in_array($operator_sql, ['!=', 'NOT LIKE', 'NOT SOUNDS LIKE'])) {
                            $where[] = "`song`.`mbid` IS NOT NULL";
                            break;
                        }
                    }
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "NOT (`song`.`mbid` SOUNDS LIKE ?)";
                    } else {
                        $where[] = "`song`.`mbid` $operator_sql ?";
                    }
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'mbid_artist':
                    if (!$input || $input == '%%' || $input == '%') {
                        if (in_array($operator_sql, ['=', 'LIKE', 'SOUNDS LIKE'])) {
                            $where[] = "`artist`.`mbid` IS NULL";
                            break;
                        }
                        if (in_array($operator_sql, ['!=', 'NOT LIKE', 'NOT SOUNDS LIKE'])) {
                            $where[] = "`artist`.`mbid` IS NOT NULL";
                            break;
                        }
                    }
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "NOT (`artist`.`mbid` SOUNDS LIKE ?)";
                    } else {
                        $where[] = "`artist`.`mbid` $operator_sql ?";
                    }
                    $parameters[]      = $input;
                    $join['album_map'] = true;
                    break;
                case 'possible_duplicate':
                    $where[]               = "(`dupe_search1`.`dupe_id1` IS NOT NULL OR `dupe_search2`.`dupe_id2` IS NOT NULL)";
                    $table['dupe_search1'] = "LEFT JOIN (SELECT MIN(`id`) AS `dupe_id1`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) AS `fullname`, COUNT(LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`))) AS `Counting` FROM `album` GROUP BY `album_artist`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `album`.`year`, `album`.`release_type`, `album`.`release_status` HAVING `Counting` > 1) AS `dupe_search1` ON `album`.`id` = `dupe_search1`.`dupe_id1`";
                    $table['dupe_search2'] = "LEFT JOIN (SELECT MAX(`id`) AS `dupe_id2`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) AS `fullname`, COUNT(LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`))) AS `Counting` FROM `album` GROUP BY `album_artist`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `album`.`year`, `album`.`release_type`, `album`.`release_status` HAVING `Counting` > 1) AS `dupe_search2` ON `album`.`id` = `dupe_search2`.`dupe_id2`";
                    break;
                case 'duplicate_mbid_group':
                    $where[] = "`mbid_group` IN (SELECT `mbid_group` FROM `album` GROUP BY `album`.`mbid_group` HAVING COUNT(`mbid_group`) > 1)";
                    break;
                default:
                    debug_event(self::class, 'ERROR! rule not found: ' . $rule[0], 3);
                    break;
            } // switch on ruletype album
        } // foreach rule

        $join['song']        = array_key_exists('song', $join);
        $join['catalog']     = $catalog_disable || $catalog_filter;
        $join['catalog_map'] = $catalog_filter;

        $where_sql = implode(" $sql_logic_operator ", $where);

        // always join the album table
        $table['0_album'] = "LEFT JOIN `album` ON `album`.`id` = `album_disk`.`album_id`";

        if (array_key_exists('album_map', $join)) {
            $table['0_album_map'] = "LEFT JOIN `album_map` ON `album`.`id` = `album_map`.`album_id`";
            $table['artist']      = "LEFT JOIN `artist` ON `artist`.`id` = `album_map`.`object_id`";
        }
        if ($join['song']) {
            $table['0_song'] = "LEFT JOIN `song` ON `song`.`album` = `album`.`id`";
        }
        if ($join['catalog']) {
            $table['2_catalog_map'] = "LEFT JOIN `catalog_map` AS `catalog_map_album` ON `catalog_map_album`.`object_type` = 'album' AND `catalog_map_album`.`object_id` = `album`.`id`";
            $table['3_catalog']     = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id` = `catalog_map_album`.`catalog_id`";
            if (!empty($where_sql)) {
                $where_sql = "(" . $where_sql . ") AND `catalog_se`.`enabled` = '1'";
            } else {
                $where_sql = "`catalog_se`.`enabled` = '1'";
            }
        }
        if ($join['catalog_map']) {
            if (!empty($where_sql)) {
                $where_sql = ($search_user_id > 0)
                    ? "(" . $where_sql . ") AND `catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = " . $search_user_id . " AND `catalog_filter_group_map`.`enabled`=1)"
                    : "(" . $where_sql . ") AND `catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1)";
            } else {
                $where_sql = ($search_user_id > 0)
                    ? "`catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = " . $search_user_id . " AND `catalog_filter_group_map`.`enabled`=1)"
                    : "`catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1)";
            }
        }

        if (array_key_exists('image', $join)) {
            $table['0_song'] = "LEFT JOIN `song` ON `song`.`album` = `album`.`id` LEFT JOIN `image` ON `image`.`object_id` = `album`.`id`";
            $where_sql       = "(" . $where_sql . ") AND `image`.`object_type`='album' AND `image`.`size`='original'";
        }
        ksort($table);
        $table_sql  = implode(' ', $table);
        $group_sql  = implode(',', $group);
        $having_sql = implode(" $sql_logic_operator ", $having);

        return [
            'base' => 'SELECT `album_disk`.`id` AS `id`, `album`.`name` FROM `album_disk`',
            'join' => $join,
            'where' => $where,
            'where_sql' => $where_sql,
            'table' => $table,
            'table_sql' => $table_sql,
            'group_sql' => $group_sql,
            'having_sql' => $having_sql,
            'parameters' => $parameters,
        ];
    }
}
