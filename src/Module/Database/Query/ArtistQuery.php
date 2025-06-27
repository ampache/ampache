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

use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Query;

final class ArtistQuery implements QueryInterface
{
    public const FILTERS = [
        'add_gt',
        'add_lt',
        'album_artist',
        'alpha_match',
        'catalog_enabled',
        'catalog',
        'equal',
        'exact_match',
        'genre',
        'id',
        'label',
        'like',
        'no_genre',
        'no_tag',
        'not_like',
        'not_starts_with',
        'regex_match',
        'regex_not_match',
        'song_artist',
        'starts_with',
        'tag',
        'unplayed',
        'update_gt',
        'update_lt',
        'user_catalog',
    ];

    /** @var string[] $sorts */
    protected array $sorts = [
        'album_count',
        'id',
        'name',
        'placeformed',
        'rand',
        'rating',
        'song_count',
        'time',
        'title',
        'total_count',
        'user_flag_rating',
        'user_flag',
        'userflag',
        'yearformed',
    ];

    protected string $select = "`artist`.`id`";

    protected string $base = "SELECT %%SELECT%% FROM `artist` ";

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
     */
    public function get_sql_filter(Query $query, string $filter, mixed $value): string
    {
        $filter_sql = '';
        switch ($filter) {
            case 'id':
                $filter_sql = " `artist`.`id` IN (";
                foreach ($value as $uid) {
                    $filter_sql .= (int)$uid . ',';
                }
                $filter_sql = rtrim($filter_sql, ',') . ") AND ";
                break;
            case 'no_genre':
            case 'no_tag':
                $filter_sql = " (`artist`.`id` NOT IN (SELECT `object_id` FROM `tag_map` WHERE `object_type`='artist')) AND ";
                break;
            case 'genre':
            case 'tag':
                $query->set_join('LEFT', '`tag_map`', '`tag_map`.`object_id`', '`artist`.`id`', 100);
                $filter_sql = " `tag_map`.`object_type`='artist' AND (";

                foreach ($value as $tag_id) {
                    $filter_sql .= "`tag_map`.`tag_id`='" . Dba::escape($tag_id) . "' AND ";
                }
                $filter_sql = rtrim((string) $filter_sql, 'AND ') . ') AND ';
                break;
            case 'equal':
            case 'exact_match':
                $filter_sql = " `artist`.`name` = '" . Dba::escape($value) . "' AND ";
                break;
            case 'like':
            case 'alpha_match':
                $filter_sql = " `artist`.`name` LIKE '%" . Dba::escape($value) . "%' AND ";
                break;
            case 'not_like':
                $filter_sql = " `artist`.`name` NOT LIKE '%" . Dba::escape($value) . "%' AND ";
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
            case 'not_starts_with':
                $query->set_join('LEFT', '`song`', '`artist`.`id`', '`song`.`artist`', 100);
                $filter_sql = " `artist`.`name` NOT LIKE '" . Dba::escape($value) . "%' AND ";
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
            case 'user_catalog':
                $type = '\'artist\'';
                if ($query->get_filter('album_artist')) {
                    $type = '\'album_artist\'';
                }
                if ($query->get_filter('song_artist')) {
                    $type = '\'song_artist\'';
                }
                if ($value != 0) {
                    $query->set_join_and('LEFT', '`catalog_map`', '`catalog_map`.`object_id`', '`artist`.`id`', '`catalog_map`.`object_type`', $type, 100);
                    $filter_sql = " (`catalog_map`.`catalog_id` IN (" . implode(',', Catalog::get_catalogs('', $query->user_id, true)) . ")) AND ";
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
                $filter_sql = ($value == 0)
                    ? " `artist`.`id` NOT IN (SELECT `artist_id` FROM `artist_map` WHERE `artist_map`.`object_type` = 'album') AND "
                    : " `artist`.`id` IN (SELECT `artist_id` FROM `artist_map` WHERE `artist_map`.`object_type` = 'album') AND ";
                break;
            case 'song_artist':
                $filter_sql = ($value == 0)
                    ? " `artist`.`id` NOT IN (SELECT `artist_id` FROM `artist_map` WHERE `artist_map`.`object_type` = 'song') AND "
                    : " `artist`.`id` IN (SELECT `artist_id` FROM `artist_map` WHERE `artist_map`.`object_type` = 'song') AND ";
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
     * @param string|null $field
     * @param string|null $order
     * @return string
     */
    public function get_sql_sort($query, $field, $order): string
    {
        switch ($field) {
            case 'name':
            case 'title':
                $sql = "`artist`.`name`";
                break;
            case 'album_count':
            case 'id':
            case 'placeformed':
            case 'song_count':
            case 'time':
            case 'total_count':
            case 'yearformed':
                $sql = "`artist`.`$field`";
                break;
            case 'rating':
                $sql = "`rating`.`rating` $order, `rating`.`date`";
                $query->set_join_and_and('LEFT', "`rating`", "`rating`.`object_id`", "`artist`.`id`", "`rating`.`object_type`", "'artist'", "`rating`.`user`", (string)$query->user_id, 100);
                break;
            case 'user_flag':
            case 'userflag':
                $sql = "`user_flag`.`date`";
                $query->set_join_and_and('LEFT', "`user_flag`", "`user_flag`.`object_id`", "`artist`.`id`", "`user_flag`.`object_type`", "'artist'", "`user_flag`.`user`", (string)$query->user_id, 100);
                break;
            case 'user_flag_rating':
                $sql = "`user_flag`.`date` $order, `rating`.`rating` $order, `rating`.`date`";
                $query->set_join_and_and('LEFT', "`user_flag`", "`user_flag`.`object_id`", "`artist`.`id`", "`user_flag`.`object_type`", "'artist'", "`user_flag`.`user`", (string)$query->user_id, 100);
                $query->set_join_and_and('LEFT', "`rating`", "`rating`.`object_id`", "`artist`.`id`", "`rating`.`object_type`", "'artist'", "`rating`.`user`", (string)$query->user_id, 100);
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
