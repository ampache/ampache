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
use Ampache\Repository\Model\TvShowEpisodeInterface;
use Doctrine\DBAL\Connection;

final class TvShowEpisodeRepository implements TvShowEpisodeRepositoryInterface
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
     * gets all episode ids by tv show
     *
     * @return int[]
     */
    public function getEpisodeIdsByTvShow(
        int $tvShowId
    ): array {
        $catalogDisable = $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::CATALOG_DISABLE);

        $sql = 'SELECT `tvshow_episode`.`id` FROM `tvshow_episode` ';
        if ($catalogDisable) {
            $sql .= 'LEFT JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` ';
            $sql .= 'LEFT JOIN `catalog` ON `catalog`.`id` = `video`.`catalog` ';
        }
        $sql .= 'LEFT JOIN `tvshow_season` ON `tvshow_season`.`id` = `tvshow_episode`.`season` ';
        $sql .= 'WHERE `tvshow_season`.`tvshow` = ? ';
        if ($catalogDisable) {
            $sql .= 'AND `catalog`.`enabled` = \'1\' ';
        }
        $sql .= 'ORDER BY `tvshow_season`.`season_number`, `tvshow_episode`.`episode_number`';

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
     * This cleans out unused tv shows episodes
     */
    public function collectGarbage(): void
    {
        $this->database->executeQuery(
            'DELETE FROM `tvshow_episode` USING `tvshow_episode` LEFT JOIN `video` ON `video`.`id` = `tvshow_episode`.`id` WHERE `video`.`id` IS NULL'
        );
    }

    public function delete(TvShowEpisodeInterface $episode): void
    {
        $this->database->executeQuery(
            'DELETE FROM `tvshow_episode` WHERE `id` = ?',
            [$episode->getId()]
        );
    }

    public function update(
        string $originalName,
        int $seasonId,
        int $episodeNumber,
        string $summary,
        int $episodeId
    ): void {
        $this->database->executeQuery(
            'UPDATE `tvshow_episode` SET `original_name` = ?, `season` = ?, `episode_number` = ?, `summary` = ? WHERE `id` = ?',
            [$originalName, $seasonId, $episodeNumber, $summary, $episodeId]
        );
    }

    public function create(
        int $episodeId,
        string $originalName,
        int $tvShowSeasonId,
        int $episodeNumber,
        string $summary
    ): void {
        $this->database->executeQuery(
            'INSERT INTO `tvshow_episode` (`id`, `original_name`, `season`, `episode_number`, `summary`) VALUES (?, ?, ?, ?, ?)',
            [
                $episodeId,
                $originalName,
                $tvShowSeasonId,
                $episodeNumber,
                $summary
            ]
        );
    }
}
