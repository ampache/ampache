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

namespace Ampache\Module\Api\Jellyfin\Method\Items;

use Ampache\Module\Api\Jellyfin\JellyfinCatalogRefresher;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST /Items/{itemId}/Refresh — "refreshes metadata for an item" per the spec, `RequiresElevation`. Real
 * clients (confirmed: gelly's "rescan library" action) call this with a library's own item id, not just a
 * single song/album's — since `JellyfinUserView` has no per-catalog id to target yet, every call rescans
 * every catalog, the same as `POST /Library/Refresh`, regardless of which itemId was named.
 */
final class ItemRefreshMethod implements JellyfinMethodInterface
{
    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        if ($user === null) {
            return JellyfinResponse::unauthorized();
        }
        if (!$user->has_access(AccessLevelEnum::ADMIN)) {
            return JellyfinResponse::forbidden();
        }

        JellyfinCatalogRefresher::refreshAll();

        return JellyfinResponse::noContent();
    }
}
