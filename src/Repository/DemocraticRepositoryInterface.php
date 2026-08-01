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

/**
 * Provides access to the `democratic` and `user_vote` tables
 *
 * A democratic playlist is a `tmp_playlist` of type `vote`; the votes live in `user_vote`, keyed on the
 * `tmp_playlist_data` row id rather than the object, so the same song queued twice is two votable rows.
 */
interface DemocraticRepositoryInterface
{
    /**
     * Records a vote for a queued row
     */
    public function addVote(int $rowId, ?int $userId, string $sessionId, int $date): void;

    /**
     * Drops a playlist and the `tmp_playlist` behind it
     */
    public function delete(int $democraticId): void;

    /**
     * Drops one queued row and every vote for it
     */
    public function deleteRow(int $rowId): void;

    /**
     * Drops the votes cast from sessions that are gone
     */
    public function deleteUnconnectedVotes(): void;

    /**
     * Drops one user's (or session's) vote for a row
     */
    public function deleteVote(int|string $rowId, ?int $userId, string $sessionId): void;

    /**
     * Drops every vote in one playlist
     */
    public function deleteVotesForPlaylist(int $tmpPlaylistId): void;

    /**
     * Reads the id of the playlist a user may reach, most privileged first
     */
    public function findByAccessLevel(int $accessLevel): ?int;

    /**
     * Reads a random enabled song id, for the fallback when a playlist runs dry
     */
    public function findRandomSongId(string $catalogFilter): ?int;

    /**
     * Reads the id of the `tmp_playlist_data` row holding an object in a playlist
     */
    public function findRowId(string $objectType, int $tmpPlaylistId, int $objectId): ?int;

    /**
     * Reads every playlist id, by name
     *
     * @return list<int>
     */
    public function getAllIds(): array;

    /**
     * Reads the queued rows of a playlist, most voted first
     *
     * @return list<array{object_type: string, object_id: int, id: int}>
     */
    public function getItems(int $tmpPlaylistId, ?int $limit = null): array;

    /**
     * Reads the `tmp_playlist` row belonging to a playlist
     *
     * @return array<string, mixed>
     */
    public function getTmpPlaylistRow(int $democraticId): array;

    /**
     * Counts the votes cast for one queued row
     */
    public function getVoteCount(int $rowId): int;

    /**
     * Counts the votes cast for a set of queued rows, keyed by row id
     *
     * @param list<int|string> $rowIds
     * @return array<int, int>
     */
    public function getVoteCounts(array $rowIds): array;

    /**
     * Whether a user (or session) has already voted for an object in a playlist
     */
    public function hasVoted(string $objectType, int $objectId, int $tmpPlaylistId, ?int $userId, string $sessionId): bool;

    /**
     * Creates a playlist and returns its id, or `null` when the write failed
     */
    public function insert(string $name, int $basePlaylist, int $cooldown, int $level, int $userId, int $isDefault): ?int;

    /**
     * Queues an object and returns the id of its row
     */
    public function insertRow(int $tmpPlaylistId, int $objectId, string $objectType, int $track): ?int;

    /**
     * Drops the queued rows that nobody is voting for
     */
    public function pruneTracks(): void;

    /**
     * Drops the votes whose queued row is gone
     */
    public function pruneVotes(): void;

    /**
     * Updates a playlist's settings
     */
    public function update(int $democraticId, string $name, int $basePlaylist, int $cooldown, int $isDefault, int $level): void;
}
