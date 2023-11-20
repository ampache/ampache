# Ampache Plugins

Plugins are placed in modules/plugins; the name of the file must be "./Name/Name.plugin.php". e.g. Dummy/Dummy.plugin.php.
The file must declare a corresponding class and the name of the class must be prefixed with Ampache, e.g. AmpacheDummy.

Copying an existing plugin is the easiest way to write a new one.

There is an empty example template available [here.](https://github.com/ampache/ampache/blob/patch6/docs/examples/AmpacheExample.php)

This should let you expand and develop your own plugin quickly.

## Minimum Plugin Requirements

The following public variables must be declared:

* name (string)
* description (string)
* version (int) - This plugin's version
* min_ampache (int) - Minimum Ampache DB version required
* max_ampache (int) - Maximum Ampache DB version supported

The following public methods must be implemented:

* install
* uninstall
* load

The following public methods may be implemented:

* upgrade

## Possible Plugin methods

Finally, for the plugin to actually be useful one or more of the following hooks should be implemented as a public method:

* display_home(): void
* display_on_footer(): void
* display_user_field(library_item $libitem = null): void
* display_map(array $points): bool
* external_share(string $public_url, string $share_name): string
* gather_arts(string $type, array $options, int $limit): array
* get_avatar_url(User $user): string
* get_lyrics(Song $song): array|false
* get_location_name(float $latitude float $longitude): string
* get_metadata(array $gather_types, array $media_info): array
* get_photos(string $search_name): array
* get_song_preview(string $track_mbid, string $artist_name, string $title): array
* stream_song_preview(string $file): void
* process_wanted(Wanted $wanted): bool
* save_mediaplay(Song $song): bool
* save_rating(Rating $rating, int $new_value): void
* set_flag(Song $song, boolean $flagged): void
* shortener(string $url): string|false
* stream_control(array $object_ids): bool
* stream_song_preview(string $file): bool

