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
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Generator;
use PDO;

/**
 * Manages podcast related database access
 *
 * Tables: `podcast`, `podcast_episode`
 */
final class PodcastRepository implements PodcastRepositoryInterface
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
     * Searches for an existing podcast object by the feed url
     */
    public function findByFeedUrl(
        string $feedUrl
    ): ?Podcast {
        $podcastId = $this->connection->fetchOne(
            'SELECT `id` FROM `podcast` WHERE `feed`= ?',
            [
                $feedUrl
            ]
        );

        if ($podcastId !== false) {
            return $this->modelFactory->createPodcast((int) $podcastId);
        }

        return null;
    }

    /**
     * Creates a new podcast database item
     *
     * @param array{
     *   title: string,
     *   website: string,
     *   description: string,
     *   language: string,
     *   copyright: string,
     *   generator: string,
     *   lastBuildDate: null|int
     *  } $data
     */
    public function create(
        Catalog $catalog,
        string $feedUrl,
        array $data
    ): Podcast {
        $this->connection->query(
            <<<SQL
            INSERT INTO
                `podcast`
                (
                    `feed`,
                    `catalog`,
                    `title`,
                    `website`,
                    `description`,
                    `language`,
                    `copyright`,
                    `generator`,
                    `lastbuilddate`
                )
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?)
            SQL,
            [
                $feedUrl,
                $catalog->getId(),
                $data['title'],
                $data['website'],
                $data['description'],
                $data['language'],
                $data['copyright'],
                $data['generator'],
                $data['lastBuildDate']
            ]
        );

        return $this->modelFactory->createPodcast(
            $this->connection->getLastInsertedId()
        );
    }

    /**
     * Returns all episode-ids for the given podcast
     *
     * @param string $stateFilter Return only items with this state
     *
     * @return list<int>
     */
    public function getEpisodes(Podcast $podcast, string $stateFilter = ''): array
    {
        $skipDisabledCatalogs = $this->configContainer->get(ConfigurationKeyEnum::CATALOG_DISABLE);

        $params = [$podcast->getId()];
        $sql    = 'SELECT `podcast_episode`.`id` FROM `podcast_episode` ';

        if ($skipDisabledCatalogs) {
            $sql .= 'LEFT JOIN `catalog` ON `catalog`.`id` = `podcast_episode`.`catalog` ';
        }

        $sql .= 'WHERE `podcast_episode`.`podcast`= ? ';

        if (!empty($stateFilter)) {
            $sql .= 'AND `podcast_episode`.`state` = ? ';
            $params[] = $stateFilter;
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
     * Deletes a podcast
     */
    public function delete(Podcast $podcast): void
    {
        $this->connection->query(
            'DELETE FROM `podcast` WHERE `id` = ?',
            [$podcast->getId()]
        );
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

    /**
     * Returns all deleted podcast episodes
     *
     * @return list<array{
     *  id: int,
     *  addition_time: int,
     *  delete_time: int,
     *  title: string,
     *  file: string,
     *  catalog: int,
     *  total_count: int,
     *  total_skip: int,
     *  podcast: int
     * }>
     */
    public function getDeletedEpisodes(): array
    {
        $episodes = [];

        $result = $this->connection->query('SELECT * FROM `deleted_podcast_episode`');
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $episodes[] = [
                'id' => (int) $row['id'],
                'addition_time' => (int) $row['addition_time'],
                'delete_time' => (int) $row['delete_time'],
                'title' => (string) $row['title'],
                'file' => (string) $row['file'],
                'catalog' => (int) $row['catalog'],
                'total_count' => (int) $row['total_count'],
                'total_skip' => (int) $row['total_skip'],
                'podcast' => (int) $row['podcast'],
            ];
        }

        return $episodes;
    }
}
