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
 * Provides access to the `song_preview` table
 */
interface SongPreviewRepositoryInterface
{
    /**
     * Drops the previews whose session is gone
     */
    public function collectGarbage(): void;

    /**
     * Reads the MusicBrainz id of an artist, used to fill a preview that has none of its own
     */
    public function findArtistMbid(int $artistId): ?string;

    /**
     * Reads the ids of the previews a session holds for one release
     *
     * @return list<int>
     */
    public function findIdsBySession(string $sessionId, string $albumMbid): array;

    /**
     * Reads one preview row
     *
     * @return array<string, mixed>
     */
    public function getRow(int $previewId): array;

    /**
     * Inserts a preview and returns its id, or `null` when the write failed
     *
     * @param array<string, mixed> $data
     */
    public function insert(array $data): ?int;
}
