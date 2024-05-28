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

use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Query;

final class PlaylistQuery implements QueryInterface
{
    public const FILTERS = array(
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
    );

    /** @var string[] $sorts */
    protected array $sorts = array(
        'rand',
        'date',
        'last_count',
        'last_update',
        'title',
        'name',
        'rating',
        'type',
        'user',
        'username',
        'user_flag'
    );

    /** @var string */
    protected $select = "`playlist`.`id`";

    /** @var string */
    protected $base = "SELECT %%SELECT%% FROM `playlist` ";

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
            case 'equal':
            case 'exact_match':
                $filter_sql = " `playlist`.`name` = '" . Dba::escape($value) . "' AND ";
                break;
            case 'like':
            case 'alpha_match':
                $filter_sql = " `playlist`.`name` LIKE '%" . Dba::escape($value) . "%' AND ";
                break;
            case 'not_like':
                $filter_sql = " `playlist`.`name` NOT LIKE '%" . Dba::escape($value) . "%' AND ";
                break;
            case 'regex_match':
                if (!empty($value)) {
                    $filter_sql = " `playlist`.`name` REGEXP '" . Dba::escape($value) . "' AND ";
                }
                break;
            case 'regex_not_match':
                if (!empty($value)) {
                    $filter_sql = " `playlist`.`name` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                }
                break;
            case 'starts_with':
                $filter_sql = " `playlist`.`name` LIKE '" . Dba::escape($value) . "%' AND ";
                break;
            case 'not_starts_with':
                $filter_sql = " `playlist`.`name` NOT LIKE '" . Dba::escape($value) . "%' AND ";
                break;
            case 'playlist_open':
                $filter_sql = "  (`playlist`.`type` = 'public' OR `playlist`.`user`=" . (int)$value . ") AND ";
                break;
            case 'playlist_user':
                $filter_sql = " `playlist`.`user` = " . (int)$value . " AND ";
                break;
            case 'playlist_type':
                $user_id = (!empty(Core::get_global('user')) && Core::get_global('user')->id > 0)
                    ? Core::get_global('user')->id
                    : -1;
                if ($value == 0) {
                    $filter_sql = " (`playlist`.`user`='$user_id') AND ";
                } else {
                    $filter_sql = " (`playlist`.`type` = 'public' OR `playlist`.`user`='$user_id') AND ";
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
                $sql = "`playlist`.`name`";
                break;
            case 'date':
            case 'last_count':
            case 'last_update':
            case 'type':
            case 'user':
            case 'username':
                $sql = "`playlist`.`$field`";
                break;
            case 'rating':
                $sql = "`rating`.`rating` $order, `rating`.`date`";
                $query->set_join_and_and('LEFT', "`rating`", "`rating`.`object_id`", "`playlist`.`id`", "`rating`.`object_type`", "'playlist'", "`rating`.`user`", (string)$query->user_id, 100);
                break;
            case 'user_flag':
                $sql = "`user_flag`.`date`";
                $query->set_join_and_and('LEFT', "`user_flag`", "`user_flag`.`object_id`", "`playlist`.`id`", "`user_flag`.`object_type`", "'playlist'", "`user_flag`.`user`", (string)$query->user_id, 100);
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
