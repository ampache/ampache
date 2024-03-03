<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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
     * Returns all episode-ids for the given podcast
     *
     * @param null|PodcastEpisodeStateEnum $stateFilter Return only items with this state
     *
     * @return list<int>
     */
    public function getEpisodes(Podcast $podcast, ?PodcastEpisodeStateEnum $stateFilter = null): array;

    /**
     * Deletes a podcast-episode
     *
     * Before deleting the episode, a backup of the episodes meta-data is created
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
     * @param null|positive-int $downloadLimit
     *
     * @return Traversable<Podcast_Episode>
     */
    public function getEpisodesEligibleForDownload(Podcast $podcast, ?int $downloadLimit): Traversable;

    /**
     * Returns the calculated count of available episodes for the given podcast
     */
    public function getEpisodeCount(Podcast $podcast): int;

    /**
     * Updates the state of an episode
     *
     * @todo replace state by enum after switching to php 8
     */
    public function updateState(
        Podcast_Episode $episode,
        string $state
    ): void;

    /**
     * Cleans up orphaned episodes
     */
    public function collectGarbage(): void;
}
