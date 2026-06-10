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

namespace Ampache\Module\Folder\Deletion;

use Ampache\Module\Art\ArtCleanupInterface;
use Ampache\Repository\FolderRepositoryInterface;
use Ampache\Repository\Model\Folder;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\UserActivityRepositoryInterface;

final readonly class FolderDeleter implements FolderDeleterInterface
{
    public function __construct(
        private ShoutRepositoryInterface $shoutRepository,
        private FolderRepositoryInterface $folderRepository,
        private UserActivityRepositoryInterface $useractivityRepository,
        private ArtCleanupInterface $artCleanup,
    ) {
    }

    public function delete(
        Folder $folder,
    ): void {
        $folderId = $folder->getId();

        $this->folderRepository->delete($folderId);
        $this->artCleanup->collectGarbageForObject('folder', $folderId);
        Userflag::garbage_collection('folder', $folderId);
        Rating::garbage_collection('folder', $folderId);
        $this->shoutRepository->collectGarbage('folder', $folderId);
        $this->useractivityRepository->collectGarbage('folder', $folderId);
    }
}
