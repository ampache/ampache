<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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
 *
 */

/**
 * playable_item Interface
 *
 * This defines how the playable item file classes should
 * work, this lists all required functions and the expected
 * input
 */
interface playable_item
{
    /**
     * format
     *
     * Creates member variables for output
     * @param boolean $details
     */
    public function format($details = true);

    /**
     * get_fullname
     *
     * Get the item full name.
     */
    public function get_fullname();

    /**
     * get_parent
     *
     * Get parent. Return parent `object_type`, `object_id` ; null otherwise.
     */
    public function get_parent();

    /**
     * get_childrens
     *
     * Get direct childrens. Return an array of `object_type`, `object_id` childrens.
     */
    public function get_childrens();

    /**
     * search_childrens
     *
     * Search for direct childrens. Return an array of `object_type`, `object_id` childrens matching the criteria.
     * @param string $name
     */
    public function search_childrens($name);

    /**
     * get_medias
     *
     * Get all medias from all childrens. Return an array of `object_type`, `object_id` medias.
     * @param string $filter_type
     * @return mixed
     */
    public function get_medias($filter_type = null);

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return integer[]
     */
    public function get_catalogs();
} // end playable_item.interface
