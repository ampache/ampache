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
 * Capture Ampache REST responses into the fixture corpus used by tests/Module/Api/RestSpecConformanceTest.php.
 *
 * Only read-only GET paths that need no path parameter beyond an id are captured, so the script never mutates
 * the server it points at. Fixtures are committed so the conformance test can run offline in CI.
 *
 * Usage:
 *   php resources/scripts/api-docs/capture_rest_fixtures.php --url=http://localhost:8084 --user=admin --pass=demodemo
 */

$options = getopt('', ['url:', 'user:', 'pass:', 'apikey:', 'cacert:', 'env::']);

// --env reads the same gitignored verify.env used by verify_openapi_shapes.py so credentials never have to be
// passed on the command line. Explicit flags still win.
$config = [];
if (array_key_exists('env', $options)) {
    $envPath = ($options['env'] !== false && $options['env'] !== '')
        ? (string) $options['env']
        : __DIR__ . '/verify.env';
    if (!is_file($envPath)) {
        fwrite(STDERR, sprintf("env file not found: %s\n", $envPath));
        exit(1);
    }
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value]  = explode('=', $line, 2);
        $config[trim($key)] = trim($value);
    }
}

$baseUrl = rtrim((string) ($options['url'] ?? $config['AMPACHE_HOST'] ?? 'http://localhost:8084'), '/');
$user    = (string) ($options['user'] ?? $config['AMPACHE_USER'] ?? 'admin');
$pass    = (string) ($options['pass'] ?? $config['AMPACHE_PASSWORD'] ?? '');
$apikey  = (string) ($options['apikey'] ?? $config['AMPACHE_APIKEY'] ?? '');

if ($pass === '' && $apikey === '') {
    fwrite(STDERR, "a password or api key is required (pass --pass/--apikey or --env)\n");
    exit(1);
}

// AMPACHE_VERIFY_SSL mirrors verify_openapi_shapes.py: anything but 0/false/empty keeps verification on
$verifySsl = !in_array((string) ($config['AMPACHE_VERIFY_SSL'] ?? '1'), ['0', 'false', 'False', ''], true);
$caBundle  = (string) ($options['cacert'] ?? getenv('CURL_CA_BUNDLE') ?: '');
if ($verifySsl && $caBundle === '') {
    foreach ([ini_get('curl.cainfo'), ini_get('openssl.cafile'), 'C:/Program Files/Git/mingw64/etc/ssl/certs/ca-bundle.crt'] as $candidate) {
        if (is_string($candidate) && $candidate !== '' && is_file($candidate)) {
            $caBundle = $candidate;
            break;
        }
    }
}

// obfuscation placeholders, matching the conventions in capture_reads.py
const OBF_HOST = 'music.com.au';
const OBF_SSID = 'cfj3f237d563f479f5223k23189dbb34';
const OBF_AUTH = 'eeb9f1b6056246a7d563f479f518bb34';
const OBF_MUSIC_ROOT = '/media/music';
const OBF_EMAIL = 'user@music.com.au';
const OBF_USER = 'apiuser';

/**
 * Strip anything host-, credential- or filesystem-specific before a response is written to the committed corpus.
 *
 * Play urls embed a live `ssid`/`streamtoken`, and `filename` exposes the server's music root, so a capture
 * taken against a real server would otherwise commit working credentials and someone's directory layout.
 */
function obfuscate(string $body, string $baseUrl, string $token, string $musicRoot, string $user = ''): string
{
    $host = (string) preg_replace('~^https?://~', '', rtrim($baseUrl, '/'));

    // Replace the account name only where it is an actual field value. A blanket search/replace would corrupt
    // unrelated text, since a short username matches inside ordinary metadata.
    if ($user !== '') {
        $quoted = preg_quote($user, '/');
        $body   = (string) preg_replace('/("(?:username|owner)"\s*:\s*")' . $quoted . '(")/i', '${1}' . OBF_USER . '${2}', $body);
        $body   = (string) preg_replace('/((?:username|owner)=")' . $quoted . '(")/i', '${1}' . OBF_USER . '${2}', $body);
    }

    $body = str_replace($baseUrl, 'https://' . OBF_HOST, $body);
    $body = str_replace($host, OBF_HOST, $body);
    if ($token !== '') {
        $body = str_replace($token, OBF_AUTH, $body);
    }

    $body = (string) preg_replace('/ssid=[A-Za-z0-9]+/', 'ssid=' . OBF_SSID, $body);
    $body = (string) preg_replace('/auth=[A-Za-z0-9]+/', 'auth=' . OBF_AUTH, $body);
    $body = (string) preg_replace('/"streamtoken"\s*:\s*"[^"]*"/', '"streamtoken":"' . OBF_AUTH . '"', $body);

    // collapse the server's music root to a stable placeholder, keeping the relative layout intact
    if ($musicRoot !== '') {
        foreach ([$musicRoot, str_replace('/', '\\/', $musicRoot)] as $needle) {
            $body = str_replace($needle, OBF_MUSIC_ROOT, $body);
        }
    }

    // email addresses are personal data and never needed to validate a schema
    return (string) preg_replace('/[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}/', OBF_EMAIL, $body);
}

/**
 * Derive the server's music root from a sample song filename so it can be redacted.
 */
function detectMusicRoot(string $restBase, string $token): string
{
    [, $body] = request(sprintf('%s/songs?limit=1', $restBase), $token);
    $filename = json_decode($body, true)['song'][0]['filename'] ?? '';
    if (!is_string($filename) || $filename === '') {
        return '';
    }

    // keep the last two path segments (artist/track) and treat everything above as the root
    $parts = explode('/', str_replace('\\', '/', $filename));

    return (count($parts) > 3) ? implode('/', array_slice($parts, 0, -3)) : '';
}

/**
 * @return array{0: int, 1: string}
 */
function request(string $url, string $token = ''): array
{
    global $verifySsl, $caBundle;

    $ch      = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => ($token !== '') ? ['Authorization: Bearer ' . $token] : [],
        CURLOPT_SSL_VERIFYPEER => $verifySsl,
        CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
    ];
    // php on windows often ships without a CA bundle, so point curl at one rather than skipping verification
    if ($verifySsl && $caBundle !== '') {
        $options[CURLOPT_CAINFO] = $caBundle;
    }
    curl_setopt_array($ch, $options);
    $body   = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [$status, is_string($body) ? $body : ''];
}

// handshake for a session token; an api key authenticates directly, a password needs the timestamped digest
if ($apikey !== '') {
    $handshake = sprintf(
        '%s/server/json.server.php?action=handshake&auth=%s&version=8',
        $baseUrl,
        rawurlencode($apikey)
    );
} else {
    $timestamp  = time();
    $passphrase = hash('sha256', $timestamp . hash('sha256', $pass));
    $handshake  = sprintf(
        '%s/server/json.server.php?action=handshake&user=%s&timestamp=%d&auth=%s&version=8',
        $baseUrl,
        rawurlencode($user),
        $timestamp,
        $passphrase
    );
}

[, $body] = request($handshake);
$token    = json_decode($body, true)['auth'] ?? '';
if (!is_string($token) || $token === '') {
    fwrite(STDERR, "handshake failed: " . substr($body, 0, 200) . "\n");
    exit(1);
}

$restBase = $baseUrl . '/rest/8/json';

/**
 * Resolve the first id of a collection so the detail paths return real payloads.
 */
function firstId(string $restBase, string $token, string $resource, string $key): string
{
    [, $body] = request(sprintf('%s/%s?limit=1', $restBase, $resource), $token);
    $decoded  = json_decode($body, true);

    return (string) ($decoded[$key][0]['id'] ?? '');
}

$ids = [
    '{album_id}' => firstId($restBase, $token, 'albums', 'album'),
    '{artist_id}' => firstId($restBase, $token, 'artists', 'artist'),
    '{song_id}' => firstId($restBase, $token, 'songs', 'song'),
];

// REST path (as documented in docs/openapi.json) => query string
$paths = [
    '/albums' => 'limit=2',
    '/albums/{album_id}' => '',
    '/albums/{album_id}/songs' => 'limit=2',
    '/artists' => 'limit=2',
    '/artists/{artist_id}' => '',
    '/artists/{artist_id}/albums' => 'limit=2',
    '/artists/{artist_id}/songs' => 'limit=2',
    '/songs' => 'limit=2',
    '/songs/{song_id}' => '',
    '/genres' => 'limit=2',
    '/labels' => 'limit=2',
    '/licenses' => 'limit=2',
    '/live-streams' => 'limit=2',
    '/playlists' => 'limit=2',
    '/podcasts' => 'limit=2',
    '/podcast-episodes' => 'limit=2',
    '/shares' => 'limit=2',
    '/bookmarks' => '',
    '/users' => '',
    '/videos' => 'limit=2',
    '/catalogs' => 'limit=2',
    '/folders' => 'limit=2',
    '/songs/deleted' => 'limit=2',
    '/podcast-episodes/deleted' => 'limit=2',
    '/videos/deleted' => 'limit=2',
];

$targetDir = __DIR__ . '/../../../tests/Fixtures/Api/rest';
if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
    fwrite(STDERR, sprintf("could not create %s\n", $targetDir));
    exit(1);
}

$musicRoot = detectMusicRoot($restBase, $token);

$written = 0;
foreach ($paths as $path => $query) {
    $resolved = strtr($path, $ids);
    if (str_contains($resolved, '{')) {
        fwrite(STDERR, sprintf("SKIP %s (unresolved id)\n", $path));
        continue;
    }

    $url             = $restBase . $resolved . ($query !== '' ? '?' . $query : '');
    [$status, $body] = request($url, $token);
    if ($status !== 200 || $body === '') {
        fwrite(STDERR, sprintf("SKIP %s (HTTP %d)\n", $path, $status));
        continue;
    }

    // a disabled feature still answers 200 with an error payload, which is not the documented success shape
    $decoded = json_decode($body, true);
    if (isset($decoded['error'])) {
        fwrite(STDERR, sprintf("SKIP %s (%s)\n", $path, $decoded['error']['errorMessage'] ?? 'error response'));
        continue;
    }

    // the fixture name encodes the documented path so the test can map it back to the spec
    $name = trim(str_replace(['/', '{', '}'], ['.', '', ''], $path), '.');
    file_put_contents(sprintf('%s/%s.json', $targetDir, $name), obfuscate($body, $baseUrl, $token, $musicRoot, $user));
    $written++;
}

printf("wrote %d REST fixtures to %s\n", $written, $targetDir);
