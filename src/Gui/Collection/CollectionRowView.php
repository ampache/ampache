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

use Ampache\Gui\View\AbstractView;
use Ampache\Repository\Model\Collection;
use Override;

/**
 * One row of the collection browse.
 */
final class CollectionRowView extends AbstractView
{
    public function __construct(
        private readonly string $webPath,
        private readonly Collection $collection,
        private readonly string $coverClass,
        private readonly bool $showDirectPlay,
        private readonly bool $showRatings,
    ) {}

    /**
     * Linked rather than picker-enabled: `Art::display()` only offers the edit and clear actions when it is
     * given no link, and on a browse row the thumbnail should navigate to the collection.
     */
    public function getArt(): string
    {
        ob_start();
        $this->collection->display_art(['width' => 100, 'height' => 100], true, true);

        return (string) ob_get_clean();
    }

    public function getCollectionId(): int
    {
        return $this->collection->getId();
    }

    public function getCoverClass(): string
    {
        return $this->coverClass;
    }

    public function getFullname(): string
    {
        return (string) $this->collection->get_fullname();
    }

    public function getItemCount(): int
    {
        return $this->collection->get_item_count();
    }

    public function getLink(): string
    {
        return $this->collection->get_link();
    }

    /**
     * A collection with no pinned type holds anything, which reads better than an empty cell.
     */
    public function getObjectType(): string
    {
        return ($this->collection->object_type === null || $this->collection->object_type === '')
            ? T_('Mixed')
            : $this->collection->object_type;
    }

    public function getOwner(): string
    {
        return (string) $this->collection->username;
    }

    public function getWebPath(): string
    {
        return $this->webPath;
    }

    public function isPrivate(): bool
    {
        return $this->collection->isPrivate();
    }

    public function mayCollaborate(): bool
    {
        return $this->collection->has_collaborate();
    }

    public function mayDelete(): bool
    {
        return $this->collection->has_access();
    }

    public function showDirectPlay(): bool
    {
        return $this->showDirectPlay && $this->getItemCount() > 0;
    }

    public function showRatings(): bool
    {
        return $this->showRatings;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('collection_row.phtml');
    }
}
