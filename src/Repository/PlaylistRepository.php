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

use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\playlist_object;

/**
 * Manages playlist related database access
 *
 * Tables: `playlist`, `playlist_data`
 */
final readonly class PlaylistRepository extends AbstractPlaylistObjectRepository implements PlaylistRepositoryInterface
{
    /**
     * Appends entries. Each row is [object_id, object_type, track] and replaces any entry already at
     * that position.
     *
     * @param list<array{0: int, 1: ?string, 2: int}> $rows
     */
    public function addTracks(Playlist $playlist, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $params = [];
        foreach ($rows as $row) {
            array_push($params, $playlist->getId(), $row[0], $row[1], $row[2]);
        }

        $this->connection->query(
            'REPLACE INTO `playlist_data` (`playlist`, `object_id`, `object_type`, `track`) VALUES ' . implode(', ', array_fill(0, count($rows), '(?, ?, ?, ?)')),
            $params
        );
    }

    /**
     * Removes collaborator rows whose playlist no longer exists, and dead entries from every list
     *
     * Only unprefixed keys belong to this repository, so the pattern must keep agreeing with `collaborateKey()`.
     */
    public function collectGarbage(): void
    {
        foreach (['song', 'podcast_episode', 'video'] as $objectType) {
            $this->connection->query(
                sprintf(
                    "DELETE FROM `playlist_data` USING `playlist_data` LEFT JOIN `%s` ON `%s`.`id` = `playlist_data`.`object_id` WHERE `%s`.`file` IS NULL AND `playlist_data`.`object_type`='%s';",
                    $objectType,
                    $objectType,
                    $objectType,
                    $objectType
                )
            );
        }

        $this->connection->query("DELETE FROM `playlist_data` USING `playlist_data` LEFT JOIN `live_stream` ON `live_stream`.`id` = `playlist_data`.`object_id` WHERE `live_stream`.`id` IS NULL AND `playlist_data`.`object_type`='live_stream';");
        $this->connection->query('DELETE FROM `playlist` USING `playlist` LEFT JOIN `playlist_data` ON `playlist_data`.`playlist` = `playlist`.`id` WHERE `playlist_data`.`object_id` IS NULL;');
        $this->connection->query("DELETE FROM `user_playlist_map` WHERE `playlist_id` NOT LIKE 'smart\\_%' AND `playlist_id` NOT IN (SELECT `id` FROM `playlist`);");
    }

    /**
     * Removes the playlist, its entries and the stats recorded against it
     */
    public function delete(Playlist $playlist): void
    {
        $params = [$playlist->getId()];

        $this->connection->query('DELETE FROM `playlist_data` WHERE `playlist` = ?', $params);
        $this->connection->query('DELETE FROM `playlist` WHERE `id` = ?', $params);

        foreach (['object_count', 'object_count_summary', 'object_count_archive'] as $table) {
            $this->connection->query(
                sprintf("DELETE FROM `%s` WHERE `object_type`='playlist' AND `object_id` = ?", $table),
                $params
            );
        }
    }

    /**
     * Empties the playlist
     */
    public function deleteAllTracks(Playlist $playlist): void
    {
        $this->connection->query(
            'DELETE FROM `playlist_data` WHERE `playlist_data`.`playlist` = ?',
            [$playlist->getId()]
        );
    }

    /**
     * Removes one entry by its own `playlist_data` id
     */
    public function deleteTrackById(Playlist $playlist, int $trackId): void
    {
        $this->connection->query(
            'DELETE FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`id` = ? LIMIT 1',
            [$playlist->getId(), $trackId]
        );
    }

    /**
     * Removes one entry by the position it holds in the list
     */
    public function deleteTrackByNumber(Playlist $playlist, int $track): void
    {
        $this->connection->query(
            'DELETE FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`track` = ? LIMIT 1',
            [$playlist->getId(), $track]
        );
    }

    /**
     * Removes one entry by the id of the object it points at
     */
    public function deleteTrackByObjectId(Playlist $playlist, int $objectId): void
    {
        $this->connection->query(
            'DELETE FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_id` = ? LIMIT 1',
            [$playlist->getId(), $objectId]
        );
    }

    /**
     * The highest position currently used, so appended entries carry on from there
     */
    public function getLastTrackNumber(Playlist $playlist): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT MAX(`track`) AS `track` FROM `playlist_data` WHERE `playlist` = ?',
            [$playlist->getId()]
        );
    }

    /**
     * Entry ids in their stored order, for renumbering
     *
     * @return int[]
     */
    public function getTrackIdsInOrder(Playlist $playlist): array
    {
        return $this->fetchTrackIds(
            'SELECT `id` FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? ORDER BY `track`, `id`;',
            $playlist
        );
    }

    /**
     * Entry ids sorted the way `sort_tracks()` wants them, by artist then album then track
     *
     * @return int[]
     */
    public function getTrackIdsSorted(Playlist $playlist): array
    {
        return $this->fetchTrackIds(
            'SELECT `list`.`id` FROM `playlist_data` AS `list` LEFT JOIN `song` ON `list`.`object_id` = `song`.`id` LEFT JOIN `album` ON `song`.`album` = `album`.`id` LEFT JOIN `artist` ON `album`.`album_artist` = `artist`.`id` WHERE `list`.`playlist` = ? ORDER BY `artist`.`name`, `album`.`name`, `album`.`year`, `song`.`disk`, `song`.`track`, `song`.`title`',
            $playlist
        );
    }

    /**
     * Moves every entry pointing at one object onto another
     */
    public function migrateObject(string $objectType, int $oldObjectId, int $newObjectId): void
    {
        $this->connection->query(
            'UPDATE `playlist_data` SET `object_id` = ? WHERE `object_id` = ? AND `object_type` = ?;',
            [$newObjectId, $oldObjectId, $objectType]
        );
    }

    /**
     * Puts an object at a position, displacing whatever held it
     */
    public function replaceTrackAtNumber(Playlist $playlist, int $objectId, int $track): void
    {
        $this->connection->query(
            'DELETE FROM `playlist_data` WHERE `playlist` = ? AND `track` = ?;',
            [$playlist->getId(), $track]
        );
        $this->connection->query(
            'INSERT INTO `playlist_data` (`playlist`, `object_type`, `object_id`, `track`) VALUES (?, ?, ?, ?);',
            [$playlist->getId(), 'song', $objectId, $track]
        );
    }

    /**
     * Stores the position of one entry
     */
    public function setTrackNumber(int $trackId, int $track): void
    {
        $this->connection->query(
            'UPDATE `playlist_data` SET `track` = ? WHERE `id` = ?',
            [$track, $trackId]
        );
    }

    /**
     * Writes new positions for a set of entries in one statement
     *
     * @param array<int, int> $tracksById
     */
    public function setTrackNumbers(array $tracksById): void
    {
        if ($tracksById === []) {
            return;
        }

        $params = [];
        foreach ($tracksById as $trackId => $track) {
            array_push($params, $trackId, $track);
        }

        $this->connection->query(
            'INSERT INTO `playlist_data` (`id`, `track`) VALUES ' . implode(', ', array_fill(0, count($tracksById), '(?, ?)')) . ' ON DUPLICATE KEY UPDATE `track`=VALUES(`track`)',
            $params
        );
    }

    protected function collaborateKey(playlist_object $item): int
    {
        return $item->getId();
    }

    protected function tableName(): string
    {
        return 'playlist';
    }

    /**
     * @return int[]
     */
    private function fetchTrackIds(string $sql, Playlist $playlist): array
    {
        $result = $this->connection->query($sql, [$playlist->getId()]);

        $results = [];
        while ($rowId = $result->fetchColumn()) {
            $results[] = (int) $rowId;
        }

        return $results;
    }
}
