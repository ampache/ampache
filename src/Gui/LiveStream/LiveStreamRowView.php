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

namespace Ampache\Gui\LiveStream;

use Ampache\Gui\View\AbstractView;
use Ampache\Repository\Model\Live_Stream;
use Override;

/**
 * One row of a radio-station browse.
 *
 * The access checks the template used to make for itself are decided by the caller, so the row only prints.
 */
final class LiveStreamRowView extends AbstractView
{
    public function __construct(
        private readonly Live_Stream $liveStream,
        private readonly string $classCover,
        private readonly int $browseId,
        private readonly bool $gridView,
        private readonly bool $showRatings,
        private readonly bool $directplayEnabled,
        private readonly bool $canAddToPlaylist,
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

    public function canEdit(): bool
    {
        return $this->canEdit;
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

    public function getClassCover(): string
    {
        return $this->classCover;
    }

    public function getLiveStream(): Live_Stream
    {
        return $this->liveStream;
    }

    public function isDirectplayEnabled(): bool
    {
        return $this->directplayEnabled;
    }

    public function isShowRatings(): bool
    {
        return $this->showRatings;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('live_stream_row.phtml');
    }
}
