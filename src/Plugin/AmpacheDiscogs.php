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

use AmpacheDiscogs\Discogs;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Exception;

class AmpacheDiscogs extends AmpachePlugin implements PluginGatherArtsInterface, PluginGetMetadataInterface
{
    public string $name        = 'Discogs';

    public string $categories  = 'metadata';

    public string $description = 'Discogs metadata integration';

    public string $url         = 'http://www.discogs.com';

    public string $version     = '000001';

    public string $min_ampache = '370021';

    public string $max_ampache = '999999';

    // These are internal settings used by this class, run this->load to fill them out
    private string $api_key;

    private string $secret;

    private Discogs $discogs;

    /**
     * Constructor
     * This function does nothing
     */
    public function __construct()
    {
        $this->description = T_('Discogs metadata integration');
    }

    /**
     * install
     * This is a required plugin function
     */
    public function install(): bool
    {
        if (!Preference::insert('discogs_api_key', T_('Discogs consumer key'), '', AccessLevelEnum::MANAGER->value, 'string', 'plugins', $this->name)) {
            return false;
        }

        return Preference::insert('discogs_secret_api_key', T_('Discogs secret'), '', AccessLevelEnum::MANAGER->value, 'string', 'plugins', $this->name);
    }

    /**
     * uninstall
     * This is a required plugin function
     */
    public function uninstall(): bool
    {
        return (
            Preference::delete('discogs_api_key') &&
            Preference::delete('discogs_secret_api_key')
        );
    }

    /**
     * upgrade
     * This is a recommended plugin function
     */
    public function upgrade(): bool
    {
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
        if (!strlen(trim((string) $data['discogs_api_key'])) || !strlen(trim((string) $data['discogs_secret_api_key']))) {
            $data                           = [];
            $data['discogs_api_key']        = Preference::get_by_user(-1, 'discogs_api_key');
            $data['discogs_secret_api_key'] = Preference::get_by_user(-1, 'discogs_secret_api_key');
        }

        if (strlen(trim((string) $data['discogs_api_key'])) !== 0) {
            $this->api_key = trim((string) $data['discogs_api_key']);
        } else {
            debug_event(self::class, 'No Discogs api key, metadata plugin skipped', 3);

            return false;
        }

        if (strlen(trim((string) $data['discogs_secret_api_key'])) !== 0) {
            $this->secret = trim((string) $data['discogs_secret_api_key']);
        } else {
            debug_event(self::class, 'No Discogs secret, metadata plugin skipped', 3);

            return false;
        }

        $this->discogs = new Discogs($this->api_key, $this->secret);

        return true;
    }

    /**
     * get_metadata
     * Returns song metadata for what we're passed in.
     */
    public function get_metadata(array $gather_types, array $media_info): array
    {
        debug_event(self::class, 'Getting metadata from Discogs...', 5);

        // MUSIC metadata only
        if (!in_array('music', $gather_types)) {
            debug_event(self::class, 'Not a valid media type, skipped.', 5);

            return [];
        }

        $results = [];
        try {
            if (!empty($media_info['artist']) && !in_array('album', $media_info)) {
                $artists = $this->discogs->search_artist($media_info['artist']);
                if (isset($artists['results']) && count($artists['results']) > 0) {
                    foreach ($artists['results'] as $result) {
                        if ($result['title'] === $media_info['artist']) {
                            $artist = $this->discogs->get_artist((int)$result['id']);
                            if (isset($artist['images']) && count($artist['images']) > 0) {
                                $results['art'] = $artist['images'][0]['uri'];
                            }
                            if (!empty($artist['cover_image'])) {
                                $results['art'] = $artist['cover_image'];
                            }

                            // add in the data response as well
                            $results['data'] = $artist;
                            break;
                        }
                    }
                }
            }
            if (!empty($media_info['albumartist']) && !empty($media_info['album'])) {
                /**
                 * https://api.discogs.com/database/search?type=master&release_title=Ghosts&artist=Ladytron&per_page=10&key=key@secret=secret
                 */
                $albums = $this->discogs->search_album($media_info['albumartist'], $media_info['album']);
                if (empty($albums['results'])) {
                    $albums = $this->discogs->search_album($media_info['albumartist'], $media_info['album'], 'release');
                }

                // get the album that matches $artist - $album
                if (!empty($albums['results'])) {
                    /**
                     * @var array{
                     *     country: string,
                     *     year: string,
                     *     format: string[],
                     *     label: string[],
                     *     type: string,
                     *     genre: string[],
                     *     style: string[],
                     *     id: ?int,
                     *     barcode: string[],
                     *     master_id: int,
                     *     master_url: string,
                     *     uri: string,
                     *     catno: string,
                     *     title: string,
                     *     thumb: string,
                     *     cover_image: string,
                     *     resource_url: string,
                     *     community: object,
                     *     format_quantity: ?int,
                     *     formats: ?object,
                     * } $albumSearch
                     */
                    foreach ($albums['results'] as $albumSearch) {
                        if ($media_info['albumartist'] . ' - ' . $media_info['album'] === $albumSearch['title']) {
                            $album = $albumSearch;
                            break;
                        }
                    }
                    // look up the master release if we have one or the first release
                    if (!isset($album['id'])) {
                        /**
                         * @var array{
                         *     id: ?int,
                         *     main_release: int,
                         *     most_recent_release: int,
                         *     uri: string,
                         *     versions_uri: string,
                         *     main_release_uri: string,
                         *     most_recent_release_uri: string,
                         *     num_for_sale: int,
                         *     lowest_price: int,
                         *     images: object,
                         *     genres: string[],
                         *     styles: string[],
                         *     year: int,
                         *     tracklist: object,
                         *     artists: object,
                         *     title: string,
                         *     data-quality: string,
                         *     videos: object,
                         * } $album
                         */
                        $album = (($albums['results'][0]['master_id'] ?? 0) > 0)
                            ? $this->discogs->get_album((int)$albums['results'][0]['master_id'])
                            : $this->discogs->get_album((int)$albums['results'][0]['id'], 'releases');
                    }
                    // fallback to the initial search if we don't have a master
                    if (!isset($album['id'])) {
                        $album = $albums['results'][0];
                    }
                    if (isset($album['images']) && count($album['images']) > 0) {
                        $results['art'] = $album['images'][0]['uri'];
                    }
                    if (!empty($album['cover_image'])) {
                        $results['art'] = $album['cover_image'];
                    }

                    $genres = [];
                    foreach ($albums['results'] as $release) {
                        if (!empty($release['genre'])) {
                            $genres = array_merge($genres, $release['genre']);
                        }
                    }
                    if (!empty($release['style'])) {
                        $genres = array_merge($genres, $release['style']);
                    }

                    if (!empty($genres)) {
                        $results['genre'] = array_unique($genres);
                    }

                    // add in the data response as well
                    $results['data'] = $album;
                }
            }
        } catch (Exception $exception) {
            debug_event(self::class, 'Error getting metadata: ' . $exception->getMessage(), 1);
        }

        return $results;
    }

    /**
     * gather_arts
     * Returns art items for the requested media type
     */
    public function gather_arts(string $type, ?array $options = [], ?int $limit = 5): array
    {
        return array_slice(Art::gather_metadata_plugin($this, $type, ($options ?? [])), 0, $limit);
    }
}
