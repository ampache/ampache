# API CHANGELOG

## API 6.6.3

**NO CHANGE**

## API 6.6.2

### Added

* API6
  * Add `stats` parameter to stream and download methods (If false disable stat recording when playing the object)
  * Respect `api_always_download` in stream and download methods
  * Add sorting to stats calls
  * add `user` object to playlist responses (owner of the playlist)

### Fixed

* ALL
  * index: Artist index not showing albums

## API 6.6.1

This release keeps parity between Ampache7 releases by backporting the updated code.

### Added

* API6
  * Add maximum ID properties to `handshake` and `ping` (with auth) responses for media types
    * `max_song`, `max_album`, `max_artist`, `max_video`, `max_podcast`, `max_podcast_episode`
  * flag: add `date` as a parameter (set the time for your flag)

### Changed

* lost_password: deny access in simple_user_mode

## API 6.6.0

Like with `total_count`, we've added an md5sum of the results (called `md5`) in responses

This is useful for recording whether you need to update or change results.

Inconsistency with the return of object arrays and single items have been fixed and docs updated.

### Added

* ALL
  * Track user IP on handshake and ping calls
  * playlist_edit: separate error when the playlist doesn't exist
* API6
  * New Method: playlist_hash (Get the MD5 hash of the playlist without getting the whole object)
  * Add `md5` to responses. (Hash objects in the response before slicing and limiting)
  * Add `md5` property to playlist objects. (Hash of song objects in the response)
  * Add `username` property to handshake and ping (with auth) responses to workaround missing usernames in token auth
  * Add `has_access` property to playlist objects. (Can edit the playlist if true)
  * Add `has_collaborate` property to playlist objects. (Can add and remove songs to the playlist if true)
  * Add `last_update` property to playlist objects. (Time a playlist changed. Smartplaylists do not change based on returned songs)
  * Add `object_type` and `object_id` property to shout objects

### Changed

* API6
  * playlist_edit: Add songs if you're a collaborator and ignore edit parameters if you fail has_access check
  * catalog_add: Do not return an object. (We return a single item)
  * bookmark_create: Do not return an object. (We return a single item)

### Fixed

* ALL
  * User preferences were not initiated and the server preferences would overwrite differences
  * Api::set_user_id function sending an int instead of a user
  * Some responses that include users not checking the user is valid
* API6
  * JSON: Send empty array for missing single item methods
  * lost_password: function name incorrect
  * flag: id smartplaylists correctly
  * rate: id smartplaylists correctly
  * albums: Browse user may not be set
  * podcast_episodes: Browse user may not be set

## API 6.5.0

### Added

* API6
  * Add `songartists` to all album data responses. (In an album `artists`=album_artists, `songartists`=song_artists)
  * artist_albums: add `album_artist` as an optional parameter
  * get_indexes: add `catalog`, `album_artist` and `song_artist` as possible `type` values
  * list: add `catalog` and `song_artist` as possible `type` values
  * Add `cond` and `sort` parameters to browse methods
    * album_songs
    * albums
    * artist_albums
    * artist_songs
    * artists
    * browse
    * catalogs
    * followers
    * genre_albums
    * genre_artists
    * genre_songs
    * genres
    * get_indexes
    * index
    * label_artists
    * labels
    * license_songs
    * licenses
    * list
    * live_streams
    * playlists
    * podcasts
    * podcast_episodes
    * shares
    * songs
    * user_playlists
    * user_smartlists

### Changed

* Reset any existing browse when calling Api::getBrowse()
* Filter duplicate search names outside of the data classes and filter on browses
* API6 methods converted to Browse
  * artist_albums
  * artist_songs
  * browse (`catalog` types)
  * catalogs
  * followers
  * genre_albums
  * genre_artists
  * genre_songs
  * get_indexes (`catalog` and `playlist` types)
  * index (`catalog` and `playlist` types)
  * label_artists
  * license_songs
  * list (`playlist` types)
  * playlists
  * podcast_episodes
  * stats (random `playlist` types)
  * user_playlists
  * user_smartlists
* API5 methods converted to Browse
  * get_indexes (`playlist` types)
  * playlists
  * stats (random `playlist` types)
* API4 methods converted to Browse
  * get_indexes (`playlist` types)
  * playlists

### Fixed

* ALL
  * html_entity_decode `include`, `items` and `tracks` parameter for applicable methods
  * Rating and flag data for smartlists was using incorrect id
  * playlist_edit: track insert broken by removing table constraint
  * playlist_edit: workaround sending owner username instead of ID
  * playlist_add_song: When using `unique_playlist` don't grab the whole playlist
* API6
  * list: sorting was by `id` instead of `name`
  * browse: sorting was by `id` instead of `name`
  * download: The API can use searches as playlists so check for the `smart_` prefix
  * stream: The API can use searches as playlists so check for the `smart_` prefix
  * Respect album sort preferences in all album object responses

## API 6.4.0

### Added

* API6
  * Downgrade any API7 calls to API6 [wiki](https://github.com/ampache/ampache/wiki/ampache7-for-admins#there-is-no-api7-only-api6-and-5-4-and-3-too)
  * New Method: player (Inform the server about the state of your client player)
    * Returns `now_playing` state on completion
  * download: add `bitrate` parameter
  * playlists: add `include` parameter (**note** this can be massive and slow when searches are included)

### Changed

* API6
  * Do not translate API `errorMessage` strings [ampache.org](https://ampache.org/api/api-errors)

### Fixed

* ALL
  * Download method format parameter didn't have a fallback value
* API4
  * playlist: error check for missing/deleted playlists
  * playlist_songs: error check for missing/deleted playlists
* API6
  * Playlists objects would not return duplicates items if allowed
  * has_art property missing from songs and albums
  * playlist_add: couldn't add a single item

## API 6.3.1

### Added

* API6
  * New Method: now_playing (Get what is currently being played by all users.)

## API 6.3.0

### Added

* API6
  * New Method: search_group (return multiple object types from a single set of search rules)
  * New Method: search (alias for advanced_search)
  * New Method: user_playlists (return user playlists and does not include smartlists)
  * New Method: user_smartlists (return user smartlists (searches) and does not include playlists)
  * New Method: playlist_add (add songs to a playlist, allowing different song parent types)
  * New Method: index (replaces get_indexes with a simpler list of id's. children can be included)
  * Add `has_art` parameter to any object with an `art` url
  * Add avatar url to user objects

### Changed

* API6
  * playlist_add_song: depreciated and will be removed in **API7** (Use playlist_add)
  * share_create: add more valid types ('playlist', 'podcast', 'podcast_episode', 'video')
  * user: make username optional

### Fixed

* ALL
  * Userflag wasn't sending bool when cached in the database
  * Admin would always get everyones playlists when filtering
  * Stream methods would not send the bitrate correctly
* API4
  * playlists method not respecting like for smartlists
* API5
  * playlists method not respecting like for smartlists
* API6
  * playlists method not respecting like for smartlists
  * playlist_edit method will decode html `,` separators

## API 6.2.1

**NO CHANGE**

## API 6.2.0

### Added

* API: Allow non-expiring user sessions when using a header token
* Allow endless api sessions. (You should start using http header auth to hide these)

### Changed

* Set default API version to 6 (was 5)
* Allow raising and lowering response version on ping to **any** version
* API6
  * Return error on handshake version failure

### Fixed

* ALL
  * UrlToSong couldn't handle encoded urls
* API3
  * Video data would get an incorrect stream url
* API5
  * bookmark_create: type is mandatory
* API6
  * bookmark_create: type is mandatory

## API 6.1.0

Two new methods have been added

The bookmark methods have had a bit of a rework as they were not very useful

Finally the issues with setting your auth token in the http header have been fixed

### Added

* API6
  * New Method: bookmark (Get single bookmark by bookmark_id)
  * New Method: lost_password (Allows a non-admin user to reset their password)
  * bookmark_create: Add `include` parameter (if true include the object in the bookmark)
  * bookmark_edit: Add `include` parameter (if true include the object in the bookmark)
  * get_bookmark
    * Add `include` parameter (if true include the object in the bookmark)
    * Add `all` parameter (if true include every bookmark for the object)
  * bookmarks
    * Add parameter `client` to filter by specific groups of bookmarks
    * Add `include` parameter (if true include the object in the bookmark)

### Changed

* API5
  * bookmark_edit: show error on missing bookmark instead of empty object
  * bookmark_delete: show error on missing bookmark instead of empty object
* API6
  * get_bookmark
    * add bookmark as a valid `object_type`
    * Don't return single JSON bookmarks as an object
  * bookmark_create: Remove `client` parameter default value ('AmpacheAPI')
  * bookmark_edit
    * Remove `client` parameter default value ('AmpacheAPI')
    * show error on missing bookmark instead of empty object
    * add bookmark as a valid `object_type`
    * Don't return single JSON bookmarks as an object
  * bookmark_delete
    * Remove `client` parameter default value ('AmpacheAPI')
    * show error on missing bookmark instead of empty object
    * add bookmark as a valid `object_type`

### Fixed

* ALL
  * Some JSON methods with empty results would not show `total_count` in results
  * handshake: auth failure with header token
  * playlist_generate: Don't error when optional `mode` and `format` are not set
  * advanced_search: runtime error on empty data type
* API4
  * Fix lots of Runtime Error's on missing optional data
* API5
  * Fix lots of Runtime Error's on missing optional data
  * video: error type was song instead of filter
  * genre_artists, genre_albums, genre_songs: Parameter `filter` runtime errors
  * download: random search/playlist didn't use the `id` parameter
  * stream: random search/playlist didn't use the `id` parameter
  * bookmark_edit
    * Missing user id in data array
    * Not able to edit all bookmarks
* API6
  * Fix lots of Runtime Error's on missing optional data
  * video: error type was song instead of filter
  * catalog_folder didn't get the group of items correctly
  * genre_artists, genre_albums, genre_songs: Parameter `filter` runtime errors
  * download: random search/playlist didn't use the `id` parameter
  * stream: random search/playlist didn't use the `id` parameter
  * bookmark_edit
    * Missing user id in data array
    * Not able to edit all bookmarks

## API 6.0.3

### Added

* API5::playlist_songs: Add `random` to get random objects filtered by limit

### Fixed

* ALL
  * Fixed Bearer token auth on all methods
  * handshake: runtime errors with bad username
  * handshake: Don't error on empty data counts
  * ping: Don't error on empty data counts
* Api6
  * list: searches were missing from playlists
  * browse: XML returned a list instead of a browse object

## API 6.0.2

**NO CHANGE**

## API 6.0.1

### Changed

* API6 XML
  * get_similar: return song objects to match json

### Fixed

* API6
  * user_preference: returned array instead of object
  * system_preference: returned array instead of object
  * preference_create: returned array instead of object
  * preference_edit: returned array instead of object

## API 6.0.0

Stream token's will let you design permalinked streams and allow users to stream without re authenticating to the server. [wiki](https://github.com/ampache/ampache/wiki/ampache6-details#allow-permalink-user-streams)

### Added

* API5::playlist_songs: Add `random` to get random objects filtered by limit
* API6 (Based on API5)
  * Added podcast id and name to `podcast_episode` objects
  * API6::browse: List server contents in a directory-style listing (Music, Podcast and Video catalogs)
  * API6::list: Replace get_indexes with a faster lookup and similar parameters returning `id`, `name`, `prefix` and `basename`
  * API6::catalog_add: Create a catalog (Require: 75)
  * API6::catalog_delete: Delete a catalog (Require: 75)
  * API6::catalog_folder: Perform actions on local catalog folders. (catalog_file but for folders) (Require: 50)
  * API6::live_stream_create: Create a new live stream (radio station)
  * API6::live_stream_edit: Edit a live stream
  * API6::live_stream_delete: Delete a stream by ID
  * API6::register: Allow users to register an account (if enabled)
  * API6::playlist_create: Return an error if the playlist name already exists for that user
  * API6::playlist_songs: Add `random` to get random objects filtered by limit
  * API6::user_edit (previously user_create):
    * Add `group` parameter to pick a catalog filter group
    * Add `fullname_public` to enable/disable using fullname in public display
    * Add `reset_apikey` to reset a user Api Key
    * Add `reset_streamtoken` to reset a user Stream Token
    * Add `clear_stats` reset all stats for this user **be very sure about this one!**
  * Add `prefix` (Prefix for Full Name) to album & artist responses
  * Add `basename` (Name without prefix) to album & artist responses
  * Add `bitrate` to Democratic objects
  * Add `format` to Song and Democratic objects
  * Add `stream_format`, `stream_bitrate`, `stream_mime` to Song objects (This is the transcoded output for a stream)
  * Add all mapped artists to song and album objects (JSON added an `artists` element)
  * Add `bitrate`, `stream_bitrate`, `rate`, `mode`, `channels` to Podcast Episode objects
* JSON responses
  * Cast bool fields to `true` and `false` instead of "1" & "0"
  * Add `total_count` to responses to give clients an idea of the total possible objects
* advanced_search
  * Add `song_genre` to album and artist searches
  * Add `possible_duplicate_album` to song search
  * Add `mbid_artist` to album search
  * Add `barcode` to album search
  * Add `catalog_number` to album search
  * Add `smartplaylist` to album search
  * Add `duplicate_tracks` to album and song search (MIN & MAX id for song search)
  * Alias `possible_duplicate_album` => `possible_duplicate` for album search
  * Alias `album_genre` => `genre` for album search
  * Alias `mbid_album` => `mbid` for album search
  * Alias `mbid_artist` => `mbid` for artist search
  * Alias `song_genre` => `genre` for song search

### Changed

* Api6
  * Renamed `user_update` to `user_edit` (user_update still works and will be removed in **API7**)
* Api5
  * Add backwards compatible `user_edit` method to point to `user_update`
* ALL
  * Add all possible plugin preferences to the system list so they can't be deleted
  * Albums with no album_artist may now return 0 artist called 'Various'
  * Don't send AlbumDisk objects to the API
  * Send the authenticated user to all method calls
* XML responses
  * Api6 XML success and error response messages are put in a `message` element (like json)
  * For data responses id is the only attribute and everything else is an element
  * Name was not set as an attribute OR an element so now it's always an element
  * Return original XML output (that may be malformed) when loadxml fails.
* Api6::get_indexes: This method is depreciated and will be removed in Ampache **API7** (Use list instead)

### Removed

* Api6
  * `preciserating` removed from all objects (use rating)
  * Remove non-song MBIDs as not relevant to the object
  * album_songs remove `exact` as a parameter
  * stream remove `podcast` as a valid `type` value
* preference_create: don't allow creating 'system' preferences
* Warning of depreciated methods from API5 have been removed from API6
  * Api6::tag
  * Api6::tags
  * Api6::tag_albums
  * Api6::tag_artists
  * Api6::tag_songs

### Fixed

* ALL
  * advanced_search methods were breaking with various offset and limits
* API4
  * share_create: null `expires` fall back to `share_expire` or 7 days
* API5
  * share_create: null `expires` fall back to `share_expire` or 7 days
  * preference_edit: Could apply to the wrong user
* Api6 JSON
  * Share and Bookmark object id's were not strings
* Api3
  * Never send 0 ratings. They should always be null (e.g. `<rating/>`)
  * Artists method parameters were incorrect

## API 5.6.2

### Fixed

* ALL
  * Require and set a valid version for `api_force_version`

## API 5.6.1

**NO CHANGE**

## API 5.6.0

### Fixed

* ALL
  * share_create and share_edit methods broken when setting expiry days
  * advanced_search methods were breaking with various offset and limits
  * playlists methods parameter 'exact' always ending up false
* Api5
  * update_art hardcoded url to artist
  * Typo in song bitrate xml

## API 5.5.7

### Changed

* Keep the original mime and bitrate on song objects instead of the transcoded value

## API 5.5.6

Fix various runtime errors and incorrect parameters for responses.

### Changed

* API browses all point to the Api class
* Use `FILTER_VALIDATE_IP` on ping calls

### Fixed

* Api5
  * `songs` set_filter call without browse parameter may have lost info
  * `get_indexes` set album_artist filter correctly
  * `artists` set album_artist filter correctly
  * `share_create` undefined filter check
* Api4
  * `songs` set_filter call without browse parameter may have lost info
  * `get_indexes` set album_artist filter correctly
  * `timeline` incorrect JSON attribute `data` instead of `date`
  * `catalogs` JSON had incorrect data for `last_add` and missing `enabled`
  * `albums` return an empty response with a bad artist id
  * `download` url parameter order matching "client, action, cache"
  * `catalogs` undefined filter check
  * `podcast` undefined filter check
  * `podcast_edit` undefined filter check
  * `podcasts` undefined filter check
  * `share_create` undefined filter check
  * `share_edit` undefined filter check
* Api3
  * `album_songs` return an empty response with a bad album id
  * `artist_albums` return an empty response with a bad artist id
  * Calls to `songs` with user ID instead of user object

## API 5.5.5

**NO CHANGE**

## API 5.5.4

### Fixed

* User count in Api::ping and Api::handshake was doubled
* Api3::stats method had incorrect recent parameters
* Ensure the output `bitrate` and `mime` are set for song objects

## API 5.5.3

**NO CHANGE**

## API 5.5.2

### Added

* advanced_search
  * Add `song_artist` as a search type (uses artist rules)
  * Add `album_artist` as a search type (uses artist rules)
  * Add `song_genre`, `mbid_artist`, `mbid_song` to album search
  * Add `song_genre`, `mbid_album`, `mbid_song` to artist search
  * Add `possible_duplicate_album` to song search

### Fixed

* advanced_search
  * unable to retrieve song_artist or album_artist results

## API 5.5.1

**NO CHANGE**

## API 5.5.0

This will likely be the last 5.x API release. API6 will be a continuation of API5 and not be a significant change like the 4->5 transition.

### Added

* Api::stream add new types `playlist` and `search` (Streams a random object from these lists)
* Api::download add new types `playlist` and `search`
* advanced_search
  * Add `podcast` as a search type
    * Add rule `title`
    * Add rule `podcast_episode` (Search by podcast episode name)
    * Add rule `time` (Episode length in minutes)
    * Add rule `state` (Completed, Pending Skipped)
    * Add rule `file`
    * Add rule `added`
    * Add rule `pubdate` (Episode Publication Date)
  * Add `podcast_episode` as a search type
    * Add rule `title`
    * Add rule `podcast` (Search by podcast name)
    * Add rule `time` (Length in minutes)
    * Add rule `state` (Completed, Pending Skipped)
    * Add rule `file`
    * Add rule `added`
    * Add rule `pubdate` (Publication Date)
  * Add `genre` as a search type
    * Add rule `title`

### Fixed

* API4::get_indexes podcast_episode was encoding into the API5 object
* API4::share_create was unable to share when using lowercase types
* advanced_search
  * Added missing `song` (was `song_title`) to album searches

## API 5.4.1

### Added

* Include `lyrics` in Song objects
* advanced_search
  * Add `file` to album and artist search
  * Add `track` to song search
  * Add `summary` to artist search

## API 5.4.0

### Added

* advanced_search
  * Add `file` to album and artist search

## API 5.3.3

### Added

* advanced_search
  * Add `song_title` to album search
  * Add `album_title` and `song_title` to artist search
  * Add `orphaned_album` to song search

### Fixed

* Api4::record_play had the `user` as mandatory again
* After catalog actions; verify songs with an orphaned album which you won't be able to find in the ui

## API 5.3.2

**NO CHANGE**

## API 5.3.1

**NO CHANGE**

## API 5.3.0

### Added

* advanced_search:
  * Add `songrating` to album search (My Rating (Song))
  * Add `songrating` (My Rating (Song)) and `albumrating` (My Rating (Album)) to artist search
  * Allow empty/null searches for all mbid searches
  * Allow empty/null searches for label searches
  * Add `song_count` to album and artist search
  * Add `album_count` to artist search
  * Add `myplayedartist` (Played by Me (Artist)) to album search
  * Add `song_artist` to album search
  * Add alias `album_artist` to album search for `artist` searches
  * Add `recent_added` to album search

## API 5.2.1

### Added

* API5
  * The docs for errors have been extended for the type when returned

### Changed

* API5
  * Return the xml total_count of playlists based on hide_search preference

### Fixed

* API5
  * Some errors were returning the value and not the parameter on error
* API4
  * update_from_tags: type case error
  * rate: Object type to class mapping
  * flag: Object type to class mapping
  * update_art: Object type to class mapping and type case check
  * update_from_tags: Object type to class mapping
  * genre and tag function compatibility
* API3
  * stats: incorrect getRandom call
  * rate: Object type to class mapping
  * playlist: bad escaping on the playlist id

## API 5.2.0

Check out the docs for multi API support at [ampache.org](https://ampache.org/api/)

**note** JSON didn't exist for API3 so all json requests from API3 calls will revert to API5

### Added

* Support for API3, API4 and API5 responses including PHP8 support (keeps original tag calls)
* API5
  * playlists: add parameter `show_dupes` if true ignore 'api_hide_dupe_searches' setting
  * get_art: add parameter `fallback` if true return default art ('blankalbum.png') instead of an error
* API4
  * playlists: add parameter `show_dupes` if true ignore 'api_hide_dupe_searches' setting
* API3
  * Added genre calls as an alias to tag functions to match API4 and API5

### Fixed

* Session and user id identification and errors from that
* API5
  * playlists: sql for searches wasn't filtering
  * Art URL for searches was malformed
* API4
  * Art URL for searches was malformed
* API3
  * democratic: This method was broken in API3 and never worked correctly

## API 5.1.1

### Fixed

* Access to podcast_episode_delete
* stats calls with an offest and limit
* advanced_search calls with an offset and limit

## API 5.1.0

### Added

* NEW API functions
  * Api::live_stream (get a radio stream by id)
  * Api::live_streams
* Api::stream Added type 'podcast_episode' ('podcast' to be removed in Ampache6)
* Add 'time' and 'size' to all podcast_episode responses

### Changed

* live_stream objects added 'catalog' and 'site_url'
* stats: additional type values: 'video', 'playlist', 'podcast', 'podcast_episode'

### Fixed

* get_indexes: JSON didn't think live_streams was valid (it is)
* record_play: user is optional
* Bad xml tags in deleted functions
* scrobble: Add song_mbid, artist_mbid, album_mbid (docs have no '_' so support both)

## API 5.0.0

All API code that used 'Tag' now references 'Genre' instead

This version of the API is the first semantic version. "5.0.0"

### Added

* Add global playcount to podcast_episode and video responses
* searches (the number of saved smartlists) added to the handshake/ping response
* NEW API functions
  * Api::song_delete (Delete files when you are allowed to)
  * Api::user_preferences (Get your user preferences)
  * Api::user_preference (Get your preference by name)
  * Api::system_update (Check Ampache for updates and run the update if there is one.)
  * Api::system_preferences (Preferences for the system user)
  * Api::system_preference (Get a system preference by name)
  * Api::preference_create (Add a new preference to Ampache)
  * Api::preference_edit (Edit a preference value by name; optionally apply to all users)
  * Api::preference_delete (Delete a preference by name)
  * Api::labels (list your record labels)
  * Api::label (get a label by id)
  * Api::label_artists (get all artists attached to that label)
  * Api::get_bookmark (See if you've previously played the file)
  * Api::bookmarks (List all bookmarks created by your account)
  * Api::bookmark_create (Create a bookmark to allow revisting later)
  * Api::bookmark_edit (Edit a bookmark)
  * Api::bookmark_delete (Delete a bookmark by object id, type, user and client name)
  * Api::localplay_songs (Get the list of songs in your localplay instance)
  * API::deleted_songs
  * API::deleted_podcast_episodes
  * API::deleted_videos

### Changed

* The API version matches release version '5.0.0'
* A backcompatible version (500000) is sent when using old api versions
* handshake and ping counts now return the actual object counts for playlists
  * 'playlists' => $counts['playlist'],
  * 'searches' => $counts['search'],
  * 'playlists_searches' => $counts['playlist'] + $counts['search']
* Renamed functions:
  * tags => genres
  * tag => genre
  * tag_artists => genre_artists
  * tag_albums => genre_albums
  * tag_songs => genre_songs
* Don't allow duplicate podcast feeds
* Make filter optional in shares, genre_artists, genre_albums, genre_songs (Used as a general catch all method like genres)
* Error Codes and response structure has changed
  * 4700 Access Control not Enabled
  * 4701 Received Invalid Handshake
  * 4703 Access Denied
  * 4704 Not Found
  * 4705 Missing Method
  * 4706 Depreciated Method
  * 4710 Bad Request
  * 4742 Failed Access Check
* stats: Removed back compat from older versions. Only 'type' is mandatory
* Return empty objects when the request was correct but the results were empty
* Don't transcode podcast_episodes
* localplay
  * Added 'track' parameter used by 'skip' commands to go to the playlist track (playlist starts at 1)
* system_update: update the database if required as well
* playlist_edit: added 'owner' as an optional parameter (Change playlist owner to the user id)
* catalog_file: Allow comma-separate task values. (good for API inotify scripts)
* podcast_episode object "pubdate" has been changed to ISO 8601 date (2004-02-12T15:19:21+00:00)
* podcast object "build_date" and "sync_date" have also been changed to ISO 8601 date

### Fixed

* catalog_file: Couldn't add files

## API 4.4.3

**NO CHANGE**

## API 4.4.2

### Fixed

* API::indexes Artist albums were being added incorrectly for XML
* Send back the full album name in responses

## API 4.4.1

### Fixed

* API::stats would not offset recent calls

## API 4.4.0

### Added

* NEW API functions
  * Api::users (ID and Username of the site users)
* Api::localplay added new options to 'command' ('pause', 'add', 'volume_up', 'volume_down', 'volume_mute', 'delete_all', 'skip')
* Api::localplay added parameters:
  * 'oid' (integer) object_id to add //optional
  * 'type' (string) Default: 'Song' ('Song', 'Video', 'Podcast_Episode', 'Channel', 'Broadcast', 'Democratic', 'Live_Stream') //optional
  * 'clear' (integer) 0|1 clear the current playlist on add //optional
* Api::playlist_edit added new parameter 'sort': (0,1) sort the playlist by 'Artist, Album, Song' //optional
* Api::get_indexes
  * New type options: 'album_artist', 'podcast', 'podcast_episode', 'share', 'video'
  * Added parameter 'include': (0,1) (add the extra songs details if a playlist or podcast_episodes if a podcast)
* Api::rate - Added types 'playlist', 'podcast', 'podcast_episode', 'video', 'tvshow', 'tvshow_season'
* Api::flag - Added types 'podcast', 'podcast_episode', 'video', 'tvshow', 'tvshow_season'
* Add time to artist and album objects. (total time of all songs in seconds)
* Add songcount, albumcount to artist objects. (time in seconds)
* Add songcount to album objects. (time in seconds)
* Add type (release_type) to album objects
* Add disk to song objects
* Add time to video objects. (time in seconds)
* Add title, mime, catalog to podcast_episodes
* Api::advanced_search Add 'playlist', 'user' and 'video' to search types
* Api::handshake added extra total counts to the response
  * users, tags, podcasts, podcast_episodes, shares, licenses, live_streams, labels
* Api::ping match the handshake response (excluding the auth token)

### Changed

* get_indexes: 'playlist' now requires include=1 for xml calls if you want the tracks
* Make filter optional in shares
* Api::podcast_episodes
  * "url" is now a play url (instead of a link to the episode)
  * "public_url" is now the old episode link

### Fixed

* Api::podcast_edit wasn't able to edit a podcast...
* Api::democratic was using action from localplay in the return responses
* get_indexes for XML didn't include podcast indexes
* Set OUTDATED_DATABASE_OK on image.php, play/index.php and share.php to stop blocking requests
* Don't limit sub items when using a limit (e.g return all podcast episodes when selecting a podcast)

### Deprecated

* Dropped in API 5.0.0
  * Api::get_indexes; stop including playlist track and id in xml by default
  * Album objects: "tracks" will only include track details. Use "songcount"
  * Artist objects: "albums", "songs" will only include track details Use "albumcount" and "songcount"

## API 4.3.0

### Changed

* Api::record_play
  * Make 'user' parameter optional
  * Allow 'user' to the be user_id **or** the username string
  * Add 'date' parameter (optional)
  * Require 100 (Admin) permission to record plays for other users
* Api::get_indexes
  * Add 'hide_search' parameter (optional)
* Api::playlists
  * Add 'hide_search' parameter (optional)
* Setting a limit of 'none' would slice away all the results

## API 4.2.6

**NO CHANGE**

## API 4.2.5

**NO CHANGE**

## API 4.2.4

**NO CHANGE**

## API 4.2.3

**NO CHANGE**

## API 4.2.2

Minor bugfixes

### Added

* Api::advanced_search added parameter 'random' (0|1) to shuffle your searches

### Changed

* Remove spaces from advanced_search rule names. (Backwards compatible with old names)
  * 'has image' => 'has_image'
  * 'image height' => 'image_height'
  * 'image width' => 'image_width'
  * 'filename' => 'file' (Video search)

### Deprecated

* Search rules 'has image', 'image height', 'image width', 'filename'. (Removed in Ampache 5.0.0)

### Fixed

* Api::stream, Api::download Api::playlist_generate 'format' parameter was overwritten with 'xml' or 'json'
* Produce valid XML for playlist_generate using the 'id' format in XML

## API 4.2.1

No functional changes from 4.2.0

### Fixed

* Filter in "playlist" and "playlist_songs" fixed

## API 4.2.0

**API versions will follow release version and no longer use builds in the integer versions (e.g. 420000)**
API 5.0.0-release will be the first Ampache release to match the release string.

#### Added

* JSON API now available!
  * Call xml as normal:
    * [<http://music.com.au/server/xml.server.php?action=handshake&auth=APIKEY&version=420000>]
  * Call the JSON server:
    * [<http://music.com.au/server/json.server.php?action=handshake&auth=APIKEY&version=420000>]
  * Example XML and JSON responses available [here](https://github.com/ampache/python3-ampache/tree/master/docs)
* NEW API functions
  * get_similar: send artist or song id to get related objects from last.fm
  * shares: get a list of shares you can access
  * share: get a share by id
  * share_create: create a share
  * share_edit: edit an existing share
  * share_delete: delete an existing share
  * podcasts: get a list of podcasts you can access
  * podcast: get a podcast by id
  * podcast_episodes: get a list of podcast_episodes you can access
  * podcast_episode: get a podcast_episode by id
  * podcast_episode_delete: delete an existing podcast_episode
  * podcast_create: create a podcast
  * podcast_edit: edit an existing podcast
  * podcast_delete: delete an existing podcast
  * update_podcast: sync and download new episodes
  * licenses: get a list of licenses you can access
  * license: get a license by id
  * catalogs: get all the catalogs
  * catalog: get a catalog by id
  * catalog_file: clean, add, verify using the file path (good for scripting)

#### Changed

* Bump API version to 420000 (4.2.0)
* All calls that return songs now include ```<playlisttrack>``` which can be used to identify track order.
* ```<playcount>``` added to objects with a playcount.
* ```<license>``` added to song objects.
* Don't gather art when adding songs
* Added actions to catalog_action. 'verify_catalog' 'gather_art'
* API function "playlist_edit": added ability to edit playlist items
  * items  = (string) comma-separated song_id's (replace existing items with a new object_id) //optional
  * tracks = (string) comma-separated playlisttrack numbers matched to items in order //optional
* Random albums will get songs for all disks if album_group enabled

### Deprecated

* API Build number is depreciated (the last 3 digits of the api version)
  * API 5.0.0 will be released with a string version ("5.0.0-release")
  * All future 4.x.x API versions will follow the main Ampache version. (420000, 421000, 422000)
* total_count in the XML API is depreciated and will be removed in API 5.0.0.
  * XML can count objects the same was as a JSON array [https://www.php.net/manual/en/simplexmlelement.count.php]
* Genre in songs is depreciated and will be removed in API 5.0.0.
  * Use tag instead of genre, tag provides a genre ID as well as the name.

#### Fixed

* Extra text in catalog API calls
* Don't fail the API calls when the database needs updating

## API 4.0.0 build 004

Bump API version to 400004 (4.0.0 build 004)

#### Added

* Add Api::check_access to warn when you can't access a function

#### Fixed

* Fix parameters using 0
* Get the correct total_count in xml when you set a limit
* Fix many XML formatting issues

## API 4.0.0 build 003

Bump API version to 400003 (4.0.0 build 003)

#### Added

* user_numeric searches also available in the API. ([<http://ampache.org/api/api-xml-methods>])

#### Changed

* Api::playlist - filter mandatory
* Api::playlist_edit - filter mandatory. name and type now optional
* Api::user - Extend return values to include more user fields
* Playlist::create - Return duplicate playlist ID instead of creating a new one
* Do not limit smartlists based on item count (return everything you can access)
* Api/Database - Add last_count for search table to speed up access in API

#### Removed

* Artist::check - Remove MBID from Various Artist objects

#### Fixed

* Fix Song::update_song for label
* Fix Api issues relating to playlist access

## API 4.0.0 build 001

* Bump API version to 400002 (4.0.0 build 001)

#### Added

* Documented the Ampache API [<http://ampache.org/api/api-xml-methods>]
* Include smartlists in the API playlist calls.
* Authentication: allow sha256 encrypted apikey for auth
  * You must send an encrypted api key in the following fashion. (Hash key joined with username)
  * $passphrase = hash('sha256', $username . hash('sha256', $apikey));
* Added artist_tag to song searches
* flag: allows flagging object by id & type
* record_play: allows recording play of object without streaming
* catalog_action: allow running add_to_catalog|clean_catalog
* playlist_edit: allow editing name and type of playlist
* goodbye: Destroy session
* get_indexes: return simple index lists to allow a quicker library fill.
* check_parameter: error when mandatory inputs are missing
* stream: Raw stream of song_id
* download: Download, not recorded as a play
* get_art: Raw art file like subsonic getCoverArt
* user_create: 'user' access level only!
* user_update: update user details and passwords for non-admins
* user_delete: you can't delete yourself or and admin account!
* update_from_tags: updates a single album, artist, song from the tag data instead of the entire library!
* update_art: updates a single album, artist, song running the gather_art process
* update_artist_info: Update artist information and fetch similar artists from last.fm
* playlist_generate: Get a list of song xml, indexes or id's based on some simple search criteria. care of @4phun

#### Changed

* Authentication: Require a handshake and generate unique sessions at all times
* advanced_search
  * 'is not' has been added shifting values down the list.
    (0=contains, 1=does not contain, 2=starts with, 3=ends with, 4=is, 5=is not, 6=sounds like, 7=does not sound like)
  * rule_1['name'] is depreciated. Instead of multiple searches for the same thing rule_1'name'
    has been replaced with 'title' (I have put a temp workaround into the search rules to alleviate this change)
* stats
  * allow songs|artists|albums (instead of just albums)
* playlists
  * allow return of smartlists as well as regular playlists (set a 5000 limit on unlimited smartlists)
* playlist_add_song
  * Added check boolean to skip duplicate songs
* playlist_remove_song
  * Allow uid of song instead of the track id from the playlist
