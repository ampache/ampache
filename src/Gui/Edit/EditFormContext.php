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

namespace Ampache\Gui\Edit;

use Ampache\Module\Metadata\MetadataManagerInterface;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\Share;

/**
 * Everything the edit dialog hands a form, decided per request.
 *
 * Data only, like `BrowseListContext`. `Share` is here beside `library_item` because a share is editable
 * but is not a library item, so it never resolves through `LibraryItemLoaderInterface`.
 */
final readonly class EditFormContext
{
    /**
     * @param array<int, string> $users
     */
    public function __construct(
        public string $objectType,
        public library_item|Share $item,
        public array $users,
        public MetadataManagerInterface $metadataManager,
        public ZipHandlerInterface $zipHandler,
    ) {}
}
