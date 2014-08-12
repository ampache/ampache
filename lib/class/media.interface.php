<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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
     */
    public function get_stream_types();

    /**
     * play_url
     *
     * Returns the url to stream the specified object
     *
     */
    public static function play_url($oid, $additional_params='', $local=false);

    /**
     * get_transcode_settings
     *
     * Should only be called if 'transcode' was returned by get_stream_types
     * Returns a raw transcode command for this item; the optional target
     * parameter can be used to request a specific format instead of the
     * default from the configuration file.
     */
    public function get_transcode_settings($target = null, $options=array());

    /**
     * get_stream_name
     * Get the complete name to display for the stream.
     */
    public function get_stream_name();

    public function set_played($user, $agent);

} // end interface
