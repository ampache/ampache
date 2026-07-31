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
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumFieldEnum;
use Ampache\Repository\Model\Catalog;
use PDO;

final readonly class AlbumRepository implements AlbumRepositoryInterface
{
    /**
     * The optional half of an album's identity: each is matched exactly when set and must be NULL when not, so a
     * partially tagged release can never be mistaken for a fully tagged one. Order decides the bound-parameter order.
     */
    private const array IDENTITY_COLUMNS = [
        'prefix',
        'mbid',
        'mbid_group',
        'album_artist',
        'release_type',
        'release_status',
        'original_year',
        'barcode',
        'catalog_number',
        'version',
    ];

    public function __construct(private DatabaseConnectionInterface $connection) {}

    /**
     * Maps an artist onto an album, as either its album-artist (`album`) or one of its track artists (`song`)
     */
    public function addAlbumMap(int $albumId, string $objectType, int $objectId): void
    {
        debug_event(self::class, 'addAlbumMap album_id {' . $albumId . '} ' . $objectType . '_artist {' . $objectId . '}', 5);

        $this->connection->query(
            'INSERT IGNORE INTO `album_map` (`album_id`, `object_type`, `object_id`) VALUES (?, ?, ?);',
            [$albumId, $objectType, $objectId]
        );
    }

    /**
     * Cleans out unused albums
     */
    public function collectGarbage(): void
    {
        $queries = [
            "DELETE FROM `album_map` WHERE `object_type` = 'album' AND `album_id` IN (SELECT `id` FROM `album` WHERE `album_artist` IS NULL)",
            'DELETE FROM `album_map` WHERE `object_id` NOT IN (SELECT `id` FROM `artist`)',
            'DELETE FROM `album_map` WHERE `album_map`.`album_id` NOT IN (SELECT DISTINCT `song`.`album` FROM `song`)',
            "DELETE FROM `album_map` WHERE `album_map`.`album_id` IN (SELECT `album_id` FROM (SELECT DISTINCT `album_map`.`album_id` FROM `album_map` LEFT JOIN `artist_map` ON `artist_map`.`object_type` = `album_map`.`object_type` AND `artist_map`.`artist_id` = `album_map`.`object_id` AND `artist_map`.`object_id` = `album_map`.`album_id` WHERE `artist_map`.`artist_id` IS NULL AND `album_map`.`object_type` = 'album') AS `null_album`)",
            'DELETE FROM `album` WHERE `album`.`id` NOT IN (SELECT DISTINCT `song`.`album` FROM `song`) AND `album`.`id` NOT IN (SELECT DISTINCT `album_id` FROM `album_map`)',
            'DELETE FROM `album_disk` WHERE `album_id` NOT IN (SELECT `id` FROM `album`)'
        ];

        foreach ($queries as $sql) {
            try {
                $this->connection->query($sql);
            } catch (DatabaseException) {
                debug_event(self::class, 'collectGarbage error', 5);
            }
        }

        try {
            // left over garbage
            $result = $this->connection->query("SELECT `id` FROM `album_disk` WHERE CONCAT(`album_id`, '_', `disk`) NOT IN (SELECT CONCAT(`album`, '_', `disk`) AS `id` FROM `song`);");
            while ($albumDiskId = $result->fetchColumn()) {
                $this->connection->query('DELETE FROM `album_disk` WHERE `id` = ?;', [$albumDiskId], true);
            }
        } catch (DatabaseException) {
            debug_event(self::class, 'collectGarbage error', 5);
        }
    }

    public function collectGarbageForAlbums(array $albumIds): void
    {
        if ($albumIds === []) {
            return;
        }

        $idList = implode(',', array_map('intval', $albumIds));

        $this->connection->query("DELETE FROM `artist_map` WHERE `artist_map`.`object_type` = 'album' AND `artist_map`.`object_id` IN ($idList);");
        $this->connection->query("DELETE FROM `album_map` WHERE `album_map`.`album_id` IN ($idList);");
    }

    /**
     * Inserts a new album row and returns its id, or 0 when the write failed
     *
     * @param array{name: string, prefix: ?string, year: int, mbid: ?string, mbid_group: ?string, release_type: ?string, release_status: ?string, album_artist: ?int, original_year: ?string, barcode: ?string, catalog_number: ?string, version: ?string, catalog: int} $properties
     */
    public function create(array $properties, int $additionTime): int
    {
        try {
            $this->connection->query(
                'INSERT INTO `album` (`name`, `prefix`, `year`, `mbid`, `mbid_group`, `release_type`, `release_status`, `album_artist`, `original_year`, `barcode`, `catalog_number`, `version`, `catalog`, `addition_time`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $properties['name'],
                    $properties['prefix'],
                    $properties['year'],
                    $properties['mbid'],
                    $properties['mbid_group'],
                    $properties['release_type'],
                    $properties['release_status'],
                    $properties['album_artist'],
                    $properties['original_year'],
                    $properties['barcode'],
                    $properties['catalog_number'],
                    $properties['version'],
                    $properties['catalog'],
                    $additionTime,
                ]
            );
        } catch (DatabaseException) {
            // the caller reads 0 as "no album" and carries on, which is what the old falsy `Dba::write()` gave it
            return 0;
        }

        return $this->connection->getLastInsertedId();
    }

    /**
     * Deletes the album entry
     */
    public function delete(
        Album $album,
    ): void {
        $this->connection->query(
            'DELETE FROM `album` WHERE `id` = ?',
            [$album->getId()]
        );
    }

    /**
     * Finds the album that already carries exactly these properties, matching what create() would write
     *
     * @param array{name: string, prefix: ?string, year: int, mbid: ?string, mbid_group: ?string, release_type: ?string, release_status: ?string, album_artist: ?int, original_year: ?string, barcode: ?string, catalog_number: ?string, version: ?string, catalog: int} $properties
     */
    public function findByProperties(array $properties): ?int
    {
        $sql = "SELECT DISTINCT(`album`.`id`) AS `id` FROM `album` WHERE (`album`.`name` = ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) = ?) AND `album`.`year` = ? ";

        $params = [
            $properties['name'],
            $properties['name'],
            $properties['year'],
        ];

        foreach (self::IDENTITY_COLUMNS as $column) {
            if ($properties[$column]) {
                $sql .= sprintf('AND `album`.`%s` = ? ', $column);
                $params[] = $properties[$column];
            } else {
                $sql .= sprintf('AND `album`.`%s` IS NULL ', $column);
            }
        }

        $sql .= 'AND `album`.`catalog` = ?;';
        $params[] = $properties['catalog'];

        $albumId = $this->connection->fetchOne($sql, $params);

        return ($albumId === false)
            ? null
            : (int) $albumId;
    }

    /**
     * Reads the artist an album should be credited to when it has no album_artist but only one distinct song artist
     *
     * @return array{artist_name: string, artist_prefix: ?string, album_artist: int}|null
     */
    public function findSoleSongArtist(int $albumId): ?array
    {
        $row = $this->connection->fetchRow(
            'SELECT `artist`.`name` AS `artist_name`, `artist`.`prefix` AS `artist_prefix`, `song`.`artist` AS `album_artist` FROM `song` INNER JOIN `artist` ON `artist`.`id`=`song`.`artist` WHERE `song`.`album` = ? GROUP BY `song`.`album`, `artist`.`prefix`, `artist`.`name`, `song`.`artist`;',
            [$albumId]
        );

        if (!is_array($row) || $row === []) {
            return null;
        }

        return [
            'artist_name' => (string) $row['artist_name'],
            'artist_prefix' => $row['artist_prefix'],
            'album_artist' => (int) $row['album_artist'],
        ];
    }

    /**
     * Get the primary album_artist
     */
    public function getAlbumArtistId(int $albumId): ?int
    {
        $albumArtistId = $this->connection->fetchOne(
            'SELECT DISTINCT `album_artist` FROM `album` WHERE `id` = ?;',
            [$albumId]
        );

        if ($albumArtistId !== false) {
            return (int) $albumArtistId;
        }

        return null;
    }

    /**
     * gets the album ids that the artist is a part of
     * Return Album only
     *
     * @return int[]
     */
    public function getAlbumByArtist(
        int $artistId,
    ): array {
        $userId        = Core::get_global('user')?->getId();
        $catalog_where = "AND `album`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $userId, true)) . ")";

        $original_year = (AmpConfig::get('use_original_year'))
            ? "IFNULL(`album`.`original_year`, `album`.`year`)"
            : "`album`.`year`";
        $sort_type = AmpConfig::get('album_sort');
        $sql_sort  = match ($sort_type) {
            'name_asc' => "`album`.`name` ASC",
            'name_desc' => "`album`.`name` DESC",
            'year_asc' => $original_year . ' ASC',
            'year_desc' => $original_year . ' DESC',
            default => '`album`.`name`, ' . $original_year,
        };

        $sql        = sprintf('SELECT DISTINCT `album`.`id`, `album`.`release_type`, `album`.`mbid` FROM `album` LEFT JOIN `album_map` ON `album_map`.`album_id` = `album`.`id` WHERE `album_map`.`object_id` = ? %s GROUP BY `album`.`id`, `album`.`release_type`, `album`.`mbid` ORDER BY %s', $catalog_where, $sql_sort);
        $db_results = Dba::read($sql, [$artistId]);
        $results    = [];
        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * Counts the distinct artists mapped onto an album, across both the album and song mappings
     */
    public function getArtistCount(int $albumId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(DISTINCT(`object_id`)) AS `artist_count` FROM `album_map` WHERE `album_id` = ?;',
            [$albumId]
        );
    }

    /**
     * This returns the ids of artists that have songs/albums mapped
     *
     * @return int[]
     */
    public function getArtistMap(Album $album, string $objectType): array
    {
        return $this->getMappedObjectIds($album->getId(), $objectType);
    }

    /**
     * gets the album ids that the artist is a part of
     * Return Album or AlbumDisk based on album_group preference
     *
     * @return int[]|array<string, int[]>
     */
    public function getByArtist(
        int $artistId,
        ?int $catalogId = null,
        bool $group_release_type = false,
    ): array {
        $userId        = Core::get_global('user')?->getId();
        $catalog_where = "AND `album`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $userId, true)) . ")";
        if ($catalogId !== null) {
            $catalog_where = "AND `album`.`catalog` = '" . Dba::escape($catalogId) . "'";
        }

        $original_year = (AmpConfig::get('use_original_year'))
            ? "IFNULL(`album`.`original_year`, `album`.`year`)"
            : "`album`.`year`";
        $sort_type = AmpConfig::get('album_sort');
        $showAlbum = AmpConfig::get('album_group');
        $sql_sort  = match ($sort_type) {
            'name_asc' => "`album`.`name` ASC",
            'name_desc' => "`album`.`name` DESC",
            'year_asc' => $original_year . ' ASC',
            'year_desc' => $original_year . ' DESC',
            default => '`album`.`name`, ' . $original_year,
        };

        $sql = ($showAlbum)
            ? sprintf('SELECT DISTINCT `album`.`id`, `album`.`release_type`, `album`.`mbid` FROM `album` LEFT JOIN `album_map` ON `album_map`.`album_id` = `album`.`id` WHERE `album_map`.`object_id` = ? %s GROUP BY `album`.`id`, `album`.`release_type`, `album`.`mbid` ORDER BY %s', $catalog_where, $sql_sort)
            : sprintf('SELECT DISTINCT `album_disk`.`id`, `album_disk`.`disk`, `album`.`name`, `album`.`release_type`, `album`.`mbid`, %s FROM `album_disk` LEFT JOIN `album` ON `album`.`id` = `album_disk`.`album_id` LEFT JOIN `album_map` ON `album_map`.`album_id` = `album`.`id` WHERE `album_map`.`object_id` = ? %s GROUP BY `album_disk`.`id`, `album_disk`.`disk`, `album`.`name`, `album`.`release_type`, `album`.`mbid`, %s ORDER BY %s, `album_disk`.`disk`', $original_year, $catalog_where, $original_year, $sql_sort);
        $db_results = Dba::read($sql, [$artistId]);
        $results    = [];
        if ($group_release_type) {
            while ($row = Dba::fetch_assoc($db_results)) {
                // We assume undefined release type is album
                $rtype = (string) ($row['release_type'] ?? 'album');
                if (!isset($results[$rtype])) {
                    $results[$rtype] = [];
                }

                $results[$rtype][] = (int) $row['id'];

                $sort = (string) AmpConfig::get('album_release_type_sort');
                if ($sort !== '' && $sort !== '0') {
                    $results_sort = [];
                    $asort        = explode(',', $sort);

                    foreach ($asort as $rtype) {
                        if (array_key_exists($rtype, $results)) {
                            $results_sort[$rtype] = $results[$rtype];
                            unset($results[$rtype]);
                        }
                    }

                    $results = array_merge($results_sort, $results);
                }
            }
        } else {
            while ($row = Dba::fetch_assoc($db_results)) {
                $results[] = (int) $row['id'];
            }
        }

        return $results;
    }

    /**
     * gets the album id that is part of this mbid_group
     *
     * @return int[]
     */
    public function getByMbidGroup(
        string $musicBrainzId,
    ): array {
        $result = $this->connection->query(
            'SELECT `album`.`id` FROM `album` WHERE `album`.`mbid_group` = ?',
            [$musicBrainzId]
        );

        $albumIds = [];
        while ($albumId = $result->fetchColumn()) {
            $albumIds[] = (int) $albumId;
        }

        return $albumIds;
    }

    /**
     * gets the album id has the same artist and title
     *
     * @return int[]
     */
    public function getByName(
        string $name,
        int $artistId,
    ): array {
        $result = $this->connection->query(
            "SELECT `album`.`id` FROM `album` WHERE (`album`.`name` = ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) = ?) AND `album`.`album_artist` = ?",
            [$name, $name, $artistId]
        );

        $albumIds = [];
        while ($albumId = $result->fetchColumn()) {
            $albumIds[] = (int) $albumId;
        }

        return $albumIds;
    }

    /**
     * Reads the albums that carry no album_artist, which the scanner then tries to fill in
     *
     * @return list<int>
     */
    public function getIdsMissingAlbumArtist(): array
    {
        $result = $this->connection->query(
            'SELECT `id` FROM `album` WHERE `album_artist` IS NULL AND `name` != ?;',
            [T_('Unknown (Orphaned)')]
        );

        $albumIds = [];
        while ($albumId = $result->fetchColumn()) {
            $albumIds[] = (int) $albumId;
        }

        return $albumIds;
    }

    /**
     * This returns the ids of artists mapped onto an album, by album id rather than by object
     *
     * @return list<int>
     */
    public function getMappedObjectIds(int $albumId, string $objectType): array
    {
        $result = $this->connection->query(
            'SELECT `object_id` FROM `album_map` WHERE `object_type` = ? AND `album_id` = ?',
            [$objectType, $albumId]
        );

        $artistIds = [];
        while ($artistId = $result->fetchColumn()) {
            $artistIds[] = (int) $artistId;
        }

        return $artistIds;
    }

    /**
     * Get item prefix, basename and name by the album id
     *
     * @return array{prefix: string, basename: string, name: string}
     */
    public function getNames(int $albumId): array
    {
        /** @var false|array{prefix: string, basename: string, name: string} $result */
        $result = $this->connection->fetchRow(
            "SELECT `album`.`prefix`, `album`.`name` AS `basename`, LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) AS `name` FROM `album` WHERE `id` = ?",
            [$albumId]
        );

        if ($result !== false) {
            return $result;
        }

        return [
            'prefix' => '',
            'basename' => '',
            'name' => '',
        ];
    }

    /**
     * This returns a number of random albums
     *
     * @return int[] Album ids
     */
    public function getRandom(
        int $userId,
        ?int $count = 1,
        int $catalogId = 0,
    ): array {
        $results  = [];
        $catalogs = Catalog::get_catalogs('', $userId, true);
        if ($catalogId !== 0) {
            // never let a requested catalog widen what the user is allowed to see
            $catalogs = array_intersect($catalogs, [$catalogId]);
        }

        if ($catalogs === []) {
            return $results;
        }

        $sql = "SELECT DISTINCT `album`.`id` FROM `album` WHERE `album`.`catalog` IN (" . implode(',', $catalogs) . ") ";

        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5 && $userId > 0) {
            $sql .= "AND" . sprintf(
                " `album`.`id` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = 'album' AND `rating`.`rating` <=%d AND `rating`.`user` = %d) ",
                $rating_filter,
                $userId
            );
        }

        $sql .= sprintf(
            'ORDER BY RAND() LIMIT %d',
            $count
        );
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * This returns a number of random album_disks
     *
     * @return int[] AlbumDisk ids
     */
    public function getRandomAlbumDisk(
        int $userId,
        ?int $count = 1,
    ): array {
        $results = [];

        if (!$count) {
            $count = 1;
        }

        $sql = "SELECT DISTINCT `album_disk`.`id` FROM `album_disk` LEFT JOIN `album` ON `album`.`id` = `album_disk`.`album_id` WHERE `album_disk`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $userId, true)) . ") ";

        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5 && $userId > 0) {
            $sql .= sprintf(
                "AND `album`.`id` NOT IN (SELECT `object_id` FROM `rating` WHERE `rating`.`object_type` = 'album' AND `rating`.`rating` <=%d AND `rating`.`user` = %d) ",
                $rating_filter,
                $userId
            );
        }

        $sql .= sprintf(
            'ORDER BY RAND() LIMIT %d',
            $count
        );
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * gets a random order of songs from this album
     *
     * @return int[] Album ids
     */
    public function getRandomSongs(
        int $albumId,
    ): array {
        $userId = Core::get_global('user')->id ?? -1;
        $sql    = (AmpConfig::get('catalog_disable') || AmpConfig::get('catalog_filter'))
            ? "SELECT `song`.`id` FROM `song` WHERE `song`.`album` = ? AND `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $userId, true)) . ") "
            : "SELECT `song`.`id` FROM `song` WHERE `song`.`album` = ? ";

        $sql .= 'ORDER BY RAND()';
        $db_results = Dba::read($sql, [$albumId]);

        $results = [];
        while ($row = Dba::fetch_row($db_results)) {
            $results[] = (int) $row['0'];
        }

        return $results;
    }

    /**
     * gets a random order of songs from this album group
     *
     * @return int[] Album ids
     */
    public function getRandomSongsByAlbumDisk(
        int $albumDiskId,
    ): array {
        $userId = Core::get_global('user')->id ?? -1;
        $sql    = (AmpConfig::get('catalog_disable') || AmpConfig::get('catalog_filter'))
            ? "SELECT `song`.`id` FROM `song` LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk` WHERE `album_disk`.`id` = ? AND `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $userId, true)) . ") "
            : "SELECT `song`.`id` FROM `song` LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk` WHERE `album_disk`.`id` = ? ";

        $sql .= 'ORDER BY RAND()';
        $db_results = Dba::read($sql, [$albumDiskId]);

        $results = [];
        while ($row = Dba::fetch_row($db_results)) {
            $results[] = (int) $row['0'];
        }

        return $results;
    }

    /**
     * Reads whole album rows for the in-process cache, in one statement instead of one per object
     *
     * @param array<int|string> $albumIds
     * @return list<array<string, mixed>>
     */
    public function getRowsByIds(array $albumIds): array
    {
        if ($albumIds === []) {
            return [];
        }

        $idList = implode(',', array_map('intval', $albumIds));

        $result = $this->connection->query('SELECT * FROM `album` WHERE `id` IN (' . $idList . ')');

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Reads the sole artist shared by every song on an album, or null when the album has more than one
     */
    public function getSoleSongArtistId(int $albumId): ?int
    {
        $artistId = $this->connection->fetchOne(
            'SELECT MIN(`artist`) AS `artist` FROM `song` WHERE `album` = ? GROUP BY `album` HAVING COUNT(DISTINCT `artist`) = 1 LIMIT 1',
            [$albumId]
        );

        return ($artistId === false)
            ? null
            : (int) $artistId;
    }

    /**
     * Reads every song row on an album, unordered and not scoped to the user's catalogs unlike getSongs()
     *
     * @return list<int>
     */
    public function getSongIds(int $albumId): array
    {
        $sql = (AmpConfig::get('catalog_disable'))
            ? "SELECT DISTINCT `song`.`id` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `song`.`album` = ? AND `catalog`.`enabled` = '1'"
            : 'SELECT DISTINCT `song`.`id` FROM `song` WHERE `song`.`album` = ?';

        $result = $this->connection->query($sql, [$albumId]);

        $songIds = [];
        while ($songId = $result->fetchColumn()) {
            $songIds[] = (int) $songId;
        }

        return $songIds;
    }

    /**
     * gets songs from this album
     *
     * @return int[] Album ids
     */
    public function getSongs(
        int $albumId,
    ): array {
        $userId     = Core::get_global('user')?->getId();
        $sql        = "SELECT `song`.`id` FROM `song` WHERE `song`.`album` = ? AND `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $userId, true)) . ") ORDER BY `song`.`disk`, `song`.`track`, `song`.`title`";
        $db_results = Dba::read($sql, [$albumId]);

        $results = [];
        while ($row = Dba::fetch_row($db_results)) {
            $results[] = (int) $row['0'];
        }

        return $results;
    }

    /**
     * gets songs from this album_disk id
     *
     * @return int[] Song ids
     */
    public function getSongsByAlbumDisk(
        int $albumDiskId,
    ): array {
        $user   = Core::get_global('user');
        $userId = $user?->getId() ?? -1;
        $sql    = (AmpConfig::get('catalog_disable') || AmpConfig::get('catalog_filter'))
            ? "SELECT `song`.`id` FROM `song` LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk` WHERE `album_disk`.`id` = ? AND `album_disk`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $userId, true)) . ") "
            : "SELECT `song`.`id` FROM `song` LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk` WHERE `album_disk`.`id` = ? ";

        $sql .= "ORDER BY `song`.`disk`, `song`.`track`, `song`.`title`";
        $db_results = Dba::read($sql, [$albumDiskId]);

        $results = [];
        while ($row = Dba::fetch_row($db_results)) {
            $results[] = (int) $row['0'];
        }

        return $results;
    }

    /**
     * Whether the album is one of the placeholders the scanner parks songs on when their real album is unknown
     */
    public function isOrphan(int $albumId): bool
    {
        // the untranslated literal is matched too, because the placeholder was written under whatever locale ran the scan
        return $this->connection->fetchOne(
            "SELECT `id` FROM `album` WHERE `id` = ? AND (`name` = 'Unknown (Orphaned)' OR `name` = ?);",
            [$albumId, T_('Unknown (Orphaned)')]
        ) !== false;
    }

    /**
     * Drops the album_map row, undoing addAlbumMap()
     */
    public function removeAlbumMap(int $albumId, string $objectType, int $objectId): void
    {
        debug_event(self::class, 'removeAlbumMap album_id {' . $albumId . '} ' . $objectType . '_artist {' . $objectId . '}', 5);

        $this->connection->query(
            'DELETE FROM `album_map` WHERE `album_id` = ? AND `object_type` = ? AND `object_id` = ?;',
            [$albumId, $objectType, $objectId]
        );
    }

    /**
     * Drops the album_map row only once the artist_map no longer backs it, and reports whether it did
     */
    public function removeUnusedAlbumMap(int $albumId, string $objectType, int $objectId): bool
    {
        // an `album` mapping is backed by the album's own artist_map row, a `song` one by any track on the album
        $sql = ($objectType === 'album')
            ? 'SELECT `artist_id` FROM `artist_map` WHERE `artist_id` = ? AND `object_id` = ? AND `object_type` = ?;'
            : 'SELECT `artist_id` FROM `artist_map` WHERE `artist_id` = ? AND `object_id` IN (SELECT `id` FROM `song` WHERE `album` = ?) AND `object_type` = ?;';

        if ($this->connection->fetchOne($sql, [$objectId, $albumId, $objectType]) !== false) {
            return false;
        }

        $this->removeAlbumMap($albumId, $objectType, $objectId);

        return true;
    }

    /**
     * Writes a single album column, bounded by the enum because the column name goes into the statement
     */
    public function setField(int $albumId, AlbumFieldEnum $field, int|string|null $value): bool
    {
        try {
            $this->connection->query(
                sprintf('UPDATE `album` SET `%s` = ? WHERE `id` = ?', $field->value),
                [$value, $albumId]
            );
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Recomputes the cached totals on every album and disk, and backfills any album_disk the scanner missed
     */
    public function updateAllCounts(): void
    {
        // the whole-table twin of updateCounts(), plus the INSERT IGNORE that back-fills an album_disk row for
        // any (album, disk) pair the songs imply but the scanner never created
        $statements = [
            "UPDATE `album`, (SELECT SUM(`song`.`time`) AS `time`, `song`.`album` FROM `song` GROUP BY `song`.`album`) AS `song` SET `album`.`time` = `song`.`time` WHERE `album`.`id` = `song`.`album` AND ((`album`.`time` != `song`.`time`) OR (`album`.`time` IS NULL AND `song`.`time` > 0));",
            "UPDATE `album`, (SELECT MIN(`song`.`addition_time`) AS `addition_time`, `song`.`album` FROM `song` GROUP BY `song`.`album`) AS `song` SET `album`.`addition_time` = `song`.`addition_time` WHERE `album`.`addition_time` != `song`.`addition_time` AND `song`.`album` = `album`.`id`;",
            "UPDATE `album`, (SELECT SUM(`total`) AS `total_count`, `object_id` FROM (SELECT COUNT(`object_count`.`object_id`) AS `total`, `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'album' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id` UNION ALL SELECT `count` AS `total`, `object_id` FROM `object_count_summary` WHERE `object_type` = 'album' AND `count_type` = 'stream') AS `combined_count` GROUP BY `object_id`) AS `object_count` SET `album`.`total_count` = `object_count`.`total_count` WHERE `album`.`total_count` != `object_count`.`total_count` AND `album`.`id` = `object_count`.`object_id`;",
            "UPDATE `album`, (SELECT 0 AS `total_count`, `album`.`id` FROM `album` WHERE `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'album' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id` UNION SELECT `object_id` FROM `object_count_summary` WHERE `object_type` = 'album' AND `count_type` = 'stream')) AS `object_count` SET `album`.`total_count` = `object_count`.`total_count` WHERE `album`.`total_count` != `object_count`.`total_count` AND `object_count`.`id` = `album`.`id`;",
            "UPDATE `album`, (SELECT COUNT(`song`.`id`) AS `song_count`, `album` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `catalog`.`enabled` = '1' GROUP BY `album`) AS `song` SET `album`.`song_count` = `song`.`song_count` WHERE `album`.`song_count` != `song`.`song_count` AND `album`.`id` = `song`.`album`;",
            "UPDATE `album` SET `album`.`artist_count` = 0 WHERE `album_artist` IS NULL;",
            "UPDATE `album`, (SELECT COUNT(DISTINCT(`album_map`.`object_id`)) AS `artist_count`, `album_id` FROM `album_map` LEFT JOIN `album` ON `album`.`id` = `album_map`.`album_id` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `album_map`.`object_type` = 'album' AND `catalog`.`enabled` = '1' GROUP BY `album_id`) AS `album_map` SET `album`.`artist_count` = `album_map`.`artist_count` WHERE `album`.`artist_count` != `album_map`.`artist_count` AND `album`.`id` = `album_map`.`album_id` AND `album`.`album_artist` IS NOT NULL;",
            "UPDATE `album`, (SELECT COUNT(DISTINCT(`album_map`.`object_id`)) AS `artist_count`, `album_id` FROM `album_map` LEFT JOIN `album` ON `album`.`id` = `album_map`.`album_id` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `album_map`.`object_type` = 'song' AND `catalog`.`enabled` = '1' GROUP BY `album_id`) AS `album_map` SET `album`.`song_artist_count` = `album_map`.`artist_count` WHERE `album`.`song_artist_count` != `album_map`.`artist_count` AND `album`.`id` = `album_map`.`album_id`;",
            "INSERT IGNORE INTO `album_disk` (`album_id`, `disk`, `catalog`, `disksubtitle`) SELECT DISTINCT `song`.`album` AS `album_id`, `song`.`disk` AS `disk`, `song`.`catalog` AS `catalog`, NULLIF(`song_data`.`disksubtitle`, '') AS `disksubtitle` FROM `song` LEFT JOIN `song_data` ON `song_data`.`song_id` = `song`.`id`;",
            "UPDATE `album`, (SELECT COUNT(DISTINCT `album_disk`.`disk`) AS `disk_count`, `album_id` FROM `album_disk` GROUP BY `album_disk`.`album_id`) AS `album_disk` SET `album`.`disk_count` = `album_disk`.`disk_count` WHERE `album`.`disk_count` != `album_disk`.`disk_count` AND `album`.`id` = `album_disk`.`album_id`;",
            "UPDATE `album_disk`, (SELECT `disk_count`, `id` FROM `album`) AS `album` SET `album_disk`.`disk_count` = `album`.`disk_count` WHERE `album`.`disk_count` != `album_disk`.`disk_count` AND `album`.`id` = `album_disk`.`album_id`;",
            "UPDATE `album_disk`, (SELECT SUM(`time`) AS `time`, `album`, `disk` FROM `song` GROUP BY `album`, `disk`) AS `song` SET `album_disk`.`time` = `song`.`time` WHERE (`album_disk`.`time` != `song`.`time` OR `album_disk`.`time` IS NULL) AND `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk`;",
            "UPDATE `album_disk`, (SELECT COUNT(DISTINCT `id`) AS `song_count`, `album`, `disk` FROM `song` GROUP BY `album`, `disk`) AS `song` SET `album_disk`.`song_count` = `song`.`song_count` WHERE `album_disk`.`song_count` != `song`.`song_count` AND `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk`;",
            "UPDATE `album_disk`, (SELECT SUM(`song`.`total_count`) AS `total_count`, `album_disk`.`id` AS `object_id` FROM `song` LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk` GROUP BY `album_disk`.`id`) AS `object_count` SET `album_disk`.`total_count` = `object_count`.`total_count` WHERE `album_disk`.`total_count` != `object_count`.`total_count` AND `album_disk`.`id` = `object_count`.`object_id`;",
        ];

        foreach ($statements as $sql) {
            $this->runMaintenance($sql);
        }
    }

    /**
     * Recomputes the cached totals on one album and its disks, after a song on it changed
     */
    public function updateCounts(int $albumId): void
    {
        // each statement names the column it maintains in its own SET clause; they run in order because the
        // album_disk ones read `album`.`disk_count` back out after the album ones have written it
        $statements = [
            ["UPDATE `album`, (SELECT SUM(`song`.`time`) AS `time`, `song`.`album` FROM `song` WHERE `album` = ? GROUP BY `song`.`album`) AS `song` SET `album`.`time` = `song`.`time` WHERE `album`.`id` = `song`.`album` AND ((`album`.`time` != `song`.`time`) OR (`album`.`time` IS NULL AND `song`.`time` > 0));", [$albumId]],
            ["UPDATE `album`, (SELECT MIN(`song`.`addition_time`) AS `addition_time`, `song`.`album` FROM `song` WHERE `song`.`album` = ? GROUP BY `song`.`album`) AS `song` SET `album`.`addition_time` = `song`.`addition_time` WHERE `album`.`addition_time` != `song`.`addition_time` AND `song`.`album` = `album`.`id`;", [$albumId]],
            ["UPDATE `album`, (SELECT SUM(`total`) AS `total_count`, `object_id` FROM (SELECT COUNT(`object_count`.`object_id`) AS `total`, `object_id` FROM `object_count` WHERE `object_count`.`object_id` = ? AND `object_count`.`object_type` = 'album' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id` UNION ALL SELECT `count` AS `total`, `object_id` FROM `object_count_summary` WHERE `object_id` = ? AND `object_type` = 'album' AND `count_type` = 'stream') AS `combined_count` GROUP BY `object_id`) AS `object_count` SET `album`.`total_count` = `object_count`.`total_count` WHERE `album`.`total_count` != `object_count`.`total_count` AND `album`.`id` = `object_count`.`object_id`;", [$albumId, $albumId]],
            ["UPDATE `album`, (SELECT 0 AS `total_count`, `album`.`id` FROM `album` WHERE `id` = ? AND `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_id` = ? AND `object_count`.`object_type` = 'album' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id` UNION SELECT `object_id` FROM `object_count_summary` WHERE `object_id` = ? AND `object_type` = 'album' AND `count_type` = 'stream')) AS `object_count` SET `album`.`total_count` = `object_count`.`total_count` WHERE `album`.`total_count` != `object_count`.`total_count` AND `object_count`.`id` = `album`.`id`;", [$albumId, $albumId, $albumId]],
            ["UPDATE `album`, (SELECT COUNT(`song`.`id`) AS `song_count`, `album` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `catalog`.`enabled` = '1' AND `album` = ? GROUP BY `album`) AS `song` SET `album`.`song_count` = `song`.`song_count` WHERE `album`.`song_count` != `song`.`song_count` AND `album`.`id` = `song`.`album`;", [$albumId]],
            ["UPDATE `album` SET `album`.`artist_count` = 0 WHERE `album`.`id` = ? AND `album_artist` IS NULL;", [$albumId]],
            ["UPDATE `album`, (SELECT COUNT(DISTINCT(`album_map`.`object_id`)) AS `artist_count`, `album_id` FROM `album_map` LEFT JOIN `album` ON `album`.`id` = `album_map`.`album_id` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `album_map`.`object_type` = 'album' AND `catalog`.`enabled` = '1' AND `album`.`id` = ? GROUP BY `album_id`) AS `album_map` SET `album`.`artist_count` = `album_map`.`artist_count` WHERE `album`.`artist_count` != `album_map`.`artist_count` AND `album`.`id` = `album_map`.`album_id` AND `album`.`album_artist` IS NOT NULL;", [$albumId]],
            ["UPDATE `album`, (SELECT COUNT(DISTINCT(`album_map`.`object_id`)) AS `artist_count`, `album_id` FROM `album_map` LEFT JOIN `album` ON `album`.`id` = `album_map`.`album_id` LEFT JOIN `catalog` ON `catalog`.`id` = `album`.`catalog` WHERE `album_map`.`object_type` = 'song' AND `catalog`.`enabled` = '1' AND `album`.`id` = ? GROUP BY `album_id`) AS `album_map` SET `album`.`song_artist_count` = `album_map`.`artist_count` WHERE `album`.`song_artist_count` != `album_map`.`artist_count` AND `album`.`id` = `album_map`.`album_id`;", [$albumId]],
            ["UPDATE `album`, (SELECT COUNT(DISTINCT `album_disk`.`disk`) AS `disk_count`, `album_id` FROM `album_disk` WHERE `album_disk`.`album_id` = ? GROUP BY `album_disk`.`album_id`) AS `album_disk` SET `album`.`disk_count` = `album_disk`.`disk_count` WHERE `album`.`disk_count` != `album_disk`.`disk_count` AND `album`.`id` = `album_disk`.`album_id`;", [$albumId]],
            ["UPDATE `album_disk`, (SELECT `album`.`disk_count`, `id` FROM `album` WHERE `album`.`id` = ?) AS `album` SET `album_disk`.`disk_count` = `album`.`disk_count` WHERE `album`.`disk_count` != `album_disk`.`disk_count` AND `album`.`id` = `album_disk`.`album_id`;", [$albumId]],
            ["UPDATE `album_disk`, (SELECT SUM(`time`) AS `time`, `album`, `disk` FROM `song` WHERE `song`.`album` = ? GROUP BY `album`, `disk`) AS `song` SET `album_disk`.`time` = `song`.`time` WHERE (`album_disk`.`time` != `song`.`time` OR `album_disk`.`time` IS NULL) AND `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk`;", [$albumId]],
            ["UPDATE `album_disk`, (SELECT COUNT(DISTINCT `id`) AS `song_count`, `album`, `disk` FROM `song` WHERE `song`.`album` = ? GROUP BY `album`, `disk`) AS `song` SET `album_disk`.`song_count` = `song`.`song_count` WHERE `album_disk`.`song_count` != `song`.`song_count` AND `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk`;", [$albumId]],
            ["UPDATE `album_disk`, (SELECT SUM(`song`.`total_count`) AS `total_count`, `album_disk`.`id` AS `object_id` FROM `song` LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk` WHERE `song`.`album` = ? GROUP BY `album_disk`.`id`) AS `object_count` SET `album_disk`.`total_count` = `object_count`.`total_count` WHERE `album_disk`.`total_count` != `object_count`.`total_count` AND `album_disk`.`id` = `object_count`.`object_id`;", [$albumId]],
        ];

        foreach ($statements as [$sql, $params]) {
            $this->runMaintenance($sql, $params);
        }
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
