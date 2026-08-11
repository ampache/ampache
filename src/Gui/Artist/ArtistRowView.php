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
use Override;

/**
 * One row of an artist browse.
 *
 * The access checks and config reads the template used to make for itself are decided by the caller. The
 * shout, edit and delete links all sat inside one authentication check, so that check is folded into each.
 */
final class ArtistRowView extends AbstractView
{
    public function __construct(
        private readonly Artist $artist,
        private readonly string $webPath,
        private readonly string $classCover,
        private readonly string $classArtist,
        private readonly string $classTime,
        private readonly string $classCounter,
        private readonly string $classTags,
        private readonly string $classMoods,
        private readonly int $browseId,
        private readonly bool $gridView,
        private readonly bool $hideGenres,
        private readonly bool $hideMoods,
        private readonly bool $showRatings,
        private readonly bool $showPlayedTimes,
        private readonly bool $showDirectPlay,
        private readonly bool $showPlaylistAdd,
        private readonly bool $canPostShout,
        private readonly bool $canEdit,
        private readonly bool $canDelete,
    ) {}

    public function canDelete(): bool
    {
        return $this->canDelete;
    }

    public function canEdit(): bool
    {
        return $this->canEdit;
    }

    public function canPostShout(): bool
    {
        return $this->canPostShout;
    }

    public function getArtist(): Artist
    {
        return $this->artist;
    }

    /**
     * @return array{width: int, height: int}
     */
    public function getArtSize(): array
    {
        return ($this->gridView)
            ? ['width' => 150, 'height' => 150]
            : ['width' => 100, 'height' => 100];
    }

    public function getBrowseId(): int
    {
        return $this->browseId;
    }

    public function getClassArtist(): string
    {
        return $this->classArtist;
    }

    public function getClassCounter(): string
    {
        return $this->classCounter;
    }

    public function getClassCover(): string
    {
        return $this->classCover;
    }

    public function getClassMoods(): string
    {
        return $this->classMoods;
    }

    public function getClassTags(): string
    {
        return $this->classTags;
    }

    public function getClassTime(): string
    {
        return $this->classTime;
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    public function isHideGenres(): bool
    {
        return $this->hideGenres;
    }

    public function isHideMoods(): bool
    {
        return $this->hideMoods;
    }

    public function isShowDirectPlay(): bool
    {
        return $this->showDirectPlay;
    }

    public function isShowPlayedTimes(): bool
    {
        return $this->showPlayedTimes;
    }

    public function isShowPlaylistAdd(): bool
    {
        return $this->showPlaylistAdd;
    }

    public function isShowRatings(): bool
    {
        return $this->showRatings;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('artist_row.phtml');
    }
}
