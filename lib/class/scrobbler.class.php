<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

class scrobbler {

        public $error_msg;
        public $username;
        public $password;
        public $challenge;
        public $submit_host;
        public $submit_port;
        public $submit_url;
        public $queued_tracks;
	public $reset_handshake = false; 

        /**
         * Constructor
         * This is the constructer it takes a username and password
         */
        public function __construct($username, $password,$host='',$port='',$url='',$challenge='') {

                $this->error_msg = '';
                $this->username = trim($username);
                $this->password = trim($password);
                $this->challenge = $challenge;
		$this->submit_host = $host; 
		$this->submit_port = $port; 
		$this->submit_url = $url; 
                $this->queued_tracks = array();

        } // scrobbler

        /**
         * get_error_msg
         */
        public function get_error_msg() {

                return $this->error_msg;

        } // get_error_msg

        /**
         * get_queue_count
	 */
        public function get_queue_count() {

                return count($this->queued_tracks);

        } // get_queue_count

        /**
         * handshake
         * This does a handshake with the audioscrobber server it doesn't pass the password, but 
	 * it does pass the username and has a 10 second timeout 
         */
        public function handshake() {

                $as_socket = fsockopen('post.audioscrobbler.com', 80, $errno, $errstr, 2);
                if(!$as_socket) {
                        $this->error_msg = $errstr;
                        return false;
                }

                $username	= rawurlencode($this->username);
		$timestamp	= time(); 
		$auth_token	= rawurlencode(md5($this->password . $timestamp)); 
		
		$get_string = "GET /?hs=true&p=1.2&c=apa&v=0.1&u=$username&t=$timestamp&a=$auth_token HTTP/1.1\r\n";
                
		fwrite($as_socket, $get_string);
                fwrite($as_socket, "Host: post.audioscrobbler.com\r\n");
                fwrite($as_socket, "Accept: */*\r\n\r\n");

                $buffer = '';
                while(!feof($as_socket)) {
                        $buffer .= fread($as_socket, 4096);
                }
                fclose($as_socket);
                $split_response = preg_split("/\r\n\r\n/", $buffer);
                if(!isset($split_response[1])) {
                        $this->error_msg = 'Did not receive a valid response';
                        return false;
                }
                $response = explode("\n", $split_response[1]);
		if(substr($response[0], 0, 6) == 'FAILED') {
                        $this->error_msg = substr($response[0], 7);
                        return false;
                }
                if(substr($response[0], 0, 7) == 'BADUSER') {
                        $this->error_msg = 'Invalid Username';
                        return false;
                }
		if(substr($response[0],0,7) == 'BADTIME') { 
			$this->error_msg = 'Your time is too far off from the server, or your PHP timezone is incorrect'; 
			return false; 
		} 
                if(substr($response[0], 0, 6) == 'UPDATE') {
                        $this->error_msg = 'You need to update your client: '.substr($response[0], 7);
                        return false;
                }

                if(preg_match('/http:\/\/(.*):(\d+)(.*)/', $response[3], $matches)) {
                        $data['submit_host'] = $matches[1];
                        $data['submit_port'] = $matches[2];
                        $data['submit_url'] = $matches[3];
                } else {
                        $this->error_msg = "Invalid POST URL returned, unable to continue. Sent:\n$get_string\n----\nReceived:\n" . $buffer; 
                        return false;
                }

                $data['challenge'] = $response[1];
                return $data;

        } // handshake

	/**
	 * queue_track 
	 * This queues the LastFM track by storing it in this object, it doesn't actually
	 * submit the track or talk to LastFM in anyway, kind of useless for our uses but its
	 * here, and that's how it is. 
	 */
        public function queue_track($artist, $album, $title, $timestamp, $length,$track) {

                if ($length < 30) {
                        debug_event('LastFM',"Not queuing track, too short",'5');
                        return false;
                } 

                $newtrack = array();
                $newtrack['artist'] = $artist;
                $newtrack['album'] = $album;
		$newtrack['title'] = $title; 
                $newtrack['track'] = $track;
                $newtrack['length'] = $length;
                $newtrack['time'] = $timestamp;

                $this->queued_tracks[$timestamp] = $newtrack;
                return true; 
		
        } // queue_track

	/**
	 * submit_tracks
	 * This actually talks to LastFM submiting the tracks that are queued up. It
	 * passed the md5'd password combinted with the challenge, which is then md5'd
	 */ 
        public function submit_tracks() {

		// Check and make sure that we've got some queued tracks
                if(!count($this->queued_tracks)) {
                        $this->error_msg = "No tracks to submit";
                        return false;
                }

		//sort array by timestamp
                ksort($this->queued_tracks); 

		// build the query string
                $query_str = 's='.rawurlencode($this->challenge).'&';

                $i = 0;

                foreach($this->queued_tracks as $track) {
                        $query_str .= "a[$i]=".rawurlencode($track['artist'])."&t[$i]=".rawurlencode($track['title'])."&b[$i]=".rawurlencode($track['album'])."&";
                        $query_str .= "m[$i]=&l[$i]=".rawurlencode($track['length'])."&i[$i]=".rawurlencode($track['time'])."&";
			$query_str .= "n[$i]=" . rawurlencode($track['track']) . "&o[$i]=P&r[$i]=&"; 
                        $i++;
                }

		if (!trim($this->submit_host) || !$this->submit_port) { 
			$this->reset_handshake = true; 
			return false; 
		} 

                $as_socket = fsockopen($this->submit_host, intval($this->submit_port), $errno, $errstr, 2);

                if(!$as_socket) {
                        $this->error_msg = $errstr;
			$this->reset_handshake = true; 
                        return false;
                }

                $action = "POST ".$this->submit_url." HTTP/1.0\r\n";
                fwrite($as_socket, $action);
                fwrite($as_socket, "Host: ".$this->submit_host."\r\n");
                fwrite($as_socket, "Accept: */*\r\n");
		fwrite($as_socket, "User-Agent: Ampache/3.5\r\n");
                fwrite($as_socket, "Content-type: application/x-www-form-urlencoded\r\n");
                fwrite($as_socket, "Content-length: ".strlen($query_str)."\r\n\r\n");

                fwrite($as_socket, $query_str."\r\n\r\n");
                
		$buffer = '';
                while(!feof($as_socket)) {
                        $buffer .= fread($as_socket, 8192);
                }
                fclose($as_socket);

                $split_response = preg_split("/\r\n\r\n/", $buffer);
                if(!isset($split_response[1])) {
                        $this->error_msg = 'Did not receive a valid response';
			$this->reset_handshake = true; 
                        return false;
                }
                $response = explode("\n", $split_response[1]);
                if(!isset($response[0])) {
                        $this->error_msg = 'Unknown error submitting tracks'.
                                          "\nDebug output:\n".$buffer;
			$this->reset_handshake = true; 
                        return false;
                }
                if(substr($response[0], 0, 6) == 'FAILED') {
                        $this->error_msg = $response[0];
			$this->reset_handshake = true; 
                        return false;
                }
                if(substr($response[0], 0, 7) == 'BADAUTH') {
                        $this->error_msg = 'Invalid username/password (' . $response[0] . ')';
                        return false;
                }
		if (substr($response[0],0,10) == 'BADSESSION') { 
			$this->error_msg = 'Invalid Session passed (' . trim($response[0]) . ')'; 
			$this->reset_handshake = true; 
			return false; 
		} 
                if(substr($response[0], 0, 2) != 'OK') {
                        $this->error_msg = 'Response Not ok, unknown error'.
                                          "\nDebug output:\n".$buffer;
			$this->reset_handshake = true; 
                        return false;
                }

                return true;

        } // submit_tracks

} // end audioscrobbler class
?>
