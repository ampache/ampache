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

namespace Ampache\Gui\Genre;

use Ampache\Gui\View\AbstractView;
use Override;

/**
 * The row of category links above a genre browse.
 */
final class GenreFormView extends AbstractView
{
    public function __construct(
        private readonly string $webPath,
        private readonly string $currentType,
        private readonly bool $albumsAreGrouped,
        private readonly bool $videoEnabled,
        private readonly bool $hasHiddenGenres,
    ) {}

    /**
     * The album link follows the album_group preference, so its tab stays current under either spelling.
     *
     * @return list<array{type: string, label: string, current: bool}>
     */
    public function getCategories(): array
    {
        $categories = [
            ['type' => 'song', 'label' => T_('Songs'), 'current' => $this->currentType === 'song'],
            [
                'type' => $this->albumsAreGrouped ? 'album' : 'album_disk',
                'label' => T_('Albums'),
                'current' => in_array($this->currentType, ['album', 'album_disk'], true),
            ],
            [
                'type' => 'artist',
                'label' => T_('Artists'),
                'current' => in_array($this->currentType, ['artist', 'album_artist'], true),
            ],
        ];

        if ($this->videoEnabled) {
            $categories[] = ['type' => 'video', 'label' => T_('Videos'), 'current' => $this->currentType === 'video'];
        }

        if ($this->hasHiddenGenres) {
            $categories[] = ['type' => 'tag_hidden', 'label' => T_('Hidden'), 'current' => $this->currentType === 'tag_hidden'];
        }

        return $categories;
    }

    public function getWebPath('/client'): string
    {
        return $this->webPath;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('genre_form.phtml');
    }
}
