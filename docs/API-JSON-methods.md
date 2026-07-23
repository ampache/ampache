# API JSON Methods

Let's go through come calls and examples that you can do for each JSON method.

Parameters may be sent as a query string, or (for `POST`/`PUT`/`PATCH`/`DELETE`) as a form-encoded or `application/json` request body. See [API.md](API.md#news) for details.

Valid responses will always return a HTTP 200 response.

Error responses return codes based on the error type:

* HTTP 400
  * Error '4710': BAD_REQUEST
  * Error '4705': MISSING
* HTTP 401
  * Error '4701': INVALID_HANDSHAKE
* HTTP 403
  * Error '4700': ACCESS_CONTROL_NOT_ENABLED
  * Error '4703': ACCESS_DENIED
  * Error '4742': FAILED_ACCESS_CHECK
* HTTP 404
  * Error '4704': NOT_FOUND
* HTTP 410
  * Error '4706': DEPRECATED
* HTTP 500
  * Error '4702': GENERIC_ERROR

Binary data methods will not return JSON; just the file/data you have requested.

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

**NOTE** For privacy, send `auth` in a request body or the `Authorization: Bearer` header rather than the query string. Query-string support for `auth` is deprecated and will be removed in **API9**.

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field               | Type              | Nullable | Optional | Notes |
|---------------------|-------------------|:--------:|:--------:|-------|
| auth                | string            |   YES    |    NO    |       |
| streamtoken         | string            |   YES    |    NO    |       |
| api                 | string            |    NO    |    NO    |       |
| session_expire      | integer \| string |    NO    |    NO    |       |
| update              | string            |    NO    |    NO    |       |
| add                 | string            |    NO    |    NO    |       |
| clean               | string            |    NO    |    NO    |       |
| max_song            | integer           |    NO    |    NO    |       |
| max_album           | integer           |    NO    |    NO    |       |
| max_artist          | integer           |    NO    |    NO    |       |
| max_video           | integer           |    NO    |    NO    |       |
| max_podcast         | integer           |    NO    |    NO    |       |
| max_podcast_episode | integer           |    NO    |    NO    |       |
| songs               | integer           |    NO    |    NO    |       |
| albums              | integer           |    NO    |    NO    |       |
| artists             | integer           |    NO    |    NO    |       |
| genres              | integer           |    NO    |    NO    |       |
| playlists           | integer           |    NO    |    NO    |       |
| searches            | integer           |    NO    |    NO    |       |
| playlists_searches  | integer           |    NO    |    NO    |       |
| users               | integer           |    NO    |    NO    |       |
| catalogs            | integer           |    NO    |    NO    |       |
| videos              | integer           |    NO    |    NO    |       |
| podcasts            | integer           |    NO    |    NO    |       |
| podcast_episodes    | integer           |    NO    |    NO    |       |
| shares              | integer           |    NO    |    NO    |       |
| licenses            | integer           |    NO    |    NO    |       |
| live_streams        | integer           |    NO    |    NO    |       |
| labels              | integer           |    NO    |    NO    |       |
| username            | string            |    NO    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/handshake.json)

### goodbye

Destroy a session using the auth parameter.

| Input  | Type   | Description                                    | Optional |
|--------|--------|------------------------------------------------|---------:|
| 'auth' | string | (Session ID) destroys the session if it exists |       NO |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/goodbye.json)

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

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field   | Type   | Nullable | Optional | Notes |
|---------|--------|:--------:|:--------:|-------|
| success | string |    NO    |   YES    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

### ping

This can be called without being authenticated, it is useful for determining if what the status of the server is, and what version it is running/compatible with

| Input     | Type   | Description                                                                | Optional |
|-----------|--------|----------------------------------------------------------------------------|---------:|
| 'auth'    | string | (Session ID) returns version information and extends the session if passed |      YES |
| 'version' | string | $version (API Version that the application understands)                    |      YES |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
`server`, `version` and `compatible` are always returned. Sending a valid `auth` extends the session and adds the handshake fields (`session_expire`, server counts, ...).

| Field               | Type              | Nullable | Optional | Notes |
|---------------------|-------------------|:--------:|:--------:|-------|
| server              | string            |    NO    |    NO    |       |
| version             | string            |    NO    |    NO    |       |
| compatible          | string            |    NO    |    NO    |       |
| auth                | string            |   YES    |   YES    |       |
| streamtoken         | string            |   YES    |   YES    |       |
| api                 | string            |    NO    |   YES    |       |
| session_expire      | integer \| string |    NO    |   YES    |       |
| update              | string            |    NO    |   YES    |       |
| add                 | string            |    NO    |   YES    |       |
| clean               | string            |    NO    |   YES    |       |
| max_song            | integer           |    NO    |   YES    |       |
| max_album           | integer           |    NO    |   YES    |       |
| max_artist          | integer           |    NO    |   YES    |       |
| max_video           | integer           |    NO    |   YES    |       |
| max_podcast         | integer           |    NO    |   YES    |       |
| max_podcast_episode | integer           |    NO    |   YES    |       |
| songs               | integer           |    NO    |   YES    |       |
| albums              | integer           |    NO    |   YES    |       |
| artists             | integer           |    NO    |   YES    |       |
| genres              | integer           |    NO    |   YES    |       |
| playlists           | integer           |    NO    |   YES    |       |
| searches            | integer           |    NO    |   YES    |       |
| playlists_searches  | integer           |    NO    |   YES    |       |
| users               | integer           |    NO    |   YES    |       |
| catalogs            | integer           |    NO    |   YES    |       |
| videos              | integer           |    NO    |   YES    |       |
| podcasts            | integer           |    NO    |   YES    |       |
| podcast_episodes    | integer           |    NO    |   YES    |       |
| shares              | integer           |    NO    |   YES    |       |
| licenses            | integer           |    NO    |   YES    |       |
| live_streams        | integer           |    NO    |   YES    |       |
| labels              | integer           |    NO    |   YES    |       |
| username            | string            |    NO    |   YES    |       |
<!-- GENERATED:RESPONSE:END -->

* throws array

```JSON
"server": "",
"version": "",
"compatible": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/ping.json)

### register

Register as a new user if allowed. (Requires the username, password and email.)

| Input      | Type   | Description               | Optional |
|------------|--------|---------------------------|---------:|
| 'username' | string | $username                 |       NO |
| 'password' | string | hash('sha256', $password) |       NO |
| 'email'    | string | e.g. `user@gmail.com`     |       NO |
| 'fullname' | string |                           |      YES |

**NOTE** For privacy, send `password` in a form or JSON request body rather than the query string. Query-string support for `password` is deprecated and will be removed in **API9**.

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

## Non-Data Methods

These methods take no parameters beyond your auth key to return information

### system_update

Check Ampache for updates and run the update if there is one.

**ACCESS REQUIRED:** 100 (Admin)

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field   | Type   | Nullable | Optional | Notes |
|---------|--------|:--------:|:--------:|-------|
| success | string |    NO    |   YES    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/system_update.json)

### system_preferences

Get your server preferences

**ACCESS REQUIRED:** 100 (Admin)

* You can modify and update your preferences using the following methods
  * [preference_create](#preference_create)
  * [preference_delete](#preference_delete)
  * [preference_edit](#preference_edit)

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `preference` list.

| Field      | Type                                              | Nullable | Optional | Notes                                           |
|------------|---------------------------------------------------|:--------:|:--------:|-------------------------------------------------|
| preference | array&lt;[PreferenceObject](#user_preference)&gt; |    NO    |    NO    | see [PreferenceObject](#user_preference) fields |

Each `preference` entry ([PreferenceObject](#user_preference)):

| Field       | Type                                        | Nullable | Optional | Notes |
|-------------|---------------------------------------------|:--------:|:--------:|-------|
| id          | string                                      |    NO    |    NO    |       |
| name        | string                                      |    NO    |    NO    |       |
| value       | string                                      |    NO    |    NO    |       |
| description | string                                      |    NO    |    NO    |       |
| level       | integer                                     |    NO    |    NO    |       |
| type        | string                                      |    NO    |    NO    |       |
| category    | string                                      |    NO    |    NO    |       |
| subcategory | string                                      |   YES    |    NO    |       |
| has_access  | boolean                                     |    NO    |   YES    |       |
| values      | array&lt;string&gt; \| array&lt;integer&gt; |    NO    |   YES    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/system_preferences.json)

### users

Get ids and usernames for your site

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `user` list.

| Field | Type                             | Nullable | Optional | Notes                          |
|-------|----------------------------------|:--------:|:--------:|--------------------------------|
| user  | array&lt;`UserSummaryObject`&gt; |    NO    |    NO    | see `UserSummaryObject` fields |

Each `user` entry (`UserSummaryObject`):

| Field    | Type   | Nullable | Optional | Notes |
|----------|--------|:--------:|:--------:|-------|
| id       | string |    NO    |    NO    |       |
| username | string |   YES    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/users.json)

### user_preferences

Get your user preferences

* You can modify and update your preferences using the following methods
  * [preference_create](#preference_create)
  * [preference_delete](#preference_delete)
  * [preference_edit](#preference_edit)

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `preference` list.

| Field      | Type                                              | Nullable | Optional | Notes                                           |
|------------|---------------------------------------------------|:--------:|:--------:|-------------------------------------------------|
| preference | array&lt;[PreferenceObject](#user_preference)&gt; |    NO    |    NO    | see [PreferenceObject](#user_preference) fields |

Each `preference` entry ([PreferenceObject](#user_preference)):

| Field       | Type                                        | Nullable | Optional | Notes |
|-------------|---------------------------------------------|:--------:|:--------:|-------|
| id          | string                                      |    NO    |    NO    |       |
| name        | string                                      |    NO    |    NO    |       |
| value       | string                                      |    NO    |    NO    |       |
| description | string                                      |    NO    |    NO    |       |
| level       | integer                                     |    NO    |    NO    |       |
| type        | string                                      |    NO    |    NO    |       |
| category    | string                                      |    NO    |    NO    |       |
| subcategory | string                                      |   YES    |    NO    |       |
| has_access  | boolean                                     |    NO    |   YES    |       |
| values      | array&lt;string&gt; \| array&lt;integer&gt; |    NO    |   YES    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/user_preferences.json)

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

* return array

```JSON
"song": []|"album": []|"artist": []|"playlist": []|"label": []|"user": []|"video": []
```

* throws object

```JSON
"error": ""
```

SONG [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/advanced_search%20\(song\).json)

ARTIST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/advanced_search%20\(artist\).json)

ALBUM [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/advanced_search%20\(album\).json)

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

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `album` list.

| Field       | Type                               | Nullable | Optional | Notes                            |
|-------------|------------------------------------|:--------:|:--------:|----------------------------------|
| total_count | integer                            |    NO    |    NO    |                                  |
| md5         | string                             |    NO    |    NO    |                                  |
| album       | array&lt;[AlbumObject](#album)&gt; |    NO    |    NO    | see [AlbumObject](#album) fields |

Each `album` entry ([AlbumObject](#album)):

| Field         | Type                             | Nullable | Optional | Notes                          |
|---------------|----------------------------------|:--------:|:--------:|--------------------------------|
| id            | string                           |    NO    |    NO    |                                |
| name          | string                           |   YES    |    NO    |                                |
| prefix        | string                           |   YES    |    NO    |                                |
| basename      | string                           |   YES    |    NO    |                                |
| artist        | object                           |   YES    |   YES    | `{id, name, prefix, basename}` |
| artists       | array&lt;object&gt;              |    NO    |   YES    | `{id, name, prefix, basename}` |
| songartists   | array&lt;object&gt;              |    NO    |   YES    | `{id, name, prefix, basename}` |
| time          | integer                          |    NO    |    NO    |                                |
| year          | integer                          |    NO    |    NO    |                                |
| tracks        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |
| songcount     | integer                          |    NO    |    NO    |                                |
| diskcount     | integer                          |    NO    |    NO    |                                |
| type          | string                           |   YES    |    NO    |                                |
| genre         | array&lt;object&gt;              |    NO    |    NO    | `{id, name}`                   |
| art           | string                           |   YES    |    NO    |                                |
| has_art       | boolean                          |    NO    |    NO    |                                |
| flag          | boolean                          |    NO    |    NO    |                                |
| rating        | integer                          |   YES    |    NO    |                                |
| averagerating | number                           |   YES    |    NO    |                                |
| mbid          | string                           |   YES    |    NO    |                                |
| mbid_group    | string                           |   YES    |    NO    |                                |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/albums.json)

### album

This returns a single album based on the UID provided

| Input     | Type   | Description                                     | Optional |
|-----------|--------|-------------------------------------------------|---------:|
| 'filter'  | string | UID of Album, returns album JSON                |       NO |
| 'include' | string | `songs` (include child objects in the response) |      YES |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field         | Type                             | Nullable | Optional | Notes                          |
|---------------|----------------------------------|:--------:|:--------:|--------------------------------|
| id            | string                           |    NO    |    NO    |                                |
| name          | string                           |   YES    |    NO    |                                |
| prefix        | string                           |   YES    |    NO    |                                |
| basename      | string                           |   YES    |    NO    |                                |
| artist        | object                           |   YES    |   YES    | `{id, name, prefix, basename}` |
| artists       | array&lt;object&gt;              |    NO    |   YES    | `{id, name, prefix, basename}` |
| songartists   | array&lt;object&gt;              |    NO    |   YES    | `{id, name, prefix, basename}` |
| time          | integer                          |    NO    |    NO    |                                |
| year          | integer                          |    NO    |    NO    |                                |
| tracks        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |
| songcount     | integer                          |    NO    |    NO    |                                |
| diskcount     | integer                          |    NO    |    NO    |                                |
| type          | string                           |   YES    |    NO    |                                |
| genre         | array&lt;object&gt;              |    NO    |    NO    | `{id, name}`                   |
| art           | string                           |   YES    |    NO    |                                |
| has_art       | boolean                          |    NO    |    NO    |                                |
| flag          | boolean                          |    NO    |    NO    |                                |
| rating        | integer                          |   YES    |    NO    |                                |
| averagerating | number                           |   YES    |    NO    |                                |
| mbid          | string                           |   YES    |    NO    |                                |
| mbid_group    | string                           |   YES    |    NO    |                                |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/album.json)

### album_songs

This returns the songs of a specified album

| Input    | Type    | Description                                                | Optional |
|----------|---------|------------------------------------------------------------|---------:|
| 'filter' | string  | UID of Album, returns song JSON                            |       NO |
| 'offset' | integer | Return results starting from this index position           |      YES |
| 'limit'  | integer | Maximum number of results to return                        |      YES |
| 'cond'   | string  | Apply additional filters to the browse using `;` separated |      YES |
|          |         | comma string pairs (e.g. 'filter1,value1;filter2,value2')  |          |
| 'sort'   | string  | Sort name or comma-separated key pair. (e.g. 'name,order') |      YES |
|          |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')            |          |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `song` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| song        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |

Each `song` entry ([SongObject](#song)):

| Field                 | Type                         | Nullable | Optional | Notes                          |
|-----------------------|------------------------------|:--------:|:--------:|--------------------------------|
| id                    | string                       |    NO    |    NO    |                                |
| title                 | string                       |   YES    |    NO    |                                |
| name                  | string                       |   YES    |    NO    |                                |
| artist                | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| artists               | array&lt;object&gt;          |    NO    |    NO    | `{id, name, prefix, basename}` |
| album                 | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| albumartist           | object                       |    NO    |   YES    | `{id, name, prefix, basename}` |
| disk                  | integer                      |    NO    |    NO    |                                |
| disksubtitle          | string                       |   YES    |    NO    |                                |
| track                 | integer                      |    NO    |    NO    |                                |
| filename              | string                       |   YES    |    NO    |                                |
| genre                 | array&lt;object&gt;          |    NO    |    NO    | `{id, name}`                   |
| playlisttrack         | integer                      |    NO    |    NO    |                                |
| time                  | integer                      |    NO    |    NO    |                                |
| year                  | integer                      |    NO    |    NO    |                                |
| format                | string                       |   YES    |    NO    |                                |
| stream_format         | string                       |   YES    |    NO    |                                |
| bitrate               | integer                      |   YES    |    NO    |                                |
| stream_bitrate        | integer                      |   YES    |    NO    |                                |
| rate                  | integer                      |    NO    |    NO    |                                |
| mode                  | string                       |   YES    |    NO    |                                |
| mime                  | string                       |   YES    |    NO    |                                |
| stream_mime           | string                       |   YES    |    NO    |                                |
| url                   | string                       |    NO    |    NO    |                                |
| size                  | integer                      |    NO    |    NO    |                                |
| mbid                  | string                       |   YES    |    NO    |                                |
| art                   | string                       |   YES    |    NO    |                                |
| has_art               | boolean                      |    NO    |    NO    |                                |
| flag                  | boolean                      |    NO    |    NO    |                                |
| rating                | integer                      |   YES    |    NO    |                                |
| averagerating         | number                       |   YES    |    NO    |                                |
| playcount             | integer                      |    NO    |    NO    |                                |
| catalog               | string                       |    NO    |    NO    |                                |
| composer              | string                       |   YES    |    NO    |                                |
| channels              | integer                      |   YES    |    NO    |                                |
| comment               | string                       |   YES    |    NO    |                                |
| license               | string                       |   YES    |    NO    |                                |
| publisher             | string                       |   YES    |    NO    |                                |
| language              | string                       |   YES    |    NO    |                                |
| lyrics                | string                       |   YES    |    NO    |                                |
| replaygain_album_gain | number                       |   YES    |    NO    |                                |
| replaygain_album_peak | number                       |   YES    |    NO    |                                |
| replaygain_track_gain | number                       |   YES    |    NO    |                                |
| replaygain_track_peak | number                       |   YES    |    NO    |                                |
| r128_album_gain       | number                       |   YES    |    NO    |                                |
| r128_track_gain       | number                       |   YES    |    NO    |                                |
| metadata              | object&lt;string, string&gt; |    NO    |   YES    |                                |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/album_songs.json)

### album_disks

This returns the disks of a specified album

Album disks are the browsing unit whenever the `album_group` preference is disabled. This method is
API 8 only; `albums` and `album` never change shape based on that preference.

| Input     | Type    | Description                                                | Optional |
|-----------|---------|------------------------------------------------------------|---------:|
| 'filter'  | string  | UID of Album, returns album_disk JSON                      |       NO |
| 'include' | string  | `songs` (include child objects in the response)            |      YES |
| 'offset'  | integer | Return results starting from this index position           |      YES |
| 'limit'   | integer | Maximum number of results to return                        |      YES |
| 'cond'    | string  | Apply additional filters to the browse using `;` separated |      YES |
|           |         | comma string pairs (e.g. 'filter1,value1;filter2,value2')  |          |
| 'sort'    | string  | Sort name or comma-separated key pair. (e.g. 'name,order') |      YES |
|           |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')            |          |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `album_disk` list.

| Field       | Type                                        | Nullable | Optional | Notes                                     |
|-------------|---------------------------------------------|:--------:|:--------:|-------------------------------------------|
| total_count | integer                                     |    NO    |    NO    |                                           |
| md5         | string                                      |    NO    |    NO    |                                           |
| album_disk  | array&lt;[AlbumDiskObject](#album_disk)&gt; |    NO    |    NO    | see [AlbumDiskObject](#album_disk) fields |

Each `album_disk` entry ([AlbumDiskObject](#album_disk)):

| Field         | Type                             | Nullable | Optional | Notes                          |
|---------------|----------------------------------|:--------:|:--------:|--------------------------------|
| id            | string                           |    NO    |    NO    |                                |
| name          | string                           |    NO    |    NO    |                                |
| prefix        | string                           |   YES    |    NO    |                                |
| basename      | string                           |   YES    |    NO    |                                |
| album         | object                           |    NO    |    NO    | `{id, name, prefix, basename}` |
| artist        | object                           |    NO    |   YES    | `{id, name, prefix, basename}` |
| artists       | array&lt;object&gt;              |    NO    |   YES    | `{id, name, prefix, basename}` |
| songartists   | array&lt;object&gt;              |    NO    |   YES    | `{id, name, prefix, basename}` |
| disk          | integer                          |    NO    |    NO    |                                |
| disksubtitle  | string                           |   YES    |    NO    |                                |
| time          | integer                          |    NO    |    NO    |                                |
| year          | integer                          |    NO    |    NO    |                                |
| tracks        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |
| songcount     | integer                          |    NO    |    NO    |                                |
| type          | string                           |   YES    |    NO    |                                |
| genre         | array&lt;object&gt;              |    NO    |    NO    | `{id, name}`                   |
| art           | string                           |   YES    |    NO    |                                |
| has_art       | boolean                          |    NO    |    NO    |                                |
| flag          | boolean                          |    NO    |    NO    |                                |
| rating        | integer                          |   YES    |    NO    |                                |
| averagerating | number                           |   YES    |    NO    |                                |
| mbid          | string                           |   YES    |    NO    |                                |
| mbid_group    | string                           |   YES    |    NO    |                                |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

### album_disk

This returns a single album disk based on the UID provided

| Input     | Type   | Description                                     | Optional |
|-----------|--------|-------------------------------------------------|---------:|
| 'filter'  | string | UID of AlbumDisk, returns album_disk JSON       |       NO |
| 'include' | string | `songs` (include child objects in the response) |      YES |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field         | Type                             | Nullable | Optional | Notes                          |
|---------------|----------------------------------|:--------:|:--------:|--------------------------------|
| id            | string                           |    NO    |    NO    |                                |
| name          | string                           |    NO    |    NO    |                                |
| prefix        | string                           |   YES    |    NO    |                                |
| basename      | string                           |   YES    |    NO    |                                |
| album         | object                           |    NO    |    NO    | `{id, name, prefix, basename}` |
| artist        | object                           |    NO    |   YES    | `{id, name, prefix, basename}` |
| artists       | array&lt;object&gt;              |    NO    |   YES    | `{id, name, prefix, basename}` |
| songartists   | array&lt;object&gt;              |    NO    |   YES    | `{id, name, prefix, basename}` |
| disk          | integer                          |    NO    |    NO    |                                |
| disksubtitle  | string                           |   YES    |    NO    |                                |
| time          | integer                          |    NO    |    NO    |                                |
| year          | integer                          |    NO    |    NO    |                                |
| tracks        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |
| songcount     | integer                          |    NO    |    NO    |                                |
| type          | string                           |   YES    |    NO    |                                |
| genre         | array&lt;object&gt;              |    NO    |    NO    | `{id, name}`                   |
| art           | string                           |   YES    |    NO    |                                |
| has_art       | boolean                          |    NO    |    NO    |                                |
| flag          | boolean                          |    NO    |    NO    |                                |
| rating        | integer                          |   YES    |    NO    |                                |
| averagerating | number                           |   YES    |    NO    |                                |
| mbid          | string                           |   YES    |    NO    |                                |
| mbid_group    | string                           |   YES    |    NO    |                                |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

### album_disk_songs

This returns the songs of a specified album disk

| Input    | Type    | Description                                                | Optional |
|----------|---------|------------------------------------------------------------|---------:|
| 'filter' | string  | UID of AlbumDisk, returns song JSON                        |       NO |
| 'offset' | integer | Return results starting from this index position           |      YES |
| 'limit'  | integer | Maximum number of results to return                        |      YES |
| 'cond'   | string  | Apply additional filters to the browse using `;` separated |      YES |
|          |         | comma string pairs (e.g. 'filter1,value1;filter2,value2')  |          |
| 'sort'   | string  | Sort name or comma-separated key pair. (e.g. 'name,order') |      YES |
|          |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')            |          |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `song` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| song        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |

Each `song` entry ([SongObject](#song)):

| Field                 | Type                         | Nullable | Optional | Notes                          |
|-----------------------|------------------------------|:--------:|:--------:|--------------------------------|
| id                    | string                       |    NO    |    NO    |                                |
| title                 | string                       |   YES    |    NO    |                                |
| name                  | string                       |   YES    |    NO    |                                |
| artist                | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| artists               | array&lt;object&gt;          |    NO    |    NO    | `{id, name, prefix, basename}` |
| album                 | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| albumartist           | object                       |    NO    |   YES    | `{id, name, prefix, basename}` |
| disk                  | integer                      |    NO    |    NO    |                                |
| disksubtitle          | string                       |   YES    |    NO    |                                |
| track                 | integer                      |    NO    |    NO    |                                |
| filename              | string                       |   YES    |    NO    |                                |
| genre                 | array&lt;object&gt;          |    NO    |    NO    | `{id, name}`                   |
| playlisttrack         | integer                      |    NO    |    NO    |                                |
| time                  | integer                      |    NO    |    NO    |                                |
| year                  | integer                      |    NO    |    NO    |                                |
| format                | string                       |   YES    |    NO    |                                |
| stream_format         | string                       |   YES    |    NO    |                                |
| bitrate               | integer                      |   YES    |    NO    |                                |
| stream_bitrate        | integer                      |   YES    |    NO    |                                |
| rate                  | integer                      |    NO    |    NO    |                                |
| mode                  | string                       |   YES    |    NO    |                                |
| mime                  | string                       |   YES    |    NO    |                                |
| stream_mime           | string                       |   YES    |    NO    |                                |
| url                   | string                       |    NO    |    NO    |                                |
| size                  | integer                      |    NO    |    NO    |                                |
| mbid                  | string                       |   YES    |    NO    |                                |
| art                   | string                       |   YES    |    NO    |                                |
| has_art               | boolean                      |    NO    |    NO    |                                |
| flag                  | boolean                      |    NO    |    NO    |                                |
| rating                | integer                      |   YES    |    NO    |                                |
| averagerating         | number                       |   YES    |    NO    |                                |
| playcount             | integer                      |    NO    |    NO    |                                |
| catalog               | string                       |    NO    |    NO    |                                |
| composer              | string                       |   YES    |    NO    |                                |
| channels              | integer                      |   YES    |    NO    |                                |
| comment               | string                       |   YES    |    NO    |                                |
| license               | string                       |   YES    |    NO    |                                |
| publisher             | string                       |   YES    |    NO    |                                |
| language              | string                       |   YES    |    NO    |                                |
| lyrics                | string                       |   YES    |    NO    |                                |
| replaygain_album_gain | number                       |   YES    |    NO    |                                |
| replaygain_album_peak | number                       |   YES    |    NO    |                                |
| replaygain_track_gain | number                       |   YES    |    NO    |                                |
| replaygain_track_peak | number                       |   YES    |    NO    |                                |
| r128_album_gain       | number                       |   YES    |    NO    |                                |
| r128_track_gain       | number                       |   YES    |    NO    |                                |
| metadata              | object&lt;string, string&gt; |    NO    |   YES    |                                |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

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

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `artist` list.

| Field       | Type                                 | Nullable | Optional | Notes                              |
|-------------|--------------------------------------|:--------:|:--------:|------------------------------------|
| total_count | integer                              |    NO    |    NO    |                                    |
| md5         | string                               |    NO    |    NO    |                                    |
| artist      | array&lt;[ArtistObject](#artist)&gt; |    NO    |    NO    | see [ArtistObject](#artist) fields |

Each `artist` entry ([ArtistObject](#artist)):

| Field         | Type                               | Nullable | Optional | Notes                            |
|---------------|------------------------------------|:--------:|:--------:|----------------------------------|
| id            | string                             |    NO    |    NO    |                                  |
| name          | string                             |   YES    |    NO    |                                  |
| prefix        | string                             |   YES    |    NO    |                                  |
| basename      | string                             |   YES    |    NO    |                                  |
| albums        | array&lt;[AlbumObject](#album)&gt; |    NO    |    NO    | see [AlbumObject](#album) fields |
| albumcount    | integer                            |    NO    |    NO    |                                  |
| songs         | array&lt;[SongObject](#song)&gt;   |    NO    |    NO    | see [SongObject](#song) fields   |
| songcount     | integer                            |    NO    |    NO    |                                  |
| genre         | array&lt;object&gt;                |    NO    |    NO    | `{id, name}`                     |
| art           | string                             |   YES    |    NO    |                                  |
| has_art       | boolean                            |    NO    |    NO    |                                  |
| flag          | boolean                            |    NO    |    NO    |                                  |
| rating        | integer                            |   YES    |    NO    |                                  |
| averagerating | number                             |   YES    |    NO    |                                  |
| mbid          | string                             |   YES    |    NO    |                                  |
| summary       | string                             |   YES    |    NO    |                                  |
| time          | integer                            |    NO    |    NO    |                                  |
| yearformed    | integer                            |    NO    |    NO    |                                  |
| placeformed   | string                             |   YES    |    NO    |                                  |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/artists.json)

### artist

This returns a single artist based on the UID of said artist

| Input     | Type   | Description                                               | Optional |
|-----------|--------|-----------------------------------------------------------|---------:|
| 'filter'  | string | UID of Artist, returns artist JSON                        |       NO |
| 'include' | string | `albums`, `songs` (include child objects in the response) |      YES |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field         | Type                               | Nullable | Optional | Notes                            |
|---------------|------------------------------------|:--------:|:--------:|----------------------------------|
| id            | string                             |    NO    |    NO    |                                  |
| name          | string                             |   YES    |    NO    |                                  |
| prefix        | string                             |   YES    |    NO    |                                  |
| basename      | string                             |   YES    |    NO    |                                  |
| albums        | array&lt;[AlbumObject](#album)&gt; |    NO    |    NO    | see [AlbumObject](#album) fields |
| albumcount    | integer                            |    NO    |    NO    |                                  |
| songs         | array&lt;[SongObject](#song)&gt;   |    NO    |    NO    | see [SongObject](#song) fields   |
| songcount     | integer                            |    NO    |    NO    |                                  |
| genre         | array&lt;object&gt;                |    NO    |    NO    | `{id, name}`                     |
| art           | string                             |   YES    |    NO    |                                  |
| has_art       | boolean                            |    NO    |    NO    |                                  |
| flag          | boolean                            |    NO    |    NO    |                                  |
| rating        | integer                            |   YES    |    NO    |                                  |
| averagerating | number                             |   YES    |    NO    |                                  |
| mbid          | string                             |   YES    |    NO    |                                  |
| summary       | string                             |   YES    |    NO    |                                  |
| time          | integer                            |    NO    |    NO    |                                  |
| yearformed    | integer                            |    NO    |    NO    |                                  |
| placeformed   | string                             |   YES    |    NO    |                                  |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/artist.json)

### artist_albums

This returns the albums of an artist

| Input          | Type    | Description                                                                   | Optional |
|----------------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter'       | string  | UID of Artist, returns Album JSON                                             |       NO |
| 'album_artist' | boolean | `0`, `1` (if true filter for album artists only)                              |      YES |
| 'offset'       | integer | Return results starting from this index position                              |      YES |
| 'limit'        | integer | Maximum number of results to return                                           |      YES |
| 'cond'         | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|                |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'         | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|                |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `album` list.

| Field       | Type                               | Nullable | Optional | Notes                            |
|-------------|------------------------------------|:--------:|:--------:|----------------------------------|
| total_count | integer                            |    NO    |    NO    |                                  |
| md5         | string                             |    NO    |    NO    |                                  |
| album       | array&lt;[AlbumObject](#album)&gt; |    NO    |    NO    | see [AlbumObject](#album) fields |

Each `album` entry ([AlbumObject](#album)):

| Field         | Type                             | Nullable | Optional | Notes                          |
|---------------|----------------------------------|:--------:|:--------:|--------------------------------|
| id            | string                           |    NO    |    NO    |                                |
| name          | string                           |   YES    |    NO    |                                |
| prefix        | string                           |   YES    |    NO    |                                |
| basename      | string                           |   YES    |    NO    |                                |
| artist        | object                           |   YES    |   YES    | `{id, name, prefix, basename}` |
| artists       | array&lt;object&gt;              |    NO    |   YES    | `{id, name, prefix, basename}` |
| songartists   | array&lt;object&gt;              |    NO    |   YES    | `{id, name, prefix, basename}` |
| time          | integer                          |    NO    |    NO    |                                |
| year          | integer                          |    NO    |    NO    |                                |
| tracks        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |
| songcount     | integer                          |    NO    |    NO    |                                |
| diskcount     | integer                          |    NO    |    NO    |                                |
| type          | string                           |   YES    |    NO    |                                |
| genre         | array&lt;object&gt;              |    NO    |    NO    | `{id, name}`                   |
| art           | string                           |   YES    |    NO    |                                |
| has_art       | boolean                          |    NO    |    NO    |                                |
| flag          | boolean                          |    NO    |    NO    |                                |
| rating        | integer                          |   YES    |    NO    |                                |
| averagerating | number                           |   YES    |    NO    |                                |
| mbid          | string                           |   YES    |    NO    |                                |
| mbid_group    | string                           |   YES    |    NO    |                                |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/artist_albums.json)

### artist_songs

This returns the songs of the specified artist

| Input    | Type    | Description                                                                   | Optional |
|----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter' | string  | UID of Artist, returns Song JSON                                              |       NO |
| 'top50'  | boolean | `0`, `1` (if true filter to the artist top 50)                                |      YES |
| 'offset' | integer | Return results starting from this index position                              |      YES |
| 'limit'  | integer | Maximum number of results to return                                           |      YES |
| 'cond'   | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|          |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'   | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|          |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `song` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| song        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |

Each `song` entry ([SongObject](#song)):

| Field                 | Type                         | Nullable | Optional | Notes                          |
|-----------------------|------------------------------|:--------:|:--------:|--------------------------------|
| id                    | string                       |    NO    |    NO    |                                |
| title                 | string                       |   YES    |    NO    |                                |
| name                  | string                       |   YES    |    NO    |                                |
| artist                | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| artists               | array&lt;object&gt;          |    NO    |    NO    | `{id, name, prefix, basename}` |
| album                 | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| albumartist           | object                       |    NO    |   YES    | `{id, name, prefix, basename}` |
| disk                  | integer                      |    NO    |    NO    |                                |
| disksubtitle          | string                       |   YES    |    NO    |                                |
| track                 | integer                      |    NO    |    NO    |                                |
| filename              | string                       |   YES    |    NO    |                                |
| genre                 | array&lt;object&gt;          |    NO    |    NO    | `{id, name}`                   |
| playlisttrack         | integer                      |    NO    |    NO    |                                |
| time                  | integer                      |    NO    |    NO    |                                |
| year                  | integer                      |    NO    |    NO    |                                |
| format                | string                       |   YES    |    NO    |                                |
| stream_format         | string                       |   YES    |    NO    |                                |
| bitrate               | integer                      |   YES    |    NO    |                                |
| stream_bitrate        | integer                      |   YES    |    NO    |                                |
| rate                  | integer                      |    NO    |    NO    |                                |
| mode                  | string                       |   YES    |    NO    |                                |
| mime                  | string                       |   YES    |    NO    |                                |
| stream_mime           | string                       |   YES    |    NO    |                                |
| url                   | string                       |    NO    |    NO    |                                |
| size                  | integer                      |    NO    |    NO    |                                |
| mbid                  | string                       |   YES    |    NO    |                                |
| art                   | string                       |   YES    |    NO    |                                |
| has_art               | boolean                      |    NO    |    NO    |                                |
| flag                  | boolean                      |    NO    |    NO    |                                |
| rating                | integer                      |   YES    |    NO    |                                |
| averagerating         | number                       |   YES    |    NO    |                                |
| playcount             | integer                      |    NO    |    NO    |                                |
| catalog               | string                       |    NO    |    NO    |                                |
| composer              | string                       |   YES    |    NO    |                                |
| channels              | integer                      |   YES    |    NO    |                                |
| comment               | string                       |   YES    |    NO    |                                |
| license               | string                       |   YES    |    NO    |                                |
| publisher             | string                       |   YES    |    NO    |                                |
| language              | string                       |   YES    |    NO    |                                |
| lyrics                | string                       |   YES    |    NO    |                                |
| replaygain_album_gain | number                       |   YES    |    NO    |                                |
| replaygain_album_peak | number                       |   YES    |    NO    |                                |
| replaygain_track_gain | number                       |   YES    |    NO    |                                |
| replaygain_track_peak | number                       |   YES    |    NO    |                                |
| r128_album_gain       | number                       |   YES    |    NO    |                                |
| r128_track_gain       | number                       |   YES    |    NO    |                                |
| metadata              | object&lt;string, string&gt; |    NO    |   YES    |                                |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/artist_songs.json)

### bookmarks

Get information about bookmarked media this user is allowed to manage.

| Input     | Type    | Description                                     | Optional |
|-----------|---------|-------------------------------------------------|---------:|
| 'client'  | string  | filter by the agent/client name                 |      YES |
| 'include' | integer | 0,1, if true include the object in the bookmark |      YES |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `bookmark` list.

| Field       | Type                                     | Nullable | Optional | Notes                                  |
|-------------|------------------------------------------|:--------:|:--------:|----------------------------------------|
| total_count | integer                                  |    NO    |    NO    |                                        |
| md5         | string                                   |    NO    |    NO    |                                        |
| bookmark    | array&lt;[BookmarkObject](#bookmark)&gt; |    NO    |    NO    | see [BookmarkObject](#bookmark) fields |

Each `bookmark` entry ([BookmarkObject](#bookmark)):

| Field           | Type                                                  | Nullable | Optional | Notes                                               |
|-----------------|-------------------------------------------------------|:--------:|:--------:|-----------------------------------------------------|
| id              | string                                                |    NO    |    NO    |                                                     |
| owner           | string                                                |    NO    |    NO    |                                                     |
| object_type     | string                                                |   YES    |    NO    |                                                     |
| object_id       | string                                                |    NO    |    NO    |                                                     |
| position        | integer                                               |    NO    |    NO    |                                                     |
| client          | string                                                |   YES    |    NO    |                                                     |
| creation_date   | integer                                               |    NO    |    NO    |                                                     |
| update_date     | integer                                               |    NO    |    NO    |                                                     |
| song            | array&lt;[SongObject](#song)&gt;                      |    NO    |   YES    | see [SongObject](#song) fields                      |
| podcast_episode | array&lt;[PodcastEpisodeObject](#podcast_episode)&gt; |    NO    |   YES    | see [PodcastEpisodeObject](#podcast_episode) fields |
| video           | array&lt;[VideoObject](#video)&gt;                    |    NO    |   YES    | see [VideoObject](#video) fields                    |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/bookmarks.json)
[Example (with include)](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/bookmarks%20\(with%20include\).json)

### bookmark

Get a single bookmark by bookmark_id

| Input     | Type    | Description                                     | Optional |
|-----------|---------|-------------------------------------------------|---------:|
| 'filter'  | string  | bookmark_id                                     |      YES |
| 'include' | integer | 0,1, if true include the object in the bookmark |      YES |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field           | Type                                                  | Nullable | Optional | Notes                                               |
|-----------------|-------------------------------------------------------|:--------:|:--------:|-----------------------------------------------------|
| id              | string                                                |    NO    |    NO    |                                                     |
| owner           | string                                                |    NO    |    NO    |                                                     |
| object_type     | string                                                |   YES    |    NO    |                                                     |
| object_id       | string                                                |    NO    |    NO    |                                                     |
| position        | integer                                               |    NO    |    NO    |                                                     |
| client          | string                                                |   YES    |    NO    |                                                     |
| creation_date   | integer                                               |    NO    |    NO    |                                                     |
| update_date     | integer                                               |    NO    |    NO    |                                                     |
| song            | array&lt;[SongObject](#song)&gt;                      |    NO    |   YES    | see [SongObject](#song) fields                      |
| podcast_episode | array&lt;[PodcastEpisodeObject](#podcast_episode)&gt; |    NO    |   YES    | see [PodcastEpisodeObject](#podcast_episode) fields |
| video           | array&lt;[VideoObject](#video)&gt;                    |    NO    |   YES    | see [VideoObject](#video) fields                    |
<!-- GENERATED:RESPONSE:END -->

* throws

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/bookmark.json)
[Example (with include)](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/bookmark%20\(with%20include\).json)

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

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field           | Type                                                  | Nullable | Optional | Notes                                               |
|-----------------|-------------------------------------------------------|:--------:|:--------:|-----------------------------------------------------|
| id              | string                                                |    NO    |    NO    |                                                     |
| owner           | string                                                |    NO    |    NO    |                                                     |
| object_type     | string                                                |   YES    |    NO    |                                                     |
| object_id       | string                                                |    NO    |    NO    |                                                     |
| position        | integer                                               |    NO    |    NO    |                                                     |
| client          | string                                                |   YES    |    NO    |                                                     |
| creation_date   | integer                                               |    NO    |    NO    |                                                     |
| update_date     | integer                                               |    NO    |    NO    |                                                     |
| song            | array&lt;[SongObject](#song)&gt;                      |    NO    |   YES    | see [SongObject](#song) fields                      |
| podcast_episode | array&lt;[PodcastEpisodeObject](#podcast_episode)&gt; |    NO    |   YES    | see [PodcastEpisodeObject](#podcast_episode) fields |
| video           | array&lt;[VideoObject](#video)&gt;                    |    NO    |   YES    | see [VideoObject](#video) fields                    |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/bookmark_create.json)

### bookmark_delete

Delete an existing bookmark. (if it exists)

| Input    | Type   | Description                                                       | Optional |
|----------|--------|-------------------------------------------------------------------|---------:|
| 'filter' | string | $object_id to delete                                              |       NO |
| 'type'   | string | `bookmark`, `song`, `video`, `podcast_episode`, default: bookmark |       NO |
| 'client' | string | Agent string.                                                     |      YES |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/bookmark_delete)

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

* return array

```JSON
"bookmark": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/bookmark_edit.json)

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

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `browse` list.

| Field       | Type                        | Nullable | Optional | Notes                     |
|-------------|-----------------------------|:--------:|:--------:|---------------------------|
| total_count | integer                     |    NO    |    NO    |                           |
| md5         | string                      |    NO    |    NO    |                           |
| catalog_id  | string                      |    NO    |    NO    |                           |
| parent_id   | string                      |    NO    |    NO    |                           |
| parent_type | string                      |    NO    |    NO    |                           |
| child_type  | string                      |    NO    |    NO    |                           |
| browse      | array&lt;`BrowseObject`&gt; |    NO    |    NO    | see `BrowseObject` fields |

Each `browse` entry (`BrowseObject`):

| Field    | Type   | Nullable | Optional | Notes |
|----------|--------|:--------:|:--------:|-------|
| id       | string |    NO    |    NO    |       |
| name     | string |    NO    |    NO    |       |
| prefix   | string |   YES    |    NO    |       |
| basename | string |    NO    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/browse%20\(root\).json)

[Example: music catalog](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/browse%20\(music%20catalog\).json)

[Example: podcast catalog](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/browse%20\(podcast%20catalog\).json)

[Example: video catalog](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/browse%20\(video%20catalog\).json)

[Example: artist](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/browse%20\(artist\).json)

[Example: album](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/browse%20\(album\).json)

[Example: podcast](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/browse%20\(podcast\).json)

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

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `catalog` list.

| Field       | Type                                   | Nullable | Optional | Notes                                |
|-------------|----------------------------------------|:--------:|:--------:|--------------------------------------|
| total_count | integer                                |    NO    |    NO    |                                      |
| md5         | string                                 |    NO    |    NO    |                                      |
| catalog     | array&lt;[CatalogObject](#catalog)&gt; |    NO    |    NO    | see [CatalogObject](#catalog) fields |

Each `catalog` entry ([CatalogObject](#catalog)):

| Field          | Type    | Nullable | Optional | Notes |
|----------------|---------|:--------:|:--------:|-------|
| id             | string  |    NO    |    NO    |       |
| name           | string  |   YES    |    NO    |       |
| type           | string  |   YES    |    NO    |       |
| gather_types   | string  |   YES    |    NO    |       |
| enabled        | boolean |    NO    |    NO    |       |
| last_add       | integer |    NO    |    NO    |       |
| last_clean     | integer |   YES    |    NO    |       |
| last_update    | integer |    NO    |    NO    |       |
| path           | string  |    NO    |    NO    |       |
| rename_pattern | string  |   YES    |    NO    |       |
| sort_pattern   | string  |   YES    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/catalogs.json)

### catalog

Return catalog by UID

| Input    | Type   | Description    | Optional |
|----------|--------|----------------|---------:|
| 'filter' | string | UID of Catalog |       NO |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field          | Type    | Nullable | Optional | Notes |
|----------------|---------|:--------:|:--------:|-------|
| id             | string  |    NO    |    NO    |       |
| name           | string  |   YES    |    NO    |       |
| type           | string  |   YES    |    NO    |       |
| gather_types   | string  |   YES    |    NO    |       |
| enabled        | boolean |    NO    |    NO    |       |
| last_add       | integer |    NO    |    NO    |       |
| last_clean     | integer |   YES    |    NO    |       |
| last_update    | integer |    NO    |    NO    |       |
| path           | string  |    NO    |    NO    |       |
| rename_pattern | string  |   YES    |    NO    |       |
| sort_pattern   | string  |   YES    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/catalog.json)

### catalog_action

Kick off a catalog update or clean for the selected catalog

**ACCESS REQUIRED:** 75 (Catalog Manager)

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `catalog` is deprecated and will be removed in **API9**.

| Input    | Type   | Description                       | Optional |
|----------|--------|-----------------------------------|---------:|
| 'task'   | string | `add_to_catalog`, `clean_catalog` |       NO |
| 'filter' | string | $catalog_id                       |       NO |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example: clean_catalog](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/catalog_action%20\(clean_catalog\).json)

[Example: add_to_catalog](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/catalog_action%20\(add_to_catalog\).json)

### catalog_add

Create a new catalog.

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

**NOTE** For privacy, send `password` in a form or JSON request body rather than the query string. Query-string support for `password` is deprecated and will be removed in **API9**.

* return array

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/catalog_create.json)

### catalog_delete

Delete an existing catalog.

**ACCESS REQUIRED:** 75 (Catalog Manager)

| Input    | Type   | Description              | Optional |
|----------|--------|--------------------------|---------:|
| 'filter' | string | UID of catalog to delete |       NO |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/catalog_delete.json)

### catalog_file

Perform actions on local catalog files.
Single file versions of catalog add, clean, verify and remove (delete)
Make sure you remember to urlencode those file names!

**ACCESS REQUIRED:** 50 (Content Manager)

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `catalog` is deprecated and will be removed in **API9**.

| Input    | Type   | Description                                                              | Optional |
|----------|--------|--------------------------------------------------------------------------|---------:|
| 'file'   | string | FULL path to local file                                                  |       NO |
| 'task'   | string | `add`, `clean`, `verify`, `remove`, (can include comma-separated values) |       NO |
| 'filter' | string | $catalog_id                                                              |       NO |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/catalog_file.json)

### catalog_folder

Perform actions on local catalog folders.
Single folder versions of catalog add, clean, verify and remove (delete)
Make sure you remember to urlencode those folder names!

**ACCESS REQUIRED:** 50 (Content Manager)

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `catalog` is deprecated and will be removed in **API9**.

| Input    | Type   | Description                                                              | Optional |
|----------|--------|--------------------------------------------------------------------------|---------:|
| 'folder' | string | FULL path to local folder                                                |       NO |
| 'task'   | string | `add`, `clean`, `verify`, `remove`, (can include comma-separated values) |       NO |
| 'filter' | string | $catalog_id                                                              |       NO |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field   | Type   | Nullable | Optional | Notes |
|---------|--------|:--------:|:--------:|-------|
| success | string |    NO    |   YES    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/catalog_folder.json)

### deleted_podcast_episodes

This returns the episodes for a podcast that have been deleted

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `deleted_podcast_episode` list.

| Field                   | Type                                       | Nullable | Optional | Notes                                    |
|-------------------------|--------------------------------------------|:--------:|:--------:|------------------------------------------|
| total_count             | integer                                    |    NO    |    NO    |                                          |
| md5                     | string                                     |    NO    |    NO    |                                          |
| deleted_podcast_episode | array&lt;`DeletedPodcastEpisodeObject`&gt; |    NO    |    NO    | see `DeletedPodcastEpisodeObject` fields |

Each `deleted_podcast_episode` entry (`DeletedPodcastEpisodeObject`):

| Field         | Type    | Nullable | Optional | Notes |
|---------------|---------|:--------:|:--------:|-------|
| id            | string  |    NO    |    NO    |       |
| addition_time | integer |    NO    |    NO    |       |
| delete_time   | integer |    NO    |    NO    |       |
| title         | string  |   YES    |    NO    |       |
| file          | string  |    NO    |    NO    |       |
| catalog       | string  |    NO    |    NO    |       |
| total_count   | integer |    NO    |    NO    |       |
| total_skip    | integer |    NO    |    NO    |       |
| podcast       | string  |    NO    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/deleted_podcast_episodes.json)

### deleted_songs

Returns songs that have been deleted from the server

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `deleted_song` list.

| Field        | Type                             | Nullable | Optional | Notes                          |
|--------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count  | integer                          |    NO    |    NO    |                                |
| md5          | string                           |    NO    |    NO    |                                |
| deleted_song | array&lt;`DeletedSongObject`&gt; |    NO    |    NO    | see `DeletedSongObject` fields |

Each `deleted_song` entry (`DeletedSongObject`):

| Field         | Type    | Nullable | Optional | Notes |
|---------------|---------|:--------:|:--------:|-------|
| id            | string  |    NO    |    NO    |       |
| addition_time | integer |    NO    |    NO    |       |
| delete_time   | integer |    NO    |    NO    |       |
| title         | string  |   YES    |    NO    |       |
| file          | string  |    NO    |    NO    |       |
| catalog       | string  |    NO    |    NO    |       |
| total_count   | integer |    NO    |    NO    |       |
| total_skip    | integer |    NO    |    NO    |       |
| update_time   | integer |    NO    |    NO    |       |
| album         | string  |    NO    |    NO    |       |
| artist        | string  |    NO    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/deleted_songs.json)

### deleted_videos

This returns video objects that have been deleted

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `deleted_video` list.

| Field         | Type                              | Nullable | Optional | Notes                           |
|---------------|-----------------------------------|:--------:|:--------:|---------------------------------|
| total_count   | integer                           |    NO    |    NO    |                                 |
| md5           | string                            |    NO    |    NO    |                                 |
| deleted_video | array&lt;`DeletedVideoObject`&gt; |    NO    |    NO    | see `DeletedVideoObject` fields |

Each `deleted_video` entry (`DeletedVideoObject`):

| Field         | Type    | Nullable | Optional | Notes |
|---------------|---------|:--------:|:--------:|-------|
| id            | string  |    NO    |    NO    |       |
| addition_time | integer |    NO    |    NO    |       |
| delete_time   | integer |    NO    |    NO    |       |
| title         | string  |   YES    |    NO    |       |
| file          | string  |    NO    |    NO    |       |
| catalog       | string  |    NO    |    NO    |       |
| total_count   | integer |    NO    |    NO    |       |
| total_skip    | integer |    NO    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/deleted_videos.json)

### flag

This flags a library item as a favorite

* Setting flag to true (1) will set the flag
* Setting flag to false (0) will remove the flag

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `id` is deprecated and will be removed in **API9**.

| Input    | Type    | Description                                           | Optional |
|----------|---------|-------------------------------------------------------|---------:|
| 'type'   | string  | `song`, `album`, `artist`, `playlist`, `podcast`      |       NO |
|          |         | `podcast_episode`, `video`, `tvshow`, `tvshow_season` |          |
| 'filter' | string  | $object_id                                            |       NO |
| 'flag'   | boolean | `0`, `1`                                              |       NO |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/flag.json)

### folder

Return children of a parent folder object by ID **Ampache 8.0.0+**

| Input    | Type       | Description                                                                                        | Optional |
|----------|------------|----------------------------------------------------------------------------------------------------|---------:|
| 'filter' | integer    | UID of the folder object (Default: -1, the root folder)                                            |      YES |
| 'add'    | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'add' date newer than the specified date    |      YES |
| 'update' | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'update' time newer than the specified date |      YES |
| 'offset' | integer    | Return results starting from this index position                                                   |      YES |
| 'limit'  | integer    | Maximum number of results to return                                                                |      YES |
| 'cond'   | string     | Apply additional filters to the browse using `;` separated comma string pairs                      |      YES |
|          |            | (e.g. 'filter1,value1;filter2,value2')                                                             |          |
| 'sort'   | string     | Sort name or comma-separated key pair. (e.g. 'name,order')                                         |      YES |
|          |            | Default order 'ASC' (e.g. 'name,ASC' == 'name')                                                    |          |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field   | Type                            | Nullable | Optional | Notes                         |
|---------|---------------------------------|:--------:|:--------:|-------------------------------|
| id      | string                          |    NO    |    NO    |                               |
| title   | string                          |   YES    |    NO    |                               |
| parent  | integer                         |   YES    |    NO    |                               |
| path    | string                          |   YES    |    NO    |                               |
| catalog | integer                         |    NO    |    NO    |                               |
| items   | array&lt;`FolderBrowseItem`&gt; |    NO    |    NO    | see `FolderBrowseItem` fields |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

### folders

Return children of a parent object in a folder traversal style **Ampache 8.0.0+**

| Input    | Type       | Description                                                                                        | Optional |
|----------|------------|----------------------------------------------------------------------------------------------------|---------:|
| 'filter' | string     | Path name filter (Default: '/', the root folder)                                                   |      YES |
| 'exact'  | boolean    | `0`, `1` (if true filter is exact rather than fuzzy; default: 1)                                   |      YES |
| 'add'    | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'add' date newer than the specified date    |      YES |
| 'update' | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'update' time newer than the specified date |      YES |
| 'offset' | integer    | Return results starting from this index position                                                   |      YES |
| 'limit'  | integer    | Maximum number of results to return                                                                |      YES |
| 'cond'   | string     | Apply additional filters to the browse using `;` separated comma string pairs                      |      YES |
|          |            | (e.g. 'filter1,value1;filter2,value2')                                                             |          |
| 'sort'   | string     | Sort name or comma-separated key pair. (e.g. 'name,order')                                         |      YES |
|          |            | Default order 'ASC' (e.g. 'name,ASC' == 'name')                                                    |          |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field       | Type                        | Nullable | Optional | Notes                                  |
|-------------|-----------------------------|:--------:|:--------:|----------------------------------------|
| total_count | integer                     |    NO    |    NO    |                                        |
| md5         | string                      |    NO    |    NO    |                                        |
| folder      | [FolderBrowseNode](#folder) |    NO    |    NO    | see [FolderBrowseNode](#folder) fields |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

### followers

This gets the followers for the requested username

| Input      | Type    | Description                                                                   | Optional |
|------------|---------|-------------------------------------------------------------------------------|---------:|
| 'username' | string  | Username of the user to get followers list                                    |       NO |
| 'offset'   | integer | Return results starting from this index position                              |      YES |
| 'limit'    | integer | Maximum number of results to return                                           |      YES |
| 'cond'     | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|            |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'     | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|            |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `user` list.

| Field | Type                             | Nullable | Optional | Notes                          |
|-------|----------------------------------|:--------:|:--------:|--------------------------------|
| user  | array&lt;`UserSummaryObject`&gt; |    NO    |    NO    | see `UserSummaryObject` fields |

Each `user` entry (`UserSummaryObject`):

| Field    | Type   | Nullable | Optional | Notes |
|----------|--------|:--------:|:--------:|-------|
| id       | string |    NO    |    NO    |       |
| username | string |   YES    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/followers.json)

### following

Get a list of people that this user follows

| Input      | Type   | Description                                | Optional |
|------------|--------|--------------------------------------------|---------:|
| 'username' | string | Username of the user to get following list |       NO |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `user` list.

| Field | Type                             | Nullable | Optional | Notes                          |
|-------|----------------------------------|:--------:|:--------:|--------------------------------|
| user  | array&lt;`UserSummaryObject`&gt; |    NO    |    NO    | see `UserSummaryObject` fields |

Each `user` entry (`UserSummaryObject`):

| Field    | Type   | Nullable | Optional | Notes |
|----------|--------|:--------:|:--------:|-------|
| id       | string |    NO    |    NO    |       |
| username | string |   YES    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/following.json)

### friends_timeline

This get current user friends timeline

| Input   | Type    | Description                         | Optional |
|---------|---------|-------------------------------------|---------:|
| 'limit' | integer | Maximum number of results to return |      YES |
| 'since' | integer | UNIXTIME()                          |       NO |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `activity` list.

| Field    | Type                          | Nullable | Optional | Notes                       |
|----------|-------------------------------|:--------:|:--------:|-----------------------------|
| activity | array&lt;`ActivityObject`&gt; |    NO    |    NO    | see `ActivityObject` fields |

Each `activity` entry (`ActivityObject`):

| Field       | Type    | Nullable | Optional | Notes            |
|-------------|---------|:--------:|:--------:|------------------|
| id          | string  |    NO    |    NO    |                  |
| date        | integer |    NO    |    NO    |                  |
| object_type | string  |   YES    |    NO    |                  |
| object_id   | string  |    NO    |    NO    |                  |
| action      | string  |    NO    |    NO    |                  |
| user        | object  |    NO    |    NO    | `{id, username}` |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/friends_timeline.json)

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

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `genre` list.

| Field       | Type                               | Nullable | Optional | Notes                            |
|-------------|------------------------------------|:--------:|:--------:|----------------------------------|
| total_count | integer                            |    NO    |    NO    |                                  |
| md5         | string                             |    NO    |    NO    |                                  |
| genre       | array&lt;[GenreObject](#genre)&gt; |    NO    |    NO    | see [GenreObject](#genre) fields |

Each `genre` entry ([GenreObject](#genre)):

| Field        | Type                | Nullable | Optional | Notes        |
|--------------|---------------------|:--------:|:--------:|--------------|
| id           | string              |    NO    |    NO    |              |
| name         | string              |   YES    |    NO    |              |
| albums       | integer             |    NO    |    NO    |              |
| artists      | integer             |    NO    |    NO    |              |
| songs        | integer             |    NO    |    NO    |              |
| videos       | integer             |    NO    |    NO    |              |
| playlists    | integer             |    NO    |    NO    |              |
| live_streams | integer             |    NO    |    NO    |              |
| is_hidden    | boolean             |    NO    |    NO    |              |
| merge        | array&lt;object&gt; |    NO    |    NO    | `{id, name}` |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/genres.json)

### genre

This returns a single genre based on UID

| Input    | Type   | Description                      | Optional |
|----------|--------|----------------------------------|---------:|
| 'filter' | string | UID of genre, returns genre JSON |       NO |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field        | Type                | Nullable | Optional | Notes        |
|--------------|---------------------|:--------:|:--------:|--------------|
| id           | string              |    NO    |    NO    |              |
| name         | string              |   YES    |    NO    |              |
| albums       | integer             |    NO    |    NO    |              |
| artists      | integer             |    NO    |    NO    |              |
| songs        | integer             |    NO    |    NO    |              |
| videos       | integer             |    NO    |    NO    |              |
| playlists    | integer             |    NO    |    NO    |              |
| live_streams | integer             |    NO    |    NO    |              |
| is_hidden    | boolean             |    NO    |    NO    |              |
| merge        | array&lt;object&gt; |    NO    |    NO    | `{id, name}` |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/genre.json)

### genre_albums

This returns the albums associated with the genre in question

| Input    | Type    | Description                                                                   | Optional |
|----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter' | string  | UID of genre, returns album JSON                                              |       NO |
| 'offset' | integer | Return results starting from this index position                              |      YES |
| 'limit'  | integer | Maximum number of results to return                                           |      YES |
| 'cond'   | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|          |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'   | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|          |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `album` list.

| Field       | Type                               | Nullable | Optional | Notes                            |
|-------------|------------------------------------|:--------:|:--------:|----------------------------------|
| total_count | integer                            |    NO    |    NO    |                                  |
| md5         | string                             |    NO    |    NO    |                                  |
| album       | array&lt;[AlbumObject](#album)&gt; |    NO    |    NO    | see [AlbumObject](#album) fields |

Each `album` entry ([AlbumObject](#album)):

| Field         | Type                             | Nullable | Optional | Notes                          |
|---------------|----------------------------------|:--------:|:--------:|--------------------------------|
| id            | string                           |    NO    |    NO    |                                |
| name          | string                           |   YES    |    NO    |                                |
| prefix        | string                           |   YES    |    NO    |                                |
| basename      | string                           |   YES    |    NO    |                                |
| artist        | object                           |   YES    |   YES    | `{id, name, prefix, basename}` |
| artists       | array&lt;object&gt;              |    NO    |   YES    | `{id, name, prefix, basename}` |
| songartists   | array&lt;object&gt;              |    NO    |   YES    | `{id, name, prefix, basename}` |
| time          | integer                          |    NO    |    NO    |                                |
| year          | integer                          |    NO    |    NO    |                                |
| tracks        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |
| songcount     | integer                          |    NO    |    NO    |                                |
| diskcount     | integer                          |    NO    |    NO    |                                |
| type          | string                           |   YES    |    NO    |                                |
| genre         | array&lt;object&gt;              |    NO    |    NO    | `{id, name}`                   |
| art           | string                           |   YES    |    NO    |                                |
| has_art       | boolean                          |    NO    |    NO    |                                |
| flag          | boolean                          |    NO    |    NO    |                                |
| rating        | integer                          |   YES    |    NO    |                                |
| averagerating | number                           |   YES    |    NO    |                                |
| mbid          | string                           |   YES    |    NO    |                                |
| mbid_group    | string                           |   YES    |    NO    |                                |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/genre_albums.json)

### genre_artists

This returns the artists associated with the genre in question as defined by the UID

| Input    | Type    | Description                                                                   | Optional |
|----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter' | string  | UID of genre, returns artist JSON                                             |       NO |
| 'offset' | integer | Return results starting from this index position                              |      YES |
| 'limit'  | integer | Maximum number of results to return                                           |      YES |
| 'cond'   | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|          |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'   | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|          |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `artist` list.

| Field       | Type                                 | Nullable | Optional | Notes                              |
|-------------|--------------------------------------|:--------:|:--------:|------------------------------------|
| total_count | integer                              |    NO    |    NO    |                                    |
| md5         | string                               |    NO    |    NO    |                                    |
| artist      | array&lt;[ArtistObject](#artist)&gt; |    NO    |    NO    | see [ArtistObject](#artist) fields |

Each `artist` entry ([ArtistObject](#artist)):

| Field         | Type                               | Nullable | Optional | Notes                            |
|---------------|------------------------------------|:--------:|:--------:|----------------------------------|
| id            | string                             |    NO    |    NO    |                                  |
| name          | string                             |   YES    |    NO    |                                  |
| prefix        | string                             |   YES    |    NO    |                                  |
| basename      | string                             |   YES    |    NO    |                                  |
| albums        | array&lt;[AlbumObject](#album)&gt; |    NO    |    NO    | see [AlbumObject](#album) fields |
| albumcount    | integer                            |    NO    |    NO    |                                  |
| songs         | array&lt;[SongObject](#song)&gt;   |    NO    |    NO    | see [SongObject](#song) fields   |
| songcount     | integer                            |    NO    |    NO    |                                  |
| genre         | array&lt;object&gt;                |    NO    |    NO    | `{id, name}`                     |
| art           | string                             |   YES    |    NO    |                                  |
| has_art       | boolean                            |    NO    |    NO    |                                  |
| flag          | boolean                            |    NO    |    NO    |                                  |
| rating        | integer                            |   YES    |    NO    |                                  |
| averagerating | number                             |   YES    |    NO    |                                  |
| mbid          | string                             |   YES    |    NO    |                                  |
| summary       | string                             |   YES    |    NO    |                                  |
| time          | integer                            |    NO    |    NO    |                                  |
| yearformed    | integer                            |    NO    |    NO    |                                  |
| placeformed   | string                             |   YES    |    NO    |                                  |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/genre_artists.json)

### genre_songs

returns the songs for this genre

| Input    | Type    | Description                                                                   | Optional |
|----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter' | string  | UID of genre, returns song JSON                                               |       NO |
| 'offset' | integer | Return results starting from this index position                              |      YES |
| 'limit'  | integer | Maximum number of results to return                                           |      YES |
| 'cond'   | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|          |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'   | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|          |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `song` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| song        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |

Each `song` entry ([SongObject](#song)):

| Field                 | Type                         | Nullable | Optional | Notes                          |
|-----------------------|------------------------------|:--------:|:--------:|--------------------------------|
| id                    | string                       |    NO    |    NO    |                                |
| title                 | string                       |   YES    |    NO    |                                |
| name                  | string                       |   YES    |    NO    |                                |
| artist                | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| artists               | array&lt;object&gt;          |    NO    |    NO    | `{id, name, prefix, basename}` |
| album                 | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| albumartist           | object                       |    NO    |   YES    | `{id, name, prefix, basename}` |
| disk                  | integer                      |    NO    |    NO    |                                |
| disksubtitle          | string                       |   YES    |    NO    |                                |
| track                 | integer                      |    NO    |    NO    |                                |
| filename              | string                       |   YES    |    NO    |                                |
| genre                 | array&lt;object&gt;          |    NO    |    NO    | `{id, name}`                   |
| playlisttrack         | integer                      |    NO    |    NO    |                                |
| time                  | integer                      |    NO    |    NO    |                                |
| year                  | integer                      |    NO    |    NO    |                                |
| format                | string                       |   YES    |    NO    |                                |
| stream_format         | string                       |   YES    |    NO    |                                |
| bitrate               | integer                      |   YES    |    NO    |                                |
| stream_bitrate        | integer                      |   YES    |    NO    |                                |
| rate                  | integer                      |    NO    |    NO    |                                |
| mode                  | string                       |   YES    |    NO    |                                |
| mime                  | string                       |   YES    |    NO    |                                |
| stream_mime           | string                       |   YES    |    NO    |                                |
| url                   | string                       |    NO    |    NO    |                                |
| size                  | integer                      |    NO    |    NO    |                                |
| mbid                  | string                       |   YES    |    NO    |                                |
| art                   | string                       |   YES    |    NO    |                                |
| has_art               | boolean                      |    NO    |    NO    |                                |
| flag                  | boolean                      |    NO    |    NO    |                                |
| rating                | integer                      |   YES    |    NO    |                                |
| averagerating         | number                       |   YES    |    NO    |                                |
| playcount             | integer                      |    NO    |    NO    |                                |
| catalog               | string                       |    NO    |    NO    |                                |
| composer              | string                       |   YES    |    NO    |                                |
| channels              | integer                      |   YES    |    NO    |                                |
| comment               | string                       |   YES    |    NO    |                                |
| license               | string                       |   YES    |    NO    |                                |
| publisher             | string                       |   YES    |    NO    |                                |
| language              | string                       |   YES    |    NO    |                                |
| lyrics                | string                       |   YES    |    NO    |                                |
| replaygain_album_gain | number                       |   YES    |    NO    |                                |
| replaygain_album_peak | number                       |   YES    |    NO    |                                |
| replaygain_track_gain | number                       |   YES    |    NO    |                                |
| replaygain_track_peak | number                       |   YES    |    NO    |                                |
| r128_album_gain       | number                       |   YES    |    NO    |                                |
| r128_track_gain       | number                       |   YES    |    NO    |                                |
| metadata              | object&lt;string, string&gt; |    NO    |   YES    |                                |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/genre_songs.json)

### get_bookmark

Get the bookmark from it's object_id and object_type.
By default; get only the most recent bookmark. Use `all` to retrieve all media bookmarks for the object in a bookmark array.

| Input     | Type    | Description                                        | Optional |
|-----------|---------|----------------------------------------------------|---------:|
| 'filter'  | string  | $object_id to find                                 |       NO |
| 'type'    | string  | `song`, `video`, `podcast_episode` (object_type)   |       NO |
| 'include' | integer | 0,1, if true include the object in the bookmark    |      YES |
| 'all'     | integer | 0,1, if true include every bookmark for the object |      YES |

* DEFAULT return object (all=False)

```JSON
"id": "",
"owner": "",
"object_type": "",
"object_id": "",
"position": 0,
"client": "client",
"creation_date": 0,
"update_date": 0
```

* return array (all=True)

```JSON
"bookmark": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/get_bookmark.json)
[Example (with include)](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/get_bookmark%20\(with%20include\).json)

### get_external_metadata

Return External plugin metadata searching by object id and type

| Input    | Type   | Description                                      | Optional |
|----------|--------|--------------------------------------------------|---------:|
| 'filter' | string | $object_id to find                               |       NO |
| 'type'   | string | `song`, `album`, `artist`, `label` (object_type) |       NO |

* return object|array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns the plugin payloads when at least one metadata plugin answered, and the empty list envelope for the requested type when none did.

**`ExternalMetadataObject`**

`plugin` is keyed by metadata-retriever plugin name; each value is that plugin's payload.

`plugin` is keyed by metadata-retriever plugin name; each value is that plugin's payload.

| Field       | Type         | Nullable | Optional | Notes                   |
|-------------|--------------|:--------:|:--------:|-------------------------|
| object_id   | string       |    NO    |    NO    |                         |
| object_type | string       |    NO    |    NO    |                         |
| plugin      | `_PluginMap` |    NO    |    NO    | see `_PluginMap` fields |

**`EmptyListResponse`**

The standard empty envelope, with an empty list keyed by the requested type.

The standard empty envelope, with an empty list keyed by the requested type.

| Field       | Type    | Nullable | Optional | Notes |
|-------------|---------|:--------:|:--------:|-------|
| total_count | integer |    NO    |    NO    |       |
| md5         | string  |    NO    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/get_external_metadata.json)

### get_indexes

This takes a collection of inputs and returns ID + name for the object type

**NOTE** This method was **removed** in **API8** (Use list OR index)

| Input         | Type       | Description                                                                                        | Optional |
|---------------|------------|----------------------------------------------------------------------------------------------------|---------:|
| 'type'        | string     | `song`, `album`, `artist`, `album_artist`, `song_artist`, `playlist`, `podcast`                    |       NO |
|               |            | `podcast_episode`, `live_stream`, `catalog`                                                        |          |
| 'filter'      | string     | Value is Alpha Match for returned results, may be more than one letter/number                      |      YES |
| 'hide_search' | integer    | `0`, `1` (if true do not include searches/smartlists in the result)                                |      YES |
| 'add'         | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'add' date newer than the specified date    |      YES |
| 'update'      | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'update' time newer than the specified date |      YES |
| 'include'     | boolean    | `0`, `1` (include songs in a playlist or episodes in a podcast)                                    |      YES |
| 'offset'      | integer    | Return results starting from this index position                                                   |      YES |
| 'limit'       | integer    | Maximum number of results to return                                                                |      YES |
| 'cond'        | string     | Apply additional filters to the browse using `;` separated comma string pairs                      |      YES |
|               |            | (e.g. 'filter1,value1;filter2,value2')                                                             |          |
| 'sort'        | string     | Sort name or comma-separated key pair. (e.g. 'name,order')                                         |      YES |
|               |            | Default order 'ASC' (e.g. 'name,ASC' == 'name')                                                    |          |

* return array

```JSON
"song": []|"album": []|"artist": []|"playlist": []|"podcast": []

```

* throws object

```JSON
"error": ""
```

SONG [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/get_indexes%20\(song\).json)

ARTIST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/get_indexes%20\(artist\).json)

ALBUM [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/get_indexes%20\(album\).json)

PLAYLIST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/get_indexes%20\(playlist\).json)

### get_lyrics

Return Database lyrics or search with plugins by Song id

| Input     | Type   | Description                                           | Optional |
|-----------|--------|-------------------------------------------------------|---------:|
| 'filter'  | string | $song_id to find                                      |       NO |
| 'plugins' | string | `0`, `1`, if false disable plugin lookup (default: 1) |       NO |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
`plugin` is keyed by lyric source (`database` plus any lyric-retriever plugin that answered). When nothing answered it is serialised as an empty array, not an empty object.

| Field       | Type         | Nullable | Optional | Notes                   |
|-------------|--------------|:--------:|:--------:|-------------------------|
| object_id   | string       |    NO    |    NO    |                         |
| object_type | string       |    NO    |    NO    |                         |
| plugin      | `_PluginMap` |    NO    |    NO    | see `_PluginMap` fields |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/get_lyrics.json)

### get_similar

Return similar artist id's or similar song ids compared to the input filter

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'type'   | string  | `song`, `artist`                                 |       NO |
| 'filter' | string  | artist id or song id                             |       NO |
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `song` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| song        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |

Each `song` entry ([SongObject](#song)):

| Field                 | Type                         | Nullable | Optional | Notes                          |
|-----------------------|------------------------------|:--------:|:--------:|--------------------------------|
| id                    | string                       |    NO    |    NO    |                                |
| title                 | string                       |   YES    |    NO    |                                |
| name                  | string                       |   YES    |    NO    |                                |
| artist                | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| artists               | array&lt;object&gt;          |    NO    |    NO    | `{id, name, prefix, basename}` |
| album                 | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| albumartist           | object                       |    NO    |   YES    | `{id, name, prefix, basename}` |
| disk                  | integer                      |    NO    |    NO    |                                |
| disksubtitle          | string                       |   YES    |    NO    |                                |
| track                 | integer                      |    NO    |    NO    |                                |
| filename              | string                       |   YES    |    NO    |                                |
| genre                 | array&lt;object&gt;          |    NO    |    NO    | `{id, name}`                   |
| playlisttrack         | integer                      |    NO    |    NO    |                                |
| time                  | integer                      |    NO    |    NO    |                                |
| year                  | integer                      |    NO    |    NO    |                                |
| format                | string                       |   YES    |    NO    |                                |
| stream_format         | string                       |   YES    |    NO    |                                |
| bitrate               | integer                      |   YES    |    NO    |                                |
| stream_bitrate        | integer                      |   YES    |    NO    |                                |
| rate                  | integer                      |    NO    |    NO    |                                |
| mode                  | string                       |   YES    |    NO    |                                |
| mime                  | string                       |   YES    |    NO    |                                |
| stream_mime           | string                       |   YES    |    NO    |                                |
| url                   | string                       |    NO    |    NO    |                                |
| size                  | integer                      |    NO    |    NO    |                                |
| mbid                  | string                       |   YES    |    NO    |                                |
| art                   | string                       |   YES    |    NO    |                                |
| has_art               | boolean                      |    NO    |    NO    |                                |
| flag                  | boolean                      |    NO    |    NO    |                                |
| rating                | integer                      |   YES    |    NO    |                                |
| averagerating         | number                       |   YES    |    NO    |                                |
| playcount             | integer                      |    NO    |    NO    |                                |
| catalog               | string                       |    NO    |    NO    |                                |
| composer              | string                       |   YES    |    NO    |                                |
| channels              | integer                      |   YES    |    NO    |                                |
| comment               | string                       |   YES    |    NO    |                                |
| license               | string                       |   YES    |    NO    |                                |
| publisher             | string                       |   YES    |    NO    |                                |
| language              | string                       |   YES    |    NO    |                                |
| lyrics                | string                       |   YES    |    NO    |                                |
| replaygain_album_gain | number                       |   YES    |    NO    |                                |
| replaygain_album_peak | number                       |   YES    |    NO    |                                |
| replaygain_track_gain | number                       |   YES    |    NO    |                                |
| replaygain_track_peak | number                       |   YES    |    NO    |                                |
| r128_album_gain       | number                       |   YES    |    NO    |                                |
| r128_track_gain       | number                       |   YES    |    NO    |                                |
| metadata              | object&lt;string, string&gt; |    NO    |   YES    |                                |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/get_similar.json)

### index

This takes a collection of inputs and return ID's for the object type. Add 'include' to include child objects

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

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Keyed by the requested `type` (e.g. `album`, `artist`, `song`). Without `include` the value is an array of object ids; with `include` it is an array of `{id, type}` references, or a map of parent id -> reference array for parent types such as playlists.

Open map — each value is: array&lt;string&gt; \| array&lt;`IndexReferenceObject`&gt; \| object&lt;string, array&lt;`IndexReferenceObject`&gt;&gt;.
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

SONG [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/index%20\(song\).json)

ARTIST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/index%20\(artist\).json)

ALBUM [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/index%20\(album\).json)

PLAYLIST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/index%20\(playlist\).json)

SONG [Example (with include)](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/index%20\(song%20with%20include\).json)

ARTIST [Example (with include)](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/index%20\(artist%20with%20include\).json)

ALBUM [Example (with include)](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/index%20\(album%20with%20include\).json)

PLAYLIST [Example (with include)](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/index%20\(playlist%20with%20include\).json)

### labels

This returns labels based on the specified filter

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

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `label` list.

| Field       | Type                               | Nullable | Optional | Notes                            |
|-------------|------------------------------------|:--------:|:--------:|----------------------------------|
| total_count | integer                            |    NO    |    NO    |                                  |
| md5         | string                             |    NO    |    NO    |                                  |
| label       | array&lt;[LabelObject](#label)&gt; |    NO    |    NO    | see [LabelObject](#label) fields |

Each `label` entry ([LabelObject](#label)):

| Field         | Type    | Nullable | Optional | Notes |
|---------------|---------|:--------:|:--------:|-------|
| id            | string  |    NO    |    NO    |       |
| name          | string  |   YES    |    NO    |       |
| artists       | integer |    NO    |    NO    |       |
| summary       | string  |   YES    |    NO    |       |
| external_link | string  |    NO    |    NO    |       |
| address       | string  |   YES    |    NO    |       |
| category      | string  |   YES    |    NO    |       |
| email         | string  |   YES    |    NO    |       |
| website       | string  |   YES    |    NO    |       |
| user          | string  |    NO    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/labels.json)

### label

This returns a single label

| Input    | Type   | Description                      | Optional |
|----------|--------|----------------------------------|---------:|
| 'filter' | string | UID of label, returns label JSON |       NO |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field         | Type    | Nullable | Optional | Notes |
|---------------|---------|:--------:|:--------:|-------|
| id            | string  |    NO    |    NO    |       |
| name          | string  |   YES    |    NO    |       |
| artists       | integer |    NO    |    NO    |       |
| summary       | string  |   YES    |    NO    |       |
| external_link | string  |    NO    |    NO    |       |
| address       | string  |   YES    |    NO    |       |
| category      | string  |   YES    |    NO    |       |
| email         | string  |   YES    |    NO    |       |
| website       | string  |   YES    |    NO    |       |
| user          | string  |    NO    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/label.json)

### label_artists

This returns the artists for a label

| Input    | Type    | Description                                                                   | Optional |
|----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter' | string  | UID of label, returns artist JSON                                             |       NO |
| 'offset' | integer | Return results starting from this index position                              |      YES |
| 'limit'  | integer | Maximum number of results to return                                           |      YES |
| 'cond'   | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|          |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'   | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|          |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `artist` list.

| Field       | Type                                 | Nullable | Optional | Notes                              |
|-------------|--------------------------------------|:--------:|:--------:|------------------------------------|
| total_count | integer                              |    NO    |    NO    |                                    |
| md5         | string                               |    NO    |    NO    |                                    |
| artist      | array&lt;[ArtistObject](#artist)&gt; |    NO    |    NO    | see [ArtistObject](#artist) fields |

Each `artist` entry ([ArtistObject](#artist)):

| Field         | Type                               | Nullable | Optional | Notes                            |
|---------------|------------------------------------|:--------:|:--------:|----------------------------------|
| id            | string                             |    NO    |    NO    |                                  |
| name          | string                             |   YES    |    NO    |                                  |
| prefix        | string                             |   YES    |    NO    |                                  |
| basename      | string                             |   YES    |    NO    |                                  |
| albums        | array&lt;[AlbumObject](#album)&gt; |    NO    |    NO    | see [AlbumObject](#album) fields |
| albumcount    | integer                            |    NO    |    NO    |                                  |
| songs         | array&lt;[SongObject](#song)&gt;   |    NO    |    NO    | see [SongObject](#song) fields   |
| songcount     | integer                            |    NO    |    NO    |                                  |
| genre         | array&lt;object&gt;                |    NO    |    NO    | `{id, name}`                     |
| art           | string                             |   YES    |    NO    |                                  |
| has_art       | boolean                            |    NO    |    NO    |                                  |
| flag          | boolean                            |    NO    |    NO    |                                  |
| rating        | integer                            |   YES    |    NO    |                                  |
| averagerating | number                             |   YES    |    NO    |                                  |
| mbid          | string                             |   YES    |    NO    |                                  |
| summary       | string                             |   YES    |    NO    |                                  |
| time          | integer                            |    NO    |    NO    |                                  |
| yearformed    | integer                            |    NO    |    NO    |                                  |
| placeformed   | string                             |   YES    |    NO    |                                  |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/label_artists.json)

### last_shouts

This gets the latest posted shouts

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `catalog` is deprecated and will be removed in **API9**

| Input    | Type    | Description                                                                  | Optional |
|----------|---------|------------------------------------------------------------------------------|---------:|
| 'filter' | string  | Get latest shouts for this username                                          |      YES |
| 'limit'  | integer | Maximum number of results (Use `popular_threshold` when missing; default 10) |      YES |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `shout` list.

| Field | Type                       | Nullable | Optional | Notes                    |
|-------|----------------------------|:--------:|:--------:|--------------------------|
| shout | array&lt;`ShoutObject`&gt; |    NO    |    NO    | see `ShoutObject` fields |

Each `shout` entry (`ShoutObject`):

| Field       | Type    | Nullable | Optional | Notes            |
|-------------|---------|:--------:|:--------:|------------------|
| id          | string  |    NO    |    NO    |                  |
| date        | integer |    NO    |    NO    |                  |
| text        | string  |    NO    |    NO    |                  |
| object_type | string  |    NO    |    NO    |                  |
| object_id   | string  |    NO    |    NO    |                  |
| user        | object  |    NO    |    NO    | `{id, username}` |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/last_shouts.json)

### licenses

This returns licenses based on the specified filter

| Input    | Type       | Description                                                                                        | Optional |
|----------|------------|----------------------------------------------------------------------------------------------------|---------:|
| 'filter' | string     | Filter results to match this string                                                                |      YES |
| 'exact'  | boolean    | `0`, `1` (if true filter is exact `=` rather than fuzzy `LIKE`)                                    |      YES |
| 'add'    | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'add' date newer than the specified date    |      YES |
| 'update' | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'update' time newer than the specified date |      YES |
| 'offset' | integer    |                                                                                                    |      YES |
| 'limit'  | integer    |                                                                                                    |      YES |
| 'cond'   | string     | Apply additional filters to the browse using `;` separated comma string pairs                      |      YES |
|          |            | (e.g. 'filter1,value1;filter2,value2')                                                             |          |
| 'sort'   | string     | Sort name or comma-separated key pair. (e.g. 'name,order')                                         |      YES |
|          |            | Default order 'ASC' (e.g. 'name,ASC' == 'name')                                                    |          |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `license` list.

| Field       | Type                                   | Nullable | Optional | Notes                                |
|-------------|----------------------------------------|:--------:|:--------:|--------------------------------------|
| total_count | integer                                |    NO    |    NO    |                                      |
| md5         | string                                 |    NO    |    NO    |                                      |
| license     | array&lt;[LicenseObject](#license)&gt; |    NO    |    NO    | see [LicenseObject](#license) fields |

Each `license` entry ([LicenseObject](#license)):

| Field         | Type   | Nullable | Optional | Notes |
|---------------|--------|:--------:|:--------:|-------|
| id            | string |    NO    |    NO    |       |
| name          | string |    NO    |    NO    |       |
| description   | string |    NO    |    NO    |       |
| external_link | string |    NO    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/licenses.json)

### license

This returns a single license

| Input    | Type   | Description                          | Optional |
|----------|--------|--------------------------------------|---------:|
| 'filter' | string | UID of license, returns license JSON |       NO |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field         | Type   | Nullable | Optional | Notes |
|---------------|--------|:--------:|:--------:|-------|
| id            | string |    NO    |    NO    |       |
| name          | string |    NO    |    NO    |       |
| description   | string |    NO    |    NO    |       |
| external_link | string |    NO    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/license.json)

### license_songs

This returns the songs for a license

| Input    | Type    | Description                                                                   | Optional |
|----------|---------|-------------------------------------------------------------------------------|---------:|
| 'filter' | string  | UID of license, returns song JSON                                             |       NO |
| 'offset' | integer | Return results starting from this index position                              |      YES |
| 'limit'  | integer | Maximum number of results to return                                           |      YES |
| 'cond'   | string  | Apply additional filters to the browse using `;` separated comma string pairs |      YES |
|          |         | (e.g. 'filter1,value1;filter2,value2')                                        |          |
| 'sort'   | string  | Sort name or comma-separated key pair. (e.g. 'name,order')                    |      YES |
|          |         | Default order 'ASC' (e.g. 'name,ASC' == 'name')                               |          |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `song` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| song        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |

Each `song` entry ([SongObject](#song)):

| Field                 | Type                         | Nullable | Optional | Notes                          |
|-----------------------|------------------------------|:--------:|:--------:|--------------------------------|
| id                    | string                       |    NO    |    NO    |                                |
| title                 | string                       |   YES    |    NO    |                                |
| name                  | string                       |   YES    |    NO    |                                |
| artist                | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| artists               | array&lt;object&gt;          |    NO    |    NO    | `{id, name, prefix, basename}` |
| album                 | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| albumartist           | object                       |    NO    |   YES    | `{id, name, prefix, basename}` |
| disk                  | integer                      |    NO    |    NO    |                                |
| disksubtitle          | string                       |   YES    |    NO    |                                |
| track                 | integer                      |    NO    |    NO    |                                |
| filename              | string                       |   YES    |    NO    |                                |
| genre                 | array&lt;object&gt;          |    NO    |    NO    | `{id, name}`                   |
| playlisttrack         | integer                      |    NO    |    NO    |                                |
| time                  | integer                      |    NO    |    NO    |                                |
| year                  | integer                      |    NO    |    NO    |                                |
| format                | string                       |   YES    |    NO    |                                |
| stream_format         | string                       |   YES    |    NO    |                                |
| bitrate               | integer                      |   YES    |    NO    |                                |
| stream_bitrate        | integer                      |   YES    |    NO    |                                |
| rate                  | integer                      |    NO    |    NO    |                                |
| mode                  | string                       |   YES    |    NO    |                                |
| mime                  | string                       |   YES    |    NO    |                                |
| stream_mime           | string                       |   YES    |    NO    |                                |
| url                   | string                       |    NO    |    NO    |                                |
| size                  | integer                      |    NO    |    NO    |                                |
| mbid                  | string                       |   YES    |    NO    |                                |
| art                   | string                       |   YES    |    NO    |                                |
| has_art               | boolean                      |    NO    |    NO    |                                |
| flag                  | boolean                      |    NO    |    NO    |                                |
| rating                | integer                      |   YES    |    NO    |                                |
| averagerating         | number                       |   YES    |    NO    |                                |
| playcount             | integer                      |    NO    |    NO    |                                |
| catalog               | string                       |    NO    |    NO    |                                |
| composer              | string                       |   YES    |    NO    |                                |
| channels              | integer                      |   YES    |    NO    |                                |
| comment               | string                       |   YES    |    NO    |                                |
| license               | string                       |   YES    |    NO    |                                |
| publisher             | string                       |   YES    |    NO    |                                |
| language              | string                       |   YES    |    NO    |                                |
| lyrics                | string                       |   YES    |    NO    |                                |
| replaygain_album_gain | number                       |   YES    |    NO    |                                |
| replaygain_album_peak | number                       |   YES    |    NO    |                                |
| replaygain_track_gain | number                       |   YES    |    NO    |                                |
| replaygain_track_peak | number                       |   YES    |    NO    |                                |
| r128_album_gain       | number                       |   YES    |    NO    |                                |
| r128_track_gain       | number                       |   YES    |    NO    |                                |
| metadata              | object&lt;string, string&gt; |    NO    |   YES    |                                |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/license_songs.json)

### list

This takes a named array of objects and returning `id`, `name`, `prefix` and `basename`

**NOTE** This method replaces get_indexes and does not have the `include` parameter and does not include children in the response.

| Input         | Type       | Description                                                                                        | Optional |
|---------------|------------|----------------------------------------------------------------------------------------------------|---------:|
| 'type'        | string     | `song`, `album`, `artist`, `album_artist`, `song_artist`, `playlist`, `podcast`                    |       NO |
|               |            | `podcast_episode`, `live_stream`, `catalog`                                                        |          |
| 'filter'      | string     | Value is Alpha Match for returned results, may be more than one letter/number                      |      YES |
| 'hide_search' | integer    | `0`, `1` (if true do not include searches/smartlists in the result)                                |      YES |
| 'add'         | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'add' date newer than the specified date    |      YES |
| 'update'      | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'update' time newer than the specified date |      YES |
| 'offset'      | integer    | Return results starting from this index position                                                   |      YES |
| 'limit'       | integer    | Maximum number of results to return                                                                |      YES |
| 'cond'        | string     | Apply additional filters to the browse using `;` separated comma string pairs                      |      YES |
|               |            | (e.g. 'filter1,value1;filter2,value2')                                                             |          |
| 'sort'        | string     | Sort name or comma-separated key pair. (e.g. 'name,order')                                         |      YES |
|               |            | Default order 'ASC' (e.g. 'name,ASC' == 'name')                                                    |          |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `list` list.

| Field       | Type                      | Nullable | Optional | Notes                   |
|-------------|---------------------------|:--------:|:--------:|-------------------------|
| total_count | integer                   |    NO    |    NO    |                         |
| md5         | string                    |    NO    |    NO    |                         |
| list        | array&lt;`ListObject`&gt; |    NO    |    NO    | see `ListObject` fields |

Each `list` entry (`ListObject`):

| Field    | Type   | Nullable | Optional | Notes |
|----------|--------|:--------:|:--------:|-------|
| id       | string |    NO    |    NO    |       |
| name     | string |    NO    |    NO    |       |
| prefix   | string |   YES    |    NO    |       |
| basename | string |    NO    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/list.json)

### live_streams

This returns live_streams based on the specified filter

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

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `live_stream` list.

| Field       | Type                                          | Nullable | Optional | Notes                                       |
|-------------|-----------------------------------------------|:--------:|:--------:|---------------------------------------------|
| total_count | integer                                       |    NO    |    NO    |                                             |
| md5         | string                                        |    NO    |    NO    |                                             |
| live_stream | array&lt;[LiveStreamObject](#live_stream)&gt; |    NO    |    NO    | see [LiveStreamObject](#live_stream) fields |

Each `live_stream` entry ([LiveStreamObject](#live_stream)):

| Field    | Type   | Nullable | Optional | Notes |
|----------|--------|:--------:|:--------:|-------|
| id       | string |    NO    |    NO    |       |
| name     | string |   YES    |    NO    |       |
| url      | string |   YES    |    NO    |       |
| codec    | string |   YES    |    NO    |       |
| catalog  | string |    NO    |    NO    |       |
| site_url | string |   YES    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/live_streams.json)

### live_stream

This returns a single live_stream

| Input    | Type   | Description                                  | Optional |
|----------|--------|----------------------------------------------|---------:|
| 'filter' | string | UID of live_stream, returns live_stream JSON |       NO |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field    | Type   | Nullable | Optional | Notes |
|----------|--------|:--------:|:--------:|-------|
| id       | string |    NO    |    NO    |       |
| name     | string |   YES    |    NO    |       |
| url      | string |   YES    |    NO    |       |
| codec    | string |   YES    |    NO    |       |
| catalog  | string |    NO    |    NO    |       |
| site_url | string |   YES    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/live_stream.json)

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

* return array

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/live_stream_create.json)

### live_stream_delete

Delete an existing live_stream (radio station). (if it exists)

**ACCESS REQUIRED:** 50 (Content Manager) permission to create and edit live_streams

| Input    | Type   | Description                                      | Optional |
|----------|--------|--------------------------------------------------|---------:|
| 'filter' | string | $object_id to delete                             |       NO |
| 'type'   | string | `song`, `video`, `podcast_episode` (object_type) |       NO |
| 'client' | string | Agent string. (Default: 'AmpacheAPI')            |      YES |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/live_stream_delete.json)

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

* return array

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/live_stream_edit.json)

### now_playing

Get what is currently being played by all users.

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `now_playing` list.

| Field       | Type                            | Nullable | Optional | Notes                         |
|-------------|---------------------------------|:--------:|:--------:|-------------------------------|
| now_playing | array&lt;`NowPlayingObject`&gt; |    NO    |    NO    | see `NowPlayingObject` fields |

Each `now_playing` entry (`NowPlayingObject`):

| Field  | Type    | Nullable | Optional | Notes            |
|--------|---------|:--------:|:--------:|------------------|
| id     | string  |    NO    |    NO    |                  |
| type   | string  |    NO    |    NO    |                  |
| client | string  |    NO    |    NO    |                  |
| expire | integer |    NO    |    NO    |                  |
| user   | object  |    NO    |    NO    | `{id, username}` |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/now_playing.json)

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

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `now_playing` list.

| Field       | Type                            | Nullable | Optional | Notes                         |
|-------------|---------------------------------|:--------:|:--------:|-------------------------------|
| now_playing | array&lt;`NowPlayingObject`&gt; |    NO    |    NO    | see `NowPlayingObject` fields |

Each `now_playing` entry (`NowPlayingObject`):

| Field  | Type    | Nullable | Optional | Notes            |
|--------|---------|:--------:|:--------:|------------------|
| id     | string  |    NO    |    NO    |                  |
| type   | string  |    NO    |    NO    |                  |
| client | string  |    NO    |    NO    |                  |
| expire | integer |    NO    |    NO    |                  |
| user   | object  |    NO    |    NO    | `{id, username}` |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/player.json)

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

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `playlist` list.

| Field       | Type                                     | Nullable | Optional | Notes                                  |
|-------------|------------------------------------------|:--------:|:--------:|----------------------------------------|
| total_count | integer                                  |    NO    |    NO    |                                        |
| md5         | string                                   |    NO    |    NO    |                                        |
| playlist    | array&lt;[PlaylistObject](#playlist)&gt; |    NO    |    NO    | see [PlaylistObject](#playlist) fields |

Each `playlist` entry ([PlaylistObject](#playlist)):

| Field           | Type                           | Nullable | Optional | Notes            |
|-----------------|--------------------------------|:--------:|:--------:|------------------|
| id              | string                         |    NO    |    NO    |                  |
| name            | string                         |   YES    |    NO    |                  |
| owner           | string                         |   YES    |    NO    |                  |
| user            | object                         |    NO    |    NO    | `{id, username}` |
| items           | array&lt;object&gt; \| integer |    NO    |    NO    |                  |
| type            | string                         |   YES    |    NO    |                  |
| art             | string                         |   YES    |    NO    |                  |
| has_access      | boolean                        |    NO    |    NO    |                  |
| has_collaborate | boolean                        |    NO    |    NO    |                  |
| has_art         | boolean                        |    NO    |    NO    |                  |
| flag            | boolean                        |    NO    |    NO    |                  |
| rating          | integer                        |   YES    |    NO    |                  |
| averagerating   | number                         |   YES    |    NO    |                  |
| md5             | string                         |   YES    |    NO    |                  |
| last_update     | integer                        |   YES    |    NO    |                  |
| time            | integer                        |    NO    |    NO    |                  |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/playlists.json)

### playlist

This returns a single playlist

| Input    | Type   | Description                            | Optional |
|----------|--------|----------------------------------------|---------:|
| 'filter' | string | UID of playlist, returns playlist JSON |       NO |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field           | Type                           | Nullable | Optional | Notes            |
|-----------------|--------------------------------|:--------:|:--------:|------------------|
| id              | string                         |    NO    |    NO    |                  |
| name            | string                         |   YES    |    NO    |                  |
| owner           | string                         |   YES    |    NO    |                  |
| user            | object                         |    NO    |    NO    | `{id, username}` |
| items           | array&lt;object&gt; \| integer |    NO    |    NO    |                  |
| type            | string                         |   YES    |    NO    |                  |
| art             | string                         |   YES    |    NO    |                  |
| has_access      | boolean                        |    NO    |    NO    |                  |
| has_collaborate | boolean                        |    NO    |    NO    |                  |
| has_art         | boolean                        |    NO    |    NO    |                  |
| flag            | boolean                        |    NO    |    NO    |                  |
| rating          | integer                        |   YES    |    NO    |                  |
| averagerating   | number                         |   YES    |    NO    |                  |
| md5             | string                         |   YES    |    NO    |                  |
| last_update     | integer                        |   YES    |    NO    |                  |
| time            | integer                        |    NO    |    NO    |                  |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/playlist.json)

### playlist_add

This adds a song to a playlist. setting check=1 will not add duplicates to the playlist

**NOTE** `type` is optional from Ampache8+

| Input    | Type   | Description                                           | Optional |
|----------|--------|-------------------------------------------------------|---------:|
| 'filter' | string | UID of Playlist                                       |       NO |
| 'id'     | string | UID of the object to add to playlist                  |       NO |
| 'type'   | string | 'song', 'album', 'artist', 'playlist' (Default: song) |      YES |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/playlist_add.json)

### playlist_add_song

This adds a song to a playlist. setting check=1 will not add duplicates to the playlist

**NOTE** This method was **removed** in **API8** (Use playlist_add)

| Input    | Type    | Description                                                   | Optional |
|----------|---------|---------------------------------------------------------------|---------:|
| 'filter' | string  | UID of Playlist                                               |       NO |
| 'song'   | string  | UID of song to add to playlist                                |       NO |
| 'check'  | boolean | `0`, `1` Whether to check and ignore duplicates (default = 0) |      YES |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/playlist_add_song.json)

### playlist_create

This create a new playlist and return it

| Input  | Type   | Description                         | Optional |
|--------|--------|-------------------------------------|---------:|
| 'name' | string | Playlist name                       |       NO |
| 'type' | string | `public`, `private` (Playlist type) |      YES |

* return array

```JSON
"playlist": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/playlist_create.json)

### playlist_delete

This deletes a playlist

| Input    | Type   | Description     | Optional |
|----------|--------|-----------------|---------:|
| 'filter' | string | UID of Playlist |       NO |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/playlist_delete.json)

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

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/playlist_edit.json)

### playlist_generate

Get a list of song JSON, indexes or id's based on some simple search criteria
'recent' will search for tracks played after 'Popular Threshold' days
'forgotten' will search for tracks played before 'Popular Threshold' days
'unplayed' added in 400002 for searching unplayed tracks

| Input    | Type    | Description                                                      | Optional |
|----------|---------|------------------------------------------------------------------|---------:|
| 'mode'   | string  | `recent`, `forgotten`, `unplayed`, `random` (default = 'random') |      YES |
| 'filter' | string  | string LIKE matched to song title                                |      YES |
| 'album'  | string  | $album_id                                                        |      YES |
| 'artist' | string  | $artist_id                                                       |      YES |
| 'flag'   | boolean | `0`, `1` (get flagged songs only. default = 0)                   |      YES |
| 'format' | string  | `song`, `index`, `id` (default = 'song')                         |      YES |
| 'offset' | integer | Return results starting from this index position                 |      YES |
| 'limit'  | integer | Maximum number of results to return                              |      YES |

* return object|array

<!-- GENERATED:RESPONSE:BEGIN -->
Depends on the `format` parameter: `song` (default) and `index` return the song list envelope, `id` returns a bare array of song ids.

**[SongsResponse](#album_disk_songs)**

Returns a `song` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| song        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |

Each `song` entry ([SongObject](#song)):

| Field                 | Type                         | Nullable | Optional | Notes                          |
|-----------------------|------------------------------|:--------:|:--------:|--------------------------------|
| id                    | string                       |    NO    |    NO    |                                |
| title                 | string                       |   YES    |    NO    |                                |
| name                  | string                       |   YES    |    NO    |                                |
| artist                | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| artists               | array&lt;object&gt;          |    NO    |    NO    | `{id, name, prefix, basename}` |
| album                 | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| albumartist           | object                       |    NO    |   YES    | `{id, name, prefix, basename}` |
| disk                  | integer                      |    NO    |    NO    |                                |
| disksubtitle          | string                       |   YES    |    NO    |                                |
| track                 | integer                      |    NO    |    NO    |                                |
| filename              | string                       |   YES    |    NO    |                                |
| genre                 | array&lt;object&gt;          |    NO    |    NO    | `{id, name}`                   |
| playlisttrack         | integer                      |    NO    |    NO    |                                |
| time                  | integer                      |    NO    |    NO    |                                |
| year                  | integer                      |    NO    |    NO    |                                |
| format                | string                       |   YES    |    NO    |                                |
| stream_format         | string                       |   YES    |    NO    |                                |
| bitrate               | integer                      |   YES    |    NO    |                                |
| stream_bitrate        | integer                      |   YES    |    NO    |                                |
| rate                  | integer                      |    NO    |    NO    |                                |
| mode                  | string                       |   YES    |    NO    |                                |
| mime                  | string                       |   YES    |    NO    |                                |
| stream_mime           | string                       |   YES    |    NO    |                                |
| url                   | string                       |    NO    |    NO    |                                |
| size                  | integer                      |    NO    |    NO    |                                |
| mbid                  | string                       |   YES    |    NO    |                                |
| art                   | string                       |   YES    |    NO    |                                |
| has_art               | boolean                      |    NO    |    NO    |                                |
| flag                  | boolean                      |    NO    |    NO    |                                |
| rating                | integer                      |   YES    |    NO    |                                |
| averagerating         | number                       |   YES    |    NO    |                                |
| playcount             | integer                      |    NO    |    NO    |                                |
| catalog               | string                       |    NO    |    NO    |                                |
| composer              | string                       |   YES    |    NO    |                                |
| channels              | integer                      |   YES    |    NO    |                                |
| comment               | string                       |   YES    |    NO    |                                |
| license               | string                       |   YES    |    NO    |                                |
| publisher             | string                       |   YES    |    NO    |                                |
| language              | string                       |   YES    |    NO    |                                |
| lyrics                | string                       |   YES    |    NO    |                                |
| replaygain_album_gain | number                       |   YES    |    NO    |                                |
| replaygain_album_peak | number                       |   YES    |    NO    |                                |
| replaygain_track_gain | number                       |   YES    |    NO    |                                |
| replaygain_track_peak | number                       |   YES    |    NO    |                                |
| r128_album_gain       | number                       |   YES    |    NO    |                                |
| r128_track_gain       | number                       |   YES    |    NO    |                                |
| metadata              | object&lt;string, string&gt; |    NO    |   YES    |                                |

**array&lt;string&gt;**

Returned by `format=id`: song ids only, with no envelope.
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

SONG [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/playlist_generate%20\(song\).json)

INDEX [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/playlist_generate%20\(index\).json)

ID [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/playlist_generate%20\(id\).json)

### playlist_hash

This returns the md5 hash for the songs in a playlist

| Input    | Type   | Description     | Optional |
|----------|--------|-----------------|---------:|
| 'filter' | string | UID of Playlist |       NO |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field | Type   | Nullable | Optional | Notes |
|-------|--------|:--------:|:--------:|-------|
| md5   | string |   YES    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/playlist_hash.json)

### playlist_remove

Remove objects from a playlist using track number in the list or object id and type.
Using clear will empty the entire list.

**NOTE** this replaces `playlist_remove_song` in API8

| Input    | Type    | Description                                           | Optional |
|----------|---------|-------------------------------------------------------|---------:|
| 'filter' | string  | UID of Playlist                                       |       NO |
| 'id'     | string  | UID of object to remove from playlist                 |      YES |
| 'type'   | string  | 'song', 'album', 'artist', 'playlist', default = song |      YES |
| 'track'  | integer | Track number to remove from playlist                  |      YES |
| 'clear'  | integer | 0,1 Clear the whole playlist                          |      YES |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/playlist_remove.json)

### playlist_songs

This returns the songs for a playlist

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'filter' | string  | UID of Playlist, returns song JSON               |       NO |
| 'random' | integer | `0`, `1` (if true get random songs using limit)  |      YES |
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `song` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| song        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |

Each `song` entry ([SongObject](#song)):

| Field                 | Type                         | Nullable | Optional | Notes                          |
|-----------------------|------------------------------|:--------:|:--------:|--------------------------------|
| id                    | string                       |    NO    |    NO    |                                |
| title                 | string                       |   YES    |    NO    |                                |
| name                  | string                       |   YES    |    NO    |                                |
| artist                | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| artists               | array&lt;object&gt;          |    NO    |    NO    | `{id, name, prefix, basename}` |
| album                 | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| albumartist           | object                       |    NO    |   YES    | `{id, name, prefix, basename}` |
| disk                  | integer                      |    NO    |    NO    |                                |
| disksubtitle          | string                       |   YES    |    NO    |                                |
| track                 | integer                      |    NO    |    NO    |                                |
| filename              | string                       |   YES    |    NO    |                                |
| genre                 | array&lt;object&gt;          |    NO    |    NO    | `{id, name}`                   |
| playlisttrack         | integer                      |    NO    |    NO    |                                |
| time                  | integer                      |    NO    |    NO    |                                |
| year                  | integer                      |    NO    |    NO    |                                |
| format                | string                       |   YES    |    NO    |                                |
| stream_format         | string                       |   YES    |    NO    |                                |
| bitrate               | integer                      |   YES    |    NO    |                                |
| stream_bitrate        | integer                      |   YES    |    NO    |                                |
| rate                  | integer                      |    NO    |    NO    |                                |
| mode                  | string                       |   YES    |    NO    |                                |
| mime                  | string                       |   YES    |    NO    |                                |
| stream_mime           | string                       |   YES    |    NO    |                                |
| url                   | string                       |    NO    |    NO    |                                |
| size                  | integer                      |    NO    |    NO    |                                |
| mbid                  | string                       |   YES    |    NO    |                                |
| art                   | string                       |   YES    |    NO    |                                |
| has_art               | boolean                      |    NO    |    NO    |                                |
| flag                  | boolean                      |    NO    |    NO    |                                |
| rating                | integer                      |   YES    |    NO    |                                |
| averagerating         | number                       |   YES    |    NO    |                                |
| playcount             | integer                      |    NO    |    NO    |                                |
| catalog               | string                       |    NO    |    NO    |                                |
| composer              | string                       |   YES    |    NO    |                                |
| channels              | integer                      |   YES    |    NO    |                                |
| comment               | string                       |   YES    |    NO    |                                |
| license               | string                       |   YES    |    NO    |                                |
| publisher             | string                       |   YES    |    NO    |                                |
| language              | string                       |   YES    |    NO    |                                |
| lyrics                | string                       |   YES    |    NO    |                                |
| replaygain_album_gain | number                       |   YES    |    NO    |                                |
| replaygain_album_peak | number                       |   YES    |    NO    |                                |
| replaygain_track_gain | number                       |   YES    |    NO    |                                |
| replaygain_track_peak | number                       |   YES    |    NO    |                                |
| r128_album_gain       | number                       |   YES    |    NO    |                                |
| r128_track_gain       | number                       |   YES    |    NO    |                                |
| metadata              | object&lt;string, string&gt; |    NO    |   YES    |                                |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/playlist_songs.json)

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

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `podcast` list.

| Field       | Type                                   | Nullable | Optional | Notes                                |
|-------------|----------------------------------------|:--------:|:--------:|--------------------------------------|
| total_count | integer                                |    NO    |    NO    |                                      |
| md5         | string                                 |    NO    |    NO    |                                      |
| podcast     | array&lt;[PodcastObject](#podcast)&gt; |    NO    |    NO    | see [PodcastObject](#podcast) fields |

Each `podcast` entry ([PodcastObject](#podcast)):

| Field           | Type                                                  | Nullable | Optional | Notes                                               |
|-----------------|-------------------------------------------------------|:--------:|:--------:|-----------------------------------------------------|
| id              | string                                                |    NO    |    NO    |                                                     |
| name            | string                                                |   YES    |    NO    |                                                     |
| description     | string                                                |    NO    |    NO    |                                                     |
| language        | string                                                |    NO    |    NO    |                                                     |
| copyright       | string                                                |    NO    |    NO    |                                                     |
| feed_url        | string                                                |    NO    |    NO    |                                                     |
| generator       | string                                                |    NO    |    NO    |                                                     |
| website         | string                                                |    NO    |    NO    |                                                     |
| build_date      | string                                                |    NO    |    NO    |                                                     |
| sync_date       | string                                                |    NO    |    NO    |                                                     |
| public_url      | string                                                |    NO    |    NO    |                                                     |
| art             | string                                                |   YES    |    NO    |                                                     |
| has_art         | boolean                                               |    NO    |    NO    |                                                     |
| flag            | boolean                                               |    NO    |    NO    |                                                     |
| rating          | integer                                               |   YES    |    NO    |                                                     |
| averagerating   | number                                                |   YES    |    NO    |                                                     |
| podcast_episode | array&lt;[PodcastEpisodeObject](#podcast_episode)&gt; |    NO    |    NO    | see [PodcastEpisodeObject](#podcast_episode) fields |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/podcasts.json)

### podcast

Get the podcast from it's id.

| Input     | Type   | Description                                           | Optional |
|-----------|--------|-------------------------------------------------------|---------:|
| 'filter'  | string | UID of podcast, returns podcast JSON                  |       NO |
| 'include' | string | `episodes` (include podcast_episodes in the response) |      YES |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field           | Type                                                  | Nullable | Optional | Notes                                               |
|-----------------|-------------------------------------------------------|:--------:|:--------:|-----------------------------------------------------|
| id              | string                                                |    NO    |    NO    |                                                     |
| name            | string                                                |   YES    |    NO    |                                                     |
| description     | string                                                |    NO    |    NO    |                                                     |
| language        | string                                                |    NO    |    NO    |                                                     |
| copyright       | string                                                |    NO    |    NO    |                                                     |
| feed_url        | string                                                |    NO    |    NO    |                                                     |
| generator       | string                                                |    NO    |    NO    |                                                     |
| website         | string                                                |    NO    |    NO    |                                                     |
| build_date      | string                                                |    NO    |    NO    |                                                     |
| sync_date       | string                                                |    NO    |    NO    |                                                     |
| public_url      | string                                                |    NO    |    NO    |                                                     |
| art             | string                                                |   YES    |    NO    |                                                     |
| has_art         | boolean                                               |    NO    |    NO    |                                                     |
| flag            | boolean                                               |    NO    |    NO    |                                                     |
| rating          | integer                                               |   YES    |    NO    |                                                     |
| averagerating   | number                                                |   YES    |    NO    |                                                     |
| podcast_episode | array&lt;[PodcastEpisodeObject](#podcast_episode)&gt; |    NO    |    NO    | see [PodcastEpisodeObject](#podcast_episode) fields |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/podcast.json)

### podcast_create

Create a podcast that can be used by anyone to stream media.
Takes the url and catalog parameters.

**ACCESS REQUIRED:** 75 (Catalog Manager)

| Input     | Type   | Description         | Optional |
|-----------|--------|---------------------|---------:|
| 'url'     | string | rss url for podcast |       NO |
| 'catalog' | string | podcast catalog     |       NO |

* return array

```JSON
"podcast": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/podcast_create.json)

### podcast_delete

Delete an existing podcast.

**ACCESS REQUIRED:** 75 (Catalog Manager)

| Input    | Type   | Description              | Optional |
|----------|--------|--------------------------|---------:|
| 'filter' | string | UID of podcast to delete |       NO |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/podcast_delete.json)

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

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/podcast_edit.json)

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

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `podcast_episode` list.

| Field           | Type                                                  | Nullable | Optional | Notes                                               |
|-----------------|-------------------------------------------------------|:--------:|:--------:|-----------------------------------------------------|
| total_count     | integer                                               |    NO    |    NO    |                                                     |
| md5             | string                                                |    NO    |    NO    |                                                     |
| podcast_episode | array&lt;[PodcastEpisodeObject](#podcast_episode)&gt; |    NO    |    NO    | see [PodcastEpisodeObject](#podcast_episode) fields |

Each `podcast_episode` entry ([PodcastEpisodeObject](#podcast_episode)):

| Field          | Type    | Nullable | Optional | Notes        |
|----------------|---------|:--------:|:--------:|--------------|
| id             | string  |    NO    |    NO    |              |
| title          | string  |   YES    |    NO    |              |
| name           | string  |   YES    |    NO    |              |
| podcast        | object  |    NO    |    NO    | `{id, name}` |
| description    | string  |    NO    |    NO    |              |
| category       | string  |   YES    |    NO    |              |
| author         | string  |   YES    |    NO    |              |
| author_full    | string  |   YES    |    NO    |              |
| website        | string  |    NO    |    NO    |              |
| pubdate        | string  |   YES    |    NO    |              |
| state          | string  |    NO    |    NO    |              |
| filelength     | string  |    NO    |    NO    |              |
| filesize       | string  |    NO    |    NO    |              |
| filename       | string  |    NO    |    NO    |              |
| mime           | string  |   YES    |    NO    |              |
| time           | integer |    NO    |    NO    |              |
| size           | integer |    NO    |    NO    |              |
| bitrate        | integer |    NO    |    NO    |              |
| stream_bitrate | integer |    NO    |    NO    |              |
| rate           | integer |    NO    |    NO    |              |
| mode           | string  |   YES    |    NO    |              |
| channels       | integer |   YES    |    NO    |              |
| public_url     | string  |    NO    |    NO    |              |
| url            | string  |    NO    |    NO    |              |
| catalog        | string  |    NO    |    NO    |              |
| art            | string  |   YES    |    NO    |              |
| has_art        | boolean |    NO    |    NO    |              |
| flag           | boolean |    NO    |    NO    |              |
| rating         | integer |   YES    |    NO    |              |
| averagerating  | number  |   YES    |    NO    |              |
| playcount      | integer |    NO    |    NO    |              |
| played         | string  |    NO    |    NO    |              |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/podcast_episodes.json)

### podcast_episode

Get the podcast_episode from it's id.

| Input    | Type   | Description               | Optional |
|----------|--------|---------------------------|---------:|
| 'filter' | string | podcast_episode ID number |       NO |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field          | Type    | Nullable | Optional | Notes        |
|----------------|---------|:--------:|:--------:|--------------|
| id             | string  |    NO    |    NO    |              |
| title          | string  |   YES    |    NO    |              |
| name           | string  |   YES    |    NO    |              |
| podcast        | object  |    NO    |    NO    | `{id, name}` |
| description    | string  |    NO    |    NO    |              |
| category       | string  |   YES    |    NO    |              |
| author         | string  |   YES    |    NO    |              |
| author_full    | string  |   YES    |    NO    |              |
| website        | string  |    NO    |    NO    |              |
| pubdate        | string  |   YES    |    NO    |              |
| state          | string  |    NO    |    NO    |              |
| filelength     | string  |    NO    |    NO    |              |
| filesize       | string  |    NO    |    NO    |              |
| filename       | string  |    NO    |    NO    |              |
| mime           | string  |   YES    |    NO    |              |
| time           | integer |    NO    |    NO    |              |
| size           | integer |    NO    |    NO    |              |
| bitrate        | integer |    NO    |    NO    |              |
| stream_bitrate | integer |    NO    |    NO    |              |
| rate           | integer |    NO    |    NO    |              |
| mode           | string  |   YES    |    NO    |              |
| channels       | integer |   YES    |    NO    |              |
| public_url     | string  |    NO    |    NO    |              |
| url            | string  |    NO    |    NO    |              |
| catalog        | string  |    NO    |    NO    |              |
| art            | string  |   YES    |    NO    |              |
| has_art        | boolean |    NO    |    NO    |              |
| flag           | boolean |    NO    |    NO    |              |
| rating         | integer |   YES    |    NO    |              |
| averagerating  | number  |   YES    |    NO    |              |
| playcount      | integer |    NO    |    NO    |              |
| played         | string  |    NO    |    NO    |              |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/podcast_episode.json)

### podcast_episode_delete

Delete an existing podcast_episode.

| Input    | Type   | Description                      | Optional |
|----------|--------|----------------------------------|---------:|
| 'filter' | string | UID of podcast_episode to delete |       NO |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/podcast_episode_delete.json)

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

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/preference_create.json)

### preference_delete

Delete a non-system preference by name

**ACCESS REQUIRED:** 100 (Admin)

| Input    | Type   | Description                                       | Optional |
|----------|--------|---------------------------------------------------|---------:|
| 'filter' | string | Preference name e.g ('notify_email', 'ajax_load') |       NO |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/preference_delete.json)

### preference_edit

Edit a preference value and apply to all users if allowed

| Input     | Type    | Description                                                                             | Optional |
|-----------|---------|-----------------------------------------------------------------------------------------|---------:|
| 'filter'  | string  | Preference name e.g ('notify_email', 'ajax_load')                                       |       NO |
| 'value'   | mixed   | (string/integer) Preference value                                                       |       NO |
| 'all'     | boolean | `0`, `1` apply to all users **ACCESS REQUIRED:** 100 (Admin)                            |      YES |
| 'default' | boolean | `0`, `1` set as system default (New and public users)  **ACCESS REQUIRED:** 100 (Admin) |      YES |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/preference_edit.json)

### rate

This rates a library item

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `id` is deprecated and will be removed in **API9**.

| Input    | Type    | Description                                           | Optional |
|----------|---------|-------------------------------------------------------|---------:|
| 'filter' | string  | library item id                                       |       NO |
| 'type'   | string  | `song`, `album`, `artist`, `playlist`, `podcast`      |       NO |
|          |         | `podcast_episode`, `video`, `tvshow`, `tvshow_season` |          |
| 'rating' | integer | rating between 0-5                                    |       NO |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/rate.json)

### record_play

Take a song_id and update the object_count and user_activity table with a play. This allows other sources to record play history to Ampache.

If you don't supply a user id (optional) then just fall back to you.

**ACCESS REQUIRED:** 100 (Admin) permission to change another user's play history

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `id` is deprecated and will be removed in **API9**.

| Input    | Type    | Description | Optional |
|----------|---------|-------------|---------:|
| 'filter' | string  | $object_id  |       NO |
| 'user'   | string  | $user_id    |      YES |
| 'client' | string  | $agent      |      YES |
| 'date'   | integer | UNIXTIME()  |      YES |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/record_play.json)

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

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/scrobble.json)

### search

Perform an advanced search given passed rules. This works in a similar way to the web/UI search pages. **Ampache 6.3.0+**

This is the current name for the [advanced_search](#advanced_search) method; parameters and results are identical.

Refer to the [Advanced Search](https://ampache.org/api/api-advanced-search) page for details about creating searches.

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

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `video` list.

| Field       | Type                               | Nullable | Optional | Notes                            |
|-------------|------------------------------------|:--------:|:--------:|----------------------------------|
| total_count | integer                            |    NO    |    NO    |                                  |
| md5         | string                             |    NO    |    NO    |                                  |
| video       | array&lt;[VideoObject](#video)&gt; |    NO    |    NO    | see [VideoObject](#video) fields |

Each `video` entry ([VideoObject](#video)):

| Field         | Type                | Nullable | Optional | Notes        |
|---------------|---------------------|:--------:|:--------:|--------------|
| id            | string              |    NO    |    NO    |              |
| title         | string              |   YES    |    NO    |              |
| mime          | string              |   YES    |    NO    |              |
| resolution    | string              |   YES    |    NO    |              |
| size          | integer             |    NO    |    NO    |              |
| genre         | array&lt;object&gt; |    NO    |    NO    | `{id, name}` |
| time          | integer             |    NO    |    NO    |              |
| url           | string              |    NO    |    NO    |              |
| art           | string              |   YES    |    NO    |              |
| has_art       | boolean             |    NO    |    NO    |              |
| flag          | boolean             |    NO    |    NO    |              |
| rating        | integer             |   YES    |    NO    |              |
| averagerating | number              |   YES    |    NO    |              |
| playcount     | integer             |    NO    |    NO    |              |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

SONG [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/advanced_search%20\(song\).json)

ARTIST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/advanced_search%20\(artist\).json)

ALBUM [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/advanced_search%20\(album\).json)

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

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
`search` is keyed by object type (`album`, `artist`, `album_artist`, `song_artist`, `song`, `playlist`, `podcast`, `podcast_episode`, `genre`, `label`, `user`, `video`); each value is that type's usual object list. Types with no matches are omitted.

| Field  | Type                                      | Nullable | Optional | Notes |
|--------|-------------------------------------------|:--------:|:--------:|-------|
| search | object&lt;string, array&lt;object&gt;&gt; |    NO    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

ALL [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/search_group%20\(all\).json)

MUSIC [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/search_group%20\(music\).json)

PODCAST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/search_group%20\(podcast\).json)

### search_rules

Print a list of valid search rules for your search type

| Input    | Type   | Description                                     | Optional |
|----------|--------|-------------------------------------------------|---------:|
| 'filter' | string | 'song', 'album', 'song_artist', 'album_artist', |       NO |
|          |        | 'artist', 'label', 'playlist', 'podcast',       |          |
|          |        | 'podcast_episode', 'genre', 'user', 'video'     |          |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `rule` list.

| Field | Type                            | Nullable | Optional | Notes                         |
|-------|---------------------------------|:--------:|:--------:|-------------------------------|
| rule  | array&lt;`SearchRuleObject`&gt; |    NO    |    NO    | see `SearchRuleObject` fields |

Each `rule` entry (`SearchRuleObject`):

| Field  | Type                                                | Nullable | Optional | Notes |
|--------|-----------------------------------------------------|:--------:|:--------:|-------|
| name   | string                                              |    NO    |    NO    |       |
| label  | string                                              |    NO    |    NO    |       |
| type   | string                                              |    NO    |    NO    |       |
| widget | array&lt;string \| object&lt;string, string&gt;&gt; |    NO    |    NO    |       |
| title  | string                                              |    NO    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

Artist [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/search_rules%20\(artist\).json)

Album [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/search_rules%20\(album\).json)

Song [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/search_rules%20\(song\).json)

### search_songs

This searches the songs and returns... songs

**NOTE** `filter` has an alias `rule_1_input` to match other search methods

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'filter' | string  | Filter results to match this string              |       NO |
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

* return array

```JSON
"song": []
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/search_songs.json)

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

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `share` list.

| Field       | Type                               | Nullable | Optional | Notes                            |
|-------------|------------------------------------|:--------:|:--------:|----------------------------------|
| total_count | integer                            |    NO    |    NO    |                                  |
| md5         | string                             |    NO    |    NO    |                                  |
| share       | array&lt;[ShareObject](#share)&gt; |    NO    |    NO    | see [ShareObject](#share) fields |

Each `share` entry ([ShareObject](#share)):

| Field          | Type    | Nullable | Optional | Notes |
|----------------|---------|:--------:|:--------:|-------|
| id             | string  |    NO    |    NO    |       |
| name           | string  |    NO    |    NO    |       |
| owner          | string  |    NO    |    NO    |       |
| allow_stream   | boolean |    NO    |    NO    |       |
| allow_download | boolean |    NO    |    NO    |       |
| creation_date  | integer |    NO    |    NO    |       |
| lastvisit_date | integer |    NO    |    NO    |       |
| object_type    | string  |   YES    |    NO    |       |
| object_id      | string  |    NO    |    NO    |       |
| expire_days    | integer |    NO    |    NO    |       |
| max_counter    | integer |    NO    |    NO    |       |
| counter        | integer |    NO    |    NO    |       |
| secret         | string  |   YES    |    NO    |       |
| public_url     | string  |   YES    |    NO    |       |
| description    | string  |   YES    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/shares.json)

### share

Return shares by UID

| Input    | Type   | Description                     | Optional |
|----------|--------|---------------------------------|---------:|
| 'filter' | string | UID of Share, returns song JSON |       NO |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field          | Type    | Nullable | Optional | Notes |
|----------------|---------|:--------:|:--------:|-------|
| id             | string  |    NO    |    NO    |       |
| name           | string  |    NO    |    NO    |       |
| owner          | string  |    NO    |    NO    |       |
| allow_stream   | boolean |    NO    |    NO    |       |
| allow_download | boolean |    NO    |    NO    |       |
| creation_date  | integer |    NO    |    NO    |       |
| lastvisit_date | integer |    NO    |    NO    |       |
| object_type    | string  |   YES    |    NO    |       |
| object_id      | string  |    NO    |    NO    |       |
| expire_days    | integer |    NO    |    NO    |       |
| max_counter    | integer |    NO    |    NO    |       |
| counter        | integer |    NO    |    NO    |       |
| secret         | string  |   YES    |    NO    |       |
| public_url     | string  |   YES    |    NO    |       |
| description    | string  |   YES    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/share.json)

### share_create

Create a public url that can be used by anyone to stream media.
Takes the file id with optional description and expires parameters.

| Input         | Type    | Description                                   | Optional |
|---------------|---------|-----------------------------------------------|---------:|
| 'filter'      | string  | UID of object you are sharing                 |       NO |
| 'type'        | string  | object_type                                   |       NO |
| 'description' | string  | description (will be filled for you if empty) |      YES |
| 'expires'     | integer | days to keep active                           |      YES |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field          | Type    | Nullable | Optional | Notes |
|----------------|---------|:--------:|:--------:|-------|
| id             | string  |    NO    |    NO    |       |
| name           | string  |    NO    |    NO    |       |
| owner          | string  |    NO    |    NO    |       |
| allow_stream   | boolean |    NO    |    NO    |       |
| allow_download | boolean |    NO    |    NO    |       |
| creation_date  | integer |    NO    |    NO    |       |
| lastvisit_date | integer |    NO    |    NO    |       |
| object_type    | string  |   YES    |    NO    |       |
| object_id      | string  |    NO    |    NO    |       |
| expire_days    | integer |    NO    |    NO    |       |
| max_counter    | integer |    NO    |    NO    |       |
| counter        | integer |    NO    |    NO    |       |
| secret         | string  |   YES    |    NO    |       |
| public_url     | string  |   YES    |    NO    |       |
| description    | string  |   YES    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/share_create.json)

### share_delete

Delete an existing share.

| Input    | Type   | Description            | Optional |
|----------|--------|------------------------|---------:|
| 'filter' | string | UID of Share to delete |       NO |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/share_delete.json)

### share_edit

Update the description and/or expiration date for an existing share.
Takes the share id to update with optional description and expires parameters.

| Input         | Type    | Description                  | Optional |
|---------------|---------|------------------------------|---------:|
| 'filter'      | string  | Alpha-numeric search term    |       NO |
| 'stream'      | boolean | `0`, `1`                     |      YES |
| 'download'    | boolean | `0`, `1`                     |      YES |
| 'expires'     | integer | number of days before expiry |      YES |
| 'description' | string  | update description           |      YES |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/share_edit.json)

### smartlists

This returns smartlists based on the specified filter

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

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `playlist` list.

| Field       | Type                                     | Nullable | Optional | Notes                                  |
|-------------|------------------------------------------|:--------:|:--------:|----------------------------------------|
| total_count | integer                                  |    NO    |    NO    |                                        |
| md5         | string                                   |    NO    |    NO    |                                        |
| playlist    | array&lt;[PlaylistObject](#playlist)&gt; |    NO    |    NO    | see [PlaylistObject](#playlist) fields |

Each `playlist` entry ([PlaylistObject](#playlist)):

| Field           | Type                           | Nullable | Optional | Notes            |
|-----------------|--------------------------------|:--------:|:--------:|------------------|
| id              | string                         |    NO    |    NO    |                  |
| name            | string                         |   YES    |    NO    |                  |
| owner           | string                         |   YES    |    NO    |                  |
| user            | object                         |    NO    |    NO    | `{id, username}` |
| items           | array&lt;object&gt; \| integer |    NO    |    NO    |                  |
| type            | string                         |   YES    |    NO    |                  |
| art             | string                         |   YES    |    NO    |                  |
| has_access      | boolean                        |    NO    |    NO    |                  |
| has_collaborate | boolean                        |    NO    |    NO    |                  |
| has_art         | boolean                        |    NO    |    NO    |                  |
| flag            | boolean                        |    NO    |    NO    |                  |
| rating          | integer                        |   YES    |    NO    |                  |
| averagerating   | number                         |   YES    |    NO    |                  |
| md5             | string                         |   YES    |    NO    |                  |
| last_update     | integer                        |   YES    |    NO    |                  |
| time            | integer                        |    NO    |    NO    |                  |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/smartlists.json)

### smartlist

This returns a single smartlist

| Input    | Type   | Description                              | Optional |
|----------|--------|------------------------------------------|---------:|
| 'filter' | string | UID of smartlist, returns smartlist JSON |       NO |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field           | Type                           | Nullable | Optional | Notes            |
|-----------------|--------------------------------|:--------:|:--------:|------------------|
| id              | string                         |    NO    |    NO    |                  |
| name            | string                         |   YES    |    NO    |                  |
| owner           | string                         |   YES    |    NO    |                  |
| user            | object                         |    NO    |    NO    | `{id, username}` |
| items           | array&lt;object&gt; \| integer |    NO    |    NO    |                  |
| type            | string                         |   YES    |    NO    |                  |
| art             | string                         |   YES    |    NO    |                  |
| has_access      | boolean                        |    NO    |    NO    |                  |
| has_collaborate | boolean                        |    NO    |    NO    |                  |
| has_art         | boolean                        |    NO    |    NO    |                  |
| flag            | boolean                        |    NO    |    NO    |                  |
| rating          | integer                        |   YES    |    NO    |                  |
| averagerating   | number                         |   YES    |    NO    |                  |
| md5             | string                         |   YES    |    NO    |                  |
| last_update     | integer                        |   YES    |    NO    |                  |
| time            | integer                        |    NO    |    NO    |                  |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/smartlist.json)

### smartlist_songs

This returns the songs for a smartlist

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'filter' | string  | UID of smartlist, returns song JSON              |       NO |
| 'random' | integer | `0`, `1` (if true get random songs using limit)  |      YES |
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `song` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| song        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |

Each `song` entry ([SongObject](#song)):

| Field                 | Type                         | Nullable | Optional | Notes                          |
|-----------------------|------------------------------|:--------:|:--------:|--------------------------------|
| id                    | string                       |    NO    |    NO    |                                |
| title                 | string                       |   YES    |    NO    |                                |
| name                  | string                       |   YES    |    NO    |                                |
| artist                | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| artists               | array&lt;object&gt;          |    NO    |    NO    | `{id, name, prefix, basename}` |
| album                 | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| albumartist           | object                       |    NO    |   YES    | `{id, name, prefix, basename}` |
| disk                  | integer                      |    NO    |    NO    |                                |
| disksubtitle          | string                       |   YES    |    NO    |                                |
| track                 | integer                      |    NO    |    NO    |                                |
| filename              | string                       |   YES    |    NO    |                                |
| genre                 | array&lt;object&gt;          |    NO    |    NO    | `{id, name}`                   |
| playlisttrack         | integer                      |    NO    |    NO    |                                |
| time                  | integer                      |    NO    |    NO    |                                |
| year                  | integer                      |    NO    |    NO    |                                |
| format                | string                       |   YES    |    NO    |                                |
| stream_format         | string                       |   YES    |    NO    |                                |
| bitrate               | integer                      |   YES    |    NO    |                                |
| stream_bitrate        | integer                      |   YES    |    NO    |                                |
| rate                  | integer                      |    NO    |    NO    |                                |
| mode                  | string                       |   YES    |    NO    |                                |
| mime                  | string                       |   YES    |    NO    |                                |
| stream_mime           | string                       |   YES    |    NO    |                                |
| url                   | string                       |    NO    |    NO    |                                |
| size                  | integer                      |    NO    |    NO    |                                |
| mbid                  | string                       |   YES    |    NO    |                                |
| art                   | string                       |   YES    |    NO    |                                |
| has_art               | boolean                      |    NO    |    NO    |                                |
| flag                  | boolean                      |    NO    |    NO    |                                |
| rating                | integer                      |   YES    |    NO    |                                |
| averagerating         | number                       |   YES    |    NO    |                                |
| playcount             | integer                      |    NO    |    NO    |                                |
| catalog               | string                       |    NO    |    NO    |                                |
| composer              | string                       |   YES    |    NO    |                                |
| channels              | integer                      |   YES    |    NO    |                                |
| comment               | string                       |   YES    |    NO    |                                |
| license               | string                       |   YES    |    NO    |                                |
| publisher             | string                       |   YES    |    NO    |                                |
| language              | string                       |   YES    |    NO    |                                |
| lyrics                | string                       |   YES    |    NO    |                                |
| replaygain_album_gain | number                       |   YES    |    NO    |                                |
| replaygain_album_peak | number                       |   YES    |    NO    |                                |
| replaygain_track_gain | number                       |   YES    |    NO    |                                |
| replaygain_track_peak | number                       |   YES    |    NO    |                                |
| r128_album_gain       | number                       |   YES    |    NO    |                                |
| r128_track_gain       | number                       |   YES    |    NO    |                                |
| metadata              | object&lt;string, string&gt; |    NO    |   YES    |                                |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/smartlist_songs.json)

### smartlist_delete

This deletes a smartlist

| Input    | Type   | Description      | Optional |
|----------|--------|------------------|---------:|
| 'filter' | string | UID of smartlist |       NO |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/smartlist_delete.json)

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

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `song` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| song        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |

Each `song` entry ([SongObject](#song)):

| Field                 | Type                         | Nullable | Optional | Notes                          |
|-----------------------|------------------------------|:--------:|:--------:|--------------------------------|
| id                    | string                       |    NO    |    NO    |                                |
| title                 | string                       |   YES    |    NO    |                                |
| name                  | string                       |   YES    |    NO    |                                |
| artist                | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| artists               | array&lt;object&gt;          |    NO    |    NO    | `{id, name, prefix, basename}` |
| album                 | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| albumartist           | object                       |    NO    |   YES    | `{id, name, prefix, basename}` |
| disk                  | integer                      |    NO    |    NO    |                                |
| disksubtitle          | string                       |   YES    |    NO    |                                |
| track                 | integer                      |    NO    |    NO    |                                |
| filename              | string                       |   YES    |    NO    |                                |
| genre                 | array&lt;object&gt;          |    NO    |    NO    | `{id, name}`                   |
| playlisttrack         | integer                      |    NO    |    NO    |                                |
| time                  | integer                      |    NO    |    NO    |                                |
| year                  | integer                      |    NO    |    NO    |                                |
| format                | string                       |   YES    |    NO    |                                |
| stream_format         | string                       |   YES    |    NO    |                                |
| bitrate               | integer                      |   YES    |    NO    |                                |
| stream_bitrate        | integer                      |   YES    |    NO    |                                |
| rate                  | integer                      |    NO    |    NO    |                                |
| mode                  | string                       |   YES    |    NO    |                                |
| mime                  | string                       |   YES    |    NO    |                                |
| stream_mime           | string                       |   YES    |    NO    |                                |
| url                   | string                       |    NO    |    NO    |                                |
| size                  | integer                      |    NO    |    NO    |                                |
| mbid                  | string                       |   YES    |    NO    |                                |
| art                   | string                       |   YES    |    NO    |                                |
| has_art               | boolean                      |    NO    |    NO    |                                |
| flag                  | boolean                      |    NO    |    NO    |                                |
| rating                | integer                      |   YES    |    NO    |                                |
| averagerating         | number                       |   YES    |    NO    |                                |
| playcount             | integer                      |    NO    |    NO    |                                |
| catalog               | string                       |    NO    |    NO    |                                |
| composer              | string                       |   YES    |    NO    |                                |
| channels              | integer                      |   YES    |    NO    |                                |
| comment               | string                       |   YES    |    NO    |                                |
| license               | string                       |   YES    |    NO    |                                |
| publisher             | string                       |   YES    |    NO    |                                |
| language              | string                       |   YES    |    NO    |                                |
| lyrics                | string                       |   YES    |    NO    |                                |
| replaygain_album_gain | number                       |   YES    |    NO    |                                |
| replaygain_album_peak | number                       |   YES    |    NO    |                                |
| replaygain_track_gain | number                       |   YES    |    NO    |                                |
| replaygain_track_peak | number                       |   YES    |    NO    |                                |
| r128_album_gain       | number                       |   YES    |    NO    |                                |
| r128_track_gain       | number                       |   YES    |    NO    |                                |
| metadata              | object&lt;string, string&gt; |    NO    |   YES    |                                |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/songs.json)

### song

returns a single song

| Input    | Type   | Description                    | Optional |
|----------|--------|--------------------------------|---------:|
| 'filter' | string | UID of Song, returns song JSON |       NO |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field                 | Type                         | Nullable | Optional | Notes                          |
|-----------------------|------------------------------|:--------:|:--------:|--------------------------------|
| id                    | string                       |    NO    |    NO    |                                |
| title                 | string                       |   YES    |    NO    |                                |
| name                  | string                       |   YES    |    NO    |                                |
| artist                | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| artists               | array&lt;object&gt;          |    NO    |    NO    | `{id, name, prefix, basename}` |
| album                 | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| albumartist           | object                       |    NO    |   YES    | `{id, name, prefix, basename}` |
| disk                  | integer                      |    NO    |    NO    |                                |
| disksubtitle          | string                       |   YES    |    NO    |                                |
| track                 | integer                      |    NO    |    NO    |                                |
| filename              | string                       |   YES    |    NO    |                                |
| genre                 | array&lt;object&gt;          |    NO    |    NO    | `{id, name}`                   |
| playlisttrack         | integer                      |    NO    |    NO    |                                |
| time                  | integer                      |    NO    |    NO    |                                |
| year                  | integer                      |    NO    |    NO    |                                |
| format                | string                       |   YES    |    NO    |                                |
| stream_format         | string                       |   YES    |    NO    |                                |
| bitrate               | integer                      |   YES    |    NO    |                                |
| stream_bitrate        | integer                      |   YES    |    NO    |                                |
| rate                  | integer                      |    NO    |    NO    |                                |
| mode                  | string                       |   YES    |    NO    |                                |
| mime                  | string                       |   YES    |    NO    |                                |
| stream_mime           | string                       |   YES    |    NO    |                                |
| url                   | string                       |    NO    |    NO    |                                |
| size                  | integer                      |    NO    |    NO    |                                |
| mbid                  | string                       |   YES    |    NO    |                                |
| art                   | string                       |   YES    |    NO    |                                |
| has_art               | boolean                      |    NO    |    NO    |                                |
| flag                  | boolean                      |    NO    |    NO    |                                |
| rating                | integer                      |   YES    |    NO    |                                |
| averagerating         | number                       |   YES    |    NO    |                                |
| playcount             | integer                      |    NO    |    NO    |                                |
| catalog               | string                       |    NO    |    NO    |                                |
| composer              | string                       |   YES    |    NO    |                                |
| channels              | integer                      |   YES    |    NO    |                                |
| comment               | string                       |   YES    |    NO    |                                |
| license               | string                       |   YES    |    NO    |                                |
| publisher             | string                       |   YES    |    NO    |                                |
| language              | string                       |   YES    |    NO    |                                |
| lyrics                | string                       |   YES    |    NO    |                                |
| replaygain_album_gain | number                       |   YES    |    NO    |                                |
| replaygain_album_peak | number                       |   YES    |    NO    |                                |
| replaygain_track_gain | number                       |   YES    |    NO    |                                |
| replaygain_track_peak | number                       |   YES    |    NO    |                                |
| r128_album_gain       | number                       |   YES    |    NO    |                                |
| r128_track_gain       | number                       |   YES    |    NO    |                                |
| metadata              | object&lt;string, string&gt; |    NO    |   YES    |                                |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/song.json)

### song_delete

Delete an existing song. (if you are allowed to)

| Input    | Type   | Description           | Optional |
|----------|--------|-----------------------|---------:|
| 'filter' | string | UID of song to delete |       NO |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/song_delete.json)

### song_tags

Get the full song file tags using VaInfo

This is used to get tags for remote catalogs to allow maximum data to be returned

| Input    | Type   | Description          | Optional |
|----------|--------|----------------------|---------:|
| 'filter' | string | UID of song to fetch |       NO |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field                  | Type                | Nullable | Optional | Notes |
|------------------------|---------------------|:--------:|:--------:|-------|
| id                     | string              |    NO    |    NO    |       |
| albumartist            | string              |   YES    |    NO    |       |
| album                  | string              |   YES    |    NO    |       |
| artist                 | string              |   YES    |    NO    |       |
| artists                | array&lt;string&gt; |   YES    |    NO    |       |
| art                    | string              |   YES    |    NO    |       |
| audio_codec            | string              |   YES    |    NO    |       |
| barcode                | string              |   YES    |    NO    |       |
| bitrate                | integer             |   YES    |    NO    |       |
| catalog                | integer             |   YES    |    NO    |       |
| catalog_number         | string              |   YES    |    NO    |       |
| channels               | integer             |   YES    |    NO    |       |
| comment                | string              |   YES    |    NO    |       |
| composer               | string              |   YES    |    NO    |       |
| description            | string              |   YES    |    NO    |       |
| disk                   | integer             |   YES    |    NO    |       |
| disksubtitle           | string              |   YES    |    NO    |       |
| display_x              | integer             |   YES    |    NO    |       |
| display_y              | integer             |   YES    |    NO    |       |
| encoding               | string              |   YES    |    NO    |       |
| file                   | string              |   YES    |    NO    |       |
| frame_rate             | number              |   YES    |    NO    |       |
| genre                  | array&lt;string&gt; |   YES    |    NO    |       |
| isrc                   | string              |   YES    |    NO    |       |
| language               | string              |   YES    |    NO    |       |
| lyrics                 | string              |   YES    |    NO    |       |
| mb_albumartistid       | string              |   YES    |    NO    |       |
| mb_albumartistid_array | array&lt;string&gt; |   YES    |    NO    |       |
| mb_albumid_group       | string              |   YES    |    NO    |       |
| mb_albumid             | string              |   YES    |    NO    |       |
| mb_artistid            | string              |   YES    |    NO    |       |
| mb_artistid_array      | array&lt;string&gt; |   YES    |    NO    |       |
| mb_trackid             | string              |   YES    |    NO    |       |
| mime                   | string              |   YES    |    NO    |       |
| mode                   | string              |   YES    |    NO    |       |
| original_name          | string              |   YES    |    NO    |       |
| original_year          | string              |   YES    |    NO    |       |
| publisher              | string              |   YES    |    NO    |       |
| r128_album_gain        | integer             |   YES    |    NO    |       |
| r128_track_gain        | integer             |   YES    |    NO    |       |
| rate                   | integer             |   YES    |    NO    |       |
| rating                 | number              |   YES    |    NO    |       |
| release_date           | string              |   YES    |    NO    |       |
| release_status         | string              |   YES    |    NO    |       |
| release_type           | string              |   YES    |    NO    |       |
| replaygain_album_gain  | number              |   YES    |    NO    |       |
| replaygain_album_peak  | number              |   YES    |    NO    |       |
| replaygain_track_gain  | number              |   YES    |    NO    |       |
| replaygain_track_peak  | number              |   YES    |    NO    |       |
| size                   | integer             |   YES    |    NO    |       |
| version                | string              |   YES    |    NO    |       |
| summary                | string              |   YES    |    NO    |       |
| time                   | integer             |   YES    |    NO    |       |
| title                  | string              |   YES    |    NO    |       |
| totaldisks             | integer             |   YES    |    NO    |       |
| totaltracks            | integer             |   YES    |    NO    |       |
| track                  | integer             |   YES    |    NO    |       |
| year                   | integer             |   YES    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/song_tags.json)

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

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `video` list.

| Field       | Type                               | Nullable | Optional | Notes                            |
|-------------|------------------------------------|:--------:|:--------:|----------------------------------|
| total_count | integer                            |    NO    |    NO    |                                  |
| md5         | string                             |    NO    |    NO    |                                  |
| video       | array&lt;[VideoObject](#video)&gt; |    NO    |    NO    | see [VideoObject](#video) fields |

Each `video` entry ([VideoObject](#video)):

| Field         | Type                | Nullable | Optional | Notes        |
|---------------|---------------------|:--------:|:--------:|--------------|
| id            | string              |    NO    |    NO    |              |
| title         | string              |   YES    |    NO    |              |
| mime          | string              |   YES    |    NO    |              |
| resolution    | string              |   YES    |    NO    |              |
| size          | integer             |    NO    |    NO    |              |
| genre         | array&lt;object&gt; |    NO    |    NO    | `{id, name}` |
| time          | integer             |    NO    |    NO    |              |
| url           | string              |    NO    |    NO    |              |
| art           | string              |   YES    |    NO    |              |
| has_art       | boolean             |    NO    |    NO    |              |
| flag          | boolean             |    NO    |    NO    |              |
| rating        | integer             |   YES    |    NO    |              |
| averagerating | number              |   YES    |    NO    |              |
| playcount     | integer             |    NO    |    NO    |              |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

SONG [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/stats%20\(song\).json)

ARTIST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/stats%20\(artist\).json)

ALBUM [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/stats%20\(album\).json)

### system_preference

Get your server preference by name

**ACCESS REQUIRED:** 100 (Admin)

| Input    | Type   | Description                                       | Optional |
|----------|--------|---------------------------------------------------|---------:|
| 'filter' | string | Preference name e.g ('notify_email', 'ajax_load') |       NO |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field       | Type                                        | Nullable | Optional | Notes |
|-------------|---------------------------------------------|:--------:|:--------:|-------|
| id          | string                                      |    NO    |    NO    |       |
| name        | string                                      |    NO    |    NO    |       |
| value       | string                                      |    NO    |    NO    |       |
| description | string                                      |    NO    |    NO    |       |
| level       | integer                                     |    NO    |    NO    |       |
| type        | string                                      |    NO    |    NO    |       |
| category    | string                                      |    NO    |    NO    |       |
| subcategory | string                                      |   YES    |    NO    |       |
| has_access  | boolean                                     |    NO    |   YES    |       |
| values      | array&lt;string&gt; \| array&lt;integer&gt; |    NO    |   YES    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/system_preferences.json)

### timeline

This gets a user's timeline

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `username` is deprecated and will be removed in **API9**.

| Input    | Type    | Description                                       | Optional |
|----------|---------|---------------------------------------------------|---------:|
| 'filter' | string  | Username of the user for whom to get the timeline |       NO |
| 'limit'  | integer | Maximum number of results to return               |      YES |
| 'since'  | integer | UNIXTIME()                                        |      YES |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `activity` list.

| Field    | Type                          | Nullable | Optional | Notes                       |
|----------|-------------------------------|:--------:|:--------:|-----------------------------|
| activity | array&lt;`ActivityObject`&gt; |    NO    |    NO    | see `ActivityObject` fields |

Each `activity` entry (`ActivityObject`):

| Field       | Type    | Nullable | Optional | Notes            |
|-------------|---------|:--------:|:--------:|------------------|
| id          | string  |    NO    |    NO    |                  |
| date        | integer |    NO    |    NO    |                  |
| object_type | string  |   YES    |    NO    |                  |
| object_id   | string  |    NO    |    NO    |                  |
| action      | string  |    NO    |    NO    |                  |
| user        | object  |    NO    |    NO    | `{id, username}` |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/timeline.json)

### toggle_follow

This follow/unfollow a user

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `username` is deprecated and will be removed in **API9**.

| Input    | Type   | Description                             | Optional |
|----------|--------|-----------------------------------------|---------:|
| 'filter' | string | Username of the user to follow/unfollow |       NO |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/toggle_follow.json)

### update_art

Updates a single album, artist, song running the gather_art process
Existing art is replaced unless you send overwrite=0, which keeps whatever is already there.

**ACCESS REQUIRED:** 75 (Catalog Manager)

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `id` is deprecated and will be removed in **API9**.

| Input       | Type    | Description                    | Optional |
|-------------|---------|--------------------------------|---------:|
| 'id'        | string  | $object_id                     |       NO |
| 'filter'    | string  | Alias of `id` (Ampache 7.9.0+) |      YES |
| 'type'      | string  | `song`, `podcast`              |       NO |
| 'overwrite' | boolean | `0`, `1`                       |      YES |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/update_art.json)

### update_artist_info

Update artist information and fetch similar artists from last.fm
Make sure lastfm_API_key is set in your configuration file

**ACCESS REQUIRED:** 75 (Catalog Manager)

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `id` is deprecated and will be removed in **API9**.

| Input    | Type   | Description | Optional |
|----------|--------|-------------|---------:|
| 'filter' | string | $artist_id  |       NO |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/update_artist_info.json)

### update_from_tags

Update a single album, artist, song from the tag data

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `id` is deprecated and will be removed in **API9**.

| Input    | Type   | Description                     | Optional |
|----------|--------|---------------------------------|---------:|
| 'type'   | string | `song`, `artist`, `album`       |       NO |
| 'filter' | string | $artist_id, $album_id, $song_id |       NO |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/update_from_tags.json)

### update_podcast

Sync and download new podcast episodes

**ACCESS REQUIRED:** 50 (Content Manager)

**NOTE** There was an error in documentation listing `id` as a valid parameter. (`id` will work in Ampache 7.9.0 / API 6.9.1+)

| Input    | Type   | Description                        | Optional |
|----------|--------|------------------------------------|---------:|
| 'filter' | string | $object_id                         |       NO |
| 'id'     | string | Alias of `filter` (Ampache 7.9.0+) |      YES |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/update_podcast.json)

### url_to_song

This takes a url and returns the song object in question

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `url` is deprecated and will be removed in **API9**.

| Input    | Type   | Description                                                   | Optional |
|----------|--------|---------------------------------------------------------------|---------:|
| 'filter' | string | Full Ampache URL from server, translates back into a song XML |       NO |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `song` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| song        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |

Each `song` entry ([SongObject](#song)):

| Field                 | Type                         | Nullable | Optional | Notes                          |
|-----------------------|------------------------------|:--------:|:--------:|--------------------------------|
| id                    | string                       |    NO    |    NO    |                                |
| title                 | string                       |   YES    |    NO    |                                |
| name                  | string                       |   YES    |    NO    |                                |
| artist                | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| artists               | array&lt;object&gt;          |    NO    |    NO    | `{id, name, prefix, basename}` |
| album                 | object                       |    NO    |    NO    | `{id, name, prefix, basename}` |
| albumartist           | object                       |    NO    |   YES    | `{id, name, prefix, basename}` |
| disk                  | integer                      |    NO    |    NO    |                                |
| disksubtitle          | string                       |   YES    |    NO    |                                |
| track                 | integer                      |    NO    |    NO    |                                |
| filename              | string                       |   YES    |    NO    |                                |
| genre                 | array&lt;object&gt;          |    NO    |    NO    | `{id, name}`                   |
| playlisttrack         | integer                      |    NO    |    NO    |                                |
| time                  | integer                      |    NO    |    NO    |                                |
| year                  | integer                      |    NO    |    NO    |                                |
| format                | string                       |   YES    |    NO    |                                |
| stream_format         | string                       |   YES    |    NO    |                                |
| bitrate               | integer                      |   YES    |    NO    |                                |
| stream_bitrate        | integer                      |   YES    |    NO    |                                |
| rate                  | integer                      |    NO    |    NO    |                                |
| mode                  | string                       |   YES    |    NO    |                                |
| mime                  | string                       |   YES    |    NO    |                                |
| stream_mime           | string                       |   YES    |    NO    |                                |
| url                   | string                       |    NO    |    NO    |                                |
| size                  | integer                      |    NO    |    NO    |                                |
| mbid                  | string                       |   YES    |    NO    |                                |
| art                   | string                       |   YES    |    NO    |                                |
| has_art               | boolean                      |    NO    |    NO    |                                |
| flag                  | boolean                      |    NO    |    NO    |                                |
| rating                | integer                      |   YES    |    NO    |                                |
| averagerating         | number                       |   YES    |    NO    |                                |
| playcount             | integer                      |    NO    |    NO    |                                |
| catalog               | string                       |    NO    |    NO    |                                |
| composer              | string                       |   YES    |    NO    |                                |
| channels              | integer                      |   YES    |    NO    |                                |
| comment               | string                       |   YES    |    NO    |                                |
| license               | string                       |   YES    |    NO    |                                |
| publisher             | string                       |   YES    |    NO    |                                |
| language              | string                       |   YES    |    NO    |                                |
| lyrics                | string                       |   YES    |    NO    |                                |
| replaygain_album_gain | number                       |   YES    |    NO    |                                |
| replaygain_album_peak | number                       |   YES    |    NO    |                                |
| replaygain_track_gain | number                       |   YES    |    NO    |                                |
| replaygain_track_peak | number                       |   YES    |    NO    |                                |
| r128_album_gain       | number                       |   YES    |    NO    |                                |
| r128_track_gain       | number                       |   YES    |    NO    |                                |
| metadata              | object&lt;string, string&gt; |    NO    |   YES    |                                |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/url_to_song.json)

### user

This gets a user's public information.

If the username is omitted, this will return the current api user's public information.

| Input      | Type   | Description                             | Optional |
|------------|--------|-----------------------------------------|---------:|
| 'username' | string | Username of the user to get details for |      YES |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field           | Type    | Nullable | Optional | Notes |
|-----------------|---------|:--------:|:--------:|-------|
| id              | string  |    NO    |    NO    |       |
| username        | string  |   YES    |    NO    |       |
| create_date     | integer |   YES    |    NO    |       |
| last_seen       | integer |    NO    |    NO    |       |
| link            | string  |    NO    |    NO    |       |
| website         | string  |   YES    |    NO    |       |
| state           | string  |   YES    |    NO    |       |
| city            | string  |   YES    |    NO    |       |
| art             | string  |   YES    |    NO    |       |
| has_art         | boolean |    NO    |    NO    |       |
| auth            | string  |   YES    |   YES    |       |
| email           | string  |   YES    |   YES    |       |
| access          | integer |    NO    |   YES    |       |
| streamtoken     | string  |   YES    |   YES    |       |
| fullname_public | boolean |    NO    |   YES    |       |
| validation      | string  |   YES    |   YES    |       |
| disabled        | boolean |    NO    |   YES    |       |
| fullname        | string  |   YES    |   YES    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/user.json)

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

**NOTE** For privacy, send `password` in a form or JSON request body rather than the query string. Query-string support for `password` is deprecated and will be removed in **API9**.

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/user_create.json)

### user_delete

Delete an existing user.

**ACCESS REQUIRED:** 100 (Admin)

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `username` is deprecated and will be removed in **API9**.

| Input    | Type   | Description | Optional |
|----------|--------|-------------|---------:|
| 'filter' | string |             |       NO |

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/user_delete.json)

### user_edit

Update an existing user.

**ACCESS REQUIRED:** 100 (Admin)

**NOTE** This function has been renamed from user_update to match other edit functions

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `username` is deprecated and will be removed in **API9**.

| Input               | Type    | Description                              | Optional |
|---------------------|---------|------------------------------------------|---------:|
| 'filter'            | string  | $username                                |       NO |
| 'password'          | string  | hash('sha256', $password)                |      YES |
| 'email'             | string  | e.g. `user@gmail.com`                    |      YES |
| 'fullname'          | string  |                                          |      YES |
| 'website'           | string  |                                          |      YES |
| 'state'             | string  |                                          |      YES |
| 'city'              | string  |                                          |      YES |
| 'disable'           | boolean | `0`, `1`                                 |      YES |
| 'group'             | integer | Catalog filter group, default = 0        |      YES |
| 'maxbitrate'        | string  | Transcode bitrate in bps, e.g. `320000`  |      YES |
| 'fullname_public'   | integer | `0`, `1` show fullname in public display |      YES |
| 'reset_apikey'      | integer | `0`, `1` reset user Api Key              |      YES |
| 'reset_streamtoken' | integer | `0`, `1` reset user Stream Token         |      YES |
| 'clear_stats'       | integer | `0`, `1` reset all stats for this user   |      YES |

**NOTE** For privacy, send `password` in a form or JSON request body rather than the query string. Query-string support for `password` is deprecated and will be removed in **API9**.

* return object

```JSON
"success": ""
```

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/user_edit.json)

### user_playlists

This returns playlists based on the specified filter

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

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `playlist` list.

| Field       | Type                                     | Nullable | Optional | Notes                                  |
|-------------|------------------------------------------|:--------:|:--------:|----------------------------------------|
| total_count | integer                                  |    NO    |    NO    |                                        |
| md5         | string                                   |    NO    |    NO    |                                        |
| playlist    | array&lt;[PlaylistObject](#playlist)&gt; |    NO    |    NO    | see [PlaylistObject](#playlist) fields |

Each `playlist` entry ([PlaylistObject](#playlist)):

| Field           | Type                           | Nullable | Optional | Notes            |
|-----------------|--------------------------------|:--------:|:--------:|------------------|
| id              | string                         |    NO    |    NO    |                  |
| name            | string                         |   YES    |    NO    |                  |
| owner           | string                         |   YES    |    NO    |                  |
| user            | object                         |    NO    |    NO    | `{id, username}` |
| items           | array&lt;object&gt; \| integer |    NO    |    NO    |                  |
| type            | string                         |   YES    |    NO    |                  |
| art             | string                         |   YES    |    NO    |                  |
| has_access      | boolean                        |    NO    |    NO    |                  |
| has_collaborate | boolean                        |    NO    |    NO    |                  |
| has_art         | boolean                        |    NO    |    NO    |                  |
| flag            | boolean                        |    NO    |    NO    |                  |
| rating          | integer                        |   YES    |    NO    |                  |
| averagerating   | number                         |   YES    |    NO    |                  |
| md5             | string                         |   YES    |    NO    |                  |
| last_update     | integer                        |   YES    |    NO    |                  |
| time            | integer                        |    NO    |    NO    |                  |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/user_playlists.json)

### user_preference

Get your user preference by name

| Input    | Type   | Description                                       | Optional |
|----------|--------|---------------------------------------------------|---------:|
| 'filter' | string | Preference name e.g ('notify_email', 'ajax_load') |       NO |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field       | Type                                        | Nullable | Optional | Notes |
|-------------|---------------------------------------------|:--------:|:--------:|-------|
| id          | string                                      |    NO    |    NO    |       |
| name        | string                                      |    NO    |    NO    |       |
| value       | string                                      |    NO    |    NO    |       |
| description | string                                      |    NO    |    NO    |       |
| level       | integer                                     |    NO    |    NO    |       |
| type        | string                                      |    NO    |    NO    |       |
| category    | string                                      |    NO    |    NO    |       |
| subcategory | string                                      |   YES    |    NO    |       |
| has_access  | boolean                                     |    NO    |   YES    |       |
| values      | array&lt;string&gt; \| array&lt;integer&gt; |    NO    |   YES    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/user_preference.json)

### user_smartlists

This returns smartlists based on the specified filter

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

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `playlist` list.

| Field       | Type                                     | Nullable | Optional | Notes                                  |
|-------------|------------------------------------------|:--------:|:--------:|----------------------------------------|
| total_count | integer                                  |    NO    |    NO    |                                        |
| md5         | string                                   |    NO    |    NO    |                                        |
| playlist    | array&lt;[PlaylistObject](#playlist)&gt; |    NO    |    NO    | see [PlaylistObject](#playlist) fields |

Each `playlist` entry ([PlaylistObject](#playlist)):

| Field           | Type                           | Nullable | Optional | Notes            |
|-----------------|--------------------------------|:--------:|:--------:|------------------|
| id              | string                         |    NO    |    NO    |                  |
| name            | string                         |   YES    |    NO    |                  |
| owner           | string                         |   YES    |    NO    |                  |
| user            | object                         |    NO    |    NO    | `{id, username}` |
| items           | array&lt;object&gt; \| integer |    NO    |    NO    |                  |
| type            | string                         |   YES    |    NO    |                  |
| art             | string                         |   YES    |    NO    |                  |
| has_access      | boolean                        |    NO    |    NO    |                  |
| has_collaborate | boolean                        |    NO    |    NO    |                  |
| has_art         | boolean                        |    NO    |    NO    |                  |
| flag            | boolean                        |    NO    |    NO    |                  |
| rating          | integer                        |   YES    |    NO    |                  |
| averagerating   | number                         |   YES    |    NO    |                  |
| md5             | string                         |   YES    |    NO    |                  |
| last_update     | integer                        |   YES    |    NO    |                  |
| time            | integer                        |    NO    |    NO    |                  |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/user_smartlists.json)

### videos

This returns video objects!

| Input    | Type    | Description                                                     | Optional |
|----------|---------|-----------------------------------------------------------------|---------:|
| 'filter' | string  | Filter results to match this string                             |      YES |
| 'exact'  | boolean | `0`, `1` (if true filter is exact `=` rather than fuzzy `LIKE`) |      YES |
| 'offset' | integer | Return results starting from this index position                |      YES |
| 'limit'  | integer | Maximum number of results to return                             |      YES |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `video` list.

| Field       | Type                               | Nullable | Optional | Notes                            |
|-------------|------------------------------------|:--------:|:--------:|----------------------------------|
| total_count | integer                            |    NO    |    NO    |                                  |
| md5         | string                             |    NO    |    NO    |                                  |
| video       | array&lt;[VideoObject](#video)&gt; |    NO    |    NO    | see [VideoObject](#video) fields |

Each `video` entry ([VideoObject](#video)):

| Field         | Type                | Nullable | Optional | Notes        |
|---------------|---------------------|:--------:|:--------:|--------------|
| id            | string              |    NO    |    NO    |              |
| title         | string              |   YES    |    NO    |              |
| mime          | string              |   YES    |    NO    |              |
| resolution    | string              |   YES    |    NO    |              |
| size          | integer             |    NO    |    NO    |              |
| genre         | array&lt;object&gt; |    NO    |    NO    | `{id, name}` |
| time          | integer             |    NO    |    NO    |              |
| url           | string              |    NO    |    NO    |              |
| art           | string              |   YES    |    NO    |              |
| has_art       | boolean             |    NO    |    NO    |              |
| flag          | boolean             |    NO    |    NO    |              |
| rating        | integer             |   YES    |    NO    |              |
| averagerating | number              |   YES    |    NO    |              |
| playcount     | integer             |    NO    |    NO    |              |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/videos.json)

### video

This returns a single video

| Input    | Type   | Description                      | Optional |
|----------|--------|----------------------------------|---------:|
| 'filter' | string | UID of video, returns video JSON |       NO |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a single object.

| Field         | Type                | Nullable | Optional | Notes        |
|---------------|---------------------|:--------:|:--------:|--------------|
| id            | string              |    NO    |    NO    |              |
| title         | string              |   YES    |    NO    |              |
| mime          | string              |   YES    |    NO    |              |
| resolution    | string              |   YES    |    NO    |              |
| size          | integer             |    NO    |    NO    |              |
| genre         | array&lt;object&gt; |    NO    |    NO    | `{id, name}` |
| time          | integer             |    NO    |    NO    |              |
| url           | string              |    NO    |    NO    |              |
| art           | string              |   YES    |    NO    |              |
| has_art       | boolean             |    NO    |    NO    |              |
| flag          | boolean             |    NO    |    NO    |              |
| rating        | integer             |   YES    |    NO    |              |
| averagerating | number              |   YES    |    NO    |              |
| playcount     | integer             |    NO    |    NO    |              |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/video.json)

## Binary Data Methods

Binary data methods are used for returning raw data to the user such as a image or stream.

### download

Downloads a given media file. set format=raw to download the full file

**NOTE** search and playlist will only download a random object from the list

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `id` is deprecated and will be removed in **API9**.

| Input     | Type    | Description                                                                    | Optional |
|-----------|---------|--------------------------------------------------------------------------------|---------:|
| 'filter'  | string  | $object_id                                                                     |       NO |
| 'type'    | string  | `song`, `podcast_episode`, `search`, `playlist`                                |       NO |
| 'bitrate' | integer | max bitrate for transcoding in bytes (e.g 192000=192Kb)                        |      YES |
| 'format'  | string  | `mp3`, `ogg`, `raw`, etc (raw returns the original format)                     |      YES |
| 'stats'   | boolean | `0`, `1`, if false disable stat recording when playing the object (default: 1) |      YES |

* return file (HTTP 200 OK)
* throws (HTTP 400 Bad Request)
* throws (HTTP 404 Not Found)

### get_art

Get an art image.

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `id` is deprecated and will be removed in **API9**.

| Input    | Type   | Description                                                | Optional |
|----------|--------|------------------------------------------------------------|---------:|
| 'filter' | string | $object_id                                                 |       NO |
| 'type'   | string | `song`, `artist`, `album`, `playlist`, `search`, `podcast` |       NO |
| 'size'   | string | width x height ('640x480')                                 |      YES |

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

### random

Picks a random song, podcast episode or video from the whole library and redirects (302) to its stream url. **Ampache 8.0.0+**

Mirrors [stream](#stream)'s transcode parameters but takes no `filter`/`id`; only single-file media types are supported.
Picking a random item from a container (album, artist, playlist, search) is what the search/browse/playlist methods are for.

| Input     | Type    | Description                                                                    | Optional |
|-----------|---------|--------------------------------------------------------------------------------|---------:|
| 'type'    | string  | `song`, `podcast_episode`, `video` (default: song)                             |      YES |
| 'bitrate' | integer | max bitrate for transcoding in bytes (e.g 192000=192Kb) **song only**          |      YES |
| 'format'  | string  | `mp3`, `ogg`, `raw`, etc (raw returns the original format) **song only**       |      YES |
| 'offset'  | integer | time offset in seconds                                                         |      YES |
| 'stats'   | boolean | `0`, `1`, if false disable stat recording when playing the object (default: 1) |      YES |

* return file (HTTP 302 Found; redirects to the stream url)
* throws (HTTP 400 Bad Request)
* throws (HTTP 404 Not Found)

### stream

Streams a given media file. Takes the file id in parameter with optional max bit rate, file format, time offset, size and estimate content length option.

**NOTE** search and playlist will only stream a random object from the list

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `id` is deprecated and will be removed in **API9**.

| Input     | Type    | Description                                                                    | Optional |
|-----------|---------|--------------------------------------------------------------------------------|---------:|
| 'filter'  | string  | $object_id                                                                     |       NO |
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

### democratic

This is for controlling democratic play (Songs only)

* **Method Descriptions**
  * vote: +1 vote for the oid
  * devote: -1 vote for the oid
  * playlist: Return an array of song items with an additional \<vote>[VOTE COUNT]\</vote> element
  * play: Returns the URL for playing democratic play

| Input    | Type   | Description                          | Optional |
|----------|--------|--------------------------------------|---------:|
| 'oid'    | string | UID of Song object                   |       NO |
| 'method' | string | `vote`, `devote`, `playlist`, `play` |       NO |

* return object|array

<!-- GENERATED:RESPONSE:BEGIN -->
Depends on the `method` parameter: `play` returns the stream url, `vote`/`devote` return the applied method and its result, and `playlist` returns the current democratic song list.

**`DemocraticPlayResponse`**

Returned by `method=play`: the stream URL of the democratic playlist.

Returned by `method=play`: the stream URL of the democratic playlist.

| Field | Type   | Nullable | Optional | Notes |
|-------|--------|:--------:|:--------:|-------|
| url   | string |    NO    |    NO    |       |

**`DemocraticVoteResponse`**

Returned by `method=vote` and `method=devote`.

Returned by `method=vote` and `method=devote`.

| Field  | Type    | Nullable | Optional | Notes |
|--------|---------|:--------:|:--------:|-------|
| method | string  |    NO    |    NO    |       |
| result | boolean |    NO    |    NO    |       |

**`DemocraticSongsResponse`**

Returns a `song` list.

| Field | Type                                | Nullable | Optional | Notes                             |
|-------|-------------------------------------|:--------:|:--------:|-----------------------------------|
| song  | array&lt;`DemocraticSongObject`&gt; |    NO    |    NO    | see `DemocraticSongObject` fields |

Each `song` entry (`DemocraticSongObject`):

| Field         | Type                | Nullable | Optional | Notes                          |
|---------------|---------------------|:--------:|:--------:|--------------------------------|
| id            | string              |    NO    |    NO    |                                |
| title         | string              |   YES    |    NO    |                                |
| artist        | object              |    NO    |    NO    | `{id, name, prefix, basename}` |
| album         | object              |    NO    |    NO    | `{id, name, prefix, basename}` |
| genre         | array&lt;object&gt; |    NO    |    NO    | `{id, name}`                   |
| track         | integer             |    NO    |    NO    |                                |
| time          | integer             |    NO    |    NO    |                                |
| format        | string              |   YES    |    NO    |                                |
| bitrate       | integer             |   YES    |    NO    |                                |
| mime          | string              |   YES    |    NO    |                                |
| url           | string              |    NO    |    NO    |                                |
| size          | integer             |    NO    |    NO    |                                |
| art           | string              |   YES    |    NO    |                                |
| has_art       | boolean             |    NO    |    NO    |                                |
| rating        | integer             |   YES    |    NO    |                                |
| averagerating | number              |   YES    |    NO    |                                |
| playcount     | integer             |    NO    |    NO    |                                |
| vote          | integer             |    NO    |    NO    |                                |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/democratic%20\(play\).json)

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/democratic%20\(vote\).json)

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/democratic%20\(playlist\).json)

### localplay

This is for controlling localplay

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `oid` is deprecated and will be removed in **API9**.

| Input     | Type    | Description                                                                             | Optional |
|-----------|---------|-----------------------------------------------------------------------------------------|---------:|
| 'command' | string  | `next`, `prev`, `stop`, `play`, `pause`, `add`, `volume_up`                             |       NO |
|           |         | `volume_down`, `volume_mute`, `delete_all`, `skip`, `status`                            |          |
| 'filter'  | string  | $object_id                                                                              |      YES |
| 'type'    | string  | `song`, `video`, `podcast_episode`, `channel`, `broadcast`, `democratic`, `live_stream` |      YES |
| 'clear'   | boolean | `0`, `1` (Clear the current playlist before adding)                                     |      YES |

* return object

<!-- GENERATED:RESPONSE:BEGIN -->
The `status` command reports the player state instead of a boolean.

| Field     | Type   | Nullable | Optional | Notes       |
|-----------|--------|:--------:|:--------:|-------------|
| localplay | object |    NO    |    NO    | `{command}` |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/localplay.json)

[Example (status)](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/localplay%20\(status\).json)

### localplay_songs

Get the list of songs in your localplay instance

This method takes no additional parameters.

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
Returns a `localplay_songs` list.

| Field           | Type                               | Nullable | Optional | Notes                            |
|-----------------|------------------------------------|:--------:|:--------:|----------------------------------|
| localplay_songs | array&lt;`LocalplaySongObject`&gt; |    NO    |    NO    | see `LocalplaySongObject` fields |

Each `localplay_songs` entry (`LocalplaySongObject`):

| Field | Type    | Nullable | Optional | Notes |
|-------|---------|:--------:|:--------:|-------|
| id    | integer |    NO    |    NO    |       |
| raw   | string  |    NO    |    NO    |       |
| vlid  | integer |    NO    |   YES    |       |
| oid   | integer |    NO    |   YES    |       |
| name  | string  |   YES    |   YES    |       |
| link  | string  |   YES    |   YES    |       |
| track | integer |    NO    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws object

```JSON
"error": ""
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/localplay_songs.json)
