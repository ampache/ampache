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

namespace Ampache\Module\Podcast;

use Ampache\Module\Catalog\CatalogLoaderInterface;
use Ampache\Module\Catalog\Exception\CatalogLoadingException;
use Ampache\Module\Podcast\Exception\PodcastFolderException;
use Ampache\Repository\Model\Podcast;

/**
 * Provides functionality to build the podcasts root folder
 */
final class PodcastFolderProvider implements PodcastFolderProviderInterface
{
    private CatalogLoaderInterface $catalogLoader;

    public function __construct(
        CatalogLoaderInterface $catalogLoader
    ) {
        $this->catalogLoader = $catalogLoader;
    }

    /**
     * Returns the podcasts base folder
     *
     * If the folder does not exist yet, it will be created
     *
     * @throws PodcastFolderException
     */
    public function getBaseFolder(Podcast $podcast): string
    {
        $catalogId = $podcast->getCatalogId();

        try {
            $catalog = $this->catalogLoader->getById($catalogId);
        } catch (CatalogLoadingException $e) {
            throw new PodcastFolderException(sprintf('Catalog not found: %d', $catalogId));
        }

        $catalogType = $catalog->get_type();

        // only local catalogs are supported
        if ($catalogType !== 'local') {
            throw new PodcastFolderException(sprintf('Bad catalog type: %s', $catalogType));
        }

        $fullPath = $catalog->get_path() . DIRECTORY_SEPARATOR . $podcast->get_fullname();

        // create path if it doesn't exist
        if (
            !is_dir($fullPath) &&
            @mkdir($fullPath) === false
        ) {
            throw new PodcastFolderException(sprintf('Cannot create folder: %s', $fullPath));
        }

        return $fullPath;
    }
}
