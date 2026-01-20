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

namespace Ampache\Module\Playback\Localplay\Upnp;

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Song;
use Ampache\Module\Api\Upnp_Api;
use Ampache\Module\System\Session;
use SimpleXMLElement;

/**
 * UPnPPlayer Class
 *
 * This player controls an instance of UPnP player
 *
 */
class UPnPPlayer
{
    private ?UPnPPlaylist $_playlist = null;

    private ?UPnPDevice $_device = null;

    private string $_description_url = "http://localhost";

    private int $_intState = 0; // 0 - stopped, 1 - playing

    private bool $_shuffle = false; // 0 - stopped, 1 - playing

    public function __construct(
        string $name = "noname",
        string $description_url = "http://localhost"
    ) {
        debug_event(self::class, 'constructor: ' . $name . ' | ' . $description_url, 5);

        $this->_description_url = $description_url;

        $this->ReadIndState();
    }

    /**
     * Lazy initialization for UPNP device property
     */
    private function Device(): UPnPDevice
    {
        if ($this->_device === null) {
            $this->_device = new UPnPDevice($this->_description_url);
        }

        return $this->_device;
    }

    /**
     * Lazy initialization for UPNP playlist property
     */
    private function Playlist(): UPnPPlaylist
    {
        if ($this->_playlist === null) {
            $this->_playlist = new UPnPPlaylist($this->_description_url);
        }

        return $this->_playlist;
    }

    /**
     * add
     * append a song to the playlist
     * $name Name to be shown in the playlist
     * $link URL of the song
     */
    public function PlayListAdd(string $name, string $link): bool
    {
        $this->Playlist()->Add($name, $link);

        return true;
    }

    /**
     * delete_pos
     * This deletes a specific track
     */
    public function PlaylistRemove(int $track): bool
    {
        $this->Playlist()->RemoveTrack($track);

        return true;
    }

    /**
     * PlaylistClear
     */
    public function PlaylistClear(): bool
    {
        $this->Playlist()->Clear();

        return true;
    }

    /**
     * GetPlayListItems
     * This returns a delimited string of all of the filenames
     * current in your playlist, only urls at the moment
     */
    public function GetPlaylistItems(): array
    {
        return $this->Playlist()->AllItems();
    }

    /**
     * @return array{name?: string, link?: string}
     */
    public function GetCurrentItem(): array
    {
        return $this->Playlist()->CurrentItem();
    }

    /**
     * GetState
     *
     * @return SimpleXMLElement|string
     */
    public function GetState()
    {
        $state       = '';
        $response    = $this->Device()->instanceOnly('GetTransportInfo');
        $responseXML = simplexml_load_string($response);

        if ($responseXML instanceof SimpleXMLElement) {
            $xpath = $responseXML->xpath('//CurrentTransportState');
            if (is_array($xpath)) {
                list($state) = $xpath;
            }
        }

        debug_event(self::class, 'GetState = ' . $state, 5);

        return $state;
    }

    /**
     * next
     * go to next song
     * @param bool $forcePlay
     */
    public function Next($forcePlay = true): bool
    {
        // get current internal play state, for case if someone has changed it
        if (!$forcePlay) {
            $this->ReadIndState();
        }
        if (($forcePlay || ($this->_intState == 1)) && ($this->Playlist()->Next())) {
            $this->Play();

            return true;
        }

        return false;
    }

    /**
     * prev
     * go to previous song
     */
    public function Prev(): bool
    {
        if ($this->Playlist()->Prev()) {
            $this->Play();

            return true;
        }

        return false;
    }

    /**
     * skip
     * This skips to POS in the playlist
     */
    public function skip(int $track_id): bool
    {
        if ($this->Playlist()->skip($track_id)) {
            $this->Play();

            return true;
        }

        return false;
    }

    /**
     * @param $song
     * @param $prefix
     */
    private function prepareURIRequest($song, $prefix): ?array
    {
        if ($song == null) {
            return null;
        }

        $songUrl = $song['link'];
        $songId  = (int)preg_replace('/(.+)\/oid\/(\d+)\/(.+)/i', '${2}', $songUrl);

        $song     = new Song($songId);
        $songItem = Upnp_Api::_itemSong($song, '');
        $domDIDL  = Upnp_Api::createDIDL($songItem, '');
        $xmlDIDL  = $domDIDL->saveXML();

        return [
            'InstanceID' => 0,
            $prefix . 'URI' => $songUrl,
            $prefix . 'URIMetaData' => htmlentities($xmlDIDL),
        ];
    }

    /**
     * CallAsyncURL
     */
    private function CallAsyncURL(string $url): void
    {
        $curl = curl_init();
        if ($curl) {
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_exec($curl);
            curl_close($curl);
        }
    }

    /**
     * play
     * play a random song
     */
    public function PlayShuffle(bool $state): bool
    {
        return $this->_shuffle = $state;
    }

    /**
     * play
     * play the current song
     */
    public function Play(): bool
    {
        //!!$this->Stop();

        $this->SetIntState(1);

        if ($this->_shuffle) {
            $items = $this->Playlist()->AllItems();
            $item  = $items[array_rand($items)];
        } else {
            $item = $this->Playlist()->CurrentItem();
        }
        $currentSongArgs = $this->prepareURIRequest($item, "Current") ?? [];
        $response        = $this->Device()->sendRequestToDevice('SetAVTransportURI', $currentSongArgs, 'AVTransport');

        $args = [
            'InstanceID' => 0,
            'Speed' => 1,
        ];
        $response = $this->Device()->sendRequestToDevice('Play', $args, 'AVTransport');

        //!! UPNP subscription work not for all renderers, and works strange
        //!! so now is not used
        //$sid = $this->Device()->Subscribe();
        //$_SESSION['upnp_SID'] = $sid;

        // launch special page in background for periodically check play status
        $url = AmpConfig::get('local_web_path') . "/upnp/playstatus.php";
        $this->CallAsyncURL($url);

        return true;
    }

    /**
     * Stop
     * stops the current song amazing!
     */
    public function Stop(): bool
    {
        $this->SetIntState(0);
        $response = $this->Device()->instanceOnly('Stop');

        //!! UPNP subscription work not for all renderers, and works strange
        //!! so now is not used
        //$sid = $_SESSION['upnp_SID'];
        //$_SESSION['upnp_SID'] = "";
        //$this->Device()->UnSubscribe($sid);

        return true;
    }

    /**
     * pause
     * toggle pause mode on current song
     */
    public function Pause(): bool
    {
        $state = $this->GetState();
        debug_event(self::class, 'Pause. prev state = ' . $state, 5);

        if ($state == 'PLAYING') {
            $response = $this->Device()->instanceOnly('Pause');
        } else {
            $args = [
                'InstanceID' => 0,
                'Speed' => 1
            ];
            $response = $this->Device()->sendRequestToDevice('Play', $args, 'AVTransport');
        }

        return true;
    }

    /**
     * Repeat
     * This toggles the repeat state
     */
    public function repeat(bool $state): bool
    {
        //!! TODO not implemented yet
        return true;
    }

    /**
     * Random
     * this toggles the random state
     */
    public function Random(bool $state): bool
    {
        //!! TODO not implemented yet
        return true;
    }

    /**
     *
     */
    public function FullState(): string
    {
        //!! TODO not implemented yet
        return "";
    }

    /**
     * VolumeUp
     * increases the volume
     */
    public function VolumeUp(): bool
    {
        $volume = $this->GetVolume() + 2;

        return $this->SetVolume($volume);
    }

    /**
     * VolumeDown
     * decreases the volume
     */
    public function VolumeDown(): bool
    {
        $volume = $this->GetVolume() - 2;

        return $this->SetVolume($volume);
    }

    public function SetVolume(int $value): bool
    {
        $desiredVolume = max(0, min(100, $value));
        $instanceId    = 0;
        $channel       = 'Master';

        $this->Device()->sendRequestToDevice('SetVolume', [
            'InstanceID' => $instanceId,
            'Channel' => $channel,
            'DesiredVolume' => $desiredVolume,
        ]);

        return true;
    }

    /**
     * GetVolume
     *
     * @return SimpleXMLElement|string
     */
    public function GetVolume()
    {
        $instanceId = 0;
        $channel    = 'Master';
        $arguments  = [
            'InstanceID' => $instanceId,
            'Channel' => $channel,
        ];

        $volume      = '';
        $response    = $this->Device()->sendRequestToDevice('GetVolume', $arguments);
        $responseXML = simplexml_load_string($response);

        if ($responseXML instanceof SimpleXMLElement) {
            $xpath = $responseXML->xpath('//CurrentVolume');
            if (is_array($xpath)) {
                list($volume) = $xpath;
            }
        }

        debug_event(self::class, 'GetVolume:' . $volume, 5);

        return $volume;
    }

    private function SetIntState(int $state): void
    {
        $this->_intState = $state;

        $sid  = 'upnp_ply_' . $this->_description_url;
        $data = json_encode($this->_intState) ?: '';
        if (!Session::exists(AccessTypeEnum::STREAM->value, $sid)) {
            Session::create(['type' => 'stream', 'sid' => $sid, 'value' => $data]);
        } else {
            Session::write($sid, $data);
        }
        debug_event(self::class, 'SetIntState:' . $this->_intState, 5);
    }

    private function ReadIndState(): void
    {
        $sid  = 'upnp_ply_' . $this->_description_url;
        $data = Session::read($sid);

        $this->_intState = json_decode($data, true) ?? 0;
        debug_event(self::class, 'ReadIndState:' . $this->_intState, 5);
    }
}
