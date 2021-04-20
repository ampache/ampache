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
}
