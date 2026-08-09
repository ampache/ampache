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

namespace Ampache\Gui\Playlist;

use Ampache\Gui\View\AbstractView;
use Ampache\Repository\Model\displayable_item;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\Playlist;
use Override;

/**
 * One media row of a playlist or collection listing.
 */
final class PlaylistMediaRowView extends AbstractView
{
    public function __construct(
        private readonly string $webPath,
        private readonly library_item&displayable_item $item,
        private readonly string $objectType,
        private readonly int $trackId,
        private readonly int $track,
        private readonly ?Playlist $playlist,
        private readonly int $browseId,
        private readonly string $coverClass,
        private readonly string $timeClass,
        private readonly bool $gridView,
        private readonly bool $showRatings,
        private readonly bool $showParent,
        private readonly bool $extendedLinks,
        private readonly bool $canMultiselect,
        private readonly bool $showMultiselect,
        private readonly bool $directPlay,
        private readonly bool $autoplayNext,
        private readonly bool $autoplayAppend,
        private readonly bool $mayAdd,
        private readonly bool $mayDownload,
        private readonly bool $mayShare,
        private readonly bool $mayRemove,
    ) {}

    public function canMultiselect(): bool
    {
        return $this->canMultiselect;
    }

    public function getArt(): string
    {
        ob_start();
        $this->item->display_art($this->gridView ? ['width' => 150, 'height' => 150] : ['width' => 80, 'height' => 80]);

        return (string) ob_get_clean();
    }

    public function getBrowseId(): int
    {
        return $this->browseId;
    }

    public function getCoverClass(): string
    {
        return $this->coverClass;
    }

    public function getItemId(): int
    {
        return $this->item->getId();
    }

    /**
     * The extended form names the parent beside the title, which is the only way a mixed list reads.
     */
    public function getLink(): string
    {
        $parent = $this->item->get_f_parent_link();

        return ($this->extendedLinks && !empty($parent))
            ? $this->item->get_f_link() . '&nbsp;-&nbsp;' . $parent
            : $this->item->get_f_link();
    }

    public function getObjectType(): string
    {
        return $this->objectType;
    }

    public function getParentLink(): string
    {
        return (string) $this->item->get_f_parent_link();
    }

    public function getPlaylistId(): int
    {
        return ($this->playlist instanceof Playlist) ? $this->playlist->getId() : 0;
    }

    public function getPublicLink(): string
    {
        return $this->item->get_link();
    }

    public function getTime(): string
    {
        return (string) $this->item->get_f_time();
    }

    public function getTimeClass(): string
    {
        return $this->timeClass;
    }

    public function getTrack(): int
    {
        return $this->track;
    }

    public function getTrackId(): int
    {
        return $this->trackId;
    }

    public function getWebPath('/client'): string
    {
        return $this->webPath;
    }

    public function isAutoplayAppend(): bool
    {
        return $this->autoplayAppend;
    }

    public function isAutoplayNext(): bool
    {
        return $this->autoplayNext;
    }

    public function isDirectPlay(): bool
    {
        return $this->directPlay;
    }

    public function mayAdd(): bool
    {
        return $this->mayAdd;
    }

    public function mayDownload(): bool
    {
        return $this->mayDownload;
    }

    public function mayRemove(): bool
    {
        return $this->mayRemove;
    }

    public function mayShare(): bool
    {
        return $this->mayShare;
    }

    public function showMultiselect(): bool
    {
        return $this->showMultiselect;
    }

    public function showParent(): bool
    {
        return $this->showParent;
    }

    public function showRatings(): bool
    {
        return $this->showRatings;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('playlist_media_row.phtml');
    }
}
