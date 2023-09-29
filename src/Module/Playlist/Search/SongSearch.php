<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

declare(strict_types=0);

namespace Ampache\Module\Playlist\Search;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Search;

final class SongSearch implements SearchInterface
{
    /**
     * Handles the generation of the SQL for song searches.
     * @param Search $search
     * @return array
     */
    public function getSql(
        Search $search
    ): array {
        $search_user_id     = $search->search_user->id ?? -1;
        $sql_logic_operator = $search->logic_operator;
        $catalog_disable    = AmpConfig::get('catalog_disable');
        $catalog_filter     = AmpConfig::get('catalog_filter');

        $where       = array();
        $table       = array();
        $join        = array();
        $group       = array();
        $having      = array();
        $parameters  = array();
        $metadata    = array();

        foreach ($search->rules as $rule) {
            $type     = $search->get_rule_type($rule[0]);
            $operator = array();
            if (!$type) {
                continue;
            }
            foreach ($search->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $input        = $search->filter_data($rule[2], $type, $operator);
            $operator_sql = $operator['sql'] ?? '';

            switch ($rule[0]) {
                case 'anywhere':
                    // 'anywhere' searches song title, song filename, song genre, album title, artist title, label title and song comment
                    switch ($operator_sql) {
                        case '!=':
                        case 'NOT':
                            $tag_string = "`song`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` = ? WHERE `tag_map`.`object_type`='song' AND `tag`.`id` IS NOT NULL)";
                            break;
                        case 'NOT LIKE':
                            $tag_string = "`song`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` LIKE ? WHERE `tag_map`.`object_type`='song' AND `tag`.`id` IS NOT NULL)";
                            break;
                        default:
                            $tag_string = "`song`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` $operator_sql ? WHERE `tag_map`.`object_type`='song' AND `tag`.`id` IS NOT NULL)";
                    }
                    $parameters[] = $input;
                    // we want AND NOT and like for this query to really exclude them
                    if (in_array($operator_sql, array('!=', 'NOT LIKE', 'NOT'))) {
                        $where[] = "NOT ((`artist`.`name` LIKE ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) LIKE ?) OR (`album`.`name` LIKE ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) LIKE ?) OR (`song_data`.`comment` LIKE ? AND `song_data`.`comment` IS NOT NULL) OR (`song_data`.`label` LIKE ? AND `song_data`.`label` IS NOT NULL) OR `song`.`file` LIKE ? OR `song`.`title` LIKE ? OR " . $tag_string . ')';
                    } else {
                        $where[] = "((`artist`.`name` $operator_sql ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) $operator_sql ?) OR (`album`.`name` $operator_sql ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) $operator_sql ?) OR `song_data`.`comment` $operator_sql ? OR `song_data`.`label` $operator_sql ? OR `song`.`file` $operator_sql ? OR `song`.`title` $operator_sql ? OR " . $tag_string . ')';
                    }
                    $parameters = array_merge($parameters, array($input, $input, $input, $input, $input, $input, $input, $input));
                    // join it all up
                    $join['album']     = true;
                    $join['artist']    = true;
                    $join['song_data'] = true;
                    break;
                case 'title':
                    $where[]      = "`song`.`title` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'genre':
                    switch ($operator_sql) {
                        case '!=':
                        case 'NOT':
                            $where[] = "`song`.`id` NOT IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` = ? WHERE `tag_map`.`object_type`='song' AND `tag`.`id` IS NOT NULL)";
                            break;
                        case 'NOT LIKE':
                            $where[] = "`song`.`id` NOT IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` LIKE ? WHERE `tag_map`.`object_type`='song' AND `tag`.`id` IS NOT NULL)";
                            break;
                        default:
                            $where[] = "`song`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` $operator_sql ? WHERE `tag_map`.`object_type`='song' AND `tag`.`id` IS NOT NULL)";
                    }
                    $parameters[] = $input;
                    break;
                case 'album_genre':
                    $table['album'] = "LEFT JOIN `album` ON `song`.`album` = `album`.`id`";
                    $where[]        = ($operator_sql == "NOT LIKE")
                        ? "`album`.`id` NOT IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` LIKE ? WHERE `tag_map`.`object_type`='album' AND `tag`.`id` IS NOT NULL)"
                        : "`album`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` $operator_sql ? WHERE `tag_map`.`object_type`='album' AND `tag`.`id` IS NOT NULL)";
                    $parameters[]   = $input;
                    break;
                case 'artist_genre':
                    $where[]         = ($operator_sql == "NOT LIKE")
                        ? "`artist`.`id` NOT IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` LIKE ? WHERE `tag_map`.`object_type`='artist' AND `tag`.`id` IS NOT NULL)"
                        : "`artist`.`id` IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 AND `tag`.`name` $operator_sql ? WHERE `tag_map`.`object_type`='artist' AND `tag`.`id` IS NOT NULL)";
                    $parameters[]    = $input;
                    $join['artist']  = true;
                    break;
                case 'no_genre':
                    $where[] = "`song`.`id` NOT IN (SELECT `tag_map`.`object_id` FROM `tag_map` LEFT JOIN `tag` ON `tag_map`.`tag_id` = `tag`.`id` AND `tag`.`is_hidden` = 0 WHERE `tag_map`.`object_type`='song' AND `tag`.`id` IS NOT NULL)";
                    break;
                case 'album':
                    $where[]        = "(`album`.`name` $operator_sql ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) $operator_sql ?)";
                    $parameters     = array_merge($parameters, array($input, $input));
                    $table['album'] = "LEFT JOIN `album` ON `song`.`album` = `album`.`id`";
                    break;
                case 'artist':
                    $where[]        = "(`artist`.`name` $operator_sql ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) $operator_sql ?)";
                    $parameters     = array_merge($parameters, array($input, $input));
                    $join['artist'] = true;
                    break;
                case 'album_artist':
                    $where[]               = "(`album_artist`.`name` $operator_sql ? OR LTRIM(CONCAT(COALESCE(`album_artist`.`prefix`, ''), ' ', `album_artist`.`name`)) $operator_sql ?)";
                    $parameters            = array_merge($parameters, array($input, $input));
                    $table['album']        = "LEFT JOIN `album` ON `song`.`album` = `album`.`id`";
                    $table['album_artist'] = "LEFT JOIN `artist` AS `album_artist` ON `album`.`album_artist` = `album_artist`.`id`";
                    break;
                case 'time':
                    $input        = $input * 60;
                    $where[]      = "`song`.`time` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'file':
                case 'composer':
                case 'year':
                case 'track':
                case 'catalog':
                case 'license':
                    $where[]      = "`song`.`$rule[0]` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'comment':
                    $join['song_data'] = true;
                    if (!$input || $input == '%%' || $input == '%') {
                        if (in_array($operator_sql, array('=', 'LIKE', 'SOUNDS LIKE'))) {
                            $where[]      = "`song_data`.`comment` IS NULL";
                            break;
                        }
                        if (in_array($operator_sql, array('!=', 'NOT LIKE', 'NOT SOUNDS LIKE'))) {
                            $where[]      = "`song_data`.`comment` IS NOT NULL";
                            break;
                        }
                    }
                    $where[]           = "`song_data`.`comment` $operator_sql ?";
                    $parameters[]      = $input;
                    break;
                case 'label':
                    $join['song_data'] = true;
                    if (!$input || $input == '%%' || $input == '%') {
                        if (in_array($operator_sql, array('=', 'LIKE', 'SOUNDS LIKE'))) {
                            $where[]      = "`song_data`.`label` IS NULL";
                            break;
                        }
                        if (in_array($operator_sql, array('!=', 'NOT LIKE', 'NOT SOUNDS LIKE'))) {
                            $where[]      = "`song_data`.`label` IS NOT NULL";
                            break;
                        }
                    }
                    $where[]      = "`song_data`.`label` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'lyrics':
                    $join['song_data'] = true;
                    if (!$input || $input == '%%' || $input == '%') {
                        if (in_array($operator_sql, array('=', 'LIKE', 'SOUNDS LIKE'))) {
                            $where[]      = "`song_data`.`lyrics` IS NULL";
                            break;
                        }
                        if (in_array($operator_sql, array('!=', 'NOT LIKE', 'NOT SOUNDS LIKE'))) {
                            $where[]      = "`song_data`.`lyrics` IS NOT NULL";
                            break;
                        }
                    }
                    $where[]      = "`song_data`.`lyrics` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'played':
                    $where[] = "`song`.`played` = '$operator_sql'";
                    break;
                case 'last_play':
                    $my_type = 'song';
                    if (!array_key_exists('last_play', $table)) {
                        $table['last_play'] = '';
                    }
                    $table['last_play'] .= (!strpos((string) $table['last_play'], "last_play_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `last_play_" . $my_type . "_" . $search_user_id . "` ON `song`.`id` = `last_play_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `last_play_" . $my_type . "_" . $search_user_id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[] = "`last_play_" . $my_type . "_" . $search_user_id . "`.`date` $operator_sql (UNIX_TIMESTAMP() - ($input * 86400))";
                    break;
                case 'last_skip':
                    $my_type = 'song';
                    if (!array_key_exists('last_skip', $table)) {
                        $table['last_skip'] = '';
                    }
                    $table['last_skip'] .= (!strpos((string) $table['last_skip'], "last_skip_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'skip' AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `last_skip_" . $my_type . "_" . $search_user_id . "` ON `song`.`id` = `last_skip_" . $my_type . "_" . $search_user_id . "`.`object_id` AND `last_skip_" . $my_type . "_" . $search_user_id . "`.`object_type` = '$my_type' "
                        : "";
                    $where[] = "`last_skip_" . $my_type . "_" . $search_user_id . "`.`date` $operator_sql (UNIX_TIMESTAMP() - ($input * 86400))";
                    break;
                case 'last_play_or_skip':
                    $my_type = 'song';
                    if (!array_key_exists('last_play_or_skip', $table)) {
                        $table['last_play_or_skip'] = '';
                    }
                    $table['last_play_or_skip'] .= (!strpos((string) $table['last_play_or_skip'], "last_play_or_skip_" . $my_type . "_" . $search_user_id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` IN ('stream', 'skip') AND `object_count`.`user` = " . $search_user_id . " GROUP BY `object_id`, `object_type`, `user`) AS `last_play_or_skip_" . $my_type . "_" . $search_user_id . "` ON `song`.`id` = `last_play_or_skip_" . $my_type . "_" . $search->search_user->id . "`.`object_id` AND `last_play_or_skip_" . $my_type . "_" . $search->search_user->id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[] = "`last_play_or_skip_" . $my_type . "_" . $search->search_user->id . "`.`date` $operator_sql (UNIX_TIMESTAMP() - ($input * 86400))";
                    break;
                case 'played_times':
                    $where[]      = "(`song`.`total_count` $operator_sql ?)";
                    $parameters[] = $input;
                    break;
                case 'skipped_times':
                    $where[]      = "(`song`.`total_skip` $operator_sql ?)";
                    $parameters[] = $input;
                    break;
                case 'played_or_skipped_times':
                    $where[]      = "((`song`.`total_count` + `song`.`total_skip`) $operator_sql ?)";
                    $parameters[] = $input;
                    break;
                case 'play_skip_ratio':
                    $where[]      = "(((`song`.`total_count`/`song`.`total_skip`) * 100) $operator_sql ?)";
                    $parameters[] = $input;
                    break;
                case 'myplayed':
                case 'myplayedalbum':
                case 'myplayedartist':
                    // combine these as they all do the same thing just different tables
                    $looking      = str_replace('myplayed', '', $rule[0]);
                    $column       = ($looking == '') ? 'id' : $looking;
                    $my_type      = ($looking == '') ? 'song' : $looking;
                    $operator_sql = ((int) $operator_sql == 0) ? 'IS NULL' : 'IS NOT NULL';
                    // played once per user
                    if (!array_key_exists('myplayed', $table)) {
                        $table['myplayed'] = '';
                    }
                    $table['myplayed'] .= (!strpos((string) $table['myplayed'], "myplayed_" . $my_type . "_" . $search->search_user->id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `object_count` WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' AND `object_count`.`user` = " . $search->search_user->id . " GROUP BY `object_id`, `object_type`, `user`) AS `myplayed_" . $my_type . "_" . $search->search_user->id . "` ON `song`.`$column` = `myplayed_" . $my_type . "_" . $search->search_user->id . "`.`object_id` AND `myplayed_" . $my_type . "_" . $search->search_user->id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[] = "`myplayed_" . $my_type . "_" . $search->search_user->id . "`.`object_id` $operator_sql";
                    break;
                case 'bitrate':
                    $input        = $input * 1000;
                    $where[]      = "`song`.`bitrate` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'rating':
                    // average ratings only
                    $where[]          = "`average_rating`.`avg` $operator_sql ?";
                    $parameters[]     = $input;
                    $table['average'] = "LEFT JOIN (SELECT `object_id`, ROUND(AVG(IFNULL(`rating`.`rating`,0))) AS `avg` FROM `rating` WHERE `rating`.`object_type`='song' GROUP BY `object_id`) AS `average_rating` ON `average_rating`.`object_id` = `song`.`id` ";
                    break;
                case 'favorite':
                    $where[]      = "`song`.`title` $operator_sql ? AND `favorite_song_" . $search->search_user->id . "`.`user` = " . $search->search_user->id . " AND `favorite_song_" . $search->search_user->id . "`.`object_type` = 'song'";
                    $parameters[] = $input;
                    // flag once per user
                    if (!array_key_exists('favorite', $table)) {
                        $table['favorite'] = '';
                    }
                    $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_song_" . $search->search_user->id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = " . $search->search_user->id . ") AS `favorite_song_" . $search->search_user->id . "` ON `song`.`id` = `favorite_song_" . $search->search_user->id . "`.`object_id` AND `favorite_song_" . $search->search_user->id . "`.`object_type` = 'song'"
                        : "";
                    break;
                case 'favorite_album':
                    $where[]    = "(`album`.`name` $operator_sql ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) $operator_sql ?) AND `favorite_album_" . $search->search_user->id . "`.`user` = " . $search->search_user->id . " AND `favorite_album_" . $search->search_user->id . "`.`object_type` = 'album'";
                    $parameters = array_merge($parameters, array($input, $input));
                    // flag once per user
                    if (!array_key_exists('favorite', $table)) {
                        $table['favorite'] = '';
                    }
                    $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_album_" . $search->search_user->id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = " . $search->search_user->id . ") AS `favorite_album_" . $search->search_user->id . "` ON `album`.`id` = `favorite_album_" . $search->search_user->id . "`.`object_id` AND `favorite_album_" . $search->search_user->id . "`.`object_type` = 'album'"
                        : "";
                    $join['album'] = true;
                    break;
                case 'favorite_artist':
                    $where[]    = "(`artist`.`name` $operator_sql ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) $operator_sql ?) AND `favorite_artist_" . $search->search_user->id . "`.`user` = " . $search->search_user->id . " AND `favorite_artist_" . $search->search_user->id . "`.`object_type` = 'artist'";
                    $parameters = array_merge($parameters, array($input, $input));
                    // flag once per user
                    if (!array_key_exists('favorite', $table)) {
                        $table['favorite'] = '';
                    }
                    $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_artist_" . $search->search_user->id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = " . $search->search_user->id . ") AS `favorite_artist_" . $search->search_user->id . "` ON `artist`.`id` = `favorite_artist_" . $search->search_user->id . "`.`object_id` AND `favorite_artist_" . $search->search_user->id . "`.`object_type` = 'artist'"
                        : "";
                    $join['artist'] = true;
                    break;
                case 'myrating':
                case 'albumrating':
                case 'artistrating':
                    // combine these as they all do the same thing just different tables
                    $looking = str_replace('rating', '', $rule[0]);
                    $column  = ($looking == 'my') ? 'id' : $looking;
                    $my_type = ($looking == 'my') ? 'song' : $looking;
                    if ($input == 0 && $operator_sql == '>=') {
                        break;
                    }
                    if ($input == 0 && $operator_sql == '<') {
                        $input        = -1;
                        $operator_sql = '=';
                    }
                    if ($input == 0 && $operator_sql == '<>') {
                        $input        = 1;
                        $operator_sql = '>=';
                    }
                    if (($input == 0 && $operator_sql != '>') || ($input == 1 && $operator_sql == '<')) {
                        $where[] = "`rating_" . $my_type . "_" . $search->search_user->id . "`.`rating` IS NULL";
                    } elseif (in_array($operator_sql, array('<>', '<', '<=', '!='))) {
                        $where[]      = "(`rating_" . $my_type . "_" . $search->search_user->id . "`.`rating` $operator_sql ? OR `rating_" . $my_type . "_" . $search->search_user->id . "`.`rating` IS NULL)";
                        $parameters[] = $input;
                    } else {
                        $where[]      = "`rating_" . $my_type . "_" . $search->search_user->id . "`.`rating` $operator_sql ?";
                        $parameters[] = $input;
                    }
                    // rating once per user
                    if (!array_key_exists('rating', $table)) {
                        $table['rating'] = '';
                    }
                    $table['rating'] .= (!strpos((string) $table['rating'], "rating_" . $my_type . "_" . $search->search_user->id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `rating` FROM `rating` WHERE `user` = " . $search->search_user->id . " AND `object_type`='$my_type') AS `rating_" . $my_type . "_" . $search->search_user->id . "` ON `rating_" . $my_type . "_" . $search->search_user->id . "`.`object_id` = `song`.`$column`"
                        : "";
                    break;
                case 'my_flagged':
                case 'my_flagged_album':
                case 'my_flagged_artist':
                    // combine these as they all do the same thing just different tables
                    $looking      = str_replace('my_flagged_', '', $rule[0]);
                    $column       = ($looking == 'my_flagged') ? 'id' : $looking;
                    $my_type      = ($looking == 'my_flagged') ? 'song' : $looking;
                    $operator_sql = ((int) $operator_sql == 0) ? 'IS NULL' : 'IS NOT NULL';
                    // played once per user
                    if (!array_key_exists('my_flagged_', $table)) {
                        $table['my_flagged_'] = '';
                    }
                    $table['my_flagged_'] .= (!strpos((string) $table['my_flagged_'], "my_flagged__" . $my_type . "_" . $search->search_user->id))
                        ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user_flag`.`object_type` = '$my_type' AND `user_flag`.`count_type` = 'stream' AND `user_flag`.`user` = " . $search->search_user->id . " GROUP BY `object_id`, `object_type`, `user`) AS `my_flagged__" . $my_type . "_" . $search->search_user->id . "` ON `song`.`$column` = `my_flagged__" . $my_type . "_" . $search->search_user->id . "`.`object_id` AND `my_flagged__" . $my_type . "_" . $search->search_user->id . "`.`object_type` = '$my_type'"
                        : "";
                    $where[] = "`my_flagged__" . $my_type . "_" . $search->search_user->id . "`.`object_id` $operator_sql";
                    break;
                case 'other_user':
                case 'other_user_album':
                case 'other_user_artist':
                    // combine these as they all do the same thing just different tables
                    $looking      = str_replace('other_user_', '', $rule[0]);
                    $column       = ($looking == 'other_user') ? 'id' : $looking;
                    $my_type      = ($looking == 'other_user') ? 'song' : $looking;
                    $other_userid = $input;
                    if ($operator_sql == 'userflag') {
                        $where[] = "`favorite_" . $my_type . "_" . $other_userid . "`.`user` = $other_userid AND `favorite_" . $my_type . "_" . $other_userid . "`.`object_type` = '$my_type'";
                        // flag once per user
                        if (!array_key_exists('favorite', $table)) {
                            $table['favorite'] = '';
                        }
                        $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_" . $my_type . "_" . $other_userid))
                            ? "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `user_flag` WHERE `user` = $other_userid) AS `favorite_" . $my_type . "_" . $other_userid . "` ON `song`.`$column` = `favorite_" . $my_type . "_" . $other_userid . "`.`object_id` AND `favorite_" . $my_type . "_" . $other_userid . "`.`object_type` = '$my_type'"
                            : "";
                    } else {
                        $unrated = ($operator_sql == 'unrated');
                        $where[] = ($unrated) ? "`song`.`$column` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = '$my_type' AND `rating`.`user` = $other_userid)" : "`rating_" . $my_type . "_" . $other_userid . "`.$operator_sql AND `rating_" . $my_type . "_" . $other_userid . "`.`user` = $other_userid AND `rating_" . $my_type . "_" . $other_userid . "`.`object_type` = '$my_type'";
                        // rating once per user
                        if (!array_key_exists('rating', $table)) {
                            $table['rating'] = '';
                        }
                        $table['rating'] .= (!strpos((string) $table['rating'], "rating_" . $my_type . "_" . $other_userid))
                            ? "LEFT JOIN `rating` AS `rating_" . $my_type . "_" . $other_userid . "` ON `rating_" . $my_type . "_" . $other_userid . "`.`object_type`='$my_type' AND `rating_" . $my_type . "_" . $other_userid . "`.`object_id` = `song`.`$column` AND `rating_" . $my_type . "_" . $other_userid . "`.`user` = $other_userid "
                            : "";
                    }
                    break;
                case 'playlist_name':
                    $join['playlist']      = true;
                    $join['playlist_data'] = true;
                    $where[]               = "`playlist`.`name` $operator_sql ?";
                    $parameters[]          = $input;
                    break;
                case 'playlist':
                    $where[]      = "`song`.`id` $operator_sql IN (SELECT `object_id` FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_type` = 'song')";
                    $parameters[] = $input;
                    break;
                case 'smartplaylist':
                    //debug_event(self::class, '_get_sql_song: SUBSEARCH ' . $input, 5);
                    $subsearch = new Search($input, 'song', $search->search_user);
                    $results   = $subsearch->get_subsearch('song');
                    if (!empty($results)) {
                        $where[]      = "`song`.`id` $operator_sql IN (" . $results['sql'] . ")";
                        foreach ($results['parameters'] as $parameter) {
                            $parameters[] = $parameter;
                        }
                    }
                    break;
                case 'added':
                    $input        = strtotime((string) $input);
                    $where[]      = "`song`.`addition_time` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'updated':
                    $input        = strtotime((string) $input);
                    $where[]      = "`song`.`update_time` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'recent_played':
                    $key                     = md5($input . $operator_sql);
                    $where[]                 = "`played_$key`.`object_id` IS NOT NULL";
                    $table['played_' . $key] = "LEFT JOIN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'song' ORDER BY $operator_sql DESC LIMIT " . (int)$input . ") AS `played_$key` ON `song`.`id` = `played_$key`.`object_id`";
                    break;
                case 'recent_added':
                    $key                       = md5($input . $operator_sql);
                    $where[]                   = "`addition_time_$key`.`id` IS NOT NULL";
                    $table['addition_' . $key] = "LEFT JOIN (SELECT `id` FROM `song` ORDER BY $operator_sql DESC LIMIT " . (int)$input . ") AS `addition_time_$key` ON `song`.`id` = `addition_time_$key`.`id`";
                    break;
                case 'recent_updated':
                    $key                     = md5($input . $operator_sql);
                    $where[]                 = "`update_time_$key`.`id` IS NOT NULL";
                    $table['update_' . $key] = "LEFT JOIN (SELECT `id` FROM `song` ORDER BY $operator_sql DESC LIMIT " . (int)$input . ") AS `update_time_$key` ON `song`.`id` = `update_time_$key`.`id`";
                    break;
                case 'mbid':
                    if (!$input || $input == '%%' || $input == '%') {
                        if (in_array($operator_sql, array('=', 'LIKE', 'SOUNDS LIKE'))) {
                            $where[]      = "`song`.`mbid` IS NULL";
                            break;
                        }
                        if (in_array($operator_sql, array('!=', 'NOT LIKE', 'NOT SOUNDS LIKE'))) {
                            $where[]      = "`song`.`mbid` IS NOT NULL";
                            break;
                        }
                    }
                    $where[]      = "`song`.`mbid` $operator_sql ?";
                    $parameters[] = $input;
                    break;
                case 'mbid_album':
                    $table['album'] = "LEFT JOIN `album` ON `song`.`album` = `album`.`id`";
                    if (!$input || $input == '%%' || $input == '%') {
                        if (in_array($operator_sql, array('=', 'LIKE', 'SOUNDS LIKE'))) {
                            $where[]      = "`album`.`mbid` IS NULL";
                            break;
                        }
                        if (in_array($operator_sql, array('!=', 'NOT LIKE', 'NOT SOUNDS LIKE'))) {
                            $where[]      = "`album`.`mbid` IS NOT NULL";
                            break;
                        }
                    }
                    $where[]        = "`album`.`mbid` $operator_sql ?";
                    $parameters[]   = $input;
                    break;
                case 'mbid_artist':
                    if (!$input || $input == '%%' || $input == '%') {
                        if (in_array($operator_sql, array('=', 'LIKE', 'SOUNDS LIKE'))) {
                            $where[]      = "`artist`.`mbid` IS NULL";
                            break;
                        }
                        if (in_array($operator_sql, array('!=', 'NOT LIKE', 'NOT SOUNDS LIKE'))) {
                            $where[]      = "`artist`.`mbid` IS NOT NULL";
                            break;
                        }
                    }
                    $where[]        = "`artist`.`mbid` $operator_sql ?";
                    $parameters[]   = $input;
                    $join['artist'] = true;
                    break;
                case 'possible_duplicate':
                    $where[]               = "(`dupe_search1`.`dupe_id1` IS NOT NULL OR `dupe_search2`.`dupe_id2` IS NOT NULL)";
                    $table['dupe_search1'] = "LEFT JOIN (SELECT MIN(`song`.`id`) AS `dupe_id1`, CONCAT(LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)), LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `song`.`title`) AS `fullname`, COUNT(CONCAT(LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)), LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `song`.`title`)) AS `counting` FROM `song` LEFT JOIN `album` ON `song`.`album` = `album`.`id` LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `song`.`id` AND `artist_map`.`object_type` = 'song' LEFT JOIN `artist` ON `artist_map`.`artist_id` = `artist`.`id` GROUP BY `fullname` HAVING `Counting` > 1) AS `dupe_search1` ON `song`.`id` = `dupe_search1`.`dupe_id1` ";
                    $table['dupe_search2'] = "LEFT JOIN (SELECT MAX(`song`.`id`) AS `dupe_id2`, CONCAT(LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)), LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `song`.`title`) AS `fullname`, COUNT(CONCAT(LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)), LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `song`.`title`)) AS `counting` FROM `song` LEFT JOIN `album` ON `song`.`album` = `album`.`id` LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `song`.`id` AND `artist_map`.`object_type` = 'song' LEFT JOIN `artist` ON `artist_map`.`artist_id` = `artist`.`id` GROUP BY `fullname` HAVING `Counting` > 1) AS `dupe_search2` ON `song`.`id` = `dupe_search2`.`dupe_id2`";
                    break;
                case 'possible_duplicate_album':
                    $where[]                     = "((`dupe_album_search1`.`dupe_album_id1` IS NOT NULL OR `dupe_album_search2`.`dupe_album_id2` IS NOT NULL))";
                    $table['dupe_album_search1'] = "LEFT JOIN (SELECT `album_artist`, MIN(`id`) AS `dupe_album_id1`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) AS `fullname`, COUNT(LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`))) AS `Counting` FROM `album` GROUP BY `album_artist`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `album`.`year`, `album`.`release_type`, `album`.`release_status` HAVING `Counting` > 1) AS `dupe_album_search1` ON `album`.`id` = `dupe_album_search1`.`dupe_album_id1`";
                    $table['dupe_album_search2'] = "LEFT JOIN (SELECT `album_artist`, MAX(`id`) AS `dupe_album_id2`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) AS `fullname`, COUNT(LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`))) AS `Counting` FROM `album` GROUP BY `album_artist`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)), `album`.`year`, `album`.`release_type`, `album`.`release_status` HAVING `Counting` > 1) AS `dupe_album_search2` ON `album`.`id` = `dupe_album_search2`.`dupe_album_id2`";
                    $join['album']               = true;
                    break;
                case 'duplicate_tracks':
                    $where[] = "`song`.`id` IN (SELECT MAX(`id`) AS `id` FROM `song` GROUP BY `track`, `album`, `disk` HAVING COUNT(`track`) > 1)";
                    break;
                case 'orphaned_album':
                    $where[] = "`song`.`album` IN (SELECT `album_id` FROM `album_map` WHERE `album_id` NOT IN (SELECT `id` from `album`))";
                    break;
                case 'metadata':
                    $field = (int)$rule[3];
                    if ($operator_sql === '=' && strlen($input) == 0) {
                        $where[] = "NOT EXISTS (SELECT NULL FROM `metadata` WHERE `metadata`.`object_id` = `song`.`id` AND `metadata`.`field` = {$field})";
                    } else {
                        $parsedInput = is_numeric($input) ? $input : '"' . $input . '"';
                        if (!array_key_exists($field, $metadata)) {
                            $metadata[$field] = array();
                        }
                        $metadata[$field][] = "`metadata`.`data` $operator_sql ?";
                        $parameters[]       = $parsedInput;
                    }
                    break;
            } // switch on ruletype song
        } // foreach over rules

        // translate metadata queries into sql for each field
        foreach ($metadata as $metadata_field => $metadata_queries) {
            $metadata_sql = "EXISTS (SELECT NULL FROM `metadata` WHERE `metadata`.`object_id` = `song`.`id` AND `metadata`.`field` = {$metadata_field} AND (";
            $metadata_sql .= implode(" $sql_logic_operator ", $metadata_queries);
            $where[] = $metadata_sql . '))';
        }

        $join['catalog_map'] = $catalog_filter;
        $join['catalog']     = $catalog_disable || $catalog_filter;

        $where_sql = implode(" $sql_logic_operator ", $where);

        // now that we know which things we want to JOIN...
        if (array_key_exists('song_data', $join)) {
            $table['song_data'] = "LEFT JOIN `song_data` ON `song`.`id` = `song_data`.`song_id`";
        }
        if (array_key_exists('playlist_data', $join)) {
            $table['playlist_data'] = "LEFT JOIN `playlist_data` ON `song`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type`='song'";
            if (array_key_exists('playlist', $join)) {
                $table['playlist'] = "LEFT JOIN `playlist` ON `playlist_data`.`playlist` = `playlist`.`id`";
            }
        }
        if ($join['catalog']) {
            $table['1_catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id` = `song`.`catalog`";
            if (!empty($where_sql)) {
                $where_sql = "(" . $where_sql . ") AND `catalog_se`.`enabled` = '1' AND `song`.`enabled` = 1";
            } else {
                $where_sql = "`catalog_se`.`enabled` = '1' AND `song`.`enabled` = 1";
            }
        }
        if ($join['catalog_map']) {
            $table['2_catalog_map'] = "LEFT JOIN `catalog_map` AS `catalog_map_song` ON `catalog_map_song`.`object_id` = `song`.`id` AND `catalog_map_song`.`object_type` = 'song' AND `catalog_map_song`.`catalog_id` = `catalog_se`.`id`";
            if (!empty($where_sql)) {
                $where_sql = "(" . $where_sql . ") AND `catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = " . $search->search_user->id . " AND `catalog_filter_group_map`.`enabled`=1)";
            } else {
                $where_sql = "`catalog_se`.`id` IN (SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = " . $search->search_user->id . " AND `catalog_filter_group_map`.`enabled`=1)";
            }
        }
        if (array_key_exists('artist', $join)) {
            $table['3_artist_map'] = "LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `song`.`id` AND `artist_map`.`object_type` = 'song'";
            $table['4_artist']     = "LEFT JOIN `artist` ON `artist_map`.`artist_id` = `artist`.`id`";
        }
        if (array_key_exists('album', $join)) {
            $table['album'] = "LEFT JOIN `album` ON `song`.`album` = `album`.`id`";
        }
        ksort($table);
        $table_sql  = implode(' ', $table);
        $group_sql  = implode(',', $group);
        $having_sql = implode(" $sql_logic_operator ", $having);

        return array(
            'base' => 'SELECT DISTINCT(`song`.`id`), `song`.`file` FROM `song`',
            'join' => $join,
            'where' => $where,
            'where_sql' => $where_sql,
            'table' => $table,
            'table_sql' => $table_sql,
            'group_sql' => $group_sql,
            'having_sql' => $having_sql,
            'parameters' => $parameters
        );
    }
}
