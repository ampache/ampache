<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

class Slideshow
{
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

    protected static function get_images($artist_name)
    {
        $images = array();
        if (AmpConfig::get('echonest_api_key')) {
            $echonest = new EchoNest_Client(new EchoNest_HttpClient_Requests());
            $echonest->authenticate(AmpConfig::get('echonest_api_key'));

            try {
                $images = $echonest->getArtistApi()->setName($artist_name)->getImages();
            } catch (Exception $error) {
                debug_event('slideshow.class', 'EchoNest artist images error: ' . $error->getMessage(), 1);
            }
        }

        foreach (Plugin::get_plugins('get_photos') as $plugin_name) {
            $plugin = new Plugin($plugin_name);
            if ($plugin->load(Core::get_global('user'))) {
                $images += $plugin->_plugin->get_photos($artist_name);
            }
        }

        return $images;
    }
} // end of Slideshow class
