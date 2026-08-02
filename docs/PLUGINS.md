# Ampache Plugins

Plugins are single PHP classes in `src/Plugin`, one file per plugin, in the `Ampache\Plugin`
namespace. The class name must be prefixed with `Ampache`, e.g. `AmpacheDummy` in
`src/Plugin/AmpacheDummy.php`.

Plugins are **not** discovered by scanning a folder. Every plugin has to be registered in
`PluginEnum::LIST` (`src/Plugin/PluginEnum.php`), keyed by the lowercase name it is stored
under in the database:

```php
public const array LIST = [
    // ...
    'dummy' => AmpacheDummy::class,
];
```

Copying an existing plugin is the easiest way to write a new one.

There is an empty example template available [here.](https://github.com/ampache/ampache/blob/develop/docs/examples/AmpacheExample.php)

This should let you expand and develop your own plugin quickly.

## Minimum Plugin Requirements

Extend `AmpachePlugin` (which implements `AmpachePluginInterface`) and declare the following
public properties:

* name (string)
* description (string)
* categories (string) - Groups the plugin's preferences in the interface
* url (string)
* version (string) - This plugin's version
* min_ampache (string) - Minimum Ampache DB version required
* max_ampache (string) - Maximum Ampache DB version supported

The following public methods must be implemented:

* install
* load
* uninstall
* upgrade

## Possible Plugin methods

Finally, for the plugin to actually be useful one or more of the following hooks should be
implemented as a public method. Each hook is matched against a `PluginTypeEnum` case with
`method_exists()`, so the method name has to match exactly. Most hooks also have a matching
interface in `src/Plugin`; implement it so the call sites can type check the plugin.

| Method | Interface |
|---|---|
| `display_home(): void` | `PluginDisplayHomeInterface` |
| `display_map(array $points): bool` | `PluginLocationInterface` |
| `display_on_footer(): void` | `PluginDisplayOnFooterInterface` |
| `display_user_field(?library_item $libitem = null): void` | `PluginDisplayUserFieldInterface` |
| `external_share(string $url, string $text): string` | `PluginExternalShareInterface` |
| `gather_arts(string $type, ?array $options = [], ?int $limit = 5): array` | `PluginGatherArtsInterface` |
| `get_avatar_url(User $user, ?int $size = 80): string` | `PluginGetAvatarUrlInterface` |
| `get_external_metadata(library_item $object, string $object_type): bool` | none |
| `get_location_name(float $latitude, float $longitude): string` | `PluginLocationInterface` |
| `get_lyrics(Song $song): ?array` | `PluginGetLyricsInterface` |
| `get_metadata(array $gather_types, array $media_info): array` | `PluginGetMetadataInterface` |
| `get_photos(string $search, string $category = 'concert'): array` | none |
| `get_song_preview(string $track_mbid, string $artist_name, string $title): list<SongPreviewResult>` | `PluginSongPreviewInterface` |
| `process_wanted(Wanted $wanted): bool` | `PluginProcessWantedInterface` |
| `save_mediaplay(Song $song): bool` | `PluginSaveMediaplayInterface` |
| `save_rating(Rating $rating, int $new_rating): void` | none |
| `set_flag(Song $song, bool $flagged): void` | `PluginSaveMediaplayInterface` |
| `shortener(string $url): ?string` | `PluginShortenerInterface` |
| `stream_control(array $media_ids): bool` | `PluginStreamControlInterface` |

## Song preview providers

A preview provider finds a short sample for a track on the wanted list. `AmpacheItunes` and
`AmpacheDeezer` are the bundled examples; copy either one to add another.

* No provider indexes MusicBrainz ids, so the track is looked up by artist and title text and the
  mbid is only used for logging.
* Return every candidate as a `SongPreviewResult` (`file`, `title`, `artist`) and hand the list to
  `SongPreviewResult::rank()`, which drops anything that isn't close enough to what was asked for
  and sorts the rest best match first. The caller takes the first entry, so a bad match is worse
  than no result at all.
* `file` is the provider's own url. Ampache stores it and `Song_Preview::stream()` answers with a
  `303` to it, so the client fetches the sample directly and no preview traffic — or provider
  credential — passes through Ampache. A plugin never streams the audio itself.

