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
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Video;
use PDO;

final readonly class VideoRepository implements VideoRepositoryInterface
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
        private ConfigContainerInterface $configContainer,
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
     * Return the number of entries in the database...
     */
    public function getItemCount(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) AS `count` FROM `video`;');
    }

    /**
     * This returns a number of random videos.
     *
     * @return int[]
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
}
