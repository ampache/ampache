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

namespace Ampache\Gui\Preferences;

/**
 * The rules saying which preference cancels which other one, each naming the file where the two meet
 *
 * Conditions prefixed `config:` read `config/ampache.cfg.php` rather than a preference.
 */
final class PreferencePrerequisiteCatalog
{
    /** @var ?array<string, list<PreferencePrerequisite>> */
    private ?array $byPreference = null;

    /**
     * @return list<PreferencePrerequisite>
     */
    public function all(): array
    {
        return [
            ...$this->transcoding(),
            ...$this->playback(),
            ...$this->quotas(),
            ...$this->uploads(),
            ...$this->backends(),
            ...$this->interface(),
            ...$this->sidebar(),
        ];
    }

    /**
     * The config settings the rules need, so the caller can read them once and hand them over.
     *
     * @return list<string>
     */
    public function configKeys(): array
    {
        $keys = [];
        foreach ($this->all() as $rule) {
            $keys = [...$keys, ...$rule->configKeys()];
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param array<string, string> $values the subject's preferences, plus `config:<key>` entries
     */
    public function find(string $preference, array $values): ?string
    {
        // a page asks this once per row, so the rules are built and indexed once rather than 55 times each
        if ($this->byPreference === null) {
            $this->byPreference = [];
            foreach ($this->all() as $rule) {
                $this->byPreference[$rule->preference][] = $rule;
            }
        }

        foreach ($this->byPreference[$preference] ?? [] as $rule) {
            if ($rule->holds($values)) {
                return $rule->text;
            }
        }

        return null;
    }

    /**
     * @return list<PreferencePrerequisite>
     */
    private function backends(): array
    {
        $rules = [];

        // SubsonicApiApplication.php answers "Disabled" before any subsonic preference is read
        foreach (['subsonic_always_download', 'subsonic_single_user_data', 'subsonic_force_album_artist', 'subsonic_legacy'] as $name) {
            $rules[] = new PreferencePrerequisite(
                $name,
                [['subsonic_backend', PreferencePrerequisite::IS_OFF]],
                T_('The Subsonic backend is off, so this has no effect.')
            );
        }

        // DaapApiApplication.php answers "Disabled" before the password is read
        $rules[] = new PreferencePrerequisite(
            'daap_pass',
            [['daap_backend', PreferencePrerequisite::IS_OFF]],
            T_('The DAAP backend is off, so this password protects nothing.')
        );

        // JellyfinApiApplication.php refuses before /QuickConnect/Enabled can answer
        $rules[] = new PreferencePrerequisite(
            'quickconnect_enable',
            [['quickconnect_enable', PreferencePrerequisite::IS_ON], ['jellyfin_backend_enable', PreferencePrerequisite::IS_OFF]],
            T_('Jellyfin clients cannot reach QuickConnect while the Jellyfin backend is off. Ampache clients still can.')
        );

        // ApiHandler.php drops the forced version when its own api_enable_N is off
        foreach (['3', '4', '5', '6', '8'] as $version) {
            $rules[] = new PreferencePrerequisite(
                'api_force_version',
                [['api_force_version', PreferencePrerequisite::IS, $version], ['api_enable_' . $version, PreferencePrerequisite::IS_OFF]],
                T_('That API version is disabled, so the forced version is ignored and clients negotiate another one.')
            );
        }

        // Stats.php requires the cron cache before the live count is consulted
        $rules[] = new PreferencePrerequisite(
            'cron_cache_live_count',
            [['cron_cache', PreferencePrerequisite::IS_OFF]],
            T_('The cron cache is off, so live plays are counted anyway and this changes nothing.')
        );

        // AbstractShareCreateMethod.php throws before share_expire is read
        $rules[] = new PreferencePrerequisite(
            'share_expire',
            [['share', PreferencePrerequisite::IS_OFF]],
            T_('Sharing is off, so no share is ever created to expire.')
        );

        return $rules;
    }

    /**
     * @return list<PreferencePrerequisite>
     */
    private function interface(): array
    {
        return [
            // Art.php returns before the template is read when drawn art is off
            new PreferencePrerequisite(
                'generated_art_template',
                [['generated_art', PreferencePrerequisite::IS_OFF]],
                T_('Drawn placeholders are off, so this style is never used.')
            ),
            // AbstractShowAction.php drops the drawing whenever a custom blank album is set
            new PreferencePrerequisite(
                'generated_art',
                [['generated_art', PreferencePrerequisite::IS_ON], ['custom_blankalbum', PreferencePrerequisite::IS_NOT, '']],
                T_('A custom blank album image is set, so drawn placeholders only appear on browse pages. The API, Subsonic, feeds and the web player serve that image instead.')
            ),
            // AmpacheRatingMatch.php requires a minimum above zero before anything is copied or written
            new PreferencePrerequisite(
                'ratingmatch_write_tags',
                [['ratingmatch_write_tags', PreferencePrerequisite::IS_ON], ['ratingmatch_stars', PreferencePrerequisite::IS_EMPTY]],
                T_('The minimum star rating is 0, which turns the whole sync off: nothing is written to your files.')
            ),
            // AmpachePersonalFavorites.php renders nothing at all while the widget is off
            new PreferencePrerequisite(
                'personalfav_playlist',
                [['personalfav_display', PreferencePrerequisite::IS_OFF]],
                T_('The favourites widget is off, so this list is never shown.')
            ),
            new PreferencePrerequisite(
                'personalfav_smartlist',
                [['personalfav_display', PreferencePrerequisite::IS_OFF]],
                T_('The favourites widget is off, so this list is never shown.')
            ),
            // the arrows collapse the sidebar whatever `sidebar_light` says, so only a missing switcher settles it
            new PreferencePrerequisite(
                'sidebar_hide_playlist',
                [
                    ['sidebar_hide_playlist', PreferencePrerequisite::IS_ON],
                    ['sidebar_light', PreferencePrerequisite::IS_OFF],
                    ['sidebar_hide_switcher', PreferencePrerequisite::IS_ON],
                ],
                T_('The sidebar can no longer be collapsed, and this only applies to its collapsed form.')
            ),
            // HeaderView.php keeps the full sidebar collapsed for good once the switcher is gone
            new PreferencePrerequisite(
                'browse_filter',
                [['sidebar_light', PreferencePrerequisite::IS_ON], ['sidebar_hide_switcher', PreferencePrerequisite::IS_ON]],
                T_('The full sidebar can no longer be opened, and the filter box only lives there.')
            ),
            // Artist/ShowAction.php asks for grouped albums only when release types are enabled
            new PreferencePrerequisite(
                'album_release_type_sort',
                [['album_release_type', PreferencePrerequisite::IS_OFF]],
                T_('Albums are not grouped by release type, so this order is never used.')
            ),
            // web_player.phtml:319 never calls the notifier, so the timeout is never reached
            new PreferencePrerequisite(
                'browser_notify_timeout',
                [['browser_notify', PreferencePrerequisite::IS_OFF]],
                T_('Browser notifications are off, so nothing is ever shown for this long.')
            ),
            // HomeView.php requires both before the panel is drawn
            new PreferencePrerequisite(
                'home_moment_videos',
                [['allow_video', PreferencePrerequisite::IS_OFF]],
                T_('Video is disabled on this server, so this panel never appears.')
            ),
            // SongListRenderer.php requires licensing first
            new PreferencePrerequisite(
                'show_license',
                [['show_license', PreferencePrerequisite::IS_ON], ['config:licensing', PreferencePrerequisite::IS_OFF]],
                T_('Licensing is off in the server configuration file, so no licence is ever shown.')
            ),
            // MediaEmbedView.php refuses to publish a session-bound stream url into someone else's page
            new PreferencePrerequisite(
                'embed_player',
                [
                    ['embed_player', PreferencePrerequisite::IS_ON],
                    ['config:use_auth', PreferencePrerequisite::IS_ON],
                    ['config:require_session', PreferencePrerequisite::IS_ON],
                ],
                T_('Every stream on this server needs a session, so no embedded player is ever offered to other sites.')
            ),
            // WebPlayerView.php only draws the broadcast button when the config file allows broadcasting
            new PreferencePrerequisite(
                'broadcast_by_default',
                [['broadcast_by_default', PreferencePrerequisite::IS_ON], ['config:broadcast', PreferencePrerequisite::IS_OFF]],
                T_('Broadcasting is off in the server configuration file, so the player never starts one.')
            ),
            // HomeSidebarView.php asks for the Video entry only while video is allowed
            new PreferencePrerequisite(
                'sidebar_hide_video',
                [['sidebar_hide_video', PreferencePrerequisite::IS_ON], ['allow_video', PreferencePrerequisite::IS_OFF]],
                T_('Video is disabled on this server, so the Video entry is never drawn anyway.')
            ),
        ];
    }

    /**
     * @return list<PreferencePrerequisite>
     */
    private function playback(): array
    {
        $rules = [];

        // DemocraticAction.php reads it too, and StreamItemAction.php hands out a playlist whatever the type
        $rules[] = new PreferencePrerequisite(
            'playlist_type',
            [
                ['play_type', PreferencePrerequisite::IS_NOT, 'stream'],
                ['play_type', PreferencePrerequisite::IS_NOT, 'democratic'],
            ],
            T_('Your playback type does not hand out playlists, so this only shapes the "Download Playlist" link.')
        );

        // StreamAjaxHandler.php refuses to switch to a mode its allow_* preference turns off
        foreach (['stream', 'democratic', 'localplay'] as $mode) {
            $rules[] = new PreferencePrerequisite(
                'play_type',
                [['play_type', PreferencePrerequisite::IS, $mode], ['allow_' . $mode . '_playback', PreferencePrerequisite::IS_OFF]],
                T_('This playback type is no longer allowed on the server, so it is not offered here: pick another one, nothing plays with it.')
            );
        }

        return $rules;
    }

    /**
     * @return list<PreferencePrerequisite>
     */
    private function quotas(): array
    {
        $rules = [];
        foreach (['hits', 'time', 'bandwidth'] as $kind) {
            // AmpacheStreamHits.php returns before the window is read when the maximum is negative
            $rules[] = new PreferencePrerequisite(
                'stream_control_' . $kind . '_days',
                [['stream_control_' . $kind . '_max', PreferencePrerequisite::IS_EMPTY]],
                T_('The matching limit is unlimited, so this window is never used.')
            );

            // the three plugins return true, allowing the stream, when graphs are off
            $rules[] = new PreferencePrerequisite(
                'stream_control_' . $kind . '_max',
                [['config:statistical_graphs', PreferencePrerequisite::IS_OFF]],
                T_('Stream limits need statistical graphs, which are off in the server configuration file. Nothing is enforced.')
            );
        }

        return $rules;
    }

    /**
     * @return list<PreferencePrerequisite>
     */
    private function sidebar(): array
    {
        $rules = [];

        // HomeSidebarView.php reads a section's order only while that section is shown
        foreach (['browse', 'dashboard', 'search', 'information'] as $section) {
            $rules[] = new PreferencePrerequisite(
                'sidebar_order_' . $section,
                [['sidebar_hide_' . $section, PreferencePrerequisite::IS_ON]],
                T_('That sidebar section is hidden, so its order is never used.')
            );
        }

        return $rules;
    }

    /**
     * `PlayAction.php` nulls the target when transcoding is off, so no encode target is ever read
     *
     * @return list<PreferencePrerequisite>
     */
    private function transcoding(): array
    {
        $never = [['transcode', PreferencePrerequisite::IS, 'never']];
        $off   = T_('Transcoding is off, so this is never read.');

        $rules = [];
        foreach ([
            'transcode_bitrate',
            'transcode_bitrate_webplayer',
            'transcode_bitrate_api',
            'max_bit_rate',
            'min_bit_rate',
            'encode_target',
            'encode_video_target',
            'encode_player_webplayer_target',
            'encode_player_api_target',
        ] as $name) {
            $rules[] = new PreferencePrerequisite($name, $never, $off);
        }

        // Stream.php returns the player override before ever reading the default
        $rules[] = new PreferencePrerequisite(
            'transcode_bitrate',
            [['transcode', PreferencePrerequisite::IS_NOT, 'never'], ['transcode_bitrate_webplayer', PreferencePrerequisite::IS_NOT, '0']],
            T_('The web player has its own bitrate set, which wins over this one for the web player.')
        );
        $rules[] = new PreferencePrerequisite(
            'transcode_bitrate',
            [['transcode', PreferencePrerequisite::IS_NOT, 'never'], ['transcode_bitrate_api', PreferencePrerequisite::IS_NOT, '0']],
            T_('The API has its own bitrate set, which wins over this one for the API.')
        );

        // Stream.php falls back to the webplayer target for songs even when no player was named
        $rules[] = new PreferencePrerequisite(
            'encode_target',
            [['transcode', PreferencePrerequisite::IS_NOT, 'never'], ['encode_player_webplayer_target', PreferencePrerequisite::IS_NOT, '']],
            T_('The web player output format wins over this one, and it applies to most streams, not only the web player.')
        );

        // Stream.php reads the floor only inside the `max_bit_rate > 1` branch, so 0, -1 and 1 all mean none
        foreach ([PreferencePrerequisite::IS_EMPTY => null, PreferencePrerequisite::IS => '1'] as $operator => $value) {
            $rules[] = new PreferencePrerequisite(
                'min_bit_rate',
                [
                    ['transcode', PreferencePrerequisite::IS_NOT, 'never'],
                    ($value === null) ? ['max_bit_rate', $operator] : ['max_bit_rate', $operator, $value],
                ],
                T_('The floor is only applied while a maximum bitrate is set, and a maximum of 1 counts as none.')
            );
        }

        return $rules;
    }

    /**
     * @return list<PreferencePrerequisite>
     */
    private function uploads(): array
    {
        return [
            // Upload/DefaultAction.php requires a catalog id above zero; the shipped value is -1
            new PreferencePrerequisite(
                'upload_catalog',
                [['allow_upload', PreferencePrerequisite::IS_ON], ['upload_catalog', PreferencePrerequisite::IS_EMPTY]],
                T_('Uploads are allowed but no destination catalog is chosen, so every upload fails.')
            ),
            // Upload.php runs the script only when the config file allows scripts at all
            new PreferencePrerequisite(
                'upload_script',
                [['upload_script', PreferencePrerequisite::IS_NOT, ''], ['config:allow_upload_scripts', PreferencePrerequisite::IS_OFF]],
                T_('Upload scripts are disabled in the server configuration file, so this command never runs.')
            ),
            // Catalog.php returns false before ever reaching the upload_allow_remove branch
            new PreferencePrerequisite(
                'upload_allow_remove',
                [['upload_allow_remove', PreferencePrerequisite::IS_ON], ['config:delete_from_disk', PreferencePrerequisite::IS_OFF]],
                T_('Deleting from disk is off in the server configuration file, so no delete button ever appears.')
            ),
            // PodcastSyncer.php downloads nothing when the limit is negative, so nothing is ever pruned
            new PreferencePrerequisite(
                'podcast_keep',
                [['podcast_new_download', PreferencePrerequisite::IS, '-1']],
                T_('New episodes are never downloaded, so nothing is ever pruned either.')
            ),
        ];
    }
}
