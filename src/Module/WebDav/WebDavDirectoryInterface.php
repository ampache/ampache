<?php

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

namespace Ampache\Module\WebDav;

use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemEnum;

/**
 * library_item Interface
 *
 * This defines how the media file classes should
 * work, this lists all required functions and the expected
 * input
 */
interface WebDavDirectoryInterface extends library_item
{
    /**
     * get_childrens
     *
     * Get direct childrens. Return an array of `object_type`, `object_id` childrens.
     * @return array{string?: array<int, array{object_type: LibraryItemEnum, object_id: int}>}
     */
    public function get_childrens(): array;

    /**
     * Search for direct children of an object
     * @return array<int, array{object_type: LibraryItemEnum, object_id: int}>
     */
    public function get_children(string $name): array;
}
