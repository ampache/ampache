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
    /** @var UPnPPlaylist $object */
    private $_playlist = null;

    /** @var UPnPDevice $object */
    private $_device;

    private $_description_url;

    // 0 - stopped, 1 - playing
    private $_intState = 0;

    /**
     * Lazy initialization for UPNP device property
     * @return UPnPDevice
     */
    private function Device()
    {
        if ($this->_device === null) {
            $this->_device = new UPnPDevice($this->_description_url);
        }

        return $this->_device;
    }

    /**
     * Lazy initialization for UPNP playlist property
     * @return UPnPPlaylist
     */
    private function Playlist()
    {
        if ($this->_playlist === null) {
            $this->_playlist = new UPnPPlaylist($this->_description_url);
        }

        return $this->_playlist;
    }

    /**
     * UPnPPlayer
     * This is the constructor,
     * @param string $name
     * @param string $description_url
     */
    public function __construct(
        $name = "noname",
        $description_url = "http://localhost"
    ) {
        debug_event(__CLASS__, 'constructor: ' . $name . ' | ' . $description_url, 5);

        $this->_description_url = $description_url;

        $this->ReadIndState();
    }

    /**
     * add
     * append a song to the playlist
     * $name    Name to be shown in the playlist
     * $link    URL of the song
     * @param string $name
     * @param $link
     */
    public function PlayListAdd($name, $link): bool
    {
        $this->Playlist()->Add($name, $link);

        return true;
    }

    /**
     * delete_pos
     * This deletes a specific track
     * @param $track
     */
    public function PlaylistRemove($track): bool
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
    public function GetPlaylistItems()
    {
        return $this->Playlist()->AllItems();
    }

    /**
     * @return array
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
        $response    = $this->Device()->instanceOnly('GetTransportInfo');
        $responseXML = simplexml_load_string($response);
        if (empty($responseXML)) {
            return '';
        }
        list($state) = $responseXML->xpath('//CurrentTransportState');
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
     * @param $pos
     */
    public function Skip($pos): bool
    {
        if ($this->Playlist()->Skip($pos)) {
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
        $songId  = preg_replace('/(.+)\/oid\/(\d+)\/(.+)/i', '${2}', $songUrl);

        $song = new Song($songId);
        $song->format();
        $songItem = Upnp_Api::_itemSong($song, '');
        $domDIDL  = Upnp_Api::createDIDL($songItem, '');
        $xmlDIDL  = $domDIDL->saveXML();

        return [
            'InstanceID' => 0,
            $prefix . 'URI' => $songUrl,
            $prefix . 'URIMetaData' => htmlentities($xmlDIDL)
        ];
    }

    /**
     * @param $url
     */
    private function CallAsyncURL($url)
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
     * play the current song
     */
    public function Play(): bool
    {
        //!!$this->Stop();

        $this->SetIntState(1);

        $currentSongArgs = $this->prepareURIRequest($this->Playlist()->CurrentItem(), "Current");
        $response        = $this->Device()->sendRequestToDevice('SetAVTransportURI', $currentSongArgs, 'AVTransport');

        $args     = ['InstanceID' => 0, 'Speed' => 1];
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
            $args     = ['InstanceID' => 0, 'Speed' => 1];
            $response = $this->Device()->sendRequestToDevice('Play', $args, 'AVTransport');
        }

        return true;
    }

    /**
     * Repeat
     * This toggles the repeat state
     * @param $value
     */
    public function Repeat($value): bool
    {
        //!! TODO not implemented yet
        return true;
    }

    /**
     * Random
     * this toggles the random state
     * @param $value
     */
    public function Random($value): bool
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

    /**
     * SetVolume
     * @param $value
     */
    public function SetVolume($value): bool
    {
        $desiredVolume = Max(0, Min(100, $value));
        $instanceId    = 0;
        $channel       = 'Master';

        $response = $this->Device()->sendRequestToDevice('SetVolume', [
            'InstanceID' => $instanceId,
            'Channel' => $channel,
            'DesiredVolume' => $desiredVolume
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

        $response = $this->Device()->sendRequestToDevice('GetVolume', [
            'InstanceID' => $instanceId,
            'Channel' => $channel
        ]);

        $responseXML = simplexml_load_string($response);
        if (empty($responseXML)) {
            return '';
        }
        list($volume) = ($responseXML->xpath('//CurrentVolume'));
        debug_event(self::class, 'GetVolume:' . $volume, 5);

        return $volume;
    }

    /**
     * @param $state
     */
    private function SetIntState($state)
    {
        $this->_intState = $state;

        $sid  = 'upnp_ply_' . $this->_description_url;
        $data = json_encode($this->_intState);
        if (!Session::exists(AccessTypeEnum::STREAM->value, $sid)) {
            Session::create(['type' => 'stream', 'sid' => $sid, 'value' => $data]);
        } else {
            Session::write($sid, $data);
        }
        debug_event(self::class, 'SetIntState:' . $this->_intState, 5);
    }

    private function ReadIndState()
    {
        $sid  = 'upnp_ply_' . $this->_description_url;
        $data = Session::read($sid);

        $this->_intState = json_decode($data, true) ?? 0;
        debug_event(self::class, 'ReadIndState:' . $this->_intState, 5);
    }
}
