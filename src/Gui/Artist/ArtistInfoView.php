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
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Wanted;
use Override;

/**
 * The last.fm biography panel shown beside an artist.
 */
final class ArtistInfoView extends AbstractView
{
    /**
     * @param array<string, int|string|null> $biography
     */
    public function __construct(
        private readonly Artist|Wanted|null $artist,
        private readonly array $biography,
    ) {}

    /**
     * Only a real artist has art; a wanted one is not in the catalog yet, and the name lookup can miss entirely.
     */
    public function getArtistIdWithArt(): ?int
    {
        return ($this->artist instanceof Artist) ? $this->artist->id : null;
    }

    public function getArtistName(): string
    {
        return ($this->artist instanceof Artist)
            ? ($this->artist->get_fullname() ?? $this->artist->name ?? '')
            : '';
    }

    /**
     * With no summary to sit beside, the art takes the space the text would have used.
     *
     * @return array{width: int, height: int}
     */
    public function getArtSize(): array
    {
        return ($this->getSummary() === null)
            ? ['width' => 384, 'height' => 384]
            : ['width' => 128, 'height' => 128];
    }

    /**
     * Where and when the act formed, whichever of the two last.fm knows.
     */
    public function getFormationDetails(): string
    {
        $details = [];
        $place   = (string) ($this->biography['placeformed'] ?? '');
        if ($place !== '') {
            $details[] = $place;
        }

        if ((int) ($this->biography['yearformed'] ?? 0) > 0) {
            $details[] = (string) $this->biography['yearformed'];
        }

        return implode(', ', $details);
    }

    public function getSummary(): ?string
    {
        $summary = trim((string) ($this->biography['summary'] ?? ''));

        return ($summary === '') ? null : $summary;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('artist_info.phtml');
    }
}
