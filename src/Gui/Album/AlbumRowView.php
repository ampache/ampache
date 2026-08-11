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

namespace Ampache\Gui\Album;

use Ampache\Gui\System\ConfigViewAdapterInterface;
use Ampache\Gui\View\AbstractView;
use Override;

/**
 * One row of an album browse.
 *
 * Built fresh per row rather than reused with reassigned context, so a value from the previous row cannot
 * survive into the next one.
 */
final class AlbumRowView extends AbstractView
{
    public function __construct(
        private readonly AlbumViewAdapterInterface $album,
        private readonly ConfigViewAdapterInterface $config,
        private readonly bool $usingRatings,
        private readonly bool $isHideGenre,
        private readonly bool $isHideMood,
        private readonly bool $isShowPlayedTimes,
        private readonly bool $isShowPlaylistAdd,
        private readonly string $classCover,
        private readonly string $classAlbum,
        private readonly string $classArtist,
        private readonly string $classTags,
        private readonly string $classMoods,
        private readonly string $classCounter,
    ) {}

    public function getAlbum(): AlbumViewAdapterInterface
    {
        return $this->album;
    }

    public function getClassAlbum(): string
    {
        return $this->classAlbum;
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

    public function getConfig(): ConfigViewAdapterInterface
    {
        return $this->config;
    }

    public function isHideGenre(): bool
    {
        return $this->isHideGenre;
    }

    public function isHideMood(): bool
    {
        return $this->isHideMood;
    }

    public function isShowPlayedTimes(): bool
    {
        return $this->isShowPlayedTimes;
    }

    public function isShowPlaylistAdd(): bool
    {
        return $this->isShowPlaylistAdd;
    }

    public function isUsingRatings(): bool
    {
        return $this->usingRatings;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('album_row.phtml');
    }
}
