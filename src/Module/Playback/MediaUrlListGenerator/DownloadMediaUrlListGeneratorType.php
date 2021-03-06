<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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

declare(strict_types=1);

namespace Ampache\Module\Playback\MediaUrlListGenerator;

use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Playback\Stream_Url;
use Psr\Http\Message\ResponseInterface;
use Teapot\StatusCode;

/**
 * This prompts for a download of the song
 */
final class DownloadMediaUrlListGeneratorType extends AbstractMediaUrlListGeneratorType
{
    public function generate(
        Stream_Playlist $playlist,
        ResponseInterface $response
    ): ResponseInterface {
        // There should only be one here...
        if (count($playlist->urls) != 1) {
            debug_event(self::class, 'Download called, but $urls contains ' . json_encode($playlist->urls), 2);
        }

        // Header redirect baby!
        $url = current($playlist->urls);
        $url = Stream_Url::add_options($url->url, '&action=download');

        return $response
            ->withStatus(StatusCode::FOUND)
            ->withHeader('Location', $url);
    }
}
