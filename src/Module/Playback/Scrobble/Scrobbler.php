<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\Playback\Scrobble;

use Ampache\Config\AmpConfig;
use SimpleXMLElement;

class Scrobbler
{
    public string $api_key;

    public string $error_msg;

    public ?string $challenge;

    public ?string $host;

    public ?string $scheme;

    /** @var array<int, array{artist: string, album: string, title: string, track: int, length: int, time: int}> $queued_tracks */
    public array $queued_tracks;

    private ?string $secret = null;

    /**
     * Constructor
     * This is the constructer it takes a username and password
     * @param string $api_key
     * @param string|null $scheme
     * @param string|null $host
     * @param string|null $challenge
     * @param string|null $secret
     */
    public function __construct(
        string  $api_key,
        ?string $scheme = 'https',
        ?string $host = '',
        ?string $challenge = '',
        ?string $secret = ''
    ) {
        $this->error_msg     = '';
        $this->challenge     = $challenge;
        $this->host          = $host;
        $this->scheme        = $scheme;
        $this->api_key       = $api_key;
        $this->secret        = $secret;
        $this->queued_tracks = [];
    }

    /**
     * get_api_sig
     * Provide the API signature for calling Last.fm / Libre.fm services
     * It is the md5 of the <name><value> of all parameter plus API's secret
     * @param null|array<string, string> $vars
     */
    public function get_api_sig(?array $vars = []): string
    {
        if (!$vars) {
            return '';
        }
        ksort($vars);
        $sig = '';
        foreach ($vars as $name => $value) {
            $sig .= $name . $value;
        }
        $sig .= $this->secret;

        return md5($sig);
    }

    /**
     * call_url
     * This is a generic caller for HTTP requests
     * It need the method (GET/POST), the url and the parameters
     * @param string $url
     * @param string $method
     * @param array<string, string>|null $vars
     * @return string|null
     */
    public function call_url(string $url, string $method = 'GET', ?array $vars = []): ?string
    {
        // Encode parameters per RFC1738
        $params = http_build_query($vars ?? []);
        $opts   = [
            'http' => [
                'method' => $method,
                'header' => [
                    'Host: ' . $this->host,
                    'User-Agent: Ampache/' . AmpConfig::get('version')
                ],
            ]
        ];
        // POST request need parameters in body and additional headers
        if ($method == 'POST') {
            $opts['http']['content']  = $params;
            $opts['http']['header'][] = 'Content-type: application/x-www-form-urlencoded';
            $opts['http']['header'][] = 'Content-length: ' . strlen((string)$params);
            $params                   = '';
        }
        $context = stream_context_create($opts);
        if ($params != '') {
            // If there are parameters for GET request, adding the "?" character before
            $params = '?' . $params;
        }
        $target   = $this->scheme . '://' . $this->host . $url . $params;
        $filepath = @fopen($target, 'r', false, $context);
        if (!$filepath) {
            debug_event(self::class, 'Cannot access ' . $target, 1);

            return null;
        }
        ob_start();
        fpassthru($filepath);
        $buffer = ob_get_contents();
        ob_end_clean();
        fclose($filepath);

        return $buffer ?: null;
    }

    /**
     * get_error_msg
     */
    public function get_error_msg(): string
    {
        return $this->error_msg;
    }

    /**
     * get_queue_count
     */
    public function get_queue_count(): ?int
    {
        return count($this->queued_tracks);
    }

    /**
     * get_session_key
     * This is a generic caller for HTTP requests
     * It need the method (GET/POST), the url and the parameters
     */
    public function get_session_key(string $token = null): ?SimpleXMLElement
    {
        if ($token !== null) {
            $vars = [
                'method' => 'auth.getSession',
                'api_key' => $this->api_key,
                'token' => $token
            ];
            // sign the call
            $sig             = $this->get_api_sig($vars);
            $vars['api_sig'] = $sig;
            // call the getSession API
            $response = $this->call_url('/2.0/', 'GET', $vars);
            $xml      = ($response)
                ? simplexml_load_string($response)
                : false;
            if ($xml) {
                $status = (string)$xml['status'];
                if ($status == 'ok') {
                    if ($xml->session && $xml->session->key) {
                        return $xml->session->key;
                    } else {
                        $this->error_msg = 'Did not receive a valid response';

                        return null;
                    }
                } else {
                    $this->error_msg = $xml->error;

                    return null;
                }
            } else {
                $this->error_msg = 'Did not receive a valid response';

                return null;
            }
        }
        $this->error_msg = 'Need a token to call getSession';

        return null;
    }

    /**
     * queue_track
     * This queues the LastFM / Libre.fm track by storing it in this object, it doesn't actually
     * submit the track or talk to LastFM / Libre in anyway, kind of useless for our uses but its
     * here, and that's how it is.
     */
    public function queue_track(
        string $artist,
        string $album,
        string $title,
        int    $timestamp,
        int    $length,
        int    $track
    ): bool {
        if ($length < 30) {
            debug_event(self::class, "Not queuing track, too short", 3);

            return false;
        }

        $newtrack           = [];
        $newtrack['artist'] = $artist;
        $newtrack['album']  = $album;
        $newtrack['title']  = $title;
        $newtrack['track']  = $track;
        $newtrack['length'] = $length;
        $newtrack['time']   = $timestamp;

        $this->queued_tracks[$timestamp] = $newtrack;

        return true;
    }

    /**
     * submit_tracks
     * This actually talks to LastFM / Libre.fm submitting the tracks that are queued up.
     * It passed the API key, session key combined with the signature
     */
    public function submit_tracks(): bool
    {
        // Check and make sure that we've got some queued tracks
        if (!count($this->queued_tracks)) {
            $this->error_msg = "No tracks to submit";

            return false;
        }

        // sort array by timestamp
        ksort($this->queued_tracks);

        // Build the query string (encoded per RFC1738 by the call method)
        $count = 0;
        $vars  = [];
        foreach ($this->queued_tracks as $track) {
            // construct array of parameters for each song
            $vars["artist[$count]"]      = (string)$track['artist'];
            $vars["track[$count]"]       = (string)$track['title'];
            $vars["timestamp[$count]"]   = (string)$track['time'];
            $vars["album[$count]"]       = (string)$track['album'];
            $vars["trackNumber[$count]"] = (string)$track['track'];
            $vars["duration[$count]"]    = (string)$track['length'];
            $count++;
        }
        // Add the method, API and session keys
        $vars['method']  = 'track.scrobble';
        $vars['api_key'] = $this->api_key;
        $vars['sk']      = (string)$this->challenge;

        // Sign the call
        $sig             = $this->get_api_sig($vars);
        $vars['api_sig'] = $sig;

        // Call the method and parse response
        $response = $this->call_url('/2.0/', 'POST', $vars);
        $xml      = ($response)
            ? simplexml_load_string($response)
            : false;
        if ($xml) {
            $status = (string)$xml['status'];
            if ($status == 'ok') {
                return true;
            } else {
                $this->error_msg = $xml->error;

                return false;
            }
        } else {
            $this->error_msg = 'Did not receive a valid response';

            return false;
        }
    }

    /**
     * love
     * This takes care of spreading your love to the world
     * If passed the API key, session key combined with the signature
     */
    public function love(bool $is_loved, string $artist = '', string $title = ''): bool
    {
        $vars           = [];
        $vars['track']  = $title;
        $vars['artist'] = $artist;
        // Add the method, API and session keys
        $vars['method']  = ($is_loved) ? 'track.love' : 'track.unlove';
        $vars['api_key'] = $this->api_key;
        $vars['sk']      = (string)$this->challenge;

        // Sign the call
        $sig             = $this->get_api_sig($vars);
        $vars['api_sig'] = $sig;

        // Call the method and parse response
        $response = $this->call_url('/2.0/', 'POST', $vars);
        $xml      = ($response)
            ? simplexml_load_string($response)
            : false;
        if ($xml) {
            $status = (string)$xml['status'];
            if ($status == 'ok') {
                return true;
            } else {
                $this->error_msg = $xml->error;

                return false;
            }
        } else {
            $this->error_msg = 'Did not receive a valid response';

            return false;
        }
    }
}
