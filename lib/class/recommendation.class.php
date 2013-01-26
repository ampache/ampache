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

class Recommendation {

    /**
     * Constructor
     * Not on my watch, boyo.
     */
    private function __construct() {
        return false;
    } //constructor

    /**
     * get_lastfm_results
     * Runs a last.fm query and returns the parsed results
     */
    private static function get_lastfm_results($method, $query) {
        $api_key = Config::get('lastfm_api_key');
        $api_base = "http://ws.audioscrobbler.com/2.0/?method=";
        $url = $api_base . $method . '&api_key=' . $api_key . '&' . $query;
        debug_event('Recommendation', 'search url : ' . $url, 5);

        $snoopy = new Snoopy();
        if(Config::get('proxy_host') AND Config::get('proxy_port')) {
            $snoopy->proxy_user = Config::get('proxy_host');
            $snoopy->proxy_port = Config::get('proxy_port');
            $snoopy->proxy_user = Config::get('proxy_user');
            $snoopy->proxy_pass = Config::get('proxy_pass');
        }
        $snoopy->fetch($url);
        $content = $snoopy->results;
        
        return simplexml_load_string($content);
    } // get_lastfm_results

    /**
     * get_songs_like
     * Returns a list of similar songs
     */
    public static function get_songs_like($song_id, $limit = 5, $local_only = true) {
        $song = new Song($song_id);

        if (isset($song->mbid)) {
            $query = 'mbid=' . rawurlencode($song->mbid);
        }
        else {
            $query = 'track=' . rawurlencode($song->title);
        }

        if ($limit && !$local_only) {
            $query .= "&limit=$limit";
        }
        
        $xml = self::get_lastfm_results('track.getsimilar', $query);

        foreach ($xml->similartracks->children() as $child) {
            $name = $child->name;
            $local_id = null;

            $artist_name = $child->artist->name;
            $s_artist_name = Catalog::trim_prefix($artist_name);
            $s_artist_name = Dba::escape($s_artist_name['string']);

            $sql = "SELECT `song`.`id` FROM `song` " .
                "LEFT JOIN `artist` ON " .
                "`song`.`artist`=`artist`.`id` WHERE " .
                "`song`.`title`='" . Dba::escape($name) . 
                "' AND `artist`.`name`='$s_artist_name'";

            $db_result = Dba::read($sql);
            
            if ($result = Dba::fetch_assoc($db_result)) {
                $local_id = $result['id'];
            }

            if (is_null($local_id)) {
                debug_event('Recommendation', "$name did not match any local song", 5);
                if (! $local_only) {
                    $results[] = array(
                        'id' => null,
                        'title' => $name,
                        'artist' => $artist_name
                    );
                }
            }
            else {
                debug_event('Recommendation', "$name matched local song $local_id", 5);
                $results[] = array(
                    'id' => $local_id,
                    'title' => $name
                );
            }

            if ($limit && count($results) >= $limit) {
                break;
            }
        }

        if (isset($results)) {
            return $results;
        }

        return false;
    }

    /**
     * get_artists_like
     * Returns a list of similar artists
     */
    public static function get_artists_like($artist_id, $limit = 5, $local_only = true) {
        $artist = new Artist($artist_id);

        $query = 'artist=' . rawurlencode($artist->name);
        if ($limit && !$local_only) {
            $query .= "&limit=$limit";
        }

        $xml = self::get_lastfm_results('artist.getsimilar', $query);    

        foreach ($xml->similarartists->children() as $child) {
            $name = $child->name;
            $local_id = null;

            // First we check by MBID
            if ((string)$child->mbid) {
                $mbid = Dba::escape($child->mbid);
                $sql = "SELECT `id` FROM `artist` WHERE `mbid`='$mbid'";
                $db_result = Dba::read($sql);
                if ($result = Dba::fetch_assoc($db_result)) {
                    $local_id = $result['id'];
                }
            }

            // Then we fall back to the less likely to work exact
            // name match
            if (is_null($local_id)) {
                $searchname = Catalog::trim_prefix($name);
                $searchname = Dba::escape($searchname['string']);
                $sql = "SELECT `id` FROM `artist` WHERE `name`='$searchname'";
                $db_result = Dba::read($sql);
                if ($result = Dba::fetch_assoc($db_result)) {
                    $local_id = $result['id'];
                }
            }
            
            // Then we give up
            if (is_null($local_id)) {
                debug_event('Recommendation', "$name did not match any local artist", 5);
                if (! $local_only) {
                    $results[] = array(
                        'id' => null,
                        'name' => $name
                    );
                }
            }
            else {
                debug_event('Recommendation', "$name matched local artist " . $local_id, 5);
                $results[] = array(
                    'id' => $local_id,
                    'name' => $name
                );
            }

            // Don't do more work than we have to
            if ($limit && count($results) >= $limit) {
                break;
            }
        }

        if (isset($results)) {
            return $results;
        }
        
        return false;
    } // get_artists_like

} // end of recommendation class
?>
