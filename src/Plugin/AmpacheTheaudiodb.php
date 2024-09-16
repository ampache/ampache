<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Plugin;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
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

class AmpacheTheaudiodb extends AmpachePlugin implements PluginGatherArtsInterface
{
    public string $name        = 'TheAudioDb';
    public string $categories  = 'metadata';
    public string $description = 'TheAudioDb metadata integration';
    public string $url         = 'http://www.theaudiodb.com';
    public string $version     = '000003';
    public string $min_ampache = '370009';
    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    public $overwrite_name;

    private $api_key;

    /**
     * Constructor
     * This function does nothing
     */
    public function __construct()
    {
        $this->description = T_('TheAudioDb metadata integration');
    }

    /**
     * install
     * This is a required plugin function
     */
    public function install(): bool
    {
        // API Key requested in TheAudioDB forum, see http://www.theaudiodb.com/forum/viewtopic.php?f=6&t=8&start=140
        if (!Preference::insert('tadb_api_key', T_('TheAudioDb API key'), '41214789306c4690752dfb', AccessLevelEnum::MANAGER->value, 'string', 'plugins', $this->name)) {
            return false;
        }
        if (!Preference::insert('tadb_overwrite_name', T_('Overwrite Artist names that match an mbid'), '0', AccessLevelEnum::USER->value, 'boolean', 'plugins', $this->name)) {
            return false;
        }

        return true;
    }

    /**
     * uninstall
     * This is a required plugin function
     */
    public function uninstall(): bool
    {
        return (
            Preference::delete('tadb_api_key') &&
            Preference::delete('tadb_overwrite_name')
        );
    }

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade(): bool
    {
        $from_version = Plugin::get_plugin_version($this->name);
        if ($from_version == 0) {
            return false;
        }
        if ($from_version < (int)$this->version) {
            Preference::insert('tadb_overwrite_name', T_('Overwrite Artist names that match an mbid'), '0', AccessLevelEnum::USER->value, 'boolean', 'plugins', $this->name);
        }

        return true;
    }

    /**
     * load
     * This is a required plugin function; here it populates the prefs we
     * need for this object.
     */
    public function load(User $user): bool
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
    }

    /**
     * get_metadata
     * Returns song metadata for what we're passed in.
     */
    public function get_metadata(array $gather_types, array $media_info): array
    {
        // Music metadata only
        if (!in_array('music', $gather_types)) {
            debug_event('theaudiodb.plugin', 'Not a valid media type, skipped.', 5);

            return [];
        }

        $results = [];
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
    }

    /**
     * get_external_metadata
     * Update an Artist using theAudioDb
     * @param Label|Artist $object
     * @param string $object_type
     * @return bool
     */
    public function get_external_metadata($object, string $object_type): bool
    {
        $valid_types = ['artist'];
        // Artist metadata only for now
        if (!in_array($object_type, $valid_types)) {
            debug_event('theaudiodb.plugin', 'get_external_metadata only supports Artists', 5);

            return false;
        }

        $data = [];
        try {
            if (in_array($object_type, $valid_types)) {
                $release = null;
                if ($object->mbid !== null && VaInfo::is_mbid($object->mbid)) {
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
                    $locale          = explode('_', AmpConfig::get('lang', 'en_US'))[0] ?? 'en';
                    $data['summary'] = match ($locale) {
                        'de' => $release->strBiographyDE ?? null,
                        'fr' => $release->strBiographyFR ?? null,
                        'cn' => $release->strBiographyCN ?? null,
                        'it' => $release->strBiographyIT ?? null,
                        'jp' => $release->strBiographyJP ?? null,
                        'ru' => $release->strBiographyRU ?? null,
                        'es' => $release->strBiographyES ?? null,
                        'pt' => $release->strBiographyPT ?? null,
                        'se' => $release->strBiographySE ?? null,
                        'nl' => $release->strBiographyNL ?? null,
                        'hu' => $release->strBiographyHU ?? null,
                        'no' => $release->strBiographyNO ?? null,
                        'il' => $release->strBiographyIL ?? null,
                        'pl' => $release->strBiographyPL ?? null,
                        default => $release->strBiographyEN ?? null,
                    };
                    $data['placeformed'] = $release->strCountry ?? null;
                    $data['yearformed']  = $release->intFormedYear ?? null;

                    // when you come in with an mbid you might want to keep the name updated (ignore case)
                    if (
                        $this->overwrite_name &&
                        $object->mbid !== null &&
                        VaInfo::is_mbid($object->mbid) &&
                        strtolower($data['name'] ?? '') !== strtolower((string)$object->get_fullname())
                    ) {
                        $name_check     = Artist::update_name_from_mbid($data['name'], $object->mbid);
                        if (isset($object->prefix)) {
                            $object->prefix = $name_check['prefix'];
                        }
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
    }

    /**
     * gather_arts
     * Returns art items for the requested media type
     */
    public function gather_arts(string $type, ?array $options = [], ?int $limit = 5): array
    {
        debug_event('theaudiodb.plugin', 'gather_arts for type `' . $type . '`', 5);

        return array_slice(Art::gather_metadata_plugin($this, $type, ($options ?? [])), 0, $limit);
    }

    /**
     * @param string $func
     * @return mixed|null
     */
    private function api_call($func)
    {
        $url = 'http://www.theaudiodb.com/api/v1/json/' . $this->api_key . '/' . $func;
        //debug_event('theaudiodb.plugin', 'API call: ' . $url, 5);
        $request = Requests::get($url, [], Core::requests_options());

        if ($request->status_code != 200) {
            return null;
        }

        return json_decode($request->body);
    }

    /**
     * @param null|string $name
     * @return mixed|null
     */
    private function search_artists($name)
    {
        return ($name)
            ? $this->api_call('search.php?s=' . rawurlencode($name))
            : null;
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
