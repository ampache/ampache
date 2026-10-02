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

namespace Ampache\Module\Api\Jellyfin\Method\Image;

use Ampache\Module\Api\Jellyfin\JellyfinId;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\Art\Art;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /Items/{itemId}/Images/{imageType}[/{imageIndex}] — serves whatever art Ampache already has for the
 * decoded object, ignoring imageType/imageIndex/size params in v1 (Ampache has one image per object, not a
 * typed/indexed set). No art on file is a plain 404, same as real Jellyfin for a missing image — the client
 * already knows how to fall back to its own placeholder, so this doesn't generate one server-side.
 *
 * Unauthenticated by design: the vendored spec declares `security: null` on this path (confirmed by direct
 * inspection) — real Jellyfin serves images without a token, and a strict client (confirmed: Symfonium)
 * never sends one here, so requiring auth 401s every image fetch that client ever makes.
 */
final class ImageMethod implements JellyfinMethodInterface
{
    public function handle(ServerRequestInterface $request, ?User $user): JellyfinResponse
    {
        // the spec wants this open, but an install that turned public_images off asked for the opposite
        if (!Art::isPublic() && $user === null) {
            return JellyfinResponse::unauthorized();
        }

        $itemId = (string) ($request->getAttribute('itemId') ?? '');
        $type   = JellyfinId::decodeType($itemId);
        $id     = JellyfinId::decodeId($itemId);
        if ($type === null || $id === null) {
            http_response_code(404);

            return JellyfinResponse::alreadySent();
        }

        $art  = $this->resolveArt($type, $id);
        $body = $art->get();
        if ($body === '') {
            http_response_code(404);

            return JellyfinResponse::alreadySent();
        }

        header('Content-Type: ' . ($art->raw_mime !== '' ? $art->raw_mime : 'image/jpeg'));
        header('Content-Length: ' . strlen($body));
        header('Cache-Control: private, max-age=604800');

        if (strtoupper($request->getMethod()) === 'HEAD') {
            return JellyfinResponse::alreadySent();
        }

        echo $body;

        return JellyfinResponse::alreadySent();
    }

    /**
     * A song with no cover of its own falls back to its album's.
     *
     * A playlist does not fall back to a member's album art: this route answers anonymous callers, and
     * reading the members would hand a private playlist's composition to anyone who can guess its id.
     */
    private function resolveArt(string $type, int $id): Art
    {
        if ($type === 'song' && !Art::has_db($id, 'song')) {
            $song = new Song($id);

            return new Art($song->album, 'album');
        }

        return new Art($id, $type);
    }
}
