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

namespace Ampache\Module\Album\Deletion;

use Ampache\Module\Album\Deletion\Exception\AlbumDiskDeletionException;
use Ampache\Repository\Model\AlbumDisk;

interface AlbumDiskDeleterInterface
{
    /**
     * Deletes one disk's songs and its own row, finishing off the parent album once it has no disk left.
     *
     * @return bool Whether the now-empty parent album was deleted along with the disk
     *
     * @throws AlbumDiskDeletionException
     */
    public function delete(AlbumDisk $albumDisk): bool;
}
