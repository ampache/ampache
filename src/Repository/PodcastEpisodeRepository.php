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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Module\Podcast\PodcastEpisodeStateEnum;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Generator;
use PDO;
use Psr\Log\LoggerInterface;

/**
 * Manages database access related to podcast-episodes
 *
 * Tables: `podcast_episode`
 */
final readonly class PodcastEpisodeRepository implements PodcastEpisodeRepositoryInterface
{
    public function __construct(
        private ModelFactoryInterface $modelFactory,
        private DatabaseConnectionInterface $connection,
        private ConfigContainerInterface $configContainer,
        private LoggerInterface $logger,
    ) {}

    /**
     * Cleans up orphaned episodes
     */
    public function collectGarbage(): void
    {
        try {
            $this->connection->query(
                'DELETE FROM `podcast_episode` USING `podcast_episode` LEFT JOIN `podcast` ON `podcast`.`id` = `podcast_episode`.`podcast` WHERE `podcast`.`id` IS NULL'
            );
        } catch (DatabaseException) {
            $this->logger->debug(
                'collectGarbage error',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
        }
    }

    /**
     * Counts the episodes of one podcast still held by a catalog, which decides whether the podcast moves too
     */
    public function countByPodcastAndCatalog(int $podcastId, int $catalogId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(`id`) FROM `podcast_episode` WHERE `podcast` = ? AND `catalog` = ?;',
            [$podcastId, $catalogId]
        );
    }

    /**
     * Records a set of episodes in the `deleted_podcast_episode` archive and removes them
     *
     * @param list<int> $episodeIds
     */
    public function deleteByIdsWithArchive(array $episodeIds): void
    {
        if ($episodeIds === []) {
            return;
        }

        $idList = implode(',', array_map(intval(...), $episodeIds));

        // keep details about deletions, but losing the record must not stop the delete itself
        try {
            $this->connection->query(
                'REPLACE INTO `deleted_podcast_episode` (`id`, `addition_time`, `delete_time`, `title`, `file`, `catalog`, `total_count`, `total_skip`, `podcast`) SELECT `id`, `addition_time`, UNIX_TIMESTAMP(), `title`, `file`, `catalog`, `total_count`, `total_skip`, `podcast` FROM `podcast_episode` WHERE `id` IN (' . $idList . ');'
            );
        } catch (DatabaseException) {
            $this->logger->warning(
                'deleteByIdsWithArchive could not record deleted_podcast_episode ' . $idList,
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
        }

        $this->connection->query('DELETE FROM `podcast_episode` WHERE `id` IN (' . $idList . ');');
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
     * Finds a single item by its id
     */
    public function findById(int $itemId): ?Podcast_Episode
    {
        $item = new Podcast_Episode($itemId);
        if ($item->isNew()) {
            return null;
        }

        return $item;
    }

    /**
     * Reads the id of the episode holding this file
     */
    public function findIdByFile(string $file): ?int
    {
        $episodeId = $this->connection->fetchOne('SELECT `id` FROM `podcast_episode` WHERE `file` = ?;', [$file]);

        return ($episodeId === false || $episodeId === null)
            ? null
            : (int) $episodeId;
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
     * Returns all episode-ids for the given podcast
     *
     * @param null|PodcastEpisodeStateEnum $stateFilter Return only items with this state
     * @return int[]
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
            $sql .= "AND `catalog`.`enabled` = '1' ";
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
     * Returns all podcast episodes which are eligible for deletion
     *
     * If enabled, this will return all episodes of the podcast which are above the keep-limit
     *
     * @return Generator<Podcast_Episode>
     */
    public function getEpisodesEligibleForDeletion(Podcast $podcast): Generator
    {
        $keepLimit = $this->configContainer->getInt(ConfigurationKeyEnum::PODCAST_KEEP);

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
     * @return Generator<Podcast_Episode>
     */
    public function getEpisodesEligibleForDownload(Podcast $podcast, ?int $downloadLimit = null): Generator
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
     * Reads every episode file of one catalog keyed by episode id, for the scanner's in-process cache
     *
     * @return array<int, string>
     */
    public function getFilesByCatalog(int $catalogId, int $limit = 0, int $offset = 0): array
    {
        $result = $this->connection->query(
            'SELECT `id`, `file` FROM `podcast_episode` WHERE `catalog` = ? AND `file` IS NOT NULL ORDER BY `id` DESC' . (($limit > 0) ? sprintf(' LIMIT %d, %d', $offset, $limit) : '') . ';',
            [$catalogId]
        );

        $files = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $files[(int) $row['id']] = (string) $row['file'];
        }

        return $files;
    }

    /**
     * Reads the episodes whose file sits under a base folder path
     *
     * @return list<int>
     */
    public function getIdsByFilePrefix(string $folderPath): array
    {
        $result = $this->connection->query(
            'SELECT `id` FROM `podcast_episode` WHERE `file` LIKE ?',
            [$folderPath . '%']
        );

        $episodeIds = [];
        while ($episodeId = $result->fetchColumn()) {
            $episodeIds[] = (int) $episodeId;
        }

        return $episodeIds;
    }

    /**
     * Reads the most recently published episodes of one catalog, newest first
     *
     * @return list<int>
     */
    public function getNewestIdsByCatalog(int $catalogId, int $count): array
    {
        $sql = 'SELECT `podcast_episode`.`id` FROM `podcast_episode` INNER JOIN `podcast` ON `podcast`.`id` = `podcast_episode`.`podcast` WHERE `podcast`.`catalog` = ? ORDER BY `podcast_episode`.`pubdate` DESC';
        if ($count > 0) {
            $sql .= ' LIMIT ' . $count;
        }

        $result = $this->connection->query($sql, [$catalogId]);

        $episodeIds = [];
        while ($episodeId = $result->fetchColumn()) {
            $episodeIds[] = (int) $episodeId;
        }

        return $episodeIds;
    }

    /**
     * Returns a number of random, completed podcast episodes from the whole library
     *
     * @return list<int>
     */
    public function getRandom(int $userId, ?int $count = 1): array
    {
        $sql = 'SELECT `podcast_episode`.`id` FROM `podcast_episode` '
            . 'LEFT JOIN `catalog` ON `catalog`.`id` = `podcast_episode`.`catalog` '
            . 'WHERE `podcast_episode`.`state` = ? '
            . 'AND `catalog`.`id` IN (' . implode(',', Catalog::get_catalogs('', $userId, true)) . ') '
            . 'ORDER BY RAND() LIMIT ' . $count;

        $result = $this->connection->query($sql, [PodcastEpisodeStateEnum::COMPLETED->value]);

        $episodeIds = [];
        while ($episodeId = $result->fetchColumn()) {
            $episodeIds[] = (int) $episodeId;
        }

        return $episodeIds;
    }

    /**
     * Returns a number of random, completed episodes from a single podcast
     *
     * @return list<int>
     */
    public function getRandomByPodcast(int $podcastId, int $userId, ?int $count = 1): array
    {
        $sql = 'SELECT `podcast_episode`.`id` FROM `podcast_episode` '
            . 'LEFT JOIN `catalog` ON `catalog`.`id` = `podcast_episode`.`catalog` '
            . 'WHERE `podcast_episode`.`podcast` = ? AND `podcast_episode`.`state` = ? '
            . 'AND `catalog`.`id` IN (' . implode(',', Catalog::get_catalogs('', $userId, true)) . ') '
            . 'ORDER BY RAND() LIMIT ' . $count;

        $result = $this->connection->query($sql, [$podcastId, PodcastEpisodeStateEnum::COMPLETED->value]);

        $episodeIds = [];
        while ($episodeId = $result->fetchColumn()) {
            $episodeIds[] = (int) $episodeId;
        }

        return $episodeIds;
    }

    /**
     * Reads a page of the podcast_episodes a verify pass walks, newest path first
     *
     * @return list<array{id: int, file: string, min_update_time: int}>
     */
    public function getVerifyRowsByCatalog(int $catalogId, int $limit, bool $onlyStale): array
    {
        $sql = ($onlyStale)
            ? 'SELECT `podcast_episode`.`id`, `podcast_episode`.`file`, `podcast_episode`.`update_time` AS `min_update_time` FROM `podcast_episode` LEFT JOIN `catalog` ON `podcast_episode`.`catalog` = `catalog`.`id` WHERE `podcast_episode`.`catalog` = ? AND `podcast_episode`.`update_time` < `catalog`.`last_update` ORDER BY `podcast_episode`.`file` DESC LIMIT '
            : 'SELECT `podcast_episode`.`id`, `podcast_episode`.`file`, `podcast_episode`.`update_time` AS `min_update_time` FROM `podcast_episode` LEFT JOIN `catalog` ON `podcast_episode`.`catalog` = `catalog`.`id` WHERE `podcast_episode`.`catalog` = ? ORDER BY `podcast_episode`.`file` DESC LIMIT ';

        $result = $this->connection->query($sql . $limit . ';', [$catalogId]);

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = [
                'id' => (int) $row['id'],
                'file' => (string) $row['file'],
                'min_update_time' => (int) $row['min_update_time'],
            ];
        }

        return $rows;
    }

    /**
     * Stores the path the episode was downloaded to
     */
    public function setFile(int $episodeId, string $file): void
    {
        $this->connection->query(
            'UPDATE `podcast_episode` SET `file` = ? WHERE `id` = ?',
            [$file, $episodeId]
        );
    }

    /**
     * Moves a podcast episode to another catalog and to the file it now lives in
     */
    public function setFileAndCatalog(int $objectId, string $file, int $catalogId): bool
    {
        try {
            $this->connection->query(
                'UPDATE `podcast_episode` SET `file` = ?, `catalog` = ? WHERE `id` = ?;',
                [$file, $catalogId, $objectId]
            );
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Flags the episode as played
     */
    public function setPlayed(int $episodeId): void
    {
        $this->connection->query(
            'UPDATE `podcast_episode` SET `played` = 1 WHERE `id` = ?',
            [$episodeId]
        );
    }

    /**
     * Stamps the episode as updated
     */
    public function setUpdateTime(int $episodeId, int $time): void
    {
        $this->connection->query(
            'UPDATE `podcast_episode` SET `update_time` = ? WHERE `id` = ?;',
            [$time, $episodeId]
        );
    }

    /**
     * Writes the editable properties of an existing episode
     */
    public function update(Podcast_Episode $episode): void
    {
        $this->connection->query(
            'UPDATE `podcast_episode` SET `title` = ?, `website` = ?, `description` = ?, `author` = ?, `category` = ? WHERE `id` = ?',
            [
                $episode->title,
                $episode->website,
                $episode->description,
                $episode->author,
                $episode->category,
                $episode->getId(),
            ]
        );
    }

    /**
     * Rebuilds every episode's play and skip totals from `object_count`, and the played flag that follows them
     *
     * Each total is cleared against rows of its own count type, so an episode that was only ever skipped keeps
     * the skips it has.
     */
    public function updateAllCounts(): void
    {
        $statements = [
            "UPDATE `podcast_episode` SET `total_count` = 0 WHERE `total_count` > 0 AND `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'podcast_episode' AND `object_count`.`count_type` = 'stream' UNION SELECT `object_id` FROM `object_count_summary` WHERE `object_type` = 'podcast_episode' AND `count_type` = 'stream');",
            "UPDATE `podcast_episode` SET `total_skip` = 0 WHERE `total_skip` > 0 AND `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'podcast_episode' AND `object_count`.`count_type` = 'skip' UNION SELECT `object_id` FROM `object_count_summary` WHERE `object_type` = 'podcast_episode' AND `count_type` = 'skip');",
            "UPDATE `podcast_episode` SET `podcast_episode`.`played` = 0 WHERE `podcast_episode`.`played` = 1 AND `podcast_episode`.`id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'podcast_episode' AND `count_type` = 'stream' UNION SELECT `object_id` FROM `object_count_summary` WHERE `object_type` = 'podcast_episode' AND `count_type` = 'stream');",
            "UPDATE `podcast_episode` SET `podcast_episode`.`played` = 1 WHERE `podcast_episode`.`played` = 0 AND `podcast_episode`.`id` IN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'podcast_episode' AND `count_type` = 'stream' UNION SELECT `object_id` FROM `object_count_summary` WHERE `object_type` = 'podcast_episode' AND `count_type` = 'stream');",
            "UPDATE `podcast_episode`, (SELECT SUM(`total`) AS `total_count`, `object_id` FROM (SELECT COUNT(`object_count`.`object_id`) AS `total`, `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'podcast_episode' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id` UNION ALL SELECT `count` AS `total`, `object_id` FROM `object_count_summary` WHERE `object_type` = 'podcast_episode' AND `count_type` = 'stream') AS `combined_count` GROUP BY `object_id`) AS `object_count` SET `podcast_episode`.`total_count` = `object_count`.`total_count` WHERE `podcast_episode`.`total_count` != `object_count`.`total_count` AND `podcast_episode`.`id` = `object_count`.`object_id`;",
            "UPDATE `podcast_episode` SET `played` = 0 WHERE `total_count` = 0 and `played` = 1;",
        ];

        foreach ($statements as $sql) {
            $this->runMaintenance($sql);
        }
    }

    /**
     * Writes the description an episode's feed item now carries
     */
    public function updateDescription(int $episodeId, string $description): void
    {
        $this->connection->query(
            'UPDATE `podcast_episode` SET `description` = ? WHERE `id` = ?',
            [$description, $episodeId]
        );
    }

    /**
     * Writes back what reading the downloaded file told us about it, and marks the episode complete
     *
     * @param array<string, mixed> $values
     */
    public function updateFromTags(int $episodeId, string $file, array $values, int $updateTime): void
    {
        $this->connection->query(
            "UPDATE `podcast_episode` SET `file` = ?, `size` = ?, `time` = ?, `bitrate` = ?, `rate` = ?, `mode` = ?, `channels` = ?, `update_time` = ?, `state` = 'completed' WHERE `id` = ?",
            [
                $file,
                $values['size'],
                $values['time'],
                $values['bitrate'],
                $values['rate'],
                (in_array($values['mode'], ['vbr', 'cbr', 'abr'])) ? $values['mode'] : 'vbr',
                $values['channels'],
                $updateTime,
                $episodeId,
            ]
        );
    }

    /**
     * Updates the state of an episode
     */
    public function updateState(
        Podcast_Episode $episode,
        PodcastEpisodeStateEnum $state,
    ): void {
        $this->connection->query(
            'UPDATE `podcast_episode` SET `state` = ? WHERE `id` = ?',
            [$state->value, $episode->getId()]
        );
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
