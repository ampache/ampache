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
 * library_item Interface
 *
 * This defines how the media file classes should
 * work, this lists all required functions and the expected
 * input
 */
interface library_item extends playable_item
{
    /**
     * @return mixed
     */
    public function get_keywords();

    /**
     * @return mixed
     */
    public function get_user_owner();

    /**
     * @return mixed
     */
    public function get_default_art_kind();

    /**
     * @return mixed
     */
    public function get_description();

    /**
     * display_art
     * @param integer $thumb
     * @param boolean $force
     */
    public function display_art($thumb, $force = false);

    /**
     * @param array $data
     * @return mixed
     */
    public function update(array $data);

    /**
     * @return mixed
     */
    public static function garbage_collection();
} // end library_item.interface
