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

final class ArtistQuery implements QueryInterface
{
    public const FILTERS = array(
        'add_gt',
        'add_lt',
        'album_artist',
        'song_artist',
        'alpha_match',
        'catalog',
        'catalog_enabled',
        'exact_match',
        'label',
        'regex_match',
        'regex_not_match',
        'starts_with',
        'tag',
        'unplayed',
        'update_gt',
        'update_lt',
    );

    /** @var string[] $sorts */
    protected array $sorts = array(
        'title',
        'name',
        'placeformed',
        'yearformed',
        'song_count',
        'album_count',
        'total_count',
        'rand',
        'rating',
        'time',
        'user_flag'
    );

    /** @var string */
    protected $select = "`artist`.`id`";

    /** @var string */
    protected $base = "SELECT %%SELECT%% FROM `artist` ";

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
            case 'tag':
                $query->set_join('LEFT', '`tag_map`', '`tag_map`.`object_id`', '`artist`.`id`', 100);
                $filter_sql = " `tag_map`.`object_type`='" . $query->get_type() . "' AND (";

                foreach ($value as $tag_id) {
                    $filter_sql .= "`tag_map`.`tag_id`='" . Dba::escape($tag_id) . "' AND ";
                }
                $filter_sql = rtrim((string) $filter_sql, 'AND ') . ') AND ';
                break;
            case 'exact_match':
                $filter_sql = " `artist`.`name` = '" . Dba::escape($value) . "' AND ";
                break;
            case 'alpha_match':
                $filter_sql = " `artist`.`name` LIKE '%" . Dba::escape($value) . "%' AND ";
                break;
            case 'regex_match':
                if (!empty($value)) {
                    $filter_sql = " `artist`.`name` REGEXP '" . Dba::escape($value) . "' AND ";
                }
                break;
            case 'regex_not_match':
                if (!empty($value)) {
                    $filter_sql = " `artist`.`name` NOT REGEXP '" . Dba::escape($value) . "' AND ";
                }
                break;
            case 'starts_with':
                $query->set_join('LEFT', '`song`', '`artist`.`id`', '`song`.`artist`', 100);
                $filter_sql = " `artist`.`name` LIKE '" . Dba::escape($value) . "%' AND ";
                if ($query->catalog != 0) {
                    $filter_sql .= "`song`.`catalog` = '" . $query->catalog . "' AND ";
                }
                break;
            case 'add_lt':
                $query->set_join('LEFT', '`song`', '`song`.`artist`', '`artist`.`id`', 100);
                $filter_sql = " `song`.`addition_time` <= '" . Dba::escape($value) . "' AND ";
                break;
            case 'add_gt':
                $query->set_join('LEFT', '`song`', '`song`.`artist`', '`artist`.`id`', 100);
                $filter_sql = " `song`.`addition_time` >= '" . Dba::escape($value) . "' AND ";
                break;
            case 'update_lt':
                $query->set_join('LEFT', '`song`', '`song`.`artist`', '`artist`.`id`', 100);
                $filter_sql = " `song`.`update_time` <= '" . Dba::escape($value) . "' AND ";
                break;
            case 'update_gt':
                $query->set_join('LEFT', '`song`', '`song`.`artist`', '`artist`.`id`', 100);
                $filter_sql = " `song`.`update_time` >= '" . Dba::escape($value) . "' AND ";
                break;
            case 'label':
                $query->set_join('LEFT', '`label_asso`', '`label_asso`.`artist`', '`artist`.`id`', 100);
                $filter_sql = " `label_asso`.`label` = '" . Dba::escape($value) . "' AND ";
                break;
            case 'catalog':
                $type = '\'artist\'';
                if ($query->get_filter('album_artist')) {
                    $type = '\'album_artist\'';
                }
                if ($query->get_filter('song_artist')) {
                    $type = '\'song_artist\'';
                }
                if ($value != 0) {
                    $query->set_join_and('LEFT', '`catalog_map`', '`catalog_map`.`object_id`', '`artist`.`id`', '`catalog_map`.`object_type`', $type, 100);
                    $filter_sql = " (`catalog_map`.`catalog_id` = '" . Dba::escape($value) . "') AND ";
                }
                break;
            case 'catalog_enabled':
                $type = '\'artist\'';
                if ($query->get_filter('album_artist')) {
                    $type = '\'album_artist\'';
                }
                if ($query->get_filter('song_artist')) {
                    $type = '\'song_artist\'';
                }
                $query->set_join_and('LEFT', '`catalog_map`', '`catalog_map`.`object_id`', '`artist`.`id`', '`catalog_map`.`object_type`', $type, 50);
                $query->set_join('LEFT', '`catalog`', '`catalog`.`id`', '`catalog_map`.`catalog_id`', 100);
                $filter_sql = " `catalog`.`enabled` = '1' AND ";
                break;
            case 'album_artist':
                $filter_sql = " `artist`.`id` IN (SELECT `artist_id` FROM `artist_map` WHERE `artist_map`.`object_type` = 'album') AND ";
                break;
            case 'song_artist':
                $filter_sql = " `artist`.`id` IN (SELECT `artist_id` FROM `artist_map` WHERE `artist_map`.`object_type` = 'song') AND ";
                break;
            case 'unplayed':
                if ((int)$value == 1) {
                    $filter_sql = " `artist`.`total_count`='0' AND ";
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
            case 'name':
            case 'title':
                $sql = "`artist`.`name`";
                break;
            case 'placeformed':
            case 'yearformed':
            case 'song_count':
            case 'album_count':
            case 'total_count':
            case 'time':
                $sql = "`artist`.`$field`";
                break;
            case 'rating':
                $sql = "`rating`.`rating` $order, `rating`.`date`";
                $query->set_join_and_and('LEFT', "`rating`", "`rating`.`object_id`", "`artist`.`id`", "`rating`.`object_type`", "'artist'", "`rating`.`user`", (string)$query->user_id, 100);
                break;
            case 'user_flag':
                $sql = "`user_flag`.`date`";
                $query->set_join_and_and('LEFT', "`user_flag`", "`user_flag`.`object_id`", "`artist`.`id`", "`user_flag`.`object_type`", "'artist'", "`user_flag`.`user`", (string)$query->user_id, 100);
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
