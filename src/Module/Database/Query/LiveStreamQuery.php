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

use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Query;

final class LiveStreamQuery implements QueryInterface
{
    public const FILTERS = [
        'alpha_match',
        'catalog_enabled',
        'catalog',
        'equal',
        'exact_match',
        'id',
        'like',
        'not_like',
        'not_starts_with',
        'regex_match',
        'regex_not_match',
        'starts_with',
        'user_catalog',
        'user_flag',
        'user_rating',
    ];

    /** @var string[] $sorts */
    protected array $sorts = [
        'catalog',
        'catalog',
        'codec',
        'genre',
        'id',
        'name',
        'rating',
        'site_url',
        'title',
        'url',
        'user_flag_rating',
        'user_flag',
        'userflag',
    ];

    protected string $select = "`live_stream`.`id`";

    protected string $base = "SELECT %%SELECT%% FROM `live_stream` ";

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
        switch ($filter) {
            case 'id':
                $filter_sql = " `live_stream`.`id` IN (";
                foreach ($value as $uid) {
                    $filter_sql .= (int)$uid . ',';
                }
                $filter_sql = rtrim($filter_sql, ',') . ") AND ";
                break;
            case 'equal':
            case 'exact_match':
                $filter_sql = " `live_stream`.`name` = '" . Dba::escape($value) . "' AND ";
                break;
            case 'like':
            case 'alpha_match':
                $filter_sql = " `live_stream`.`name` LIKE '%" . Dba::escape($value) . "%' AND ";
                break;
            case 'not_like':
                $filter_sql = " `live_stream`.`name` NOT LIKE '%" . Dba::escape($value) . "%' AND ";
                break;
            case 'regex_match':
                if (!empty($value)) {
                    $filter_sql = " `live_stream`.`name` REGEXP '" . Dba::escape($value) . "' AND ";
                }
                break;
            case 'regex_not_match':
                if (!empty($value)) {
                    $filter_sql = " `live_stream`.`name` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                }
                break;
            case 'starts_with':
                $filter_sql = " `live_stream`.`name` LIKE '" . Dba::escape($value) . "%' AND ";
                break;
            case 'not_starts_with':
                $filter_sql = " `live_stream`.`name` NOT LIKE '" . Dba::escape($value) . "%' AND ";
                break;
            case 'catalog':
                if ($value != 0) {
                    $filter_sql = " (`live_stream`.`catalog` = '" . Dba::escape($value) . "') AND ";
                }
                break;
            case 'user_catalog':
                $filter_sql = " `live_stream`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $query->user_id, true)) . ") AND ";
                break;
            case 'user_flag':
                $filter_sql = ((int)$value === 0)
                    ? " `live_stream`.`id` NOT IN (SELECT `object_id` FROM `user_flag` WHERE `object_type` = 'live_stream' AND `user` = " . (int)$query->user_id . ") AND "
                    : " `live_stream`.`id` IN (SELECT `object_id` FROM `user_flag` WHERE `object_type` = 'live_stream' AND `user` = " . (int)$query->user_id . ") AND ";
                break;
            case 'user_rating':
                $filter_sql = ((int)$value === 0)
                    ? " `live_stream`.`id` NOT IN (SELECT `object_id` FROM `rating` WHERE `object_type` = 'live_stream' AND `user` = " . (int)$query->user_id . ") AND "
                    : " `live_stream`.`id` IN (SELECT `object_id` FROM `rating` WHERE `object_type` = 'live_stream' AND `user` = " . (int)$query->user_id . " AND `rating` = " . Dba::escape($value) . ") AND ";
                break;
            case 'catalog_enabled':
                $query->set_join('LEFT', '`catalog`', '`catalog`.`id`', '`live_stream`.`catalog`', 100);
                $filter_sql = " `catalog`.`enabled` = '1' AND ";
                break;
        }

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
        switch ($field) {
            case 'name':
            case 'title':
                $sql = "`live_stream`.`name`";
                break;
            case 'catalog':
            case 'codec':
            case 'genre':
            case 'id':
            case 'site_url':
            case 'url':
                $sql = "`live_stream`.`$field`";
                break;
            case 'rating':
                $sql = "`rating`.`rating` $order, `rating`.`date`";
                $query->set_join_and_and('LEFT', "`rating`", "`rating`.`object_id`", "`live_stream`.`id`", "`rating`.`object_type`", "'live_stream'", "`rating`.`user`", (string)$query->user_id, 100);
                break;
            case 'user_flag':
            case 'userflag':
                $sql = "`user_flag`.`date`";
                $query->set_join_and_and('LEFT', "`user_flag`", "`user_flag`.`object_id`", "`live_stream`.`id`", "`user_flag`.`object_type`", "'live_stream'", "`user_flag`.`user`", (string)$query->user_id, 100);
                break;
            case 'user_flag_rating':
                $sql = "`user_flag`.`date` $order, `rating`.`rating` $order, `rating`.`date`";
                $query->set_join_and_and('LEFT', "`user_flag`", "`user_flag`.`object_id`", "`live_stream`.`id`", "`user_flag`.`object_type`", "'live_stream'", "`user_flag`.`user`", (string)$query->user_id, 100);
                $query->set_join_and_and('LEFT', "`rating`", "`rating`.`object_id`", "`live_stream`.`id`", "`rating`.`object_type`", "'live_stream'", "`rating`.`user`", (string)$query->user_id, 100);
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
