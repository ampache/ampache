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

namespace Ampache\Module\Playback\Localplay\HttpQ;

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
     * @param string $host
     * @param string $password
     * @param int $port
     */
    public function __construct(
        $host = "localhost",
        $password = '',
        $port = 4800
    ) {
        $this->host     = $host;
        $this->port     = $port;
        $this->password = $password;
    }

    /**
     * add
     * append a song to the playlist
     * $name    Name to be shown in the playlist
     * $url     URL of the song
     * @param string $name
     * @param string $url
     * @return bool|string
     */
    public function add($name, $url)
    {
        $args = [];

        $args['name'] = urlencode($name);
        $args['url']  = urlencode($url);

        $results = $this->sendCommand('playurl', $args);

        if ($results == '0') {
            return false;
        }

        return $results;
    }

    /**
     * version
     * This gets the version of winamp currently
     * running, use this to test for a valid connection
     */
    public function version(): bool
    {
        $args    = [];
        $results = $this->sendCommand('getversion', $args);

        return ($results !== '0'); // a return of 0 is a bad value
    }

    /**
     * clear
     * clear the playlist
     */
    public function clear()
    {
        $args    = [];
        $results = $this->sendCommand("delete", $args);

        if ($results == '0') {
            $results = false;
        }

        return $results;
    }

    /**
     * next
     * go to next song
     */
    public function next(): bool
    {
        $args    = [];
        $results = $this->sendCommand("next", $args);

        if ($results == '0') {
            return false;
        }

        return true;
    }

    /**
     * prev
     * go to previous song
     */
    public function prev(): bool
    {
        $args    = [];
        $results = $this->sendCommand("prev", $args);

        if ($results == '0') {
            return false;
        }

        return true;
    }

    /**
     * skip
     * This skips to POS in the playlist
     * @param $pos
     */
    public function skip($pos): bool
    {
        $args    = ['index' => $pos];
        $results = $this->sendCommand('setplaylistpos', $args);

        if ($results == '0') {
            return false;
        }

        // Now stop start
        $this->stop();
        $this->play();

        return true;
    }

    /**
     * play
     * play the current song
     */
    public function play()
    {
        $args    = [];
        $results = $this->sendCommand("play", $args);

        if ($results == '0') {
            $results = false;
        }

        return $results;
    }

    /**
     * pause
     * toggle pause mode on current song
     */
    public function pause()
    {
        $args    = [];
        $results = $this->sendCommand("pause", $args);

        if ($results == '0') {
            $results = false;
        }

        return $results;
    }

    /**
     * stop
     * stops the current song amazing!
     */
    public function stop()
    {
        $args    = [];
        $results = $this->sendCommand('stop', $args);

        if ($results == '0') {
            $results = false;
        }

        return $results;
    }

    /**
     * repeat
     * This toggles the repeat state of HttpQ
     * @param $value
     * @return bool|string
     */
    public function repeat($value)
    {
        $args    = ['enable' => $value];
        $results = $this->sendCommand('repeat', $args);

        if ($results == '0') {
            $results = false;
        }

        return $results;
    }

    /**
     * random
     * this toggles the random state of HttpQ
     * @param $value
     * @return bool|string
     */
    public function random($value)
    {
        $args    = ['enable' => $value];
        $results = $this->sendCommand('shuffle', $args);

        if ($results == '0') {
            $results = false;
        }

        return $results;
    }

    /**
     * delete_pos
     * This deletes a specific track
     * @param $track
     * @return bool|string
     */
    public function delete_pos($track)
    {
        $args    = ['index' => $track];
        $results = $this->sendCommand('deletepos', $args);

        if ($results == '0') {
            $results = false;
        }

        return $results;
    }

    /**
     * state
     * This returns the current state of the httpQ player
     */
    public function state(): string
    {
        $state   = '';
        $args    = [];
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
    }

    /**
     * get_volume
     * This returns the current volume
     */
    public function get_volume()
    {
        $args    = [];
        $results = $this->sendCommand('getvolume', $args);

        if ($results == '0') {
            return false;
        }

        return round((($results / 255) * 100), 2);
    }

    /**
     * volume_up
     * This increases the volume by Winamp's defined amount
     */
    public function volume_up(): bool
    {
        $args    = [];
        $results = $this->sendCommand('volumeup', $args);

        if ($results == '0') {
            return false;
        }

        return true;
    }

    /**
     * volume_down
     * This decreases the volume by Winamp's defined amount
     */
    public function volume_down(): bool
    {
        $args    = [];
        $results = $this->sendCommand('volumedown', $args);

        if ($results == '0') {
            return false;
        }

        return true;
    }

    /**
     * set_volume
     * This sets the volume as best it can, we go from a resolution
     * of 100 --> 255 so it's a little fuzzy
     * @param $value
     */
    public function set_volume($value): bool
    {
        // Convert it to base 255
        $volume  = $value * 2.55;
        $args    = ['level' => $volume];
        $results = $this->sendCommand('setvolume', $args);

        if ($results == '0') {
            return false;
        }

        return true;
    }

    /**
     * clear_playlist
     * this flushes the playlist cache (I hope this means clear)
     */
    public function clear_playlist(): bool
    {
        $args    = [];
        $results = $this->sendcommand('flushplaylist', $args);

        if ($results == '0') {
            return false;
        }

        return true;
    }

    /**
     * get_repeat
     * This returns the current state of the repeat
     */
    public function get_repeat()
    {
        $args = [];

        return $this->sendCommand('repeat_status', $args);
    }

    /**
     * get_random
     * This returns the current state of shuffle
     */
    public function get_random()
    {
        $args = [];

        return $this->sendCommand('shuffle_status', $args);
    }

    /**
     * get_now_playing
     * This returns the file information for the currently
     * playing song
     */
    public function get_now_playing()
    {
        // First get the current POS
        $pos = $this->sendCommand('getlistpos', []);

        // Now get the filename
        return $this->sendCommand('getplaylistfile', ['index' => $pos]);
    }

    /**
     * get_tracks
     * This returns a delimited string of all of the filenames
     * current in your playlist
     */
    public function get_tracks()
    {
        // Pull a delimited list of all tracks
        $results = $this->sendCommand('getplaylistfile', ['delim' => '::']);

        if ($results == '0') {
            $results = false;
        }

        return $results;
    }

    /**
     * sendCommand
     * This is the core of this library it takes care of sending the HTTP
     * request to the HttpQ server and getting the response
     * @param $cmd
     * @param $args
     * @return string|bool
     */
    private function sendCommand($cmd, $args)
    {
        $fsock = fsockopen($this->host, (int)$this->port, $errno, $errstr);

        if (!$fsock) {
            debug_event(self::class, "HttpQPlayer: $errstr ($errno)", 1);

            return false;
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

        return $data['4'];
    }
}
