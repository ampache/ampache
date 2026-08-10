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

namespace Ampache\Gui\Folder;

use Ampache\Gui\View\AbstractView;
use Ampache\Module\Database\Query\Browse;
use Ampache\Repository\Model\Folder;
use Override;

/**
 * The listing for one folder, with its contents beneath.
 *
 * Every action here queues what sits below the folder, subfolders included, so the media count is what
 * decides whether any of them are offered.
 */
final class FolderView extends AbstractView
{
    public function __construct(
        private readonly Folder $folder,
        private readonly Browse $browse,
        private readonly string $browseForm,
        private readonly int $mediaCount,
        private readonly int $directPlayLimit,
        private readonly bool $directPlay,
        private readonly bool $mayInteract,
        private readonly bool $autoplayNext,
        private readonly bool $autoplayAppend,
    ) {}

    public function getBrowse(): Browse
    {
        return $this->browse;
    }

    public function getBrowseForm(): string
    {
        return $this->browseForm;
    }

    public function getFolder(): Folder
    {
        return $this->folder;
    }

    /**
     * The root listing is a virtual folder, so it shows its own name rather than a trail.
     */
    public function getTitle(): string
    {
        $name = $this->e((string) $this->folder->get_fullname());
        if ($this->folder->getId() === -1) {
            return $name;
        }

        $parentLink = ($this->folder->parent !== null)
            ? $this->folder->get_f_parent_link()
            : $this->folder->get_f_home_link();

        return $parentLink . '&nbsp;\\&nbsp;' . $name;
    }

    public function isAutoplayAppendEnabled(): bool
    {
        return $this->autoplayAppend;
    }

    public function isAutoplayNextEnabled(): bool
    {
        return $this->autoplayNext;
    }

    /**
     * A folder holding more than the direct-play limit is not offered for queueing at all.
     */
    public function isPlayShown(): bool
    {
        return $this->directPlay && $this->isQueueShown();
    }

    public function isQueueShown(): bool
    {
        if ($this->mediaCount < 1 || !$this->mayInteract) {
            return false;
        }

        return $this->directPlayLimit <= 0 || $this->mediaCount <= $this->directPlayLimit;
    }

    #[Override]
    protected function templateFile(): string
    {
        return $this->findTemplate('folder.phtml');
    }
}
