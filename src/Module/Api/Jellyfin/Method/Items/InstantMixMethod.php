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

use Ampache\Module\Api\Jellyfin\JellyfinId;
use Ampache\Module\Api\Jellyfin\JellyfinItemMapper;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Module\Util\Recommendation;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /Items/{itemId}/InstantMix — seeded from `Recommendation::get_songs_like()`, falling back to the
 * seed's own album and then a random sample once that runs out (it needs a configured `lastfm_api_key` and
 * answers nothing without one), so a mix is never empty regardless of what's configured.
 */
final class InstantMixMethod implements JellyfinMethodInterface
{
    private const int DEFAULT_LIMIT = 32;

    public function __construct(private readonly JellyfinItemMapper $mapper) {}

    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        if ($user === null) {
            return JellyfinResponse::unauthorized();
        }

        $itemId = (string) ($request->getAttribute('itemId') ?? '');
        if (!JellyfinId::isType($itemId, 'song')) {
            return JellyfinResponse::notFound();
        }

        $songId = JellyfinId::decodeId($itemId);
        $seed   = ($songId !== null) ? new Song($songId) : null;
        if ($seed === null || $seed->isNew()) {
            return JellyfinResponse::notFound();
        }

        $limitParam = (string) ($request->getQueryParams()['limit'] ?? '');
        $limit      = ($limitParam !== '') ? max(1, (int) $limitParam) : self::DEFAULT_LIMIT;

        $ids = array_map('intval', array_column(Recommendation::get_songs_like($songId, $limit), 'id'));
        $ids = array_values(array_filter($ids, static fn(int $candidate): bool => $candidate !== $songId));

        if (count($ids) < $limit) {
            $album = new Album($seed->album);
            $ids   = $this->fillWithRandom($ids, array_values($album->get_songs()), $songId, $limit);
        }
        if (count($ids) < $limit) {
            $pool = array_values(Catalog::get_all_song_ids(0, 0, $user->get_catalogs('music')));
            $ids  = $this->fillWithRandom($ids, $pool, $songId, $limit);
        }
        $ids = array_slice($ids, 0, $limit);

        $items = array_map(fn(int $id): array => $this->mapper->mapSong(new Song($id), $user, []), $ids);

        return JellyfinResponse::json([
            'Items' => $items,
            'TotalRecordCount' => count($items),
            'StartIndex' => 0,
        ]);
    }

    /**
     * @param list<int> $ids
     * @param list<int> $pool
     * @return list<int>
     */
    private function fillWithRandom(array $ids, array $pool, int $exclude, int $limit): array
    {
        shuffle($pool);
        foreach ($pool as $candidate) {
            if ($candidate !== $exclude && !in_array($candidate, $ids, true)) {
                $ids[] = $candidate;
            }
            if (count($ids) >= $limit) {
                break;
            }
        }

        return $ids;
    }
}
