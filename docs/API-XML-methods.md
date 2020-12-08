---
title: "XML Methods"
metaTitle: "XML Methods"
metaDescription: "API documentation"
---

Let's go through come calls and examples that you can do for each XML method.

With the exception of Binary methods, all responses will return a HTTP 200 response.

Also remember that Binary data methods will not return xml; just the file/data you have requested.

Binary methods will also return:
* HTTP 400 responses for a bad or incomplete request
* HTTP 404 responses where the requests data was not found

## Auth Methods

Auth methods are used for authenticating or checking the status of your session in an Ampache server

### handshake

This is the function that handles verifying a new handshake Takes a timestamp, auth key, and username.

| Input       | Type    | Description                                              | Optional |
|-------------|---------|----------------------------------------------------------|---------:|
| 'auth'      | string  | $passphrase (Timestamp . Password SHA hash) OR (API Key) |       NO |
| 'user'      | string  | $username (Required if login/password authentication)    |      YES |
| 'timestamp' | integer | UNIXTIME() The timestamp used in seed of password hash   |      YES |
|             |         | (Required if login/password authentication)              |          |
| 'version'   | string  | $version (API Version that the application understands)  |      YES |

* return

```XML
<root>
    <auth>
    <api>
    <session_expire>
    <update>
    <add>
    <clean>
    <songs>
    <albums>
    <artists>
    <playlists>
    <videos>
    <catalogs>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/handshake.xml)

### ping

This can be called without being authenticated, it is useful for determining if what the status of the server is, and what version it is running/compatible with

| Input  | Type   | Description                                                                | Optional |
|--------|--------|----------------------------------------------------------------------------|---------:|
| 'auth' | string | (Session ID) returns version information and extends the session if passed |      YES |

* return

```XML
<root>
    <session_expire>
    <server>
    <version>
    <compatible>
</root>
```

* throws

```XML
<root>
    <server>
    <version>
    <compatible>
</root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/ping.xml)

### goodbye

Destroy a session using the auth parameter.

| Input  | Type   | Description                                    | Optional |
|--------|--------|------------------------------------------------|---------:|
| 'auth' | string | (Session ID) destroys the session if it exists |       NO |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/goodbye.xml)

## Non-Data Methods

These methods take no parameters beyond your auth key to return information

### system_update

* **NEW** in develop

Check Ampache for updates and run the update if there is one.

* return

```XML
<root>
    <success>
</root>
```

* throws
```XML
<root>
    <success>
</root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/system_update.xml)

### system_preferences

* **NEW** in develop

Get your server preferences

* return

```XML
<root>
    <preferences>
        <pref>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/system_preferences.xml)

### users

* **NEW** in develop

Get ids and usernames for your site

* return

```XML
<root>
    <user>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/users.xml)

### user_preferences

* **NEW** in develop

Get your user preferences

* return

```XML
<root>
    <preferences>
        <pref>
</root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/user_preferences.xml)

### bookmarks

* **NEW** in develop

Get information about bookmarked media this user is allowed to manage.

* return

```XML
<root>
    <bookmark>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/bookmarks.xml)

## Data Methods

Data methods require additional information and parameters to return information

### get_indexes

This takes a collection of inputs and returns ID + name for the object type

| Input     | Type       | Description                                                      | Optional |
|-----------|------------|------------------------------------------------------------------|---------:|
| 'type'    | string     | 'song', 'album', 'artist', 'album_artist', 'playlist',           |       NO |
|           |            | 'podcast', 'podcast_episode', 'live_stream'                      |          |
| 'filter'  | string     |                                                                  |      YES |
|           |            | Find objects with an 'add' date newer than the specified date    |          |
| 'update'  | set_filter | ISO 8601 Date Format (2020-09-16)                                |      YES |
|           |            | Find objects with an 'update' time newer than the specified date |          |
| 'include' | boolean    | 0,1 include songs in a playlist or episodes in a podcast         |      YES |
| 'offset'  | integer    |                                                                  |      YES |
| 'limit'   | integer    |                                                                  |      YES |

* return

```XML
<root>
    <song>|<album>|<artist>|<playlist>|<podcast>
</root>
```

* throws

```XML
<root><error></root>
```

SONGS [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/get_indexes%20\(song\).xml)

ARTIST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/get_indexes%20\(artist\).xml)

ALBUM [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/get_indexes%20\(album\).xml)

PLAYLIST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/get_indexes%20\(playlist\).xml)

PODCAST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/get_indexes%20\(podcast\).xml)

### advanced_search

#### Using advanced_search

Perform an advanced search given passed rules. This works in a similar way to the web/UI search pages.
You can pass multiple rules as well as joins to create in depth search results

Rules must be sent in groups of 3 using an int (starting from 1) to designate which rules are combined.
Use operator ('and', 'or') to choose whether to join or separate each rule when searching.

Refer to the [Advanced Search](http://ampache.org/api/api-advanced-search) page for details about creating searches.

**NOTE** the rules part can be confusing but essentially you can include as many 'arrays' of rules as you want.
Just add 1 to the rule value to create a new group of rules.

* Mandatory Rule Values
  * rule_1
  * rule_1_operator
  * rule_1_input
* Optional (Metadata searches **only**)
  * rule_1_subtype

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

* return

```XML
<root>
    <song>|<album>|<artist>|<playlist>|<label>|<user>|<video>
</root>
```

* throws

```XML
<root><error></root>
```

SONG [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/advanced_search%20\(song\).xml)

ARTIST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/advanced_search%20\(artist\).xml)

ALBUM [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/advanced_search%20\(album\).xml)

### artists

This takes a collection of inputs and returns artist objects.

| Input          | Type       | Description                                                      | Optional |
|----------------|------------|------------------------------------------------------------------|---------:|
| 'filter'       | string     | Filter results to match this string                              |      YES |
| 'exact'        | boolean    | 0,1 if true filter is exact (=) rather than fuzzy (LIKE)         |      YES |
| 'add'          | set_filter | ISO 8601 Date Format (2020-09-16)                                |      YES |
|                |            | Find objects with an 'add' date newer than the specified date    |          |
| 'update'       | set_filter | ISO 8601 Date Format (2020-09-16)                                |      YES |
|                |            | Find objects with an 'update' time newer than the specified date |          |
| 'include'      | string     | 'albums', 'songs' and will include the corresponding XML         |      YES |
| 'album_artist' | boolean    | 0,1 if true filter for album artists only                        |      YES |
| 'offset'       | integer    |                                                                  |      YES |
| 'limit'        | integer    |                                                                  |      YES |

* return

```XML
<root>
    <artist>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/artists.xml)

### artist

This returns a single artist based on the UID of said artist

| Input     | Type   | Description                                              | Optional |
|-----------|--------|----------------------------------------------------------|---------:|
| 'filter'  | string | UID of Artist, returns artist XML                        |       NO |
| 'include' | string | 'albums', 'songs' and will include the corresponding XML |      YES |

* return

```XML
<root>
    <artist>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/artist.xml)

### artist_albums

This returns the albums of an artist

| Input    | Type    | Description                      | Optional |
|----------|---------|----------------------------------|---------:|
| 'filter' | string  | UID of Artist, returns Album XML |       NO |
| 'offset' | integer |                                  |      YES |
| 'limit'  | integer |                                  |      YES |

* return

```XML
<root>
    <album>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/artist_albums.xml)

### artist_songs

This returns the songs of the specified artist

| Input    | Type    | Description                     | Optional |
|----------|---------|---------------------------------|---------:|
| 'filter' | string  | UID of Artist, returns Song XML |       NO |
| 'offset' | integer |                                 |      YES |
| 'limit'  | integer |                                 |      YES |

* return

```XML
<root>
    <song>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/artist_songs.xml)

### albums

This returns albums based on the provided search filters

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
| 'include' | string     | 'albums', 'songs' will include the corresponding XML             |      YES |

* return

```XML
<root>
    <album>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/albums.xml)

### album

This returns a single album based on the UID provided

| Input     | Type   | Description                                | Optional |
|-----------|--------|--------------------------------------------|---------:|
| 'filter'  | string | UID of Album, returns album XML            |       NO |
| 'include' | string | 'songs' will include the corresponding XML |      YES |

* return

```XML
<root>
    <album>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/album.xml)

### album_songs

This returns the songs of a specified album

| Input    | Type    | Description                    | Optional |
|----------|---------|--------------------------------|---------:|
| 'filter' | string  | UID of Album, returns song XML |       NO |
| 'offset' | integer |                                |      YES |
| 'limit'  | integer |                                |      YES |

* return

```XML
<root>
    <song>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/album_songs.xml)

### genres

This returns the genres (Tags) based on the specified filter

| Input    | Type    | Description                                              | Optional |
|----------|---------|----------------------------------------------------------|---------:|
| 'filter' | string  | Filter results to match this string                      |      YES |
| 'exact'  | boolean | 0,1 if true filter is exact (=) rather than fuzzy (LIKE) |      YES |
| 'offset' | integer |                                                          |      YES |
| 'limit'  | integer |                                                          |      YES |

* return

```XML
<root>
    <genre>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/genres.xml)

### genre

This returns a single genre based on UID.
All XML Documents that have a ```<genre>``` element may have 0 or more genre elements associated with them.
Each genre element has an attribute "count" that indicates the number of people who have specified this genre.

| Input    | Type   | Description                     | Optional |
|----------|--------|---------------------------------|---------:|
| 'filter' | string | UID of genre, returns genre XML |       NO |

* return

```XML
<root>
    <genre>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/genre.xml)

### genre_artists

This returns the artists associated with the genre in question as defined by the UID

| Input    | Type    | Description                      | Optional |
|----------|---------|----------------------------------|---------:|
| 'filter' | string  | UID of genre, returns artist XML |      YES |
| 'offset' | integer |                                  |      YES |
| 'limit'  | integer |                                  |      YES |

* return

```XML
<root>
    <artist>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/genre_artists.xml)

### genre_albums

This returns the albums associated with the genre in question

| Input    | Type    | Description                     | Optional |
|----------|---------|---------------------------------|---------:|
| 'filter' | string  | UID of genre, returns album XML |      YES |
| 'offset' | integer |                                 |      YES |
| 'limit'  | integer |                                 |      YES |

* return

```XML
<root>
    <album>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/genre_albums.xml)

### genre_songs

returns the songs for this genre

| Input    | Type    | Description                    | Optional |
|----------|---------|--------------------------------|---------:|
| 'filter' | string  | UID of genre, returns song XML |      YES |
| 'offset' | integer |                                |      YES |
| 'limit'  | integer |                                |      YES |

* return

```XML
<root>
    <song>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/genre_songs.xml)

### songs

Returns songs based on the specified filter

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

* return

```XML
<root>
    <song>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/songs.xml)

### song

returns a single song

| Input    | Type   | Description                   | Optional |
|----------|--------|-------------------------------|---------:|
| 'filter' | string | UID of Song, returns song XML |       NO |

* return

```XML
<root>
    <song>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/song.xml)

### song_delete

* **NEW** in Develop

Delete an existing song. (if you are allowed to)

| Input    | Type   | Description           | Optional |
|----------|--------|-----------------------|---------:|
| 'filter' | string | UID of song to delete |       NO |

* return

```XML
<root>
    <success>
</root>
```
* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/song_delete.xml)

### url_to_song

This takes a url and returns the song object in question

| Input | Type   | Description                                                   | Optional |
|-------|--------|---------------------------------------------------------------|---------:|
| 'url' | string | Full Ampache URL from server, translates back into a song XML |       NO |

* return

```XML
<root>
    <song>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/url_to_song.xml)

### playlists

This returns playlists based on the specified filter

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

* return

```XML
<root>
    <playlist>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/playlists.xml)

### playlist

This returns a single playlist

| Input    | Type   | Description                           | Optional |
|----------|--------|---------------------------------------|---------:|
| 'filter' | string | UID of playlist, returns playlist XML |       NO |

* return

```XML
<root>
    <playlist>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/playlist.xml)

### playlist_songs

This returns the songs for a playlist

| Input    | Type    | Description                       | Optional |
|----------|---------|-----------------------------------|---------:|
| 'filter' | string  | UID of Playlist, returns song XML |       NO |
| 'offset' | integer |                                   |      YES |
| 'limit'  | integer |                                   |      YES |

* return

```XML
<root>
    <song>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/playlist_songs.xml)

### playlist_create

This create a new playlist and return it

| Input  | Type   | Description                       | Optional |
|--------|--------|-----------------------------------|---------:|
| 'name' | string | Playlist name                     |       NO |
| 'type' | string | Playlist type 'public', 'private' |      YES |

* return

```XML
<root>
    <playlist>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/playlist_create.xml)

### playlist_edit

This modifies name and type of a playlist
Previously name and type were mandatory while filter wasn't. this has been reversed.

| Input    | Type   | Description                                                       | Optional |
|----------|--------|-------------------------------------------------------------------|---------:|
| 'filter' | string | UID of Playlist                                                   |       NO |
| 'name'   | string | Playlist name                                                     |      YES |
| 'type'   | string | Playlist type 'public', 'private'                                 |      YES |
| 'items'  | string | comma-separated song_id's (replaces existing items with a new id) |      YES |
| 'tracks' | string | comma-separated playlisttrack numbers matched to 'items' in order |      YES |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/playlist_edit.xml)

### playlist_delete

This deletes a playlist

| Input    | Type   | Description     | Optional |
|----------|--------|-----------------|---------:|
| 'filter' | string | UID of Playlist |       NO |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/playlist_delete.xml)

### playlist_add_song

This adds a song to a playlist. setting check=1 will not add duplicates to the playlist

| Input    | Type    | Description                                               | Optional |
|----------|---------|-----------------------------------------------------------|---------:|
| 'filter' | string  | UID of Playlist                                           |       NO |
| 'song'   | string  | UID of song to add to playlist                            |       NO |
| 'check'  | boolean | 0, 1 Whether to check and ignore duplicates (default = 0) |      YES |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/playlist_add_song.xml)

### playlist_remove_song

This remove a song from a playlist.
Previous versions required 'track' instead of 'song'.

| Input    | Type    | Description                          | Optional |
|----------|---------|--------------------------------------|---------:|
| 'filter' | string  | UID of Playlist                      |       NO |
| 'song'   | string  | UID of song to remove from playlist  |      YES |
| 'track'  | integer | Track number to remove from playlist |      YES |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/playlist_remove_song.xml)

### playlist_generate

Get a list of song XML, indexes or id's based on some simple search criteria
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
| 'offset' | integer |                                                                  |      YES |
| 'limit'  | integer |                                                                  |      YES |

* return

```XML
<root>
    <song>|<index>|<id>
</root>
```

* throws

```XML
<root><error></root>
```

SONG [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/playlist_generate%20\(song\).xml)

INDEX [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/playlist_generate%20\(index\).xml)

ID [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/playlist_generate%20\(id\).xml)

### shares

This searches the shares and returns... shares

| Input    | Type    | Description                                   | Optional |
|----------|---------|-----------------------------------------------|---------:|
| 'filter' | string  | Filter results to match this string           |      YES |
| 'exact'  | boolean | 0, 1 boolean to match the exact filter string |      YES |
| 'offset' | integer |                                               |      YES |
| 'limit'  | integer |                                               |      YES |

* return

```XML
<root>
    <share>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/shares.xml)

### share

Return shares by UID

| Input    | Type   | Description                    | Optional |
|----------|--------|--------------------------------|---------:|
| 'filter' | string | UID of Share, returns song XML |       NO |

* return

```XML
<root>
    <share>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/share.xml)

### share_create

Create a public url that can be used by anyone to stream media.
Takes the file id with optional description and expires parameters.

| Input         | Type    | Description                                   | Optional |
|---------------|---------|-----------------------------------------------|---------:|
| 'filter'      | string  | UID of object you are sharing                 |       NO |
| 'type'        | string  | object_type                                   |       NO |
| 'description' | string  | description (will be filled for you if empty) |      YES |
| 'expires'     | integer | days to keep active                           |      YES |

* return

```XML
<root>
    <share>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/share_create.xml)

### share_edit

Update the description and/or expiration date for an existing share.
Takes the share id to update with optional description and expires parameters.

| Input         | Type    | Description                        | Optional |
|---------------|---------|------------------------------------|---------:|
| 'filter'      | string  | Alpha-numeric search term          |       NO |
| 'stream'      | boolean | 0, 1 Allow streaming               |      YES |
| 'download'    | boolean | 0, 1 Allow Downloading             |      YES |
| 'expires'     | integer | number of whole days before expiry |      YES |
| 'description' | string  | update description                 |      YES |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/share_edit.xml)

### share_delete

Delete an existing share.

| Input    | Type   | Description            | Optional |
|----------|--------|------------------------|---------:|
| 'filter' | string | UID of Share to delete |       NO |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/share_delete.xml)

### get_similar

Return similar artist id's or similar song ids compared to the input filter

| Input    | Type    | Description          | Optional |
|----------|---------|----------------------|---------:|
| 'type'   | string  | 'song' or 'artist'   |       NO |
| 'filter' | integer | artist id or song id |       NO |
| 'offset' | integer |                      |      YES |
| 'limit'  | integer |                      |      YES |

* return

```XML
<root>
    <song>|<artist>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/get_similar.xml)

### search_songs

This searches the songs and returns... songs

| Input    | Type    | Description                         | Optional |
|----------|---------|-------------------------------------|---------:|
| 'filter' | string  | Filter results to match this string |       NO |
| 'offset' | integer |                                     |      YES |
| 'limit'  | integer |                                     |      YES |

* return

```XML
<root>
    <song>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/search_songs.xml)

### videos

This returns video objects!

| Input    | Type    | Description                                              | Optional |
|----------|---------|----------------------------------------------------------|---------:|
| 'filter' | string  | Filter results to match this string                      |      YES |
| 'exact'  | boolean | 0,1 if true filter is exact (=) rather than fuzzy (LIKE) |      YES |
| 'offset' | integer |                                                          |      YES |
| 'limit'  | integer |                                                          |      YES |

* return

```XML
<root>
    <video>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/videos.xml)

### video

This returns a single video

| Input    | Type   | Description                     | Optional |
|----------|--------|---------------------------------|---------:|
| 'filter' | string | UID of video, returns video XML |       NO |

* return

```XML
<root>
    <video>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/video.xml)

### stats

Get some items based on some simple search types and filters. (Random by default)
This method HAD partial backwards compatibility with older api versions but it has now been removed

| Input      | Type    | Description                                | Optional |
|------------|---------|--------------------------------------------|---------:|
| 'type'     | string  | 'song', 'album', 'artist'                  |       NO |
| 'filter'   | string  | 'newest', 'highest', 'frequent', 'recent', |      YES |
|            |         | 'forgotten', 'flagged', 'random'           |          |
| 'user_id'  | integer |                                            |      YES |
| 'username' | string  |                                            |      YES |
| 'offset'   | integer |                                            |      YES |
| 'limit'    | integer |                                            |      YES |

* return

```XML
<root>
    <song>|<album>|<artist>
</root>
```

* throws

```XML
<root><error></root>
```

SONG [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/stats%20\(song\).xml)

ARTIST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/stats%20\(artist\).xml)

ALBUM [Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/stats%20\(album\).xml)

### podcasts

Get information about podcasts

| Input     | Type    | Description                                   | Optional |
|-----------|---------|-----------------------------------------------|---------:|
| 'filter'  | string  |                                               |      YES |
| 'offset'  | integer |                                               |      YES |
| 'limit'   | integer |                                               |      YES |
| 'include' | string  | 'episodes' (include episodes in the response) |      YES |

* return

```XML
<root>
    <podcast>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/podcasts.xml)

### podcast

Get the podcast from it's id.

| Input     | Type   | Description                                   | Optional |
|-----------|--------|-----------------------------------------------|---------:|
| 'filter'  | string |                                               |       NO |
| 'include' | string | 'episodes' (include episodes in the response) |      YES |

* return

```XML
<root>
    <podcast>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/podcast.xml)

### podcast_create

Create a podcast that can be used by anyone to stream media.
Takes the url and catalog parameters.

| Input     | Type   | Description         | Optional |
|-----------|--------|---------------------|---------:|
| 'url'     | string | rss url for podcast |       NO |
| 'catalog' | string | podcast catalog     |       NO |

* return

```XML
<root>
    <podcast>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/podcast_create.xml)

### podcast_edit

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

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/podcast_edit.xml)

### podcast_delete

Delete an existing podcast.

| Input    | Type   | Description              | Optional |
|----------|--------|--------------------------|---------:|
| 'filter' | string | UID of podcast to delete |       NO |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/podcast_delete.xml)

### podcast_episodes

This returns the episodes for a podcast

| Input    | Type    | Description    | Optional |
|----------|---------|----------------|---------:|
| 'filter' | string  | UID of podcast |       NO |
| 'offset' | integer |                |      YES |
| 'limit'  | integer |                |      YES |

* return

```XML
<root>
    <podcast_episode>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/podcast_episodes.xml)

### podcast_episode

Get the podcast_episode from it's id.

| Input    | Type   | Description               | Optional |
|----------|--------|---------------------------|---------:|
| 'filter' | string | podcast_episode ID number |       NO |

* return

```XML
<root>
    <podcast_episode>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/podcast_episode.xml)

### podcast_episode_delete

Delete an existing podcast_episode.

| Input    | Type   | Description                      | Optional |
|----------|--------|----------------------------------|---------:|
| 'filter' | string | UID of podcast_episode to delete |       NO |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/podcast_episode_delete.xml)

### user

This get an user public information

| Input      | Type   | Description                             | Optional |
|------------|--------|-----------------------------------------|---------:|
| 'username' | string | Username of the user to get details for |       NO |

* return

```XML
<root>
    <user>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/user.xml)

### user_create

Create a new user. (Requires the username, password and email.)

| Input      | Type    | Description                | Optional |
|------------|---------|----------------------------|---------:|
| 'username' | string  | $username                  |       NO |
| 'password' | string  | hash('sha256', $password)) |       NO |
| 'email'    | string  | 'user@gmail.com'           |       NO |
| 'fullname' | string  |                            |      YES |
| 'disable'  | boolean | 0, 1                       |      YES |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/user_create.xml)

### user_update

Update an existing user.

| Input        | Type    | Description                | Optional |
|--------------|---------|----------------------------|---------:|
| 'username'   | string  | $username                  |       NO |
| 'password'   | string  | hash('sha256', $password)) |      YES |
| 'email'      | string  | 'user#gmail.com'           |      YES |
| 'fullname'   | string  |                            |      YES |
| 'website'    | string  |                            |      YES |
| 'state'      | string  |                            |      YES |
| 'city'       | string  |                            |      YES |
| 'disable'    | boolean | 0, 1                       |      YES |
| 'maxbitrate' | string  |                            |      YES |

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/user_update.xml)

### user_delete

Delete an existing user.

| Input      | Type   | Description | Optional |
|------------|--------|-------------|---------:|
| 'username' | string |             |       NO |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/user_delete.xml)

### licenses

This returns licenses based on the specified filter

| Input    | Type       | Description                                              | Optional |
|----------|------------|----------------------------------------------------------|---------:|
| 'filter' | string     | Filter results to match this string                      |      YES |
| 'exact'  | boolean    | 0,1 if true filter is exact (=) rather than fuzzy (LIKE) |      YES |
| 'offset' | integer    |                                                          |      YES |
| 'limit'  | integer    |                                                          |      YES |

* return

```XML
<root>
    <license>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/licenses.xml)

### license

This returns a single license

| Input    | Type   | Description                         | Optional |
|----------|--------|-------------------------------------|---------:|
| 'filter' | string | UID of license, returns license XML |       NO |

* return

```XML
<root>
    <license>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/license.xml)

### license_songs

This returns the songs for a license

| Input    | Type    | Description                      | Optional |
|----------|---------|----------------------------------|---------:|
| 'filter' | string  | UID of license, returns song XML |       NO |
| 'offset' | integer |                                  |      YES |
| 'limit'  | integer |                                  |      YES |

* return

```XML
<root>
    <song>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/license_songs.xml)

### labels

* **NEW** in develop

This returns labels based on the specified filter

| Input    | Type       | Description                                              | Optional |
|----------|------------|----------------------------------------------------------|---------:|
| 'filter' | string     | Filter results to match this string                      |      YES |
| 'exact'  | boolean    | 0,1 if true filter is exact (=) rather than fuzzy (LIKE) |      YES |
| 'offset' | integer    |                                                          |      YES |
| 'limit'  | integer    |                                                          |      YES |

* return

```XML
<root>
    <label>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/labels.xml)

### label

* **NEW** in develop

This returns a single label

| Input    | Type   | Description                     | Optional |
|----------|--------|---------------------------------|---------:|
| 'filter' | string | UID of label, returns label XML |       NO |

* return

```XML
<root>
    <label>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/label.xml)

### label_artists

* **NEW** in develop

This returns the artists for a label

| Input    | Type    | Description                      | Optional |
|----------|---------|----------------------------------|---------:|
| 'filter' | string  | UID of label, returns artist XML |       NO |
| 'offset' | integer |                                  |      YES |
| 'limit'  | integer |                                  |      YES |

* return

```XML
<root>
    <artist>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/label_artists.xml)

### followers

This gets the followers for the requested username

| Input      | Type   | Description                                        | Optional |
|------------|--------|----------------------------------------------------|---------:|
| 'username' | string | Username of the user for who to get followers list |       NO |

* return

```XML
<root>
    <song>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/followers.xml)

### following

Get a list of people that this user follows

| Input      | Type   | Description                                         | Optional |
|------------|--------|-----------------------------------------------------|---------:|
| 'username' | string | (Username of the user for who to get following list |       NO |

* return

```XML
<root>
    <user>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/following.xml)

### toggle_follow

This follow/unfollow an user

| Input      | Type   | Description                             | Optional |
|------------|--------|-----------------------------------------|---------:|
| 'username' | string | Username of the user to follow/unfollow |       NO |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/toggle_follow.xml)

### last_shouts

This gets the latest posted shouts

| Input      | Type    | Description                         | Optional |
|------------|---------|-------------------------------------|---------:|
| 'username' | string  | Get latest shouts for this username |      YES |
| 'limit'    | integer |                                     |      YES |

* return

```XML
<root>
    <shout>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/last_shouts.xml)

### rate

This rates a library item

| Input    | Type    | Description                                   | Optional |
|----------|---------|-----------------------------------------------|---------:|
| 'type'   | string  | library item type, album, artist, song, video |       NO |
| 'id'     | string  | library item id                               |       NO |
| 'rating' | integer | rating between 0-5                            |       NO |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/rate.xml)

### flag

This flags a library item as a favorite

* Setting flag to true (1) will set the flag
* Setting flag to false (0) will remove the flag

| Input  | Type    | Description                        | Optional |
|--------|---------|------------------------------------|---------:|
| 'type' | string  | 'song', 'album', 'artist', 'video' |       NO |
| 'id'   | integer | $object_id                         |       NO |
| 'flag' | boolean | 0, 1                               |       NO |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/flag.xml)

### record_play

Take a song_id and update the object_count and user_activity table with a play. This allows other sources to record play history to ampache

| Input    | Type    | Description | Optional |
|----------|---------|-------------|---------:|
| 'id'     | integer | $object_id  |       NO |
| 'user'   | integer | $user_id    |       NO |
| 'client' | string  | $agent      |      YES |
| 'date'   | integer | UNIXTIME()  |      YES |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/record_play.xml)

### scrobble

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

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/scrobble.xml)

### catalogs

This searches the catalogs and returns... catalogs

| Input    | Type   | Description                        | Optional |
|----------|--------|------------------------------------|---------:|
| 'filter' | string | Catalog type: music, clip, tvshow, |      YES |
|          |        | movie, personal_video, podcast     |          |

* return

```XML
<root>
    <catalog>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/catalogs.xml)

### catalog

Return catalog by UID

| Input    | Type   | Description    | Optional |
|----------|--------|----------------|---------:|
| 'filter' | string | UID of Catalog |       NO |

* return

```XML
<root>
    <catalog>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/catalog.xml)

### catalog_action

Kick off a catalog update or clean for the selected catalog

| Input     | Type    | Description                       | Optional |
|-----------|---------|-----------------------------------|---------:|
| 'task'    | string  | 'add_to_catalog', 'clean_catalog' |       NO |
| 'catalog' | integer | $catalog_id                       |       NO |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example: clean_catalog](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/catalog_action%20\(clean_catalog\).xml)

[Example: add_to_catalog](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/catalog_action%20\(add_to_catalog\).xml)

### catalog_file

Perform actions on local catalog files.
Single file versions of catalog add, clean, verify and remove (delete)
Make sure you remember to urlencode those file names!

| Input     | Type    | Description                      | Optional |
|-----------|---------|----------------------------------|---------:|
| 'file'    | string  | FULL path to local file          |       NO |
| 'task'    | string  | 'add','clean','verify', 'remove' |       NO |
| 'catalog' | integer | $catalog_id                      |       NO |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/catalog_file.xml)

### timeline

This get an user timeline

| Input      | Type    | Description                                       | Optional |
|------------|---------|---------------------------------------------------|---------:|
| 'username' | string  | Username of the user for whom to get the timeline |       NO |
| 'limit'    | integer |                                                   |      YES |
| 'since'    | integer | UNIXTIME()                                        |      YES |

* return

```XML
<root>
    <activity>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/timeline.xml)

### friends_timeline

This get current user friends timeline

| Input   | Type    | Description | Optional |
|---------|---------|-------------|---------:|
| 'limit' | integer |             |      YES |
| 'since' | integer | UNIXTIME()  |       NO |

* return

```XML
<root>
    <activity>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/friends_timeline.xml)

### update_from_tags

Update a single album, artist, song from the tag data

| Input  | Type    | Description                     | Optional |
|--------|---------|---------------------------------|---------:|
| 'type' | string  | 'artist', 'album', 'song'       |       NO |
| 'id'   | integer | $artist_id, $album_id, $song_id |       NO |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/update_from_tags.xml)

### update_artist_info

Update artist information and fetch similar artists from last.fm
Make sure lastfm_API_key is set in your configuration file

| Input | Type    | Description | Optional |
|-------|---------|-------------|---------:|
| 'id'  | integer | $artist_id  |       NO |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/update_artist_info.xml)

### update_art

Updates a single album, artist, song running the gather_art process
Doesn't overwrite existing art by default.

| Input       | Type    | Description       | Optional |
|-------------|---------|-------------------|---------:|
| 'id'        | integer | $object_id        |       NO |
| 'type'      | string  | 'song', 'podcast' |       NO |
| 'overwrite' | boolean | 0, 1              |      YES |

* return

```XML
<root>
    <success>
    <art>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/update_art.xml)

### update_podcast

Sync and download new podcast episodes

| Input | Type    | Description | Optional |
|-------|---------|-------------|---------:|
| 'id'  | integer | $object_id  |       NO |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/update_podcast.xml)

### user_preference

* **NEW** in develop

Get your user preference by name

| Input    | Type   | Description                                       | Optional |
|----------|--------|---------------------------------------------------|---------:|
| 'filter' | string | Preference name e.g ('notify_email', 'ajax_load') |       NO |

* return

```XML
<root>
    <preferences>
        <pref>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/user_preference.xml)

### system_preference

* **NEW** in develop

Get your server preference by name

| Input    | Type   | Description                                       | Optional |
|----------|--------|---------------------------------------------------|---------:|
| 'filter' | string | Preference name e.g ('notify_email', 'ajax_load') |       NO |

* return

```XML
<root>
    <preferences>
        <pref>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/system_preferences.xml)

### preference_create

* **NEW** in develop

Add a new preference to your server

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/preference_create.xml)

### preference_edit

* **NEW** in develop

Edit a preference value and apply to all users if allowed

| Input    | Type    | Description                                       | Optional |
|----------|---------|---------------------------------------------------|---------:|
| 'filter' | string  | Preference name e.g ('notify_email', 'ajax_load') |       NO |
| 'value'  | mixed   | (string|integer) Preference value                 |       NO |
| 'all'    | boolean | 0, 1 apply to all users                           |      YES |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/preference_edit.xml)

### preference_delete

* **NEW** in develop

Delete a non-system preference by name

| Input    | Type   | Description                                       | Optional |
|----------|--------|---------------------------------------------------|---------:|
| 'filter' | string | Preference name e.g ('notify_email', 'ajax_load') |       NO |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/preference_delete.xml)

### get_bookmark

* **NEW** in develop

Get the bookmark from it's object_id and object_type.

| Input    | Type   | Description                                       | Optional |
|----------|--------|---------------------------------------------------|---------:|
| 'filter' | string | object_id to find                                 |       NO |
| 'type'   | string | object_type  ('song', 'video', 'podcast_episode') |       NO |

* return

```XML
<root>
    <bookmark>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/get_bookmark.xml)

### bookmark_create

* **NEW** in develop

Create a placeholder for the current media that you can return to later.

| Input      | Type    | Description                                       | Optional |
|------------|---------|---------------------------------------------------|---------:|
| 'filter'   | string  | object_id to find                                 |       NO |
| 'type'     | string  | object_type  ('song', 'video', 'podcast_episode') |       NO |
| 'position' | integer | current track time in seconds                     |       NO |
| 'client'   | string  | Agent string. (Default: 'AmpacheAPI')             |      YES |
| 'date'     | integer | update time (Default: UNIXTIME())                 |      YES |

* return

```XML
<root>
    <bookmark>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/bookmark_create.xml)

### bookmark_edit

* **NEW** in develop

Edit a placeholder for the current media that you can return to later.

| Input      | Type    | Description                                       | Optional |
|------------|---------|---------------------------------------------------|---------:|
| 'filter'   | string  | object_id to find                                 |       NO |
| 'type'     | string  | object_type  ('song', 'video', 'podcast_episode') |       NO |
| 'position' | integer | current track time in seconds                     |       NO |
| 'client'   | string  | Agent string. (Default: 'AmpacheAPI')             |      YES |
| 'date'     | integer | update time (Default: UNIXTIME())                 |      YES |

* return

```XML
<root>
    <bookmark>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/bookmark_edit.xml)

### bookmark_delete

* **NEW** in develop

Delete an existing bookmark. (if it exists)

| Input    | Type   | Description                                       | Optional |
|----------|--------|---------------------------------------------------|---------:|
| 'filter' | string | object_id to delete                               |       NO |
| 'type'   | string | object_type  ('song', 'video', 'podcast_episode') |       NO |
| 'client' | string | Agent string. (Default: 'AmpacheAPI')             |      YES |

* return

```XML
<root>
    <success>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/bookmark_delete)

## Binary Data Methods

Binary data methods are used for returning raw data to the user such as a image or stream.

### stream

Streams a given media file. Takes the file id in parameter with optional max bit rate, file format, time offset, size and estimate content length option.

| Input     | Type    | Description                 | Optional |
|-----------|---------|-----------------------------|---------:|
| 'id'      | integer | $object_id                  |       NO |
| 'type'    | string  | 'song', 'podcast'           |       NO |
| 'bitrate' | integer | max bitrate for transcoding |      YES |
| 'format'  | string  | 'mp3', 'ogg', 'raw', etc    |      YES |
| 'offset'  | integer | time offset in seconds      |      YES |
| 'length'  | boolean | 0, 1                        |      YES |

* return file (HTTP 200 OK)
* throws (HTTP 400 Bad Request)
* throws (HTTP 404 Not Found)

### download

Downloads a given media file. set format=raw to download the full file

| Input    | Type    | Description               | Optional |
|----------|---------|---------------------------|---------:|
| 'id'     | integer | $object_id                |       NO |
| 'type'   | string  | 'song', 'podcast_episode' |       NO |
| 'format' | string  | 'mp3', 'ogg', 'raw', etc  |      YES |

* return file (HTTP 200 OK)
* throws (HTTP 400 Bad Request)
* throws (HTTP 404 Not Found)

### get_art

Get an art image.

| Input  | Type    | Description                                                | Optional |
|--------|---------|------------------------------------------------------------|---------:|
| 'id'   | integer | $object_id                                                 |       NO |
| 'type' | string  | 'song', 'artist', 'album', 'playlist', 'search', 'podcast' |       NO |

* return image (HTTP 200 OK)
* throws (HTTP 400 Bad Request)
* throws (HTTP 404 Not Found)

## Control Methods

### localplay

This is for controlling localplay

| Input     | Type    | Description                                                  | Optional |
|-----------|---------|--------------------------------------------------------------|---------:|
| 'command' | string  | 'next', 'prev', 'stop', 'play', 'pause', 'add', 'volume_up', |       NO |
|           |         | 'volume_down', 'volume_mute', 'delete_all', 'skip', 'status' |          |
| 'oid'     | integer | object_id                                                    |      YES |
| 'type'    | string  | 'Song', 'Video', 'Podcast_Episode', 'Channel',               |      YES |
|           |         | 'Broadcast', 'Democratic', 'Live_Stream'                     |          |
| 'clear'   | boolean | 0,1 Clear the current playlist before adding                 |      YES |

* return

```XML
<root>
    <localplay>
        <command>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/localplay.xml)

[Example (status)](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/localplay%20\(status\).xml)

### democratic

This is for controlling democratic play (Songs only)

* **Method Descriptions**
  * vote: +1 vote for the oid
  * devote: -1 vote for the oid
  * playlist: Return an array of song items with an additional \<vote>[VOTE COUNT]\</vote> element
  * play: Returns the URL for playing democratic play

| Input    | Type    | Description                  | Optional |
|----------|---------|------------------------------|---------:|
| 'oid'    | integer | UID of Song object           |       NO |
| 'method' | string  | vote, devote, playlist, play |       NO |

* return

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/master/docs/xml-responses/democratic%20\(play\).xml)

