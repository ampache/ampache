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

namespace Ampache\Module\Api\OpenSubsonic;

use Ampache\Module\Api\OpenSubsonic_Api;
use Ampache\Repository\Model\User;

final class MusicFolderResolver implements MusicFolderResolverInterface
{
    public function musicFolderId(array $input, User $user): int
    {
        $sub_id = $input['musicFolderId'] ?? null;
        if ($sub_id === null || $sub_id === '') {
            return 0;
        }

        return $this->musicFolders($input, $user)[0] ?? -1;
    }

    public function musicFolders(array $input, User $user): array
    {
        $catalogs = $user->get_catalogs('music');
        $sub_id   = $input['musicFolderId'] ?? null;
        if ($sub_id === null || $sub_id === '') {
            return $catalogs;
        }

        return array_values(array_intersect($catalogs, [(int) OpenSubsonic_Api::getAmpacheId((string) $sub_id)]));
    }
}
