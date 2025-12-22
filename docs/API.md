---
title: "Ampache API"
metaTitle: "Ampache API"
description: "API documentation"
---

The Ampache API Provides methods for pulling out it's meta data in the form of
simple XML (and JSON!) documents. This was originally created for use by [Amarok](https://ampache.org/api/http://amarok.kde.org/),
but there is no reason it couldn't be used to create other front-ends to the Ampache data.

Access to the API is controlled by the Internal [Access Control Lists](https://ampache.org/api/api-acls).
Currently all requests are limited to a maximum of 5000 results for performance reasons. To get additional results
pass offset as an additional parameter.

If you have any questions or requests for this API please submit a [Feature Request](https://github.com/ampache/ampache/issues/new?assignees=&labels=&template=feature_request.md&title=%5BFeature+Request%5D).
All dates in the API calls should be passed as [ISO 8601](http://en.wikipedia.org/wiki/ISO_8601) dates.

## News

As of 7.7.1 (API 6.9.0) Ampache fully supports POST data requests for all API methods.

POST requests offer more privacy than a regular GET request because data is sent in the body, not the URL, avoiding exposure in browser history and logs.

## Archived Version Documentation

After each release, a documentation page will be created to allow pruning old features from the current version.

* [API 5.6 Documentation](https://ampache.org/api/api-5)
* [API 4.4 Documentation](https://ampache.org/api/api-4)
* [API 3.9 Documentation](https://ampache.org/api/api-3)

Ampache supports the last major release of each API version. You can also check out the [past releases](https://ampache.org/api/versions/) page for some historical detail but **DO NOT** use these pages as a guide for API development.

## API Changelog

Take a look at the [API Changelog](https://ampache.org/api/api-changelog) to keep an eye on changes between versions

## Before you begin

Ampache 5.2.0+ supports multiple API versions. This means that you can send your handshake with a specific version (e.g. 390001, 440001, 5.2.0 or 6.0.0) you will be sent API3, API4, API5 and API6 responses in return.

To change from API3 to API5 you can send a ping with a new version parameter to update your session (or send goodbye to log off and start again.)

API3 is not recommended for use outside of running old applications and it is recommended that you turn off API versions you don't use.

## Sending Handshake Request

Multiple authentication methods are available, described in the next sections.

**NOTE** if you use a [Bearer Token](https://ampache.org/api/#http-header-authentication) you do not need to send a handshake request.

### User / Password

The handshake is a combination of the following three things

* Encoded Passphrase
* Timestamp
* Username

The key that must be passed to Ampache is `SHA256(TIME+KEY)` where `KEY` is `SHA256('PASSWORD')`. Below is a PHP example

```PHP
$time = time();
$key = hash('sha256', 'mypassword');
$passphrase = hash('sha256', $time . $key);
```

Once you've generated the encoded passphrase, you can call the following URL (localhost/ampache is the location of your Ampache installation)

```URL
http://localhost/ampache/server/xml.server.php?action=handshake&auth=PASSPHRASE&timestamp=TIME&version=6.0.0&user=USER
```

### Api Key

The key that must be passed to Ampache is the API Key generated for a specific user (none by default, only the administrator can generate one). Then call the following URL (localhost/ampache is the location of your Ampache installation):

```URL
http://localhost/ampache/server/xml.server.php?action=handshake&auth=API_KEY&version=6.0.0
```

If you are using Ampache 4.0.0 and higher; the key can be passed to Ampache using `SHA256(USER+KEY)` where `KEY` is `SHA256('APIKEY')`. Below is a PHP example

```PHP
$user = 'username';
$key = hash('sha256', 'myapikey');
$passphrase = hash('sha256', $user . $key);
```

### HTTP Header Authentication

Ampache supports sending your auth parameter to the server using a Bearer Token.

The `auth` parameter does not need to be sent with your URL. We will check your header for a token first

```Text
GET https://demo.ampache.dev/server/json.server.php?action=handshake&version=6.0.0 HTTP/1.1
Authorization: Bearer 000111112233334444455556667777788888899aaaaabbbbcccccdddeeeeeeff
```

### Other handshake-related stuff

#### Ampache scheme

To standardize how to transfer Ampache connection information, the following Ampache scheme is defined.

```Text
ampache://authentication@hostname[:port]/subdirectory[#parameters]
```

for example:

* ampache://myuser:mypwd@localhost/ampache
* ampache://yourapikey@localhost:993/ampache#ssl=true

### Stream Token's

Ampache6+ allows you to create a Stream Token for a user.

The biggest bonus here is that these static tokens let you can create links that avoid the risk of the session expiring and don't require a handshake to create a session.

Once a user has been given a streaming token; all Democratic, Song, Podcast Episode and Video streams will use this session token.

e.g. `https://music.com.au/play/index.php?ssid=supercoolstreamingtoken&type=song&oid=1511&uid=1&player=api&name=The%20Smashing%20Pumpkins%20-%20Wound.flac`

This token does not allow a user to do anything except stream music and it requires an Admin to create the token for the user.

#### Application Name

By default Ampache uses USER_AGENT as application name but this could also be defined through http query string. Add `&client=YourAppName` to override the application name. This parameter also works on stream sessions.

#### Geolocation

* Latitude
* Longitude
* Place name

Optionally, you can also provide geolocation information `&geo_latitude=$latitude&geo_longitude=$longitude`, with an optional place name if you already know coordinates match `&geo_name=$placename`.

### Result

If your authenticated User and IP match a row in the Access List the following will be returned.

For XML

```XML
<?xml version="1.0" encoding="UTF-8" ?>
<root>
  <auth><![CDATA[cfj3f237d563f479f5223k23189dbb34]]></auth>
  <api><![CDATA[6.0.0]]></api>
  <session_expire><![CDATA[2022-08-17T04:34:55+00:00]]></session_expire>
  <update><![CDATA[2021-07-21T02:51:36+00:00]]></update>
  <add><![CDATA[2021-08-03T00:04:14+00:00]]></add>
  <clean><![CDATA[2021-08-03T00:05:54+00:00]]></clean>
  <songs><![CDATA[75]]></songs>
  <albums><![CDATA[9]]></albums>
  <artists><![CDATA[17]]></artists>
  <genres><![CDATA[7]]></genres>
  <playlists><![CDATA[2]]></playlists>
  <searches><![CDATA[17]]></searches>
  <playlists_searches><![CDATA[19]]></playlists_searches>
  <users><![CDATA[4]]></users>
  <catalogs><![CDATA[4]]></catalogs>
  <videos><![CDATA[2]]></videos>
  <podcasts><![CDATA[2]]></podcasts>
  <podcast_episodes><![CDATA[13]]></podcast_episodes>
  <shares><![CDATA[2]]></shares>
  <licenses><![CDATA[14]]></licenses>
  <live_streams><![CDATA[3]]></live_streams>
  <labels><![CDATA[3]]></labels>
</root>
```

For JSON

```JSON
{
    "auth": "cfj3f237d563f479f5223k23189dbb34",
    "api": "6.0.0",
    "session_expire": "2022-08-17T06:21:00+00:00",
    "update": "2021-07-21T02:51:36+00:00",
    "add": "2021-08-03T00:04:14+00:00",
    "clean": "2021-08-03T00:05:54+00:00",
    "songs": 75,
    "albums": 9,
    "artists": 17,
    "genres": 7,
    "playlists": 2,
    "searches": 17,
    "playlists_searches": 19,
    "users": 4,
    "catalogs": 4,
    "videos": 2,
    "podcasts": 2,
    "podcast_episodes": 13,
    "shares": 2,
    "licenses": 14,
    "live_streams": 3,
    "labels": 3
}
```

All future interactions with the Ampache API must include the `AUTHENTICATION_TOKEN` as a `GET` variable named `auth`.

## Methods

All methods must be passed as `action=METHODNAME`. All [data methods](https://ampache.org/api/#data-methods) can take an optional `offset=XXX` and `limit=XXX`. The limit determines the maximum number of results returned. The offset will tell Ampache where to start in the result set. For example if there are 100 total results and you set the offset to 50, and the limit to 50 Ampache will return results between 50 and 100. The default limit is 5000. The default offset is 0.

You can also pass it `limit=none` to overcome the `limit` limitation and return **all** the matching elements.

For more in depth information regarding the different api servers you can view the following documentation pages.

* [XML Documentation](https://ampache.org/api/api-xml-methods)
* [JSON Documentation](https://ampache.org/api/api-json-methods)

### Auth Methods

All Auth methods return HTTP 200 responses

* handshake
* goodbye
* ping
* register
* lost_password **Ampache 6.1.0+**

### Non-Data Methods

All Non-Data methods return HTTP 200 responses

* bookmarks
* system_update
* users
* user_preferences

### Data Methods

All Data methods return HTTP 200 responses

* [advanced_search](https://ampache.org/api/api-advanced-search)
* albums
* album
* album_songs
* artists
* artist
* artist_albums
* artist_songs
* bookmark **Ampache 6.1.0+**
* bookmark_create
* bookmark_delete
* bookmark_edit
* browse
* catalogs
* catalog
* catalog_action
* catalog_add
* catalog_delete
* catalog_file
* catalog_folder
* deleted_podcast_episodes
* deleted_songs
* deleted_videos
* flag
* followers
* following
* friends_timeline
* genres
* genre
* genre_albums
* genre_artists
* genre_songs
* get_bookmark
* get_external_metadata
* get_indexes
* get_lyrics
* get_similar
* index **Ampache 6.3.0+**
* labels
* label
* label_artists
* last_shouts
* licenses
* license
* license_songs
* list (Replaces get_indexes)
* live_streams
* live_stream
* live_stream_create
* live_stream_delete
* live_stream_edit
* now_playing **Ampache 6.3.1+**
* player **Ampache 6.4.0+**
* playlists
* playlist
* playlist_add **Ampache 6.3.0+**
* playlist_add_song
* playlist_create
* playlist_delete
* playlist_edit
* playlist_generate
* playlist_hash **Ampache 6.6.0+**
* playlist_remove_song
* playlist_songs
* podcasts
* podcast
* podcast_create
* podcast_delete
* podcast_edit
* podcast_episodes
* podcast_episode
* podcast_episode_delete
* preference_create
* preference_delete
* preference_edit
* rate
* record_play
* scrobble
* search_group **Ampache 6.3.0+**
* search  **Ampache 6.3.0+** (alias for [advanced_search](https://ampache.org/api/api-advanced-search))
* search_rules
* search_songs
* shares
* share
* share_create
* share_delete
* share_edit
* songs
* song
* song_delete
* song_tags
* stats
* system_preference
* system_preferences
* timeline
* toggle_follow
* update_art
* update_artist_info
* update_from_tags
* update_podcast
* url_to_song
* user
* user_create
* user_delete
* user_edit (Replaces user_update)
* user_playlists **Ampache 6.3.0+**
* user_preference
* user_smartlists **Ampache 6.3.0+**
* user_update
* videos
* video

### Binary Data Methods

All binary methods will not return XML/JSON responses. they will either return the requested file/data or an HTTP error code.

For information about about how playback works and what a client can expect from Ampache check out [API Media Methods](https://ampache.org/api/api-media-methods)

@return (HTTP 200 OK)

@throws (HTTP 400 Bad Request)

@throws (HTTP 404 Not Found)

@throws (HTTP 416 Range Not Satisfiable)

* download
* get_art
* stream

### Control Methods

All Control methods return HTTP 200 responses

* democratic
* localplay
* localplay_songs

## Access Levels

Some methods have a user access level requirement. Access goes from 0-100 and is split into the following types.

* 5: Guest
* 25: User
* 50: Content Manager
* 75: Catalog Manager
* 100: Admin

## Request URL Examples

For the purpose of this example the Ampache host is 'localhost' and the path to Ampache is /ampache

### Requesting all genres whose name starts with Rock

XML

```URL
http://localhost/ampache/server/xml.server.php?action=tags&auth=1234567890123456789012345678901&filter=Rock
```

JSON

```URL
http://localhost/ampache/server/json.server.php?action=tags&auth=1234567890123456789012345678901&filter=Rock
```

### Requesting all song titles, with an offset of 5000

XML

```URL
http://localhost/ampache/server/xml.server.php?action=songs&auth=12345678901234567890123456789012&offset=5000
```

JSON

```URL
http://localhost/ampache/server/json.server.php?action=songs&auth=12345678901234567890123456789012&offset=5000
```
