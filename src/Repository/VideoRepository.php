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
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Video;
use PDO;
use Psr\Log\LoggerInterface;

final readonly class VideoRepository implements VideoRepositoryInterface
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
        private ConfigContainerInterface $configContainer,
        private ModelFactoryInterface $modelFactory,
        private LoggerInterface $logger,
    ) {}

    /**
     * Removes videos whose file matches the ignore pattern, and any left behind by a deleted catalog
     */
    public function collectGarbage(): void
    {
        // delete files matching catalog_ignore_pattern
        $ignorePattern = $this->configContainer->get(ConfigurationKeyEnum::CATALOG_IGNORE_PATTERN);
        if ($ignorePattern) {
            $this->connection->query('DELETE FROM `video` WHERE `file` REGEXP ?;', [$ignorePattern]);
        }

        // clean up missing catalogs
        $this->connection->query('DELETE FROM `video` WHERE `video`.`catalog` NOT IN (SELECT `id` FROM `catalog`);');
    }

    /**
     * Records the video's details in `deleted_video` and removes the row
     *
     * Returns false when the delete failed, so the caller can skip the dependent garbage collection
     */
    public function delete(Video $video): bool
    {
        $params = [$video->getId()];

        try {
            $this->connection->query(
                'REPLACE INTO `deleted_video` (`id`, `addition_time`, `delete_time`, `title`, `file`, `catalog`, `total_count`, `total_skip`) SELECT `id`, `addition_time`, UNIX_TIMESTAMP(), `title`, `file`, `catalog`, `total_count`, `total_skip` FROM `video` WHERE `id` = ?;',
                $params
            );

            $this->connection->query('DELETE FROM `video` WHERE `id` = ?', $params);
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Removes every videos of one catalog, for a catalog that is being deleted
     */
    public function deleteByCatalog(int $catalogId): bool
    {
        try {
            $this->connection->query('DELETE FROM `video` WHERE `catalog` = ?', [$catalogId]);
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Records a set of videos in the `deleted_video` archive and removes them
     *
     * @param list<int> $videoIds
     */
    public function deleteByIdsWithArchive(array $videoIds): void
    {
        if ($videoIds === []) {
            return;
        }

        $idList = implode(',', array_map(intval(...), $videoIds));

        // keep details about deletions, but losing the record must not stop the delete itself
        try {
            $this->connection->query(
                'REPLACE INTO `deleted_video` (`id`, `addition_time`, `delete_time`, `title`, `file`, `catalog`, `total_count`, `total_skip`) SELECT `id`, `addition_time`, UNIX_TIMESTAMP(), `title`, `file`, `catalog`, `total_count`, `total_skip` FROM `video` WHERE `id` IN (' . $idList . ');'
            );
        } catch (DatabaseException) {
            $this->logger->warning(
                'deleteByIdsWithArchive could not record deleted_video ' . $idList,
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
        }

        $this->connection->query('DELETE FROM `video` WHERE `id` IN (' . $idList . ');');
    }

    /**
     * Loads a single video, or null when the id matches nothing
     */
    public function findById(int $objectId): ?Video
    {
        $video = $this->modelFactory->createVideo($objectId);

        if ($video->isNew()) {
            return null;
        }

        return $video;
    }

    /**
     * Reads the id of the video holding this file
     */
    public function findIdByFile(string $file): ?int
    {
        $videoId = $this->connection->fetchOne('SELECT `id` FROM `video` WHERE `file` = ?;', [$file]);

        return ($videoId === false || $videoId === null)
            ? null
            : (int) $videoId;
    }

    /**
     * Returns the recorded details of every deleted video
     *
     * @return list<array<string, mixed>>
     */
    public function getDeletedRows(): array
    {
        $result = $this->connection->query('SELECT * FROM `deleted_video`');

        $results = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $row;
        }

        return $results;
    }

    /**
     * Reads every video file of one catalog keyed by video id, for the scanner's in-process cache
     *
     * @return array<int, string>
     */
    public function getFilesByCatalog(int $catalogId, int $limit = 0, int $offset = 0): array
    {
        $result = $this->connection->query(
            'SELECT `id`, `file` FROM `video` WHERE `catalog` = ? AND `file` IS NOT NULL ORDER BY `id` DESC' . (($limit > 0) ? sprintf(' LIMIT %d, %d', $offset, $limit) : '') . ';',
            [$catalogId]
        );

        $files = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $files[(int) $row['id']] = (string) $row['file'];
        }

        return $files;
    }

    /**
     * Reads the videos of one catalog
     *
     * @return list<int>
     */
    public function getIdsByCatalog(int $catalogId): array
    {
        $result = $this->connection->query(
            'SELECT DISTINCT(`video`.`id`) AS `id` FROM `video` WHERE `video`.`catalog` = ?',
            [$catalogId]
        );

        $videoIds = [];
        while ($videoId = $result->fetchColumn()) {
            $videoIds[] = (int) $videoId;
        }

        return $videoIds;
    }

    /**
     * Reads the videos whose file sits under a base folder path
     *
     * @return list<int>
     */
    public function getIdsByFilePrefix(string $folderPath): array
    {
        $result = $this->connection->query(
            'SELECT `id` FROM `video` WHERE `file` LIKE ?',
            [$folderPath . '%']
        );

        $videoIds = [];
        while ($videoId = $result->fetchColumn()) {
            $videoIds[] = (int) $videoId;
        }

        return $videoIds;
    }

    /**
     * Return the number of entries in the database...
     */
    public function getItemCount(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) AS `count` FROM `video`;');
    }

    /**
     * This returns a number of random videos.
     *
     * @return list<int>
     */
    public function getRandom(
        int $userId,
        ?int $count = 1,
    ): array {
        $result = $this->connection->query(
            "SELECT DISTINCT(`video`.`id`) AS `id` FROM `video` LEFT JOIN `catalog` ON `catalog`.`id` = `video`.`catalog` WHERE `video`.`enabled` = '1' AND `catalog`.`id` IN (" . implode(',', Catalog::get_catalogs('', $userId, true)) . ') ORDER BY RAND() LIMIT ' . $count
        );

        $results = [];
        while ($rowId = $result->fetchColumn()) {
            $results[] = (int) $rowId;
        }

        return $results;
    }

    /**
     * Returns the full rows for a set of ids, for the object cache
     *
     * @param array<int|string> $videoIds
     * @return array<int, array<string, mixed>>
     */
    public function getRowsByIds(array $videoIds): array
    {
        if ($videoIds === []) {
            return [];
        }

        $result = $this->connection->query(
            'SELECT * FROM `video` WHERE `video`.`id` IN (' . implode(',', array_map(intval(...), $videoIds)) . ')'
        );

        $results = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $results[] = $row;
        }

        return $results;
    }

    /**
     * Reads a page of the videos a verify pass walks, newest path first
     *
     * @return list<array{id: int, file: string, min_update_time: int}>
     */
    public function getVerifyRowsByCatalog(int $catalogId, int $limit, bool $onlyStale, int $offset = 0): array
    {
        $sql = ($onlyStale)
            ? 'SELECT `video`.`id`, `video`.`file`, `video`.`update_time` AS `min_update_time` FROM `video` LEFT JOIN `catalog` ON `video`.`catalog` = `catalog`.`id` WHERE `video`.`catalog` = ? AND `video`.`update_time` < `catalog`.`last_update` ORDER BY `video`.`file` DESC LIMIT '
            : 'SELECT `video`.`id`, `video`.`file`, `video`.`update_time` AS `min_update_time` FROM `video` LEFT JOIN `catalog` ON `video`.`catalog` = `catalog`.`id` WHERE `video`.`catalog` = ? ORDER BY `video`.`file` DESC LIMIT ';

        $result = $this->connection->query($sql . $limit . ' OFFSET ' . $offset . ';', [$catalogId]);

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
     * Inserts a new video row and returns its id
     *
     * @param list<mixed> $params
     */
    public function insert(array $params): int
    {
        $this->connection->query(
            'INSERT INTO `video` (`file`, `catalog`, `title`, `video_codec`, `audio_codec`, `resolution_x`, `resolution_y`, `size`, `time`, `mime`, `release_date`, `addition_time`, `bitrate`, `mode`, `channels`, `display_x`, `display_y`, `frame_rate`, `video_bitrate`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            $params
        );

        return $this->connection->getLastInsertedId();
    }

    /**
     * Stores the path or url a video is served from
     */
    public function setFile(int $videoId, string $file): void
    {
        $this->connection->query('UPDATE `video` SET `file` = ? WHERE `id` = ?', [$file, $videoId]);
    }

    /**
     * Moves a video to another catalog and to the file it now lives in
     */
    public function setFileAndCatalog(int $objectId, string $file, int $catalogId): bool
    {
        try {
            $this->connection->query(
                'UPDATE `video` SET `file` = ?, `catalog` = ? WHERE `id` = ?;',
                [$file, $catalogId, $objectId]
            );
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Flags the video as played, or clears the flag
     */
    public function setPlayed(int $videoId, bool $played): void
    {
        $this->connection->query(
            'UPDATE `video` SET `played` = ? WHERE `id` = ?',
            [($played) ? 1 : 0, $videoId]
        );
    }

    /**
     * Stamps the video as updated
     */
    public function setUpdateTime(int $videoId, int $time): void
    {
        $this->connection->query(
            'UPDATE `video` SET `update_time` = ? WHERE `id` = ?;',
            [$time, $videoId]
        );
    }

    /**
     * Writes the title, and the release date only when the caller supplied one
     */
    public function update(Video $video, bool $withReleaseDate): void
    {
        $sql    = 'UPDATE `video` SET `title` = ?';
        $params = [$video->title];

        if ($withReleaseDate) {
            $sql .= ', `release_date` = ?';
            $params[] = $video->release_date;
        }

        $sql .= ' WHERE `id` = ?';
        $params[] = $video->getId();

        $this->connection->query($sql, $params);
    }

    /**
     * Rebuilds every video's play and skip totals from `object_count`, and the played flag that follows them
     *
     * Each total is cleared against rows of its own count type, so a video that was only ever skipped keeps
     * the skips it has.
     */
    public function updateAllCounts(): void
    {
        $statements = [
            "UPDATE `video` SET `total_count` = 0 WHERE `total_count` > 0 AND `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'video' AND `object_count`.`count_type` = 'stream' UNION SELECT `object_id` FROM `object_count_summary` WHERE `object_type` = 'video' AND `count_type` = 'stream');",
            "UPDATE `video` SET `total_skip` = 0 WHERE `total_skip` > 0 AND `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'video' AND `object_count`.`count_type` = 'skip' UNION SELECT `object_id` FROM `object_count_summary` WHERE `object_type` = 'video' AND `count_type` = 'skip');",
            "UPDATE `video` SET `video`.`played` = 0 WHERE `video`.`played` = 1 AND `video`.`id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'video' AND `count_type` = 'stream' UNION SELECT `object_id` FROM `object_count_summary` WHERE `object_type` = 'video' AND `count_type` = 'stream');",
            "UPDATE `video` SET `video`.`played` = 1 WHERE `video`.`played` = 0 AND `video`.`id` IN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'video' AND `count_type` = 'stream' UNION SELECT `object_id` FROM `object_count_summary` WHERE `object_type` = 'video' AND `count_type` = 'stream');",
            "UPDATE `video`, (SELECT SUM(`total`) AS `total_count`, `object_id` FROM (SELECT COUNT(`object_count`.`object_id`) AS `total`, `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'video' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id` UNION ALL SELECT `count` AS `total`, `object_id` FROM `object_count_summary` WHERE `object_type` = 'video' AND `count_type` = 'stream') AS `combined_count` GROUP BY `object_id`) AS `object_count` SET `video`.`total_count` = `object_count`.`total_count` WHERE `video`.`total_count` != `object_count`.`total_count` AND `video`.`id` = `object_count`.`object_id`;",
            "UPDATE `video` SET `played` = 0 WHERE `total_count` = 0 and `played` = 1;",
        ];

        foreach ($statements as $sql) {
            $this->runMaintenance($sql);
        }
    }

    /**
     * Re-derives the play/skip counters and the played flag from the recorded stats
     */
    public function updateCounts(int $videoId): void
    {
        $params = [$videoId, $videoId];

        $this->connection->query(
            "UPDATE `video` SET `total_count` = 0 WHERE `total_count` > 0 AND `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_id` = ? AND `object_count`.`object_type` = 'video' AND `object_count`.`count_type` = 'stream' UNION SELECT `object_id` FROM `object_count_summary` WHERE `object_id` = ? AND `object_type` = 'video' AND `count_type` = 'stream');",
            $params
        );
        $this->connection->query(
            "UPDATE `video` SET `total_skip` = 0 WHERE `total_skip` > 0 AND `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_id` = ? AND `object_count`.`object_type` = 'video' AND `object_count`.`count_type` = 'stream' UNION SELECT `object_id` FROM `object_count_summary` WHERE `object_id` = ? AND `object_type` = 'video' AND `count_type` = 'stream');",
            $params
        );
        $this->connection->query(
            "UPDATE `video` SET `video`.`played` = 0 WHERE `video`.`played` = 1 AND `video`.`id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_id` = ? AND `object_type` = 'video' AND `count_type` = 'stream' UNION SELECT `object_id` FROM `object_count_summary` WHERE `object_id` = ? AND `object_type` = 'video' AND `count_type` = 'stream');",
            $params
        );
        $this->connection->query(
            "UPDATE `video` SET `video`.`played` = 1 WHERE `video`.`played` = 0 AND `video`.`id` IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_id` = ? AND `object_type` = 'video' AND `count_type` = 'stream' UNION SELECT `object_id` FROM `object_count_summary` WHERE `object_id` = ? AND `object_type` = 'video' AND `count_type` = 'stream');",
            $params
        );
    }

    /**
     * Copies the tag-derived fields of a freshly read file onto the stored row
     */
    public function updateFromTags(int $videoId, Video $newVideo, int $updateTime): void
    {
        $this->connection->query(
            'UPDATE `video` SET `title` = ?, `bitrate` = ?, `size` = ?, `time` = ?, `video_codec` = ?, `audio_codec` = ?, `resolution_x` = ?, `resolution_y` = ?, `release_date` = ?, `channels` = ?, `display_x` = ?, `display_y` = ?, `frame_rate` = ?, `video_bitrate` = ?, `update_time` = ? WHERE `id` = ?',
            [
                $newVideo->title,
                $newVideo->bitrate,
                $newVideo->size,
                $newVideo->time,
                $newVideo->video_codec,
                $newVideo->audio_codec,
                $newVideo->resolution_x,
                $newVideo->resolution_y,
                (is_numeric($newVideo->release_date)) ? $newVideo->release_date : null,
                $newVideo->channels,
                $newVideo->display_x,
                $newVideo->display_y,
                $newVideo->frame_rate,
                $newVideo->video_bitrate,
                $updateTime,
                $videoId,
            ]
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
