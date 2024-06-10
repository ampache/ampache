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

use Ampache\Repository\Model\User;

class AmpacheExample implements AmpachePluginInterface
{
    public string $name        = 'Example';
    public string $categories  = 'home';
    public string $description = 'Example Plugin';
    public string $url         = '';
    public string $version     = '000001';
    public string $min_ampache = '370021';
    public string $max_ampache = '999999';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->description = T_('Example Plugin');
    }

    /**
     * install
     * Inserts plugin preferences into Ampache
     */
    public function install(): bool
    {
        return true;
    }

    /**
     * uninstall
     * Removes our preferences from the database returning it to its original form
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
        return true;
    }

    /**
     * load
     * This loads up the data we need into this object, this stuff comes from the preferences.
     * @param User $user
     * @return bool
     */
    public function load($user): bool
    {
        $user->set_preferences();

        return true;
    }

    /**
     *  display_home() Display something in the home page / index
     *  display_on_footer() Same as home, except in the page footer
     *  display_user_field(library_item $libitem = null) This display the module in user page
     *  display_map(array $points) Used for graphs and charts
     *  external_share(string $public_url, string $share_name) Send a shared object to an external site
     *  gather_arts(string $type, array $options, integer $limit) Search for art externally
     *  get_avatar_url(User $user)
     *  get_lyrics(Song $song)
     *  get_location_name(float $latitude float $longitude)
     *  get_metadata(array $gather_types, array $media_info) Array of object types and array of info for that object
     *  get_photos(string $search_name)
     *  get_song_preview(string $track_mbid, string $artist_name, string $title)
     *  process_wanted(Wanted $wanted)
     *  save_mediaplay(Song $song)
     *  save_rating(Rating $rating, integer $new_value)
     *  set_flag(Song $song, boolean $flagged)
     *  shortener(string $url)
     *  stream_control(array $object_ids)
     *  stream_song_preview(string $file)
     */
    public function PLUGIN_FUNCTION(): void
    {
        // usually you would do something here
    }

}
