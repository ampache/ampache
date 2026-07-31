<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

/**
 * Copy this file into src/Plugin/ and register the class in PluginEnum::LIST, keyed by the
 * lowercase name you want it stored under in the database. Plugins are no longer discovered
 * by scanning a folder; a class missing from PluginEnum::LIST is never loaded.
 */
class AmpacheExample extends AmpachePlugin
{
    // avatar, geolocation, home, lyrics, metadata, preview, save_rating, scrobbling, share,
    // shortener, slideshow, stats, stream_control, user, wanted
    public string $categories  = 'home';
    public string $description = 'Example Plugin';
    public string $max_ampache = '999999';
    public string $min_ampache = '370021';
    public string $name        = 'Example';
    public string $url         = '';
    public string $version     = '000001';

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
     * load
     * This loads up the data we need into this object, this stuff comes from the preferences.
     */
    public function load(User $user): bool
    {
        unset($user);

        return true;
    }

    /**
     * Implement one or more of the hooks below to make the plugin do something. Each hook is
     * looked up with method_exists() against the matching PluginTypeEnum case, so the method
     * name has to match exactly. Where an interface exists, implement it as well so the call
     * sites can type check the plugin before calling it.
     *
     * display_home(): void                                                        PluginDisplayHomeInterface
     * display_map(array $points): bool                                            PluginLocationInterface
     * display_on_footer(): void                                                   PluginDisplayOnFooterInterface
     * display_user_field(?library_item $libitem = null): void                     PluginDisplayUserFieldInterface
     * external_share(string $url, string $text): string                           PluginExternalShareInterface
     * gather_arts(string $type, ?array $options = [], ?int $limit = 5): array      PluginGatherArtsInterface
     * get_avatar_url(User $user, ?int $size = 80): string                         PluginGetAvatarUrlInterface
     * get_external_metadata(library_item $object, string $object_type): bool      (no interface)
     * get_location_name(float $latitude, float $longitude): string                PluginLocationInterface
     * get_lyrics(Song $song): ?array                                              PluginGetLyricsInterface
     * get_metadata(array $gather_types, array $media_info): array                 PluginGetMetadataInterface
     * get_photos(string $search, string $category = 'concert'): array             (no interface)
     * get_song_preview(string $track_mbid, string $artist_name, string $title): array   PluginSongPreviewInterface
     * process_wanted(Wanted $wanted): bool                                        PluginProcessWantedInterface
     * save_mediaplay(Song $song): bool                                            PluginSaveMediaplayInterface
     * save_rating(Rating $rating, int $new_rating): void                          (no interface)
     * set_flag(Song $song, bool $flagged): void                                   PluginSaveMediaplayInterface
     * shortener(string $url): ?string                                             PluginShortenerInterface
     * stream_control(array $media_ids): bool                                      PluginStreamControlInterface
     * stream_song_preview(string $file): void                                     PluginSongPreviewInterface
     */
    public function PLUGIN_FUNCTION(): void
    {
        // usually you would do something here
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
}
