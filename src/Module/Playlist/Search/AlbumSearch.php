<?php

declare(strict_types=0);

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
use Ampache\Repository\Model\Search;

final class AlbumSearch implements SearchInterface
{
    /**
     * Handles the generation of the SQL for album searches.
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
        Search $search,
    ): array {
        $search_user_id     = $search->search_user->getId();
        $sql_logic_operator = strtoupper($search->logic_operator ?? 'and');
        $catalog_disable    = AmpConfig::get('catalog_disable');
        $catalog_filter     = AmpConfig::get('catalog_filter');
        $subsearch_count    = 0;

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
            $group[]      = "`album`.`id`";
            switch ($rule[0]) {
                case 'title':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "(NOT (`album`.`name` SOUNDS LIKE ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) SOUNDS LIKE ?))";
                    } else {
                        $where[] = sprintf("(`album`.`name` %s ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) %s ?)", $operator_sql, $operator_sql);
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
                        $where[] = "`album`.`" . $rule[0] . sprintf('` %s ?', $operator_sql);
                    }

                    $parameters[] = $input;
                    break;
                case 'catalog':
                case 'id':
                case 'year':
                case 'version':
                    $where[]      = "`album`.`" . $rule[0] . sprintf('` %s ?', $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'original_year':
                    $where[]    = sprintf('(`album`.`original_year` %s ? OR (`album`.`original_year` IS NULL AND `album`.`year` %s ?))', $operator_sql, $operator_sql);
                    $parameters = array_merge($parameters, [$input, $input]);
                    break;
                case 'time':
                    $input        = ((int)$input) * 60;
                    $where[]      = sprintf('`album`.`time` %s ?', $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'rating':
                    // average ratings only
                    $where[]          = sprintf('IFNULL(`average_rating`.`avg`, 0) %s ?', $operator_sql);
                    $parameters[]     = $input;
                    $table['average'] = "LEFT JOIN (SELECT `object_id`, ROUND(AVG(IFNULL(`rating`.`rating`,0))) AS `avg` FROM `rating` WHERE `rating`.`object_type`='album' GROUP BY `object_id`) AS `average_rating` ON `average_rating`.`object_id` = `album`.`id` ";
                    break;
                case 'favorite':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "NOT ((`album`.`name` SOUNDS LIKE ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) SOUNDS LIKE ?) AND `favorite_album_" . $search_user_id . "`.`user` = " . $search_user_id . " AND `favorite_album_" . $search_user_id . "`.`object_type` = 'album')";
                    } else {
                        $where[] = sprintf("(`album`.`name` %s ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) %s ?) AND `favorite_album_", $operator_sql, $operator_sql) . $search_user_id . "`.`user` = " . $search_user_id . " AND `favorite_album_" . $search_user_id . "`.`object_type` = 'album'";
                    }

                    $parameters = array_merge($parameters, [$input, $input]);
                    // flag once per user
                    if (!array_key_exists('favorite', $table)) {
                        $table['favorite'] = '';
                    }

                    $table['favorite'] .= (strpos($table['favorite'], "favorite_album_" . $search_user_id))
                        ? ""
                        : "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = " . $search_user_id . ") AS `favorite_album_" . $search_user_id . "` ON `album`.`id` = `favorite_album_" . $search_user_id . "`.`object_id` AND `favorite_album_" . $search_user_id . "`.`object_type` = 'album'";
                    break;
                case 'myrating':
                case 'albumrating':
                case 'artistrating':
                    // combine these as they all do the same thing just different tables
                    $looking = str_replace('rating', '', $rule[0]);
                    $column  = ($looking == 'my') ? '`album`.`id`' : '`album_map`.`object_id`';
                    $my_type = ($looking == 'my') ? 'album' : $looking;
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

                    $where[]      = "IFNULL(`rating_" . $my_type . "_" . $search_user_id . sprintf('`.`rating`, 0) %s ?', $operator_sql);
                    $parameters[] = $input;
                    // rating once per user
                    if (!array_key_exists('rating', $table)) {
                        $table['rating'] = '';
                    }

                    $table['rating'] .= (strpos($table['rating'], "rating_" . $my_type . "_" . $search_user_id))
                        ? ""
                        : "LEFT JOIN (SELECT `object_id`, `object_type`, `rating` FROM `rating` WHERE `user` = " . $search_user_id . " AND `object_type` = '" . $my_type . "') AS `rating_" . $my_type . "_" . $search_user_id . "` ON `rating_" . $my_type . "_" . $search_user_id . ('`.`object_id` = ' . $column);
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
                        $where[]      = "`album`.`id` IN (SELECT `id` FROM `album` WHERE `id` IN (SELECT `album` FROM `song` WHERE `id` IN (SELECT `object_id` FROM `rating` WHERE `user` = " . $search_user_id . sprintf(" AND `object_type`='song' AND `rating` %s ?))) OR `album`.`id` NOT IN (SELECT `id` FROM `album` WHERE `id` IN (SELECT `album` FROM `song` WHERE `id` IN (SELECT `object_id` FROM `rating` WHERE `user` = ", $operator_sql) . $search_user_id . " AND `object_type`='song')))";
                        $parameters[] = $input;
                    } else {
                        $where[]      = "`album`.`id` IN (SELECT `id` FROM `album` WHERE `id` IN (SELECT `album` FROM `song` WHERE `id` IN (SELECT `object_id` FROM `rating` WHERE `user` = " . $search_user_id . sprintf(" AND `object_type`='song' AND `rating` %s ?)))", $operator_sql);
                        $parameters[] = $input;
                    }

                    break;
                case 'my_flagged_song':
                case 'my_flagged_album':
                case 'my_flagged_artist':
                    // combine these as they all do the same thing just different tables
                    $looking      = str_replace('my_flagged_', '', $rule[0]);
                    $column       = ($looking == 'album') ? 'id' : $looking;
                    $my_type      = $looking;
                    $operator_sql = ((int) $operator_sql === 0) ? 'IS NULL' : 'IS NOT NULL';
                    // played once per user
                    if (!array_key_exists('my_flagged_', $table)) {
                        $table['my_flagged_'] = '';
                    }

                    $table['my_flagged_'] .= (strpos($table['my_flagged_'], "my_flagged__" . $my_type . "_" . $search_user_id))
                        ? ""
                        : "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user_flag`.`object_type` = '" . $my_type . "' AND `user_flag`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `my_flagged__" . $my_type . "_" . $search_user_id . "` ON `" . $my_type . sprintf('`.`%s` = `my_flagged__', $column) . $my_type . "_" . $search_user_id . "`.`object_id` AND `my_flagged__" . $my_type . "_" . $search_user_id . "`.`object_type` = '" . $my_type . "'";
                    $where[] = "`my_flagged__" . $my_type . "_" . $search_user_id . ('`.`object_id` ' . $operator_sql);
                    if ($my_type == 'song') {
                        $join['song'] = true;
                    }

                    if ($my_type == 'artist') {
                        $join['artist'] = true;
                    }

                    break;
                case 'myplayed':
                case 'myplayedartist':
                    // combine these as they all do the same thing just different tables
                    $looking      = str_replace('myplayed', '', $rule[0]);
                    $column       = ($looking == 'artist') ? 'album_artist' : 'id';
                    $my_type      = ($looking == 'artist') ? 'artist' : 'album';
                    $operator_sql = ((int)$operator_sql === 0) ? 'IS NULL' : 'IS NOT NULL';
                    // played once per user
                    if (!array_key_exists('myplayed', $table)) {
                        $table['myplayed'] = '';
                    }

                    $table['myplayed'] .= (strpos($table['myplayed'], "myplayed_" . $my_type . "_" . $search_user_id))
                        ? ""
                        : "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `object_count` WHERE `object_count`.`object_type` = '" . $my_type . "' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `myplayed_" . $my_type . "_" . $search_user_id . sprintf('` ON `album`.`%s` = `myplayed_', $column) . $my_type . "_" . $search_user_id . "`.`object_id` AND `myplayed_" . $my_type . "_" . $search_user_id . "`.`object_type` = '" . $my_type . "'";
                    $where[] = "`myplayed_" . $my_type . "_" . $search_user_id . ('`.`object_id` ' . $operator_sql);
                    break;
                case 'weight_album':
                case 'weight_artist':
                case 'weight_song':
                    $my_type      = str_replace('weight_', '', $rule[0]);
                    $where[]      = "`" . $my_type . sprintf('`.`weight` %s ?', $operator_sql);
                    $parameters[] = $input;
                    if ($my_type == 'artist') {
                        $join['artist'] = true;
                    }

                    if ($my_type == 'song') {
                        $join['song'] = true;
                    }

                    break;
                case 'played':
                    $column       = 'id';
                    $my_type      = 'album';
                    $operator_sql = ((int)$operator_sql === 0) ? 'IS NULL' : 'IS NOT NULL';
                    // played once per user
                    if (!array_key_exists('played', $table)) {
                        $table['played'] = '';
                    }

                    $table['played'] .= (strpos($table['played'], "played_" . $my_type))
                        ? ""
                        : "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `object_count` WHERE `object_count`.`object_type` = '" . $my_type . "' AND `object_count`.`count_type` = 'stream' GROUP BY `object_id`, `object_type`, `user`) AS `played_" . $my_type . sprintf('` ON `album`.`%s` = `played_', $column) . $my_type . "`.`object_id` AND `played_" . $my_type . "`.`object_type` = '" . $my_type . "'";
                    $where[] = "`played_" . $my_type . ('`.`object_id` ' . $operator_sql);
                    break;
                case 'last_play':
                    $my_type = 'album';
                    if (!array_key_exists('last_play', $table)) {
                        $table['last_play'] = '';
                    }

                    $table['last_play'] .= (strpos($table['last_play'], "last_play_" . $my_type . "_" . $search_user_id))
                        ? ""
                        : "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = '" . $my_type . "' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `last_play_" . $my_type . "_" . $search_user_id . "` ON `album`.`id` = `last_play_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `last_play_" . $my_type . "_" . $search_user_id . "`.`object_type` = '" . $my_type . "'";
                    $where[]      = "`last_play_" . $my_type . "_" . $search_user_id . sprintf('`.`date` %s (UNIX_TIMESTAMP() - (? * 86400))', $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'last_skip':
                    $my_type = 'album';
                    if (!array_key_exists('last_skip', $table)) {
                        $table['last_skip'] = '';
                    }

                    $table['last_skip'] .= (strpos($table['last_skip'], "last_skip_" . $my_type . "_" . $search_user_id))
                        ? ""
                        : "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'skip' AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `last_skip_" . $my_type . "_" . $search_user_id . "` ON `song`.`id` = `last_skip_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `last_skip_" . $my_type . "_" . $search_user_id . "`.`object_type` = 'song'";
                    $where[]      = "`last_skip_" . $my_type . "_" . $search_user_id . sprintf('`.`date` %s (UNIX_TIMESTAMP() - (? * 86400))', $operator_sql);
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'last_play_or_skip':
                    $my_type = 'album';
                    if (!array_key_exists('last_play_or_skip', $table)) {
                        $table['last_play_or_skip'] = '';
                    }

                    $table['last_play_or_skip'] .= (strpos($table['last_play_or_skip'], "last_play_or_skip_" . $my_type . "_" . $search_user_id))
                        ? ""
                        : "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` IN ('stream', 'skip') AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `last_play_or_skip_" . $my_type . "_" . $search_user_id . "` ON `song`.`id` = `last_play_or_skip_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `last_play_or_skip_" . $my_type . "_" . $search_user_id . "`.`object_type` = 'song'";
                    $where[]      = "`last_play_or_skip_" . $my_type . "_" . $search_user_id . sprintf('`.`date` %s (UNIX_TIMESTAMP() - (? * 86400))', $operator_sql);
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'added':
                    $input        = strtotime((string) $input);
                    $where[]      = sprintf('`song`.`addition_time` %s ?', $operator_sql);
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'updated':
                    $input        = strtotime((string) $input);
                    $where[]      = sprintf('`song`.`update_time` %s ?', $operator_sql);
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'days_added':
                    $where[] = sprintf('`album`.`addition_time` %s (UNIX_TIMESTAMP() - (', $operator_sql) . (int)$input . " * 86400))";
                    break;
                case 'played_times':
                    $where[]      = sprintf('`album`.`total_count` %s ?', $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'skipped_times':
                    $where[]      = sprintf('(`album`.`total_skip` %s ?)', $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'played_or_skipped_times':
                    $where[]      = sprintf('((`album`.`total_count` + `album`.`total_skip`) %s ?)', $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'myplayed_times':
                    $my_type = 'album';
                    if (!array_key_exists('myplayed', $table)) {
                        $table['myplayed'] = '';
                    }

                    $table['myplayed'] .= (strpos($table['myplayed'], "myplayed_" . $my_type . "_" . $search_user_id))
                        ? ""
                        : "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, COUNT(`object_id`) AS `total` FROM `object_count` WHERE `object_count`.`object_type` = '" . $my_type . "' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `myplayed_" . $my_type . "_" . $search_user_id . "` ON `album`.`id` = `myplayed_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `myplayed_" . $my_type . "_" . $search_user_id . "`.`object_type` = '" . $my_type . "'";
                    $where[]      = "`myplayed_" . $my_type . "_" . $search_user_id . sprintf('`.`total` %s ?', $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'myskipped_times':
                    $my_type = 'album';
                    if (!array_key_exists('myskipped', $table)) {
                        $table['myskipped'] = '';
                    }

                    $table['myskipped'] .= (strpos($table['myskipped'], "myskipped_" . $my_type . "_" . $search_user_id))
                        ? ""
                        : "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, COUNT(`object_id`) AS `total` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'skip' AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `myskipped_" . $my_type . "_" . $search_user_id . "` ON `song`.`id` = `myskipped_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `myskipped_" . $my_type . "_" . $search_user_id . "`.`object_type` = 'song'";
                    $where[]      = "`myskipped_" . $my_type . "_" . $search_user_id . sprintf('`.`total` %s ?', $operator_sql);
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'myplayed_or_skipped_times':
                    $my_type = 'album';
                    if (!array_key_exists('myplayed_or_skip', $table)) {
                        $table['myplayed_or_skip'] = '';
                    }

                    $table['myplayed_or_skip'] .= (strpos($table['myplayed_or_skip'], "myplayed_or_skip_" . $my_type . "_" . $search_user_id))
                        ? ""
                        : "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, COUNT(`object_id`) AS `total` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` IN ('stream', 'skip') AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `myplayed_or_skip_" . $my_type . "_" . $search_user_id . "` ON `song`.`id` = `myplayed_or_skip_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `myplayed_or_skip_" . $my_type . "_" . $search_user_id . "`.`object_type` = 'song'";
                    $where[]      = "`myplayed_or_skip_" . $my_type . "_" . $search_user_id . sprintf('`.`total` %s ?', $operator_sql);
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'disk_count':
                    $where[]      = sprintf('`album`.`disk_count` %s ?', $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'song_count':
                    $where[]      = sprintf('`album`.`song_count` %s ?', $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'other_user':
                    $other_userid = $input;
                    if ($operator_sql == 'userflag') {
                        $where[] = sprintf("`favorite_album_%s`.`user` = %s AND `favorite_album_%s`.`object_type` = 'album'", $other_userid, $other_userid, $other_userid);
                        // flag once per user
                        if (!array_key_exists('favorite', $table)) {
                            $table['favorite'] = '';
                        }

                        $table['favorite'] .= (strpos($table['favorite'], 'favorite_album_' . $other_userid))
                            ? ""
                            : sprintf("LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = %s) AS `favorite_album_%s` ON `album`.`id` = `favorite_album_%s`.`object_id` AND `favorite_album_%s`.`object_type` = 'album'", $other_userid, $other_userid, $other_userid, $other_userid);
                    } else {
                        $column  = 'id';
                        $my_type = 'album';
                        $unrated = ($operator_sql == 'unrated');
                        $where[] = ($unrated) ? "`" . $my_type . sprintf("`.`%s` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = '", $column) . $my_type . sprintf("' AND `rating`.`user` = %s)", $other_userid) : "`rating_" . $my_type . "_" . $other_userid . sprintf('`.%s AND `rating_', $operator_sql) . $my_type . "_" . $other_userid . sprintf('`.`user` = %s AND `rating_', $other_userid) . $my_type . "_" . $other_userid . "`.`object_type` = '" . $my_type . "'";
                        // rating once per user
                        if (!array_key_exists('rating', $table)) {
                            $table['rating'] = '';
                        }

                        $table['rating'] .= (strpos($table['rating'], "rating_" . $my_type . "_" . $search_user_id))
                            ? ""
                            : "LEFT JOIN `rating` AS `rating_" . $my_type . "_" . $search_user_id . "` ON `rating_" . $my_type . "_" . $search_user_id . "`.`object_type` = '" . $my_type . "' AND `rating_" . $my_type . "_" . $search_user_id . sprintf('`.`object_id` = `%s`.`%s` AND `rating_', $my_type, $column) . $my_type . "_" . $search_user_id . "`.`user` = " . $search_user_id;
                    }

                    break;
                case 'recent_played':
                    $key                     = md5($input . $operator_sql);
                    $where[]                 = sprintf('`played_%s`.`object_id` IS NOT NULL', $key);
                    $table['played_' . $key] = sprintf("LEFT JOIN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'album' ORDER BY %s DESC LIMIT ", $operator_sql) . (int)$input . sprintf(') AS `played_%s` ON `album`.`id` = `played_%s`.`object_id`', $key, $key);
                    break;
                case 'recent_added':
                    $key                       = md5($input . $operator_sql);
                    $where[]                   = sprintf('`addition_time_%s`.`id` IS NOT NULL', $key);
                    $table['addition_' . $key] = sprintf('LEFT JOIN (SELECT `id` FROM `album` ORDER BY %s DESC LIMIT ', $operator_sql) . (int)$input . sprintf(') AS `addition_time_%s` ON `album`.`id` = `addition_time_%s`.`id`', $key, $key);
                    break;
                case 'genre':
                    $where[] = ($operator_sql == "NOT LIKE")
                        ? "`album`.`id` NOT IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` LIKE ? WHERE `tag_map`.`object_type`='album' AND `tag`.`id` IS NOT NULL)"
                        : sprintf("`album`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` %s ? WHERE `tag_map`.`object_type`='album' AND `tag`.`id` IS NOT NULL)", $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'genre_count_album':
                    $where[]      = sprintf("`album`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`album` %s ? WHERE `tag_map`.`object_type`='album' AND `tag`.`id` IS NOT NULL)", $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'genre_count_artist':
                    $where[]           = sprintf("`artist`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`artist` %s ? WHERE `tag_map`.`object_type`='artist' AND `tag`.`id` IS NOT NULL)", $operator_sql);
                    $parameters[]      = $input;
                    $join['album_map'] = true;
                    break;
                case 'genre_count_song':
                    $where[]      = sprintf("`song`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`song` %s ? WHERE `tag_map`.`object_type`='song' AND `tag`.`id` IS NOT NULL)", $operator_sql);
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'no_genre':
                    $where[] = "`album`.`id` NOT IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 WHERE `tag_map`.`object_type`='album' AND `tag`.`id` IS NOT NULL)";
                    break;
                case 'song_genre':
                    $where[] = ($operator_sql == "NOT LIKE")
                        ? "`song`.`id` NOT IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` LIKE ? WHERE `tag_map`.`object_type`='song' AND `tag`.`id` IS NOT NULL)"
                        : sprintf("`song`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` %s ? WHERE `tag_map`.`object_type`='song' AND `tag`.`id` IS NOT NULL)", $operator_sql);
                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'playlist_name':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "NOT (`album`.`id` IN (SELECT `song`.`album` FROM `playlist_data` LEFT JOIN `playlist` ON `playlist_data`.`playlist` = `playlist`.`id` LEFT JOIN `song` ON `song`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type` = 'song' WHERE `playlist`.`name` SOUNDS LIKE ?))";
                    } else {
                        $where[] = sprintf("`album`.`id` IN (SELECT `song`.`album` FROM `playlist_data` LEFT JOIN `playlist` ON `playlist_data`.`playlist` = `playlist`.`id` LEFT JOIN `song` ON `song`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type` = 'song' WHERE `playlist`.`name` %s ?)", $operator_sql);
                    }

                    $parameters[] = $input;
                    break;
                case 'playlist':
                    $where[]      = sprintf("`album`.`id` %s IN (SELECT `song`.`album` FROM `playlist_data` LEFT JOIN `song` ON `song`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type` = 'song' WHERE `playlist_data`.`playlist` = ?)", $operator_sql);
                    $parameters[] = $input;
                    break;
                case 'smartplaylist':
                    //debug_event(self::class, '_get_sql_song: SUBSEARCH ' . $input, 5);
                    $subsearch = new Search((int)$input, 'song', $search->search_user);
                    $results   = $subsearch->get_subsearch('song');
                    $subsearch_count++;
                    $where[] = sprintf('`song`.`id` %s IN (SELECT * FROM (', $operator_sql) . $results['sql'] . ") AS sp_" . $subsearch_count . ")";
                    foreach ($results['parameters'] as $parameter) {
                        $parameters[] = $parameter;
                    }

                    $join['song'] = true;
                    break;
                case 'file':
                    $where[] = $operator_sql === 'NOT SOUNDS LIKE' ? "NOT (`song`.`file` SOUNDS LIKE ?)" : sprintf('`song`.`file` %s ?', $operator_sql);

                    $parameters[] = $input;
                    $join['song'] = true;
                    break;
                case 'has_image':
                    $where[] = ($operator_sql == '1')
                        ? "`album`.`id` IN (SELECT `object_id` FROM `image` WHERE `object_type` = 'album' AND `size` = 'original')"
                        : "`album`.`id` NOT IN (SELECT `object_id` FROM `image` WHERE `object_type` = 'album' AND `size` = 'original')";
                    break;
                case 'image_height':
                case 'image_width':
                    $looking       = ($rule[0] == 'image_width') ? 'width' : 'height';
                    $where[]       = sprintf('`image`.`%s` %s ?', $looking, $operator_sql);
                    $parameters[]  = $input;
                    $join['image'] = true;
                    break;
                case 'artist':
                case 'album_artist':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "(NOT ((`artist`.`name` SOUNDS LIKE ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) SOUNDS LIKE ?) AND `album_map`.`object_type` = 'album'))";
                    } else {
                        $where[] = sprintf("((`artist`.`name` %s ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) %s ?) AND `album_map`.`object_type` = 'album')", $operator_sql, $operator_sql);
                    }

                    $parameters        = array_merge($parameters, [$input, $input]);
                    $join['album_map'] = true;
                    break;
                case 'song':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "NOT (`song`.`title` SOUNDS LIKE ?)";
                    } else {
                        $where[] = sprintf('`song`.`title` %s ?', $operator_sql);
                    }

                    $parameters   = array_merge($parameters, [$input]);
                    $join['song'] = true;
                    break;
                case 'song_artist':
                    if ($operator_sql === 'NOT SOUNDS LIKE') {
                        $where[] = "(NOT ((`artist`.`name` SOUNDS LIKE ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) SOUNDS LIKE ?) AND `album_map`.`object_type` = 'song'))";
                    } else {
                        $where[] = sprintf("((`artist`.`name` %s ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) %s ?) AND `album_map`.`object_type` = 'song')", $operator_sql, $operator_sql);
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
                        $where[] = sprintf('`album`.`mbid` %s ?', $operator_sql);
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

                    $where[] = $operator_sql === 'NOT SOUNDS LIKE' ? "NOT (`song`.`mbid` SOUNDS LIKE ?)" : sprintf('`song`.`mbid` %s ?', $operator_sql);

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
                        $where[] = sprintf('`artist`.`mbid` %s ?', $operator_sql);
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
                    $where[] = "`album`.`mbid_group` IN (SELECT `mbid_group` FROM `album` GROUP BY `album`.`mbid_group` HAVING COUNT(`mbid_group`) > 1)";
                    break;
                case 'duplicate_tracks':
                    $where[] = "`album`.`id` IN (SELECT `album` FROM `song` GROUP BY `track`, `album`, `disk` HAVING COUNT(`track`) > 1)";
                    break;
                default:
                    debug_event(self::class, 'ERROR! rule not found: ' . $rule[0], 3);
                    break;
            } // switch on ruletype album
        } // foreach rule

        $join['song']        = array_key_exists('song', $join);
        $join['catalog']     = $catalog_disable || $catalog_filter;
        $join['catalog_map'] = $catalog_filter;

        $where_sql = implode(sprintf(' %s ', $sql_logic_operator), $where);

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
            if ($where_sql !== '' && $where_sql !== '0') {
                $where_sql = "(" . $where_sql . ") AND `catalog_se`.`enabled` = '1'";
            } else {
                $where_sql = "`catalog_se`.`enabled` = '1'";
            }
        }

        if ($join['catalog_map']) {
            if ($where_sql !== '' && $where_sql !== '0') {
                $where_sql = ($search_user_id > 0)
                    ? "(" . $where_sql . ") AND `catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = " . $search_user_id . " AND `catalog_filter_group_map`.`enabled`=1)"
                    : "(" . $where_sql . ") AND `catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1)";
            } else {
                $where_sql = ($search_user_id > 0)
                    ? "`catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = " . $search_user_id . " AND `catalog_filter_group_map`.`enabled`=1)"
                    : "`catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1)";
            }
        }

        //if (array_key_exists('count', $join)) {
        //    $table['object_count'] = "LEFT JOIN (SELECT `object_count`.`object_id`, MAX(`object_count`.`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = 'album' AND `object_count`.`user`='" . $search_user_id . "' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id`) AS `object_count` ON `object_count`.`object_id` = `album`.`id`";
        //}
        if (array_key_exists('image', $join)) {
            $table['0_song'] = "LEFT JOIN `song` ON `song`.`album` = `album`.`id` LEFT JOIN `image` ON `image`.`object_id` = `album`.`id`";
            $where_sql       = "(" . $where_sql . ") AND `image`.`object_type`='album' AND `image`.`size`='original'";
        }

        ksort($table);
        $table_sql  = implode(' ', $table);
        $group_sql  = implode(',', $group);
        $having_sql = implode(sprintf(' %s ', $sql_logic_operator), $having);

        return [
            'base' => 'SELECT `album`.`id` AS `id` FROM `album`',
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
