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
use Ampache\Module\Statistics\Stats;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;

final class RecentlyPlayedFeed implements FeedTypeInterface
{
    public function __construct(
        private readonly int $user_id
    ) {
    }

    /**
     * load_recently_played
     * This loads in the Recently Played information and formats it up real nice like
     */
    public function handle(): string
    {
        $results = array();
        $data    = Stats::get_recently_played($this->user_id, 'stream', 'song');

        foreach ($data as $item) {
            $client = new User($item['user']);
            $song   = new Song($item['object_id']);
            $row_id = ($item['user'] > 0) ? (int) $item['user'] : -1;

            $has_allowed_recent = (bool) $item['user_recent'];
            $is_allowed_recent  = ($this->user_id > 0) ? $this->user_id == $row_id : $has_allowed_recent;
            if ($song->enabled && $is_allowed_recent) {
                $description = '<p>' . T_('User') . ': ' . $client->username . '</p><p>' . T_('Title') . ': ' . $song->get_fullname() . '</p><p>' . T_('Artist') . ': ' . $song->get_artist_fullname() . '</p><p>' . T_('Album') . ': ' . $song->get_album_fullname() . '</p><p>' . T_('Play date') . ': ' . get_datetime($item['date']) . '</p>';

                $xml_array = array(
                    'title' => $song->get_fullname() . ' - ' . $song->get_artist_fullname() . ' - ' . $song->get_album_fullname(),
                    'link' => str_replace('&amp;', '&', (string)$song->get_link()),
                    'description' => $description,
                    'comments' => (string)$client->username,
                    'pubDate' => date("r", (int)$item['date'])
                );
                $results[] = $xml_array;
                $pub_date  = (int)$item['date'];
            }
        } // end foreach

        Xml_Data::set_type('rss');

        return Xml_Data::rss_feed($results, $this->getTitle());
    }

    public function getTitle(): string
    {
        return AmpConfig::get('site_title') . ' - ' . T_('Recently Played');
    }
}
