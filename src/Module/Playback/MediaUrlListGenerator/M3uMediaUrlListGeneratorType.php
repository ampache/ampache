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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * creates an m3u file, this includes the EXTINFO and as such can be
 * large with very long playlists
 */
final class M3uMediaUrlListGeneratorType extends AbstractMediaUrlListGeneratorType
{
    private StreamFactoryInterface $streamFactory;

    public function __construct(
        StreamFactoryInterface $streamFactory
    ) {
        $this->streamFactory = $streamFactory;
    }

    public function generate(
        Stream_Playlist $playlist,
        ResponseInterface $response
    ): ResponseInterface {
        $ret = "#EXTM3U\n";

        $count = 0;
        foreach ($playlist->urls as $url) {
            $ret .= '#EXTINF:' . $url->time . ', ' . $url->author . ' - ' . $url->title . "\n";
            $ret .= $url->url . "\n";
            $count++;
        }

        return $this
            ->setHeader($response, 'm3u', 'audio/x-mpegurl')
            ->withBody($this->streamFactory->createStream($ret));
    }
}
