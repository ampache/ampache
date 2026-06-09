<?php

declare(strict_types=0);

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

namespace Ampache\Module\Catalog;

/**
 * SubsonicClient inspired from https://github.com/webeight/SubExt
 */
class SubsonicClient
{
    protected string $_serverUrl;

    protected string $_serverPort;

    /** @var string[] $_creds */
    protected array $_creds;

    /** @var string[] $_commands */
    protected array $_commands = [
        'addChatMessage',
        'changePassword',
        'createPlaylist',
        'createShare',
        'createUser',
        'deletePlaylist',
        'deleteShare',
        'deleteUser',
        'download',
        'getAlbum',
        'getAlbumList',
        'getArtist',
        'getArtistInfo',
        'getChatMessages',
        'getCoverArt',
        'getIndexes',
        'getLicense',
        'getLyrics',
        'getMusicDirectory',
        'getMusicFolders',
        'getNowPlaying',
        'getOpenSubsonicExtensions',
        'getPlaylist',
        'getPlaylists',
        'getPordcasts',
        'getRandomSongs',
        'getSong',
        'getUser',
        'jukeboxControl',
        'ping',
        'scrobble',
        'search',
        'search2',
        'setRating',
        'stream',
        'updateShare',
    ];

    /**
     * SubsonicClient constructor.
     */
    public function __construct(
        string $username,
        string $password,
        string $serverUrl,
        ?string $port = null,
        string $client = "Ampache",
    ) {
        $this->setServer($serverUrl, $port);

        $this->_creds = [
            'u' => $username,
            'p' => $password,
            'v' => '1.8.0',
            'c' => $client,
            'f' => 'json',
        ];
    }

    /**
     * @param array<string, int|string> $object
     */
    public function querySubsonic(string $action, array $object = [], ?bool $rawAnswer = false): object|bool|array|string
    {
        return $this->_querySubsonic($action, $object, $rawAnswer);
    }

    /**
     * @param array<string, int|string>|null $object
     */
    public function parameterize(string $url, ?array $object = []): string
    {
        $params = array_merge($this->_creds, $object ?? []);

        return $url . http_build_query($params);
    }

    /**
     * @param array<string, int|string>|null $object
     */
    protected function _querySubsonic(string $action, ?array $object = [], ?bool $rawAnswer = false): object|bool|array|string
    {
        // Make sure the command is in the list of commands
        if ($this->isCommand($action)) {
            $url  = $this->parameterize($this->getServer() . "/rest/" . $action . ".view?", $object);
            $curl = curl_init($url);
            if ($curl) {
                curl_setopt_array(
                    $curl,
                    [
                        CURLOPT_HEADER => false,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_CONNECTTIMEOUT => 8,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_PORT => (int)($this->_serverPort)
                    ]
                );

                $answer = curl_exec($curl);
                if ($rawAnswer) {
                    return $answer;
                }

                return $this->parseResponse($answer);
            }
        } else {
            return $this->error("Error: Invalid subsonic command: " . $action, $object);
        }

        return false;
    }

    public function setServer(string $server, ?string $port = null): void
    {
        $protocol = "";
        if (preg_match("/^https\:\/\//", $server)) {
            $protocol = "https://";
        }

        if ($protocol === '') {
            if (!preg_match("/^http\:\/\//", $server)) {
                $server = "http://" . $server;
            }

            $protocol = "http://";
        }

        preg_match("/\:\d{1,6}$/", $server, $matches);
        if ($matches !== []) {
            // If there's a port on the url, remove it and save it for later use.
            $server = str_replace($matches[0], "", $server);
            $_port  = str_replace(":", "", $matches[0]);
        }

        if (in_array($port, [null, '', '0'], true) && isset($_port)) {
            // If port parameter not set but there was one on the url, use the one from the url.
            $port = $_port;
        } elseif (in_array($port, [null, '', '0'], true)) {
            $port = ($protocol === "https://") ? '443' : '80';
        } else {
            $port = '4040';
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
     * @param array<string, int|string>|null $data
     */
    protected function error(string $error, ?array $data = null): object
    {
        error_log($error . "\n" . print_r($data, true));

        return (object)[
            'success' => false,
            'error' => $error,
            'data' => $data
        ];
    }

    protected function parseResponse(bool|string $response): object|array
    {
        $arr = (is_string($response))
            ? json_decode($response, true)
            : false;
        if (is_array($arr) && $arr['subsonic-response']) {
            $response = (array)$arr['subsonic-response'];
            $data     = $response;

            return [
                "success" => ($response['status'] == "ok"),
                "data" => $data
            ];
        }

        debug_event(self::class, 'parseResponse ERROR: ' . print_r($response, true), 1);

        return $this->error("Invalid response from server!");
    }

    public function isCommand(string $command): bool
    {
        return in_array($command, $this->_commands);
    }

    /**
     * @param array<string, int|string>|null $object
     * @return array|bool|object|string
     */
    public function __call(string $action, ?array $object = null)
    {
        return $this->_querySubsonic($action, $object ?? []);
    }
}
