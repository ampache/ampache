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

namespace Ampache\Gui\Collection;

use Ampache\Gui\View\TemplateInterface;
use Ampache\Module\Database\Query\Browse;
use Ampache\Repository\Model\Collection;
use Ampache\Repository\Model\LibraryItemEnum;

interface CollectionViewAdapterInterface extends TemplateInterface
{
    public function canDelete(): bool;

    public function canDirectPlay(): bool;

    public function canEdit(): bool;

    public function canPlayAppend(): bool;

    public function canPlayNext(): bool;

    public function canReorder(): bool;

    public function createBrowse(): Browse;

    /**
     * @return array{width: int, height: int}
     */
    public function getArtSize(): array;

    public function getCollection(): Collection;

    public function getDeletionConfirmation(): string;

    public function getDeletionIcon(): string;

    public function getDeletionUrl(): string;

    public function getDirectplayButton(): string;

    public function getEditDialogTitle(): string;

    public function getEditIcon(): string;

    public function getId(): int;

    /**
     * @return list<int>
     */
    public function getMemberIds(): array;

    public function getName(): string;

    /**
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int, track_id: int, track: int, time: int}>
     */
    public function getObjectIds(): array;

    public function getOwner(): string;

    /**
     * The browse type a pinned collection renders through, or null when the members go into one mixed list.
     */
    public function getPinnedBrowseType(): ?string;

    public function getPlayLastButton(): string;

    public function getPlayNextButton(): string;

    public function getRating(): string;

    public function getReorderConfirmation(): string;

    public function getReorderIcon(): string;

    public function getTrackNumbersUrl(): string;

    /**
     * The plain-text type label, either the pinned type or the word for holding anything.
     */
    public function getTypeLabel(): string;

    public function getUserFlags(): string;

    public function isEmpty(): bool;

    public function isMixed(): bool;

    public function isPlayable(): bool;

    public function isRatingsEnabled(): bool;
}
