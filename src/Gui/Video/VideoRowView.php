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

namespace Ampache\Gui\Video;

use Ampache\Gui\View\AbstractView;
use Ampache\Repository\Model\Video;
use Override;

/**
 * One row of a video browse.
 *
 * The access checks and config reads the template used to make for itself are decided by the caller.
 */
final class VideoRowView extends AbstractView
{
    public function __construct(
        private readonly Video $video,
        private readonly string $webPath,
        private readonly string $classCover,
        private readonly string $classCounter,
        private readonly string $classTags,
        private readonly string $classMoods,
        private readonly int $browseId,
        private readonly bool $gridView,
        private readonly bool $hideGenres,
        private readonly bool $hideMoods,
        private readonly bool $showRatings,
        private readonly bool $showPlayedTimes,
        private readonly bool $directplayEnabled,
        private readonly bool $canAddToPlaylist,
        private readonly bool $canPostShout,
        private readonly bool $canShare,
        private readonly bool $canDownload,
        private readonly bool $canEdit,
        private readonly bool $canDelete,
    ) {}

    public function canAddToPlaylist(): bool
    {
        return $this->canAddToPlaylist;
    }

    public function canDelete(): bool
    {
        return $this->canDelete;
    }

    public function canDownload(): bool
    {
        return $this->canDownload;
    }

    public function canEdit(): bool
    {
        return $this->canEdit;
    }

    public function canPostShout(): bool
    {
        return $this->canPostShout;
    }

    public function canShare(): bool
    {
        return $this->canShare;
    }

    /**
     * @return array{width: int, height: int}
     */
    public function getArtSize(): array
    {
        return ($this->gridView)
            ? ['width' => 200, 'height' => 300]
            : ['width' => 100, 'height' => 150];
    }

    public function getBrowseId(): int
    {
        return $this->browseId;
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

    public function getVideo(): Video
    {
        return $this->video;
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    public function isDirectplayEnabled(): bool
    {
        return $this->directplayEnabled;
    }

    public function isHideGenres(): bool
    {
        return $this->hideGenres;
    }

    public function isHideMoods(): bool
    {
        return $this->hideMoods;
    }

    public function isShowPlayedTimes(): bool
    {
        return $this->showPlayedTimes;
    }

    public function isShowRatings(): bool
    {
        return $this->showRatings;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('video_row.phtml');
    }
}
