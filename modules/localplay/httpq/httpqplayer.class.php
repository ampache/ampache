<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2020 Ampache.org
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

/**
 * HttpQPlayer Class
 *
 * This player controls an instance of httpQ which in turn controls WinAmp
 *
 */
class HttpQPlayer
{
    public $host;
    public $port;
    public $password;

    /**
     * HttpQPlayer
     * This is the constructor, it defaults to localhost
     * with port 4800
     */
    public function __construct($host = "localhost", $password = '', $port = 4800)
    {
        $this->host     = $host;
        $this->port     = $port;
        $this->password = $password;
    } // HttpQPlayer

    /**
     * add
     * append a song to the playlist
     * $name    Name to be shown in the playlist
     * $url        URL of the song
     */
    public function add($name, $url)
    {
        $args['name'] = urlencode($name);
        $args['url']  = urlencode($url);

        $results = $this->sendCommand('playurl', $args);

        if ($results == '0') {
            $results = null;
        }

        return $results;
    } // add

    /**
     * version
     * This gets the version of winamp currently
     * running, use this to test for a valid connection
     */
    public function version()
    {
        $args    = array();
        $results = $this->sendCommand('getversion', $args);

        // a return of 0 is a bad value
        if ($results == '0') {
            $results = null;
        }


        return $results;
    } // version

    /**
     * clear
     * clear the playlist
     */
    public function clear()
    {
        $args    = array();
        $results = $this->sendCommand("delete", $args);

        if ($results == '0') {
            $results = null;
        }

        return $results;
    } // clear

    /**
     * next
     * go to next song
     */
    public function next()
    {
        $args    = array();
        $results = $this->sendCommand("next", $args);

        if ($results == '0') {
            return null;
        }

        return true;
    } // next

    /**
     * prev
     * go to previous song
     */
    public function prev()
    {
        $args    = array();
        $results = $this->sendCommand("prev", $args);

        if ($results == '0') {
            return null;
        }

        return true;
    } // prev

    /**
     * skip
     * This skips to POS in the playlist
     */
    public function skip($pos)
    {
        $args    = array('index' => $pos);
        $results = $this->sendCommand('setplaylistpos', $args);

        if ($results == '0') {
            return null;
        }

        // Now stop start
        $this->stop();
        $this->play();

        return true;
    } // skip

    /**
     * play
     * play the current song
     */
    public function play()
    {
        $args    = array();
        $results = $this->sendCommand("play", $args);

        if ($results == '0') {
            $results = null;
        }

        return $results;
    } // play

    /**
     * pause
     * toggle pause mode on current song
     */
    public function pause()
    {
        $args    = array();
        $results = $this->sendCommand("pause", $args);

        if ($results == '0') {
            $results = null;
        }

        return $results;
    } // pause

    /**
     * stop
     * stops the current song amazing!
     */
    public function stop()
    {
        $args    = array();
        $results = $this->sendCommand('stop', $args);

        if ($results == '0') {
            $results = null;
        }

        return $results;
    } // stop

    /**
      * repeat
     * This toggles the repeat state of HttpQ
     */
    public function repeat($value)
    {
        $args    = array('enable' => $value);
        $results = $this->sendCommand('repeat', $args);

        if ($results == '0') {
            $results = null;
        }

        return $results;
    } // repeat

    /**
     * random
     * this toggles the random state of HttpQ
     */
    public function random($value)
    {
        $args    = array('enable' => $value);
        $results = $this->sendCommand('shuffle', $args);

        if ($results == '0') {
            $results = null;
        }

        return $results;
    } // random

    /**
     * delete_pos
     * This deletes a specific track
     */
    public function delete_pos($track)
    {
        $args    = array('index' => $track);
        $results = $this->sendCommand('deletepos', $args);

        if ($results == '0') {
            $results = null;
        }

        return $results;
    } // delete_pos

    /**
     * state
     * This returns the current state of the httpQ player
     */
    public function state()
    {
        $args    = array();
        $results = $this->sendCommand('isplaying', $args);

        if ($results == '1') {
            $state = 'play';
        }
        if ($results == '0') {
            $state = 'stop';
        }
        if ($results == '3') {
            $state = 'pause';
        }

        return $state;
    } // state

    /**
     * get_volume
     * This returns the current volume
     */
    public function get_volume()
    {
        $args    = array();
        $results = $this->sendCommand('getvolume', $args);

        if ($results == '0') {
            $results = null;
        } else {
            /* Need to make this out of 100 */
            $results = round((($results / 255) * 100), 2);
        }

        return $results;
    } // get_volume

    /**
     * volume_up
     * This increases the volume by Wimamp's defined amount
     */
    public function volume_up()
    {
        $args    = array();
        $results = $this->sendCommand('volumeup', $args);

        if ($results == '0') {
            return null;
        }

        return true;
    } // volume_up

    /**
     * volume_down
     * This decreases the volume by Winamp's defined amount
     */
    public function volume_down()
    {
        $args    = array();
        $results = $this->sendCommand('volumedown', $args);

        if ($results == '0') {
            return null;
        }

        return true;
    } // volume_down

    /**
     * set_volume
     * This sets the volume as best it can, we go from a resolution
     * of 100 --> 255 so it's a little fuzzy
     */
    public function set_volume($value)
    {

        // Convert it to base 255
        $volume  = $value * 2.55;
        $args    = array('level' => $volume);
        $results = $this->sendCommand('setvolume', $args);

        if ($results == '0') {
            return null;
        }

        return true;
    } // set_volume

    /**
     * clear_playlist
     * this flushes the playlist cache (I hope this means clear)
     */
    public function clear_playlist()
    {
        $args    = array();
        $results = $this->sendcommand('flushplaylist', $args);

        if ($results == '0') {
            return null;
        }

        return true;
    } // clear_playlist

    /**
     * get_repeat
     * This returns the current state of the repeat
     */
    public function get_repeat()
    {
        $args    = array();
        $results = $this->sendCommand('repeat_status', $args);

        return $results;
    } // get_repeat

    /**
     * get_random
     * This returns the current state of shuffle
     */
    public function get_random()
    {
        $args    = array();
        $results = $this->sendCommand('shuffle_status', $args);

        return $results;
    } // get_random

    /**
     * get_now_playing
     * This returns the file information for the currently
     * playing song
     */
    public function get_now_playing()
    {

        // First get the current POS
        $pos = $this->sendCommand('getlistpos', array());

        // Now get the filename
        $file = $this->sendCommand('getplaylistfile', array('index' => $pos));

        return $file;
    } // get_now_playing

    /**
     * get_tracks
     * This returns a delimiated string of all of the filenames
     * current in your playlist
     */
    public function get_tracks()
    {

        // Pull a delimited list of all tracks
        $results = $this->sendCommand('getplaylistfile', array('delim' => '::'));

        if ($results == '0') {
            $results = null;
        }

        return $results;
    } // get_tracks

    /**
      * sendCommand
     * This is the core of this library it takes care of sending the HTTP
     * request to the HttpQ server and getting the response
     */
    private function sendCommand($cmd, $args)
    {
        $fsock = fsockopen($this->host, $this->port, $errno, $errstr);

        if (!$fsock) {
            debug_event('httpqplayer.class', "HttpQPlayer: $errstr ($errno)", 1);

            return null;
        }

        // Define the base message
        $msg = "GET /$cmd?p=$this->password";

        // Foreach our arguments
        foreach ($args as $key => $val) {
            $msg = $msg . "&$key=$val";
        }

        $msg = $msg . " HTTP/1.0\r\n\r\n";
        fputs($fsock, $msg);
        $data = '';

        while (!feof($fsock)) {
            $data .= fgets($fsock);
        }
        fclose($fsock);

        // Explode the results by line break and take 4th line (results)
        $data = explode("\n", $data);

        $result = $data['4'];

        return $result;
    } // sendCommand
} // End HttpQPlayer Class
