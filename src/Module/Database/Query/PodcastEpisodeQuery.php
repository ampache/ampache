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

use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Query;

final class PodcastEpisodeQuery implements QueryInterface
{
    public const FILTERS = array(
        'podcast',
        'catalog',
        'add_gt',
        'add_lt',
        'alpha_match',
        'exact_match',
        'regex_match',
        'regex_not_match',
        'starts_with',
        'unplayed'
    );

    /** @var string[] $sorts */
    protected array $sorts = array(
        'podcast',
        'title',
        'catalog',
        'category',
        'author',
        'time',
        'pubdate',
        'state',
        'rand',
        'addition_time',
        'total_count',
        'total_skip',
        'rating',
        'user_flag',
    );

    /** @var string */
    protected $select = "`podcast_episode`.`id`";

    /** @var string */
    protected $base = "SELECT %%SELECT%% FROM `podcast_episode` ";

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
            case 'podcast':
                $filter_sql = " `podcast_episode`.`podcast` = '" . Dba::escape($value) . "' AND ";
                break;
            case 'exact_match':
                $filter_sql = " `podcast_episode`.`title` = '" . Dba::escape($value) . "' AND ";
                break;
            case 'alpha_match':
                $filter_sql = " `podcast_episode`.`title` LIKE '%" . Dba::escape($value) . "%' AND ";
                break;
            case 'regex_match':
                if (!empty($value)) {
                    $filter_sql = " `podcast_episode`.`title` REGEXP '" . Dba::escape($value) . "' AND ";
                }
                break;
            case 'regex_not_match':
                if (!empty($value)) {
                    $filter_sql = " `podcast_episode`.`title` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                }
                break;
            case 'starts_with':
                $filter_sql = " `podcast_episode`.`title` LIKE '" . Dba::escape($value) . "%' AND ";
                break;
            case 'add_lt':
                $filter_sql = " `podcast_episode`.`addition_time` <= '" . Dba::escape($value) . "' AND ";
                break;
            case 'add_gt':
                $filter_sql = " `podcast_episode`.`addition_time` >= '" . Dba::escape($value) . "' AND ";
                break;
            case 'catalog':
                if ($value != 0) {
                    $filter_sql = " (`podcast_episode`.`catalog` = '" . Dba::escape($value) . "') AND ";
                }
                break;
            case 'unplayed':
                if ((int)$value == 1) {
                    $filter_sql = " `podcast_episode`.`played`='0' AND ";
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
            case 'podcast':
                $sql = "`podcast_episode`.`$field` $order, `podcast_episode`.`pubdate` ";
                break;
            case 'title':
            case 'catalog':
            case 'category':
            case 'author':
            case 'time':
            case 'pubdate':
            case 'state':
            case 'addition_time':
                $sql = "`podcast_episode`.`$field`";
                break;
            case 'rating':
                $sql = "`rating`.`rating` $order, `rating`.`date`";
                $query->set_join_and_and('LEFT', "`rating`", "`rating`.`object_id`", "`podcast_episode`.`id`", "`rating`.`object_type`", "'podcast_episode'", "`rating`.`user`", (string)$query->user_id, 100);
                break;
            case 'user_flag':
                $sql = "`user_flag`.`date`";
                $query->set_join_and_and('LEFT', "`user_flag`", "`user_flag`.`object_id`", "`podcast_episode`.`id`", "`user_flag`.`object_type`", "'podcast_episode'", "`user_flag`.`user`", (string)$query->user_id, 100);
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
