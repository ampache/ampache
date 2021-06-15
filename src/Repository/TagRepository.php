<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Repository;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Repository\Model\Catalog;

final class TagRepository implements TagRepositoryInterface
{
    private ConfigContainerInterface $configContainer;

    public function __construct(
        ConfigContainerInterface $configContainer
    ) {
        $this->configContainer = $configContainer;
    }

    /**
     * This gets the objects from a specified tag and returns an array of object ids, nothing more
     *
     * @return int[]
     */
    public function getTagObjectIds(
        string $type,
        int $tagId,
        ?int $limit = null,
        int $offset = 0
    ): array {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return [];
        }
        $tag_sql   = ($tagId == 0) ? "" : "`tag_map`.`tag_id` = ? AND";
        $sql_param = ($tag_sql == "") ? [$type] : [$tagId, $type];
        $limit_sql = "";
        if ($limit !== null) {
            $limit_sql = " LIMIT ";
            if ($offset) {
                $limit_sql .= (string)($offset) . ', ';
            }
            $limit_sql .= (string)($limit);
        }

        $sql = ($type == 'album')
            ? 'SELECT DISTINCT MIN(`tag_map`.`object_id`) as `object_id` FROM `tag_map` LEFT JOIN `album` ON `tag_map`.`object_id` = `album`.`id` '
            : 'SELECT DISTINCT `tag_map`.`object_id` FROM `tag_map` ';
        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::CATALOG_DISABLE) === true &&
            in_array($type, ['song', 'artist', 'album'])
        ) {
            $sql .= "AND " . Catalog::get_enable_filter($type, '`tag_map`.`object_id`');
        }
        if ($type == 'album') {
            if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALBUM_GROUP)) {
                $sql .= 'GROUP BY `album`.`prefix`, `album`.`name`, `album`.`album_artist`, `album`.`mbid`, `album`.`year`';
            } else {
                $sql .= 'GROUP BY `album`.`id`, `album`.`disk`';
            }
        }
        $sql .= $limit_sql;
        $db_results = Dba::read($sql, $sql_param);

        $result = [];

        while ($row = Dba::fetch_assoc($db_results)) {
            $result[] = (int) $row['object_id'];
        }

        return $result;
    }

    /**
     * Get all tags from all Songs from [type] (artist, album, ...)
     *
     * @return string[]
     */
    public function getSongTags(string $type, int $objectId): array
    {
        $tags       = [];
        $db_results = Dba::read(
            'SELECT `tag`.`name` FROM `tag` JOIN `tag_map` ON `tag`.`id` = `tag_map`.`tag_id`
                JOIN `song` ON `tag_map`.`object_id` = `song`.`id` WHERE
                `song`.`%s` = ? AND `tag_map`.`object_type` = ? GROUP BY `tag`.`id`, `tag`.`name`',
            [$objectId, $type]
        );
        while ($row = Dba::fetch_assoc($db_results)) {
            $tags[] = $row['name'];
        }

        return $tags;
    }

    /**
     * Migrate an object associate stats to a new object
     */
    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): void
    {
        Dba::write(
            'UPDATE IGNORE `tag_map` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
            [$newObjectId, $objectType, $oldObjectId]
        );
    }

    /**
     * This checks to see if a tag exists, this has nothing to do with objects or maps
     */
    public function findByName(string $value): ?int
    {
        $sql        = "SELECT `id` FROM `tag` WHERE `name` = ?";
        $db_results = Dba::read($sql, array($value));

        $results = Dba::fetch_assoc($db_results);

        if ($results === []) {
            return null;
        }

        return (int)$results['id'];
    }

    /**
     * This is a non-object non type dependent function that just returns tags
     * we've got, it can take filters (this is used by the tag cloud)
     *
     * @return array<int, array{id: int, name: string, is_hidden: int, count: int}>
     */
    public function getByType(string $type = '', string $order = 'count'): array
    {
        $results = [];

        $sql = "SELECT `tag_map`.`tag_id`, `tag`.`name`, `tag`.`is_hidden`, COUNT(`tag_map`.`object_id`) AS `count` " . "FROM `tag_map` " . "LEFT JOIN `tag` ON `tag`.`id`=`tag_map`.`tag_id` " . "WHERE `tag`.`is_hidden` = false " . "GROUP BY `tag_map`.`tag_id`, `tag`.`name`, `tag`.`is_hidden` ";
        if (!empty($type)) {
            $sql .= ", `tag_map`.`object_type` = '" . $type . "' ";
        }
        $order = "`" . $order . "`";
        if ($order == 'count') {
            $order .= " DESC";
        }
        $sql .= "ORDER BY " . $order;

        $db_results = Dba::read($sql);
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[(int) $row['tag_id']] = [
                'id' => (int) $row['tag_id'],
                'name' => $row['name'],
                'is_hidden' => (int) $row['is_hidden'],
                'count' => (int) $row['count']
            ];
        }

        return $results;
    }

    /**
     * This gets the top tags for the specified object using limit
     *
     * @return array<int, array{
     *  user: int,
     *  id: int,
     *  name: string
     * }>
     */
    public function getTopTags(string $type, int $object_id, int $limit = 10): array
    {
        if (!InterfaceImplementationChecker::is_library_item($type)) {
            return [];
        }

        $db_results = Dba::read(
            sprintf("SELECT `tag_map`.`id`, `tag_map`.`tag_id`, `tag`.`name`, `tag_map`.`user` FROM `tag` LEFT JOIN `tag_map` ON `tag_map`.`tag_id`=`tag`.`id` WHERE `tag_map`.`object_type`='$type' AND `tag_map`.`object_id` = ? LIMIT %d", $limit),
            [$object_id]
        );
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[(int) $row['id']] = [
                'user' => (int) $row['user'],
                'id' => (int) $row['tag_id'],
                'name' => (string) $row['name']
            ];
        }

        return $results;
    }

    /**
     * This cleans out tag_maps that are obsolete and then removes tags that
     * have no maps.
     */
    public function collectGarbage(): void
    {
        $sql = "DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `song` ON `song`.`id`=`tag_map`.`object_id` " . "WHERE `tag_map`.`object_type`='song' AND `song`.`id` IS NULL";
        Dba::write($sql);

        $sql = "DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `album` ON `album`.`id`=`tag_map`.`object_id` " . "WHERE `tag_map`.`object_type`='album' AND `album`.`id` IS NULL";
        Dba::write($sql);

        $sql = "DELETE FROM `tag_map` USING `tag_map` LEFT JOIN `artist` ON `artist`.`id`=`tag_map`.`object_id` " . "WHERE `tag_map`.`object_type`='artist' AND `artist`.`id` IS NULL";
        Dba::write($sql);

        // Now nuke the tags themselves
        $sql = "DELETE FROM `tag` USING `tag` LEFT JOIN `tag_map` ON `tag`.`id`=`tag_map`.`tag_id` " . "WHERE `tag_map`.`id` IS NULL " . "AND NOT EXISTS (SELECT 1 FROM `tag_merge` WHERE `tag_merge`.`tag_id` = `tag`.`id`)";
        Dba::write($sql);

        // delete duplicates
        $sql = "DELETE `b` FROM `tag_map` AS `a`, `tag_map` AS `b` " . "WHERE `a`.`id` < `b`.`id` AND `a`.`tag_id` <=> `b`.`tag_id` AND " . "`a`.`object_id` <=> `b`.`object_id` AND `a`.`object_type` <=> `b`.`object_type`";
        Dba::write($sql);
    }
}
