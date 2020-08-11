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

class AmpacheTheaudiodb
{
    public $name           = 'TheAudioDb';
    public $categories     = 'metadata';
    public $description    = 'TheAudioDb metadata integration';
    public $url            = 'http://www.theaudiodb.com';
    public $version        = '000002';
    public $min_ampache    = '370009';
    public $max_ampache    = '999999';

    // These are internal settings used by this class, run this->load to
    // fill them out
    private $api_key;

    /**
     * Constructor
     * This function does nothing
     */
    public function __construct()
    {
        $this->description = T_('TheAudioDb metadata integration');

        return true;
    }

    /**
     * install
     * This is a required plugin function
     */
    public function install()
    {
        if (Preference::exists('tadb_api_key')) {
            return false;
        }

        // API Key requested in TheAudioDB forum, see http://www.theaudiodb.com/forum/viewtopic.php?f=6&t=8&start=140
        Preference::insert('tadb_api_key', T_('TheAudioDb API key'), '41214789306c4690752dfb', 75, 'string', 'plugins', $this->name);

        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function
     */
    public function uninstall()
    {
        Preference::delete('tadb_api_key');

        return true;
    } // uninstall

    /**
     * load
     * This is a required plugin function; here it populates the prefs we
     * need for this object.
     * @param User $user
     * @return boolean
     */
    public function load($user)
    {
        $user->set_preferences();
        $data = $user->prefs;
        // load system when nothing is given
        if (!strlen(trim($data['tadb_api_key']))) {
            $data                 = array();
            $data['tadb_api_key'] = Preference::get_by_user(-1, 'tadb_api_key');
        }

        if (strlen(trim($data['tadb_api_key']))) {
            $this->api_key = trim($data['tadb_api_key']);
        } else {
            debug_event('theaudiodb.plugin', 'No TheAudioDb api key, metadata plugin skipped', 3);

            return false;
        }

        return true;
    } // load

    /**
     * get_metadata
     * Returns song metadata for what we're passed in.
     * @param array $gather_types
     * @param array $media_info
     * @return array
     */
    public function get_metadata($gather_types, $media_info)
    {
        // Music metadata only
        if (!in_array('music', $gather_types)) {
            debug_event('theaudiodb.plugin', 'Not a valid media type, skipped.', 5);

            return array();
        }

        $results = array();
        try {
            if (in_array('album', $gather_types)) {
                debug_event('theaudiodb.plugin', 'Getting album metadata from TheAudioDb...', 5);
                $release = null;
                if ($media_info['mb_albumid_group']) {
                    $album = $this->get_album($media_info['mb_albumid_group']);
                    if ($album) {
                        $release = $album->album[0];
                    }
                } else {
                    $albums = $this->search_album($media_info['artist'], $media_info['title']);
                    if ($albums) {
                        $release = $albums->album[0];
                    }
                }

                if ($release) {
                    $results['art']   = $release->strAlbumThumb;
                    $results['title'] = $release->strAlbum;
                }
            } elseif (in_array('artist', $gather_types)) {
                debug_event('theaudiodb.plugin', 'Getting artist metadata from TheAudioDb...', 5);
                $release = null;
                if ($media_info['mb_artistid']) {
                    $artist = $this->get_artist($media_info['mb_artistid']);
                    if ($artist) {
                        $release = $artist->artists[0];
                    }
                } else {
                    $artists = $this->search_artists($media_info['title']);
                    if ($artists) {
                        $release = $artists->artists[0];
                    }
                }
                if ($release) {
                    $results['art']        = $release->strArtistThumb;
                    $results['title']      = $release->strArtist;
                    $results['summary']    = $release->strBiographyEN;
                    $results['yearformed'] = $release->intFormedYear;
                }
            } elseif ($media_info['mb_trackid']) {
                $track = $this->get_track($media_info['mb_trackid']);
                if ($track) {
                    $track                       = $track->track[0];
                    $results['mb_artistid']      = $track->strMusicBrainzArtistID;
                    $results['mb_albumid_group'] = $track->strMusicBrainzAlbumID;
                    $results['album']            = $track->strAlbum;
                    $results['artist']           = $track->strArtist;
                    $results['title']            = $track->strTrack;
                }
            }
        } catch (Exception $error) {
            debug_event('theaudiodb.plugin', 'Error getting metadata: ' . $error->getMessage(), 1);
        }

        return $results;
    } // get_metadata

    /**
     * @param string $type
     * @param array $options
     * @param integer $limit
     * @return array
     */
    public function gather_arts($type, $options = array(), $limit = 5)
    {
        debug_event('theaudiodb.plugin', 'gather_arts for type `' . $type . '`', 5);

        return Art::gather_metadata_plugin($this, $type, $options);
    }

    /**
     * @param string $func
     * @return mixed|null
     */
    private function api_call($func)
    {
        $url = 'http://www.theaudiodb.com/api/v1/json/' . $this->api_key . '/' . $func;
        debug_event('theaudiodb.plugin', 'API call: ' . $url, 5);
        $request = Requests::get($url, array(), Core::requests_options());

        if ($request->status_code != 200) {
            return null;
        }

        return json_decode($request->body);
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    private function search_artists($name)
    {
        return $this->api_call('search.php?s=' . rawurlencode($name));
    }

    /**
     * @param string $mbid
     * @return mixed|null
     */
    private function get_artist($mbid)
    {
        return $this->api_call('artist-mb.php?i=' . $mbid);
    }

    /**
     * @param string $artist
     * @param string $album
     * @return mixed|null
     */
    private function search_album($artist, $album)
    {
        return $this->api_call('searchalbum.php?s=' . rawurlencode($artist) . '&a=' . rawurlencode($album));
    }

    /**
     * @param string $mbid
     * @return mixed|null
     */
    private function get_album($mbid)
    {
        return $this->api_call('album-mb.php?i=' . $mbid);
    }

    /**
     * @param string $artist
     * @param string $title
     * @return mixed|null
     */
    private function search_track($artist, $title)
    {
        return $this->api_call('searchtrack.php?s=' . rawurlencode($artist) . '&t=' . rawurlencode($title));
    }

    /**
     * @param string $mbid
     * @return mixed|null
     */
    private function get_track($mbid)
    {
        return $this->api_call('track-mb.php?i=' . $mbid);
    }
} // end AmpacheTheaudiodb
