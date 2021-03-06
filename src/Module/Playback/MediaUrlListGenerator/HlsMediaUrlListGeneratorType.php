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
use Ampache\Module\Stream\Url\StreamUrlParserInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class HlsMediaUrlListGeneratorType extends AbstractMediaUrlListGeneratorType
{
    private StreamFactoryInterface $streamFactory;

    private StreamUrlParserInterface $streamUrlParser;

    private ModelFactoryInterface $modelFactory;

    public function __construct(
        StreamFactoryInterface $streamFactory,
        StreamUrlParserInterface $streamUrlParser,
        ModelFactoryInterface $modelFactory
    ) {
        $this->streamFactory   = $streamFactory;
        $this->streamUrlParser = $streamUrlParser;
        $this->modelFactory    = $modelFactory;
    }

    public function generate(
        Stream_Playlist $playlist,
        ResponseInterface $response
    ): ResponseInterface {
        $ssize = 10;
        $ret   = "#EXTM3U\n";
        $ret .= "#EXT-X-TARGETDURATION:" . $ssize . "\n";
        $ret .= "#EXT-X-VERSION:1\n";
        $ret .= "#EXT-X-ALLOW-CACHE:NO\n";
        $ret .= "#EXT-X-MEDIA-SEQUENCE:0\n";
        $ret .= "#EXT-X-PLAYLIST-TYPE:VOD\n";   // Static list of segments

        foreach ($playlist->urls as $url) {
            $soffset = 0;
            $segment = 0;
            while ($soffset < $url->time) {
                $type              = $url->type;
                $size              = (($soffset + $ssize) <= $url->time) ? $ssize : ($url->time - $soffset);
                $additional_params = '&transcode_to=ts&segment=' . $segment;
                $ret .= "#EXTINF:" . $size . ",\n";
                $purl = $this->streamUrlParser->parse($url->url);
                $id   = $purl['id'];

                unset($purl['id']);
                unset($purl['ssid']);
                unset($purl['type']);
                unset($purl['base_url']);
                unset($purl['uid']);
                unset($purl['name']);

                foreach ($purl as $key => $value) {
                    $additional_params .= '&' . $key . '=' . $value;
                }

                $item = $this->modelFactory->mapObjectType($type, (int) $id);

                if ($item === null) {
                    continue;
                }
                $hu   = $item->play_url($additional_params);
                $ret .= $hu . "\n";
                $soffset += $size;
                $segment++;
            }
        }

        $ret .= "#EXT-X-ENDLIST\n\n";

        return $this
            ->setHeader($response, 'm3u8', 'application/vnd.apple.mpegurl')
            ->withBody($this->streamFactory->createStream($ret));
    }
}
