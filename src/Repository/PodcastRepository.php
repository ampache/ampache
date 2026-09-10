<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;
use Generator;
use PDO;
use Psr\Log\LoggerInterface;

/**
 * Manages podcast related database access
 *
 * Tables: `podcast`, `podcast_episode`, `deleted_podcast_episodes`
 */
final readonly class PodcastRepository implements PodcastRepositoryInterface
{
    public function __construct(
        private ModelFactoryInterface $modelFactory,
        private DatabaseConnectionInterface $connection,
        private LoggerInterface $logger,
    ) {}

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
     * Removes every podcasts of one catalog, for a catalog that is being deleted
     */
    public function deleteByCatalog(int $catalogId): bool
    {
        try {
            $this->connection->query('DELETE FROM `podcast` WHERE `catalog` = ?', [$catalogId]);
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve all podcast objects and maintain db-order
     *
     * @return Generator<Podcast>
     */
    public function findAll(): Generator
    {
        $result = $this->connection->query(
            'SELECT `id` FROM `podcast`',
        );

        while ($podcastId = $result->fetchColumn()) {
            yield $this->modelFactory->createPodcast((int) $podcastId);
        }
    }

    /**
     * Every podcast living in one of the given catalogs
     *
     * @param list<int> $catalogIds
     *
     * @return Generator<Podcast>
     */
    public function findAllByCatalogs(array $catalogIds): Generator
    {
        if ($catalogIds === []) {
            return;
        }

        $result = $this->connection->query(
            sprintf(
                'SELECT `id` FROM `podcast` WHERE `catalog` IN (%s)',
                implode(',', array_fill(0, count($catalogIds), '?'))
            ),
            $catalogIds
        );

        while ($podcastId = $result->fetchColumn()) {
            yield $this->modelFactory->createPodcast((int) $podcastId);
        }
    }

    /**
     * Searches for an existing podcast object by the feed url
     */
    public function findByFeedUrl(
        string $feedUrl,
    ): ?Podcast {
        $podcastId = $this->connection->fetchOne(
            'SELECT `id` FROM `podcast` WHERE `feed` = ?',
            [$feedUrl]
        );

        if ($podcastId !== false) {
            return $this->modelFactory->createPodcast((int) $podcastId);
        }

        return null;
    }

    /**
     * Retrieve a single podcast-item by its id
     */
    public function findById(int $podcastId): ?Podcast
    {
        $podcast = $this->modelFactory->createPodcast($podcastId);
        if ($podcast->isNew()) {
            return null;
        }

        return $podcast;
    }

    /**
     * Reads the podcasts of one catalog
     *
     * @return list<int>
     */
    public function getIdsByCatalog(int $catalogId): array
    {
        $result = $this->connection->query(
            'SELECT `podcast`.`id` FROM `podcast` WHERE `podcast`.`catalog` = ?',
            [$catalogId]
        );

        $podcastIds = [];
        while ($podcastId = $result->fetchColumn()) {
            $podcastIds[] = (int) $podcastId;
        }

        return $podcastIds;
    }

    /**
     * Reads whole podcast rows for the in-process cache, in one statement instead of one per object
     *
     * @param array<int|string> $podcastIds
     * @return list<array<string, mixed>>
     */
    public function getRowsByIds(array $podcastIds): array
    {
        if ($podcastIds === []) {
            return [];
        }

        $idList = implode(',', array_map(intval(...), $podcastIds));

        $result = $this->connection->query('SELECT * FROM `podcast` WHERE `id` IN (' . $idList . ')');

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Persists the podcast-item in the database
     *
     * If the item is new, it will be created. Otherwise, an update will happen
     *
     * @return null|non-negative-int
     */
    public function persist(Podcast $podcast): ?int
    {
        $result = null;

        if ($podcast->isNew()) {
            $this->connection->query(
                'INSERT INTO `podcast` (`catalog`, `feed`, `title`, `website`, `description`, `language`, `generator`, `copyright`, `total_skip`, `total_count`, `episodes`, `lastbuilddate`, `lastsync`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $podcast->getCatalogId(),
                    $podcast->getFeedUrl(),
                    $podcast->getTitle(),
                    $podcast->getWebsite(),
                    $podcast->getDescription(),
                    $podcast->getLanguage(),
                    $podcast->getGenerator(),
                    $podcast->getCopyright(),
                    $podcast->getTotalSkip(),
                    $podcast->getTotalCount(),
                    $podcast->getEpisodeCount(),
                    $podcast->getLastBuildDate()->getTimestamp(),
                    $podcast->getLastSyncDate()->getTimestamp()
                ]
            );

            $result = $this->connection->getLastInsertedId();
        } else {
            $this->connection->query(
                'UPDATE `podcast` SET `feed` = ?, `title` = ?, `website` = ?, `description` = ?, `language` = ?, `generator` = ?, `copyright` = ?, `total_skip` = ?, `total_count` = ?, `episodes` = ?, `lastbuilddate` = ?, `lastsync` = ? WHERE `id` = ?',
                [
                    $podcast->getFeedUrl(),
                    $podcast->getTitle(),
                    $podcast->getWebsite(),
                    $podcast->getDescription(),
                    $podcast->getLanguage(),
                    $podcast->getGenerator(),
                    $podcast->getCopyright(),
                    $podcast->getTotalSkip(),
                    $podcast->getTotalCount(),
                    $podcast->getEpisodeCount(),
                    $podcast->getLastBuildDate()->getTimestamp(),
                    $podcast->getLastSyncDate()->getTimestamp(),
                    $podcast->getId(),
                ]
            );
        }

        return $result;
    }

    /**
     * Returns a new podcast item
     */
    public function prototype(): Podcast
    {
        return new Podcast();
    }

    /**
     * Points a podcast at another catalog, for a podcast whose episodes have all moved
     */
    public function setCatalog(int $podcastId, int $catalogId): bool
    {
        try {
            $this->connection->query(
                'UPDATE `podcast` SET `catalog` = ? WHERE `id` = ?;',
                [$catalogId, $podcastId]
            );
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Rolls each podcast's play and skip totals up from the episodes it holds
     */
    public function updateAllCounts(): void
    {
        $statements = [
            "UPDATE `podcast`, (SELECT SUM(`podcast_episode`.`total_count`) AS `total_count`, `podcast` FROM `podcast_episode` GROUP BY `podcast_episode`.`podcast`) AS `object_count` SET `podcast`.`total_count` = `object_count`.`total_count` WHERE `podcast`.`total_count` != `object_count`.`total_count` AND `podcast`.`id` = `object_count`.`podcast`;",
            "UPDATE `podcast`, (SELECT SUM(`podcast_episode`.`total_skip`) AS `total_skip`, `podcast` FROM `podcast_episode` GROUP BY `podcast_episode`.`podcast`) AS `object_count` SET `podcast`.`total_skip` = `object_count`.`total_skip` WHERE `podcast`.`total_skip` != `object_count`.`total_skip` AND `podcast`.`id` = `object_count`.`podcast`;",
        ];

        foreach ($statements as $sql) {
            $this->runMaintenance($sql);
        }
    }

    /**
     * Runs one count-maintenance statement, where a failure must not take the rest of the sweep down with it
     *
     * @param list<mixed> $params
     */
    private function runMaintenance(string $sql, array $params = []): void
    {
        try {
            $this->connection->query($sql, $params);
        } catch (DatabaseException) {
            $this->logger->warning(
                'count maintenance failed: ' . $sql,
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
        }
    }
}
