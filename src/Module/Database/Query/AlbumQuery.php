<?php

declare(strict_types=0);

/**
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

namespace Ampache\Module\Database\Query;

use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Query;

final class AlbumQuery implements QueryInterface
{
    public const FILTERS = array(
        'add_gt',
        'add_lt',
        'alpha_match',
        'artist',
        'album_artist',
        'song_artist',
        'catalog',
        'catalog_enabled',
        'exact_match',
        'regex_match',
        'regex_not_match',
        'starts_with',
        'tag',
        'unplayed',
        'update_gt',
        'update_lt'
    );

    /** @var string[] $sorts */
    protected array $sorts = array(
        'album_artist',
        'artist',
        'barcode',
        'catalog',
        'catalog_number',
        'generic_artist',
        'name',
        'name_year',
        'name_original_year',
        'original_year',
        'rand',
        'release_status',
        'release_type',
        'disk_count',
        'song_count',
        'subtitle',
        'time',
        'total_count',
        'version',
        'year',
        'rating',
        'user_flag'
    );

    /** @var string */
    protected $select = "`album`.`id`";

    /** @var string */
    protected $base = "SELECT %%SELECT%% AS `id` FROM `album` ";

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
     * @param Query $query
     * @param string $filter
     * @param mixed $value
     * @return string
     */
    public function get_sql_filter($query, $filter, $value): string
    {
        $filter_sql = '';
        switch ($filter) {
            case 'tag':
                $query->set_join('LEFT', '`tag_map`', '`tag_map`.`object_id`', '`album`.`id`', 100);
                $filter_sql = "`tag_map`.`object_type`='" . $query->get_type() . "' AND (";

                foreach ($value as $tag_id) {
                    $filter_sql .= "`tag_map`.`tag_id`='" . Dba::escape($tag_id) . "' AND ";
                }
                $filter_sql = rtrim((string) $filter_sql, 'AND ') . ") AND ";
                break;
            case 'exact_match':
                $filter_sql = " `album`.`name` = '" . Dba::escape($value) . "' AND ";
                break;
            case 'alpha_match':
                $filter_sql = " `album`.`name` LIKE '%" . Dba::escape($value) . "%' AND ";
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
                    $filter_sql .= "`album`.`catalog` = '" . $query->catalog . "' AND ";
                }
                break;
            case 'artist':
                $filter_sql = " `album`.`id` IN (SELECT `album_id` FROM `album_map` WHERE `album_map`.`object_id` = '" . Dba::escape($value) . "') AND ";
                break;
            case 'album_artist':
                $filter_sql = " `album`.`id` IN (SELECT `album_id` FROM `album_map` WHERE `album_map`.`object_id` = '" . Dba::escape($value) . "' AND `album_map`.`object_type` = 'album') AND ";
                break;
            case 'song_artist':
                $filter_sql = " `album`.`id` IN (SELECT `album_id` FROM `album_map` WHERE `album_map`.`object_id` = '" . Dba::escape($value) . "' AND `album_map`.`object_type` = 'song') AND ";
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
                    $filter_sql = " (`album`.`catalog` = '" . Dba::escape($value) . "') AND ";
                }
                break;
            case 'catalog_enabled':
                $query->set_join('LEFT', '`catalog`', '`catalog`.`id`', '`album`.`catalog`', 100);
                $filter_sql = " `catalog`.`enabled` = '1' AND ";
                break;
            case 'unplayed':
                if ((int)$value == 1) {
                    $filter_sql = " `album`.`total_count`='0' AND ";
                }
                break;
        }

        return $filter_sql;
    }

    /**
     * get_sql_sort
     *
     * Sorting SQL for ORDER BY
     * @param Query $query
     * @param string $field
     * @param string $order
     * @return string
     */
    public function get_sql_sort($query, $field, $order): string
    {
        switch ($field) {
            case 'name':
                $sql = "`album`.`name`";
                break;
            case 'name_original_year':
                $sql = "`album`.`name` $order, IFNULL(`album`.`original_year`, `album`.`year`)";
                break;
            case 'name_year':
                $sql = "`album`.`name` $order, `album`.`year`";
                break;
            case 'generic_artist':
                $sql = "`artist`.`name`";
                $query->set_join('LEFT', '`song`', '`song`.`album`', '`album`.`id`', 50);
                $query->set_join(
                    'LEFT',
                    '`artist`',
                    'COALESCE(`album`.`album_artist`, `song`.`artist`)',
                    '`artist`.`id`',
                    100
                );
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
                $query->set_join_and_and('LEFT', "`rating`", "`rating`.`object_id`", "`album`.`id`", "`rating`.`object_type`", "'album'", "`rating`.`user`", (string)$query->user_id, 100);
                break;
            case 'user_flag':
                $sql = "`user_flag`.`date`";
                $query->set_join_and_and('LEFT', "`user_flag`", "`user_flag`.`object_id`", "`album`.`id`", "`user_flag`.`object_type`", "'album'", "`user_flag`.`user`", (string)$query->user_id, 100);
                break;
            case 'original_year':
                $sql = "IFNULL(`album`.`original_year`, `album`.`year`) $order, `album`.`addition_time`";
                break;
            case 'year':
                $sql = "`album`.`year` $order, `album`.`addition_time`";
                break;
            case 'catalog':
            case 'disk_count':
            case 'song_count':
            case 'total_count':
            case 'release_type':
            case 'release_status':
            case 'barcode':
            case 'catalog_number':
            case 'subtitle':
            case 'time':
            case 'version':
                $sql = "`album`.`$field`";
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
