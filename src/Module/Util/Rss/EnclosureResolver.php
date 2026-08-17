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

namespace Ampache\Module\Util\Rss;

use Ampache\Config\AmpConfig;
use Ampache\Module\Catalog\Catalog;
use Ampache\Repository\Model\Podcast_Episode;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;

/**
 * Enclosure target without on-the-fly transcoding: mp3 raw, otherwise cached file if present, else raw.
 */
final class EnclosureResolver
{
    /**
     * @return array{0: string, 1: string, 2: string} [play_url params, mime, size]
     */
    public static function target(Song|Podcast_Episode $media): array
    {
        $cache_target = (string) AmpConfig::get('cache_target', '');

        if (
            $media instanceof Song
            && $media->type !== 'mp3'
            && $cache_target !== ''
            && $media->type !== $cache_target
        ) {
            $cache_path  = (string) AmpConfig::get('cache_path', '');
            $file_target = ($cache_path !== '')
                ? Catalog::get_cache_path($media->id, $media->catalog, $cache_path, $cache_target)
                : null;
            if ($file_target !== null && is_file($file_target)) {
                return [
                    '&format=' . $cache_target,
                    Song::type_to_mime($cache_target),
                    (string) (filesize($file_target) ?: $media->size),
                ];
            }
        }

        return ['&format=raw', (string) $media->mime, (string) $media->size];
    }

    /**
     * Stream url for a feed listener, null when the instance requires a session and no user is set
     */
    public static function url(Song|Podcast_Episode $media, ?User $user, string $params): ?string
    {
        if ($user !== null) {
            return $media->play_url($params, 'api', false, $user->getId(), $user->streamtoken);
        }

        if (!AmpConfig::get('use_auth') || !AmpConfig::get('require_session')) {
            return $media->play_url($params, 'api');
        }

        return null;
    }
}
