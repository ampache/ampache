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

use Ampache\Config\AmpConfig;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\ArtistFieldEnum;
use Ampache\Repository\Model\Catalog;
use PDO;

final readonly class ArtistRepository implements ArtistRepositoryInterface
{
    public function __construct(private DatabaseConnectionInterface $connection) {}

    /**
     * Maps an artist onto a song or album; duplicates are ignored so the scanner can call it unconditionally
     */
    public function addArtistMap(int $artistId, string $objectType, int $objectId): void
    {
        debug_event(self::class, 'addArtistMap artist_id {' . $artistId . '} ' . $objectType . ' {' . $objectId . '}', 5);

        $this->connection->query(
            'INSERT IGNORE INTO `artist_map` (`artist_id`, `object_type`, `object_id`) VALUES (?, ?, ?);',
            [$artistId, $objectType, $objectId]
        );
    }

    /**
     * This cleans out unused artists
     */
    public function collectGarbage(): void
    {
        debug_event(self::class, 'collectGarbage', 5);
        $queries = [
            ['DELETE FROM `artist_map` WHERE `artist_map`.`object_type` = ? AND `artist_map`.`object_id` IN (SELECT `id` FROM `album` WHERE `album_artist` IS NULL);', ['album']],
            ['DELETE FROM `artist_map` WHERE `artist_map`.`object_type` = ? AND `artist_map`.`object_id` NOT IN (SELECT `id` FROM `album`);', ['album']],
            ['DELETE FROM `artist_map` WHERE `artist_map`.`object_type` = ? AND `artist_map`.`object_id` NOT IN (SELECT `id` FROM `song`);', ['song']],
            ['DELETE FROM `artist_map` WHERE `artist_map`.`artist_id` NOT IN (SELECT `id` FROM `artist`);', []],
            ['DELETE FROM `artist` WHERE `id` IN (SELECT `id` FROM (SELECT `id` FROM `artist` LEFT JOIN (SELECT DISTINCT `song`.`artist` AS `artist_id` FROM `song` UNION SELECT DISTINCT `album`.`album_artist` AS `artist_id` FROM `album` UNION SELECT DISTINCT `wanted`.`artist` AS `artist_id` FROM `wanted` UNION SELECT DISTINCT `artist_id` FROM `artist_map`) AS `artist_map` ON `artist_map`.`artist_id` = `artist`.`id` WHERE `artist_map`.`artist_id` IS NULL AND `artist`.`user` IS NULL) AS `null_artist`);', []]
        ];

        foreach ($queries as $query) {
            try {
                $sql    = $query[0];
                $params = $query[1];
                $this->connection->query($sql, $params);
            } catch (DatabaseException) {
                debug_event(self::class, 'collectGarbage error', 5);
            }
        }
    }

    public function collectGarbageForArtist(int $artistId): void
    {
        if ($artistId <= 0) {
            return;
        }

        $this->connection->query(
            'DELETE FROM `artist_map` WHERE `artist_map`.`artist_id` = ?',
            [$artistId]
        );
    }

    /**
     * Inserts a new artist row and returns its id, or null when the write failed
     */
    public function create(string $name, ?string $prefix, ?string $mbid, ?int $userId): ?int
    {
        try {
            $this->connection->query(
                'INSERT INTO `artist` (`name`, `prefix`, `mbid`, `user`) VALUES(?, ?, ?, ?)',
                [$name, $prefix, $mbid, $userId]
            );
        } catch (DatabaseException) {
            // the caller reads null as "no artist" and gives up, which is what the old falsy `Dba::write()` gave it
            return null;
        }

        return $this->connection->getLastInsertedId();
    }

    /**
     * Deletes the artist entry
     */
    public function delete(
        Artist $artist,
    ): void {
        $this->connection->query(
            'DELETE FROM `artist` WHERE `id` = ?',
            [$artist->getId()]
        );
    }

    /**
     * This finds an artist based on its name
     */
    public function findByName(string $name): ?Artist
    {
        $rowId = $this->connection->fetchOne(
            "SELECT `id` FROM `artist` WHERE `name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) = ? ",
            [$name, $name]
        );

        if ($rowId === false) {
            return null;
        }

        return new Artist((int) $rowId);
    }

    /**
     * Finds the single artist carrying this MusicBrainz id
     */
    public function findIdByMbid(string $mbid): ?int
    {
        $artistId = $this->connection->fetchOne('SELECT `id` FROM `artist` WHERE `mbid` = ?', [$mbid]);

        return ($artistId === false)
            ? null
            : (int) $artistId;
    }

    /**
     * Finds an artist by either form of its name, restricted to rows that do or do not already carry an mbid
     */
    public function findIdByName(string $name, string $fullName, bool $withMbid): ?int
    {
        $sql = ($withMbid)
            ? "SELECT `id` FROM `artist` WHERE `mbid` IS NOT NULL AND (`artist`.`name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) = ?) ORDER BY `id` LIMIT 1;"
            : "SELECT `id` FROM `artist` WHERE `mbid` IS NULL AND (`artist`.`name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) = ?) ORDER BY `id` LIMIT 1;";

        $artistId = $this->connection->fetchOne($sql, [$name, $fullName]);

        return ($artistId === false)
            ? null
            : (int) $artistId;
    }

    /**
     * Finds every mbid-less artist matching either form of the name, so a known mbid can be back-filled onto them
     *
     * @return list<int>
     */
    public function findIdsByNameWithoutMbid(string $name, string $fullName): array
    {
        $result = $this->connection->query(
            "SELECT `id` FROM `artist` WHERE (`artist`.`name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) = ?) AND `mbid` IS NULL;",
            [$name, $fullName]
        );

        $artistIds = [];
        while ($artistId = $result->fetchColumn()) {
            $artistIds[] = (int) $artistId;
        }

        return $artistIds;
    }

    /**
     * Reads the album ids credited to an artist through the album half of the artist_map
     *
     * @return list<int>
     */
    public function getAlbumIds(int $artistId): array
    {
        $result = $this->connection->query(
            "SELECT DISTINCT `album`.`id` FROM `album` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `album`.`id` WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_type` = 'album' AND `catalog`.`enabled` = '1'",
            [$artistId]
        );

        $albumIds = [];
        while ($albumId = $result->fetchColumn()) {
            $albumIds[] = (int) $albumId;
        }

        return $albumIds;
    }

    /**
     * Reads the prefixed display name of an artist, or null when there is no such row
     */
    public function getFullNameById(int $artistId): ?string
    {
        $fullName = $this->connection->fetchOne(
            "SELECT LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `f_name` FROM `artist` WHERE `id` = ?;",
            [$artistId]
        );

        return ($fullName === false)
            ? null
            : (string) $fullName;
    }

    /**
     * Reads the minimal artist detail Subsonic needs, together with its lowest-numbered catalog
     *
     * @return array{id: int, f_name: string, name: string, album_count: int, song_count: int, catalog_id: int}|null
     */
    public function getIdArray(int $artistId): ?array
    {
        $row = $this->connection->fetchRow(
            "SELECT DISTINCT `artist`.`id`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `f_name`, `artist`.`name`, `artist`.`album_count` AS `album_count`, `artist`.`song_count`, `catalog_map`.`catalog_id` FROM `artist` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = 'artist' AND `catalog_map`.`object_id` = `artist`.`id` AND `catalog_map`.`catalog_id` = (SELECT MIN(`catalog_map`.`catalog_id`) FROM `catalog_map` WHERE `catalog_map`.`object_type` = 'artist' AND `catalog_map`.`object_id` = `artist`.`id`) WHERE `artist`.`id` = ? ORDER BY `artist`.`name`",
            [$artistId]
        );

        if (!is_array($row) || $row === []) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'f_name' => (string) $row['f_name'],
            'name' => (string) $row['name'],
            'album_count' => (int) $row['album_count'],
            'song_count' => (int) $row['song_count'],
            'catalog_id' => (int) $row['catalog_id'],
        ];
    }

    /**
     * Reads the same minimal detail for every artist, optionally scoped to one catalog
     *
     * @return list<array{id: int, f_name: string, name: string, album_count: int, song_count: int, has_art: int}>
     */
    public function getIdArrayRows(?int $catalogId, bool $albumArtist): array
    {
        if ($catalogId !== null) {
            $sql = ($albumArtist)
                ? "SELECT DISTINCT `artist`.`id`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `f_name`, `artist`.`name`, `artist`.`album_count` AS `album_count`, `artist`.`song_count`, `image`.`object_id` AS `has_art` FROM `artist` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = 'album_artist' AND `catalog_map`.`object_id` = `artist`.`id` LEFT JOIN `image` ON `image`.`object_type` = 'artist' AND `image`.`object_id` = `artist`.`id` AND `image`.`size` = 'original' WHERE `catalog_map`.`catalog_id` = ? ORDER BY `artist`.`name`;"
                : "SELECT DISTINCT `artist`.`id`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `f_name`, `artist`.`name`, `artist`.`album_count` AS `album_count`, `artist`.`song_count`, `image`.`object_id` AS `has_art` FROM `artist` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = 'artist' AND `catalog_map`.`object_id` = `artist`.`id` LEFT JOIN `image` ON `image`.`object_type` = 'artist' AND `image`.`object_id` = `artist`.`id` AND `image`.`size` = 'original' WHERE `catalog_map`.`catalog_id` = ? ORDER BY `artist`.`name`;";
            $params = [$catalogId];
        } else {
            $sql = ($albumArtist)
                ? "SELECT DISTINCT `artist`.`id`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `f_name`, `artist`.`name`, `artist`.`album_count` AS `album_count`, `artist`.`song_count`, `image`.`object_id` AS `has_art` FROM `artist` LEFT JOIN `catalog_map` ON `catalog_map`.`object_type` = 'album_artist' AND `catalog_map`.`object_id` = `artist`.`id` LEFT JOIN `image` ON `image`.`object_type` = 'artist' AND `image`.`object_id` = `artist`.`id` AND `image`.`size` = 'original' WHERE `catalog_map`.`catalog_id` IS NOT NULL ORDER BY `artist`.`name`;"
                : "SELECT DISTINCT `artist`.`id`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `f_name`, `artist`.`name`, `artist`.`album_count` AS `album_count`, `artist`.`song_count`, `image`.`object_id` AS `has_art` FROM `artist` LEFT JOIN `image` ON `image`.`object_type` = 'artist' AND `image`.`object_id` = `artist`.`id` AND `image`.`size` = 'original' ORDER BY `artist`.`name`;";
            $params = [];
        }

        $result = $this->connection->query($sql, $params);

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = [
                'id' => (int) $row['id'],
                'f_name' => (string) $row['f_name'],
                'name' => (string) $row['name'],
                'album_count' => (int) $row['album_count'],
                'song_count' => (int) $row['song_count'],
                'has_art' => (int) $row['has_art'],
            ];
        }

        return $rows;
    }

    /**
     * Reads the prefix, basename and display name of an artist, or null when there is no such row
     *
     * @return array{id: string, name: string, prefix: string, basename: string}|null
     */
    public function getNameArrayById(int $artistId): ?array
    {
        $row = $this->connection->fetchRow(
            "SELECT `artist`.`id`, `artist`.`prefix`, `artist`.`name` AS `basename`, LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `name` FROM `artist` WHERE `id` = ?;",
            [$artistId]
        );

        if (!is_array($row) || $row === []) {
            return null;
        }

        return [
            'id' => (string) $row['id'],
            'name' => (string) $row['name'],
            'prefix' => (string) $row['prefix'],
            'basename' => (string) $row['basename'],
        ];
    }

    /**
     * Reads the ids of artists mapped onto one song or album
     *
     * @return list<int>
     */
    public function getObjectMap(string $objectType, int $objectId): array
    {
        $result = $this->connection->query(
            'SELECT `artist_id` AS `artist_id` FROM `artist_map` WHERE `object_type` = ? AND `object_id` = ?',
            [$objectType, $objectId]
        );

        $artistIds = [];
        while ($artistId = $result->fetchColumn()) {
            $artistIds[] = (int) $artistId;
        }

        return $artistIds;
    }

    /**
     * Reads the summed play counts of a set of artists, for the prefetch that feeds the browse display
     *
     * @param array<int|string> $artistIds
     *
     * @return list<array{artist: int, total_count: int}>
     */
    public function getPlayCountsByIds(array $artistIds): array
    {
        if ($artistIds === []) {
            return [];
        }

        $idList = implode(',', array_map('intval', $artistIds));

        $result = $this->connection->query(
            'SELECT `song`.`artist`, SUM(`song`.`total_count`) AS `total_count` FROM `song` WHERE `song`.`artist` IN (' . $idList . ') GROUP BY `song`.`artist`'
        );

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = [
                'artist' => (int) $row['artist'],
                'total_count' => (int) $row['total_count'],
            ];
        }

        return $rows;
    }

    /**
     * This returns a number of random artists.
     *
     * @return int[]
     */
    public function getRandom(
        int $userId,
        ?int $count = 1,
    ): array {
        $results = [];
        $sql     = "SELECT DISTINCT `artist_map`.`artist_id` FROM `artist_map` LEFT JOIN `song` ON `song`.`artist` = `artist_map`.`artist_id` WHERE `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $userId, true)) . ") ";

        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5 && $userId > 0) {
            $sql .= sprintf("AND `artist_map`.`artist_id` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = 'artist' AND `rating`.`rating` <= %d AND `rating`.`user` = ", $rating_filter) . $userId . ") ";
        }

        $sql .= "ORDER BY RAND() LIMIT " . $count;
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['artist_id'];
        }

        return $results;
    }

    /**
     * Reads whole artist rows for the in-process cache, in one statement instead of one per object
     *
     * @param array<int|string> $artistIds
     *
     * @return list<array<string, mixed>>
     */
    public function getRowsByIds(array $artistIds): array
    {
        if ($artistIds === []) {
            return [];
        }

        $idList = implode(',', array_map('intval', $artistIds));

        $result = $this->connection->query('SELECT * FROM `artist` WHERE `id` IN (' . $idList . ')');

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Reads the user an artist was uploaded by, or 0 when it was not an upload
     */
    public function getUploaderId(int $artistId): int
    {
        return (int) $this->connection->fetchOne('SELECT `user` FROM `artist` WHERE `id` = ?', [$artistId]);
    }

    /**
     * Moves everything credited to one artist onto another, or clears the credit when there is no replacement
     */
    public function migrate(int $oldArtistId, int $newArtistId): void
    {
        $statements = ($newArtistId > 0)
            ? [
                // migrating to a new artist, then the maps; UPDATE IGNORE leaves behind whatever would collide
                ['UPDATE `song` SET `artist` = ? WHERE `artist` = ?;', [$newArtistId, $oldArtistId]],
                ['UPDATE `album` SET `album_artist` = ? WHERE `album_artist` = ?;', [$newArtistId, $oldArtistId]],
                ['UPDATE IGNORE `artist_map` SET `artist_id` = ? WHERE `artist_id` = ?;', [$newArtistId, $oldArtistId]],
                ["UPDATE IGNORE `album_map` SET `object_id` = ? WHERE `object_id` = ? AND `object_type` = 'album';", [$newArtistId, $oldArtistId]],
            ]
            : [
                // removing the artist outright, so the credit is cleared rather than pointed somewhere else
                ['UPDATE `song` SET `artist` = NULL WHERE `artist` = ?;', [$oldArtistId]],
                ['UPDATE `album` SET `album_artist` = NULL WHERE `album_artist` = ?;', [$oldArtistId]],
            ];

        // delete the old one if it's a dupe row above
        $statements[] = ['DELETE FROM `artist_map` WHERE `artist_id` = ?;', [$oldArtistId]];
        $statements[] = ["DELETE FROM `album_map` WHERE `object_id` = ? AND `object_type` = 'album';", [$oldArtistId]];

        foreach ($statements as [$sql, $params]) {
            $this->connection->query($sql, $params);
        }
    }

    /**
     * Drops the artist_map row, undoing addArtistMap()
     */
    public function removeArtistMap(int $artistId, string $objectType, int $objectId): void
    {
        debug_event(self::class, 'removeArtistMap artist_id {' . $artistId . '} ' . $objectType . ' {' . $objectId . '}', 5);

        $this->connection->query(
            'DELETE FROM `artist_map` WHERE `artist_id` = ? AND `object_type` = ? AND `object_id` = ?;',
            [$artistId, $objectType, $objectId]
        );
    }

    /**
     * Writes the split name of an artist
     */
    public function rename(int $artistId, ?string $prefix, string $name): void
    {
        $this->connection->query(
            'UPDATE `artist` SET `prefix` = ?, `name` = ? WHERE `id` = ?',
            [$prefix, $name, $artistId]
        );
    }

    /**
     * Writes the split name onto whichever artist carries this MusicBrainz id
     */
    public function renameByMbid(string $mbid, ?string $prefix, string $name): void
    {
        $this->connection->query(
            'UPDATE `artist` SET `prefix` = ?, `name` = ? WHERE `mbid` = ?',
            [$prefix, $name, $mbid]
        );
    }

    /**
     * Writes a single artist column, bounded by the enum because the column name goes into the statement
     */
    public function setField(int $artistId, ArtistFieldEnum $field, int|string|null $value): bool
    {
        try {
            $this->connection->query(
                sprintf('UPDATE `artist` SET `%s` = ? WHERE `id` = ?', $field->value),
                [$value, $artistId]
            );
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Recomputes the cached totals on every artist
     */
    public function updateAllCounts(): void
    {
        // the whole-table twin of updateCounts(), run after a migration or a catalog sweep rather than per artist
        $statements = [
            "UPDATE `artist`, (SELECT SUM(`song`.`time`) AS `time`, `artist_map`.`artist_id` FROM `song` LEFT JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' GROUP BY `artist_map`.`artist_id`) AS `song` SET `artist`.`time` = `song`.`time` WHERE (`artist`.`time` IS NULL OR `artist`.`time` != `song`.`time`) AND `artist`.`id` = `song`.`artist_id`;",
            "UPDATE `artist`, (SELECT SUM(`total`) AS `total_count`, `object_id` FROM (SELECT COUNT(`object_count`.`object_id`) AS `total`, `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'artist' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id` UNION ALL SELECT `count` AS `total`, `object_id` FROM `object_count_summary` WHERE `object_type` = 'artist' AND `count_type` = 'stream') AS `combined_count` GROUP BY `object_id`) AS `object_count` SET `artist`.`total_count` = `object_count`.`total_count` WHERE `artist`.`total_count` != `object_count`.`total_count` AND `artist`.`id` = `object_count`.`object_id`;",
            "UPDATE `artist`, (SELECT 0 AS `total_count`, `artist`.`id` FROM `artist` WHERE `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'artist' AND `count_type` = 'stream' UNION SELECT `object_id` FROM `object_count_summary` WHERE `object_type` = 'artist' AND `count_type` = 'stream')) AS `object_count` SET `artist`.`total_count` = `object_count`.`total_count` WHERE `artist`.`total_count` != `object_count`.`total_count` AND `artist`.`id` = `object_count`.`id`;",
            "UPDATE `artist`, (SELECT COUNT(DISTINCT `album`.`id`) AS `album_count`, `artist_map`.`artist_id` FROM `artist_map` LEFT JOIN `album` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `artist_map`.`artist_id`) AS `album` SET `artist`.`album_count` = `album`.`album_count` WHERE `artist`.`album_count` != `album`.`album_count` AND `artist`.`id` = `album`.`artist_id`;",
            "UPDATE `artist`, (SELECT COUNT(DISTINCT `album_disk`.`id`) AS `album_disk_count`, `artist_map`.`artist_id` FROM `artist_map` LEFT JOIN `album` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `album`.`id` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `artist_map`.`artist_id`) AS `album_disk` SET `artist`.`album_disk_count` = `album_disk`.`album_disk_count` WHERE `artist`.`album_disk_count` != `album_disk`.`album_disk_count` AND `artist`.`id` = `album_disk`.`artist_id`;",
            "UPDATE `artist` SET `album_count` = 0, `album_disk_count` = 0 WHERE (`album_count` > 0 OR `album_disk_count` > 0) AND `id` NOT IN (SELECT `artist_id` FROM `artist_map` WHERE `object_type` = 'album');",
            "UPDATE `artist`, (SELECT COUNT(`song`.`id`) AS `song_count`, `artist_map`.`artist_id` FROM `artist_map` LEFT JOIN `song` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `artist_map`.`artist_id`) AS `song` SET `artist`.`song_count` = `song`.`song_count` WHERE `artist`.`song_count` != `song`.`song_count` AND `artist`.`id` = `song`.`artist_id`;",
            "UPDATE `artist` SET `song_count` = 0 WHERE `song_count` > 0 AND `id` NOT IN (SELECT `artist_id` FROM `artist_map` WHERE `object_type` = 'song');",
        ];

        foreach ($statements as $sql) {
            $this->runMaintenance($sql);
        }
    }

    /**
     * Recomputes the cached totals on one artist, after something mapped to it changed
     */
    public function updateCounts(int $artistId): void
    {
        // each statement names the column it maintains in its own SET clause; the paired zeroing statements exist
        // because a correlated UPDATE cannot reach an artist that has no matching artist_map row left at all
        $statements = [
            ["UPDATE `artist`, (SELECT SUM(`song`.`time`) AS `time`, `artist_map`.`artist_id` FROM `song` LEFT JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`artist_id` = ? GROUP BY `artist_map`.`artist_id`) AS `song` SET `artist`.`time` = `song`.`time` WHERE (`artist`.`time` IS NULL OR `artist`.`time` != `song`.`time`) AND `artist`.`id` = `song`.`artist_id`;", [$artistId]],
            ["UPDATE `artist`, (SELECT SUM(`total`) AS `total_count`, `object_id` FROM (SELECT COUNT(`object_count`.`object_id`) AS `total`, `object_id` FROM `object_count` WHERE `object_count`.`object_id` = ? AND `object_count`.`object_type` = 'artist' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id` UNION ALL SELECT `count` AS `total`, `object_id` FROM `object_count_summary` WHERE `object_id` = ? AND `object_type` = 'artist' AND `count_type` = 'stream') AS `combined_count` GROUP BY `object_id`) AS `object_count` SET `artist`.`total_count` = `object_count`.`total_count` WHERE `artist`.`total_count` != `object_count`.`total_count` AND `artist`.`id` = `object_count`.`object_id`;", [$artistId, $artistId]],
            ["UPDATE `artist`, (SELECT 0 AS `total_count`, `artist`.`id` FROM `artist` WHERE `id` = ? AND `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_id` = ? AND `object_type` = 'artist' AND `count_type` = 'stream' UNION SELECT `object_id` FROM `object_count_summary` WHERE `object_id` = ? AND `object_type` = 'artist' AND `count_type` = 'stream')) AS `object_count` SET `artist`.`total_count` = `object_count`.`total_count` WHERE `artist`.`total_count` != `object_count`.`total_count` AND `artist`.`id` = `object_count`.`id`;", [$artistId, $artistId, $artistId]],
            ["UPDATE `artist`, (SELECT COUNT(DISTINCT `album`.`id`) AS `album_count`, `artist_map`.`artist_id` FROM `artist_map` LEFT JOIN `album` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `artist_map`.`artist_id` = ? AND `catalog`.`enabled` = '1' GROUP BY `artist_map`.`artist_id`) AS `album` SET `artist`.`album_count` = `album`.`album_count` WHERE `artist`.`album_count` != `album`.`album_count` AND `artist`.`id` = `album`.`artist_id`;", [$artistId]],
            ["UPDATE `artist`, (SELECT COUNT(DISTINCT `album_disk`.`id`) AS `album_disk_count`, `artist_map`.`artist_id` FROM `artist_map` LEFT JOIN `album` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `album`.`id` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `artist_map`.`artist_id` = ? AND `catalog`.`enabled` = '1' GROUP BY `artist_map`.`artist_id`) AS `album_disk` SET `artist`.`album_disk_count` = `album_disk`.`album_disk_count` WHERE `artist`.`album_disk_count` != `album_disk`.`album_disk_count` AND `artist`.`id` = `album_disk`.`artist_id`;", [$artistId]],
            ["UPDATE `artist` SET `album_count` = 0, `album_disk_count` = 0 WHERE `artist`.`id` = ? AND (`album_count` > 0 OR `album_disk_count` > 0) AND `id` NOT IN (SELECT `artist_id` FROM `artist_map` WHERE `object_type` = 'album');", [$artistId]],
            ["UPDATE `artist`, (SELECT COUNT(`song`.`id`) AS `song_count`, `artist_map`.`artist_id` FROM `artist_map` LEFT JOIN `song` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `artist_map`.`artist_id` = ? AND `catalog`.`enabled` = '1' GROUP BY `artist_map`.`artist_id`) AS `song` SET `artist`.`song_count` = `song`.`song_count` WHERE `artist`.`song_count` != `song`.`song_count` AND `artist`.`id` = `song`.`artist_id`;", [$artistId]],
            ["UPDATE `artist` SET `song_count` = 0 WHERE `id` = ? AND `song_count` > 0 AND `id` NOT IN (SELECT `artist_id` FROM `artist_map` WHERE `object_type` = 'song');", [$artistId]],
        ];

        foreach ($statements as [$sql, $params]) {
            $this->runMaintenance($sql, $params);
        }
    }

    /**
     * Writes the biography fields, stamping the row as manually edited when a person supplied them
     */
    public function updateInfo(
        int $artistId,
        ?string $summary,
        ?string $placeformed,
        ?int $yearformed,
        int $lastUpdate,
        bool $manual,
        ?string $lastfmUrl = null,
    ): void {
        $this->connection->query(
            'UPDATE `artist` SET `summary` = ?, `placeformed` = ?, `yearformed` = ?, `last_update` = ?, `manual_update` = ?, `lastfm_url` = ? WHERE `id` = ?',
            [$summary, $placeformed, $yearformed, $lastUpdate, (int) $manual, $lastfmUrl, $artistId]
        );
    }

    /**
     * Runs one count-maintenance statement, where a failure must not take the rest of the sweep down with it
     *
     * @param list<mixed> $params
     */
    private function runMaintenance(string $sql, array $params = []): void
    {
        try {
            $this->connection->query($sql, $params);
        } catch (DatabaseException) {
            debug_event(self::class, 'count maintenance failed: ' . $sql, 3);
        }
    }
}
