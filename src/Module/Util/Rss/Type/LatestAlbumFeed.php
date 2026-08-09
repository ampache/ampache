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

namespace Ampache\Module\Util\Rss\Type;

use Ampache\Config\AmpConfig;
use Ampache\Module\Art\Art;
use Ampache\Module\Util\Rss\PodcastGuid;
use Ampache\Module\Statistics\Stats;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\User;
use Generator;
use Override;
use Psr\Http\Message\ServerRequestInterface;

final readonly class LatestAlbumFeed extends AbstractGenericRssFeed
{
    public function __construct(
        private ?User $user,
        private ServerRequestInterface $request,
    ) {}

    protected function getItems(): Generator
    {
        $queryParams = $this->request->getQueryParams();
        $count       = (int) ($queryParams['count'] ?? 10);
        $offset      = (int) ($queryParams['offset'] ?? 0);
        $ids         = Stats::get_newest('album', $count, $offset, 0, $this->user);

        foreach ($ids as $albumid) {
            $album = new Album($albumid);

            yield [
                'title' => $album->get_fullname(),
                'link' => $album->get_link(),
                'description' => $album->get_parent_fullname() . ' - ' . $album->get_fullname(true),
                'comments' => '',
                'pubDate' => date(DATE_RFC2822, $album->addition_time),
                'guid' => ($album->mbid !== null) ? 'https://musicbrainz.org/release/' . $album->mbid : ($album->mbid_group !== null ? 'https://musicbrainz.org/release-group/' . $album->mbid_group : 'album-' . $album->id),
                'isPermaLink' => ($album->mbid !== null || $album->mbid_group !== null)
                    ? 'true'
                    : 'false',
                'image' => (string) Art::url($album->id, 'album', null, 2),
            ];
        }
    }

    protected function getTitle(): string
    {
        return T_('Newest Albums');
    }

    #[Override]
    protected function getMedium(): ?string
    {
        return 'playlist';
    }

    /**
     * Point Podcasting 2.0 apps at each album's own library_item feed
     *
     * @return list<array{feedUrl: string, feedGuid: string}>
     */
    #[Override]
    protected function getRemoteItems(): array
    {
        $queryParams = $this->request->getQueryParams();
        $count       = (int) ($queryParams['count'] ?? 10);
        $offset      = (int) ($queryParams['offset'] ?? 0);
        $ids         = Stats::get_newest('album', $count, $offset, 0, $this->user);

        $items = [];
        foreach ($ids as $albumid) {
            $canonical = AmpConfig::get_web_path() . '/rss.php?type=library_item&object_type=album&object_id=' . $albumid;
            $items[]   = [
                'feedUrl' => ($this->user !== null && AmpConfig::get('use_auth'))
                    ? $canonical . '&rsstoken=' . $this->user->getRssToken()
                    : $canonical,
                'feedGuid' => PodcastGuid::fromFeedUrl($canonical),
            ];
        }

        return $items;
    }
}
