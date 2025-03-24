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
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Label;
use Ampache\Repository\Model\Plugin;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Exception;
use MusicBrainz\Entities\EntityInterface;
use MusicBrainz\Entities\Genre;
use MusicBrainz\Entities\Recording;
use MusicBrainz\Entities\ReleaseGroup;
use MusicBrainz\MusicBrainz;
use MusicBrainz\Objects\LifeSpan;
use MusicBrainz\Objects\Tag;

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
     * find
     * Lookup item by mbid or search by name / artist information
     * @param array<string, string|null> $media_info
     */
    private function _find(array $media_info): ?EntityInterface
    {
        if (isset($media_info['mb_trackid'])) {
            $object_type = 'track';
            $mbid        = $media_info['mb_trackid'];
            $fullname    = $media_info['song'] ?? $media_info['title'] ?? '';
            $parent_name = $media_info['artist'] ?? '';
        } elseif (isset($media_info['mb_albumid_group'])) {
            $object_type = 'album';
            $mbid        = $media_info['mb_albumid_group'];
            $fullname    = $media_info['album'] ?? $media_info['title'] ?? '';
            $parent_name = $media_info['albumartist'] ?? $media_info['artist'] ?? '';
        } elseif (isset($media_info['mb_artistid'])) {
            $object_type = 'artist';
            $mbid        = $media_info['mb_artistid'];
            $fullname    = $media_info['artist'] ?? $media_info['title'] ?? '';
            $parent_name = '';
        } elseif (isset($media_info['mb_labelid'])) {
            $object_type = 'label';
            $mbid        = $media_info['mb_labelid'];
            $fullname    = $media_info['label'] ?? $media_info['title'] ?? '';
            $parent_name = '';
        } else {
            return null;
        }

        $results = false;
        if (MusicBrainz::isMBID($mbid)) {
            try {
                $brainz = MusicBrainz::newMusicBrainz('request');
                switch ($object_type) {
                    case 'label':
                        $lookup = $brainz->lookup($object_type, $mbid, ['genres', 'tags']);
                        /**
                         * https://musicbrainz.org/ws/2/label/b66d15cc-b372-4dc1-8cbd-efdeb02e23e7?fmt=json
                         * @var \MusicBrainz\Entities\Label $results
                         */
                        $results = $brainz->getObject($lookup, $object_type);
                        break;
                    case 'album':
                        $lookup = $brainz->lookup('release-group', $mbid, ['releases', 'genres', 'tags']);
                        /**
                         * https://musicbrainz.org/ws/2/release-group/299f707e-ddf1-4edc-8a76-b0e85a31095b?inc=releases+tags&fmt=json
                         * @var ReleaseGroup $results
                         */
                        $results = $brainz->getObject($lookup, 'release-group');
                        break;
                    case 'artist':
                        $lookup = $brainz->lookup($object_type, $mbid, ['release-groups', 'genres', 'tags']);
                        /**
                         * https://musicbrainz.org/ws/2/artist/859a5c63-08df-42da-905c-7307f56db95d?inc=release-groups+tags&fmt=json
                         * @var \MusicBrainz\Entities\Artist $results
                         */
                        $results = $brainz->getObject($lookup, $object_type);
                        break;
                    case 'track':
                        $lookup = $brainz->lookup('recording', $mbid, ['artists', 'releases', 'genres', 'tags']);
                        /**
                         * https://musicbrainz.org/ws/2/recording/140e8071-d7bb-4e05-9547-bfeea33916d0?inc=artists+releases&fmt=json
                         * @var Recording $results
                         */
                        $results = $brainz->getObject($lookup, 'recording');

                        break;
                    default:
                }
            } catch (Exception $error) {
                debug_event('MusicBrainz.plugin', 'Lookup error ' . $error->getMessage(), 3);

                return null;
            }
        } else {
            try {
                $brainz = MusicBrainz::newMusicBrainz('request');
                switch ($object_type) {
                    case 'label':
                        $args   = ['name' => $fullname];
                        $filter = MusicBrainz::newFilter('label', $args);
                        $search = $brainz->search($filter, 1, null, false);
                        /**
                         * https://musicbrainz.org/ws/2/label?query=Arrow%20land&fmt=json
                         * @var \MusicBrainz\Entities\Label[] $results
                         */
                        $results = $brainz->getObjects($search, $object_type);
                        if (!empty($results)) {
                            /** @var \MusicBrainz\Entities\Label $results */
                            $results = $results[0];
                        }

                        break;
                    case 'album':
                        $args = [
                            'release' => $fullname,
                            'artist' => $parent_name,
                        ];
                        $filter = MusicBrainz::newFilter('release-group', $args);
                        $search = (array)$brainz->search(
                            $filter,
                            1,
                            null,
                            false,
                        );
                        /**
                         * https://musicbrainz.org/ws/2/release-group?query=release:The%20Shape%20AND%20artist:Code%2064&fmt=json
                         * @var ReleaseGroup[] $results
                         */
                        $results = $brainz->getObjects($search, 'release-group');
                        if (!empty($results)) {
                            /** @var ReleaseGroup $results */
                            $results = $results[0];
                        }

                        break;
                    case 'artist':
                        $args   = ['name' => $fullname];
                        $filter = MusicBrainz::newFilter('artist', $args);
                        $search = (array)$brainz->search(
                            $filter,
                            1,
                            null,
                            false,
                        );
                        /**
                         * https://musicbrainz.org/ws/2/artist?query=name:Code%2064&fmt=json
                         * @var \MusicBrainz\Entities\Artist[] $results
                         */
                        $results = $brainz->getObjects($search, 'artist');
                        if (!empty($results)) {
                            /** @var \MusicBrainz\Entities\Artist $results */
                            $results = $results[0];
                        }

                        break;
                    case 'track':
                        $args = [
                            'title' => $fullname,
                            'artist' => $parent_name,
                        ];
                        $filter = MusicBrainz::newFilter('recording', $args);
                        $search = (array)$brainz->search(
                            $filter,
                            1,
                            null,
                            false,
                        );
                        /**
                         * https://musicbrainz.org/ws/2/release-group?query=release:The%20Shape%20AND%20artist:Code%2064&fmt=json
                         * @var Recording[] $results
                         */
                        $results = $brainz->getObjects($search, 'recording');
                        if (!empty($results)) {
                            /** @var Recording $results */
                            $results = $results[0];
                        }

                        break;
                    default:
                        return null;
                }
            } catch (Exception $error) {
                debug_event('MusicBrainz.plugin', 'Lookup error ' . $error, 3);

                return null;
            }
        }

        // couldn't find an object
        if (!$results instanceof EntityInterface) {
            return null;
        }

        return $results;
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
        try {
            $brainz = MusicBrainz::newMusicBrainz('request');
        } catch (Exception) {
            return [];
        }

        if (isset($media_info['mb_trackid'])) {
            $object_type = 'track';
        } elseif (isset($media_info['mb_albumid_group'])) {
            $object_type = 'album';
        } elseif (isset($media_info['mb_artistid'])) {
            $object_type = 'artist';
        } elseif (isset($media_info['mb_labelid'])) {
            $object_type = 'label';
        } else {
            return [];
        }

        // lookup a musicbrainz object
        $results = self::_find($media_info);

        // couldn't find an object
        if (!$results instanceof EntityInterface) {
            debug_event('MusicBrainz.plugin', 'Entity not found ' . $object_type, 3);

            return [];
        }

        $genres     = [];
        $brainzData = $results->getData();
        try {
            foreach ($brainz->getObjects($brainzData, 'tag') as $tag) {
                /** @var Tag $tag */
                $genres[] = $tag->name;
            }
        } catch (Exception) {
            // no tags found;
        }
        try {
            foreach ($brainz->getObjects($brainzData, 'genre') as $genre) {
                /** @var Genre $genre */
                $genres[] = $genre->getName();
            }
        } catch (Exception) {
            // no genres found;
        }

        if (
            isset($brainzData['artist-credit']) ||
            isset($brainzData['releases'])
        ) {
            // pull first artist-credit
            if (isset($brainzData['artist-credit']) && count($brainzData['artist-credit']) > 0) {
                $artist = $brainzData['artist-credit'][0];
                $artist = (is_array($artist))
                    ? $artist['artist']
                    : (array)$artist->{'artist'};
            }

            // pull first release
            if (isset($brainzData['releases']) && count($brainzData['releases']) == 1) {
                $release = $brainzData['releases'][0];
            }

            $results = (array)$results;
            if (isset($artist)) {
                $results['mb_artistid'] = $artist['id'];
                $results['artist']      = $artist['name'];
            }

            if (isset($release)) {
                $results['album'] = is_array($release)
                    ? $release['title']
                    : $release->title;
            }
        } else {
            $results = (array)$results;
        }

        if (!empty($genres)) {
            $results['genre'] = array_unique($genres);
        }

        return $results;
    }

    /**
     * get_external_metadata
     * Update an object (label or artist for now) using musicbrainz
     * @param Label|Album|Artist|Song $object
     */
    public function get_external_metadata($object, string $object_type): bool
    {
        // Artist and label metadata only for now
        $media_info = [];
        $fullname   = $object->get_fullname();
        if ($object_type === 'song' || $object instanceof Song) {
            debug_event('MusicBrainz.plugin', 'get_external_metadata only supports Labels and Artists (' . $object_type . ')', 5);

            return false;
        }
        if ($object_type === 'album' || $object instanceof Album) {
            debug_event('MusicBrainz.plugin', 'get_external_metadata only supports Labels and Artists (' . $object_type . ')', 5);

            return false;
        }

        if ($object_type === 'artist' && $object instanceof Artist) {
            $media_info['mb_artistid'] = $object->mbid;
            $media_info['artist']      = $fullname;
            $results                   = self::_find($media_info);
        } elseif ($object_type === 'label' && $object instanceof Label) {
            $media_info['mb_labelid'] = $object->mbid;
            $media_info['label']      = $fullname;
            $results                  = self::_find($media_info);
        } else {
            debug_event('MusicBrainz.plugin', 'get_external_metadata only supports Labels and Artists (' . $object_type . ')', 5);

            return false;
        }

        if ($results instanceof EntityInterface) {
            try {
                debug_event('MusicBrainz.plugin', sprintf('Updating %s: ', $object_type) . $fullname, 3);
                $data       = [];
                $brainzData = $results->getData();
                $life_span  = $brainzData['life-span'] ?? null;
                $active     = 1;
                $begin      = '';
                if (is_array($life_span)) {
                    $active = ($life_span['ended'] == 1) ? 0 : 1;
                    $begin  = $life_span['begin'] ?? '';
                } elseif (is_object($life_span)) {
                    /** @var LifeSpan $life_span */
                    $active = ($life_span->{'ended'} == 1) ? 0 : 1;
                    $begin  = $life_span->{'begin'} ?? '';
                }

                $begin_area = $brainzData['begin-area'] ?? null;
                $beginName  = null;
                if (is_array($begin_area)) {
                    $beginName = $begin_area['name'] ?? null;
                } elseif (is_object($begin_area)) {
                    $beginName = $begin_area->{'name'} ?? null;
                }

                $area     = $brainzData['area'] ?? null;
                $areaName = null;
                if (is_array($area)) {
                    $areaName = ($area['name']) ?? null;
                } elseif (is_object($area)) {
                    $areaName = ($area->{'name'}) ?? null;
                }
            } catch (Exception) {
                return false;
            }

            switch ($object_type) {
                case 'label':
                    if ($object instanceof Label) {
                        $data = [
                            /** @var \MusicBrainz\Entities\Label $results */
                            'name' => $results->getName(),
                            'mbid' => $results->getId(),
                            'category' => $results->type ?? $object->category,
                            'summary' => $results->getData()['disambiguation'] ?? $object->summary,
                            'address' => $object->address,
                            'country' => $results->country ?? $object->country,
                            'email' => $object->email,
                            'website' => $object->website,
                            'active' => $active
                        ];
                    }
                    break;
                case 'artist':
                    if ($object instanceof Artist) {
                        $data = [
                            /** @var \MusicBrainz\Entities\Artist $results */
                            'name' => $results->getName(),
                            'mbid' => $results->getId(),
                            'summary' => $object->summary,
                            'placeformed' => $beginName ?? $areaName ?? null,
                            'yearformed' => explode('-', ($begin))[0] ?? $object->yearformed
                        ];
                    }

                    break;
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
        $results = false;
        $data    = [];
        if (MusicBrainz::isMBID($mbid)) {
            try {
                $brainz = MusicBrainz::newMusicBrainz('request');
                $lookup = $brainz->lookup('artist', $mbid, ['tags']);
                /**
                 * https://musicbrainz.org/ws/2/artist/859a5c63-08df-42da-905c-7307f56db95d?inc=release-groups&fmt=json
                 * @var \MusicBrainz\Entities\Artist $results
                 */
                $results = $brainz->getObject($lookup, 'artist');
            } catch (Exception $error) {
                debug_event('MusicBrainz.plugin', 'Lookup error ' . $error->getMessage(), 3);

                return [];
            }
        }

        if ($results) {
            $data = [
                'name' => $results->getName(),
                'mbid' => $results->getId(),
            ];
        }

        return $data;
    }
}
