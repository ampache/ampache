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

use Ampache\Module\Catalog\CatalogMapTableEnum;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Module\System\LegacyLogger;
use Psr\Log\LoggerInterface;

final readonly class CatalogMapRepository implements CatalogMapRepositoryInterface
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
        private LoggerInterface $logger,
    ) {}

    public function add(int $catalogId, string $objectType, int $objectId): void
    {
        $this->connection->query(
            'INSERT IGNORE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) VALUES (?, ?, ?);',
            [$catalogId, $objectType, $objectId]
        );
    }

    public function addForArtist(int $artistId): void
    {
        $this->connection->query(
            "INSERT IGNORE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT `catalog_id`, `map_type`, `object_id` FROM (SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` LEFT JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_id` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` LEFT JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_id` IS NOT NULL UNION SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'song_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `song` LEFT JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_id` IS NOT NULL UNION SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'album_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id` FROM `album` LEFT JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`artist_id` = ? AND `artist_map`.`object_id` IS NOT NULL) AS artist_mapping GROUP BY `catalog_id`, `map_type`, `object_id`;",
            [$artistId, $artistId, $artistId, $artistId]
        );
    }

    public function collectGarbage(array $tables): void
    {
        $statements = [];
        foreach ($tables as $table) {
            if ($table === CatalogMapTableEnum::ARTIST) {
                // an artist row is derived from the two role rows, so the roles are swept first and the artist from them
                $statements[] = "DELETE FROM `catalog_map` WHERE `object_type` = 'album_artist' AND `object_id` NOT IN (SELECT `object_id` FROM (SELECT `artist_map`.`artist_id` AS `object_id` FROM `album` INNER JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album' WHERE `artist_map`.`object_type` IS NOT NULL) AS orphanalbumartist);";
                $statements[] = "DELETE FROM `catalog_map` WHERE `object_type` = 'song_artist' AND `object_id` NOT IN (SELECT `object_id` FROM (SELECT `artist_map`.`artist_id` AS `object_id` FROM `song` INNER JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song' WHERE `artist_map`.`object_type` IS NOT NULL) AS orphansongartist);";
                $statements[] = "DELETE FROM `catalog_map` WHERE `object_type` = 'artist' AND `object_id` NOT IN (SELECT `object_id` FROM (SELECT `object_id` FROM `catalog_map` WHERE `object_type` IN ('song_artist', 'album_artist')) AS orphanartist);";

                continue;
            }

            $statements[] = sprintf(
                'DELETE FROM `catalog_map` USING `catalog_map` LEFT JOIN (SELECT DISTINCT `%s`.`catalog` AS `catalog_id`, `%s`.`id` AS `object_id` FROM `%s`) AS `valid_maps` ON `valid_maps`.`catalog_id` = `catalog_map`.`catalog_id` AND `valid_maps`.`object_id` = `catalog_map`.`object_id` WHERE `catalog_map`.`object_type` = \'%s\' AND `valid_maps`.`object_id` IS NULL;',
                $table->value,
                $table->value,
                $table->value,
                $table->value
            );
        }

        $statements[] = 'DELETE FROM `catalog_map` WHERE `catalog_id` = 0';

        // one table that cannot be swept must not take the rest of the sweep down with it
        foreach ($statements as $statement) {
            try {
                $this->connection->query($statement);
            } catch (DatabaseException) {
                $this->logger->debug(
                    'collectGarbage error: ' . $statement,
                    [LegacyLogger::CONTEXT_TYPE => self::class]
                );
            }
        }
    }

    public function deleteForObject(string $objectType, int $objectId): void
    {
        $this->connection->query(
            'DELETE FROM `catalog_map` WHERE `object_id` = ? AND `object_type` = ?',
            [$objectId, $objectType]
        );
    }

    public function migrate(string $objectType, int $oldObjectId, int $newObjectId): bool
    {
        try {
            $this->connection->query(
                'UPDATE IGNORE `catalog_map` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?',
                [$newObjectId, $objectType, $oldObjectId]
            );
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }

    public function rebuild(CatalogMapTableEnum $table): void
    {
        $sql = match ($table) {
            CatalogMapTableEnum::ARTIST => <<<SQL
                INSERT IGNORE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`)
                SELECT `catalog_id`, `map_type`, `object_id`
                FROM (
                    SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id`
                    FROM `song`
                        LEFT JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song'
                    WHERE `artist_map`.`object_id` IS NOT NULL
                    UNION
                    SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id`
                    FROM `album`
                        LEFT JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album'
                    WHERE `artist_map`.`object_id` IS NOT NULL
                    UNION
                    SELECT DISTINCT `song`.`catalog` AS `catalog_id`, 'song_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id`
                    FROM `song`
                        LEFT JOIN `artist_map` ON `song`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'song'
                    WHERE `artist_map`.`object_id` IS NOT NULL
                    UNION
                    SELECT DISTINCT `album`.`catalog` AS `catalog_id`, 'album_artist' AS `map_type`, `artist_map`.`artist_id` AS `object_id`
                    FROM `album`
                        LEFT JOIN `artist_map` ON `album`.`id` = `artist_map`.`object_id` AND `artist_map`.`object_type` = 'album'
                    WHERE `artist_map`.`object_id` IS NOT NULL
                ) AS full_mapping
                GROUP BY `catalog_id`, `map_type`, `object_id`;
                SQL,
            CatalogMapTableEnum::PLAYLIST => "INSERT IGNORE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT `song`.`catalog`, 'playlist', `playlist`.`id` FROM `playlist` LEFT JOIN `playlist_data` ON `playlist`.`id`=`playlist_data`.`playlist` LEFT JOIN `song` ON `song`.`id` = `playlist_data`.`object_id` AND `playlist_data`.`object_type` = 'song' GROUP BY `song`.`catalog`, 'playlist', `playlist`.`id`;",
            default => sprintf(
                'INSERT IGNORE INTO `catalog_map` (`catalog_id`, `object_type`, `object_id`) SELECT `%s`.`catalog`, \'%s\', `%s`.`id` FROM `%s` GROUP BY `%s`.`catalog`, \'%s\', `%s`.`id`;',
                $table->value,
                $table->value,
                $table->value,
                $table->value,
                $table->value,
                $table->value,
                $table->value
            ),
        };

        $this->connection->query($sql);
    }

    /**
     * Points an object's mapping at another catalog, for media that moved between them
     */
    public function setCatalog(string $objectType, int $objectId, int $catalogId): bool
    {
        try {
            $this->connection->query(
                'UPDATE `catalog_map` SET `catalog_id` = ? WHERE `object_type` = ? AND `object_id` = ?;',
                [$catalogId, $objectType, $objectId]
            );
        } catch (DatabaseException) {
            return false;
        }

        return true;
    }
}
