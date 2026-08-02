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

use Ampache\Module\Catalog\CountableTableEnum;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Module\Database\Exception\InsertIdInvalidException;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\playlist_object;
use PDO;

/**
 * Manages playlist related database access
 *
 * Tables: `playlist`, `playlist_data`
 */
final readonly class PlaylistRepository extends AbstractPlaylistObjectRepository implements PlaylistRepositoryInterface
{
    /** @var list<string> the media types carrying a catalog, so the catalog filter can reach them */
    private const array CATALOG_TYPES = [
        'live_stream',
        'podcast_episode',
        'song',
        'video',
    ];
    /** @var list<string> the types `playlist_data` may point at, and the tables they are counted from */
    private const array MEDIA_TYPES = [
        'broadcast',
        'democratic',
        'live_stream',
        'podcast_episode',
        'song',
        'song_preview',
        'video',
    ];
    /** @var list<string> the media types whose table carries a `time` column; a live stream never ends, so it has none */
    private const array TIMED_TYPES = [
        'podcast_episode',
        'song',
        'video',
    ];

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

        $this->catalogCounter->count(CountableTableEnum::PLAYLIST);
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
     * Reads the id of a user's playlist with this exact name and type, or `null` when they have none
     */
    public function findIdByName(string $name, int $userId, string $type): ?int
    {
        $playlistId = $this->connection->fetchOne(
            'SELECT `id` FROM `playlist` WHERE `name` = ? AND `user` = ? AND `type` = ?',
            [$name, $userId, $type]
        );

        return ($playlistId === false)
            ? null
            : (int) $playlistId;
    }

    /**
     * Reads the ids a user may see, optionally narrowed by name and by the names they hide
     *
     * @return list<int>
     */
    public function findIds(
        int $userId,
        bool $isAdmin,
        bool $includePublic,
        string $playlistName,
        bool $like,
        ?string $hiddenPrefix,
    ): array {
        $sql    = 'SELECT `id` FROM `playlist` ';
        $params = [];
        $join   = 'WHERE';

        if (!$isAdmin) {
            $sql .= ($includePublic)
                ? $join . " (`user` = ? OR `type` = 'public') "
                : $join . ' (`user` = ?) ';
            $params[] = $userId;
            $join     = 'AND';
        }

        if ($playlistName !== '') {
            // the name is a value, so it is bound rather than pasted into the statement
            $sql .= ($like)
                ? $join . ' `name` LIKE ? '
                : $join . ' `name` = ? ';
            $params[] = ($like) ? '%' . $playlistName . '%' : $playlistName;
            $join     = 'AND';
        }

        if ($hiddenPrefix !== null && $hiddenPrefix !== '') {
            $sql .= $join . ' `name` NOT LIKE ? ';
            $params[] = $hiddenPrefix . '%';
        }

        $result = $this->connection->query($sql . 'ORDER BY `name`', $params);

        $playlistIds = [];
        while ($playlistId = $result->fetchColumn()) {
            $playlistIds[] = (int) $playlistId;
        }

        return $playlistIds;
    }

    /**
     * Reads the id and display name of every playlist a user may see, keyed by id
     *
     * Someone else's public playlist carries its owner's name, so the two are told apart in a list.
     *
     * @return array<int, string>
     */
    public function findNames(int $userId, bool $isAdmin): array
    {
        $sql    = "SELECT `id`, IF(`user` = ?, `name`, CONCAT(`name`, ' (', `username`, ')')) AS `name` FROM `playlist` ";
        $params = [$userId];

        if (!$isAdmin) {
            $sql .= "WHERE (`user` = ? OR `type` = 'public') ";
            $params[] = $userId;
        }

        $result = $this->connection->query($sql . 'ORDER BY `name`', $params);

        $names = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $names[(int) $row['id']] = (string) $row['name'];
        }

        return $names;
    }

    /**
     * Reads the saved smartlists a user can reach, as id => name
     *
     * @return array<int, string>
     */
    public function findSearchNames(int $userId, bool $ownedOnly): array
    {
        $sql = ($ownedOnly)
            ? 'SELECT `id`, `name` FROM `search` WHERE `user` = ?'
            : "SELECT `id`, `name` FROM `search` WHERE (`type`='public' OR `user` = ?)";

        $result = $this->connection->query($sql, [$userId]);

        $names = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $names[(int) $row['id']] = (string) $row['name'];
        }

        return $names;
    }

    /**
     * Reads the playlists holding media of one catalog, optionally only the ones with no original-size art
     *
     * @return list<int>
     */
    public function getIdsByCatalog(int $catalogId, bool $missingArtOnly = false): array
    {
        $sql = ($missingArtOnly)
            ? "SELECT DISTINCT `playlist_data`.`playlist` FROM `playlist_data` LEFT JOIN `image` ON `playlist_data`.`playlist` = `image`.`object_id` AND `image`.`object_type` = 'playlist' AND `image`.`size` = 'original' LEFT JOIN `song` ON `song`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type` = 'song' LEFT JOIN `live_stream` ON `live_stream`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type` = 'live_stream' LEFT JOIN `podcast_episode` ON `podcast_episode`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type` = 'podcast_episode' LEFT JOIN `video` ON `video`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type` = 'video' WHERE (`song`.`catalog` = ? OR `live_stream`.`catalog` = ? OR `podcast_episode`.`catalog` = ? OR `video`.`catalog` = ?) AND `image`.`object_id` IS NULL;"
            : "SELECT DISTINCT `playlist_data`.`playlist` FROM `playlist_data` LEFT JOIN `song` ON `song`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type` = 'song' LEFT JOIN `live_stream` ON `live_stream`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type` = 'live_stream' LEFT JOIN `podcast_episode` ON `podcast_episode`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type` = 'podcast_episode' LEFT JOIN `video` ON `video`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type` = 'video' WHERE `song`.`catalog` = ? OR `live_stream`.`catalog` = ? OR `podcast_episode`.`catalog` = ? OR `video`.`catalog` = ?;";

        $result = $this->connection->query($sql, [$catalogId, $catalogId, $catalogId, $catalogId]);

        $playlistIds = [];
        while ($playlistId = $result->fetchColumn()) {
            $playlistIds[] = (int) $playlistId;
        }

        return $playlistIds;
    }

    /**
     * Reads the entries of one media type in a playlist, in track order or at random
     *
     * @return list<array<string, mixed>>
     */
    public function getItemsOfType(
        int $playlistId,
        string $objectType,
        int $userId,
        bool $catalogFilter,
        bool $withTime,
        bool $random,
        string $limit = '',
    ): array {
        $params = [$playlistId];

        if (in_array($objectType, self::CATALOG_TYPES, true)) {
            $time = ($withTime && in_array($objectType, self::TIMED_TYPES, true))
                ? sprintf('`%s`.`time`', $objectType)
                : '0 AS `time`';
            $sql = sprintf(
                "SELECT `playlist_data`.`id`, `object_id`, `object_type`, `playlist_data`.`track`, %s FROM `playlist_data` INNER JOIN `%s` ON `playlist_data`.`object_id` = `%s`.`id` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_type` = '%s' AND `object_id` IS NOT NULL ",
                $time,
                $objectType,
                $objectType,
                $objectType
            );

            if ($catalogFilter) {
                $sql .= sprintf(
                    "AND `%s`.`catalog` IN (%s) ",
                    $objectType,
                    $this->getCatalogFilterSql($userId < 0)
                );
                if ($userId >= 0) {
                    $params[] = $userId;
                }
            }
        } else {
            $sql      = 'SELECT `id`, `object_id`, `object_type`, `track`, 0 AS `time` FROM `playlist_data` WHERE `playlist` = ? AND `object_type` = ? ';
            $params[] = $objectType;
        }

        $sql .= ($random)
            ? 'ORDER BY RAND()'
            : 'ORDER BY `playlist_data`.`track`';

        if ($random && $limit !== '') {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        $result = $this->connection->query($sql, $params);

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
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
     * Counts the entries of a playlist, honouring the catalog filter the user browses under
     */
    public function getMediaCount(int $playlistId, string $type, int $userId, bool $catalogFilter): int
    {
        $params    = [$playlistId];
        $allMedia  = ($type === '' || !in_array($type, self::MEDIA_TYPES, true));
        $isSystem  = ($userId < 0);

        if ($allMedia) {
            // empty or invalid type so check for all media types
            $sql = 'SELECT COUNT(`playlist_data`.`id`) AS `list_count` FROM `playlist_data` ';
            foreach (self::MEDIA_TYPES as $mediaType) {
                $sql .= sprintf(
                    "LEFT JOIN `%s` ON `playlist_data`.`object_id` = `%s`.`id` AND `playlist_data`.`object_type` = '%s' ",
                    $mediaType,
                    $mediaType,
                    $mediaType
                );
            }

            $sql .= 'WHERE `playlist_data`.`playlist` = ?  AND `playlist_data`.`object_type` IS NOT NULL ';
        } else {
            // check for a specific type of object
            $sql = sprintf(
                "SELECT COUNT(`playlist_data`.`id`) AS `list_count` FROM `playlist_data` INNER JOIN `%s` ON `playlist_data`.`object_id` = `%s`.`id` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_type` = '%s' AND `object_id` IS NOT NULL ",
                $type,
                $type,
                $type
            );
        }

        if ($catalogFilter) {
            if ($allMedia) {
                $clauses = [];
                foreach (self::CATALOG_TYPES as $mediaType) {
                    $clauses[] = sprintf(
                        "`playlist_data`.`object_type` = '%s' AND `%s`.`catalog` IN (%s)",
                        $mediaType,
                        $mediaType,
                        $this->getCatalogFilterSql($isSystem)
                    );
                    if (!$isSystem) {
                        $params[] = $userId;
                    }
                }

                $sql .= 'AND (' . implode(' OR ', $clauses) . ') ';
            } else {
                $sql .= sprintf(
                    "AND `playlist_data`.`object_type` = '%s' AND `%s`.`catalog` IN (%s) ",
                    $type,
                    $type,
                    $this->getCatalogFilterSql($isSystem)
                );
                if (!$isSystem) {
                    $params[] = $userId;
                }
            }
        }

        return (int) $this->connection->fetchOne($sql . 'GROUP BY `playlist_data`.`playlist`;', $params);
    }

    /**
     * Reads the media types a playlist holds
     *
     * @return list<string>
     */
    public function getObjectTypes(int $playlistId): array
    {
        $result = $this->connection->query(
            'SELECT DISTINCT `object_type` FROM `playlist_data` WHERE `playlist` = ?',
            [$playlistId]
        );

        $types = [];
        while ($type = $result->fetchColumn()) {
            $types[] = (string) $type;
        }

        return $types;
    }

    /**
     * Reads whole playlist rows for the in-request cache
     *
     * @param list<int|string> $playlistIds
     * @return list<array<string, mixed>>
     */
    public function getRowsByIds(array $playlistIds): array
    {
        if ($playlistIds === []) {
            return [];
        }

        $result = $this->connection->query(
            sprintf(
                'SELECT * FROM `playlist` WHERE `id` IN (%s)',
                implode(',', array_map(intval(...), $playlistIds))
            )
        );

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Sums the running time of a set of songs
     *
     * @param list<int> $songIds
     */
    public function getTotalDuration(array $songIds): int
    {
        if ($songIds === []) {
            return 0;
        }

        return (int) $this->connection->fetchOne(
            sprintf('SELECT SUM(`time`) FROM `song` WHERE `id` IN (%s)', implode(',', $songIds))
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
     * Whether a playlist holds an object, a track position, or that object at or before that position
     */
    public function hasItem(int $playlistId, ?int $objectId, ?int $track, string $objectType): bool
    {
        if (!$objectId && $track !== null && $track > 0) {
            // searching by track
            $sql    = 'SELECT `track` FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_type` = ? AND `playlist_data`.`track` = ? LIMIT 1';
            $params = [$playlistId, $objectType, $track];
        } elseif ($track !== null && $track > 0) {
            $sql    = 'SELECT `object_id` FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_type` = ? AND `track` <= ? AND `playlist_data`.`object_id` = ? LIMIT 1';
            $params = [$playlistId, $objectType, $track, $objectId];
        } else {
            // Search object and optionally check by track
            $sql    = 'SELECT `object_id` FROM `playlist_data` WHERE `playlist_data`.`playlist` = ? AND `playlist_data`.`object_type` = ? AND `playlist_data`.`object_id` = ? LIMIT 1';
            $params = [$playlistId, $objectType, $objectId];
        }

        return $this->connection->fetchOne($sql, $params) !== false;
    }

    /**
     * Inserts a playlist and returns its id, or `null` when the write failed
     */
    public function insert(string $name, int $userId, string $username, string $type, int $date): ?int
    {
        try {
            $this->connection->query(
                'INSERT INTO `playlist` (`name`, `user`, `username`, `type`, `date`, `last_update`) VALUES (?, ?, ?, ?, ?, ?)',
                [$name, $userId, $username, $type, $date, $date]
            );

            return $this->connection->getLastInsertedId();
        } catch (DatabaseException|InsertIdInvalidException) {
            return null;
        }
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

    /**
     * The catalog clause a browse runs under: the system group when nobody is logged in, the user's otherwise
     */
    private function getCatalogFilterSql(bool $isSystem): string
    {
        return ($isSystem)
            ? 'SELECT `catalog_id` FROM `catalog_filter_group_map` WHERE `catalog_filter_group_map`.`group_id` = 0 AND `catalog_filter_group_map`.`enabled`=1'
            : 'SELECT `catalog_id` FROM `catalog_filter_group_map` INNER JOIN `user` ON `user`.`catalog_filter_group` = `catalog_filter_group_map`.`group_id` WHERE `user`.`id` = ? AND `catalog_filter_group_map`.`enabled`=1';
    }
}
