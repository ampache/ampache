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

use Ampache\Gui\View\AbstractView;
use Ampache\Repository\Model\Song_Preview;
use Override;

/**
 * One row of a missing-album track listing.
 */
final class SongPreviewRowView extends AbstractView
{
    public function __construct(
        private readonly Song_Preview $preview,
        private readonly bool $directPlay,
        private readonly bool $autoplayNext,
        private readonly bool $autoplayAppend,
    ) {}

    public function getPreview(): Song_Preview
    {
        return $this->preview;
    }

    public function isAutoplayAppendEnabled(): bool
    {
        return $this->autoplayAppend;
    }

    public function isAutoplayNextEnabled(): bool
    {
        return $this->autoplayNext;
    }

    public function isDirectPlayEnabled(): bool
    {
        return $this->directPlay;
    }

    /**
     * A preview with no file behind it cannot be played or queued, so those controls are left out entirely.
     */
    public function isPlayable(): bool
    {
        return $this->preview->file !== null && $this->preview->file !== '';
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('song_preview_row.phtml');
    }
}
