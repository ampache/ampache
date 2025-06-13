---
title: "Subsonic API"
metaTitle: "Subsonic API"
description: "API documentation"
---

## Subsonic API support

Ampache implements the [OpenSubsonic](https://opensubsonic.netlify.app/docs/) API as well as a pure [Subsonic](http://www.subsonic.org/pages/api.jsp) API.

Each user can enable OpenSubsonic by disabling the `Enable legacy Subsonic API responses for compatibility issues` preference on the Options page. (`preferences.php?tab=options`)

**Compatible Versions:**

* Ampache7.6.0 => Separated OpenSubsonic [docs](https://opensubsonic.netlify.app/docs/) & Subsonic API's [1.16.1](http://www.subsonic.org/pages/inc/api/schema/subsonic-rest-api-1.16.1.xsd)
* Ampache7 => OpenSubsonic extensions & Subsonic [1.16.1](http://www.subsonic.org/pages/inc/api/schema/subsonic-rest-api-1.16.1.xsd)
* Ampache6 => Subsonic [1.16.1](http://www.subsonic.org/pages/inc/api/schema/subsonic-rest-api-1.16.1.xsd)
* Ampache5 => Subsonic [1.13.0](http://www.subsonic.org/pages/inc/api/schema/subsonic-rest-api-1.13.0.xsd)
* Ampache4 => Subsonic [1.13.0](http://www.subsonic.org/pages/inc/api/schema/subsonic-rest-api-1.13.0.xsd)
* Ampache3 => Subsonic [1.11.0](http://www.subsonic.org/pages/inc/api/schema/subsonic-rest-api-1.11.0.xsd)

## OpenSubsonic API extension

[OpenSubsonic API](https://opensubsonic.netlify.app/docs/) is an open source initiative to create backward-compatible extensions for the original Subsonic API.

Ampache Subsonic support is being extended to support these changes

### Extensions

* Add [API Key Authentication](https://opensubsonic.netlify.app/docs/extensions/apikeyauth/)
* Add [getPodcastEpisode](https://opensubsonic.netlify.app/docs/extensions/getpodcastepisode/) method
* Expanded [subsonic-response](https://opensubsonic.netlify.app/docs/responses/subsonic-response/)
* Expanded [subsonic-response error](https://opensubsonic.netlify.app/docs/responses/error/)
* Support [HTTP form POST](https://opensubsonic.netlify.app/docs/extensions/formpost/)
* Add [songLyrics](https://opensubsonic.netlify.app/docs/extensions/songlyrics/) support
* Tentatively supported [Transcode Offset](https://opensubsonic.netlify.app/docs/extensions/transcodeoffset/) (Parameter is supported but untested)

#### Api Key authentication

The key that must be passed to Ampache is the API Key generated for a specific user (none by default, only the administrator can generate one).

Then call the following URL (Where localhost/ampache is the location of your Ampache installation):

```URL
http://localhost/ampache/rest/ping.view?apiKey=API_KEY&v=1.2.0&c=DSub&f=json
```

**NOTE** Do not send a user (u) parameter or auth will be rejected.

The key can be also be passed to Ampache using `SHA256(USER+KEY)` where `KEY` is `SHA256('APIKEY')`. Below is a PHP example

```PHP
$user = 'username';
$key = hash('sha256', 'myapikey');
$passphrase = hash('sha256', $user . $key);
```

#### HTTP Header Authentication

Ampache supports sending your apiKey parameter to the server using a Bearer Token.

The `apiKey` parameter does not need to be sent with your URL. We will check your header for a token first

```Text
GET http://localhost/ampache/rest/ping.view?v=1.2.0&c=DSub&f=jsonHTTP/1.1
Authorization: Bearer 000111112233334444455556667777788888899aaaaabbbbcccccdddeeeeeeff
```

### Endpoint extension

* Edit [search3](https://opensubsonic.netlify.app/docs/endpoints/search3/) to allow empty `query` argument
* Edit [savePlayQueue](https://opensubsonic.netlify.app/docs/endpoints/saveplayqueue/) to allow empty `id` argument
* Add [getOpenSubsonicExtensions](https://opensubsonic.netlify.app/docs/endpoints/getopensubsonicextensions/)

### Partially implemented

* [stream](https://opensubsonic.netlify.app/docs/endpoints/stream/)
  * Support `timeOffset` (Parameter is supported but untested)
  * Enable `subsonic_always_download` preference (`preferences.php?tab=options`) to stop scrobbling plays

### NOT implemented

* [getLyricsBySongId](https://opensubsonic.netlify.app/docs/endpoints/getlyricsbysongid/)
  * Currently Ampache lyrics do not track individual lines or timestamps

## Subsonic Examples

You can get examples from an official Subsonic release as well as examples from Ampache.

These servers are using a Subsonic 1.16.1 compatible API version.

* [Ampache 7.0.0 (1.16.1+opensubsonic)](https://github.com/ampache/python3-ampache/tree/api6/docs/ampache-opensubsonic)
* [Ampache 6.0.0 (1.16.1)](https://github.com/ampache/python3-ampache/tree/api6/docs/ampache-subsonic)
* [Subsonic 6.1.6 (1.16.1)](https://github.com/ampache/python3-ampache/tree/api6/docs/subsonic-6.1.6)
