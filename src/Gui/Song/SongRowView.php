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

namespace Ampache\Gui\Song;

use Ampache\Gui\System\ConfigViewAdapterInterface;
use Ampache\Gui\View\AbstractView;
use Override;

/**
 * One row of a song browse.
 *
 * Built fresh per row rather than reused with reassigned context, so a value from the previous row cannot
 * survive into the next one.
 */
final class SongRowView extends AbstractView
{
    public function __construct(
        private readonly SongViewAdapterInterface $song,
        private readonly ConfigViewAdapterInterface $config,
        private readonly string $argumentParam,
        private readonly bool $usingRatings,
        private readonly bool $isTableView,
        private readonly bool $isAlbumGroup,
        private readonly bool $isShowTrack,
        private readonly bool $isShowLicense,
        private readonly bool $isHideGenre,
        private readonly bool $isHideMood,
        private readonly bool $isHideArtist,
        private readonly bool $isHideAlbum,
        private readonly bool $isHideYear,
        private readonly bool $isHideDrag,
    ) {}

    public function getArgumentParam(): string
    {
        return $this->argumentParam;
    }

    public function getConfig(): ConfigViewAdapterInterface
    {
        return $this->config;
    }

    public function getSong(): SongViewAdapterInterface
    {
        return $this->song;
    }

    public function isAlbumGroup(): bool
    {
        return $this->isAlbumGroup;
    }

    public function isHideAlbum(): bool
    {
        return $this->isHideAlbum;
    }

    public function isHideArtist(): bool
    {
        return $this->isHideArtist;
    }

    public function isHideDrag(): bool
    {
        return $this->isHideDrag;
    }

    public function isHideGenre(): bool
    {
        return $this->isHideGenre;
    }

    public function isHideMood(): bool
    {
        return $this->isHideMood;
    }

    public function isHideYear(): bool
    {
        return $this->isHideYear;
    }

    public function isShowLicense(): bool
    {
        return $this->isShowLicense;
    }

    public function isShowTrack(): bool
    {
        return $this->isShowTrack;
    }

    public function isTableView(): bool
    {
        return $this->isTableView;
    }

    public function isUsingRatings(): bool
    {
        return $this->usingRatings;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('song_row.phtml');
    }
}
