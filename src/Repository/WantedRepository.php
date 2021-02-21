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

use Ampache\Module\Cache\DatabaseObjectCacheInterface;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\ModelFactoryInterface;

final class WantedRepository implements WantedRepositoryInterface
{
    private DatabaseObjectCacheInterface $databaseObjectCache;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        DatabaseObjectCacheInterface $databaseObjectCache,
        ModelFactoryInterface $modelFactory
    ) {
        $this->databaseObjectCache = $databaseObjectCache;
        $this->modelFactory        = $modelFactory;
    }

    /**
     * Get wanted list.
     *
     * @return int[]
     */
    public function getAll(?int $userId): array
    {
        $sql = "SELECT `id` FROM `wanted` ";

        if ($userId !== null) {
            $sql .= "WHERE `user` = '" . (string) $userId . "'";
        }
        $db_results = Dba::read($sql);
        $results    = array();

        while ($row = Dba::fetch_assoc($db_results)) {
            $results[] = (int) $row['id'];
        }

        return $results;
    }

    /**
     * Check if a release mbid is already marked as wanted
     */
    public function find(string $musicbrainzId, int $userId): ?int
    {
        $db_results = Dba::read(
            'SELECT `id` FROM `wanted` WHERE `mbid` = ? AND `user` = ?',
            [$musicbrainzId, $userId]
        );

        if ($row = Dba::fetch_assoc($db_results)) {
            return (int) $row['id'];
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
        $sql    = "DELETE FROM `wanted` WHERE `mbid` = ?";
        $params = [$musicbrainzId];
        if ($userId !== null) {
            $sql .= " AND `user` = ?";
            $params[] = $userId;
        }

        Dba::write($sql, $params);
    }

    /**
     * Get accepted wanted release count.
     */
    public function getAcceptedCount(): int
    {
        $db_results = Dba::read(
            'SELECT COUNT(`id`) AS `wanted_cnt` FROM `wanted` WHERE `accepted` = 1'
        );
        if ($row = Dba::fetch_assoc($db_results)) {
            return (int) $row['wanted_cnt'];
        }

        return 0;
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

        $cacheItem = $this->databaseObjectCache->retrieve('wanted', $wantedId);

        if ($cacheItem !== []) {
            return $cacheItem;
        }

        $params     = [$wantedId];
        $sql        = "SELECT * FROM `wanted` WHERE `id`= ?";
        $db_results = Dba::read($sql, $params);

        if (!$db_results) {
            return [];
        }

        $row = Dba::fetch_assoc($db_results);

        $this->databaseObjectCache->add('wanted', $wantedId, $row);

        return $row;
    }

    /**
     * Adds a new wanted entry
     */
    public function add(
        string $mbid,
        int $artist,
        string $artist_mbid,
        string $name,
        int $year,
        int $userId,
        bool $accept
    ): void {
        $sql    = "INSERT INTO `wanted` (`user`, `artist`, `artist_mbid`, `mbid`, `name`, `year`, `date`, `accepted`) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $params = array($userId, $artist, $artist_mbid, $mbid, $name, $year, time(), '0');
        Dba::write($sql, $params);

        if ($accept) {
            $wanted_id = (int)Dba::insert_id();
            $wanted    = $this->modelFactory->createWanted($wanted_id);
            $wanted->accept();

            $this->databaseObjectCache->remove('wanted', $wanted_id);
        }
    }

    /**
     * Get wanted release by mbid.
     */
    public function getByMusicbrainzId(string $musicbrainzId): int
    {
        $sql        = "SELECT `id` FROM `wanted` WHERE `mbid` = ?";
        $db_results = Dba::read($sql, array($musicbrainzId));
        if ($row = Dba::fetch_assoc($db_results)) {
            return (int) $row['id'];
        }

        return 0;
    }
}
