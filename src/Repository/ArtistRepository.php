<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 */

declare(strict_types=1);

namespace Ampache\Repository;

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\ModelFactoryInterface;
use Generator;

final class ArtistRepository implements ArtistRepositoryInterface
{
    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory
    ) {
        $this->configContainer = $configContainer;
        $this->modelFactory    = $modelFactory;
    }

    /**
     * Deletes the artist entry
     */
    public function delete(
        int $artistId
    ): bool {
        $result = Dba::write(
            'DELETE FROM `artist` WHERE `id` = ?',
            [$artistId]
        );

        return $result !== false;
    }

    /**
     * This returns a number of random artists.
     *
     * @return int[]
     */
    public function getRandom(
        int $userId,
        int $count = 1
    ): array {
        $results = array();

        if (!$count) {
            $count = 1;
        }

        $sql = "SELECT DISTINCT `artist`.`id` FROM `artist` " . "LEFT JOIN `song` ON `song`.`artist` = `artist`.`id` ";
        if (AmpConfig::get('catalog_disable')) {
            $sql .= "LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog` ";
            $where = "WHERE `catalog`.`enabled` = '1' ";
        } else {
            $where = "WHERE 1=1 ";
        }
        $sql .= $where;

        $rating_filter = AmpConfig::get_rating_filter();
        if ($rating_filter > 0 && $rating_filter <= 5) {
            $sql .= " AND `artist`.`id` NOT IN" .
                " (SELECT `object_id` FROM `rating`" .
                " WHERE `rating`.`object_type` = 'artist'" .
                " AND `rating`.`rating` <=" . $rating_filter .
                " AND `rating`.`user` = " . $userId . ") ";
        }

        $sql .= "ORDER BY RAND() LIMIT " . (string)$count;
        $db_results = Dba::read($sql);

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * Get time for an artist's songs.
     */
    public function getDuration(Artist $artist): int
    {
        $params     = array($artist->getId());
        $sql        = "SELECT SUM(`song`.`time`) AS `time` from `song` WHERE `song`.`artist` = ?";
        $db_results = Dba::read($sql, $params);
        $results    = Dba::fetch_assoc($db_results);
        // album artists that don't have any songs
        if ((int) $results['time'] == 0) {
            $sql        = "SELECT SUM(`album`.`time`) AS `time` from `album` WHERE `album`.`album_artist` = ?";
            $db_results = Dba::read($sql, $params);
            $results    = Dba::fetch_assoc($db_results);
        }

        return (int) $results['time'];
    }

    /**
     * This gets an artist object based on the artist name
     */
    public function findByName(string $name): Artist
    {
        $dbResults = Dba::read(
            'SELECT `id` FROM `artist` WHERE `name` = ? OR LTRIM(CONCAT(COALESCE(`artist`.`prefix`, \'\'), \' \', `artist`.`name`)) = ? ',
            [$name, $name]
        );

        $row = Dba::fetch_assoc($dbResults);

        return $this->modelFactory->createArtist((int) ($row['id'] ?? 0));
    }

    /**
     * This cleans out unused artists
     */
    public function collectGarbage(): void
    {
        Dba::write('DELETE FROM `artist` USING `artist` LEFT JOIN `song` ON `song`.`artist` = `artist`.`id` LEFT JOIN `album` ON `album`.`album_artist` = `artist`.`id` ' . 'LEFT JOIN `wanted` ON `wanted`.`artist` = `artist`.`id` ' . 'LEFT JOIN `clip` ON `clip`.`artist` = `artist`.`id` ' . 'WHERE `song`.`id` IS NULL AND `album`.`id` IS NULL AND `wanted`.`id` IS NULL AND `clip`.`id` IS NULL');
    }

    /**
     * Update artist associated user.
     */
    public function updateArtistUser(Artist $artist, int $user): void
    {
        Dba::write(
            'UPDATE `artist` SET `user` = ? WHERE `id` = ?',
            [$user, $artist->getId()]
        );
    }

    /**
     * Update artist last_update time.
     */
    public function updateLastUpdate(int $artistId): void
    {
        Dba::write(
            'UPDATE `artist` SET `last_update` = ? WHERE `id` = ?',
            [time(), $artistId]
        );
    }

    public function updateAlbumCount(Artist $artist, int $count): void
    {
        Dba::write(
            'UPDATE `artist` SET `album_count` = ? WHERE `id` = ?',
            [$count, $artist->getId()]
        );
    }

    public function updateAlbumGroupCount(Artist $artist, int $count): void
    {
        Dba::write(
            'UPDATE `artist` SET `album_group_count`= ? WHERE `id` = ?',
            [$count, $artist->getId()]
        );
    }

    public function updateSongCount(Artist $artist, int $count): void
    {
        Dba::write(
            'UPDATE `artist` SET `song_count` = ? WHERE `id` = ?',
            [$count, $artist->getId()]
        );
    }

    public function updateTime(Artist $artist, int $time): void
    {
        Dba::write(
            'UPDATE `artist` SET `time` = ? WHERE `id` = ?',
            [$time, $artist->getId()]
        );
    }

    public function updateArtistInfo(
        Artist $artist,
        string $summary,
        string $placeformed,
        int $yearformed,
        bool $manual = false
    ): void {
        Dba::write(
            'UPDATE `artist` SET `summary` = ?, `placeformed` = ?, `yearformed` = ?, `last_update` = ?, `manual_update` = ? WHERE `id` = ?',
            [$summary, $placeformed, $yearformed, time(), $manual ? 1 : 0, $artist->getId()]
        );
    }

    /**
     * @return Generator<array<string, mixed>>
     */
    public function getByIdList(
        array $idList
    ): Generator {
        if ($idList === []) {
            return [];
        }
        $db_results = Dba::read(
            'SELECT * FROM `artist` WHERE `id` IN (?)',
            [implode(',', $idList)]
        );

        while ($row = Dba::fetch_assoc($db_results)) {
            yield $row;
        }
    }

    /**
     * Get each id from the artist table with the minimum detail required for subsonic
     * @param int[] $catalogIds
     * @return array
     */
    public function getSubsonicRelatedDataByCatalogs(array $catalogIds = []): array
    {
        $group_column = $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALBUM_GROUP) ? '`artist`.`album_group_count`' : '`artist`.`album_count`';
        if ($catalogIds !== []) {
            $sql = <<<SQL
            SELECT
                DISTINCT `artist`.`id`,
                LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `full_name`,
                `artist`.`name`,
                %s AS `album_count`,
                `artist`.`song_count`
            FROM `song`
            LEFT JOIN `catalog` ON `catalog`.`id` = `song`.`catalog`
            LEFT JOIN `artist` ON `artist`.`id` = `song`.`artist`
            WHERE `song`.`catalog` = ? ORDER BY `artist`.`name`
            SQL;

            $db_results = Dba::read(
                sprintf($sql, $group_column),
                $catalogIds
            );
        } else {
            $sql = <<<SQL
            SELECT DISTINCT
                `artist`.`id`,
                LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `full_name`,
                `artist`.`name`,
                %s AS `album_count`,
                `artist`.`song_count`
                FROM `artist` ORDER BY `artist`.`name`
            SQL;

            $db_results = Dba::read(
                sprintf($sql, $group_column)
            );
        }
        $results = [];

        while ($row = Dba::fetch_assoc($db_results, false)) {
            $results[] = $row;
        }

        return $results;
    }

    /**
     * Get info from the artist table with the minimum detail required for subsonic
     *
     * @return array{id: int, full_name: string, name: string, album_count: int, song_count: int}
     */
    public function getSubsonicRelatedDataByArtist(int $artistId): array
    {
        $group_column = $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALBUM_GROUP) ? '`artist`.`album_group_count`' : '`artist`.`album_count`';

        $sql = <<<SQL
        SELECT
            DISTINCT `artist`.`id`,
            LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) AS `full_name`,
            `artist`.`name`,
            %s AS `album_count`,
            `artist`.`song_count`
        FROM `artist` WHERE `artist`.`id` = ? ORDER BY `artist`.`name`
        SQL;

        $db_results = Dba::read(
            sprintf($sql, $group_column),
            [$artistId]
        );

        return Dba::fetch_assoc($db_results, false);
    }
}
