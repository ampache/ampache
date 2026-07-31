<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

/**
 * Capture Subsonic/OpenSubsonic responses from a running Ampache server into the fixture corpus used by
 * tests/Module/Api/SubsonicSpecConformanceTest.php. The fixtures are committed so the conformance test can
 * run offline in CI; re-run this whenever the serializers change shape.
 *
 * Usage:
 *   php resources/scripts/api-docs/capture_subsonic_fixtures.php --url=http://localhost:8084 --user=admin --pass=demodemo
 *
 * The `subsonic` (legacy) corpus requires the `subsonic_legacy` preference enabled for that user; the
 * `opensubsonic` corpus requires it disabled. Pass --mode to say which corpus is being captured.
 */

$options = getopt('', ['url:', 'user:', 'pass:', 'mode:']);
$baseUrl = rtrim((string) ($options['url'] ?? 'http://localhost:8084'), '/');
$user    = (string) ($options['user'] ?? 'admin');
$pass    = (string) ($options['pass'] ?? '');
$mode    = (string) ($options['mode'] ?? 'opensubsonic');

if ($pass === '') {
    fwrite(STDERR, "--pass is required\n");
    exit(1);
}
if (!in_array($mode, ['subsonic', 'opensubsonic'], true)) {
    fwrite(STDERR, "--mode must be 'subsonic' or 'opensubsonic'\n");
    exit(1);
}

// endpoint => extra query parameters. Ids are resolved before capture. Mutating endpoints are left out (they answer
// with the `EmptySubsonicResponse` that `ping` captures) and so are binary ones, which have no schema to validate.
$endpoints = [
    'ping' => '',
    'getLicense' => '',
    'getMusicFolders' => '',
    'getIndexes' => '',
    'getArtists' => '',
    'getArtist' => 'id={artist}',
    'getAlbum' => 'id={album}',
    'getSong' => 'id={song}',
    'getGenres' => '',
    'getAlbumList' => 'type=newest&size=3',
    'getAlbumList2' => 'type=newest&size=3',
    'getRandomSongs' => 'size=3',
    'getNowPlaying' => '',
    'getStarred' => '',
    'getStarred2' => '',
    'getPlaylists' => '',
    'search2' => 'query=a&songCount=3',
    'search3' => 'query=a&songCount=3',
    'getUser' => 'username={user}',
    'getUsers' => '',
    'getScanStatus' => '',
    'getPodcasts' => '',
    'getInternetRadioStations' => '',
    'getBookmarks' => '',
    'getPlayQueue' => '',
    'getShares' => '',
    'getMusicDirectory' => 'id={artist}',
    'getVideos' => '',
    'getChatMessages' => '',
    'getArtistInfo' => 'id={artist}',
    'getArtistInfo2' => 'id={artist}',
    'getAlbumInfo' => 'id={album}',
    'getSimilarSongs' => 'id={artist}',
    'getSimilarSongs2' => 'id={artist}',
    'getNewestPodcasts' => '',
    // The rest of the documented read surface. Nothing here writes, so a capture run stays idempotent when repeated.
    'getAlbumInfo2' => 'id={album}',
    'getSongsByGenre' => 'genre={genre}&count=3',
    'getTopSongs' => 'artist={artistName}&count=3',
    'getLyrics' => 'artist={artistName}&title={songName}',
    'getLyricsBySongId' => 'id={song}',
    'getPodcastEpisode' => 'id={song}',
    'getPlayQueueByIndex' => '',
    'getOpenSubsonicExtensions' => '',
    'tokenInfo' => '',
    'getVideoInfo' => 'id={song}',
    'jukeboxControl' => 'action=status',
    'getSonicSimilarTracks' => 'id={song}&count=3',
    'findSonicPath' => 'startSongId={song}&endSongId={song}&count=3',
    'reportPlayback' => 'mediaId={song}&mediaType=song&positionMs=0&state=paused&ignoreScrobble=true',
];

$auth = http_build_query(['u' => $user, 'p' => $pass, 'v' => '1.16.1', 'c' => 'fixture-capture']);

// obfuscation placeholders, matching the conventions in capture_reads.py
const OBF_HOST = 'music.com.au';
const OBF_SSID = 'cfj3f237d563f479f5223k23189dbb34';
const OBF_AUTH = 'eeb9f1b6056246a7d563f479f518bb34';

/**
 * Strip anything host- or credential-specific before a response is written to the committed corpus.
 *
 * Stream and art urls embed a live `ssid`/`auth` token, so a capture taken against a real server would
 * otherwise commit working credentials.
 */
function obfuscate(string $body, string $baseUrl): string
{
    $host = (string) preg_replace('~^https?://~', '', rtrim($baseUrl, '/'));

    $body = str_replace($baseUrl, 'https://' . OBF_HOST, $body);
    $body = str_replace($host, OBF_HOST, $body);

    $body = (string) preg_replace('/ssid=[A-Za-z0-9]+/', 'ssid=' . OBF_SSID, $body);

    return (string) preg_replace('/auth=[A-Za-z0-9]+/', 'auth=' . OBF_AUTH, $body);
}

/**
 * Fetch a single endpoint in the requested format.
 */
function fetch(string $baseUrl, string $auth, string $action, string $extra, string $format): string
{
    $query = $auth . '&f=' . $format . ($extra !== '' ? '&' . $extra : '');
    $ch    = curl_init(sprintf('%s/rest/%s.view?%s', $baseUrl, $action, $query));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30]);
    $body = curl_exec($ch);
    curl_close($ch);

    return is_string($body) ? $body : '';
}

// Resolve a usable artist/album/song id so the detail endpoints return real payloads. Browse endpoints are
// used rather than search because the pure Subsonic and OpenSubsonic search paths do not behave identically.
$ids = ['{user}' => $user];

$artists         = json_decode(fetch($baseUrl, $auth, 'getArtists', '', 'json'), true);
$ids['{artist}'] = (string) ($artists['subsonic-response']['artists']['index'][0]['artist'][0]['id'] ?? '');

$albums         = json_decode(fetch($baseUrl, $auth, 'getAlbumList2', 'type=newest&size=1', 'json'), true);
$ids['{album}'] = (string) ($albums['subsonic-response']['albumList2']['album'][0]['id'] ?? '');

$songs         = json_decode(fetch($baseUrl, $auth, 'getRandomSongs', 'size=1', 'json'), true);
$ids['{song}'] = (string) ($songs['subsonic-response']['randomSongs']['song'][0]['id'] ?? '');

// Some endpoints key off a name rather than an id, so they are read back off the same rows the ids came from.
$ids['{songName}']   = (string) ($songs['subsonic-response']['randomSongs']['song'][0]['title'] ?? '');
$ids['{artistName}'] = (string) ($artists['subsonic-response']['artists']['index'][0]['artist'][0]['name'] ?? '');

$genres         = json_decode(fetch($baseUrl, $auth, 'getGenres', '', 'json'), true);
$ids['{genre}'] = (string) ($genres['subsonic-response']['genres']['genre'][0]['value'] ?? '');

foreach (['{artist}', '{album}', '{song}'] as $key) {
    if ($ids[$key] === '') {
        fwrite(STDERR, sprintf("could not resolve %s - is the catalog populated?\n", $key));
        exit(1);
    }
}

$targetDir = __DIR__ . '/../../../tests/Fixtures/Api/' . $mode;
if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
    fwrite(STDERR, sprintf("could not create %s\n", $targetDir));
    exit(1);
}

$written = 0;
foreach ($endpoints as $action => $extra) {
    $extra = strtr($extra, $ids);
    foreach (['xml', 'json'] as $format) {
        $body = fetch($baseUrl, $auth, $action, $extra, $format);
        if ($body === '') {
            fwrite(STDERR, sprintf("SKIP %s.%s (empty response)\n", $action, $format));
            continue;
        }
        file_put_contents(sprintf('%s/%s.%s', $targetDir, $action, $format), obfuscate($body, $baseUrl));
        $written++;
    }
}

printf("wrote %d %s fixtures to %s\n", $written, $mode, $targetDir);
