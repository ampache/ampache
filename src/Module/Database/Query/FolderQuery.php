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

use Ampache\Module\System\Dba;

final class FolderQuery implements QueryInterface
{
    public const array FILTERS = [
        'alpha_match',
        'equal',
        'exact_match',
        'id',
        'int_id',
        'like',
        'not_like',
        'not_starts_with',
        'regex_match',
        'regex_not_match',
        'starts_with',
        'user_flag',
        'user_rating',
    ];

    protected string $base = "SELECT %%SELECT%% FROM (
            SELECT CONCAT(`object_type`, '-', `object_id`) AS `id`, `object_id` AS `int_id`, `name`, `folder_id`, `object_type`, IF(`object_type`='folder', 1, 0) AS `is_folder`, `path_name`, `catalog` FROM `folder_map`
        ) AS `folder` ";
    protected string $select = "`folder`.`id`";

    /** @var string[] $sorts */
    protected array $sorts = [
        'date',
        'id',
        'int_id',
        'last_count',
        'last_update',
        'name',
        'object_count',
        'object_type',
        'rand',
        'rating',
        'title',
        'total_count',
        'type',
        'user_flag_rating',
        'user_flag',
        'user',
        'userflag',
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
                $filter_sql = " `folder`.`id` = " . Dba::escape($value) . " AND ";
                break;
            case 'int_id':
                $filter_sql = ($value === -1)
                    ? " `folder`.`folder_id` IS NULL AND "
                    : " `folder`.`folder_id` = " . (int) $value . " AND ";
                break;
            case 'equal':
            case 'exact_match':
                $filter_sql = " (`folder`.`name` = '" . Dba::escape($value) . "' OR `folder`.`path_name` = '" . Dba::escape($value) . "') AND ";
                break;
            case 'like':
            case 'alpha_match':
                $filter_sql = " (`folder`.`name` LIKE '%" . Dba::escape($value) . "%' OR `folder`.`path_name` LIKE '%" . Dba::escape($value) . "%') AND ";
                break;
            case 'not_like':
                $filter_sql = " (`folder`.`name` NOT LIKE '%" . Dba::escape($value) . "%' AND `folder`.`path_name` NOT LIKE '%" . Dba::escape($value) . "%') AND ";
                break;
            case 'regex_match':
                if (!empty($value)) {
                    $filter_sql = " (`folder`.`name` REGEXP '" . Dba::escape($value) . "' OR `folder`.`path_name` REGEXP '" . Dba::escape($value) . "') AND ";
                }

                break;
            case 'regex_not_match':
                if (!empty($value)) {
                    $filter_sql = " (`folder`.`name` NOT REGEXP '" . Dba::escape($value) . "' AND `folder`.`path_name` NOT REGEXP '" . Dba::escape($value) . "') AND ";
                }

                break;
            case 'starts_with':
                $filter_sql = " (`folder`.`name` LIKE '" . Dba::escape($value) . "%' OR `folder`.`path_name` LIKE '" . Dba::escape($value) . "%') AND ";
                break;
            case 'not_starts_with':
                $filter_sql = " (`folder`.`name` NOT LIKE '" . Dba::escape($value) . "%' AND `folder`.`path_name` NOT LIKE '" . Dba::escape($value) . "%') AND ";
                break;
            case 'user_flag':
                $filter_sql = ((int) $value === 0)
                    ? " `folder`.`int_id` NOT IN (SELECT `object_id` FROM `user_flag` WHERE `object_type` = 'folder' AND `user` = " . (int) $query->user_id . ") AND "
                    : " `folder`.`int_id` IN (SELECT `object_id` FROM `user_flag` WHERE `object_type` = 'folder' AND `user` = " . (int) $query->user_id . ") AND ";
                break;
            case 'user_rating':
                $filter_sql = ((int) $value === 0)
                    ? " `folder`.`int_id` NOT IN (SELECT `object_id` FROM `rating` WHERE `object_type` = 'folder' AND `user` = " . (int) $query->user_id . ") AND "
                    : " `folder`.`int_id` IN (SELECT `object_id` FROM `rating` WHERE `object_type` = 'folder' AND `user` = " . (int) $query->user_id . " AND `rating` = " . Dba::escape($value) . ") AND ";
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
                $sql = "`folder`.`is_folder` DESC, `folder`.`name`";
                break;
            case 'id':
            case 'int_id':
            case 'object_type':
                $sql = sprintf('`folder`.`%s`', $field);
                break;
            case 'type':
                $sql = "`folder`.`object_type`";
                break;
            case 'date':
                $sql = "`folder`.`is_folder` DESC, `folder_data`.`addition_time`";
                $this->_join_folder($query);
                break;
            case 'last_update':
                $sql = "`folder`.`is_folder` DESC, `folder_data`.`update_time`";
                $this->_join_folder($query);
                break;
            case 'last_count':
            case 'object_count':
                $sql = "`folder`.`is_folder` DESC, `folder_data`.`object_count`";
                $this->_join_folder($query);
                break;
            case 'total_count':
            case 'user':
                $sql = sprintf('`folder`.`is_folder` DESC, `folder_data`.`%s`', $field);
                $this->_join_folder($query);
                break;
            case 'rating':
                $sql = sprintf('`rating`.`rating` %s, `rating`.`date`', $order);
                $query->set_join_and_and('LEFT', "`rating`", "`rating`.`object_id`", "`folder`.`int_id`", "`rating`.`object_type`", "'folder'", "`rating`.`user`", (string) $query->user_id, 100);
                break;
            case 'user_flag':
            case 'userflag':
                $sql = "`user_flag`.`date`";
                $query->set_join_and_and('LEFT', "`user_flag`", "`user_flag`.`object_id`", "`folder`.`int_id`", "`user_flag`.`object_type`", "'folder'", "`user_flag`.`user`", (string) $query->user_id, 100);
                break;
            case 'user_flag_rating':
                $sql = sprintf('`user_flag`.`date` %s, `rating`.`rating` %s, `rating`.`date`', $order, $order);
                $query->set_join_and_and('LEFT', "`user_flag`", "`user_flag`.`object_id`", "`folder`.`int_id`", "`user_flag`.`object_type`", "'folder'", "`user_flag`.`user`", (string) $query->user_id, 100);
                $query->set_join_and_and('LEFT', "`rating`", "`rating`.`object_id`", "`folder`.`int_id`", "`rating`.`object_type`", "'folder'", "`rating`.`user`", (string) $query->user_id, 100);
                break;
            default:
                $sql = '';
        }

        if ($sql === '') {
            return '';
        }

        return sprintf('%s %s,', $sql, $order);
    }

    /**
     * _join_folder
     *
     * The folder row behind a browse row, so a sort reads the folder's own columns and a media row has none of them
     */
    private function _join_folder(Query $query): void
    {
        $query->set_join_and('LEFT', '`folder` `folder_data`', '`folder_data`.`id`', '`folder`.`int_id`', '`folder`.`object_type`', "'folder'", 100);
    }
}
