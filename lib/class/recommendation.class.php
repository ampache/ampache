<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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

class Recommendation
{
    /**
     * Constructor
     * Not on my watch, boyo.
     */
    private function __construct()
    {
        return false;
    } //constructor

    /**
     * get_lastfm_results
     * Runs a last.fm query and returns the parsed results
     */
    public static function get_lastfm_results($method, $query)
    {
        $api_key  = AmpConfig::get('lastfm_api_key');
        $api_base = "http://ws.audioscrobbler.com/2.0/?method=";
        $url      = $api_base . $method . '&api_key=' . $api_key . '&' . $query;

        return self::query_lastfm($url);
    }

    public static function query_lastfm($url)
    {
        debug_event('Recommendation', 'search url : ' . $url, 5);

        $request = Requests::get($url, array(), Core::requests_options());
        $content = $request->body;

        return simplexml_load_string($content);
    }

    public static function album_search($artist, $album)
    {
        $api_key = AmpConfig::get('lastfm_api_key');
        $url     = 'http://ws.audioscrobbler.com/2.0/?method=album.getInfo&artist=' . urlencode($artist) . '&album=' . urlencode($album) . '&api_key=' . $api_key;

        return self::query_lastfm($url);
    }

    /**
     * gc
     *
     * This cleans out old recommendations cache
     */
    public static function gc()
    {
        Dba::write('DELETE FROM `recommendation` WHERE `last_update` < ?', array((time() - 604800)));
    }

    protected static function get_recommendation_cache($type, $id, $get_items = false)
    {
        self::gc();

        $sql        = "SELECT `id`, `last_update` FROM `recommendation` WHERE `object_type` = ? AND `object_id` = ?";
        $db_results = Dba::read($sql, array($type, $id));

        if ($cache = Dba::fetch_assoc($db_results)) {
            if ($get_items) {
                $cache['items'] = array();
                $sql            = "SELECT `recommendation_id`, `name`, `rel`, `mbid` FROM `recommendation_item` WHERE `recommendation` = ?";
                $db_results     = Dba::read($sql, array($cache['id']));
                while ($results = Dba::fetch_assoc($db_results)) {
                    $cache['items'][] = array(
                        'id' => $results['recommendation_id'],
                        'name' => $results['name'],
                        'rel' => $results['rel'],
                        'mbid' => $results['mbid'],
                    );
                }
            }
        }

        return $cache;
    }

    protected static function delete_recommendation_cache($type, $id)
    {
        $cache = self::get_recommendation_cache($type, $id);
        if ($cache['id']) {
            Dba::write('DELETE FROM `recommendation_item` WHERE `recommendation` = ?', array($cache['id']));
            Dba::write('DELETE FROM `recommendation` WHERE `id` = ?', array($cache['id']));
        }
    }

    protected static function update_recommendation_cache($type, $id, $recommendations)
    {
        self::delete_recommendation_cache($type, $id);
        $sql = "INSERT INTO `recommendation` (`object_type`, `object_id`, `last_update`) VALUES (?, ?, ?)";
        Dba::write($sql, array($type, $id, time()));
        $insertid = Dba::insert_id();
        foreach ($recommendations as $recommendation) {
            $sql = "INSERT INTO `recommendation_item` (`recommendation`, `recommendation_id`, `name`, `rel`, `mbid`) VALUES (?, ?, ?, ?, ?)";
            Dba::write($sql, array($insertid, $recommendation['id'], $recommendation['name'], $recommendation['rel'], $recommendation['mbid']));
        }
    }

    /**
     * get_songs_like
     * Returns a list of similar songs
     */
    public static function get_songs_like($song_id, $limit = 5, $local_only = true)
    {
        $song = new Song($song_id);

        if (isset($song->mbid)) {
            $query = 'mbid=' . rawurlencode($song->mbid);
        } else {
            $query = 'track=' . rawurlencode($song->title);
        }

        $cache = self::get_recommendation_cache('song', $song_id, true);
        if (!$cache['id']) {
            $similars = array();
            $xml      = self::get_lastfm_results('track.getsimilar', $query);

            if ($xml->similartracks) {
                foreach ($xml->similartracks->children() as $child) {
                    $name     = $child->name;
                    $local_id = null;

                    $artist_name   = $child->artist->name;
                    $s_artist_name = Catalog::trim_prefix($artist_name);

                    $sql = "SELECT `song`.`id` FROM `song` " .
                        "LEFT JOIN `artist` ON " .
                        "`song`.`artist`=`artist`.`id` ";
                    if (AmpConfig::get('catalog_disable')) {
                        $sql .= "LEFT JOIN `catalog` ON `song`.`catalog` = `catalog`.`id` ";
                    }
                    $sql .= "WHERE `song`.`title` = ? " .
                        "AND `artist`.`name` = ? ";
                    if (AmpConfig::get('catalog_disable')) {
                        $sql .= "AND `catalog`.`enabled` = '1'";
                    }

                    $db_result = Dba::read($sql, array($name, $s_artist_name['string']));

                    if ($result = Dba::fetch_assoc($db_result)) {
                        $local_id = $result['id'];
                    }

                    if (is_null($local_id)) {
                        debug_event('Recommendation', "$name did not match any local song", 5);
                        $similars[] = array(
                            'id' => null,
                            'name' => $name,
                            'rel' => $artist_name
                        );
                    } else {
                        debug_event('Recommendation', "$name matched local song $local_id", 5);
                        $similars[] = array(
                            'id' => $local_id,
                            'name' => $name
                        );
                    }
                }

                if (count($similars) > 0) {
                    self::update_recommendation_cache('song', $song_id, $similars);
                }
            }
        }

        if (!isset($similars) || count($similars) == 0) {
            $similars = $cache['items'];
        }
        if ($similars) {
            $results = array();
            foreach ($similars as $similar) {
                if (!$local_only || !is_null($similar['id'])) {
                    $results[] = $similar;
                }

                if ($limit && count($results) >= $limit) {
                    break;
                }
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
    public static function get_artists_like($artist_id, $limit = 10, $local_only = true)
    {
        $artist = new Artist($artist_id);

        $cache = self::get_recommendation_cache('artist', $artist_id, true);
        if (!$cache['id']) {
            $similars = array();
            $query    = 'artist=' . rawurlencode($artist->name);

            $xml = self::get_lastfm_results('artist.getsimilar', $query);

            foreach ($xml->similarartists->children() as $child) {
                $name     = $child->name;
                $mbid     = (string) $child->mbid;
                $local_id = null;

                // First we check by MBID
                if ($mbid) {
                    $sql = "SELECT `artist`.`id` FROM `artist` WHERE `mbid` = ?";
                    if (AmpConfig::get('catalog_disable')) {
                        $sql .= " AND " . Catalog::get_enable_filter('artist', '`artist`.`id`');
                    }
                    $db_result = Dba::read($sql, array($mbid));
                    if ($result = Dba::fetch_assoc($db_result)) {
                        $local_id = $result['id'];
                    }
                }

                // Then we fall back to the less likely to work exact
                // name match
                if (is_null($local_id)) {
                    $searchname = Catalog::trim_prefix($name);
                    $searchname = Dba::escape($searchname['string']);
                    $sql        = "SELECT `artist`.`id` FROM `artist` WHERE `name` = ?";
                    if (AmpConfig::get('catalog_disable')) {
                        $sql .= " AND " . Catalog::get_enable_filter('artist', '`artist`.`id`');
                    }
                    $db_result = Dba::read($sql, array($searchname));
                    if ($result = Dba::fetch_assoc($db_result)) {
                        $local_id = $result['id'];
                    }
                }

                // Then we give up
                if (is_null($local_id)) {
                    debug_event('Recommendation', "$name did not match any local artist", 5);
                    $similars[] = array(
                        'id' => null,
                        'name' => $name,
                        'mbid' => $mbid
                    );
                } else {
                    debug_event('Recommendation', "$name matched local artist " . $local_id, 5);
                    $similars[] = array(
                        'id' => $local_id,
                        'name' => $name
                    );
                }
            }

            if (count($similars) > 0) {
                self::update_recommendation_cache('artist', $artist_id, $similars);
            }
        }

        if (!isset($similars) || count($similars) == 0) {
            $similars = $cache['items'];
        }
        if ($similars) {
            $results = array();
            foreach ($similars as $similar) {
                if (!$local_only || !is_null($similar['id'])) {
                    $results[] = $similar;
                }

                if ($limit && count($results) >= $limit) {
                    break;
                }
            }
        }

        if (isset($results)) {
            return $results;
        }

        return false;
    } // get_artists_like

    /**
     * get_artist_info
     * Returns artist information
     */
    public static function get_artist_info($artist_id, $fullname='')
    {
        $artist = null;
        if ($artist_id) {
            $artist = new Artist($artist_id);
            $artist->format();
            $fullname = $artist->f_full_name;

            // Data newer than 6 months, use it
            if (($artist->last_update + 15768000) > time() || $artist->manual_update) {
                $results                = array();
                $results['id']          = $artist_id;
                $results['summary']     = $artist->summary;
                $results['placeformed'] = $artist->placeformed;
                $results['yearformed']  = $artist->yearformed;
                $results['largephoto']  = Art::url($artist->id, 'artist');
                $results['smallphoto']  = $results['largephoto'];    // TODO: Change to thumb size?
                $results['mediumphoto'] = $results['largephoto'];   // TODO: Change to thumb size?
                $results['megaphoto']   = $results['largephoto'];
                return $results;
            }
        }

        $query = 'artist=' . rawurlencode($fullname);

        $xml = self::get_lastfm_results('artist.getinfo', $query);

        $results                = array();
        $results['summary']     = strip_tags(preg_replace("#<a href=([^<]*)Last\.fm</a>.#", "", (string) $xml->artist->bio->summary));
        $results['summary']     = str_replace("Read more on Last.fm", "", $results['summary']);
        $results['placeformed'] = (string) $xml->artist->bio->placeformed;
        $results['yearformed']  = (string) $xml->artist->bio->yearformed;
        $results['smallphoto']  = $xml->artist->image[0];
        $results['mediumphoto'] = $xml->artist->image[1];
        $results['largephoto']  = $xml->artist->image[2];
        $results['megaphoto']   = $xml->artist->image[4];

        if ($artist) {
            $results['id'] = $artist->id;
            if (!empty($results['summary']) || !empty($results['megaphoto'])) {
                $artist->update_artist_info($results['summary'], $results['placeformed'], $results['yearformed']);

                $image = Art::get_from_source(array('url' => $results['megaphoto']), 'artist');
                $rurl  = pathinfo($results['megaphoto']);
                $mime  = 'image/' . $rurl['extension'];
                $art   = new Art($artist->id, 'artist');
                $art->reset();
                $art->insert($image, $mime);
                $results['largephoto'] = Art::url($artist->id, 'artist');
                $results['megaphoto']  = $results['largephoto'];
            }
        }

        return $results;
    } // get_artist_info
} // end of recommendation class

