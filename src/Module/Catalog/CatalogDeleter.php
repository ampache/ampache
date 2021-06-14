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

namespace Ampache\Module\Catalog;

use Ampache\Module\Catalog\GarbageCollector\CatalogGarbageCollectorInterface;
use Ampache\Module\Catalog\Loader\CatalogLoaderInterface;
use Ampache\Module\Catalog\Loader\Exception\CatalogNotFoundException;
use Ampache\Module\System\Dba;
use Ampache\Repository\AlbumRepositoryInterface;

final class CatalogDeleter implements CatalogDeleterInterface
{
    private AlbumRepositoryInterface $albumRepository;

    private CatalogLoaderInterface $catalogLoader;

    private CatalogGarbageCollectorInterface $catalogGarbageCollector;

    public function __construct(
        AlbumRepositoryInterface $albumRepository,
        CatalogLoaderInterface $catalogLoader,
        CatalogGarbageCollectorInterface $catalogGarbageCollector
    ) {
        $this->albumRepository         = $albumRepository;
        $this->catalogLoader           = $catalogLoader;
        $this->catalogGarbageCollector = $catalogGarbageCollector;
    }

    /**
     * Deletes the catalog and everything associated with it
     * it takes the catalog id
     */
    public function delete(int $catalogId): bool
    {
        // Large catalog deletion can take time
        set_time_limit(0);

        // First remove the songs in this catalog
        $sql        = "DELETE FROM `song` WHERE `catalog` = ?";
        $db_results = Dba::write($sql, array($catalogId));

        // Only if the previous one works do we go on
        if (!$db_results) {
            return false;
        }

        $this->albumRepository->cleanEmptyAlbums();

        $sql        = "DELETE FROM `video` WHERE `catalog` = ?";
        $db_results = Dba::write($sql, array($catalogId));

        if (!$db_results) {
            return false;
        }
        try {
            $catalog = $this->catalogLoader->byId($catalogId);
        } catch (CatalogNotFoundException $e) {
            return false;
        }

        if (!$catalog->id) {
            return false;
        }

        $sql        = 'DELETE FROM `catalog_' . $catalog->get_type() . '` WHERE catalog_id = ?';
        $db_results = Dba::write($sql, array($catalogId));

        if (!$db_results) {
            return false;
        }

        // Next Remove the Catalog Entry it's self
        $sql = "DELETE FROM `catalog` WHERE `id` = ?";
        Dba::write($sql, array($catalogId));

        // run garbage collection
        $this->catalogGarbageCollector->collect();

        return true;
    }
}
