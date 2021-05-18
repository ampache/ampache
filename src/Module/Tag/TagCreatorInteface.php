<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 */

namespace Ampache\Module\Tag;

interface TagCreatorInteface
{
    /**
     * This is a wrapper function, it figures out what we need to add, be it a tag
     * and map, or just the mapping
     */
    public function add(
        string $type,
        int $object_id,
        string $value
    ): ?int;

    /**
     * add_tag_map
     * This adds a specific tag to the map for specified object
     * @param string $type
     * @param integer|string $object_id
     * @param integer|string $tag_id
     * @param boolean $user
     * @return boolean|string|null
     */
    public function add_tag_map($type, $object_id, $tag_id, $user = true);
}
