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

namespace Ampache\Module\Api;

use Ampache\Repository\Model\Playlist;
use Ampache\Repository\PlaylistRepositoryInterface;
use Ampache\Repository\SearchRepositoryInterface;

/**
 * How many playlists one answer carries when the caller asked for their songs.
 *
 * `include=songs` reports memberships rather than songs: a track in twelve playlists is sent twelve times,
 * and a smart playlist is expanded in full wherever it appears. A library of 61 752 songs answered 1 344 993
 * rows that way, which is why a page counted in playlists bounds nothing -- one list can carry them all.
 */
final readonly class PlaylistTrackBudget
{
    /**
     * Rows a single answer will carry, measured against a 256M server: a page of 400 000 peaked at 254M and
     * one of 600 000 died, so this keeps about half the headroom.
     */
    private const int TRACK_BUDGET = 200000;

    public function __construct(
        private PlaylistRepositoryInterface $playlistRepository,
        private SearchRepositoryInterface $searchRepository,
    ) {}

    /**
     * The limit to answer with, never above the one the caller asked for.
     *
     * @param array<int|string> $results playlist ids, searches prefixed with `smart_`
     */
    public function limitFor(array $results, int $offset, int $limit): int
    {
        $page = array_slice($results, $offset, ($limit > 0) ? $limit : null);
        if ($page === []) {
            return $limit;
        }

        $counts = $this->trackCounts($page);
        $spent  = 0;
        foreach ($page as $rank => $id) {
            $cost = $counts[(string) $id] ?? 0;
            // the first one always goes out, or a list longer than the budget would answer an empty page for ever
            if ($rank > 0 && $spent + $cost > self::TRACK_BUDGET) {
                return $rank;
            }

            $spent += $cost;
        }

        return count($page);
    }

    /**
     * The track count each one last reported, which is maintained for both kinds and costs two queries.
     *
     * @param array<int|string> $ids
     * @return array<string, int>
     */
    private function trackCounts(array $ids): array
    {
        $split = Playlist::split_mixed_ids($ids);

        $counts = [];
        foreach ($this->playlistRepository->getRowsByIds($split['playlist']) as $row) {
            $counts[(string) $row['id']] = (int) ($row['last_count'] ?? 0);
        }

        foreach ($this->searchRepository->getRowsByIds($split['search']) as $row) {
            $counts['smart_' . $row['id']] = (int) ($row['last_count'] ?? 0);
        }

        return $counts;
    }
}
