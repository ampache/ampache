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

namespace Ampache\Module\Api\Jellyfin\Method\UserData;

use Ampache\Module\Api\Jellyfin\JellyfinId;
use Ampache\Module\Api\Jellyfin\JellyfinItemMapper;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\Statistics\Rating;
use Ampache\Repository\Model\Playlist;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * POST/DELETE /UserItems/{itemId}/Rating (+ legacy /Users/{userId}/Items/{itemId}/Rating). Real Jellyfin's
 * boolean like/dislike is mapped onto Ampache's 5-star rating: 5 (like), 1 (dislike), 0 on DELETE.
 */
final class RatingMethod implements JellyfinMethodInterface
{
    private const array RATABLE_TYPES = ['song', 'album', 'artist', 'playlist'];

    public function __construct(private readonly JellyfinItemMapper $mapper) {}

    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        if ($user === null) {
            return JellyfinResponse::unauthorized();
        }

        $itemId = (string) ($request->getAttribute('itemId') ?? '');
        $type   = JellyfinId::decodeType($itemId);
        $id     = JellyfinId::decodeId($itemId);
        if ($type === null || $id === null || !in_array($type, self::RATABLE_TYPES, true)) {
            return JellyfinResponse::notFound();
        }

        if ($type === 'playlist') {
            $playlist = new Playlist($id);
            if ($playlist->isNew()) {
                return JellyfinResponse::notFound();
            }
            if ($playlist->type !== 'public' && !$playlist->has_collaborate($user)) {
                return JellyfinResponse::forbidden();
            }
        }

        $rating = new Rating($id, $type);
        if (strtoupper($request->getMethod()) === 'DELETE') {
            $rating->set_rating(0, $user->getId());
        } else {
            $query = $request->getQueryParams();
            if (!array_key_exists('likes', $query)) {
                return JellyfinResponse::badRequest();
            }

            $likes = strtolower((string) $query['likes']) === 'true';
            $rating->set_rating($likes ? 5 : 1, $user->getId());
        }

        return JellyfinResponse::json($this->mapper->mapUserData($type, $id, $user));
    }
}
