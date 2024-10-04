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

use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Query;

final class SmartplaylistQuery implements QueryInterface
{
    public const FILTERS = [
        'id',
        'alpha_match',
        'equal',
        'like',
        'exact_match',
        'playlist_open',
        'playlist_type',
        'playlist_user',
        'regex_match',
        'regex_not_match',
        'starts_with',
        'not_starts_with',
        'not_like',
    ];

    /** @var string[] $sorts */
    protected array $sorts = [
        'id',
        'date',
        'last_count',
        'last_update',
        'title',
        'name',
        'limit',
        'rand',
        'random',
        'rating',
        'type',
        'user',
        'username',
        'user_flag',
        'userflag',
        'user_flag_rating',
    ];

    protected string $select = "`search`.`id`";

    protected string $base = "SELECT %%SELECT%% FROM `search` ";

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
                $filter_sql = " `search`.`id` IN (";
                foreach ($value as $uid) {
                    $filter_sql .= (int)$uid . ',';
                }
                $filter_sql = rtrim($filter_sql, ',') . ") AND ";
                break;
            case 'equal':
            case 'exact_match':
                $filter_sql = " `search`.`name` = '" . Dba::escape($value) . "' AND ";
                break;
            case 'like':
            case 'alpha_match':
                $filter_sql = " `search`.`name` LIKE '%" . Dba::escape($value) . "%' AND ";
                break;
            case 'not_like':
                $filter_sql = " `search`.`name` NOT LIKE '%" . Dba::escape($value) . "%' AND ";
                break;
            case 'regex_match':
                if (!empty($value)) {
                    $filter_sql = " `search`.`name` REGEXP '" . Dba::escape($value) . "' AND ";
                }
                break;
            case 'regex_not_match':
                if (!empty($value)) {
                    $filter_sql = " `search`.`name` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                }
                break;
            case 'starts_with':
                $filter_sql = " `search`.`name` LIKE '" . Dba::escape($value) . "%' AND ";
                break;
            case 'not_starts_with':
                $filter_sql = " `search`.`name` NOT LIKE '" . Dba::escape($value) . "%' AND ";
                break;
            case 'playlist_open':
                $filter_sql = " (`search`.`type` = 'public' OR `search`.`user`=" . (int)$value . ") AND ";
                break;
            case 'playlist_user':
                $filter_sql = " `search`.`user` = " . (int)$value . " AND ";
                break;
            case 'playlist_type':
                $user_id = (!empty(Core::get_global('user')) && Core::get_global('user')->id > 0)
                    ? Core::get_global('user')->id
                    : -1;
                if ($value == 0) {
                    $filter_sql = " (`search`.`user`='$user_id') AND ";
                } else {
                    $filter_sql = " (`search`.`type` = 'public' OR `search`.`user`='$user_id') AND ";
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
            case 'title':
                $sql = "`search`.`name`";
                break;
            case 'id':
            case 'date':
            case 'last_count':
            case 'last_update':
            case 'limit':
            case 'random':
            case 'type':
            case 'user':
            case 'username':
                $sql = "`search`.`$field`";
                break;
            case 'rating':
                $sql = "`rating`.`rating` $order, `rating`.`date`";
                $query->set_join_and_and('LEFT', "`rating`", "`rating`.`object_id`", "`search`.`id`", "`rating`.`object_type`", "'search'", "`rating`.`user`", (string)$query->user_id, 100);
                break;
            case 'user_flag':
            case 'userflag':
                $sql = "`user_flag`.`date`";
                $query->set_join_and_and('LEFT', "`user_flag`", "`user_flag`.`object_id`", "`search`.`id`", "`user_flag`.`object_type`", "'search'", "`user_flag`.`user`", (string)$query->user_id, 100);
                break;
            case 'user_flag_rating':
                $sql = "`user_flag`.`date` $order, `rating`.`rating` $order, `rating`.`date`";
                $query->set_join_and_and('LEFT', "`user_flag`", "`user_flag`.`object_id`", "`search`.`id`", "`user_flag`.`object_type`", "'search'", "`user_flag`.`user`", (string)$query->user_id, 100);
                $query->set_join_and_and('LEFT', "`rating`", "`rating`.`object_id`", "`search`.`id`", "`rating`.`object_type`", "'search'", "`rating`.`user`", (string)$query->user_id, 100);
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
