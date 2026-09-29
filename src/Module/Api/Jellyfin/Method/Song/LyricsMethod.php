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

namespace Ampache\Module\Api\Jellyfin\Method\Song;

use Ampache\Module\Api\Jellyfin\JellyfinId;
use Ampache\Module\Api\Jellyfin\JellyfinResponse;
use Ampache\Module\Api\Jellyfin\Method\JellyfinMethodInterface;
use Ampache\Module\Catalog\Catalog;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * GET /Audio/{itemId}/Lyrics — Ampache stores plain, unsynced lyric text, so every `LyricLine` carries only
 * `Text`; `Start`/`Cues` (line timing) are omitted rather than sent null, per this surface's usual rule.
 */
final class LyricsMethod implements JellyfinMethodInterface
{
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
        $song   = ($songId !== null) ? new Song($songId) : null;
        if ($song === null || $song->isNew() || !Catalog::has_access($song->getCatalogId(), $user->getId())) {
            return JellyfinResponse::notFound();
        }

        // get_lyrics()'s own lazy-load reads a column set that excludes lyrics; force the full row first
        $song->fill_ext_info();
        $lyrics = $song->get_lyrics(true);
        $text   = (string) ($lyrics['text'] ?? '');
        if ($text === '') {
            return JellyfinResponse::notFound();
        }

        // stored lyrics carry both an HTML break and a real newline per line, so a blank line follows every one
        $text  = strip_tags(str_ireplace(['<br />', '<br/>', '<br>'], "\n", $text));
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [$text];
        $lines = array_values(array_filter($lines, static fn(string $line): bool => trim($line) !== ''));

        return JellyfinResponse::json([
            'Lyrics' => array_map(static fn(string $line): array => ['Text' => $line], $lines),
        ]);
    }
}
