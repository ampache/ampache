---
title: "API 5 JSON Methods"
metaTitle: "API 5 JSON Methods"
metaDescription: "API documentation"
---

Let's go through come calls and examples that you can do for each JSON method.

With the exception of Binary methods, all responses will return a HTTP 200 response.

Also remember that Binary data methods will not return JSON; just the file/data you have requested.

Binary methods will also return:

* HTTP 400 responses for a bad or incomplete request
* HTTP 404 responses where the requests data was not found

## Auth Methods

Auth methods are used for authenticating or checking the status of your session in an Ampache server

### handshake

This is the function that handles verifying a new handshake Takes a timestamp, auth key, and username.

| Input       | Type    | Description                                              | Optional |
|-------------|---------|----------------------------------------------------------|----------|
| 'auth'      | string  | $passphrase (Timestamp . Password SHA hash) OR (API Key) | NO       |
| 'user'      | string  | $username (Required if login/password authentication)    | YES      |
| 'timestamp' | integer | UNIXTIME() The timestamp used in seed of password hash   | YES      |
|             |         | (Required if login/password authentication)              |          |
| 'version'   | string  | $version (API Version that the application understands)  | YES      |

* return object

```JSON
"auth": "",
"api": "",
"session_expire": "",
"update": "",
"add": "",
"clean": "",
"songs": 0,
"albums": 0,
"artists": 0,
"genres": 0,
"playlists": 0,
"users": 0,
"catalogs": 0,
"videos": 0,
"podcasts": 0,
"podcast_episodes": 0,
"shares": 0,
"licenses": 0,
"live_streams": 0,
"labels": 0
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/handshake.json)

### ping

This can be called without being authenticated, it is useful for determining if what the status of the server is, and what version it is running/compatible with

| Input     | Type   | Description                                                                | Optional |
|-----------|--------|----------------------------------------------------------------------------|----------|
| 'auth'    | string | (Session ID) returns version information and extends the session if passed | YES      |
| 'version' | string | $version (API Version that the application understands)                    | YES      |

* return object

```JSON
"session_expire": "",
"server": "",
"version": "",
"compatible": "",
"auth": "",
"api": "",
"update": "",
"add": "",
"clean": "",
"songs": 0,
"albums": 0,
"artists": 0,
"genres": 0,
"playlists": 0,
"users": 0,
"catalogs": 0,
"videos": 0,
"podcasts": 0,
"podcast_episodes": 0,
"shares": 0,
"licenses": 0,
"live_streams": 0,
"labels": 0
```

* throws array

```JSON
"server": "",
"version": "",
"compatible": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/ping.json)

### goodbye

Destroy a session using the auth parameter.

| Input  | Type   | Description                                    | Optional |
|--------|--------|------------------------------------------------|----------|
| 'auth' | string | (Session ID) destroys the session if it exists | NO       |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/goodbye.json)

## Non-Data Methods

These methods take no parameters beyond your auth key to return information

### system_update

Check Ampache for updates and run the update if there is one.

**ACCESS REQUIRED:** 100 (Admin)

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/system_update.json)

### system_preferences

Get your server preferences

**ACCESS REQUIRED:** 100 (Admin)

* return array

```JSON
"preference": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/system_preferences.json)

### users

Get ids and usernames for your site

* return array

```JSON
"user": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/users.json)

### user_preferences

Get your user preferences

* return array

```JSON
"preference": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/user_preferences.json)

### bookmarks

Get information about bookmarked media this user is allowed to manage.

* return array

```JSON
"bookmark": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/bookmarks.json)

## Data Methods

Data methods require additional information and parameters to return information

### get_indexes

This takes a collection of inputs and returns ID + name for the object type

| Input         | Type       | Description                                                                   | Optional |
|---------------|------------|-------------------------------------------------------------------------------|----------|
| 'type'        | string     | 'song', 'album', 'artist', 'album_artist', 'playlist',                        | NO       |
|               |            | 'podcast', 'podcast_episode', 'live_stream'                                   |          |
| 'filter'      | string     | Value is Alpha Match for returned results, may be more than one letter/number | YES      |
| 'add'         | set_filter | ISO 8601 Date Format (2020-09-16)                                             | YES      |
|               |            | Find objects with an 'add' date newer than the specified date                 |          |
| 'update'      | set_filter | ISO 8601 Date Format (2020-09-16)                                             | YES      |
|               |            | Find objects with an 'update' time newer than the specified date              |          |
| 'include'     | boolean    | 0,1 include songs in a playlist or episodes in a podcast                      | YES      |
| 'offset'      | integer    | Return results starting from this index position                              | YES      |
| 'limit'       | integer    | Maximum number of results to return                                           | YES      |
| 'hide_search' | integer    | 0,1, if true do not include searches/smartlists in the result                 | YES      |

* return array

```JSON
"song": []|"album": []|"artist": []|"playlist": []|"podcast": []

```

* throws object

```JSON
"error": ""
```

SONGS [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/get_indexes%20\(song\).json)

ARTIST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/get_indexes%20\(artist\).json)

ALBUM [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/get_indexes%20\(album\).json)

PLAYLIST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/get_indexes%20\(playlist\).json)

### advanced_search

#### Using advanced_search

Perform an advanced search given passed rules. This works in a similar way to the web/UI search pages.
You can pass multiple rules as well as joins to create in depth search results

Rules must be sent in groups of 3 using an int (starting from 1) to designate which rules are combined.
Use operator ('and', 'or') to choose whether to join or separate each rule when searching.

Refer to the [Advanced Search](https://ampache.org/api/api-advanced-search) page for details about creating searches.

**NOTE** the rules part can be confusing but essentially you can include as many 'arrays' of rules as you want.
Just add 1 to the rule value to create a new group of rules.

* Mandatory Rule Values
  * rule_1
  * rule_1_operator
  * rule_1_input
* Optional (Metadata searches **only**)
  * rule_1_subtype

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|----------|
| operator | string  | 'and','or' (whether to match one rule or all)    | NO       |
| rule_*   | array   | [rule_1,rule_1_operator,rule_1_input],           | NO       |
| rule_*   | array   | [rule_2,rule_2_operator,rule_2_input], [etc]     | YES      |
| type     | string  | 'song', 'album', 'artist', 'playlist',           | NO       |
|          |         | 'label', 'user', 'video'                         |          |
| random   | boolean | 0, 1 (random order of results; default to 0)     | YES      |
| 'offset' | integer | Return results starting from this index position | YES      |
| 'limit'  | integer | Maximum number of results to return              | YES      |

* return array

```JSON
"song": []|"album": []|"artist": []|"playlist": []|"label": []|"user": []|"video": []
```

* throws object

```JSON
"error": ""
```

SONG [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/advanced_search%20\(song\).json)

ARTIST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/advanced_search%20\(artist\).json)

ALBUM [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/advanced_search%20\(album\).json)

### artists

This takes a collection of inputs and returns artist objects.

| Input          | Type       | Description                                                      | Optional |
|----------------|------------|------------------------------------------------------------------|----------|
| 'filter'       | string     | Filter results to match this string                              | YES      |
| 'exact'        | boolean    | 0,1 if true filter is exact (=) rather than fuzzy (LIKE)         | YES      |
| 'add'          | set_filter | ISO 8601 Date Format (2020-09-16)                                | YES      |
|                |            | Find objects with an 'add' date newer than the specified date    |          |
| 'update'       | set_filter | ISO 8601 Date Format (2020-09-16)                                | YES      |
|                |            | Find objects with an 'update' time newer than the specified date |          |
| 'include'      | string     | 'albums', 'songs' and will include the corresponding JSON        | YES      |
| 'album_artist' | boolean    | 0,1 if true filter for album artists only                        | YES      |
| 'offset'       | integer    | Return results starting from this index position                 | YES      |
| 'limit'        | integer    | Maximum number of results to return                              | YES      |

* return array

```JSON
"artist": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/artists.json)

### artist

This returns a single artist based on the UID of said artist

| Input     | Type   | Description                                                                         | Optional |
|-----------|--------|-------------------------------------------------------------------------------------|----------|
| 'filter'  | string | UID of Artist, returns artist JSON                                                  | NO       |
| 'include' | string | 'albums', 'songs' and will include the corresponding JSON nested in the artist JSON | YES      |

* return object

```JSON
"id": "",
"name": "",
"albums": [],
"albumcount": 0,
"songs": [],
"songcount": 0,
"genre": [],
"art": "",
"flag": 0,
"preciserating": 0,
"rating": 0,
"averagerating": 0,
"mbid": "",
"summary": "",
"time": 0,
"yearformed": 0,
"placeformed": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/artist.json)

### artist_albums

This returns the albums of an artist

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|----------|
| 'filter' | string  | UID of Artist, returns Album JSON                | NO       |
| 'offset' | integer | Return results starting from this index position | YES      |
| 'limit'  | integer | Maximum number of results to return              | YES      |

* return array

```JSON
album": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/artist_albums.json)

### artist_songs

This returns the songs of the specified artist

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|----------|
| 'filter' | string  | UID of Artist, returns Song JSON                 | NO       |
| 'top50'  | boolean | 0,1, if true filter to the artist top 50         | YES      |
| 'offset' | integer | Return results starting from this index position | YES      |
| 'limit'  | integer | Maximum number of results to return              | YES      |

* return array

```JSON
"song": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/artist_songs.json)

### albums

This returns albums based on the provided search filters

| Input     | Type       | Description                                                      | Optional |
|-----------|------------|------------------------------------------------------------------|----------|
| 'filter'  | string     | Filter results to match this string                              | YES      |
| 'exact'   | boolean    | 0,1 if true filter is exact (=) rather than fuzzy (LIKE)         | YES      |
| 'add'     | set_filter | ISO 8601 Date Format (2020-09-16)                                | YES      |
|           |            | Find objects with an 'add' date newer than the specified date    |          |
| 'update'  | set_filter | ISO 8601 Date Format (2020-09-16)                                | YES      |
|           |            | Find objects with an 'update' time newer than the specified date |          |
| 'offset'  | integer    | Return results starting from this index position                 | YES      |
| 'limit'   | integer    | Maximum number of results to return                              | YES      |
| 'include' | string     | 'albums', 'songs' will include nested in the album JSON          | YES      |

* return array

```JSON
"album": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/albums.json)

### album

This returns a single album based on the UID provided

| Input     | Type   | Description                                                          | Optional |
|-----------|--------|----------------------------------------------------------------------|----------|
| 'filter'  | string | UID of Album, returns album JSON                                     | NO       |
| 'include' | string | 'songs' will include the corresponding JSON nested in the album JSON | YES      |

* return object

```JSON
"id": "",
"name": "",
"artist": {},
"time": 0,
"year": 0,
"tracks": [],
"songcount": 0,
"diskcount": 0,
"type": "",
"genre": [],
"art": "",
"flag": 0,
"preciserating": 0.0,
"rating": 0.0,
"averagerating": 0.0,
"mbid": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/album.json)

### album_songs

This returns the songs of a specified album

| Input    | Type    | Description                                        | Optional |
|----------|---------|----------------------------------------------------|----------|
| 'filter' | string  | UID of Album, returns song JSON                    | NO       |
| 'exact'  | boolean | 0,1 if true don't group songs from different disks | YES      |
| 'offset' | integer | Return results starting from this index position   | YES      |
| 'limit'  | integer | Maximum number of results to return                | YES      |

* return array

```JSON
"song": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/album_songs.json)

### genres

This returns the genres (Tags) based on the specified filter

| Input    | Type    | Description                                              | Optional |
|----------|---------|----------------------------------------------------------|----------|
| 'filter' | string  | Filter results to match this string                      | YES      |
| 'exact'  | boolean | 0,1 if true filter is exact (=) rather than fuzzy (LIKE) | YES      |
| 'offset' | integer | Return results starting from this index position         | YES      |
| 'limit'  | integer | Maximum number of results to return                      | YES      |

* return array

```JSON
"genre": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/genres.json)

### genre

This returns a single genre based on UID

| Input    | Type   | Description                      | Optional |
|----------|--------|----------------------------------|----------|
| 'filter' | string | UID of genre, returns genre JSON | NO       |

* return object

```JSON
"id": "",
"name": "",
"albums": 0,
"artists": 0,
"songs": 0,
"videos": 0,
"playlists": 0,
"live_streams": 0
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/genre.json)

### genre_artists

This returns the artists associated with the genre in question as defined by the UID

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|----------|
| 'filter' | string  | UID of genre, returns artist JSON                | YES      |
| 'offset' | integer | Return results starting from this index position | YES      |
| 'limit'  | integer | Maximum number of results to return              | YES      |

* return array

```JSON
"artist": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/genre_artists.json)

### genre_albums

This returns the albums associated with the genre in question

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|----------|
| 'filter' | string  | UID of genre, returns album JSON                 | YES      |
| 'offset' | integer | Return results starting from this index position | YES      |
| 'limit'  | integer | Maximum number of results to return              | YES      |

* return array

```JSON
"album": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/genre_albums.json)

### genre_songs

returns the songs for this genre

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|----------|
| 'filter' | string  | UID of genre, returns song JSON                  | YES      |
| 'offset' | integer | Return results starting from this index position | YES      |
| 'limit'  | integer | Maximum number of results to return              | YES      |

* return array

```JSON
"song": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/genre_songs.json)

### songs

Returns songs based on the specified filter

| Input    | Type       | Description                                                      | Optional |
|----------|------------|------------------------------------------------------------------|----------|
| 'filter' | string     | Filter results to match this string                              | YES      |
| 'exact'  | boolean    | 0,1 if true filter is exact (=) rather than fuzzy (LIKE)         | YES      |
| 'add'    | set_filter | ISO 8601 Date Format (2020-09-16)                                | YES      |
|          |            | Find objects with an 'add' date newer than the specified date    |          |
| 'update' | set_filter | ISO 8601 Date Format (2020-09-16)                                | YES      |
|          |            | Find objects with an 'update' time newer than the specified date |          |
| 'offset' | integer    | Return results starting from this index position                 | YES      |
| 'limit'  | integer    | Maximum number of results to return                              | YES      |

* return array

```JSON
"song": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/songs.json)

### song

returns a single song

| Input    | Type   | Description                    | Optional |
|----------|--------|--------------------------------|----------|
| 'filter' | string | UID of Song, returns song JSON | NO       |

* return object

```JSON
"id": "",
"title": "",
"name": "",
"artist": {},
"album": {},
"genre": [],
"albumartist": {},
"filename": "",
"track": 0,
"playlisttrack": 0,
"time": 0,
"year": 0,
"bitrate": 0,
"rate": 0,
"mode": "",
"mime": "",
"url": "",
"size": 0,
"mbid": "0",
"album_mbid": "",
"artist_mbid": "",
"albumartist_mbid": ",
"art": "",
"flag": 0,
"preciserating": 0.0,
"rating": 0.0,
"averagerating": 0.0,
"playcount": 0,
"catalog": 0,
"composer": "",
"channels": null,
"comment": "",
"publisher": "",
"language": "",
"replaygain_album_gain": 0.000000,
"replaygain_album_peak": 0.000000,
"replaygain_track_gain": 0.000000,
"replaygain_track_peak": 0.000000
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/song.json)

### song_delete

Delete an existing song. (if you are allowed to)

| Input    | Type   | Description           | Optional |
|----------|--------|-----------------------|----------|
| 'filter' | string | UID of song to delete | NO       |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/song_delete.json)

### url_to_song

This takes a url and returns the song object in question

| Input | Type   | Description                                                    | Optional |
|-------|--------|----------------------------------------------------------------|----------|
| 'url' | string | Full Ampache URL from server, translates back into a song JSON | NO       |

* return object

```JSON
"id": "",
"title": "",
"name": "",
"artist": {},
"album": {},
"genre": [],
"albumartist": {},
"filename": "",
"track": 0,
"playlisttrack": 0,
"time": 0,
"year": 0,
"bitrate": 0,
"rate": 0,
"mode": "",
"mime": "",
"url": "",
"size": 0,
"mbid": "",
"album_mbid": "",
"artist_mbid": "",
"albumartist_mbid": "",
"art": "",
"flag": 0,
"preciserating": 0.0,
"rating": 0.0,
"averagerating": 0.0,
"playcount": 0,
"catalog": 0,
"composer": "",
"channels": null,
"comment": "",
"publisher": "",
"language": "",
"replaygain_album_gain": 0.000000,
"replaygain_album_peak": 0.000000,
"replaygain_track_gain": 0.000000,
"replaygain_track_peak": 0.000000
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/url_to_song.json)

### playlists

This returns playlists based on the specified filter

| Input         | Type       | Description                                                      | Optional |
|---------------|------------|------------------------------------------------------------------|----------|
| 'filter'      | string     | Filter results to match this string                              | YES      |
| 'exact'       | boolean    | 0,1 if true filter is exact (=) rather than fuzzy (LIKE)         | YES      |
| 'add'         | set_filter | ISO 8601 Date Format (2020-09-16)                                | YES      |
|               |            | Find objects with an 'add' date newer than the specified date    |          |
| 'update'      | set_filter | ISO 8601 Date Format (2020-09-16)                                | YES      |
|               |            | Find objects with an 'update' time newer than the specified date |          |
| 'offset'      | integer    | Return results starting from this index position                 | YES      |
| 'limit'       | integer    | Maximum number of results to return                              | YES      |
| 'hide_search' | integer    | 0,1, if true do not include searches/smartlists in the result    | YES      |
| 'show_dupes'  | integer    | 0,1, if true if true ignore 'api_hide_dupe_searches' setting     | YES      |

* return array

```JSON
"playlist": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/playlists.json)

### playlist

This returns a single playlist

| Input    | Type   | Description                            | Optional |
|----------|--------|----------------------------------------|----------|
| 'filter' | string | UID of playlist, returns playlist JSON | NO       |

* return array

```JSON
"playlist": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/playlist.json)

### playlist_songs

This returns the songs for a playlist

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|----------|
| 'filter' | string  | UID of Playlist, returns song JSON               | NO       |
| 'offset' | integer | Return results starting from this index position | YES      |
| 'limit'  | integer | Maximum number of results to return              | YES      |

* return array

```JSON
"song": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/playlist_songs.json)

### playlist_create

This create a new playlist and return it

| Input  | Type   | Description                       | Optional |
|--------|--------|-----------------------------------|----------|
| 'name' | string | Playlist name                     | NO       |
| 'type' | string | Playlist type 'public', 'private' | YES      |

* return array

```JSON
"playlist": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/playlist_create.json)

### playlist_edit

This modifies name and type of a playlist
Previously name and type were mandatory while filter wasn't. this has been reversed.

| Input    | Type   | Description                                                       | Optional |
|----------|--------|-------------------------------------------------------------------|----------|
| 'filter' | string | UID of Playlist                                                   | NO       |
| 'name'   | string | Playlist name                                                     | YES      |
| 'type'   | string | Playlist type 'public', 'private'                                 | YES      |
| 'owner'  | string | Change playlist owner to the user id (-1 = System playlist)       | YES      |
| 'items'  | string | comma-separated song_id's (replaces existing items with a new id) | YES      |
| 'tracks' | string | comma-separated playlisttrack numbers matched to 'items' in order | YES      |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/playlist_edit.json)

### playlist_delete

This deletes a playlist

| Input    | Type   | Description     | Optional |
|----------|--------|-----------------|----------|
| 'filter' | string | UID of Playlist | NO       |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/playlist_delete.json)

### playlist_add_song

This adds a song to a playlist. setting check=1 will not add duplicates to the playlist

| Input    | Type    | Description                                               | Optional |
|----------|---------|-----------------------------------------------------------|----------|
| 'filter' | string  | UID of Playlist                                           | NO       |
| 'song'   | string  | UID of song to add to playlist                            | NO       |
| 'check'  | boolean | 0, 1 Whether to check and ignore duplicates (default = 0) | YES      |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/playlist_add_song.json)

### playlist_remove_song

This remove a song from a playlist.
Previous versions required 'track' instead of 'song'.

| Input    | Type    | Description                          | Optional |
|----------|---------|--------------------------------------|----------|
| 'filter' | string  | UID of Playlist                      | NO       |
| 'song'   | string  | UID of song to remove from playlist  | YES      |
| 'track'  | integer | Track number to remove from playlist | YES      |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/playlist_remove_song.json)

### playlist_generate

Get a list of song JSON, indexes or id's based on some simple search criteria
'recent' will search for tracks played after 'Popular Threshold' days
'forgotten' will search for tracks played before 'Popular Threshold' days
'unplayed' added in 400002 for searching unplayed tracks

| Input    | Type    | Description                                                      | Optional |
|----------|---------|------------------------------------------------------------------|----------|
| 'mode'   | string  | 'recent', 'forgotten', 'unplayed', 'random' (default = 'random') | YES      |
| 'filter' | string  | string LIKE matched to song title                                | YES      |
| 'album'  | integer | $album_id                                                        | YES      |
| 'artist' | integer | $artist_id                                                       | YES      |
| 'flag'   | boolean | get flagged songs only 0, 1 (default = 0)                        | YES      |
| 'format' | string  | 'song', 'index','id' (default = 'song')                          | YES      |
| 'offset' | integer | Return results starting from this index position                 | YES      |
| 'limit'  | integer | Maximum number of results to return                              | YES      |

* return array

```JSON
"song": []|"index": []|"id": []
```

* throws object

```JSON
"error": ""
```

SONG [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/playlist_generate%20\(song\).json)

INDEX [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/playlist_generate%20\(index\).json)

ID [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/playlist_generate%20\(id\).json)

### shares

This searches the shares and returns... shares

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|----------|
| 'filter' | string  | Filter results to match this string              | YES      |
| 'exact'  | boolean | 0, 1 boolean to match the exact filter string    | YES      |
| 'offset' | integer | Return results starting from this index position | YES      |
| 'limit'  | integer | Maximum number of results to return              | YES      |

* return array

```JSON
"share": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/shares.json)

### share

Return shares by UID

| Input    | Type   | Description                     | Optional |
|----------|--------|---------------------------------|----------|
| 'filter' | string | UID of Share, returns song JSON | NO       |

* return object

```JSON
"id": "",
"name": "",
"owner": "",
"allow_stream": 0,
"allow_download": 0,
"creation_date": "",
"lastvisit_date": "",
"object_type": "",
"object_id": "",
"expire_days": 0,
"max_counter": 0,
"counter": 0,
"secret": "",
"public_url": "",
"description": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/share.json)

### share_create

Create a public url that can be used by anyone to stream media.
Takes the file id with optional description and expires parameters.

| Input         | Type    | Description                                   | Optional |
|---------------|---------|-----------------------------------------------|----------|
| 'filter'      | string  | UID of object you are sharing                 | NO       |
| 'type'        | string  | object_type                                   | NO       |
| 'description' | string  | description (will be filled for you if empty) | YES      |
| 'expires'     | integer | days to keep active                           | YES      |

* return array

```JSON
"share": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/share_create.json)

### share_edit

Update the description and/or expiration date for an existing share.
Takes the share id to update with optional description and expires parameters.

| Input         | Type    | Description                  | Optional |
|---------------|---------|------------------------------|----------|
| 'filter'      | string  | Alpha-numeric search term    | NO       |
| 'stream'      | boolean | 0, 1                         | YES      |
| 'download'    | boolean | 0, 1                         | YES      |
| 'expires'     | integer | number of days before expiry | YES      |
| 'description' | string  | update description           | YES      |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/share_edit.json)

### share_delete

Delete an existing share.

| Input    | Type   | Description            | Optional |
|----------|--------|------------------------|----------|
| 'filter' | string | UID of Share to delete | NO       |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/share_delete.json)

### get_similar

Return similar artist id's or similar song ids compared to the input filter

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|----------|
| 'type'   | string  | 'song' or 'artist'                               | NO       |
| 'filter' | integer | artist id or song id                             | NO       |
| 'offset' | integer | Return results starting from this index position | YES      |
| 'limit'  | integer | Maximum number of results to return              | YES      |

* return array

```JSON
"song": []|"artist": []

```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/get_similar.json)

### search_songs

This searches the songs and returns... songs

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|----------|
| 'filter' | string  | Filter results to match this string              | NO       |
| 'offset' | integer | Return results starting from this index position | YES      |
| 'limit'  | integer | Maximum number of results to return              | YES      |

* return array

```JSON
"song": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/search_songs.json)

### videos

This returns video objects!

| Input    | Type    | Description                                              | Optional |
|----------|---------|----------------------------------------------------------|----------|
| 'filter' | string  | Filter results to match this string                      | YES      |
| 'exact'  | boolean | 0,1 if true filter is exact (=) rather than fuzzy (LIKE) | YES      |
| 'offset' | integer | Return results starting from this index position         | YES      |
| 'limit'  | integer | Maximum number of results to return                      | YES      |

* return array

```JSON
"video": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/videos.json)

### video

This returns a single video

| Input    | Type   | Description                      | Optional |
|----------|--------|----------------------------------|----------|
| 'filter' | string | UID of video, returns video JSON | NO       |

* return object

```JSON
"id": "",
"title": "",
"mime": "",
"resolution": "",
"size": 0,
"genre": [],
"url": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/video.json)

### podcasts

Get information about podcasts

| Input     | Type    | Description                                                                   | Optional |
|-----------|---------|-------------------------------------------------------------------------------|----------|
| 'filter'  | string  | Value is Alpha Match for returned results, may be more than one letter/number | YES      |
| 'offset'  | integer | Return results starting from this index position                              | YES      |
| 'limit'   | integer | Maximum number of results to return                                           | YES      |
| 'include' | string  | 'episodes' (include episodes in the response)                                 | YES      |

* return array

```JSON
"podcast": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/podcasts.json)

### podcast

Get the podcast from it's id.

| Input     | Type   | Description                                   | Optional |
|-----------|--------|-----------------------------------------------|----------|
| 'filter'  | string | UID of podcast, returns podcast JSON          | NO       |
| 'include' | string | 'episodes' (include episodes in the response) | YES      |

* return object

```JSON
"id": "",
"name": "",
"description": "",
"language": "",
"copyright": "",
"feed_url": "",
"generator": "",
"website": "",
"build_date": "",
"sync_date": "",
"public_url": "",
"podcast_episode": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/podcast.json)

### podcast_create

Create a podcast that can be used by anyone to stream media.
Takes the url and catalog parameters.

**ACCESS REQUIRED:** 75 (Catalog Manager)

| Input     | Type   | Description         | Optional |
|-----------|--------|---------------------|----------|
| 'url'     | string | rss url for podcast | NO       |
| 'catalog' | string | podcast catalog     | NO       |

* return array

```JSON
"podcast": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/podcast_create.json)

### podcast_edit

Update the description and/or expiration date for an existing podcast.
Takes the podcast id to update with optional description and expires parameters.

**ACCESS REQUIRED:** 50 (Content Manager)

| Input         | Type   | Description               | Optional |
|---------------|--------|---------------------------|----------|
| 'filter'      | string | Alpha-numeric search term | NO       |
| 'feed'        | string | feed rss xml url          | YES      |
| 'title'       | string | title string              | YES      |
| 'website'     | string | source website url        | YES      |
| 'description' | string |                           | YES      |
| 'generator'   | string |                           | YES      |
| 'copyright'   | string |                           | YES      |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/podcast_edit.json)

### podcast_delete

Delete an existing podcast.

**ACCESS REQUIRED:** 75 (Catalog Manager)

| Input    | Type   | Description              | Optional |
|----------|--------|--------------------------|----------|
| 'filter' | string | UID of podcast to delete | NO       |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/podcast_delete.json)

### podcast_episodes

This returns the episodes for a podcast

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|----------|
| 'filter' | string  | UID of podcast                                   | NO       |
| 'offset' | integer | Return results starting from this index position | YES      |
| 'limit'  | integer | Maximum number of results to return              | YES      |

* return array

```JSON
"podcast_episode": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/podcast_episodes.json)

### podcast_episode

Get the podcast_episode from it's id.

| Input    | Type   | Description               | Optional |
|----------|--------|---------------------------|----------|
| 'filter' | string | podcast_episode ID number | NO       |

* return array

```JSON
"podcast_episode": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/podcast_episode.json)

### podcast_episode_delete

Delete an existing podcast_episode.

| Input    | Type   | Description                      | Optional |
|----------|--------|----------------------------------|----------|
| 'filter' | string | UID of podcast_episode to delete | NO       |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/podcast_episode_delete.json)

### stats

Get some items based on some simple search types and filters. (Random by default)
This method **HAD** partial backwards compatibility with older api versions but it has now been removed

| Input      | Type    | Description                                      | Optional |
|------------|---------|--------------------------------------------------|----------|
| 'type'     | string  | 'song', 'album', 'artist', 'video',              | NO       |
|            |         | 'playlist', 'podcast', 'podcast_episode'         |          |
| 'filter'   | string  | 'newest', 'highest', 'frequent', 'recent',       | YES      |
|            |         | 'forgotten', 'flagged', 'random'                 |          |
| 'user_id'  | integer |                                                  | YES      |
| 'username' | string  |                                                  | YES      |
| 'offset'   | integer | Return results starting from this index position | YES      |
| 'limit'    | integer | Maximum number of results to return              | YES      |

* return array

```JSON
"song": []|"album": []|"artist": []
```

* throws object

```JSON
"error": ""
```

SONG [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/stats%20\(song\).json)

ARTIST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/stats%20\(artist\).json)

ALBUM [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/stats%20\(album\).json)

### user

This get an user public information

| Input      | Type   | Description                         | Optional |
|------------|--------|-------------------------------------|----------|
| 'username' | string | Username of the user to get details | NO       |

* return array

```JSON
"user": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/user.json)

### user_create

Create a new user. (Requires the username, password and email.)

**ACCESS REQUIRED:** 100 (Admin)

| Input      | Type    | Description                | Optional |
|------------|---------|----------------------------|----------|
| 'username' | string  | $username                  | NO       |
| 'password' | string  | hash('sha256', $password)) | NO       |
| 'email'    | string  | 'user@gmail.com'           | NO       |
| 'fullname' | string  |                            | YES      |
| 'disable'  | boolean | 0, 1                       | YES      |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/user_create.json)

### user_update

Update an existing user.

**ACCESS REQUIRED:** 100 (Admin)

| Input        | Type    | Description                | Optional |
|--------------|---------|----------------------------|----------|
| 'username'   | string  | $username                  | NO       |
| 'password'   | string  | hash('sha256', $password)) | YES      |
| 'email'      | string  | 'user@gmail.com'           | YES      |
| 'fullname'   | string  |                            | YES      |
| 'website'    | string  |                            | YES      |
| 'state'      | string  |                            | YES      |
| 'city'       | string  |                            | YES      |
| 'disable'    | boolean | 0, 1                       | YES      |
| 'maxbitrate' | string  |                            | YES      |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/user_update.json)

### user_delete

Delete an existing user.

**ACCESS REQUIRED:** 100 (Admin)

| Input      | Type   | Description | Optional |
|------------|--------|-------------|----------|
| 'username' | string |             | NO       |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/user_update.json)

### licenses

This returns licenses based on the specified filter

| Input    | Type       | Description                                                      | Optional |
|----------|------------|------------------------------------------------------------------|----------|
| 'filter' | string     | Filter results to match this string                              | YES      |
| 'exact'  | boolean    | 0,1 if true filter is exact (=) rather than fuzzy (LIKE)         | YES      |
| 'add'    | set_filter | ISO 8601 Date Format (2020-09-16)                                | YES      |
|          |            | Find objects with an 'add' date newer than the specified date    |          |
| 'update' | set_filter | ISO 8601 Date Format (2020-09-16)                                | YES      |
|          |            | Find objects with an 'update' time newer than the specified date |          |
| 'offset' | integer    |                                                                  | YES      |
| 'limit'  | integer    |                                                                  | YES      |

* return array

```JSON
"license": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/licenses.json)

### license

This returns a single license

| Input    | Type   | Description                          | Optional |
|----------|--------|--------------------------------------|----------|
| 'filter' | string | UID of license, returns license JSON | NO       |

* return object

```JSON
"id": "",
"name": "",
"description": "",
"external_link": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/license.json)

### license_songs

This returns the songs for a license

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|----------|
| 'filter' | string  | UID of license, returns song JSON                | NO       |
| 'offset' | integer | Return results starting from this index position | YES      |
| 'limit'  | integer | Maximum number of results to return              | YES      |

* return array

```JSON
"song": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/license_songs.json)

### live_streams

This returns live_streams based on the specified filter

| Input    | Type       | Description                                                      | Optional |
|----------|------------|------------------------------------------------------------------|----------|
| 'filter' | string     | Filter results to match this string                              | YES      |
| 'exact'  | boolean    | 0,1 if true filter is exact (=) rather than fuzzy (LIKE)         | YES      |
| 'add'    | set_filter | ISO 8601 Date Format (2020-09-16)                                | YES      |
|          |            | Find objects with an 'add' date newer than the specified date    |          |
| 'update' | set_filter | ISO 8601 Date Format (2020-09-16)                                | YES      |
|          |            | Find objects with an 'update' time newer than the specified date |          |
| 'offset' | integer    | Return results starting from this index position                 | YES      |
| 'limit'  | integer    | Maximum number of results to return                              | YES      |

* return array

```JSON
"live_stream": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/live_streams.json)

### live_stream

This returns a single live_stream

| Input    | Type   | Description                                  | Optional |
|----------|--------|----------------------------------------------|----------|
| 'filter' | string | UID of live_stream, returns live_stream JSON | NO       |

* return object

```JSON
"id": "",
"name": "",
"artists": 0,
"summary": "",
"external_link": "'",
"address": "",
"category": "",
"email": "",
"website": "",
"user": 0
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/live_stream.json)

### labels

This returns labels based on the specified filter

| Input    | Type       | Description                                                      | Optional |
|----------|------------|------------------------------------------------------------------|----------|
| 'filter' | string     | Filter results to match this string                              | YES      |
| 'exact'  | boolean    | 0,1 if true filter is exact (=) rather than fuzzy (LIKE)         | YES      |
| 'add'    | set_filter | ISO 8601 Date Format (2020-09-16)                                | YES      |
|          |            | Find objects with an 'add' date newer than the specified date    |          |
| 'update' | set_filter | ISO 8601 Date Format (2020-09-16)                                | YES      |
|          |            | Find objects with an 'update' time newer than the specified date |          |
| 'offset' | integer    | Return results starting from this index position                 | YES      |
| 'limit'  | integer    | Maximum number of results to return                              | YES      |

* return array

```JSON
"label": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/labels.json)

### label

This returns a single label

| Input    | Type   | Description                      | Optional |
|----------|--------|----------------------------------|----------|
| 'filter' | string | UID of label, returns label JSON | NO       |

* return object

```JSON
"id": "",
"name": "",
"artists": 0,
"summary": "",
"external_link": "'",
"address": "",
"category": "",
"email": "",
"website": "",
"user": 0
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/label.json)

### label_artists

This returns the artists for a label

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|----------|
| 'filter' | string  | UID of label, returns artist JSON                | NO       |
| 'offset' | integer | Return results starting from this index position | YES      |
| 'limit'  | integer | Maximum number of results to return              | YES      |

* return array

```JSON
"artist": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/label_artists.json)

### followers

This gets the followers for the requested username

| Input      | Type   | Description                                | Optional |
|------------|--------|--------------------------------------------|----------|
| 'username' | string | Username of the user to get followers list | NO       |

* return array

```JSON
"user": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/followers.json)

### following

Get a list of people that this user follows

| Input      | Type   | Description                                | Optional |
|------------|--------|--------------------------------------------|----------|
| 'username' | string | Username of the user to get following list | NO       |

* return array

```JSON
"user": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/following.json)

### toggle_follow

This follow/unfollow an user

| Input      | Type   | Description                             | Optional |
|------------|--------|-----------------------------------------|----------|
| 'username' | string | Username of the user to follow/unfollow | NO       |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/toggle_follow.json)

### last_shouts

This gets the latest posted shouts

| Input      | Type    | Description                         | Optional |
|------------|---------|-------------------------------------|----------|
| 'username' | string  | Get latest shouts for this username | YES      |
| 'limit'    | integer | Maximum number of results to return | YES      |

* return array

```JSON
"shout": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/last_shouts.json)

### rate

This rates a library item

| Input    | Type    | Description                                   | Optional |
|----------|---------|-----------------------------------------------|---------:|
| 'type'   | string  | 'song', 'album', 'artist', 'playlist',        |       NO |
|          |         | 'podcast', 'podcast_episode', 'video'         |          |
|          |         | 'tvshow', 'tvshow_season'                     |          |
| 'id'     | integer | library item id                               |       NO |
| 'rating' | integer | rating between 0-5                            |       NO |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/rate.json)

### flag

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

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/flag.json)

### record_play

Take a song_id and update the object_count and user_activity table with a play. This allows other sources to record play history to Ampache.

If you don't supply a user id (optional) then just fall back to you.

**ACCESS REQUIRED:** 100 (Admin) permission to change another user's play history

| Input    | Type    | Description | Optional |
|----------|---------|-------------|----------|
| 'id'     | integer | $object_id  | NO       |
| 'user'   | integer | $user_id    | YES      |
| 'client' | string  | $agent      | YES      |
| 'date'   | integer | UNIXTIME()  | YES      |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/record_play.json)

### scrobble

Search for a song using text info and then record a play if found. This allows other sources to record play history to ampache

| Input        | Type    | Description                  | Optional |
|--------------|---------|------------------------------|----------|
| 'song'       | string  | HTML encoded string          | NO       |
| 'artist'     | string  | HTML encoded string          | NO       |
| 'album'      | string  | HTML encoded string          | NO       |
| 'songmbid'   | string  | `song_mbid` also supported   | YES      |
| 'artistmbid' | string  | `artist_mbid` also supported | YES      |
| 'albummbid'  | string  | `album_mbid` also supported  | YES      |
| 'date'       | integer | UNIXTIME()                   | YES      |
| 'client'     | string  | $agent                       | YES      |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/scrobble.json)

### catalogs

This searches the catalogs and returns... catalogs

| Input    | Type   | Description                        | Optional |
|----------|--------|------------------------------------|----------|
| 'filter' | string | Catalog type: music, clip, tvshow, | YES      |
|          |        | movie, personal_video, podcast     |          |

* return array

```JSON
"catalog": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/catalogs.json)

### catalog

Return catalog by UID

| Input    | Type   | Description    | Optional |
|----------|--------|----------------|----------|
| 'filter' | string | UID of Catalog | NO       |

* return object

```JSON
"id": "",
"name": "",
"type": "",
"gather_types": "",
"enabled": 0,
"last_add": "",
"last_clean": "",
"last_update": "",
"path": "",
"rename_pattern": "",
"sort_pattern": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/catalog.json)

### catalog_action

Kick off a catalog update or clean for the selected catalog

**ACCESS REQUIRED:** 75 (Catalog Manager)

| Input     | Type    | Description                       | Optional |
|-----------|---------|-----------------------------------|----------|
| 'task'    | string  | 'add_to_catalog', 'clean_catalog' | NO       |
| 'catalog' | integer | $catalog_id                       | NO       |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/catalog_action%20\(clean_catalog\).json)

### catalog_file

Perform actions on local catalog files.
Single file versions of catalog add, clean, verify and remove (delete)
Make sure you remember to urlencode those file names!

**ACCESS REQUIRED:** 50 (Content Manager)

| Input     | Type    | Description                          | Optional |
|-----------|---------|--------------------------------------|----------|
| 'file'    | string  | FULL path to local file              | NO       |
| 'task'    | string  | 'add','clean','verify','remove'      | NO       |
|           |         | (can include comma-separated values) | NO       |
| 'catalog' | integer | $catalog_id                          | NO       |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/catalog_file.json)

### timeline

This get an user timeline

| Input      | Type    | Description                                       | Optional |
|------------|---------|---------------------------------------------------|----------|
| 'username' | string  | Username of the user for whom to get the timeline | NO       |
| 'limit'    | integer | Maximum number of results to return               | YES      |
| 'since'    | integer | UNIXTIME()                                        | YES      |

* return array

```JSON
"activity": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/timeline.json)

### friends_timeline

This get current user friends timeline

| Input   | Type    | Description                         | Optional |
|---------|---------|-------------------------------------|----------|
| 'limit' | integer | Maximum number of results to return | YES      |
| 'since' | integer | UNIXTIME()                          | NO       |

* return array

```JSON
"activity": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/friends_timeline.json)

### update_from_tags

Update a single album, artist, song from the tag data

| Input  | Type    | Description                     | Optional |
|--------|---------|---------------------------------|----------|
| 'type' | string  | 'artist', 'album', 'song'       | NO       |
| 'id'   | integer | $artist_id, $album_id, $song_id | NO       |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/update_from_tags.json)

### update_artist_info

Update artist information and fetch similar artists from last.fm
Make sure lastfm_API_key is set in your configuration file

**ACCESS REQUIRED:** 75 (Catalog Manager)

| Input | Type    | Description | Optional |
|-------|---------|-------------|----------|
| 'id'  | integer | $artist_id  | NO       |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/update_artist_info.json)

### update_art

Updates a single album, artist, song running the gather_art process
Doesn't overwrite existing art by default.

**ACCESS REQUIRED:** 75 (Catalog Manager)

| Input       | Type    | Description       | Optional |
|-------------|---------|-------------------|----------|
| 'id'        | integer | $object_id        | NO       |
| 'type'      | string  | 'song', 'podcast' | NO       |
| 'overwrite' | boolean | 0, 1              | YES      |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/update_art.json)

### update_podcast

Sync and download new podcast episodes

**ACCESS REQUIRED:** 50 (Content Manager)

| Input | Type    | Description | Optional |
|-------|---------|-------------|----------|
| 'id'  | integer | $object_id  | NO       |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/update_podcast.json)

### user_preference

Get your user preference by name

| Input    | Type   | Description                                       | Optional |
|----------|--------|---------------------------------------------------|----------|
| 'filter' | string | Preference name e.g ('notify_email', 'ajax_load') | NO       |

* return array

```JSON
"preference": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/user_preference.json)

### system_preference

Get your server preference by name

**ACCESS REQUIRED:** 100 (Admin)

| Input    | Type   | Description                                       | Optional |
|----------|--------|---------------------------------------------------|----------|
| 'filter' | string | Preference name e.g ('notify_email', 'ajax_load') | NO       |

* return array

```JSON
"preference": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/system_preferences.json)

### preference_create

Add a new preference to your server

**ACCESS REQUIRED:** 100 (Admin)

| Input         | Type    | Description                                             | Optional |
|---------------|---------|---------------------------------------------------------|----------|
| 'filter'      | string  | Preference name e.g ('notify_email', 'ajax_load')       | NO       |
| 'type'        | string  | 'boolean', 'integer', 'string', 'special'               | NO       |
| 'default'     | mixed   | string or integer default value                         | NO       |
| 'category'    | string  | 'interface', 'internal', 'options', 'playlist',         | NO       |
|               |         | 'plugins', 'streaming', 'system'                        |          |
| 'description' | string  |                                                         | YES      |
| 'subcategory' | string  |                                                         | YES      |
| 'level'       | integer | access level required to change the value (default 100) | YES      |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/preference_create.json)

### preference_edit

Edit a preference value and apply to all users if allowed

**ACCESS REQUIRED:** 100 (Admin)

| Input    | Type    | Description                                       | Optional                  |    |
|----------|---------|---------------------------------------------------|---------------------------|----|
| 'filter' | string  | Preference name e.g ('notify_email', 'ajax_load') | NO                        |    |
| 'value'  | mixed   | (string                                           | integer) Preference value | NO |
| 'all'    | boolean | 0, 1 apply to all users                           | YES                       |    |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/preference_edit.json)

### preference_delete

Delete a non-system preference by name

**ACCESS REQUIRED:** 100 (Admin)

| Input    | Type   | Description                                       | Optional |
|----------|--------|---------------------------------------------------|----------|
| 'filter' | string | Preference name e.g ('notify_email', 'ajax_load') | NO       |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/preference_delete.json)

### get_bookmark

Get the bookmark from it's object_id and object_type.

| Input    | Type   | Description                                       | Optional |
|----------|--------|---------------------------------------------------|----------|
| 'filter' | string | object_id to find                                 | NO       |
| 'type'   | string | object_type  ('song', 'video', 'podcast_episode') | NO       |

* return array

```JSON
"bookmark": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/get_bookmark.json)

### bookmark_create

Create a placeholder for the current media that you can return to later.

| Input      | Type    | Description                                       | Optional |
|------------|---------|---------------------------------------------------|----------|
| 'filter'   | string  | object_id to find                                 | NO       |
| 'type'     | string  | object_type  ('song', 'video', 'podcast_episode') | NO       |
| 'position' | integer | current track time in seconds                     | NO       |
| 'client'   | string  | Agent string. (Default: 'AmpacheAPI')             | YES      |
| 'date'     | integer | update time (Default: UNIXTIME())                 | YES      |

* return array

```JSON
"bookmark": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/bookmark_create.json)

### bookmark_edit

Edit a placeholder for the current media that you can return to later.

| Input      | Type    | Description                                       | Optional |
|------------|---------|---------------------------------------------------|----------|
| 'filter'   | string  | object_id to find                                 | NO       |
| 'type'     | string  | object_type  ('song', 'video', 'podcast_episode') | NO       |
| 'position' | integer | current track time in seconds                     | NO       |
| 'client'   | string  | Agent string. (Default: 'AmpacheAPI')             | YES      |
| 'date'     | integer | update time (Default: UNIXTIME())                 | YES      |

* return array

```JSON
"bookmark": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/bookmark_edit.json)

### bookmark_delete

Delete an existing bookmark. (if it exists)

| Input    | Type   | Description                                       | Optional |
|----------|--------|---------------------------------------------------|----------|
| 'filter' | string | object_id to delete                               | NO       |
| 'type'   | string | object_type  ('song', 'video', 'podcast_episode') | NO       |
| 'client' | string | Agent string. (Default: 'AmpacheAPI')             | YES      |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/bookmark_delete)

### deleted_songs

Returns songs that have been deleted from the server

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|----------|
| 'offset' | integer | Return results starting from this index position | YES      |
| 'limit'  | integer | Maximum number of results to return              | YES      |

* return array

```JSON
"deleted_song": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/deleted_songs.json)

### deleted_podcast_episodes

This returns the episodes for a podcast that have been deleted

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|----------|
| 'offset' | integer | Return results starting from this index position | YES      |
| 'limit'  | integer | Maximum number of results to return              | YES      |

* return array

```JSON
"deleted_podcast_episode": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/deleted_podcast_episodes.json)

### deleted_videos

This returns video objects that have been deleted

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|----------|
| 'offset' | integer | Return results starting from this index position | YES      |
| 'limit'  | integer | Maximum number of results to return              | YES      |

* return array

```JSON
"deleted_video": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/deleted_videos.json)

## Binary Data Methods

Binary data methods are used for returning raw data to the user such as a image or stream.

### stream

Streams a given media file. Takes the file id in parameter with optional max bit rate, file format, time offset, size and estimate content length option.

**DEVELOP** 'podcast_episode' has been added. 'podcast' is incorrect and will be removed in Ampache 6

| Input     | Type    | Description                                      | Optional |
|-----------|---------|--------------------------------------------------|----------|
| 'id'      | integer | $object_id                                       | NO       |
| 'type'    | string  | 'song', 'podcast_episode', 'podcast'             | NO       |
| 'bitrate' | integer | max bitrate for transcoding                      | YES      |
| 'format'  | string  | 'mp3', 'ogg', 'raw', etc                         | YES      |
| 'offset'  | integer | Return results starting from this index position | YES      |
| 'length'  | boolean | 0, 1                                             | YES      |

* return file (HTTP 200 OK)
* throws (HTTP 400 Bad Request)
* throws (HTTP 404 Not Found)

### download

Downloads a given media file. set format=raw to download the full file

| Input    | Type    | Description               | Optional |
|----------|---------|---------------------------|----------|
| 'id'     | integer | $object_id                | NO       |
| 'type'   | string  | 'song', 'podcast_episode' | NO       |
| 'format' | string  | 'mp3', 'ogg', 'raw', etc  | YES      |

* return file (HTTP 200 OK)
* throws (HTTP 400 Bad Request)
* throws (HTTP 404 Not Found)

### get_art

Get an art image.

| Input  | Type    | Description                                                | Optional |
|--------|---------|------------------------------------------------------------|----------|
| 'id'   | integer | $object_id                                                 | NO       |
| 'type' | string  | 'song', 'artist', 'album', 'playlist', 'search', 'podcast' | NO       |

* return image (HTTP 200 OK)
* throws (HTTP 400 Bad Request)
* throws (HTTP 404 Not Found)

## Control Methods

### localplay

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/localplay.json)

[Example (status)](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/localplay%20\(status\).json)

### democratic

This is for controlling democratic play (Songs only)

* **Method Descriptions**
  * vote: +1 vote for the oid
  * devote: -1 vote for the oid
  * playlist: Return an array of song items with an additional \<vote>[VOTE COUNT]\</vote> element
  * play: Returns the URL for playing democratic play

| Input    | Type    | Description                  | Optional |
|----------|---------|------------------------------|----------|
| 'oid'    | integer | UID of Song object           | NO       |
| 'method' | string  | vote, devote, playlist, play | NO       |

* return object|array

```JSON
"url": ""|"method": "","result": false|"song": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/democratic%20\(play\).json)

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/democratic%20\(vote\).json)

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/json-responses/democratic%20\(playlist\).json)
