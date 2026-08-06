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
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Module\Database\Exception\InsertIdInvalidException;
use Ampache\Module\System\Core;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\SongDataFieldEnum;
use Ampache\Repository\Model\SongFieldEnum;
use Ampache\Repository\Model\SongMbidSourceEnum;
use Ampache\Repository\Model\Tag;
use Generator;
use PDO;
use Psr\Log\LoggerInterface;

final readonly class SongRepository implements SongRepositoryInterface
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
        private LoggerInterface $logger,
    ) {}

    public function collectGarbage(Song $song): void
    {
        foreach (Song::get_parent_array($song->id) as $song_artist_id) {
            Album::check_album_map($song->album, 'song', $song_artist_id);
        }

        $statements = [
            ["DELETE FROM `artist_map` WHERE `artist_map`.`object_type` = 'album' AND `artist_map`.`object_id` = ? AND (EXISTS (SELECT 1 FROM `album` WHERE `album`.`id` = ? AND `album`.`album_artist` IS NULL) OR NOT EXISTS (SELECT 1 FROM `song` WHERE `song`.`album` = ?));", [$song->album, $song->album, $song->album]],
            ["DELETE FROM `artist_map` WHERE `artist_map`.`object_type` = 'song' AND `artist_map`.`object_id` = ?;", [$song->id]],
        ];

        // a map that cannot be cleaned is not worth failing the caller over
        foreach ($statements as $statement) {
            try {
                $this->connection->query($statement[0], $statement[1]);
            } catch (DatabaseException) {
                $this->logger->debug(
                    'collectGarbage error: ' . $statement[0],
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }
        }
    }

    public function collectGarbageForSongs(array $songIds): void
    {
        if ($songIds === []) {
            return;
        }

        $idList = implode(',', array_map('intval', $songIds));

        try {
            $this->connection->query("DELETE FROM `artist_map` WHERE `artist_map`.`object_type` = 'song' AND `artist_map`.`object_id` IN ($idList);");
        } catch (DatabaseException) {
            $this->logger->debug(
                'collectGarbageForSongs error',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
        }
    }

    /**
     * Removes the songs and song rows the scanner has orphaned, across the whole table
     */
    public function collectOrphanedGarbage(?string $ignorePattern): void
    {
        $statements = [];
        if ($ignorePattern !== null && $ignorePattern !== '') {
            // delete files matching catalog_ignore_pattern
            $statements[] = ['DELETE FROM `song` WHERE `file` REGEXP ?;', [$ignorePattern]];
        }

        // delete duplicates
        $statements[] = ['DELETE `dupe` FROM `song` AS `dupe`, `song` AS `orig` WHERE `dupe`.`id` > `orig`.`id` AND `dupe`.`file` <=> `orig`.`file`;', []];
        // clean up missing catalogs
        $statements[] = ['DELETE FROM `song` WHERE `song`.`catalog` NOT IN (SELECT `id` FROM `catalog`);', []];
        // delete the rest
        $statements[] = ['DELETE FROM `song_data` WHERE `song_data`.`song_id` NOT IN (SELECT `song`.`id` FROM `song`);', []];
        $statements[] = ['DELETE FROM `song_map` WHERE `song_map`.`song_id` NOT IN (SELECT `song`.`id` FROM `song`);', []];
        // also clean up some bad data that might creep in, one table scan instead of two
        $statements[] = ["UPDATE `song` SET `composer` = NULLIF(`composer`, ''), `mbid` = NULLIF(`mbid`, '');", []];
        $statements[] = ['INSERT IGNORE INTO `song_data` (`song_id`) SELECT `id` FROM `song` WHERE `id` NOT IN (SELECT `song_id` FROM `song_data`);', []];
        // one table scan instead of six: NULLIF empties each column independently
        $statements[] = ["UPDATE `song_data` SET `comment` = NULLIF(`comment`, ''), `lyrics` = NULLIF(`lyrics`, ''), `label` = NULLIF(`label`, ''), `language` = NULLIF(`language`, ''), `waveform` = NULLIF(`waveform`, ''), `disksubtitle` = NULLIF(`disksubtitle`, '');", []];

        // one statement that cannot run must not take the rest of the sweep down with it
        foreach ($statements as $statement) {
            try {
                $this->connection->query($statement[0], $statement[1]);
            } catch (DatabaseException) {
                $this->logger->debug(
                    'collectGarbage error: ' . $statement[0],
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }
        }
    }

    /**
     * Counts the songs of one album still held by a catalog, which decides whether the album moves with them
     */
    public function countByAlbumAndCatalog(int $albumId, int $catalogId): int
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(`id`) FROM `song` WHERE `album` = ? AND `catalog` = ?;',
            [$albumId, $catalogId]
        );
    }

    public function delete(int $songId): bool
    {
        // keep details about deletions, but losing the record must not stop the delete itself
        try {
            $this->connection->query(
                'REPLACE INTO `deleted_song` (`id`, `addition_time`, `delete_time`, `title`, `file`, `catalog`, `total_count`, `total_skip`, `album`, `artist`) SELECT `id`, `addition_time`, UNIX_TIMESTAMP(), `title`, `file`, `catalog`, `total_count`, `total_skip`, `album`, `artist` FROM `song` WHERE `id` = ?;',
                [$songId]
            );
        } catch (DatabaseException) {
            $this->logger->warning(
                'delete could not record deleted_song ' . $songId,
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
        }

        try {
            $this->connection->query(
                'DELETE FROM `song` WHERE `id` = ?',
                [$songId]
            );
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Removes every songs of one catalog, for a catalog that is being deleted
     */
    public function deleteByCatalog(int $catalogId): bool
    {
        try {
            $this->connection->query('DELETE FROM `song` WHERE `catalog` = ?', [$catalogId]);
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Removes a set of songs by id, without recording them in the `deleted_song` archive
     *
     * @param list<int> $songIds
     */
    public function deleteByIds(array $songIds): void
    {
        if ($songIds === []) {
            return;
        }

        $this->connection->query(
            'DELETE FROM `song` WHERE `id` IN (' . implode(',', array_map(intval(...), $songIds)) . ')'
        );
    }

    /**
     * Records a set of songs in the `deleted_song` archive and removes them
     *
     * @param list<int> $songIds
     */
    public function deleteByIdsWithArchive(array $songIds): void
    {
        if ($songIds === []) {
            return;
        }

        $idList = implode(',', array_map(intval(...), $songIds));

        // keep details about deletions, but losing the record must not stop the delete itself
        try {
            $this->connection->query(
                'REPLACE INTO `deleted_song` (`id`, `addition_time`, `delete_time`, `title`, `file`, `catalog`, `total_count`, `total_skip`, `album`, `artist`) SELECT `id`, `addition_time`, UNIX_TIMESTAMP(), `title`, `file`, `catalog`, `total_count`, `total_skip`, `album`, `artist` FROM `song` WHERE `id` IN (' . $idList . ');'
            );
        } catch (DatabaseException) {
            $this->logger->warning(
                'deleteByIdsWithArchive could not record deleted_song ' . $idList,
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
        }

        $this->connection->query('DELETE FROM `song` WHERE `id` IN (' . $idList . ');');
    }

    /**
     * Reads the id of the song holding this file
     */
    public function findIdByFile(string $file): ?int
    {
        $songId = $this->connection->fetchOne('SELECT `song`.`id` FROM `song` WHERE `song`.`file` = ? LIMIT 1', [$file]);

        return ($songId === false)
            ? null
            : (int) $songId;
    }

    /**
     * Reads the id of the song whose file matches a pattern, for a remote url that carries its own id
     */
    public function findIdByFilePattern(string $pattern): ?int
    {
        $songId = $this->connection->fetchOne('SELECT `id` FROM `song` WHERE `file` LIKE ? LIMIT 1', [$pattern]);

        return ($songId === false)
            ? null
            : (int) $songId;
    }

    /**
     * Reads the id of the song carrying this MusicBrainz id
     */
    public function findIdByMbid(string $mbid): ?int
    {
        $songId = $this->connection->fetchOne('SELECT `song`.`id` FROM `song` WHERE `song`.`mbid` = ? LIMIT 1', [$mbid]);

        return ($songId === false)
            ? null
            : (int) $songId;
    }

    /**
     * Reads the id of the song matching a set of tags, by mbid where the tags carry one and by name where they do not
     *
     * @param array<string, mixed> $data
     */
    public function findIdByTags(array $data): ?int
    {
        $where  = 'WHERE `song`.`title` = ?';
        $params = [$data['title']];
        if ($data['track']) {
            $where .= ' AND `song`.`track` = ?';
            $params[] = $data['track'];
        }

        $sql = 'SELECT `song`.`id` FROM `song` INNER JOIN `artist` ON `artist`.`id` = `song`.`artist` INNER JOIN `album` ON `album`.`id` = `song`.`album` ';

        if ($data['mb_artistid']) {
            $where .= ' AND `artist`.`mbid` = ?';
            $params[] = $data['mb_artistid'];
        } else {
            $where .= ' AND `artist`.`name` = ?';
            $params[] = $data['artist'];
        }

        if ($data['mb_albumid']) {
            $where .= ' AND `album`.`mbid` = ?';
            $params[] = $data['mb_albumid'];
        } else {
            $where .= ' AND `album`.`name` = ?';
            $params[] = $data['album'];
        }

        $songId = $this->connection->fetchOne($sql . $where . ' LIMIT 1', $params);

        return ($songId === false)
            ? null
            : (int) $songId;
    }

    /**
     * Reads a song id from a last.fm style match on title, artist and album
     */
    public function findIdForScrobble(
        string $songName,
        string $artistName,
        string $albumName,
        string $songMbid,
        string $artistMbid,
        string $albumMbid,
    ): ?int {
        // by default require song, album, artist for any searches
        $sql    = "SELECT `song`.`id` FROM `song` LEFT JOIN `album` ON `album`.`id` = `song`.`album` LEFT JOIN `artist` ON `artist`.`id` = `song`.`artist` WHERE `song`.`title` = ? AND (`artist`.`name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) = ?) AND (`album`.`name` = ? OR LTRIM(CONCAT(COALESCE(`album`.`prefix`, ''), ' ', `album`.`name`)) = ?)";
        $params = [$songName, $artistName, $artistName, $albumName, $albumName];

        if ($songMbid !== '') {
            $sql .= ' AND `song`.`mbid` = ?';
            $params[] = $songMbid;
        }

        if ($artistMbid !== '') {
            $sql .= ' AND `artist`.`mbid` = ?';
            $params[] = $artistMbid;
        }

        if ($albumMbid !== '') {
            $sql .= ' AND `album`.`mbid` = ?';
            $params[] = $albumMbid;
        }

        $songId = $this->connection->fetchOne($sql . ' LIMIT 1;', $params);

        return ($songId === false)
            ? null
            : (int) $songId;
    }

    /**
     * Reads the songs whose album row has gone, which have to be re-read from their tags to be fixed
     *
     * @return list<int>
     */
    public function findIdsWithMissingAlbum(): array
    {
        $result = $this->connection->query(
            'SELECT `id` FROM `song` WHERE (`song`.`album` IN (SELECT `album_id` FROM `album_map` WHERE `album_id` NOT IN (SELECT `id` FROM `album`)) OR `song`.`album` NOT IN (SELECT `id` FROM `album`));'
        );

        $songIds = [];
        while ($songId = $result->fetchColumn()) {
            $songIds[] = (int) $songId;
        }

        return $songIds;
    }

    /**
     * The uploader of the song
     *
     * Three distinct states: an id, `null` when the song exists but was not user-uploaded, and `false`
     * when there is no such song. The caller only downgrades an access level for a real row, so it has
     * to be able to tell the last two apart.
     */
    public function findOwnerId(int $songId): int|false|null
    {
        $row = $this->connection->fetchRow('SELECT `user_upload` FROM `song` WHERE `id` = ?', [$songId]);

        if (!is_array($row) || !array_key_exists('user_upload', $row)) {
            return false;
        }

        return ($row['user_upload'] === null)
            ? null
            : (int) $row['user_upload'];
    }

    /**
     * Reads the MusicBrainz id of an album, an artist or an album artist
     */
    public function findRelatedMbid(SongMbidSourceEnum $source, int $objectId): ?string
    {
        $mbid = $this->connection->fetchOne(
            sprintf('SELECT `mbid` FROM `%s` WHERE `id` = ? LIMIT 1;', $source->value),
            [$objectId]
        );

        return ($mbid === false || $mbid === null)
            ? null
            : (string) $mbid;
    }

    /**
     * gets the songs (including songs where they are the album artist) for this artist
     *
     * @return int[]
     */
    public function getAllByArtist(
        int $artistId,
    ): array {
        $user_id = Core::get_global('user')?->getId();
        $sql     = (AmpConfig::get('catalog_disable') || AmpConfig::get('catalog_filter'))
            ? "SELECT DISTINCT `song`.`id`, `song`.`album`, `song`.`disk`, `song`.`track` FROM `song` LEFT JOIN `album` ON `song`.`album` = `album`.`id` LEFT JOIN `album_map` ON `album_map`.`album_id` = `album`.`id` WHERE `album_map`.`object_id` = ? AND `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $user_id, true)) . ") ORDER BY `song`.`album`, `song`.`disk`, `song`.`track`, `song`.`id`;"
            : "SELECT DISTINCT `song`.`id`, `song`.`album`, `song`.`disk`, `song`.`track` FROM `song` LEFT JOIN `album` ON `song`.`album` = `album`.`id` LEFT JOIN `album_map` ON `album_map`.`album_id` = `album`.`id` WHERE `album_map`.`object_id` = ? ORDER BY `song`.`album`, `song`.`disk`, `song`.`track`, `song`.`id`;";

        $dbResults = $this->connection->query($sql, [$artistId]);
        $results   = [];
        while ($row = $dbResults->fetch(PDO::FETCH_ASSOC)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * gets the songs for an album takes an optional limit
     *
     * @return int[]
     */
    public function getByAlbum(
        int $albumId,
        int $limit = 0,
    ): array {
        $user_id = Core::get_global('user')?->getId() ?? -1;
        $sql     = (AmpConfig::get('catalog_disable') || AmpConfig::get('catalog_filter'))
            ? "SELECT `song`.`id` FROM `song` WHERE `song`.`album` = ? AND `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $user_id, true)) . ") ORDER BY `song`.`disk`, `song`.`track`, `song`.`title`"
            : "SELECT `song`.`id` FROM `song` WHERE `song`.`album` = ? ORDER BY `song`.`disk`, `song`.`track`, `song`.`title`";

        if ($limit !== 0) {
            $sql .= " LIMIT " . $limit;
        }

        $dbResults = $this->connection->query($sql, [$albumId]);
        $results   = [];
        while ($songId = $dbResults->fetchColumn()) {
            $results[] = (int) $songId;
        }

        return $results;
    }

    /**
     * gets the songs for an album for a single disk takes an optional limit
     *
     * @return int[]
     */
    public function getByAlbumDisk(
        int $albumDiskId,
        int $limit = 0,
    ): array {
        $user_id = Core::get_global('user')?->getId() ?? -1;
        $sql     = (AmpConfig::get('catalog_disable') || AmpConfig::get('catalog_filter'))
            ? "SELECT `song`.`id` FROM `song` LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk` WHERE `album_disk`.`id` = ? AND `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $user_id, true)) . ") ORDER BY `song`.`disk`, `song`.`track`, `song`.`title` "
            : "SELECT `song`.`id` FROM `song` LEFT JOIN `album_disk` ON `album_disk`.`album_id` = `song`.`album` AND `album_disk`.`disk` = `song`.`disk` WHERE `album_disk`.`id` = ? ORDER BY `song`.`disk`, `song`.`track`, `song`.`title` ";

        if ($limit !== 0) {
            $sql .= "LIMIT " . $limit;
        }

        $dbResults = $this->connection->query($sql, [$albumDiskId]);
        $results   = [];
        while ($songId = $dbResults->fetchColumn()) {
            $results[] = (int) $songId;
        }

        return $results;
    }

    /**
     * gets the songs for this artist
     *
     * @return int[]
     */
    public function getByArtist(
        int $artistId,
    ): array {
        $user_id = Core::get_global('user')?->getId();
        $sql     = (AmpConfig::get('catalog_disable') || AmpConfig::get('catalog_filter'))
            ? "SELECT DISTINCT `song`.`id`, `song`.`album`, `song`.`disk`, `song`.`track` FROM `song` LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `song`.`id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_type` = 'song' AND `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $user_id, true)) . ") ORDER BY `song`.`album`, `song`.`disk`, `song`.`track`, `song`.`id`"
            : "SELECT DISTINCT `song`.`id`, `song`.`album`, `song`.`disk`, `song`.`track` FROM `song` LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `song`.`id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_type` = 'song' ORDER BY `song`.`album`, `song`.`disk`, `song`.`track`, `song`.`id`";

        $dbResults = $this->connection->query($sql, [$artistId]);
        $results   = [];
        while ($row = $dbResults->fetch(PDO::FETCH_ASSOC)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * Returns all song ids linked to the provided catalog (or all)
     *
     * @return Generator<int>
     */
    public function getByCatalog(?Catalog $catalog = null): Generator
    {
        if ($catalog !== null) {
            $result = $this->connection->query(
                'SELECT `id` FROM `song` WHERE `catalog` = ? ORDER BY `album`, `track`',
                [$catalog->getId()]
            );
        } else {
            $result = $this->connection->query(
                'SELECT `id` FROM `song` ORDER BY `album`, `track`'
            );
        }

        while ($songId = $result->fetchColumn()) {
            yield (int) $songId;
        }
    }

    /**
     * gets the songs for a folder, based on folder name
     *
     * @return int[]
     */
    public function getByFolder(
        string $folderName,
    ): array {
        $user_id = Core::get_global('user')?->getId() ?? -1;
        $sql     = (AmpConfig::get('catalog_disable') || AmpConfig::get('catalog_filter'))
            ? "SELECT `song`.`id` FROM `song` LEFT JOIN `folder` ON `folder`.`id` = `song`.`folder` WHERE `folder`.`name` = ? AND `folder`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $user_id, true)) . ") ORDER BY `song`.`album`, `song`.`disk`, `song`.`track`, `song`.`id`"
            : "SELECT `song`.`id` FROM `song` LEFT JOIN `folder` ON `folder`.`id` = `song`.`folder` WHERE `folder`.`name` = ? ORDER BY `song`.`album`, `song`.`disk`, `song`.`track`, `song`.`id`";

        $dbResults = $this->connection->query($sql, [$folderName]);
        $results   = [];
        while ($songId = $dbResults->fetchColumn()) {
            $results[] = (int) $songId;
        }

        return $results;
    }

    /**
     * gets the songs for a label, based on label name
     *
     * @return int[]
     */
    public function getByLabel(
        string $labelName,
    ): array {
        $user_id = Core::get_global('user')?->getId() ?? -1;
        $sql     = (AmpConfig::get('catalog_disable') || AmpConfig::get('catalog_filter'))
            ? "SELECT `song`.`id` FROM `song` LEFT JOIN `song_data` ON `song_data`.`song_id` = `song`.`id` WHERE `song_data`.`label` = ? AND `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $user_id, true)) . ") ORDER BY `song`.`album`, `song`.`disk`, `song`.`track`, `song`.`id`"
            : "SELECT `song`.`id` FROM `song` LEFT JOIN `song_data` ON `song_data`.`song_id` = `song`.`id` WHERE `song_data`.`label` = ? ORDER BY `song`.`album`, `song`.`disk`, `song`.`track`, `song`.`id`";

        $dbResults = $this->connection->query($sql, [$labelName]);
        $results   = [];
        while ($songId = $dbResults->fetchColumn()) {
            $results[] = (int) $songId;
        }

        return $results;
    }

    /**
     * Returns a list of song ID's attached to a license ID.
     *
     * @return int[]
     */
    public function getByLicense(int $licenseId): array
    {
        $dbResults = $this->connection->query(
            'SELECT `id` FROM `song` WHERE `song`.`license` = ?',
            [$licenseId]
        );

        $results = [];
        while ($songId = $dbResults->fetchColumn()) {
            $results[] = (int) $songId;
        }

        return $results;
    }

    /**
     * Reads the extended row of a song
     *
     * @return array<string, mixed>
     */
    public function getDataRow(int $songId): array
    {
        $row = $this->connection->fetchRow(
            'SELECT `comment`, `lyrics`, `label`, `language`, `replaygain_track_gain`, `replaygain_track_peak`, `replaygain_album_gain`, `replaygain_album_peak`, `r128_track_gain`, `r128_album_gain`, `disksubtitle` FROM `song_data` WHERE `song_id` = ?',
            [$songId]
        );

        return ($row === false)
            ? []
            : $row;
    }

    /**
     * Reads the extended rows of a set of songs, for the in-request cache
     *
     * @param list<int|string> $songIds
     * @return list<array<string, mixed>>
     */
    public function getDataRowsByIds(array $songIds): array
    {
        if ($songIds === []) {
            return [];
        }

        $result = $this->connection->query(
            sprintf(
                'SELECT `song_id`, `comment`, `lyrics`, `label`, `language`, `replaygain_track_gain`, `replaygain_track_peak`, `replaygain_album_gain`, `replaygain_album_peak`, `r128_track_gain`, `r128_album_gain`, `disksubtitle` FROM `song_data` WHERE `song_id` IN (%s)',
                implode(',', array_map(intval(...), $songIds))
            )
        );

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Reads the rows the `deleted_song` archive holds
     *
     * @return list<array<string, mixed>>
     */
    public function getDeletedRows(): array
    {
        $result = $this->connection->query('SELECT * FROM `deleted_song`');

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Gets a list of the disabled songs for and returns an array of Songs
     *
     * @return Generator<Song>
     */
    public function getDisabled(): Generator
    {
        $result = $this->connection->query(
            'SELECT `id` FROM `song` WHERE `enabled` = 0'
        );

        while ($rowId = $result->fetchColumn()) {
            yield new Song((int) $rowId);
        }
    }

    /**
     * Reads a page of the enabled songs across every catalog, or the given ones
     *
     * @param array<int|string>|null $catalogIds every catalog when null or empty
     * @return list<int>
     */
    public function getEnabledIds(?array $catalogIds, int $size = 0, int $offset = 0, bool $catalogDisable = false): array
    {
        // the catalog join mirrors the stored server song count, so a total and a page agree
        $sql = ($catalogDisable)
            ? "SELECT `song`.`id` FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `song`.`enabled` = '1' AND `catalog`.`enabled` = '1' "
            : "SELECT `song`.`id` FROM `song` WHERE `song`.`enabled` = '1' ";
        if ($catalogIds !== null && $catalogIds !== []) {
            $sql .= sprintf('AND `song`.`catalog` IN (%s) ', implode(',', array_map(intval(...), $catalogIds)));
        }

        // the id tiebreaker keeps the order total, so a paging client can't see a row twice or skip one
        $result = $this->connection->query(
            $sql . 'ORDER BY `song`.`album`, `song`.`id` ' . $this->limitClause($size, $offset)
        );

        $songIds = [];
        while ($songId = $result->fetchColumn()) {
            $songIds[] = (int) $songId;
        }

        return $songIds;
    }

    /**
     * Reads a page of the enabled songs of one catalog, optionally ordered by album
     *
     * @return list<int>
     */
    public function getEnabledIdsByCatalog(int $catalogId, int $size = 0, int $offset = 0, bool $byAlbum = false): array
    {
        $result = $this->connection->query(
            "SELECT `id` FROM `song` WHERE `catalog` = ? AND `enabled` = '1' " . (($byAlbum) ? 'ORDER BY `album` ' : '') . $this->limitClause($size, $offset),
            [$catalogId]
        );

        $songIds = [];
        while ($songId = $result->fetchColumn()) {
            $songIds[] = (int) $songId;
        }

        return $songIds;
    }

    /**
     * Reads the file and title of every song of one catalog, which a remote verify compares against
     *
     * @return list<array{id: int, file: string, title: string}>
     */
    public function getFileRowsByCatalog(int $catalogId): array
    {
        $result = $this->connection->query(
            'SELECT `id`, `file`, `title` FROM `song` WHERE `catalog` = ?',
            [$catalogId]
        );

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = [
                'id' => (int) $row['id'],
                'file' => (string) $row['file'],
                'title' => (string) $row['title'],
            ];
        }

        return $rows;
    }

    /**
     * Reads every song file of one catalog keyed by song id, for the scanner's in-process cache
     *
     * @return array<int, string>
     */
    public function getFilesByCatalog(int $catalogId, int $limit = 0, int $offset = 0): array
    {
        $result = $this->connection->query(
            'SELECT `id`, `file` FROM `song` WHERE `catalog` = ? AND `file` IS NOT NULL ORDER BY `id` DESC' . (($limit > 0) ? sprintf(' LIMIT %d, %d', $offset, $limit) : '') . ';',
            [$catalogId]
        );

        $files = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $files[(int) $row['id']] = (string) $row['file'];
        }

        return $files;
    }

    /**
     * Reads the ids of every song of one catalog, enabled or not
     *
     * @return list<int>
     */
    public function getIdsByCatalog(int $catalogId): array
    {
        $result = $this->connection->query('SELECT `id` FROM `song` WHERE `catalog` = ?', [$catalogId]);

        $songIds = [];
        while ($songId = $result->fetchColumn()) {
            $songIds[] = (int) $songId;
        }

        return $songIds;
    }

    /**
     * Reads the ids of the songs of one catalog whose file carries one of the given extensions
     *
     * @param list<string> $extensions without the dot, as the cache preferences name them
     * @return list<int>
     */
    public function getIdsByCatalogAndExtension(int $catalogId, array $extensions): array
    {
        if ($extensions === []) {
            return [];
        }

        $params = [$catalogId];
        $like   = [];
        foreach ($extensions as $extension) {
            $like[]   = '`file` LIKE ?';
            $params[] = '%.' . $extension;
        }

        $result = $this->connection->query(
            'SELECT `id` FROM `song` WHERE `catalog` = ? AND (' . implode(' OR ', $like) . ');',
            $params
        );

        $songIds = [];
        while ($songId = $result->fetchColumn()) {
            $songIds[] = (int) $songId;
        }

        return $songIds;
    }

    /**
     * Reads the songs whose file sits under a base folder path
     *
     * @return list<int>
     */
    public function getIdsByFilePrefix(string $folderPath): array
    {
        $result = $this->connection->query(
            'SELECT `id` FROM `song` WHERE `file` LIKE ?',
            [$folderPath . '%']
        );

        $songIds = [];
        while ($songId = $result->fetchColumn()) {
            $songIds[] = (int) $songId;
        }

        return $songIds;
    }

    /**
     * Reads the artists mapped onto a song, or the artists mapped onto an album
     *
     * @return list<int>
     */
    public function getParentIds(int $objectId, bool $forAlbum): array
    {
        $sql = ($forAlbum)
            ? "SELECT DISTINCT `object_id` FROM `album_map` WHERE `object_type` = 'album' AND `album_id` = ?;"
            : "SELECT DISTINCT `artist_id` AS `object_id` FROM `artist_map` WHERE `object_type` = 'song' AND `object_id` = ?;";

        $result = $this->connection->query($sql, [$objectId]);

        $objectIds = [];
        while ($parentId = $result->fetchColumn()) {
            $objectIds[] = (int) $parentId;
        }

        return $objectIds;
    }

    /**
     * The parent artists of many songs (or albums) in one statement, keyed by the object they belong to
     *
     * Every requested id is present in the result, mapping to an empty list when it has no parents, so a
     * caller caching the answer does not fall back to a per-object query for the ones that matched nothing.
     *
     * @param list<int> $objectIds
     * @return array<int, list<int>>
     */
    public function getParentIdsBulk(array $objectIds, bool $forAlbum): array
    {
        if ($objectIds === []) {
            return [];
        }

        /** @var array<int, list<int>> $parents */
        $parents = array_fill_keys($objectIds, []);

        $holders = implode(',', array_fill(0, count($objectIds), '?'));
        $sql     = ($forAlbum)
            ? sprintf("SELECT DISTINCT `album_id` AS `owner_id`, `object_id` FROM `album_map` WHERE `object_type` = 'album' AND `album_id` IN (%s);", $holders)
            : sprintf("SELECT DISTINCT `object_id` AS `owner_id`, `artist_id` AS `object_id` FROM `artist_map` WHERE `object_type` = 'song' AND `object_id` IN (%s);", $holders);

        $result = $this->connection->query($sql, $objectIds);
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $parents[(int) $row['owner_id']][] = (int) $row['object_id'];
        }

        return $parents;
    }

    /**
     * Gets the songs from the artist in a random order
     *
     * @return int[]
     */
    public function getRandomByArtist(
        Artist $artist,
    ): array {
        $user_id = Core::get_global('user')?->getId();
        $sql     = (AmpConfig::get('catalog_disable') || AmpConfig::get('catalog_filter'))
            ? "SELECT DISTINCT `artist_map`.`object_id` AS `id` FROM `artist_map` LEFT JOIN `song` ON `artist_map`.`object_id` = `song`.`id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_type` = 'song' AND `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $user_id, true)) . ") ORDER BY RAND()"
            : "SELECT DISTINCT `artist_map`.`object_id` AS `id` FROM `artist_map` LEFT JOIN `song` ON `artist_map`.`object_id` = `song`.`id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_type` = 'song' ORDER BY RAND()";

        $dbResults = $this->connection->query($sql, [$artist->getId()]);
        $results   = [];
        while ($songId = $dbResults->fetchColumn()) {
            $results[] = (int) $songId;
        }

        return $results;
    }

    /**
     * Gets the songs from a genre in a random order
     *
     * @return int[]
     */
    public function getRandomByGenre(
        Tag $genre,
    ): array {
        if ($genre->isNew()) {
            return [];
        }

        $results = Tag::get_tag_objects('song', $genre->getId());
        shuffle($results);

        return $results;
    }

    /**
     * Reads the replaygain columns of the extended row, the one partial read the callers ask for
     *
     * @return array<string, mixed>
     */
    public function getReplaygainRow(int $songId): array
    {
        $row = $this->connection->fetchRow(
            'SELECT `replaygain_track_gain`, `replaygain_track_peak`, `replaygain_album_gain`, `replaygain_album_peak`, `r128_track_gain`, `r128_album_gain` FROM `song_data` WHERE `song_id` = ?',
            [$songId]
        );

        return ($row === false)
            ? []
            : $row;
    }

    /**
     * Reads one song row with the album and artist identity a `Song` object is built from
     *
     * @return array<string, mixed>
     */
    public function getRow(int $songId): array
    {
        $row = $this->connection->fetchRow(
            'SELECT `song`.`id`, `song`.`file`, `song`.`catalog`, `song`.`album`, `song`.`album_disk`, `song`.`disk`, `song`.`year`, `song`.`artist`, `song`.`title`, `song`.`bitrate`, `song`.`rate`, `song`.`mode`, `song`.`size`, `song`.`time`, `song`.`track`, `song`.`mbid`, `song`.`played`, `song`.`enabled`, `song`.`update_time`, `song`.`addition_time`, `song`.`user_upload`, `song`.`license`, `song`.`composer`, `song`.`channels`, `song`.`total_count`, `song`.`total_skip`, `song`.`last_played`, `album`.`album_artist` AS `albumartist`, `album`.`mbid` AS `album_mbid`, `artist`.`mbid` AS `artist_mbid`, `album_artist`.`mbid` AS `albumartist_mbid` FROM `song` LEFT JOIN `album` ON `album`.`id` = `song`.`album` LEFT JOIN `artist` ON `artist`.`id` = `song`.`artist` LEFT JOIN `artist` AS `album_artist` ON `album_artist`.`id` = `album`.`album_artist` WHERE `song`.`id` = ?',
            [$songId]
        );

        return ($row === false)
            ? []
            : $row;
    }

    /**
     * Reads song rows for the in-request cache, dropping the disabled catalogs when they are hidden
     *
     * @param list<int|string> $songIds
     * @return list<array<string, mixed>>
     */
    public function getRowsByIds(array $songIds, bool $catalogDisable): array
    {
        if ($songIds === []) {
            return [];
        }

        $columns = '`song`.`id`, `song`.`file`, `song`.`catalog`, `song`.`album`, `song`.`album_disk`, `song`.`disk`, `song`.`year`, `song`.`artist`, `song`.`title`, `song`.`bitrate`, `song`.`rate`, `song`.`mode`, `song`.`size`, `song`.`time`, `song`.`track`, `song`.`mbid`, `song`.`played`, `song`.`enabled`, `song`.`update_time`, `song`.`addition_time`, `song`.`user_upload`, `song`.`license`, `song`.`composer`, `song`.`channels`, `song`.`total_count`, `song`.`total_skip`, `song`.`last_played`';
        $idList  = implode(',', array_map(intval(...), $songIds));

        $sql = ($catalogDisable)
            ? sprintf("SELECT %s FROM `song` LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` WHERE `song`.`id` IN (%s) AND `catalog`.`enabled` = '1' ", $columns, $idList)
            : sprintf('SELECT %s FROM `song` WHERE `song`.`id` IN (%s)', $columns, $idList);

        $result = $this->connection->query($sql);

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Reads the values mapped onto a song, ISRCs being the only kind so far
     *
     * @return list<string>
     */
    public function getSongMapValues(int $songId, string $objectType): array
    {
        $result = $this->connection->query(
            'SELECT DISTINCT `object_id` FROM `song_map` WHERE `object_type` = ? AND `song_id` = ?;',
            [$objectType, $songId]
        );

        $values = [];
        while ($value = $result->fetchColumn()) {
            $values[] = (string) $value;
        }

        return $values;
    }

    /**
     * gets the songs for this artist

     * @return int[]
     */
    public function getTopSongsByArtist(
        Artist $artist,
        int $count = 50,
    ): array {
        $user_id = Core::get_global('user')?->getId();
        $sql     = (AmpConfig::get('catalog_disable') || AmpConfig::get('catalog_filter'))
            ? "SELECT DISTINCT `song`.`id`, COUNT(`object_count`.`object_id`) AS `counting` FROM `song` LEFT JOIN `object_count` ON `object_count`.`object_id` = `song`.`id` AND `object_type` = 'song' LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `song`.`id` WHERE `artist_map`.`artist_id` = " . $artist->getId() . " AND `artist_map`.`object_type` = 'song' AND `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $user_id, true)) . ") GROUP BY `song`.`id` ORDER BY count(`object_count`.`object_id`) DESC LIMIT " . $count
            : "SELECT DISTINCT `song`.`id`, COUNT(`object_count`.`object_id`) AS `counting` FROM `song` LEFT JOIN `object_count` ON `object_count`.`object_id` = `song`.`id` AND `object_type` = 'song' LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `song`.`id` WHERE `artist_map`.`artist_id` = " . $artist->getId() . " AND `artist_map`.`object_type` = 'song' GROUP BY `song`.`id` ORDER BY count(`object_count`.`object_id`) DESC LIMIT " . $count;

        $dbResults = $this->connection->query($sql);
        $results   = [];
        while ($row = $dbResults->fetch(PDO::FETCH_ASSOC)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * Reads a page of the songs a verify pass walks, newest path first
     *
     * @return list<array{id: int, file: string, min_update_time: int}>
     */
    public function getVerifyRowsByCatalog(int $catalogId, int $limit, bool $onlyStale): array
    {
        $sql = ($onlyStale)
            ? 'SELECT `song`.`id`, `song`.`file`, `song`.`update_time` AS `min_update_time` FROM `song` LEFT JOIN `catalog` ON `song`.`catalog` = `catalog`.`id` WHERE `song`.`catalog` = ? AND `song`.`update_time` < `catalog`.`last_update` ORDER BY `song`.`file` DESC LIMIT '
            : 'SELECT `song`.`id`, `song`.`file`, `song`.`update_time` AS `min_update_time` FROM `song` LEFT JOIN `catalog` ON `song`.`catalog` = `catalog`.`id` WHERE `song`.`catalog` = ? ORDER BY `song`.`file` DESC LIMIT ';

        return $this->readVerifyRows($sql . $limit . ';', $catalogId);
    }

    /**
     * Reads the stored waveform, which is a blob every other read deliberately leaves behind
     *
     * @return array<string, mixed>
     */
    public function getWaveformRow(int $songId): array
    {
        $row = $this->connection->fetchRow(
            'SELECT `waveform` FROM `song_data` WHERE `song_id` = ?',
            [$songId]
        );

        return ($row === false)
            ? []
            : $row;
    }

    /**
     * Whether a song row exists
     */
    public function hasId(int $songId): bool
    {
        return $this->connection->fetchOne('SELECT `song`.`id` FROM `song` WHERE `song`.`id` = ?', [$songId]) !== false;
    }

    /**
     * Inserts a song row and returns its id, or `null` when the write failed
     *
     * @param list<mixed> $values in the column order of the statement
     */
    public function insert(array $values): ?int
    {
        try {
            $this->connection->query(
                'INSERT INTO `song` (`catalog`, `file`, `album`, `album_disk`, `disk`, `artist`, `title`, `bitrate`, `rate`, `mode`, `size`, `time`, `track`, `addition_time`, `update_time`, `year`, `mbid`, `user_upload`, `license`, `composer`, `channels`) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                $values
            );

            return $this->connection->getLastInsertedId();
        } catch (DatabaseException|InsertIdInvalidException) {
            return null;
        }
    }

    /**
     * Inserts the extended row that belongs with a new song
     *
     * @param list<mixed> $values in the column order of the statement
     */
    public function insertData(array $values): void
    {
        $this->connection->query(
            'INSERT INTO `song_data` (`song_id`, `disksubtitle`, `comment`, `lyrics`, `label`, `language`, `replaygain_track_gain`, `replaygain_track_peak`, `replaygain_album_gain`, `replaygain_album_peak`, `r128_track_gain`, `r128_album_gain`) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            $values
        );
    }

    /**
     * Points the maps of one album at another and drops what the old one left behind
     *
     * A single song is moved on its own when the caller names one; otherwise the whole album moves.
     */
    public function migrateAlbum(int $newAlbumId, int $oldAlbumId, int $songId): bool
    {
        $statements = [
            ['UPDATE IGNORE `album_disk` SET `album_id` = ? WHERE `album_id` = ?', [$newAlbumId, $oldAlbumId]],
        ];

        $statements[] = ($songId > 0)
            ? ["UPDATE IGNORE `album_map` SET `album_id` = ? WHERE `album_id` = ? AND `object_id` = ? AND `object_type` = 'song'", [$newAlbumId, $oldAlbumId, $songId]]
            : ["UPDATE IGNORE `album_map` SET `album_id` = ? WHERE `album_id` = ? AND `object_type` = 'song'", [$newAlbumId, $oldAlbumId]];

        $statements[] = ['UPDATE IGNORE `artist_map` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?', [$newAlbumId, 'album', $oldAlbumId]];
        $statements[] = ['UPDATE IGNORE `catalog_map` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?', [$newAlbumId, 'album', $oldAlbumId]];

        foreach ($statements as $statement) {
            try {
                $this->connection->query($statement[0], $statement[1]);
            } catch (DatabaseException) {
                return false;
            }
        }

        // delete leftover duplicate maps
        $this->connection->query('DELETE FROM `album_disk` WHERE `album_id` = ?', [$oldAlbumId]);
        $this->connection->query('DELETE FROM `album_map` WHERE `album_id` = ?', [$oldAlbumId]);
        $this->connection->query('DELETE FROM `artist_map` WHERE `object_type` = ? AND `object_id` = ?', ['album', $oldAlbumId]);
        $this->connection->query('DELETE FROM `catalog_map` WHERE `object_type` = ? AND `object_id` = ?', ['album', $oldAlbumId]);

        return true;
    }

    /**
     * Points the maps of one artist at another and drops what the old one left behind
     */
    public function migrateArtist(int $newArtistId, int $oldArtistId): bool
    {
        $statements = [
            ['UPDATE IGNORE `album_map` SET `object_id` = ? WHERE `object_id` = ?', [$newArtistId, $oldArtistId]],
            ['UPDATE IGNORE `artist_map` SET `artist_id` = ? WHERE `artist_id` = ?', [$newArtistId, $oldArtistId]],
            ['UPDATE IGNORE `catalog_map` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?', [$newArtistId, 'artist', $oldArtistId]],
        ];

        foreach ($statements as $statement) {
            try {
                $this->connection->query($statement[0], $statement[1]);
            } catch (DatabaseException) {
                return false;
            }
        }

        // delete leftover duplicate maps
        $this->connection->query('DELETE FROM `album_map` WHERE `object_id` = ?', [$oldArtistId]);
        $this->connection->query('DELETE FROM `artist_map` WHERE `artist_id` = ?', [$oldArtistId]);
        $this->connection->query('DELETE FROM `catalog_map` WHERE `object_type` = ? AND `object_id` = ?', ['artist', $oldArtistId]);

        return true;
    }

    /**
     * Rewrites the leading path of every file of one catalog, for a catalog that moved on disk
     */
    public function replaceFilePathForCatalog(int $catalogId, string $oldPath, string $newPath): void
    {
        $this->connection->query(
            'UPDATE `song` SET `file` = REPLACE(`file`, ?, ?) WHERE `catalog` = ?',
            [$oldPath, $newPath, $catalogId]
        );
    }

    /**
     * Puts the play counters of songs whose history changed back in step, which a rebuild cannot reach
     *
     * The rebuilds are `UPDATE `song`, (SELECT …) … WHERE `song`.`id` = …`, so a song with no
     * `object_count` rows is absent from the derived table and keeps whatever counter it already had.
     * The `played` flag moves in both directions here for the same reason.
     */
    public function resetCountsWithoutHistory(): void
    {
        $statements = [
            "UPDATE `song` SET `total_count` = 0 WHERE `total_count` > 0 AND `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' UNION SELECT `object_id` FROM `object_count_summary` WHERE `object_type` = 'song' AND `count_type` = 'stream');",
            "UPDATE `song` SET `total_skip` = 0 WHERE `total_skip` > 0 AND `id` NOT IN (SELECT `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'skip' UNION SELECT `object_id` FROM `object_count_summary` WHERE `object_type` = 'song' AND `count_type` = 'skip');",
            'UPDATE `song` SET `played` = 0 WHERE `played` = 1 AND `total_count` = 0;',
            "UPDATE `song` SET `played` = 1 WHERE `played` = 0 AND `id` IN (SELECT `object_id` FROM `object_count` WHERE `object_type` = 'song' AND `count_type` = 'stream' UNION SELECT `object_id` FROM `object_count_summary` WHERE `object_type` = 'song' AND `count_type` = 'stream');",
        ];

        foreach ($statements as $sql) {
            try {
                $this->connection->query($sql);
            } catch (DatabaseException) {
                $this->logger->warning(
                    'count maintenance failed: ' . $sql,
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }
        }
    }

    /**
     * Writes a single `song_data` column
     */
    public function setDataField(int $songId, SongDataFieldEnum $field, string $value): bool
    {
        try {
            $this->connection->query(
                sprintf('UPDATE `song_data` SET `%s` = ? WHERE `song_id` = ?', $field->value),
                [$value, $songId]
            );
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Writes a single `song` column
     *
     * Returns false when the write failed, matching what the model's callers already expect. Callers own
     * the authorization and the blank-value guard; this only performs the statement.
     */
    public function setField(int $songId, SongFieldEnum $field, int|string|null $value): bool
    {
        try {
            $this->connection->query(
                sprintf('UPDATE `song` SET `%s` = ? WHERE `id` = ?', $field->value),
                [$value, $songId]
            );
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Moves a song to another catalog and to the file it now lives in
     */
    public function setFileAndCatalog(int $songId, string $file, int $catalogId): bool
    {
        try {
            $this->connection->query(
                'UPDATE `song` SET `file` = ?, `catalog` = ? WHERE `id` = ?;',
                [$file, $catalogId, $songId]
            );
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    /**
     * Rebuilds every song's play and skip totals from `object_count`, and the played flag that follows them
     */
    public function updateAllCounts(): void
    {
        $statements = [
            "UPDATE `song`, (SELECT SUM(`total`) AS `total_count`, `object_id` FROM (SELECT COUNT(`object_count`.`object_id`) AS `total`, `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'stream' GROUP BY `object_count`.`object_id` UNION ALL SELECT `count` AS `total`, `object_id` FROM `object_count_summary` WHERE `object_type` = 'song' AND `count_type` = 'stream') AS `combined_count` GROUP BY `object_id`) AS `object_count` SET `song`.`total_count` = `object_count`.`total_count` WHERE `song`.`total_count` != `object_count`.`total_count` AND `song`.`id` = `object_count`.`object_id`;",
            "UPDATE `song`, (SELECT SUM(`total`) AS `total_skip`, `object_id` FROM (SELECT COUNT(`object_count`.`object_id`) AS `total`, `object_id` FROM `object_count` WHERE `object_count`.`object_type` = 'song' AND `object_count`.`count_type` = 'skip' GROUP BY `object_count`.`object_id` UNION ALL SELECT `count` AS `total`, `object_id` FROM `object_count_summary` WHERE `object_type` = 'song' AND `count_type` = 'skip') AS `combined_count` GROUP BY `object_id`) AS `object_count` SET `song`.`total_skip` = `object_count`.`total_skip` WHERE `song`.`total_skip` != `object_count`.`total_skip` AND `song`.`id` = `object_count`.`object_id`;",
            "UPDATE `song` SET `played` = 0 WHERE `total_count` = 0 and `played` = 1;",
        ];

        foreach ($statements as $sql) {
            try {
                $this->connection->query($sql);
            } catch (DatabaseException) {
                $this->logger->warning(
                    'count maintenance failed: ' . $sql,
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }
        }
    }

    /**
     * Rewrites a song row and its extended row from a freshly read file
     *
     * @param list<mixed> $songValues in the column order of the statement
     * @param list<mixed> $dataValues in the column order of the statement
     */
    public function updateSong(int $songId, array $songValues, array $dataValues): void
    {
        $this->connection->query(
            'UPDATE `song` SET `album` = ?, `album_disk` = ?, `disk` = ?, `year` = ?, `artist` = ?, `title` = ?, `composer` = ?, `bitrate` = ?, `rate` = ?, `mode` = ?, `channels` = ?, `size` = ?, `time` = ?, `track` = ?, `mbid` = ?, `update_time` = ? WHERE `id` = ?',
            $songValues
        );

        // did you miss the insert? it'll never come back if we don't check
        $this->connection->query(
            'INSERT IGNORE INTO `song_data` (`song_id`) SELECT `id` from `song` where `id` = ? AND `id` NOT IN (SELECT `song_id` FROM `song_data`);',
            [$songId]
        );

        $this->connection->query(
            'UPDATE `song_data` SET `label` = ?, `lyrics` = ?, `language` = ?, `disksubtitle` = ?, `comment` = ?, `replaygain_track_gain` = ?, `replaygain_track_peak` = ?, `replaygain_album_gain` = ?, `replaygain_album_peak` = ?, `r128_track_gain` = ?, `r128_album_gain` = ? WHERE `song_id` = ?',
            $dataValues
        );
    }

    /**
     * Replaces the mapped values of one kind for a song, dropping whatever is no longer in the list
     *
     * @param list<int|string> $objectIds
     */
    public function updateSongMap(array $objectIds, string $objectType, int $songId): void
    {
        if ($objectIds === []) {
            $this->connection->query(
                'DELETE FROM `song_map` WHERE `song_id` = ? AND `object_type` = ?;',
                [$songId, $objectType]
            );

            return;
        }

        foreach ($objectIds as $objectId) {
            $this->connection->query(
                'REPLACE INTO `song_map` (`song_id`, `object_type`, `object_id`) VALUES (?, ?, ?);',
                [$songId, $objectType, $objectId]
            );
        }

        // we only want the latest values in the map, so anything not in the new list goes
        $this->connection->query(
            sprintf(
                'DELETE FROM `song_map` WHERE `song_id` = ? AND `object_type` = ? AND `object_id` NOT IN (%s);',
                implode(',', array_fill(0, count($objectIds), '?'))
            ),
            array_merge([$songId, $objectType], $objectIds)
        );
    }

    /**
     * Stamps a song as read from its file
     */
    public function updateUpdateTime(int $songId, int $time): void
    {
        $this->connection->query(
            'UPDATE `song` SET `update_time` = ? WHERE `id` = ?;',
            [$time, $songId]
        );
    }

    /**
     * Builds the LIMIT clause for a paged read, where an offset with no size runs to the end of the result
     */
    private function limitClause(int $size, int $offset): string
    {
        if ($offset > 0 && $size > 0) {
            return sprintf('LIMIT %d, %d', $offset, $size);
        }

        if ($size > 0) {
            return 'LIMIT ' . $size;
        }

        // MySQL has no notation for the last row, so an open-ended offset takes the largest possible BIGINT
        return ($offset > 0)
            ? sprintf('LIMIT %d, 18446744073709551615', $offset)
            : '';
    }

    /**
     * Reads the id, file and update time of a verify page
     *
     * @return list<array{id: int, file: string, min_update_time: int}>
     */
    private function readVerifyRows(string $sql, int $catalogId): array
    {
        $result = $this->connection->query($sql, [$catalogId]);

        $rows = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = [
                'id' => (int) $row['id'],
                'file' => (string) $row['file'],
                'min_update_time' => (int) $row['min_update_time'],
            ];
        }

        return $rows;
    }
}
