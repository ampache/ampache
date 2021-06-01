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

namespace Ampache\Module\Video;

use Ampache\Module\Catalog\Loader\CatalogLoaderInterface;
use Ampache\Module\Catalog\Loader\Exception\CatalogNotFoundException;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Repository\CatalogRepositoryInterface;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\Video;

final class VideoLoader implements VideoLoaderInterface
{
    private ModelFactoryInterface $modelFactory;

    private CatalogRepositoryInterface $catalogRepository;

    private CatalogLoaderInterface $catalogLoader;

    public function __construct(
        ModelFactoryInterface $modelFactory,
        CatalogRepositoryInterface $catalogRepository,
        CatalogLoaderInterface $catalogLoader
    ) {
        $this->modelFactory      = $modelFactory;
        $this->catalogRepository = $catalogRepository;
        $this->catalogLoader     = $catalogLoader;
    }

    /**
     * Create a video strongly typed object from its id.
     *
     * @return Video&library_item
     */
    public function load(int $videoId)
    {
        foreach (ObjectTypeToClassNameMapper::VIDEO_TYPES as $dtype) {
            /** @var Video&library_item $result */
            $result = $this->modelFactory->mapObjectType($dtype, $videoId);
            if ($result->isNew() === false) {
                return $result;
            }
        }

        return $this->modelFactory->createVideo($videoId);
    }

    /**
     * @param array<int> $catalogIds
     * @param string $type
     *
     * @return array<Video>
     */
    public function loadByCatalogs(array $catalogIds = [], string $type = ''): array
    {
        if (!$catalogIds) {
            $catalogIds = $this->catalogRepository->getList();
        }

        $results = [];
        foreach ($catalogIds as $catalogId) {
            try {
                $catalog  = $this->catalogLoader->byId($catalogId);
            } catch (CatalogNotFoundException $e) {
                continue;
            }
            $videoIds = $catalog->get_video_ids($type);

            foreach ($videoIds as $videoId) {
                $results[] = $this->load($videoId);
            }
        }

        return $results;
    }
}
