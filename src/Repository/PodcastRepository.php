<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;
use Generator;

/**
 * Manages podcast related database access
 *
 * Tables: `podcast`, `podcast_episode`, `deleted_podcast_episodes`
 */
final readonly class PodcastRepository implements PodcastRepositoryInterface
{
    public function __construct(private ModelFactoryInterface $modelFactory, private DatabaseConnectionInterface $connection)
    {
    }

    /**
     * Retrieve all podcast objects and maintain db-order
     *
     * @return Generator<Podcast>
     */
    public function findAll(): Generator
    {
        $result = $this->connection->query(
            'SELECT `id` FROM `podcast`',
        );

        while ($podcastId = $result->fetchColumn()) {
            yield $this->modelFactory->createPodcast((int) $podcastId);
        }
    }

    /**
     * Searches for an existing podcast object by the feed url
     */
    public function findByFeedUrl(
        string $feedUrl
    ): ?Podcast {
        $podcastId = $this->connection->fetchOne(
            'SELECT `id` FROM `podcast` WHERE `feed` = ?',
            [
                $feedUrl
            ]
        );

        if ($podcastId !== false) {
            return $this->modelFactory->createPodcast((int) $podcastId);
        }

        return null;
    }

    /**
     * Deletes a podcast
     */
    public function delete(Podcast $podcast): void
    {
        $this->connection->query(
            'DELETE FROM `podcast` WHERE `id` = ?',
            [$podcast->getId()]
        );
    }

    /**
     * Returns a new podcast item
     */
    public function prototype(): Podcast
    {
        return new Podcast();
    }

    /**
     * Persists the podcast-item in the database
     *
     * If the item is new, it will be created. Otherwise, an update will happen
     *
     * @return null|non-negative-int
     */
    public function persist(Podcast $podcast): ?int
    {
        $result = null;

        if ($podcast->isNew()) {
            $this->connection->query(
                'INSERT INTO `podcast` (`catalog`, `feed`, `title`, `website`, `description`, `language`, `generator`, `copyright`, `total_skip`, `total_count`, `episodes`, `lastbuilddate`, `lastsync`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $podcast->getCatalogId(),
                    $podcast->getFeedUrl(),
                    $podcast->getTitle(),
                    $podcast->getWebsite(),
                    $podcast->getDescription(),
                    $podcast->getLanguage(),
                    $podcast->getGenerator(),
                    $podcast->getCopyright(),
                    $podcast->getTotalSkip(),
                    $podcast->getTotalCount(),
                    $podcast->getEpisodeCount(),
                    $podcast->getLastBuildDate()->getTimestamp(),
                    $podcast->getLastSyncDate()->getTimestamp()
                ]
            );

            $result = $this->connection->getLastInsertedId();
        } else {
            $this->connection->query(
                'UPDATE `podcast` SET `feed` = ?, `title` = ?, `website` = ?, `description` = ?, `language` = ?, `generator` = ?, `copyright` = ?, `total_skip` = ?, `total_count` = ?, `episodes` = ?, `lastbuilddate` = ?, `lastsync` = ? WHERE `id` = ?',
                [
                    $podcast->getFeedUrl(),
                    $podcast->getTitle(),
                    $podcast->getWebsite(),
                    $podcast->getDescription(),
                    $podcast->getLanguage(),
                    $podcast->getGenerator(),
                    $podcast->getCopyright(),
                    $podcast->getTotalSkip(),
                    $podcast->getTotalCount(),
                    $podcast->getEpisodeCount(),
                    $podcast->getLastBuildDate()->getTimestamp(),
                    $podcast->getLastSyncDate()->getTimestamp(),
                    $podcast->getId(),
                ]
            );
        }

        return $result;
    }

    /**
     * Retrieve a single podcast-item by its id
     */
    public function findById(int $podcastId): ?Podcast
    {
        $podcast = $this->modelFactory->createPodcast($podcastId);
        if ($podcast->isNew()) {
            return null;
        }

        return $podcast;
    }
}
