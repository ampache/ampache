<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

class Slideshow
{
    public static function get_current_slideshow()
    {
        $songs = Song::get_recently_played($GLOBALS['user']->id);
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
            } catch (Exception $e) {
                debug_event('echonest', 'EchoNest artist images error: ' . $e->getMessage(), '1');
            }
        }

        foreach (Plugin::get_plugins('get_photos') as $plugin_name) {
            $plugin = new Plugin($plugin_name);
            if ($plugin->load($GLOBALS['user'])) {
                $images += $plugin->_plugin->get_photos($artist_name);
            }
        }

        return $images;
    }

} // end of Slideshow class
