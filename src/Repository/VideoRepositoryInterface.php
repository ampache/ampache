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
     * Returns the recorded details of every deleted video
     *
     * @return list<array<string, mixed>>
     */
    public function getDeletedRows(): array;

    /**
     * Return the number of entries in the database...
     */
    public function getItemCount(): int;

    /**
     * This returns a number of random videos.
     *
     * @return int[]
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
     * Inserts a new video row and returns its id
     *
     * @param list<mixed> $params
     */
    public function insert(array $params): int;

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
     * Re-derives the play/skip counters and the played flag from the recorded stats
     */
    public function updateCounts(int $videoId): void;

    /**
     * Copies the tag-derived fields of a freshly read file onto the stored row
     */
    public function updateFromTags(int $videoId, Video $newVideo, int $updateTime): void;
}
