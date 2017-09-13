<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

class scrobbler
{
    public $error_msg;
    public $challenge;
    public $host;
    public $scheme;
    public $api_key;
    public $queued_tracks;
    private $secret;

    /**
     * Constructor
     * This is the constructer it takes a username and password
     */
    public function __construct($api_key, $scheme='https', $host='', $challenge='', $secret='')
    {
        $this->error_msg     = '';
        $this->challenge     = $challenge;
        $this->host          = $host;
        $this->scheme        = $scheme;
        $this->api_key       = $api_key;
        $this->secret        =$secret;
        $this->queued_tracks = array();
    } // scrobbler

    /**
     * get_api_sig
     * Provide the API signature for calling Last.fm / Libre.fm services
     * It is the md5 of the <name><value> of all parameter plus API's secret
     */
    public function get_api_sig($vars=null)
    {
        ksort($vars);
        $sig = '';
        foreach ($vars as $name => $value) {
            $sig .= $name . $value;
        }
        $sig .= $this->secret;
        $sig = md5($sig);

        return $sig;
    } // get_api_sig

    /**
     * call_url
     * This is a generic caller for HTTP requests
     * It need the method (GET/POST), the url and the parameters
     */
    public function call_url($url, $method='GET', $vars=null)
    {
        // Encode parameters per RFC1738
        $params=http_build_query($vars);
        $opts  = array(
                'http' => array(
                        'method' => $method,
                        'header' => array(
                                'Host: ' . $this->host,
                                'User-Agent: Ampache/' . AmpConfig::get('version')
                        ),
                )
        );
        // POST request need parameters in body and additional headers
        if ($method == 'POST') {
            $opts['http']['content']  = $params;
            $opts['http']['header'][] = 'Content-type: application/x-www-form-urlencoded';
            $opts['http']['header'][] = 'Content-length: ' . strlen($params);
            $params                   ='';
        }
        $context = stream_context_create($opts);
        if ($params != '') {
            // If there are paramters for GET request, adding the "?" caracter before
            $params='?' . $params;
        }
        $target = $this->scheme . '://' . $this->host . $url . $params;
        $fp     = @fopen($target, 'r', false, $context);
        if (!$fp) {
            debug_event('Scrobbler', 'Cannot access ' . $target, 1);

            return false;
        }
        ob_start();
        fpassthru($fp);
        $buffer = ob_get_contents();
        ob_end_clean();
        fclose($fp);

        return $buffer;
    } // call_url

    /**
     * get_error_msg
     */
    public function get_error_msg()
    {
        return $this->error_msg;
    } // get_error_msg

    /**
     * get_queue_count
     */
    public function get_queue_count()
    {
        return count($this->queued_tracks);
    } // get_queue_count

    /**
     * get_session_key
     * This is a generic caller for HTTP requests
     * It need the method (GET/POST), the url and the parameters
     */
    public function get_session_key($token=null)
    {
        if (!is_null($token)) {
            $vars = array(
            'method' => 'auth.getSession',
            'api_key' => $this->api_key,
            'token' => $token
            );
            //sign the call
            $sig             = $this->get_api_sig($vars);
            $vars['api_sig'] = $sig;
            //call the getSession API
            $response=$this->call_url('/2.0/', 'GET', $vars);
            $xml     = simplexml_load_string($response);
            if ($xml) {
                $status = (string) $xml['status'];
                if ($status == 'ok') {
                    if ($xml->session && $xml->session->key) {
                        return $xml->session->key;
                    } else {
                        $this->error_msg = 'Did not receive a valid response';

                        return false;
                    }
                } else {
                    $this->error_msg = $xml->error;

                    return false;
                }
            } else {
                $this->error_msg = 'Did not receive a valid response';

                return false;
            }
        }
        $this->error_msg = 'Need a token to call getSession';

        return false;
    } // get_session_key

    /**
     * queue_track
     * This queues the LastFM / Libre.fm track by storing it in this object, it doesn't actually
     * submit the track or talk to LastFM / Libre in anyway, kind of useless for our uses but its
     * here, and that's how it is.
     */
    public function queue_track($artist, $album, $title, $timestamp, $length, $track)
    {
        if ($length < 30) {
            debug_event('Scrobbler', "Not queuing track, too short", '5');

            return false;
        }

        $newtrack           = array();
        $newtrack['artist'] = $artist;
        $newtrack['album']  = $album;
        $newtrack['title']  = $title;
        $newtrack['track']  = $track;
        $newtrack['length'] = $length;
        $newtrack['time']   = $timestamp;

        $this->queued_tracks[$timestamp] = $newtrack;

        return true;
    } // queue_track

    /**
     * submit_tracks
     * This actually talks to LastFM / Libre.fm submiting the tracks that are queued up.
     * It passed the API key, session key combinted with the signature
     */
    public function submit_tracks()
    {
        // Check and make sure that we've got some queued tracks
        if (!count($this->queued_tracks)) {
            $this->error_msg = "No tracks to submit";

            return false;
        }

        //sort array by timestamp
        ksort($this->queued_tracks);

        // Build the query string (encoded per RFC1738 by the call method)
        $i   = 0;
        $vars= array();
        foreach ($this->queued_tracks as $track) {
            //construct array of parameters for each song
            $vars["artist[$i]"]      = $track['artist'];
            $vars["track[$i]"]       = $track['title'];
            $vars["timestamp[$i]"]   = $track['time'];
            $vars["album[$i]"]       = $track['album'];
            $vars["trackNumber[$i]"] = $track['track'];
            $vars["duration[$i]"]    = $track['length'];
            $i++;
        }
        // Add the method, API and session keys
        $vars['method']  = 'track.scrobble';
        $vars['api_key'] = $this->api_key;
        $vars['sk']      = $this->challenge;

        // Sign the call
        $sig             = $this->get_api_sig($vars);
        $vars['api_sig'] = $sig;

        // Call the method and parse response
        $response=$this->call_url('/2.0/', 'POST', $vars);
        $xml     = simplexml_load_string($response);
        if ($xml) {
            $status = (string) $xml['status'];
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
    } // submit_tracks

    /**
     * love
     * This takes care of spreading your love to the world
     * It passed the API key, session key combinted with the signature
     */
    public function love($is_loved, $type, $artist = '', $title = '', $album = '')
    {
        $vars['track']  = $title;
        $vars['artist'] = $artist;
        // Add the method, API and session keys
        $vars['method']  = $is_loved ? 'track.love' : 'track.unlove';
        $vars['api_key'] = $this->api_key;
        $vars['sk']      = $this->challenge;

        // Sign the call
        $sig             = $this->get_api_sig($vars);
        $vars['api_sig'] = $sig;

        // Call the method and parse response
        $response=$this->call_url('/2.0/', 'POST', $vars);
        $xml     = simplexml_load_string($response);
        if ($xml) {
            $status = (string) $xml['status'];
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
    } // love
} // end audioscrobbler class
