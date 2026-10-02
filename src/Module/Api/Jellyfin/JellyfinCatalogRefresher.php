<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

namespace Ampache\Module\Api\Jellyfin;

use Ampache\Module\Catalog\Catalog;

/**
 * The one place every "trigger a rescan" entry point (`POST /Library/Refresh`, `POST /Items/{itemId}/Refresh`)
 * goes through. There is no library-to-catalog mapping yet — `JellyfinUserView` is a single synthetic view
 * over every catalog (the plan's own still-open "one merged view vs. one per catalog" decision) — so any
 * refresh request just rescans every real catalog on the server, regardless of which item it named.
 */
final class JellyfinCatalogRefresher
{
    public static function refreshAll(): void
    {
        foreach (Catalog::get_catalogs() as $catalogId) {
            // Catalog::get_catalogs() always appends 0, the parking lot for orphaned albums, not a real catalog
            if ($catalogId === 0) {
                continue;
            }

            $catalog = Catalog::create_from_id($catalogId);
            if ($catalog === null) {
                continue;
            }

            $catalog->clean_catalog_proc();
            $catalog->verify_catalog_proc();
            $catalog->add_to_catalog(['gather_art' => true, 'parse_playlist' => false]);
        }
    }
}
