<?php
declare(strict_types=0);
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

class Slideshow
{
    /**
     * @return array
     */
    public static function get_current_slideshow()
    {
        $songs  = Song::get_recently_played((int) Core::get_global('user')->id);
        $images = array();
        if (count($songs) > 0) {
            $last_song = new Song($songs[0]['object_id']);
            $last_song->format();
            $images = self::get_images($last_song->f_artist);
        }

        return $images;
    }

    /**
     * @param string $artist_name
     * @return array
     */
    protected static function get_images($artist_name)
    {
        $images = array();

        foreach (Plugin::get_plugins('get_photos') as $plugin_name) {
            $plugin = new Plugin($plugin_name);
            if ($plugin->load(Core::get_global('user'))) {
                $images += $plugin->_plugin->get_photos($artist_name);
            }
        }

        return $images;
    }
} // end slideshow.class
