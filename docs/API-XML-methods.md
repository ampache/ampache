---
title: "XML Methods"
metaTitle: "XML Methods"
description: "API documentation"
---

Let's go through come calls and examples that you can do for each XML method.

With the exception of Binary methods, all responses will return a HTTP 200 response.

Also remember that Binary data methods will not return xml; just the file/data you have requested.

Binary methods will also return:

* HTTP 400 responses for a bad or incomplete request
* HTTP 404 responses where the requests data was not found
* HTTP 416 responses where the stream is unable to return the requested content-range

For information about about how playback works and what a client can expect from Ampache check out [API Media Methods](https://ampache.org/api/api-media-methods)

## Auth Methods

Auth methods are used for authenticating or checking the status of your session in an Ampache server.

Remember that the auth parameter does not need to be sent as a parameter in the URL.

[HTTP header authentication](https://ampache.org/api/#http-header-authentication) is supported for the auth parameter where present.

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
    <genres>
    <playlists>
    <users>
    <catalogs>
    <videos>
    <podcasts>
    <podcast_episodes>
    <shares>
    <licenses>
    <live_streams>
    <labels>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/handshake.xml)

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/goodbye.xml)

### lost_password

Email a new password to the user (if allowed) using a reset token.

```php
   $username;
   $key = hash('sha256', 'email');
   auth = hash('sha256', $username . $key);
```

| Input  | Type   | Description          | Optional |
|--------|--------|----------------------|---------:|
| 'auth' | string | password reset token |       NO |

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

### ping

This can be called without being authenticated, it is useful for determining if what the status of the server is, and what version it is running/compatible with

| Input     | Type   | Description                                                                | Optional |
|-----------|--------|----------------------------------------------------------------------------|---------:|
| 'auth'    | string | (Session ID) returns version information and extends the session if passed |      YES |
| 'version' | string | $version (API Version that the application understands)                    |      YES |

* return

```XML
<root>
    <session_expire>
    <server>
    <version>
    <compatible>
    <auth>
    <api>
    <update>
    <add>
    <clean>
    <songs>
    <albums>
    <artists>
    <genres>
    <playlists>
    <users>
    <catalogs>
    <videos>
    <podcasts>
    <podcast_episodes>
    <shares>
    <licenses>
    <live_streams>
    <labels>
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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/ping.xml)

### register

Register as a new user if allowed. (Requires the username, password and email.)

| Input      | Type   | Description               | Optional |
|------------|--------|---------------------------|---------:|
| 'username' | string | $username                 |       NO |
| 'password' | string | hash('sha256', $password) |       NO |
| 'email'    | string | e.g. `user@gmail.com`     |       NO |
| 'fullname' | string |                           |      YES |

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

## Non-Data Methods

These methods take no parameters beyond your auth key to return information

### system_update

Check Ampache for updates and run the update if there is one.

**ACCESS REQUIRED:** 100 (Admin)

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/system_update.xml)

### system_preferences

Get your server preferences

**ACCESS REQUIRED:** 100 (Admin)

* return

```XML
<root>
    <preferences>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/system_preferences.xml)

### users

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/users.xml)

### user_preferences

Get your user preferences

* return

```XML
<root>
    <preference>
</root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/user_preferences.xml)

## Data Methods

Data methods require additional information and parameters to return information

### advanced_search

Perform an advanced search given passed rules. This works in a similar way to the web/UI search pages.
You can pass multiple rules as well as joins to create in depth search results.

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

| Input    | Type    | Description                                            | Optional |
|----------|---------|--------------------------------------------------------|---------:|
| operator | string  | and, or (whether to match one rule or all)             |       NO |
| rule_*   | array   | [`rule_1`, `rule_1_operator`, `rule_1_input`]          |       NO |
| rule_*   | array   | [`rule_2`, `rule_2_operator`, `rule_2_input`], [etc]   |      YES |
| type     | string  | `song`, `album`, `artist`, `label`, `playlist`         |       NO |
|          |         | `podcast`, `podcast_episode`, `genre`, `user`, `video` |          |
| random   | boolean | `0`, `1` (random order of results; default to 0)       |      YES |
| 'offset' | integer | Return results starting from this index position       |      YES |
| 'limit'  | integer | Maximum number of results to return                    |      YES |

* return

```XML
<root>
    <total_count>
    <song>|<album>|<artist>|<playlist>|<label>|<user>|<video>
</root>
```

* throws

```XML
<root><error></root>
```

SONG [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/advanced_search%20\(song\).xml)

ARTIST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/advanced_search%20\(artist\).xml)

ALBUM [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/advanced_search%20\(album\).xml)

### albums

This returns albums based on the provided search filters

| Input     | Type       | Description                                                                                        | Optional |
|-----------|------------|----------------------------------------------------------------------------------------------------|---------:|
| 'filter'  | string     | Filter results to match this string                                                                |      YES |
| 'include' | string     | `albums`, `songs` (include child objects in the response)                                          |      YES |
| 'exact'   | boolean    | `0`, `1` (if true filter is exact `=` rather than fuzzy `LIKE`)                                    |      YES |
| 'add'     | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'add' date newer than the specified date    |      YES |
| 'update'  | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'update' time newer than the specified date |      YES |
| 'offset'  | integer    | Return results starting from this index position                                                   |      YES |
| 'limit'   | integer    | Maximum number of results to return                                                                |      YES |
| 'cond'    | string     | Apply additional filters to the browse using `;` separated comma string pairs                      |      YES |
|           |            | (e.g. 'filter1,value1;filter2,value2')                                                             |          |
| 'sort'    | string     | Sort name or comma-separated key pair. (e.g. 'name,order')                                         |      YES |
|           |            | Default order 'ASC' (e.g. 'name,ASC' == 'name')                                                    |          |

* return

```XML
<root>
    <total_count>
    <album>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/albums.xml)

### album

This returns a single album based on the UID provided

| Input     | Type   | Description                                     | Optional |
|-----------|--------|-------------------------------------------------|---------:|
| 'filter'  | string | UID of Album, returns album XML                 |       NO |
| 'include' | string | `songs` (include child objects in the response) |      YES |

* return

```XML
<root>
    <total_count>
    <album>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/album.xml)

### album_songs

This returns the songs of a specified album

| Input    | Type    | Description                                                | Optional |
|----------|---------|------------------------------------------------------------|---------:|
| 'filter' | string  | UID of Album, returns song XML                             |       NO |
| 'offset' | integer | Return results starting from this index position           |      YES |
| 'limit'  | integer | Maximum number of results to return                        |      YES |
| 'cond'   | string  | Apply additional filters to the browse using `;` separated |      YES |
|          |         | comma string pairs (e.g. 'filter1,value1;filter2,value2')  |          |
| 'sort'   | string  | Sort name or comma-separated key pair. (e.g. 'name,order') |      YES |
|          |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')            |          |

* return

```XML
<root>
    <total_count>
    <song>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/album_songs.xml)

### artists

This takes a collection of inputs and returns artist objects.

| Input          | Type       | Description                                                                                        | Optional |
|----------------|------------|----------------------------------------------------------------------------------------------------|---------:|
| 'filter'       | string     | Filter results to match this string                                                                |      YES |
| 'exact'        | boolean    | `0`, `1` (if true filter is exact `=` rather than fuzzy `LIKE`)                                    |      YES |
| 'add'          | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'add' date newer than the specified date    |      YES |
| 'update'       | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'update' time newer than the specified date |      YES |
| 'include'      | string     | `albums`, `songs` (include child objects in the response)                                          |      YES |
| 'album_artist' | boolean    | `0`, `1` (if true filter for album artists only)                                                   |      YES |
| 'offset'       | integer    | Return results starting from this index position                                                   |      YES |
| 'limit'        | integer    | Maximum number of results to return                                                                |      YES |
| 'cond'         | string     | Apply additional filters to the browse using `;` separated comma string pairs                      |      YES |
|                |            | (e.g. 'filter1,value1;filter2,value2')                                                             |          |
| 'sort'         | string     | Sort name or comma-separated key pair. (e.g. 'name,order')                                         |      YES |
|                |            | Default order 'ASC' (e.g. 'name,ASC' == 'name')                                                    |          |

* return

```XML
<root>
    <total_count>
    <artist>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/artists.xml)

### artist

This returns a single artist based on the UID of said artist

| Input     | Type   | Description                                               | Optional |
|-----------|--------|-----------------------------------------------------------|---------:|
| 'filter'  | string | UID of Artist, returns artist XML                         |       NO |
| 'include' | string | `albums`, `songs` (include child objects in the response) |      YES |

* return

```XML
<root>
    <total_count>
    <artist>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/artist.xml)

### artist_albums

This returns the albums of an artist

| Input          | Type    | Description                                                                   | Optional |
|----------------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter'       | string  | UID of Artist, returns Album XML                                              |       NO |
| 'album_artist' | boolean | `0`, `1` (if true filter for album artists only)                              |      YES |
| 'offset'       | integer | Return results starting from this index position                              |      YES |
| 'limit'        | integer | Maximum number of results to return                                           |      YES |
| 'cond'         | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|                |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'         | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|                |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

* return

```XML
<root>
    <total_count>
    <album>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/artist_albums.xml)

### artist_songs

This returns the songs of the specified artist

| Input    | Type    | Description                                                                   | Optional |
|----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter' | string  | UID of Artist, returns Song XML                                               |       NO |
| 'top50'  | boolean | `0`, `1` (if true filter to the artist top 50)                                |      YES |
| 'offset' | integer | Return results starting from this index position                              |      YES |
| 'limit'  | integer | Maximum number of results to return                                           |      YES |
| 'cond'   | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|          |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'   | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|          |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

* return

```XML
<root>
    <total_count>
    <song>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/artist_songs.xml)

### bookmarks

Get information about bookmarked media this user is allowed to manage.

| Input     | Type    | Description                                     | Optional |
|-----------|---------|-------------------------------------------------|---------:|
| 'client'  | string  | filter by the agent/client name                 |      YES |
| 'include' | integer | 0,1, if true include the object in the bookmark |      YES |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/bookmarks.xml)
[Example (with include)](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/bookmarks%20\(with%20include\).xml)

### bookmark

Get a single bookmark by bookmark_id

| Input     | Type    | Description                                     | Optional |
|-----------|---------|-------------------------------------------------|---------:|
| 'filter'  | string  | bookmark_id                                     |      YES |
| 'include' | integer | 0,1, if true include the object in the bookmark |      YES |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/bookmark.xml)
[Example (with include)](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/bookmark%20\(with%20include\).xml)

### bookmark_create

Create a placeholder for the current media that you can return to later.

| Input      | Type    | Description                                      | Optional |
|------------|---------|--------------------------------------------------|---------:|
| 'filter'   | string  | $object_id to find                               |       NO |
| 'type'     | string  | `song`, `video`, `podcast_episode` (object_type) |       NO |
| 'position' | integer | current track time in seconds                    |       NO |
| 'client'   | string  | Agent string.                                    |      YES |
| 'date'     | integer | update time (Default: UNIXTIME())                |      YES |
| 'include'  | integer | 0,1, if true include the object in the bookmark  |      YES |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/bookmark_create.xml)

### bookmark_delete

Delete an existing bookmark. (if it exists)

| Input    | Type   | Description                                      | Optional |
|----------|--------|--------------------------------------------------|---------:|
| 'filter' | string | $object_id to delete                             |       NO |
| 'type'   | string | `song`, `video`, `podcast_episode` (object_type) |       NO |
| 'client' | string | Agent string.                                    |      YES |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/bookmark_delete)

### bookmark_edit

Edit a placeholder for the current media that you can return to later.

| Input      | Type    | Description                                                  | Optional |
|------------|---------|--------------------------------------------------------------|---------:|
| 'filter'   | string  | $object_id to find                                           |       NO |
| 'type'     | string  | `bookmark`, `song`, `video`, `podcast_episode` (object_type) |       NO |
| 'position' | integer | current track time in seconds                                |       NO |
| 'client'   | string  | Agent string.                                                |      YES |
| 'date'     | integer | update time (Default: UNIXTIME())                            |      YES |
| 'include'  | integer | 0,1, if true include the object in the bookmark              |      YES |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/bookmark_edit.xml)

### browse

Return children of a parent object in a folder traversal/browse style. If you don't send any parameters you'll get a catalog list (the 'root' path)

**NOTE** Catalog ID is required on 'artist', 'album', 'podcast' so you can filter the browse correctly

| Input     | Type       | Description                                                                                        | Optional |
|-----------|------------|----------------------------------------------------------------------------------------------------|---------:|
| 'filter'  | string     | object_id                                                                                          |      YES |
| 'type'    | string     | 'root', 'catalog', 'artist', 'album', 'podcast'                                                    |      YES |
| 'catalog' | string     | catalog ID you are browsing                                                                        |      YES |
| 'add'     | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'add' date newer than the specified date    |      YES |
| 'update'  | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'update' time newer than the specified date |      YES |
| 'offset'  | integer    | Return results starting from this index position                                                   |      YES |
| 'limit'   | integer    | Maximum number of results to return                                                                |      YES |
| 'cond'    | string     | Apply additional filters to the browse using `;` separated comma string pairs                      |      YES |
|           |            | (e.g. 'filter1,value1;filter2,value2')                                                             |          |
| 'sort'    | string     | Sort name or comma-separated key pair. (e.g. 'name,order')                                         |      YES |
|           |            | Default order 'ASC' (e.g. 'name,ASC' == 'name')                                                    |          |

* return

```XML
<root>
    <total_count>
    <browse>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/browse%20\(root\).xml)

[Example: music catalog](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/browse%20\(music%20catalog\).xml)

[Example: podcast catalog](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/browse%20\(podcast%20catalog\).xml)

[Example: video catalog](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/browse%20\(video%20catalog\).xml)

[Example: artist](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/browse%20\(artist\).xml)

[Example: album](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/browse%20\(album\).xml)

[Example: podcast](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/browse%20\(podcast\).xml)

### catalogs

This searches the catalogs and returns... catalogs

| Input    | Type    | Description                                                                    | Optional |
|----------|---------|--------------------------------------------------------------------------------|---------:|
| 'filter' | string  | `music`, `clip`, `tvshow`, `movie`, `personal_video`, `podcast` (Catalog type) |      YES |
| 'offset' | integer | Return results starting from this index position                               |      YES |
| 'limit'  | integer | Maximum number of results to return                                            |      YES |
| 'cond'   | string  | Apply additional filters to the browse using `;` separated comma string pairs  |      YES |
|          |         | (e.g. 'filter1,value1;filter2,value2')                                         |          |
| 'sort'   | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                     |      YES |
|          |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                                |          |

* return

```XML
<root>
    <total_count>
    <catalog>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/catalogs.xml)

### catalog

Return catalog by UID

| Input    | Type   | Description    | Optional |
|----------|--------|----------------|----------|
| 'filter' | string | UID of Catalog | NO       |

* return

```XML
<root>
    <total_count>
    <catalog>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/catalog.xml)

### catalog_action

Kick off a catalog update or clean for the selected catalog

**ACCESS REQUIRED:** 75 (Catalog Manager)

| Input     | Type    | Description                       | Optional |
|-----------|---------|-----------------------------------|---------:|
| 'task'    | string  | `add_to_catalog`, `clean_catalog` |       NO |
| 'catalog' | string  | $catalog_id                       |       NO |

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

[Example: clean_catalog](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/catalog_action%20\(clean_catalog\).xml)

[Example: add_to_catalog](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/catalog_action%20\(add_to_catalog\).xml)

### catalog_add

Create a public url that can be used by anyone to stream media.
Takes the file id with optional description and expires parameters.

**ACCESS REQUIRED:** 75 (Catalog Manager)

| Input            | Type   | Description                                                                      | Optional |
|------------------|--------|----------------------------------------------------------------------------------|---------:|
| 'name'           | string | Name for the catalog                                                             |       NO |
| 'path'           | string | URL or folder path for your catalog                                              |       NO |
| 'type'           | string | 'local', 'beets', 'remote', 'subsonic', 'seafile', 'beetsremote' Default: local  |      YES |
| 'media_type'     | string | 'music', 'podcast', 'clip', 'tvshow', 'movie', 'personal_video' Default: 'music' |      YES |
| 'file_pattern'   | string | Pattern used identify tags from the file name. Default: '%T - %t'                |      YES |
| 'folder_pattern' | string | Pattern used identify tags from the folder name. Default: '%a/%A'                |      YES |
| 'username'       | string | login to remote catalog ('remote', 'subsonic', 'seafile', 'beetsremote')         |      YES |
| 'password'       | string | password to remote catalog ('remote', 'subsonic', 'seafile', 'beetsremote')      |      YES |

* return

```XML
<root>
    <total_count>
    <catalog>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/catalog_create.xml)

### catalog_delete

Delete an existing catalog.

**ACCESS REQUIRED:** 75 (Catalog Manager)

| Input    | Type   | Description              | Optional |
|----------|--------|--------------------------|---------:|
| 'filter' | string | UID of catalog to delete |       NO |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/catalog_delete.xml)

### catalog_file

Perform actions on local catalog files.
Single file versions of catalog add, clean, verify and remove (delete)
Make sure you remember to urlencode those file names!

**ACCESS REQUIRED:** 50 (Content Manager)

| Input     | Type    | Description                                                             | Optional |
|-----------|---------|-------------------------------------------------------------------------|---------:|
| 'file'    | string  | FULL path to local file                                                 |       NO |
| 'task'    | string  | `add`, `clean`, `verify`, `remove` (can include comma-separated values) |       NO |
| 'catalog' | string  | $catalog_id                                                             |       NO |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/catalog_file.xml)

### catalog_folder

Perform actions on local catalog folders.
Single folder versions of catalog add, clean, verify and remove (delete)
Make sure you remember to urlencode those folder names!

**ACCESS REQUIRED:** 50 (Content Manager)

| Input     | Type    | Description                                                             | Optional |
|-----------|---------|-------------------------------------------------------------------------|---------:|
| 'folder'  | string  | FULL path to local folder                                               |       NO |
| 'task'    | string  | `add`, `clean`, `verify`, `remove` (can include comma-separated values) |       NO |
| 'catalog' | string  | $catalog_id                                                             |       NO |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/catalog_folder.xml)

### deleted_podcast_episodes

This returns the episodes for a podcast that have been deleted

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

* return

```XML
<root>
    <deleted_podcast_episode>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/deleted_podcast_episodes.xml)

### deleted_songs

Returns songs that have been deleted from the server

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

* return

```XML
<root>
    <deleted_song>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/deleted_songs.xml)

### deleted_videos

This returns video objects that have been deleted

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

* return

```XML
<root>
    <deleted_video>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/deleted_videos.xml)

### flag

This flags a library item as a favorite

* Setting flag to true (1) will set the flag
* Setting flag to false (0) will remove the flag

| Input  | Type    | Description                                           | Optional |
|--------|---------|-------------------------------------------------------|---------:|
| 'type' | string  | `song`, `album`, `artist`, `playlist`, `podcast`      |       NO |
|        |         | `podcast_episode`, `video`, `tvshow`, `tvshow_season` |          |
| 'id'   | string  | $object_id                                            |       NO |
| 'flag' | boolean | `0`, `1`                                              |       NO |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/flag.xml)

### followers

This gets the followers for the requested username

| Input      | Type    | Description                                                                   | Optional |
|------------|---------|-------------------------------------------------------------------------------|---------:|
| 'username' | string  | Username of the user for who to get followers list                            |       NO |
| 'offset'   | integer | Return results starting from this index position                              |      YES |
| 'limit'    | integer | Maximum number of results to return                                           |      YES |
| 'cond'     | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|            |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'     | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|            |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/followers.xml)

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/following.xml)

### friends_timeline

This get current user friends timeline

| Input   | Type    | Description                         | Optional |
|---------|---------|-------------------------------------|---------:|
| 'limit' | integer | Maximum number of results to return |      YES |
| 'since' | integer | UNIXTIME()                          |       NO |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/friends_timeline.xml)

### genres

This returns the genres (Tags) based on the specified filter

| Input    | Type    | Description                                                                   | Optional |
|----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter' | string  | Filter results to match this string                                           |      YES |
| 'exact'  | boolean | `0`, `1` (if true filter is exact `=` rather than fuzzy `LIKE`)               |      YES |
| 'offset' | integer | Return results starting from this index position                              |      YES |
| 'limit'  | integer | Maximum number of results to return                                           |      YES |
| 'cond'   | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|          |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'   | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|          |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

* return

```XML
<root>
    <total_count>
    <genre>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/genres.xml)

### genre

This returns a single genre based on UID.
All XML Documents that have a ```<genre></genre>``` element may have 0 or more genre elements associated with them.
Each genre element has an attribute "count" that indicates the number of people who have specified this genre.

| Input    | Type   | Description                     | Optional |
|----------|--------|---------------------------------|---------:|
| 'filter' | string | UID of genre, returns genre XML |       NO |

* return

```XML
<root>
    <total_count>
    <genre>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/genre.xml)

### genre_albums

This returns the albums associated with the genre in question

| Input    | Type    | Description                                                                   | Optional |
|----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter' | string  | UID of genre, returns album XML                                               |       NO |
| 'offset' | integer | Return results starting from this index position                              |      YES |
| 'limit'  | integer | Maximum number of results to return                                           |      YES |
| 'cond'   | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|          |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'   | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|          |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

* return

```XML
<root>
    <total_count>
    <album>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/genre_albums.xml)

### genre_artists

This returns the artists associated with the genre in question as defined by the UID

| Input    | Type    | Description                                                                   | Optional |
|----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter' | string  | UID of genre, returns artist XML                                              |       NO |
| 'offset' | integer | Return results starting from this index position                              |      YES |
| 'limit'  | integer | Maximum number of results to return                                           |      YES |
| 'cond'   | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|          |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'   | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|          |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

* return

```XML
<root>
    <total_count>
    <artist>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/genre_artists.xml)

### genre_songs

returns the songs for this genre

| Input    | Type    | Description                                                                   | Optional |
|----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter' | string  | UID of genre, returns song XML                                                |       NO |
| 'offset' | integer | Return results starting from this index position                              |      YES |
| 'limit'  | integer | Maximum number of results to return                                           |      YES |
| 'cond'   | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|          |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'   | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|          |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

* return

```XML
<root>
    <total_count>
    <song>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/genre_songs.xml)

### get_bookmark

Get the bookmark from it's object_id and object_type.
By default; get only the most recent bookmark. Use `all` to retrieve all media bookmarks for the object.

| Input     | Type    | Description                                        | Optional |
|-----------|---------|----------------------------------------------------|---------:|
| 'filter'  | string  | $object_id to find                                 |       NO |
| 'type'    | string  | `song`, `video`, `podcast_episode` (object_type)   |       NO |
| 'include' | integer | 0,1, if true include the object in the bookmark    |      YES |
| 'all'     | integer | 0,1, if true include every bookmark for the object |      YES |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/get_bookmark.xml)
[Example (with include)](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/get_bookmark%20\(with%20include\).xml)

### get_indexes

This takes a collection of inputs and returns ID + name for the object type

**NOTE** This method is depreciated and will be removed in **API7** (Use list)

| Input         | Type       | Description                                                                                        | Optional |
|---------------|------------|----------------------------------------------------------------------------------------------------|---------:|
| 'type'        | string     | `song`, `album`, `artist`, `album_artist`, `song_artist`, `playlist`, `podcast`                    |       NO |
|               |            | `podcast_episode`, `live_stream`, `catalog`                                                        |          |
| 'filter'      | string     | Value is Alpha Match for returned results, may be more than one letter/number                      |      YES |
| 'hide_search' | integer    | `0`, `1` (if true do not include searches/smartlists in the result)                                |      YES |
| 'update'      | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'update' time newer than the specified date |      YES |
| 'include'     | boolean    | `0`, `1` (include songs in a playlist or episodes in a podcast)                                    |      YES |
| 'offset'      | integer    | Return results starting from this index position                                                   |      YES |
| 'limit'       | integer    | Maximum number of results to return                                                                |      YES |
| 'cond'        | string     | Apply additional filters to the browse using `;` separated comma string pairs                      |      YES |
|               |            | (e.g. 'filter1,value1;filter2,value2')                                                             |          |
| 'sort'        | string     | Sort name or comma-separated key pair. (e.g. 'name,order')                                         |      YES |
|               |            | Default order 'ASC' (e.g. 'name,ASC' == 'name')                                                    |          |

* return

```XML
<root>
    <total_count>
    <song>|<album>|<artist>|<playlist>|<podcast>
</root>
```

* throws

```XML
<root><error></root>
```

SONGS [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/get_indexes%20\(song\).xml)

ARTIST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/get_indexes%20\(artist\).xml)

ALBUM [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/get_indexes%20\(album\).xml)

PLAYLIST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/get_indexes%20\(playlist\).xml)

PODCAST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/get_indexes%20\(podcast\).xml)

### get_similar

Return similar artist id's or similar song ids compared to the input filter

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'type'   | string  | `song`, `artist`                                 |       NO |
| 'filter' | string  | artist id or song id                             |       NO |
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

* return

```XML
<root>
    <total_count>
    <song>|<artist>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/get_similar.xml)

### index

This takes a collection of inputs and returns ID + name for the object type

| Input         | Type       | Description                                                                                        | Optional |
|---------------|------------|----------------------------------------------------------------------------------------------------|---------:|
| 'type'        | string     | `catalog`, `song`, `album`, `artist`, `album_artist`, `song_artist`                                |       NO |
|               |            | `playlist`, `podcast`, `podcast_episode`, `share`, `video`, `live_stream`                          |          |
| 'filter'      | string     | Value is Alpha Match for returned results, may be more than one letter/number                      |      YES |
| 'hide_search' | integer    | `0`, `1` (if true do not include searches/smartlists in the result)                                |      YES |
| 'exact'       | boolean    | `0`, `1` (if true filter is exact `=` rather than fuzzy `LIKE`)                                    |      YES |
| 'add'         | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'add' date newer than the specified date    |      YES |
| 'update'      | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'update' time newer than the specified date |      YES |
| 'include'     | boolean    | `0`, `1` (include songs in a playlist or episodes in a podcast)                                    |      YES |
| 'offset'      | integer    | Return results starting from this index position                                                   |      YES |
| 'limit'       | integer    | Maximum number of results to return                                                                |      YES |
| 'cond'        | string     | Apply additional filters to the browse using `;` separated comma string pairs                      |      YES |
|               |            | (e.g. 'filter1,value1;filter2,value2')                                                             |          |
| 'sort'        | string     | Sort name or comma-separated key pair. (e.g. 'name,order')                                         |      YES |
|               |            | Default order 'ASC' (e.g. 'name,ASC' == 'name')                                                    |          |

* return

```XML
<root>
    <total_count>
    <catalog>|<song>|<album>|<artist>|<album_artist>|<song_artist>|<playlist>|<podcast>|<podcast_episode>|<share>|<video>|<live_stream>
</root>
```

* throws

```XML
<root><error></root>
```

SONGS [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/index%20\(song\).xml)

ARTIST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/index%20\(artist\).xml)

ALBUM [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/index%20\(album\).xml)

PLAYLIST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/index%20\(playlist\).xml)

PODCAST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/index%20\(podcast\).xml)

SONG [Example (with include)](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/index%20\(song%20with%20include\).xml)

ARTIST [Example (with include)](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/index%20\(artist%20with%20include\).xml)

ALBUM [Example (with include)](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/index%20\(album%20with%20include\).xml)

PLAYLIST [Example (with include)](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/index%20\(playlist%20with%20include\).xml)

### labels

This returns labels based on the specified filter

| Input    | Type    | Description                                                                   | Optional |
|----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter' | string  | Filter results to match this string                                           |      YES |
| 'exact'  | boolean | `0`, `1` (if true filter is exact `=` rather than fuzzy `LIKE`)               |      YES |
| 'offset' | integer | Return results starting from this index position                              |      YES |
| 'limit'  | integer | Maximum number of results to return                                           |      YES |
| 'cond'   | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|          |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'   | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|          |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

* return

```XML
<root>
    <total_count>
    <label>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/labels.xml)

### label

This returns a single label

| Input    | Type   | Description                     | Optional |
|----------|--------|---------------------------------|---------:|
| 'filter' | string | UID of label, returns label XML |       NO |

* return

```XML
<root>
    <total_count>
    <label>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/label.xml)

### label_artists

This returns the artists for a label

| Input    | Type    | Description                                                                   | Optional |
|----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter' | string  | UID of label, returns artist XML                                              |       NO |
| 'offset' | integer | Return results starting from this index position                              |      YES |
| 'limit'  | integer | Maximum number of results to return                                           |      YES |
| 'cond'   | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|          |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'   | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|          |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

* return

```XML
<root>
    <total_count>
    <artist>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/label_artists.xml)

### last_shouts

This gets the latest posted shouts

| Input      | Type    | Description                                                                  | Optional |
|------------|---------|------------------------------------------------------------------------------|---------:|
| 'username' | string  | Get latest shouts for this username                                          |      YES |
| 'limit'    | integer | Maximum number of results (Use `popular_threshold` when missing; default 10) |      YES |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/last_shouts.xml)

### license

This returns a single license

| Input    | Type   | Description                         | Optional |
|----------|--------|-------------------------------------|---------:|
| 'filter' | string | UID of license, returns license XML |       NO |

* return

```XML
<root>
    <total_count>
    <license>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/license.xml)

### license_songs

This returns the songs for a license

| Input    | Type    | Description                                                                   | Optional |
|----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter' | string  | UID of license, returns song XML                                              |       NO |
| 'offset' | integer | Return results starting from this index position                              |      YES |
| 'limit'  | integer | Maximum number of results to return                                           |      YES |
| 'cond'   | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|          |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'   | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|          |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

* return

```XML
<root>
    <total_count>
    <song>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/license_songs.xml)

### licenses

This returns licenses based on the specified filter

| Input    | Type    | Description                                                                   | Optional |
|----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter' | string  | Filter results to match this string                                           |      YES |
| 'exact'  | boolean | `0`, `1` (if true filter is exact `=` rather than fuzzy `LIKE`)               |      YES |
| 'offset' | integer | Return results starting from this index position                              |      YES |
| 'limit'  | integer | Maximum number of results to return                                           |      YES |
| 'cond'   | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|          |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'   | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|          |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

* return

```XML
<root>
    <total_count>
    <license>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/licenses.xml)

### list

This takes a named array of objects and returning `id`, `name`, `prefix` and `basename`

**NOTE** This method replaces get_indexes and does not have the `include` parameter and does not include children in the response.

| Input         | Type       | Description                                                                                        | Optional |
|---------------|------------|----------------------------------------------------------------------------------------------------|---------:|
| 'type'        | string     | `song`, `album`, `artist`, `album_artist`, `song_artist`, `playlist`, `podcast`                    |       NO |
|               |            | `podcast_episode`, `live_stream`, `catalog`                                                        |          |
| 'filter'      | string     | Value is Alpha Match for returned results, may be more than one letter/number                      |      YES |
| 'hide_search' | integer    | `0`, `1` (if true do not include searches/smartlists in the result)                                |      YES |
| 'update'      | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'update' time newer than the specified date |      YES |
| 'offset'      | integer    | Return results starting from this index position                                                   |      YES |
| 'limit'       | integer    | Maximum number of results to return                                                                |      YES |
| 'cond'        | string     | Apply additional filters to the browse using `;` separated comma string pairs                      |      YES |
|               |            | (e.g. 'filter1,value1;filter2,value2')                                                             |          |
| 'sort'        | string     | Sort name or comma-separated key pair. (e.g. 'name,order')                                         |      YES |
|               |            | Default order 'ASC' (e.g. 'name,ASC' == 'name')                                                    |          |

* return

```XML
<root>
    <total_count>
    <list>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/list.xml)

### live_streams

This returns live_streams based on the specified filter

| Input    | Type    | Description                                                                   | Optional |
|----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter' | string  | Filter results to match this string                                           |      YES |
| 'exact'  | boolean | `0`, `1` (if true filter is exact `=` rather than fuzzy `LIKE`)               |      YES |
| 'offset' | integer | Return results starting from this index position                              |      YES |
| 'limit'  | integer | Maximum number of results to return                                           |      YES |
| 'cond'   | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|          |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'   | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|          |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

* return

```XML
<root>
    <total_count>
    <live_stream>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/live_streams.xml)

### live_stream

This returns a single live_stream

| Input    | Type   | Description                                 | Optional |
|----------|--------|---------------------------------------------|---------:|
| 'filter' | string | UID of live_stream, returns live_stream XML |       NO |

* return

```XML
<root>
    <total_count>
    <live_stream>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/live_stream.xml)

### live_stream_create

Create a live_stream (radio station) object.

**ACCESS REQUIRED:** 50 (Content Manager) permission to create and edit live_streams

| Input      | Type    | Description                                      | Optional |
|------------|---------|--------------------------------------------------|---------:|
| 'filter'   | string  | $object_id to find                               |       NO |
| 'type'     | string  | `song`, `video`, `podcast_episode` (object_type) |       NO |
| 'position' | integer | current track time in seconds                    |       NO |
| 'client'   | string  | Agent string. (Default: 'AmpacheAPI')            |      YES |
| 'date'     | integer | update time (Default: UNIXTIME())                |      YES |

* return

```XML
<root>
    <live_stream>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/live_stream_create.xml)

### live_stream_delete

Delete an existing live_stream (radio station). (if it exists)

**ACCESS REQUIRED:** 50 (Content Manager) permission to create and edit live_streams

| Input    | Type   | Description                                      | Optional |
|----------|--------|--------------------------------------------------|---------:|
| 'filter' | string | $object_id to delete                             |       NO |
| 'type'   | string | `song`, `video`, `podcast_episode` (object_type) |       NO |
| 'client' | string | Agent string. (Default: 'AmpacheAPI')            |      YES |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/live_stream_delete.xml)

### live_stream_edit

Edit a live_stream (radio station) object.

**ACCESS REQUIRED:** 50 (Content Manager) permission to create and edit live_streams

| Input      | Type    | Description                                      | Optional |
|------------|---------|--------------------------------------------------|---------:|
| 'filter'   | string  | $object_id to find                               |       NO |
| 'type'     | string  | `song`, `video`, `podcast_episode` (object_type) |       NO |
| 'position' | integer | current track time in seconds                    |       NO |
| 'client'   | string  | Agent string. (Default: 'AmpacheAPI')            |      YES |
| 'date'     | integer | update time (Default: UNIXTIME())                |      YES |

* return

```XML
<root>
    <live_stream>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/live_stream_edit.xml)

### now_playing

Get what is currently being played by all users.

* return

```XML
<root>
    <now_playing>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/now_playing.xml)

### player

Inform the server about the state of your client. (Song you are playing, Play/Pause state, etc.)

Return the `now_playing` state when completed

| Input    | Type    | Description                                          | Optional |
|----------|---------|------------------------------------------------------|---------:|
| 'filter' | string  | $object_id currently playing/stopping                |       NO |
| 'type'   | string  | `song`, `video`, `podcast_episode` (Default: `song`) |      YES |
| 'state'  | string  | `play`, `stop` (Default: `play`)                     |      YES |
| 'time'   | integer | current play time in whole seconds (Default: 0)      |      YES |
| 'client' | string  | agent/client name                                    |      YES |

* return

```XML
<root>
    <now_playing>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/player.xml)

### playlists

This returns playlists based on the specified filter

| Input         | Type       | Description                                                                                        | Optional |
|---------------|------------|----------------------------------------------------------------------------------------------------|---------:|
| 'filter'      | string     | Filter results to match this string                                                                |      YES |
| 'hide_search' | integer    | `0`, `1` (if true do not include searches/smartlists in the result)                                |      YES |
| 'show_dupes'  | integer    | `0`, `1` (if true if true ignore 'api_hide_dupe_searches' setting)                                 |      YES |
| 'exact'       | boolean    | `0`, `1` (if true filter is exact `=` rather than fuzzy `LIKE`)                                    |      YES |
| 'add'         | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'add' date newer than the specified date    |      YES |
| 'update'      | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'update' time newer than the specified date |      YES |
| 'offset'      | integer    | Return results starting from this index position                                                   |      YES |
| 'limit'       | integer    | Maximum number of results to return                                                                |      YES |
| 'cond'        | string     | Apply additional filters to the browse using `;` separated comma string pairs                      |      YES |
|               |            | (e.g. 'filter1,value1;filter2,value2')                                                             |          |
| 'sort'        | string     | Sort name or comma-separated key pair. (e.g. 'name,order')                                         |      YES |
|               |            | Default order 'ASC' (e.g. 'name,ASC' == 'name')                                                    |          |

* return

```XML
<root>
    <total_count>
    <playlist>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/playlists.xml)

### playlist

This returns a single playlist

| Input    | Type   | Description                           | Optional |
|----------|--------|---------------------------------------|---------:|
| 'filter' | string | UID of playlist, returns playlist XML |       NO |

* return

```XML
<root>
    <total_count>
    <playlist>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/playlist.xml)

### playlist_add

This adds a song to a playlist. setting check=1 will not add duplicates to the playlist

| Input    | Type   | Description                           | Optional |
|----------|--------|---------------------------------------|---------:|
| 'filter' | string | UID of Playlist                       |       NO |
| 'id'     | string | UID of the object to add to playlist  |       NO |
| 'type'   | string | 'song', 'album', 'artist', 'playlist' |       NO |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/playlist_add.xml)

### playlist_add_song

This adds a song to a playlist. setting check=1 will not add duplicates to the playlist

**NOTE** This method is depreciated and will be removed in **API7** (Use playlist_add)

| Input    | Type    | Description                                                   | Optional |
|----------|---------|---------------------------------------------------------------|---------:|
| 'filter' | string  | UID of Playlist                                               |       NO |
| 'song'   | string  | UID of song to add to playlist                                |       NO |
| 'check'  | boolean | `0`, `1` Whether to check and ignore duplicates (default = 0) |      YES |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/playlist_add_song.xml)

### playlist_create

This create a new playlist and return it

| Input  | Type   | Description                         | Optional |
|--------|--------|-------------------------------------|---------:|
| 'name' | string | Playlist name                       |       NO |
| 'type' | string | `public`, `private` (Playlist type) |      YES |

* return

```XML
<root>
    <total_count>
    <playlist>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/playlist_create.xml)

### playlist_delete

This deletes a playlist

| Input    | Type   | Description     | Optional |
|----------|--------|-----------------|----------|
| 'filter' | string | UID of Playlist | NO       |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/playlist_delete.xml)

### playlist_edit

This modifies name and type of a playlist
Previously name and type were mandatory while filter wasn't. this has been reversed.

**NOTE** items and tracks must be sent together and be of equal length

| Input    | Type   | Description                                                       | Optional |
|----------|--------|-------------------------------------------------------------------|---------:|
| 'filter' | string | UID of Playlist                                                   |       NO |
| 'name'   | string | Playlist name                                                     |      YES |
| 'type'   | string | `public`, `private` (Playlist type)                               |      YES |
| 'owner'  | string | Change playlist owner to the user id (-1 = System playlist)       |      YES |
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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/playlist_edit.xml)

### playlist_generate

Get a list of song XML, indexes or id's based on some simple search criteria
'recent' will search for tracks played after 'Popular Threshold' days
'forgotten' will search for tracks played before 'Popular Threshold' days
'unplayed' added in 400002 for searching unplayed tracks

**Note** when using the 'id' format total_count is not returned.

| Input    | Type    | Description                                                      | Optional |
|----------|---------|------------------------------------------------------------------|---------:|
| 'mode'   | string  | `recent`, `forgotten`, `unplayed`, `random` (default = 'random') |      YES |
| 'filter' | string  | string LIKE matched to song title                                |      YES |
| 'album'  | string  | $album_id                                                        |      YES |
| 'artist' | string  | $artist_id                                                       |      YES |
| 'flag'   | integer | `0`, `1` (get flagged songs only. default = 0)                   |      YES |
| 'format' | string  | `song`, `index`, `id` (default = 'song')                         |      YES |
| 'offset' | integer | Return results starting from this index position                 |      YES |
| 'limit'  | integer | Maximum number of results to return                              |      YES |

* return

```XML
<root>
    <total_count>
    <song>|<index>|<id>
</root>
```

* throws

```XML
<root><error></root>
```

SONG [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/playlist_generate%20\(song\).xml)

INDEX [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/playlist_generate%20\(index\).xml)

ID [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/playlist_generate%20\(id\).xml)

### playlist_hash

This returns the md5 hash for the songs in a playlist

| Input    | Type   | Description     | Optional |
|----------|--------|-----------------|---------:|
| 'filter' | string | UID of Playlist |       NO |

* return

```XML
<root>
    <md5>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/playlist_hash.xml)

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/playlist_remove_song.xml)

### playlist_songs

This returns the songs for a playlist

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'filter' | string  | UID of Playlist, returns song XML                |       NO |
| 'random' | integer | `0`, `1` (if true get random songs using limit)  |      YES |
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

* return

```XML
<root>
    <total_count>
    <song>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/playlist_songs.xml)

### podcast

Get the podcast from it's id.

| Input     | Type   | Description                                           | Optional |
|-----------|--------|-------------------------------------------------------|---------:|
| 'filter'  | string | UID of podcast, returns podcast XML                   |       NO |
| 'include' | string | `episodes` (include podcast_episodes in the response) |      YES |

* return

```XML
<root>
    <total_count>
    <podcast>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/podcast.xml)

### podcasts

Get information about podcasts

| Input     | Type    | Description                                                                   | Optional |
|-----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter'  | string  | Value is Alpha Match for returned results, may be more than one letter/number |      YES |
| 'include' | string  | `episodes` (include podcast_episodes in the response)                         |      YES |
| 'offset'  | integer | Return results starting from this index position                              |      YES |
| 'limit'   | integer | Maximum number of results to return                                           |      YES |
| 'cond'    | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|           |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'    | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|           |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

* return

```XML
<root>
    <total_count>
    <podcast>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/podcasts.xml)

### podcast_create

Create a podcast that can be used by anyone to stream media.
Takes the url and catalog parameters.

**ACCESS REQUIRED:** 75 (Catalog Manager)

| Input     | Type   | Description         | Optional |
|-----------|--------|---------------------|---------:|
| 'url'     | string | rss url for podcast |       NO |
| 'catalog' | string | podcast catalog     |       NO |

* return

```XML
<root>
    <total_count>
    <podcast>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/podcast_create.xml)

### podcast_delete

Delete an existing podcast.

**ACCESS REQUIRED:** 75 (Catalog Manager)

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/podcast_delete.xml)

### podcast_edit

Update the description and/or expiration date for an existing podcast.
Takes the podcast id to update with optional description and expires parameters.

**ACCESS REQUIRED:** 50 (Content Manager)

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/podcast_edit.xml)

### podcast_episodes

This returns the episodes for a podcast

| Input    | Type    | Description                                                                   | Optional |
|----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter' | string  | UID of podcast                                                                |       NO |
| 'offset' | integer | Return results starting from this index position                              |      YES |
| 'limit'  | integer | Maximum number of results to return                                           |      YES |
| 'cond'   | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|          |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'   | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|          |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

* return

```XML
<root>
    <total_count>
    <podcast_episode>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/podcast_episodes.xml)

### podcast_episode

Get the podcast_episode from it's id.

| Input    | Type   | Description               | Optional |
|----------|--------|---------------------------|---------:|
| 'filter' | string | podcast_episode ID number |       NO |

* return

```XML
<root>
    <total_count>
    <podcast_episode>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/podcast_episode.xml)

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/podcast_episode_delete.xml)

### preference_create

Add a new preference to your server

**ACCESS REQUIRED:** 100 (Admin)

| Input         | Type    | Description                                                            | Optional |
|---------------|---------|------------------------------------------------------------------------|---------:|
| 'filter'      | string  | Preference name e.g ('notify_email', 'ajax_load')                      |       NO |
| 'type'        | string  | `boolean`, `integer`, `string`, `special`                              |       NO |
| 'default'     | mixed   | string or integer default value                                        |       NO |
| 'category'    | string  | `interface`, `internal`, `options`, `playlist`, `plugins`, `streaming` |       NO |
| 'description' | string  |                                                                        |      YES |
| 'subcategory' | string  |                                                                        |      YES |
| 'level'       | integer | access level required to change the value (default 100)                |      YES |

* return

```XML
<root>
    <preference>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/preference_create.xml)

### preference_delete

Delete a non-system preference by name

**ACCESS REQUIRED:** 100 (Admin)

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/preference_delete.xml)

### preference_edit

Edit a preference value and apply to all users if allowed

**ACCESS REQUIRED:** 100 (Admin)

| Input    | Type    | Description                                       | Optional |
|----------|---------|---------------------------------------------------|---------:|
| 'filter' | string  | Preference name e.g ('notify_email', 'ajax_load') |       NO |
| 'value'  | mixed   | (string/integer) Preference value                 |       NO |
| 'all'    | boolean | `0`, `1` apply to all users                       |      YES |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/preference_edit.xml)

### rate

This rates a library item

| Input    | Type    | Description                                           | Optional |
|----------|---------|-------------------------------------------------------|---------:|
| 'type'   | string  | `song`, `album`, `artist`, `playlist`, `podcast`      |       NO |
|          |         | `podcast_episode`, `video`, `tvshow`, `tvshow_season` |          |
| 'id'     | string  | library item id                                       |       NO |
| 'rating' | integer | rating between 0-5                                    |       NO |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/rate.xml)

### record_play

Take a song_id and update the object_count and user_activity table with a play. This allows other sources to record play history to Ampache.

If you don't supply a user id (optional) then just fall back to you.

**ACCESS REQUIRED:** 100 (Admin) permission to change another user's play history

| Input    | Type    | Description | Optional |
|----------|---------|-------------|----------|
| 'id'     | string  | $object_id  | NO       |
| 'user'   | string  | $user_id    | YES      |
| 'client' | string  | $agent      | YES      |
| 'date'   | integer | UNIXTIME()  | YES      |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/record_play.xml)

### scrobble

Search for a song using text info and then record a play if found. This allows other sources to record play history to ampache

| Input        | Type    | Description                  | Optional |
|--------------|---------|------------------------------|---------:|
| 'song'       | string  | HTML encoded string          |       NO |
| 'artist'     | string  | HTML encoded string          |       NO |
| 'album'      | string  | HTML encoded string          |       NO |
| 'songmbid'   | string  | `song_mbid` also supported   |      YES |
| 'artistmbid' | string  | `artist_mbid` also supported |      YES |
| 'albummbid'  | string  | `album_mbid` also supported  |      YES |
| 'date'       | integer | UNIXTIME()                   |      YES |
| 'client'     | string  | $agent                       |      YES |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/scrobble.xml)

### search_group

Perform a group search given passed rules. This function will return multiple object types if the rule names match the object type.
You can pass multiple rules as well as joins to create in depth search results.

Limit and offset are applied per object type. Meaning with a limit of 10 you will return 10 objects of each type not 10 results total.

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

**NOTE** the type parameter is different from the regular advanced_search method.
Each type is a grouping of object types so allow single search calls to be made

* all
  * song
  * album
  * song_artist
  * album_artist
  * artist
  * label
  * playlist
  * podcast
  * podcast_episode
  * genre
  * user

* music
  * song
  * album
  * artist

* song_artist
  * song
  * album
  * song_artist

* album_artist
  * song
  * album
  * album_artist

* podcast
  * podcast
  * podcast_episode

* video
  * video

| Input    | Type    | Description                                                                          | Optional |
|----------|---------|--------------------------------------------------------------------------------------|---------:|
| operator | string  | and, or (whether to match one rule or all)                                           |       NO |
| rule_*   | array   | [`rule_1`, `rule_1_operator`, `rule_1_input`]                                        |       NO |
| rule_*   | array   | [`rule_2`, `rule_2_operator`, `rule_2_input`], [etc]                                 |      YES |
| type     | string  | `all`, `music`, `song_artist`, `album_artist`, `podcast`, `video` (`all` by default) |      YES |
| random   | boolean | `0`, `1` (random order of results; default to 0)                                     |      YES |
| 'offset' | integer | Return results starting from this index position                                     |      YES |
| 'limit'  | integer | Maximum number of results to return                                                  |      YES |

* return

```XML
<root>
    <search>
        <song>|<album>|<song_artist>|<album_artist>|<artist>|<label>|<playlist>|<podcast>|<podcast_episode>|<genre>|<user>
    </search>
</root>
```

* throws

```XML
<root><error></root>
```

ALL [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/search_group%20\(all\).xml)

MUSIC [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/search_group%20\(music\).xml)

PODCAST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/search_group%20\(podcast\).xml)

### search_rules

Print a list of valid search rules for your search type

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'filter' | string  | 'song', 'album', 'song_artist', 'album_artist',  |       NO |
|          |         | 'artist', 'label', 'playlist', 'podcast',        |          |
|          |         | 'podcast_episode', 'genre', 'user', 'video'      |          |

* return

```XML
<root>
    <rule>
</root>
```

* throws

```XML
<root><error></root>
```

Artist [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/search_rules (artist).xml)

Album [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/search_rules (album).xml)

Song [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/search_rules (song).xml)

### search_songs

This searches the songs and returns... songs

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'filter' | string  | Filter results to match this string              |       NO |
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

* return

```XML
<root>
    <total_count>
    <song>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/search_songs.xml)

### shares

This searches the shares and returns... shares

| Input    | Type    | Description                                                                   | Optional |
|----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter' | string  | Filter results to match this string                                           |      YES |
| 'exact'  | boolean | `0`, `1` boolean to match the exact filter string                             |      YES |
| 'offset' | integer | Return results starting from this index position                              |      YES |
| 'limit'  | integer | Maximum number of results to return                                           |      YES |
| 'cond'   | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|          |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'   | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|          |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

* return

```XML
<root>
    <total_count>
    <share>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/shares.xml)

### share

Return shares by UID

| Input    | Type   | Description                    | Optional |
|----------|--------|--------------------------------|---------:|
| 'filter' | string | UID of Share, returns song XML |       NO |

* return

```XML
<root>
    <total_count>
    <share>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/share.xml)

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
    <total_count>
    <share>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/share_create.xml)

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/share_delete.xml)

### share_edit

Update the description and/or expiration date for an existing share.
Takes the share id to update with optional description and expires parameters.

| Input         | Type    | Description                        | Optional |
|---------------|---------|------------------------------------|---------:|
| 'filter'      | string  | Alpha-numeric search term          |       NO |
| 'stream'      | boolean | `0`, `1` Allow streaming           |      YES |
| 'download'    | boolean | `0`, `1` Allow Downloading         |      YES |
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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/share_edit.xml)

### songs

Returns songs based on the specified filter

| Input    | Type       | Description                                                                                        | Optional |
|----------|------------|----------------------------------------------------------------------------------------------------|---------:|
| 'filter' | string     | Filter results to match this string                                                                |      YES |
| 'exact'  | boolean    | `0`, `1` (if true filter is exact `=` rather than fuzzy `LIKE`)                                    |      YES |
| 'add'    | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'add' date newer than the specified date    |      YES |
| 'update' | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'update' time newer than the specified date |      YES |
| 'offset' | integer    | Return results starting from this index position                                                   |      YES |
| 'limit'  | integer    | Maximum number of results to return                                                                |      YES |
| 'cond'   | string     | Apply additional filters to the browse using `;` separated comma string pairs                      |      YES |
|          |            | (e.g. 'filter1,value1;filter2,value2')                                                             |          |
| 'sort'   | string     | Sort name or comma-separated key pair. (e.g. 'name,order')                                         |      YES |
|          |            | Default order 'ASC' (e.g. 'name,ASC' == 'name')                                                    |          |

* return

```XML
<root>
    <total_count>
    <song>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/songs.xml)

### song

returns a single song

| Input    | Type   | Description                   | Optional |
|----------|--------|-------------------------------|---------:|
| 'filter' | string | UID of Song, returns song XML |       NO |

* return

```XML
<root>
    <total_count>
    <song>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/song.xml)

### song_delete

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/song_delete.xml)

### song_tags

Get the full song file tags using VaInfo

This is used to get tags for remote catalogs to allow maximum data to be returned

| Input    | Type   | Description           | Optional |
|----------|--------|-----------------------|---------:|
| 'filter' | string | UID of song to fetch  |       NO |

* return

```XML
<root>
    <song_tag>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/song_tags.xml)

### stats

Get some items based on some simple search types and filters. (Random by default)
This method **HAD** partial backwards compatibility with older api versions but it has now been removed
Pass -1 limit to get all results. (0 will fall back to the `popular_threshold` value)

| Input      | Type    | Description                                                                  | Optional |
|------------|---------|------------------------------------------------------------------------------|---------:|
| 'type'     | string  | `song`, `album`, `artist`, `video`, `playlist`, `podcast`, `podcast_episode` |       NO |
| 'filter'   | string  | `newest`, `highest`, `frequent`, `recent`, `forgotten`, `flagged`, `random`  |      YES |
| 'user_id'  | integer |                                                                              |      YES |
| 'username' | string  |                                                                              |      YES |
| 'offset'   | integer | Return results starting from this index position                             |      YES |
| 'limit'    | integer | Maximum number of results (Use `popular_threshold` when missing; default 10) |      YES |

* return

```XML
<root>
    <total_count>
    <song>|<album>|<artist>
</root>
```

* throws

```XML
<root><error></root>
```

SONG [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/stats%20\(song\).xml)

ARTIST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/stats%20\(artist\).xml)

ALBUM [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/stats%20\(album\).xml)

### system_preference

Get your server preference by name

**ACCESS REQUIRED:** 100 (Admin)

| Input    | Type   | Description                                       | Optional |
|----------|--------|---------------------------------------------------|---------:|
| 'filter' | string | Preference name e.g ('notify_email', 'ajax_load') |       NO |

* return

```XML
<root>
    <preference>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/system_preferences.xml)

### timeline

This gets a user's timeline

| Input      | Type    | Description                                       | Optional |
|------------|---------|---------------------------------------------------|---------:|
| 'username' | string  | Username of the user for whom to get the timeline |       NO |
| 'limit'    | integer | Maximum number of results to return               |      YES |
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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/timeline.xml)

### toggle_follow

This follow/unfollow a user

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/toggle_follow.xml)

### update_art

Updates a single album, artist, song running the gather_art process
Doesn't overwrite existing art by default.

**ACCESS REQUIRED:** 75 (Catalog Manager)

| Input       | Type    | Description       | Optional |
|-------------|---------|-------------------|---------:|
| 'id'        | string  | $object_id        |       NO |
| 'type'      | string  | `song`, `podcast` |       NO |
| 'overwrite' | boolean | `0`, `1`          |      YES |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/update_art.xml)

### update_artist_info

Update artist information and fetch similar artists from last.fm
Make sure lastfm_API_key is set in your configuration file

**ACCESS REQUIRED:** 75 (Catalog Manager)

| Input | Type    | Description | Optional |
|-------|---------|-------------|----------|
| 'id'  | string  | $artist_id  | NO       |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/update_artist_info.xml)

### update_from_tags

Update a single album, artist, song from the tag data

| Input  | Type    | Description                     | Optional |
|--------|---------|---------------------------------|---------:|
| 'type' | string  | `song`, `artist`, `album`       |       NO |
| 'id'   | string  | $artist_id, $album_id, $song_id |       NO |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/update_from_tags.xml)

### update_podcast

Sync and download new podcast episodes

**ACCESS REQUIRED:** 50 (Content Manager)

| Input | Type    | Description | Optional |
|-------|---------|-------------|----------|
| 'id'  | string  | $object_id  | NO       |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/update_podcast.xml)

### url_to_song

This takes a url and returns the song object in question

| Input | Type   | Description                                                   | Optional |
|-------|--------|---------------------------------------------------------------|---------:|
| 'url' | string | Full Ampache URL from server, translates back into a song XML |       NO |

* return

```XML
<root>
    <total_count>
    <song>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/url_to_song.xml)

### user

This gets a user's public information.

If the username is omitted, this will return the current api user's public information.

| Input      | Type   | Description                             | Optional |
|------------|--------|-----------------------------------------|---------:|
| 'username' | string | Username of the user to get details for |      YES |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/user.xml)

### user_create

Create a new user. (Requires the username, password and email.)

**ACCESS REQUIRED:** 100 (Admin)

| Input      | Type    | Description                       | Optional |
|------------|---------|-----------------------------------|---------:|
| 'username' | string  | $username                         |       NO |
| 'password' | string  | hash('sha256', $password)         |       NO |
| 'email'    | string  | e.g. `user@gmail.com`             |       NO |
| 'fullname' | string  |                                   |      YES |
| 'disable'  | boolean | `0`, `1`                          |      YES |
| 'group'    | integer | Catalog filter group, default = 0 |      YES |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/user_create.xml)

### user_delete

Delete an existing user.

**ACCESS REQUIRED:** 100 (Admin)

| Input      | Type   | Description | Optional |
|------------|--------|-------------|----------|
| 'username' | string |             | NO       |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/user_delete.xml)

### user_edit

Update an existing user.

**ACCESS REQUIRED:** 100 (Admin)

**NOTE** This function has been renamed from user_update to match other edit functions

| Input               | Type    | Description                              | Optional |
|---------------------|---------|------------------------------------------|---------:|
| 'username'          | string  | $username                                |       NO |
| 'password'          | string  | hash('sha256', $password)                |      YES |
| 'email'             | string  | e.g. `user@gmail.com`                    |      YES |
| 'fullname'          | string  |                                          |      YES |
| 'website'           | string  |                                          |      YES |
| 'state'             | string  |                                          |      YES |
| 'city'              | string  |                                          |      YES |
| 'disable'           | boolean | `0`, `1`                                 |      YES |
| 'group'             | integer | Catalog filter group, default = 0        |      YES |
| 'maxbitrate'        | string  |                                          |      YES |
| 'fullname_public'   | integer | `0`, `1` show fullname in public display |      YES |
| 'reset_apikey'      | integer | `0`, `1` reset user Api Key              |      YES |
| 'reset_streamtoken' | integer | `0`, `1` reset user Stream Token         |      YES |
| 'clear_stats'       | integer | `0`, `1` reset all stats for this user   |      YES |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/user_edit.xml)

### user_playlists

This returns playlists based on the specified filter for your user

**NOTE** This method does not include smartlists

| Input     | Type       | Description                                                                                        | Optional |
|-----------|------------|----------------------------------------------------------------------------------------------------|---------:|
| 'filter'  | string     | Filter results to match this string                                                                |      YES |
| 'exact'   | boolean    | `0`, `1` (if true filter is exact `=` rather than fuzzy `LIKE`)                                    |      YES |
| 'include' | string     | `0`, `1` (include playlist items)                                                                  |      YES |
| 'add'     | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'add' date newer than the specified date    |      YES |
| 'update'  | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'update' time newer than the specified date |      YES |
| 'offset'  | integer    | Return results starting from this index position                                                   |      YES |
| 'limit'   | integer    | Maximum number of results to return                                                                |      YES |
| 'cond'    | string     | Apply additional filters to the browse using `;` separated comma string pairs                      |      YES |
|           |            | (e.g. 'filter1,value1;filter2,value2')                                                             |          |
| 'sort'    | string     | Sort name or comma-separated key pair. (e.g. 'name,order')                                         |      YES |
|           |            | Default order 'ASC' (e.g. 'name,ASC' == 'name')                                                    |          |

* return

```XML
<root>
    <total_count>
    <playlist>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/user_playlists.xml)

### user_preference

Get your user preference by name

| Input    | Type   | Description                                       | Optional |
|----------|--------|---------------------------------------------------|---------:|
| 'filter' | string | Preference name e.g ('notify_email', 'ajax_load') |       NO |

* return

```XML
<root>
    <preference>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/user_preference.xml)

### user_smartlists

This returns smartlists based on the specified filter for your user

**NOTE** This method does not include playlists

| Input     | Type       | Description                                                                                        | Optional |
|-----------|------------|----------------------------------------------------------------------------------------------------|---------:|
| 'filter'  | string     | Filter results to match this string                                                                |      YES |
| 'exact'   | boolean    | `0`, `1` (if true filter is exact `=` rather than fuzzy `LIKE`)                                    |      YES |
| 'include' | string     | `0`, `1` (include playlist items)                                                                  |      YES |
| 'add'     | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'add' date newer than the specified date    |      YES |
| 'update'  | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'update' time newer than the specified date |      YES |
| 'offset'  | integer    | Return results starting from this index position                                                   |      YES |
| 'limit'   | integer    | Maximum number of results to return                                                                |      YES |
| 'cond'    | string     | Apply additional filters to the browse using `;` separated comma string pairs                      |      YES |
|           |            | (e.g. 'filter1,value1;filter2,value2')                                                             |          |
| 'sort'    | string     | Sort name or comma-separated key pair. (e.g. 'name,order')                                         |      YES |
|           |            | Default order 'ASC' (e.g. 'name,ASC' == 'name')                                                    |          |

* return

```XML
<root>
    <total_count>
    <playlist>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/user_smartlists.xml)

### videos

This returns video objects!

| Input    | Type    | Description                                                     | Optional |
|----------|---------|-----------------------------------------------------------------|---------:|
| 'filter' | string  | Filter results to match this string                             |      YES |
| 'exact'  | boolean | `0`, `1` (if true filter is exact `=` rather than fuzzy `LIKE`) |      YES |
| 'offset' | integer | Return results starting from this index position                |      YES |
| 'limit'  | integer | Maximum number of results to return                             |      YES |

* return

```XML
<root>
    <total_count>
    <video>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/videos.xml)

### video

This returns a single video

| Input    | Type   | Description                     | Optional |
|----------|--------|---------------------------------|---------:|
| 'filter' | string | UID of video, returns video XML |       NO |

* return

```XML
<root>
    <total_count>
    <video>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/video.xml)

## Binary Data Methods

Binary data methods are used for returning raw data to the user such as a image or stream.

### download

Downloads a given media file. set format=raw to download the full file

**NOTE** search and playlist will only download a random object from the list

| Input     | Type    | Description                                                                    | Optional |
|-----------|---------|--------------------------------------------------------------------------------|---------:|
| 'id'      | string  | $object_id                                                                     |       NO |
| 'type'    | string  | `song`, `podcast_episode`, `search`, `playlist`                                |       NO |
| 'bitrate' | integer | max bitrate for transcoding in bytes (e.g 192000=192Kb)                        |      YES |
| 'format'  | string  | `mp3`, `ogg`, `raw`, etc (raw returns the original format)                     |      YES |
| 'stats'   | boolean | `0`, `1`, if false disable stat recording when playing the object (default: 1) |      YES |

* return file (HTTP 200 OK)
* throws (HTTP 400 Bad Request)
* throws (HTTP 404 Not Found)

### get_art

Get an art image.

| Input  | Type    | Description                                                | Optional |
|--------|---------|------------------------------------------------------------|---------:|
| 'id'   | string  | $object_id                                                 |       NO |
| 'type' | string  | `song`, `artist`, `album`, `playlist`, `search`, `podcast` |       NO |
| 'size' | string  | width x height ('640x480')                                 |      YES |

* return image (HTTP 200 OK)
* throws (HTTP 400 Bad Request)
* throws (HTTP 404 Not Found)

**NOTE** Art was called using thumb parameters which do not make size obvious.Here is a conversion table to convert any links you have created previously

| Thumb | Width | Height |
|-------|-------|--------|
| 1     | 200   | 200    |
| 2     | 256   | 256    |
| 22    | 512   | 512    |
| 32    | 768   | 768    |
| 3     | 160   | 160    |
| 5     | 64    | 64     |
| 6     | 200   | 300    |
| 34    | 68    | 68     |
| 64    | 128   | 128    |
| 174   | 348   | 348    |
| 300   | 400   | 600    |
| 7     | 400   | 600    |
| 8     | 940   | 400    |
| 9     | 300   | 168    |
| 10    | 48    | 48     |
| 4     | 300   | 300    |
| 11    | 300   | 300    |
| 12    | 300   | 300    |
| 999   | 400   | 400    |

### stream

Streams a given media file. Takes the file id in parameter with optional max bit rate, file format, time offset, size and estimate content length option.

**NOTE** search and playlist will only stream a random object from the list

| Input     | Type    | Description                                                                    | Optional |
|-----------|---------|--------------------------------------------------------------------------------|---------:|
| 'id'      | string  | $object_id                                                                     |       NO |
| 'type'    | string  | `song`, `podcast_episode`, `search`, `playlist`                                |       NO |
| 'bitrate' | integer | max bitrate for transcoding in bytes (e.g 192000=192Kb)                        |      YES |
| 'format'  | string  | `mp3`, `ogg`, `raw`, etc (raw returns the original format)                     |      YES |
| 'offset'  | integer | Return results starting from this index position                               |      YES |
| 'length'  | boolean | `0`, `1`                                                                       |      YES |
| 'stats'   | boolean | `0`, `1`, if false disable stat recording when playing the object (default: 1) |      YES |

* return file (HTTP 200 OK)
* throws (HTTP 400 Bad Request)
* throws (HTTP 404 Not Found)

## Control Methods

### localplay

This is for controlling localplay

| Input     | Type    | Description                                                                             | Optional |
|-----------|---------|-----------------------------------------------------------------------------------------|---------:|
| 'command' | string  | `next`, `prev`, `stop`, `play`, `pause`, `add`, `volume_up`                             |       NO |
|           |         | `volume_down`, `volume_mute`, `delete_all`, `skip`, `status`                            |          |
| 'oid'     | string  | $object_id                                                                              |      YES |
| 'type'    | string  | `song`, `video`, `podcast_episode`, `channel`, `broadcast`, `democratic`, `live_stream` |      YES |
| 'clear'   | boolean | `0`, `1` (Clear the current playlist before adding)                                     |      YES |

* return

```XML
<root>
    <localplay>
        <command>
            <next>|<prev>|<stop>|<play>|<pause>|<add>|<volume_up>|<volume_down>|<volume_mute>|<delete_all>|<skip>|<status>
        </command>
    </localplay>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/localplay.xml)

[Example (status)](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/localplay%20\(status\).xml)

### democratic

This is for controlling democratic play (Songs only)

* **Method Descriptions**
  * vote: +1 vote for the oid
  * devote: -1 vote for the oid
  * playlist: Return an array of song items with an additional \<vote>[VOTE COUNT]\</vote> element
  * play: Returns the URL for playing democratic play

| Input    | Type    | Description                          | Optional |
|----------|---------|--------------------------------------|---------:|
| 'oid'    | string  | UID of Song object                   |       NO |
| 'method' | string  | `vote`, `devote`, `playlist`, `play` |       NO |

* return

```XML
<root>
    <url>|<method><result>|<song>
</root>
```

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/democratic%20\(play\).xml)

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/democratic%20\(vote\).xml)

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/democratic%20\(playlist\).xml)
