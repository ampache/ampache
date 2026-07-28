# API XML Methods

Let's go through come calls and examples that you can do for each XML method.

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

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a single object.

| Field   | Type   | Nullable | Optional | Notes |
|---------|--------|:--------:|:--------:|-------|
| success | string |    NO    |   YES    |       |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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

**NOTE** For privacy, send `password` in a form or JSON request body rather than the query string. Query-string support for `password` is deprecated and will be removed in **API9**.

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a single object.

| Field   | Type   | Nullable | Optional | Notes |
|---------|--------|:--------:|:--------:|-------|
| success | string |    NO    |   YES    |       |
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/system_update.xml)

### system_preferences

Get your server preferences

**ACCESS REQUIRED:** 100 (Admin)

* You can modify and update your preferences using the following methods
  * [preference_create](#preference_create)
  * [preference_delete](#preference_delete)
  * [preference_edit](#preference_edit)

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/system_preferences.xml)

### users

Get ids and usernames for your site

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `user` list.

| Field | Type                                     | Nullable | Optional | Notes                                  |
|-------|------------------------------------------|:--------:|:--------:|----------------------------------------|
| user  | array&lt;[UserSummaryObject](#users)&gt; |    NO    |    NO    | see [UserSummaryObject](#users) fields |

Each `user` entry ([UserSummaryObject](#users)):

| Field    | Type   | Nullable | Optional | Notes |
|----------|--------|:--------:|:--------:|-------|
| id       | string |    NO    |    NO    |       |
| username | string |   YES    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/users.xml)

### user_preferences

Get your user preferences

* You can modify and update your preferences using the following methods
  * [preference_create](#preference_create)
  * [preference_delete](#preference_delete)
  * [preference_edit](#preference_edit)

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `album` list.

| Field       | Type                               | Nullable | Optional | Notes                            |
|-------------|------------------------------------|:--------:|:--------:|----------------------------------|
| total_count | integer                            |    NO    |    NO    |                                  |
| md5         | string                             |    NO    |    NO    |                                  |
| album       | array&lt;[AlbumObject](#album)&gt; |    NO    |    NO    | see [AlbumObject](#album) fields |

Each `album` entry ([AlbumObject](#album)):

| Field         | Type                                           | Nullable | Optional | Notes                                        |
|---------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id            | string                                         |    NO    |    NO    |                                              |
| name          | string                                         |   YES    |    NO    |                                              |
| prefix        | string                                         |   YES    |    NO    |                                              |
| basename      | string                                         |   YES    |    NO    |                                              |
| artist        | object                                         |   YES    |   YES    | `{id, name, prefix, basename}`               |
| artists       | array&lt;[NamedReference](#namedreference)&gt; |    NO    |   YES    | see [NamedReference](#namedreference) fields |
| songartists   | array&lt;[NamedReference](#namedreference)&gt; |    NO    |   YES    | see [NamedReference](#namedreference) fields |
| time          | integer                                        |    NO    |    NO    |                                              |
| year          | integer                                        |    NO    |    NO    |                                              |
| tracks        | array&lt;[SongObject](#song)&gt;               |    NO    |    NO    | see [SongObject](#song) fields               |
| songcount     | integer                                        |    NO    |    NO    |                                              |
| diskcount     | integer                                        |    NO    |    NO    |                                              |
| type          | string                                         |   YES    |    NO    |                                              |
| genre         | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| art           | string                                         |   YES    |    NO    |                                              |
| has_art       | boolean                                        |    NO    |    NO    |                                              |
| flag          | boolean                                        |    NO    |    NO    |                                              |
| rating        | integer                                        |   YES    |    NO    |                                              |
| averagerating | number                                         |   YES    |    NO    |                                              |
| mbid          | string                                         |   YES    |    NO    |                                              |
| mbid_group    | string                                         |   YES    |    NO    |                                              |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a single object.

| Field         | Type                                           | Nullable | Optional | Notes                                        |
|---------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id            | string                                         |    NO    |    NO    |                                              |
| name          | string                                         |   YES    |    NO    |                                              |
| prefix        | string                                         |   YES    |    NO    |                                              |
| basename      | string                                         |   YES    |    NO    |                                              |
| artist        | object                                         |   YES    |   YES    | `{id, name, prefix, basename}`               |
| artists       | array&lt;[NamedReference](#namedreference)&gt; |    NO    |   YES    | see [NamedReference](#namedreference) fields |
| songartists   | array&lt;[NamedReference](#namedreference)&gt; |    NO    |   YES    | see [NamedReference](#namedreference) fields |
| time          | integer                                        |    NO    |    NO    |                                              |
| year          | integer                                        |    NO    |    NO    |                                              |
| tracks        | array&lt;[SongObject](#song)&gt;               |    NO    |    NO    | see [SongObject](#song) fields               |
| songcount     | integer                                        |    NO    |    NO    |                                              |
| diskcount     | integer                                        |    NO    |    NO    |                                              |
| type          | string                                         |   YES    |    NO    |                                              |
| genre         | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| art           | string                                         |   YES    |    NO    |                                              |
| has_art       | boolean                                        |    NO    |    NO    |                                              |
| flag          | boolean                                        |    NO    |    NO    |                                              |
| rating        | integer                                        |   YES    |    NO    |                                              |
| averagerating | number                                         |   YES    |    NO    |                                              |
| mbid          | string                                         |   YES    |    NO    |                                              |
| mbid_group    | string                                         |   YES    |    NO    |                                              |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `song` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| song        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |

Each `song` entry ([SongObject](#song)):

| Field                 | Type                                           | Nullable | Optional | Notes                                        |
|-----------------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id                    | string                                         |    NO    |    NO    |                                              |
| title                 | string                                         |   YES    |    NO    |                                              |
| name                  | string                                         |   YES    |    NO    |                                              |
| artist                | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| artists               | array&lt;[NamedReference](#namedreference)&gt; |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| album                 | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| albumartist           | [NamedReference](#namedreference)              |    NO    |   YES    | see [NamedReference](#namedreference) fields |
| disk                  | integer                                        |    NO    |    NO    |                                              |
| disksubtitle          | string                                         |   YES    |    NO    |                                              |
| track                 | integer                                        |    NO    |    NO    |                                              |
| filename              | string                                         |   YES    |    NO    |                                              |
| genre                 | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| playlisttrack         | integer                                        |    NO    |    NO    |                                              |
| time                  | integer                                        |    NO    |    NO    |                                              |
| year                  | integer                                        |    NO    |    NO    |                                              |
| format                | string                                         |   YES    |    NO    |                                              |
| stream_format         | string                                         |   YES    |    NO    |                                              |
| bitrate               | integer                                        |   YES    |    NO    |                                              |
| stream_bitrate        | integer                                        |   YES    |    NO    |                                              |
| rate                  | integer                                        |    NO    |    NO    |                                              |
| mode                  | string                                         |   YES    |    NO    |                                              |
| mime                  | string                                         |   YES    |    NO    |                                              |
| stream_mime           | string                                         |   YES    |    NO    |                                              |
| url                   | string                                         |    NO    |    NO    |                                              |
| size                  | integer                                        |    NO    |    NO    |                                              |
| mbid                  | string                                         |   YES    |    NO    |                                              |
| art                   | string                                         |   YES    |    NO    |                                              |
| has_art               | boolean                                        |    NO    |    NO    |                                              |
| flag                  | boolean                                        |    NO    |    NO    |                                              |
| rating                | integer                                        |   YES    |    NO    |                                              |
| averagerating         | number                                         |   YES    |    NO    |                                              |
| playcount             | integer                                        |    NO    |    NO    |                                              |
| last_played           | string                                         |   YES    |    NO    |                                              |
| catalog               | string                                         |    NO    |    NO    |                                              |
| composer              | string                                         |   YES    |    NO    |                                              |
| channels              | integer                                        |   YES    |    NO    |                                              |
| comment               | string                                         |   YES    |    NO    |                                              |
| license               | string                                         |   YES    |    NO    |                                              |
| publisher             | string                                         |   YES    |    NO    |                                              |
| language              | string                                         |   YES    |    NO    |                                              |
| lyrics                | string                                         |   YES    |    NO    |                                              |
| replaygain_album_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_album_peak | number                                         |   YES    |    NO    |                                              |
| replaygain_track_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_track_peak | number                                         |   YES    |    NO    |                                              |
| r128_album_gain       | number                                         |   YES    |    NO    |                                              |
| r128_track_gain       | number                                         |   YES    |    NO    |                                              |
| metadata              | object&lt;string, string&gt;                   |    NO    |   YES    |                                              |
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/album_songs.xml)

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

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `album_disk` list.

| Field       | Type                                        | Nullable | Optional | Notes                                     |
|-------------|---------------------------------------------|:--------:|:--------:|-------------------------------------------|
| total_count | integer                                     |    NO    |    NO    |                                           |
| md5         | string                                      |    NO    |    NO    |                                           |
| album_disk  | array&lt;[AlbumDiskObject](#album_disk)&gt; |    NO    |    NO    | see [AlbumDiskObject](#album_disk) fields |

Each `album_disk` entry ([AlbumDiskObject](#album_disk)):

| Field         | Type                                           | Nullable | Optional | Notes                                        |
|---------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id            | string                                         |    NO    |    NO    |                                              |
| name          | string                                         |    NO    |    NO    |                                              |
| prefix        | string                                         |   YES    |    NO    |                                              |
| basename      | string                                         |   YES    |    NO    |                                              |
| album         | object                                         |    NO    |    NO    | `{id, name, prefix, basename}`               |
| artist        | object                                         |    NO    |   YES    | `{id, name, prefix, basename}`               |
| artists       | array&lt;object&gt;                            |    NO    |   YES    | `{id, name, prefix, basename}`               |
| songartists   | array&lt;object&gt;                            |    NO    |   YES    | `{id, name, prefix, basename}`               |
| disk          | integer                                        |    NO    |    NO    |                                              |
| disksubtitle  | string                                         |   YES    |    NO    |                                              |
| time          | integer                                        |    NO    |    NO    |                                              |
| year          | integer                                        |    NO    |    NO    |                                              |
| tracks        | array&lt;[SongObject](#song)&gt;               |    NO    |    NO    | see [SongObject](#song) fields               |
| songcount     | integer                                        |    NO    |    NO    |                                              |
| type          | string                                         |   YES    |    NO    |                                              |
| genre         | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| art           | string                                         |   YES    |    NO    |                                              |
| has_art       | boolean                                        |    NO    |    NO    |                                              |
| flag          | boolean                                        |    NO    |    NO    |                                              |
| rating        | integer                                        |   YES    |    NO    |                                              |
| averagerating | number                                         |   YES    |    NO    |                                              |
| mbid          | string                                         |   YES    |    NO    |                                              |
| mbid_group    | string                                         |   YES    |    NO    |                                              |
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

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a single object.

| Field         | Type                                           | Nullable | Optional | Notes                                        |
|---------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id            | string                                         |    NO    |    NO    |                                              |
| name          | string                                         |    NO    |    NO    |                                              |
| prefix        | string                                         |   YES    |    NO    |                                              |
| basename      | string                                         |   YES    |    NO    |                                              |
| album         | object                                         |    NO    |    NO    | `{id, name, prefix, basename}`               |
| artist        | object                                         |    NO    |   YES    | `{id, name, prefix, basename}`               |
| artists       | array&lt;object&gt;                            |    NO    |   YES    | `{id, name, prefix, basename}`               |
| songartists   | array&lt;object&gt;                            |    NO    |   YES    | `{id, name, prefix, basename}`               |
| disk          | integer                                        |    NO    |    NO    |                                              |
| disksubtitle  | string                                         |   YES    |    NO    |                                              |
| time          | integer                                        |    NO    |    NO    |                                              |
| year          | integer                                        |    NO    |    NO    |                                              |
| tracks        | array&lt;[SongObject](#song)&gt;               |    NO    |    NO    | see [SongObject](#song) fields               |
| songcount     | integer                                        |    NO    |    NO    |                                              |
| type          | string                                         |   YES    |    NO    |                                              |
| genre         | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| art           | string                                         |   YES    |    NO    |                                              |
| has_art       | boolean                                        |    NO    |    NO    |                                              |
| flag          | boolean                                        |    NO    |    NO    |                                              |
| rating        | integer                                        |   YES    |    NO    |                                              |
| averagerating | number                                         |   YES    |    NO    |                                              |
| mbid          | string                                         |   YES    |    NO    |                                              |
| mbid_group    | string                                         |   YES    |    NO    |                                              |
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

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `song` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| song        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |

Each `song` entry ([SongObject](#song)):

| Field                 | Type                                           | Nullable | Optional | Notes                                        |
|-----------------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id                    | string                                         |    NO    |    NO    |                                              |
| title                 | string                                         |   YES    |    NO    |                                              |
| name                  | string                                         |   YES    |    NO    |                                              |
| artist                | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| artists               | array&lt;[NamedReference](#namedreference)&gt; |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| album                 | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| albumartist           | [NamedReference](#namedreference)              |    NO    |   YES    | see [NamedReference](#namedreference) fields |
| disk                  | integer                                        |    NO    |    NO    |                                              |
| disksubtitle          | string                                         |   YES    |    NO    |                                              |
| track                 | integer                                        |    NO    |    NO    |                                              |
| filename              | string                                         |   YES    |    NO    |                                              |
| genre                 | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| playlisttrack         | integer                                        |    NO    |    NO    |                                              |
| time                  | integer                                        |    NO    |    NO    |                                              |
| year                  | integer                                        |    NO    |    NO    |                                              |
| format                | string                                         |   YES    |    NO    |                                              |
| stream_format         | string                                         |   YES    |    NO    |                                              |
| bitrate               | integer                                        |   YES    |    NO    |                                              |
| stream_bitrate        | integer                                        |   YES    |    NO    |                                              |
| rate                  | integer                                        |    NO    |    NO    |                                              |
| mode                  | string                                         |   YES    |    NO    |                                              |
| mime                  | string                                         |   YES    |    NO    |                                              |
| stream_mime           | string                                         |   YES    |    NO    |                                              |
| url                   | string                                         |    NO    |    NO    |                                              |
| size                  | integer                                        |    NO    |    NO    |                                              |
| mbid                  | string                                         |   YES    |    NO    |                                              |
| art                   | string                                         |   YES    |    NO    |                                              |
| has_art               | boolean                                        |    NO    |    NO    |                                              |
| flag                  | boolean                                        |    NO    |    NO    |                                              |
| rating                | integer                                        |   YES    |    NO    |                                              |
| averagerating         | number                                         |   YES    |    NO    |                                              |
| playcount             | integer                                        |    NO    |    NO    |                                              |
| last_played           | string                                         |   YES    |    NO    |                                              |
| catalog               | string                                         |    NO    |    NO    |                                              |
| composer              | string                                         |   YES    |    NO    |                                              |
| channels              | integer                                        |   YES    |    NO    |                                              |
| comment               | string                                         |   YES    |    NO    |                                              |
| license               | string                                         |   YES    |    NO    |                                              |
| publisher             | string                                         |   YES    |    NO    |                                              |
| language              | string                                         |   YES    |    NO    |                                              |
| lyrics                | string                                         |   YES    |    NO    |                                              |
| replaygain_album_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_album_peak | number                                         |   YES    |    NO    |                                              |
| replaygain_track_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_track_peak | number                                         |   YES    |    NO    |                                              |
| r128_album_gain       | number                                         |   YES    |    NO    |                                              |
| r128_track_gain       | number                                         |   YES    |    NO    |                                              |
| metadata              | object&lt;string, string&gt;                   |    NO    |   YES    |                                              |
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

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `artist` list.

| Field       | Type                                 | Nullable | Optional | Notes                              |
|-------------|--------------------------------------|:--------:|:--------:|------------------------------------|
| total_count | integer                              |    NO    |    NO    |                                    |
| md5         | string                               |    NO    |    NO    |                                    |
| artist      | array&lt;[ArtistObject](#artist)&gt; |    NO    |    NO    | see [ArtistObject](#artist) fields |

Each `artist` entry ([ArtistObject](#artist)):

| Field         | Type                                           | Nullable | Optional | Notes                                        |
|---------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id            | string                                         |    NO    |    NO    |                                              |
| name          | string                                         |   YES    |    NO    |                                              |
| prefix        | string                                         |   YES    |    NO    |                                              |
| basename      | string                                         |   YES    |    NO    |                                              |
| albums        | array&lt;[AlbumObject](#album)&gt;             |    NO    |    NO    | see [AlbumObject](#album) fields             |
| albumcount    | integer                                        |    NO    |    NO    |                                              |
| songs         | array&lt;[SongObject](#song)&gt;               |    NO    |    NO    | see [SongObject](#song) fields               |
| songcount     | integer                                        |    NO    |    NO    |                                              |
| genre         | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| art           | string                                         |   YES    |    NO    |                                              |
| has_art       | boolean                                        |    NO    |    NO    |                                              |
| flag          | boolean                                        |    NO    |    NO    |                                              |
| rating        | integer                                        |   YES    |    NO    |                                              |
| averagerating | number                                         |   YES    |    NO    |                                              |
| mbid          | string                                         |   YES    |    NO    |                                              |
| summary       | string                                         |   YES    |    NO    |                                              |
| time          | integer                                        |    NO    |    NO    |                                              |
| yearformed    | integer                                        |    NO    |    NO    |                                              |
| placeformed   | string                                         |   YES    |    NO    |                                              |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a single object.

| Field         | Type                                           | Nullable | Optional | Notes                                        |
|---------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id            | string                                         |    NO    |    NO    |                                              |
| name          | string                                         |   YES    |    NO    |                                              |
| prefix        | string                                         |   YES    |    NO    |                                              |
| basename      | string                                         |   YES    |    NO    |                                              |
| albums        | array&lt;[AlbumObject](#album)&gt;             |    NO    |    NO    | see [AlbumObject](#album) fields             |
| albumcount    | integer                                        |    NO    |    NO    |                                              |
| songs         | array&lt;[SongObject](#song)&gt;               |    NO    |    NO    | see [SongObject](#song) fields               |
| songcount     | integer                                        |    NO    |    NO    |                                              |
| genre         | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| art           | string                                         |   YES    |    NO    |                                              |
| has_art       | boolean                                        |    NO    |    NO    |                                              |
| flag          | boolean                                        |    NO    |    NO    |                                              |
| rating        | integer                                        |   YES    |    NO    |                                              |
| averagerating | number                                         |   YES    |    NO    |                                              |
| mbid          | string                                         |   YES    |    NO    |                                              |
| summary       | string                                         |   YES    |    NO    |                                              |
| time          | integer                                        |    NO    |    NO    |                                              |
| yearformed    | integer                                        |    NO    |    NO    |                                              |
| placeformed   | string                                         |   YES    |    NO    |                                              |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `album` list.

| Field       | Type                               | Nullable | Optional | Notes                            |
|-------------|------------------------------------|:--------:|:--------:|----------------------------------|
| total_count | integer                            |    NO    |    NO    |                                  |
| md5         | string                             |    NO    |    NO    |                                  |
| album       | array&lt;[AlbumObject](#album)&gt; |    NO    |    NO    | see [AlbumObject](#album) fields |

Each `album` entry ([AlbumObject](#album)):

| Field         | Type                                           | Nullable | Optional | Notes                                        |
|---------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id            | string                                         |    NO    |    NO    |                                              |
| name          | string                                         |   YES    |    NO    |                                              |
| prefix        | string                                         |   YES    |    NO    |                                              |
| basename      | string                                         |   YES    |    NO    |                                              |
| artist        | object                                         |   YES    |   YES    | `{id, name, prefix, basename}`               |
| artists       | array&lt;[NamedReference](#namedreference)&gt; |    NO    |   YES    | see [NamedReference](#namedreference) fields |
| songartists   | array&lt;[NamedReference](#namedreference)&gt; |    NO    |   YES    | see [NamedReference](#namedreference) fields |
| time          | integer                                        |    NO    |    NO    |                                              |
| year          | integer                                        |    NO    |    NO    |                                              |
| tracks        | array&lt;[SongObject](#song)&gt;               |    NO    |    NO    | see [SongObject](#song) fields               |
| songcount     | integer                                        |    NO    |    NO    |                                              |
| diskcount     | integer                                        |    NO    |    NO    |                                              |
| type          | string                                         |   YES    |    NO    |                                              |
| genre         | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| art           | string                                         |   YES    |    NO    |                                              |
| has_art       | boolean                                        |    NO    |    NO    |                                              |
| flag          | boolean                                        |    NO    |    NO    |                                              |
| rating        | integer                                        |   YES    |    NO    |                                              |
| averagerating | number                                         |   YES    |    NO    |                                              |
| mbid          | string                                         |   YES    |    NO    |                                              |
| mbid_group    | string                                         |   YES    |    NO    |                                              |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `song` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| song        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |

Each `song` entry ([SongObject](#song)):

| Field                 | Type                                           | Nullable | Optional | Notes                                        |
|-----------------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id                    | string                                         |    NO    |    NO    |                                              |
| title                 | string                                         |   YES    |    NO    |                                              |
| name                  | string                                         |   YES    |    NO    |                                              |
| artist                | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| artists               | array&lt;[NamedReference](#namedreference)&gt; |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| album                 | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| albumartist           | [NamedReference](#namedreference)              |    NO    |   YES    | see [NamedReference](#namedreference) fields |
| disk                  | integer                                        |    NO    |    NO    |                                              |
| disksubtitle          | string                                         |   YES    |    NO    |                                              |
| track                 | integer                                        |    NO    |    NO    |                                              |
| filename              | string                                         |   YES    |    NO    |                                              |
| genre                 | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| playlisttrack         | integer                                        |    NO    |    NO    |                                              |
| time                  | integer                                        |    NO    |    NO    |                                              |
| year                  | integer                                        |    NO    |    NO    |                                              |
| format                | string                                         |   YES    |    NO    |                                              |
| stream_format         | string                                         |   YES    |    NO    |                                              |
| bitrate               | integer                                        |   YES    |    NO    |                                              |
| stream_bitrate        | integer                                        |   YES    |    NO    |                                              |
| rate                  | integer                                        |    NO    |    NO    |                                              |
| mode                  | string                                         |   YES    |    NO    |                                              |
| mime                  | string                                         |   YES    |    NO    |                                              |
| stream_mime           | string                                         |   YES    |    NO    |                                              |
| url                   | string                                         |    NO    |    NO    |                                              |
| size                  | integer                                        |    NO    |    NO    |                                              |
| mbid                  | string                                         |   YES    |    NO    |                                              |
| art                   | string                                         |   YES    |    NO    |                                              |
| has_art               | boolean                                        |    NO    |    NO    |                                              |
| flag                  | boolean                                        |    NO    |    NO    |                                              |
| rating                | integer                                        |   YES    |    NO    |                                              |
| averagerating         | number                                         |   YES    |    NO    |                                              |
| playcount             | integer                                        |    NO    |    NO    |                                              |
| last_played           | string                                         |   YES    |    NO    |                                              |
| catalog               | string                                         |    NO    |    NO    |                                              |
| composer              | string                                         |   YES    |    NO    |                                              |
| channels              | integer                                        |   YES    |    NO    |                                              |
| comment               | string                                         |   YES    |    NO    |                                              |
| license               | string                                         |   YES    |    NO    |                                              |
| publisher             | string                                         |   YES    |    NO    |                                              |
| language              | string                                         |   YES    |    NO    |                                              |
| lyrics                | string                                         |   YES    |    NO    |                                              |
| replaygain_album_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_album_peak | number                                         |   YES    |    NO    |                                              |
| replaygain_track_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_track_peak | number                                         |   YES    |    NO    |                                              |
| r128_album_gain       | number                                         |   YES    |    NO    |                                              |
| r128_track_gain       | number                                         |   YES    |    NO    |                                              |
| metadata              | object&lt;string, string&gt;                   |    NO    |   YES    |                                              |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/bookmark_create.xml)

### bookmark_delete

Delete an existing bookmark. (if it exists)

| Input    | Type   | Description                                                       | Optional |
|----------|--------|-------------------------------------------------------------------|---------:|
| 'filter' | string | $object_id to delete                                              |       NO |
| 'type'   | string | `bookmark`, `song`, `video`, `podcast_episode`, default: bookmark |       NO |
| 'client' | string | Agent string.                                                     |      YES |

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `browse` list.

| Field       | Type                                 | Nullable | Optional | Notes                              |
|-------------|--------------------------------------|:--------:|:--------:|------------------------------------|
| total_count | integer                              |    NO    |    NO    |                                    |
| md5         | string                               |    NO    |    NO    |                                    |
| catalog_id  | string                               |    NO    |    NO    |                                    |
| parent_id   | string                               |    NO    |    NO    |                                    |
| parent_type | string                               |    NO    |    NO    |                                    |
| child_type  | string                               |    NO    |    NO    |                                    |
| browse      | array&lt;[BrowseObject](#browse)&gt; |    NO    |    NO    | see [BrowseObject](#browse) fields |

Each `browse` entry ([BrowseObject](#browse)):

| Field    | Type   | Nullable | Optional | Notes |
|----------|--------|:--------:|:--------:|-------|
| id       | string |    NO    |    NO    |       |
| name     | string |    NO    |    NO    |       |
| prefix   | string |   YES    |    NO    |       |
| basename | string |    NO    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/catalogs.xml)

### catalog

Return catalog by UID

| Input    | Type   | Description    | Optional |
|----------|--------|----------------|---------:|
| 'filter' | string | UID of Catalog |       NO |

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/catalog.xml)

### catalog_action

Kick off a catalog update or clean for the selected catalog

**ACCESS REQUIRED:** 75 (Catalog Manager)

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `catalog` is deprecated and will be removed in **API9**.

| Input    | Type   | Description                       | Optional |
|----------|--------|-----------------------------------|---------:|
| 'task'   | string | `add_to_catalog`, `clean_catalog` |       NO |
| 'filter' | string | $catalog_id                       |       NO |

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

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `catalog` is deprecated and will be removed in **API9**.

| Input    | Type   | Description                                                             | Optional |
|----------|--------|-------------------------------------------------------------------------|---------:|
| 'file'   | string | FULL path to local file                                                 |       NO |
| 'task'   | string | `add`, `clean`, `verify`, `remove` (can include comma-separated values) |       NO |
| 'filter' | string | $catalog_id                                                             |       NO |

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

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `catalog` is deprecated and will be removed in **API9**.

| Input    | Type   | Description                                                             | Optional |
|----------|--------|-------------------------------------------------------------------------|---------:|
| 'folder' | string | FULL path to local folder                                               |       NO |
| 'task'   | string | `add`, `clean`, `verify`, `remove` (can include comma-separated values) |       NO |
| 'filter' | string | $catalog_id                                                             |       NO |

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a single object.

| Field   | Type   | Nullable | Optional | Notes |
|---------|--------|:--------:|:--------:|-------|
| success | string |    NO    |   YES    |       |
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/catalog_folder.xml)

### collections

A collection is a hand-curated list of objects of any type: the static counterpart to a search, and the non-media counterpart to a playlist. Playing one expands its members, so an album contributes its songs and anything that cannot be streamed is skipped.

This returns every collection you own, plus every public collection on the server.

| Input    | Type    | Description                                        | Optional |
|----------|---------|----------------------------------------------------|---------:|
| 'type'   | string  | Only return collections pinned to this object_type |      YES |
| 'offset' | integer | Return results starting from this index position   |      YES |
| 'limit'  | integer | Maximum number of results to return                |      YES |

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `collection` list.

| Field      | Type                                          | Nullable | Optional | Notes                                       |
|------------|-----------------------------------------------|:--------:|:--------:|---------------------------------------------|
| collection | array&lt;[CollectionObject](#collections)&gt; |    NO    |    NO    | see [CollectionObject](#collections) fields |

Each `collection` entry ([CollectionObject](#collections)):

| Field       | Type    | Nullable | Optional | Notes |
|-------------|---------|:--------:|:--------:|-------|
| id          | string  |    NO    |    NO    |       |
| name        | string  |    NO    |    NO    |       |
| owner       | string  |   YES    |    NO    |       |
| type        | string  |   YES    |    NO    |       |
| object_type | string  |   YES    |    NO    |       |
| items       | integer |    NO    |    NO    |       |
| has_art     | boolean |    NO    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

### collection

Return a collection by UID, without its contents.

| Input    | Type   | Description       | Optional |
|----------|--------|-------------------|---------:|
| 'filter' | string | UID of Collection |       NO |

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `collection` list.

| Field      | Type                                          | Nullable | Optional | Notes                                       |
|------------|-----------------------------------------------|:--------:|:--------:|---------------------------------------------|
| collection | array&lt;[CollectionObject](#collections)&gt; |    NO    |    NO    | see [CollectionObject](#collections) fields |

Each `collection` entry ([CollectionObject](#collections)):

| Field       | Type    | Nullable | Optional | Notes |
|-------------|---------|:--------:|:--------:|-------|
| id          | string  |    NO    |    NO    |       |
| name        | string  |    NO    |    NO    |       |
| owner       | string  |   YES    |    NO    |       |
| type        | string  |   YES    |    NO    |       |
| object_type | string  |   YES    |    NO    |       |
| items       | integer |    NO    |    NO    |       |
| has_art     | boolean |    NO    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

### collection_items

A collection's members, grouped by object_type.

A collection is heterogeneous by design, so a flat list would leave you guessing what each id is. The members arrive under `contents` as one group per type ([CollectionGroupObject](#collectiongroupobject)), each built by the same builder that type's own methods use. The scalar `items` stays the member count.

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'filter' | string  | UID of Collection                                |       NO |
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a single object.

| Field      | Type   | Nullable | Optional | Notes                                                            |
|------------|--------|:--------:|:--------:|------------------------------------------------------------------|
| collection | object |    NO    |    NO    | `{id, name, owner, type, object_type, items, has_art, contents}` |
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

### collection_create

Create a new, empty collection.

Leave `object_type` out for a mixed collection, or set it to pin the collection to a single type so anything else is refused when it is added.

| Input         | Type   | Description                                | Optional |
|---------------|--------|--------------------------------------------|---------:|
| 'name'        | string | Collection name                            |       NO |
| 'type'        | string | `public`, `private` (Default: `private`)   |      YES |
| 'object_type' | string | Pin the collection to a single object_type |      YES |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

### collection_edit

Change a collection's name, visibility, pinned type or collaborators.

Only the values you send are changed. Send an empty `object_type` to un-pin a collection back to mixed; pinning is refused while the collection still holds a different type.

| Input         | Type   | Description                                                     | Optional |
|---------------|--------|-----------------------------------------------------------------|---------:|
| 'filter'      | string | UID of Collection                                               |       NO |
| 'name'        | string | Collection name                                                 |      YES |
| 'type'        | string | `public`, `private`                                             |      YES |
| 'object_type' | string | Pinned object_type, or an empty string to un-pin                |      YES |
| 'collaborate' | string | Comma separated list of user ids allowed to curate the contents |      YES |

* return array

<!-- GENERATED:RESPONSE:BEGIN -->
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

### collection_delete

Delete a collection and its membership rows. The objects it referenced are untouched.

**ACCESS REQUIRED:** collection owner or admin. A collaborator may curate the contents but not destroy the list.

| Input    | Type   | Description       | Optional |
|----------|--------|-------------------|---------:|
| 'filter' | string | UID of Collection |       NO |

* return object

```XML
<root><success></root>
```

* throws

```XML
<root><error></root>
```

### collection_add

Add one object to a collection.

Adding the same object twice is a no-op rather than a duplicate. A pinned collection refuses anything but its own type, and an object that does not exist is refused rather than stored as a dangling id.

| Input         | Type   | Description               | Optional |
|---------------|--------|---------------------------|---------:|
| 'filter'      | string | UID of Collection         |       NO |
| 'id'          | string | UID of the object to add  |       NO |
| 'object_type' | string | type of the object to add |       NO |

* return object

```XML
<root><success></root>
```

* throws

```XML
<root><error></root>
```

### collection_remove

Remove one object from a collection. The object itself is untouched, and removing something that was never a member is not an error.

| Input         | Type   | Description                  | Optional |
|---------------|--------|------------------------------|---------:|
| 'filter'      | string | UID of Collection            |       NO |
| 'id'          | string | UID of the object to remove  |       NO |
| 'object_type' | string | type of the object to remove |       NO |

* return object

```XML
<root><success></root>
```

* throws

```XML
<root><error></root>
```


### deleted_podcast_episodes

This returns the episodes for a podcast that have been deleted

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `deleted_podcast_episode` list.

| Field                   | Type                                                                  | Nullable | Optional | Notes                                                               |
|-------------------------|-----------------------------------------------------------------------|:--------:|:--------:|---------------------------------------------------------------------|
| total_count             | integer                                                               |    NO    |    NO    |                                                                     |
| md5                     | string                                                                |    NO    |    NO    |                                                                     |
| deleted_podcast_episode | array&lt;[DeletedPodcastEpisodeObject](#deleted_podcast_episodes)&gt; |    NO    |    NO    | see [DeletedPodcastEpisodeObject](#deleted_podcast_episodes) fields |

Each `deleted_podcast_episode` entry ([DeletedPodcastEpisodeObject](#deleted_podcast_episodes)):

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `deleted_song` list.

| Field        | Type                                             | Nullable | Optional | Notes                                          |
|--------------|--------------------------------------------------|:--------:|:--------:|------------------------------------------------|
| total_count  | integer                                          |    NO    |    NO    |                                                |
| md5          | string                                           |    NO    |    NO    |                                                |
| deleted_song | array&lt;[DeletedSongObject](#deleted_songs)&gt; |    NO    |    NO    | see [DeletedSongObject](#deleted_songs) fields |

Each `deleted_song` entry ([DeletedSongObject](#deleted_songs)):

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `deleted_video` list.

| Field         | Type                                               | Nullable | Optional | Notes                                            |
|---------------|----------------------------------------------------|:--------:|:--------:|--------------------------------------------------|
| total_count   | integer                                            |    NO    |    NO    |                                                  |
| md5           | string                                             |    NO    |    NO    |                                                  |
| deleted_video | array&lt;[DeletedVideoObject](#deleted_videos)&gt; |    NO    |    NO    | see [DeletedVideoObject](#deleted_videos) fields |

Each `deleted_video` entry ([DeletedVideoObject](#deleted_videos)):

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

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/deleted_videos.xml)

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
| 'filter' | string  | Alias of `id` (Ampache 7.9.0+)                        |      YES |
| 'flag'   | boolean | `0`, `1`                                              |       NO |

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

### folders

Return children of a parent object in a folder traversal style **Ampache 8.0.0+**

| Input    | Type       | Description                                                                                        | Optional |
|----------|------------|----------------------------------------------------------------------------------------------------|---------:|
| 'filter' | string     | Path name or folder UID filter (Default: '/', the root folder; -1 is also the root)                |      YES |
| 'exact'  | boolean    | `0`, `1` (if true filter is exact rather than fuzzy; default: 1)                                   |      YES |
| 'add'    | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'add' date newer than the specified date    |      YES |
| 'update' | set_filter | ISO 8601 Date Format (2020-09-16) Find objects with an 'update' time newer than the specified date |      YES |
| 'offset' | integer    | Return results starting from this index position                                                   |      YES |
| 'limit'  | integer    | Maximum number of results to return                                                                |      YES |
| 'cond'   | string     | Apply additional filters to the browse using `;` separated comma string pairs                      |      YES |
|          |            | (e.g. 'filter1,value1;filter2,value2')                                                             |          |
| 'sort'   | string     | Sort name or comma-separated key pair. (e.g. 'name,order')                                         |      YES |
|          |            | Default order 'ASC' (e.g. 'name,ASC' == 'name')                                                    |          |

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a single object.

| Field       | Type                                  | Nullable | Optional | Notes                                            |
|-------------|---------------------------------------|:--------:|:--------:|--------------------------------------------------|
| total_count | integer                               |    NO    |    NO    |                                                  |
| md5         | string                                |    NO    |    NO    |                                                  |
| folder      | [FolderBrowseNode](#folderbrowsenode) |    NO    |    NO    | see [FolderBrowseNode](#folderbrowsenode) fields |
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `user` list.

| Field | Type                                     | Nullable | Optional | Notes                                  |
|-------|------------------------------------------|:--------:|:--------:|----------------------------------------|
| user  | array&lt;[UserSummaryObject](#users)&gt; |    NO    |    NO    | see [UserSummaryObject](#users) fields |

Each `user` entry ([UserSummaryObject](#users)):

| Field    | Type   | Nullable | Optional | Notes |
|----------|--------|:--------:|:--------:|-------|
| id       | string |    NO    |    NO    |       |
| username | string |   YES    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `user` list.

| Field | Type                                     | Nullable | Optional | Notes                                  |
|-------|------------------------------------------|:--------:|:--------:|----------------------------------------|
| user  | array&lt;[UserSummaryObject](#users)&gt; |    NO    |    NO    | see [UserSummaryObject](#users) fields |

Each `user` entry ([UserSummaryObject](#users)):

| Field    | Type   | Nullable | Optional | Notes |
|----------|--------|:--------:|:--------:|-------|
| id       | string |    NO    |    NO    |       |
| username | string |   YES    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `activity` list.

| Field    | Type                                             | Nullable | Optional | Notes                                          |
|----------|--------------------------------------------------|:--------:|:--------:|------------------------------------------------|
| activity | array&lt;[ActivityObject](#friends_timeline)&gt; |    NO    |    NO    | see [ActivityObject](#friends_timeline) fields |

Each `activity` entry ([ActivityObject](#friends_timeline)):

| Field       | Type                        | Nullable | Optional | Notes                                  |
|-------------|-----------------------------|:--------:|:--------:|----------------------------------------|
| id          | string                      |    NO    |    NO    |                                        |
| date        | integer                     |    NO    |    NO    |                                        |
| object_type | string                      |   YES    |    NO    |                                        |
| object_id   | string                      |    NO    |    NO    |                                        |
| action      | string                      |    NO    |    NO    |                                        |
| user        | [UserSummaryObject](#users) |    NO    |    NO    | see [UserSummaryObject](#users) fields |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `genre` list.

| Field       | Type                               | Nullable | Optional | Notes                            |
|-------------|------------------------------------|:--------:|:--------:|----------------------------------|
| total_count | integer                            |    NO    |    NO    |                                  |
| md5         | string                             |    NO    |    NO    |                                  |
| genre       | array&lt;[GenreObject](#genre)&gt; |    NO    |    NO    | see [GenreObject](#genre) fields |

Each `genre` entry ([GenreObject](#genre)):

| Field        | Type                                           | Nullable | Optional | Notes                                        |
|--------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id           | string                                         |    NO    |    NO    |                                              |
| name         | string                                         |   YES    |    NO    |                                              |
| albums       | integer                                        |    NO    |    NO    |                                              |
| artists      | integer                                        |    NO    |    NO    |                                              |
| songs        | integer                                        |    NO    |    NO    |                                              |
| videos       | integer                                        |    NO    |    NO    |                                              |
| playlists    | integer                                        |    NO    |    NO    |                                              |
| live_streams | integer                                        |    NO    |    NO    |                                              |
| is_hidden    | boolean                                        |    NO    |    NO    |                                              |
| merge        | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a single object.

| Field        | Type                                           | Nullable | Optional | Notes                                        |
|--------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id           | string                                         |    NO    |    NO    |                                              |
| name         | string                                         |   YES    |    NO    |                                              |
| albums       | integer                                        |    NO    |    NO    |                                              |
| artists      | integer                                        |    NO    |    NO    |                                              |
| songs        | integer                                        |    NO    |    NO    |                                              |
| videos       | integer                                        |    NO    |    NO    |                                              |
| playlists    | integer                                        |    NO    |    NO    |                                              |
| live_streams | integer                                        |    NO    |    NO    |                                              |
| is_hidden    | boolean                                        |    NO    |    NO    |                                              |
| merge        | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `album` list.

| Field       | Type                               | Nullable | Optional | Notes                            |
|-------------|------------------------------------|:--------:|:--------:|----------------------------------|
| total_count | integer                            |    NO    |    NO    |                                  |
| md5         | string                             |    NO    |    NO    |                                  |
| album       | array&lt;[AlbumObject](#album)&gt; |    NO    |    NO    | see [AlbumObject](#album) fields |

Each `album` entry ([AlbumObject](#album)):

| Field         | Type                                           | Nullable | Optional | Notes                                        |
|---------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id            | string                                         |    NO    |    NO    |                                              |
| name          | string                                         |   YES    |    NO    |                                              |
| prefix        | string                                         |   YES    |    NO    |                                              |
| basename      | string                                         |   YES    |    NO    |                                              |
| artist        | object                                         |   YES    |   YES    | `{id, name, prefix, basename}`               |
| artists       | array&lt;[NamedReference](#namedreference)&gt; |    NO    |   YES    | see [NamedReference](#namedreference) fields |
| songartists   | array&lt;[NamedReference](#namedreference)&gt; |    NO    |   YES    | see [NamedReference](#namedreference) fields |
| time          | integer                                        |    NO    |    NO    |                                              |
| year          | integer                                        |    NO    |    NO    |                                              |
| tracks        | array&lt;[SongObject](#song)&gt;               |    NO    |    NO    | see [SongObject](#song) fields               |
| songcount     | integer                                        |    NO    |    NO    |                                              |
| diskcount     | integer                                        |    NO    |    NO    |                                              |
| type          | string                                         |   YES    |    NO    |                                              |
| genre         | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| art           | string                                         |   YES    |    NO    |                                              |
| has_art       | boolean                                        |    NO    |    NO    |                                              |
| flag          | boolean                                        |    NO    |    NO    |                                              |
| rating        | integer                                        |   YES    |    NO    |                                              |
| averagerating | number                                         |   YES    |    NO    |                                              |
| mbid          | string                                         |   YES    |    NO    |                                              |
| mbid_group    | string                                         |   YES    |    NO    |                                              |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `artist` list.

| Field       | Type                                 | Nullable | Optional | Notes                              |
|-------------|--------------------------------------|:--------:|:--------:|------------------------------------|
| total_count | integer                              |    NO    |    NO    |                                    |
| md5         | string                               |    NO    |    NO    |                                    |
| artist      | array&lt;[ArtistObject](#artist)&gt; |    NO    |    NO    | see [ArtistObject](#artist) fields |

Each `artist` entry ([ArtistObject](#artist)):

| Field         | Type                                           | Nullable | Optional | Notes                                        |
|---------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id            | string                                         |    NO    |    NO    |                                              |
| name          | string                                         |   YES    |    NO    |                                              |
| prefix        | string                                         |   YES    |    NO    |                                              |
| basename      | string                                         |   YES    |    NO    |                                              |
| albums        | array&lt;[AlbumObject](#album)&gt;             |    NO    |    NO    | see [AlbumObject](#album) fields             |
| albumcount    | integer                                        |    NO    |    NO    |                                              |
| songs         | array&lt;[SongObject](#song)&gt;               |    NO    |    NO    | see [SongObject](#song) fields               |
| songcount     | integer                                        |    NO    |    NO    |                                              |
| genre         | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| art           | string                                         |   YES    |    NO    |                                              |
| has_art       | boolean                                        |    NO    |    NO    |                                              |
| flag          | boolean                                        |    NO    |    NO    |                                              |
| rating        | integer                                        |   YES    |    NO    |                                              |
| averagerating | number                                         |   YES    |    NO    |                                              |
| mbid          | string                                         |   YES    |    NO    |                                              |
| summary       | string                                         |   YES    |    NO    |                                              |
| time          | integer                                        |    NO    |    NO    |                                              |
| yearformed    | integer                                        |    NO    |    NO    |                                              |
| placeformed   | string                                         |   YES    |    NO    |                                              |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `song` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| song        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |

Each `song` entry ([SongObject](#song)):

| Field                 | Type                                           | Nullable | Optional | Notes                                        |
|-----------------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id                    | string                                         |    NO    |    NO    |                                              |
| title                 | string                                         |   YES    |    NO    |                                              |
| name                  | string                                         |   YES    |    NO    |                                              |
| artist                | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| artists               | array&lt;[NamedReference](#namedreference)&gt; |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| album                 | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| albumartist           | [NamedReference](#namedreference)              |    NO    |   YES    | see [NamedReference](#namedreference) fields |
| disk                  | integer                                        |    NO    |    NO    |                                              |
| disksubtitle          | string                                         |   YES    |    NO    |                                              |
| track                 | integer                                        |    NO    |    NO    |                                              |
| filename              | string                                         |   YES    |    NO    |                                              |
| genre                 | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| playlisttrack         | integer                                        |    NO    |    NO    |                                              |
| time                  | integer                                        |    NO    |    NO    |                                              |
| year                  | integer                                        |    NO    |    NO    |                                              |
| format                | string                                         |   YES    |    NO    |                                              |
| stream_format         | string                                         |   YES    |    NO    |                                              |
| bitrate               | integer                                        |   YES    |    NO    |                                              |
| stream_bitrate        | integer                                        |   YES    |    NO    |                                              |
| rate                  | integer                                        |    NO    |    NO    |                                              |
| mode                  | string                                         |   YES    |    NO    |                                              |
| mime                  | string                                         |   YES    |    NO    |                                              |
| stream_mime           | string                                         |   YES    |    NO    |                                              |
| url                   | string                                         |    NO    |    NO    |                                              |
| size                  | integer                                        |    NO    |    NO    |                                              |
| mbid                  | string                                         |   YES    |    NO    |                                              |
| art                   | string                                         |   YES    |    NO    |                                              |
| has_art               | boolean                                        |    NO    |    NO    |                                              |
| flag                  | boolean                                        |    NO    |    NO    |                                              |
| rating                | integer                                        |   YES    |    NO    |                                              |
| averagerating         | number                                         |   YES    |    NO    |                                              |
| playcount             | integer                                        |    NO    |    NO    |                                              |
| last_played           | string                                         |   YES    |    NO    |                                              |
| catalog               | string                                         |    NO    |    NO    |                                              |
| composer              | string                                         |   YES    |    NO    |                                              |
| channels              | integer                                        |   YES    |    NO    |                                              |
| comment               | string                                         |   YES    |    NO    |                                              |
| license               | string                                         |   YES    |    NO    |                                              |
| publisher             | string                                         |   YES    |    NO    |                                              |
| language              | string                                         |   YES    |    NO    |                                              |
| lyrics                | string                                         |   YES    |    NO    |                                              |
| replaygain_album_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_album_peak | number                                         |   YES    |    NO    |                                              |
| replaygain_track_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_track_peak | number                                         |   YES    |    NO    |                                              |
| r128_album_gain       | number                                         |   YES    |    NO    |                                              |
| r128_track_gain       | number                                         |   YES    |    NO    |                                              |
| metadata              | object&lt;string, string&gt;                   |    NO    |   YES    |                                              |
<!-- GENERATED:RESPONSE:END -->

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

### get_external_metadata

Return External plugin metadata searching by object id and type

| Input    | Type   | Description                                      | Optional |
|----------|--------|--------------------------------------------------|---------:|
| 'filter' | string | $object_id to find                               |       NO |
| 'type'   | string | `song`, `album`, `artist`, `label` (object_type) |       NO |

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns the plugin payloads when at least one metadata plugin answered, and the empty list envelope for the requested type when none did.

**[ExternalMetadataObject](#get_external_metadata)**

`plugin` is keyed by metadata-retriever plugin name; each value is that plugin's payload.

`plugin` is keyed by metadata-retriever plugin name; each value is that plugin's payload.

| Field       | Type         | Nullable | Optional | Notes                   |
|-------------|--------------|:--------:|:--------:|-------------------------|
| object_id   | string       |    NO    |    NO    |                         |
| object_type | string       |    NO    |    NO    |                         |
| plugin      | `_PluginMap` |    NO    |    NO    | see `_PluginMap` fields |

**[EmptyListResponse](#get_external_metadata)**

The standard empty envelope, with an empty list keyed by the requested type.

The standard empty envelope, with an empty list keyed by the requested type.

| Field       | Type    | Nullable | Optional | Notes |
|-------------|---------|:--------:|:--------:|-------|
| total_count | integer |    NO    |    NO    |       |
| md5         | string  |    NO    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/get_external_metadata.xml)

### get_indexes

This takes a collection of inputs and returns ID + name for the object type

**NOTE** This method was **removed** in **API8** (Use list)

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

### get_lyrics

Return Database lyrics or search with plugins by Song id

| Input     | Type   | Description                                           | Optional |
|-----------|--------|-------------------------------------------------------|---------:|
| 'filter'  | string | $song_id to find                                      |       NO |
| 'plugins' | string | `0`, `1`, if false disable plugin lookup (default: 1) |       NO |

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

`plugin` is keyed by lyric source (`database` plus any lyric-retriever plugin that answered). When nothing answered it is serialised as an empty array, not an empty object.

| Field       | Type         | Nullable | Optional | Notes                   |
|-------------|--------------|:--------:|:--------:|-------------------------|
| object_id   | string       |    NO    |    NO    |                         |
| object_type | string       |    NO    |    NO    |                         |
| plugin      | `_PluginMap` |    NO    |    NO    | see `_PluginMap` fields |
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/get_lyrics.xml)

### get_similar

Return similar artist id's or similar song ids compared to the input filter

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'type'   | string  | `song`, `artist`                                 |       NO |
| 'filter' | string  | artist id or song id                             |       NO |
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `song` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| song        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |

Each `song` entry ([SongObject](#song)):

| Field                 | Type                                           | Nullable | Optional | Notes                                        |
|-----------------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id                    | string                                         |    NO    |    NO    |                                              |
| title                 | string                                         |   YES    |    NO    |                                              |
| name                  | string                                         |   YES    |    NO    |                                              |
| artist                | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| artists               | array&lt;[NamedReference](#namedreference)&gt; |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| album                 | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| albumartist           | [NamedReference](#namedreference)              |    NO    |   YES    | see [NamedReference](#namedreference) fields |
| disk                  | integer                                        |    NO    |    NO    |                                              |
| disksubtitle          | string                                         |   YES    |    NO    |                                              |
| track                 | integer                                        |    NO    |    NO    |                                              |
| filename              | string                                         |   YES    |    NO    |                                              |
| genre                 | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| playlisttrack         | integer                                        |    NO    |    NO    |                                              |
| time                  | integer                                        |    NO    |    NO    |                                              |
| year                  | integer                                        |    NO    |    NO    |                                              |
| format                | string                                         |   YES    |    NO    |                                              |
| stream_format         | string                                         |   YES    |    NO    |                                              |
| bitrate               | integer                                        |   YES    |    NO    |                                              |
| stream_bitrate        | integer                                        |   YES    |    NO    |                                              |
| rate                  | integer                                        |    NO    |    NO    |                                              |
| mode                  | string                                         |   YES    |    NO    |                                              |
| mime                  | string                                         |   YES    |    NO    |                                              |
| stream_mime           | string                                         |   YES    |    NO    |                                              |
| url                   | string                                         |    NO    |    NO    |                                              |
| size                  | integer                                        |    NO    |    NO    |                                              |
| mbid                  | string                                         |   YES    |    NO    |                                              |
| art                   | string                                         |   YES    |    NO    |                                              |
| has_art               | boolean                                        |    NO    |    NO    |                                              |
| flag                  | boolean                                        |    NO    |    NO    |                                              |
| rating                | integer                                        |   YES    |    NO    |                                              |
| averagerating         | number                                         |   YES    |    NO    |                                              |
| playcount             | integer                                        |    NO    |    NO    |                                              |
| last_played           | string                                         |   YES    |    NO    |                                              |
| catalog               | string                                         |    NO    |    NO    |                                              |
| composer              | string                                         |   YES    |    NO    |                                              |
| channels              | integer                                        |   YES    |    NO    |                                              |
| comment               | string                                         |   YES    |    NO    |                                              |
| license               | string                                         |   YES    |    NO    |                                              |
| publisher             | string                                         |   YES    |    NO    |                                              |
| language              | string                                         |   YES    |    NO    |                                              |
| lyrics                | string                                         |   YES    |    NO    |                                              |
| replaygain_album_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_album_peak | number                                         |   YES    |    NO    |                                              |
| replaygain_track_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_track_peak | number                                         |   YES    |    NO    |                                              |
| r128_album_gain       | number                                         |   YES    |    NO    |                                              |
| r128_track_gain       | number                                         |   YES    |    NO    |                                              |
| metadata              | object&lt;string, string&gt;                   |    NO    |   YES    |                                              |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Keyed by the requested `type` (e.g. `album`, `artist`, `song`). Without `include` the value is an array of object ids; with `include` it is an array of `{id, type}` references, or a map of parent id -> reference array for parent types such as playlists.

Open map — each value is: array&lt;string&gt; \| array&lt;[IndexReferenceObject](#indexreferenceobject)&gt; \| object&lt;string, array&lt;[IndexReferenceObject](#indexreferenceobject)&gt;&gt;.
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `artist` list.

| Field       | Type                                 | Nullable | Optional | Notes                              |
|-------------|--------------------------------------|:--------:|:--------:|------------------------------------|
| total_count | integer                              |    NO    |    NO    |                                    |
| md5         | string                               |    NO    |    NO    |                                    |
| artist      | array&lt;[ArtistObject](#artist)&gt; |    NO    |    NO    | see [ArtistObject](#artist) fields |

Each `artist` entry ([ArtistObject](#artist)):

| Field         | Type                                           | Nullable | Optional | Notes                                        |
|---------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id            | string                                         |    NO    |    NO    |                                              |
| name          | string                                         |   YES    |    NO    |                                              |
| prefix        | string                                         |   YES    |    NO    |                                              |
| basename      | string                                         |   YES    |    NO    |                                              |
| albums        | array&lt;[AlbumObject](#album)&gt;             |    NO    |    NO    | see [AlbumObject](#album) fields             |
| albumcount    | integer                                        |    NO    |    NO    |                                              |
| songs         | array&lt;[SongObject](#song)&gt;               |    NO    |    NO    | see [SongObject](#song) fields               |
| songcount     | integer                                        |    NO    |    NO    |                                              |
| genre         | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| art           | string                                         |   YES    |    NO    |                                              |
| has_art       | boolean                                        |    NO    |    NO    |                                              |
| flag          | boolean                                        |    NO    |    NO    |                                              |
| rating        | integer                                        |   YES    |    NO    |                                              |
| averagerating | number                                         |   YES    |    NO    |                                              |
| mbid          | string                                         |   YES    |    NO    |                                              |
| summary       | string                                         |   YES    |    NO    |                                              |
| time          | integer                                        |    NO    |    NO    |                                              |
| yearformed    | integer                                        |    NO    |    NO    |                                              |
| placeformed   | string                                         |   YES    |    NO    |                                              |
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/label_artists.xml)

### last_shouts

This gets the latest posted shouts

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `catalog` is deprecated and will be removed in **API9**

| Input    | Type    | Description                                                                  | Optional |
|----------|---------|------------------------------------------------------------------------------|---------:|
| 'filter' | string  | Get latest shouts for this username                                          |      YES |
| 'limit'  | integer | Maximum number of results (Use `popular_threshold` when missing; default 10) |      YES |

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `shout` list.

| Field | Type                                     | Nullable | Optional | Notes                                  |
|-------|------------------------------------------|:--------:|:--------:|----------------------------------------|
| shout | array&lt;[ShoutObject](#last_shouts)&gt; |    NO    |    NO    | see [ShoutObject](#last_shouts) fields |

Each `shout` entry ([ShoutObject](#last_shouts)):

| Field       | Type    | Nullable | Optional | Notes            |
|-------------|---------|:--------:|:--------:|------------------|
| id          | string  |    NO    |    NO    |                  |
| date        | integer |    NO    |    NO    |                  |
| text        | string  |    NO    |    NO    |                  |
| object_type | string  |    NO    |    NO    |                  |
| object_id   | string  |    NO    |    NO    |                  |
| user        | object  |    NO    |    NO    | `{id, username}` |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a single object.

| Field         | Type   | Nullable | Optional | Notes |
|---------------|--------|:--------:|:--------:|-------|
| id            | string |    NO    |    NO    |       |
| name          | string |    NO    |    NO    |       |
| description   | string |    NO    |    NO    |       |
| external_link | string |    NO    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `song` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| song        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |

Each `song` entry ([SongObject](#song)):

| Field                 | Type                                           | Nullable | Optional | Notes                                        |
|-----------------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id                    | string                                         |    NO    |    NO    |                                              |
| title                 | string                                         |   YES    |    NO    |                                              |
| name                  | string                                         |   YES    |    NO    |                                              |
| artist                | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| artists               | array&lt;[NamedReference](#namedreference)&gt; |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| album                 | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| albumartist           | [NamedReference](#namedreference)              |    NO    |   YES    | see [NamedReference](#namedreference) fields |
| disk                  | integer                                        |    NO    |    NO    |                                              |
| disksubtitle          | string                                         |   YES    |    NO    |                                              |
| track                 | integer                                        |    NO    |    NO    |                                              |
| filename              | string                                         |   YES    |    NO    |                                              |
| genre                 | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| playlisttrack         | integer                                        |    NO    |    NO    |                                              |
| time                  | integer                                        |    NO    |    NO    |                                              |
| year                  | integer                                        |    NO    |    NO    |                                              |
| format                | string                                         |   YES    |    NO    |                                              |
| stream_format         | string                                         |   YES    |    NO    |                                              |
| bitrate               | integer                                        |   YES    |    NO    |                                              |
| stream_bitrate        | integer                                        |   YES    |    NO    |                                              |
| rate                  | integer                                        |    NO    |    NO    |                                              |
| mode                  | string                                         |   YES    |    NO    |                                              |
| mime                  | string                                         |   YES    |    NO    |                                              |
| stream_mime           | string                                         |   YES    |    NO    |                                              |
| url                   | string                                         |    NO    |    NO    |                                              |
| size                  | integer                                        |    NO    |    NO    |                                              |
| mbid                  | string                                         |   YES    |    NO    |                                              |
| art                   | string                                         |   YES    |    NO    |                                              |
| has_art               | boolean                                        |    NO    |    NO    |                                              |
| flag                  | boolean                                        |    NO    |    NO    |                                              |
| rating                | integer                                        |   YES    |    NO    |                                              |
| averagerating         | number                                         |   YES    |    NO    |                                              |
| playcount             | integer                                        |    NO    |    NO    |                                              |
| last_played           | string                                         |   YES    |    NO    |                                              |
| catalog               | string                                         |    NO    |    NO    |                                              |
| composer              | string                                         |   YES    |    NO    |                                              |
| channels              | integer                                        |   YES    |    NO    |                                              |
| comment               | string                                         |   YES    |    NO    |                                              |
| license               | string                                         |   YES    |    NO    |                                              |
| publisher             | string                                         |   YES    |    NO    |                                              |
| language              | string                                         |   YES    |    NO    |                                              |
| lyrics                | string                                         |   YES    |    NO    |                                              |
| replaygain_album_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_album_peak | number                                         |   YES    |    NO    |                                              |
| replaygain_track_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_track_peak | number                                         |   YES    |    NO    |                                              |
| r128_album_gain       | number                                         |   YES    |    NO    |                                              |
| r128_track_gain       | number                                         |   YES    |    NO    |                                              |
| metadata              | object&lt;string, string&gt;                   |    NO    |   YES    |                                              |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `list` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| list        | array&lt;[ListObject](#list)&gt; |    NO    |    NO    | see [ListObject](#list) fields |

Each `list` entry ([ListObject](#list)):

| Field    | Type   | Nullable | Optional | Notes |
|----------|--------|:--------:|:--------:|-------|
| id       | string |    NO    |    NO    |       |
| name     | string |    NO    |    NO    |       |
| prefix   | string |   YES    |    NO    |       |
| basename | string |    NO    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `now_playing` list.

| Field       | Type                                          | Nullable | Optional | Notes                                       |
|-------------|-----------------------------------------------|:--------:|:--------:|---------------------------------------------|
| now_playing | array&lt;[NowPlayingObject](#now_playing)&gt; |    NO    |    NO    | see [NowPlayingObject](#now_playing) fields |

Each `now_playing` entry ([NowPlayingObject](#now_playing)):

| Field  | Type    | Nullable | Optional | Notes            |
|--------|---------|:--------:|:--------:|------------------|
| id     | string  |    NO    |    NO    |                  |
| type   | string  |    NO    |    NO    |                  |
| client | string  |    NO    |    NO    |                  |
| expire | integer |    NO    |    NO    |                  |
| user   | object  |    NO    |    NO    | `{id, username}` |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `now_playing` list.

| Field       | Type                                          | Nullable | Optional | Notes                                       |
|-------------|-----------------------------------------------|:--------:|:--------:|---------------------------------------------|
| now_playing | array&lt;[NowPlayingObject](#now_playing)&gt; |    NO    |    NO    | see [NowPlayingObject](#now_playing) fields |

Each `now_playing` entry ([NowPlayingObject](#now_playing)):

| Field  | Type    | Nullable | Optional | Notes            |
|--------|---------|:--------:|:--------:|------------------|
| id     | string  |    NO    |    NO    |                  |
| type   | string  |    NO    |    NO    |                  |
| client | string  |    NO    |    NO    |                  |
| expire | integer |    NO    |    NO    |                  |
| user   | object  |    NO    |    NO    | `{id, username}` |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `playlist` list.

| Field       | Type                                     | Nullable | Optional | Notes                                  |
|-------------|------------------------------------------|:--------:|:--------:|----------------------------------------|
| total_count | integer                                  |    NO    |    NO    |                                        |
| md5         | string                                   |    NO    |    NO    |                                        |
| playlist    | array&lt;[PlaylistObject](#playlist)&gt; |    NO    |    NO    | see [PlaylistObject](#playlist) fields |

Each `playlist` entry ([PlaylistObject](#playlist)):

| Field           | Type                           | Nullable | Optional | Notes                                  |
|-----------------|--------------------------------|:--------:|:--------:|----------------------------------------|
| id              | string                         |    NO    |    NO    |                                        |
| name            | string                         |   YES    |    NO    |                                        |
| owner           | string                         |   YES    |    NO    |                                        |
| user            | [UserSummaryObject](#users)    |    NO    |    NO    | see [UserSummaryObject](#users) fields |
| items           | array&lt;object&gt; \| integer |    NO    |    NO    |                                        |
| type            | string                         |   YES    |    NO    |                                        |
| art             | string                         |   YES    |    NO    |                                        |
| has_access      | boolean                        |    NO    |    NO    |                                        |
| has_collaborate | boolean                        |    NO    |    NO    |                                        |
| has_art         | boolean                        |    NO    |    NO    |                                        |
| flag            | boolean                        |    NO    |    NO    |                                        |
| rating          | integer                        |   YES    |    NO    |                                        |
| averagerating   | number                         |   YES    |    NO    |                                        |
| md5             | string                         |   YES    |    NO    |                                        |
| last_update     | integer                        |   YES    |    NO    |                                        |
| time            | integer                        |    NO    |    NO    |                                        |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a single object.

| Field           | Type                           | Nullable | Optional | Notes                                  |
|-----------------|--------------------------------|:--------:|:--------:|----------------------------------------|
| id              | string                         |    NO    |    NO    |                                        |
| name            | string                         |   YES    |    NO    |                                        |
| owner           | string                         |   YES    |    NO    |                                        |
| user            | [UserSummaryObject](#users)    |    NO    |    NO    | see [UserSummaryObject](#users) fields |
| items           | array&lt;object&gt; \| integer |    NO    |    NO    |                                        |
| type            | string                         |   YES    |    NO    |                                        |
| art             | string                         |   YES    |    NO    |                                        |
| has_access      | boolean                        |    NO    |    NO    |                                        |
| has_collaborate | boolean                        |    NO    |    NO    |                                        |
| has_art         | boolean                        |    NO    |    NO    |                                        |
| flag            | boolean                        |    NO    |    NO    |                                        |
| rating          | integer                        |   YES    |    NO    |                                        |
| averagerating   | number                         |   YES    |    NO    |                                        |
| md5             | string                         |   YES    |    NO    |                                        |
| last_update     | integer                        |   YES    |    NO    |                                        |
| time            | integer                        |    NO    |    NO    |                                        |
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/playlist.xml)

### playlist_add

This adds a song to a playlist. setting check=1 will not add duplicates to the playlist

**NOTE** `type` is optional from Ampache8+

| Input    | Type   | Description                                           | Optional |
|----------|--------|-------------------------------------------------------|---------:|
| 'filter' | string | UID of Playlist                                       |       NO |
| 'id'     | string | UID of the object to add to playlist                  |       NO |
| 'type'   | string | 'song', 'album', 'artist', 'playlist' (Default: song) |      YES |

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

**NOTE** This method was **removed** in **API8** (Use playlist_add)

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Depends on the `format` parameter: `song` (default) and `index` return the song list envelope, `id` returns a bare array of song ids.

**[SongsResponse](#album_disk_songs)**

Returns a `song` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| song        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |

Each `song` entry ([SongObject](#song)):

| Field                 | Type                                           | Nullable | Optional | Notes                                        |
|-----------------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id                    | string                                         |    NO    |    NO    |                                              |
| title                 | string                                         |   YES    |    NO    |                                              |
| name                  | string                                         |   YES    |    NO    |                                              |
| artist                | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| artists               | array&lt;[NamedReference](#namedreference)&gt; |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| album                 | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| albumartist           | [NamedReference](#namedreference)              |    NO    |   YES    | see [NamedReference](#namedreference) fields |
| disk                  | integer                                        |    NO    |    NO    |                                              |
| disksubtitle          | string                                         |   YES    |    NO    |                                              |
| track                 | integer                                        |    NO    |    NO    |                                              |
| filename              | string                                         |   YES    |    NO    |                                              |
| genre                 | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| playlisttrack         | integer                                        |    NO    |    NO    |                                              |
| time                  | integer                                        |    NO    |    NO    |                                              |
| year                  | integer                                        |    NO    |    NO    |                                              |
| format                | string                                         |   YES    |    NO    |                                              |
| stream_format         | string                                         |   YES    |    NO    |                                              |
| bitrate               | integer                                        |   YES    |    NO    |                                              |
| stream_bitrate        | integer                                        |   YES    |    NO    |                                              |
| rate                  | integer                                        |    NO    |    NO    |                                              |
| mode                  | string                                         |   YES    |    NO    |                                              |
| mime                  | string                                         |   YES    |    NO    |                                              |
| stream_mime           | string                                         |   YES    |    NO    |                                              |
| url                   | string                                         |    NO    |    NO    |                                              |
| size                  | integer                                        |    NO    |    NO    |                                              |
| mbid                  | string                                         |   YES    |    NO    |                                              |
| art                   | string                                         |   YES    |    NO    |                                              |
| has_art               | boolean                                        |    NO    |    NO    |                                              |
| flag                  | boolean                                        |    NO    |    NO    |                                              |
| rating                | integer                                        |   YES    |    NO    |                                              |
| averagerating         | number                                         |   YES    |    NO    |                                              |
| playcount             | integer                                        |    NO    |    NO    |                                              |
| last_played           | string                                         |   YES    |    NO    |                                              |
| catalog               | string                                         |    NO    |    NO    |                                              |
| composer              | string                                         |   YES    |    NO    |                                              |
| channels              | integer                                        |   YES    |    NO    |                                              |
| comment               | string                                         |   YES    |    NO    |                                              |
| license               | string                                         |   YES    |    NO    |                                              |
| publisher             | string                                         |   YES    |    NO    |                                              |
| language              | string                                         |   YES    |    NO    |                                              |
| lyrics                | string                                         |   YES    |    NO    |                                              |
| replaygain_album_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_album_peak | number                                         |   YES    |    NO    |                                              |
| replaygain_track_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_track_peak | number                                         |   YES    |    NO    |                                              |
| r128_album_gain       | number                                         |   YES    |    NO    |                                              |
| r128_track_gain       | number                                         |   YES    |    NO    |                                              |
| metadata              | object&lt;string, string&gt;                   |    NO    |   YES    |                                              |

**array&lt;string&gt;**

Returned by `format=id`: song ids only, with no envelope.
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a single object.

| Field | Type   | Nullable | Optional | Notes |
|-------|--------|:--------:|:--------:|-------|
| md5   | string |   YES    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/playlist_hash.xml)

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/playlist_remove.xml)

### playlist_songs

This returns the songs for a playlist

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'filter' | string  | UID of Playlist, returns song XML                |       NO |
| 'random' | integer | `0`, `1` (if true get random songs using limit)  |      YES |
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `song` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| song        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |

Each `song` entry ([SongObject](#song)):

| Field                 | Type                                           | Nullable | Optional | Notes                                        |
|-----------------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id                    | string                                         |    NO    |    NO    |                                              |
| title                 | string                                         |   YES    |    NO    |                                              |
| name                  | string                                         |   YES    |    NO    |                                              |
| artist                | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| artists               | array&lt;[NamedReference](#namedreference)&gt; |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| album                 | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| albumartist           | [NamedReference](#namedreference)              |    NO    |   YES    | see [NamedReference](#namedreference) fields |
| disk                  | integer                                        |    NO    |    NO    |                                              |
| disksubtitle          | string                                         |   YES    |    NO    |                                              |
| track                 | integer                                        |    NO    |    NO    |                                              |
| filename              | string                                         |   YES    |    NO    |                                              |
| genre                 | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| playlisttrack         | integer                                        |    NO    |    NO    |                                              |
| time                  | integer                                        |    NO    |    NO    |                                              |
| year                  | integer                                        |    NO    |    NO    |                                              |
| format                | string                                         |   YES    |    NO    |                                              |
| stream_format         | string                                         |   YES    |    NO    |                                              |
| bitrate               | integer                                        |   YES    |    NO    |                                              |
| stream_bitrate        | integer                                        |   YES    |    NO    |                                              |
| rate                  | integer                                        |    NO    |    NO    |                                              |
| mode                  | string                                         |   YES    |    NO    |                                              |
| mime                  | string                                         |   YES    |    NO    |                                              |
| stream_mime           | string                                         |   YES    |    NO    |                                              |
| url                   | string                                         |    NO    |    NO    |                                              |
| size                  | integer                                        |    NO    |    NO    |                                              |
| mbid                  | string                                         |   YES    |    NO    |                                              |
| art                   | string                                         |   YES    |    NO    |                                              |
| has_art               | boolean                                        |    NO    |    NO    |                                              |
| flag                  | boolean                                        |    NO    |    NO    |                                              |
| rating                | integer                                        |   YES    |    NO    |                                              |
| averagerating         | number                                         |   YES    |    NO    |                                              |
| playcount             | integer                                        |    NO    |    NO    |                                              |
| last_played           | string                                         |   YES    |    NO    |                                              |
| catalog               | string                                         |    NO    |    NO    |                                              |
| composer              | string                                         |   YES    |    NO    |                                              |
| channels              | integer                                        |   YES    |    NO    |                                              |
| comment               | string                                         |   YES    |    NO    |                                              |
| license               | string                                         |   YES    |    NO    |                                              |
| publisher             | string                                         |   YES    |    NO    |                                              |
| language              | string                                         |   YES    |    NO    |                                              |
| lyrics                | string                                         |   YES    |    NO    |                                              |
| replaygain_album_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_album_peak | number                                         |   YES    |    NO    |                                              |
| replaygain_track_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_track_peak | number                                         |   YES    |    NO    |                                              |
| r128_album_gain       | number                                         |   YES    |    NO    |                                              |
| r128_track_gain       | number                                         |   YES    |    NO    |                                              |
| metadata              | object&lt;string, string&gt;                   |    NO    |   YES    |                                              |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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
| last_played    | string  |   YES    |    NO    |              |
| played         | string  |    NO    |    NO    |              |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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
| last_played    | string  |   YES    |    NO    |              |
| played         | string  |    NO    |    NO    |              |
<!-- GENERATED:RESPONSE:END -->

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

| Input     | Type    | Description                                                                             | Optional |
|-----------|---------|-----------------------------------------------------------------------------------------|---------:|
| 'filter'  | string  | Preference name e.g ('notify_email', 'ajax_load')                                       |       NO |
| 'value'   | mixed   | (string/integer) Preference value                                                       |       NO |
| 'all'     | boolean | `0`, `1` apply to all users **ACCESS REQUIRED:** 100 (Admin)                            |      YES |
| 'default' | boolean | `0`, `1` set as system default (New and public users)  **ACCESS REQUIRED:** 100 (Admin) |      YES |

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

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `id` is deprecated and will be removed in **API9**.

| Input    | Type    | Description                                           | Optional |
|----------|---------|-------------------------------------------------------|---------:|
| 'filter' | string  | library item id                                       |       NO |
| 'type'   | string  | `song`, `album`, `artist`, `playlist`, `podcast`      |       NO |
|          |         | `podcast_episode`, `video`, `tvshow`, `tvshow_season` |          |
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

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `id` is deprecated and will be removed in **API9**.

| Input    | Type    | Description | Optional |
|----------|---------|-------------|---------:|
| 'filter' | string  | $object_id  |       NO |
| 'user'   | string  | $user_id    |      YES |
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

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `video` list.

| Field       | Type                               | Nullable | Optional | Notes                            |
|-------------|------------------------------------|:--------:|:--------:|----------------------------------|
| total_count | integer                            |    NO    |    NO    |                                  |
| md5         | string                             |    NO    |    NO    |                                  |
| video       | array&lt;[VideoObject](#video)&gt; |    NO    |    NO    | see [VideoObject](#video) fields |

Each `video` entry ([VideoObject](#video)):

| Field         | Type                                           | Nullable | Optional | Notes                                        |
|---------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id            | string                                         |    NO    |    NO    |                                              |
| title         | string                                         |   YES    |    NO    |                                              |
| mime          | string                                         |   YES    |    NO    |                                              |
| resolution    | string                                         |   YES    |    NO    |                                              |
| size          | integer                                        |    NO    |    NO    |                                              |
| genre         | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| time          | integer                                        |    NO    |    NO    |                                              |
| url           | string                                         |    NO    |    NO    |                                              |
| art           | string                                         |   YES    |    NO    |                                              |
| has_art       | boolean                                        |    NO    |    NO    |                                              |
| flag          | boolean                                        |    NO    |    NO    |                                              |
| rating        | integer                                        |   YES    |    NO    |                                              |
| averagerating | number                                         |   YES    |    NO    |                                              |
| playcount     | integer                                        |    NO    |    NO    |                                              |
| last_played   | string                                         |   YES    |    NO    |                                              |
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

SONG [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/advanced_search%20\(song\).xml)

ARTIST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/advanced_search%20\(artist\).xml)

ALBUM [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/advanced_search%20\(album\).xml)

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

`search` is keyed by object type (`album`, `artist`, `album_artist`, `song_artist`, `song`, `playlist`, `podcast`, `podcast_episode`, `genre`, `label`, `user`, `video`); each value is that type's usual object list. Types with no matches are omitted.

| Field  | Type                                      | Nullable | Optional | Notes |
|--------|-------------------------------------------|:--------:|:--------:|-------|
| search | object&lt;string, array&lt;object&gt;&gt; |    NO    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

ALL [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/search_group%20\(all\).xml)

MUSIC [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/search_group%20\(music\).xml)

PODCAST [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/search_group%20\(podcast\).xml)

### search_rules

Print a list of valid search rules for your search type

| Input    | Type   | Description                                     | Optional |
|----------|--------|-------------------------------------------------|---------:|
| 'filter' | string | 'song', 'album', 'song_artist', 'album_artist', |       NO |
|          |        | 'artist', 'label', 'playlist', 'podcast',       |          |
|          |        | 'podcast_episode', 'genre', 'user', 'video'     |          |

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `rule` list.

| Field | Type                                           | Nullable | Optional | Notes                                        |
|-------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| rule  | array&lt;[SearchRuleObject](#search_rules)&gt; |    NO    |    NO    | see [SearchRuleObject](#search_rules) fields |

Each `rule` entry ([SearchRuleObject](#search_rules)):

| Field  | Type                                                | Nullable | Optional | Notes |
|--------|-----------------------------------------------------|:--------:|:--------:|-------|
| name   | string                                              |    NO    |    NO    |       |
| label  | string                                              |    NO    |    NO    |       |
| type   | string                                              |    NO    |    NO    |       |
| widget | array&lt;string \| object&lt;string, string&gt;&gt; |    NO    |    NO    |       |
| title  | string                                              |    NO    |    NO    |       |
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

Artist [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/search_rules%20\(artist\).xml)

Album [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/search_rules%20\(album\).xml)

Song [Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/json-responses/search_rules%20\(song\).xml)

### search_songs

This searches the songs and returns... songs

**NOTE** `filter` has an alias `rule_1_input` to match other search methods

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `playlist` list.

| Field       | Type                                     | Nullable | Optional | Notes                                  |
|-------------|------------------------------------------|:--------:|:--------:|----------------------------------------|
| total_count | integer                                  |    NO    |    NO    |                                        |
| md5         | string                                   |    NO    |    NO    |                                        |
| playlist    | array&lt;[PlaylistObject](#playlist)&gt; |    NO    |    NO    | see [PlaylistObject](#playlist) fields |

Each `playlist` entry ([PlaylistObject](#playlist)):

| Field           | Type                           | Nullable | Optional | Notes                                  |
|-----------------|--------------------------------|:--------:|:--------:|----------------------------------------|
| id              | string                         |    NO    |    NO    |                                        |
| name            | string                         |   YES    |    NO    |                                        |
| owner           | string                         |   YES    |    NO    |                                        |
| user            | [UserSummaryObject](#users)    |    NO    |    NO    | see [UserSummaryObject](#users) fields |
| items           | array&lt;object&gt; \| integer |    NO    |    NO    |                                        |
| type            | string                         |   YES    |    NO    |                                        |
| art             | string                         |   YES    |    NO    |                                        |
| has_access      | boolean                        |    NO    |    NO    |                                        |
| has_collaborate | boolean                        |    NO    |    NO    |                                        |
| has_art         | boolean                        |    NO    |    NO    |                                        |
| flag            | boolean                        |    NO    |    NO    |                                        |
| rating          | integer                        |   YES    |    NO    |                                        |
| averagerating   | number                         |   YES    |    NO    |                                        |
| md5             | string                         |   YES    |    NO    |                                        |
| last_update     | integer                        |   YES    |    NO    |                                        |
| time            | integer                        |    NO    |    NO    |                                        |
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/smartlists.xml)

### smartlist

This returns a single smartlist

| Input    | Type   | Description                             | Optional |
|----------|--------|-----------------------------------------|---------:|
| 'filter' | string | UID of smartlist, returns smartlist XML |       NO |

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a single object.

| Field           | Type                           | Nullable | Optional | Notes                                  |
|-----------------|--------------------------------|:--------:|:--------:|----------------------------------------|
| id              | string                         |    NO    |    NO    |                                        |
| name            | string                         |   YES    |    NO    |                                        |
| owner           | string                         |   YES    |    NO    |                                        |
| user            | [UserSummaryObject](#users)    |    NO    |    NO    | see [UserSummaryObject](#users) fields |
| items           | array&lt;object&gt; \| integer |    NO    |    NO    |                                        |
| type            | string                         |   YES    |    NO    |                                        |
| art             | string                         |   YES    |    NO    |                                        |
| has_access      | boolean                        |    NO    |    NO    |                                        |
| has_collaborate | boolean                        |    NO    |    NO    |                                        |
| has_art         | boolean                        |    NO    |    NO    |                                        |
| flag            | boolean                        |    NO    |    NO    |                                        |
| rating          | integer                        |   YES    |    NO    |                                        |
| averagerating   | number                         |   YES    |    NO    |                                        |
| md5             | string                         |   YES    |    NO    |                                        |
| last_update     | integer                        |   YES    |    NO    |                                        |
| time            | integer                        |    NO    |    NO    |                                        |
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/smartlist.xml)

### smartlist_songs

This returns the songs for a smartlist

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'filter' | string  | UID of smartlist, returns song XML               |       NO |
| 'random' | integer | `0`, `1` (if true get random songs using limit)  |      YES |
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `song` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| song        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |

Each `song` entry ([SongObject](#song)):

| Field                 | Type                                           | Nullable | Optional | Notes                                        |
|-----------------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id                    | string                                         |    NO    |    NO    |                                              |
| title                 | string                                         |   YES    |    NO    |                                              |
| name                  | string                                         |   YES    |    NO    |                                              |
| artist                | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| artists               | array&lt;[NamedReference](#namedreference)&gt; |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| album                 | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| albumartist           | [NamedReference](#namedreference)              |    NO    |   YES    | see [NamedReference](#namedreference) fields |
| disk                  | integer                                        |    NO    |    NO    |                                              |
| disksubtitle          | string                                         |   YES    |    NO    |                                              |
| track                 | integer                                        |    NO    |    NO    |                                              |
| filename              | string                                         |   YES    |    NO    |                                              |
| genre                 | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| playlisttrack         | integer                                        |    NO    |    NO    |                                              |
| time                  | integer                                        |    NO    |    NO    |                                              |
| year                  | integer                                        |    NO    |    NO    |                                              |
| format                | string                                         |   YES    |    NO    |                                              |
| stream_format         | string                                         |   YES    |    NO    |                                              |
| bitrate               | integer                                        |   YES    |    NO    |                                              |
| stream_bitrate        | integer                                        |   YES    |    NO    |                                              |
| rate                  | integer                                        |    NO    |    NO    |                                              |
| mode                  | string                                         |   YES    |    NO    |                                              |
| mime                  | string                                         |   YES    |    NO    |                                              |
| stream_mime           | string                                         |   YES    |    NO    |                                              |
| url                   | string                                         |    NO    |    NO    |                                              |
| size                  | integer                                        |    NO    |    NO    |                                              |
| mbid                  | string                                         |   YES    |    NO    |                                              |
| art                   | string                                         |   YES    |    NO    |                                              |
| has_art               | boolean                                        |    NO    |    NO    |                                              |
| flag                  | boolean                                        |    NO    |    NO    |                                              |
| rating                | integer                                        |   YES    |    NO    |                                              |
| averagerating         | number                                         |   YES    |    NO    |                                              |
| playcount             | integer                                        |    NO    |    NO    |                                              |
| last_played           | string                                         |   YES    |    NO    |                                              |
| catalog               | string                                         |    NO    |    NO    |                                              |
| composer              | string                                         |   YES    |    NO    |                                              |
| channels              | integer                                        |   YES    |    NO    |                                              |
| comment               | string                                         |   YES    |    NO    |                                              |
| license               | string                                         |   YES    |    NO    |                                              |
| publisher             | string                                         |   YES    |    NO    |                                              |
| language              | string                                         |   YES    |    NO    |                                              |
| lyrics                | string                                         |   YES    |    NO    |                                              |
| replaygain_album_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_album_peak | number                                         |   YES    |    NO    |                                              |
| replaygain_track_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_track_peak | number                                         |   YES    |    NO    |                                              |
| r128_album_gain       | number                                         |   YES    |    NO    |                                              |
| r128_track_gain       | number                                         |   YES    |    NO    |                                              |
| metadata              | object&lt;string, string&gt;                   |    NO    |   YES    |                                              |
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/smartlist_songs.xml)

### smartlist_delete

This deletes a smartlist

| Input    | Type   | Description      | Optional |
|----------|--------|------------------|---------:|
| 'filter' | string | UID of smartlist |       NO |

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

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/smartlist_delete.xml)

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `song` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| song        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |

Each `song` entry ([SongObject](#song)):

| Field                 | Type                                           | Nullable | Optional | Notes                                        |
|-----------------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id                    | string                                         |    NO    |    NO    |                                              |
| title                 | string                                         |   YES    |    NO    |                                              |
| name                  | string                                         |   YES    |    NO    |                                              |
| artist                | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| artists               | array&lt;[NamedReference](#namedreference)&gt; |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| album                 | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| albumartist           | [NamedReference](#namedreference)              |    NO    |   YES    | see [NamedReference](#namedreference) fields |
| disk                  | integer                                        |    NO    |    NO    |                                              |
| disksubtitle          | string                                         |   YES    |    NO    |                                              |
| track                 | integer                                        |    NO    |    NO    |                                              |
| filename              | string                                         |   YES    |    NO    |                                              |
| genre                 | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| playlisttrack         | integer                                        |    NO    |    NO    |                                              |
| time                  | integer                                        |    NO    |    NO    |                                              |
| year                  | integer                                        |    NO    |    NO    |                                              |
| format                | string                                         |   YES    |    NO    |                                              |
| stream_format         | string                                         |   YES    |    NO    |                                              |
| bitrate               | integer                                        |   YES    |    NO    |                                              |
| stream_bitrate        | integer                                        |   YES    |    NO    |                                              |
| rate                  | integer                                        |    NO    |    NO    |                                              |
| mode                  | string                                         |   YES    |    NO    |                                              |
| mime                  | string                                         |   YES    |    NO    |                                              |
| stream_mime           | string                                         |   YES    |    NO    |                                              |
| url                   | string                                         |    NO    |    NO    |                                              |
| size                  | integer                                        |    NO    |    NO    |                                              |
| mbid                  | string                                         |   YES    |    NO    |                                              |
| art                   | string                                         |   YES    |    NO    |                                              |
| has_art               | boolean                                        |    NO    |    NO    |                                              |
| flag                  | boolean                                        |    NO    |    NO    |                                              |
| rating                | integer                                        |   YES    |    NO    |                                              |
| averagerating         | number                                         |   YES    |    NO    |                                              |
| playcount             | integer                                        |    NO    |    NO    |                                              |
| last_played           | string                                         |   YES    |    NO    |                                              |
| catalog               | string                                         |    NO    |    NO    |                                              |
| composer              | string                                         |   YES    |    NO    |                                              |
| channels              | integer                                        |   YES    |    NO    |                                              |
| comment               | string                                         |   YES    |    NO    |                                              |
| license               | string                                         |   YES    |    NO    |                                              |
| publisher             | string                                         |   YES    |    NO    |                                              |
| language              | string                                         |   YES    |    NO    |                                              |
| lyrics                | string                                         |   YES    |    NO    |                                              |
| replaygain_album_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_album_peak | number                                         |   YES    |    NO    |                                              |
| replaygain_track_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_track_peak | number                                         |   YES    |    NO    |                                              |
| r128_album_gain       | number                                         |   YES    |    NO    |                                              |
| r128_track_gain       | number                                         |   YES    |    NO    |                                              |
| metadata              | object&lt;string, string&gt;                   |    NO    |   YES    |                                              |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a single object.

| Field                 | Type                                           | Nullable | Optional | Notes                                        |
|-----------------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id                    | string                                         |    NO    |    NO    |                                              |
| title                 | string                                         |   YES    |    NO    |                                              |
| name                  | string                                         |   YES    |    NO    |                                              |
| artist                | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| artists               | array&lt;[NamedReference](#namedreference)&gt; |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| album                 | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| albumartist           | [NamedReference](#namedreference)              |    NO    |   YES    | see [NamedReference](#namedreference) fields |
| disk                  | integer                                        |    NO    |    NO    |                                              |
| disksubtitle          | string                                         |   YES    |    NO    |                                              |
| track                 | integer                                        |    NO    |    NO    |                                              |
| filename              | string                                         |   YES    |    NO    |                                              |
| genre                 | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| playlisttrack         | integer                                        |    NO    |    NO    |                                              |
| time                  | integer                                        |    NO    |    NO    |                                              |
| year                  | integer                                        |    NO    |    NO    |                                              |
| format                | string                                         |   YES    |    NO    |                                              |
| stream_format         | string                                         |   YES    |    NO    |                                              |
| bitrate               | integer                                        |   YES    |    NO    |                                              |
| stream_bitrate        | integer                                        |   YES    |    NO    |                                              |
| rate                  | integer                                        |    NO    |    NO    |                                              |
| mode                  | string                                         |   YES    |    NO    |                                              |
| mime                  | string                                         |   YES    |    NO    |                                              |
| stream_mime           | string                                         |   YES    |    NO    |                                              |
| url                   | string                                         |    NO    |    NO    |                                              |
| size                  | integer                                        |    NO    |    NO    |                                              |
| mbid                  | string                                         |   YES    |    NO    |                                              |
| art                   | string                                         |   YES    |    NO    |                                              |
| has_art               | boolean                                        |    NO    |    NO    |                                              |
| flag                  | boolean                                        |    NO    |    NO    |                                              |
| rating                | integer                                        |   YES    |    NO    |                                              |
| averagerating         | number                                         |   YES    |    NO    |                                              |
| playcount             | integer                                        |    NO    |    NO    |                                              |
| last_played           | string                                         |   YES    |    NO    |                                              |
| catalog               | string                                         |    NO    |    NO    |                                              |
| composer              | string                                         |   YES    |    NO    |                                              |
| channels              | integer                                        |   YES    |    NO    |                                              |
| comment               | string                                         |   YES    |    NO    |                                              |
| license               | string                                         |   YES    |    NO    |                                              |
| publisher             | string                                         |   YES    |    NO    |                                              |
| language              | string                                         |   YES    |    NO    |                                              |
| lyrics                | string                                         |   YES    |    NO    |                                              |
| replaygain_album_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_album_peak | number                                         |   YES    |    NO    |                                              |
| replaygain_track_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_track_peak | number                                         |   YES    |    NO    |                                              |
| r128_album_gain       | number                                         |   YES    |    NO    |                                              |
| r128_track_gain       | number                                         |   YES    |    NO    |                                              |
| metadata              | object&lt;string, string&gt;                   |    NO    |   YES    |                                              |
<!-- GENERATED:RESPONSE:END -->

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

| Input    | Type   | Description          | Optional |
|----------|--------|----------------------|---------:|
| 'filter' | string | UID of song to fetch |       NO |

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/song_tags.xml)

### sonic_match

Songs that sound like the given song, most similar first.

Similarity is derived from analysing the audio, which Ampache does not do itself, so this needs a sonic analysis plugin (e.g. AudioMuse) enabled for the user. With no plugin to ask, the method reports the feature as unavailable rather than returning an empty list.

Each entry carries the full song plus `similarity`, a `0.0`-`1.0` score where `1.0` is the same recording. A backend that gives no comparable score reports `-1`.

| Input    | Type    | Description                                      | Optional |
|----------|---------|--------------------------------------------------|---------:|
| 'filter' | string  | UID of Song                                      |       NO |
| 'offset' | integer | Return results starting from this index position |      YES |
| 'limit'  | integer | Maximum number of results to return              |      YES |

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `sonic_match` list.

| Field       | Type                                          | Nullable | Optional | Notes                                       |
|-------------|-----------------------------------------------|:--------:|:--------:|---------------------------------------------|
| sonic_match | array&lt;[SonicMatchObject](#sonic_match)&gt; |    NO    |    NO    | see [SonicMatchObject](#sonic_match) fields |

Each `sonic_match` entry ([SonicMatchObject](#sonic_match)):

| Field | Type | Nullable | Optional | Notes |
|-------|------|:--------:|:--------:|-------|
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `video` list.

| Field       | Type                               | Nullable | Optional | Notes                            |
|-------------|------------------------------------|:--------:|:--------:|----------------------------------|
| total_count | integer                            |    NO    |    NO    |                                  |
| md5         | string                             |    NO    |    NO    |                                  |
| video       | array&lt;[VideoObject](#video)&gt; |    NO    |    NO    | see [VideoObject](#video) fields |

Each `video` entry ([VideoObject](#video)):

| Field         | Type                                           | Nullable | Optional | Notes                                        |
|---------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id            | string                                         |    NO    |    NO    |                                              |
| title         | string                                         |   YES    |    NO    |                                              |
| mime          | string                                         |   YES    |    NO    |                                              |
| resolution    | string                                         |   YES    |    NO    |                                              |
| size          | integer                                        |    NO    |    NO    |                                              |
| genre         | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| time          | integer                                        |    NO    |    NO    |                                              |
| url           | string                                         |    NO    |    NO    |                                              |
| art           | string                                         |   YES    |    NO    |                                              |
| has_art       | boolean                                        |    NO    |    NO    |                                              |
| flag          | boolean                                        |    NO    |    NO    |                                              |
| rating        | integer                                        |   YES    |    NO    |                                              |
| averagerating | number                                         |   YES    |    NO    |                                              |
| playcount     | integer                                        |    NO    |    NO    |                                              |
| last_played   | string                                         |   YES    |    NO    |                                              |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/system_preferences.xml)

### timeline

This gets a user's timeline

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `username` is deprecated and will be removed in **API9**.

| Input    | Type    | Description                                       | Optional |
|----------|---------|---------------------------------------------------|---------:|
| 'filter' | string  | Username of the user for whom to get the timeline |       NO |
| 'limit'  | integer | Maximum number of results to return               |      YES |
| 'since'  | integer | UNIXTIME()                                        |      YES |

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `activity` list.

| Field    | Type                                             | Nullable | Optional | Notes                                          |
|----------|--------------------------------------------------|:--------:|:--------:|------------------------------------------------|
| activity | array&lt;[ActivityObject](#friends_timeline)&gt; |    NO    |    NO    | see [ActivityObject](#friends_timeline) fields |

Each `activity` entry ([ActivityObject](#friends_timeline)):

| Field       | Type                        | Nullable | Optional | Notes                                  |
|-------------|-----------------------------|:--------:|:--------:|----------------------------------------|
| id          | string                      |    NO    |    NO    |                                        |
| date        | integer                     |    NO    |    NO    |                                        |
| object_type | string                      |   YES    |    NO    |                                        |
| object_id   | string                      |    NO    |    NO    |                                        |
| action      | string                      |    NO    |    NO    |                                        |
| user        | [UserSummaryObject](#users) |    NO    |    NO    | see [UserSummaryObject](#users) fields |
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/timeline.xml)

### toggle_follow

This follow/unfollow a user

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `username` is deprecated and will be removed in **API9**.

| Input    | Type   | Description                             | Optional |
|----------|--------|-----------------------------------------|---------:|
| 'filter' | string | Username of the user to follow/unfollow |       NO |

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
Existing art is replaced unless you send overwrite=0, which keeps whatever is already there.

**ACCESS REQUIRED:** 75 (Catalog Manager)

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `id` is deprecated and will be removed in **API9**.

| Input       | Type    | Description       | Optional |
|-------------|---------|-------------------|---------:|
| 'filter'    | string  | $object_id        |       NO |
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

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `id` is deprecated and will be removed in **API9**.

| Input    | Type   | Description | Optional |
|----------|--------|-------------|---------:|
| 'filter' | string | $artist_id  |       NO |

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

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `id` is deprecated and will be removed in **API9**.

| Input    | Type   | Description                     | Optional |
|----------|--------|---------------------------------|---------:|
| 'type'   | string | `song`, `artist`, `album`       |       NO |
| 'filter' | string | $artist_id, $album_id, $song_id |       NO |

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

**NOTE** There was an error in documentation listing `id` as a valid parameter. (`id` will work in Ampache 7.9.0 / API 6.9.1+)

| Input    | Type   | Description                        | Optional |
|----------|--------|------------------------------------|---------:|
| 'filter' | string | $object_id                         |       NO |
| 'id'     | string | Alias of `filter` (Ampache 7.9.0+) |      YES |

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

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `url` is deprecated and will be removed in **API9**.

| Input    | Type   | Description                                                   | Optional |
|----------|--------|---------------------------------------------------------------|---------:|
| 'filter' | string | Full Ampache URL from server, translates back into a song XML |       NO |

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `song` list.

| Field       | Type                             | Nullable | Optional | Notes                          |
|-------------|----------------------------------|:--------:|:--------:|--------------------------------|
| total_count | integer                          |    NO    |    NO    |                                |
| md5         | string                           |    NO    |    NO    |                                |
| song        | array&lt;[SongObject](#song)&gt; |    NO    |    NO    | see [SongObject](#song) fields |

Each `song` entry ([SongObject](#song)):

| Field                 | Type                                           | Nullable | Optional | Notes                                        |
|-----------------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id                    | string                                         |    NO    |    NO    |                                              |
| title                 | string                                         |   YES    |    NO    |                                              |
| name                  | string                                         |   YES    |    NO    |                                              |
| artist                | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| artists               | array&lt;[NamedReference](#namedreference)&gt; |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| album                 | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| albumartist           | [NamedReference](#namedreference)              |    NO    |   YES    | see [NamedReference](#namedreference) fields |
| disk                  | integer                                        |    NO    |    NO    |                                              |
| disksubtitle          | string                                         |   YES    |    NO    |                                              |
| track                 | integer                                        |    NO    |    NO    |                                              |
| filename              | string                                         |   YES    |    NO    |                                              |
| genre                 | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| playlisttrack         | integer                                        |    NO    |    NO    |                                              |
| time                  | integer                                        |    NO    |    NO    |                                              |
| year                  | integer                                        |    NO    |    NO    |                                              |
| format                | string                                         |   YES    |    NO    |                                              |
| stream_format         | string                                         |   YES    |    NO    |                                              |
| bitrate               | integer                                        |   YES    |    NO    |                                              |
| stream_bitrate        | integer                                        |   YES    |    NO    |                                              |
| rate                  | integer                                        |    NO    |    NO    |                                              |
| mode                  | string                                         |   YES    |    NO    |                                              |
| mime                  | string                                         |   YES    |    NO    |                                              |
| stream_mime           | string                                         |   YES    |    NO    |                                              |
| url                   | string                                         |    NO    |    NO    |                                              |
| size                  | integer                                        |    NO    |    NO    |                                              |
| mbid                  | string                                         |   YES    |    NO    |                                              |
| art                   | string                                         |   YES    |    NO    |                                              |
| has_art               | boolean                                        |    NO    |    NO    |                                              |
| flag                  | boolean                                        |    NO    |    NO    |                                              |
| rating                | integer                                        |   YES    |    NO    |                                              |
| averagerating         | number                                         |   YES    |    NO    |                                              |
| playcount             | integer                                        |    NO    |    NO    |                                              |
| last_played           | string                                         |   YES    |    NO    |                                              |
| catalog               | string                                         |    NO    |    NO    |                                              |
| composer              | string                                         |   YES    |    NO    |                                              |
| channels              | integer                                        |   YES    |    NO    |                                              |
| comment               | string                                         |   YES    |    NO    |                                              |
| license               | string                                         |   YES    |    NO    |                                              |
| publisher             | string                                         |   YES    |    NO    |                                              |
| language              | string                                         |   YES    |    NO    |                                              |
| lyrics                | string                                         |   YES    |    NO    |                                              |
| replaygain_album_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_album_peak | number                                         |   YES    |    NO    |                                              |
| replaygain_track_gain | number                                         |   YES    |    NO    |                                              |
| replaygain_track_peak | number                                         |   YES    |    NO    |                                              |
| r128_album_gain       | number                                         |   YES    |    NO    |                                              |
| r128_track_gain       | number                                         |   YES    |    NO    |                                              |
| metadata              | object&lt;string, string&gt;                   |    NO    |   YES    |                                              |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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

**NOTE** For privacy, send `password` in a form or JSON request body rather than the query string. Query-string support for `password` is deprecated and will be removed in **API9**.

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

**NOTE** `filter` is available in Ampache 7.9.0 and higher. `username` is deprecated and will be removed in **API9**.

| Input    | Type   | Description | Optional |
|----------|--------|-------------|---------:|
| 'filter' | string |             |       NO |

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `playlist` list.

| Field       | Type                                     | Nullable | Optional | Notes                                  |
|-------------|------------------------------------------|:--------:|:--------:|----------------------------------------|
| total_count | integer                                  |    NO    |    NO    |                                        |
| md5         | string                                   |    NO    |    NO    |                                        |
| playlist    | array&lt;[PlaylistObject](#playlist)&gt; |    NO    |    NO    | see [PlaylistObject](#playlist) fields |

Each `playlist` entry ([PlaylistObject](#playlist)):

| Field           | Type                           | Nullable | Optional | Notes                                  |
|-----------------|--------------------------------|:--------:|:--------:|----------------------------------------|
| id              | string                         |    NO    |    NO    |                                        |
| name            | string                         |   YES    |    NO    |                                        |
| owner           | string                         |   YES    |    NO    |                                        |
| user            | [UserSummaryObject](#users)    |    NO    |    NO    | see [UserSummaryObject](#users) fields |
| items           | array&lt;object&gt; \| integer |    NO    |    NO    |                                        |
| type            | string                         |   YES    |    NO    |                                        |
| art             | string                         |   YES    |    NO    |                                        |
| has_access      | boolean                        |    NO    |    NO    |                                        |
| has_collaborate | boolean                        |    NO    |    NO    |                                        |
| has_art         | boolean                        |    NO    |    NO    |                                        |
| flag            | boolean                        |    NO    |    NO    |                                        |
| rating          | integer                        |   YES    |    NO    |                                        |
| averagerating   | number                         |   YES    |    NO    |                                        |
| md5             | string                         |   YES    |    NO    |                                        |
| last_update     | integer                        |   YES    |    NO    |                                        |
| time            | integer                        |    NO    |    NO    |                                        |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `playlist` list.

| Field       | Type                                     | Nullable | Optional | Notes                                  |
|-------------|------------------------------------------|:--------:|:--------:|----------------------------------------|
| total_count | integer                                  |    NO    |    NO    |                                        |
| md5         | string                                   |    NO    |    NO    |                                        |
| playlist    | array&lt;[PlaylistObject](#playlist)&gt; |    NO    |    NO    | see [PlaylistObject](#playlist) fields |

Each `playlist` entry ([PlaylistObject](#playlist)):

| Field           | Type                           | Nullable | Optional | Notes                                  |
|-----------------|--------------------------------|:--------:|:--------:|----------------------------------------|
| id              | string                         |    NO    |    NO    |                                        |
| name            | string                         |   YES    |    NO    |                                        |
| owner           | string                         |   YES    |    NO    |                                        |
| user            | [UserSummaryObject](#users)    |    NO    |    NO    | see [UserSummaryObject](#users) fields |
| items           | array&lt;object&gt; \| integer |    NO    |    NO    |                                        |
| type            | string                         |   YES    |    NO    |                                        |
| art             | string                         |   YES    |    NO    |                                        |
| has_access      | boolean                        |    NO    |    NO    |                                        |
| has_collaborate | boolean                        |    NO    |    NO    |                                        |
| has_art         | boolean                        |    NO    |    NO    |                                        |
| flag            | boolean                        |    NO    |    NO    |                                        |
| rating          | integer                        |   YES    |    NO    |                                        |
| averagerating   | number                         |   YES    |    NO    |                                        |
| md5             | string                         |   YES    |    NO    |                                        |
| last_update     | integer                        |   YES    |    NO    |                                        |
| time            | integer                        |    NO    |    NO    |                                        |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `video` list.

| Field       | Type                               | Nullable | Optional | Notes                            |
|-------------|------------------------------------|:--------:|:--------:|----------------------------------|
| total_count | integer                            |    NO    |    NO    |                                  |
| md5         | string                             |    NO    |    NO    |                                  |
| video       | array&lt;[VideoObject](#video)&gt; |    NO    |    NO    | see [VideoObject](#video) fields |

Each `video` entry ([VideoObject](#video)):

| Field         | Type                                           | Nullable | Optional | Notes                                        |
|---------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id            | string                                         |    NO    |    NO    |                                              |
| title         | string                                         |   YES    |    NO    |                                              |
| mime          | string                                         |   YES    |    NO    |                                              |
| resolution    | string                                         |   YES    |    NO    |                                              |
| size          | integer                                        |    NO    |    NO    |                                              |
| genre         | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| time          | integer                                        |    NO    |    NO    |                                              |
| url           | string                                         |    NO    |    NO    |                                              |
| art           | string                                         |   YES    |    NO    |                                              |
| has_art       | boolean                                        |    NO    |    NO    |                                              |
| flag          | boolean                                        |    NO    |    NO    |                                              |
| rating        | integer                                        |   YES    |    NO    |                                              |
| averagerating | number                                         |   YES    |    NO    |                                              |
| playcount     | integer                                        |    NO    |    NO    |                                              |
| last_played   | string                                         |   YES    |    NO    |                                              |
<!-- GENERATED:RESPONSE:END -->

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

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a single object.

| Field         | Type                                           | Nullable | Optional | Notes                                        |
|---------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id            | string                                         |    NO    |    NO    |                                              |
| title         | string                                         |   YES    |    NO    |                                              |
| mime          | string                                         |   YES    |    NO    |                                              |
| resolution    | string                                         |   YES    |    NO    |                                              |
| size          | integer                                        |    NO    |    NO    |                                              |
| genre         | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| time          | integer                                        |    NO    |    NO    |                                              |
| url           | string                                         |    NO    |    NO    |                                              |
| art           | string                                         |   YES    |    NO    |                                              |
| has_art       | boolean                                        |    NO    |    NO    |                                              |
| flag          | boolean                                        |    NO    |    NO    |                                              |
| rating        | integer                                        |   YES    |    NO    |                                              |
| averagerating | number                                         |   YES    |    NO    |                                              |
| playcount     | integer                                        |    NO    |    NO    |                                              |
| last_played   | string                                         |   YES    |    NO    |                                              |
<!-- GENERATED:RESPONSE:END -->

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

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

The `status` command reports the player state instead of a boolean.

| Field     | Type   | Nullable | Optional | Notes       |
|-----------|--------|:--------:|:--------:|-------------|
| localplay | object |    NO    |    NO    | `{command}` |
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/localplay.xml)

[Example (status)](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/localplay%20\(status\).xml)

### localplay_songs

Get the list of songs in your localplay instance

This method takes no additional parameters.

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Returns a `localplay_songs` list.

| Field           | Type                                                 | Nullable | Optional | Notes                                              |
|-----------------|------------------------------------------------------|:--------:|:--------:|----------------------------------------------------|
| localplay_songs | array&lt;[LocalplaySongObject](#localplay_songs)&gt; |    NO    |    NO    | see [LocalplaySongObject](#localplay_songs) fields |

Each `localplay_songs` entry ([LocalplaySongObject](#localplay_songs)):

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

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/localplay_songs.xml)

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

* return

<!-- GENERATED:RESPONSE:BEGIN -->
> **XML structure:** serialised inside a `<root>` element. Each object is an element
> (e.g. `<song>`) with `id` as an *attribute*; nested objects are child elements (also
> carrying an `id` attribute), array/list fields are emitted as *repeated* elements,
> booleans are `0`/`1`, and text values are wrapped in CDATA. Field names match the JSON
> model below, but element nesting/repetition differs from the JSON representation.

Depends on the `method` parameter: `play` returns the stream url, `vote`/`devote` return the applied method and its result, and `playlist` returns the current democratic song list.

**[DemocraticPlayResponse](#democratic)**

Returned by `method=play`: the stream URL of the democratic playlist.

Returned by `method=play`: the stream URL of the democratic playlist.

| Field | Type   | Nullable | Optional | Notes |
|-------|--------|:--------:|:--------:|-------|
| url   | string |    NO    |    NO    |       |

**[DemocraticVoteResponse](#democratic)**

Returned by `method=vote` and `method=devote`.

Returned by `method=vote` and `method=devote`.

| Field  | Type    | Nullable | Optional | Notes |
|--------|---------|:--------:|:--------:|-------|
| method | string  |    NO    |    NO    |       |
| result | boolean |    NO    |    NO    |       |

**[DemocraticSongsResponse](#democratic)**

Returns a `song` list.

| Field | Type                                             | Nullable | Optional | Notes                                          |
|-------|--------------------------------------------------|:--------:|:--------:|------------------------------------------------|
| song  | array&lt;[DemocraticSongObject](#democratic)&gt; |    NO    |    NO    | see [DemocraticSongObject](#democratic) fields |

Each `song` entry ([DemocraticSongObject](#democratic)):

| Field         | Type                                           | Nullable | Optional | Notes                                        |
|---------------|------------------------------------------------|:--------:|:--------:|----------------------------------------------|
| id            | string                                         |    NO    |    NO    |                                              |
| title         | string                                         |   YES    |    NO    |                                              |
| artist        | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| album         | [NamedReference](#namedreference)              |    NO    |    NO    | see [NamedReference](#namedreference) fields |
| genre         | array&lt;[GenreReference](#genrereference)&gt; |    NO    |    NO    | see [GenreReference](#genrereference) fields |
| track         | integer                                        |    NO    |    NO    |                                              |
| time          | integer                                        |    NO    |    NO    |                                              |
| format        | string                                         |   YES    |    NO    |                                              |
| bitrate       | integer                                        |   YES    |    NO    |                                              |
| mime          | string                                         |   YES    |    NO    |                                              |
| url           | string                                         |    NO    |    NO    |                                              |
| size          | integer                                        |    NO    |    NO    |                                              |
| art           | string                                         |   YES    |    NO    |                                              |
| has_art       | boolean                                        |    NO    |    NO    |                                              |
| rating        | integer                                        |   YES    |    NO    |                                              |
| averagerating | number                                         |   YES    |    NO    |                                              |
| playcount     | integer                                        |    NO    |    NO    |                                              |
| vote          | integer                                        |    NO    |    NO    |                                              |
<!-- GENERATED:RESPONSE:END -->

* throws

```XML
<root><error></root>
```

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/democratic%20\(play\).xml)

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/democratic%20\(vote\).xml)

[Example](https://raw.githubusercontent.com/ampache/python3-ampache/api6/docs/xml-responses/democratic%20\(playlist\).xml)

## Shared reference objects

<!-- GENERATED:SHARED-REFS:BEGIN -->
Objects referenced by the field tables above (as `see <name> fields`) that no single method response documents on its own — the shared reference shapes and a few payloads carried inside another response.

### CollectionGroupObject

One group of collection members. `object_type` names the type and the property of the same name carries that type's own objects, e.g. `{"object_type": "album", "album": [...]}`.

| Field       | Type   | Nullable | Optional | Notes |
|-------------|--------|:--------:|:--------:|-------|
| object_type | string |    NO    |    NO    |       |

### FolderBrowseItem

| Field         | Type    | Nullable | Optional | Notes |
|---------------|---------|:--------:|:--------:|-------|
| id            | string  |    NO    |    NO    |       |
| object_type   | string  |    NO    |    NO    |       |
| title         | string  |   YES    |    NO    |       |
| parent        | integer |    NO    |    NO    |       |
| path          | string  |   YES    |    NO    |       |
| art           | string  |   YES    |    NO    |       |
| has_art       | boolean |    NO    |    NO    |       |
| play_url      | string  |    NO    |    NO    |       |
| rating        | integer |   YES    |    NO    |       |
| averagerating | integer |   YES    |    NO    |       |

### FolderBrowseNode

| Field   | Type                                               | Nullable | Optional | Notes                                            |
|---------|----------------------------------------------------|:--------:|:--------:|--------------------------------------------------|
| id      | string                                             |    NO    |    NO    |                                                  |
| title   | string                                             |   YES    |    NO    |                                                  |
| parent  | integer                                            |   YES    |    NO    |                                                  |
| path    | string                                             |   YES    |    NO    |                                                  |
| catalog | integer                                            |    NO    |    NO    |                                                  |
| items   | array&lt;[FolderBrowseItem](#folderbrowseitem)&gt; |    NO    |    NO    | see [FolderBrowseItem](#folderbrowseitem) fields |

### GenreReference

| Field | Type   | Nullable | Optional | Notes |
|-------|--------|:--------:|:--------:|-------|
| id    | string |    NO    |    NO    |       |
| name  | string |    NO    |    NO    |       |

### IndexReferenceObject

| Field | Type   | Nullable | Optional | Notes |
|-------|--------|:--------:|:--------:|-------|
| id    | string |    NO    |    NO    |       |
| type  | string |    NO    |    NO    |       |

### LocalplayStatusObject

Player state. The exact fields come from the configured Localplay controller (MPD, VLC, XBMC, UPnP, HTTPQ), so only `repeat` and `random` are guaranteed - the API coerces those two to booleans. The rest are what that controller reports.

| Field        | Type    | Nullable | Optional | Notes |
|--------------|---------|:--------:|:--------:|-------|
| state        | string  |    NO    |   YES    |       |
| volume       | string  |    NO    |   YES    |       |
| repeat       | boolean |    NO    |    NO    |       |
| random       | boolean |    NO    |    NO    |       |
| track        | integer |    NO    |   YES    |       |
| track_title  | string  |    NO    |   YES    |       |
| track_artist | string  |    NO    |   YES    |       |
| track_album  | string  |    NO    |   YES    |       |

### NamedReference

| Field    | Type   | Nullable | Optional | Notes |
|----------|--------|:--------:|:--------:|-------|
| id       | string |    NO    |    NO    |       |
| name     | string |   YES    |    NO    |       |
| prefix   | string |   YES    |    NO    |       |
| basename | string |   YES    |    NO    |       |
<!-- GENERATED:SHARED-REFS:END -->
