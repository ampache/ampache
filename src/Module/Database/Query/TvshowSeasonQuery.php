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

final class TvshowSeasonQuery implements QueryInterface
{
    public const FILTERS = array(
        'season_eq',
        'season_gt',
        'season_lt'
    );

    /** @var string[] $sorts */
    protected array $sorts = array(
        'rand',
        'season',
        'tvshow',
        'title',
        'resolution',
        'length',
        'codec',
        'rating',
        'user_flag'
    );

    /** @var string */
    protected $select = "`tvshow_season`.`id`";

    /** @var string */
    protected $base = "SELECT %%SELECT%% FROM `tvshow_season` ";

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
            case 'season_lt':
                $filter_sql = " `tvshow_season`.`season_number` < '" . Dba::escape($value) . "' AND ";
                break;
            case 'season_gt':
                $filter_sql = " `tvshow_season`.`season_number` > '" . Dba::escape($value) . "' AND ";
                break;
            case 'season_eq':
                $filter_sql = " `tvshow_season`.`season_number` = '" . Dba::escape($value) . "' AND ";
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
            case 'season':
                $sql = "`tvshow_season`.`season_number`";
                break;
            case 'tvshow':
                $sql = "`tvshow`.`name`";
                $query->set_join('LEFT', '`tvshow`', '`tvshow_season`.`tvshow`', '`tvshow`.`id`', 100);
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
