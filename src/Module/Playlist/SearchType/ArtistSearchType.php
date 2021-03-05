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

final class ArtistSearchType extends AbstractSearchType
{

    /**
     * Handles the generation of the SQL for artist searches.
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
                case 'title':
                case 'name':
                    $where[] = "(`artist`.`name` $sql_match_operator '$input' " . " OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), " . "' ', `artist`.`name`)) $sql_match_operator '$input')";
                    break;
                case 'yearformed':
                    $where[] = "`artist`.`yearformed` $sql_match_operator '$input'";
                    break;
                case 'placeformed':
                    $where[] = "`artist`.`placeformed` $sql_match_operator '$input'";
                    break;
                case 'tag':
                    $key = md5($input . $sql_match_operator);
                    if ($sql_match_operator == 'LIKE' || $sql_match_operator == 'NOT LIKE') {
                        $where[]           = "`realtag_$key`.`name` $sql_match_operator '$input'";
                        $join['tag'][$key] = "$sql_match_operator '$input'";
                    } else {
                        $where[]           = "find_in_set('$input', cast(`realtag_$key`.`name` as char)) $sql_match_operator 0";
                        $join['tag'][$key] = "find_in_set('$input', cast(`realtag_$key`.`name` as char)) $sql_match_operator 0";
                    }
                    break;
                case 'rating':
                    // average ratings only
                    $where[]          = "`average_rating`.`avg` $sql_match_operator '$input'";
                    $table['average'] = "LEFT JOIN (SELECT `object_id`, ROUND(AVG(IFNULL(`rating`.`rating`,0))) AS " . "`avg` FROM `rating` WHERE `rating`.`object_type`='artist' GROUP BY `object_id`) AS " . "`average_rating` on `average_rating`.`object_id` = `artist`.`id` ";
                    break;
                case 'favorite':
                    $where[] = "(`artist`.`name` $sql_match_operator '$input'  OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) $sql_match_operator '$input') " . "AND `favorite_artist_$userid`.`user` = $userid " . "AND `favorite_artist_$userid`.`object_type` = 'artist'";
                    // flag once per user
                    $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_artist_$userid")) ?
                        "LEFT JOIN (SELECT `object_id`, `object_type`, `user` " .
                        "FROM `user_flag` WHERE `user` = $userid) AS `favorite_artist_$userid` " .
                        "ON `artist`.`id`=`favorite_artist_$userid`.`object_id` " .
                        "AND `favorite_artist_$userid`.`object_type` = 'artist' " : ' ';
                    break;
                case 'has_image':
                    $where[]            = ($sql_match_operator == '1') ? "`has_image`.`object_id` IS NOT NULL" : "`has_image`.`object_id` IS NULL";
                    $table['has_image'] = "LEFT JOIN (SELECT `object_id` from `image` WHERE `object_type` = 'artist') as `has_image` ON `artist`.`id` = `has_image`.`object_id`";
                    break;
                case 'image_height':
                case 'image_width':
                    $looking       = strpos($rule[0], "image_") ? str_replace('image_', '', $rule[0]) : str_replace('image ', '', $rule[0]);
                    $where[]       = "`image`.`$looking` $sql_match_operator '$input'";
                    $join['image'] = true;
                    break;
                case 'myrating':
                    // combine these as they all do the same thing just different tables
                    $column  = 'id';
                    $my_type = 'artist';
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
                        "ON `rating_" . $my_type . "_" . $userid . "`.`object_id`=`artist`.`$column` " : ' ';
                    break;
                case 'myplayed':
                    $column       = 'id';
                    $my_type      = 'artist';
                    $operator_sql = ((int)$sql_match_operator == 0) ? 'IS NULL' : 'IS NOT NULL';
                    // played once per user
                    $table['myplayed'] .= (!strpos((string) $table['myplayed'], "myplayed_" . $my_type . "_" . $userid)) ?
                        "LEFT JOIN (SELECT `object_id`, `object_type`, `user` FROM `object_count` " .
                        "WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' " .
                        "AND `object_count`.`user`=$userid GROUP BY `object_id`, `object_type`, `user`) AS " .
                        "`myplayed_" . $my_type . "_" . $userid . "` " .
                        "ON `artist`.`$column`=`myplayed_" . $my_type . "_" . $userid . "`.`object_id` " .
                        "AND `myplayed_" . $my_type . "_" . $userid . "`.`object_type` = '$my_type' " : ' ';
                    $where[] = "`myplayed_" . $my_type . "_" . $userid . "`.`object_id` $operator_sql";
                    break;
                case 'last_play':
                    $my_type = 'artist';
                    $table['last_play'] .= (!strpos((string) $table['last_play'], "last_play_" . $my_type . "_" . $userid)) ?
                        "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` " .
                        "WHERE `object_count`.`object_type` = '$my_type' AND `object_count`.`count_type` = 'stream' " .
                        "AND `object_count`.`user`=$userid GROUP BY `object_id`, `object_type`, `user`) AS `last_play_" . $my_type . "_" . $userid . "` " .
                        "ON `artist`.`id`=`last_play_" . $my_type . "_" . $userid . "`.`object_id` " .
                        "AND `last_play_" . $my_type . "_" . $userid . "`.`object_type` = '$my_type' " : ' ';
                    $where[] = "`last_play_" . $my_type . "_" . $userid . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - ($input * 86400))";
                    break;
                case 'last_skip':
                    $my_type = 'artist';
                    $table['last_skip'] .= (!strpos((string) $table['last_skip'], "last_skip_" . $my_type . "_" . $userid)) ?
                        "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` " .
                        "WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'skip' " .
                        "AND `object_count`.`user`=$userid GROUP BY `object_id`, `object_type`, `user`) AS `last_skip_" . $my_type . "_" . $userid . "` " .
                        "ON `song`.`id`=`last_skip_" . $my_type . "_" . $userid . "`.`object_id` " .
                        "AND `last_skip_" . $my_type . "_" . $userid . "`.`object_type` = 'song' " : ' ';
                    $where[]      = "`last_skip_" . $my_type . "_" . $userid . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - ($input * 86400))";
                    $join['song'] = true;
                    break;
                case 'last_play_or_skip':
                    $my_type = 'artist';
                    $table['last_play_or_skip'] .= (!strpos((string) $table['last_play_or_skip'], "last_play_or_skip_" . $my_type . "_" . $userid)) ?
                        "LEFT JOIN (SELECT `object_id`, `object_type`, `user`, MAX(`date`) AS `date` FROM `object_count` " .
                        "WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` IN ('stream', 'skip') " .
                        "AND `object_count`.`user`=$userid GROUP BY `object_id`, `object_type`, `user`) AS `last_play_or_skip_" . $my_type . "_" . $userid . "` " .
                        "ON `song`.`id`=`last_play_or_skip_" . $my_type . "_" . $userid . "`.`object_id` " .
                        "AND `last_play_or_skip_" . $my_type . "_" . $userid . "`.`object_type` = 'song' " : ' ';
                    $where[]      = "`last_play_or_skip_" . $my_type . "_" . $userid . "`.`date` $sql_match_operator (UNIX_TIMESTAMP() - ($input * 86400))";
                    $join['song'] = true;
                    break;
                case 'played_times':
                    $where[] = "`artist`.`id` IN (SELECT `object_count`.`object_id` FROM `object_count` " . "WHERE `object_count`.`object_type` = 'artist' AND `object_count`.`count_type` = 'stream' " . "GROUP BY `object_count`.`object_id` HAVING COUNT(*) $sql_match_operator '$input')";
                    break;
                case 'other_user':
                    $other_userid = $input;
                    if ($sql_match_operator == 'userflag') {
                        $where[] = "`favorite_artist_$other_userid`.`user` = $other_userid " . " AND `favorite_artist_$other_userid`.`object_type` = 'artist'";
                        // flag once per user
                        $table['favorite'] .= (!strpos((string) $table['favorite'], "favorite_artist_$other_userid")) ?
                            "LEFT JOIN (SELECT `object_id`, `object_type`, `user` " .
                            "FROM `user_flag` WHERE `user` = $other_userid) AS `favorite_artist_$other_userid` " .
                            "ON `song`.`artist`=`favorite_artist_$other_userid`.`object_id` " .
                            "AND `favorite_artist_$other_userid`.`object_type` = 'artist' " : ' ';
                    } else {
                        $column  = 'id';
                        $my_type = 'artist';
                        $where[] = "`rating_artist_" . $other_userid . '`.' . $sql_match_operator . " AND `rating_artist_$other_userid`.`user` = $other_userid " . " AND `rating_artist_$other_userid`.`object_type` = 'artist'";
                        // rating once per user
                        $table['rating'] .= (!strpos((string) $table['rating'], "rating_" . $my_type . "_" . $userid)) ?
                            "LEFT JOIN `rating` AS `rating_" . $my_type . "_" . $userid . "` ON " .
                            "`rating_" . $my_type . "_" . $userid . "`.`object_type`='$my_type' AND " .
                            "`rating_" . $my_type . "_" . $userid . "`.`object_id`=`$my_type`.`$column` AND " .
                            "`rating_" . $my_type . "_" . $userid . "`.`user` = $userid " : ' ';
                    }
                    break;
                case 'mbid':
                    $where[] = "`artist`.`mbid` $sql_match_operator '$input'";
                    break;
                default:
                    break;
            } // switch on ruletype artist
        } // foreach rule

        $join['song']    = $join['song'] || AmpConfig::get('catalog_disable');
        $join['catalog'] = AmpConfig::get('catalog_disable');

        $where_sql = implode(" $sql_logic_operator ", $where);

        foreach ($join['tag'] as $key => $value) {
            $table['tag_' . $key] = "LEFT JOIN (" . "SELECT `object_id`, GROUP_CONCAT(`name`) AS `name` " . "FROM `tag` LEFT JOIN `tag_map` " . "ON `tag`.`id`=`tag_map`.`tag_id` " . "WHERE `tag_map`.`object_type`='artist' " . "GROUP BY `object_id`" . ") AS `realtag_$key` " . "ON `artist`.`id`=`realtag_$key`.`object_id`";
        }

        if ($join['song']) {
            $table['0_song'] = "LEFT JOIN `song` ON `song`.`artist`=`artist`.`id`";

            if ($join['catalog']) {
                $table['catalog'] = "LEFT JOIN `catalog` AS `catalog_se` ON `catalog_se`.`id`=`song`.`catalog`";
                if (!empty($where_sql)) {
                    $where_sql .= " AND `catalog_se`.`enabled` = '1' AND `song`.`enabled` = 1";
                } else {
                    $where_sql .= " `catalog_se`.`enabled` = '1' AND `song`.`enabled` = 1";
                }
            }
        }
        if ($join['count']) {
            $table['object_count'] = "LEFT JOIN (SELECT `object_count`.`object_id`, MAX(`object_count`.`date`) AS " . "`date` FROM `object_count` WHERE `object_count`.`object_type` = 'artist' AND " . "`object_count`.`user`='" . $userid . "' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id`) AS " . "`object_count` ON `object_count`.`object_id`=`artist`.`id`";
        }
        if ($join['image']) {
            $table['0_song'] = "LEFT JOIN `song` ON `song`.`artist`=`artist`.`id` LEFT JOIN `image` ON `image`.`object_id`=`artist`.`id`";
            $where_sql .= " AND `image`.`object_type`='artist'";
            $where_sql .= " AND `image`.`size`='original'";
        }
        ksort($table);
        $table_sql  = implode(' ', $table);
        $group_sql  = implode(',', $group);
        $having_sql = implode(" $sql_logic_operator ", $having);

        return array(
            'base' => 'SELECT DISTINCT(`artist`.`id`), `artist`.`name` FROM `artist`',
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
