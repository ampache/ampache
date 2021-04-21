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
 *
 */

declare(strict_types=1);

namespace Ampache\Module\Podcast;

use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\Model\PodcastInterface;
use Ampache\Repository\PodcastRepositoryInterface;

final class PodcastByCatalogLoader implements PodcastByCatalogLoaderInterface
{
    private CatalogRepositoryInterface $catalogRepository;

    private PodcastRepositoryInterface $podcastRepository;

    public function __construct(
        CatalogRepositoryInterface $catalogRepository,
        PodcastRepositoryInterface $podcastRepository
    ) {
        $this->catalogRepository = $catalogRepository;
        $this->podcastRepository = $podcastRepository;
    }

    /**
     * @param int[]|null $catalogIds
     *
     * @return PodcastInterface[]
     */
    public function load(?array $catalogIds = null): array
    {
        if ($catalogIds === null) {
            $catalogIds = $this->catalogRepository->getList('podcast');
        }

        $results = [];
        foreach ($catalogIds as $catalogId) {
            $podcastIds = $this->podcastRepository->getPodcastIds((int) $catalogId);
            foreach ($podcastIds as $podcastId) {
                $results[] = $this->podcastRepository->findById($podcastId);
            }
        }

        return $results;
    }
}
