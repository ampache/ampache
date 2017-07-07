<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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

class AmpacheDiscogs
{
    public $name           = 'Discogs';
    public $categories     = 'metadata';
    public $description    = 'Discogs metadata integration';
    public $url            = 'http://www.discogs.com';
    public $version        = '000001';
    public $min_ampache    = '370021';
    public $max_ampache    = '999999';
    
    private $api_key;
    private $secret;
    
    /**
     * Constructor
     * This function does nothing
     */
    public function __construct()
    {
        return true;
    }

    /**
     * install
     * This is a required plugin function
     */
    public function install()
    {
        if (Preference::exists('discogs_api_key')) {
            return false;
        }
        Preference::insert('discogs_api_key', 'Discogs consumer key', '', '75', 'string', 'plugins', $this->name);
        Preference::insert('discogs_secret_api_key', 'Discogs secret', '', '75', 'string', 'plugins', $this->name);

        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function
     */
    public function uninstall()
    {
        Preference::delete('discogs_api_key');
        Preference::delete('discogs_secret_api_key');

        return true;
    } // uninstall

    /**
     * load
     * This is a required plugin function; here it populates the prefs we
     * need for this object.
     */
    public function load($user)
    {
        $user->set_preferences();
        $data = $user->prefs;
        
        if (strlen(trim($data['discogs_api_key']))) {
            $this->api_key = trim($data['discogs_api_key']);
        } else {
            debug_event($this->name, 'No Discogs api key, metadata plugin skipped', '3');

            return false;
        }
        if (strlen(trim($data['discogs_secret_api_key']))) {
            $this->secret = trim($data['discogs_secret_api_key']);
        } else {
            debug_event($this->name, 'No Discogs secret, metadata plugin skipped', '3');

            return false;
        }
        
        return true;
    } // load

    protected function query_discogs($query)
    {
        $url     = 'https://api.discogs.com/' . $query;
        $url .= (strpos($query, '?') !== false) ? '&' : '?';
        $url .= 'key=' . $this->api_key . '&secret=' . $this->secret;
        debug_event('discogs', 'Discogs request: ' . $url, 5);
        $request = Requests::get($url);

        return json_decode($request->body, true);
    }
    
    protected function search_artist($artist)
    {
        $query = "database/search?type=artist&title=" . rawurlencode($artist) . "&per_page=10";

        return $this->query_discogs($query);
    }
    
    protected function get_artist($id)
    {
        $query = "artists/" . $id;

        return $this->query_discogs($query);
    }
    
    protected function search_album($artist, $album)
    {
        $query = "database/search?type=master&release_title=" . rawurlencode($album) . "&artist=" . rawurlencode($artist) . "&per_page=10";

        return $this->query_discogs($query);
    }
    
    protected function get_album($id)
    {
        $query = "masters/" . $id;

        return $this->query_discogs($query);
    }

    /**
     * get_metadata
     * Returns song metadata for what we're passed in.
     */
    public function get_metadata($gather_types, $media_info)
    {
        debug_event('discogs', 'Getting metadata from Discogs...', '5');

        // TVShow and Movie metadata only
        if (!in_array('music', $gather_types)) {
            debug_event('discogs', 'Not a valid media type, skipped.', '5');

            return null;
        }
        
        $results = array();
        try {
            if (in_array('artist', $gather_types)) {
                $artists = $this->search_artist($media_info['title']);
                if (count($artists['results']) > 0) {
                    $artist = $this->get_artist($artists['results'][0]['id']);
                    if (count($artist['images']) > 0) {
                        $results['art'] = $artist['images'][0]['uri'];
                    }
                }
            } else {
                if (in_array('album', $gather_types)) {
                    $albums = $this->search_album($media_info['artist'], $media_info['title']);
                    if (count($albums['results']) > 0) {
                        $album = $this->get_album($albums['results'][0]['id']);
                        if (count($album['images']) > 0) {
                            $results['art'] = $album['images'][0]['uri'];
                        }
                    }
                }
            }
        } catch (Exception $e) {
            debug_event('discogs', 'Error getting metadata: ' . $e->getMessage(), '1');
        }
        
        return $results;
    } // get_metadata

    public function gather_arts($type, $options = array(), $limit = 5)
    {
        return Art::gather_metadata_plugin($this, $type, $options);
    }
}
