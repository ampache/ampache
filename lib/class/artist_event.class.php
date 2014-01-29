<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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

class Artist_Event
{
    /**
     * Constructor
     */
    private function __construct()
    {
        return false;
    } //constructor

    /**
     * get_upcoming_events
     * Returns a list of upcoming events
     */
    public static function get_upcoming_events($artist)
    {
        if (isset($artist->mbid)) {
            $query = 'mbid=' . rawurlencode($artist->mbid);
        } else {
            $query = 'artist=' . rawurlencode($artist->name);
        }

        $xml = Recommendation::get_lastfm_results('artist.getevents', $query);

        if ($xml->events) {
            return $xml->events;
        }

        return false;
    }

    /**
     * get_past_events
     * Returns a list of past events
     */
    public static function get_past_events($artist)
    {
        if (isset($artist->mbid)) {
            $query = 'mbid=' . rawurlencode($artist->mbid);
        } else {
            $query = 'artist=' . rawurlencode($artist->name);
        }

        $xml = Recommendation::get_lastfm_results('artist.getpastevents', $query);

        if ($xml->events) {
            return $xml->events;
        }

        return false;
    }

} // end of recommendation class
