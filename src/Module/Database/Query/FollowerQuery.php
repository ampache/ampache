<?php

declare(strict_types=1);

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

final class FollowerQuery implements QueryInterface
{
    public const array FILTERS = [
        'follow_user',
        'user',
    ];

    protected string $base = "SELECT %%SELECT%% FROM `user_follower` ";

    // a row is rendered and returned as the account doing the following, so the id has to be that user, not the follow
    protected string $select = "`user_follower`.`follow_user`";

    /** @var string[] $sorts */
    protected array $sorts = [
        'create_date',
        'follow_date',
        'follow_user',
        'last_seen',
        'user',
        'username',
    ];

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
     * get_select
     *
     * This method returns the columns a query will user for SELECT
     */
    public function get_select(): string
    {
        return $this->select;
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

        return match ($filter) {
            'follow_user', 'user' => sprintf(" `user_follower`.`%s` = '", $filter) . (int) $value . "' AND ",
            default => $filter_sql,
        };
    }

    /**
     * get_sql_sort
     *
     * Sorting SQL for ORDER BY
     */
    public function get_sql_sort(Query $query, ?string $field = null, ?string $order = null): string
    {
        switch ($field) {
            case 'follow_date':
            case 'follow_user':
            case 'user':
                $sql = sprintf('`user_follower`.`%s`', $field);
                break;
            case 'create_date':
            case 'last_seen':
            case 'username':
                // a row is rendered as the account doing the following, so these sort that account, not the follow
                $sql = sprintf('`user`.`%s`', $field);
                $query->set_join('LEFT', '`user`', '`user`.`id`', '`user_follower`.`follow_user`', 100);
                break;
            default:
                $sql = '';
        }

        if ($sql === '') {
            return '';
        }

        return sprintf('%s %s,', $sql, $order);
    }
}
