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

namespace Ampache\Module\Api\Method\Api8;

use Ampache\Module\Api\Method\AbstractFolderMethod;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Folder;
use Override;

/**
 * Returns the children of a folder, found by its id
 *
 * Only api version 8 knows about folders.
 */
final class Folder8Method extends AbstractFolderMethod
{
    public const string ACTION = 'folder';

    protected const bool AS_OBJECT = false;

    /**
     * @param array<string, mixed> $input
     */
    #[Override]
    protected function narrowBrowse(Browse $browse, Folder $folder, array $input): void
    {
        $browse->set_filter('int_id', $this->objectId($input));
    }

    /**
     * @param array<string, mixed> $input
     */
    #[Override]
    protected function requestedFolder(array $input): string
    {
        return (string) $this->objectId($input);
    }

    /**
     * Always hands back a folder; the base checks whether it actually exists
     *
     * @param array<string, mixed> $input
     */
    #[Override]
    protected function resolveFolder(array $input): Folder
    {
        return new Folder($this->objectId($input));
    }

    /**
     * The root folder is -1
     *
     * @param array<string, mixed> $input
     */
    private function objectId(array $input): int
    {
        return (isset($input['filter'])) ? (int) $input['filter'] : -1;
    }
}
