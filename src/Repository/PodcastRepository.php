<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=3 shiftwidth=4 expandtab:
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

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Database\DatabaseConnectionInterface;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Podcast;

/**
 * Manages podcast related database access
 *
 * Tables: `podcast`, `podcast_episode`
 */
final class PodcastRepository implements PodcastRepositoryInterface
{
    private ModelFactoryInterface $modelFactory;

    private DatabaseConnectionInterface $connection;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        DatabaseConnectionInterface $connection,
        ConfigContainerInterface $configContainer
    ) {
        $this->modelFactory    = $modelFactory;
        $this->connection      = $connection;
        $this->configContainer = $configContainer;
    }

    /**
     * Searches for an existing podcast object by the feed url
     */
    public function findByFeedUrl(
        string $feedUrl
    ): ?Podcast {
        $podcastId = $this->connection->fetchOne(
            'SELECT `id` FROM `podcast` WHERE `feed`= ?',
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
     * Creates a new podcast database item
     *
     * @param array{
     *   title: string,
     *   website: string,
     *   description: string,
     *   language: string,
     *   copyright: string,
     *   generator: string,
     *   lastBuildDate: null|int
     *  } $data
     */
    public function create(
        Catalog $catalog,
        string $feedUrl,
        array $data
    ): Podcast {
        $this->connection->query(
            <<<SQL
            INSERT INTO
                `podcast`
                (
                    `feed`,
                    `catalog`,
                    `title`,
                    `website`,
                    `description`,
                    `language`,
                    `copyright`,
                    `generator`,
                    `lastbuilddate`
                )
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?)
            SQL,
            [
                $feedUrl,
                $catalog->getId(),
                $data['title'],
                $data['website'],
                $data['description'],
                $data['language'],
                $data['copyright'],
                $data['generator'],
                $data['lastBuildDate']
            ]
        );

        return $this->modelFactory->createPodcast(
            $this->connection->getLastInsertedId()
        );
    }

    /**
     * Returns all episode-ids for the given podcast
     *
     * @param string $stateFilter Return only items with this state
     *
     * @return list<int>
     */
    public function getEpisodes(Podcast $podcast, string $stateFilter = ''): array
    {
        $skipDisabledCatalogs = $this->configContainer->get(ConfigurationKeyEnum::CATALOG_DISABLE);

        $params = [$podcast->getId()];
        $sql    = 'SELECT `podcast_episode`.`id` FROM `podcast_episode` ';

        if ($skipDisabledCatalogs) {
            $sql .= 'LEFT JOIN `catalog` ON `catalog`.`id` = `podcast_episode`.`catalog` ';
        }

        $sql .= 'WHERE `podcast_episode`.`podcast`= ? ';

        if (!empty($stateFilter)) {
            $sql .= 'AND `podcast_episode`.`state` = ? ';
            $params[] = $stateFilter;
        }

        if ($skipDisabledCatalogs) {
            $sql .= 'AND `catalog`.`enabled` = \'1\' ';
        }

        $sql .= 'ORDER BY `podcast_episode`.`pubdate` DESC';

        $result = $this->connection->query($sql, $params);

        $episodeIds = [];
        while ($episodeId = $result->fetchColumn()) {
            $episodeIds[] = (int) $episodeId;
        }

        return $episodeIds;
    }
}
