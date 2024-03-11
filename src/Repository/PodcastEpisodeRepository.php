<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=3 shiftwidth=4 expandtab:
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

namespace Ampache\Repository;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Podcast\PodcastEpisodeStateEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Generator;

/**
 * Manages database access related to podcast-episodes
 *
 * Tables: `podcast_episode`
 */
final class PodcastEpisodeRepository implements PodcastEpisodeRepositoryInterface
{
    private ModelFactoryInterface $modelFactory;

    private DatabaseConnectionInterface $connection;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        DatabaseConnectionInterface $connection,
        ConfigContainerInterface $configContainer
    ) {
        $this->modelFactory    = $modelFactory;
        $this->connection      = $connection;
        $this->configContainer = $configContainer;
    }

    /**
     * Returns all episode-ids for the given podcast
     *
     * @param null|PodcastEpisodeStateEnum $stateFilter Return only items with this state
     *
     * @return list<int>
     */
    public function getEpisodes(Podcast $podcast, ?PodcastEpisodeStateEnum $stateFilter = null): array
    {
        $skipDisabledCatalogs = $this->configContainer->get(ConfigurationKeyEnum::CATALOG_DISABLE);

        $params = [$podcast->getId()];
        $sql    = 'SELECT `podcast_episode`.`id` FROM `podcast_episode` ';

        if ($skipDisabledCatalogs) {
            $sql .= 'LEFT JOIN `catalog` ON `catalog`.`id` = `podcast_episode`.`catalog` ';
        }

        $sql .= 'WHERE `podcast_episode`.`podcast` = ? ';

        if ($stateFilter !== null) {
            $sql .= 'AND `podcast_episode`.`state` = ? ';
            $params[] = $stateFilter->value;
        }

        if ($skipDisabledCatalogs) {
            $sql .= 'AND `catalog`.`enabled` = \'1\' ';
        }

        $sql .= 'ORDER BY `podcast_episode`.`pubdate` DESC';

        $result = $this->connection->query($sql, $params);

        $episodeIds = [];
        while ($episodeId = $result->fetchColumn()) {
            $episodeIds[] = (int) $episodeId;
        }

        return $episodeIds;
    }

    /**
     * Deletes a podcast-episode
     *
     * Before deleting the episode, a backup of the episodes meta-data is created
     */
    public function deleteEpisode(Podcast_Episode $episode): void
    {
        $params = [$episode->getId()];

        // keep details about deletions
        $sql = <<<SQL
        REPLACE INTO
            `deleted_podcast_episode`
            (`id`, `addition_time`, `delete_time`, `title`, `file`, `catalog`, `total_count`, `total_skip`, `podcast`)
        SELECT
            `id`, `addition_time`, UNIX_TIMESTAMP(), `title`, `file`, `catalog`, `total_count`, `total_skip`, `podcast`
        FROM
            `podcast_episode`
        WHERE
            `id` = ?;
        SQL;

        $this->connection->query($sql, $params);

        $this->connection->query(
            'DELETE FROM `podcast_episode` WHERE `id` = ?',
            $params
        );
    }

    /**
     * Returns all podcast episodes which are eligible for deletion
     *
     * If enabled, this will return all episodes of the podcast which are above the keep-limit
     *
     * @return Generator<Podcast_Episode>
     */
    public function getEpisodesEligibleForDeletion(Podcast $podcast): Generator
    {
        $keepLimit = (int) $this->configContainer->get(ConfigurationKeyEnum::PODCAST_KEEP);

        if ($keepLimit !== 0) {
            $result = $this->connection->query(
                sprintf(
                    'SELECT `id` FROM `podcast_episode` WHERE `podcast` = ? ORDER BY `pubdate` DESC LIMIT %d,18446744073709551615',
                    $keepLimit
                ),
                [$podcast->getId()]
            );

            while ($episodeId = $result->fetchColumn()) {
                yield $this->modelFactory->createPodcastEpisode((int) $episodeId);
            }
        }
    }

    /**
     * Returns all podcast episodes which are eligible for download
     *
     * @param null|positive-int $downloadLimit
     *
     * @return Generator<Podcast_Episode>
     */
    public function getEpisodesEligibleForDownload(Podcast $podcast, ?int $downloadLimit): Generator
    {
        $limitSql = '';
        if ($downloadLimit !== null) {
            $limitSql = sprintf(' LIMIT %d', $downloadLimit);
        }

        $query = <<<SQL
            SELECT
                `id`
            FROM
                `podcast_episode`
            WHERE
                `podcast` = ?
                AND
                (`addition_time` > ? OR `state` = ?)
            ORDER BY
                `pubdate`
            DESC%s
            SQL;

        $result = $this->connection->query(
            sprintf(
                $query,
                $limitSql
            ),
            [
                $podcast->getId(),
                $podcast->getLastSyncDate()->getTimestamp(),
                PodcastEpisodeStateEnum::PENDING->value
            ]
        );

        while ($episodeId = $result->fetchColumn()) {
            yield $this->modelFactory->createPodcastEpisode((int) $episodeId);
        }
    }

    /**
     * Returns the calculated count of available episodes for the given podcast
     */
    public function getEpisodeCount(Podcast $podcast): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(id) from `podcast_episode` where `podcast` = ?',
            [$podcast->getId()]
        );
    }

    /**
     * Updates the state of an episode
     */
    public function updateState(
        Podcast_Episode $episode,
        PodcastEpisodeStateEnum $state
    ): void {
        $this->connection->query(
            'UPDATE `podcast_episode` SET `state` = ? WHERE `id` = ?',
            [$state->value, $episode->getId()]
        );
    }

    /**
     * Cleans up orphaned episodes
     */
    public function collectGarbage(): void
    {
        $this->connection->query(
            'DELETE FROM `podcast_episode` USING `podcast_episode` LEFT JOIN `podcast` ON `podcast`.`id` = `podcast_episode`.`podcast` WHERE `podcast`.`id` IS NULL'
        );
    }
}
