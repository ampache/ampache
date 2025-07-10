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

namespace Ampache\Module\Database\Query;

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Query;

final class AlbumDiskQuery implements QueryInterface
{
    public const FILTERS = [
        'add_gt',
        'add_lt',
        'album_artist',
        'alpha_match',
        'artist',
        'catalog_enabled',
        'catalog',
        'equal',
        'exact_match',
        'genre',
        'id',
        'like',
        'no_genre',
        'no_tag',
        'not_like',
        'not_starts_with',
        'regex_match',
        'regex_not_match',
        'song_artist',
        'starts_with',
        'tag',
        'unplayed',
        'update_gt',
        'update_lt',
        'user_catalog',
        'user_flag',
        'user_rating',
    ];

    /** @var string[] $sorts */
    protected array $sorts = [
        'album_artist_album_sort',
        'album_artist_title',
        'album_artist',
        'album_id',
        'artist',
        'barcode',
        'catalog_number',
        'catalog',
        'disk_count',
        'disk',
        'disksubtitle',
        'generic_artist',
        'id',
        'name_original_year',
        'name_year',
        'name',
        'original_year',
        'rand',
        'rating',
        'release_status',
        'release_type',
        'song_count',
        'subtitle',
        'time',
        'title',
        'total_count',
        'user_flag_rating',
        'user_flag',
        'userflag',
        'version',
        'year',
    ];

    protected string $select = "`album_disk`.`id`";

    protected string $base = "SELECT %%SELECT%% AS `id` FROM `album_disk` ";

    /**
     * get_select
     *
     * This method returns the columns a query will user for SELECT
     */
    public function get_select(): string
    {
        return $this->select;
    }

    /**
     * get_base_sql
     *
     * Base SELECT query string without filters or joins
     */
    public function get_base_sql(): string
    {
        return $this->base;
    }

    /**
     * get_sorts
     *
     * List of valid sorts for this query
     * @return string[]
     */
    public function get_sorts(): array
    {
        return $this->sorts;
    }

    /**
     * get_sql_filter
     *
     * SQL filters for WHERE and required table joins for the selected $filter
     */
    public function get_sql_filter(Query $query, string $filter, mixed $value): string
    {
        $filter_sql = '';
        $query->set_join('LEFT', '`album`', '`album_disk`.`album_id`', '`album`.`id`', 10);
        switch ($filter) {
            case 'id':
                $filter_sql = " `album_disk`.`id` IN (";
                foreach ($value as $uid) {
                    $filter_sql .= (int)$uid . ',';
                }
                $filter_sql = rtrim($filter_sql, ',') . ") AND ";
                break;
            case 'no_genre':
            case 'no_tag':
                $filter_sql = " (`album`.`id` NOT IN (SELECT `object_id` FROM `tag_map` WHERE `object_type`='album')) AND ";
                break;
            case 'genre':
            case 'tag':
                $query->set_join('LEFT', '`tag_map`', '`tag_map`.`object_id`', '`album`.`id`', 100);
                $filter_sql = " `tag_map`.`object_type`='album' AND (";

                foreach ($value as $tag_id) {
                    $filter_sql .= "`tag_map`.`tag_id`='" . Dba::escape($tag_id) . "' AND ";
                }
                $filter_sql = rtrim((string) $filter_sql, 'AND ') . ") AND ";
                break;
            case 'equal':
            case 'exact_match':
                $filter_sql = " `album`.`name` = '" . Dba::escape($value) . "' AND ";
                break;
            case 'like':
            case 'alpha_match':
                $filter_sql = " `album`.`name` LIKE '%" . Dba::escape($value) . "%' AND ";
                break;
            case 'not_like':
                $filter_sql = " `album`.`name` NOT LIKE '%" . Dba::escape($value) . "%' AND ";
                break;
            case 'regex_match':
                if (!empty($value)) {
                    $filter_sql = " `album`.`name` REGEXP '" . Dba::escape($value) . "' AND ";
                }
                break;
            case 'regex_not_match':
                if (!empty($value)) {
                    $filter_sql = " `album`.`name` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                }
                break;
            case 'starts_with':
                $query->set_join('LEFT', '`song`', '`album`.`id`', '`song`.`album`', 100);
                $filter_sql = " `album`.`name` LIKE '" . Dba::escape($value) . "%' AND ";
                if ($query->catalog != 0) {
                    $filter_sql .= "`song`.`catalog` = '" . $query->catalog . "' AND ";
                }
                break;
            case 'not_starts_with':
                $query->set_join('LEFT', '`song`', '`album`.`id`', '`song`.`album`', 100);
                $filter_sql = " `album`.`name` NOT LIKE '" . Dba::escape($value) . "%' AND ";
                if ($query->catalog != 0) {
                    $filter_sql .= "`song`.`catalog` = '" . $query->catalog . "' AND ";
                }
                break;
            case 'artist':
                $filter_sql = " `album`.`id` IN (SELECT `album_id` FROM `album_map` WHERE `album_map`.`object_id` = '" . Dba::escape($value) . "') AND ";
                break;
            case 'album_artist':
                $filter_sql = ($value == 0)
                    ? " `album`.`id` NOT IN (SELECT `album_id` FROM `album_map` WHERE `album_map`.`object_id` = '" . Dba::escape($value) . "' AND `artist_map`.`object_type` = 'album') AND "
                    : " `album`.`id` IN (SELECT `album_id` FROM `album_map` WHERE `album_map`.`object_id` = '" . Dba::escape($value) . "' AND `artist_map`.`object_type` = 'album') AND ";
                break;
            case 'song_artist':
                $filter_sql = ($value == 0)
                    ? " `album`.`id` NOT IN (SELECT `album_id` FROM `album_map` WHERE `album_map`.`object_id` = '" . Dba::escape($value) . "' AND `artist_map`.`object_type` = 'song') AND "
                    : " `album`.`id` IN (SELECT `album_id` FROM `album_map` WHERE `album_map`.`object_id` = '" . Dba::escape($value) . "' AND `artist_map`.`object_type` = 'song') AND ";
                break;
            case 'add_lt':
                $query->set_join('LEFT', '`song`', '`song`.`album`', '`album`.`id`', 100);
                $filter_sql = " `song`.`addition_time` <= '" . Dba::escape($value) . "' AND ";
                break;
            case 'add_gt':
                $query->set_join('LEFT', '`song`', '`song`.`album`', '`album`.`id`', 100);
                $filter_sql = " `song`.`addition_time` >= '" . Dba::escape($value) . "' AND ";
                break;
            case 'update_lt':
                $query->set_join('LEFT', '`song`', '`song`.`album`', '`album`.`id`', 100);
                $filter_sql = " `song`.`update_time` <= '" . Dba::escape($value) . "' AND ";
                break;
            case 'update_gt':
                $query->set_join('LEFT', '`song`', '`song`.`album`', '`album`.`id`', 100);
                $filter_sql = " `song`.`update_time` >= '" . Dba::escape($value) . "' AND ";
                break;
            case 'catalog':
                if ($value != 0) {
                    $filter_sql = " (`album_disk`.`catalog` = '" . Dba::escape($value) . "') AND ";
                }
                break;
            case 'user_catalog':
                $filter_sql = " `album_disk`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $query->user_id, true)) . ") AND ";
                break;
            case 'user_flag':
                $filter_sql = ($value === 0)
                    ? " `album_disk`.`id` NOT IN (SELECT `object_id` FROM `user_flag` WHERE `object_type` = 'album_disk' AND `user` = " . (int)$query->user_id . ") AND "
                    : " `album_disk`.`id` IN (SELECT `object_id` FROM `user_flag` WHERE `object_type` = 'album_disk' AND `user` = " . (int)$query->user_id . ") AND ";
                break;
            case 'user_rating':
                $filter_sql = ($value === 0)
                    ? " `album_disk`.`id` NOT IN (SELECT `id` FROM `rating` WHERE `object_type` = 'album_disk' AND `user` = " . (int)$query->user_id . ") AND "
                    : " `album_disk`.`id` IN (SELECT `id` FROM `rating` WHERE `object_type` = 'album_disk' AND `user` = " . (int)$query->user_id . " AND `rating` = " . Dba::escape($value) . ") AND ";
                break;
            case 'catalog_enabled':
                $query->set_join('LEFT', '`catalog`', '`catalog`.`id`', '`album_disk`.`catalog`', 100);
                $filter_sql = " `catalog`.`enabled` = '1' AND ";
                break;
            case 'unplayed':
                if ((int)$value == 1) {
                    $filter_sql = " `album_disk`.`total_count`='0' AND ";
                }
                break;
        }
        $filter_sql .= " `album`.`id` IS NOT NULL AND ";

        return $filter_sql;
    }

    /**
     * get_sql_sort
     *
     * Sorting SQL for ORDER BY
     * @param Query $query
     * @param string|null $field
     * @param string|null $order
     * @return string
     */
    public function get_sql_sort($query, $field, $order): string
    {
        $query->set_join('LEFT', '`album`', '`album_disk`.`album_id`', '`album`.`id`', 10);
        switch ($field) {
            case 'name':
            case 'title':
                $sql   = "`album`.`name` $order, `album_disk`.`disk`";
                $order = '';
                break;
            case 'name_original_year':
                $sql = "`album`.`name` $order, IFNULL(`album`.`original_year`, `album`.`year`) $order, `album_disk`.`disk`";
                break;
            case 'name_year':
                $sql   = "`album`.`name` $order, `album`.`year` $order, `album_disk`.`disk`";
                $order = '';
                break;
            case 'generic_artist':
                $sql = "`artist`.`name`";
                $query->set_join('LEFT', '`song`', '`song`.`album`', '`album`.`id`', 100);
                $query->set_join(
                    'LEFT',
                    '`artist`',
                    'COALESCE(`album`.`album_artist`, `song`.`artist`)',
                    '`artist`.`id`',
                    100
                );
                break;
            case 'album_artist_album_sort':
                $sql = "`artist`.`name` $order, ";
                // sort the albums by arist AND default sort
                $original_year = (AmpConfig::get('use_original_year')) ? "original_year" : "year";
                $sort_type     = AmpConfig::get('album_sort');
                switch ($sort_type) {
                    case 'name_asc':
                        $sql .= '`album`.`name` ' . $order . ', `album_disk`.`disk`';
                        $order = 'ASC';
                        break;
                    case 'name_desc':
                        $sql .= '`album`.`name` ' . $order . ', `album_disk`.`disk`';
                        $order = 'DESC';
                        break;
                    case 'year_asc':
                        $sql .= '`album`.`' . $original_year . '` ' . $order . ', `album_disk`.`disk`';
                        $order = 'ASC';
                        break;
                    case 'year_desc':
                        $sql .= '`album`.`' . $original_year . '` ' . $order . ', `album_disk`.`disk`';
                        $order = 'DESC';
                        break;
                    case 'default':
                    default:
                        $sql .= '`album`.`name` ' . $order . ', `album_disk`.`disk`, `album`.`' . $original_year . '`';
                }
                $query->set_join('LEFT', '`artist`', '`album`.`album_artist`', '`artist`.`id`', 100);
                break;
            case 'album_artist_title':
                $sql = "`artist`.`name` $order, `album`.`name` $order, `album_disk`.`disk`";
                $query->set_join('LEFT', '`artist`', '`album`.`album_artist`', '`artist`.`id`', 100);
                break;
            case 'album_artist':
                $sql = "`artist`.`name`";
                $query->set_join('LEFT', '`artist`', '`album`.`album_artist`', '`artist`.`id`', 100);
                break;
            case 'artist':
                $sql = "`artist`.`name`";
                $query->set_join('LEFT', '`song`', '`song`.`album`', '`album`.`id`', 50);
                $query->set_join('LEFT', '`artist`', '`song`.`artist`', '`artist`.`id`', 100);
                break;
            case 'rating':
                $sql = "`rating`.`rating` $order, `rating`.`date`";
                $query->set_join_and_and('LEFT', "`rating`", "`rating`.`object_id`", "`album_disk`.`id`", "`rating`.`object_type`", "'album_disk'", "`rating`.`user`", (string)$query->user_id, 100);
                break;
            case 'user_flag':
            case 'userflag':
                $sql = "`user_flag`.`date`";
                $query->set_join_and_and('LEFT', "`user_flag`", "`user_flag`.`object_id`", "`album_disk`.`id`", "`user_flag`.`object_type`", "'album_disk'", "`user_flag`.`user`", (string)$query->user_id, 100);
                break;
            case 'user_flag_rating':
                $sql = "`user_flag`.`date` $order, `rating`.`rating` $order, `rating`.`date`";
                $query->set_join_and_and('LEFT', "`user_flag`", "`user_flag`.`object_id`", "`album_disk`.`id`", "`user_flag`.`object_type`", "'album_disk'", "`user_flag`.`user`", (string)$query->user_id, 100);
                $query->set_join_and_and('LEFT', "`rating`", "`rating`.`object_id`", "`album_disk`.`id`", "`rating`.`object_type`", "'album_disk'", "`rating`.`user`", (string)$query->user_id, 100);
                break;
            case 'original_year':
                $sql   = "IFNULL(`album`.`original_year`, `album`.`year`) $order, `album`.`addition_time` $order, `album`.`name`, `album_disk`.`disk`";
                $order = '';
                break;
            case 'year':
                $sql   = "`album`.`year` $order, `album`.`addition_time` $order, `album`.`name`, `album_disk`.`disk`";
                $order = '';
                break;
            case 'album_id':
            case 'catalog':
            case 'disk_count':
            case 'disk':
            case 'disksubtitle':
            case 'song_count':
            case 'time':
            case 'total_count':
                $sql   = "`album_disk`.`$field` $order, `album`.`name`, `album_disk`.`disk`";
                $order = '';
                break;
            case 'barcode':
            case 'catalog_number':
            case 'release_status':
            case 'release_type':
            case 'subtitle':
            case 'version':
                $sql   = "`album`.`$field` $order, `album`.`name`, `album_disk`.`disk`";
                $order = '';
                break;
            case 'id':
                $sql   = "`album_disk`.`id`";
                $order = '';
                break;
            default:
                $sql = '';
        }

        if (empty($sql)) {
            return '';
        }

        return "$sql $order,";
    }
}
