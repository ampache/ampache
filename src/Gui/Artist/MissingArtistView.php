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

namespace Ampache\Gui\Artist;

use Ampache\Gui\View\AbstractView;
use Override;

/**
 * The page for an artist that is wanted but not in the catalog.
 */
final class MissingArtistView extends AbstractView
{
    public function __construct(
        private readonly string $name,
        private readonly string $musicBrainzId,
        private readonly bool $biographyEnabled,
        private readonly bool $wantedEnabled,
    ) {}

    public function getMusicBrainzId(): string
    {
        return $this->musicBrainzId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * The biography is fetched from last.fm, so it needs both a name to look up and an api key.
     */
    public function hasBiography(): bool
    {
        return $this->name !== '' && $this->biographyEnabled;
    }

    public function hasMissingAlbums(): bool
    {
        return $this->musicBrainzId !== '' && $this->wantedEnabled;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('missing_artist.phtml');
    }
}
