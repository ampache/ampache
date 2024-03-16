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
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\User;

final readonly class LatestArtistFeed implements FeedTypeInterface
{
    public function __construct(
        private User $user,
    ) {
    }

    /**
     * load_latest_artist
     * This loads in the latest added artists
     */
    public function handle(): string
    {

        $ids = Stats::get_newest('artist', 10, 0, 0, $this->user->getId());

        $results = array();

        foreach ($ids as $artistid) {
            $artist = new Artist($artistid);
            $artist->format();

            $xml_array = array(
                'title' => $artist->get_fullname(),
                'link' => $artist->get_link(),
                'description' => $artist->summary,
                'image' => (string)Art::url($artist->id, 'artist', null, 2),
                'comments' => '',
                'pubDate' => ''
            );
            $results[] = $xml_array;
        } // end foreach

        Xml_Data::set_type('rss');

        return Xml_Data::rss_feed($results, $this->getTitle());
    }

    public function getTitle(): string
    {
        return AmpConfig::get('site_title') . ' - ' . T_('Newest Artists');
    }
}
