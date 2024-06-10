<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Util\Rss\Type;

use Ampache\Config\AmpConfig;
use Ampache\Module\Playback\Stream;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Generator;

final readonly class NowPlayingFeed extends AbstractGenericRssFeed
{
    protected function getTitle(): string
    {
        return T_('Now Playing');
    }

    /**
     * this is the pub date we should use for the Now Playing information,
     * this is a little specific as it uses the 'newest' expire we can find
     */
    protected function getPubDate(): ?int
    {
        // Little redundant, should be fixed by an improvement in the get_now_playing stuff
        $data    = Stream::get_now_playing();
        $element = array_shift($data);

        return $element['expire'] ?? null;
    }

    protected function getItems(): Generator
    {
        $data = Stream::get_now_playing();

        $format     = (string) (AmpConfig::get('rss_format') ?? '%t - %a - %A');
        $string_map = array(
            '%t' => 'title',
            '%a' => 'artist',
            '%A' => 'album'
        );
        foreach ($data as $element) {
            /** @var Song|Video $media */
            $media = $element['media'];
            /** @var User $client */
            $client      = $element['client'];
            $title       = $format;
            $description = $format;
            foreach ($string_map as $search => $replace) {
                $text = match ($replace) {
                    'title' => (string)$media->get_fullname(),
                    'artist' => ($media instanceof Song)
                        ? (string)$media->get_artist_fullname()
                        : '',
                    'album' => ($media instanceof Song)
                        ? (string)$media->get_album_fullname($media->album, true)
                        : '',
                };
                $title       = str_replace($search, $text, $title);
                $description = str_replace($search, $text, $description);
            }

            yield array(
                'title' => str_replace(' - - ', ' - ', $title),
                'link' => $media->get_link(),
                'description' => str_replace('<p>Artist: </p><p>Album: </p>', '', $description),
                'comments' => $client->get_fullname() . ' - ' . $element['agent'],
                'pubDate' => date("r", (int)$element['expire'])
            );
        }
    }
}
