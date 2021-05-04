<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\PodcastEpisodeInterface;
use Ampache\Repository\Model\PodcastInterface;
use Generator;

interface PodcastEpisodeRepositoryInterface
{
    /**
     * This returns an array of ids of latest podcast episodes in this catalog
     *
     * @return Generator<PodcastEpisodeInterface>
     */
    public function getNewestPodcastEpisodes(
        int $catalogId,
        int $count
    ): Generator;

    /**
     * @return Generator<Podcast_Episode>
     */
    public function getDownloadableEpisodes(
        PodcastInterface $podcast,
        int $limit
    ): Generator;

    /**
     * @return Generator<Podcast_Episode>
     */
    public function getDeletableEpisodes(
        PodcastInterface $podcast,
        int $limit
    ): Generator;

    public function create(
        PodcastInterface $podcast,
        string $title,
        string $guid,
        string $source,
        string $website,
        string $description,
        string $author,
        string $category,
        int $time,
        int $publicationDate
    ): bool;

    /**
     * Gets all episodes for the podcast
     *
     * @return int[]
     */
    public function getEpisodeIds(
        PodcastInterface $podcast,
        ?string $state_filter = null
    ): array;

    public function remove(PodcastEpisodeInterface $podcastEpisode): bool;

    public function changeState(
        PodcastEpisodeInterface $podcastEpisode,
        string $state
    ): void;

    /**
     * Sets the vital meta informations after the episode has been downloaded
     */
    public function updateDownloadState(
        PodcastEpisodeInterface $podcastEpisode,
        string $filePath,
        int $size,
        int $duration,
        ?int $bitrate,
        ?int $frequency,
        ?string $mode
    ): void;

    /**
     * Cleans up the podcast_episode table
     */
    public function collectGarbage(): void;

    /**
     * Returns the amount of available episodes for a certain podcast
     */
    public function getEpisodeCount(PodcastInterface $podcast): int;

    public function findById(
        int $id
    ): ?PodcastEpisodeInterface;

    public function update(
        PodcastEpisodeInterface $podcastEpisode,
        ?string $title,
        ?string $website,
        ?string $description,
        ?string $author,
        ?string $category
    ): void;

    /**
     * Sets the played state for the episode
     */
    public function setPlayed(PodcastEpisodeInterface $episode): void;
}
