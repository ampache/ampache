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
    private $_description_url;
	private $_playlist = array();
	private $_device;
	

    /**
     * UPnPPlayer
     * This is the constructor, ...
     */    
    public function UPnPPlayer($name = "noname", $description_url = "http://localhost") 
	{
        debug_event('upnpPlayer', 'constructor: ' . $name . ' | ' . $description_url, 5);
        require_once AmpConfig::get('prefix') . '/modules/upnp/upnpdevice.php';

        $this->_description_url = $description_url;		
		$this->_device = new upnpdevice($this->_description_url);
    }

    /**
     * version
     */
    public function version() 
	{ 
        return true; 
    }
	
    /**
     * add
     * append a song to the playlist
     * $name    Name to be shown in the playlist
     * $url     URL of the song
     */      
    public function PlayListAdd($name, $url) 
	{
		$this->_playlist[] = array('name' => $name, 'url' => $url);
        return true;
    }

    /**
     * clear_playlist
     * this flushes the playlist cache (I hope this means clear)
     */
    public function PlayListClear() 
	{ 
		$this->_playlist = array();
        return true; 
    }
    
    /**
     * next
     * go to next song
     */      
    public function Next() 
	{
        return true; 
    }

    /**
     * prev
     * go to previous song
     */      
    public function Prev() 
	{
        return true;
    }

    /**
     * skip
     * This skips to POS in the playlist
     */
    public function Skip($pos) 
	{ 
        return true; 
    }
    
    /** 
     * play
     * play the current song
     */      
    public function Play() 
	{
		$songUrl = $this->_playlist[0]['url'];
		$songName = $this->_playlist[0]['name'];
		
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
		$response = $this->_device->sendRequestToDevice('SetAVTransportURI', $args, 'AVTransport');

		$args = array( 'InstanceID' => 0, 'Speed' => 1);
		$response = $this->_device->sendRequestToDevice('Play', $args, 'AVTransport');
		
        return true; 
    }
        
    /** 
     * pause
     * toggle pause mode on current song
     */      
    public function Pause() 
	{
        debug_event('upnpPlayer', 'Pause', 5);
		$response = $this->_device->instanceOnly('Pause');
        return true; 
    }
    
    /** 
     * stop
     * stops the current song amazing!
     */      
    public function Stop() 
	{
        debug_event('upnpPlayer', 'Stop', 5);
		$args = array( 'InstanceID' => 0, 'Speed' => 1);
		$response = $this->_device->sendRequestToDevice('Stop', $args, 'AVTransport');
		return true;
    }

    /** 
      * repeat
     * This toggles the repeat state
     */
    public function Repeat($value) 
	{ 
        return true;  
    }

    /** 
     * random
     * this toggles the random state
     */
    public function Random($value) 
	{ 
        return true; 
    }

    /**
     * delete_pos
     * This deletes a specific track
     */
    public function DeletePos($track) 
	{ 
        return true; 
    }

    /**
     * state
     * This returns the current state of the player
     */
    public function State() 
	{ 
        $state = 'play';
        return $state; 
    }

    /**
     * extract the full state from the xml file and send to status in vlccontroller for further parsing.
     *  
     */
    public function FullState() 
	{
        return $results;
    }
   

    /**
     * volume_up
     * This increases the volume of vlc , set to +20 can be changed to your preference
     */
    public function volume_up() 
	{ 
        return $this->set_volume(20); 
    }

    /**
     * volume_down
     * This decreases the volume, can be set to your preference
     */
    public function volume_down() 
	{ 
        return $this->set_volume(10); 
    }

    /**
     * set_volume
     * This sets the volume as best it can, i think it's from 0 to 400, need more testing'
     */
    public function set_volume($value) 
	{ 
		$desiredVolume = value;
		$channel = 'Master';
		$instanceId = 0;
		
		$response = $this->_device->sendRequestToDevice( 'SetVolume', array(
			'DesiredVolume' => $desiredVolume,
			'Channel' => $channel,
			'InstanceId' => $instanceId,
		));

        return true; 
    }

     /**
     * get_tracks
     * This returns a delimiated string of all of the filenames
     * current in your playlist, only url's at the moment,normal files put in the playlist with vlc wil not show'
     */
    public function get_tracks() 
	{ 
        return $results; 
    }
   

} // End UPnPPlayer Class
?>
