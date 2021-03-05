<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

namespace Ampache\Module\Playlist\SearchType;

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Search;

final class SongSearchType extends AbstractSearchType
{

    /**
     * Handles the generation of the SQL for song searches.
     * @return array
     */
    public function getSql(
        Search $search
    ): array {
        $sql_logic_operator = $search->logic_operator;
        $userid             = $search->search_user->id;

        $where       = array();
        $table       = array();
        $join        = array();
        $group       = array();
        $having      = array();
        $join['tag'] = array();
        $metadata    = array();

        foreach ($search->rules as $rule) {
            $type     = $search->name_to_basetype($rule[0]);
            $operator = array();
            if (!$type) {
                return array();
            }
            foreach ($search->basetypes[$type] as $op) {
                if ($op['name'] == $rule[1]) {
                    $operator = $op;
                    break;
                }
            }
            $raw_input          = $this->mangleData($rule[2], $type, $operator);
            $input              = filter_var($raw_input, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
            $sql_match_operator = $operator['sql'];

            switch ($rule[0]) {
                case 'anywhere':
                    $key = md5($input . $sql_match_operator);
                    if ($sql_match_operator == 'LIKE') {
                        $tag_string        = "`realtag_$key`.`name` IS NOT NULL ";
                        $join['tag'][$key] = "AND `tag`.`name` LIKE '$input' ";
                    } elseif ($sql_match_operator == 'NOT LIKE') {
                        $tag_string        = "`realtag_$key`.`name` IS NULL ";
                        $join['tag'][$key] = "AND `tag`.`name` LIKE '$input' ";
                    } elseif ($operator['description'] == 'is') {
                        $tag_string        = "`realtag_$key`.`name` IS NOT NULL ";
                        $join['tag'][$key] = "AND `tag`.`name` = '$input' ";
                    } elseif ($operator['description'] == 'is not') {
                        $tag_string        = "`realtag_$key`.`name` IS NULL ";
                        $join['tag'][$key] = "AND `tag`.`name` = '$input' ";
                    } else {
                        $tag_string        = "find_in_set('$input', cast(`realtag_$key`.`name` as char)) $sql_match_operator 0 ";
                        $join['tag'][$key] = "AND find_in_set('$input', cast(`name` as char)) $sql_match_operator 0 ";
                    }
                    // we want AND NOT and like for this query to really exclude them
                    if ($sql_match_operator == 'NOT LIKE' || $sql_match_operator == 'NOT' || $sql_match_operator == '!=') {
                        $where[] = "NOT ((`artist`.`name` LIKE '$input' OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) LIKE '$input') OR " . "(`album`.`name` LIKE '$input' OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) LIKE '$input') OR " . "`song_data`.`comment` LIKE '$input' OR `song_data`.`label` LIKE '$input' OR `song`.`file` LIKE '$input' OR " . "`song`.`title` LIKE '$input' OR NOT " . $tag_string . ')';
                    } else {
                        $where[] = "((`artist`.`name` $sql_match_operator '$input' OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) $sql_match_operator '$input') OR " . "(`album`.`name` $sql_match_operator '$input' OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) $sql_match_operator '$input') OR " . "`song_data`.`comment` $sql_match_operator '$input' OR `song_data`.`label` $sql_match_operator '$input' OR `song`.`file` $sql_match_operator '$input' OR " . "`song`.`title` $sql_match_operator '$input' OR " . $tag_string . ')';
                    }
                    // join it all up
                    $table['album']    = "LEFT JOIN `album` ON `song`.`album`=`album`.`id`";
                    $table['artist']   = "LEFT JOIN `artist` ON `song`.`artist`=`artist`.`id`";
                    $join['song_data'] = true;
                    break;
                case 'tag':
                    $key = md5($input . $sql_match_operator);
                    if ($sql_match_operator == 'LIKE') {
                        $where[]           = "`realtag_$key`.`name` IS NOT NULL ";
                        $join['tag'][$key] = "AND `tag`.`name` LIKE '$input' ";
                    } elseif ($sql_match_operator == 'NOT LIKE') {
                        $where[]           = "`realtag_$key`.`name` IS NULL ";
                        $join['tag'][$key] = "AND `tag`.`name` LIKE '$input' ";
                    } elseif ($operator['description'] == 'is') {
                        $where[]           = "`realtag_$key`.`name` IS NOT NULL ";
                        $join['tag'][$key] = "AND `tag`.`name` = '$input' ";
                    } elseif ($operator['description'] == 'is not') {
                        $where[]           = "`realtag_$key`.`name` IS NULL ";
                        $join['tag'][$key] = "AND `tag`.`name` = '$input' ";
                    } else {
                        $where[]           = "find_in_set('$input', cast(`realtag_$key`.`name` as char)) $sql_match_operator 0 ";
                        $join['tag'][$key] = "AND find_in_set('$input', cast(`name` as char)) $sql_match_operator 0 ";
                    }
                    break;
                case 'album_tag':
                    $table['album'] = "LEFT JOIN `album` ON `song`.`album`=`album`.`id`";
                    $key            = md5($input . $sql_match_operator);
                    if ($sql_match_operator == 'LIKE') {
                        $where[]                 = "`realtag_$key`.`name` IS NOT NULL ";
                        $join['album_tag'][$key] = "AND `tag`.`name` LIKE '$input' ";
                    } elseif ($sql_match_operator == 'NOT LIKE') {
                        $where[]                 = "`realtag_$key`.`name` IS NULL ";
                        $join['album_tag'][$key] = "AND `tag`.`name` LIKE '$input' ";
                    } elseif ($operator['description'] == 'is') {
                        $where[]                 = "`realtag_$key`.`name` IS NOT NULL ";
                        $join['album_tag'][$key] = "AND `tag`.`name` = '$input' ";
                    } elseif ($operator['description'] == 'is not') {
                        $where[]                 = "`realtag_$key`.`name` IS NULL ";
                        $join['album_tag'][$key] = "AND `tag`.`name` = '$input' ";
                    } else {
                        $where[]                 = "find_in_set('$input', cast(`realtag_$key`.`name` as char)) $sql_match_operator 0 ";
                        $join['album_tag'][$key] = "AND find_in_set('$input', cast(`name` as char)) $sql_match_operator 0 ";
                    }
                    break;
                case 'artist_tag':
                    $table['artist'] = "LEFT JOIN `artist` ON `song`.`artist`=`artist`.`id`";
                    $key             = md5($input . $sql_match_operator);
                    if ($sql_match_operator == 'LIKE') {
                        $where[]                  = "`realtag_$key`.`name` IS NOT NULL ";
                        $join['artist_tag'][$key] = "AND `tag`.`name` LIKE '$input' ";
                    } elseif ($sql_match_operator == 'NOT LIKE') {
                        $where[]                  = "`realtag_$key`.`name` IS NULL ";
                        $join['artist_tag'][$key] = "AND `tag`.`name` LIKE '$input' ";
                    } elseif ($operator['description'] == 'is') {
                        $where[]                  = "`realtag_$key`.`name` IS NOT NULL ";
                        $join['artist_tag'][$key] = "AND `tag`.`name` = '$input' ";
                    } elseif ($operator['description'] == 'is not') {
                        $where[]                  = "`realtag_$key`.`name` IS NULL ";
                        $join['artist_tag'][$key] = "AND `tag`.`name` = '$input' ";
                    } else {
                        $where[]                  = "find_in_set('$input', cast(`realtag_$key`.`name` as char)) $sql_match_operator 0 ";
                        $join['artist_tag'][$key] = "AND find_in_set('$input', cast(`name` as char)) $sql_match_operator 0 ";
                    }
                    break;
                case 'title':
                    $where[] = "`song`.`title` $sql_match_operator '$input'";
                    break;
                case 'album':
                    $where[]        = "(`album`.`name` $sql_match_operator '$input' " . " OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), " . "' ', `album`.`name`)) $sql_match_operator '$input')";
                    $table['album'] = "LEFT JOIN `album` ON `song`.`album`=`album`.`id`";
                    break;
                case 'artist':
                    $where[]         = "(`artist`.`name` $sql_match_operator '$input' " . " OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), " . "' ', `artist`.`name`)) $sql_match_operator '$input')";
                    $table['artist'] = "LEFT JOIN `artist` ON `song`.`artist`=`artist`.`id`";
                    break;
                case 'album_artist':
                    $where[]         = "(`album_artist`.`name` $sql_match_operator '$input' " .
                        " OR LTRIM(CONCAT(COALESCE(`album_artist`.`prefix`, ''), " .
                        "' ', `album_artist`.`name`)) $sql_match_operator '$input')";
                    $table['album']        = "LEFT JOIN `album` ON `song`.`album`=`album`.`id`";
                    $table['album_artist'] = "LEFT JOIN `artist` AS `album_artist` ON `album`.`album_artist`=`album_artist`.`id`";
                    break;
                case 'composer':
                    $where[] = "`song`.`composer` $sql_match_operator '$input'";
                    break;
                case 'time':
                    $input   = $input * 60;
                    $where[] = "`song`.`time` $sql_match_operator '$input'";
                    break;
                case 'file':
                    $where[] = "`song`.`file` $sql_match_operator '$input'";
                    break;
                case 'year':
                    $where[] = "`song`.`year` $sql_match_operator '$input'";
                    break;
                case 'comment':
                    $where[]           = "`song_data`.`comment` $sql_match_operator '$input'";
                    $join['song_data'] = true;
                    break;
                case 'label':
                    $where[]           = "`song_data`.`label` $sql_match_operator '$input'";
                    $join['song_data'] = true;
                    break;
                case 'lyrics':
                    $where[]           = "`song_data`.`lyrics` $sql_match_operator '$input'";
                    $join['song_data'] = true;
                    break;
                case 'played':
                    $where[] = "`song`.`played` = '$sql_match_operator'";
                    break;
                case 'last_play':
                    $my_type = 'song';
                    $table['last_play'] .= (!strpos((string) $table['last_play'], "last_play_" . $my_type . "_" . $userid)) ?
                        "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` " .
                        "WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' " .
                        "AND `object_count`.`user`=$userid GROUP BY `object_id`, `object_type`, `user`) AS `last_play_" . $my_type . "_" . $userid . "` " .
                        "ON `song`.`id`=`last_play_" . $my_type . "_" . $userid . "`.`object_id` " .
                        "AND `last_play_" . $my_type . "_" . $userid . "`.`object_type` = '$my_type' " : ' ';
                    $where[] = "`last_play_" . $my_type . "_" . $userid . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - ($input * 86400))";
                    break;
                case 'last_skip':
                    $my_type = 'song';
                    $table['last_skip'] .= (!strpos((string) $table['last_skip'], "last_skip_" . $my_type . "_" . $userid)) ?
                        "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` " .
                        "WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'skip' " .
                        "AND `object_count`.`user`=$userid GROUP BY `object_id`, `object_type`, `user`) AS `last_skip_" . $my_type . "_" . $userid . "` " .
                        "ON `song`.`id`=`last_skip_" . $my_type . "_" . $userid . "`.`object_id` " .
                        "AND `last_skip_" . $my_type . "_" . $userid . "`.`object_type` = '$my_type' " : ' ';
                    $where[] = "`last_skip_" . $my_type . "_" . $userid . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - ($input * 86400))";
                    break;
                case 'last_play_or_skip':
                    $my_type = 'song';
                    $table['last_play_or_skip'] .= (!strpos((string) $table['play_or_skip'], "play_or_skip_" . $my_type . "_" . $userid)) ?
                        "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` " .
                        "WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` IN ('stream', 'skip') " .
                        "AND `object_count`.`user`=$userid GROUP BY `object_id`, `object_type`, `user`) AS `play_or_skip_" . $my_type . "_" . $userid . "` " .
                        "ON `song`.`id`=`play_or_skip_" . $my_type . "_" . $userid . "`.`object_id` " .
                        "AND `play_or_skip_" . $my_type . "_" . $userid . "`.`object_type` = '$my_type' " : ' ';
                    $where[] = "`play_or_skip_" . $my_type . "_" . $userid . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - ($input * 86400))";
                    break;
                case 'played_times':
                    $where[] = "`song`.`id` IN (SELECT `object_count`.`object_id` FROM `object_count` " . "WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' " . "GROUP BY `object_count`.`object_id` HAVING COUNT(*) $sql_match_operator '$input')";
                    break;
                case 'skipped_times':
                    $where[] = "`song`.`id` IN (SELECT `object_count`.`object_id` FROM `object_count` " . "WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'skip' " . "GROUP BY `object_count`.`object_id` HAVING COUNT(*) $sql_match_operator '$input')";
                    break;
                case 'played_or_skipped_times':
                    $where[] = "`song`.`id` IN (SELECT `object_count`.`object_id` FROM `object_count` " .
                        "WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` IN ('stream', 'skip') " .
                        "GROUP BY `object_count`.`object_id` HAVING COUNT(*) $sql_match_operator '$input')";
                    break;
                case 'play_skip_ratio':
                    $where[] = "`song`.`id` IN (SELECT `song`.`id` FROM `song` " . "LEFT JOIN (SELECT COUNT(`object_id`) AS `counting`, `object_id`, `count_type` " . "FROM `object_count` WHERE `object_type` = 'song' AND `count_type` = 'stream' " . "GROUP BY `object_id`, `count_type`) AS `stream_count` on `song`.`id` = `stream_count`.`object_id`" . "LEFT JOIN (SELECT COUNT(`object_id`) AS `counting`, `object_id`, `count_type` " . "FROM `object_count` WHERE `object_type` = 'song' AND `count_type` = 'skip' " . "GROUP BY `object_id`, `count_type`) AS `skip_count` on `song`.`id` = `skip_count`.`object_id` " . "WHERE ((IFNULL(`stream_count`.`counting`, 0)/IFNULL(`skip_count`.`counting`, 0)) * 100) " . "$sql_match_operator '$input' GROUP BY `song`.`id`)";
                    break;
                case 'myplayed':
                case 'myplayedalbum':
                case 'myplayedartist':
                    // combine these as they all do the same thing just different tables
                    $looking      = str_replace('myplayed', '', $rule[0]);
                    $column       = ($looking == '') ? 'id' : $looking;
                    $my_type      = ($looking == '') ? 'song' : $looking;
                    $operator_sql = ((int) $sql_match_operator == 0) ? 'IS NULL' : 'IS NOT NULL';
                    // played once per user
                    $table['myplayed'] .= (!strpos((string) $table['myplayed'], "myplayed_" . $my_type . "_" . $userid)) ?
                        "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `object_count` " .
                        "WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' " .
                        "AND `object_count`.`user`=$userid GROUP BY `object_id`, `object_type`, `user`) AS " .
                        "`myplayed_" . $my_type . "_" . $userid . "` " .
                        "ON `song`.`$column`=`myplayed_" . $my_type . "_" . $userid . "`.`object_id` " .
                        "AND `myplayed_" . $my_type . "_" . $userid . "`.`object_type` = '$my_type' " : ' ';
                    $where[] = "`myplayed_" . $my_type . "_" . $userid . "`.`object_id` $operator_sql";
                    break;
                case 'bitrate':
                    $input   = $input * 1000;
                    $where[] = "`song`.`bitrate` $sql_match_operator '$input'";
                    break;
                case 'rating':
                    // average ratings only
                    $where[]          = "`average_rating`.`avg` $sql_match_operator '$input'";
                    $table['average'] = "LEFT JOIN (SELECT `object_id`, ROUND(AVG(IFNULL(`rating`.`rating`,0))) AS " . "`avg` FROM `rating` WHERE `rating`.`object_type`='song' GROUP BY `object_id`) AS " . "`average_rating` on `average_rating`.`object_id` = `song`.`id` ";
                    break;
                case 'favorite':
                    $where[] = "`song`.`title` $sql_match_operator '$input' " . "AND `favorite_song_$userid`.`user` = $userid " . "AND `favorite_song_$userid`.`object_type` = 'song'";
                    // flag once per user
                    $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_song_$userid")) ?
                        "LEFT JOIN (SELECT `object_id`, `object_type`, `user` " .
                        "FROM `user_flag` WHERE `user` = $userid) AS `favorite_song_$userid` " .
                        "ON `song`.`id`=`favorite_song_$userid`.`object_id` " .
                        "AND `favorite_song_$userid`.`object_type` = 'song' " : ' ';
                    break;
                case 'myrating':
                case 'albumrating':
                case 'artistrating':
                    // combine these as they all do the same thing just different tables
                    $looking = str_replace('rating', '', $rule[0]);
                    $column  = ($looking == 'my') ? 'id' : $looking;
                    $my_type = ($looking == 'my') ? 'song' : $looking;
                    if ($input == 0 && $sql_match_operator == '>=') {
                        break;
                    }
                    if ($input == 0 && $sql_match_operator == '<') {
                        $input              = -1;
                        $sql_match_operator = '=';
                    }
                    if ($input == 0 && $sql_match_operator == '<>') {
                        $input              = 1;
                        $sql_match_operator = '>=';
                    }
                    if (($input == 0 && $sql_match_operator != '>') || ($input == 1 && $sql_match_operator == '<')) {
                        $where[] = "`rating_" . $my_type . "_" . $userid . "`.`rating` IS NULL";
                    } elseif ($sql_match_operator == '<>' || $sql_match_operator == '<' || $sql_match_operator == '<=' || $sql_match_operator == '!=') {
                        $where[] = "(`rating_" . $my_type . "_" . $userid . "`.`rating` $sql_match_operator $input OR `rating_" . $my_type . "_" . $userid . "`.`rating` IS NULL)";
                    } else {
                        $where[] = "`rating_" . $my_type . "_" . $userid . "`.`rating` $sql_match_operator $input";
                    }
                    // rating once per user
                    $table['rating'] .= (!strpos((string) $table['rating'], "rating_" . $my_type . "_" . $userid)) ?
                        "LEFT JOIN (SELECT `object_id`, `object_type`, `rating` FROM `rating` " .
                        "WHERE `user` = $userid AND `object_type`='$my_type') " .
                        "AS `rating_" . $my_type . "_" . $userid . "` " .
                        "ON `rating_" . $my_type . "_" . $userid . "`.`object_id`=`song`.`$column` " : ' ';
                    break;
                case 'catalog':
                    $where[] = "`song`.`catalog` $sql_match_operator '$input'";
                    break;
                case 'other_user':
                case 'other_user_album':
                case 'other_user_artist':
                    // combine these as they all do the same thing just different tables
                    $looking      = str_replace('other_user_', '', $rule[0]);
                    $column       = ($looking == 'other_user') ? 'id' : $looking;
                    $my_type      = ($looking == 'other_user') ? 'song' : $looking;
                    $other_userid = $input;
                    if ($sql_match_operator == 'userflag') {
                        $where[] = "`favorite_" . $my_type . "_" . $other_userid . "`.`user` = $other_userid " . " AND `favorite_" . $my_type . "_" . $other_userid . "`.`object_type` = '$my_type'";
                        // flag once per user
                        $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_" . $my_type . "_" . $other_userid . "")) ?
                            "LEFT JOIN (SELECT `object_id`, `object_type`, `user` " .
                            "from `user_flag` WHERE `user` = $other_userid) AS `favorite_" . $my_type . "_" . $other_userid . "` " .
                            "ON `song`.`$column`=`favorite_" . $my_type . "_" . $other_userid . "`.`object_id` " .
                            "AND `favorite_" . $my_type . "_" . $other_userid . "`.`object_type` = '$my_type' " : ' ';
                    } else {
                        $unrated = ($sql_match_operator == 'unrated');
                        $where[] = ($unrated) ? "`song`.`$column` NOT IN (SELECT `object_id` FROM `rating` WHERE `object_type` = '$my_type' AND `user` = $other_userid)" : "`rating_" . $my_type . "_" . $other_userid . "`.$sql_match_operator" . " AND `rating_" . $my_type . "_" . $other_userid . "`.`user` = $other_userid " . " AND `rating_" . $my_type . "_" . $other_userid . "`.`object_type` = '$my_type'";
                        // rating once per user
                        $table['rating'] .= (!strpos((string) $table['rating'], "rating_" . $my_type . "_" . $other_userid)) ?
                            "LEFT JOIN `rating` AS `rating_" . $my_type . "_" . $other_userid . "` ON " .
                            "`rating_" . $my_type . "_" . $other_userid . "`.`object_type`='$my_type' AND " .
                            "`rating_" . $my_type . "_" . $other_userid . "`.`object_id`=`song`.`$column` AND " .
                            "`rating_" . $my_type . "_" . $other_userid . "`.`user` = $other_userid " : ' ';
                    }
                    break;
                case 'playlist_name':
                    $join['playlist']      = true;
                    $join['playlist_data'] = true;
                    $where[]               = "`playlist`.`name` $sql_match_operator '$input'";
                    break;
                case 'playlist':
                    $join['playlist_data'] = true;
                    $where[]               = "`playlist_data`.`playlist` $sql_match_operator '$input'";
                    break;
                case 'smartplaylist':
                    $subsearch  = new Search($input, 'song', $search->search_user);
                    $subsql     = $subsearch->buildSql();
                    $results    = $subsearch->get_items();
                    $itemstring = '';
                    if (count($results) > 0) {
                        foreach ($results as $item) {
                            $itemstring .= ' ' . $item['object_id'] . ',';
                        }
                        $table['smart'] .= (!strpos((string) $table['smart'], "smart_" . $input)) ?
                            "LEFT JOIN (SELECT `id` FROM `song` " .
                            "WHERE `id` $sql_match_operator IN (" . substr($itemstring, 0, -1) . ")) " .
                            "AS `smartlist_$input` ON `smartlist_$input`.`id` = `song`.`id`" : ' ';
                        $where[]  = "`smartlist_$input`.`id` IS NOT NULL";
                        // HACK: array_merge would potentially lose tags, since it
                        // overwrites. Save our merged tag joins in a temp variable,
                        // even though that's ugly.
                        $tagjoin     = array_merge($subsql['join']['tag'], $join['tag']);
                        $join        = array_merge($subsql['join'], $join);
                        $join['tag'] = $tagjoin;
                    }
                    break;
                case 'license':
                    $where[] = "`song`.`license` $sql_match_operator '$input'";
                    break;
                case 'added':
                    $input   = strtotime((string) $input);
                    $where[] = "`song`.`addition_time` $sql_match_operator $input";
                    break;
                case 'updated':
                    $input   = strtotime((string) $input);
                    $where[] = "`song`.`update_time` $sql_match_operator $input";
                    break;
                case 'recent_added':
                    $key                       = md5($input . $sql_match_operator);
                    $where[]                   = "`addition_time_$key`.`id` IS NOT NULL";
                    $table['addition_' . $key] = "LEFT JOIN (SELECT `id` from `song` ORDER BY $sql_match_operator DESC LIMIT $input) as `addition_time_$key` ON `song`.`id` = `addition_time_$key`.`id`";
                    break;
                case 'recent_updated':
                    $key                     = md5($input . $sql_match_operator);
                    $where[]                 = "`update_time_$key`.`id` IS NOT NULL";
                    $table['update_' . $key] = "LEFT JOIN (SELECT `id` from `song` ORDER BY $sql_match_operator DESC LIMIT $input) as `update_time_$key` ON `song`.`id` = `update_time_$key`.`id`";
                    break;
                case 'mbid':
                    $where[] = "`song`.`mbid` $sql_match_operator '$input'";
                    break;
                case 'mbid_album':
                    $table['album'] = "LEFT JOIN `album` ON `song`.`album`=`album`.`id`";
                    $where[]        = "`album`.`mbid` $sql_match_operator '$input'";
                    break;
                case 'mbid_artist':
                    $table['artist'] = "LEFT JOIN `artist` ON `song`.`artist`=`artist`.`id`";
                    $where[]         = "`artist`.`mbid` $sql_match_operator '$input'";
                    break;
                case 'metadata':
                    $field = (int)$rule[3];
                    if ($sql_match_operator === '=' && strlen($input) == 0) {
                        $where[] = "NOT EXISTS (SELECT NULL FROM `metadata` WHERE `metadata`.`object_id` = `song`.`id` AND `metadata`.`field` = {$field})";
                    } else {
                        $parsedInput = is_numeric($input) ? $input : '"' . $input . '"';
                        if (!array_key_exists($field, $metadata)) {
                            $metadata[$field] = array();
                        }
                        $metadata[$field][] = "`metadata`.`data` $sql_match_operator $parsedInput";
                    }
                    break;
                default:
                    break;
            } // switch on ruletype song
        } // foreach over rules

        // translate metadata queries into sql for each field
        foreach ($metadata as $metadata_field => $metadata_queries) {
            $metadata_sql = "EXISTS (SELECT NULL FROM `metadata` WHERE `metadata`.`object_id` = `song`.`id` AND `metadata`.`field` = {$metadata_field} AND (";
            $metadata_sql .= implode(" $sql_logic_operator ", $metadata_queries);
            $where[] = $metadata_sql . '))';
        }

        $join['catalog'] = AmpConfig::get('catalog_disable');

        $where_sql = implode(" $sql_logic_operator ", $where);

        // now that we know which things we want to JOIN...
        if ($join['song_data']) {
            $table['song_data'] = "LEFT JOIN `song_data` ON `song`.`id`=`song_data`.`song_id`";
        }
        if ($join['tag']) {
            foreach ($join['tag'] as $key => $value) {
                $table['tag_' . $key] = "LEFT JOIN (" . "SELECT `object_id`, GROUP_CONCAT(`name`) AS `name` " . "FROM `tag` LEFT JOIN `tag_map` " . "ON `tag`.`id`=`tag_map`.`tag_id` " . "WHERE `tag_map`.`object_type`='song' " . "$value" . "GROUP BY `object_id`" . ") AS `realtag_$key` " . "ON `song`.`id`=`realtag_$key`.`object_id`";
            }
        }
        if ($join['album_tag']) {
            foreach ($join['album_tag'] as $key => $value) {
                $table['tag_' . $key] = "LEFT JOIN (" . "SELECT `object_id`, GROUP_CONCAT(`name`) AS `name` " . "FROM `tag` LEFT JOIN `tag_map` " . "ON `tag`.`id`=`tag_map`.`tag_id` " . "WHERE `tag_map`.`object_type`='album' " . "$value" . "GROUP BY `object_id`" . ") AS `realtag_$key` " . "ON `album`.`id`=`realtag_$key`.`object_id`";
            }
        }
        if ($join['artist_tag']) {
            foreach ($join['artist_tag'] as $key => $value) {
                $table['tag_' . $key] = "LEFT JOIN (" . "SELECT `object_id`, GROUP_CONCAT(`name`) AS `name` " . "FROM `tag` LEFT JOIN `tag_map` " . "ON `tag`.`id`=`tag_map`.`tag_id` " . "WHERE `tag_map`.`object_type`='artist' " . "$value" . "GROUP BY `object_id`" . ") AS `realtag_$key` " . "ON `artist`.`id`=`realtag_$key`.`object_id`";
            }
        }
        if ($join['playlist_data']) {
            $table['playlist_data'] = "LEFT JOIN `playlist_data` ON `song`.`id`=`playlist_data`.`object_id` AND `playlist_data`.`object_type`='song'";
            if ($join['playlist']) {
                $table['playlist'] = "LEFT JOIN `playlist` ON `playlist_data`.`playlist`=`playlist`.`id`";
            }
        }
        if ($join['catalog']) {
            $table['catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id`=`song`.`catalog`";
            if (!empty($where_sql)) {
                $where_sql .= " AND `catalog_se`.`enabled` = '1' AND `song`.`enabled` = 1";
            } else {
                $where_sql .= " `catalog_se`.`enabled` = '1' AND `song`.`enabled` = 1";
            }
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
            'having_sql' => $having_sql
        );
    }
}
