<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

namespace Ampache\Module\Playback\Localplay;

use Ampache\Config\AmpConfig;
use Ampache\Module\Playback\Stream_Url;

/**
 * localplay_controller Class
 *
 * This is the abstract class for any Localplay controller
 *
 */
abstract class localplay_controller
{
    // Required Functions
    /**
     * @param Stream_Url $url
     * @return mixed
     */
    abstract public function add_url(Stream_Url $url); // Takes an array of song_ids

    /**
     * Takes a single object_id and removes it from the playlist
     * @param integer $object_id
     * @return mixed
     */
    abstract public function delete_track($object_id);

    abstract public function play();

    abstract public function stop();

    abstract public function get();

    abstract public function connect();

    abstract public function get_version(); // Returns the version of this plugin

    abstract public function get_description(); // Returns the description

    abstract public function is_installed(); // Returns an boolean t/f

    abstract public function install();

    abstract public function uninstall();

    /**
     * @return array
     */
    abstract public function status(): array;

    // For display we need the following 'instance' functions

    /**
     * @param $data
     * @return mixed
     */
    abstract public function add_instance($data);

    /**
     * @param $uid
     * @return mixed
     */
    abstract public function delete_instance($uid);

    /**
     * @param $uid
     * @param array $data
     * @return mixed
     */
    abstract public function update_instance($uid, $data);

    abstract public function get_instances();

    abstract public function instance_fields(): array;

    /**
     * @param $uid
     * @return mixed
     */
    abstract public function set_active_instance($uid);

    abstract public function get_active_instance();

    /**
     * get_url
     * This returns the URL for the passed object
     * @param $object
     * @return mixed
     */
    public function get_url($object)
    {
        // This might not be an object!
        if (!is_object($object)) {
            // Stupidly we'll just blindly add it for now
            return $object;
        }

        $class = get_class($object);

        return call_user_func(array($class, 'play_url'), $object->id);
    } // get_url

    /**
     * get_file
     * This returns the Filename for the passed object, not
     * always possible
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param $object
     */
    public function get_file($object)
    {
    } // get_file

    /**
     * parse_url
     * This takes an Ampache URL and then returns the 'primary' part of it
     * So that it's easier for Localplay modules to return valid song information
     * @param $url
     * @return array
     */
    public function parse_url($url)
    {
        // Define possible 'primary' keys
        $primary_array = array('oid', 'demo_id', 'random');
        $data          = array();

        //beautiful urls need their own parsing as parse_url will find nothing.
        if (AmpConfig::get('stream_beautiful_url')) {
            preg_match('/oid[\=|\/](.*?)[\&|\/]/', $url, $match);
            if (array_key_exists(1, $match) && $match[1]) {
                return array(
                    'primary_key' => 'oid',
                    'oid' => $match[1]
                );
            }
            preg_match('/demo_id.(.*)/', $url, $match);
            if (array_key_exists(1, $match) && $match[1]) {
                return array(
                    'primary_key' => 'demo_id',
                    'oid' => $match[1]
                );
            }
            preg_match_all('#\b(random_id|random_type)=([^&]*)#', $url, $match);
            if (array_key_exists(1, $match) && $match[1] && array_key_exists(2, $match) && $match[2]) {
                $result = array_combine($match[1], $match[2]);

                return array(
                    'primary_key' => $result['random_type'],
                    'oid' => $result['random_id']
                );
            }
        }
        $variables = parse_url($url, PHP_URL_QUERY);
        if ($variables) {
            parse_str($variables, $data);
            foreach ($primary_array as $pkey) {
                if (array_key_exists($pkey, $data)) {
                    $data['primary_key'] = $pkey;

                    return $data;
                }
            } // end foreach
        }

        return $data;
    } // parse_url
} // end localplay_controller.abstract
