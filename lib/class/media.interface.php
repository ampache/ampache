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
 * media Interface
 *
 * This defines how the media file classes should
 * work, this lists all required functions and the expected
 * input
 */
interface media
{
    /**
     * get_stream_types
     *
     * Returns an array of strings; current types are 'native'
     * and 'transcode'
     * @param array $player
     */
    public function get_stream_types($player = array());

    /**
     * play_url
     *
     * Returns the url to stream the specified object
     * @param string $additional_params
     * @param string $player
     * @param boolean $local
     */
    public function play_url($additional_params = '', $player = '', $local = false);

    /**
     * get_transcode_settings
     *
     * Should only be called if 'transcode' was returned by get_stream_types
     * Returns a raw transcode command for this item; the optional target
     * parameter can be used to request a specific format instead of the
     * default from the configuration file.
     * @param string $target
     * @param string $player
     * @param array $options
     */
    public function get_transcode_settings($target = null, $player = null, $options = array());

    /**
     * get_stream_name
     * Get the complete name to display for the stream.
     */
    public function get_stream_name();

    /**
     * @param integer $user
     * @param string $agent
     * @param array $location
     * @param integer $date
     * @return boolean
     */
    public function set_played($user, $agent, $location, $date = null);

    /**
     * @param integer $user
     * @param string $agent
     * @param integer $date
     * @return boolean
     */
    public function check_play_history($user, $agent, $date);
} // end media.interface
