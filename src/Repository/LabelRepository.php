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

use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Module\Database\Exception\DatabaseException;
use Ampache\Module\System\LegacyLogger;
use Ampache\Repository\Model\Label;
use DateTimeInterface;
use PDO;
use Psr\Log\LoggerInterface;

final readonly class LabelRepository implements LabelRepositoryInterface
{
    public function __construct(
        private DatabaseConnectionInterface $connection,
        private LoggerInterface $logger,
    ) {}

    /**
     * Associate a label with an album, ignoring a pairing already recorded (the scanner runs this per song).
     */
    public function addAlbumAssoc(int $labelId, int $albumId, DateTimeInterface $date): void
    {
        $existing = $this->connection->fetchOne(
            'SELECT `id` FROM `label_asso` WHERE `label` = ? AND `album` = ?',
            [$labelId, $albumId]
        );

        if ($existing) {
            return;
        }

        $this->connection->query(
            'INSERT INTO `label_asso` (`label`, `album`, `creation_date`) VALUES (?, ?, ?)',
            [$labelId, $albumId, $date->getTimestamp()]
        );
    }

    public function addArtistAssoc(int $labelId, int $artistId, DateTimeInterface $date): void
    {
        $this->connection->query(
            'INSERT INTO `label_asso` (`label`, `artist`, `creation_date`) VALUES (?, ?, ?)',
            [$labelId, $artistId, $date->getTimestamp()]
        );
    }

    /**
     * This cleans out unused labels
     */
    public function collectGarbage(): void
    {
        try {
            // A row links a label to one side only, so each side is swept against its own table
            $this->connection->query('DELETE FROM `label_asso` WHERE `label_asso`.`artist` IS NOT NULL AND `label_asso`.`artist` NOT IN (SELECT `artist`.`id` FROM `artist`)');
            $this->connection->query('DELETE FROM `label_asso` WHERE `label_asso`.`album` IS NOT NULL AND `label_asso`.`album` NOT IN (SELECT `album`.`id` FROM `album`)');
            $this->connection->query('DELETE FROM `label_asso` WHERE `label_asso`.`label` NOT IN (SELECT `label`.`id` FROM `label`)');
            $this->connection->query('DELETE FROM `label` WHERE `id` NOT IN (SELECT `label` FROM `label_asso`) AND `user` IS NULL');
        } catch (DatabaseException) {
            $this->logger->debug(
                'collectGarbage error',
                [LegacyLogger::CONTEXT_TYPE => self::class]
            );
        }
    }

    public function delete(int $labelId): void
    {
        $this->connection->query(
            'DELETE FROM `label` WHERE `id` = ?',
            [$labelId]
        );
    }

    public function findById(int $labelId): ?Label
    {
        $label = new Label($labelId);
        if ($label->isNew()) {
            return null;
        }

        return $label;
    }

    /**
     * Returns the ids of every album associated with the label
     *
     * @return int[]
     */
    public function getAlbums(Label $label): array
    {
        $result = $this->connection->query(
            'SELECT `album` FROM `label_asso` WHERE `label` = ? AND `album` IS NOT NULL',
            [$label->getId()]
        );

        $results = [];
        while ($rowId = $result->fetchColumn()) {
            $results[] = (int) $rowId;
        }

        return $results;
    }

    /**
     * Return the list of all available labels
     *
     * @return string[]
     */
    public function getAll(): array
    {
        $result = $this->connection->query('SELECT `id`, `name` FROM `label`');

        $labels = [];

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $labels[(int) $row['id']] = $row['name'];
        }

        return $labels;
    }

    /**
     * Returns the ids of every artist associated with the label
     *
     * @return int[]
     */
    public function getArtists(Label $label): array
    {
        // an artist row is only one side of the table, and a null column would end the fetch loop early
        $result = $this->connection->query(
            'SELECT `artist` FROM `label_asso` WHERE `label` = ? AND `artist` IS NOT NULL',
            [$label->getId()]
        );

        $results = [];
        while ($rowId = $result->fetchColumn()) {
            $results[] = (int) $rowId;
        }

        return $results;
    }

    /**
     * The labels associated with an album, keyed by label id
     *
     * @return array<int, string>
     */
    public function getByAlbum(int $albumId): array
    {
        $labels = [];

        $result = $this->connection->query(
            'SELECT `label`.`id`, `label`.`name` FROM `label` LEFT JOIN `label_asso` ON `label_asso`.`label` = `label`.`id` WHERE `label_asso`.`album` = ?',
            [$albumId]
        );

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $labels[(int) $row['id']] = $row['name'];
        }

        return $labels;
    }

    /**
     * @return string[]
     */
    public function getByArtist(int $artistId): array
    {
        $labels = [];

        $result = $this->connection->query(
            'SELECT `label`.`id`, `label`.`name` FROM `label` LEFT JOIN `label_asso` ON `label_asso`.`label` = `label`.`id` WHERE `label_asso`.`artist` = ?',
            [$artistId]
        );

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $labels[(int) $row['id']] = $row['name'];
        }

        return $labels;
    }

    public function lookup(string $labelName, int $labelId = 0): int
    {
        $ret  = -1;
        $name = trim($labelName);

        if ($name !== '') {
            $ret    = 0;
            $sql    = 'SELECT `id` FROM `label` WHERE `name` = ?';
            $params = [$name];
            if ($labelId > 0) {
                $sql .= ' AND `id` != ?';
                $params[] = $labelId;
            }

            $result = $this->connection->fetchOne($sql, $params);

            if ($result !== false) {
                $ret = (int) $result;
            }
        }

        return $ret;
    }

    /**
     * Moves every album association from one album onto another
     */
    public function migrateAlbum(int $oldAlbumId, int $newAlbumId): void
    {
        // the target album may already carry the label, and moving the row on top of it would duplicate the pairing
        $this->connection->query(
            'DELETE FROM `label_asso` WHERE `album` = ? AND `label` IN (SELECT `label` FROM (SELECT `label` FROM `label_asso` WHERE `album` = ?) AS `existing`)',
            [$oldAlbumId, $newAlbumId]
        );

        $this->connection->query(
            'UPDATE `label_asso` SET `album` = ? WHERE `album` = ?',
            [$newAlbumId, $oldAlbumId]
        );
    }

    /**
     * Moves every artist association from one artist onto another
     */
    public function migrateArtist(int $oldArtistId, int $newArtistId): void
    {
        $this->connection->query(
            'UPDATE `label_asso` SET `artist` = ? WHERE `artist` = ?',
            [$newArtistId, $oldArtistId]
        );
    }

    /**
     * Saves the label, inserting it when it is new
     *
     * Returns the id of a newly created label, null when an existing one was updated
     */
    public function persist(Label $label): ?int
    {
        if (!$label->isNew()) {
            $this->connection->query(
                'UPDATE `label` SET `name` = ?, `mbid` = ?, `category` = ?, `summary` = ?, `address` = ?, `country` = ?, `email` = ?, `website` = ?, `active` = ? WHERE `id` = ?',
                [
                    $label->name,
                    $label->mbid,
                    $label->category,
                    $label->summary,
                    $label->address,
                    $label->country,
                    $label->email,
                    $label->website,
                    ($label->active) ? 1 : 0,
                    $label->getId(),
                ]
            );

            return null;
        }

        $this->connection->query(
            'INSERT INTO `label` (`name`, `mbid`, `category`, `summary`, `address`, `country`, `email`, `website`, `user`, `active`, `creation_date`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $label->name,
                $label->mbid,
                $label->category,
                $label->summary,
                $label->address,
                $label->country,
                $label->email,
                $label->website,
                $label->user,
                ($label->active) ? 1 : 0,
                $label->creation_date,
            ]
        );

        return $this->connection->getLastInsertedId() ?: null;
    }

    public function removeArtistAssoc(int $labelId, int $artistId): void
    {
        $this->connection->query(
            'DELETE FROM `label_asso` WHERE `label` = ? AND `artist` = ?',
            [$labelId, $artistId]
        );
    }
}
