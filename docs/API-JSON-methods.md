---
title: "JSON Methods"
metaTitle: "JSON Methods"
metaDescription: "API documentation"
---

Let's go through come calls and examples that you can do for each JSON method.

With the exception of Binary methods, all responses will return a HTTP 200 response.

Also remember that Binary data methods will not return JSON; just the file/data you have requested.

Binary methods will also return:
* HTTP 400 responses for a bad or incomplete request
* HTTP 404 responses where the requests data was not found

## Non-Data Methods

### handshake

This is the function that handles verifying a new handshake Takes a timestamp, auth key, and username.

@param array $input

@return array

```JSON
{
    "auth"
    "api"
    "session_expire"
    "update"
    "add"
    "clean"
    "songs"
    "albums"
    "artists"
    "playlists"
    "videos"
    "catalogs"
}
```

@throws object

```JSON
"error": {}
```

| Input       | Type    | Description                                              | Optional |
|-------------|---------|----------------------------------------------------------|---------:|
| 'auth'      | string  | $passphrase (Timestamp . Password SHA hash) OR (API Key) |       NO |
| 'user'      | string  | $username (Required if login/password authentication)    |      YES |
| 'timestamp' | integer | UNIXTIME() The timestamp used in seed of password hash   |      YES |
|             |         | (Required if login/password authentication)              |          |
| 'version'   | string  | $version (API Version that the application understands)  |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/handshake.json)

### ping

This can be called without being authenticated, it is useful for determining if what the status of the server is, and what version it is running/compatible with
@param array $input

@return array

```JSON
{
    "session_expire"
    "server"
    "version"
    "compatible"
}
```

@throws array

```JSON
{
    "server"
    "version"
    "compatible"
}
```

| Input  | Type   | Description                                                                | Optional |
|--------|--------|----------------------------------------------------------------------------|---------:|
| 'auth' | string | (Session ID) returns version information and extends the session if passed |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/ping.json)

### goodbye

Destroy a session using the auth parameter.

@param array $input

@return object

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input  | Type   | Description                                    | Optional |
|--------|--------|------------------------------------------------|---------:|
| 'auth' | string | (Session ID) destroys the session if it exists |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/goodbye.json)

### url_to_song

This takes a url and returns the song object in question
@param array $input

@return object

```JSON
"song": {}
```

@throws object

```JSON
"error": {}
```

| Input | Type   | Description                                                    | Optional |
|-------|--------|----------------------------------------------------------------|---------:|
| 'url' | string | Full Ampache URL from server, translates back into a song JSON |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/url_to_song.json)

### system_update

* **NEW** in develop

Check Ampache for updates and run the update if there is one.
@param array $input

@return object

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/system_update.json)

## Data Methods

### get_indexes

This takes a collection of inputs and returns ID + name for the object type
@param array $input

@return object

```JSON
"song": {}|"album": {}|"artist": {}|"playlist": {}|"podcast": {}

```

@throws object

```JSON
"error": {}
```

| Input     | Type       | Description                                                      | Optional |
|-----------|------------|------------------------------------------------------------------|---------:|
| 'type'    | string     | 'song', 'album', 'artist', 'album_artist', 'playlist', 'podcast' |       NO |
| 'filter'  | string     |                                                                  |      YES |
| 'add'     | set_filter | ISO 8601 Date Format (2020-09-16)                                |      YES |
|           |            | Find objects with an 'add' date newer than the specified date    |          |
| 'update'  | set_filter | ISO 8601 Date Format (2020-09-16)                                |      YES |
|           |            | Find objects with an 'update' time newer than the specified date |          |
| 'include' | boolean    | 0,1 include songs in a playlist or episodes in a podcast         |      YES |
| 'offset'  | integer    |                                                                  |      YES |
| 'limit'   | integer    |                                                                  |      YES |

SONGS

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/get_indexes%20\(song\).json)

ARTIST

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/get_indexes%20\(artist\).json)

ALBUM

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/get_indexes%20\(album\).json)

PLAYLIST

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/get_indexes%20\(playlist\).json)

### advanced_search

#### Using advanced_search

Perform an advanced search given passed rules. This works in a similar way to the web/UI search pages.
You can pass multiple rules as well as joins to create in depth search results

Rules must be sent in groups of 3 using an int (starting from 1) to designate which rules are combined.
Use operator ('and', 'or') to choose whether to join or separate each rule when searching.

Refer to the [Advanced Search](http://ampache.org/api/api-advanced-search) page for details about creating searches.

@param array $input

@return object

```JSON
"song": {}|"album": {}|"artist": {}|"playlist": {}|"label": {}|"user": {}|"video": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type    | Description                                   | Optional |
|----------|---------|--------------- -------------------------------|---------:|
| operator | string  | 'and','or' (whether to match one rule or all) |       NO |
| rule_*   | array   | [rule_1,rule_1_operator,rule_1_input],        |       NO |
| rule_*   | array   | [rule_2,rule_2_operator,rule_2_input], [etc]] |      YES |
| type     | string  | 'song', 'album', 'artist', 'playlist',        |       NO |
|          |         | 'label', 'user', 'video'                      |          |
| random   | boolean | 0, 1 (random order of results; default to 0)  |      YES |
| offset   | integer |                                               |      YES |
| limit'   | integer |                                               |      YES |

**NOTE** the rules part can be confusing but essentially you can include as many 'arrays' of rules as you want.
Just add 1 to the rule value to create a new group of rules.

* Mandatory Rule Values
  * rule_1
  * rule_1_operator
  * rule_1_input
* Optional (Metadata searches **only**)
  * rule_1_subtype

SONG

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/advanced_search%20\(song\).json)

ARTIST

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/advanced_search%20\(artist\).json)

ALBUM

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/advanced_search%20\(album\).json)

### artists

This takes a collection of inputs and returns artist objects.

@param array $input

@return object

```JSON
"artist": {}
```

@throws object

```JSON
"error": {}
```

| Input          | Type       | Description                                                      | Optional |
|----------------|------------|------------------------------------------------------------------|---------:|
| 'filter'       | string     | Filter results to match this string                              |      YES |
| 'exact'        | boolean    | 0,1 if true filter is exact (=) rather than fuzzy (LIKE)         |      YES |
| 'add'          | set_filter | ISO 8601 Date Format (2020-09-16)                                |      YES |
|                |            | Find objects with an 'add' date newer than the specified date    |          |
| 'update'       | set_filter | ISO 8601 Date Format (2020-09-16)                                |      YES |
|                |            | Find objects with an 'update' time newer than the specified date |          |
| 'include'      | string     | 'albums', 'songs' and will include the corresponding JSON        |      YES |
| 'album_artist' | boolean    | 0,1 if true filter for album artists only                        |      YES |
| 'offset'       | integer    |                                                                  |      YES |
| 'limit'        | integer    |                                                                  |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/artists.json)

### artist

This returns a single artist based on the UID of said artist
@param array $input

@return object

```JSON
"artist": {}
```

@throws object

```JSON
"error": {}
```

| Input     | Type   | Description                                                                         | Optional |
|-----------|--------|-------------------------------------------------------------------------------------|---------:|
| 'filter'  | string | UID of Artist, returns artist JSON                                                  |       NO |
| 'include' | string | 'albums', 'songs' and will include the corresponding JSON nested in the artist JSON |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/artist.json)

### artist_albums

This returns the albums of an artist
@param array $input

@return object

```JSON
"album": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type    | Description                       | Optional |
|----------|---------|-----------------------------------|---------:|
| 'filter' | string  | UID of Artist, returns Album JSON |       NO |
| 'offset' | integer |                                   |      YES |
| 'limit'  | integer |                                   |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/artist_albums.json)

### artist_songs

This returns the songs of the specified artist
@param array $input

@return object

```JSON
"song": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type    | Description                      | Optional |
|----------|---------|----------------------------------|---------:|
| 'filter' | string  | UID of Artist, returns Song JSON |       NO |
| 'offset' | integer |                                  |      YES |
| 'limit'  | integer |                                  |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/artist_songs.json)

### albums

This returns albums based on the provided search filters
@param array $input

@return object

```JSON
"album": {}
```

@throws object

```JSON
"error": {}
```

| Input     | Type       | Description                                                      | Optional |
|-----------|------------|------------------------------------------------------------------|---------:|
| 'filter'  | string     | Filter results to match this string                              |      YES |
| 'exact'   | boolean    | 0,1 if true filter is exact (=) rather than fuzzy (LIKE)         |      YES |
| 'add'     | set_filter | ISO 8601 Date Format (2020-09-16)                                |      YES |
|           |            | Find objects with an 'add' date newer than the specified date    |          |
| 'update'  | set_filter | ISO 8601 Date Format (2020-09-16)                                |      YES |
|           |            | Find objects with an 'update' time newer than the specified date |          |
| 'offset'  | integer    |                                                                  |      YES |
| 'limit'   | integer    |                                                                  |      YES |
| 'include' | string     | 'albums', 'songs' will include nested in the album JSON          |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/albums.json)

### album

This returns a single album based on the UID provided
@param array $input

@return object

```JSON
"album": {}
```

@throws object

```JSON
"error": {}
```

| Input     | Type   | Description                                                          | Optional |
|-----------|--------|----------------------------------------------------------------------|---------:|
| 'filter'  | string | UID of Album, returns album JSON                                     |       NO |
| 'include' | string | 'songs' will include the corresponding JSON nested in the album JSON |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/album.json)

### album_songs

This returns the songs of a specified album
@param array $input

@return object

```JSON
"song": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type    | Description                     | Optional |
|----------|---------|---------------------------------|---------:|
| 'filter' | string  | UID of Album, returns song JSON |       NO |
| 'offset' | integer |                                 |      YES |
| 'limit'  | integer |                                 |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/album_songs.json)

### genres

This returns the genres (Tags) based on the specified filter
@param array $input

@return object

```JSON
"genre": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type    | Description                                              | Optional |
|----------|---------|----------------------------------------------------------|---------:|
| 'filter' | string  | Filter results to match this string                      |      YES |
| 'exact'  | boolean | 0,1 if true filter is exact (=) rather than fuzzy (LIKE) |      YES |
| 'offset' | integer |                                                          |      YES |
| 'limit'  | integer |                                                          |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/genres.json)

### genre

This returns a single genre based on UID
@param array $input

@return object

```JSON
"genre": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type   | Description                      | Optional |
|----------|--------|----------------------------------|---------:|
| 'filter' | string | UID of genre, returns genre JSON |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/genre.json)

### genre_artists

This returns the artists associated with the genre in question as defined by the UID
@param array $input

@return object

```JSON
"artist": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type    | Description                       | Optional |
|----------|---------|-----------------------------------|---------:|
| 'filter' | string  | UID of genre, returns artist JSON |      YES |
| 'offset' | integer |                                   |      YES |
| 'limit'  | integer |                                   |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/genre_artists.json)

### genre_albums

This returns the albums associated with the genre in question
@param array $input

@return object

```JSON
"album": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type    | Description                      | Optional |
|----------|---------|----------------------------------|---------:|
| 'filter' | string  | UID of genre, returns album JSON |      YES |
| 'offset' | integer |                                  |      YES |
| 'limit'  | integer |                                  |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/genre_albums.json)

### genre_songs

returns the songs for this genre
@param array $input

@return object

```JSON
"song": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type    | Description                     | Optional |
|----------|---------|---------------------------------|---------:|
| 'filter' | string  | UID of genre, returns song JSON |      YES |
| 'offset' | integer |                                 |      YES |
| 'limit'  | integer |                                 |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/genre_songs.json)

### songs

Returns songs based on the specified filter
@param array $input

@return object

```JSON
"song": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type       | Description                                                      | Optional |
|----------|------------|------------------------------------------------------------------|---------:|
| 'filter' | string     | Filter results to match this string                              |      YES |
| 'exact'  | boolean    | 0,1 if true filter is exact (=) rather than fuzzy (LIKE)         |      YES |
| 'add'    | set_filter | ISO 8601 Date Format (2020-09-16)                                |      YES |
|          |            | Find objects with an 'add' date newer than the specified date    |          |
| 'update' | set_filter | ISO 8601 Date Format (2020-09-16)                                |      YES |
|          |            | Find objects with an 'update' time newer than the specified date |          |
| 'offset' | integer    |                                                                  |      YES |
| 'limit'  | integer    |                                                                  |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/songs.json)

### song

returns a single song
@param array $input

@return object

```JSON
"song": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type   | Description                    | Optional |
|----------|--------|--------------------------------|---------:|
| 'filter' | string | UID of Song, returns song JSON |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/song.json)

### song_delete

* **NEW** in Develop

Delete an existing song. (if you are allowed to)
@param array $input

@return object

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type   | Description           | Optional |
|----------|--------|-----------------------|---------:|
| 'filter' | string | UID of song to delete |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/song_delete.json)

### playlists

This returns playlists based on the specified filter
@param array $input

@return object

```JSON
"playlist": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type       | Description                                                      | Optional |
|----------|------------|------------------------------------------------------------------|---------:|
| 'filter' | string     | Filter results to match this string                              |      YES |
| 'exact'  | boolean    | 0,1 if true filter is exact (=) rather than fuzzy (LIKE)         |      YES |
| 'add'    | set_filter | ISO 8601 Date Format (2020-09-16)                                |      YES |
|          |            | Find objects with an 'add' date newer than the specified date    |          |
| 'update' | set_filter | ISO 8601 Date Format (2020-09-16)                                |      YES |
|          |            | Find objects with an 'update' time newer than the specified date |          |
| 'offset' | integer    |                                                                  |      YES |
| 'limit'  | integer    |                                                                  |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/playlists.json)

### playlist

This returns a single playlist
@param array $input

@return object

```JSON
"playlist": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type   | Description                            | Optional |
|----------|--------|----------------------------------------|---------:|
| 'filter' | string | UID of playlist, returns playlist JSON |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/playlist.json)

### playlist_songs

This returns the songs for a playlist
@param array $input

@return object

```JSON
"song": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type    | Description                        | Optional |
|----------|---------|------------------------------------|---------:|
| 'filter' | string  | UID of Playlist, returns song JSON |       NO |
| 'offset' | integer |                                    |      YES |
| 'limit'  | integer |                                    |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/playlist_songs.json)

### playlist_create

This create a new playlist and return it
@param array $input

@return object

```JSON
"playlist": {}
```

@throws object

```JSON
"error": {}
```

| Input  | Type   | Description                       | Optional |
|--------|--------|-----------------------------------|---------:|
| 'name' | string | Playlist name                     |       NO |
| 'type' | string | Playlist type 'public', 'private' |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/playlist_create.json)

### playlist_edit

This modifies name and type of a playlist
Previously name and type were mandatory while filter wasn't. this has been reversed.
@param array $input

@return object

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type   | Description                                                       | Optional |
|----------|--------|-------------------------------------------------------------------|---------:|
| 'filter' | string | UID of Playlist                                                   |       NO |
| 'name'   | string | Playlist name                                                     |      YES |
| 'type'   | string | Playlist type 'public', 'private'                                 |      YES |
| 'items'  | string | comma-separated song_id's (replaces existing items with a new id) |      YES |
| 'tracks' | string | comma-separated playlisttrack numbers matched to 'items' in order |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/playlist_edit.json)

### playlist_delete

This deletes a playlist
@param array $input

@return object

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type   | Description     | Optional |
|----------|--------|-----------------|---------:|
| 'filter' | string | UID of Playlist |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/playlist_delete.json)

### playlist_add_song

This adds a song to a playlist. setting check=1 will not add duplicates to the playlist
@param array $input

@return object

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type    | Description                                               | Optional |
|----------|---------|-----------------------------------------------------------|---------:|
| 'filter' | string  | UID of Playlist                                           |       NO |
| 'song'   | string  | UID of song to add to playlist                            |       NO |
| 'check'  | boolean | 0, 1 Whether to check and ignore duplicates (default = 0) |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/playlist_add_song.json)

### playlist_remove_song

This remove a song from a playlist.
Previous versions required 'track' instead of 'song'.
@param array $input

@return object

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type    | Description                          | Optional |
|----------|---------|--------------------------------------|---------:|
| 'filter' | string  | UID of Playlist                      |       NO |
| 'song'   | string  | UID of song to remove from playlist  |      YES |
| 'track'  | integer | Track number to remove from playlist |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/playlist_remove_song.json)

### playlist_generate

Get a list of song JSON, indexes or id's based on some simple search criteria
'recent' will search for tracks played after 'Popular Threshold' days
'forgotten' will search for tracks played before 'Popular Threshold' days
'unplayed' added in 400002 for searching unplayed tracks

@param array $input
@return object

```JSON
"song": {}|"index": {}|"id": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type    | Description                                                      | Optional |
|----------|---------|------------------------------------------------------------------|---------:|
| 'mode'   | string  | 'recent', 'forgotten', 'unplayed', 'random' (default = 'random') |      YES |
| 'filter' | string  | string LIKE matched to song title                                |      YES |
| 'album'  | integer | $album_id                                                        |      YES |
| 'artist' | integer | $artist_id                                                       |      YES |
| 'flag'   | boolean | get flagged songs only 0, 1 (default = 0)                        |      YES |
| 'format' | string  | 'song', 'index','id' (default = 'song')                          |      YES |
| 'offset' | integer |                                                                  |      YES |
| 'limit'  | integer |                                                                  |      YES |

SONG

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/playlist_generate%20\(song\).json)

INDEX

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/playlist_generate%20\(index\).json)

ID

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/playlist_generate%20\(id\).json)

### shares

* **NEW** in 4.2.0

This searches the shares and returns... shares
@param array $input

@return object

```JSON
"share": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type    | Description                                   | Optional |
|----------|---------|-----------------------------------------------|---------:|
| 'filter' | string  | Filter results to match this string           |      YES |
| 'exact'  | boolean | 0, 1 boolean to match the exact filter string |      YES |
| 'offset' | integer |                                               |      YES |
| 'limit'  | integer |                                               |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/shares.json)

### share

* (MINIMUM_API_VERSION=420000)

Return shares by UID
@param array $input

@return object

```JSON
"share": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type   | Description                     | Optional |
|----------|--------|---------------------------------|---------:|
| 'filter' | string | UID of Share, returns song JSON |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/share.json)

### share_create

* (MINIMUM_API_VERSION=420000

Create a public url that can be used by anyone to stream media.
Takes the file id with optional description and expires parameters.

@param array $input

@return object

```JSON
"share": {}
```

@throws object

```JSON
"error": {}
```

| Input         | Type    | Description                                   | Optional |
|---------------|---------|-----------------------------------------------|---------:|
| 'filter'      | string  | UID of object you are sharing                 |       NO |
| 'type'        | string  | object_type                                   |       NO |
| 'description' | string  | description (will be filled for you if empty) |      YES |
| 'expires'     | integer | days to keep active                           |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/share_create.json)

### share_edit

* (MINIMUM_API_VERSION=420000

Update the description and/or expiration date for an existing share.
Takes the share id to update with optional description and expires parameters.

@param array $input

@return object

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input         | Type    | Description                  | Optional |
|---------------|---------|------------------------------|---------:|
| 'filter'      | string  | Alpha-numeric search term    |       NO |
| 'stream'      | boolean | 0, 1                         |      YES |
| 'download'    | boolean | 0, 1                         |      YES |
| 'expires'     | integer | number of days before expiry |      YES |
| 'description' | string  | update description           |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/share_edit.json)

### share_delete

* (MINIMUM_API_VERSION=420000

Delete an existing share.

@param array $input

@return object

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type   | Description            | Optional |
|----------|--------|------------------------|---------:|
| 'filter' | string | UID of Share to delete |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/share_delete.json)

### get_similar

* **NEW** in 4.2.0

Return similar artist id's or similar song ids compared to the input filter
@param array $input

@return object

```JSON
"song": {}|"artist": {}

```

@throws object

```JSON
"error": {}
```

| Input    | Type    | Description          | Optional |
|----------|---------|----------------------|---------:|
| 'type'   | string  | 'song' or 'artist'   |       NO |
| 'filter' | integer | artist id or song id |       NO |
| 'offset' | integer |                      |      YES |
| 'limit'  | integer |                      |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/get_similar.json)

### search_songs

This searches the songs and returns... songs
@param array $input

@return object

```JSON
"song": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type    | Description                         | Optional |
|----------|---------|-------------------------------------|---------:|
| 'filter' | string  | Filter results to match this string |       NO |
| 'offset' | integer |                                     |      YES |
| 'limit'  | integer |                                     |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/search_songs.json)

### videos

This returns video objects!
@param array $input

@return object

```JSON
"video": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type    | Description                                              | Optional |
|----------|---------|----------------------------------------------------------|---------:|
| 'filter' | string  | Filter results to match this string                      |      YES |
| 'exact'  | boolean | 0,1 if true filter is exact (=) rather than fuzzy (LIKE) |      YES |
| 'offset' | integer |                                                          |      YES |
| 'limit'  | integer |                                                          |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/videos.json)

### video

This returns a single video
@param array $input

@return object

```JSON
"video": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type   | Description                      | Optional |
|----------|--------|----------------------------------|---------:|
| 'filter' | string | UID of video, returns video JSON |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/video.json)

### podcasts

* **NEW** in 4.2.0

Get information about podcasts
@param array $input

@return object

```JSON
"podcast": {}
```

@throws object

```JSON
"error": {}
```

| Input     | Type    | Description                                   | Optional |
|-----------|---------|-----------------------------------------------|---------:|
| 'filter'  | string  |                                               |      YES |
| 'offset'  | integer |                                               |      YES |
| 'limit'   | integer |                                               |      YES |
| 'include' | string  | 'episodes' (include episodes in the response) |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/podcasts.json)

### podcast

* **NEW** in 4.2.0

Get the podcast from it's id.
@param array $input

@return object

```JSON
"podcast": {}
```

@throws object

```JSON
"error": {}
```

| Input     | Type   | Description                                   | Optional |
|-----------|--------|-----------------------------------------------|---------:|
| 'filter'  | string |                                               |       NO |
| 'include' | string | 'episodes' (include episodes in the response) |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/podcast.json)

### podcast_create

* **NEW** in 4.2.0

Create a podcast that can be used by anyone to stream media.
Takes the url and catalog parameters.
@param array $input

@return object

```JSON
"podcast": {}
```

@throws object

```JSON
"error": {}
```

| Input     | Type   | Description         | Optional |
|-----------|--------|---------------------|---------:|
| 'url'     | string | rss url for podcast |       NO |
| 'catalog' | string | podcast catalog     |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/podcast_create.json)

### podcast_edit

* **NEW** in 4.2.0

Update the description and/or expiration date for an existing podcast.
Takes the podcast id to update with optional description and expires parameters.
@param array $input

@return object

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input         | Type   | Description               | Optional |
|---------------|--------|---------------------------|---------:|
| 'filter'      | string | Alpha-numeric search term |       NO |
| 'feed'        | string | feed rss xml url          |      YES |
| 'title'       | string | title string              |      YES |
| 'website'     | string | source website url        |      YES |
| 'description' | string |                           |      YES |
| 'generator'   | string |                           |      YES |
| 'copyright'   | string |                           |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/podcast_edit.json)

### podcast_delete

* **NEW** in 4.2.0

Delete an existing podcast.
@param array $input

@return object

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type   | Description              | Optional |
|----------|--------|--------------------------|---------:|
| 'filter' | string | UID of podcast to delete |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/podcast_delete.json)

### podcast_episodes

* **NEW** in 4.2.0

This returns the episodes for a podcast
@param array $input

@return object

```JSON
"podcast_episode": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type    | Description    | Optional |
|----------|---------|----------------|---------:|
| 'filter' | string  | UID of podcast |       NO |
| 'offset' | integer |                |      YES |
| 'limit'  | integer |                |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/podcast_episodes.json)

### podcast_episode

* **NEW** in 4.2.0

Get the podcast_episode from it's id.
@param array $input

@return object

```JSON
"podcast_episode": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type   | Description               | Optional |
|----------|--------|---------------------------|---------:|
| 'filter' | string | podcast_episode ID number |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/podcast_episode.json)

### podcast_episode_delete

* **NEW** in 4.2.0

Delete an existing podcast_episode.
@param array $input

@return object

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type   | Description                      | Optional |
|----------|--------|----------------------------------|---------:|
| 'filter' | string | UID of podcast_episode to delete |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/podcast_episode_delete.json)

### stats

Get some items based on some simple search types and filters. (Random by default)
This method HAD partial backwards compatibility with older api versions but it has now been removed
@param array $input

@return object

```JSON
"song": {}|"album": {}|"artist": {}
```

@throws object

```JSON
"error": {}
```

| Input      | Type    | Description                                | Optional |
|------------|---------|--------------------------------------------|---------:|
| 'type'     | string  | 'song', 'album', 'artist'                  |       NO |
| 'filter'   | string  | 'newest', 'highest', 'frequent', 'recent', |      YES |
|            |         | 'forgotten', 'flagged', 'random'           |          |
| 'user_id'  | integer |                                            |      YES |
| 'username' | string  |                                            |      YES |
| 'offset'   | integer |                                            |      YES |
| 'limit'    | integer |                                            |      YES |

SONG

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/stats%20\(song\).json)

ARTIST

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/stats%20\(artist\).json)

ALBUM

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/stats%20\(album\).json)

### users

* **NEW** in develop

Get ids and usernames for your site
@param array $input

@return object

```JSON
"user": {}
```

@throws object

```JSON
"error": {}
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/users.json)

### user

This get an user public information
@param array $input

@return object

```JSON
"user": {}
```

@throws object

```JSON
"error": {}
```

| Input      | Type   | Description                         | Optional |
|------------|--------|-------------------------------------|---------:|
| 'username' | string | Username of the user to get details |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/user.json)

### user_create

Create a new user. (Requires the username, password and email.)
@param array $input

@return object

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input      | Type    | Description                | Optional |
|------------|---------|----------------------------|---------:|
| 'username' | string  | $username                  |       NO |
| 'password' | string  | hash('sha256', $password)) |       NO |
| 'email'    | string  | 'user@gmail.com'           |       NO |
| 'fullname' | string  |                            |      YES |
| 'disable'  | boolean | 0, 1                       |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/user_create.json)

### user_update

Update an existing user.
@param array $input

@return object

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/user_update.json)

### user_delete

Delete an existing user.
@param array $input

@return object

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input      | Type   | Description | Optional |
|------------|--------|-------------|---------:|
| 'username' | string |             |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/user_update.json)

### licenses

* **NEW** in 4.2.0

This returns licenses based on the specified filter
@param array $input

```JSON
"license": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type       | Description                                                      | Optional |
|----------|------------|------------------------------------------------------------------|---------:|
| 'filter' | string     | Filter results to match this string                              |      YES |
| 'exact'  | boolean    | 0,1 if true filter is exact (=) rather than fuzzy (LIKE)         |      YES |
| 'add'    | set_filter | ISO 8601 Date Format (2020-09-16)                                |      YES |
|          |            | Find objects with an 'add' date newer than the specified date    |          |
| 'update' | set_filter | ISO 8601 Date Format (2020-09-16)                                |      YES |
|          |            | Find objects with an 'update' time newer than the specified date |          |
| 'offset' | integer    |                                                                  |      YES |
| 'limit'  | integer    |                                                                  |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/licenses.json)

### license

* **NEW** in 4.2.0

This returns a single license
@param array $input

```JSON
"license": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type   | Description                          | Optional |
|----------|--------|--------------------------------------|---------:|
| 'filter' | string | UID of license, returns license JSON |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/license.json)

### license_songs

* **NEW** in 4.2.0

```JSON
"song": {}
```

@throws object

```JSON
"error": {}
```

This returns the songs for a license
@param array $input

| Input    | Type    | Description                       | Optional |
|----------|---------|-----------------------------------|---------:|
| 'filter' | string  | UID of license, returns song JSON |       NO |
| 'offset' | integer |                                   |      YES |
| 'limit'  | integer |                                   |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/license_songs.json)

### labels

* **NEW** in develop

This returns labels based on the specified filter
@param array $input

```JSON
"label": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type       | Description                                                      | Optional |
|----------|------------|------------------------------------------------------------------|---------:|
| 'filter' | string     | Filter results to match this string                              |      YES |
| 'exact'  | boolean    | 0,1 if true filter is exact (=) rather than fuzzy (LIKE)         |      YES |
| 'add'    | set_filter | ISO 8601 Date Format (2020-09-16)                                |      YES |
|          |            | Find objects with an 'add' date newer than the specified date    |          |
| 'update' | set_filter | ISO 8601 Date Format (2020-09-16)                                |      YES |
|          |            | Find objects with an 'update' time newer than the specified date |          |
| 'offset' | integer    |                                                                  |      YES |
| 'limit'  | integer    |                                                                  |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/labels.json)

### label

* **NEW** in develop

This returns a single label
@param array $input

```JSON
"label": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type   | Description                      | Optional |
|----------|--------|----------------------------------|---------:|
| 'filter' | string | UID of label, returns label JSON |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/label.json)

### label_artists

* **NEW** in develop

```JSON
"artist": {}
```

@throws object

```JSON
"error": {}
```

This returns the artists for a label
@param array $input

| Input    | Type    | Description                       | Optional |
|----------|---------|-----------------------------------|---------:|
| 'filter' | string  | UID of label, returns artist JSON |       NO |
| 'offset' | integer |                                   |      YES |
| 'limit'  | integer |                                   |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/label_artists.json)

### followers

This get an user followers
@param array $input

```JSON
"user": {}
```

@throws object

```JSON
"error": {}
```

| Input      | Type   | Description                                | Optional |
|------------|--------|--------------------------------------------|---------:|
| 'username' | string | Username of the user to get followers list |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/followers.json)

### following

This get the user list followed by an user
@param array $input

```JSON
"user": {}
```

@throws object

```JSON
"error": {}
```

| Input      | Type   | Description                                | Optional |
|------------|--------|--------------------------------------------|---------:|
| 'username' | string | Username of the user to get following list |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/following.json)

### toggle_follow

This follow/unfollow an user
@param array $input

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input      | Type   | Description                             | Optional |
|------------|--------|-----------------------------------------|---------:|
| 'username' | string | Username of the user to follow/unfollow |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/toggle_follow.json)

### last_shouts

```JSON
"shout": {}
```

@throws object

```JSON
"error": {}
```

This get the latest posted shouts
@param array $input

| Input      | Type    | Description                         | Optional |
|------------|---------|-------------------------------------|---------:|
| 'username' | string  | Get latest shouts for this username |      YES |
| 'limit'    | integer |                                     |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/last_shouts.json)

### rate

This rates a library item
@param array $input

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type    | Description                                   | Optional |
|----------|---------|-----------------------------------------------|---------:|
| 'type'   | string  | library item type, album, artist, song, video |       NO |
| 'id'     | integer | library item id                               |       NO |
| 'rating' | string  | rating between 0-5                            |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/rate.json)

### flag

This flags a library item as a favorite

* Setting flag to true (1) will set the flag
* Setting flag to false (0) will remove the flag
@param array $input

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input  | Type    | Description                        | Optional |
|--------|---------|------------------------------------|---------:|
| 'type' | string  | 'song', 'album', 'artist', 'video' |       NO |
| 'id'   | integer | $object_id                         |       NO |
| 'flag' | boolean | 0, 1                               |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/flag.json)

### record_play

Take a song_id and update the object_count and user_activity table with a play. This allows other sources to record play history to ampache
@param array $input

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type    | Description | Optional |
|----------|---------|-------------|---------:|
| 'id'     | integer | $object_id  |       NO |
| 'user'   | integer | $user_id    |       NO |
| 'client' | string  | $agent      |      YES |
| 'date'   | integer | UNIXTIME()  |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/record_play.json)

### scrobble

Search for a song using text info and then record a play if found. This allows other sources to record play history to ampache
@param array $input

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/scrobble.json)

### catalogs

* **NEW** in 4.2.0

This searches the catalogs and returns... catalogs
@param array $input

```JSON
"catalog": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type   | Description                        | Optional |
|----------|--------|------------------------------------|---------:|
| 'filter' | string | Catalog type: music, clip, tvshow, |      YES |
|          |        | movie, personal_video, podcast     |          |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/catalogs.json)

### catalog

* **NEW** in 4.2.0

Return catalog by UID
@param array $input

```JSON
"catalog": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type   | Description    | Optional |
|----------|--------|----------------|---------:|
| 'filter' | string | UID of Catalog |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/catalog.json)

### catalog_action

Kick off a catalog update or clean for the selected catalog
@param array $input

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input     | Type    | Description                       | Optional |
|-----------|---------|-----------------------------------|---------:|
| 'task'    | string  | 'add_to_catalog', 'clean_catalog' |       NO |
| 'catalog' | integer | $catalog_id                       |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/catalog_action%20\(clean_catalog\).json)

### catalog_file

* **NEW** in 4.2.0

Perform actions on local catalog files.
Single file versions of catalog add, clean, verify and remove (delete)
Make sure you remember to urlencode those file names!

@param array $input

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input     | Type    | Description                     | Optional |
|-----------|---------|---------------------------------|---------:|
| 'file'    | string  | FULL path to local file         |       NO |
| 'task'    | string  | 'add','clean','verify','remove' |       NO |
| 'catalog' | integer | $catalog_id                     |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/catalog_file.json)

### timeline

This get an user timeline
@param array $input

```JSON
"activity": {}
```

@throws object

```JSON
"error": {}
```

| Input      | Type    | Description                                       | Optional |
|------------|---------|---------------------------------------------------|---------:|
| 'username' | string  | Username of the user for whom to get the timeline |       NO |
| 'limit'    | integer |                                                   |      YES |
| 'since'    | integer | UNIXTIME()                                        |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/timeline.json)

### friends_timeline

This get current user friends timeline
@param array $input

```JSON
"activity": {}
```

@throws object

```JSON
"error": {}
```

| Input   | Type    | Description | Optional |
|---------|---------|-------------|---------:|
| 'limit' | integer |             |      YES |
| 'since' | integer | UNIXTIME()  |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/friends_timeline.json)

### update_from_tags

Update a single album, artist, song from the tag data
@param array $input

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input  | Type    | Description                     | Optional |
|--------|---------|---------------------------------|---------:|
| 'type' | string  | 'artist', 'album', 'song'       |       NO |
| 'id'   | integer | $artist_id, $album_id, $song_id |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/update_from_tags.json)

### update_artist_info

Update artist information and fetch similar artists from last.fm
Make sure lastfm_API_key is set in your configuration file
@param array $input

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input | Type    | Description | Optional |
|-------|---------|-------------|---------:|
| 'id'  | integer | $artist_id  |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/update_artist_info.json)

### update_art

Updates a single album, artist, song running the gather_art process
Doesn't overwrite existing art by default.
@param array $input

| Input       | Type    | Description       | Optional |
|-------------|---------|-------------------|---------:|
| 'id'        | integer | $object_id        |       NO |
| 'type'      | string  | 'song', 'podcast' |       NO |
| 'overwrite' | boolean | 0, 1              |      YES |

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/update_art.json)

### update_podcast

Sync and download new podcast episodes
@param array $input

| Input | Type    | Description | Optional |
|-------|---------|-------------|---------:|
| 'id'  | integer | $object_id  |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/update_podcast.json)

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

### user_preferences

* **NEW** in develop

Get your user preferences
@param array $input

```JSON
"preference": {}
```

@throws object

```JSON
"error": {}
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/user_preferences.json)

### user_preference

* **NEW** in develop

Get your user preference by name
@param array $input

```JSON
"preference": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type   | Description                                       | Optional |
|----------|--------|---------------------------------------------------|---------:|
| 'filter' | string | Preference name e.g ('notify_email', 'ajax_load') |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/user_preference.json)

### system_preferences

* **NEW** in develop

Get your server preferences
@param array $input

```JSON
"preference": {}
```

@throws object

```JSON
"error": {}
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/system_preferences.json)

### system_preference

* **NEW** in develop

Get your server preference by name
@param array $input

```JSON
"preference": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type   | Description                                       | Optional |
|----------|--------|---------------------------------------------------|---------:|
| 'filter' | string | Preference name e.g ('notify_email', 'ajax_load') |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/system_preferences.json)

### preference_create

* **NEW** in develop

Add a new preference to your server
@param array $input

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input         | Type    | Description                                             | Optional |
|---------------|---------|---------------------------------------------------------|---------:|
| 'filter'      | string  | Preference name e.g ('notify_email', 'ajax_load')       |       NO |
| 'type'        | string  | 'boolean', 'integer', 'string', 'special'               |       NO |
| 'default'     | mixed   | string or integer default value                         |       NO |
| 'category'    | string  | 'interface', 'internal', 'options', 'playlist',         |       NO |
|               |         | 'plugins', 'streaming', 'system'                        |          |
| 'description' | string  |                                                         |      YES |
| 'subcategory' | string  |                                                         |      YES |
| 'level'       | integer | access level required to change the value (default 100) |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/preference_create.json)

### preference_edit

* **NEW** in develop

Edit a preference value and apply to all users if allowed
@param array $input

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type   | Description                                       | Optional |
|----------|--------|---------------------------------------------------|---------:|
| 'filter' | string | Preference name e.g ('notify_email', 'ajax_load') |       NO |
| 'value'  | mixed   | (string|integer) Preference value                 |       NO |
| 'all'    | boolean | 0, 1 apply to all users                           |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/preference_edit.json)

### preference_delete

* **NEW** in develop

Delete a non-system preference by name
@param array $input

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type   | Description                                       | Optional |
|----------|--------|---------------------------------------------------|---------:|
| 'filter' | string | Preference name e.g ('notify_email', 'ajax_load') |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/preference_delete.json)

### bookmarks

* **NEW** in develop

Get information about bookmarked media this user is allowed to manage.
@param array $input

```JSON
"bookmark": {}
```

@throws object

```JSON
"error": {}
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/bookmarks.json)

### get_bookmark

* **NEW** in develop

Get the bookmark from it's object_id and object_type.
@param array $input

```JSON
"bookmark": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type   | Description                                       | Optional |
|----------|--------|---------------------------------------------------|---------:|
| 'filter' | string | object_id to find                                 |       NO |
| 'type'   | string | object_type  ('song', 'video', 'podcast_episode') |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/get_bookmark.json)

### bookmark_create

* **NEW** in develop

Create a placeholder for the current media that you can return to later.
@param array $input

```JSON
"bookmark": {}
```

@throws object

```JSON
"error": {}
```

| Input      | Type    | Description                                       | Optional |
|------------|---------|---------------------------------------------------|---------:|
| 'filter'   | string  | object_id to find                                 |       NO |
| 'type'     | string  | object_type  ('song', 'video', 'podcast_episode') |       NO |
| 'position' | integer | current track time in seconds                     |       NO |
| 'client'   | string  | Agent string. (Default: 'AmpacheAPI')             |      YES |
| 'date'     | integer | update time (Default: UNIXTIME())                 |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/bookmark_create.json)

### bookmark_edit

* **NEW** in develop

Edit a placeholder for the current media that you can return to later.
@param array $input

```JSON
"bookmark": {}
```

@throws object

```JSON
"error": {}
```

| Input      | Type    | Description                                       | Optional |
|------------|---------|---------------------------------------------------|---------:|
| 'filter'   | string  | object_id to find                                 |       NO |
| 'type'     | string  | object_type  ('song', 'video', 'podcast_episode') |       NO |
| 'position' | integer | current track time in seconds                     |       NO |
| 'client'   | string  | Agent string. (Default: 'AmpacheAPI')             |      YES |
| 'date'     | integer | update time (Default: UNIXTIME())                 |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/bookmark_edit.json)

### bookmark_delete

* **NEW** in develop

Delete an existing bookmark. (if it exists)
@param array $input

```JSON
"success": {}
```

@throws object

```JSON
"error": {}
```

| Input    | Type   | Description                                       | Optional |
|----------|--------|---------------------------------------------------|---------:|
| 'filter' | string | object_id to delete                               |       NO |
| 'type'   | string | object_type  ('song', 'video', 'podcast_episode') |       NO |
| 'client' | string | Agent string. (Default: 'AmpacheAPI')             |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/bookmark_delete)

## Binary Data Methods

Binary data methods are used for returning raw data to the user such as a image or stream.

### stream

Streams a given media file. Takes the file id in parameter with optional max bit rate, file format, time offset, size and estimate content length option.
@param array $input
@return file (HTTP 200 OK)

@throws (HTTP 400 Bad Request)
@throws (HTTP 404 Not Found)

| Input     | Type    | Description                 | Optional |
|-----------|---------|-----------------------------|---------:|
| 'id'      | integer | $object_id                  |       NO |
| 'type'    | string  | 'song', 'podcast'           |       NO |
| 'bitrate' | integer | max bitrate for transcoding |      YES |
| 'format'  | string  | 'mp3', 'ogg', 'raw', etc    |      YES |
| 'offset'  | integer | time offset in seconds      |      YES |
| 'length'  | boolean | 0, 1                        |      YES |

### download

Downloads a given media file. set format=raw to download the full file
@param array $input
@return file (HTTP 200 OK)

@throws (HTTP 400 Bad Request)
@throws (HTTP 404 Not Found)

| Input    | Type    | Description              | Optional |
|----------|---------|--------------------------|---------:|
| 'id'     | integer | $object_id               |       NO |
| 'type'   | string  | 'song', 'podcast'        |       NO |
| 'format' | string  | 'mp3', 'ogg', 'raw', etc |      YES |

### get_art

Get an art image.
@param array $input
@return image (HTTP 200 OK)

@throws (HTTP 400 Bad Request)
@throws (HTTP 404 Not Found)


| Input  | Type    | Description                                                | Optional |
|--------|---------|------------------------------------------------------------|---------:|
| 'id'   | integer | $object_id                                                 |       NO |
| 'type' | string  | 'song', 'artist', 'album', 'playlist', 'search', 'podcast' |       NO |

## Control Methods

### localplay

This is for controlling localplay
@param array $input

@return object

```JSON
"localplay": { "command": {} }
```

@throws object

```JSON
"error": {}
```

| Input     | Type    | Description                                                  | Optional |
|-----------|---------|--------------------------------------------------------------|---------:|
| 'command' | string  | 'next', 'prev', 'stop', 'play', 'pause', 'add', 'volume_up', |       NO |
|           |         | 'volume_down', 'volume_mute', 'delete_all', 'skip', 'status' |          |
| 'oid'     | integer | object_id                                                    |      YES |
| 'type'    | string  | 'Song', 'Video', 'Podcast_Episode', 'Channel',               |      YES |
|           |         | 'Broadcast', 'Democratic', 'Live_Stream'                     |          |
| 'clear'   | boolean | 0,1 Clear the current playlist before adding                 |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/localplay.json)

[Example (status)](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/localplay%20\(status\).json)

### democratic

This is for controlling democratic play (Songs only)
@param array $input

@return object

```JSON
"song": {}
```

@throws object

```JSON
"error": {}
```

* ACTION
  * method
    * vote
      * oid (Unique ID of the element you want to vote on)
    * devote
      * oid (Unique ID of the element you want to vote on)
    * playlist (Returns an array of song items with an additional \<vote>[VOTE COUNT]\</vote> element)
    * play (Returns the URL for playing democratic play)

| Input    | Type    | Description                  | Optional |
|----------|---------|------------------------------|---------:|
| 'oid'    | integer | UID of Song object           |       NO |
| 'method' | string  | vote, devote, playlist, play |       NO |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/democratic%20\(play\).json)
