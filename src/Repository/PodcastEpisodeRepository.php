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
use Ampache\Module\Podcast\PodcastStateEnum;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\PodcastEpisodeInterface;
use Ampache\Repository\Model\PodcastInterface;
use Doctrine\DBAL\Connection;

final class PodcastEpisodeRepository implements PodcastEpisodeRepositoryInterface
{
    private ConfigContainerInterface $configContainer;

    private Connection $connection;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        Connection $connection,
        ModelFactoryInterface $modelFactory
    ) {
        $this->configContainer = $configContainer;
        $this->connection      = $connection;
        $this->modelFactory    = $modelFactory;
    }

    /**
     * This returns the latest podcast episodes in this catalog
     *
     * @return iterable<Podcast_Episode>
     */
    public function getNewestPodcastEpisodes(
        int $catalogId,
        int $count
    ): iterable {
        $sql = <<<SQL
        SELECT
            `podcast_episode`.`id`
        FROM
            `podcast_episode`
        INNER JOIN
            `podcast`
        ON
            `podcast`.`id` = `podcast_episode`.`podcast`
        WHERE
            `podcast`.`catalog` = ? 
        ORDER BY
            `podcast_episode`.`pubdate` DESC
        SQL;

        if ($count > 0) {
            $sql .= sprintf(' LIMIT %d', $count);
        }

        $result = $this->connection->executeQuery(
            $sql,
            [$catalogId]
        );

        while ($episodeId = $result->fetchOne()) {
            yield $this->findById((int) $episodeId);
        }
    }

    /**
     * @return iterable<Podcast_Episode>
     */
    public function getDownloadableEpisodes(
        PodcastInterface $podcast,
        int $limit
    ): iterable {
        $sql = <<<SQL
        SELECT
            `podcast_episode`.`id`
        FROM
            `podcast_episode`
            INNER JOIN 
                `podcast`
            ON
                `podcast`.`id` = `podcast_episode`.`podcast`
        WHERE
            `podcast`.`id` = ? AND `podcast_episode`.`addition_time` > `podcast`.`lastsync`
        ORDER BY
              `podcast_episode`.`pubdate` DESC
        LIMIT %d;
        SQL;

        $result = $this->connection->executeQuery(
            sprintf($sql, $limit),
            [$podcast->getId()]
        );

        while ($episodeId = $result->fetchOne()) {
            yield $this->findById((int) $episodeId);
        }
    }

    /**
     * @return iterable<Podcast_Episode>
     */
    public function getDeletableEpisodes(
        PodcastInterface $podcast,
        int $limit
    ): iterable {
        $sql = <<<SQL
        SELECT
            `podcast_episode`.`id`
        FROM
            `podcast_episode`
        WHERE
            `podcast_episode`.`podcast` = ?
        ORDER BY
            `podcast_episode`.`pubdate` DESC
        LIMIT
            %d,18446744073709551615
        SQL;

        $result = $this->connection->executeQuery(
            sprintf($sql, $limit),
            [$podcast->getId()]
        );

        while ($episodeId = $result->fetchOne()) {
            yield $this->findById((int) $episodeId);
        }
    }

    public function create(
        PodcastInterface $podcast,
        string $title,
        string $guid,
        string $source,
        string $website,
        string $description,
        string $author,
        string $category,
        int $time,
        int $publicationDate
    ): bool {
        $sql = <<<SQL
        INSERT INTO
            `podcast_episode`
            (`title`, `guid`, `podcast`, `state`, `source`, `website`, `description`, `author`, `category`, `time`, `pubdate`, `addition_time`)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP())
        SQL;

        $result = $this->connection->executeQuery(
            $sql,
            [
                $title,
                $guid,
                $podcast->getId(),
                PodcastStateEnum::PENDING,
                $source,
                $website,
                $description,
                $author,
                $category,
                $time,
                $publicationDate,
            ]
        );

        return $result->rowCount() > 0;
    }

    /**
     * Gets all episodes for the podcast
     *
     * @return int[]
     */
    public function getEpisodeIds(
        PodcastInterface $podcast,
        ?string $state_filter = null
    ): array {
        $catalogDisabled = $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::CATALOG_DISABLE);

        $params = [];
        $sql    = 'SELECT `podcast_episode`.`id` FROM `podcast_episode` ';
        if ($catalogDisabled) {
            $sql .= 'LEFT JOIN `podcast` ON `podcast`.`id` = `podcast_episode`.`podcast` ';
            $sql .= 'LEFT JOIN `catalog` ON `catalog`.`id` = `podcast`.`catalog` ';
        }
        $sql .= 'WHERE `podcast_episode`.`podcast`= ? ';
        $params[] = $podcast->getId();
        if ($state_filter !== null) {
            $sql .= 'AND `podcast_episode`.`state` = ? ';
            $params[] = $state_filter;
        }
        if ($catalogDisabled) {
            $sql .= 'AND `catalog`.`enabled` = \'1\' ';
        }
        $sql .= 'ORDER BY `podcast_episode`.`pubdate` DESC';

        $result = $this->connection->executeQuery(
            $sql,
            $params
        );

        $episodeIds = [];
        while ($episodeId = $result->fetchOne()) {
            $episodeIds[] = (int) $episodeId;
        }

        return $episodeIds;
    }

    public function remove(PodcastEpisodeInterface $podcastEpisode): bool
    {
        $result = $this->connection->executeQuery(
            'DELETE FROM `podcast_episode` WHERE `id` = ?',
            [$podcastEpisode->getId()]
        );

        return $result->rowCount() > 0;
    }

    public function changeState(
        PodcastEpisodeInterface $podcastEpisode,
        string $state
    ): void {
        $this->connection->executeQuery(
            'UPDATE `podcast_episode` SET `state` = ? WHERE `id` = ?',
            [$state, $podcastEpisode->getId()]
        );
    }

    /**
     * Sets the vital meta informations after the episode has been downloaded
     */
    public function updateDownloadState(
        PodcastEpisodeInterface $podcastEpisode,
        string $filePath,
        int $size,
        int $duration,
        ?int $bitrate,
        ?int $frequency,
        ?string $mode
    ): void {
        $sql = <<<SQL
        UPDATE
            `podcast_episode`
        SET
            `file` = ?, `size` = ?, `time` = ?, `state` = ?, `bitrate` = ?, `rate` = ?, `mode` = ? WHERE `id` = ?
        SQL;

        $this->connection->executeQuery(
            $sql,
            [
                $filePath,
                $size,
                $duration,
                PodcastStateEnum::COMPLETED,
                $bitrate,
                $frequency,
                $mode,
                $podcastEpisode->getId()
            ]
        );
    }

    /**
     * Cleans up the podcast_episode table
     */
    public function collectGarbage(): void
    {
        $sql = <<<SQL
        DELETE FROM
            `podcast_episode`
        USING
            `podcast_episode`
        LEFT JOIN
            `podcast`
        ON
            `podcast`.`id` = `podcast_episode`.`podcast`
        WHERE
            `podcast`.`id` IS NULL
        SQL;

        $this->connection->executeQuery($sql);
    }

    /**
     * Returns the amount of available episodes for a certain podcast
     */
    public function getEpisodeCount(PodcastInterface $podcast): int
    {
        $sql = <<<SQL
        SELECT
            COUNT(`podcast_episode`.`id`) AS `episode_count`
        FROM
            `podcast_episode`
        WHERE
            `podcast_episode`.`podcast` = ?
        SQL;

        return (int) $this->connection->fetchOne(
            $sql,
            [$podcast->getId()]
        );
    }

    public function findById(
        int $id
    ): ?PodcastEpisodeInterface {
        $episode = $this->modelFactory->createPodcastEpisode($id);
        if ($episode->isNew()) {
            return null;
        }

        return $episode;
    }

    public function update(
        PodcastEpisodeInterface $podcastEpisode,
        ?string $title,
        ?string $website,
        ?string $description,
        ?string $author,
        ?string $category
    ): void {
        $this->connection->executeQuery(
            'UPDATE `podcast_episode` SET `title` = ?, `website` = ?, `description` = ?, `author` = ?, `category` = ? WHERE `id` = ?',
            [$title, $website, $description, $author, $category, $podcastEpisode->getId()]
        );
    }

    /**
     * Sets the played state for the episode
     */
    public function setPlayed(PodcastEpisodeInterface $episode): void
    {
        $this->connection->executeQuery(
            'UPDATE `podcast_episode` SET `played` = ? WHERE `id` = ?',
            [1, $episode->getId()]
        );
    }
}
