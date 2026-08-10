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

namespace Ampache\Gui\Index;

use Ampache\Gui\View\AbstractView;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Song;
use Override;

/**
 * The similar artists and songs shown beside what is playing now.
 */
final class NowPlayingSimilarView extends AbstractView
{
    /**
     * @param array<int, array{id: null|int, name: string, mbid?: null|string}> $artists
     * @param array<int, array{id: null|int}> $songs
     */
    public function __construct(
        private readonly string $webPath,
        private readonly array $artists,
        private readonly array $songs,
        private readonly bool $wantedEnabled,
    ) {}

    /**
     * An artist that is not in the catalog renders as a link to its wanted page, or as plain text when
     * there is no musicbrainz id to look it up by.
     *
     * @return list<string>
     */
    public function getArtistLinks(): array
    {
        $links = [];
        foreach ($this->artists as $artist) {
            if ($artist['id'] !== null) {
                $links[] = (new Artist($artist['id']))->get_f_link();

                continue;
            }

            $musicBrainzId = (string) ($artist['mbid'] ?? '');
            $links[]       = ($this->wantedEnabled && $musicBrainzId !== '')
                ? sprintf(
                    '<a class="missing_album" href="%s/artists.php?action=show_missing&mbid=%s" title="%s">%s</a>',
                    $this->e($this->webPath),
                    $this->e(rawurlencode($musicBrainzId)),
                    $this->e($artist['name']),
                    $this->e($artist['name'])
                )
                : $this->e($artist['name']);
        }

        return $links;
    }

    /**
     * @return list<string>
     */
    public function getSongLinks(): array
    {
        $links = [];
        foreach ($this->songs as $song) {
            if ($song['id'] === null) {
                continue;
            }

            $links[] = (new Song($song['id']))->get_f_link();
        }

        return $links;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('now_playing_similar.phtml');
    }
}
