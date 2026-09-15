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

namespace Ampache\Module\Api\Jellyfin\Method\Artist;

use Ampache\Module\Api\Jellyfin\JellyfinItemMapper;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Userflag;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /Artists and GET /Artists/AlbumArtists — bare top-level artist browses, distinct from `/Items` (found
 * missing via a live mobile client's own root-navigation query, which never goes through `/Items` at all).
 */
final class ArtistsMethod implements JellyfinMethodInterface
{
    public function __construct(private readonly JellyfinItemMapper $mapper) {}

    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        if ($user === null) {
            return JellyfinResponse::unauthorized();
        }

        $query        = $request->getQueryParams();
        $onlyFavorite = str_contains((string) ($query['Filters'] ?? ''), 'IsFavorite')
            || strtolower((string) ($query['isFavorite'] ?? '')) === 'true';
        $startIndex = max(0, (int) ($query['startIndex'] ?? $query['StartIndex'] ?? 0));
        $limitParam = (string) ($query['limit'] ?? $query['Limit'] ?? '');
        $limit      = ($limitParam !== '') ? (int) $limitParam : 0;

        $artists = Catalog::get_artists($user->get_catalogs('music'));
        $ids     = array_map(static fn(Artist $artist): int => $artist->id, $artists);
        Artist::build_cache($ids);
        Rating::build_cache('artist', $ids);
        Userflag::build_cache('artist', $ids);

        $items = [];
        foreach ($artists as $artist) {
            $items[] = $this->mapper->mapArtist($artist, $user, []);
        }

        if ($onlyFavorite) {
            $items = array_values(array_filter(
                $items,
                static fn(array $dto): bool => (bool) ($dto['UserData']['IsFavorite'] ?? false),
            ));
        }

        $total = count($items);
        $items = ($limit > 0) ? array_slice($items, $startIndex, $limit) : array_slice($items, $startIndex);

        return JellyfinResponse::json([
            'Items' => $items,
            'TotalRecordCount' => $total,
            'StartIndex' => $startIndex,
        ]);
    }
}
