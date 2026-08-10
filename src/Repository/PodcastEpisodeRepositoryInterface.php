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
 */

namespace Ampache\Repository;

use Ampache\Module\Podcast\PodcastEpisodeStateEnum;
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Traversable;

/**
 * Manages database access related to podcast-episodes
 *
 * Tables: `podcast_episode`
 */
interface PodcastEpisodeRepositoryInterface
{
    /**
     * Cleans up orphaned episodes
     */
    public function collectGarbage(): void;

    /**
     * Counts the episodes of one podcast still held by a catalog, which decides whether the podcast moves too
     */
    public function countByPodcastAndCatalog(int $podcastId, int $catalogId): int;

    /**
     * Records a set of episodes in the `deleted_podcast_episode` archive and removes them
     *
     * @param list<int> $episodeIds
     */
    public function deleteByIdsWithArchive(array $episodeIds): void;

    /**
     * Deletes a podcast-episode
     *
     * Before deleting the episode, a backup of the episodes meta-data is created
     */
    public function deleteEpisode(Podcast_Episode $episode): void;

    /**
     * Finds a single item by its id
     */
    public function findById(int $itemId): ?Podcast_Episode;

    /**
     * Reads the id of the episode holding this file
     */
    public function findIdByFile(string $file): ?int;

    /**
     * Returns the calculated count of available episodes for the given podcast
     */
    public function getEpisodeCount(Podcast $podcast): int;

    /**
     * Returns all episode-ids for the given podcast
     *
     * @param null|PodcastEpisodeStateEnum $stateFilter Return only items with this state
     * @return list<int>
     */
    public function getEpisodes(Podcast $podcast, ?PodcastEpisodeStateEnum $stateFilter = null): array;

    /**
     * Returns all podcast episodes which are eligible for deletion
     *
     * If enabled, this will return all episodes of the podcast which are above the keep-limit
     *
     * @return Traversable<Podcast_Episode>
     */
    public function getEpisodesEligibleForDeletion(Podcast $podcast): Traversable;

    /**
     * Returns all podcast episodes which are eligible for download
     *
     * @param null|positive-int $downloadLimit
     * @return Traversable<Podcast_Episode>
     */
    public function getEpisodesEligibleForDownload(Podcast $podcast, ?int $downloadLimit = null): Traversable;

    /**
     * Reads every episode file of one catalog keyed by episode id, for the scanner's in-process cache
     *
     * @return array<int, string>
     */
    public function getFilesByCatalog(int $catalogId, int $limit = 0, int $offset = 0): array;

    /**
     * Reads the episodes whose file sits under a base folder path
     *
     * @return list<int>
     */
    public function getIdsByFilePrefix(string $folderPath): array;

    /**
     * Reads the most recently published episodes of one catalog, newest first
     *
     * @return list<int>
     */
    public function getNewestIdsByCatalog(int $catalogId, int $count): array;

    /**
     * Returns a number of random, completed podcast episodes from the whole library
     *
     * @return list<int>
     */
    public function getRandom(int $userId, ?int $count = 1): array;

    /**
     * Returns a number of random, completed episodes from a single podcast
     *
     * @return list<int>
     */
    public function getRandomByPodcast(int $podcastId, int $userId, ?int $count = 1): array;

    /**
     * Reads a page of the episodes a verify pass walks, newest path first
     *
     * @return list<array{id: int, file: string, min_update_time: int}>
     */
    public function getVerifyRowsByCatalog(int $catalogId, int $limit, bool $onlyStale): array;

    /**
     * Stores the path the episode was downloaded to
     */
    public function setFile(int $episodeId, string $file): void;

    /**
     * Moves a podcast episode to another catalog and to the file it now lives in
     */
    public function setFileAndCatalog(int $objectId, string $file, int $catalogId): bool;

    /**
     * Flags the episode as played
     */
    public function setPlayed(int $episodeId): void;

    /**
     * Stamps the episode as updated
     */
    public function setUpdateTime(int $episodeId, int $time): void;

    /**
     * Writes the editable properties of an existing episode
     */
    public function update(Podcast_Episode $episode): void;

    /**
     * Rebuilds every episode's play and skip totals from `object_count`, and the played flag that follows them
     */
    public function updateAllCounts(): void;

    /**
     * Writes the description an episode's feed item now carries
     *
     * The caller decides whether it changed, so a sync over an unchanged feed never gets here.
     */
    public function updateDescription(int $episodeId, string $description): void;

    /**
     * Writes back what reading the downloaded file told us about it, and marks the episode complete
     *
     * @param array<string, mixed> $values
     */
    public function updateFromTags(int $episodeId, string $file, array $values, int $updateTime): void;

    public function updateState(
        Podcast_Episode $episode,
        PodcastEpisodeStateEnum $state,
    ): void;
}
