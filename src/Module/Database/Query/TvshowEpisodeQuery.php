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

use Ampache\Repository\Model\Query;

final class TvshowEpisodeQuery implements QueryInterface
{
    public const FILTERS = array(
    );

    /** @var string[] $sorts */
    protected array $sorts = array(
        'title',
        'resolution',
        'length',
        'codec',
        'episode',
        'season',
        'tvshow'
    );

    /** @var string */
    protected $select = "`tvshow_episode`.`id`";

    /** @var string */
    protected $base = "SELECT %%SELECT%% FROM `tvshow_episode` ";

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
        return '';
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
            case 'episode':
                $sql = "`tvshow_episode`.`episode_number`";
                break;
            case 'season':
                $sql = "`tvshow_season`.`season_number`";
                $query->set_join(
                    'LEFT',
                    '`tvshow_season`',
                    '`tvshow_episode`.`season`',
                    '`tvshow_season`.`id`',
                    100
                );
                break;
            case 'tvshow':
                $sql = "`tvshow`.`name`";
                $query->set_join(
                    'LEFT',
                    '`tvshow_season`',
                    '`tvshow_episode`.`season`',
                    '`tvshow_season`.`id`',
                    100
                );
                $query->set_join('LEFT', '`tvshow`', '`tvshow_season`.`tvshow`', '`tvshow`.`id`', 100);
                break;
            default:
                $sql = $query->sql_sort_video($field, $order, 'tvshow_episode');
                break;
        }

        if (empty($sql)) {
            return '';
        }

        return "$sql $order,";
    }
}
