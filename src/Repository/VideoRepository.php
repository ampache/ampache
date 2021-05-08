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
 */

declare(strict_types=1);

namespace Ampache\Repository;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Doctrine\DBAL\Connection;

final class VideoRepository implements VideoRepositoryInterface
{
    private Connection $database;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        Connection $database,
        ConfigContainerInterface $configContainer
    ) {
        $this->database        = $database;
        $this->configContainer = $configContainer;
    }

    /**
     * This returns a number of random videos.
     *
     * @return int[]
     */
    public function getRandom(int $limit = 1): array
    {
        $results = [];

        if (!$limit) {
            $limit = 1;
        }

        $sql   = 'SELECT DISTINCT(`video`.`id`) AS `id` FROM `video` ';
        $where = 'WHERE `video`.`enabled` = \'1\' ';
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::CATALOG_DISABLE)) {
            $sql .= 'LEFT JOIN `catalog` ON `catalog`.`id` = `video`.`catalog` ';
            $where .= 'AND `catalog`.`enabled` = \'1\' ';
        }

        $sql .= $where;
        $sql .= sprintf('ORDER BY RAND() LIMIT %d', $limit);
        $db_results = $this->database->executeQuery(
            $sql
        );

        while ($rowId = $db_results->fetchOne()) {
            $results[] = (int) $rowId;
        }

        return $results;
    }

    /**
     * Return the number of entries in the database...
     */
    public function getItemCount(string $type): int
    {
        return (int) $this->database->fetchOne(
            sprintf(
                'SELECT COUNT(*) as count from `%s`',
                strtolower(ObjectTypeToClassNameMapper::VIDEO_TYPES[$type])
            )
        );
    }
}
