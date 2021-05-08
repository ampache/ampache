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

use Ampache\Repository\Model\ModelFactoryInterface;
use Doctrine\DBAL\Connection;

final class WantedRepository implements WantedRepositoryInterface
{
    private ModelFactoryInterface $modelFactory;

    private Connection $database;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        Connection $database
    ) {
        $this->modelFactory = $modelFactory;
        $this->database     = $database;
    }

    /**
     * Get wanted list.
     *
     * @return int[]
     */
    public function getAll(?int $userId): array
    {
        $sql    = 'SELECT `id` FROM `wanted`';
        $params = [];

        if ($userId !== null) {
            $sql .= ' WHERE `user` = ?';
            $params[] = $userId;
        }

        $dbResults = $this->database->executeQuery(
            $sql,
            $params
        );

        $results = [];

        while ($rowId = $dbResults->fetchOne()) {
            $results[] = (int) $rowId;
        }

        return $results;
    }

    /**
     * Check if a release mbid is already marked as wanted
     */
    public function find(string $musicbrainzId, int $userId): ?int
    {
        $dbResults = $this->database->executeQuery(
            'SELECT `id` FROM `wanted` WHERE `mbid` = ? AND `user` = ?',
            [$musicbrainzId, $userId]
        );

        $result = $dbResults->fetchOne();
        if ($result !== false) {
            return (int) $result;
        }

        return null;
    }

    /**
     * Delete wanted release.
     */
    public function deleteByMusicbrainzId(
        string $musicbrainzId,
        ?int $userId
    ): void {
        $sql    = 'DELETE FROM `wanted` WHERE `mbid` = ?';
        $params = [$musicbrainzId];
        if ($userId !== null) {
            $sql .= ' AND `user` = ?';
            $params[] = $userId;
        }

        $this->database->executeQuery(
            $sql,
            $params
        );
    }

    /**
     * Get accepted wanted release count.
     */
    public function getAcceptedCount(): int
    {
        $dbResults = $this->database->executeQuery(
            'SELECT COUNT(`id`) FROM `wanted` WHERE `accepted` = 1'
        );

        return (int) $dbResults->fetchOne();
    }

    /**
     * retrieves the info from the database and puts it in the cache
     *
     * @return array<string, mixed>
     */
    public function getById(int $wantedId): array
    {
        // Make sure we've got a real id
        if ($wantedId < 1) {
            return [];
        }

        $db_results = $this->database->executeQuery(
            'SELECT * FROM `wanted` WHERE `id`= ?',
            [$wantedId]
        );

        $row = $db_results->fetchAssociative();
        if ($row === false) {
            return [];
        }

        return $row;
    }

    /**
     * Adds a new wanted entry
     */
    public function add(
        string $mbid,
        int $artistId,
        string $artistMbid,
        string $name,
        int $year,
        int $userId,
        bool $accept
    ): void {
        $this->database->executeQuery(
            'INSERT INTO `wanted` (`user`, `artist`, `artist_mbid`, `mbid`, `name`, `year`, `date`, `accepted`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$userId, $artistId, $artistMbid, $mbid, $name, $year, time(), '0']
        );

        if ($accept) {
            $wantedId = (int) $this->database->lastInsertId();
            $wanted   = $this->modelFactory->createWanted($wantedId);
            $wanted->accept();
        }
    }

    /**
     * Get wanted release by mbid.
     */
    public function getByMusicbrainzId(string $musicbrainzId): int
    {
        $dbResults = $this->database->executeQuery(
            'SELECT `id` FROM `wanted` WHERE `mbid` = ?',
            [$musicbrainzId]
        );

        return (int) $dbResults->fetchOne();
    }
}
