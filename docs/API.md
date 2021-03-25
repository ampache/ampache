---
title: "API 5"
metaTitle: "API 5"
metaDescription: "API documentation"
---

The [Ampache API](https://ampache.org/api/) Provides methods for pulling out it's meta data in the form of
simple XML (and JSON!) documents. This was originally created for use by [Amarok](https://ampache.org/api/http://amarok.kde.org/),
but there is no reason it couldn't be used to create other front-ends to the Ampache data.

Access to the API is controlled by the Internal [Access Control Lists](https://ampache.org/api/api-acls).
Currently all requests are limited to a maximum of 5000 results for performance reasons. To get additional results
pass offset as an additional parameter.

If you have any questions or requests for this API please submit a [Feature Request](https://github.com/ampache/ampache/issues/new?assignees=&labels=&template=feature_request.md&title=%5BFeature+Request%5D).
All dates in the API calls should be passed as [ISO 8601](http://en.wikipedia.org/wiki/ISO_8601) dates.

## Current Stable Release

The current stable release is documented under it's own version page.
Refer to the taged versions if you just want to follow the stable releases.

* [Ampache 4.4.0-release](https://github.com/ampache/ampache/releases/tag/4.4.0)
* [API 4 Documentation](https://ampache.org/api/api-4)

## Archived Version Documentation

After each release, a documentation page will be created to allow pruning old features from the current version.
Note that API 4.1 docs cover all previous versions.

* [API 4.3 Documentation](https://ampache.org/api/versions/api-4.3)
* [API 4.2 Documentation](https://ampache.org/api/versions/api-4.2)
* [API 4.1 Documentation](https://ampache.org/api/versions/api-4.1)

## Changelog API develop

All API code that used 'Tag' now references 'Genre' instead

### Added

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

### Changed

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

## Sending Handshake Request

Multiple authentication methods are available, described in the next sections.

### User / Password

The handshake is a combination of the following three things

* Encoded Passphrase
* Timestamp
* Username

The key that must be passed to Ampache is `SHA256(TIME+KEY)` where `KEY` is `SHA256('PASSWORD')`. Below is a PHP example

```PHP
$time = time();
$key = hash('sha256','mypassword');
$passphrase = hash('sha256',$time . $key);
```

Once you've generated the encoded passphrase, you can call the following URL (localhost/ampache is the location of your Ampache installation)

```Text
http://localhost/ampache/server/xml.server.php?action=handshake&auth=PASSPHRASE&timestamp=TIME&version=350001&user=USER
```

### Api Key

The key that must be passed to Ampache is the API Key generated for a specific user (none by default, only the administrator can generate one). Then call the following URL (localhost/ampache is the location of your Ampache installation):

```Text
http://localhost/ampache/server/xml.server.php?action=handshake&auth=API_KEY&version=350001
```

In API 4.0.0 and higher; the key can be passed to Ampache using `SHA256(USER+KEY)` where `KEY` is `SHA256('APIKEY')`. Below is a PHP example

```PHP
$user = 'username';
$key = hash('sha256', 'myapikey');
$passphrase = hash('sha256', $user . $key);
```

### Other handshake-related stuff

#### Ampache sheme

To standardize how to transfer Ampache connection information, the following Ampache scheme is defined.

```Text
ampache://authentication@hostname[:port]/subdirectory[#parameters]
```

for example:

* ampache://myuser:mypwd@localhost/ampache
* ampache://yourapikey@localhost:993/ampache#ssl=true

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
    <auth><%AUTHENTICATION TOKEN%></auth>
    <api><%APIVERSION%></api>
    <session_expire><![CDATA[2019-12-03T09:36:46+10:00]]></session_expire>
    <update><![CDATA[2019-11-26T16:35:05+10:00]]></update>
    <add><![CDATA[2019-12-03T06:42:55+10:00]]></add>
    <clean><![CDATA[2019-12-03T06:41:02+10:00]]></clean>
    <songs><![CDATA[268302]]></songs>
    <albums><![CDATA[25686]]></albums>
    <artists><![CDATA[11055]]></artists>
    <playlists><![CDATA[20]]></playlists>
    <videos><![CDATA[0]]></videos>
    <catalogs><![CDATA[4]]></catalogs>
</root>
```

For JSON

```JSON
{
    "auth": "%AUTHENTICATION TOKEN%",
    "api": "%APIVERSION%",
    "session_expire": "2020-01-28T13:59:24+10:00",
    "update": "2020-01-24T19:29:35+10:00",
    "add": "2020-01-28T04:49:18+10:00",
    "clean": "2020-01-28T04:47:28+10:00",
    "songs": "274209",
    "albums": "26275",
    "artists": "11275",
    "playlists": 31,
    "videos": "0",
    "catalogs": "4"
}
```

All future interactions with the Ampache API must include the `AUTHENTICATION_TOKEN` as a `GET` variable named `auth`.

## Methods

All methods must be passed as `action=METHODNAME`. All methods except the `handshake` can take an optional `offset=XXX` and `limit=XXX`. The limit determines the maximum number of results returned. The offset will tell Ampache where to start in the result set. For example if there are 100 total results and you set the offset to 50, and the limit to 50 Ampache will return results between 50 and 100. The default limit is 5000. The default offset is 0.

You can also pass it `limit=none` to overcome the `limit` limitation and return **all** the matching elements.

For more in depth information regarding the different api servers you can view the following documentation pages.

* [XML Documentation](https://ampache.org/api/api-xml-methods)
* [JSON Documentation](https://ampache.org/api/api-json-methods)

### Auth Methods

All Auth methods return HTTP 200 responses

* handshake
* ping
* goodbye

### Non-Data Methods

All Non-Data methods return HTTP 200 responses

* system_update **(develop only)**
* users **(develop only)**
* user_preferences **(develop only)**
* bookmarks **(develop only)**

### Data Methods

All Data methods return HTTP 200 responses

* get_indexes
* [advanced_search](https://ampache.org/api/api-advanced-search)
* artists
* artist
* artist_songs
* artist_albums
* albums
* album
* album_songs
* genres **(develop only)**
* genre **(develop only)**
* genre_artists **(develop only)**
* genre_albums **(develop only)**
* genre_songs **(develop only)**
* songs
* song
* song_delete **(develop only)**
* url_to_song
* playlists
* playlist
* playlist_songs
* playlist_create
* playlist_edit
* playlist_delete
* playlist_add_song
* playlist_remove_song
* playlist_generate
* shares
* share
* share_create
* share_edit
* share_delete
* get_similar
* search_songs
* videos
* video
* podcasts
* podcast
* podcast_create
* podcast_edit
* podcast_delete
* podcast_episodes
* podcast_episode
* podcast_episode_delete
* stats
* catalogs
* catalog
* catalog_file
* licenses
* license
* license_songs
* labels **(develop only)**
* label **(develop only)**
* label_artists **(develop only)**
* user
* user_create
* user_update
* user_delete
* rate
* flag
* record_play
* scrobble
* followers
* following
* toggle_follow
* last_shouts
* timeline
* friends_timeline
* catalog_action
* update_from_tags
* update_artist_info
* update_art
* update_podcast
* user_preference **(develop only)**
* system_preferences **(develop only)**
* system_preference **(develop only)**
* preference_create **(develop only)**
* preference_edit **(develop only)**
* preference_delete **(develop only)**
* get_bookmark **(develop only)**
* bookmark_create **(develop only)**
* bookmark_edit **(develop only)**
* bookmark_delete **(develop only)**

### Binary Data Methods

All binary methods will not return XML/JSON responses. they will either return the requested file/data or an HTTP error code.

@return (HTTP 200 OK)

@throws (HTTP 400 Bad Request)

@throws (HTTP 404 Not Found)

* stream
* download
* get_art

### Control Methods

All Control methods return HTTP 200 responses

* localplay
* democratic

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

```XML
http://localhost/ampache/server/xml.server.php?action=tags&auth=1234567890123456789012345678901&filter=Rock
```

JSON

```JSON
http://localhost/ampache/server/json.server.php?action=tags&auth=1234567890123456789012345678901&filter=Rock
```

### Requesting all song titles, with an offset of 5000

XML

```XML
http://localhost/ampache/server/xml.server.php?action=songs&auth=12345678901234567890123456789012&offset=5000
```

JSON

```JSON
http://localhost/ampache/server/json.server.php?action=songs&auth=12345678901234567890123456789012&offset=5000
```
