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

namespace Ampache\Module\Playback\Localplay\Mpd;

/**
 *  mpd.class.php - PHP Object Interface to the MPD Music Player Daemon
 *  Version 1.3
 *
 *  Copyright (C) 2003-2004  Benjamin Carlisle (bcarlisle@24oz.com)
 *  Copyright 2010 Paul Arthur MacIain
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * Class mpd
 */
class mpd
{
    // Command names
    // Status queries
    public const COMMAND_CLEARERROR  = 'clearerror';
    public const COMMAND_CURRENTSONG = 'currentsong';
    public const COMMAND_IDLE        = 'idle';
    public const COMMAND_STATUS      = 'status';
    public const COMMAND_STATISTICS  = 'stats';

    // Playback options
    public const COMMAND_CONSUME            = 'consume';
    public const COMMAND_CROSSFADE          = 'crossfade';
    public const COMMAND_RANDOM             = 'random';
    public const COMMAND_REPEAT             = 'repeat';
    public const COMMAND_SETVOL             = 'setvol';
    public const COMMAND_SINGLE             = 'single';
    public const COMMAND_REPLAY_GAIN_MODE   = 'replay_gain_mode';
    public const COMMAND_REPLAY_GAIN_STATUS = 'replay_gain_status';

    // Playback control
    public const COMMAND_NEXT     = 'next';
    public const COMMAND_PAUSE    = 'pause';
    public const COMMAND_PLAY     = 'play';
    public const COMMAND_PLAYID   = 'playid';
    public const COMMAND_PREVIOUS = 'previous';
    public const COMMAND_SEEK     = 'seek';
    public const COMMAND_SEEKID   = 'seekid';
    public const COMMAND_STOP     = 'stop';

    // Current playlist control
    public const COMMAND_ADD            = 'add';
    public const COMMAND_ADDID          = 'addid';
    public const COMMAND_CLEAR          = 'clear';
    public const COMMAND_DELETE         = 'delete';
    public const COMMAND_DELETEID       = 'deleteid';
    public const COMMAND_MOVETRACK      = 'move';
    public const COMMAND_MOVEID         = 'moveid';
    public const COMMAND_PLFIND         = 'playlistfind';
    public const COMMAND_PLID           = 'playlistid';
    public const COMMAND_PLINFO         = 'playlistinfo';
    public const COMMAND_PLSEARCH       = 'playlistsearch';
    public const COMMAND_PLCHANGES      = 'plchanges';
    public const COMMAND_PLCHANGESPOSID = 'plchangesposid';
    public const COMMAND_PLSHUFFLE      = 'shuffle';
    public const COMMAND_PLSWAPTRACK    = 'swap';
    public const COMMAND_PLSWAPID       = 'swapid';

    // Stored playlists
    public const COMMAND_LISTPL        = 'listplaylist';
    public const COMMAND_LISTPLINFO    = 'listplaylistinfo';
    public const COMMAND_LISTPLAYLISTS = 'listplaylists';
    public const COMMAND_PLLOAD        = 'load';
    public const COMMAND_PLADD         = 'playlistadd';
    public const COMMAND_PLCLEAR       = 'playlistclear';
    public const COMMAND_PLDELETE      = 'playlistdelete';
    public const COMMAND_PLMOVE        = 'playlistmove';
    public const COMMAND_RENAME        = 'rename';
    public const COMMAND_RM            = 'rm';
    public const COMMAND_PLSAVE        = 'save';

    // Music database
    public const COMMAND_COUNT       = 'count';
    public const COMMAND_FIND        = 'find';
    public const COMMAND_FINDADD     = 'findadd';
    public const COMMAND_TABLE       = 'list';
    public const COMMAND_LISTALL     = 'listall';
    public const COMMAND_LISTALLINFO = 'listallinfo';
    public const COMMAND_LSDIR       = 'lsinfo';
    public const COMMAND_SEARCH      = 'search';
    public const COMMAND_REFRESH     = 'update';
    public const COMMAND_RESCAN      = 'rescan';

    // Stickers
    public const COMMAND_STICKER = 'sticker';
    public const STICKER_GET     = 'get';
    public const STICKER_SET     = 'set';
    public const STICKER_DELETE  = 'delete';
    public const STICKER_LIST    = 'list';
    public const STICKER_FIND    = 'find';

    // Connection
    public const COMMAND_CLOSE    = 'close';
    public const COMMAND_KILL     = 'kill';
    public const COMMAND_PASSWORD = 'password';
    public const COMMAND_PING     = 'ping';
    public const COMMAND_SHUTDOWN = 'shutdown';

    // Deprecated commands
    public const COMMAND_VOLUME = 'volume';

    // Bulk commands
    public const COMMAND_START_BULK = 'command_list_begin';
    public const COMMAND_END_BULK   = 'command_list_end';

    // Predefined MPD Response messages
    public const RESPONSE_ERR = 'ACK';
    public const RESPONSE_OK  = 'OK';

    // MPD State Constants
    public const STATE_PLAYING = 'play';
    public const STATE_STOPPED = 'stop';
    public const STATE_PAUSED  = 'pause';

    // MPD Searching Constants
    public const SEARCH_ARTIST = 'artist';
    public const SEARCH_TITLE  = 'title';
    public const SEARCH_ALBUM  = 'album';

    // MPD Cache Tables
    public const TABLE_ARTIST = 'artist';
    public const TABLE_ALBUM  = 'album';

    // Table holding version compatibility information
    private static array $_COMPATIBILITY_TABLE = [
        self::COMMAND_CONSUME => ['min' => '0.15.0', 'max' => false],
        self::COMMAND_IDLE => ['min' => '0.14.0', 'max' => false],
        self::COMMAND_PASSWORD => ['min' => '0.10.0', 'max' => false],
        self::COMMAND_MOVETRACK => ['min' => '0.9.1', 'max' => false],
        self::COMMAND_PLSWAPTRACK => ['min' => '0.9.1', 'max' => false],
        self::COMMAND_RANDOM => ['min' => '0.9.1', 'max' => false],
        self::COMMAND_SEEK => ['min' => '0.9.1', 'max' => false],
        self::COMMAND_SETVOL => ['min' => '0.10.0', 'max' => false],
        self::COMMAND_SINGLE => ['min' => '0.15.0', 'max' => false],
        self::COMMAND_STICKER => ['min' => '0.15.0', 'max' => false],
        self::COMMAND_VOLUME => ['min' => false, 'max' => '0.10.0']
    ];

    /**
     * TCP/Connection variables
     */

    private $host;
    private $port;
    private $password;

    private $_mpd_sock     = null;
    public bool $connected = false;

    public $mpd_version = "(unknown)"; // MPD Status variables

    public $stats;
    public $status;
    public $playlist;

    public $mpd_class_version = '1.3'; // Misc Other Vars

    public $err_str = ''; // Stores the latest error message

    private $_command_queue; // The list of commands for bulk command sending

    /** @property callable|null $_debug_callback */
    private $_debug_callback = null; // Optional callback to be run on debug

    public bool $debugging = false;

    private $current_track_id;

    /**
     * Constructor
     * Builds the MPD object, connects to the server, and refreshes all
     * local object properties.
     * mpd constructor.
     * @param string $server
     * @param string|int $port
     * @param string $password
     * @param string $debug_callback
     */
    public function __construct(
        $server,
        $port,
        $password = null,
        $debug_callback = null
    ) {
        $this->host     = trim($server);
        $this->port     = (int)$port;
        $this->password = $password;

        if (is_callable($debug_callback)) {
            $this->_debug_callback = $debug_callback;
        }

        $this->_debug('construct', 'constructor called', 5);

        if (empty($this->host)) {
            $this->_error('construct', 'Host is empty');

            return;
        }

        $response = $this->connect();
        if (!$response) {
            $this->_error('construct', 'Could not connect');

            return;
        }

        $version           = sscanf($response, self::RESPONSE_OK . " MPD %s\n");
        $this->mpd_version = $version[0] ?? "(unknown)";

        if ($password) {
            if (!$this->SendCommand(self::COMMAND_PASSWORD, $password, false)) {
                // bad password or command
                $this->connected = false;
                $this->_error('construct', 'Password supplied is incorrect or Invalid Command');
            }
        } elseif (!$this->RefreshInfo()) {
            // no read access, might as well be disconnected
            $this->connected = false;
            $this->_error('construct', 'Password required to access server');
        }
    }

    /**
     * Connect
     *
     * Connects to the MPD server.
     *
     * NOTE: This is called automatically upon object instantiation; you
     * should not need to call this directly.
     */
    public function connect(): ?string
    {
        $this->_debug(self::class, "host: " . $this->host . ", port: " . $this->port, 5);
        $this->_mpd_sock = fsockopen($this->host, (int) $this->port, $err, $err_str, 6);

        if (!$this->_mpd_sock) {
            $this->_error('Connect', "Socket Error: $err_str ($err)");

            return null;
        }
        // Set the timeout on the connection */
        stream_set_timeout($this->_mpd_sock, 6);

        // We want blocking, cause otherwise it doesn't timeout, and feof just keeps on spinning
        stream_set_blocking($this->_mpd_sock, true);
        $status = socket_get_status($this->_mpd_sock);
        while (!feof($this->_mpd_sock) && !$status['timed_out']) {
            $response = fgets($this->_mpd_sock, 1024);
            if (function_exists('socket_get_status')) {
                $status = socket_get_status($this->_mpd_sock);
            }
            if (strncmp(self::RESPONSE_OK, (string)$response, strlen(self::RESPONSE_OK)) == 0) {
                $this->connected = true;

                return $response ?: null;
            }
            if (strncmp(self::RESPONSE_ERR, (string)$response, strlen(self::RESPONSE_ERR)) == 0) {
                $this->_error('Connect', "Server responded with: " . (string)$response);

                return null;
            }
        } // end while
        // Generic response
        $this->_error('Connect', "Connection not available");

        return null;
    }

    /**
     * SendCommand
     *
     * Sends a generic command to the MPD server. Several command constants
     * are pre-defined for use (see self::COMMAND_* constant definitions above).
     * @param string $command
     * @param array<string|int, scalar>|string|float|int|null $arguments
     * @param bool $refresh_info
     * @return string|bool
     */
    public function SendCommand(string $command, $arguments = null, $refresh_info = true)
    {
        $this->_debug('SendCommand', "cmd: $command, args: " . json_encode($arguments), 5);
        if (
            !$this->connected ||
            !is_resource($this->_mpd_sock)
        ) {
            $this->_error('SendCommand', 'Not connected');

            return false;
        } else {
            $response_string = '';

            // Check the command compatibility:
            if (!$this->_checkCompatibility($command, $this->mpd_version)) {
                return false;
            }

            if (isset($arguments)) {
                if (is_array($arguments)) {
                    foreach ($arguments as $arg) {
                        $command .= ' "' . $arg . '"';
                    }
                } else {
                    $command .= ' "' . $arguments . '"';
                }
            }

            fputs($this->_mpd_sock, "$command\n");
            while (!feof($this->_mpd_sock)) {
                $response = fgets($this->_mpd_sock, 1024);

                // An OK signals the end of transmission
                if (strncmp(self::RESPONSE_OK, (string)$response, strlen(self::RESPONSE_OK)) == 0) {
                    break;
                }

                // An ERR signals an error!
                if (strncmp(self::RESPONSE_ERR, (string)$response, strlen(self::RESPONSE_ERR)) == 0) {
                    $this->_error('SendCommand', "MPD Error: $response");

                    return false;
                }

                // Build the response string
                $response_string .= $response;
            }
            $this->_debug('SendCommand', "response: $response_string", 5);
        }

        if ($refresh_info) {
            $this->RefreshInfo();
        }

        return $response_string ?: true;
    }

    /**
     * QueueCommand
     *
     * Queues a generic command for later sending to the MPD server. The
     * CommandQueue can hold as many commands as needed, and are sent all
     * at once, in the order they were queued, using the SendCommandQueue
     * method. The syntax for queueing commands is identical to SendCommand.
     * @param string $command
     * @param string|array|null $arguments
     */
    public function QueueCommand($command, $arguments = null): bool
    {
        $this->_debug('QueueCommand', "start; cmd: $command args: " . json_encode($arguments), 5);
        if (!$this->connected) {
            $this->_error('QueueCommand', 'Not connected');

            return false;
        }

        if (!$this->_command_queue) {
            $this->_command_queue = self::COMMAND_START_BULK . "\n";
        }

        if ($arguments) {
            if (is_array($arguments)) {
                foreach ($arguments as $arg) {
                    $command .= ' "' . $arg . '"';
                }
            } else {
                $command .= ' "' . $arguments . '"';
            }
        }

        $this->_command_queue .= $command . "\n";

        $this->_debug('QueueCommand', 'return', 5);

        return true;
    }

    /**
     * SendCommandQueue
     *
     * Sends all commands in the Command Queue to the MPD server.
     * @return string|bool
     */
    public function SendCommandQueue()
    {
        $this->_debug('SendCommandQueue', 'start', 5);
        if (!$this->connected) {
            $this->_error('SendCommandQueue', 'Not connected');

            return false;
        }

        $this->_command_queue .= self::COMMAND_END_BULK . "\n";
        $response = $this->SendCommand($this->_command_queue);

        if ($response) {
            $this->_command_queue = null;
        }

        $this->_debug('SendCommandQueue', "response: $response", 5);

        return $response;
    }

    /**
     * RefreshInfo
     *
     * Updates all class properties with the values from the MPD server.
     * NOTE: This function is automatically called on Connect()
     */
    public function RefreshInfo(): bool
    {
        $stats  = $this->SendCommand(self::COMMAND_STATISTICS, null, false);
        $status = $this->SendCommand(self::COMMAND_STATUS, null, false);

        if (!$stats || !$status) {
            return false;
        }

        $stats  = self::_parseResponse($stats);
        $status = self::_parseResponse($status);

        $this->stats  = $stats;
        $this->status = $status;

        // Get the Playlist
        $playlist       = $this->SendCommand(self::COMMAND_PLINFO, null, false);
        $this->playlist = self::_parseFileListResponse($playlist);

        return true;
    }

    /**
     * AdjustVolume
     *
     * Adjusts the mixer volume on the MPD by <value>, which can be a
     * positive (volume increase) or negative (volume decrease) value.
     * @param float|int|string $value
     * @return bool|string
     */
    public function AdjustVolume($value)
    {
        $this->_debug('AdjustVolume', 'start', 5);
        if (!is_numeric($value)) {
            $this->_error('AdjustVolume', "argument must be numeric: $value");

            return false;
        }

        $this->RefreshInfo();
        $value    = $this->status['volume'] + $value;
        $response = $this->SetVolume($value);

        $this->_debug('AdjustVolume', "return $response", 5);

        return $response;
    }

    /**
     * SetVolume
     *
     * Sets the mixer volume to <value>, which should be between 1 - 100.
     * @param float|int|string $value
     * @return bool|string
     */
    public function SetVolume($value)
    {
        $this->_debug('SetVolume', 'start', 5);
        if (!is_numeric($value)) {
            $this->_error('SetVolume', "argument must be numeric: $value");

            return false;
        }

        // Forcibly prevent out of range errors
        $value = ($value > 0)
            ? $value
            : 0;
        $value = ($value < 100)
            ? $value
            : 100;

        // If we're not compatible with SETVOL, we'll try adjusting
        // using VOLUME
        if ($this->_checkCompatibility(self::COMMAND_SETVOL, $this->mpd_version)) {
            $command = self::COMMAND_SETVOL;
        } else {
            $this->RefreshInfo(); // Get the latest volume
            if ($this->status['volume'] === null) {
                return false;
            } else {
                $command = self::COMMAND_VOLUME;
                $value   = $value - $this->status['volume'];
            }
        }

        $response = $this->SendCommand($command, $value);

        $this->_debug('SetVolume', "return: $response", 5);

        return $response;
    }

    /**
     * GetDir
     *
     * Retrieves a database directory listing of the <dir> directory and
     * places the results into a multidimensional array. If no directory is
     * specified the directory listing is at the base of the MPD music path.
     * @param string $dir
     * @return array|bool
     */
    public function GetDir($dir = '')
    {
        $this->_debug('GetDir', 'start', 5);
        $response = $this->SendCommand(self::COMMAND_LSDIR, $dir, false);
        $dirlist  = self::_parseFileListResponse($response);
        $this->_debug('GetDir', 'return: ' . json_encode($dirlist), 5);

        return $dirlist;
    }

    /**
     * PLAdd
     *
     * Adds each track listed in a single-dimensional <trackArray>, which
     * contains filenames of tracks to add to the end of the playlist. This
     * is used to add many, many tracks to the playlist in one swoop.
     * @param $trackArray
     * @return bool|string
     */
    public function PLAddBulk($trackArray)
    {
        $this->_debug('PLAddBulk', 'start', 5);
        $num_files = count($trackArray);
        for ($count = 0; $count < $num_files; $count++) {
            $this->QueueCommand(self::COMMAND_ADD, $trackArray[$count]);
        }
        $response = $this->SendCommandQueue();
        $this->_debug('PLAddBulk', "return: $response", 5);

        return $response;
    }

    /**
     * PLAdd
     *
     * Adds the file <file> to the end of the playlist. <file> must be a
     * track in the MPD database.
     * @param string $filename
     * @return bool|string
     */
    public function PLAdd($filename)
    {
        $this->_debug('PLAdd', 'start', 5);
        $response = $this->SendCommand(self::COMMAND_ADD, $filename);
        $this->_debug('PLAdd', "return: $response", 5);

        return $response;
    }

    /**
     * PLMoveTrack
     *
     * Moves track number <current_position> to position <new_position> in
     * the playlist. This is used to reorder the songs in the playlist.
     * @param $current_position
     * @param $new_position
     */
    public function PLMoveTrack($current_position, $new_position): bool|string
    {
        $this->_debug('PLMoveTrack', 'start', 5);
        if (!is_numeric($current_position)) {
            $this->_error('PLMoveTrack', "current_position must be numeric: $current_position");

            return false;
        }
        if ($current_position < 0 || $current_position > count($this->playlist)) {
            $this->_error('PLMoveTrack', "current_position out of range");

            return false;
        }
        $new_position = ($new_position > 0)
            ? $new_position
            : 0;
        $new_position = ($new_position < count($this->playlist)) ? $new_position : count($this->playlist);

        $response = $this->SendCommand(self::COMMAND_MOVETRACK, [$current_position, $new_position]);

        $this->_debug('PLMoveTrack', "return: $response", 5);

        return $response;
    }

    /**
     * PLShuffle
     *
     * Randomly reorders the songs in the playlist.
     * @return bool|string
     */
    public function PLShuffle()
    {
        $this->_debug('PLShuffle', 'start', 5);
        $response = $this->SendCommand(self::COMMAND_PLSHUFFLE);
        $this->_debug('PLShuffle', "return: $response", 5);

        return $response;
    }

    /**
     * PLLoad
     *
     * Retrieves the playlist from <file>.m3u and loads it into the current
     * playlist.
     * @param $file
     * @return bool|string
     */
    public function PLLoad($file)
    {
        $this->_debug('PLLoad', 'start', 5);
        $response = $this->SendCommand(self::COMMAND_PLLOAD, $file);
        $this->_debug('PLLoad', "return: $response", 5);

        return $response;
    }

    /**
     * PLSave
     *
     * Saves the playlist to <file>.m3u for later retrieval. The file is
     * saved in the MPD playlist directory.
     * @param $file
     * @return bool|string
     */
    public function PLSave($file)
    {
        $this->_debug('PLSave', 'start', 5);
        $response = $this->SendCommand(self::COMMAND_PLSAVE, $file, false);
        $this->_debug('PLSave', "return: $response", 5);

        return $response;
    }

    /**
     * PLClear
     *
     * Empties the playlist.
     * @return bool|string
     */
    public function PLClear()
    {
        $this->_debug('PLClear', 'start', 5);
        $response = $this->SendCommand(self::COMMAND_CLEAR);
        $this->_debug('PLClear', "return: $response", 5);

        return $response;
    }

    /**
     * PLRemove
     *
     * Removes track <id> from the playlist.
     * @param int $id
     * @return bool|string
     */
    public function PLRemove($id)
    {
        if (!is_numeric($id)) {
            $this->_error('PLRemove', "id must be numeric: $id");

            return false;
        }
        $response = $this->SendCommand(self::COMMAND_DELETE, $id);
        $this->_debug('PLRemove', "return: $response", 5);

        return $response;
    }

    /**
     * SetRepeat
     *
     * Enables 'loop' mode -- tells MPD continually loop the playlist. The
     * <repVal> parameter is either 1 (on) or 0 (off).
     * @param $value
     * @return bool|string
     */
    public function SetRepeat($value)
    {
        $this->_debug('SetRepeat', 'start', 5);
        $value    = ($value) ? 1 : 0;
        $response = $this->SendCommand(self::COMMAND_REPEAT, $value);
        $this->_debug('SetRepeat', "return: $response", 5);

        return $response;
    }

    /**
     * SetRandom
     *
     * Enables 'randomize' mode -- tells MPD to play songs in the playlist
     * in random order. The parameter is either 1 (on) or 0 (off).
     * @param $value
     * @return bool|string
     */
    public function SetRandom($value)
    {
        $this->_debug('SetRandom', 'start', 5);
        $value    = ($value) ? 1 : 0;
        $response = $this->SendCommand(self::COMMAND_RANDOM, $value);
        $this->_debug('SetRandom', "return: $response", 5);

        return $response;
    }

    /**
     * Shutdown
     *
     * Shuts down the MPD server (aka sends the KILL command). This closes
     * the current connection and prevents future communication with the
     * server.
     * @return bool|string
     */
    public function Shutdown()
    {
        $this->_debug('Shutdown', 'start', 5);
        $response = $this->SendCommand(self::COMMAND_SHUTDOWN);

        $this->connected = false;
        unset($this->mpd_version);
        unset($this->err_str);
        unset($this->_mpd_sock);

        $this->_debug('Shutdown', "return: $response", 5);

        return $response;
    }

    /**
     * DBRefresh
     *
     * Tells MPD to rescan the music directory for new tracks and refresh
     * the Database. Tracks cannot be played unless they are in the MPD
     * database.
     * @return bool|string
     */
    public function DBRefresh()
    {
        $this->_debug('DBRefresh', 'start', 5);
        $response = $this->SendCommand(self::COMMAND_REFRESH);
        $this->_debug('DBRefresh', "return: $response", 5);

        return $response;
    }

    /**
     * Play
     *
     * Begins playing the songs in the MPD playlist.
     * @return bool|string
     */
    public function Play()
    {
        $this->_debug('Play', 'start', 5);
        $response = $this->SendCommand(self::COMMAND_PLAY);
        $this->_debug('Play', "return: $response", 5);

        return $response;
    }

    /**
     * Stop
     *
     * Stops playback.
     * @return bool|string
     */
    public function Stop()
    {
        $this->_debug('Stop', 'start', 5);
        $response = $this->SendCommand(self::COMMAND_STOP);
        $this->_debug('Stop', "return: $response", 5);

        return $response;
    }

    /**
     * Pause
     *
     * Toggles pausing.
     * @return bool|string
     */
    public function Pause()
    {
        $this->_debug('Pause', 'start', 5);
        $response = $this->SendCommand(self::COMMAND_PAUSE);
        $this->_debug('Pause', "return: $response", 5);

        return $response;
    }

    /**
     * SeekTo
     *
     * Skips directly to the <idx> song in the MPD playlist.
     * @param $idx
     * @return bool|float|int|string
     */
    public function SkipTo($idx)
    {
        $this->_debug('SkipTo', 'start', 5);
        if (!is_numeric($idx)) {
            $this->_error('SkipTo', "argument must be numeric: $idx");

            return false;
        }
        $response = $this->SendCommand(self::COMMAND_PLAY, $idx);
        $this->_debug('SkipTo', "return: $idx", 5);

        return $idx;
    }

    /**
     * SeekTo
     *
     * Skips directly to a given position within a track in the MPD
     * playlist. The <pos> argument, given in seconds, is the track position
     * to locate. The <track> argument, if supplied, is the track number in
     * the playlist. If <track> is not specified, the current track is
     * assumed.
     * @param $pos
     * @param int $track
     * @return bool|float|int|string
     */
    public function SeekTo($pos, $track = -1)
    {
        $this->_debug('SeekTo', 'start', 5);
        if (!is_numeric($pos)) {
            $this->_error('SeekTo', "pos must be numeric: $pos");

            return false;
        }
        if (!is_numeric($track)) {
            $this->_error('SeekTo', "track must be numeric: $track");

            return false;
        }
        if ($track == -1) {
            $track = $this->current_track_id;
        }

        $response = $this->SendCommand(self::COMMAND_SEEK, [$track, $pos]);
        $this->_debug('SeekTo', "return: $pos", 5);

        return $pos;
    }

    /**
     * Next
     *
     * Skips to the next song in the MPD playlist. If not playing, returns
     * an error.
     * @return bool|string
     */
    public function Next()
    {
        $this->_debug('Next', 'start', 5);
        $response = $this->SendCommand(self::COMMAND_NEXT);
        $this->_debug('Next', "return: $response", 5);

        return $response;
    }

    /**
     * Previous
     *
     * Skips to the previous song in the MPD playlist. If not playing,
     * returns an error.
     * @return bool|string
     */
    public function Previous()
    {
        $this->_debug('Previous', 'start', 5);
        $response = $this->SendCommand(self::COMMAND_PREVIOUS);
        $this->_debug('Previous', "return: $response", 5);

        return $response;
    }

    /**
     * Search
     *
     * Searches the MPD database. The search <type> should be one of the
     * following:
     *     self::SEARCH_ARTIST, self::SEARCH_TITLE, self::SEARCH_ALBUM
     * The search <string> is a case-insensitive locator string. Anything
     * that contains <string> will be returned in the results.
     * @param string $type
     * @param string $string
     * @return array|bool
     */
    public function Search($type, $string)
    {
        $this->_debug('Search', 'start', 5);

        if ($type != self::SEARCH_ARTIST && $type != self::SEARCH_ALBUM && $type != self::SEARCH_TITLE) {
            $this->_error('Search', 'invalid search type');

            return false;
        }

        $response = $this->SendCommand(self::COMMAND_SEARCH, [$type, $string], false);

        $results = false;

        if ($response) {
            $results = self::_parseFileListResponse($response);
        }
        $this->_debug('Search', 'return: ' . json_encode($results), 5);

        return $results;
    }

    /**
     * Find
     *
     * Find looks for exact matches in the MPD database. The find <type>
     * should be one of the following:
     *    self::SEARCH_ARTIST, self::SEARCH_TITLE, self::SEARCH_ALBUM
     * The find <string> is a case-insensitive locator string. Anything that
     * exactly matches <string> will be returned in the results.
     * @param string $type
     * @param string $string
     * @return array|bool
     */
    public function Find($type, $string)
    {
        $this->_debug('Find', 'start', 5);
        if ($type != self::SEARCH_ARTIST && $type != self::SEARCH_ALBUM && $type != self::SEARCH_TITLE) {
            $this->_error('Find', 'invalid find type');

            return false;
        }

        $response = $this->SendCommand(self::COMMAND_FIND, [$type, $string], false);

        $results = false;

        if ($response) {
            $results = self::_parseFileListResponse($response);
        }

        $this->_debug('Find', 'return: ' . json_encode($results), 5);

        return $results;
    }

    /**
     * Disconnect
     *
     * Closes the connection to the MPD server.
     */
    public function Disconnect(): void
    {
        $this->_debug('Disconnect', 'start', 5);
        if (is_resource($this->_mpd_sock)) {
            fclose($this->_mpd_sock);
        }

        $this->connected = false;
        unset($this->mpd_version);
        unset($this->err_str);
        unset($this->_mpd_sock);
    }

    /**
     * GetArtists
     *
     * Returns the list of artists in the database in an associative array.
     * @return array|bool
     */
    public function GetArtists()
    {
        $this->_debug('GetArtists', 'start', 5);
        if (!$response = $this->SendCommand(self::COMMAND_TABLE, self::TABLE_ARTIST, false)) {
            return false;
        }
        $results = [];

        $parsed = self::_parseResponse($response);

        foreach ($parsed as $key => $value) {
            if ($key == 'Artist') {
                $results[] = $value;
            }
        }

        $this->_debug('GetArtists', 'return: ' . json_encode($results), 5);

        return $results;
    }

    /**
     * GetAlbums
     *
     * Returns the list of albums in the database in an associative array.
     * Optional parameter is an artist Name which will list all albums by a
     * particular artist.
     * @param $artist
     * @return array|bool
     */
    public function GetAlbums($artist = null)
    {
        $this->_debug('GetAlbums', 'start', 5);

        $params = [self::TABLE_ALBUM];
        if ($artist !== null) {
            $params[] = $artist;
        }

        if (!$response = $this->SendCommand(self::COMMAND_TABLE, $params, false)) {
            return false;
        }

        $results = [];
        $parsed  = self::_parseResponse($response);

        foreach ($parsed as $key => $value) {
            if ($key == 'Album') {
                $results[] = $value;
            }
        }

        $this->_debug('GetAlbums', 'return: ' . json_encode($results), 5);

        return $results;
    }

    /**
     * _computeVersionValue
     *
     * Computes numeric value from a version string
     *
     * @param string $string
     * @return float|int
     */
    private static function _computeVersionValue($string)
    {
        $parts = explode('.', $string);

        return (100 * (int)$parts[0]) + (10 * (int)$parts[1]) + (int)$parts[2];
    }

    /**
     * _checkCompatibility
     *
     * Check MPD command compatibility against our internal table of  incompatibilities.
     */
    private function _checkCompatibility(string $cmd, string $mpd_version): bool
    {
        $mpd = self::_computeVersionValue($mpd_version);

        if (isset(self::$_COMPATIBILITY_TABLE[$cmd])) {
            $min_version = self::$_COMPATIBILITY_TABLE[$cmd]['min'];
            $max_version = self::$_COMPATIBILITY_TABLE[$cmd]['max'];

            if ($min_version) {
                $min = self::_computeVersionValue($min_version);
                if ($mpd < $min) {
                    $this->_error(
                        'compatibility',
                        "Command '$cmd' is not compatible with this version of MPD, version $min_version required"
                    );

                    return false;
                }
            }

            if ($max_version) {
                $max = self::_computeVersionValue($max_version);

                if ($mpd >= $max) {
                    $this->_error(
                        'compatibility',
                        "Command '$cmd' has been deprecated in this version of MPD.  Last compatible version: $max_version"
                    );

                    return false;
                }
            }
        }

        return true;
    }

    /**
     * _parseFileListResponse
     *
     * Builds a multidimensional array with MPD response lists.
     * @param bool|string $response
     * @return array|bool
     */
    private static function _parseFileListResponse($response)
    {
        if (is_bool($response)) {
            return false;
        }

        $results = [];
        $counter = -1;
        $lines   = explode("\n", $response);
        foreach ($lines as $line) {
            if (preg_match('/(\w+): (.+)/', $line, $matches)) {
                if ($matches[1] == 'file') {
                    $counter++;
                }
                $results[$counter][$matches[1]] = $matches[2];
            }
        }

        return $results;
    }

    /**
     * _parseResponse
     * Turns a response into an array
     * @param $response
     * @return array|bool
     */
    private static function _parseResponse($response)
    {
        if (!$response) {
            return false;
        }

        $results = [];
        $lines   = explode("\n", $response);
        foreach ($lines as $line) {
            if (preg_match('/(\w+): (.+)/', $line, $matches)) {
                $results[$matches[1]] = $matches[2];
            }
        }

        return $results;
    }

    /**
     * _error
     *
     * Set error state
     * @param string $source
     * @param string $message
     * @param int $level
     */
    private function _error($source, $message, $level = 1): void
    {
        $this->err_str = "$source: $message";
        $this->_debug($source, $message, $level);
    }

    /**
     * _debug
     *
     * Do the debugging boogaloo
     * @param string $source
     * @param string $message
     * @param int $level
     */
    private function _debug($source, $message, $level): void
    {
        if ($this->debugging) {
            echo "$source / $message\n";
        }

        if ($this->_debug_callback !== null) {
            call_user_func($this->_debug_callback, 'MPD', "$source / $message", $level);
        }
    }
}
