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
 * The genres that have been merged away, listed so a manager can edit or delete them.
 */
final class HiddenGenreCloudView extends AbstractView
{
    /**
     * @param array<int, array{id: int, name: string}> $genres
     */
    public function __construct(
        private readonly GenreFormView $form,
        private readonly string $ajaxUri,
        private readonly int $browseId,
        private readonly array $genres,
        private readonly bool $mayEdit,
    ) {}

    public function getAjaxUri(): string
    {
        return $this->ajaxUri;
    }

    public function getBrowseArgument(): string
    {
        return '&browse_id=' . $this->browseId;
    }

    public function getForm(): GenreFormView
    {
        return $this->form;
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function getGenres(): array
    {
        return $this->genres;
    }

    public function mayEdit(): bool
    {
        return $this->mayEdit;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('hidden_genre_cloud.phtml');
    }
}
