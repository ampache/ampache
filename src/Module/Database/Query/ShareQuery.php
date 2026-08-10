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

final class ShareQuery implements QueryInterface
{
    public const array FILTERS = [
        'alpha_match',
        'equal',
        'exact_match',
        'like',
        'not_like',
        'not_starts_with',
        'object_type',
        'regex_match',
        'regex_not_match',
        'starts_with',
        'user',
    ];

    protected string $base   = "SELECT %%SELECT%% FROM `share` ";
    protected string $select = "`share`.`id`";

    /** @var string[] $sorts */
    protected array $sorts = [
        'allow_download',
        'allow_stream',
        'counter',
        'creation_date',
        'expire',
        'id',
        'lastvisit_date',
        'max_counter',
        'name',
        'object_type',
        'object',
        'title',
        'user',
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
            case 'equal':
            case 'exact_match':
                $filter_sql = sprintf(" %s = '%s' AND ", $this->_get_object_title($query), Dba::escape($value));
                break;
            case 'like':
            case 'alpha_match':
                $filter_sql = sprintf(" %s LIKE '%%%s%%' AND ", $this->_get_object_title($query), Dba::escape($value));
                break;
            case 'not_like':
                $filter_sql = sprintf(" %s NOT LIKE '%%%s%%' AND ", $this->_get_object_title($query), Dba::escape($value));
                break;
            case 'starts_with':
                $filter_sql = sprintf(" %s LIKE '%s%%' AND ", $this->_get_object_title($query), Dba::escape($value));
                break;
            case 'not_starts_with':
                $filter_sql = sprintf(" %s NOT LIKE '%s%%' AND ", $this->_get_object_title($query), Dba::escape($value));
                break;
            case 'regex_match':
                if (!empty($value)) {
                    $filter_sql = sprintf(" %s REGEXP '%s' AND ", $this->_get_object_title($query), Dba::escape($value));
                }

                break;
            case 'regex_not_match':
                if (!empty($value)) {
                    $filter_sql = sprintf(" %s NOT REGEXP '%s' AND ", $this->_get_object_title($query), Dba::escape($value));
                }

                break;
            case 'object_type':
                $filter_sql = sprintf(" `share`.`object_type` = '%s' AND ", Dba::escape($value));
                break;
            case 'user':
                $filter_sql = ' `share`.`user` = ' . (int) $value . ' AND ';
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
                // the direction belongs to the title, so the type and id are only a tiebreak for shares of the same name
                $sql   = sprintf('%s %s, `share`.`object_type`, `share`.`object_id`', $this->_get_object_title($query), $order);
                $order = '';
                break;
            case 'object':
                $sql = "`share`.`object_type`, `share`.`object_id`";
                break;
            case 'allow_download':
            case 'allow_stream':
            case 'counter':
            case 'creation_date':
            case 'expire':
            case 'id':
            case 'lastvisit_date':
            case 'max_counter':
            case 'object_type':
            case 'user':
                $sql = sprintf('`share`.`%s`', $field);
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
     * _get_object_title
     *
     * The title of the shared object, which lives in a different table for each of the nine types a share can point at
     */
    private function _get_object_title(Query $query): string
    {
        $query->set_join_and('LEFT', '`album`', '`album`.`id`', '`share`.`object_id`', '`share`.`object_type`', "'album'", 100);
        $query->set_join_and('LEFT', '`album_disk`', '`album_disk`.`id`', '`share`.`object_id`', '`share`.`object_type`', "'album_disk'", 100);
        $query->set_join('LEFT', '`album` `disk_album`', '`disk_album`.`id`', '`album_disk`.`album_id`', 100);
        $query->set_join_and('LEFT', '`artist`', '`artist`.`id`', '`share`.`object_id`', '`share`.`object_type`', "'artist'", 100);
        $query->set_join_and('LEFT', '`playlist`', '`playlist`.`id`', '`share`.`object_id`', '`share`.`object_type`', "'playlist'", 100);
        $query->set_join_and('LEFT', '`podcast`', '`podcast`.`id`', '`share`.`object_id`', '`share`.`object_type`', "'podcast'", 100);
        $query->set_join_and('LEFT', '`podcast_episode`', '`podcast_episode`.`id`', '`share`.`object_id`', '`share`.`object_type`', "'podcast_episode'", 100);
        $query->set_join_and('LEFT', '`search`', '`search`.`id`', '`share`.`object_id`', '`share`.`object_type`', "'search'", 100);
        $query->set_join_and('LEFT', '`song`', '`song`.`id`', '`share`.`object_id`', '`share`.`object_type`', "'song'", 100);
        $query->set_join_and('LEFT', '`video`', '`video`.`id`', '`share`.`object_id`', '`share`.`object_type`', "'video'", 100);

        return "COALESCE(`album`.`name`, `disk_album`.`name`, `artist`.`name`, `playlist`.`name`, `podcast`.`title`, `podcast_episode`.`title`, `search`.`name`, `song`.`title`, `video`.`title`, '')";
    }
}
