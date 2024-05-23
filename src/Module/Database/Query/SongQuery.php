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
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Query;

final class SongQuery implements QueryInterface
{
    public const FILTERS = array(
        'add_gt',
        'add_lt',
        'album',
        'album_disk',
        'alpha_match',
        'artist',
        'catalog',
        'catalog_enabled',
        'disk',
        'enabled',
        'exact_match',
        'genre',
        'license',
        'regex_match',
        'regex_not_match',
        'starts_with',
        'not_starts_with',
        'tag',
        'top50',
        'unplayed',
        'update_gt',
        'update_lt',
        'user_catalog'
    );

    /** @var string[] $sorts */
    protected array $sorts = array(
        'title',
        'name',
        'catalog',
        'year',
        'track',
        'time',
        'composer',
        'addition_time',
        'update_time',
        'object_count',
        'total_count',
        'total_skip',
        'album',
        'album_disk',
        'artist',
        'rand',
        'rating',
        'user_flag'
    );

    /** @var string */
    protected $select = "`song`.`id`";

    /** @var string */
    protected $base = "SELECT %%SELECT%% FROM `song` ";

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
            case 'top50':
                $query->set_join_and('LEFT', '`artist_map`', '`artist_map`.`object_id`', '`song`.`id`', '`artist_map`.`object_type`', "'song'", 50);
                $query->set_join_and_and('LEFT', '`object_count`', '`object_count`.`object_id`', '`song`.`id`', '`object_count`.`object_type`', "'song'", '`object_count`.`count_type`', "'stream'", 100);
                $filter_sql = " `artist_map`.`artist_id` = " . Dba::escape($value) . " AND ";
                break;
            case 'genre':
            case 'tag':
                $query->set_join('LEFT', '`tag_map`', '`tag_map`.`object_id`', '`song`.`id`', 100);
                $filter_sql = " `tag_map`.`object_type`='" . $query->get_type() . "' AND (";

                foreach ($value as $tag_id) {
                    $filter_sql .= "`tag_map`.`tag_id`='" . Dba::escape($tag_id) . "' AND ";
                }
                $filter_sql = rtrim((string) $filter_sql, 'AND ') . ") AND ";
                break;
            case 'exact_match':
                $filter_sql = " `song`.`title` = '" . Dba::escape($value) . "' AND ";
                break;
            case 'alpha_match':
                $filter_sql = " `song`.`title` LIKE '%" . Dba::escape($value) . "%' AND ";
                break;
            case 'regex_match':
                if (!empty($value)) {
                    $filter_sql = " `song`.`title` REGEXP '" . Dba::escape($value) . "' AND ";
                }
                break;
            case 'regex_not_match':
                if (!empty($value)) {
                    $filter_sql = " `song`.`title` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                }
                break;
            case 'starts_with':
                $filter_sql = " `song`.`title` LIKE '" . Dba::escape($value) . "%' AND ";
                if ($query->catalog != 0) {
                    $filter_sql .= " `song`.`catalog` = '" . $query->catalog . "' AND ";
                }
                break;
            case 'not_starts_with':
                $filter_sql = " `song`.`title` NOT LIKE '" . Dba::escape($value) . "%' AND ";
                if ($query->catalog != 0) {
                    $filter_sql .= " `song`.`catalog` = '" . $query->catalog . "' AND ";
                }
                break;
            case 'unplayed':
                if ((int)$value == 1) {
                    $filter_sql = " `song`.`played`='0' AND ";
                }
                break;
            case 'album':
                $filter_sql = " `song`.`album` = '" . Dba::escape($value) . "' AND ";
                break;
            case 'album_disk':
                $query->set_join_and('LEFT', '`album_disk`', '`album_disk`.`album_id`', '`song`.`album`', '`album_disk`.`disk`', '`song`.`disk`', 100);
                $filter_sql = " `album_disk`.`id` = '" . Dba::escape($value) . "' AND ";
                break;
            case 'disk':
                $filter_sql = " `song`.`disk` = '" . Dba::escape($value) . "' AND ";
                break;
            case 'artist':
                $filter_sql = " `song`.`id` IN (SELECT `object_id` FROM `artist_map` WHERE `artist_map`.`artist_id` = '" . Dba::escape($value) . "' AND `artist_map`.`object_type` = 'song') AND ";
                break;
            case 'add_lt':
                $filter_sql = " `song`.`addition_time` <= '" . Dba::escape($value) . "' AND ";
                break;
            case 'add_gt':
                $filter_sql = " `song`.`addition_time` >= '" . Dba::escape($value) . "' AND ";
                break;
            case 'update_lt':
                $filter_sql = " `song`.`update_time` <= '" . Dba::escape($value) . "' AND ";
                break;
            case 'update_gt':
                $filter_sql = " `song`.`update_time` >= '" . Dba::escape($value) . "' AND ";
                break;
            case 'catalog':
                if ($value != 0) {
                    $filter_sql = " `song`.`catalog` = '" . Dba::escape($value) . "' AND ";
                }
                break;
            case 'user_catalog':
                $filter_sql = " `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $query->user_id, true)) . ") AND ";
                break;
            case 'catalog_enabled':
                $query->set_join('LEFT', '`catalog`', '`catalog`.`id`', '`song`.`catalog`', 100);
                $filter_sql = " `catalog`.`enabled` = '1' AND ";
                break;
            case 'license':
                $filter_sql = " `song`.`license` = '" . (int)$value . "' AND ";
                break;
            case 'enabled':
                $filter_sql = " `song`.`enabled`= '" . Dba::escape($value) . "' AND ";
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
            case 'title':
                $sql = "`song`.`title`";
                break;
            case 'catalog':
            case 'year':
            case 'track':
            case 'time':
            case 'composer':
            case 'addition_time':
            case 'update_time':
            case 'total_count':
            case 'total_skip':
                $sql = "`song`.`$field`";
                break;
            case 'album':
                $sql   = "`album`.`name` $order, `song`.`disk`, `song`.`track`";
                $order = '';
                $query->set_join('LEFT', "`album`", "`album`.`id`", "`song`.`album`", 100);
                break;
            case 'album_disk':
                $sql   = "`album`.`name` $order, `album_disk`.`disk`, `song`.`track`";
                $order = '';
                $query->set_join('LEFT', "`album`", "`album`.`id`", "`song`.`album`", 100);
                $query->set_join_and('LEFT', '`album_disk`', '`album_disk`.`album_id`', '`song`.`album`', '`album_disk`.`disk`', '`song`.`disk`', 100);
                break;
            case 'artist':
                $sql = "`artist`.`name`";
                $query->set_join('LEFT', "`artist`", "`artist`.`id`", "`song`.`artist`", 100);
                break;
            case 'rating':
                $sql = "`rating`.`rating` $order, `rating`.`date`";
                $query->set_join_and_and('LEFT', "`rating`", "`rating`.`object_id`", "`song`.`id`", "`rating`.`object_type`", "'song'", "`rating`.`user`", (string)$query->user_id, 100);
                break;
            case 'user_flag':
                $sql = "`user_flag`.`date`";
                $query->set_join_and_and('LEFT', "`user_flag`", "`user_flag`.`object_id`", "`song`.`id`", "`user_flag`.`object_type`", "'song'", "`user_flag`.`user`", (string)$query->user_id, 100);
                break;
            case 'object_count':
                $sql = "count(`object_count`.`object_id`)";
                $query->set_join_and_and('LEFT', '`object_count`', '`object_count`.`object_id`', '`song`.`id`', '`object_count`.`object_type`', "'song'", '`object_count`.`count_type`', "'stream'", 100);
                $query->set_group('song_id', '`song`.`id`', 100);
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
