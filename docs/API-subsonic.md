---
title: "Subsonic API"
metaTitle: "Subsonic API"
description: "API documentation"
---

## Subsonic API support

Ampache implements the [Subsonic API](http://www.subsonic.org/pages/api.jsp) with some minor extensions for clients.

**Compatible Versions:**

* Ampache7 => OpenSubsonic extensions & Subsonic [1.16.1](http://www.subsonic.org/pages/inc/api/schema/subsonic-rest-api-1.16.1.xsd)
* Ampache6 => Subsonic [1.16.1](http://www.subsonic.org/pages/inc/api/schema/subsonic-rest-api-1.16.1.xsd)
* Ampache5 => Subsonic [1.13.0](http://www.subsonic.org/pages/inc/api/schema/subsonic-rest-api-1.13.0.xsd)
* Ampache4 => Subsonic [1.13.0](http://www.subsonic.org/pages/inc/api/schema/subsonic-rest-api-1.13.0.xsd)
* Ampache3 => Subsonic [1.11.0](http://www.subsonic.org/pages/inc/api/schema/subsonic-rest-api-1.11.0.xsd)

## OpenSubsonic API extension

[OpenSubsonic API](https://opensubsonic.netlify.app/docs/) is an open source initiative to create backward-compatible extensions for the original Subsonic API.

Ampache Subsonic support is being extended to support these changes

### Extensions

* Expanded [subsonic-response](https://opensubsonic.netlify.app/docs/responses/subsonic-response/)
* Support [HTTP form POST](https://opensubsonic.netlify.app/docs/extensions/formpost/)
* Tentatively supported [Transcode Offset](https://opensubsonic.netlify.app/docs/extensions/transcodeoffset/) (Parameter is supported but untested)

### Endpoint extenstion

* Edit [search3](https://opensubsonic.netlify.app/docs/endpoints/search3/) to allow empty query argument
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
