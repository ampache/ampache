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
declare(strict_types=0);

namespace Ampache\Plugin;

use Ampache\Config\AmpConfig;
use Ampache\Module\Util\VaInfo;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\System\Core;
use Exception;
use WpOrg\Requests\Requests;

class AmpacheTheaudiodb
{
    public $name        = 'TheAudioDb';
    public $categories  = 'metadata';
    public $description = 'TheAudioDb metadata integration';
    public $url         = 'http://www.theaudiodb.com';
    public $version     = '000003';
    public $min_ampache = '370009';
    public $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private $api_key;
    private $overwrite_name;

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
        Preference::insert('tadb_overwrite_name', T_('Overwrite Artist names that match an mbid'), '0', 25, 'boolean', 'plugins', $this->name);

        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function
     */
    public function uninstall()
    {
        Preference::delete('tadb_api_key');
        Preference::delete('tadb_overwrite_name');

        return true;
    } // uninstall

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade()
    {
        $from_version = Plugin::get_plugin_version($this->name);
        if ($from_version == 0) {
            return false;
        }
        if ($from_version < (int)$this->version) {
            Preference::insert('tadb_overwrite_name', T_('Overwrite Artist names that match an mbid'), '0', 25, 'boolean', 'plugins', $this->name);
        }

        return true;
    }

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
        if (!array_key_exists('tadb_api_key', $data) && !array_key_exists('tadb_overwrite_name', $data)) {
            $data['tadb_api_key']        = Preference::get_by_user(-1, 'tadb_api_key');
            $data['tadb_overwrite_name'] = Preference::get_by_user(-1, 'tadb_overwrite_name');
        }

        if (strlen(trim($data['tadb_api_key']))) {
            $this->api_key = trim($data['tadb_api_key']);
        } else {
            debug_event('theaudiodb.plugin', 'No TheAudioDb api key, metadata plugin skipped', 3);

            return false;
        }
        $this->overwrite_name = (bool)$data['tadb_overwrite_name'];

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
                    if ($album && $album->album !== null) {
                        $release = $album->album[0];
                    }
                } else {
                    $albums = $this->search_album($media_info['artist'], $media_info['title']);
                    if ($albums && $albums->album !== null) {
                        $release = $albums->album[0];
                    }
                }

                if ($release) {
                    $results['art']   = $release->strAlbumThumb ?? null;
                    $results['title'] = $release->strAlbum ?? null;
                }
            } elseif (in_array('artist', $gather_types)) {
                debug_event('theaudiodb.plugin', 'Getting artist metadata from TheAudioDb...', 5);
                $release = null;
                if ($media_info['mb_artistid']) {
                    $artist  = $this->get_artist($media_info['mb_artistid']);
                    $release = $artist->artists[0] ?? $release;
                } else {
                    $artists = $this->search_artists($media_info['title']);
                    $release = $artists->artists[0] ?? $release;
                }
                if ($release !== null) {
                    $results['art']        = $release->strArtistThumb ?? null;
                    $results['title']      = $release->strArtist ?? null;
                    $results['summary']    = $release->strBiographyEN ?? null;
                    $results['yearformed'] = $release->intFormedYear ?? null;
                }
            } elseif ($media_info['mb_trackid']) {
                $track = $this->get_track($media_info['mb_trackid']);
                if ($track !== null) {
                    $track                       = $track->track[0] ?? null;
                    $results['mb_artistid']      = $track->strMusicBrainzArtistID ?? null;
                    $results['mb_albumid_group'] = $track->strMusicBrainzAlbumID ?? null;
                    $results['album']            = $track->strAlbum ?? null;
                    $results['artist']           = $track->strArtist ?? null;
                    $results['title']            = $track->strTrack ?? null;
                }
            }
        } catch (Exception $error) {
            debug_event('theaudiodb.plugin', 'Error getting metadata: ' . $error->getMessage(), 1);
        }

        return $results;
    } // get_metadata

    /**
     * get_external_metadata
     * Update an Artist using theAudioDb
     * @param Label|Artist $object
     * @param string $object_type
     * @return bool
     */
    public function get_external_metadata($object, string $object_type)
    {
        $valid_types = array('artist');
        // Artist metadata only for now
        if (!in_array($object_type, $valid_types)) {
            debug_event('theaudiodb.plugin', 'get_external_metadata only supports Artists', 5);

            return false;
        }

        $data = array();
        try {
            if (in_array($object_type, $valid_types)) {
                $release = null;
                if (Vainfo::is_mbid($object->mbid)) {
                    $artist  = $this->get_artist($object->mbid);
                    $release = $artist->artists[0] ?? $release;
                } else {
                    $artists = $this->search_artists($object->get_fullname());
                    $release = $artists->artists[0] ?? $release;
                }
                if ($release !== null) {
                    debug_event('theaudiodb.plugin', "Updating $object_type: " . $object->get_fullname(), 3);
                    $data['name'] = $release->strArtist ?? null;
                    // get the biography based on your locale
                    $locale = explode('_', AmpConfig::get('lang', 'en_US'))[0] ?? 'en';
                    switch ($locale) {
                        case 'de':
                            $data['summary'] = $release->strBiographyDE ?? null;
                            break;
                        case 'fr':
                            $data['summary'] = $release->strBiographyFR ?? null;
                            break;
                        case 'cn':
                            $data['summary'] = $release->strBiographyCN ?? null;
                            break;
                        case 'it':
                            $data['summary'] = $release->strBiographyIT ?? null;
                            break;
                        case 'jp':
                            $data['summary'] = $release->strBiographyJP ?? null;
                            break;
                        case 'ru':
                            $data['summary'] = $release->strBiographyRU ?? null;
                            break;
                        case 'es':
                            $data['summary'] = $release->strBiographyES ?? null;
                            break;
                        case 'pt':
                            $data['summary'] = $release->strBiographyPT ?? null;
                            break;
                        case 'se':
                            $data['summary'] = $release->strBiographySE ?? null;
                            break;
                        case 'nl':
                            $data['summary'] = $release->strBiographyNL ?? null;
                            break;
                        case 'hu':
                            $data['summary'] = $release->strBiographyHU ?? null;
                            break;
                        case 'no':
                            $data['summary'] = $release->strBiographyNO ?? null;
                            break;
                        case 'il':
                            $data['summary'] = $release->strBiographyIL ?? null;
                            break;
                        case 'pl':
                            $data['summary'] = $release->strBiographyPL ?? null;
                            break;
                        case 'en':
                        default:
                            $data['summary'] = $release->strBiographyEN ?? null;
                            break;
                    }
                    $data['placeformed'] = $release->strCountry ?? null;
                    $data['yearformed']  = $release->intFormedYear ?? null;

                    // when you come in with an mbid you might want to keep the name updated (ignore case)
                    if ($this->overwrite_name && Vainfo::is_mbid($object->mbid) && strtolower($data['name'] ?? '') !== strtolower($object->get_fullname())) {
                        $name_check     = Artist::update_name_from_mbid($data['name'], $object->mbid);
                        $object->prefix = $name_check['prefix'];
                        $object->name   = $name_check['name'];
                    }
                }
            }
        } catch (Exception $error) {
            debug_event('theaudiodb.plugin', 'Error getting metadata: ' . $error->getMessage(), 1);

            return false;
        }
        if (!empty($data)) {
            $object->update($data);
        }

        return true;
    } // get_external_metadata

    /**
     * @param string $type
     * @param array $options
     * @param integer $limit
     * @return array
     */
    public function gather_arts($type, $options = array(), $limit = 5)
    {
        debug_event('theaudiodb.plugin', 'gather_arts for type `' . $type . '`', 5);

        return array_slice(Art::gather_metadata_plugin($this, $type, $options), 0, $limit);
    }

    /**
     * @param string $func
     * @return mixed|null
     */
    private function api_call($func)
    {
        $url = 'http://www.theaudiodb.com/api/v1/json/' . $this->api_key . '/' . $func;
        //debug_event('theaudiodb.plugin', 'API call: ' . $url, 5);
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
}
