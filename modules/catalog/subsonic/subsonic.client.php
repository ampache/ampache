<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

// SubsonicClient inspired from https://github.com/webeight/SubExt

class SubsonicClient
{
    protected $_serverUrl;
    protected $_serverPort;
    protected $_creds;
    protected $_commands;

    /**
     * SubsonicClient constructor.
     * @param string $username
     * @param string $password
     * @param string $serverUrl
     * @param string $port
     * @param string $client
     */
    public function __construct($username, $password, $serverUrl, $port = "4040", $client = "Ampache")
    {
        $this->setServer($serverUrl, $port);

        $this->_creds = array(
                'u' => $username,
                'p' => $password,
                'v' => '1.8.0',
                'c' => $client,
                'f' => 'json'
        );

        $this->_commands = array(
                'ping',
                'getLicense',
                'getMusicFolders',
                'getNowPlaying',
                'getIndexes',
                'getSong',
                'getMusicDirectory',
                'getArtistInfo',
                'search',
                'search2',
                'getPlaylists',
                'getPlaylist',
                'createPlaylist',
                'deletePlaylist',
                'download',
                'stream',
                'getCoverArt',
                'scrobble',
                'changePassword',
                'getUser',
                'createUser',
                'deleteUser',
                'getChatMessages',
                'addChatMessage',
                'getAlbumList',
                'getRandomSongs',
                'getLyrics',
                'jukeboxControl',
                'getPordcasts',
                'createShare',
                'updateShare',
                'deleteShare',
                'setRating',
        );
    }

    /**
     * @param $action
     * @param array $object
     * @param boolean $rawAnswer
     * @return array|boolean|object|string
     */
    public function querySubsonic($action, $object = array(), $rawAnswer = false)
    {
        return $this->_querySubsonic($action, $object, $rawAnswer);
    }

    /**
     * @param $url
     * @param array $object
     * @return string
     */
    public function parameterize($url, $object = array())
    {
        $params = array_merge($this->_creds, $object);

        return $url . http_build_query($params);
    }

    /**
     * @param $action
     * @param array $object
     * @param boolean $rawAnswer
     * @return array|boolean|object|string
     */
    protected function _querySubsonic($action, $object = array(), $rawAnswer = false)
    {
        // Make sure the command is in the list of commands
        if ($this->isCommand($action)) {
            $url = $this->parameterize($this->getServer() . "/rest/" . $action . ".view?", $object);

            $options = array(
                CURLOPT_URL => $url,
                CURLOPT_HEADER => 0,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_FOLLOWLOCATION => 1,
                CURLOPT_PORT => (int) ($this->_serverPort)
            );
            $curl = curl_init();
            if ($curl) {
                curl_setopt_array($curl, $options);
                $answer = curl_exec($curl);
                curl_close($curl);
                if ($rawAnswer) {
                    return $answer;
                } else {
                    return $this->parseResponse($answer);
                }
            }
        } else {
            return $this->error("Error: Invalid subsonic command: " . $action);
        }
    }

    /**
     * @param $server
     * @param $port
     */
    public function setServer($server, $port = null)
    {
        $protocol = "";
        if (preg_match("/^https\:\/\//", $server)) {
            $protocol = "https://";
        }
        if (empty($protocol)) {
            if (!preg_match("/^http\:\/\//", $server)) {
                $server = "http://" . $server;
            }
            $protocol = "http://";
        }
        preg_match("/\:\d{1,6}$/", $server, $matches);
        if (count($matches)) {
            // If theres a port on the url, remove it and save it for later use.
            $server = str_replace($matches[0], "", $server);
            $_port  = str_replace(":", "", $matches[0]);
        }
        if ($port == null && isset($_port)) {
            // If port parameter not set but there was one on the url, use the one from the url.
            $port = $_port;
        } else {
            if ($port == null) {
                $port = ($protocol == "https") ? '443' : '80';
            }
        }
        $this->_serverUrl  = $server;
        $this->_serverPort = $port;
    }

    /**
     * @return string
     */
    public function getServer()
    {
        return $this->_serverUrl . ":" . $this->_serverPort;
    }

    /**
     * @param $error
     * @param $data
     * @return object
     */
    protected function error($error, $data = null)
    {
        error_log($error . "\n" . print_r($data, true));

        return (object) array("success" => false, "error" => $error, "data" => $data);
    }

    /**
     * @param $response
     * @return array|object
     */
    protected function parseResponse($response)
    {
        $arr = json_decode($response, true);
        if ($arr['subsonic-response']) {
            $response = (array) $arr['subsonic-response'];
            $data     = $response;

            return array("success" => ($response['status'] == "ok"), "data" => $data);
        } else {
            return $this->error("Invalid response from server!");
        }
    }

    /**
     * @param $command
     * @return boolean
     */
    public function isCommand($command)
    {
        return in_array($command, $this->_commands);
    }

    /**
     * @param $action
     * @param $arguments
     * @return array|boolean|object|string
     */
    public function __call($action, $arguments)
    {
        $object = count($arguments) ? (array) $arguments[0] : array();

        return $this->_querySubsonic($action, $object);
    }
}
