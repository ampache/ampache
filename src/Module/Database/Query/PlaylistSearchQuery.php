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

final class PlaylistSearchQuery implements QueryInterface
{
    public const FILTERS = [
        'alpha_match',
        'equal',
        'exact_match',
        'hide_dupe_smartlist',
        'id',
        'like',
        'not_like',
        'not_starts_with',
        'playlist_open',
        'playlist_type',
        'playlist_user',
        'regex_match',
        'regex_not_match',
        'smartlist',
        'starts_with',
    ];

    /** @var string[] $sorts */
    protected array $sorts = [
        'date',
        'id',
        'last_count',
        'last_update',
        'name',
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

    protected string $select = "`playlist`.`id`";

    protected string $base = <<<SQL
        SELECT %%SELECT%% FROM (
            SELECT `id`, `id` AS `int_id`, `name`, `user`, `type`, `date`, `last_update`, `last_duration`, `username`, 'playlist' AS `object_type` FROM `playlist`
            UNION
            SELECT CONCAT('smart_', `id`) AS `id`, `id` AS `int_id`, `name`, `user`, `type`, `date`, `last_update`, `last_duration`, `username`, 'search' AS `object_type` FROM `search`
        ) AS `playlist`
        SQL;

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
                $filter_sql = " `playlist`.`id` IN (";
                foreach ($value as $uid) {
                    $filter_sql .= ((int)str_replace('smart_', '', $uid) > 0)
                        ? Dba::escape($uid) . ','
                        : (int)$uid . ',';
                }
                $filter_sql = rtrim($filter_sql, ',') . ") AND ";
                break;
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
            case 'smartlist':
                $filter_sql = " `playlist`.`id` LIKE 'smart_%' AND ";
                break;
            case 'hide_dupe_smartlist':
                $filter_sql = " (`playlist`.`id` NOT LIKE 'smart_%' OR (`playlist`.`id` like 'smart_%' AND CONCAT(`playlist`.`name`, `playlist`.`user`) NOT IN (SELECT CONCAT(`playlist`.`name`, `playlist`.`user`) FROM `playlist`))) AND ";
                break;
            case 'playlist_open':
                $query->set_join_and_and('LEFT', '`user_playlist_map`', '`user_playlist_map`.`playlist_id`', '`playlist`.`object_type`', "'playlist'", '`playlist`.`id`', "`user_playlist_map`.`user_id`", (string)$value, 100);
                $filter_sql = " (`playlist`.`type` = 'public' OR `playlist`.`user`=" . (int)$value . " OR `user_playlist_map`.`user_id` IS NOT NULL) AND ";
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
                    $query->set_join_and_and('LEFT', '`user_playlist_map`', '`user_playlist_map`.`playlist_id`', '`playlist`.`object_type`', "'playlist'", '`playlist`.`id`', "`user_playlist_map`.`user_id`", (string)$user_id, 100);
                    $filter_sql = " (`playlist`.`type` = 'public' OR `playlist`.`user`=" . $user_id . " OR `user_playlist_map`.`user_id` IS NOT NULL) AND ";
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
     * @param string|null $field
     * @param string|null $order
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
            case 'id':
            case 'last_count':
            case 'last_update':
            case 'type':
            case 'user':
            case 'username':
                $sql = "`playlist`.`$field`";
                break;
            case 'rating':
                $sql = "`rating`.`rating` $order, `rating`.`date`";
                $query->set_join_and_and('LEFT', "`rating`", "`rating`.`object_id`", "`playlist`.`int_id`", "`rating`.`object_type`", "`playlist`.`object_type`", "`rating`.`user`", (string)$query->user_id, 100);
                break;
            case 'user_flag':
            case 'userflag':
                $sql = "`user_flag`.`date`";
                $query->set_join_and_and('LEFT', "`user_flag`", "`user_flag`.`object_id`", "`playlist`.`int_id`", "`user_flag`.`object_type`", "`playlist`.`object_type`", "`user_flag`.`user`", (string)$query->user_id, 100);
                break;
            case 'user_flag_rating':
                $sql = "`user_flag`.`date` $order, `rating`.`rating` $order, `rating`.`date`";
                $query->set_join_and_and('LEFT', "`user_flag`", "`user_flag`.`object_id`", "`playlist`.`int_id`", "`user_flag`.`object_type`", "`playlist`.`object_type`", "`user_flag`.`user`", (string)$query->user_id, 100);
                $query->set_join_and_and('LEFT', "`rating`", "`rating`.`object_id`", "`playlist`.`int_id`", "`rating`.`object_type`", "`playlist`.`object_type`", "`rating`.`user`", (string)$query->user_id, 100);
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
