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

namespace Ampache\Gui\Wanted;

use Ampache\Gui\View\AbstractView;
use Override;

/**
 * The artists a search turned up that the catalog does not hold.
 */
final class MissingArtistsView extends AbstractView
{
    /**
     * @param list<array{mbid: string, name: string}> $artists
     */
    public function __construct(
        private readonly string $webPath,
        private readonly array $artists,
    ) {}

    /**
     * @return list<array{mbid: string, name: string}>
     */
    public function getArtists(): array
    {
        return $this->artists;
    }

    public function getArtistUrl(string $musicBrainzId): string
    {
        return $this->webPath . '/artists.php?action=show_missing&mbid=' . rawurlencode($musicBrainzId);
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('missing_artists.phtml');
    }
}
