<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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

use Ampache\Module\Util\VaInfo;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Exception;
use MusicBrainz\Filters\ArtistFilter;
use MusicBrainz\Filters\LabelFilter;
use MusicBrainz\MusicBrainz;
use MusicBrainz\HttpAdapters\RequestsHttpAdapter;

class AmpacheMusicBrainz
{
    public $name        = 'MusicBrainz';
    public $categories  = 'metadata';
    public $description = 'MusicBrainz metadata integration';
    public $url         = 'http://www.musicbrainz.org';
    public $version     = '000003';
    public $min_ampache = '360003';
    public $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    public $overwrite_name;

    /**
     * Constructor
     * This function does nothing
     */
    public function __construct()
    {
        $this->description = T_('MusicBrainz metadata integration');

        return true;
    }

    /**
     * install
     * This is a required plugin function
     */
    public function install()
    {
        Preference::insert('mb_overwrite_name', T_('Overwrite Artist names that match an mbid'), '0', 25, 'boolean', 'plugins', $this->name);

        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function
     */
    public function uninstall()
    {
        Preference::delete('mb_overwrite_name');

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
        if (!Preference::exists('mb_overwrite_name')) {
            // this wasn't installed correctly only upgraded so may be missing
            Preference::insert('mb_overwrite_name', T_('Overwrite Artist names that match an mbid'), '0', 25, 'boolean', 'plugins', $this->name);
        }

        // did the upgrade work?
        if (Preference::exists('mb_overwrite_name')) {
            return true;
        }

        return false;
    }

    /**
     * load
     * This is a required plugin function; here it populates the prefs we
     * need for this object.
     * @param User $user
     * @return bool
     */
    public function load($user)
    {
        $user->set_preferences();
        $data = $user->prefs;
        // load system when nothing is given
        if (!array_key_exists('mb_overwrite_name', $data)) {
            $data['mb_overwrite_name'] = Preference::get_by_user(-1, 'mb_overwrite_name');
        }
        // overwrite matching MBID artist names
        $this->overwrite_name = (bool)$data['mb_overwrite_name'];

        return true;
    } // load

    /**
     * get_metadata
     * Returns song metadata for what we're passed in.
     * @param array $gather_types
     * @param array $song_info
     * @return array|null
     */
    public function get_metadata($gather_types, $song_info)
    {
        // Music metadata only
        if (!in_array('music', $gather_types)) {
            return null;
        }

        if (!$mbid = $song_info['mb_trackid']) {
            return null;
        }

        $mbrainz  = new MusicBrainz(new RequestsHttpAdapter());
        $includes = array(
            'artists',
            'releases'
        );
        try {
            $track = $mbrainz->lookup('recording', $mbid, $includes);
        } catch (Exception $error) {
            debug_event('MusicBrainz.plugin', 'Lookup error ' . $error, 3);

            return null;
        }

        $results = array();

        if (isset($track->{'artist-credit'}) && count($track->{'artist-credit'}) > 0) {
            $artist                 = $track->{'artist-credit'}[0];
            $artist                 = $artist->artist;
            $results['mb_artistid'] = $artist->id;
            $results['artist']      = $artist->name;
            $results['title']       = $track->{'title'};
            if (count($track->{'releases'}) == 1) {
                $release          = $track->{'releases'}[0];
                $results['album'] = $release->title;
            }
        }

        return $results;
    } // get_metadata

    /**
     * get_external_metadata
     * Update an object (label or artist for now) using musicbrainz
     * @param Label|Artist $object
     * @param string $object_type
     * @return bool
     */
    public function get_external_metadata($object, string $object_type)
    {
        $valid_types = array('label', 'artist');
        // Artist metadata only for now
        if (!in_array($object_type, $valid_types)) {
            debug_event('MusicBrainz.plugin', 'get_external_metadata only supports Labels and Artists', 5);

            return false;
        }

        $mbrainz = new MusicBrainz(new RequestsHttpAdapter());
        $results = array();
        if (Vainfo::is_mbid($object->mbid)) {
            try {
                $results = $mbrainz->lookup($object_type, $object->mbid);
            } catch (Exception $error) {
                debug_event('MusicBrainz.plugin', 'Lookup error ' . $error, 3);

                return false;
            }
        } else {
            try {
                $args = array($object_type => $object->get_fullname());
                switch ($object_type) {
                    case 'label':
                        $results = $mbrainz->search(new LabelFilter($args), 1);
                        break;
                    case 'artist':
                        $results = $mbrainz->search(new ArtistFilter($args), 1);
                        break;
                    default:
                }
            } catch (Exception $error) {
                debug_event('MusicBrainz.plugin', 'Lookup error ' . $error, 3);

                return false;
            }
        }
        if (is_array($results) && !empty($results)) {
            $results = $results[0];
        }
        if (!empty($results)) {
            debug_event('MusicBrainz.plugin', "Updating $object_type: " . $object->get_fullname(), 3);
            $data = array();
            switch ($object_type) {
                case 'label':
                    /** @var Label $object */
                    $data = array(
                        'name' => $results->{'name'} ?? $object->get_fullname(),
                        'mbid' => $results->{'id'} ?? $object->mbid,
                        'category' => $results->{'type'} ?? $object->category,
                        'summary' => $results->{'disambiguation'} ?? $object->summary,
                        'address' => $object->address,
                        'country' => $results->{'country'} ?? $object->country,
                        'email' => $object->email,
                        'website' => $object->website,
                        'active' => ($results->{'life-span'}->{'ended'} == 1) ? 0 : 1
                    );
                    break;
                case 'artist':
                    /** @var Artist $object */
                    $placeFormed = (isset($results->{'begin-area'}->{'name'}) && isset($results->{'area'}->{'name'}))
                        ? $results->{'begin-area'}->{'name'} . ', ' . $results->{'area'}->{'name'}
                        : $results->{'begin-area'}->{'name'} ?? $object->placeformed;
                    $data = array(
                        'name' => $results->{'name'} ?? $object->get_fullname(),
                        'mbid' => $results->{'id'} ?? $object->mbid,
                        'summary' => $object->summary,
                        'placeformed' => $placeFormed,
                        'yearformed' => explode('-', ($results->{'life-span'}->{'begin'} ?? ''))[0] ?? $object->yearformed
                    );

                    // when you come in with an mbid you might want to keep the name updated
                    if ($this->overwrite_name && Vainfo::is_mbid($object->mbid) && $data['name'] !== $object->get_fullname()) {
                        $name_check     = Artist::update_name_from_mbid($data['name'], $object->mbid);
                        $object->prefix = $name_check['prefix'];
                        $object->name   = $name_check['name'];
                    }
                    break;
                default:
            }
            if (!empty($data)) {
                $object->update($data);
            }

            return true;
        }

        return false;
    } // get_external_metadata

    /**
     * get_artist
     * Get an artist from musicbrainz
     * @param string $mbid
     * @return array
     */
    public function get_artist(string $mbid)
    {
        //debug_event(self::class, "get_artist: {{$mbid}}", 4);
        $mbrainz = new MusicBrainz(new RequestsHttpAdapter());
        $results = array();
        $data    = array();
        if (Vainfo::is_mbid($mbid)) {
            try {
                $results = $mbrainz->lookup('artist', $mbid);
            } catch (Exception $error) {
                debug_event('MusicBrainz.plugin', 'Lookup error ' . $error, 3);

                return $data;
            }
        }
        if (is_array($results) && !empty($results)) {
            $results = $results[0];
        }
        if (!empty($results) && isset($results->{'name'}) && isset($results->{'id'})) {
            $data = array(
                'name' => $results->{'name'},
                'mbid' => $results->{'id'}
            );
        }

        return $data;
    } // get_artist
}
