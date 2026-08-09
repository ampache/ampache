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

use Ampache\Repository\Model\Video;

interface VideoRepositoryInterface
{
    /**
     * Removes videos whose file matches the ignore pattern, and any left behind by a deleted catalog
     */
    public function collectGarbage(): void;

    /**
     * Records the video's details in `deleted_video` and removes the row
     */
    public function delete(Video $video): bool;

    /**
     * Removes every videos of one catalog, for a catalog that is being deleted
     */
    public function deleteByCatalog(int $catalogId): bool;

    /**
     * Records a set of videos in the `deleted_video` archive and removes them
     *
     * @param list<int> $videoIds
     */
    public function deleteByIdsWithArchive(array $videoIds): void;

    /**
     * Loads a single video, or null when the id matches nothing
     */
    public function findById(int $objectId): ?Video;

    /**
     * Reads the id of the video holding this file
     */
    public function findIdByFile(string $file): ?int;

    /**
     * Returns the recorded details of every deleted video
     *
     * @return list<array<string, mixed>>
     */
    public function getDeletedRows(): array;

    /**
     * Reads every video file of one catalog keyed by video id, for the scanner's in-process cache
     *
     * @return array<int, string>
     */
    public function getFilesByCatalog(int $catalogId, int $limit = 0, int $offset = 0): array;

    /**
     * Reads the videos of one catalog
     *
     * @return list<int>
     */
    public function getIdsByCatalog(int $catalogId): array;

    /**
     * Reads the videos whose file sits under a base folder path
     *
     * @return list<int>
     */
    public function getIdsByFilePrefix(string $folderPath): array;

    /**
     * Return the number of entries in the database...
     */
    public function getItemCount(): int;

    /**
     * This returns a number of random videos.
     *
     * @return list<int>
     */
    public function getRandom(
        int $userId,
        ?int $count = 1,
    ): array;

    /**
     * Returns the full rows for a set of ids, for the object cache
     *
     * @param array<int|string> $videoIds
     * @return array<int, array<string, mixed>>
     */
    public function getRowsByIds(array $videoIds): array;

    /**
     * Reads a page of the videos a verify pass walks, newest path first
     *
     * @return list<array{id: int, file: string, min_update_time: int}>
     */
    public function getVerifyRowsByCatalog(int $catalogId, int $limit, bool $onlyStale): array;

    /**
     * Inserts a new video row and returns its id
     *
     * @param list<mixed> $params
     */
    public function insert(array $params): int;

    /**
     * Stores the path or url a video is served from
     */
    public function setFile(int $videoId, string $file): void;

    /**
     * Moves a video to another catalog and to the file it now lives in
     */
    public function setFileAndCatalog(int $objectId, string $file, int $catalogId): bool;

    /**
     * Flags the video as played, or clears the flag
     */
    public function setPlayed(int $videoId, bool $played): void;

    /**
     * Stamps the video as updated
     */
    public function setUpdateTime(int $videoId, int $time): void;

    /**
     * Writes the title, and the release date only when the caller supplied one
     */
    public function update(Video $video, bool $withReleaseDate): void;

    /**
     * Rebuilds every video's play and skip totals from `object_count`, and the played flag that follows them
     */
    public function updateAllCounts(): void;

    /**
     * Re-derives the play/skip counters and the played flag from the recorded stats
     */
    public function updateCounts(int $videoId): void;

    /**
     * Copies the tag-derived fields of a freshly read file onto the stored row
     */
    public function updateFromTags(int $videoId, Video $newVideo, int $updateTime): void;
}
