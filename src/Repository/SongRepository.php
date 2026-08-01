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
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\SongDataFieldEnum;
use Ampache\Repository\Model\SongFieldEnum;
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
     * gets the songs (including songs where they are the album artist) for this artist
     *
     * @return int[]
     */
    public function getAllByArtist(
        int $artistId,
    ): array {
        $user_id = Core::get_global('user')?->getId();
        $sql     = (AmpConfig::get('catalog_disable') || AmpConfig::get('catalog_filter'))
            ? "SELECT DISTINCT `song`.`id`, `song`.`album`, `song`.`disk`, `song`.`track` FROM `song` LEFT JOIN `album` ON `song`.`album` = `album`.`id` LEFT JOIN `album_map` ON `album_map`.`album_id` = `album`.`id` WHERE `album_map`.`object_id` = ? AND `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $user_id, true)) . ") ORDER BY `song`.`album`, `song`.`disk`, `song`.`track`;"
            : "SELECT DISTINCT `song`.`id`, `song`.`album`, `song`.`disk`, `song`.`track` FROM `song` LEFT JOIN `album` ON `song`.`album` = `album`.`id` LEFT JOIN `album_map` ON `album_map`.`album_id` = `album`.`id` WHERE `album_map`.`object_id` = ? ORDER BY `song`.`album`, `song`.`disk`, `song`.`track`;";

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
            ? "SELECT DISTINCT `song`.`id`, `song`.`album`, `song`.`disk`, `song`.`track` FROM `song` LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `song`.`id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_type` = 'song' AND `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $user_id, true)) . ") ORDER BY `song`.`album`, `song`.`disk`, `song`.`track`"
            : "SELECT DISTINCT `song`.`id`, `song`.`album`, `song`.`disk`, `song`.`track` FROM `song` LEFT JOIN `artist_map` ON `artist_map`.`object_id` = `song`.`id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_type` = 'song' ORDER BY `song`.`album`, `song`.`disk`, `song`.`track`";

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
            ? "SELECT `song`.`id` FROM `song` LEFT JOIN `folder` ON `folder`.`id` = `song`.`folder` WHERE `folder`.`name` = ? AND `folder`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $user_id, true)) . ") ORDER BY `song`.`album`, `song`.`disk`, `song`.`track`"
            : "SELECT `song`.`id` FROM `song` LEFT JOIN `folder` ON `folder`.`id` = `song`.`folder` WHERE `folder`.`name` = ? ORDER BY `song`.`album`, `song`.`disk`, `song`.`track`";

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
            ? "SELECT `song`.`id` FROM `song` LEFT JOIN `song_data` ON `song_data`.`song_id` = `song`.`id` WHERE `song_data`.`label` = ? AND `song`.`catalog` IN (" . implode(',', Catalog::get_catalogs('', $user_id, true)) . ") ORDER BY `song`.`album`, `song`.`disk`, `song`.`track`"
            : "SELECT `song`.`id` FROM `song` LEFT JOIN `song_data` ON `song_data`.`song_id` = `song`.`id` WHERE `song_data`.`label` = ? ORDER BY `song`.`album`, `song`.`disk`, `song`.`track`";

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
}
