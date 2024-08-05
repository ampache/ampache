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
use Ampache\Repository\Model\Query;

final class TagQuery implements QueryInterface
{
    public const FILTERS = [
        'id',
        'alpha_match',
        'equal',
        'like',
        'exact_match',
        'hidden',
        'genre',
        'object_type',
        'regex_match',
        'regex_not_match',
        'starts_with',
        'not_starts_with',
        'not_like',
        'tag',
    ];

    /** @var string[] $sorts */
    protected array $sorts = [
        'id',
        'rand',
        'tag',
        'name',
        'rating',
        'user_flag',
        'userflag',
        'user_flag_rating',
    ];

    /** @var string */
    protected $select = "`tag`.`id`";

    /** @var string */
    protected $base = "SELECT %%SELECT%% FROM `tag` ";

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
            case 'id':
                $filter_sql = " `tag`.`id` IN (";
                foreach ($value as $uid) {
                    $filter_sql .= (int)$uid . ',';
                }
                $filter_sql = rtrim($filter_sql, ',') . ") AND ";
                break;
            case 'equal':
            case 'exact_match':
                $filter_sql = " `tag`.`name` = '" . Dba::escape($value) . "' AND ";
                break;
            case 'like':
            case 'alpha_match':
                $filter_sql = " `tag`.`name` LIKE '%" . Dba::escape($value) . "%' AND ";
                break;
            case 'not_like':
                $filter_sql = " `tag`.`name` NOT LIKE '%" . Dba::escape($value) . "%' AND ";
                break;
            case 'regex_match':
                if (!empty($value)) {
                    $filter_sql = " `tag`.`name` REGEXP '" . Dba::escape($value) . "' AND ";
                }
                break;
            case 'regex_not_match':
                if (!empty($value)) {
                    $filter_sql = " `tag`.`name` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                }
                break;
            case 'starts_with':
                $filter_sql = " `tag`.`name` LIKE '" . Dba::escape($value) . "%' AND ";
                break;
            case 'not_starts_with':
                $filter_sql = " `tag`.`name` NOT LIKE '" . Dba::escape($value) . "%' AND ";
                break;
            case 'genre':
            case 'tag':
                $filter_sql = " `tag`.`id` = '" . Dba::escape($value) . "' AND ";
                break;
            case 'hidden':
                $filter_sql = " `tag`.`is_hidden` = " . Dba::escape($value) . " AND ";
                break;
            case 'object_type':
                $query->set_join('LEFT', '`tag_map`', '`tag_map`.`tag_id`', '`tag`.`id`', 100);
                $filter_sql = " `tag_map`.`object_type` = '" . Dba::escape($value) . "' AND ";
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
            case 'id':
            case 'genre':
            case 'tag':
                $sql = "`tag`.`id`";
                break;
            case 'name':
            case 'title':
                $sql = "`tag`.`name`";
                break;
            case 'rating':
                $sql = "`rating`.`rating` $order, `rating`.`date`";
                $query->set_join_and_and('LEFT', "`rating`", "`rating`.`object_id`", "`tag`.`id`", "`rating`.`object_type`", "'tag'", "`rating`.`user`", (string)$query->user_id, 100);
                break;
            case 'user_flag':
            case 'userflag':
                $sql = "`user_flag`.`date`";
                $query->set_join_and_and('LEFT', "`user_flag`", "`user_flag`.`object_id`", "`tag`.`id`", "`user_flag`.`object_type`", "'tag'", "`user_flag`.`user`", (string)$query->user_id, 100);
                break;
            case 'user_flag_rating':
                $sql = "`user_flag`.`date` $order, `rating`.`rating` $order, `rating`.`date`";
                $query->set_join_and_and('LEFT', "`user_flag`", "`user_flag`.`object_id`", "`tag`.`id`", "`user_flag`.`object_type`", "'tag'", "`user_flag`.`user`", (string)$query->user_id, 100);
                $query->set_join_and_and('LEFT', "`rating`", "`rating`.`object_id`", "`tag`.`id`", "`rating`.`object_type`", "'tag'", "`rating`.`user`", (string)$query->user_id, 100);
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
