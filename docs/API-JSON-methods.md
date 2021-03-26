---
title: "API 4 JSON"
metaTitle: "API 4 JSON"
metaDescription: "API documentation"
---

Lets go through come calls and examples that you can do for each JSON method.

Remember that Binary data methods will not return JSON; just the file/data you have requested.

## Non-Data Methods

## handshake

This is the function that handles verifying a new handshake Takes a timestamp, auth key, and username.

| Input       | Type    | Description                                                                                     | Optional |
|-------------|---------|-------------------------------------------------------------------------------------------------|---------:|
| 'auth'      | string  | $passphrase (Timestamp . Password SHA hash) OR (API Key)                                        |       NO |
| 'user'      | string  | $username (Required if login/password authentication)                                           |      YES |
| 'timestamp' | integer | UNIXTIME() (Timestamp used in seed of password hash. Required if login/password authentication) |      YES |
| 'version'   | string  | $version (API Version that the application understands)                                         |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/handshake.json)

## ping

This can be called without being authenticated, it is useful for determining if what the status of the server is, and what version it is running/compatible with

| Input     | Type   | Description                                                                | Optional |
|-----------|--------|----------------------------------------------------------------------------|----------|
| 'auth'    | string | (Session ID) returns version information and extends the session if passed | YES      |
| 'version' | string | $version (API Version that the application understands)                    | YES      |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/ping.json)

## goodbye

Destroy a session using the auth parameter.

| Input  | Type   | Description                                    | Optional |
|--------|--------|------------------------------------------------|----------|
| 'auth' | string | (Session ID) destroys the session if it exists | NO       |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/goodbye.json)

## url_to_song

This takes a url and returns the song object in question

| Input | Type   | Description                                                    | Optional |
|-------|--------|----------------------------------------------------------------|---------:|
| 'url' | string | Full Ampache URL from server, translates back into a song JSON |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/url_to_song.json)

## Data Methods

## get_indexes

This takes a collection of inputs and returns ID + name for the object type

| Input         | Type       | Description                                                      | Optional |
|---------------|------------|------------------------------------------------------------------|---------:|
| 'type'        | string     | 'song', 'album', 'artist', 'playlist'                            |       NO |
| 'filter'      | string     |                                                                  |      YES |
| 'add'         | set_filter | ISO 8601 Date Format (2020-09-16)                                |      YES |
|               |            | Find objects with an 'add' date newer than the specified date    |          |
| 'update'      | set_filter | ISO 8601 Date Format (2020-09-16)                                |      YES |
|               |            | Find objects with an 'update' time newer than the specified date |          |
| 'offset'      | integer    | Return results starting from this index position                 |      YES |
| 'limit'       | integer    | Maximum number of results to return                              |      YES |
| 'hide_search' | integer    | 0,1, if true do not include searches/smartlists in the result    |      YES |

SONGS

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/get_indexes%20\(song\).json)

ARTIST

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/get_indexes%20\(artist\).json)

ALBUM

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/get_indexes%20\(album\).json)

PLAYLIST

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/get_indexes%20\(playlist\).json)

## advanced_search

### Changes to text searches

* 'is not' has been added shifting values down the list.
  0=contains, 1=does not contain, 2=starts with, 3=ends with
  4=is, 5=is not, 6=sounds like, 7=does not sound like
* rule_1['name'] is depreciated. Instead of rule_1['name'] use rule_1['title'] (I have put a temp workaround into the search rules to alleviate this change for any existing apps)
* Metadata Search is combined with text and numeric. Meaning that changes to text lists push the numeric fields down.

### Using advanced_search

Perform an advanced search given passed rules. This works in a similar way to the web/UI search pages.
You can pass multiple rules as well as joins to create in depth search results

Rules must be sent in groups of 3 using an int (starting from 1) to designate which rules are combined.
Use operator ('and'|'or') to choose whether to join or separate each rule when searching.

Refer to the [Advanced Search](https://ampache.org/api/api-4/api-advanced-search) page for details about creating searches.

* INPUTS
  * ampache_url = (string)
  * ampache_API = (string)
  * operator = (string) 'and'|'or' (whether to match one rule or all)
  * rules = (array) = [[rule_1,rule_1_operator,rule_1_input], [rule_2,rule_2_operator,rule_2_input], [etc]]
  * type = (string) 'song', 'album', 'artist', 'playlist', 'label', 'user', 'video'
  * random = (integer) 0|1 (random order of results; default to 0)
  * offset = (integer)
  * limit' = (integer)

SONG

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/advanced_search%20\(song\).json)

ARTIST

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/advanced_search%20\(artist\).json)

ALBUM

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/advanced_search%20\(album\).json)

## artists

This takes a collection of inputs and returns artist objects.

| Input     | Type       | Description                                                                   | Optional |
|-----------|------------|-------------------------------------------------------------------------------|---------:|
| 'filter'  | string     | Value is Alpha Match for returned results, may be more than one letter/number |      YES |
| 'exact'   | boolean    | if true filter is exact rather then fuzzy                                     |      YES |
| 'add'     | set_filter | ISO 8601 Date Format (2020-09-16)                                             |      YES |
|           |            | Find objects with an 'add' date newer than the specified date                 |          |
| 'update'  | set_filter | ISO 8601 Date Format (2020-09-16)                                             |      YES |
|           |            | Find objects with an 'update' time newer than the specified date              |          |
| 'offset'  | integer    | Return results starting from this index position                              |      YES |
| 'limit'   | integer    | Maximum number of results to return                                           |      YES |
| 'include' | string     | 'albums', 'songs' and will include the corresponding JSON                     |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/artists.json)

## artist

This returns a single artist based on the UID of said artist

| Input     | Type    | Description                                                                         | Optional |
|-----------|---------|-------------------------------------------------------------------------------------|---------:|
| 'filter'  | integer | UID of Artist, returns artist JSON                                                  |       NO |
| 'include' | string  | 'albums', 'songs' and will include the corresponding JSON nested in the artist JSON |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/artist.json)

## artist_albums

This returns the albums of an artist

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'filter' | integer | UID of Artist, returns Album JSON                |       NO |
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/artist_albums.json)

## artist_songs

This returns the songs of the specified artist

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'filter' | integer | UID of Artist, returns Song JSON                 |       NO |
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/artist_songs.json)

## albums

This returns albums based on the provided search filters

| Input     | Type       | Description                                                                   | Optional |
|-----------|------------|-------------------------------------------------------------------------------|---------:|
| 'filter'  | string     | Value is Alpha Match for returned results, may be more than one letter/number |      YES |
| 'exact'   | boolean    | if true filter is exact rather then fuzzy                                     |       NO |
| 'add'     | set_filter | ISO 8601 Date Format (2020-09-16)                                             |      YES |
|           |            | Find objects with an 'add' date newer than the specified date                 |          |
| 'update'  | set_filter | ISO 8601 Date Format (2020-09-16)                                             |      YES |
|           |            | Find objects with an 'update' time newer than the specified date              |          |
| 'offset'  | integer    | Return results starting from this index position                              |      YES |
| 'limit'   | integer    | Maximum number of results to return                                           |      YES |
| 'include' | string     | 'albums', 'songs' will include nested in the album JSON                       |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/albums.json)

## album

This returns a single album based on the UID provided

| Input     | Type    | Description                                                          | Optional |
|-----------|---------|----------------------------------------------------------------------|---------:|
| 'filter'  | integer | UID of Album, returns album JSON                                     |       NO |
| 'include' | string  | 'songs' will include the corresponding JSON nested in the album JSON |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/album.json)

## album_songs

This returns the songs of a specified album

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'filter' | integer | UID of Album, returns song JSON                  |       NO |
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/album_songs.json)

## tags

This returns the tags (Genres) based on the specified filter

| Input    | Type    | Description                                                                   | Optional |
|----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter' | string  | Value is Alpha Match for returned results, may be more than one letter/number |      YES |
| 'exact'  | boolean | if true filter is exact rather then fuzzy                                     |      YES |
| 'offset' | integer | Return results starting from this index position                              |      YES |
| 'limit'  | integer | Maximum number of results to return                                           |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/tags.json)

## tag

This returns a single tag based on UID

| Input    | Type    | Description                  | Optional |
|----------|---------|------------------------------|---------:|
| 'filter' | integer | UID of tag, returns tag JSON |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/tag.json)

## tag_artists

This returns the artists associated with the tag in question as defined by the UID

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'filter' | integer | UID of tag, returns artist JSON                  |       NO |
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/tag_artists.json)

## tag_albums

This returns the albums associated with the tag in question

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'filter' | integer | UID of tag, returns album JSON                   |       NO |
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/tag_albums.json)

## tag_songs

returns the songs for this tag

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'filter' | integer | UID of tag, returns song JSON                    |       NO |
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/tag_songs.json)

## songs

Returns songs based on the specified filter

| Input    | Type       | Description                                                                   | Optional |
|----------|------------|-------------------------------------------------------------------------------|---------:|
| 'filter' | string     | Value is Alpha Match for returned results, may be more than one letter/number |       NO |
| 'add'    | set_filter | ISO 8601 Date Format (2020-09-16)                                             |      YES |
|          |            | Find objects with an 'add' date newer than the specified date                 |          |
| 'update' | set_filter | ISO 8601 Date Format (2020-09-16)                                             |      YES |
|          |            | Find objects with an 'update' time newer than the specified date              |          |
| 'exact'  | boolean    | if true filter is exact rather then fuzzy                                     |       NO |
| 'offset' | integer    | Return results starting from this index position                              |      YES |
| 'limit'  | integer    | Maximum number of results to return                                           |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/songs.json)

## song

returns a single song

| Input    | Type    | Description                    | Optional |
|----------|---------|--------------------------------|---------:|
| 'filter' | integer | UID of Song, returns song JSON |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/song.json)

## playlists

This returns playlists based on the specified filter

| Input         | Type       | Description                                                                   | Optional |
|---------------|------------|-------------------------------------------------------------------------------|---------:|
| 'filter'      | string     | Value is Alpha Match for returned results, may be more than one letter/number |      YES |
| 'exact'       | boolean    | if true filter is exact rather then fuzzy                                     |      YES |
| 'add'         | set_filter | ISO 8601 Date Format (2020-09-16)                                             |      YES |
|               |            | Find objects with an 'add' date newer than the specified date                 |          |
| 'update'      | set_filter | ISO 8601 Date Format (2020-09-16)                                             |      YES |
|               |            | Find objects with an 'update' time newer than the specified date              |          |
| 'offset'      | integer    | Return results starting from this index position                              |      YES |
| 'limit'       | integer    | Maximum number of results to return                                           |      YES |
| 'hide_search' | integer    | 0,1, if true do not include searches/smartlists in the result                 |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/playlists.json)

## playlist

This returns a single playlist

| Input    | Type   | Description                            | Optional |
|----------|--------|----------------------------------------|---------:|
| 'filter' | string | UID of playlist, returns playlist JSON |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/playlist.json)

## playlist_songs

This returns the songs for a playlist

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'filter' | string  | UID of Playlist, returns song JSON               |       NO |
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/playlist_songs.json)

## playlist_create

This create a new playlist and return it

| Input  | Type   | Description                       | Optional |
|--------|--------|-----------------------------------|---------:|
| 'name' | string | Playlist name                     |       NO |
| 'type' | string | Playlist type 'public', 'private' |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/playlist_create.json)

## playlist_edit

This modifies name and type of a playlist
Previously name and type were mandatory while filter wasn't. this has been reversed.

| Input    | Type   | Description                                                             | Optional |
|----------|--------|-------------------------------------------------------------------------|---------:|
| 'filter' | string | UID of Playlist                                                         |       NO |
| 'name'   | string | Playlist name                                                           |      YES |
| 'type'   | string | Playlist type 'public', 'private'                                       |      YES |
| 'items'  | string | comma-separated song_id's (replace existing items with a new object_id) |      YES |
| 'tracks' | string | comma-separated playlisttrack numbers matched to items in order         |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/playlist_edit.json)

## playlist_delete

This deletes a playlist

| Input    | Type   | Description     | Optional |
|----------|--------|-----------------|---------:|
| 'filter' | string | UID of Playlist |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/playlist_delete.json)

## playlist_add_song

This adds a song to a playlist. setting check=1 will not add duplicates to the playlist

| Input    | Type    | Description                                               | Optional |
|----------|---------|-----------------------------------------------------------|---------:|
| 'filter' | integer | UID of Playlist                                           |       NO |
| 'song'   | integer | UID of song to add to playlist                            |       NO |
| 'check'  | boolean | 0, 1 Whether to check and ignore duplicates (default = 0) |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/playlist_add_song.json)

## playlist_remove_song

This remove a song from a playlist.
Previous versions required 'track' instead of 'song'.

| Input    | Type    | Description                          | Optional |
|----------|---------|--------------------------------------|---------:|
| 'filter' | string  | UID of Playlist                      |       NO |
| 'song'   | integer | UID of song to remove from playlist  |      YES |
| 'track'  | integer | Track number to remove from playlist |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/playlist_remove_song.json)

## playlist_generate

Get a list of song JSON, indexes or id's based on some simple search criteria
'recent' will search for tracks played after 'Popular Threshold' days
'forgotten' will search for tracks played before 'Popular Threshold' days
'unplayed' added in 400002 for searching unplayed tracks

| Input    | Type    | Description                                                      | Optional |
|----------|---------|------------------------------------------------------------------|---------:|
| 'mode'   | string  | 'recent', 'forgotten', 'unplayed', 'random' (default = 'random') |      YES |
| 'filter' | string  | string LIKE matched to song title                                |      YES |
| 'album'  | integer | $album_id                                                        |      YES |
| 'artist' | integer | $artist_id                                                       |      YES |
| 'flag'   | integer | get flagged songs only 0, 1 (default = 0)                        |      YES |
| 'format' | string  | 'song', 'index','id' (default = 'song')                          |      YES |
| 'offset' | integer | Return results starting from this index position                 |      YES |
| 'limit'  | integer | Maximum number of results to return                              |      YES |

SONG

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/playlist_generate%20\(song\).json)

INDEX

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/playlist_generate%20\(index\).json)

ID

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/playlist_generate%20\(id\).json)

## shares

* **NEW** in 4.2.0

This searches the shares and returns... shares

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'filter' | string  | Value is Alpha Match for Share Title             |      YES |
| 'exact'  | integer | 0, 1 boolean to match the exact filter string    |      YES |
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/shares.json)

## share

* (MINIMUM_API_VERSION=420000)

Return shares by UID

| Input    | Type    | Description                        | Optional |
|----------|---------|------------------------------------|---------:|
| 'filter' | integer | UID of Share, returns song JSON NO |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/share.json)

## share_create

* (MINIMUM_API_VERSION=420000

Create a public url that can be used by anyone to stream media.
Takes the file id with optional description and expires parameters.

| Input         | Type    | Description                                   | Optional |
|---------------|---------|-----------------------------------------------|---------:|
| 'filter'      | string  | UID of object you are sharing                 |       NO |
| 'type'        | string  | object_type                                   |       NO |
| 'description' | string  | description (will be filled for you if empty) |      YES |
| 'expires'     | integer | days to keep active                           |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/share_create.json)

## share_edit

* (MINIMUM_API_VERSION=420000

Update the description and/or expiration date for an existing share.
Takes the share id to update with optional description and expires parameters.

| Input         | Type    | Description                        | Optional |
|---------------|---------|------------------------------------|---------:|
| 'filter'      | string  | Alpha-numeric search term          |       NO |
| 'stream'      | boolean | 0, 1                               |      YES |
| 'download'    | boolean | 0, 1                               |      YES |
| 'expires'     | integer | number of whole days before expiry |      YES |
| 'description' | string  | update description                 |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/share_edit.json)

## share_delete

* (MINIMUM_API_VERSION=420000

Delete an existing share.

| Input    | Type    | Description            | Optional |
|----------|---------|------------------------|---------:|
| 'filter' | integer | UID of Share to delete |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/share_delete.json)

## get_similar

* **NEW** in 4.2.0

Return similar artist id's or similar song ids compared to the input filter

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'type'   | string  | 'song' or 'artist'                               |       NO |
| 'filter' | integer | artist id or song id                             |       NO |
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/get_similar.json)

## search_songs

This searches the songs and returns... songs

| Input    | Type    | Description                                                                                | Optional |
|----------|---------|--------------------------------------------------------------------------------------------|---------:|
| 'filter' | string  | Value is Alpha Match for Song Title, Artist Name, Album Name, Genre Name returns song JSON |       NO |
| 'offset' | integer | Return results starting from this index position                                           |      YES |
| 'limit'  | integer | Maximum number of results to return                                                        |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/search_songs.json)

## videos

This returns video objects!

| Input    | Type    | Description                                                                   | Optional |
|----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter' | string  | Value is Alpha Match for returned results, may be more than one letter/number |       NO |
| 'exact'  | boolean | if true filter is exact rather then fuzzy                                     |      YES |
| 'offset' | integer | Return results starting from this index position                              |      YES |
| 'limit'  | integer | Maximum number of results to return                                           |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/videos.json)

## video

This returns a single video

| Input    | Type    | Description                      | Optional |
|----------|---------|----------------------------------|---------:|
| 'filter' | integer | UID of video, returns video JSON |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/video.json)

## podcasts

* **NEW** in 4.2.0

Get information about podcasts

| Input     | Type    | Description                                                                   | Optional |
|-----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter'  | string  | Value is Alpha Match for returned results, may be more than one letter/number |      YES |
| 'offset'  | integer | Return results starting from this index position                              |      YES |
| 'limit'   | integer | Maximum number of results to return                                           |      YES |
| 'include' | string  | 'episodes' (include episodes in the response)                                 |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/podcasts.json)

## podcast

* **NEW** in 4.2.0

Get the podcast from it's id.

| Input     | Type   | Description                                   | Optional |
|-----------|--------|-----------------------------------------------|---------:|
| 'filter'  | string | UID of podcast, returns podcast JSON          |       NO |
| 'include' | string | 'episodes' (include episodes in the response) |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/podcast.json)

## podcast_create

* **NEW** in 4.2.0

Create a podcast that can be used by anyone to stream media.
Takes the url and catalog parameters.

| Input     | Type   | Description         | Optional |
|-----------|--------|---------------------|---------:|
| 'url'     | string | rss url for podcast |       NO |
| 'catalog' | string | podcast catalog     |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/podcast_create.json)

## podcast_edit

* **NEW** in 4.2.0

Update the description and/or expiration date for an existing podcast.
Takes the podcast id to update with optional description and expires parameters.

| Input         | Type   | Description               | Optional |
|---------------|--------|---------------------------|---------:|
| 'filter'      | string | Alpha-numeric search term |       NO |
| 'feed'        | string | feed rss xml url          |      YES |
| 'title'       | string | title string              |      YES |
| 'website'     | string | source website url        |      YES |
| 'description' | string |                           |      YES |
| 'generator'   | string |                           |      YES |
| 'copyright'   | string |                           |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/podcast_edit.json)

## podcast_delete

* **NEW** in 4.2.0

Delete an existing podcast.

| Input    | Type   | Description              | Optional |
|----------|--------|--------------------------|---------:|
| 'filter' | string | UID of podcast to delete |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/podcast_delete.json)

## podcast_episodes

* **NEW** in 4.2.0

This returns the episodes for a podcast

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'filter' | string  | UID of podcast                                   |       NO |
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/podcast_episodes.json)

## podcast_episode

* **NEW** in 4.2.0

Get the podcast_episode from it's id.

| Input    | Type   | Description               | Optional |
|----------|--------|---------------------------|---------:|
| 'filter' | string | podcast_episode ID number |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/podcast_episode.json)

## podcast_episode_delete

* **NEW** in 4.2.0

Delete an existing podcast_episode.

| Input    | Type   | Description                      | Optional |
|----------|--------|----------------------------------|---------:|
| 'filter' | string | UID of podcast_episode to delete |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/podcast_episode_delete.json)

## stats

Get some items based on some simple search types and filters.
This method has partial backwards compatibility with older api versions but should be updated to follow the current input values.
(Changed in 400001 `filter` added)

| Input      | Type    | Description                                                                 | Optional |
|------------|---------|-----------------------------------------------------------------------------|---------:|
| 'type'     | string  | 'song', 'album', 'artist'                                                   |       NO |
| 'filter'   | string  | 'newest', 'highest', 'frequent', 'recent', 'forgotten', 'flagged', 'random' |       NO |
| 'user_id'  | integer |                                                                             |      YES |
| 'username' | string  |                                                                             |      YES |
| 'offset'   | integer | Return results starting from this index position                            |      YES |
| 'limit'    | integer | Maximum number of results to return                                         |      YES |

SONG

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/stats%20\(song\).json)

ARTIST

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/stats%20\(artist\).json)

ALBUM

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/stats%20\(album\).json)

## user

This get an user public information

| Input      | Type   | Description                                 | Optional |
|------------|--------|---------------------------------------------|---------:|
| 'username' | string | Username of the user for who to get details |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/user.json)

## user_create

Create a new user. (Requires the username, password and email.)

| Input      | Type    | Description                | Optional |
|------------|---------|----------------------------|---------:|
| 'username' | string  | $username                  |       NO |
| 'password' | string  | hash('sha256', $password)) |       NO |
| 'email'    | string  | 'user@gmail.com'           |       NO |
| 'fullname' | string  |                            |      YES |
| 'disable'  | boolean | 0, 1                       |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/user_create.json)

## user_update

Update an existing user.

| Input        | Type    | Description                | Optional |
|--------------|---------|----------------------------|---------:|
| 'username'   | string  | $username                  |       NO |
| 'password'   | string  | hash('sha256', $password)) |      YES |
| 'email'      | string  | 'user@gmail.com'           |      YES |
| 'fullname'   | string  |                            |      YES |
| 'website'    | string  |                            |      YES |
| 'state'      | string  |                            |      YES |
| 'city'       | string  |                            |      YES |
| 'disable'    | boolean | 0, 1                       |      YES |
| 'maxbitrate' | string  |                            |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/user_update.json)

## user_delete

Delete an existing user.

| Input      | Type   | Description | Optional |
|------------|--------|-------------|---------:|
| 'username' | string |             |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/user_update.json)

## licenses

* **NEW** in 4.2.0

This returns the licenses based on the specified filter

| Input    | Type    | Description                                                                   | Optional |
|----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter' | string  | Value is Alpha Match for returned results, may be more than one letter/number |      YES |
| 'exact'  | boolean | if true filter is exact rather then fuzzy                                     |      YES |
| 'offset' | integer | Return results starting from this index position                              |      YES |
| 'limit'  | integer | Maximum number of results to return                                           |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/licenses.json)

## license

* **NEW** in 4.2.0

This returns a single license based on UID

| Input    | Type    | Description                          | Optional |
|----------|---------|--------------------------------------|---------:|
| 'filter' | integer | UID of license, returns license JSON |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/license.json)

## license_songs

* **NEW** in 4.2.0

This returns a list of songs based on the filter ID

| Input    | Type    | Description                       | Optional |
|----------|---------|-----------------------------------|---------:|
| 'filter' | integer | UID of license, returns song JSON |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/license_songs.json)

## followers

This get an user followers

| Input      | Type   | Description                                        | Optional |
|------------|--------|----------------------------------------------------|---------:|
| 'username' | string | Username of the user for who to get followers list |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/followers.json)

## following

This get the user list followed by an user

| Input      | Type   | Description                                         | Optional |
|------------|--------|-----------------------------------------------------|---------:|
| 'username' | string | (Username of the user for who to get following list |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/following.json)

## toggle_follow

This follow/unfollow an user

| Input      | Type   | Description                             | Optional |
|------------|--------|-----------------------------------------|---------:|
| 'username' | string | Username of the user to follow/unfollow |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/toggle_follow.json)

## last_shouts

This get the latest posted shouts

| Input      | Type    | Description                                       | Optional |
|------------|---------|---------------------------------------------------|---------:|
| 'username' | string  | Username of the user for who to get latest shouts |      YES |
| 'limit'    | integer | Maximum number of results to return               |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/last_shouts.json)

## rate

This rates a library item

| Input    | Type    | Description                                   | Optional |
|----------|---------|-----------------------------------------------|---------:|
| 'type'   | string  | 'song', 'album', 'artist', 'playlist',        |       NO |
|          |         | 'podcast', 'podcast_episode', 'video'         |          |
|          |         | 'tvshow', 'tvshow_season'                     |          |
| 'id'     | integer | library item id                               |       NO |
| 'rating' | integer | rating between 0-5                            |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/rate.json)

## flag

This flags a library item as a favorite

* Setting flag to true (1) will set the flag
* Setting flag to false (0) will remove the flag

| Input  | Type    | Description                            | Optional |
|--------|---------|----------------------------------------|---------:|
| 'type' | string  | 'song', 'album', 'artist', 'playlist', |       NO |
|        |         | 'podcast', 'podcast_episode', 'video'  |          |
|        |         | 'tvshow', 'tvshow_season'              |          |
| 'id'   | integer | $object_id                             |       NO |
| 'flag' | boolean | 0, 1                                   |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/flag.json)

## record_play

Take a song_id and update the object_count and user_activity table with a play. This allows other sources to record play history to ampache

**NOTE** Ampache 4.3.0

* 'user' has been made optional
* 'user' can be the user id (e.g 20) **or** the username (e.g generic_user)
* 'date' has also been added as an optional parameter

| Input    | Type           | Description           | Optional |
|----------|----------------|-----------------------|---------:|
| 'id'     | integer        | $object_id            |       NO |
| 'user'   | integer,string | $user_id OR $username |      YES |
| 'client' | string         | $agent                |      YES |
| 'date'   | string         | UNIXTIME()            |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/record_play.json)

## scrobble

Search for a song using text info and then record a play if found. This allows other sources to record play history to ampache

| Input        | Type    | Description  | Optional |
|--------------|---------|--------------|---------:|
| 'song'       | string  | $song_name   |       NO |
| 'artist'     | string  | $artist_name |       NO |
| 'album'      | string  | $album_name  |       NO |
| 'songmbid'   | string  | $song_mbid   |      YES |
| 'artistmbid' | string  | $artist_mbid |      YES |
| 'albummbid'  | string  | $album_mbid  |      YES |
| 'date'       | integer | UNIXTIME()   |      YES |
| 'client'     | string  | $agent       |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/scrobble.json)

## catalogs

* **NEW** in 4.2.0

This searches the catalogs and returns... catalogs

| Input    | Type   | Description                                                      | Optional |
|----------|--------|------------------------------------------------------------------|---------:|
| 'filter' | string | Catalog type music, clip, tvshow, movie, personal_video, podcast |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/catalogs.json)

## catalog

* **NEW** in 4.2.0

Return catalog by UID

| Input    | Type    | Description    | Optional |
|----------|---------|----------------|---------:|
| 'filter' | integer | UID of Catalog |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/catalog.json)

## catalog_action

Kick off a catalog update or clean for the selected catalog

| Input     | Type    | Description                       | Optional |
|-----------|---------|-----------------------------------|---------:|
| 'task'    | string  | 'add_to_catalog', 'clean_catalog' |       NO |
| 'catalog' | integer | $catalog_id                       |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/catalog_action%20\(clean_catalog\).json)

## catalog_file

* **NEW** in 4.2.0

Perform actions on local catalog files.
Single file versions of catalog add, clean, verify and remove (delete)
Make sure you remember to urlencode those file names!

| Input     | Type    | Description                     | Optional |
|-----------|---------|---------------------------------|---------:|
| 'file'    | string  | FULL path to local file         |       NO |
| 'task'    | string  | 'add','clean','verify','remove' |       NO |
| 'catalog' | integer | $catalog_id                     |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/catalog_file.json)

## timeline

This get an user timeline

| Input      | Type    | Description                                       | Optional |
|------------|---------|---------------------------------------------------|---------:|
| 'username' | string  | Username of the user for whom to get the timeline |       NO |
| 'limit'    | integer | Maximum number of results to return               |      YES |
| 'since'    | integer | UNIXTIME()                                        |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/timeline.json)

## friends_timeline

This get current user friends timeline

| Input   | Type    | Description                         | Optional |
|---------|---------|-------------------------------------|---------:|
| 'limit' | integer | Maximum number of results to return |      YES |
| 'since' | integer | UNIXTIME()                          |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/friends_timeline.json)

## update_from_tags

Update a single album, artist, song from the tag data

| Input  | Type    | Description                     | Optional |
|--------|---------|---------------------------------|---------:|
| 'type' | string  | 'artist', 'album', 'song'       |       NO |
| 'id'   | integer | $artist_id, $album_id, $song_id |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/update_from_tags.json)

## update_artist_info

Update artist information and fetch similar artists from last.fm
Make sure lastfm_API_key is set in your configuration file

| Input | Type    | Description | Optional |
|-------|---------|-------------|---------:|
| 'id'  | integer | $artist_id  |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/update_artist_info.json)

## update_art

Updates a single album, artist, song running the gather_art process
Doesn't overwrite existing art by default.

| Input       | Type    | Description       | Optional |
|-------------|---------|-------------------|---------:|
| 'id'        | integer | $object_id        |       NO |
| 'type'      | string  | 'song', 'podcast' |       NO |
| 'overwrite' | boolean | 0, 1              |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/update_art.json)

## update_podcast

Sync and download new podcast episodes

| Input | Type    | Description | Optional |
|-------|---------|-------------|---------:|
| 'id'  | integer | $object_id  |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/update_podcast.json)

## Binary Data Methods

## stream

Streams a given media file. Takes the file id in parameter with optional max bit rate, file format, time offset, size and estimate content length option.

| Input     | Type    | Description                 | Optional |
|-----------|---------|-----------------------------|---------:|
| 'id'      | integer | $object_id                  |       NO |
| 'type'    | string  | 'song', 'podcast'           |       NO |
| 'bitrate' | integer | max bitrate for transcoding |      YES |
| 'format'  | string  | 'mp3', 'ogg', 'raw', etc    |      YES |
| 'offset'  | integer | time offset in seconds      |      YES |
| 'length'  | boolean | 0, 1                        |      YES |

## download

Downloads a given media file. set format=raw to download the full file

| Input    | Type    | Description              | Optional |
|----------|---------|--------------------------|---------:|
| 'id'     | integer | $object_id               |       NO |
| 'type'   | string  | 'song', 'podcast'        |       NO |
| 'format' | string  | 'mp3', 'ogg', 'raw', etc |      YES |

## get_art

Get an art image.

| Input    | Type    | Description                                                | Optional |
|----------|---------|------------------------------------------------------------|---------:|
| 'id'     | integer | $object_id                                                 |       NO |
| 'type'   | string  | 'song', 'artist', 'album', 'playlist', 'search', 'podcast' |       NO |

## Control Methods

## localplay

This is for controlling localplay

| Input     | Type    | Description                                                  | Optional |
|-----------|---------|--------------------------------------------------------------|----------|
| 'command' | string  | 'next', 'prev', 'stop', 'play', 'pause', 'add', 'volume_up', | NO       |
|           |         | 'volume_down', 'volume_mute', 'delete_all', 'skip', 'status' |          |
| 'oid'     | integer | object_id                                                    | YES      |
| 'type'    | string  | 'Song', 'Video', 'Podcast_Episode', 'Channel',               | YES      |
|           |         | 'Broadcast', 'Democratic', 'Live_Stream'                     |          |
| 'clear'   | boolean | 0,1 Clear the current playlist before adding                 | YES      |

* return object

```JSON
"localplay": { "command": {} }
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/localplay.json)

[Example (status)](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/localplay%20\(status\).json)

## democratic

This is for controlling democratic play

* ACTION
  * method
    * vote
      * oid (Unique ID of the element you want to vote on)
      * type (Type of object, only song is currently accepted so this is optional)
    * devote
      * oid (Unique ID of the element you want to vote on)
      * type (Type of object, only song is currently accepted so this is optional)
    * playlist (Returns an array of song items with an additional \<vote>[VOTE COUNT]\</vote> element)
    * play (Returns the URL for playing democratic play)

| Input    | Type    | Description | Optional |
|----------|---------|-------------|---------:|
| 'oid'    | integer |             |       NO |
| 'method' | string  |             |       NO |
| 'action' | string  |             |       NO |

* return object|array

```JSON
"url": ""|"method": "","result": false|"song": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/democratic%20\(play\).json)
[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/democratic%20\(vote\).json)
[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api4/docs/json-responses/democratic%20\(playlist\).json)
