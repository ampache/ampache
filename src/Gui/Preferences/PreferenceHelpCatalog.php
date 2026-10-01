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
 * The help text shown under a preference, sparse on purpose: a self-explanatory description gets no entry
 */
final class PreferenceHelpCatalog
{
    /** @var ?array<string, PreferenceHelp> */
    private ?array $entries = null;

    /**
     * Help text by preference name, built rather than declared so that `xgettext` sees every `T_()` literal
     *
     * @return array<string, PreferenceHelp>
     */
    public function all(): array
    {
        return [
            'album_group' => new PreferenceHelp(T_('Treats a multi-disc release as one album instead of one row per disc. Turning it off makes browse, search and the artist page list album disks individually; API and Subsonic album lists keep their own shape either way.')),
            'album_release_type' => new PreferenceHelp(T_('Splits the album list on an artist page into a section per release type, so albums, EPs, live records and singles sit under their own headings. Off, the artist page shows one flat list.')),
            'album_release_type_sort' => new PreferenceHelp(T_('Order of the release-type sections on an artist page, as a comma-separated list of types. Types left out of the list follow the ones named, and the whole setting is idle while release types are not grouped.')),
            'album_sort' => new PreferenceHelp(T_('Default ordering of album lists when the browse carries no sort of its own. It also decides the order of the album lists returned by the API.'), 'https://ampache.org/api/'),
            'allow_personal_info_now' => new PreferenceHelp(T_('Show what you are listening to right now to other users. Administrators see the now playing list in full whatever this says.')),
            'allow_personal_info_recent' => new PreferenceHelp(T_('Show what you listened to recently to other users. With it off your activity feed is hidden and your plays drop out of everyone else statistics pages.')),
            'allow_personal_info_time' => new PreferenceHelp(T_('Show the date and time of your recent plays. With it off your profile reports the last seen time as Never rather than leaving the field out.')),
            'browser_notify' => new PreferenceHelp(T_('Raise a desktop notification on every track change. It only works in the embedded player, and the browser still has to grant the notification permission.'), 'https://ampache.org/docs/information/web-player/'),
            'browser_notify_timeout' => new PreferenceHelp(T_('How long a track-change notification stays on screen, in seconds. 0 leaves it up until it is dismissed, and the setting is idle while browser notifications are off.'), 'https://ampache.org/docs/information/web-player/'),
            'custom_blankalbum' => new PreferenceHelp(T_('Image served for an album with no artwork, as an absolute http or https url. Browse pages still draw a placeholder when that is enabled, but everything else — the API, Subsonic, RSS feeds, the web player — serves this image instead.'), 'https://ampache.org/docs/themes/'),
            'custom_datetime' => new PreferenceHelp(T_('An ICU date pattern applied to every date and time on the site in place of the one the language implies. Leave it empty to follow the language.')),
            'custom_logo_user' => new PreferenceHelp(T_('Puts your own avatar in the header instead of the site logo. An account with no avatar gets the generic silhouette, not the site logo.'), 'https://ampache.org/docs/themes/'),
            'custom_timezone' => new PreferenceHelp(T_('Timezone used to display dates, overriding the one PHP is configured with. A name PHP does not recognize is silently cleared when the form is saved.')),
            'direct_play_limit' => new PreferenceHelp(T_('Hides the one-click play and add buttons on an album, artist or folder holding more media than this, so a huge item is not queued by accident. 0 removes the limit.'), 'https://ampache.org/docs/information/web-player/'),
            'generated_art' => new PreferenceHelp(T_('Draws a placeholder tile from the item name and artist when no cover exists, instead of one shared blank image. Nothing is stored: the drawing is rebuilt on each request. Setting a custom blank album image turns it off everywhere except the browse pages.'), 'https://ampache.org/docs/themes/'),
            'generated_art_template' => new PreferenceHelp(T_('Which drawing style the placeholder tiles use. Auto follows the theme color, and a template that no longer exists falls back to the first one available.'), 'https://ampache.org/docs/themes/'),
            'hide_single_artist' => new PreferenceHelp(T_('Drops the per-song artist column on an album whose songs all share one artist. Albums crediting several artists keep the column whatever this says.')),
            'home_moment_videos' => new PreferenceHelp(T_('Shows the Videos of the Moment panel on the home page. It stays hidden while video features are disabled, whatever this is set to.')),
            'home_recently_played_all' => new PreferenceHelp(T_('Lets Recently Played list podcast episodes, videos and radio alongside songs instead of songs alone. It governs your profile page and the statistics page too, not only the home panel.')),
            'index_dashboard_form' => new PreferenceHelp(T_('Replaces the browse form at the top of the home page with the dashboard widget picker.')),
            'libitem_browse_alpha' => new PreferenceHelp(T_('Comma-separated list of item types that open on the A to Z jump bar rather than a plain list. Empty means no type does.')),
            'mini_player' => new PreferenceHelp(T_('Sends this account to the mini player on login and on every page it would otherwise render. It restricts navigation only and is no substitute for an access level.'), 'https://ampache.org/docs/information/web-player/'),
            'now_playing_per_user' => new PreferenceHelp(T_('Collapses the now playing list to the most recent stream per user instead of one line per concurrent stream.')),
            'of_the_moment' => new PreferenceHelp(T_('How many items the of the Moment panels pick at random. A value of zero or less falls back to six.')),
            'offset_limit' => new PreferenceHelp(T_('How many items one page of a browse shows before paging. It is also the size of a random selection.')),
            'popular_threshold' => new PreferenceHelp(T_('How many rows the popular, recently played and last shouts lists return.')),
            'show_license' => new PreferenceHelp(T_('Adds the license column to song lists. It shows nothing unless licensing is also enabled server-side.')),
            'show_lyrics' => new PreferenceHelp(T_('Adds a lyrics link to the web player control bar. The lyrics page itself stays reachable either way.'), 'https://ampache.org/docs/information/web-player/'),
            'show_original_year' => new PreferenceHelp(T_('Appends the original release year to an album title when the record carries one and it differs from the pressing year.')),
            'show_played_times' => new PreferenceHelp(T_('Adds a play count column to browse rows. While it is off the counts are not even queried, so turning it on costs one extra statistics lookup per page.')),
            'show_playlist_username' => new PreferenceHelp(T_('Appends the owner name to a playlist title. Your own playlists are never annotated.')),
            'show_skipped_times' => new PreferenceHelp(T_('Adds a skip count column to browse rows. Like the play count, the figure is only queried when the column is shown.')),
            'show_subtitle' => new PreferenceHelp(T_('Appends the album version, such as a remaster or deluxe tag, to the album title when the record carries one.')),
            'sidebar_hide_switcher' => new PreferenceHelp(T_('Removes the arrows that fold the sidebar into its narrow form. Together with the light sidebar it pins the sidebar to that form.')),
            'sidebar_light' => new PreferenceHelp(T_('Opens the sidebar in its narrow, icon-only form by default.')),
            'sidebar_order_browse' => new PreferenceHelp(T_('Position of the Browse menu in the sidebar. Sections are stacked in ascending order, so a lower number moves it up.')),
            'sidebar_order_dashboard' => new PreferenceHelp(T_('Position of the Dashboard menu in the sidebar. Sections are stacked in ascending order, so a lower number moves it up.')),
            'sidebar_order_information' => new PreferenceHelp(T_('Position of the Information menu in the sidebar. Sections are stacked in ascending order, so a lower number moves it up.')),
            'sidebar_order_playlist' => new PreferenceHelp(T_('Position of the Playlist menu in the sidebar. Sections are stacked in ascending order, so a lower number moves it up.')),
            'sidebar_order_search' => new PreferenceHelp(T_('Position of the Search menu in the sidebar. Sections are stacked in ascending order, so a lower number moves it up.')),
            'slideshow_time' => new PreferenceHelp(T_('Seconds of inactivity before the web player fades into a full-screen artist slideshow. 0 turns it off, and it needs the Flickr plugin key to have anything to show.'), 'https://ampache.org/docs/plugins/'),
            'song_page_title' => new PreferenceHelp(T_('Puts the playing track in the browser tab title. Share pages never do it, whatever this says.'), 'https://ampache.org/docs/information/web-player/'),
            'stats_threshold' => new PreferenceHelp(T_('How many days back the statistics pages look. 0 means all time, which is the case the cached counts are built for.')),
            'theme_color' => new PreferenceHelp(T_('Light or dark variant of the chosen theme. It also picks the logo and icon set, and the template the drawn placeholder art uses under Auto.'), 'https://ampache.org/docs/themes/'),
            'topmenu' => new PreferenceHelp(T_('Moves the main navigation into a bar across the top of the page instead of the sidebar layout.'), 'https://ampache.org/docs/themes/'),
            'ui_fixed' => new PreferenceHelp(T_('Pins the header in place so it stays visible while the page scrolls. Only themes built for it react.'), 'https://ampache.org/docs/themes/'),
            'use_original_year' => new PreferenceHelp(T_('Sorts and reports albums by their original release year rather than the year of the pressing. An album with no original year falls back to the normal one.')),
            'webplayer_confirmclose' => new PreferenceHelp(T_('Asks the browser to confirm before leaving the page while something is playing. Only the embedded player does it, not a share page.'), 'https://ampache.org/docs/information/web-player/'),
            'webplayer_pausetabs' => new PreferenceHelp(T_('Pauses this tab when another tab starts playing, so two players do not talk over each other. Tabs agree through browser storage, so it only covers the same browser profile.'), 'https://ampache.org/docs/information/web-player/'),
            'allow_democratic_playback' => new PreferenceHelp(T_('Offers democratic play, where listeners vote on what comes next, as a playback type. It governs the interface: the democratic API methods answer whether or not it is set.'), 'https://ampache.org/docs/configuration/democratic/'),
            'allow_localplay_playback' => new PreferenceHelp(T_('Offers Localplay, which drives a player running beside the server, as a playback type. Installing a Localplay plugin switches this on for every user.'), 'https://ampache.org/docs/configuration/localplay/'),
            'allow_stream_playback' => new PreferenceHelp(T_('Master switch for streaming. The server setting and the account preference both have to be on, and the Jellyfin endpoints check the pair as well.')),
            'allow_video' => new PreferenceHelp(T_('Exposes the video side of the library in menus, searches and graphs. An API client asking for video while it is off is refused outright, not merely shown nothing.')),
            'api_always_download' => new PreferenceHelp(T_('Marks every API stream as a download, so no play count or now playing entry is recorded for it. Clients can already ask for that per request; this forces it.'), 'https://ampache.org/api/'),
            'api_enable_3' => new PreferenceHelp(T_('Answer clients that negotiate API version 3. Turning it off does not refuse them: they are rolled forward to the next enabled version.'), 'https://ampache.org/api/'),
            'api_enable_4' => new PreferenceHelp(T_('Answer clients that negotiate API version 4. Turning it off does not refuse them: they are rolled forward to the next enabled version.'), 'https://ampache.org/api/'),
            'api_enable_5' => new PreferenceHelp(T_('Answer clients that negotiate API version 5. Turning it off does not refuse them: they are rolled forward to the next enabled version.'), 'https://ampache.org/api/'),
            'api_enable_6' => new PreferenceHelp(T_('Answer clients that negotiate API version 6. Turning it off does not refuse them: they are rolled forward to the next enabled version.'), 'https://ampache.org/api/'),
            'api_enable_8' => new PreferenceHelp(T_('Answer clients that negotiate API version 8. It is the newest version, so there is nothing to roll forward to and a client asking for it is refused outright when this is off.'), 'https://ampache.org/api/'),
            'api_force_version' => new PreferenceHelp(T_('Answer every API client with this version regardless of what it asked for. 0 keeps the negotiated version, and a forced version is ignored unless that version is also enabled above.'), 'https://ampache.org/api/'),
            'api_hidden_playlists' => new PreferenceHelp(T_('Playlists whose name starts with this string are left out of API and Subsonic listings. The match is on the prefix only, and an empty value hides nothing.'), 'https://ampache.org/api/'),
            'api_hide_dupe_searches' => new PreferenceHelp(T_('Leaves a smartlist out of API and Subsonic listings when a normal playlist of the same name and owner exists, so a client does not show the pair twice. Some methods let a client ask for the duplicates anyway.'), 'https://ampache.org/api/'),
            'bookmark_latest' => new PreferenceHelp(T_('Keep one bookmark per item instead of a history: a new position replaces the previous one for the same item. With it off bookmarks pile up.')),
            'download' => new PreferenceHelp(T_('Lets users fetch the original file instead of streaming it. Zip batch downloads ride on it too, so turning it off takes them with it; the API and Subsonic backends have their own download settings.')),
            'geolocation' => new PreferenceHelp(T_('Lets the browser send the listener position with a play so statistics can be mapped. Both ends check it: the page does not ask the browser, and the server refuses a position sent anyway.'), 'https://ampache.org/docs/plugins/'),
            'localplay_controller' => new PreferenceHelp(T_('Which Localplay backend is driven, such as mpd or upnp. With none chosen the Localplay actions and the Subsonic jukebox role have nothing to talk to.'), 'https://ampache.org/docs/configuration/localplay/'),
            'localplay_level' => new PreferenceHelp(T_('Lowest access level allowed to control Localplay. An unset value fails closed at administrator rather than open.'), 'https://ampache.org/docs/configuration/localplay/'),
            'notify_email' => new PreferenceHelp(T_('Send an email when a private message arrives. Nothing goes out unless the account also carries an address and the server has mail enabled.')),
            'share' => new PreferenceHelp(T_('Lets users publish a link to an item for people without an account. Turning it off also kills links already handed out, because every visit rechecks the setting.')),
            'subsonic_always_download' => new PreferenceHelp(T_('Marks every Subsonic stream as a download, so no play count or now playing entry is recorded for it. A client that scrobbles on its own still registers plays.'), 'https://ampache.org/docs/configuration/subsonic/'),
            'subsonic_force_album_artist' => new PreferenceHelp(T_('Lists only album artists in the Subsonic artist index instead of everyone credited on a track. Useful for clients that drown in featured artists.'), 'https://ampache.org/docs/configuration/subsonic/'),
            'subsonic_legacy' => new PreferenceHelp(T_('Answer with the original Subsonic API instead of the OpenSubsonic one. Older clients cope better, but every OpenSubsonic-only method stops answering. API key authentication keeps working either way.'), 'https://ampache.org/docs/configuration/subsonic/'),
            'subsonic_single_user_data' => new PreferenceHelp(T_('Report only your own ratings and favorites to Subsonic clients. With it off, starred lists mix in what other users rated.'), 'https://ampache.org/docs/configuration/subsonic/'),
            'upload_catalog' => new PreferenceHelp(T_('Which catalog receives uploaded files. Uploads stay disabled while this is unset, whatever the upload switch says.'), 'https://ampache.org/docs/installation/catalog/'),
            'demo_clear_sessions' => new PreferenceHelp(T_('Drop votes cast by users who are no longer connected. The sweep runs each time the democratic playlist is read, not on a schedule.'), 'https://ampache.org/docs/configuration/democratic/'),
            'extended_playlist_links' => new PreferenceHelp(T_('Adds the parent album or podcast next to a title in playlist rows, which helps when a playlist mixes sources. Rows with no parent are unchanged.')),
            'playlist_method' => new PreferenceHelp(T_('What adding to the playlist does to what is already there: append at the end, insert after the current track, or replace it.')),
            'playlist_type' => new PreferenceHelp(T_('File format handed out when the basket is sent as a playlist: m3u, pls, xspf and so on. It is what an external player receives, not what the web player uses.')),
            'unique_playlist' => new PreferenceHelp(T_('Refuse to add an item a playlist already contains. API and Subsonic clients can still ask for the check to be skipped on a given call.')),
            'lastfm_grant_link' => new PreferenceHelp(T_('Authorization link for Last.fm, not a field to fill in. Follow it once to let Ampache scrobble; the session token lands in the account by itself.'), 'https://ampache.org/docs/plugins/'),
            'musicbrainz_server' => new PreferenceHelp(T_('Base address used for MusicBrainz lookups. Leave it empty for the public service, or point it at your own mirror.'), 'https://ampache.org/docs/plugins/'),
            'musicbrainz_throttle' => new PreferenceHelp(T_('Pause between two MusicBrainz calls, in hundredths of a second. The public service expects one; 0 removes the wait and only makes sense against your own mirror.'), 'https://ampache.org/docs/plugins/'),
            'broadcast_by_default' => new PreferenceHelp(T_('Starts the web player with broadcasting already armed, so what you play is offered to listeners without another click.'), 'https://ampache.org/docs/information/web-player/'),
            'encode_player_api_target' => new PreferenceHelp(T_('Output format forced on API streams, overriding the audio default. Setting it also marks transcoding as allowed for API clients.'), 'https://ampache.org/docs/configuration/transcoding/'),
            'encode_player_webplayer_target' => new PreferenceHelp(T_('Output format forced on web player streams, overriding the audio default. Setting it also marks transcoding as allowed for the web player.'), 'https://ampache.org/docs/configuration/transcoding/'),
            'encode_target' => new PreferenceHelp(T_('Format audio is re-encoded to when transcoding happens. A file already in that format is sent as it is rather than re-encoded to itself.'), 'https://ampache.org/docs/configuration/transcoding/'),
            'encode_video_target' => new PreferenceHelp(T_('Format video is re-encoded to when transcoding happens. As with audio, a file already in that format is passed through untouched.'), 'https://ampache.org/docs/configuration/transcoding/'),
            'jp_volume' => new PreferenceHelp(T_('Volume the web player starts at, between 0 and 1.'), 'https://ampache.org/docs/information/web-player/'),
            'max_bit_rate' => new PreferenceHelp(T_('Ceiling shared between the streams currently being downsampled: each one gets the ceiling divided by their number. 0 turns the sharing off, and only listeners whose playback type is downsample are counted.'), 'https://ampache.org/docs/configuration/transcoding/'),
            'min_bit_rate' => new PreferenceHelp(T_('Floor for a shared-bandwidth stream. A stream that would be pushed under it is refused with a server busy error instead of being sent at an unusable quality.'), 'https://ampache.org/docs/configuration/transcoding/'),
            'play_type' => new PreferenceHelp(T_('How playback is delivered: the web player, a plain stream handed to an external player, downsampled streaming, Localplay or democratic. Only downsample listeners count toward the shared bandwidth ceiling.'), 'https://ampache.org/docs/configuration/'),
            'rate_limit' => new PreferenceHelp(T_('Upper speed limit for a single download, in kilobytes per second. 0 removes the limit, and it throttles the bytes on the wire rather than the encoding bitrate.'), 'https://ampache.org/docs/configuration/transcoding/'),
            'transcode' => new PreferenceHelp(T_('Whether Ampache may re-encode a file before sending it. Default only transcodes formats the player cannot handle, never always sends the original, always re-encodes everything; never also blocks waveform generation, which reuses the same pipeline.'), 'https://ampache.org/docs/configuration/transcoding/'),
            'transcode_bitrate' => new PreferenceHelp(T_('Target bitrate for re-encoded streams, in bits per second. It is still capped per output format, and the API and web player can override it with their own value.'), 'https://ampache.org/docs/configuration/transcoding/'),
            'transcode_bitrate_api' => new PreferenceHelp(T_('Bitrate used for API streams in place of the default. 0 means no override, not an unlimited rate.'), 'https://ampache.org/docs/configuration/transcoding/'),
            'transcode_bitrate_webplayer' => new PreferenceHelp(T_('Bitrate used for web player streams in place of the default. 0 means no override, not an unlimited rate.'), 'https://ampache.org/docs/configuration/transcoding/'),
            'webplayer_removeplayed' => new PreferenceHelp(T_('How many already played tracks the web player keeps above the current one before trimming them. Disabled keeps the whole history, and Remove all previous tracks keeps none.'), 'https://ampache.org/docs/information/web-player/'),
            'allow_upload' => new PreferenceHelp(T_('Lets users add files to the library themselves. Nothing can be uploaded until a destination catalog is chosen as well, and the account still has to reach the upload access level.'), 'https://ampache.org/docs/installation/catalog/'),
            'autoupdate' => new PreferenceHelp(T_('Poll GitHub for a newer Ampache release and flag it in the header. Turning it off also freezes the stored last check time, so nothing is polled again until it comes back on.')),
            'catalog_check_duplicate' => new PreferenceHelp(T_('At import, compare an incoming file against the library and insert a match as disabled instead of active. It only applies to music catalogs, and the row is still created.'), 'https://ampache.org/docs/installation/catalog/'),
            'cron_cache' => new PreferenceHelp(T_('Read play counts and statistics from a table built ahead of time instead of counting rows on every page. It needs the cron task to actually run; without it the figures freeze where the last run left them.'), 'https://ampache.org/docs/installation/catalog/'),
            'cron_cache_live_count' => new PreferenceHelp(T_('Adds the plays recorded since the cached counts were built on top of them, so all time figures are not a run behind. It does nothing unless the cached counts are in use.'), 'https://ampache.org/docs/installation/catalog/'),
            'daap_backend' => new PreferenceHelp(T_('Serve the DAAP protocol, which iTunes and compatible players browse. The endpoint answers Disabled outright while this is off.'), 'https://ampache.org/docs/configuration/api/'),
            'daap_pass' => new PreferenceHelp(T_('Single password guarding the DAAP endpoint. Left empty, DAAP asks for nothing at all; it is one shared secret rather than per-account credentials.'), 'https://ampache.org/docs/configuration/api/'),
            'demo_use_search' => new PreferenceHelp(T_('Feed the democratic playlist from a saved search instead of a normal playlist.'), 'https://ampache.org/docs/configuration/democratic/'),
            'disabled_custom_metadata_fields' => new PreferenceHelp(T_('Custom tag fields kept out of the edit form and out of what the catalog writes back. It is merged with the free-text list of extra fields below.')),
            'disabled_custom_metadata_fields_input' => new PreferenceHelp(T_('Extra field names to disable on top of the ones picked from the list, comma separated. Use it for fields the list does not offer.')),
            'force_http_play' => new PreferenceHelp(T_('Serve stream URLs over plain HTTP even when the site runs on HTTPS. Only useful for players that cannot do TLS.')),
            'jellyfin_backend_enable' => new PreferenceHelp(T_('Serve the Jellyfin API. Experimental.'), 'https://ampache.org/docs/configuration/api/'),
            'lock_songs' => new PreferenceHelp(T_('Refuse to start a track that is already in the now playing list, whoever is listening. A stale row left by a player that never closed its connection then blocks that track until it is cleaned up.')),
            'perpetual_api_session' => new PreferenceHelp(T_('Let API sessions live until they are revoked instead of expiring. Convenient for always-on clients, but a leaked token then stays valid until the session cache is cleared by hand.'), 'https://ampache.org/docs/configuration/api/'),
            'podcast_keep' => new PreferenceHelp(T_('Episodes past this many newest ones are deleted from a podcast once a sync has downloaded something. 0 keeps every episode.')),
            'podcast_new_download' => new PreferenceHelp(T_('How many freshly published episodes a sync downloads. 0 means all of them and -1 means none, so 0 is not the way to turn downloads off.')),
            'quickconnect_enable' => new PreferenceHelp(T_('Let a device pair by showing a short code instead of asking for a password.'), 'https://ampache.org/docs/configuration/api/'),
            'share_expire' => new PreferenceHelp(T_('Days a new share link stays valid, used when the person creating it picks no date. 0 creates links that never expire.')),
            'stream_beautiful_url' => new PreferenceHelp(T_('Write stream and art URLs as paths instead of query strings, which needs the rewrite rules in the web server. API responses always use plain query strings regardless, and the UPnP backend always uses the rewritten form.'), 'https://ampache.org/docs/configuration/'),
            'subsonic_backend' => new PreferenceHelp(T_('Serve the Subsonic API, used by many third-party players.'), 'https://ampache.org/api/subsonic'),
            'upload_access_level' => new PreferenceHelp(T_('Lowest access level allowed to upload. It is checked on top of the upload switch, so raising it locks users out without turning uploads off.'), 'https://ampache.org/docs/installation/catalog/'),
            'upload_allow_edit' => new PreferenceHelp(T_('Lets whoever uploaded a file edit its tags without being a content manager. They still cannot re-file it: the artist, album and owner fields are stripped from what they submit.'), 'https://ampache.org/docs/installation/catalog/'),
            'upload_allow_remove' => new PreferenceHelp(T_('Lets whoever uploaded a file delete it from disk without being a manager. Deleting from disk has to be enabled server-side as well.'), 'https://ampache.org/docs/installation/catalog/'),
            'upload_catalog_pattern' => new PreferenceHelp(T_('Move and rename an uploaded file to where the catalog pattern says it belongs, instead of leaving it where it landed.'), 'https://ampache.org/docs/installation/catalog/'),
            'upload_script' => new PreferenceHelp(T_('Command run in the upload directory once a file has arrived, with %FILE% replaced by its path. It stays dead until upload scripts are allowed in the configuration file.'), 'https://ampache.org/docs/configuration/'),
            'upload_subdir' => new PreferenceHelp(T_('Give every uploader a folder of their own under the catalog. An account whose username does not resolve cannot upload at all.'), 'https://ampache.org/docs/installation/catalog/'),
            'upload_user_artist' => new PreferenceHelp(T_('Drops the artist section from the uploads listing, for a server where the uploader is the artist. It changes that listing only, not how a track is credited.'), 'https://ampache.org/docs/installation/catalog/'),
            'upnp_backend' => new PreferenceHelp(T_('Serve UPnP and DLNA so devices on the network find the library on their own.'), 'https://ampache.org/docs/configuration/api/'),
            'webdav_backend' => new PreferenceHelp(T_('Expose the library over WebDAV, so it can be mounted as a network drive.'), 'https://ampache.org/docs/configuration/api/'),
        ];
    }

    public function find(string $name): ?PreferenceHelp
    {
        $this->entries ??= $this->all();

        return $this->entries[$name] ?? null;
    }
}
