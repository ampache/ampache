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

namespace Ampache\Gui\Partial;

use Ampache\Config\AmpConfig;
use Ampache\Gui\View\AbstractView;
use Ampache\Module\Util\Ui;
use Override;

/**
 * The "search on ..." links an album or artist page offers.
 *
 * The album, album-disk, grouped-disk and artist pages each carried their own copy of the same seven
 * providers, differing only in the query terms and the MusicBrainz entity.
 */
final class ExternalLinksView extends AbstractView
{
    /**
     * @param string $primary the artist name, which every provider searches on
     * @param string $secondary the album name, empty on an artist page
     * @param string $entity the MusicBrainz entity: `release` for an album, `artist` for an artist
     */
    public function __construct(
        private readonly string $primary,
        private readonly string $secondary,
        private readonly ?string $mbid,
        private readonly string $entity,
        private readonly string $lastfmType,
        private readonly string $bandcampType,
        private readonly string $discogsType,
        private readonly ?string $discogsPrimary = null,
    ) {}

    public function getIcon(string $provider, string $name): string
    {
        return Ui::get_icon($provider, sprintf(T_('Search on %s ...'), $name));
    }

    /**
     * @return list<array{provider: string, name: string, url: string}>
     */
    public function getLinks(): array
    {
        $primary   = rawurlencode($this->primary);
        $secondary = rawurlencode($this->secondary);
        // an album quotes both terms; an artist page has only the one, so the empty half is dropped
        $quoted  = ($this->secondary === '') ? '%22' . $primary . '%22' : '%22' . $primary . '%22+%22' . $secondary . '%22';
        $plain   = ($this->secondary === '') ? $primary : $primary . '+' . $secondary;
        $subject = ($this->secondary === '') ? $primary : $secondary;

        $candidates = [
            ['google', 'Google', 'https://www.google.com/search?q=' . $quoted],
            ['duckduckgo', 'DuckDuckGo', 'https://www.duckduckgo.com/?q=' . $plain],
            ['wikipedia', 'Wikipedia', 'https://en.wikipedia.org/wiki/Special:Search?search=%22' . $subject . '%22&go=Go'],
            ['lastfm', 'Last.fm', 'https://www.last.fm/search?q=' . $quoted . '&type=' . $this->lastfmType],
            ['bandcamp', 'Bandcamp', 'https://bandcamp.com/search?q=' . $plain . '&item_type=' . $this->bandcampType],
            ['discogs', 'Discogs', 'https://www.discogs.com/search/?q=' . $this->getDiscogsQuery() . '&type=' . $this->discogsType],
            ['musicbrainz', 'Musicbrainz', $this->getMusicbrainzUrl($subject)],
        ];

        $links = [];
        foreach ($candidates as [$provider, $name, $url]) {
            if (AmpConfig::get('external_links_' . $provider)) {
                $links[] = ['provider' => $provider, 'name' => $name, 'url' => $url];
            }
        }

        return $links;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('partial/external_links.phtml');
    }

    /**
     * Discogs files compilations under a shorter name, which the album page has always substituted.
     */
    private function getDiscogsQuery(): string
    {
        $primary = rawurlencode($this->discogsPrimary ?? $this->primary);

        return ($this->secondary === '') ? $primary : $primary . '+' . rawurlencode($this->secondary);
    }

    /**
     * A known MusicBrainz id links straight to the release or artist rather than searching for it.
     */
    private function getMusicbrainzUrl(string $subject): string
    {
        return ($this->mbid)
            ? 'https://musicbrainz.org/' . $this->entity . '/' . rawurlencode($this->mbid)
            : 'https://musicbrainz.org/search?query=%22' . $subject . '%22&type=' . $this->entity;
    }
}
