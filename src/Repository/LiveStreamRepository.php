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
use Doctrine\DBAL\Connection;

final class LiveStreamRepository implements LiveStreamRepositoryInterface
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
     * @return int[]
     */
    public function getAll(): array
    {
        $sql = 'SELECT `live_stream`.`id` FROM `live_stream` JOIN `catalog` ON `catalog`.`id` = `live_stream`.`catalog` ';
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::CATALOG_DISABLE)) {
            $sql .= 'WHERE `catalog`.`enabled` = \'1\' ';
        }

        $dbResults = $this->database->executeQuery($sql);

        $radios     = [];
        while ($rowId = $dbResults->fetchOne()) {
            $radios[] = (int) $rowId;
        }

        return $radios;
    }

    /**
     * This deletes the object with the given id from the database
     */
    public function delete(int $liveStreamId): void
    {
        $this->database->executeQuery(
            'DELETE FROM `live_stream` WHERE `id` = ?',
            [$liveStreamId]
        );
    }

    /**
     * @return array{
     *  id: int,
     *  name: string,
     *  site_url: string,
     *  url: string,
     *  genre: int,
     *  catalog: int,
     *  codec: string
     * }
     */
    public function getDataById(
        int $id
    ): array {
        $dbResults = $this->database->fetchAssociative(
            'SELECT * FROM `live_stream` WHERE `id` = ?',
            [$id]
        );

        if ($dbResults === false) {
            return [];
        }

        return $dbResults;
    }

    public function create(
        string $name,
        string $siteUrl,
        string $url,
        int $catalogId,
        string $codec
    ): int {
        $this->database->executeQuery(
            'INSERT INTO `live_stream` (`name`, `site_url`, `url`, `catalog`, `codec`) VALUES (?, ?, ?, ?, ?)',
            [$name, $siteUrl, $url, $catalogId, $codec]
        );

        return (int) $this->database->lastInsertId();
    }

    public function update(
        string $name,
        string $siteUrl,
        string $url,
        string $codec,
        int $liveStreamId
    ): void {
        $this->database->executeQuery(
            'UPDATE `live_stream` SET `name` = ?,`site_url` = ?,`url` = ?, codec = ? WHERE `id` = ?',
            [$name, $siteUrl, $url, $codec, $liveStreamId]
        );
    }
}
