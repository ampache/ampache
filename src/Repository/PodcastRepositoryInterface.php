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

use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\Podcast_Episode;
use Generator;
use Traversable;

interface PodcastRepositoryInterface
{
    /**
     * Retrieve all podcast objects and maintain db-order
     *
     * @return Traversable<Podcast>
     */
    public function findAll(): Traversable;

    /**
     * Searches for an existing podcast object by the feed url
     */
    public function findByFeedUrl(string $feedUrl): ?Podcast;

    /**
     * Returns all episode-ids for the given podcast
     *
     * @param string $stateFilter Return only items with this state
     *
     * @return list<int>
     */
    public function getEpisodes(Podcast $podcast, string $stateFilter = ''): array;

    /**
     * Deletes a podcast
     */
    public function delete(Podcast $podcast): void;

    /**
     * Deletes a podcast-episode
     */
    public function deleteEpisode(Podcast_Episode $episode): void;

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
     * @return Generator<Podcast_Episode>
     */
    public function getEpisodesEligibleForDownload(Podcast $podcast): Traversable;

    /**
     * Returns the calculated count of available episodes for the given podcast
     */
    public function getEpisodeCount(Podcast $podcast): int;

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
    public function getDeletedEpisodes(): array;

    /**
     * Returns a new podcast item
     */
    public function prototype(): Podcast;

    /**
     * Persists the podcast-item in the database
     *
     * If the item is new, it will be created. Otherwise, an update will happen
     *
     * @return null|non-negative-int
     */
    public function persist(Podcast $podcast): ?int;

    /**
     * Retrieve a single podcast-item by its id
     */
    public function findById(int $podcastId): ?Podcast;
}
