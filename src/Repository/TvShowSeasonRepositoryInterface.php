<?php

namespace Ampache\Repository;

interface TvShowSeasonRepositoryInterface
{
    public function collectGarbage(): void;

    /**
     * gets all episodes for this tv show season
     * @return int[]
     */
    public function getEpisodeIds(
        int $tvShowId
    ): array;

    /**
     * @return array{episode_count?: int, catalog_id?: int}
     */
    public function getExtraInfo(int $tvShowId): array;

    public function setTvShow(int $tvShowId, int $seasonId): void;

    /**
     * Performs a lookup for a certain season in a tvshow
     */
    public function findByTvShowAndSeasonNumber(
        int $tvShowId,
        int $seasonNumber
    ): ?int;

    /**
     * Adds a season to a tvshow and returns the id of the new season
     */
    public function addSeason(
        int $tvShowId,
        int $seasonNumber
    ): int;

    public function delete(int $seasonId): void;

    public function update(
        int $tvShowId,
        int $seasonNumber,
        int $seasonId
    ): void;

    /**
     * @return int[]
     */
    public function getSeasonIdsByTvShowId(int $tvShowId): array;
}
