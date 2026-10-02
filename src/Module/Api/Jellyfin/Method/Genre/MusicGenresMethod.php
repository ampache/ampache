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

namespace Ampache\Module\Api\Jellyfin\Method\Genre;

use Ampache\Module\Api\Jellyfin\JellyfinItemMapper;
use Ampache\Module\Api\Jellyfin\JellyfinRequestBody;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\Statistics\Rating;
use Ampache\Module\Statistics\Userflag;
use Ampache\Repository\Model\Tag;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /MusicGenres (missing from the vendored spec but real servers serve it — found via Symfonium sync
 * traffic retrying it forever) and GET /Genres — song-tag genres, the only kind this surface has.
 */
final class MusicGenresMethod implements JellyfinMethodInterface
{
    public function __construct(private readonly JellyfinItemMapper $mapper) {}

    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        if ($user === null) {
            return JellyfinResponse::unauthorized();
        }

        $query      = $request->getQueryParams();
        $startIndex = max(0, (int) (JellyfinRequestBody::field($query, 'StartIndex') ?? 0));
        $limitParam = (string) (JellyfinRequestBody::field($query, 'Limit') ?? '');
        $limit      = ($limitParam !== '') ? (int) $limitParam : 0;

        $tags  = array_values(array_filter(Tag::get_tags('song'), static fn(array $tag): bool => !$tag['is_hidden']));
        $total = count($tags);
        $tags  = ($limit > 0) ? array_slice($tags, $startIndex, $limit) : array_slice($tags, $startIndex);

        $ids = array_column($tags, 'id');
        Rating::build_cache('genre', $ids);
        Userflag::build_cache('genre', $ids);

        return JellyfinResponse::json([
            'Items' => array_map(fn(array $tag): array => $this->mapper->mapGenre($tag, $user), $tags),
            'TotalRecordCount' => $total,
            'StartIndex' => $startIndex,
        ]);
    }
}
