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

use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\User;

/**
 * Browse query for collections
 *
 * Modelled on `PlaylistQuery`: the `collection` table mirrors `playlist` field for field.
 */
final class CollectionQuery implements QueryInterface
{
    public const array FILTERS = [
        'alpha_match',
        'collection_open',
        'collection_type',
        'collection_user',
        'equal',
        'exact_match',
        'id',
        'like',
        'not_like',
        'not_starts_with',
        'object_type',
        'regex_match',
        'regex_not_match',
        'starts_with',
        'user_flag',
        'user_rating',
    ];

    protected string $base   = "SELECT %%SELECT%% FROM `collection` ";
    protected string $select = "`collection`.`id`";

    /** @var string[] $sorts */
    protected array $sorts = [
        'date',
        'id',
        'last_count',
        'last_update',
        'name',
        'object_type',
        'rand',
        'rating',
        'title',
        'type',
        'user_flag_rating',
        'user_flag',
        'user',
        'userflag',
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
        switch ($filter) {
            case 'id':
                $filter_sql = " `collection`.`id` IN (";
                foreach ($value as $uid) {
                    $filter_sql .= (int) $uid . ',';
                }

                $filter_sql = rtrim($filter_sql, ',') . ") AND ";
                break;
            case 'equal':
            case 'exact_match':
                $filter_sql = " `collection`.`name` = '" . Dba::escape($value) . "' AND ";
                break;
            case 'like':
            case 'alpha_match':
                $filter_sql = " `collection`.`name` LIKE '%" . Dba::escape($value) . "%' AND ";
                break;
            case 'not_like':
                $filter_sql = " `collection`.`name` NOT LIKE '%" . Dba::escape($value) . "%' AND ";
                break;
            case 'regex_match':
                if (!empty($value)) {
                    $filter_sql = " `collection`.`name` REGEXP '" . Dba::escape($value) . "' AND ";
                }

                break;
            case 'regex_not_match':
                if (!empty($value)) {
                    $filter_sql = " `collection`.`name` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                }

                break;
            case 'starts_with':
                $filter_sql = " `collection`.`name` LIKE '" . Dba::escape($value) . "%' AND ";
                break;
            case 'not_starts_with':
                $filter_sql = " `collection`.`name` NOT LIKE '" . Dba::escape($value) . "%' AND ";
                break;
            case 'object_type':
                // The type a collection is pinned to; a mixed collection stores NULL and is never matched here
                $filter_sql = " `collection`.`object_type` = '" . Dba::escape($value) . "' AND ";
                break;
            case 'collection_open':
                $filter_sql = " (`collection`.`type` = 'public' OR `collection`.`user`=" . (int) $value . " OR FIND_IN_SET(" . (int) $value . ", `collection`.`collaborate`) > 0) AND ";
                break;
            case 'collection_user':
                $filter_sql = " `collection`.`user` = " . (int) $value . " AND ";
                break;
            case 'collection_type':
                // The same scoping `CollectionRepository::getByUser()` applies, so a browse and the API agree
                $user_id = (Core::get_global('user') instanceof User && Core::get_global('user')->id > 0)
                    ? Core::get_global('user')->id
                    : -1;
                $filter_sql = ($value == 0)
                    ? sprintf(" (`collection`.`user`='%s') AND ", $user_id)
                    : " (`collection`.`type` = 'public' OR `collection`.`user`=" . $user_id . " OR FIND_IN_SET(" . $user_id . ", `collection`.`collaborate`) > 0) AND ";
                break;
            case 'user_flag':
                $filter_sql = ((int) $value === 0)
                    ? " `collection`.`id` NOT IN (SELECT `object_id` FROM `user_flag` WHERE `object_type` = 'collection' AND `user` = " . (int) $query->user_id . ") AND "
                    : " `collection`.`id` IN (SELECT `object_id` FROM `user_flag` WHERE `object_type` = 'collection' AND `user` = " . (int) $query->user_id . ") AND ";
                break;
            case 'user_rating':
                $filter_sql = ((int) $value === 0)
                    ? " `collection`.`id` NOT IN (SELECT `object_id` FROM `rating` WHERE `object_type` = 'collection' AND `user` = " . (int) $query->user_id . ") AND "
                    : " `collection`.`id` IN (SELECT `object_id` FROM `rating` WHERE `object_type` = 'collection' AND `user` = " . (int) $query->user_id . " AND `rating` = " . Dba::escape($value) . ") AND ";
                break;
        }

        return $filter_sql;
    }

    /**
     * get_sql_sort
     *
     * Sorting SQL for ORDER BY
     */
    public function get_sql_sort(Query $query, ?string $field = null, ?string $order = null): string
    {
        switch ($field) {
            case 'name':
            case 'title':
                $sql = "`collection`.`name`, `collection`.`id`";
                break;
            case 'date':
            case 'id':
            case 'last_count':
            case 'last_update':
            case 'object_type':
            case 'type':
            case 'user':
            case 'username':
                $sql = sprintf('`collection`.`%s`', $field);
                break;
            case 'rating':
                $sql = sprintf('`rating`.`rating` %s, `rating`.`date`', $order);
                $query->set_join_and_and('LEFT', "`rating`", "`rating`.`object_id`", "`collection`.`id`", "`rating`.`object_type`", "'collection'", "`rating`.`user`", (string) $query->user_id, 100);
                break;
            case 'user_flag':
            case 'userflag':
                $sql = "`user_flag`.`date`";
                $query->set_join_and_and('LEFT', "`user_flag`", "`user_flag`.`object_id`", "`collection`.`id`", "`user_flag`.`object_type`", "'collection'", "`user_flag`.`user`", (string) $query->user_id, 100);
                break;
            case 'user_flag_rating':
                $sql = sprintf('`user_flag`.`date` %s, `rating`.`rating` %s, `rating`.`date`', $order, $order);
                $query->set_join_and_and('LEFT', "`user_flag`", "`user_flag`.`object_id`", "`collection`.`id`", "`user_flag`.`object_type`", "'collection'", "`user_flag`.`user`", (string) $query->user_id, 100);
                $query->set_join_and_and('LEFT', "`rating`", "`rating`.`object_id`", "`collection`.`id`", "`rating`.`object_type`", "'collection'", "`rating`.`user`", (string) $query->user_id, 100);
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
