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

use Ampache\Module\Statistics\Stats;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\User;
use Generator;
use Psr\Http\Message\ServerRequestInterface;

final readonly class LatestArtistFeed extends AbstractGenericRssFeed
{
    private ServerRequestInterface $request;

    public function __construct(
        private ?User $user,
        ServerRequestInterface $request,
    ) {
        $this->request = $request;
    }

    protected function getTitle(): string
    {
        return T_('Newest Artists');
    }

    protected function getItems(): Generator
    {
        $queryParams = $this->request->getQueryParams();
        $count       = (int)($queryParams['count'] ?? 10);
        $offset      = (int)($queryParams['offset'] ?? 0);
        $ids         = Stats::get_newest('artist', $count, $offset, 0, $this->user);

        foreach ($ids as $artistid) {
            $artist = new Artist($artistid);

            yield [
                'title' => (string) $artist->get_fullname(),
                'link' => $artist->get_link(),
                'description' => (string) $artist->summary,
                'comments' => '',
                'pubDate' => '',
                'guid' => (isset($artist->mbid))
                    ? 'https://musicbrainz.org/artist/' . $artist->mbid
                    : 'artist-' . $artist->id,
                'isPermaLink' => (isset($artist->mbid))
                    ? 'true'
                    : 'false',
                'image' => (string)Art::url($artist->id, 'artist', null, 2),
            ];
        }
    }
}
