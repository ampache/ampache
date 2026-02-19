<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

final class UserQuery implements QueryInterface
{
    public const FILTERS = [
        'access',
        'alpha_match',
        'disabled',
        'equal',
        'exact_match',
        'id',
        'like',
        'not_like',
        'not_starts_with',
        'regex_match',
        'regex_not_match',
        'starts_with',
    ];

    /** @var string[] $sorts */
    protected array $sorts = [
        'access',
        'city',
        'create_date',
        'disabled',
        'email',
        'fullname_public',
        'fullname',
        'id',
        'last_seen',
        'rand',
        'state',
        'username',
        'website',
    ];

    protected string $select = "`user`.`id`";

    protected string $base = "SELECT %%SELECT%% FROM `user` ";

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
                $filter_sql = " `user`.`id` IN (";
                foreach ($value as $uid) {
                    $filter_sql .= (int)$uid . ',';
                }
                $filter_sql = rtrim($filter_sql, ',') . ") AND ";
                break;
            case 'equal':
            case 'exact_match':
                $filter_sql = " (`user`.`fullname` = '" . Dba::escape($value) . "' OR `user`.`username` = '" . Dba::escape($value) . "' OR `user`.`email` = '" . Dba::escape($value) . "') AND ";
                break;
            case 'like':
            case 'alpha_match':
                $filter_sql = " (`user`.`fullname` LIKE '%" . Dba::escape($value) . "%' OR `user`.`username` LIKE '%" . Dba::escape($value) . "%' OR `user`.`email` LIKE '%" . Dba::escape($value) . "%') AND ";
                break;
            case 'not_like':
                $filter_sql = " (`user`.`fullname` NOT LIKE '%" . Dba::escape($value) . "%' OR `user`.`username` LIKE '%" . Dba::escape($value) . "%' OR `user`.`email` NOT LIKE '%" . Dba::escape($value) . "%') AND ";
                break;
            case 'regex_match':
                if (!empty($value)) {
                    $filter_sql = " (`user`.`fullname` REGEXP '" . Dba::escape($value) . "' OR " .
                        "`user`.`username` REGEXP '" . Dba::escape($value) . "' OR " .
                        "`user`.`email` REGEXP '" . Dba::escape($value) . "') AND ";
                }
                break;
            case 'regex_not_match':
                if (!empty($value)) {
                    $filter_sql = " (`user`.`fullname` NOT REGEXP '" . Dba::escape($value) . "' OR " .
                        "`user`.`username` NOT REGEXP '" . Dba::escape($value) . "' OR " .
                        "`user`.`email` NOT REGEXP '" . Dba::escape($value) . "') AND ";
                }
                break;
            case 'starts_with':
                $filter_sql = " (`user`.`fullname` LIKE '" . Dba::escape($value) . "%' OR `user`.`username` LIKE '" . Dba::escape($value) . "%' OR `user`.`email` LIKE '" . Dba::escape($value) . "%') AND ";
                break;
            case 'not_starts_with':
                $filter_sql = " (`user`.`fullname` NOT LIKE '" . Dba::escape($value) . "%' OR `user`.`username` NOT LIKE '" . Dba::escape($value) . "%' OR `user`.`email` NOT LIKE '" . Dba::escape($value) . "%') AND ";
                break;
            case 'access':
            case 'disabled':
                $filter_sql = " `user`.`$filter` = " . (int)$value . " AND ";
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
            case 'access':
            case 'city':
            case 'create_date':
            case 'disabled':
            case 'email':
            case 'fullname_public':
            case 'fullname':
            case 'id':
            case 'last_seen':
            case 'state':
            case 'username':
            case 'website':
                $sql = "`user`.`$field`";
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
