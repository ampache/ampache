<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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
declare(strict_types=0);

namespace Ampache\Plugin;

use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\User;
use Exception;
use MusicBrainz\Filters\LabelFilter;
use MusicBrainz\MusicBrainz;
use MusicBrainz\HttpAdapters\RequestsHttpAdapter;

class AmpacheMusicBrainz
{
    public $name        = 'MusicBrainz';
    public $categories  = 'metadata';
    public $description = 'MusicBrainz metadata integration';
    public $url         = 'http://www.musicbrainz.org';
    public $version     = '000001';
    public $min_ampache = '360003';
    public $max_ampache = '999999';

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
        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function
     */
    public function uninstall()
    {
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

        if (count($track->{'artist-credit'}) > 0) {
            $artist                 = $track->{'artist-credit'}[0];
            $artist                 = $artist->artist;
            $results['mb_artistid'] = $artist->id;
            $results['artist']      = $artist->name;
            $results['title']       = $track->title;
            if (count($track->releases) == 1) {
                $release          = $track->releases[0];
                $results['album'] = $release->title;
            }
        }

        return $results;
    } // get_metadata

    /**
     * update_label_metadata
     * Update a label from musicbrainz
     * @param Label $label
     * @return bool
     */
    public function update_label_metadata(Label $label)
    {
        $mbrainz = new MusicBrainz(new RequestsHttpAdapter());
        if ($label->mbid) {
            try {
                $results = $mbrainz->lookup('label', $label->mbid);
            } catch (Exception $error) {
                debug_event('MusicBrainz.plugin', 'Lookup error ' . $error, 3);

                return false;
            }
        } else {
            try {
                $results = $mbrainz->search(new LabelFilter(array("label" => $label->name)), 1);
            } catch (Exception $error) {
                debug_event('MusicBrainz.plugin', 'Lookup error ' . $error, 3);

                return false;
            }
        }
        if (is_array($results) && !empty($results)) {
            $results = $results[0];
        }
        if (!empty($results)) {
            debug_event('MusicBrainz.plugin', "Updating Label: " . $label->name, 3);
            $data = array(
                'name' => $label->name,
                'mbid' => $results->{'id'} ?: $label->mbid,
                'category' => $results->{'type'} ?: $label->category,
                'summary' => $results->{'disambiguation'} ?: $label->summary,
                'address' => $label->address,
                'country' => $results->{'country'} ?: $label->country,
                'email' => $label->email,
                'website' => $label->website,
                'active' => ($results->{'life-span'}->{'ended'} == 1) ? 0 : 1
            );
            $label->update($data);
        }

        return true;
    } // get_metadata
}
