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

use Ampache\Module\Authorization\AccessLevelEnum;
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

class AmpacheMusicBrainz extends AmpachePlugin implements PluginGetMetadataInterface
{
    public string $name        = 'MusicBrainz';

    public string $categories  = 'metadata';

    public string $description = 'MusicBrainz metadata integration';

    public string $url         = 'http://www.musicbrainz.org';

    public string $version     = '000003';

    public string $min_ampache = '360003';

    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    public bool $overwrite_name = false;

    /**
     * Constructor
     * This function does nothing
     */
    public function __construct()
    {
        $this->description = T_('MusicBrainz metadata integration');
    }

    /**
     * install
     * This is a required plugin function
     */
    public function install(): bool
    {
        return Preference::insert('mb_overwrite_name', T_('Overwrite Artist names that match an mbid'), '0', AccessLevelEnum::USER->value, 'boolean', 'plugins', $this->name);
    }

    /**
     * uninstall
     * This is a required plugin function
     */
    public function uninstall(): bool
    {
        return true;
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

        if (Preference::exists('mb_overwrite_name') === 0) {
            // this wasn't installed correctly only upgraded so may be missing
            Preference::insert('mb_overwrite_name', T_('Overwrite Artist names that match an mbid'), '0', AccessLevelEnum::USER->value, 'boolean', 'plugins', $this->name);
        }

        // did the upgrade work?
        return (bool) Preference::exists('mb_overwrite_name');
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
        if (!array_key_exists('mb_overwrite_name', $data)) {
            $data['mb_overwrite_name'] = Preference::get_by_user(-1, 'mb_overwrite_name');
        }

        // overwrite matching MBID artist names
        $this->overwrite_name = (bool)$data['mb_overwrite_name'];

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
            return [];
        }

        if (!$mbid = $media_info['mb_trackid']) {
            return [];
        }

        $mbrainz  = new MusicBrainz(new RequestsHttpAdapter());
        $includes = [
            'artists',
            'releases',
        ];
        try {
            /**
             * https://musicbrainz.org/ws/2/recording/140e8071-d7bb-4e05-9547-bfeea33916d0?inc=artists+releases&fmt=json
             * @var object{
             *     id: string,
             *     artist-credit: array,
             *     length: int,
             *     disambiguation: string,
             *     video: bool,
             *     releases: array,
             *     first-release-date: string,
             *     title: string,
             * } $track
             */
            $track = $mbrainz->lookup('recording', $mbid, $includes);
        } catch (Exception $exception) {
            debug_event('MusicBrainz.plugin', 'Lookup error ' . $exception, 3);

            return [];
        }

        $results = [];

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
    }

    /**
     * get_external_metadata
     * Update an object (label or artist for now) using musicbrainz
     * @param Label|Artist $object
     */
    public function get_external_metadata($object, string $object_type): bool
    {
        $valid_types = ['label', 'artist'];
        // Artist metadata only for now
        if (!in_array($object_type, $valid_types)) {
            debug_event('MusicBrainz.plugin', 'get_external_metadata only supports Labels and Artists', 5);

            return false;
        }

        $mbrainz = new MusicBrainz(new RequestsHttpAdapter());
        $results = [];
        if ($object->mbid !== null && VaInfo::is_mbid($object->mbid)) {
            try {
                switch ($object_type) {
                    case 'label':
                    case 'artist':
                        /**
                         * https://musicbrainz.org/ws/2/artist/859a5c63-08df-42da-905c-7307f56db95d?inc=release-groups&fmt=json
                         * @var object{
                         *     sort-name: string,
                         *     id: string,
                         *     area: object,
                         *     disambiguation: string,
                         *     isnis: array,
                         *     begin-area: ?string,
                         *     name: string,
                         *     ipis: array,
                         *     release-groups: array,
                         *     end-area: ?string,
                         *     type: string,
                         *     end_area: ?string,
                         *     gender: ?string,
                         *     life-span: object,
                         *     gender-id: ?string,
                         *     type-id: string,
                         *     begin_area: ?string,
                         *     country: string
                         * } $results
                         */
                        $results = $mbrainz->lookup($object_type, $object->mbid);
                        break;
                    default:
                }
            } catch (Exception $error) {
                debug_event('MusicBrainz.plugin', 'Lookup error ' . $error, 3);

                return false;
            }
        } else {
            try {
                $args = [$object_type => $object->get_fullname()];
                switch ($object_type) {
                    case 'label':
                        /**
                         * https://musicbrainz.org/ws/2/artist/859a5c63-08df-42da-905c-7307f56db95d?inc=release-groups&fmt=json
                         * @var object{
                         *     created: string,
                         *     count: int,
                         *     offset: int,
                         *     labels: array,
                         * } $results
                         */
                        $results = $mbrainz->search(new LabelFilter($args), 1);
                        if (!empty($results->{'labels'})) {
                            $results = $results->{'labels'}[0];
                        }

                        break;
                    case 'artist':
                        /**
                         * https://musicbrainz.org/ws/2/artist/859a5c63-08df-42da-905c-7307f56db95d?inc=release-groups&fmt=json
                         * @var object{
                         *     created: string,
                         *     count: int,
                         *     offset: int,
                         *     artists: array,
                         * } $results
                         */
                        $results = $mbrainz->search(new ArtistFilter($args), 1);
                        if (!empty($results->{'artists'})) {
                            $results = $results->{'artists'}[0];
                        }

                        break;
                    default:
                }
            } catch (Exception $error) {
                debug_event('MusicBrainz.plugin', 'Lookup error ' . $error, 3);

                return false;
            }
        }

        if (!empty($results)) {
            debug_event('MusicBrainz.plugin', sprintf('Updating %s: ', $object_type) . $object->get_fullname(), 3);
            $data = [];
            switch ($object_type) {
                case 'label':
                    /** @var Label $object */
                    $data = [
                        'name' => $results->{'name'} ?? $object->get_fullname(),
                        'mbid' => $results->{'id'} ?? $object->mbid,
                        'category' => $results->{'type'} ?? $object->category,
                        'summary' => $results->{'disambiguation'} ?? $object->summary,
                        'address' => $object->address,
                        'country' => $results->{'country'} ?? $object->country,
                        'email' => $object->email,
                        'website' => $object->website,
                        'active' => ($results->{'life-span'}->{'ended'} == 1) ? 0 : 1
                    ];
                    break;
                case 'artist':
                    /** @var Artist $object */
                    $placeFormed = $results->{'begin-area'}->{'name'} ?? $results->{'area'}->{'name'} ?? $object->placeformed;
                    $data        = [
                        'name' => $results->{'name'} ?? $object->get_fullname(),
                        'mbid' => $results->{'id'} ?? $object->mbid,
                        'summary' => $object->summary,
                        'placeformed' => $placeFormed,
                        'yearformed' => explode('-', ($results->{'life-span'}->{'begin'} ?? ''))[0] ?? $object->yearformed
                    ];

                    // when you come in with an mbid you might want to keep the name updated
                    if ($this->overwrite_name && $object->mbid !== null && VaInfo::is_mbid($object->mbid) && $data['name'] !== $object->get_fullname()) {
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
    }

    /**
     * get_artist
     * Get an artist from musicbrainz
     */
    public function get_artist(string $mbid): array
    {
        //debug_event(self::class, "get_artist: {{$mbid}}", 4);
        $mbrainz = new MusicBrainz(new RequestsHttpAdapter());
        $results = [];
        $data    = [];
        if (VaInfo::is_mbid($mbid)) {
            try {
                /**
                 * https://musicbrainz.org/ws/2/artist/859a5c63-08df-42da-905c-7307f56db95d?inc=release-groups&fmt=json
                 * @var object{
                 *     sort-name: string,
                 *     id: string,
                 *     area: object,
                 *     disambiguation: string,
                 *     isnis: array,
                 *     begin-area: ?string,
                 *     name: string,
                 *     ipis: array,
                 *     release-groups: array,
                 *     end-area: ?string,
                 *     type: string,
                 *     end_area: ?string,
                 *     gender: ?string,
                 *     life-span: object,
                 *     gender-id: ?string,
                 *     type-id: string,
                 *     begin_area: ?string,
                 *     country: string
                 * } $results
                 */
                $results = $mbrainz->lookup('artist', $mbid);
            } catch (Exception $error) {
                debug_event('MusicBrainz.plugin', 'Lookup error ' . $error, 3);

                return $data;
            }
        }

        if (!empty($results) && isset($results->{'name'}) && isset($results->{'id'})) {
            $data = [
                'name' => $results->{'name'},
                'mbid' => $results->{'id'}
            ];
        }

        return $data;
    }
}
