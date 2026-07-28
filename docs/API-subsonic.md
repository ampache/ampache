# Subsonic API Support

**NOTE** Ampache8 will force all user preferences to the OpenSubsonic implementation by updating preferences to use the new version.

Ampache implements the [OpenSubsonic](https://opensubsonic.netlify.app/docs/) API as well as a pure [Subsonic](http://www.subsonic.org/pages/api.jsp) API.

Users who want to use a pure Subsonic implementation can enable the `Enable legacy Subsonic API responses for compatibility issues` preference on the Options page. (`preferences.php?tab=options`)

**Compatible Versions:**

* Ampache 8.0.0 => Force default to OpenSubsonic and clean up Subsonic to a pure 1.16.1 implementation
* Ampache 7.6.0 => Separated OpenSubsonic [docs](https://opensubsonic.netlify.app/docs/) & Subsonic API's [1.16.1](http://www.subsonic.org/pages/inc/api/schema/subsonic-rest-api-1.16.1.xsd)
* Ampache7 => OpenSubsonic extensions & Subsonic [1.16.1](http://www.subsonic.org/pages/inc/api/schema/subsonic-rest-api-1.16.1.xsd)
* Ampache6 => Subsonic [1.16.1](http://www.subsonic.org/pages/inc/api/schema/subsonic-rest-api-1.16.1.xsd)
* Ampache5 => Subsonic [1.13.0](http://www.subsonic.org/pages/inc/api/schema/subsonic-rest-api-1.13.0.xsd)
* Ampache4 => Subsonic [1.13.0](http://www.subsonic.org/pages/inc/api/schema/subsonic-rest-api-1.13.0.xsd)
* Ampache3 => Subsonic [1.11.0](http://www.subsonic.org/pages/inc/api/schema/subsonic-rest-api-1.11.0.xsd)

## Authentication

Subsonic clients authenticate with either token auth (`u`, `t`, `s`) or a plaintext password (`u`, `p`). Both need a credential the server can read back, and your Ampache login password is stored as a one-way sha256 hash, so it can not be used.

Set a **Subsonic Password** on your account page (`preferences.php?tab=account`) and use that as the password in your client. Administrators can set it for another user from the user edit page, or from the cli:

```shell
bin/cli admin:updateUser some-user --subsonic 'some-password'
```

It is stored encrypted with the `secret_key` from your Ampache config rather than hashed, because token auth requires the server to recompute `md5(password + salt)`. Changing `secret_key` invalidates every stored Subsonic Password and they have to be set again.

Your API key also still works as the Subsonic password with either auth method, so clients configured before this existed keep working.

## OpenSubsonic API extension

[OpenSubsonic API](https://opensubsonic.netlify.app/docs/) is an open source initiative to create backward-compatible extensions for the original Subsonic API.

Ampache Subsonic support is being extended to support these changes

### Spec compliance

**Audited against the OpenSubsonic specification on 2026-07-27.**

The spec is a moving target — upstream rebuilds it continuously and states the schema and prose are still being
reconciled — so the audit is pinned to one build rather than to "latest". `docs/openapi-opensubsonic.json` is that
build, copied verbatim from `https://opensubsonic.netlify.app/docs/openapi/openapi.json` and **never hand-edited**;
`tests/Module/Api/OpenSubsonicSpecVersionTest.php` records its checksum and endpoint count so a refreshed copy fails
CI and prompts a re-audit instead of drifting silently.

To check for upstream changes, re-download that file and run `composer qa`. If the checksum test fails, re-audit,
then update the checksum in that test and the date above.

At the audit date Ampache implements all 87 documented endpoints. Known gaps, all optional response fields with no
column in the Ampache schema: `bpm`, `moods`, `works`, `movements`, `groupings`, `bitDepth`, `explicitStatus`,
`isCompilation`, `disambiguation`, `fallbackGain`, `subRole`, `shortcut`. Nothing is awaiting a schema change any
more: `played` (`800029`), `positionMs`/`playbackRate`/`state` on `nowPlaying` (`800032`) and `recordLabels`
(`800034`) each got the column they needed, and `lastFmUrl` is threaded through the cached last.fm results.

`played` is the one worth spelling out: database version `800029` added a maintained `last_played` column to every
table carrying a play counter, written on the same statement that increments it. Songs, albums, videos and podcast
episodes therefore report `played` as an ISO 8601 instant, in both json and xml. It is
**server-wide, with no user predicate** — the same scoping as `playCount` and `averageRating`, where `starred`
and `userRating` are the per-user pair. A database upgraded to `800029` backfills the column from the existing
play history, so the field is populated for plays recorded before the upgrade as well.

#### Where the schema and the documentation disagree

Upstream states the schema is still being reconciled with the prose and that servers and clients should trust the
documentation until that work finishes. These are the divergences that affected this implementation, recorded so
the next audit does not re-derive them:

* **The schema is json-only.** It declares `format=json` required and does not describe xml responses at all.
  Ampache serves both, and the xml responses carry the same OpenSubsonic fields; only the json corpus can be
  machine-validated, so `tests/Module/Api/SubsonicSpecConformanceTest.php` checks json against this schema and the
  pure Subsonic xml against the official 1.16.1 XSD.
* **Extension endpoints are documented as returning 404 when unimplemented.** Ampache returns the Subsonic error
  envelope with code 30 instead, because a Subsonic client parses the body rather than the status code, and this
  matches how every other unsupported action already behaves. This is what `getSonicSimilarTracks` and
  `findSonicPath` return with no sonic-analysis plugin installed.
* **Not every extension is named in the schema.** `indexBasedQueue`, `sonicSimilarity`, `transcodeOffset` and
  `formPost` have documentation pages but appear nowhere in `openapi-opensubsonic.json`, so their exact name
  strings were taken from those pages. Only the six the schema does name are pinned by the version test.
* **Extension-only parameters are always present in the schema**, marked in their description, rather than being
  conditional — so a parameter existing there does not by itself imply the extension is required.
* **The schema carries no examples**, by deliberate upstream choice, so it cannot be used to check response values.
* **Some response wrappers are named but unused.** `FindSonicPathResult` and `SonicSimilarTracksResult` exist as
  schemas, but the actual success responses put `sonicMatch` directly on the `subsonic-response`.
* **`getTranscodeDecision` declares its response schema inline** rather than as a `$ref`, so it is the one endpoint
  the conformance test cannot resolve a schema name for.

### Extensions

All are advertised by [getOpenSubsonicExtensions](https://opensubsonic.netlify.app/docs/endpoints/getopensubsonicextensions/) at version 1.

| Extension                                                                                  | Notes                                                                                                                    |
|--------------------------------------------------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------|
| [apiKeyAuthentication](https://opensubsonic.netlify.app/docs/extensions/apikeyauth/)       | see below; adds `tokenInfo`                                                                                              |
| [formPost](https://opensubsonic.netlify.app/docs/extensions/formpost/)                     | HTTP form POST                                                                                                           |
| [getPodcastEpisode](https://opensubsonic.netlify.app/docs/extensions/getpodcastepisode/)   |                                                                                                                          |
| [indexBasedQueue](https://opensubsonic.netlify.app/docs/extensions/indexbasedqueue/)       | `getPlayQueueByIndex`, `savePlayQueueByIndex`                                                                            |
| [playbackReport](https://opensubsonic.netlify.app/docs/extensions/playbackreport/)         | `reportPlayback`; `ignoreScrobble` suppresses play counts                                                                |
| [songLyrics](https://opensubsonic.netlify.app/docs/extensions/songlyrics/)                 | `getLyricsBySongId`; `enhanced=true` returns word-level `cueLine` data when the stored lyrics carry Enhanced LRC timings |
| [sonicSimilarity](https://opensubsonic.netlify.app/docs/extensions/sonicsimilarity/)       | **only advertised when a sonic-analysis plugin is enabled** — see below                                                  |
| [topSongsByArtistId](https://opensubsonic.netlify.app/docs/extensions/topsongsbyartistid/) | `getTopSongs` accepts `id` as well as `artist`                                                                           |
| [transcodeOffset](https://opensubsonic.netlify.app/docs/extensions/transcodeoffset/)       | `timeOffset` on `stream`                                                                                                 |
| [transcoding](https://opensubsonic.netlify.app/docs/extensions/transcoding/)               | `getTranscodeDecision` (POST, JSON capabilities body) and `getTranscodeStream`                                           |

Also implemented, outside the named extensions:

* Expanded [subsonic-response](https://opensubsonic.netlify.app/docs/responses/subsonic-response/)
* Expanded [subsonic-response error](https://opensubsonic.netlify.app/docs/responses/error/)

#### Sonic similarity

`getSonicSimilarTracks` and `findSonicPath` need similarity derived from analysing the audio, which Ampache does not
do itself. They are served by a plugin implementing `PluginSonicAnalysisInterface`, and the `sonicSimilarity`
extension is advertised only while such a plugin is installed and enabled for the user — with none installed both
endpoints report the feature as unsupported rather than answering with metadata similarity, which is a different
thing entirely.

[AudioMuse-AI](https://github.com/NeptuneHub/AudioMuse-AI) is supported out of the box: install the **AudioMuse**
plugin and set its server URL. It indexes by the music server's own item id, so Ampache song ids pass straight
through; its distance scores are inverted into the normalised `[0,1]` similarity the spec asks for.

A backend that has not analysed the requested track answers with a message of its own; it is written to the Ampache
log and both endpoints answer with an empty list.

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

```text
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
* [getLyricsBySongId](https://opensubsonic.netlify.app/docs/endpoints/getlyricsbysongid/)
  * `kind` and `agents` are not returned. Ampache stores one unattributed lyric layer, `main` is already the default
    when `kind` is absent, and the spec says agents should not appear without multiple vocal layers.

## Subsonic Examples

You can get examples from an official Subsonic release as well as examples from Ampache.

These servers are using a Subsonic 1.16.1 compatible API version.

* [Ampache 7.0.0 (1.16.1+opensubsonic)](https://github.com/ampache/python3-ampache/tree/api6/docs/ampache-opensubsonic)
* [Ampache 6.0.0 (1.16.1)](https://github.com/ampache/python3-ampache/tree/api6/docs/ampache-subsonic)
* [Subsonic 6.1.6 (1.16.1)](https://github.com/ampache/python3-ampache/tree/api6/docs/subsonic-6.1.6)
