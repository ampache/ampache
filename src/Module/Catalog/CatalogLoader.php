<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Catalog;

use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\User;

final class CatalogLoader implements CatalogLoaderInterface
{
    /**
     * Finds a catalog by its id
     *
     * @throws Exception\CatalogLoadingException
     */
    public function getById(int $catalogId): Catalog
    {
        $catalog = Catalog::create_from_id($catalogId);
        if ($catalog === null) {
            throw new Exception\CatalogLoadingException();
        }

        return $catalog;
    }

    /**
     * Returns all available catalogs according to the provided filter
     *
     * @return array<int, Catalog> Dict of catalogs, indexed by its id
     *
     * @see Catalog::get_catalogs()
     */
    public function getCatalogs(
        ?string $filterType = null,
        ?User $user = null
    ): array {
        $userId = $user?->getId();

        if ($filterType === null) {
            $filterType = '';
        }

        $catalogIds = Catalog::get_catalogs($filterType, $userId);

        $catalogs = [];

        foreach ($catalogIds as $catalogId) {
            $catalog = Catalog::create_from_id($catalogId);
            if ($catalog === null) {
                continue;
            }

            $catalogs[$catalogId] = $catalog;
        }

        return $catalogs;
    }
}
