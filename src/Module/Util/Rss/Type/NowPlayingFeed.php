<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=3 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
use Ampache\Module\Api\Xml_Data;
use Ampache\Module\Playback\Stream;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;

class NowPlayingFeed implements FeedTypeInterface
{
    public function handle(): string
    {
        $data = Stream::get_now_playing();

        $results    = array();
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
                switch ($replace) {
                    case 'title':
                        $text = (string)$media->get_fullname();
                        break;
                    case 'artist':
                        $text = ($media instanceof Song)
                            ? (string)$media->get_artist_fullname()
                            : '';
                        break;
                    case 'album':
                        $text = ($media instanceof Song)
                            ? (string)$media->get_album_fullname($media->album, true)
                            : '';
                        break;
                    default:
                        $text = '';
                }
                $title       = str_replace($search, $text, $title);
                $description = str_replace($search, $text, $description);
            }
            $xml_array = array(
                'title' => str_replace(' - - ', ' - ', $title),
                'link' => $media->get_link(),
                'description' => str_replace('<p>Artist: </p><p>Album: </p>', '', $description),
                'comments' => $client->get_fullname() . ' - ' . $element['agent'],
                'pubDate' => date("r", (int)$element['expire'])
            );
            $results[] = $xml_array;
        } // end foreach

        Xml_Data::set_type('rss');

        return Xml_Data::rss_feed($results, $this->getTitle(), $this->pubdate_now_playing());
    }

    public function getTitle(): string
    {
        return AmpConfig::get('site_title') . ' - ' . T_('Now Playing');
    }

    /**
     * pubdate_now_playing
     * this is the pub date we should use for the Now Playing information,
     * this is a little specific as it uses the 'newest' expire we can find
     */
    private function pubdate_now_playing(): ?int
    {
        // Little redundent, should be fixed by an improvement in the get_now_playing stuff
        $data    = Stream::get_now_playing();
        $element = array_shift($data);

        return $element['expire'] ?? null;
    }
}
