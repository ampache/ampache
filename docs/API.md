---
title: "API 4.3"
metaTitle: "API 4.3"
metaDescription: "API documentation"
---

**Compatible Versions:**

* 4.2.0-release
* 4.2.1-release
* 4.2.2-release
* 4.2.3-release
* 4.2.4-release
* 4.2.5-release
* 4.2.6-release
* 4.3.0-release

Ampache Provides an API for pulling out it's meta data in the form of simple XML documents. This was originally created for use by [Amarok](http://amarok.kde.org/), but there is no reason it couldn't be used to create other front-ends to the Ampache data. Access to the API is controlled by the Internal [Access Control Lists](API-acls.md). The KEY defined in the ACL is the passphrase that must be used to establish an API session. Currently all requests are limited to a maximum of 5000 results for performance reasons. To get additional results pass offset as an additional parameter.
If you have any questions or requests for this API please submit a [Feature Request](https://github.com/ampache/ampache/issues?state=closed). All dates in the API calls should be passed as [ISO 8601](http://en.wikipedia.org/wiki/ISO_8601) dates.

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

In API 400001 the key that must be passed to Ampache is `SHA256(USER+KEY)` where `KEY` is `SHA256('APIKEY')`. Below is a PHP example

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

## Errors

Ampache's XML errors are loosely based around the HTTP status codes. All errors are returned in the form of an XML Document however the string error message provided is translated into the language of the Ampache server in question. All services should only use the code value.

## Example Error

```xml
<root>
      <error code="501">Access Control Not Enabled</error>
</root>
```

## Current Error Codes

All error codes are accompanied by a string value for the error and derived from the [HTTP/1.1 Status Codes](http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html)

* **501**
  * This is a fatal error, the Ampache server you have requested does not currently have access control enabled. The API is disabled.
* **400**
  * Used when you have specified a valid method but something about the input is incorrect / invalid. See Error message for details, but do not re-attempt the exact same request.
* **401**
  * This is a temporary error, this means no valid session was passed or the handshake failed. This should be an indication for services to attempt another handshake
* **403**
  * This is a fatal error, the ACL on the Ampache server prevents access from the current source IP Address.
* **405**
  * This is a fatal error, the service requested a method that the API does not implement.

## Methods

All methods must be passed as `action=METHODNAME`. All methods except the `handshake` can take an optional `offset=XXX` and `limit=XXX`. The limit determines the maximum number of results returned. The offset will tell Ampache where to start in the result set. For example if there are 100 total results and you set the offset to 50, and the limit to 50 Ampache will return results between 50 and 100. The default limit is 5000. The default offset is 0.

You can also pass it `limit=none` to overcome the `limit` limitation and return **all** the matching elements.

For more in depth information regarding the different api servers you can view the following documentation pages.

* [XML Documentation (4.3.0)](API-XML-methods.md)
* [JSON Documentation (4.3.0)](API-JSON-methods.md)

### Non-Data Methods

* handshake
* ping
* goodbye
* url_to_song
* check_parameter
* message

### Data Methods

* get_indexes
* artists
* artist
* artist_songs
* artist_albums
* albums
* album
* album_songs
* tags
* tag
* tag_artists
* tag_albums
* tag_songs
* songs
* song
* [advanced_search](https://github.com/ampache/ampache/wiki/advanced-search-4-2-0)
* stats
* playlists
* playlist
* playlist_songs
* playlist_create
* playlist_edit
* playlist_delete
* playlist_add_song
* playlist_remove_song
* playlist_generate
* search_songs
* videos
* video
* shares
* share
* share_create
* share_edit
* share_delete
* get_similar
* podcasts
* podcast
* podcast_create
* podcast_edit
* podcast_delete
* podcast_episodes
* podcast_episode
* podcast_episode_delete
* catalogs
* catalog
* catalog_file
* licenses
* license
* license_songs
* user
* user_create
* user_update
* user_delete
* stream
* download
* get_art
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

### Control Methods

* localplay
* democratic

## Request URL Examples

For the purpose of this example the Ampache host is 'localhost' and the path to Ampache is /ampache

### Requesting all genres whose name starts with Rock

XML
```
http://localhost/ampache/server/xml.server.php?action=tags&auth=1234567890123456789012345678901&filter=Rock
```

JSON
```
http://localhost/ampache/server/json.server.php?action=tags&auth=1234567890123456789012345678901&filter=Rock
```

### Requesting all song titles, with an offset of 5000

XML
```
http://localhost/ampache/server/xml.server.php?action=songs&auth=12345678901234567890123456789012&offset=5000
```

JSON
```
http://localhost/ampache/server/json.server.php?action=songs&auth=12345678901234567890123456789012&offset=5000
```
