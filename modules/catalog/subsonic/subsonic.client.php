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

// SubsonicClient inspired from https://github.com/webeight/SubExt

class SubsonicClient
{
    protected $_serverUrl;
    protected $_serverPort;
    protected $_creds;
    protected $_commands;

    public function __construct($username, $password, $serverUrl, $port="4040", $client="Ampache")
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

    public function querySubsonic($action, $o=array(), $rawAnswer=false)
    {
        return $this->_querySubsonic($action, $o, $rawAnswer);
    }
    
    public function parameterize($url, $o = array())
    {
        $params = array_merge($this->_creds, $o);

        return $url . http_build_query($params);
    }

    protected function _querySubsonic($action, $o=array(), $rawAnswer=false)
    {
        // Make sure the command is in the list of commands
        if ($this->isCommand($action)) {
            $url = $this->parameterize($this->getServer() . "/rest/" . $action . ".view?", $o);
            
            $options = array(
                CURLOPT_URL => $url,
                CURLOPT_HEADER => 0,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_FOLLOWLOCATION => 1,
                CURLOPT_PORT => intval($this->_serverPort)
            );
            $ch = curl_init();
            curl_setopt_array($ch, $options);
            $answer = curl_exec($ch);
            curl_close($ch);
            if ($rawAnswer) {
                return $answer;
            } else {
                return $this->parseResponse($answer);
            }
        } else {
            return $this->error("Error: Invalid subsonic command: " . $action);
        }
    }

    public function setServer($server, $port=null)
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

    public function getServer()
    {
        return $this->_serverUrl . ":" . $this->_serverPort;
    }

    protected function error($error, $data=null)
    {
        error_log($error . "\n" . print_r($data, true));

        return (object) array("success" => false, "error" => $error, "data" => $data);
    }

    protected function parseResponse($response)
    {
        $arr = json_decode($response, true);
        if ($arr['subsonic-response']) {
            $response = (array)$arr['subsonic-response'];
            $data     = $response;

            return array("success" => ($response['status'] == "ok"), "data" => $data);
        } else {
            return $this->error("Invalid response from server!", $object);
        }
    }

    public function isCommand($command)
    {
        return in_array($command, $this->_commands);
    }

    public function __call($action, $arguments)
    {
        $o = count($arguments) ? (array) $arguments[0] : array();

        return $this->_querySubsonic($action, $o);
    }
}
