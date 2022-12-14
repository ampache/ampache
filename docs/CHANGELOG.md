# CHANGELOG

## Ampache 5.5.6-release

This release imports the API code cleanup from Ampache Develop which has fixed a lot of data issues.

There will probably not be another big change to Ampache 5 as work has moved to Ampache6 but there will be more bugfix releases if needed.

### Changed

* Scrutinizer moved to php8.1

### Fixed

* Spotify art collector (**AGAIN**)
* get_now_playing `has_access` check
* Malformed HTML for regular users in preferences sidebar
* Missing translation on preferences sidebar
* Default catalog_filter group could be missing on a new install
* Gather genre tags when not an array
* Display webp images
* Check for a valid image extensions when uploading art

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

## Ampache 5.5.5-release

This release fixes up all the issues I created with the bad release files as well as an art search issue I missed until after the release.

### Fixed

* Set etag for image cache
* Spotify art collector
* Double scrub string in catalog search rules

## API 5.5.5

**NO CHANGE**

## Ampache 5.5.4-release

### Added

* Database 550005
  * Add `song_artist` and `album_artist` maps to catalog_map

### Changed

* Update catalog map tables based on the catalog action
* Force `b` and `n` for back, next in webplayer (was overwritten with `[` and `]`)

### Fixed

* Missing tables on a fresh install
* Not filtering song_artist on album_artist browses
* Don't use catalog_filter and rating_filter without a valid user
* Uploaded/Manual Album Artist maps on tag update
* Delete artist's from the catalog_map that don't have a song or album for that catalog
* Set correct transcode `bitrate` and `mime` for songs on play_url calls
* Save Track Order when viewing all the items
* Use cache_target for cached song cleanup (was hardcoded to mp3)
* RSS Feed generation with bad characters
* Don't spam the artist description for each song
* Show better Trending Dashboard section
* Subsonic
  * Art for artist index arrays was missing
* Search
  * SQL for Artist `catalog` searches
  * Make sure saved rules match the correct names on load
* CLI
  * Don't try to update a database when the connection fails

## API 5.5.4

### Fixed

* User count in Api::ping and Api::handshake was doubled
* Api3::stats method had incorrect recent parameters
* Ensure the output `bitrate` and `mime` are set for song objects

## Ampache 5.5.3-release

### Changed

* Update copyright year in footer.inc.php
* Localplay status and instance_fields function cleanup
* Update some docker files to match current images
* Allow adding streams to playlists (including rightbar)
* Shuffle the top 100 albums for Popular Dashboard section
* webplayer
  * Another code rework, remove the old 'original' list
  * Shuffle is an action instead of a state of the playlist

### Fixed

* Hidden Genres shouldn't have a catalog
* Streaming with certain parameters could not identify a session/user
* Should be counting podcast objects in stats
* Null artist->id on wanted pages
* Search
  * Album 'other_user' favorite searches
* Subsonic
  * Error if you didn't have data when using get_user_data
  * Response data might fall back to mp3 and not match the output format
  * Incorrect `playqueue_date` call for user playqueue
* webplayer
  * Reordering the list could lose track of items
  * Remove single item from list could create a weird list
  * Remove the final track when it's finished playing (if you've set that option)

## API 5.5.3

**NO CHANGE**

## Ampache 5.5.2-release

### Added

* Check for upload_catalog before showing upload pages
* Search
  * Class rework and many additional aliases, check the docs for [advanced_search](https://ampache.org/api/api-advanced-search)
  * Add `song_artist` as a search type (uses artist rules)
  * Add `album_artist` as a search type (uses artist rules)
  * Add `song_genre`, `mbid_artist`, `mbid_song` to album search
  * Add `song_genre`, `mbid_album`, `mbid_song` to artist search
  * Add `possible_duplicate_album` to song search
* webplayer
  * Code cleanup and attempt to make it a bit less confusing

### Changed

* Do not overwrite a custom Artist/Album when updating from tags
* Ignore case when comparing Genre
* Show an error on share create failures
* Pull some function back into the search class
* When searching without rules treat it like a browse and return results unfiltered

### Fixed

* Tmp_Playlist::get_items may not order by the playlist id
* Fix album time update when time is NULL
* Transcoding format could be ignored (`encode_player_webplayer_target`)
* Set the file extension based on expected transcode / download format
* Don't look at the transcode cache when downloading a raw file
* If you are transcoding redirect to the transcoded file
* Download stats for song, video, podcast_episode
* Set the file extension for urls on generation
* Don't overwrite artist art when searching for album art
* Retrieve song art from tags the same way they are found ('invalid' art)
* Searching from the search bar did not pickup up the rules for the search page
* Upload artist, album and license selection
* Don't show hidden Genres on object rows
* Video needs get_f_link function
* Playlists need to be in catalog_map table
* Insert Podcasts more often in catalog_map
* Subsonic basic auth may get filtered
* Don't filter auth in the PlayAction if sent
* Search
  * Correctly identify alias rule types
  * Bad SQL on 0 rating query for album/artist search
* webplayer
  * Desired transcode format not being respected
  * Video types missing from supported types
  * Playlist sorting issues

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

## Ampache 5.5.1

I made a mistake in the release string so we need a new point release already!

### Added

* Translation Updates August 2022
* Grouping for label search items

### Fixed

* Release version string is incorrect and will tell you you have updates if you use the release files
* Missing comma between label links on song pages

## API 5.5.1

**NO CHANGE**

## Ampache 5.5.0

Private catalogs have been given a lot of love. This feature allows you to assign a catalog to multiple users instead of just one.

Check out the [wiki](https://github.com/ampache/ampache/wiki/catalog-filters) for more information about this feature.

**NOTE** Any user that has a private catalog will have their own filter group created which includes all public catalogs

PHP8.1 has now been fixed up completely and is now fully supported.

### Added

* Update Copyright notice to 2022
* Added a new option 'Random Play' (shuffle) to playlists and smartlists
* Add 'Recently Skipped' to user pages
* Add Podcast Episodes to the browse pages and sidebar
* Translate podcast episode state and some other missing ones
* Allow using a smartplaylist in Democratic play
* Allow podcast_episode table sort by `time` and `state`
* Allow podcast table sort by `website` and `episodes`
* Database 550004
  * Add system preference `demo_use_search`, Use smartlists for base playlist in Democratic play
  * Add tables `catalog_filter_group` and `catalog_filter_group_map` for catalog filtering by groups
  * Add column `catalog_filter_group` to `user` table to assign a filter group
  * Migrate catalog `filter_user` settings to the `catalog_filter_group` table
  * Assign all public catalogs to the DEFAULT group
  * Drop table `user_catalog`
  * Remove `filter_user` from the `catalog` table
* Search
  * Added more missing groups to search type lists
  * Added missing `song` (was `song_title`) to album searches
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
* CLI
  * Add verify for podcast catalogs (fix time and size from tags)

### Changed

* Private catalogs have been migrated into [Catalog filters](https://github.com/ampache/ampache/wiki/catalog-filters)
* Interface cookies for the sidebar state have new names matching their page and group
* Made getID function required for library_item's
* Update codeql-analysis.yml to v2
* When streaming a Democratic or Random item, redirect to the result
* Hide 'is_true' boxes on search rows (you can't change it so why show it?)
* Hide action buttons from random and demo webplayer lists

### Fixed

* The cookies for the interface sidebar had multiple issues holding and restoring status
* Removed **A LOT** of FILTER_SANITIZE_STRING from code for PHP8.1
* Errors on empty values when loading the UI rows
* Lots of docstring and code issues
* Fixed up deleting plays (and now skips) on the user pages
* Sorting playlist, user and smartlist names in search rows
* SQL in get_tags when catalog_filter is disabled
* A lot of browse filters were missing for certain object types
* Don't try to load the playlist dialog from the webplayer when you can't add things
* When using random/Democratic play send the additional parameters to the actual media
* Respect play urls with transcode_to instead of format
* Updated example `docs/examples/inotifywait.sh`
* Podcast_episode browse may sent a camel case argument
* Null max_upload_size could still be counted as a limit
* Search
  * SQL might have connected AND and OR incorrectly
  * Metadata search might have badly parsed input
  * Added aliases for some of the confusing search types
* Subsonic
  * Checking parameters might return the error AND the empty response

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

## Ampache 5.4.1-release

### Added

* Put next (n) and back (b) shortcuts in the web_player
* Allow _ and % wildcards for hiding playlists (api_hidden_playlists)
* Missing translations on CLI strings
* Config version 62
  * Added webplayer_debug (was previously undocumented/hidden)
* Search
  * Add `track` to song search
  * Add `summary` to artist search
* CLI
  * New argument for cleanup:sortSongs `-w|--windows` Replace windows-incompatible strings with _
  * Add a table check function to admin:updateDatabase. This will repair missing tables/details

### Changed

* Only enforce `subsonic_always_download` for song objects
* Always insert podcast source urls. But mark them as skipped if out of date
* When adding a podcast feed, sync everything
* Don't trim search input (e.g. allow single spaces for search)

### Fixed

* web_player being unable to play different formats in some cases
* Playlist download button missing ID
* Truncate long podcast episode author lengths
* Incorrect link on the albums page
* Section on the information sidebar looking for the wrong cookie
* Bad verify mod time check
* SongSorter would get caught with % in your strings
* Rating Match plugin may overwrite album rating
* Artist getRandom using the wrong sql column name
* Pocast episode time regex

## API 5.4.1

### Added

* Include `lyrics` in Song objects
* advanced_search
  * Add `file` to album and artist search
  * Add `track` to song search
  * Add `summary` to artist search

## Ampache 5.4.0-release

### Added

* Translation Updates May 2022
* Search
  * Add `file` to album and artist search
* CLI
  * New argument for run:updateCatalog `-f|--find` Find missing files and print a list of filenames
  * New argument for cleanup:sortSongs `-f|--files` Rename files and keep them in the current folder
  * New argument for cleanup:sortSongs `-l|--limit` Limit how many moves to allow before stopping
  * New argument for cleanup:sortSongs `[catalogName]` Name of Catalog (optional)
* Database 540002
  * Index `title` with `enabled` on `song` table to speed up searching
  * Index `album` table columns; `catalog`, `album_artist`, `original_year`, `release_type`, `release_status`, `mbid`, `mbid_group`
  * Index `object_type` with `date` in `object_count` table

### Changed

* Moved to php-cs-fixer 3
* Update from tags now shows an 'Error' status if there was an issues reading the file

### Fixed

* SQL for random artist with mapping
* SQL for servers < 5.0.0 might try to insert into a missing table
* Respect grouping for song_count searches
* Autoplay in xbmc localplay and conform to localplay api
* Ungrouped albums were forced into groups
* Artists array should overwrite artist_mbid arrays that are smaller
* Some empty globals relating to user
* More work on the forked Jplayer playlist code when using `play last`
* DAAP play urls
* Single disk download links on group pages
* CLI
  * cleanup:sortSongs was broken (It actually works again)
  * cleanup:sortSongs removes incomplete copied files after failure

## API 5.4.0

### Added

* advanced_search
  * Add `file` to album and artist search

## Ampache 5.3.3-release

### Added

* Remove duplicates and order largest to smallest for art search
* Allow update_from_tags for a single Song from it's page
* Search
  * Add `song_title` to album search
  * Add `album_title` and `song_title` to artist search
  * Add `orphaned_album` to song search

### Changed

* Default art_search_limit raised to 15
* web_player shuffle improvements
  * Current selected track will become the first track and everything else shuffled below it
  * Playlist isn't automatically played so if a song was playing, it will continue to play

### Fixed

* Speed up stream_playlist generation by chunking into blocks
* Make sure there is an object_id to fill in update_530006
* Remove song mapping on delete
* Make sure podcast descriptions don't overfill the column
* Clean dead total wasn't returned on completion
* Searching for albums with '# Played' with grouping enabled with album_map
* Adding a new xbmc localplay
* Catalog type filter in get_top_sql
* Subsonic
  * Fixed the query searches (Again) based on the wildcards different clients may send
  * Song discNumber was sending the MAX disk instead of the actual disk
  * getPlayQueue doesn't change back to miliseconds from seconds

## API 5.3.3

### Added

* advanced_search
  * Add `song_title` to album search
  * Add `album_title` and `song_title` to artist search
  * Add `orphaned_album` to song search

### Fixed

* Api4::record_play had the `user` as mandatory again
* After catalog actions; verify songs with an orphaned album which you won't be able to find in the ui

## Ampache 5.3.2-release

Some QoL fixes here with some initial SubSonic, Search and that database column again!

### Added

* Look for orphaned maps to delete.
* Get server timezone for get_datetime (date_default_timezone_get())
* Allow deleting played activity from the ui and count using a function (Require: 100)

### Changed

* Updated the translation gathering process a little
* Organized the play/skip counting into it's own function
* Update artist from tags needs to update albums first
* Subsonic
  * Only search for song title instead of everything
  * Add starred to directory elements

### Fixed

* Format on an empty album would complain in the log
* Update from tags might not remove the old song artist
* Migrating to a new album would leave old album maps
* Artist search query with mapping was very slow
* Database column check not included in 5.3.1 correctly
* Subsonic
  * Get recently played
  * Fixed up search queries using "" (wrapping in quotes means exact search)

## API 5.3.2

**NO CHANGE**

## Ampache 5.3.1-release

There were a few reports of some databases missing an important column. This release makes sure it's there.

### Added

* Docker compose files to help create a local dev environment (read docker/README.md for more info)
* Added php8.1 to composer (**still considered unstable**)

### Changed

* Began rework of Subsonic modules

### Fixed

* Database missing rsstoken column in the user table
* gather-messages.sh was finding lots more strings than it needed
* Query sql with ambiguous ID
* New song import might not map all the artists
* Catalog query missing a comma

## API 5.3.1

**NO CHANGE**

## Ampache 5.3.0-release

This cycle we have added support for multiple Album and Song artists.

This allows multiple artists to be part of a single song/album object and is created from file tags.

Check out the [wiki](https://github.com/ampache/ampache/wiki/multi-artist) for more information about this feature.

The old and long ignored module [jPlayer](https://github.com/jplayer/jPlayer) has been forked into the base Ampache code.

There have been a few fixes and changes to the module to make the webplayer a lot better to use.

### Added

* Additional xhtml templates added
* Parse lots more WMA (ASF) file tags
* Add play next and play last to radio station rows
* Additional option for artist pages 'Show Artist Songs'
* Add some missing tag options for mpc files
* Allow manually syncing Artist name, year formed and place formed from musicbrainz (if it has an mbid)
* Notify and allow updating Plugins when updates are available
* You can now unhide a tag from the 'Hidden' page for Genres
  * This will delete previous merges but it will not retag your songs (update from tags to fix that)
* Config version 61
  * Add disable_xframe_sameorigin (allow disabling "X-Frame-Options: SAMEORIGIN")
  * Disable catalog_verify_by_time by default
* Database 530016
  * Create `artist_map` table and fill it with data
  * Create `album_map` table and fill it with data
  * Use `song_count` & `artist_count` using `album_map`
  * Drop id column from `catalog_map` table and alter object_type charset and collation
  * Alter `album_map` table charset and engine to MyISAM if engine set
  * Alter `artist_map` table charset and engine to MyISAM if engine set
  * Make sure `object_count` table has all the correct primary artist/album rows
  * Convert basic text columns into utf8 to reduce index sizes
  * Remove `user_activity` columns that are useless
  * Delete duplicate rows on `object_count`
  * Compact mbid columns back to 36 characters
  * Compact some `user` columns
  * enum `object_count`.`count_type`
  * Index data on object_count
  * Use a unique index on `object_count`
  * Compact `cache_object_count`, `cache_object_count_run` columns
  * Add `show_album_artist` and `show_artist` preferences to show/hide Sidebar Browse menu links
* Search
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

### Changed

* Clean up artists with a duplicate MBID (Use the lowest artist id)
* Delete cached recommendations instead of trying to update (Really slow)
* Artist::check uses MBID on lookups as well as name
* update_from_tags: Only update counts, tags and garbage collect after changes found
* Use albums instead of songs for catalog verify actions
* Expand search sidebar menu and collapse information without cookies
* Moved all the extended functions into the forked jplayer module
* Instead of skipping duplicate songs on import, disable them
* jPlayer (Webplayer):
  * Shuffle now follows the currently playing track (If playing)
  * Shuffle also does not track the old playlist so you can't undo a shuffle
* Subsonic
  * Check for art instead of always sending an art attribute

### Removed

* Search
  * removed mbid group sql from `possible_duplicate` and `possible_duplicate_album`

### Fixed

* VaInfo time for size/playtime_seconds
* Tag arrays for Mbid and Artists lookup
* Deleted item tables would not record some deletions
* Updating the artist name would always migrate data when not required
* Artist::check would always create an artist object with readonly set
* Genres would not update the parent (Song->Album->Artist) whan using update from tags
* Random sql that uses search rules
* Use configured Ampache temp directory in Seafile catalog
* Prepare media before update from tags (Seafile needs to download the file first)
* Seafile catalog checks for a local file before downloading it again
* Delete custom_metadata when removed from the object
* Artist Garbage Collection was way too slow
* Album and Artist count value sql
* Don't remove Genre tags when they have been merged (hidden) into a different tag
* Don't delete merged (hidden) Genres from the tag table
* Album song_artist_count not calculated correctly
* Grouping with mbid_group was missing making some albums not possible to view
* Display and hide of artist columns in some pages based on count
* Clean and verify would count totals based on all items instead of item type
* Missing strings from xhtml templates
* Album grouping for getAlbumSuite with null values
* Set ratings for all album disks (if grouping enabled) for ratings and flags
* Issues when you don't have an album artist tag
* Correctly set null values on library_item edits
* Search for song art might have sent a Song object
* Fix missing preference on musicbrainz plugin
* Disable/enable catalog
* jPlayer (Webplayer):
  * Fixed moving items in the playlist
  * Fixed adding after the current playing track
  * Fixed logic behind the index and order between the HTML and the JS lists
* Search
  * played search for album and artist was including your user in the results
  * other_user artist search sql
* Subsonic
  * Artist was missing starred status

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

## Ampache 5.2.1-release

### Added

* Translation Updates Jan 2022
* Count tables on create and delete actions
* Set allow-plugins in composer.json
* Improve description of rss feed to make each play more unique
* Wait 30 minutes between catalog updates before running update_counts
* On database connection failure, go to test.php
* Search
  * Added no_tag as a possible search item (song, album, artist)
  * Document the alias names of search rules (docs/API-advanced-search.md)
  * Add playlist and playlist_name search to artist types

### Changed

* AmpachePersonalFavorites: double the playlist title height
* Move get_f_link from playlist/search into playlist_object
* Make some functions that do the same thing use the same variable names
* Don't optimize tables when doing full_service catalog updates
* Use parameters in search queries instead of printing them into the query
* Logout action requires a session id now to log out
* Update mapping more often after catalog actions
* Album check function added mbid_group to lookup
* Support database upgrades from version 350008 (Ampache 3.5.4)
* Remove additional 'Details' string from song/video and radio pages
* Tag value is being extra filtered in the edit screen
* Hide the login link when using simple_user_mode and no auth is set **note** this does not stop you logging in with /login.php
* When not using auth, keep recording stats for system user plays
* Hide username column from Recently Played when not authed as a user

### Fixed

* Lowercase package names in composer
* CLI add user
* Demo Systemd timer files WantedBy
* Some missing garbage collection for some tables
* Phantom html table row in show_catalogs page
* Grouping albums with special characters
* Searching albums with special characters
* Lots more PHP8 runtime errors
* SQL for artists in catalog_map
* Typo in url for update_all_file_tags
* SQL error in database update 500013 (Not fatal)
* Don't garbage_collect tags after merging
* Create art URLs correctly when using rewrite rules and no auth
* Respect sidebar_light preference when no cookie is present
* Don't try to create users that already exist
* Add/Edit catalogs in the UI missing filter_user
* Search
  * possible_duplicate was grouping too much together
* Subsonic
  * Jukeboxcontrol didn't send an index to the client

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

## Ampache 5.2.0-release

Ampache 5.2.0 (and all future versions) now support multiple API versions. This means that you can send your handshake with a specific version (e.g. 390001, 440001 or 5.2.0) you will be sent API3, API4 and API5 responses in return.

To change from API3 to API5 you can send a ping with a new version parameter to update your session (or send goodbye to log off and start again.)

API3 is not recommended for use outside of running old applications and it is recommended that you turn off API versions you don't use.

### Added

* Get image from more tag types
* Translation Updates Nov 2021
* Added the ability to play random songs from a playlist with a play url
* Update AmpacheRatingMatch.php to allow writing ratings to file tags
  * Enable 'Save ratings to file tags when changed' on the plugin page
  * write_tags must be enabled in the config file
* Config version 59
  * Removed overwrite_tags (It doesn't do anything)
  * playlist_art now true by default
* Database 520005
  * Make sure preference names are always unique
  * Add ui options ('api_enable_3', 'api_enable_4', 'api_enable_5') to enable/disable specific API versions
  * Add ui option ('api_force_version') to force a specific API response (even if that version is disabled)
  * Add ui option ('show_playlist_username') Show playlist owner username in titles
  * Add ui option ('api_hidden_playlists') Hide playlists in Subsonic and API clients that start with this string
  * Add ui option ('api_hide_dupe_searches') Hide searchs in Subsonic and API clients with the same name as playlists (and both owned by you)

### Changed

* Don't try to return objects that aren't there sometimes
* Update catalog counts before returning API data
* Fix preferences for system and users after each update
* Light theme hover color for sidebar
* Changed some cookie's from Strict to Lax to fix some bugs
* Check ldap group by username instead of DN
* Allow gathering random art from playlist contents instead of generating on page load

### Removed

* Remove AssestCache class and functions (unreliable)
* When recording stats don't ignore based on a hardcoded gap

### Fixed

* test_image function would fail without php-gd (which is optional)
* Searching for images in files could not return the files you found
* Get rid of that annoying space on api key text in the WebUI
* Catalog map for artist tables
* ratingmatch_stars value 5 wasn't setting itself
* filter_has_var is returning false in FCGI enabled PHP
* Allow catalog manager to manage a catalog in the WebUI
* When using custom metadata don't overwrite managed values
* Missing (and duplicate) preferences for users and system
* Size 0 when reading file tags
* Disk and totaldisks for wma files
* Genre for quicktime/m4a files
* Last.fm login impossible with strict cookies
* Some double scrubs of text in image links
* Updating playlist user would only update the name and not the ID
* garbage collection for playlist images
* Stats when skipping songs with 0 plays
* More PHP8 runtime errors
* Fixed single song random playback using a play url
* Make sure we error if php_intl module isn't found

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

## Ampache 5.1.1-release

### Added

* Clean cache files that aren't in the database
* Translate random and democratic in the webplayer
* Add transcode_flv to config
* Add playlist, playlist_name to album searches
* Send the user to an error page when the config wasn't written
* Config version 58
  * Removed subsonic_stream_scrobble
* Database 510005
  * Add `subsonic_always_download` to preferences

### Changed

* Rebuild aurora.js modules from source
* Perform waveform and cache on disk the same way
* Move song waveforms on load if in the wrong folder
* Make genre searches faster with a join instead of select in
* Send a flat file path for zips when using browse/playlist

### Removed

* Podcast links on the dashboard removed (There is no link for them to go to)
* Remove subsonic_stream_scrobble from config and make it per user (subsonic_always_download)

### Fixed

* Use addslashes for translations in html5 player
* Send the generic client name for localplay again
* Use the set permission level for localplay access
* Webplayer playlists would become out of order after moving/adding
* Cache process could cache the wrong song
* Missing user id in search
* Grouping sql in search when not grouping albums
* Setting Localplay instance would not update the preference
* Advanced search (Random) wasn't working in php8
* Fixed returning the correct objects for advanced search (Random)
* Some objects would add transcode_to to their play url
* Set system prefs for mb and tadb plugins
* Updating a channel in php8
* Get tmp_playlist by session (could get confused and never retrieve items)
* Setting cookies and session details on remember_me sessions
* Set the catalog parameters for seafile catalogs
* Database updates for php8
* Remember me session cookie error when recreating a new session
* ampache.sql script had AUTO_INCREMENT data it didn't need
* Subsonic
  * Get the artists for a single catalog correctly
  * Browse highest used the sql differently to the UI

## API 5.1.1

### Fixed

* Access to podcast_episode_delete
* stats calls with an offest and limit
* advanced_search calls with an offset and limit

## Ampache 5.1.0-release

### Added

* Split search items in WebUI into optgroup categories
* Add en_AU as a locale
* Require confirmation on enable/disable user links
* Add f_size  to video parameters
* Record plays for live_streams and playlists in object_count
* Add podcast to the object_count table and add missing rows
* Store playlist art in the database
  * Show the art on the main playlist page
  * Allow reset and change of playlist art
  * Pick a random art item on reset and store
  * Add a simple continue button for these dialogs
  * Fix up display of image showaction
  * Subsonic art looks for the playlist art the same as UI
* Check for `?` in a query before trying to use parameters
* Add the current php version to the debug page
* Cache bust some frequently updated assets
* Clear asset cache during AutoUpdate
* Gather Artist recommendations on play/Stat insert
* Add Top Tracks and Similar Songs to Artist pages
* Extend run:updateCatalog --update to update artist name matching mbid
* Add duplicate_mbid_group to album searches
* Restored missing artist search to the header searchbar
* Press enter on the list header to allow changing page number
* Translation Update October 2021
* Config version 57
* NEW config options
  * allow_upload_scripts: Allow or disallow upload scripts on the server
* Database 510004
  * Add `podcast` to object_count table
  * Add `podcast` to cache_object_count table
  * Add `live_stream` to the rating table
  * Add `waveforms` for podcast episodes
* PHP8 Support
  * Use array_key_exists to skip runtime errors
  * Fix undefined variables in templates
  * Errors with wanted/missing lookups and templates
  * Ajax handler updates and fixes
  * Set default and fallback values more often to avoid errors
  * Query errors would kill the whole page
  * Stop trying to read unreadable files
  * Explode key pairs when there is something to explode
  * Don't try to update preferences that don't exist
  * Errors when not logged in trying to load a session
* NEW files
  * Test scripts: codecoverage.sh, stan.sh, tests.sh

### Changed

* Always update time when updating songs and videos from tags
* Merge config 'ratings' and 'userflags' checks into ratings. (drop userflags)
* Split search items into groups to help make it a bit clearer
* Rearranged the list of search items
* Simplify PlayAction code a bit and use filter_input
* Speed up update_counts for missing object_counts
* Enable Podcasts on new installs
* Delete composer.lock
* bin/cli run:updateCatalog with no options now does clean, Add, Verify and gather for all catalogs
* Make category headers a bit nicer
* Check for valid browse types before loading nothing
* Browsing Genre defaults to artist
* Skip albums that match the exact title in wanted search
* Translate all database description strings on updates
* Hi-res blankalbum/placeholder image (1400x1400)
* Allow larger artist summary with a scroll. linebreak place, year formed
* During garbage collection clean up empty strings with NULL
* Subsonic
  * Disable stat recording on stream calls (disable subsonic_stream_scrobble in config to enable)

### Removed

* object_cnt: use total_count and total_skip instead of calculated properties
* f_title: use f_name
* Scrub CSS with Autorprefixer

### Fixed

* SQL query error for Random Album in certain config setups
* Album suite needed even without grouping
* Stop scrubbing the podcast title so hard (so many `&amp;`'s)
* Use total_count and total_skip columns for searches (Fixes searching with 0)
* Can't change view limit on Android
* Localplay instance could be 1
* Missing add_type variable on ACL pages
* Light theme follow button color
* Missing CSS on list headers
* Templates with missing variables
* Fix Stream_Playlist::_add_urls to stop mismatched query values
* Fix stream.php downloads not sending their url parameters to PlayAction
* Garbage collect object_count for the possible items
* Do not drop catalog in table podcast_episode when it doesn't exist yet
* AAC codec from itunes doesn't provide a bitrate_mode (assume vbr)
* bin/cli ExportPlaylistCommand had out of order args
* bin/cli Allow database updates when out of date
* Fix computeCache for playlists
* Logic of SQL query to get random albums
* Simplify the join code for some queries
* Don't force random for smartlists when you turn it off
* Empty release_date when updating videos
* Chrome errors where Content-Disposition has a comma (,)
* Remove subtitle in stream_playlist if empty
* Fix options and bitrate selection for stream.php requests
* Scrobbles from Song::can_scrobble
* Default preference list
* MusicBrainz Artist Id could have been replaced with the Album Id
* Artists being duplicated when feat. another artist
* Don't let a non-critical update fail DB update
* Search for 'played' albums and artists
* Commands loading plugins from cli might not have a user
* Dashboard links to podcast episodes and art
* Lots of issues in the webplayer which only supported song links
* Clean up deleted user date from all tables
* Waveforms for podcast episodes
* Subsonic
  * Trim quotes (") for Subsonic searches (some clients add them)
  * Support exact (lucene) searching when using quotes (")
  * Browse by folder is fixed
  * Faster browse queries for all types
  * Fix catalog browsing and loading library

### API 5.1.0

### Added

* NEW API functions
  * Api::live_stream (get a radio stream by id)
  * Api::live_streams
* Api::stream Added type 'podcast_episode' ('podcast' to be removed in Ampache 6)
* Add 'time' and 'size' to all podcast_episode responses

### Changed

* live_stream objects added 'catalog' and 'site_url'
* stats: additional type values: 'video', 'playlist', 'podcast', 'podcast_episode'

### Fixed

* get_indexes: JSON didn't think live_streams was valid (it is)
* record_play: user is optional
* Bad xml tags in deleted functions
* scrobble: Add song_mbid, artist_mbid, album_mbid (docs have no '_' so support both)

## Ampache 5.0.0-release

Ampache 5 is here and it's big!

* Check out [Ampache 5 for Admins](https://github.com/ampache/ampache/wiki/Ampache-Next-Changes)
* As well as [Ampache 5 for Users](https://github.com/ampache/ampache/wiki/Ampache-5-for-users)
* The bin folder has had a major [rework](https://github.com/ampache/ampache/wiki/cli-faq)
* You can pre cache files using [Transcode Caching](https://github.com/ampache/ampache/wiki/Transcode-Caching)

**IMPORTANT** instead of using date() we are now using IntlDateFormatter and your locale to identify formats.
This means that 'custom_datetime' based on the date() format is incorrect and will look weird.
Look here for the code to change your 'custom_datetime' string [(<http://userguide.icu-project.org/formatparse/datetime>)]

This means Ampache now **requires** php-intl module/dll to be enabled.

**IMPORTANT** For new installs default database charset/collation and table engine have changed

* Engine MyISAM => InnoDB
* Charset utf8 => utf8mb4
* Collation utf8_unicode_ci => utf8mb4_unicode_ci

If you want to keep utf8 make sure you set it before running updates.

* To keep the current collation/charset update your config file
  * Set `database_charset = "utf8"`
  * Set `database_collation = "utf8_unicode_ci"`

### Added

* Private catalogs! You can now set a public or per user catalog for your music folders
* Cache transcoded files before a user requests them with [Transcode Caching](https://github.com/ampache/ampache/wiki/Transcode-Caching)
* Added a CONTRIBUTING.md file
* php-intl is now required for translation of date formats into your locale
* Added %R (Release Status) to catalog pattern matching
* Add ability to hide the Song Artist column for Albums with one Artist
* Add ability to browse albums by Original Year
* Add ability to hide the licence column on song pages
* A helper index.php has been added to the old project root with directions to help
* Show the country and Active status (Generated tags are assumed active) on label rows
* Podcast_Episode show episode art for podcast mashup allow sort by date
* Save a search or Smartlist as a regular playlist
* New option to refresh a Playlist from Searches with the same name
* Option to change the playlist owner when editing OR importing
* Set "X-Frame-Options: SAMEORIGIN" on login page
* Added the ability to export database art when local_metadata_dir is enabled
* Save more types of thumb to the local_metadata_dir when enabled
* Inform with a "Not Found: podcast" when you haven't created a podcast catalog
* Added CatalogUpdate import command to the WebUI (Import = Add + playlist imports)
* Search changes
  * Add 'possible_duplicate', 'recently_played' to song, artist and album search
  * Add 'catalog' to artist and album search
  * Add 'favorite_album', 'favorite_artist' to song search
  * Add 'release_status' to album search
  * Add 1, 5 and 10 to the Maximum Results limit
* Database 500015
  * Add `song_count`, `album_count`, `album_group_count` to artist table
  * Add `release_status`, `addition_time`, `catalog`, `song_count`, `artist_count` to album table
  * Add `mbid`, `country`, `active` to label table
  * Add `total_count` and `total_skip` to album, artist, song, video and podcast_episode tables
  * Add `catalog` to podcast_episode table
  * Add `filter_user` to catalog table
  * Add `total_count`, `episodes` to podcast table
  * Add `username` to playlist table
  * Create catalog_map table (map catalog location for media objects)
  * Create user_playlist table (Global play queue)
  * Create user_data table (One shot info for user actions)
  * Create deleted_song, deleted_video and deleted_podcast_episode tables for tracking deleted files
* NEW database options
  * use_original_year: Browse by Original Year for albums (falls back to Year)
  * hide_single_artist: Hide the Song Artist column for Albums with one Artist
  * show_license: Hiding the license column in song rows
  * hide_genres: Hide the Genre column in all browse table rows
* Config version 56
* NEW config options
  * composer_binary_path: Override the composer binary path to distinguish between multiple composer versions
  * write_tags: Write tag changes to file (including art if available)
  * art_zip_add: Include Album Art for zip downlaods
  * catalog_filter: Allow filtering catalogs to specific users
  * catalog_verify_by_time: Only verify the files that have been modified since the last verify
  * cache_path: The folder where the pre-transcoded files will be stored
  * cache_target: Target audio format for the cache
  * cache_remote: Remote catalogs will cache every file so this is handled separately
  * catalog_ignore_pattern: Allow you to ignore audio, video and playlist files with a regex
* NEW cli commands
  * `run:moveCatalogPath`: Change a Catalog path
  * `run:cacheProcess`: Run the [cache process](https://github.com/ampache/ampache/wiki/Transcode-Caching)
  * `export:databaseArt`: Export all database art to local_metadata_dir

### Changed

* get_datetime(): use IntlDateFormatter to format based on locale. [(<https://www.php.net/manual/en/intldateformatter.format.php>)]
* Renamed 'Tag' strings to 'Genre'
* Changed sidebar back to browse for artist/album
* Moved sidebar mashup pages into their own 'Dashboards' section
* Move artist counts (song, album) to a DB column
* Global counts are calculated after catalog changes instead of dynamically
* Display userflag with star ratings
* Always put year in Spotify art search if available
* Imported playslists don't add the extension to the title
* Visually crop images from the centre instead of resizing into a square
* Display release year if it doesn't macth original_year. e.g. 'Back in Black (2010)'
* Don't round the average rating to an integer
* Replace mt_rand with random_bytes for random token generation
* Move user bandwidth calculations out of the user format function into the user_data table
* All localplay links use the type (e.g. mpd/upnp) as the agent to fix muti-client access
* updateCatalog now implies add when using -i / --import by itself
* Playlist Import checks for playlists by your user only in the UI (System for the cli)
* Plugins: Use only https for building gravatar urls
* Scrobble actions now check for the exact time as well (different agents or scripts would insert)
* If you call a play url without an action we assume stream
* Use ISO 8601 date for podcast episode pubdate display
* Database tables default to InnoDB engine with utf8mb4 charset & collation
* Subsonic
  * Wait a few seconds before allowing scrobbles to avoid collisions
  * Shift the last music play if gap is bigger than 5 repeated plays (over night, etc)

### Removed

* Take out the random items (except random search) from the main sidebar (use the playlist on the rightbar instead)
* 'Find Duplicates' and related pages have been removed. Use 'Possible Duplicate' searches instead
* 'Genre' and 'Missing Artists' removed from the top search bar
* Take out the info icon from the song row; just click the song link
* Take song artist out of the album edit popup
* File tag reading for Band/Album Artist
* Corrected album_artist collection and added missing tags to vorbis, aac and id3v2
* Removed links from album list headers when split by release type
* REMOVED config options
  * write_id3: Use write_tags
  * write_id3_art: Use write_tags

### Fixed

* Delete duplicates from song table
* Mashup page for podcast_episodes
* Searching by Genre needed a query overhaul
* Album groupings are the same everywhere when album_group is enabled
* Unknown (Orphaned) groups all unknown files into one artist and album
* Album groups for ratings and userflags
* SQL queries regarding rating order and grouping of mutliple users
* Ensure valid media is found before inserting into a playlist
* Searching by Genre needed a lot of updates to speed up and correct
* Bump phpmailer/phpmailer from 6.4.1 to 6.5.0 (#2957)
* Groupings for albums and add original_year to grouping
* Editing album titles with prefixes
* CSS fixes for light theme
* Shares didn't insert into object_count correctly
* Repair missing rows in object_count after catalog updates
* Browse / Mashup by podcast_episode
* Sorting for a lot of browse pages that used arguments
* Refreshing the details after editing an object didn't include browse aruments
* Get the correct total artist_count for albums when grouped
* Some buttons and links in the light theme needed extra CSS
* Updated the inotifywait.sh example to stop it trying to add the same file multiple times
* Translations could break JS with apostrophes
* Playlist imports with an empty web_path would never work
* Playlist imports were importing nothing
* List preferences didn't allow null values after being set (Personal Favorites plugin)
* When using album_art_store_disk the art lookup was hardcoded for jpg
* Generating thumbnails wouldn't work with album_art_store_disk enabled
* Updating config values for spotify_art_filter and art_search_limit would not keep your value
* Delete podcasts and radio streams when deleting a catalog
* Collect recommendation garbage correctly
* Empty release date when updating a video would fail
* Scrub out some link titles that can be abused by uploads
* Subsonic
  * Support a global user playqueue with getplayqueue, saveplayqueue
  * Incorrect header being set on art requests
  * averageRating wasn't correctly cast for json
  * bookmark JSON was not correctly converted

### API 5.0.0

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

## Ampache 4.4.3-release

### Added

* Catalog::update_counts to manage catalog changes
* Gather more art files from your tags
* Allow RatingMatch plugin to rate Album->Artist (Originally Song->Album->Artist)

### Changed

* Calculate MP3 stream length on transcode to avoid cutting it off early

### Removed

* Don't apply an album artist when it isn't distinct
* MySQL faq isn't needed during install now that PHP 7.4 is a requirement

### Fixed

* CVE-2021-32644
* Identifying a distinct album_artist query wasn't great
* Don't return duplicate art while searching file tags
* SQL query in random::advanced_sql was ambiguous
* Filtering random and search page type element
* NowPlaying stats would be overwritten when they didn't need to be
* Subsonic
  * getNowPlaying was unable to return playing media or the correct time
  * createShare would not set the object_id correctly and ignored expires value

### API 4.4.3

**NO CHANGE**

## Ampache 4.4.2-release

### Added

* Larger artist images when you don't have a text summary available
* Expanded artist, album and podcast thumbnails to reduce blank space
* Update album tags first when you update artist tags

### Changed

* Simplify flagging/rating multi-disk albums
* Subsonic
  * just send getmusicfolders music folders
  * When calling createPlaylist, assume that the list needs to be empty first

### Fixed

* Require a valid database hostname instead of assuming localhost
* A valid transcode_cmd is required to transcode media
* Subsonic
  * Clients might send you a file path of Artist art instead of the id
  * Strings don't need json conversion checks
  * Send the cover art id for playlists
  * Check for artist and podcast prefixes on art id's
  * Bugs when converting between SubSonic id and Ampache id
  * Assign roles based on preferences (fixes jukebox, podcast and share roles)
  * CreateUser could overwrite admin access level
  * UpdateUser didn't write the access level
  * don't return null Genre counts
  * fix getting artist indexes for large libraries
* Don't get null playlist objects from the DB
* Using 'Save Track Order' would not apply the offset
* Vorbis/Ogg comments use 'organization' for publisher and 'track_number' for track
* Automated Label creation when updating from tags
* Grouped album downloads and rightbar actions
* Preference::get_by_user was caching into a single value
* A user who owned a playlist was unable to reorder (but could still save the order)
* When creating shares, don't allow secret to be longer than database limit (20)
* Album full name wasn't being used in some places
* Tag::get_tag_objects was not grouping albums
* Return integers for tag counts
* rmccue/requests CVE: CVE-2021-29476
* PHPMailer/PHPMailer CVE: CVE-2020-36326

### API 4.4.2

### Fixed

* API::indexes Artist albums were being added incorrectly for XML
* Send back the full album name in responses

## Ampache 4.4.1-release

### Added

* If you have an MBID in your artist, use that for last.fm queries

### Changed

* Updated composer dependencies
* Default podcast_keep and podcast_new_download preferences are set to 0 (unlimited)

### Removed

* Delete 'concerts_limit_past' and 'concerts_limit_future' database settings.

### Fixed

* Grid View shouldn't change the artist image
* Catalog Update -u (gather last.fm info) wasn't getting an ID list correctly
* Album::get_random_songs not returning id's
* Bookmark::get_bookmarks typo for get_bookmark_ids
* Sorting album browses by artist name could fail with mysql
* Subsonic getPlaylists should always send a user
* Album browsing SQL didn't include Artist name in grouping
* CVE-2021-21399: Unauthenticated SubSonic backend access in Ampache

### API 4.4.1

### Fixed

* API::stats would not offset recent calls

## Ampache 4.4.0-release

Keep an eye on the incoming changes to develop at [Ampache-Next-Changes](https://github.com/ampache/ampache/wiki/Ampache-Next-Changes)

### Added

* Write metadata to mp3, flac and ogg files. Requires metaflac and vorbiscomment installed on Linux.
* Write images to mp3 and flac files. Also requires metaflac on linux.
* File tags can be updated from catalog management page.
* Configurable settings for "Gather Art".
* Configurable art search limit.
* User selectable artist and year filter for Spotify album searches
* User selectable limit for art searches.
* Generate rsstokens for each user allowing unique feed URLs
* Allow setting custom database collation and charset without overwriting your changes
  * rsstoken: Identify users by token when generating RSS feeds
* Run garbage collection after catalog_update.inc 'clean' or 'verify'
* Add duration to the table headers when browsing playlists and smartlists
* Add time and duration to albums, artists instead of calculating from songs each time
* Allow setting a custom background on the login page
* Musicbrainz search icon on Artist, Album and Song pages
* Update missing album artists on catalog add
* Add R128 Gain adjustments
* Persist replaygain setting as a cookie
* Support for image per song
* Format XML output using DOMDocument
* SubSonic - shift the current track start time when you pause/resume
* Config version 49
* NEW config options
  * hide_ampache_messages: We sometimes need to talk and will show a warning to admin users. Allow hiding this
* NEW search options (also available in Api::advanced_search)
  * last_skip (artist, album, song)
  * last_play_or_skip (artist, album, song)
  * played_or_skipped_times (song)

### Changed

* Stop logging auth/passphrase strings
* Add Y scrolling to the current playlist box (rightbar)

### Fixed

* Escape filepaths when removing from database
* Regex in config for additional_genre_delimiters
* Grid View option was backwards
* Replaygain issues in the webplayer
* Per disk actions for grouped albums (e.g. play just that disk)
* Catalog removal needs to run garbage collection
* Recognize opus when reading tags
* Regex in config for additional_genre_delimiters
* SQL query for smartlists not joining the OR correctly
* Searching with bad rules will return no results instead of everything
* Check the 'unique_playlist' option in more places
* When you haven't set an active localplay nothing was picked
* Set time for artists that are only albums
* Don't hide rss generation when you haven't got a key
* Podcast episode durations that use seconds were converting into crazy lengths
* Playlist and Smartlist check sql simplified
* SubSonic - Json clients need their playlist entry to always array (single item lists)

### API 4.4.0

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

## Ampache 4.3.0-release

This version of Ampache seeks to bring in some of the great changes going on in develop while we work on v5.
There also a few API changes to enable a bit better control for older clients.

### Added

* Check limits on democratic playlists (> 0 && < 3000000000)
* Show an error for out of range democratic cooldowns
* SubSonic - Force a default format (xml) instead of none
* Added back the agent string in recently played (for admins)
* Replace 'Admin' icon with padlock in sidebar when access check fails. (Hide this new icon with 'simple_user_mode')
* Disable API/Subsonic password resets in 'simple_user_mode'
* New option -m 'move_catalog' added to catalog_update.inc
* More default preferences to the refill/check functions
* More functions to search (album artist, mbid)
* Config version 46
* NEW config options
  * hide_search: If true do not include searches/smartlists in playlist results for Api::get_indexes, Api::playlists
* NEW plugin:
  * 'Personal Favorites'. Show a shortcut to a favorite smartlist or playlist on the homepage
  * 'RatingMatch'. Raise the minimum star rating (and song loves) of artists and albums when you rate/love the song

### Changed

* Scrobble plugins fire after stat recording
* Split art search by 5 instead of 4
* Increase autoupdate check time and don't force it on each logon
* Updated CSS and separated mashup covers from other types
* Don't use mail_enabled for registration checks
* WebUI - Browse by album_artist instead of single artists
* Better sorting for playlists using sort_tracks
* Don't allow duplicate podcast feeds
* Updated the gather art process
* Searches will order by file/name instead of id (unless random)
* Updated amapche.sql
* Updated composer requirements
* Default false config option text changed to true (no more typing, just uncomment!)
* Compressed PNG and JPG images

### Removed

* Disabled the jPlayer fullscreen shortcut (ctrl + f)
* Remove system preferences from the user that aren't classified as a system preference
* Stop setting open_basedir from fs.ajax
* Concert/Event pages (dead Last.fm API)
* Don't run reset_db_charset on DB updates
* Disabled browse_filter for new user accounts

### Fixed

* Speed up the playlist dialog boxes (Add to playlist)
* Fix SQL query for Stats::get_newest_sql
* Session cookie creation
* Multiple auth attempts in the same second would not return a session
* Mail auth was not checked correctly
* Gather art correctly for update_file.inc
* set bitrate correctly if using a maxbitrate in play/index
* MP3's would not get a waveform without editing the config
* Recently played respects your privacy settings
* Graph class sql grouping
* **MAJOR** UPnP fixes
* Upload catalog rename logic

### API 4.3.0

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

## Ampache 4.2.6-release

### Changed

* Ignore ALL tagged releases (e.g. 4.2.6-preview 4.2.6-beta)
* Don't check the times in save_mediaplay plugins
* Plugins should only have 1 category
* Update Composer requirements

### Removed

* Some system preferences were added as user preferences

### Fixed

* Search original_year query
* Replaygain was missing from the webplayer
* Check albumartist in get_album_suite queries
* Recently played queries check for privacy options
* Headphones plugin fix for missing mbid's
* Duplicate downloads recorded in play/index
* Subsonic video HLS stream and json values
* Block more password resets when using simple_user_mode
* Upload catalog rename logic

### API 4.2.6

**NO CHANGE**

## Ampache 4.2.5-release

### Added

* Use _add_urls when building a stream playlist

### Changed

* Removed the forced random from search
* Put the browse header at the top above plugins
* Make the webplayer class a bit faster at deciding what to transcode

### Fixed

* Ampache Debug, cron.lib.php missing from init
* Slow playlist creation when inserting a large amount of items
* Stream_URL properties were inconsistently applied
* Fix streaming when play_type is Democratic
* Save your limit and random settings when creating a smartlist

### API 4.2.5

**NO CHANGE**

## Ampache 4.2.4-release

### Added

* "Random" tickbox added to search pages

### Changed

* Searching 'original_year' will now fall back to 'year' if no release year is present

### Fixed

* User was being created but you were told it isn't
* The search pages remember your limit correctly
* PHP exception when < 7.1
* Correct "Recently Added", "Recently Updated" searches
* Check that song can be inserted before inserting the remaining rows
* Logic in stat recording when skips occur
* Don't query for null tag ids

### API 4.2.4

**NO CHANGE**

## Ampache 4.2.3-release

### Added

* Subsonic Generate errors for objects missing art

### Changed

* Don't mark short songs as skipped
* Subsonic Stop converting strings to ints in JSON responses

### Fixed

* User registrations
* Workaround null values for new columns in search table
* Check release_type length before inserting into the database
* Ensure Album Artist is set correctly on songs
* Subsonic Fix callbacks for similarSongs2 and artistInfo2
* Subsonic getCoverArt fixes

### API 4.2.3

**NO CHANGE**

## Ampache 4.2.2-release

**DATABASE CHANGES** You can now force a default collation and charset on your database.
If you choose to use utf8mb4; Ampache will convert your table engine to InnoDB to handle the extra bytes.

### Added

* Numeric 'Played/Skipped ratio' added to search. (Set using (stream/skip)*100.)
  * ```> 0 & < 100```: Skipped more than played
  * ```100```: Equal plays and skips
  * ```> 100```: Played more than skipped
* Add 'Original Year', 'Release Type' to Album searches
* Allow setting custom database collation and charset without overwriting your changes
* Video search added to random.php
* 'samesite=strict' on JS cookies
* Translation updates (August 2020)
* Put 'Labels' into search, browse headers and sidebar when enabled
* NEW config options (config_version 45)
  * database_charset: Set a default charset for your database
  * database_collation: Set a default collation for your database
  * simple_user_mode: Don't allow users to edit their account values (used for demo sites that allow login)
* NEW files
  * bin/update_file.inc: Update your catalog when individual files are changed using inotifywait.sh
  * bin/update_db.inc: Update your database collation, charset and table engine from the cli
  * docs/examples/inotifywait.sh: script to use inotifywait and update_file.inc to update as file changes happen
  * docs/examples/inotifywait.service: systemd example service for inotifywait.sh

### Changed

* stats.php: Show total 'Item Count' on Statistics page instead of trying to shoehorn songs/videos/etc into different columns
* ampache.sql updated after about 4 years... no more updates on install!
* Searching by "Rating (average)" now ignores private/public and always returns the average.
* Hide searches for '# Skipped' and 'Played/Skipped ratio' when 'Show # skipped' is Off
* Search items rearranged to try to match each other
* Sort 'Playlist' and 'Smart Playlist' browse pages by name
* Display the blankuser avatar in now playing if missing
* Swap 'Random' and 'Playlists' in the sidebar (CSS order numbers)
* Don't hide artist art when you disable lastfm_api_key in the config
* Hide 'Metadata' search when 'enable_custom_metadata' is disabled

### Deprecated

* Drop version number from the release string in develop. ('4.3.0-develop' => 'develop')
  * This should stop a bit of confusion when removing / adding requirements
* The '-release' suffix in version number will be dropped for Ampache 5.0.0

### Removed

* Remove stat recording from channels
* Don't reset the database charset and collation after each db update

### Fixed

* Fixed a few issues on the Statistics page
  * Report 'Catalog Size' correctly for podcasts
  * Report 'Item Count' correctly for podcasts and video catalogs
* Searching albums for artist name
* Mashup 'Newest' would incorrectly apply an offset missing the newest items
* Search by 'Smart Playlist' rules fixed when added with other rules
* Use LEFT JOIN instead of HAVING for search rules to allow more complicated lists
* Logic searching 'My Rating' includes unrated (0 Stars) in a better way
* Captcha was not generated for registration
* Enforce lowercase codec for live streams
* Parsing integer search rules was overwriting index values
* Handle empty XML on similar artist requests to last.fm

### Security

Fix CVE-2020-15153 - Unauthenticated SQL injection in Ampache

### API 4.2.2

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

## 4.2.1-release

**NOTICE** Ampache 5.0.0 will require **php-intl** module/dll to be enabled.

### Added

* Numeric ('1 Star'-'5 Stars') searches now include '0 Stars' to show unrated objects
* Ajax refresh localplay "Now Playing" same as the index "Now Playing" section
* Add 'has not rated' to "Another User" searches
* Add higher bitrates (640, 1280) to search to allow for lossless files
* Put strings ('1 Star', '2 Stars', etc) back into numeric searches for ratings
* When using a string title for numeric searches use the order of the items starting with 0
* NEW files
  * Include API docs from the wiki. (API.md, API-JSON-methods.md, API-XML-methods.md, API-advanced-search.md)
* 'Filters' added to each sidebar tab if enabled (previously only 'Home' and 'Admin')

### Changed

* Use binary (.mo) translation files to speed up translation processing
* Don't show 'Generate new API key' if you don't have access
* QR Code in account page is now just the API Key (redundant link removed too)
* Require minimum version of Ampache 3.8.2 to upgrade database
* Added an icon to webplayer to go to album. Clicking on song title now directs to song

### Fixed

* Waveform config option 'get_tmp_dir' was ignored if set
* Rightbar: 'Add to New Playlist' not adding on new playlists
* Translate preference subcategories and status
* 'podcast_new_download' logic fix
* Filters box would show up in the Admin tab if you disabled 'browse_filter'
* Update album when 'release_type' changes
* Parse 'Release Type' from tags in a less terrible way

### API 4.2.1

No functional changes from 4.2.0

### Fixed

* Filter in "playlist" and "playlist_songs" fixed

## 4.2.0-release

The API changelog for this version has been separated into a new sub-heading below to make it easier to follow.

### Added

* Added Spotify art searches for both album and artist images.
* Updated component installer and php-cs-fixer package.
* Translation updates (April 2020, May 2020, July 2020)
* Added declare(strict_types=0); to lib/* and lib/class/* (requires more work before it can be enabled)
* Add 250 for search form limits in the web UI. (Jump from 100 to 500 is pretty big)
* Add Recently updated/added to search rules
* Add regex searching to text fields. ([<https://mariadb.com/kb/en/regexp/>])
  * Refer to the wiki for information about search rules. (<http://ampache.org/api/api-advanced-search>)
* When labels are enabled, automatically generate and associate artists with their publisher/label tag values.
* Enforced stat recording for videos. (podcasts and episodes to be added later)
* Add tags (Genres) to "Anywhere" text searches.
* 10 second redirect on "Access Denied" to the default web_path
* Allow "Update from tags" for multi-disk album and artist pages
* show and hide the rightbar (playlist) using the minimize button in the header
* Tag->f_name (New property on tag that was being set hackily)
* Add "Album" to Find Duplicates in admin/duplicates.php.
* "Local Image" added to Artist & Album search. Find out whether you have art stored in Ampache
* PHP_CodeSniffer checks and settings added to Scrutinizer. (phpcs --standard=.phpcs.xml lib/class)
* NEW database options
  * cron_cache: Speed up the interface by allowing background caching of data
  * show_skipped_times: Add "# skipped" to the UI. (disabled by default)
  * custom_datetime: Allow you to format your date strings your way.
  * unique_playlist: Force unique playlists by ignoring existing songs
* NEW config options
  * skip_timer: Add Skip Timer Threshold to the config
  * artist_art_folder: Specify a local folder to search for artist images using name/title
  * rating_file_tag_user: Set ratings to this user ID when importing ratings from file tags
  * spotify_client_id: Allows Spotify art search
  * spotify_client_secret: Allows Spotify art search
* NEW files
  * server/json.server.php & lib\class\json_data.class.php: JSON API!
  * bin/compute_cache.inc: Cache object_count data to speed up access
  * bin/cron.inc: Perform garbage_collection functions outside of main functions (includes compute_cache.inc)
* NEW examples
  * docs/examples/ampache_cron.service
  * docs/examples/ampache_cron.timer

### Changed

* Change license string from AGPLv3 to AGPL-3.0-or-later
* Update Composer requirements
* Allow searching play times without requiring UI option
* Stop showing the average rating in the web interface as stars. (show an average when available as text separately)
* When you don't have a config file redirect to installer
* Change to numeric searches: Renamed 'is' => 'equals' and 'is not' => 'does not equal'
* Allow negative track numbers; reducing the maximum track number to 32767.
* Localplay volume control moved to the playlist (rightbar)
* Podcast_Episode::check_play_history Podcast_Episode::set_played (match song.class versions for stat recording)
* Video::check_play_history Video::set_played (match song.class versions for stat recording)
* php_cs rules for line endings
* Simplify play history checks and code a bit more
* Tag (Genre) searches compare each item (e.g Pop) rather than the whole string (Pop,Rock,etc)
* Replace "Browse Library" buttons with a search header allowing faster browsing of other types
* Share secrets are generated by generate_password instead of a separate function
* inet_ntop may not convert some binary IP addresses (like ::1) these now show up as "Invalid" in the ip history.
* Searches using numeric rules must use an integer. ('1 Star' => 1, '2 Stars' => 2, etc)
* bin/delete_disabled.inc require -x to execute. (previously you needed to edit the file)

### Deprecated

* Horde_Browser::getIPAddress(). Use Core::get_user_ip() instead.

### Removed

* bin/migrate_config.inc (This was used to migrate the config file from php4 to php5)
* EchoNest api/song previews
* User::update_user_stats (used in play index only and useless)
* Share::generate_secret; use generate_password instead
* Song::get_cache_count (unused)
* Ampache Debug check for 'safe_mode'

### Fixed

* Fixed a lot of incorrectly typed function calls and code documentation
* Gravatar Plugin: Make sure https is used when force_ssl is configured
* Truncate strings to match database limits when strings go over
* Add User php warnings
* Channel authentication
* IP checks when sending null proxy values
* Gather art page layout
* Read vorbis rating correctly
* Search rules in UI failing to load with custom_metadata
* Warn correctly when inserting art fails
* Insert missing user preferences on login
* When you had beautiful_urls enabled tracks would not parse in localplay making them all Unknown
* Podcast durations aren't always correct format, prep the time before trying to insert it
* Subsonic playlist add/remove removing incorrect songs
* Search/Smartlists need to have results to be used in lists
* Auth issues with stats for recording and localplay
* Stream_urls were generated with a typo when downloading
* Respect album grouping using of the moment plugin
* Filter album title with grouping enabled. (seriously deadmau5, stop with the <> everywhere)
* Share playback without a UID would fail to start
* Set a default popular_threshold if unauthenticated or unset
* play/index would record democratic streams as a download
* Make sure the default preferences table has all the preferences in them
* Beets catalog bug; date_diff expecting a datetime but given a string
* Searches using user data like ratings has been split in the SQL correctly
* Flagged playlists never had their flag deleted
* offset and limit were incorrectly used for top/recent searches

### Security

* Fix: CVE-2020-13625 in phpmailer

### API 4.2.0

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

## 4.1.1

### Added

* Extend Shouts to 2000 characters; Labels to 250
* Add a status icon to the channel list pointing to the channel/ID/status.xsl

### Changed

* Hide localplay in the sidebar when you disable all the plugins

### Removed

* Remove non-free lib/composer.* files.
  * You can enable c-pchart with (composer require szymach/c-pchart "2.*")
* Remove shoutcast table and preferences. (Dead code)

### Fixed

* Musicbrainz Art search
* tmp_playlist bug removing items
* Dropbox catalog errors when using a small library
* some bugs getting invalid time/date when reading tags

### API 4.0.0 build 004

Bump API version to 400004 (4.0.0 build 004)

#### Added

* Add Api::check_access to warn when you can't access a function

#### Fixed

* Fix parameters using 0
* Get the correct total_count in xml when you set a limit
* Fix many XML formatting issues

## 4.1.0

### Added

* December translation update from Transifex
* Add playlist into main search page. (Songs, Albums, Artists, Playlists, Videos)
* Add docs/examples/channel_run.service for running background processes as a service
* New search option "Another User" allows searching other user ratings and favorites
* Updates to support php7.4 (Ampache supports 7.1-7.4)
* Checks in Subsonic/WebUI for recording repeated plays
* composer & php-cs-fixer updates
* Add github package guide for docker to RELEASE-PROCESS.md

### Changed

* Update channel status pages (/channel/$CHANNELID/status.xsl)
* Update ListenBrainz plugin for empty additional info. (API says remove this section from json)

### Removed

* Roll back mysql8 workarounds. (Orace MySQL supported on php7.4+ only)
* Revert changes in 4.0.0 and allow manual choices for artist/album on upload again.

### Fixed

* Fix comparison bugs found during static type testing
* Fix enable/disable song ajax
* Typo in login page HTTP_REFERER
* Fix bin\*.inc text issues with newline
* Fix bug in UI when enabling/disabling songs
* Fix smartlists when searching sub-lists (Ampache was trying to create one giant query that didn't scale well)
* Fix "Add New..." in album edit
* Subsonic return json errors when requesting json format (previously errors were always xml)

### API 4.0.0 build 003

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

## 4.0.4

Finalize release procedure to make these updates a bit smoother

### Added

* Reduce the time for repeated track playback (Song length - 5 sec)

### Changed

* Filter playlists in API, Web and Subsonic correctly for regular users vs admins
* Hide some lines from the mashup to make it a bit nicer

### Removed

* Remove the old logo from the main install page

### Fixed

* Fix album count for Artists when the album is missing data
* Fix searches / searchbox for MYSQL8
* Fix some invalid returns in lib/*
* Send the correct function in ajax.server when deleting from playlist

### Security

* None

## 4.0.3

### Changed

* Filter playlists by access in subsonic
* Fail check_php_verison() when using less than php7.1

### Fixed

* Fixes for Api::get_indexes, Api::playlists, Api::playlist, Api::playlist_songs
* Fix Access::check to allow all public lists
* Fix global user connecting through the API with an API key.

## 4.0.2

### Changed

* Bump API version to 400002 (4.0.0 build 002)
* Extend Api::playlist_generate (add new mode 'unplayed')
* Translate typo in show_test.inc
* Trim massive year, time and track when importing new songs

### Fixed

* Fix API playlist commands and access checks relating to playlists
* Access::check should be passing user id from the API
* SQL query fixes for Album, Playlist methods
* Remove spaces from play url extensions (Should help nginx users)
* Set play_type correctly in preferences pages

## 4.0.1

### Added

* Added 'file' to Song::find

### Fixed

* Bug fix that would cause albums to be recreated in Album::check

## 4.0.0

Notes about this release that can't be summed up in a log line

### Added

* JavaScript and Ajax updates
* Code documentation and bug hunting
* Added SVG support to the theme engine.
* Default to disk 1 instead of 0 (db updates to handle existing albums)
* Add Barcode, Original Year and Catalog Number to Album table
* New Plugin - Matomo.plugin. [<https://matomo.org/>]
* New Plugin - ListenBrainz.plugin [<https://listenbrainz.org/>]
* Add bin/clean_art_table.inc to clean art that doesn't fit your min or max dimensions.
* Default fallback user avatar when none found
* Added a $_SESSION['mobile'] variable to allow changing pages for mobile devices.
* Viewport settings for mobile devices
* Use a random cover for playlist art
* Add now_playing.php to allow badges for currently track and fall back to last played if none. (thanks @Rycieos)
* Add Now Playing icon to each user page if enabled.
* Add year information and links to the data rows and interface
* Add debugging in song.class.php when the file may be corrupt
* Allow the main sidebar to be reordered using CSS (.sb2_music, .sb2_video, .sb2_*)
* Subsonic Update api to 1.13.0 [<http://www.subsonic.org/pages/api.jsp>]
* Subsonic Allow token auth using API Key instead of password.
* Subsonic New Method: updateUser
* Subsonic New Method: getTopSongs
* Config Version 40
  * Add: mail_enable - Enable or disable email server features otherwise, you can reset your password and never receive an email with the new one
  * Add: rating_browse_filter, rating_browse_minimum_stars - filter based on a star rating.
  * Add: send_full_stream - allow pushing the full track instead of segmenting
  * Add: github_force_branch - Allow any official Ampache git branch set in config
  * Add: subsonic_stream_scrobble - set to false to force all caching to count as a download.
    This is to be used with the subsonic client set to scrobble. (Ampache will now scrobble to itself over subsonic.)
  * Add: waveform_height, waveform_width - customize waveform size
  * Add: of_the_moment - set custom amount of albums/videos in "of the moment areas"
  * Add: use_now_playing_embedded, now_playing_refresh_limit, now_playing_css_file - Show a user forum tag "Now playing / last played"

### Changed

* Don't allow lost password reset for Admin users
* Don't allow emails until mail_enable is true
* Don't allow last.fm queries to overwrite existing art
* Stop trying to insert art when present during catalog update
* Move some $_GET, POST, $_REQUEST calls to Core
* HTML5 doctype across the board. (DOCTYPE html)
* Lots of HTML and UI fixes courtesy of @kuzi-moto
* If you are using charts/graphs there has been a change regarding c-pchart
  * [chart-faq](https://github.com/ampache/ampache/wiki/chart-faq)
* Numerous catalog updates to allow data migration when updating file tags meaning faster tag updates/catalog verify! (Updating an album would update each file multiple times)
  * UserActivity::migrate, Userflag::migrate, Rating::migrate, Catalog::migrate,
  * Shoutbox::migrate, Recommendation::migrate, Tag::migrate, Share::migrate
* Rework user uploads to rely on file tags ONLY instead of allowing manual choices.
* Extend bin/sort_files.inc & catalog patterns to handle new fields
* Updated bin/sort_files.inc for a smoother experience that actually works
* Add -u to bin/catalog_update.inc This function will update the artist table with bio, image, etc as well as update similar artists.
* Update the CSS theme colors and structure.
* Light theme updated.
* Format the input fields. (you get a datetime picker on mobile!)
* Login/lostpassword moves the logo to the bottom on mobile like cockpit does! (makes typing easier on a touch screen)
* Load webplayer hidden to stop popup preferences hiding the window
* Hide video in search/stats if not enabled
* Lots of code tweaks to make things more uniform and readable.
* Default to mashup for artists and albums
* Remove '[Disk x]' when grouped from all UI areas by enforcing the group setting.
* Subsonic Enable getChatMessages, addMessage allowing server chat

### Removed

* Drop PHP 5.6 support for 7.1+
* Remove all Plex code
* Remove message of the day
* No video, no channels in new installs
* Remove plex and googleplus plugins

### Fixed

* Fix import_playlist code. Do not recreate existing playlists and don't imports existing songs.
* Allow cli tools to use system settings for plugins.
* Fix MySQL8 installation using mysql_native_password with caveats ([<https://github.com/ampache/ampache/wiki/mysql-faq>])
* Catalog Manager can now access catalog areas correctly
* Filter zip names in batch so they are named correctly by the download
* Fixed setting button requiring two single clicks to open. (Thanks for this 2016 pull @AshotN)
* Make test.php, init.php & install.php show an error page instead of blank screen. (gettext)
* Fix slideshow creating black screen when using web player
* Fixed QRCode views
* Subsonic Don't ignore group settings with id3 browsing
* Subsonic Fix cover art for playlists and albums
* Subsonic Api fixes for podcast playback, Ultrasonic/Dsub workarounds

### Security

* Resolve NS-18-046 Multiple Reflected Cross-site Scripting Vulnerabilities in Ampache 3.9.0
* Resolve CVE-2019-12385 for the SQL Injection
* Resolve CVE-2019-12386 for the persistent XSS

### API 4.0.0 build 001

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

## 3.9.1

* Fixed Beets catalog creation and updating.
* Autoupdate now ignores pre-release (preview) versions.
* Fixed various command ine utilities in bin folder.
* Fixed XML api syntax for logging in with user name.
* Fixed newline display in xml data returned in playlist, etc.

## 3.9.0

* Video details now correctly displayed for personal video.
* XML API now fully accepts user's API key. Session will be extended if it exists; Otherwise it's replaced.
* Artist name added to Lastfm track.getsimilar query.
* Catalog_update.inc now includes switches for catalog name and catalog type.
* Added Beets catalog to Composer autoload.
* Performance improved with playlist display and search.
* General translation Updates.
* Documented php module requirement for FreeBSD.

## 3.8.9

* Improved display of artist summary on web page.
* Fixed uploading of avatar.
* DSub now writes the correct extension when the ampache config switch `encode_player_api_target` is enabled.
* Artist info now properly displayed.

## 3.8.8

* Subsonic API now removes illegal characters before returning text.
* Removed hardcoded access level to allow guests to stream audio.
* Guest accounts can now access songs and public playlists.
* Fixed bug in subsonic API which caused DSub not to create folders or
  add file extensions when caching.

## 3.8.7

* Better able to clean up image extentions when gathering art from remote sites.
* Check for B.O.M. (\xff\xfe) only in mp3 composer tag and remove.
* Added .ogv encoding target for more efficient streaming of mkv files.
* Top menu appearance more reader friendly for translations.
* Additional fixes to update_remote_catalog function.
* Enabled similar songs when clicking on radio icon in DSub.
* Repaired problem with removing "empty" albums.
* Can now access XML-API when default ACL(s) are removed.
* 'Find Duplicates' tool now works.
* Cleaning now checks for mounted path before removing empty albums/missing files.
* Starring album and artist now work via Subsonic client.
* Modified "Gather Art" debug message to remove confusion from "Image less than 5 chars...".

## 3.8.6

* Updated Subsonic Remote Catalog Module to version 2.
* Subsonic Catalog now grabs artwork directly from the subsonic server.
* Various fixes to Subsonic Catalog and Subsonic API.
* Release package now downloads from release update alert.
* Develop package downloads from develop update alert.
* Fixed album Search.

## 3.8.5

* Added search capability for user's own ratings option for Song, Artist, Album search.
* Clean process now removes empty albums.
* Further translation updates and fixes.
* Updated Subsonic API to increase compliance with Subsonic json API specs.
* Added starred date to album list and song and formatted per Subsonic spec.
* Now compatible with latest Ultrasonic client (json transfer).
* Fixed ability to edit/save smartplaylist name without setting 'random'.
* Search/smart playlist now includes favorite artists and albums.
* Modified SQL statement to not offend default SQL_MODE of ONLY_FULL_GROUP_BY on server versions after 5.7.6.
* Set redirect to false for streaming types.

## 3.8.4

* Subsonic catalog now displays album art and artist bio.
* Artist info now displays in Ampache's installed language.
* Updated translations.
* Composer tag changes now saved to database.
* Seafile catalog module added.
* Dropbox catalog updated to V2 API.
* ip6 addresses now added to user history.
* Updated api.class.php
* fix for web_path auto configuration for subdirectory installation
* Composer tag changes now written to database
* Fixed catch null reference
* Changes to make translation easier
* Smart Playlist can now be edited/saved without 'random' set.

## 3.8.3

* Fixed upload problem
* Fixed charting problem
* Updated composer dependencies
* Fixed ipv6 issue
* Improved Opus transcoding
* Fixed localization chart
* Improved Subsonic API
* Improved ID3v2.3 and v2.4 to better handle multivalued lists
* Added sort by disc numbers
* Song comments are now editable
* Fixed composer column in iTunes
* Many typos fixed
* Fixed field types in modules and Localplay
* Fixed timeline function
* Activated jplayer "preload" option
* Fixed missing submit button on options page
* Increased info on DSub failing via Subsonic API
* Fixed Group actions in private message page causing JavaScript error
* Fixed some info lievel issues in Codacy
* Apply trim on dropbox parameters
* Added Prompt for user to change secret_key during install
* Enabled use of cache in PHPCs
* All files now use UTF8 without BOM
* Now require Exact catalog name match on catalog update
* Port 443 removed from play url
* Now test all images found to select the one with the good dimensions
* Rewrote LDAP class
* Song count in handshake now matches the number returned from songs
* Blocking of webplayer when transcoding fixed
* Cover art is added to live stream
* Added browse filter and light sidebar options
* Updated gettext, zipstream, react and sabre dependencies
* Fixed Subsonic scrobble submission check

## 3.8.2

* Fixed potential security vulnerability on smartplaylist search rule and catalog management actions (thanks Roman Ammann)
* Fixed song comparison issue on arrays (genre ...) when updating from tag
* Fixed song insertion issue if track year is out of range
* Fixed unexpected artist summary autoupdate
* Improved generated playlist filename (thanks yam655)
* Fixed user avatar upload (thanks vader083)
* Fixed waveform temporary file deletion issue if GD library is unavailable
* Fixed max number of items returned from Subsonic getStarred.view (thanks zerodogg)
* Fixed video update from tags (thanks stebe)
* Reverted PHP 5.5.9 dependency to PHP 5.4
* Added video playlist support (thanks SurvivalHive)
* Added preference subcategory
* Added prompt for new playlist name
* Fixed page refresh when canceling album art change (thanks EvilLivesHere)
* Added /play htaccess rewrite rule to avoid default max limit redirection
* Fixed Subsonic artist/album/song name JSON parsing if the name is numeric only
* Added ignored articles and cover art to Subsonic getArtists.view function
* Fixed MySQL requests to support ONLY_FULL_GROUP_BY mode
* Fixed Ajax art refresh after changing it (thanks gnujeremie)
* Fixed playlist creation from smartplaylist (thanks stebe)
* Added SQL unique constraint on tag map
* Fixed Subsonic genres with JSON format
* Added Bookmarks feature on Subsonic API
* Fixed thumb art regeneration if entry found in database without data (thanks s4astliv)
* Added Podcast feature
* Added large view / grid view option on artist and albums collection
* Moved from php-gettext to oscarotero/Gettext
* Added `Access-Control-Allow-Origin: *` header on Subsonic images & streams
* Fixed Subsonic item identifier parsing
* Added logic for external plugin directories (ampache-*)
* Added Discogs metadata plugin

## 3.8.1

* Fixed PHP7 Error class conflict (thanks trampi)
* Fixed user password with special characters at install time (thanks jagerman)
* Moved Ampache project license from GPLv2 to AGPLv3
* Added Ampache specific information on Subsonic API getAlbum using a new `ampache` parameter (thanks nicklan)
* Added 'album tag' option in song search (thanks DanielMaly)
* Added Message of the Day plugin to display MOTD
* Moved AmpacheApi class to a separate ampacheapi-php git repository
* Added timeline / friends timeline feature
* Fixed disabled song display to regular users (thanks shangril)
* Fixed random albums art size (thanks Bidules079)
* Moved tag cloud to artist browsing by default
* Fixed utf8 BOM empty string on song comparison
* Improved Recently Played and user stats queries performance (thanks thinca)
* Renamed SAMPLE_RATE to TRANSCODE_BITRATE on transcoding
* Fixed tag deletion sql error (thanks stebe)
* Moved to PNG default blank image instead of JPG (thanks Psy-Virus)
* Fixed temporary playlist initial position when scrolling down (thanks RobertoCarlo)
* Added Radio stations to UPnP backend
* Fixed Subsonic API art to use album art if song doesn't have a custom art (thanks hypfvieh)
* Fixed Subsonic API search when object count parameter is 0 (thanks hypfvieh)
* Fixed UPnP UUID to be based on host information
* Moved to Composer for dependencies management
* Fixed catalog action when not using Ajax page loading (thanks Razrael)
* Fixed unrated song default value (thanks Combustible)
* Added custom metadata support from files (thanks Razrael)
* Improved Subsonic API getArtists performance (thanks nicklan)
* Fixed theme color setting behavior
* Moved audioscrobbler API to v2
* Added m3u8 playlist import
* Fixed utf8 id3v2 comments support
* Added write_playlists script to export playlists to file
* Fixed Tvdb and Tmdb plugins (thanks wagnered)
* Improved Video filename parsing (thanks wagnered)
* Fixed non scalar settings value printing on debug page
* Improved Subsonic API getAlbumList error handling
* Fixed user login with browser used during the installation
* Fixed iTunes 12 browsing when using DAAP (thanks Chattaway83)
* Moved http_port user preference to ampache.cfg.php
* Upgraded last.fm and libre.fm scrobbling to latest API version (thanks nioc)
* Added missing space between track and album in Localplay playlist (thanks arnaudbey)
* Added check fo mbstring.func_overload support before using id3 write functionality (thanks anonymous2ch)
* Fixed file size calculation when using id3v2 tag (thanks hypfvieh)
* Added rating from id3 tag (thanks nioc)
* Added track number on streaming playlist (thanks Fondor1)
* Fixed catalog export (thanks shellshocker)
* Fixed file change detection
* Improved XML API with more information and new functions
  * (advanced_search, toggle_follow, last_shouts, rate, timeline, friends_timeline)
* Fixed 'Next' button when browsing start offset is aligned to offset limit (thanks wagnered)
* Fixed stream kill OS detection (thanks nan4k7)
* Fixed calculate_art_size script to support storage on disk (thanks nan4k7)
* Fixed sql script semicolon typo (thanks jack)
* Added support for .opus files (thanks mrpi)
* Fixed podcast owner xml information
* Fixed ldap filter parameter check (thanks ChrGeiss)
* Fixed 'Add to existing playlist' link for regular users (thanks Niols)

## 3.8.0

* Added Portuguese (Brasil) language (thanks Ione Souza Junior)
* Updated PHPMailer version to 5.2.10
* Fixed user stats clear
* Added user, followers and last shouts XML API functions
* Fixed transcoded process end on some systems (thanks nan4k7)
* Added ogg channel streaming support (thanks Deathcow)
* Fixed sql connection close before stream (thanks fufroma)
* Added support for several ldap filters (thanks T-Rock)
* Fixed 'Add to existing playlist' button on web player (thanks RyanCopley)
* Added 'add to existing playlist' link on album page (thanks RyanCopley)
* Added option to hide user fullname from other users
* Added playlist track information in Apache XML API (thanks RyanCopley)
* Fixed playlist remove song in Apache XML API (thanks RyanCopley)
* Fixed Subsonic API ifModifiedSince information
* Added Podcast links to albums / artists
* Added Piwik and Google Analytics plugins
* Added Apache 2.4 access control declaration in htaccess files
* Fixed performance issues on user preferences
* Added artist search by year and place
* Fixed search by comment (thanks malkavi)
* Added Paypal and Flattr plugins
* Added .maintenance page
* Fixed captcha
* Added private messages between users
* Fixed Subsonic API rating information on albums and songs
* Added latest artists and shouts RSS feeds
* Fixed tag cloud ordering
* Added Label entities associated to artists / users
* Added WebDAV backend
* Fixed Subsonic API requests with musicFolderId parameter (thanks dhsc19)
* Added footer text edition setting
* Added uploaded artist list on user page
* Added custom Ampache login logo and favicon support
* Added edition support on shared objects (thanks dhsc19)
* Fixed share feature on videos (thanks RobertoCarlo)
* Removed album year display from album name if unset
* Fixed Subsonic API Album/Artist song's link (thanks dhsc19 and daneren2005)
* Added mysql database socket authentication support on web setup (thanks AsavarTzeth)
* Fixed artist art url for mobile use (thanks dhsc19)
* Added Shoutbox home plugin
* Added catalog favorites home plugin
* Fixed search by rating (thanks iamnumbersix)
* Added UPnP Localplay (thanks SeregaPru)
* Changed preferences to return the global value if preference is missing for the searched user
* Fixed special chars in songs names and tags (thanks SeregaPru)
* Fixed Subsonic API playlist edition/delation (thanks dhsc19)
* Fixed integer default value in Apache XML API
* Fixed image thumb on webplayer and search preview (thanks RobertoCarlo and eephyne)
* Fixed proxy setting on all external http requests (thanks brendankearney)
* Added QRCode view of user API key
* Fixed http status code on Subsonic API streams when using curl (thanks nicklan)
* Added Server-Sent Events on catalog actions
* Added option to enable/disable channel and live stream features
* Removed official PHP 5.3 support
* Added option to show/hide footer statistics (thanks brownl)
* Added delete from disk option on user uploaded files
* Added installation type and players helper at installation process
* Added tv_episode tag on quicktime files (thanks wagnered)
* Added new option to disable deferred extended metadata, e.g. artist details
* Added Subsonic API getAvatar function
* Fixed unsynced lyrics tags
* Fixed ldap_filter setting deactivation on ampache.cfg.php update (thanks Rouzax)
* Added Subsonic API similar artists & songs functions
* Added Subsonic API getLyrics function
* Fixed disk number and album artist metadata on quicktime files (thanks JoeDat)
* Fixed Ampache API playlist_add_song function
* Added ability to store images on disk
* Added new setting to define album art min and max width/height
* Fixed Subsonic API getAlbum returned artist id on songs
* Fixed Subsonic API cover art when PHP-GD unavailable
* Fixed Localplay playlist refresh on volume changes (thanks essagl)
* Fixed web player equalizer option if visualizer is not enabled (thanks brownl)
* Fixed asx file mime type (thanks thinca)
* Added song genre parsing options (thanks Razrael and lotan)
* Added sort on languages list (thanks brownl)
* Added placeholder text to search box (thanks brownl)
* Added web player Play Next feature (thanks tan-ce)
* Fixed Plex backend administration page uri (thanks a9k)
* Fixed expired shared objects clean (thanks eephyne)
* Added missing artist search results (thanks bliptec)
* Fixed song genre id parsing (thanks lotan)
* Added Scrobble method to Subsonic API
* Added an option to add tags to child without overwriting
* Added image dimension info to image tables (thanks tsquare66)
* Replaced ArchiveLib by StreamZip-PHP to avoid temporary zip file
* Added Year field in song details and edition
* Added Subsonic API create/delete user, jukebox control and search auto suggestion
* Added few optional install tests
* Improved Share features with modal dialog choices
* Added new action on playlists to remove duplicates
* Fixed playlist addition to another playlist (thanks kszulc)
* Fixed Various Artist link on album page (thanks Jucgshu)
* Added session_destroy call when a session should be destroyed
* Added HTML5 ReplayGain track feature
* Added display and mandatory user registration fields settings
* Added .htaccess IfModule mod_access.c directives
* Fixed SmartPlayer results per user (thanks nakinigit)
* Fixed XSS vulnerability CVE-2014-8620 (thanks g0blin)
* Fixed playlist import setting on catalog update to be disabled by default (thanks DaPike)
* Added ability to browse my tags other library items than songs
* Added Stream Control plugins
* Added transcode settings per player type
* Added ability to write directly the new configuration file when it version changed
* Added `quick play url` to have permanent authenticated stream link without session
* Fixed unresponsive website on batch download (thanks Rouzax)
* Added batch download item granularity
* Fixed 'guest' user site rendering
* Added Aurora.js support in webplayer
* Added Google Maps geolocation analyze plugin
* Added statistical graphs
* Added user geolocation
* Added 'Missing Artist' search
* Fixed Ampache installation with FastCGI
* Added a new RSS Feed plugin
* Added a new 'display home' plugin type
* Added Favorite and Rating features to playlists
* Added user feedback near mouse cursor on democratic votes
* Changed header page position to be fixed
* Added external links on song page details
* Fixed Subsonic API getAlbumList2 byGenre and byYear order (thanks rrjk)
* Added html5 desktop notification
* Added album group order setting
* Fixed unwanted album merge when one of the album doesn't have mbid
* Changed video player to go outside the footer
* Added ip address in authentication failure for fail2ban scripts (thanks popindavibe)
* Added parameter to hide directplay button if number of items is above a limit
* Added Tag split (thanks jcwmoore)
* Fixed album/artist arts and stats migration on rename (thanks jcwmoore)
* Fixed get lyrics from files (thanks apastuszak)
* Fixed verify local catalog (thanks JoeDat)
* Removed Twitter code
* Added optional cookie disclaimer for the EU Cookie Law
* Replaced catalog action links to action dropdown list (thanks Psy-Virus)
* Fixed `remember me` feature (thanks ainola)
* Added email when registered user must be enabled by administrators
* Fixed local catalog clean on Windows (thanks Rouzax)
* Added Subsonic API maxBitRate parameter support (thanks philipl)
* Fixed Subsonic API special characters encode (thanks nan4k7)
* Added Beets local and remote catalog support (thanks Razrael)
* Fixed XML error code returned with invalid Ampache API handshake (thanks funkygaddafi)
* Replaced iframe to Ajax dynamic page loading
* Changed `Albums of the Moment` to not necessarily have a cover
* Added Plex backend items edition support
* Added hls stream support
* Added X-Content-Duration header support on streams
* Removed Toogle Art from artist page
* Fixed track numbers when removing a song from playlist (thanks stonie08)
* Added Plex backend playlist support
* Added gather art from video files (thanks wagnered)
* Added Plex backend movie / tvshow support
* Added release group on albums
* Added Smart Playlist songs list
* Added zlib test
* Removed old Ampache themes
* Fixed Subsonic API lastModified element (thanks bikkuri10)
* Disabled `beautiful url` on XML-API for retro-compatibility
* Fixed image resource allocation (thanks greengeek1)
* Added setting to write id3 metadata to files (thanks tsquare66)
* Added check for large files manipulation
* Added video subtitle support
* Fixed Google arts to use real arts and not the small size preview
* Added Tmdb metadata plugin
* Added Omdb metadata plugin
* Added Music Clips, Movies and TV Shows support
* Added media type information on catalog
* Fixed get SmartPlaylist in XML-API (thanks opencrf)
* Added beautiful url on arts
* Improved browse list header (thanks Psy-Virus)
* Fixed user online/offline information on Reborn theme (thanks thorsforge)
* Added UPnP backend (thanks SeregaPru)
* Added DAAP backend
* Added sort options on playlists (thanks Shdwdrgn)
* Fixed XML-API tag information (thanks jcwmoore)
* Fixed multiple broadcast play (thanks uk3gaus)
* Added SmartPlaylists to Subsonic API
* Added limit option on SmartPlaylists
* Added random option on SmartPlaylists
* Added 'item count' on browse
* Added direct typed links on items tags
* Fixed Subsonic API compatibility with few players requesting information on library -1
* Added license information on songs
* Added upload feature on web interface
* Added albumartist information on songs (thanks tsquare66)
* Fixed errors on sql table exists check
* Fixed play/pause on broadcasts (thanks uk3gaus)
* Added donation button
* Added democratic page automatic refresh
* Fixed distinct random albums
* Added collapsing menu (thanks Kaivo)
* Added 'save to playlist' feature on web player (thanks Kaivo)
* Added tag merge feature
* Fixed democratic vote with automatic logins (thanks M4DM4NZ)
* Added git pull update from web interface for development versions
* Fixed http-rang requests on streaming (thanks thejk)
* Improved installation process
* Improved French translation (thanks arnaudbey)
* Improved German translation (thanks Psy-Virus and meandor)

## 3.7.0

* Added Scrutinizer analyze
* Fixed playlist play with disabled songs (reported by stebe)
* Improved user auto-registration to optionally avoid email validation
* Fixed date.timezone php warnings breaking Ampache API (reported by redcap1)
* Fixed playlist browse with items > 1000 (reported by Tetram67)
* Fixed Amazon API Image support (thanks jbrain)
* Fixed id3v2 multiples genres (reported by Rouzax)
* Improved democratic playlist view to select the first one by default
* Improved German translation (thanks Psy-Virus)
* Fixed playlist view of all users for administrator accounts (reported by stonie08)
* Added option to regroup album disks to one album view
* Changed Ampache logo
* Fixed email validation on user registration (reported by redcap1)
* Added local charset setting
* Improved installation steps and design (thanks changi67)
* Improved Recently Played to not filter songs to one display only
* Fixed Subsonic transcoding support
* Fixed Subsonic offline storage file path (reported by Tetram76)
* Added optional top dock menu
* Added html5 web audio api visualizer and equalizer
* Added `Play List` to Localplay mode
* Fixed encoding issue in batch download
* Added pagination to democratic playlists
* Added an option to group albums discs to an unique album
* Added alphabeticalByName and alphabeticalByArtist browse view in Subsonic API
* Fixed album art on xspf generated playlist
* Added stats, playlist and new authentication method to Ampache XML API
* Added responsive tables to automatically hide optional information on small screen
* Added song action buttons (user favorite, rating, ...) to the web player
* Added sortable capability to the web player playlist
* Added Growl notification/scrobbler plugin
* Added artist slideshow photos plugin from Flickr
* Added setting to change Ampache log file name
* Added playlists to Quick and Advanced search
* Added pls, asx and xspf playlist file format import
* Fixed playlist import with song file absolute path (reported by ricksorensen)
* Fixed playlist import with same song file names (reported by captainark)
* Added shoutcast notification at specific time when playing a song with a waveform
* Added Tag edit/delete capability
* Added several search engine links
* Added myPlex support on Plex API
* Added cache on LastFM data
* Added custom buttons play actions
* Added artist pictures slideshow for current playing artist
* Added Broadcast feature
* Added Channel feature with Icecast compatibility
* Replaced Muses Radio Player by jPlayer to keep one web player for all
* Added missing artists in similar artists for Wanted feature
* Added concerts information from LastFM
* Added tabs on artist information
* Added 'add to playlist' direct button on browse items
* Added avatar on users and Gravatar/Libravatar plugins
* Fixed playlist visibility (reported by stonie08)
* Added OpenID authentication
* Fixed m3u import to playlist on catalog creation (reported by jaydoes)
* Improved missing/wanted albums with the capability to browse missing artists
* Added share feature
* Updated French translation
* Added options per browse view (alphabetic, infinite scroll, number of items per page...)
* Fixed several Subsonic players (SubHub, Jamstash...)
* Added option to get beautiful stream url with url rewriting
* Added check to use a new thread for scrobbling if available
* Added confirmation option when closing the currently playing web player
* Added auto-pause web player option between several browse tabs
* Fixed similar artists list with disabled catalogs (reported by stebe)
* Improved Shoutbox (css fix, real time notifications...)
* Fixed iframe basket play action reload
* Fixed wanted album auto-remove
* Fixed MusicBrainz get album art from releases
* Added Waveform feature on songs
* Added AutoUpdate Ampache version check
* Added auto-completion in global Ampache search
* Added option to 'lock' header/sidebars UI
* Fixed catalog export when 'All' selected
* Fixed XBMC Local Play (reported by nakinigit)
* Fixed artist search
* Fixed Random Advanced (reported by stebe)
* Changed song preview directplay icons
* Added Headphones Automatic Music Downloader support as a 'Wanted Process' plugin
* Updated PHPMailer to version 5.2.7
* Updated getID3 to version 1.9.7
* Added 'Song Preview' feature on missing albums tracks, with EchoNest api
* Added 'Missing Albums' / 'Wanted List' feature
* Upgraded to MusicBrainz api v2
* Replaced Snoopy project with Requests project
* Added user-agent on Recently Played
* Added option to show/hide Recently Played, time and user-agent per user
* Updated French language
* Added option for iframe or popup web player mode
* Improved Song/Video web player with jPlayer, Radio player with Muse Radio Player
* Added 'add media' to the currently played playlist on web player
* Added dedicated 'Recently Played' page
* Added enable/disable feature on catalogs
* Fixed Config class conflict with PEAR
* Improved recommended artists/songs loading using ajax
* Added a new modern 'Reborn' theme
* Improved Subsonic api backend support (json, ...)
* Added Plex api backend support
* Added artist art/summary when using LastFM api
* Added 'all' link when browsing
* Added option to enable/disable web player technology (flash / html5)
* Fixed artist/song edition
* Improved tag edition
* Added song re-order on album / playlists
* Replaced Prototype with jQuery
* Added 'Favorite' feature on songs/albums/artists
* Added 'Direct Play' feature to play songs without using a playlist
* Added Lyrics plugins (ChartLyrics and LyricWiki)
* Fixed ShoutBox enable/disable (reported by cipriant)
* Added SoundCloud, Dropbox, Subsonic and Google Music catalog plugins
* Improved Catalogs using plug-ins
* Added browse paging to all information pages
* Fixed LDAP authentication with password containing '&' (reported by bruth2)
* Added directories to zip archives
* Improved project code style and added Travis builds
* Added albums default sort preference
* Added number of times an artist/album/song was played
* Fixed installation process without database creation
* Removed administrative flags

## 3.6-FUTURE

* Fixed issue with long session IDs that affected OS X Mavericks and possibly
  other newer PHP installations (reported by yebo29)
* Fixed some sort issues (patch by Afterster)
* Fixed Fresh theme display on large screens (patch by Afterster)
* Fixed bug that allowed guests to add radio stations
* Added support for aacp transcoding
* Improved storage efficiency for large browse results
* Fixed unnecessary growth of the tmp_browse table from API usage (reported
  by Ondalf)
* Removed external module 'validateEmail'
* Updated PHPMailer to 5.2.6

## 3.6-alpha6 *2013-05-30*

* Fixed date searches using 'before' to use the correct comparison
  (patch by thinca)
* Fixed long-standing issue affecting Synology users (patch by NigridsVa)
* Added support for MySQL sockets (based on patches by randomessence)
* Fixed some issues with the logic around memory_limit (reported by CableNinja)
* Fixed issue that sometimes removed ratings after catalog operations (reported
  by stebe)
* Fixed catalog song stats (reported by stebe)
* Fixed ACL text field length to allow entry of IPv6 addresses (reported
  by Baggypants)
* Fixed regression preventing the use of an existing database during
  installation (reported by cjsmo)
* Fixed operating on all catalogs via the web interface
  (reported by orbisvicis)
* Added support for nonstandard database ports
* Updated getID3 to 1.9.5
* Improved the performance of stream playlist creation (reported by AkbarSerad)
* Fixed "Pure Random" / Random URLs (reported by mafe)

## 3.6-alpha5 *2013-04-15*

* Fixed persistent XSS vulnerability in user self-editing (reported by
  Jean-Lou Hau)
* Fixed persistent XSS vulnerabilities in AJAX object editing (reported by
  Jean-Lou Hau)
* Fixed character set detection for ID3v1 tags
* Added matroska to the list of known tag types
* Made the getID3 metadata source work better with tag types that Ampache
  doesn't recognise
* Switched from the deprecated mysql extension to PDO
* stderr from the transcode command is now logged for debugging
* Made database updates more robust and verified that a fresh ## 3.## 3.## 3.5 import
  will run through the updates without errors
* Added support for external authenticators like pwauth (based on a patch by
  sjlu)
* Renamed the local auth method to pam, which is less confusing
* Removed the Flash player
* Added an HTML5 player (patch by Holger Brunn)
* Changed the way themes handle RTL languages
* Fixed a display problem with the Penguin theme by adding a new CSS class
  (patch by Fred Thomsen)
* Made transcoding and its configuration more flexible
* Made transcoded streams more standards compliant by not sending a random
  value as the Content-Length or claiming that ranged requests are
  supported
* Changed rating semantics to distinguish between user ratings and the
  global average and add the ability to search for unrated items
  (< 1 star)
* Updated Prototype to git HEAD (4ce0b0f)
* Fixed bug that disclosed passwords for plugins to users that didn't
  have access to update the password (patch by Fred Thomsen)
* Fixed streaming on Android devices and anything else that expects to
  be able to pass a playlist URL to an application and have it work
* Removed the SHOUTcast Localplay controller

## 3.6-Alpha4 *2012-11-27*

* Removed lyric support, which was broken and ugly
* Removed tight coupling to the PHP mysql extension
* Fixed an issue with adding catalogs on Windows caused by inconsistent
  behaviour of is_readable() (reported by Lockzi)

## 3.6-Alpha3 *2012-10-15*

* Updated getID3 to 1.9.4b1
* Removed support for extremely old passwords
* Playlists imported from M3U now retain their ordering
  (patch by Florent Fourcot)
* Removed HTML entity encoding of plaintext email (reported by USMC Guy)
* Fixed a search issue which prevented the use of multiple tag rules
  (reported by Istarion)
* Fixed ASF tag parsing regression (reported by cygn)

## 3.6-Alpha2 *2012-08-15*

* Fixed CLI database load to work regardless of whether it's run from
  the top-level directory (reported by porthose)
* Fixed XML cleanup to work with newer versions of libpcre
  (patch by Natureshadow)
* Fixed ID3v2 disk number parsing
* Updated getID3 to 1.9.3
* Added php-gettext for fallback emulation when a locale (or gettext) isn't
  supported
* Fixed pluralisation issue in Recently Played
* Added support for extracting MBIDs from M4A files
* Fixed parsing of some tag types (most notably M4A)
* Corrected PLS output to work with more players (reported by bhassel)
* Fixed an issue with compound artists in media with MusicBrainz tags
  (reported by greengeek)
* Fixed an issue with filename pattern matching when patterns contained
  characters that are part of regex syntax (such as -)
* Fixed display of logic operator in rules (reported by Twister)
* Fixed newsearch issue preventing use of more than 9 rules
  (reported by Twister)
* Fixed JSON escaping issue that broke search in some cases
  (reported by XeeNiX)
* Overhauled CLI tools for installation and database management
* Fixed admin form issue (reported by the3rdbit)
* Improved efficiency of fetching song lists via the API
  (reported by lotan_rm)
* Added admin_enable_required option to user registration
* Fixed session issue preventing some users from streaming
  (reported by miir01)
* Quote Content-Disposition header for art, fixes Chrome issue
  (patch by Sébastien LIENARD)
* Fixed art URL returned via the API (patch by lotan_rm)
* Fixed video searches (reported by mchugh19)
* Fixed Database Upgrade issue that caused catalog user/pass for
  remote catalogs to not be added correctly
* Added the ability to locally cache passwords validated by external
  means (e.g. to allow LDAP authenticated users to use the API)
* Fixed session handling to actually use our custom handler
  (reported by ss23)
* Fixed Last.FM art method (reported by claudio)
* Updated Captcha PHP to 2.3
* Updated PHPMailer to 5.2.0
* Fixed bug in MPD module which affected toggling random or repeat
  (patch from jherold)
* Properly escape config values when writing ampache.cfg.php
* Fixed session persistence with auth disabled (reported by Nathanael
  Anderson)
* Fixed item count retention for Advanced Random (reported by USAF_Pride)
* Made catalog verify respect memory_cache
* Some catalog operations are now done in chunks, which works better on
  large catalogs
* API now returns year and bitrate for songs
* Fixed search_songs API method to use Search::run properly
* Fixed require_session when auth_type is 'local'
* Catalog filtering fix
* Toggle artwork with a button instead of a checkbox (patch from mywindow)
* API handshake code cleanup, including a bugfix from postfuturist
* Improved install process when JavaScript is disabled
* Fixed duplicate searching even more
* Committed minor bugfixes for Penguin theme
* Added Fresh theme
* Fixed spurious API handshake failure output

## 3.6-Alpha1 *2011-04-27*

* Fixed forced transcoding
* Fixed display during catalog updates (reported by Demonic)
* Fixed duplicate searching (patch from Demonic)
* Cleaned up transcoding assumptions
* Fixed tag browsing
* Added new search/advanced random/dynamic playlist interface
* byterange handling for ranges starting with 0 (patch from uberbrady)
* Fixed issue with updating ACLs under Windows (reported by Citlali)
* Add function that check ampache and php version from each website.
* Updated each ampache header comment based on phpdocumentor.
* Fixed only admin can browse phpinfo() for security reasons on /info.php
* Added a few translation words.
* Updated version ## 3.6 on docs/*
* Implemented ldap_require group (patch from eliasp)
* Fix \ in web path under Apache + Windows Bug #135
* Partial MusicBrainz metadata gathering via plugin
* Metadata code cleanup, support for plugins as metadata sources
* New plugin architecture
* Fixed display charset issue with catalog add/update
* Fixed handling of temporary playlists with >100 items
* Changed Browse from a singleton to multiple instances
* Fixed setting access levels for plugin passwords
* Fixed handling of unusual characters in passwords
* Fixed support for requesting different thumbnail sizes
* Added ability to rate Albums of the Moment
* Added ability to edit/delete playlists while they are displayed
* Fix track numbers not being 0 padded when downloading or renaming.
* Rating search now allows specification of operator (>=, <=, or =)
  and uses the same ratings as normal display.
* Add -t to catalog_update.inc for generating thumbnails
* Generate Thumbnails during catalog art operations
* Fixed transcode seeking of Flacs by switching to MM:SS format for
  flacs being transcoded
* Change album_art_order to art_order to reflect general nature of
  config option
* Fix PHP warning with IP History if no data is found.
* Add -g flag to catalog update to allow for art gathering via cmdline
* Change Update frequency of catalog display to 1 second rather then
  %10 reduces cpu load due to javascript excution (Thx Dmole)
* Add bmp to the list of allowed / supported album art types
* Strip extranious whitespace from cmdline catalog update (Thx ascheel)
* Fix catalog size math for catalogs up to 4TB (Thx Joost.t.Hart@planet.nl)
* Fix httpQ not correctly skipping to new song
* Fix refreshing of Localplay playlist when an item is skipped to
* Fix missing Content-Disposition filename= on non-transcoded songs
* Fix refresh of Localplay playlist when you delete a track from it
* Added ability to add Ampache as a search descriptor (Thx Vlet)
* Correct issue with single song downloads
* Removed old useless files
* Added local auth method that uses PHP's PAM module
* Correct potential security issues due to misuse of REQUEST for write
  operations rather then POST (Thx Raphael Geissert <geissert@debian.org>)
* Finished switching to Dba::read() Dba::write() for database calls
  (Thx dipsol)
* Improved File pattern matching (Thx october.rust)
* Updated Amazon Album art search to current Amazon API specs (Thx Vlet)
* Fix typo that caused song count to not be set on tag xml response
* Fix tag methods so that alpha_match and exact_match work
* Fix limit and offset not working on search_songs API method
* Fix import m3u on catalog build so it does something
* Fix inconsistent view during catalog operations
* Sort malformed files into "Unknown (Broken)" rather then leaving
  them in "Unknown (Orphaned)"
* Fix API democratic voting methods (Thx kindachris)
* Add server version to API ping response
* Fix Localplay API methods (Thx thomasa)
* Improve bin/catalog_update.inc to allow only verify, clean or add
  (Thx ascheel)
* Fix issue with batch download and UNC paths (Thx greengeek)
* Added config option to turn caching on/off, Default is off
* Fix issue where file tag pattern was ignore if files have no tag structure
* Add TDRC to list of parsed id3v2 tags
* Added the rating to a single song view
* Fix caching issue when updating ratings where they would not
  display correctly until a page reload
* Altered the behavior of adding to playlists so that it maintains
  playlist order rather then using track order
* Strip excessive \n's from catalog_update (Thx ascheel)
* Fix incorrect default ogg transcode target in base config file
* Fix stream user preferences using cached system preferences
  rather then their own
* Fixed prevent_multiple_logins preventing all logins (Thx Hugh)
* Added additional information to installation process
* Fix PHP 5.3 errors (Thx momo-i)
* Fix random methods not working for Localplay
* Fixed extra space on prefixed albums (Thx ibizaman)
* Add missing operator on tag and rating searches so they will
  work with other methods (Thx kiehnet@netscape.net)
* Add MusicBrainz MBID support to uniqly identify albums and
  also get more album art (Thx flowerysong)
* Fix the url to song function
* Add full path to the files needed by the installation just to
  make it a little clearer
* Fixed potential endless loop with malformed genre tags in mp3s
  (Thx Bernhard Weyrauch)
* Fixed web path always returning false on /test.php
* Updated Man Page to fix litian problems for Debian packaging
* Fixed bug where video was registering as songs for now playing
  and stats
* Add phpmailer and change ampache.cfg.php.dist
* Fixed manpage (Thx Porthose)

## 3.5 *2009-05-05*

* Added complete Czech translation (Thx martin hason)
* Add the AlmightyOatmeal-Sanity check to prevent a clean from
  removing all songs if your mount failed, but is still
  readable by ampache
* Make the Lang Install page prettier
* Added Check for hash,inet_pton,windows PHP Version to init so
  that upgrades without pre-reqs are handled correctly
* Allow mms,mmsh,mmsu,mmst,rstp in Radio Stream URLs
* Fixed a problem where after adding a track to a saved playlist
  there was no UI response upon deleting the track without
  a page refresh
* Fix an issue where the full version of the album art was never
  used even when requested
* Fix maxlength on acl fields being to small for all IPv6 addresses
* Add error message when file exists but is unreadable do not
  remove unreadable songs from catalog
* Fixed missing title tag on song browse for the title
  (Thx flowerysong)
* Fix htmlchar'd rss feed url
* Fix Port not correctly being added to URL in most cases
  even when defined in config

  v.## 3.5-Beta2 04/07/2009
* Fix ASX playlists so more data shows up in WMP (Thx Jon611)
* Fix dynamic playlist items so they work in stream methods again
* Fixed Recently Played so that it correctly shows unique songs
  with the correct data
* Fix some issues with filenames with Multi-byte characters
  (Thx Momo-i)
* Add WMV/MPG specific parsing functions (Thx Momo-i)
* Add text to /test.php for hash() and SHA256() support under PHP
  section
* Fix SHA256 Support so that it references something that exists
* Fix incorrect debug_event() on login due to typo
* Remove manage democratic playlist as it has no meaning in the
  current version
* Run Dba::reset_db_charset() after upgrade in case people are playing
  hot potato with their charsets.
* Move Server Preferences to Admin menu (Thx geekdawg)
* Fixed missing web_path reference on radio creation link
* Fixed remote catalog_clean not working
* Fixed xmlrpc get image. getEncoding wasn't static

## 3.5-Beta1 *2009-03-15*

* Add democratic methods to api, can now vote, devote, get url
  and the current democratic playlist through the api
* Revert to old Random Play method
* Added proxy use for xmlrpcclient
* Added Configuration 'Wizard' for democratic play
* Fixed interface feedback issues with democratic play actions
* Add extension to image urls for the API will add to others as
  needed due to additional query requirement. Needed to fix
  some DLNA devices
* Fixed typo that caused the height of album art not to display
* Modified database and added GC for tmp_browse table
* Added get lyrics and album art using http proxy server #313 + username,
  password patch
* Added lyricswiki link Ticket #70
* Updated README language
* Updated getid3 library 2.0.0b4 to 2.0.0b5
* Make the Democratic playlist be associated with the user
  who sends it to a 'player'
* Fixed missing page headers on democratic playlist
* Show who voted for the sogns on democratic playlist
* Increase default stream length to account for the fact that movies
  are a good bit longer then songs
* Correct Issues with multi-byte characters in Lyrics (Thx Momo-i)
* Added caching to Video
* Added Video calls to the API
* Remove redundent code from Browse class by making it extend
  new Query class
* Update Prototype to 1.6.0.3
* Add Time range to advanced search
* Add sorting to Video Browse
* Changed to new Query backend for Browsing and Dynamic Playlists

## 3.5-Alpha2 *2009-03-08*

* Fixed caching of objects with no return value
* Fixed updating of songs that should not be updated during catalog
  verify
* Added default_user_level config option that allows you to define
  the user level when use_auth is false. Also allows manual
  login of admin users when use_auth is false.
* Fix Version checking and Version Error Message on install (Thx Paleo)
* Moved Statistics to main menu, split out newest/popular/stats
* Fixed bug where saved Thumbnails were almost never used
* Fixed Localplay httpQ and MPD controls to recognize Live Stream
  urls.
* Added Localplay controls to API
* Added Added/Updated filters to API include the ability to specify
  a date range using ISO 8601 format with [START]/[END]
* Changed API Date format to ISO 8601
* Fixed Incorrect Caching of Album records that caused the
  Name + Year + Disk to not be respected
* Added Lyrics Patch (Thx alister55 & momo-i)
* Fixed password not updating when editing an httpQ Localplay
  instance
* Added Video support
* Fixed normalize tracks not re-displaying playlist correctly
* Fixed now playing now showing currently playing song
* Fixed now playing clear all not correctly refreshing screen
* Fixed adding object to playlist so that it correctly shows the
  songs rather then an empty playlist
* Added User Agent to IP History information gathering
* Added Access Control List Wizards to make API interface
  setup easier
* Added IPv6 support for Access Control, Sessions, IP History
* Fixed sorting issue on artist when using search method
* Updated flash player to 5.9.5
* Fixed bug where you admins couldn't edit preferences of
  users due to missing 'key' on form
* Added Mime type to Song XML

## 3.5-Alpha1 *2008-12-31*

* Fixed sort_files script so that it properly handles variable
  album art file names in the directories
* Fixed issue where small thumbnails were used for larger images
  if gd based resizing was enabled in the config
* Fixed catalog_update.inc so it doesn't produce errors
* Made democratic play respect force http play
* Make installation error messages more helpful
* Added Swedish (sv_SE) translation (Thanks yeager)
* Allow Add / Verify of sub directories of existing catalogs
* Prevent an fread of 0 bytes if you seek to the end of a file
* Added require_localnet_session config that allows you to exclude
  IP(s) from session checks, see config.dist
* Added Nusoap (<http://sourceforge.net/projects/nusoap/>) library
  for use with future lyrics feature
* Fixed problem with flash player where random urls were not being
  added correctly
* Fixed problem with user creation using old method (Thx Purdyk)
* Switched to SHA256() for API and passwords
* Added check for BADTIME error code from Last.FM and correctly
  return the error rather then a generic one
* Fix http auth session issues, where every request blew away the
  old session information
* Many other minor improvements (Thx Dipsol)
* Fixed warnings in caching code (Thx Dipsol)
* Massive text cleanup (Thx Dipsol)
* Fixed tag searching and improved some other search methods to
  prevent SQL warnings on no results
* Improved Test page checks to more accuratly verify putENV support
* Make network downsampling a little more sane, don't require
  access level
* Added caching to Playlist dropdown
* Fixed double caching on some objects
* Added base.css and 4 tag 'font' sizes depending on weight/count
* Fixed inline song edit
* Updated registration multi-byte mail.
* Fixed vainfo.class.php didn't catch exception for first analyze.
* Fixed iconv() returns an empty strings (Thx abs0)
* Updated getid3 for multi-byte characters, but some wrong id3tags
  have occurred exception error.
* Fixed use_auth = false not correctly re-creating the session if
  you had just switched from use_auth = true
* Add links to RSS feeds and set default to TRUE in config.dist
* Fixed Dynamic Random/Related URLs with players that always send
  a byte offset (MPD)
* Added Checkbox to use existing Database
* Updated language code and Fixed catalan language code
* Added Emulate gettext() from upgradephp-15
  (<http://freshmeat.net/p/upgradephp>)
* Fixed Test.php parse error.
* Updated multibyte character strings mail.
* Fixed To send mail don't remove the last comma from recipient.
* Updated More translatable templates.
* Removed merge-messages.sh and Add LANGLIST (each languages
  translation statistics).
* Fixed If database name don't named ampache, can't renamed tags
  to tag.
* Fixed count issue on browse Artists (Thx Sylvander)
* Fixed prevent_multiple_logins, preventing all logins (Thx hugh)
* Fixed Export catalog headers so it corretly prompts you to download
  the file
* Add ability to sort by artist name, album name on song browse
* Implemented caching on artist and album browse, added total
  artist time to the many artist view
* Fixed test config page so it bounces you back to the test page
  if the config starts parsing correctly
* Fixed browsing so that you can browse two different types in two
  windows at the same time
* Improved gather script for translations (Thx momo-i)
* Added paging to the Localplay playlist
* Updated German Translation (Thx Laurent)
* Fixed issue where Remote songs would never be removed from
  the democratic playlist
* Fixed issue where user preferences weren't set correctly
  on stream (Thx lorijho)
* Added caching of user preferences to avoid a SQL query on load
  (Thx Protagonist)
* Fixed home menu not always displaying the entire contents
* Fixed logic error with duplicate login setting which caused it
  to only work if mysql auth was used
* Changed passwords to SHA1 will prompt to reset password
* Corrected some translation strings and added jp_JP (Thx momo-i)
* Ignore filenames that start with . (hidden) solves an issue
  with mac filesystems
* Fix tracking of stats for downloaded songs
* Fix divide by 0 error during transcode in some configurations
* Remove root mysql pw requirement from installer
* Added Image Dimensions on Find Album Art page
* Added Confirmation Screen to Catalog Deletion
* Reorganized Menu System and Added Modules section
* Fix an error if you try to add a shoutbox for an invalid object
  (Thx atrophic)
* Fixed issue with art dump on jpeg files (Thx atrophic)
* Fixed issue with force http play and port not correctly specifying
  non-standard http port (Thx Deathcrow)
* Remember Starts With value even if you switch tabs
* Fixed rating caching so it actually completely works now
* Removed redundent UPDATE on session table due to /util.php
* Added Batch Download to single Artist view
* Added back in the direct links on songs, requires download set
  to enabled as it's essentially the same thing except with
  now playing information tied to it
* Bumped API Version to 350001 and require that a version is sent
  with handshake to indicate the application will work
* Removed the MyStrands plugin as did not provide good data, and does
  not appear to have been used
* Added Catalog Prefix config option used to determine which prefixes
  should not be used for sorting
* Merged Browse Menu with Home
* Added checkbox to single artist view allowing you to enable/disable
  album art thumbnails on albums of said artist
* Added timeout override on update_single_item because the function
  is a lie
* Fix translations so it's not all german
* Genre Tag is now used as a 'Tag', Browse Genre removed
* Ignore getid3() iconv stuff doesn't seem to work
* Improved fix_filenames.inc, tries a translation first then strips
  invalid characters
* Fixed album art not clearing thumbnail correctly on gather
* Fixed Localplay instance not displaying correctly after change
  until a page refresh
* Fixed endless loop on index if you haven't played a song in
  over two years
* Fixed gather art and parse m3u not working on catalog create
  also added URL read support to m3u import
* Upped Minimum requirements to Mysql 5.x
* Add codeunde1load's Web 2.0 style tag patch
* Fixed typo in e-mail From: name (Thx Xgizzmo)
* Fixed typo in browse auto_init() which could cause ampache to not
  remember your start point in some situations. (Thx Xgizzmo)
