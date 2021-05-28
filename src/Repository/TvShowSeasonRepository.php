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

final class TvShowSeasonRepository implements TvShowSeasonRepositoryInterface
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

    public function collectGarbage(): void
    {
        $this->database->executeQuery(
            'DELETE FROM `tvshow_season` USING `tvshow_season` LEFT JOIN `tvshow_episode` ON `tvshow_episode`.`season` = `tvshow_season`.`id` WHERE `tvshow_episode`.`id` IS NULL'
        );
    }

    /**
     * gets all episodes for a tv show season
     * @return int[]
     */
    public function getEpisodeIds(
        int $tvShowId
    ): array {
        $catalogDisable = $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::CATALOG_DISABLE);

        $sql = $catalogDisable
            ? 'SELECT `tvshow_episode`.`id` FROM `tvshow_episode` LEFT JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` LEFT JOIN `catalog` ON `catalog`.`id` = `video`.`catalog` WHERE `tvshow_episode`.`season` = ? AND `catalog`.`enabled` = \'1\' '
            : 'SELECT `tvshow_episode`.`id` FROM `tvshow_episode` WHERE `tvshow_episode`.`season` = ? ';
        $sql .= 'ORDER BY `tvshow_episode`.`episode_number`';
        $dbResults = $this->database->executeQuery(
            $sql,
            [$tvShowId]
        );

        $results = [];
        while ($rowId = $dbResults->fetchOne()) {
            $results[] = (int) $rowId;
        }

        return $results;
    }

    /**
     * @return array{episode_count?: int, catalog_id?: int}
     */
    public function getExtraInfo(int $tvShowId): array
    {
        $result = $this->database->fetchAssociative(
            'SELECT COUNT(`tvshow_episode`.`id`) AS `episode_count`, `video`.`catalog` as `catalog_id` FROM `tvshow_episode` LEFT JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` WHERE `tvshow_episode`.`season` = ? GROUP BY `catalog_id`',
            [$tvShowId]
        );

        if ($result === false) {
            return [];
        }

        return $result;
    }

    public function setTvShow(int $tvShowId, int $seasonId): void
    {
        $this->database->executeQuery(
            'UPDATE `tvshow_season` SET `tvshow` = ? WHERE `id` = ?',
            [$tvShowId, $seasonId]
        );
    }

    public function findByTvShowAndSeasonNumber(
        int $tvShowId,
        int $seasonNumber
    ): ?int {
        $seasonId = $this->database->fetchOne(
            'SELECT `id` FROM `tvshow_season` WHERE `tvshow` = ? AND `season_number` = ? LIMIT 1',
            [$tvShowId, $seasonNumber]
        );

        if ($seasonId === false) {
            return null;
        }

        return (int) $seasonId;
    }

    public function addSeason(
        int $tvShowId,
        int $seasonNumber
    ): int {
        $this->database->executeQuery(
            'INSERT INTO `tvshow_season` (`tvshow`, `season_number`) VALUES (?, ?)',
            [$tvShowId, $seasonNumber]
        );

        return (int) $this->database->lastInsertId();
    }

    public function delete(int $seasonId): void
    {
        $this->database->executeQuery(
            'DELETE FROM `tvshow_season` WHERE `id` = ?',
            [$seasonId]
        );
    }

    public function update(
        int $tvShowId,
        int $seasonNumber,
        int $seasonId
    ): void {
        $this->database->executeQuery(
            'UPDATE `tvshow_season` SET `season_number` = ?, `tvshow` = ? WHERE `id` = ?',
            [
                $seasonNumber,
                $tvShowId,
                $seasonId
            ]
        );
    }

    /**
     * @return int[]
     */
    public function getSeasonIdsByTvShowId(int $tvShowId): array
    {
        $dbResults = $this->database->executeQuery(
            'SELECT `id` FROM `tvshow_season` WHERE `tvshow` = ? ORDER BY `season_number`',
            [$tvShowId]
        );

        $result = [];
        while ($rowId = $dbResults->fetchOne()) {
            $result[] = (int) $rowId;
        }

        return $result;
    }
}
