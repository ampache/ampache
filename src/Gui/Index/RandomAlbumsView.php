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

namespace Ampache\Gui\Index;

use Ampache\Gui\View\AbstractView;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\LibraryItemEnum;
use Override;

/**
 * The "Albums of the Moment" panel on the home page.
 *
 * It renders albums or album disks depending on the album_group preference, so the two spellings share
 * one view rather than drifting apart as two copies of the same markup.
 */
final class RandomAlbumsView extends AbstractView
{
    /**
     * @param list<int> $objectIds
     */
    public function __construct(
        private readonly LibraryItemEnum $type,
        private readonly array $objectIds,
        private readonly bool $gridView,
        private readonly bool $directPlay,
        private readonly bool $autoplayNext,
        private readonly bool $autoplayAppend,
        private readonly bool $showRatings,
    ) {}

    public function areRatingsShown(): bool
    {
        return $this->showRatings;
    }

    /**
     * @return array{width: int, height: int}
     */
    public function getArtSize(): array
    {
        return $this->gridView
            ? ['width' => 150, 'height' => 150]
            : ['width' => 100, 'height' => 100];
    }

    /**
     * @return list<Album|AlbumDisk>
     */
    public function getItems(): array
    {
        return array_map(
            fn(int $objectId): Album|AlbumDisk => ($this->type === LibraryItemEnum::ALBUM)
                ? new Album($objectId)
                : new AlbumDisk($objectId),
            $this->objectIds
        );
    }

    public function getObjectType(): string
    {
        return $this->type->value;
    }

    public function getTitle(): string
    {
        return T_('Albums of the Moment');
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
     * The grid layout has no room for the play overlay, so it is dropped rather than overlapping the art.
     */
    public function isPlayShown(): bool
    {
        return !$this->gridView;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('random_albums.phtml');
    }
}
