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

/**
 * Browsing the `mood` table itself.
 */
final class MoodQuery implements QueryInterface
{
    public const array FILTERS = [
        'alpha_match',
        'equal',
        'exact_match',
        'id',
        'like',
        'mood',
        'not_like',
        'not_starts_with',
        'object_type',
        'regex_match',
        'regex_not_match',
        'starts_with',
    ];

    protected string $base   = "SELECT %%SELECT%% FROM `mood` ";
    protected string $select = "`mood`.`id`";

    /** @var string[] $sorts */
    protected array $sorts = [
        'album',
        'artist',
        'id',
        'mood',
        'name',
        'rand',
        'song',
        'title',
        'video',
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
                $filter_sql = " `mood`.`id` IN (";
                foreach ($value as $uid) {
                    $filter_sql .= (int) $uid . ',';
                }

                $filter_sql = rtrim($filter_sql, ',') . ") AND ";
                break;
            case 'equal':
            case 'exact_match':
                $filter_sql = " `mood`.`name` = '" . Dba::escape($value) . "' AND ";
                break;
            case 'like':
            case 'alpha_match':
                $filter_sql = " `mood`.`name` LIKE '%" . Dba::escape($value) . "%' AND ";
                break;
            case 'not_like':
                $filter_sql = " `mood`.`name` NOT LIKE '%" . Dba::escape($value) . "%' AND ";
                break;
            case 'regex_match':
                if (!empty($value)) {
                    $filter_sql = " `mood`.`name` REGEXP '" . Dba::escape($value) . "' AND ";
                }

                break;
            case 'regex_not_match':
                if (!empty($value)) {
                    $filter_sql = " `mood`.`name` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                }

                break;
            case 'starts_with':
                $filter_sql = " `mood`.`name` LIKE '" . Dba::escape($value) . "%' AND ";
                break;
            case 'not_starts_with':
                $filter_sql = " `mood`.`name` NOT LIKE '" . Dba::escape($value) . "%' AND ";
                break;
            case 'mood':
                $filter_sql = " `mood`.`id` = '" . (int) $value . "' AND ";
                break;
            case 'object_type':
                $query->set_join('LEFT', '`mood_map`', '`mood_map`.`mood_id`', '`mood`.`id`', 100);
                $filter_sql = " `mood_map`.`object_type` = '" . Dba::escape($value) . "' AND ";
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
            case 'id':
            case 'mood':
                $sql = "`mood`.`id`";
                break;
            case 'name':
            case 'title':
                $sql = "`mood`.`name`";
                break;
            case 'artist':
            case 'album':
            case 'song':
            case 'video':
                $sql = sprintf('`mood`.`%s`', $field);
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
