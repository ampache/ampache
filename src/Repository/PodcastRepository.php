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
use Ampache\Repository\Model\Podcast;
use Ampache\Repository\Model\PodcastInterface;
use Doctrine\DBAL\Connection;

final class PodcastRepository implements PodcastRepositoryInterface
{
    private Connection $connection;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        Connection $connection,
        ModelFactoryInterface $modelFactory
    ) {
        $this->connection   = $connection;
        $this->modelFactory = $modelFactory;
    }

    /**
     * This returns an array of ids of podcasts in this catalog
     *
     * @return int[]
     */
    public function getPodcastIds(
        int $catalogId
    ): array {
        $result = $this->connection->executeQuery(
            'SELECT `id` FROM `podcast` WHERE `catalog` = ?',
            [$catalogId]
        );
        $podcastIds = [];

        while ($row = $result->fetchOne()) {
            $podcastIds[] = (int) $row;
        }

        return $podcastIds;
    }

    public function remove(
        PodcastInterface $podcast
    ): bool {
        $result = $this->connection->executeQuery(
            'DELETE FROM `podcast` WHERE `id` = ?',
            [$podcast->getId()]
        );

        return $result->rowCount() !== 0;
    }

    public function updateLastsync(
        Podcast $podcast,
        int $time
    ): void {
        $this->connection->executeQuery(
            'UPDATE `podcast` SET `lastsync` = ? WHERE `id` = ?',
            [$time, $podcast->getId()]
        );
    }

    public function update(
        int $podcastId,
        string $feed,
        string $title,
        string $website,
        string $description,
        string $generator,
        string $copyright
    ): void {
        $this->connection->executeQuery(
            'UPDATE `podcast` SET `feed` = ?, `title` = ?, `website` = ?, `description` = ?, `generator` = ?, `copyright` = ? WHERE `id` = ?',
            [$feed, $title, $website, $description, $generator, $copyright, $podcastId]
        );
    }

    public function insert(
        string $feedUrl,
        int $catalogId,
        string $title,
        string $website,
        string $description,
        string $language,
        string $copyright,
        string $generator,
        int $lastBuildDate
    ): ?int {
        $sql = <<<SQL
        INSERT INTO
            `podcast`
            (`feed`, `catalog`, `title`, `website`, `description`, `language`, `copyright`, `generator`, `lastbuilddate`)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?)
        SQL;

        $result = $this->connection->executeQuery(
            $sql,
            [
                $feedUrl,
                $catalogId,
                $title,
                $website,
                $description,
                $language,
                $copyright,
                $generator,
                $lastBuildDate
            ]
        );

        if ($result->rowCount() === 0) {
            return null;
        }

        return (int) $this->connection->lastInsertId();
    }

    /**
     * Looks for existing podcast having a certain feed url to detect duplicated
     */
    public function findByFeedUrl(
        string $feedUrl
    ): ?int {
        $result = $this->connection->executeQuery(
            'SELECT `id` FROM `podcast` WHERE `feed`= ?',
            [$feedUrl]
        );
        $podcastId = $result->fetchOne();

        if ($podcastId === false) {
            return null;
        }

        return (int) $podcastId;
    }

    public function findById(int $id): ?PodcastInterface
    {
        $podcast = $this->modelFactory->createPodcast($id);
        if ($podcast->isNew()) {
            return null;
        }

        return $podcast;
    }
}
