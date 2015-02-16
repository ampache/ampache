<?php 
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

/**
 * UPnPPlayer Class
 *
 * This player controls an instance of UPnP player 
 *
 */
class UPnPPlayer 
{
    /* @var UPnPPlaylist $object */
    private $_playlist = null;

    /* @var UPnPDevice $object */
    private $_device;

    private $_description_url = null;

    /**
     * Lazy initialization for UPNP device property
     * @return UPnPDevice
     */
    private function Device()
    {
        if (is_null($this->_device))
            $this->_device = new UPnPDevice($this->_description_url);
        return $this->_device;
    }

    /**
     * Lazy initialization for UPNP playlist property
     * @return UPnPPlaylist
     */
    private function Playlist()
    {
        if (is_null($this->_playlist))
            $this->_playlist = new UPnPPlaylist();
        return $this->_playlist;
    }

    
    /**
     * UPnPPlayer
     * This is the constructor,
     */
    public function UPnPPlayer($name = "noname", $description_url = "http://localhost") 
    {
        debug_event('upnpPlayer', 'constructor: ' . $name . ' | ' . $description_url, 5);
        $this->_description_url = $description_url;

        require_once AmpConfig::get('prefix') . '/modules/upnp/upnpdevice.php';
        require_once AmpConfig::get('prefix') . '/modules/upnp/upnpplaylist.php';
    }

    /**
     * add
     * append a song to the playlist
     * $name    Name to be shown in the playlist
     * $link    URL of the song
     */      
    public function PlayListAdd($name, $link) 
    {
        $this->Playlist()->Add($name, $link);
        return true;
    }

    /**
     * delete_pos
     * This deletes a specific track
     */
    public function PlaylistRemove($track) 
    {
        $this->Playlist()->RemoveTrack($track);
        return true;
    }
    

    /**
     * clear playlist
     * this flushes the playlist cache (I hope this means clear)
     */
    public function PlaylistClear() 
    { 
        $this->Playlist()->Clear();
        return true; 
    }

     /**
     * GetPlayListItems
     * This returns a delimited string of all of the filenames
     * current in your playlist, only url's at the moment
     */
    public function GetPlaylistItems() 
    { 
        return $this->Playlist()->AllItems();
    }

    public function GetCurrentItem()
    {
        return $this->Playlist()->CurrentItem();
    }

    public function GetState()
    {
        $response = $this->Device()->instanceOnly('GetTransportInfo');
        $responseXML = simplexml_load_string($response);
        list($state) = $responseXML->xpath('//CurrentTransportState');

        debug_event('upnpPlayer', 'GetState = ' . $state, 5);

        return $state;
    }

    /**
     * next
     * go to next song
     */      
    public function Next() 
    {
        debug_event('upnpPlayer', 'Next', 5);
        $this->Playlist()->Next();
        $this->Play();
        return true;
    }

    /**
     * prev
     * go to previous song
     */      
    public function Prev() 
    {
        debug_event('upnpPlayer', 'Prev', 5);
        $this->Playlist()->Prev();
        $this->Play();
        return true;
    }

    /**
     * skip
     * This skips to POS in the playlist
     */
    public function Skip($pos) 
    { 
        debug_event('upnpPlayer', 'Skip', 5);
        $this->Playlist()->Skip($pos);
        $this->Play();
        return true;
    }

    /** 
     * play
     * play the current song
     */
    public function Play() 
    {
        $current = $this->Playlist()->CurrentItem();
        $songUrl = $current['link'];
        $songName = $current['name'];

        $songId = preg_replace('/(.+)\/oid\/(\d+)\/(.+)/i', '${2}', $songUrl);
        debug_event('upnpPlayer', 'Play: ' . $songName . ' | ' . $songUrl . ' | ' . $songId, 5);
        
        $song = new song($songId);
        $song->format();
        $songItem = Upnp_Api::_itemSong($song, '');
        $domDIDL = Upnp_Api::createDIDL($songItem);
        $xmlDIDL = $domDIDL->saveXML();

        $this->Stop();

        $args = array(
            'InstanceID' => 0,
            'CurrentURI' => $songUrl,
            'CurrentURIMetaData' => htmlentities($xmlDIDL)
        );
        $response = $this->Device()->sendRequestToDevice('SetAVTransportURI', $args, 'AVTransport');

        $args = array( 'InstanceID' => 0, 'Speed' => 1);
        $response = $this->Device()->sendRequestToDevice('Play', $args, 'AVTransport');

        $sid = $this->Device()->Subscribe();
        $_SESSION['upnp_SID'] = $sid;

        return true; 
    }

    /**
     * Stop
     * stops the current song amazing!
     */      
    public function Stop() 
    {
        debug_event('upnpPlayer', 'Stop', 5);

        $response = $this->Device()->instanceOnly('Stop');

        $sid = $_SESSION['upnp_SID'];
        $this->Device()->UnSubscribe($sid);
        $_SESSION['upnp_SID'] = "";

        return true;
    }

    /**
     * pause
     * toggle pause mode on current song
     */
    public function Pause()
    {
        $state = $this->GetState();
        debug_event('upnpPlayer', 'Pause. prev state = ' . $state, 5);

        if ($state == 'PLAYING') {
            $response = $this->Device()->instanceOnly('Pause');
        }
        else {
            $args = array( 'InstanceID' => 0, 'Speed' => 1);
            $response = $this->Device()->sendRequestToDevice('Play', $args, 'AVTransport');
        }

        return true;
    }

    /** 
     * Repeat
     * This toggles the repeat state
     */
    public function Repeat($value) 
    {
        //!! TODO not implemented yet
        return true;  
    }

    /** 
     * Random
     * this toggles the random state
     */
    public function Random($value) 
    {
        //!! TODO not implemented yet
        return true;
    }

    /**
     *
     *  
     */
    public function FullState() 
    {
        //!! TODO not implemented yet
        return "";
    }
   

    /**
     * VolumeUp
     * increases the volume
     */
    public function VolumeUp() 
    { 
        $volume = $this->GetVolume() + 2;
        return $this->SetVolume($volume); 
    }

    /**
     * VolumeDown
     * decreases the volume
     */
    public function VolumeDown() 
    { 
        $volume = $this->GetVolume() - 2;
        return $this->SetVolume($volume); 
    }

    /**
     * SetVolume
     */
    public function SetVolume($value) 
    { 
        $desiredVolume = Max(0, Min(100, $value));
        $channel = 'Master';
        $instanceId = 0;

        $response = $this->Device()->sendRequestToDevice( 'SetVolume', array(
            'InstanceID' => $instanceId,
            'Channel' => $channel,
            'DesiredVolume' => $desiredVolume
        ));

        return true; 
    }

    /**
     * GetVolume
     */
    public function GetVolume()
    { 
        $instanceId = 0;
        $channel = 'Master';
        
        $response = $this->Device()->sendRequestToDevice( 'GetVolume', array(
            'InstanceID' => $instanceId,
            'Channel' => $channel
        ));

        $responseXML = simplexml_load_string($response);
        list($volume) = ($responseXML->xpath('//CurrentVolume'));
        debug_event('upnpPlayer', 'GetVolume:' . $volume, 5);
        
        return $volume; 
    }

} // End UPnPPlayer Class
?>
