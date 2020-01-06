# CHANGELOG

## 4.1.0-develop

* Bump API version to 400003 (4.0.0 build 003)
* Api::playlist - filter mandatory
* Api::playlist_edit - filter mandatory. name and type now optional
* Api::user - Extend return values to include more user fields
* Playlist::create - Return duplicate playlist ID instead of creating a new one
* Artist::check - Remove MBID from Various Artist objects
* Fix Song::update_song for label
* Api - Do not limit smartlists based on item count (return everything you can access)
* Api/Database - Add last_count for search table to speed up access in API
* Fix Api issues relating to playlist access
* Fix comparison bugs found during static type testing
* Fix enable/disable song ajax
* December translation update from Transifex
* Add playlist into main search page. (Songs, Albums, Artists, Playlists, Videos)
* Typo in login page HTTP_REFERER
* Roll back mysql8 workarounds. (Orace MySQL supported on php7.4+ only)
* Fix bin\*.inc text issues with newline
* Add docs/examples/channel_run.service for sunning background processes as a service
* New search option "Another User" allows searching other user ratings and favorites
* user_numeric searches also available in the API. ([<https://github.com/ampache/ampache/wiki/XML-methods])>
* Updates to support php7.4 (Ampache supports 7.1-7.4)
* Fix bug in UI when enabling/disabling songs
* Checks in Subsonic/WebUI for recording repeated plays
* Update channel status pages (/channel/$CHANNELID/status.xsl)
* Fix smartlists when searching sub-lists (Ampache was trying to create one giant query that didn't scale well)
* composer & php-cs-fixer updates
* Fix "Add New..." in album edit
* Revert changes in 4.0.0 and allow manual choices for artist/album on upload again.
* Subsonic return json errors when requesting json format (previously errors were always xml)
* Add github package guide for docker to RELEASE-PROCESS.md
* Update ListenBrainz plugin for empty additional info. (API says remove this section from json)

## 4.0.4

* Finalize release procedure to make these updates a bit smoother
* Reduce the time for repeated track playback (Song length - 5 sec)
* Filter playlists in API, Web and Subsonic correctly for regular users vs admins
* Fix album count for Artists when the album is missing data
* Fix searches / searchbox for MYSQL8
* Fix some invalid returns in lib/*
* Send the correct function in ajax.server when deleting from playlist
* Hide some lines from the mashup to make it a bit nicer
* Remove the old logo from the main install page

## 4.0.3

* Fixes for Api::get_indexes, Api::playlists, Api::playlist, Api::playlist_songs
* Fix Access::check to allow all public lists
* Filter playlists by access in subsonic
* Fail check_php_verison() when using less than php7.1
* Fix global user connecting through the API with an API key.

## 4.0.2

* Bump API version to 400002 (4.0.0 build 002)
* Extend Api::playlist_generate (add new mode 'unplayed')
* Fix API playlist commands and access checks relating to playlists
* Access::check should be passing user id from the API
* SQL query fixes for Album, Playlist methods
* Remove spaces from play url extensions (Should help nginx users)
* Translate typo in show_test.inc
* Trim massive year, time and track when importing new songs
* Set play_type correctly in preferences pages

## 4.0.1

* Bug fix that would cause albums to be recreated in Album::check
* Added 'file' to Song::find

## 4.0.0

### Backend

* Drop PHP 5.6 support for 7.1+
* Resolve CVE-2019-12385 for the SQL Injection
* Resolve CVE-2019-12386 for the persistent XSS
* Resolve NS-18-046 Multiple Reflected Cross-site Scripting Vulnerabilities in Ampache 3.9.0
* Remove all Plex code
* Remove message of the day
* Don't allow lost password reset for Admin users
* Don't allow emails until mail_enable is true
* No video, no channels in new installs
* Move some $_GET, POST, $_REQUEST calls to Core
* JavaScript and Ajax updates
* Code documentation and bug hunting
* Added SVG support to the theme engine.
* Fix - MySQL8 installation using mysql_native_password with caveats ([<https://github.com/ampache/ampache/wiki/mysql-faq>])
* Fix - Catalog Manager can now access catalog areas correctly
* HTML5 doctype across the board. (DOCTYPE html)
* Lots of HTML and UI fixes courtesy of @kuzi-moto
* If you are using charts/graphs there has been a change regarding c-pchart [chart-faq](https://github.com/ampache/ampache/wiki/chart-faq)

### CLI tools / Processes

* Fix - import_playlist code. Do not recreate existing playlists and don't imports existing songs.
* Fix - allow cli tools to use system settings for plugins.
* Don't allow last.fm queries to overwrite existing art
* Stop trying to insert art when present during catalog update
* Extend bin/sort_files.inc & catalog patterns to handle new fields
* Updated bin/sort_files.inc for a smoother experience that actually works
* Add bin/clean_art_table.inc to clean art that doesn't fit your min or max dimensions.
* Add -u to bin/catalog_update.inc This function will update the artist table with bio, image, etc as well as update similar artists.
* Filter zip names in batch so they are named correctly by the download
* Numerous catalog updates to allow data migration when updating file tags. 
  * UserActivity::migrate, Userflag::migrate, Rating::migrate, Catalog::migrate,
  * Shoutbox::migrate, Recommendation::migrate, Tag::migrate, Share::migrate* Faster tag updates/catalog verify! (Updating an album would update each file multiple times)
* Default to disk 1 instead of 0 (db updates to handle existing albums)
* Add Barcode, Original Year and Catalog Number to Album table
* Rework user uploads to rely on file tags ONLY instead of allowing manual choices.

### Plugins

* New Plugin - Matomo.plugin. [<https://matomo.org/>]
* New Plugin - ListenBrainz.plugin [<https://listenbrainz.org/>]
* Remove plex and googleplus plugins

### Web-UI

* Update the CSS theme colors and structure.
* Light theme updated.
* Include smartlists in the API playlist calls.
* Default fallback user avatar when none found
* Added a $_SESSION['mobile'] variable to allow changing pages for mobile devices.
* Viewport settings for mobile devices
* Format the input fields. (you get a datetime picker on mobile!)
* Login/lostpassword moves the logo to the bottom on mobile like cockpit does! (makes typing easier on a touch screen)
* Load webplayer hidden to stop popup preferences hiding the window
* Hide video in search/stats if not enabled
* Use a random cover for playlist art
* Fixed setting button requiring two single clicks to open. (Thanks for this 2016 pull @AshotN)
* Lots of code tweaks to make things more uniform and readable.
* Add now_playing.php to allow badges for currently track and fall back to last played if none. (thanks @Rycieos)
* Add Now Playing icon to each user page if enabled.
* Add year information and links to the data rows and interface
* Default to mashup for artists and albums
* Add debugging in song.class.php when the file may be corrupt
* Remove '[Disk x]' when grouped from all UI areas by enforcing the group setting.
* Make test.php, init.php & install.php show an error page instead of blank screen. (gettext)
* Fix slideshow creating black screen when using web player
* Allow the main sidebar to be reordered using CSS (.sb2_music, .sb2_video, .sb2_*)
* Fixed QRCode views

### Ampache API

* Documented the Ampache API [<https://github.com/ampache/ampache/wiki/XML-methods>]
* Authentication: Require a handshake and generate unique sessions at all times
* Authentication: allow sha256 encrypted apikey for auth
  * You must send an encrypted api key in the following fashion. (Hash key joined with username)
  * $passphrase = hash('sha256', $username . hash('sha256', $apikey));
* Added artist_tag to song searches
* CHANGED in version 400001
  * advanced_search
    * 'is not' has been added shifting values down the list. (0=contains, 1=does not contain, 2=starts with, 3=ends with, 4=is, 5=is not, 6=sounds like, 7=does not sound like)
    * rule_1['name'] is depreciated. Instead of multiple searches for the same thing rule_1'name' has been replaced with 'title' (I have put a temp workaround into the search rules to alleviate this change)
  * stats
    * allow songs|artists|albums (instead of just albums)
  * playlists
    * allow return of smartlists as well as regular playlists (set a 5000 limit on unlimited smartlists)
  * playlist_add_song
    * Added check boolean to skip duplicate songs
  * playlist_remove_song
    * Allow uid of song instead of the track id from the playlist
* NEW in verison 400001
  * flag
    * allows flagging object by id & type
  * record_play
    * allows recording play of object without streaming
  * catalog_action
    * allow running add_to_catalog|clean_catalog
  * playlist_edit
    * allow editing name and type of playlist
  * goodbye
    * Destroy session
  * get_indexes
    * return simple index lists to allow a quicker library fill.
  * check_parameter
    * error when mandatory inputs are missing
  * stream
    * Raw stream of song_id
  * download
    * Download, not recorded as a play
  * get_art
    * Raw art file like subsonic getCoverArt
  * user_create
    * 'user' access level only!
  * user_update
    * update user details and passwords for non-admins
  * user_delete
    * you can't delete yourself or and admin account!
  * update_from_tags
    * updates a single album, artist, song from the tag data instead of the entire library!
  * update_art
    * updates a single album, artist, song running the gather_art process
  * update_artist_info
    * Update artist information and fetch similar artists from last.fm
  * playlist_generate
    * Get a list of song xml, indexes or id's based on some simple search criteria. care of @4phun

### Subsonic Backend

* Update api to 1.13.0 [<http://www.subsonic.org/pages/api.jsp>]
* Allow token auth using API Key instead of password.
* Don't ignore group settings with id3 browsing
* New Method: updateUser
* New Method: getTopSongs
* Fix cover art for playlists and albums
* Enable getChatMessages, addMessage allowing server chat
* Api fixes for podcast playback, Ultrasonic/Dsub workarounds

### Config file changes

* Bump version from 34 to 40
* Add: mail_enable - Enable or disable email server features otherwise, you can reset your password and never receive an email with the new one
* Add: rating_browse_filter, rating_browse_minimum_stars - filter based on a star rating.
* Add: send_full_stream - allow pushing the full track instead of segmenting
* Add: github_force_branch - Allow any official Ampache git branch set in config
* Add: subsonic_stream_scrobble - set to false to force all caching to count as a download. This is to be used with the subsonic client set to scrobble. (Ampache will now scrobble to itself over subsonic.) 
* Add: waveform_height,  waveform_width - customize waveform size
* Add: of_the_moment - set custom amount of albums/videos in "of the moment areas"
* Add: use_now_playing_embedded, now_playing_refresh_limit, now_playing_css_file - Show a user forum tag "Now playing / last played"

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
* Improved XML API with more information and new functions (advanced_search, toggle_follow, last_shouts, rate, timeline, friends_timeline)
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
  nwe Query class
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
