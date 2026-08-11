# API CHANGELOG

## API 8.0.0

This version is being developed for Ampache8 (`develop` branch) **only** and is not yet released.

API version **8** joins the concurrent live surfaces (3/4/5/6 — version 7 remains unused/unsupported), built on the `MethodInterface`/DI method pattern.

### Added (800000)

* ALL
  * Request parameters for `POST`/`PUT`/`PATCH`/`DELETE` may now be supplied in a JSON body (`Content-Type: application/json`), in addition to the existing query-string and form-encoded (`application/x-www-form-urlencoded`) support
  * New API version `8` added to `Api::API_VERSIONS`; `Api::DEFAULT_VERSION` bumped `6` → `8`
  * New `Api8` method surface (132 methods) under `src/Module/Api/Method/Api8/`, implemented against `MethodInterface` with dedicated `Json8_Data`/`Xml8_Data` output classes
  * `ApiOutputInterface` (and its `JsonOutput`/`XmlOutput` implementations) reworked onto a single version-parameterized method per concept — `albums(int $apiVersion, ...)`, `error(int $apiVersion, ...)`, `podcastEpisodes()`, `setCount()`, `setLimit()`, `setOffset()`, `success()`, `writeEmpty()` — replacing the previous pattern of a separate `xxx()`/`xxx6()` method pair per API version
* `album`/`albums`/`podcast_delete`/`podcast_episodes` (API3, API4, API5)
  * Converted from legacy static methods to the `MethodInterface` pattern (matching the API6/API8 conversion above); the existing `filter` presence and object-exists checks are preserved, only the error codes changed (see `ALL (internal)` under Changed)
* REST
  * New `folders` action (`Folders8Method`) for browsing the catalog's virtual folder tree; `filter` takes either a folder id or a path name, so REST paths `folders`, `folders/{folder_id}` and `folders{path}` all reach it
  * New `playlist_remove` action (`PlaylistRemove8Method`)
* `upload` (API8 only)
  * New `upload` action adds a media file to the catalog named by the `upload_catalog` preference, so uploading is no longer web-only
  * The file is sent either as a multipart form field named `upl`, or as the raw request body with `filename` naming it
  * Optional `license`, `artist_id`, `artist_name`, `album_id` and `album_name` behave exactly as the web upload form does, including refusing an artist or album owned by another user
  * Requires the `allow_upload` preference and the access level named by `upload_access_level`; a file that fails to be added is removed from the catalog directory again
* `collection` (API8 only)
  * New actions `collections`, `collection`, `collection_items`, `collection_create`, `collection_edit`, `collection_delete`, `collection_add` and `collection_remove`. A collection is a hand-curated list of objects of any type, so it is the way to curate anything a playlist cannot hold; the members are not restricted to media
  * `collection_create` takes an optional `object_type` that pins the collection to one type, after which `collection_add` refuses anything else. Leave it out for a mixed collection
  * A collection is **ordered**, and the order is part of the data. `collection_items` returns the members as one flat list under `contents` in curated order, each entry carrying its `track` (1-based position), its `track_id` (the membership row) and its `object_type`, with that type's own object nested under a property of the same name. A grouped-by-type response cannot express the order of a mixed collection, so it is not used. The scalar `items` remains the total member count and is not reduced by `offset`/`limit`
  * `collection_edit` gains paired `items`/`tracks` parameters that reorder members exactly the way `playlist_edit` does — each pair puts one member at one position — so a partial or a whole reorder is one call. Because a collection is heterogeneous, each entry in `items` carries its type as `object_type:object_id`
  * `collection_add` appends to the end, so it never disturbs the order of what is already there
  * A collection you cannot see reports *not found* rather than *access denied*, so a private collection's existence is not confirmed to a stranger
  * Duplicate members are governed by the user's existing `unique_playlist` preference rather than by a collection-specific rule or a database constraint; it is off by default, so **a collection may hold the same object twice by default**, matching playlists. With it on, `collection_add` refuses a repeat with an error instead of silently doing nothing
  * `collection_remove` accepts a `track` position as well as an `id`/`object_type` pair, because a position is the only unambiguous address once duplicates are possible. A position removes exactly one member; an object removes every member pointing at it. Either way the remaining positions close up, so they stay dense and 1-based. `collection_remove` still does not error on a non-member
  * Both name the member's type `object_type` rather than `type`, because the REST path already spends `type` on the resource name and the two would collide in the same query string
  * REST paths `collections`, `collections/{collection_id}` and `collections/{collection_id}/items`
* `playlist_folder` (API8 only)
  * New actions `playlist_folders`, `playlist_folder`, `playlist_folder_items`, `playlist_folder_create`, `playlist_folder_edit`, `playlist_folder_delete`, `playlist_folder_add` and `playlist_folder_remove`. A playlist folder organises playlists, smartlists and collections into a tree of arbitrary depth
  * The tree is **private to each user**, and where a list sits belongs to the pair of you and that list rather than to the list itself. So you may file another user's public playlist into your own folder without changing anything for them, and two users can file the same playlist in different places
  * A list is in exactly one of your folders at a time: filing one that is already filed moves it rather than copying it
  * **The root is implicit and holds every list you can see that has not been filed elsewhere.** Nothing is stored when a playlist is created, so it appears at your root immediately, and it reappears there when it is taken out of a folder. Address the root as `0` or `/`
  * `playlist_folders` returns the whole tree as one flat list; rebuild the hierarchy from each folder's `parent`, where `0` is the root. A folder belonging to another user reports *not found* rather than *access denied*, so a stranger cannot probe for one
  * `filter` takes either a folder id or a name path, so REST paths `playlist-folders/{playlist_folder_id}` and `playlist-folders/Rock/Live` both reach the same folder. A folder name may not contain a `/` for that reason, and must be unique among its siblings — the comparison is case-insensitive, so `Rock` and `rock` collide
  * `sort_order` is client-assigned and shared by a parent's folders and its filed lists, so the two interleave in one ordering space; ties are broken by name. Positions are left exactly as sent rather than renumbered, so gaps are preserved
  * `playlist_folder_edit` refuses a move into the folder's own subtree, which would otherwise detach the branch
  * `playlist_folder_delete` refuses a folder that still holds a child folder or a filed list; the lists themselves are never deleted with it, so a folder is emptied by moving its contents out first
  * `playlist_folder_items` returns the members as one flat list under `contents`, each entry carrying its `sort_order` and its `object_type` (`playlist`, `smartlist` or `collection`) with that type's own object nested under a property of the same name
  * REST paths `playlist-folders`, `playlist-folders/{playlist_folder_id}`, `playlist-folders{path}` and `playlist-folders/{playlist_folder_id}/items`
* `sonic_match` (API8 only)
  * New action (`SonicMatch8Method`) returning songs that sound like the song in `filter`, each carrying a `similarity` score. Similarity comes from analysing the audio, which needs a sonic-analysis plugin, so with none enabled the request is refused (`4703`) rather than answered with an empty list. The score shares the OpenSubsonic `sonicMatch` scale — 0.0-1.0 where 1.0 is the same recording, and -1 when the backend gives no comparable score — so a client reads the same number from either API. REST path `songs/{song_id}/sonic-match`
* `random` (API8 only)
  * New action (`Random8Method`) that picks a random object and redirects (302) to its stream url — mirrors `stream`'s params (`bitrate`/`format`/`offset`/`stats`, song only). API8 only: API6 is shared with Ampache7, which does not serve it
  * `type` takes `album`, `album_artist`, `album_disk`, `artist`, `catalog`, `favorite`, `genre`, `label`, `playlist`, `podcast_episode`, `rating`, `search`, `song`, `song_artist` or `video`; a container type resolves to a random song from within it
  * `genre` matches the song's own tag, as a song browse does — an album or artist tag is not inherited. `label` accepts either the artist or the album association recorded in `label_asso`
  * `favorite` picks from the songs you have flagged, and `rating` from the songs you have rated. Both read the song's own flag or rating; a flagged album does not make its songs favourites
  * **NOTE** `favorite` and `rating` read `filter` as a flag or star value rather than an object id — `favorite`: `1` or omitted for flagged, `0` for not flagged; `rating`: `1`-`5` for that many stars or more, `0` for unrated, omitted for any rated song
  * `filter` names that container. `artist` accepts either artist credit, where `song_artist` and `album_artist` narrow it to one
  * **NOTE** `filter` is read against the table named by `type`, and those id spaces overlap — an album id and an album_disk id of the same number are different objects
* `download` (API8 only)
  * New `zip` parameter: when `type`/`filter` identify a container object (`album`, `artist`, `playlist`, `podcast`) and zipping is enabled (`ZipHandlerInterface::isZipable()`), downloads the whole container as a zip instead of a single stream redirect — reuses the same `ZipHandlerInterface` used by the `batch.php` GUI download
* `share_create` (API8)
  * Object `type` validated via `LibraryItemEnum::tryFrom()` against `Share::VALID_TYPES`, checked after the `playlist`/`smartlist` → `search` remap
* OpenAPI / response schemas
  * `docs/openapi.json` now defines `components.schemas` for every v8 data type (`album`, `song`, `artist`, `playlist`, `podcast`, `podcast_episode`, `video`, `genre`, `label`, `live_stream`, `catalog`, `license`, `share`, `bookmark`, `user`, `song_tag`, the per-type `deleted_*` items, and the `browse`/`list`/`now_playing`/`activity`/`shout` wrappers) and wires each into its `200` response, replacing the placeholder `type: object`; every field documents its type and whether it is optional/nullable
  * `docs/API-JSON-methods.md` and `docs/API-XML-methods.md` gain a generated per-method response field table (field, type, nullable, optional) with links between related objects
  * New `docs/openapi-6.json`, a spec pinned to API6 for contract testing a single version. It documents only the surface Ampache7 and Ampache8 both serve: no API8-only paths (`/folder`, `/folders`, `/playlists/{playlist_id}/remove`), no `/random` (API8 only), no error status codes (API3-6 always return HTTP 200 with the error in the body), response schemas from the `Json6_Data` builders, and `maxbitrate` documented as kbps
  * New `tests/Module/Api/Api6SpecConformanceTest.php` locks that contract: it fails if a documented path leaves `Api6::METHOD_LIST`, if an error status code appears, if a `$ref` dangles, or if an object's fields drift from the matching `Json6_Data` builder. This is what stops API6 changing between Ampache7 and Ampache8
  * `Json6_Data::songs_array()` gains the `@return` array-shape docblock the other builders already carry. It records the two real differences from API8: `catalog` is an int (a string in API8) and metadata fields are top-level keys (nested under `metadata` in API8)
  * Response schemas for `democratic`, `handshake`, `ping`, `playlist_generate`, the preference endpoints (`user_preference`, `user_preferences`, `system_preference`, `system_preferences`), `url_to_song` and `system_update`
  * `stream` and `download` are documented as the `302` redirect they actually return (`download` also documents the `zip=1` archive body), and `get_art` as an image body, instead of an undescribed `200`. Each names the headers it sets: `Location` for the redirects, `Content-Type`/`Content-Disposition` for the zip, and `Content-Type`/`Content-Length`/`Access-Control-Allow-Origin` for art
  * Response schemas for `search`, `stats`, `get_similar`, `followers`, `following`, `localplay`, `get_lyrics`, `get_external_metadata`, `playlist_hash`, `player`, `register` and every create endpoint (`bookmark_create`, `catalog_create`, `live_stream_create`, `playlist_create`, `podcast_create`, `share_create`) - 145 operations now carry a schema, leaving only the binary media endpoints undocumented
  * New `resources/scripts/api-docs/check_openapi_examples.py`, which fails when an inline example in `docs/openapi.json` contradicts the schema its operation is wired to
  * Response schemas for `album_disks`/`album_disk`/`album_disk_songs` and `localplay_songs`, generated from the `Json8_Data::album_disks_array()` and `LocalPlay::get()` docblocks; every documented path now carries an `x-rpc-mappings` entry
  * `random` documents the `filter` parameter and all seven `type` values it accepts (`artist`, `album`, `playlist`, `podcast_episode`, `search`, `song`, `video`); only three were listed
* `album_disk` (API8 only)
  * New `album_disks` action returning the disks of an album (`filter` is the album id, the counterpart of `album_songs`)
  * New `album_disk` action returning a single album disk, and `album_disk_songs` returning the songs on one disk
  * `album_disk` accepted by `index`, `list`, `browse`, `stats` and `get_art`. `rate` and `flag` already accepted it
  * Album disks are the browsing unit whenever the per-user `album_group` preference is off, so a client can now reach the same objects the web interface shows. `albums` and `album` are unchanged and never vary with that preference
* `song` (API8 only)
  * Song responses carry `bpm`, the tagged beats per minute, read on scan into the new `song_data`.`bpm` column
  * The value keeps the fraction a detection tool wrote (`133.4`), so it is a float rather than an int
  * New `bpm` rule for `advanced_search`, numeric, song only
* `mood` (API8 only)
  * Song responses carry `mood`, a list of `{id, name}` objects shaped exactly like `genre`, read on scan from the file tags into the new `mood` and `mood_map` tables
  * The list is empty until a catalog has been scanned, and stays empty for files carrying no mood tag
  * New `mood` rule for `advanced_search`, text, on song, album and artist
  * New `mood` browse filter on the song, album, artist and video browses, taking a mood id
  * Album and artist moods are derived from their songs, so they answer *which moods are on this album* rather than naming a tag of their own
* `advanced_search` (API4 and above), `search_group` (API6 and API8)
  * A `user` search takes new `name`, `fullname` and `email` rules; `name` matches the username, full name or email at once, the same three columns the `user` browse searches
  * A `user` search accepts `title` as `name`, so the generic rule `search_group` sends returns matching users instead of every account
* `shares` (API4 and above)
  * `filter`/`exact` and the `cond` text filters match the title of the shared object; the share browse ignored every filter it was sent before
  * New `name` sort ordering shares by the title of the object they point at, with `title` as an alias. It is the sort `shares` asks for by default, so the default order changes from insertion order to alphabetical
  * New `object_type` and `user` filters, taking a share type (`album`, `album_disk`, `artist`, `playlist`, `podcast`, `podcast_episode`, `search`, `song`, `video`) and a user id
* REST
  * Multi-word resources and actions may be spelled with a dash anywhere in a path (`album-disks/{id}/songs`); the dashed form is folded onto the canonical snake_case action by a single rule rather than a per-name alias list
  * New `albums/{album_id}/disks`, `album-disks/{album_disk_id}`, `album-disks/{album_disk_id}/songs`, plus `art`/`flag`/`rate`/`search`/`stats` on `album-disks`
  * New `localplay/songs` path for the existing `localplay_songs` action
  * New object-scoped `browse` paths that need no catalog: `albums/{album_id}/browse`, `album-disks/{album_disk_id}/browse`, `artists/{artist_id}/browse` and `podcasts/{podcast_id}/browse`. The existing `catalogs/{catalog_id}/browse/...` paths still work and keep the catalog restriction
  * Each object gains `catalog`, the id of the catalog it belongs to, in both JSON and XML. `song`, `podcast_episode`, `live_stream`, `folder` and the `deleted_*` items already carried it, so every response object backed by a table with a catalog now reports one
  * `artist` is deliberately left out: an artist reaches its catalogs through `catalog_map` and has no single one to report, so `Artist::getCatalogId()` returns `0`

### Changed (800000)

* ALL
  * Passing secrets in the query string is deprecated for privacy (query values leak into server/proxy logs and browser history): the `password` on `register`/`user_create`/`user_edit`/`catalog_add` and the `handshake` `auth` key should be sent in a request body (or, for `auth`, the `Authorization: Bearer` header) — the query string keeps working for them, it is simply no longer the recommended way
  * Version rollover logic reworked for the new 5-version lineup: requests pinned to a disabled API6 now roll forward to API8 (version 7 is explicitly rejected as unsupported)
  * API8 JSON/XML output now sets real HTTP status codes for errors and empty results (`404` for empty, `Api::getHttpCode()`-mapped codes for errors) — API3–6 always returned HTTP 200 with the error embedded in the response body
  * API8 uses updated action names for a few methods present under legacy naming in API3/4: `index`/`list` (not `get_indexes`), `playlist_add` (not `playlist_add_song`), `user_edit` (not `user_update`)
* `browse` (API8 only)
  * browse: `catalog` is now an optional filter on `album_artist`, `artist`, `album`, `album_disk` and `podcast` instead of a required parameter. Send it to restrict the children to one catalog, omit it to get them from every catalog you can see. An album, disk or podcast belongs to a single catalog and an artist reaches its catalogs through `catalog_map`, so the parent object never needed a catalog to be addressed. API6 keeps the parameter mandatory, because Ampache7 serves that version too
* `download` (API8 only)
  * Converted from a legacy static method to the `MethodInterface` pattern to support the new zip response; existing `song`/`podcast_episode`/`search`/`playlist` single-item redirect behavior is unchanged
* `user_edit` (API8 only)
  * user_edit: `maxbitrate` is now bits per second (`320000`) instead of kbps (`320`), so every rate argument in the API uses the same unit. API6 and older keep kbps
* ALL (units)
  * The unit of every rate argument is now documented. `bitrate` on `download`/`random`/`stream` has always been bits per second and `maxbitrate` on `user_edit`/`user_update` was kbps, but neither was written down in `docs/openapi.json` or the method tables
  * Subsonic/OpenSubsonic `maxBitRate` is unchanged and stays kbps, as the Subsonic 1.16.1 specification requires
* ALL (internal)
  * `JsonOutput`/`XmlOutput` no longer fall back to API8 formatting for an unrecognized API version/method/format combination (e.g. JSON for API3, which was never supported) — this now throws instead of silently rendering as API8. Some v3/v4 error paths that used ad-hoc numeric error codes now use the same `ErrorCodeEnum`-based codes API5/6/8 already use, for consistency
* API8 (JSON/XML parity)
  * v8 JSON and XML now return a matching field set for each object; several inconsistencies were unified: XML `bookmark`/`share` owner is now `<owner>` (was `<user>`), `video` and `democratic` no longer emit a duplicate `<name>` (use `<title>`), deleted podcast episodes use `<podcast>` (was a mislabeled `<played>`), and `song_tags` emits the full fixed field set in both formats
  * JSON `user` adds `link` (profile url, already present in XML) and returns `fullname` on your own `/me` request; JSON `song_tags` adds the song `id`; the `users` list uses a bare `{ "user": [...] }` envelope with no `total_count`/`md5`

### Removed (800000)

* REST
  * `GET smartlists/search` is no longer documented. `smartlist` is not one of `Search::VALID_TYPES`, so the path could only ever return a `Bad Request` error. Use `playlists/search` (searches cover both playlists and smartlists)

### Fixed (800000)

* `playlists`/`list`/`index`/`stats` (API4 and above)
  * `sort=last_count` answered with an error instead of a list. The combined playlist and smartlist browse never carried the column it sorts on, though both tables have it
* `stats` (API3)
  * A `type` other than `newest`, `highest`, `frequent`, `recent` or `flagged` answered with an error instead of random albums, unless a `limit` was sent
* `followers` (API4 and above)
  * The returned ids are the accounts doing the following. They were the ids of the follow records, which line up with real accounts only by coincidence, so `following` and `followers` disagreed about the same relationship
* ALL
  * Sorts a browse implemented but never offered, so asking for one did nothing: `id` on broadcast, private message, share, shoutbox and wanted, `release_date` on video, `title` on genre and mood, and `genre` on genre
  * The private message browse sorts on `from_user` and takes the `equal`/`exact_match` filters it already implemented, and the follower browse sorts on `username`, `last_seen` and `create_date`
  * `wanted` dropped its `username` sort, which named a column the table does not have
* API5, API6
  * advanced_search: `type=album_disk` returned album disk ids rendered as songs, so a client read a disk id as a song id. Neither version has an album disk formatter, so both now return an empty result instead. `search` is affected too, being an alias. API8 returns the album disks. **NOTE** the same fix landed in Ampache7, which serves these versions as well
  * API3 and API4 are unchanged: neither validates the search `type` at all, so every unsupported type there already falls through to the song output
* API8
  * `Json8_Data`/`Xml8_Data` skip an object that no longer exists rather than returning it as an entry of empty fields, and a missing object no longer ends the list it appeared in. **NOTE** the same fix landed in Ampache7 for API3-6, which it serves as well
* `preference_edit` (API6 and API8)
  * preference_edit: `default=1` now writes the server default — the system user's value, and the value a new account is seeded from — instead of the calling user's own value, and reports that value back. Existing accounts are still only changed by `all=1`. **NOTE** the same fix landed in Ampache7, which serves API6 as well
* `live_stream_create`/`live_stream_edit` (API6 and API8)
  * live_stream_create, live_stream_edit: `codec` no longer has its digits stripped, so `mp3` is stored as `mp3` instead of `mp`. A radio station created or edited through the API before this keeps the truncated codec until it is set again. **NOTE** the same fix landed in Ampache7, which serves API6 as well

## API 6.9.2 Build 4

This version is being released for Ampache7 **only**

To ensure that there are no issues with clients checking for single int versions
we will keep on 6.9.x and resume build number versioning until Ampache 8

**NOTE** API8 has been removed from the codebase for Ampache 7.

### Fixed (692004)

* ALL
  * An object that no longer exists is left out of the response instead of being returned as an entry of empty fields under its id. Affects genres, radio stations, and the album, song, artist and playlist entries in `index`, `indexes` and `search_group`
  * A missing object no longer ends the list it appeared in, so the objects after it are returned rather than silently dropped
* `preference_edit` (API6)
  * preference_edit: `default=1` now writes the server default — the system user's value, and the value a new account is seeded from — instead of the calling user's own value, and reports that value back. Existing accounts are still only changed by `all=1`. **NOTE** the same fix landed in Ampache8, which serves API6 as well

## API 6.9.2 Build 3

To ensure that there are no issues with clients checking for single int versions
we will keep on 6.9.x and resume build number versioning until Ampache 8

**NOTE** API8 has been removed from the codebase for Ampache 7.

### Changed (692003)

* ALL
  * A request without an `action` is treated as a `ping` instead of failing with a session error (e.g. opening the API url in a browser)

### Removed (692003)

* API8
  * Leftover version constants in the base `Api` class (`$version` and `$version_numeric` were still set to `8.0.0`/`800000`). They are commented out to keep backports simple
  * `Api::server_details()`; the API8 copy of the handshake and ping counts. API3-6 call their own version of this function

### Fixed (692003)

* API5 and API6
  * advanced_search: `album_disk` is not a searchable type and returns an empty result instead of an error

## API 6.9.2 Build 2

This version is being released for Ampache7 **only**

To ensure that there are no issues with clients checking for single int versions
we will keep on 6.9.x and resume build number versioning until Ampache 8

**NOTE** API8 has been removed from the codebase for Ampache 7.

### Added (692002)

* ALL
  * Allow APIKey Authorization header
  * REST command and path changes
* REST
  * `catalogs/{catalog_id}/add`, `clean`, `update` and `verify` as aliases of `catalog_action` with the matching `task`
* API6
  * Add `time` to all Playlist and Smartlist responses

### Changed (692002)

* ALL
  * flag: Use the `UserFlag::is_valid()` function for object type validation
  * rate: Use the `Rating::is_valid()` function for object type validation
  * parameter_exists: Parameters sent with an empty value (e.g. `filter=`) are treated as missing
* `update_art` (API4, API5 and API6)
  * Existing art is replaced unless you send `overwrite=0`
* API6
  * Error messages are no longer translated

### Removed (692002)

* API8 will not be used in Ampache 7 releases

### Fixed (692002)

* ALL
  * Version and docstring inconsistencies between API versions
  * Empty object lookups now report the parameter that failed instead of `empty`
  * A `version` lower than 3 (e.g. `version=2`) rolled up to no version at all instead of the oldest enabled one
  * democratic: `vote` returns the real vote count for each song. It was counted from the item's position in the response instead of its `track_id`, so the number was meaningless
  * friends_timeline: An empty result returned a `total_count`/`md5` envelope that neither the populated response nor `timeline` uses. It now returns `activity: []`
* API5, API6 and API8
  * labels, label: XML serialised each item as `<license>` instead of `<label>`
  * search_rules: XML emitted an empty `<widget/>` for every rule that isn't a select, dropping the control type the JSON response carries
* REST
  * `preferences/{preference_name}` returned the whole preference list and ignored the name
  * `POST {type}/{id}/share` was documented as resolving to `share` (fetch a share); it resolves to `share_create`, as the code has always done
* API4
  * update_from_tags: Not found check was inverted so valid objects returned an error
  * XML list responses were not sliced by `offset` and `limit` (e.g. `users`)
* API5
  * get_bookmark: Not found check was inverted so valid objects returned an error
  * XML list responses were not sliced by `offset` and `limit` (e.g. `bookmarks`, `users`)
  * album: A missing or empty `filter` reported an empty `Bad Request:` message with the wrong error type
* API6
  * Version wasn't bumped
  * podcast_episode: JSON response was missing the full episode object
  * XML and JSON list responses were not sliced by `offset` and `limit`
  * user_preference: Returned the system value instead of the calling user's preference
  * catalog_action: Not found error didn't name the catalog id
  * localplay: `status` could fail on controllers that don't report `repeat` and `random`
  * playlists, smartlists: `api_hidden_playlists` was ignored when set to `0`
  * playlists: JSON `time` could be a string instead of an integer
  * shares: An empty result was keyed `shares` instead of `share` like the populated response (API5 was already correct)
  * last_shouts: An empty result returned a `total_count`/`md5` envelope the populated response does not use. It now returns `shout: []` (API5 was already correct)

## API 6.9.2 Build 1

This version is being released for Ampache7 **only**

To ensure that there are no issues with clients checking for single int versions
we will keep on 6.9.x and resume build number versioning until Ampache 8

**NOTE** API8 has been added to the code but is not enabled for use.

### Added (692001)

* API8
  * New Method: playlist_remove (remove items from a playlist)
  * Typing for `array<mixed>` JSON objects
  * Let `search_songs` use `rule_1_input` to match other search methods
* API6
  * Let `search_songs` use `rule_1_input` to match other search methods

### Changed (692001)

* API8
  * Method `playlist_remove_song` is deprecated and will be removed in **API9** (Use playlist_remove)
  * Deprecated parameters that will be removed in **API9**
    * catalog_action: parameter `catalog`, use `filter`
    * catalog_file: parameter `catalog`, use `filter`
    * catalog_folder: parameter `catalog`, use `filter`
    * flag: parameter `id`, use `filter`
    * rate: parameter `id`, use `filter`
    * record_play: parameter `id`, use `filter`
    * update_art: parameter `id`, use `filter`
    * update_artist:_info parameter `id`, use `filter`
    * update_from_tags: parameter `id`, use `filter`
    * url_to_song: parameter `url`, use `filter`
    * download: parameter `id`, use `filter`
    * get_art: parameter `id`, use `filter`
    * stream: parameter `id`, use `filter`
    * localplay: parameter `oid`, use `filter`
    * last_shouts: parameter `username`, use `filter`
    * timeline: parameter `username`, use `filter`
    * user_delete: parameter `username`, use `filter`
    * user_edit: parameter `username`, use `filter`
  * Optional parameters
    * playlist_add: Use 'song' as default `type`

### Fixed (692001)

* ALL
  * download: Type for `format` listed as int
  * stream: Type for `format` listed as int
  * update_art: Docstring had `overwrite` as mandatory
  * preference object `id` not cast to string
  * forced string for prefix when it should be null
  * video objects being inserted into double video arrays
* API6
  * JSON lists could be doubl filtered incorrectly splicing results
  * album, albums, podcast_delete and podcast_episodes output sent to JSON8 classes
  * download: Type for `format` listed as int
  * toggle_follow using incorrect logic for username

### Removed (692001)

* API8
  * Remove `get_indexes`
  * Remove `playlist_add_song`
  * Remove `user_update`

## API 6.9.1 Build 15

This version is being released for Ampache7 **only**

To ensure that there are no issues with clients checking for single int versions
we will keep on 6.9.x and resume build number versioning until Ampache 8

**NOTE** API8 has been added to the code but is not enabled for use.

### Added (691015)

* API8
  * Add API8 to REST htaccess file
  * Restructure the backend code to allow API8

### Changed (691015)

* API6
  * Move API6 classes to allow for API8

### Fixed (691015)

* API6
  * Bad `podcast_update` function name
  * REST rewrite rules

## API 6.9.1 Build 14

This version is being released for Ampache7 **only**

To ensure that there are no issues with clients checking for single int versions
we will keep on 6.9.x and resume build number versioning until Ampache 8

### Added (691014)

* API6
  * Allow REST calls to block GET requests for sensitive calls (e.g. `register`)

### Changed (691014)

* API6
  * Make `filter` optional for `podcast_episodes`
  * Add `song` for `id` in `playlist_add` to match `playlist_add_song`
  * Fallback to `bookmark` on empty `type` in `bookmark_delete`
  * Decode HTML for Song lyrics on output
* API5
  * Decode HTML for Song lyrics on output

### Fixed (691014)

* API6
  * Bad `podcast_update` function name
  * REST rewrite rules

## API 6.9.1 Build 13

This version is being released for Ampache7 **only**

To ensure that there are no issues with clients checking for single int versions
we will keep on 6.9.x and resume build number versioning until Ampache 8

### Added (691013)

* API6
  * followers: Add `filter` as an alias of `username`
  * following: Add `filter` as an alias of `username`
  * Add `smartlist` as a type for `share_create`
  * Add `smartlist` as a type for `get_art`
  * Add alias `podcast_update` for `update_podcast`

### Changed (691013)

* REST rewrite rules are still not stable
* Username methods now all allow `user_id` as well as username lookups

### Fixed (691013)

* API6
  * timeline would only return timeline for your user

## API 6.9.1 Build 12

This version is being released for Ampache7 **only**

To ensure that there are no issues with clients checking for single int versions
we will keep on 6.9.x and resume build number versioning until Ampache 8

### Added (691012)

* ALL
  * Split REST processes into separate applications
  * Normalize object types parameter (`type`) to allow resource paths
  * Get parsed body from `PATCH`, `PUT` and `DELETE`
* API6
  * Add alias `catalog_add` for `catalog_create`
  * Add alias `podcast_update` for `update_podcast`

## API 6.9.1 Build 11

This version is being released for Ampache7 **only**

To ensure that there are no issues with clients checking for single int versions
we will keep on 6.9.x and resume build number versioning until Ampache 8

### Added (691011)

* API6
  * New Method: smartlist (Return a single Smartlist)
  * New Method: smartlists (Return smartlists based on filters)
  * New Method: smartlist_songs (Return the songs for a smartlist)
  * New Method: smartlist_delete (Delete a smartlist)
  * preference_edit: Add `default` parameter (Set as system default for new and public users)
  * handshake: Add `streamtoken` to responses
  * ping: Add `streamtoken` to responses
  * url_to_song: Add `filter` as an alias of `url`
  * catalog_action: Add `filter` as an alias of `catalog`
  * catalog_file: Add `filter` as an alias of `catalog`
  * catalog_folder: Add `filter` as an alias of `catalog`
  * flag: Add `filter` as an alias of `id`
  * record_play: Add `filter` as an alias of `id`
  * update_art: Add `filter` as an alias of `id`
  * update_artist_info: Add `filter` as an alias of `id`
  * update_from_tags: Add `filter` as an alias of `id`
  * update_podcast: Add `id` as an alias of `filter`
  * download: Add `filter` as an alias of `id`
  * get_art: Add `filter` as an alias of `id`
  * stream: Add `filter` as an alias of `id`
  * rate: Add `filter` as an alias of `id`
  * localplay: Add `filter` as an alias of `oid`
  * last_shouts: Add `filter` as an alias of `username`
  * timeline: Add `filter` as an alias of `username`
  * toggle_follow: Add `filter` as an alias of `username`
  * user: Add `filter` as an alias of `username`
  * user_delete: Add `filter` as an alias of `username`
  * user_edit: Add `filter` as an alias of `username`

### Fixed (691011)

* ALL
  * podcast_update documentation incorrect so add a fallback for the id parameter

## API 6.9.0 Build 10

This version is being released for Ampache7 **only**

To ensure that there are no issues with clients checking for single int versions
we will keep on 6.9.x and resume build number versioning until Ampache 8

### Added (690010)

* API6
  * New Method: get_lyrics (Return Database lyrics or search with plugins by Song id)

## API 6.9.0

This version is being released for Ampache7 **only**

### Added (690000)

* ALL
  * Support POST requests for all methods

### Fixed (690000)

* ALL
  * Localplay methods might not complete when pulling status from missing controllers

## API 6.8.0

This version is being released for Ampache7 **only**

### Added (680000)

* ALL
  * Support regular handshake and ping actions with a Bearer Token
* API6
  * New Method: search_rules (Get a list of valid search rules per search type)
  * user_playlists: Add include parameter to get song data
  * user_smartlists: Add include parameter to get song data
  * toggle_follow: Return errors when the users aren't found
  * deprecation warnings from tag methods in the main API handler
* API5
  * toggle_follow: Return errors when the users aren't found

### Changed (680000)

* API6
  * browse: don't require `catalog` when browsing a `catalog`

### Fixed (680000)

* ALL
  * user_update: incorrect parameter check for `filter` instead of `username`
  * get_indexes: XML data may generate extra root elements
  * user_create: validate `disable` bool with make_bool
  * playlists: validate `show_dupes` bool with make_bool
  * get_bookmark: validate `include` and `all` bools with make_bool
  * flag: validate `include` bool with make_bool
  * bookmarks: validate `include` bool with make_bool
  * bookmark: validate `include` bool with make_bool
  * bookmark_create: validate `include` bool with make_bool
  * bookmark_edit: validate `include` bool with make_bool
API6
  * `album` and `albums` include didn't always work correctly
  * `artist` and `artists` include fixes
API5
  * user_edit: incorrect parameter check for `filter` instead of `username`
  * bookmark: Bookmark is a valid `type`
API3
  * genres: fallback function missing
  * genre: fallback function missing
  * genre_songs: fallback function missing
  * genre_artists: fallback function missing
  * genre_albums: fallback function missing

## API 6.7.3

This version is being released for Ampache7 **only**

### Added (673000)

* API6
  * Add mbid_group to `album` data responses

### Fixed (673000)

* ALL
  * handshake: Check if auth is sent as a valid session and don't try to create a new one

## API 6.7.2

This version is being released for Ampache7 **only**

### Changed (672000)

* Header auth using a bearer token will return valid sessions on handshake and ping
* Header auth for other methods will hide the session behind an MD5 hash of the username (previous behaviour)

## API 6.7.1

This version is being released for Ampache7 **only**

### Added (671000)

* API6
  * get_art: Extend `type` parameter to include 'label', 'live_stream', 'search', 'user', 'video'

## API 6.7.0

This version is being released for Ampache7 **only**

### Added (670000)

* API6
  * New Method: song_tags (Read and return all file and enabled plugin tags for the song)
  * get_art: return error 404 on bad size dimensions

### Changed (670000)

* ALL
  * localplay will not clear mpd playlists when stopped if `clear=0`
  * Ensure lowercase parameters are set on localplay calls

### Fixed (670000)

* ALL
  * get_art: Correctly size and scale art responses
* API6
  * catalog_add: typing for adding beets catalog

## API 6.6.8

This version is being released for Ampache7 **only**

This is a massive update to the code typing and backend quality without changing function.

### Changed (668000)

* ALL
  * Typed parameters, properties and arrays for all API versions
  * Ensure nullable input is allowed to be null in the code
  * Make sure size dimensions for `get_art` calls are valid and split correctly
* API6
  * Empty results on `list` calls will send an empty response
* API5
  * `playlist_generate` with `flag` not checking value
  * `catalog_file` catch exception on add_file error

### Fixed (668000)

* ALL
  * Democratic methods vote array not correct in all cases

## API 6.6.7

This version is being released for Ampache7 **only**

### Added (667000)

* API6
  * get_external_metadata: Get metadata from external plugins. (Useful for scripting)
  * Add `is_hidden` status and `merge` genres into genre data objects

### Changed (667000)

* ALL
  * stats: Allow `limit` -1 for no limit. (0 falls back to `popular_threshold` value)
* API6
  * user_preference
    * Add `has_access` to show whether you can change the preference
    * Add `values` to the response for all `special` preferences
  * user_preferences
    * Add `has_access` to show whether you can change the preference
    * Add `values` to the response for all `special` preferences
  * system_preference
    * Add `has_access` to show whether you can change the preference
    * Add `values` to the response for all `special` preferences
  * system_preferences
    * Add `has_access` to show whether you can change the preference
    * Add `values` to the response for all `special` preferences

### Fixed (667000)

* XML
  * Index keyed_array data on int indexed arrays

## API 6.6.6

### Fixed (666000)

* ALL
  * Not checking auth on User lookup

## API 6.6.5

Merge update from Ampache7.

### Added (665000)

* Search
  * Add `disk_count` to Album & AlbumDisk search types
  * Add `no_license` to Song search

### Fixed (665000)

* API6
  * handshake: Downgrade version 7 calls to 6
  * get_indexes: Respect 'api_hidden_playlists'
  * index: Respect 'api_hidden_playlists'
  * list: Respect 'api_hidden_playlists'
  * playlists: Respect 'api_hidden_playlists'
* API5
  * get_indexes: Respect 'api_hidden_playlists'
  * playlists: Respect 'api_hidden_playlists'

## API 6.6.4

Merge update from Ampache7.

This update has counting updates to allow faster responses on larger results

### Added (664000)

* API6
  * Use set_count function on data classes to speed up counting response totals
  * Validate `website` for `user_edit`
  * Use split search for search methods
    * advanced_search / search
    * search_songs
    * user_update
    * playlist_generate
* API5
  * Use split search for search methods
    * advanced_search
    * search_songs
    * user_update
    * playlist_generate
* API4
  * Use split search for search methods
    * advanced_search
    * search_songs
    * user_update
    * playlist_generate
* API3
  * Use split search for search methods
    * advanced_search
    * search_songs

### Changed (664000)

* Rename API-CHANGELOG.md => CHANGELOG-API.md

## API 6.6.3

**NOTE** NO CHANGE

## API 6.6.2

### Added (662000)

* API6
  * Add `stats` parameter to stream and download methods (If false disable stat recording when playing the object)
  * Respect `api_always_download` in stream and download methods
  * Add sorting to stats calls
  * add `user` object to playlist responses (owner of the playlist)

### Fixed (662000)

* ALL
  * index: Artist index not showing albums

## API 6.6.1

This release keeps parity between Ampache7 releases by backporting the updated code.

### Added (661000)

* API6
  * Add maximum ID properties to `handshake` and `ping` (with auth) responses for media types
    * `max_song`, `max_album`, `max_artist`, `max_video`, `max_podcast`, `max_podcast_episode`
  * flag: add `date` as a parameter (set the time for your flag)

### Changed (661000)

* lost_password: deny access in simple_user_mode

## API 6.6.0

Like with `total_count`, we've added an md5sum of the results (called `md5`) in responses

This is useful for recording whether you need to update or change results.

Inconsistency with the return of object arrays and single items have been fixed and docs updated.

### Added (660000)

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

### Changed (660000)

* API6
  * playlist_edit: Add songs if you're a collaborator and ignore edit parameters if you fail has_access check
  * catalog_add: Do not return an object. (We return a single item)
  * bookmark_create: Do not return an object. (We return a single item)

### Fixed (660000)

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

### Added (650000)

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

### Changed (650000)

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

### Fixed (650000)

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

### Added (640000)

* API6
  * Downgrade any API7 calls to API6 [wiki](/docs/help/troubleshooting/ampache7-for-admins#there-is-no-api7-only-api6-and-5-4-and-3-too)
  * New Method: player (Inform the server about the state of your client player)
    * Returns `now_playing` state on completion
  * download: add `bitrate` parameter
  * playlists: add `include` parameter (**note** this can be massive and slow when searches are included)

### Changed (640000)

* API6
  * Do not translate API `errorMessage` strings ampache.org (`https://ampache.org/api/api-errors`)

### Fixed (640000)

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

### Added (631000)

* API6
  * New Method: now_playing (Get what is currently being played by all users.)

## API 6.3.0

### Added (630000)

* API6
  * New Method: search_group (return multiple object types from a single set of search rules)
  * New Method: search (alias for advanced_search)
  * New Method: user_playlists (return user playlists and does not include smartlists)
  * New Method: user_smartlists (return user smartlists (searches) and does not include playlists)
  * New Method: playlist_add (add songs to a playlist, allowing different song parent types)
  * New Method: index (replaces get_indexes with a simpler list of id's. children can be included)
  * Add `has_art` parameter to any object with an `art` url
  * Add avatar url to user objects

### Changed (630000)

* API6
  * playlist_add_song: depreciated (Use playlist_add); removed in **API8**
  * share_create: add more valid types ('playlist', 'podcast', 'podcast_episode', 'video')
  * user: make username optional

### Fixed (630000)

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

**NOTE** NO CHANGE

## API 6.2.0

### Added (620000)

* API: Allow non-expiring user sessions when using a header token
* Allow endless api sessions. (You should start using http header auth to hide these)

### Changed (620000)

* Set default API version to 6 (was 5)
* Allow raising and lowering response version on ping to **any** version
* API6
  * Return error on handshake version failure

### Fixed (620000)

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

### Added (610000)

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

### Changed (610000)

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

### Fixed (610000)

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

### Added (603000)

* API5::playlist_songs: Add `random` to get random objects filtered by limit

### Fixed (603000)

* ALL
  * Fixed Bearer token auth on all methods
  * handshake: runtime errors with bad username
  * handshake: Don't error on empty data counts
  * ping: Don't error on empty data counts
* Api6
  * list: searches were missing from playlists
  * browse: XML returned a list instead of a browse object

## API 6.0.2

**NOTE** NO CHANGE

## API 6.0.1

### Changed (601000)

* API6 XML
  * get_similar: return song objects to match json

### Fixed (601000)

* API6
  * user_preference: returned array instead of object
  * system_preference: returned array instead of object
  * preference_create: returned array instead of object
  * preference_edit: returned array instead of object

## API 6.0.0

Stream token's will let you design permalinked streams and allow users to stream without re authenticating to the server. [wiki](/docs/old-information/ampache6-details#allow-permalink-user-streams)

### Added (600000)

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

### Changed (600000)

* Api6
  * Renamed `user_update` to `user_edit` (user_update still works in API6 and older; removed in **API8**)
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
* Api6::get_indexes: This method is depreciated (Use list instead); removed in **API8**

### Removed (600000)

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

### Fixed (600000)

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

### Fixed (562000)

* ALL
  * Require and set a valid version for `api_force_version`

## API 5.6.1

**NOTE** NO CHANGE

## API 5.6.0

### Fixed (560000)

* ALL
  * share_create and share_edit methods broken when setting expiry days
  * advanced_search methods were breaking with various offset and limits
  * playlists methods parameter 'exact' always ending up false
* Api5
  * update_art hardcoded url to artist
  * Typo in song bitrate xml

## API 5.5.7

### Changed (557000)

* Keep the original mime and bitrate on song objects instead of the transcoded value

## API 5.5.6

Fix various runtime errors and incorrect parameters for responses.

### Changed (556000)

* API browses all point to the Api class
* Use `FILTER_VALIDATE_IP` on ping calls

### Fixed (556000)

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

**NOTE** NO CHANGE

## API 5.5.4

### Fixed (554000)

* User count in Api::ping and Api::handshake was doubled
* Api3::stats method had incorrect recent parameters
* Ensure the output `bitrate` and `mime` are set for song objects

## API 5.5.3

**NOTE** NO CHANGE

## API 5.5.2

### Added (552000)

* advanced_search
  * Add `song_artist` as a search type (uses artist rules)
  * Add `album_artist` as a search type (uses artist rules)
  * Add `song_genre`, `mbid_artist`, `mbid_song` to album search
  * Add `song_genre`, `mbid_album`, `mbid_song` to artist search
  * Add `possible_duplicate_album` to song search

### Fixed (552000)

* advanced_search
  * unable to retrieve song_artist or album_artist results

## API 5.5.1

**NOTE** NO CHANGE

## API 5.5.0

This will likely be the last 5.x API release. API6 will be a continuation of API5 and not be a significant change like the 4->5 transition.

### Added (550000)

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

### Fixed (550000)

* API4::get_indexes podcast_episode was encoding into the API5 object
* API4::share_create was unable to share when using lowercase types
* advanced_search
  * Added missing `song` (was `song_title`) to album searches

## API 5.4.1

### Added (541000)

* Include `lyrics` in Song objects
* advanced_search
  * Add `file` to album and artist search
  * Add `track` to song search
  * Add `summary` to artist search

## API 5.4.0

### Added (540000)

* advanced_search
  * Add `file` to album and artist search

## API 5.3.3

### Added (533000)

* advanced_search
  * Add `song_title` to album search
  * Add `album_title` and `song_title` to artist search
  * Add `orphaned_album` to song search

### Fixed (533000)

* Api4::record_play had the `user` as mandatory again
* After catalog actions; verify songs with an orphaned album which you won't be able to find in the ui

## API 5.3.2

**NOTE** NO CHANGE

## API 5.3.1

**NOTE** NO CHANGE

## API 5.3.0

### Added (530000)

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

### Added (521000)

* API5
  * The docs for errors have been extended for the type when returned

### Changed (521000)

* API5
  * Return the xml total_count of playlists based on hide_search preference

### Fixed (521000)

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

Check out the docs for multi API support at ampache.org (`https://ampache.org/api`)

**note** JSON didn't exist for API3 so all json requests from API3 calls will revert to API5

### Added (520000)

* Support for API3, API4 and API5 responses including PHP8 support (keeps original tag calls)
* API5
  * playlists: add parameter `show_dupes` if true ignore 'api_hide_dupe_searches' setting
  * get_art: add parameter `fallback` if true return default art ('blankalbum.png') instead of an error
* API4
  * playlists: add parameter `show_dupes` if true ignore 'api_hide_dupe_searches' setting
* API3
  * Added genre calls as an alias to tag functions to match API4 and API5

### Fixed (520000)

* Session and user id identification and errors from that
* API5
  * playlists: sql for searches wasn't filtering
  * Art URL for searches was malformed
* API4
  * Art URL for searches was malformed
* API3
  * democratic: This method was broken in API3 and never worked correctly

## API 5.1.1

### Fixed (511000)

* Access to podcast_episode_delete
* stats calls with an offest and limit
* advanced_search calls with an offset and limit

## API 5.1.0

### Added (510000)

* NEW API functions
  * Api::live_stream (get a radio stream by id)
  * Api::live_streams
* Api::stream Added type 'podcast_episode' ('podcast' to be removed in Ampache6)
* Add 'time' and 'size' to all podcast_episode responses

### Changed (510000)

* live_stream objects added 'catalog' and 'site_url'
* stats: additional type values: 'video', 'playlist', 'podcast', 'podcast_episode'

### Fixed (510000)

* get_indexes: JSON didn't think live_streams was valid (it is)
* record_play: user is optional
* Bad xml tags in deleted functions
* scrobble: Add song_mbid, artist_mbid, album_mbid (docs have no '_' so support both)

## API 5.0.0

All API code that used 'Tag' now references 'Genre' instead

This version of the API is the first semantic version. "5.0.0"

### Added (500000)

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

### Changed (500000)

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

### Fixed (500000)

* catalog_file: Couldn't add files

## API 4.4.3

**NOTE** NO CHANGE

## API 4.4.2

### Fixed (442000)

* API::indexes Artist albums were being added incorrectly for XML
* Send back the full album name in responses

## API 4.4.1

### Fixed (441000)

* API::stats would not offset recent calls

## API 4.4.0

### Added (440000)

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

### Changed (440000)

* get_indexes: 'playlist' now requires include=1 for xml calls if you want the tracks
* Make filter optional in shares
* Api::podcast_episodes
  * "url" is now a play url (instead of a link to the episode)
  * "public_url" is now the old episode link

### Fixed (440000)

* Api::podcast_edit wasn't able to edit a podcast...
* Api::democratic was using action from localplay in the return responses
* get_indexes for XML didn't include podcast indexes
* Set OUTDATED_DATABASE_OK on image.php, play/index.php and share.php to stop blocking requests
* Don't limit sub items when using a limit (e.g return all podcast episodes when selecting a podcast)

### Deprecated (440000)

* Dropped in API 5.0.0
  * Api::get_indexes; stop including playlist track and id in xml by default
  * Album objects: "tracks" will only include track details. Use "songcount"
  * Artist objects: "albums", "songs" will only include track details Use "albumcount" and "songcount"

## API 4.3.0

### Changed (430000)

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

**NOTE** NO CHANGE

## API 4.2.5

**NOTE** NO CHANGE

## API 4.2.4

**NOTE** NO CHANGE

## API 4.2.3

**NOTE** NO CHANGE

## API 4.2.2

Minor bugfixes

### Added (422000)

* Api::advanced_search added parameter 'random' (0|1) to shuffle your searches

### Changed (422000)

* Remove spaces from advanced_search rule names. (Backwards compatible with old names)
  * 'has image' => 'has_image'
  * 'image height' => 'image_height'
  * 'image width' => 'image_width'
  * 'filename' => 'file' (Video search)

### Deprecated (422000)

* Search rules 'has image', 'image height', 'image width', 'filename'. (Removed in Ampache 5.0.0)

### Fixed (422000)

* Api::stream, Api::download Api::playlist_generate 'format' parameter was overwritten with 'xml' or 'json'
* Produce valid XML for playlist_generate using the 'id' format in XML

## API 4.2.1

No functional changes from 4.2.0

### Fixed (421000)

* Filter in "playlist" and "playlist_songs" fixed

## API 4.2.0

**API versions will follow release version and no longer use builds in the integer versions (e.g. 420000)**
API 5.0.0-release will be the first Ampache release to match the release string.

### Added (420000)

* JSON API now available!
  * Call xml as normal:
    * [http://music.com.au/server/xml.server.php?action=handshake&auth=APIKEY&version=420000]
  * Call the JSON server:
    * [http://music.com.au/server/json.server.php?action=handshake&auth=APIKEY&version=420000]
  * Example XML and JSON responses available at [github.com](https://github.com/ampache/python3-ampache/tree/master/docs)
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

### Changed (420000)

* Bump API version to 420000 (4.2.0)
* All calls that return songs now include `playlisttrack` which can be used to identify track order.
* `playcount` added to objects with a playcount.
* `license` added to song objects.
* Don't gather art when adding songs
* Added actions to catalog_action. 'verify_catalog' 'gather_art'
* API function "playlist_edit": added ability to edit playlist items
  * items  = (string) comma-separated song_id's (replace existing items with a new object_id) //optional
  * tracks = (string) comma-separated playlisttrack numbers matched to items in order //optional
* Random albums will get songs for all disks if album_group enabled

### Deprecated (420000)

* API Build number is depreciated (the last 3 digits of the api version)
  * API 5.0.0 will be released with a string version ("5.0.0-release")
  * All future 4.x.x API versions will follow the main Ampache version. (420000, 421000, 422000)
* total_count in the XML API is depreciated, but it keeps working.
  * XML can count objects the same was as a JSON array [https://www.php.net/manual/en/simplexmlelement.count.php]
* Genre in songs is depreciated, but it keeps working.
  * Use tag instead of genre, tag provides a genre ID as well as the name.

### Fixed (420000)

* Extra text in catalog API calls
* Don't fail the API calls when the database needs updating

## API 4.0.0 build 004

Bump API version to 400004 (4.0.0 build 004)

### Added (400004)

* Add Api::check_access to warn when you can't access a function

### Fixed (400004)

* Fix parameters using 0
* Get the correct total_count in xml when you set a limit
* Fix many XML formatting issues

## API 4.0.0 build 003

Bump API version to 400003 (4.0.0 build 003)

### Added (400003)

* user_numeric searches also available in the API. `http://ampache.org/api/api-xml-methods`

### Changed (400003)

* Api::playlist - filter mandatory
* Api::playlist_edit - filter mandatory. name and type now optional
* Api::user - Extend return values to include more user fields
* Playlist::create - Return duplicate playlist ID instead of creating a new one
* Do not limit smartlists based on item count (return everything you can access)
* Api/Database - Add last_count for search table to speed up access in API

### Removed (400003)

* Artist::check - Remove MBID from Various Artist objects

### Fixed (400003)

* Fix Song::update_song for label
* Fix Api issues relating to playlist access

## API 4.0.0 build 001

* Bump API version to 400002 (4.0.0 build 001)

### Added (400002)

* Documented the Ampache API `http://ampache.org/api/api-xml-methods`
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

### Changed (400002)

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
