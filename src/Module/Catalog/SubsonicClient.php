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

namespace Ampache\Module\Catalog;

/**
 * SubsonicClient inspired from https://github.com/webeight/SubExt
 */
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
     * @param bool $rawAnswer
     * @return array|bool|object|string
     */
    public function querySubsonic($action, $object = array(), $rawAnswer = false)
    {
        return $this->_querySubsonic($action, $object, $rawAnswer);
    }

    /**
     * @param $url
     * @param array $object
     */
    public function parameterize($url, $object = array()): string
    {
        $params = array_merge($this->_creds, $object);

        return $url . http_build_query($params);
    }

    /**
     * @param $action
     * @param array $object
     * @param bool $rawAnswer
     * @return array|bool|object|string
     */
    protected function _querySubsonic($action, $object = array(), $rawAnswer = false)
    {
        // Make sure the command is in the list of commands
        if ($this->isCommand($action)) {
            $url  = $this->parameterize($this->getServer() . "/rest/" . $action . ".view?", $object);
            $curl = curl_init($url);
            if ($curl) {
                curl_setopt_array(
                    $curl,
                    array(
                        CURLOPT_HEADER => 0,
                        CURLOPT_RETURNTRANSFER => 1,
                        CURLOPT_CONNECTTIMEOUT => 8,
                        CURLOPT_SSL_VERIFYPEER => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_PORT => (int)($this->_serverPort)
                    )
                );
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

        return false;
    }

    /**
     * @param string $server
     * @param string $port
     */
    public function setServer($server, $port = null): void
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
            // If there's a port on the url, remove it and save it for later use.
            $server = str_replace($matches[0], "", $server);
            $_port  = str_replace(":", "", $matches[0]);
        }
        if (empty($port) && isset($_port)) {
            // If port parameter not set but there was one on the url, use the one from the url.
            $port = $_port;
        } elseif (empty($port)) {
            $port = ($protocol === "https://") ? '443' : '80';
        }
        $this->_serverUrl  = $server;
        $this->_serverPort = $port;
    }

    /**
     * getServer
     */
    public function getServer(): string
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

        return (object)array("success" => false, "error" => $error, "data" => $data);
    }

    /**
     * @param $response
     * @return array|object
     */
    protected function parseResponse($response)
    {
        $arr = json_decode($response, true);
        if ($arr['subsonic-response']) {
            $response = (array)$arr['subsonic-response'];
            $data     = $response;

            return array(
                "success" => ($response['status'] == "ok"),
                "data" => $data
            );
        } else {
            debug_event(self::class, 'parseResponse ERROR: ' . print_r($arr, true), 1);

            return $this->error("Invalid response from server!");
        }
    }

    /**
     * @param $command
     */
    public function isCommand($command): bool
    {
        return in_array($command, $this->_commands);
    }

    /**
     * @param $action
     * @param $arguments
     * @return array|bool|object|string
     */
    public function __call($action, $arguments)
    {
        $object = count($arguments) ? (array)$arguments[0] : array();

        return $this->_querySubsonic($action, $object);
    }
}
