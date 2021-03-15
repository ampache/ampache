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

class Recommendation
{
    /**
     * Constructor
     * Not on my watch, boyo.
     */
    private function __construct()
    {
        return false;
    } // constructor

    /**
     * get_lastfm_results
     * Runs a last.fm query and returns the parsed results
     * @param string $method
     * @param string $query
     * @return SimpleXMLElement|false
     */
    public static function get_lastfm_results($method, $query)
    {
        $lang     = (string) AmpConfig::get('lang');
        $resp     = explode('_', $lang);
        $api_key  = AmpConfig::get('lastfm_api_key');
        $api_base = "http://ws.audioscrobbler.com/2.0/?method=";
        $url      = $api_base . $method . '&api_key=' . $api_key . '&' . $query . '&lang=' . $resp[0];

        return self::query_lastfm($url);
    }

    /**
     * @param string $url
     * @return SimpleXMLElement|false
     */
    public static function query_lastfm($url)
    {
        debug_event(self::class, 'search url : ' . $url, 5);

        $request = Requests::get($url, array(), Core::requests_options());

        return simplexml_load_string((string) $request->body);
    }

    /**
     * album_search
     *
     * @param $artist
     * @param $album
     * @return SimpleXMLElement|false
     */
    public static function album_search($artist, $album)
    {
        $api_key = AmpConfig::get('lastfm_api_key');
        $url     = 'http://ws.audioscrobbler.com/2.0/?method=album.getInfo&artist=' . urlencode($artist) . '&album=' . urlencode($album) . '&api_key=' . $api_key;

        return self::query_lastfm($url);
    }

    /**
     * garbage_collection
     *
     * This cleans out old recommendations cache
     */
    public static function garbage_collection()
    {
        Dba::write('DELETE FROM `recommendation` WHERE `last_update` < ?', array((time() - 604800)));
    }

    /**
     * @param string $type
     * @param integer $object_id
     * @param boolean $get_items
     * @return array
     */
    protected static function get_recommendation_cache($type, $object_id, $get_items = false)
    {
        if (!AmpConfig::get('cron_cache')) {
            self::garbage_collection();
        }

        $sql        = "SELECT `id`, `last_update` FROM `recommendation` WHERE `object_type` = ? AND `object_id` = ?";
        $db_results = Dba::read($sql, array($type, $object_id));

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

    /**
     * delete_recommendation_cache
     * @param string $type
     * @param integer $object_id
     */
    protected static function delete_recommendation_cache($type, $object_id)
    {
        $cache = self::get_recommendation_cache($type, $object_id);
        if ($cache['id']) {
            Dba::write('DELETE FROM `recommendation_item` WHERE `recommendation` = ?', array($cache['id']));
            Dba::write('DELETE FROM `recommendation` WHERE `id` = ?', array($cache['id']));
        }
    }

    /**
     * update_recommendation_cache
     * @param string $type
     * @param integer $object_id
     * @param $recommendations
     */
    protected static function update_recommendation_cache($type, $object_id, $recommendations)
    {
        if (count($recommendations) > 0) {
            self::delete_recommendation_cache($type, $object_id);
            $sql = "INSERT INTO `recommendation` (`object_type`, `object_id`, `last_update`) VALUES (?, ?, ?)";
            Dba::write($sql, array($type, $object_id, time()));
            $insertid = Dba::insert_id();
            foreach ($recommendations as $recommendation) {
                $sql = "INSERT INTO `recommendation_item` (`recommendation`, `recommendation_id`, `name`, `rel`, `mbid`) VALUES (?, ?, ?, ?, ?)";
                Dba::write($sql, array($insertid, $recommendation['id'], $recommendation['name'], $recommendation['rel'], $recommendation['mbid']));
            }
        }
    }

    /**
     * get_songs_like
     * Returns a list of similar songs
     * @param integer $song_id
     * @param integer $limit
     * @param boolean $local_only
     * @return array
     */
    public static function get_songs_like($song_id, $limit = 5, $local_only = true)
    {
        if (!AmpConfig::get('lastfm_api_key')) {
            return array();
        }

        $song   = new Song($song_id);
        $artist = new Artist($song->artist);
        $query  = 'artist=' . rawurlencode($artist->name) . '&track=' . rawurlencode($song->title);
        if (isset($song->mbid)) {
            $query = 'mbid=' . rawurlencode($song->mbid);
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
                    $s_artist_name = Catalog::trim_prefix((string) $artist_name);

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
                        debug_event(self::class, "$name matched local song $local_id", 4);
                        $similars[] = array(
                            'id' => $local_id,
                            'name' => $name,
                            'rel' => $artist_name
                        );
                    } else {
                        debug_event(self::class, "$name did not match any local song", 5);
                        $similars[] = array(
                            'id' => null,
                            'name' => $name,
                            'rel' => $artist_name
                        );
                    }
                }
                self::update_recommendation_cache('song', $song_id, $similars);
            }
        }

        if (!isset($similars) || count($similars) == 0) {
            $similars = $cache['items'];
        }
        if ($similars) {
            $results = array();
            foreach ($similars as $similar) {
                if (!$local_only || $similar['id'] !== null) {
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

        return array();
    }

    /**
     * get_artists_like
     * Returns a list of similar artists
     * @param integer $artist_id
     * @param integer $limit
     * @param boolean $local_only
     * @return array
     */
    public static function get_artists_like($artist_id, $limit = 10, $local_only = true)
    {
        if (!AmpConfig::get('lastfm_api_key')) {
            return array();
        }

        $artist = new Artist($artist_id);

        $cache = self::get_recommendation_cache('artist', $artist_id, true);
        if (!$cache['id']) {
            $similars = array();
            $query    = ($artist->mbid) ? 'mbid=' . rawurlencode($artist->mbid) : 'artist=' . rawurlencode($artist->f_full_name);

            $xml = self::get_lastfm_results('artist.getsimilar', $query);

            if ($xml->similarartists) {
                foreach ($xml->similarartists->children() as $child) {
                    $name     = (string) $child->name;
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
                    if ($local_id === null) {
                        $searchname = Catalog::trim_prefix($name);
                        $searchname = Dba::escape($searchname['string']);
                        $sql        = "SELECT `artist`.`id` FROM `artist` WHERE `artist`.`name` = ? OR " .
                            "LTRIM(CONCAT(COALESCE(`artist`.`prefix`, ''), ' ', `artist`.`name`)) = ?";
                        if (AmpConfig::get('catalog_disable')) {
                            $sql .= " AND " . Catalog::get_enable_filter('artist', '`artist`.`id`');
                        }
                        $db_result = Dba::read($sql, array($searchname, $searchname));
                        if ($result = Dba::fetch_assoc($db_result)) {
                            $local_id = $result['id'];
                        }
                    }

                    // Then we give up
                    if ($local_id === null) {
                        debug_event(self::class, "$name did not match any local artist", 5);
                        $similars[] = array(
                            'id' => null,
                            'name' => $name,
                            'mbid' => $mbid
                        );
                    } else {
                        debug_event(self::class, "$name matched local artist " . $local_id, 5);
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
        }

        if (!isset($similars) || count($similars) == 0) {
            $similars = $cache['items'];
        }
        if ($similars) {
            $results = array();
            foreach ($similars as $similar) {
                if (!$local_only || $similar['id'] !== null) {
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

        return array();
    } // get_artists_like

    /**
     * get_artist_info_by_name
     * Returns artist information
     * @param string $fullname
     * @return array
     */
    public static function get_artist_info_by_name($fullname)
    {
        $query = 'artist=' . rawurlencode($fullname);
        $xml   = self::get_lastfm_results('artist.getinfo', $query);

        $results            = array();
        $results['summary'] = strip_tags(preg_replace("#<a href=([^<]*)Last\.fm</a>.#", "",
            (string)$xml->artist->bio->summary));
        $results['summary']     = str_replace("Read more on Last.fm", "", $results['summary']);
        $results['placeformed'] = (string)$xml->artist->bio->placeformed;
        $results['yearformed']  = (string)$xml->artist->bio->yearformed;

        return $results;
    } // get_artist_info_by_name

    /**
     * get_artist_info
     * Returns artist information
     * @param integer $artist_id
     * @return array
     */
    public static function get_artist_info($artist_id)
    {
        $artist = new Artist($artist_id);
        $query  = ($artist->mbid)
            ? 'mbid=' . rawurlencode($artist->mbid)
            : 'artist=' . rawurlencode(trim(trim((string)$artist->prefix) . ' ' . trim((string)$artist->name)));

        // Data newer than 6 months, use it
        if (($artist->last_update + 15768000) > time() || $artist->manual_update) {
            $results                = array();
            $results['id']          = $artist_id;
            $results['summary']     = $artist->summary;
            $results['placeformed'] = $artist->placeformed;
            $results['yearformed']  = $artist->yearformed;
            $results['largephoto']  = Art::url($artist->id, 'artist', null, 174);
            $results['smallphoto']  = Art::url($artist->id, 'artist', null, 34);
            $results['mediumphoto'] = Art::url($artist->id, 'artist', null, 64);
            $results['megaphoto']   = Art::url($artist->id, 'artist', null, 300);

            return $results;
        }
        $xml = self::get_lastfm_results('artist.getinfo', $query);

        $results            = array();
        $results['summary'] = strip_tags(preg_replace("#<a href=([^<]*)Last\.fm</a>.#", "",
            (string)$xml->artist->bio->summary));
        $results['summary']     = str_replace("Read more on Last.fm", "", $results['summary']);
        $results['placeformed'] = (string)$xml->artist->bio->placeformed;
        $results['yearformed']  = (string)$xml->artist->bio->yearformed;

        if ($artist->id) {
            $results['id'] = $artist->id;
            if (!empty($results['summary'])) {
                $artist->update_artist_info($results['summary'], $results['placeformed'], (int)$results['yearformed']);
            }
            $results['largephoto']  = Art::url($artist->id, 'artist', null, 174);
            $results['smallphoto']  = Art::url($artist->id, 'artist', null, 34);
            $results['mediumphoto'] = Art::url($artist->id, 'artist', null, 64);
            $results['megaphoto']   = Art::url($artist->id, 'artist', null, 300);
        }

        return $results;
    } // get_artist_info

    /**
     * Migrate an object associate stats to a new object
     * @param string $object_type
     * @param integer $old_object_id
     * @param integer $new_object_id
     * @return PDOStatement|boolean
     */
    public static function migrate($object_type, $old_object_id, $new_object_id)
    {
        $sql = "UPDATE IGNORE `recommendation` SET `object_id` = ? WHERE `object_type` = ? AND `object_id` = ?";

        return Dba::write($sql, array($new_object_id, $object_type, $old_object_id));
    }
} // end recommendation.class
