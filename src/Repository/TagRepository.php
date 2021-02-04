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
use Ampache\Model\Catalog;
use Ampache\Module\System\Dba;
use Ampache\Module\Util\InterfaceImplementationChecker;

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

        $sql = "SELECT DISTINCT `tag_map`.`object_id` FROM `tag_map` WHERE $tag_sql `tag_map`.`object_type` = ? ";
        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::CATALOG_DISABLE) === true &&
            in_array($type, ['song', 'artist', 'album'])
        ) {
            $sql .= "AND " . Catalog::get_enable_filter($type, '`tag_map`.`object_id`');
        }
        $sql .= $limit_sql;
        $db_results = Dba::read($sql, $sql_param);

        $result = [];

        while ($row = Dba::fetch_assoc($db_results)) {
            $result[] = (int) $row['object_id'];
        }

        return $result;
    }
}
