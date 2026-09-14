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

namespace Ampache\Module\Api\Subsonic;

use Ampache\Repository\Model\User;

interface MusicFolderResolverInterface
{
    /**
     * Resolve a requested musicFolderId into a single catalog id to filter on.
     * 0 means no folder was requested; -1 can never match a catalog so a folder the user can't browse returns
     * nothing instead of everything.
     * @param array<string, mixed> $input
     */
    public function musicFolderId(array $input, User $user): int;

    /**
     * Resolve the catalogs a browse request should be limited to.
     * A requested musicFolderId is always intersected with the catalogs the user may browse.
     * @param array<string, mixed> $input
     * @return int[]
     */
    public function musicFolders(array $input, User $user): array;
}
