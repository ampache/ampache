<?php
/*
Copyright (C) 2012 raydan

http://code.google.com/p/unofficial-google-music-api-php/

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/
define("IN_GMAPI",true);
require_once('config.php');
require_once('utils.php');

require_once("PhpBuf/PhpBuf.php");

require_once('metadata/PB_Message_Abstract.php');
require_once('metadata/PB_UploadAuthResponse.php');
require_once('metadata/PB_Status.php');
require_once('metadata/PB_UploadAuth.php');
require_once('metadata/PB_MetadataRequest.php');
require_once('metadata/PB_TrackResponse.php');
require_once('metadata/PB_QueuedUpload.php');
require_once('metadata/PB_MetadataResponse.php');
require_once('metadata/PB_Track.php');


require_once('iProtocol.php');
require_once('protocol/WC_play.php');
require_once('protocol/WC_multidownload.php');
require_once('protocol/WC_deletesong.php');
require_once('protocol/WC_loadalltracks.php');
require_once('protocol/WC_modifyentries.php');
require_once('protocol/SJ_tracks.php');
require_once('protocol/SJ_playlists.php');
require_once('protocol/SJ_playlistbatch.php');


require_once('MM_Protocol.php');
require_once('GM_Protocol.php');
require_once('GM_Session.php');

class GMApi {
	
	private static $_version = "1.0";
	private static $_enable_debug;
	
	private $_gm_session;
	private $_mm_protocol;
	private $_email;
	private $_password;
	
	private $_enable_restore;
	private $_enable_mac_address_check;
	private $_enable_session_file;
	private $_skil_restore_check;
	
	private $_login_type;
	
	public function __construct() {
		$this->_enable_restore = false;
		$this->_enable_mac_address_check = true;
		$this->_enable_session_file = false;
		$this->_skil_restore_check = false;
		$this->_gm_session = new GM_Session($this);
		$this->_mm_protocol = new MM_Protocol($this);
	}
	
	/* API */
	public static function version() { return GMApi::$_version; }
	
	public static function setDebug($enable) { GMApi::$_enable_debug = $enable; }
	public static function isDebug() { return GMApi::$_enable_debug; }
	
	public static function clearAllSession() {
		GM_Session::clearAllSession();
	}
	
	
	/*
	try to restore login session from php $_SESSION or session file
	*/
	public function enableRestore($enable) { $this->_enable_restore = $enable; }
	public function isEnableRestore() { return $this->_enable_restore; }
	
	
	/*
	write current login session information to file for later use?
	*/
	public function enableSessionFile($enable) { $this->_enable_session_file = $enable; }
	public function isEnableSessionFile() { return $this->_enable_session_file; }


	/*
	set to false if already register the mac address, can save half second :D
	*/
	public function enableMACAddressCheck($enable) { $this->_enable_mac_address_check = $enable; }
	public function isEnableMACAddressCheck() { return $this->_enable_mac_address_check; }
	
	
	/*
	skip the restore login check, should NOT enable, can save half second :D
	*/
	public function skipRestoreCheck($enable) { $this->_skil_restore_check = $enable; }
	public function isSkipRestoreCheck() { return $this->_skil_restore_check; }
	
	
	/*
	fail, normal, session, file
	*/
	public function getLoginResultType() {
		return $this->_gm_session->getLoginResultType();
	}
	
	
	public function logout() {
		$this->_gm_session->logout();
	}
	
	public function login($email, $password, $mac_address) {
		$this->_email = $email;
		$this->_password = $password;
		$this->_mm_protocol->initProtocol($mac_address);
		$this->_gm_session->login($email, $password);
		
		if($this->isEnableMACAddressCheck()) {
			if($this->isAuthenticated()) {
				$res = $this->_mm_pb_call("upload_auth");
                
				if($res == false) {
					$this->logout();
					GMAPIPrintDebug("login success, but upload_auth fail");
				}
			}
		}
		return $this->isAuthenticated();
	}
	
	public function isAuthenticated() {
		return ($this->_gm_session->isLoggedIn());
	}
	
	public function get_all_songs() {
		$res = $this->_wc_call("loadalltracks");
		if($res == false)
			return false;
		
		if(!is_array($res['playlist'])) {
			if($res['success'] == false && $res['reloadXsrf'] == true) {
				$this->logout();
				$this->_gm_session->login($this->_email, $this->_password, false);
				if($this->isAuthenticated()) {
					return $this->get_all_songs();
				}
			}
			GMAPIPrintDebug("playlist not array");
			return false;
		}
		$output = array();
		$output = array_merge($output, $res['playlist']);
		$should_continue = (!empty($res['continuationToken']));
		while($should_continue) {
			$res = $this->_wc_call("loadalltracks",$res['continuationToken']);
			if($res == false)
				break;
			
			if(!is_array($res['playlist'])) {
				GMAPIPrintDebug("playlist not array");
				return false;
			}
			$output = array_merge($output, $res['playlist']);
			$should_continue = (!empty($res['continuationToken']));
		}
		
		return $output;
	}
	
	public function get_stream_url($songId) {
		$res = $this->_wc_call("play", $songId);
		if($res == false)
			return false;
		return $res['url'];
	}
	
	public function get_download_info($songId) {
		$res = $this->_wc_call("multidownload", $songId);
		if($res == false)
			return false;
		if(empty($res['downloadCounts']))
			return false;
		
		return array("url"=>$res['url'],
					"count"=>intval($res['downloadCounts'][$songId]));
	}
	
	public function delete_songs($songIds) {
		$res = $this->_wc_call("deletesong", $songIds);
		if($res == false)
			return false;
		return $res;
	}
	
	public function change_song_metadata($songData) {
		$res = $this->_wc_call("modifyentries", $songData);
		if($res == false)
			return false;
		return $res;
	}
	
	public function upload($files) {
		$fn_sid_map = array();
		$cid_map = array();
		
		$metadata_request = $this->_mm_protocol->make_metadata_request($files, $cid_map);
		if($metadata_request == false) {
			return false;
		}
	
		$metadataresp = $this->_mm_pb_call("metadata", $metadata_request);
		if($metadataresp == false) {
			return false;
		}
	
		$session_requests = $this->_mm_protocol->make_upload_session_requests($cid_map, $metadataresp);

		foreach($session_requests as $session) {
			$filename = $session[0];
			$filepath = $session[1];
			$server_id = $session[2];
			$payload = $session[3];
			
			$post_data = json_encode($payload);
			
			$success = false;
			$already_uploaded = false;
			$attempts = 0;
			
			while(!$success && $attempts < 3) {
				$result = $this->_gm_session->jumper_post("/uploadsj/rupio", $post_data);
				$res = json_decode($result,true);
				if(!empty($res['sessionStatus'])) {
					$success = true;
					break;
				}
				
				if(!empty($res['errorMessage'])) {
					$error_code = $res['errorMessage']['additionalInfo']['uploader_service.GoogleRupioAdditionalInfo']['completionInfo']['customerSpecificInfo']['ResponseCode'];
					$error_code = intval($error_code);
					switch($error_code)
					{
						case 503:
							$attempts = -1;
							break;
						case 200:
							$success = true;
							$already_uploaded = true;
							break;
						case 404:
							break;
						default:
							break;
					}
					if(!$success) {
						sleep(3);
						$attempts += 1;
					}
				}
				break;
			}
			
			if($success && !$already_uploaded) {
				$up = $res['sessionStatus']['externalFieldTransfers'][0];
				
				$content = file_get_contents($filepath);
				$result = $this->_gm_session->jumper_post($up['putInfo']['url'], $content, array('Content-Type'=>$up['content_type']));
				$res = json_decode($result,true);
				if($res['sessionStatus']['state'] == 'FINALIZED') {
					$fn_sid_map[$filename] = $server_id;
				}
			} else if($already_uploaded) {
				$fn_sid_map[$filename] = $server_id;
			} else {
				GMAPIPrintDebug("unable upload ".$filepath);
			}
		}
		
		return $fn_sid_map;
	}
	
	public function get_tracks($track_id = null) {
		$res = $this->_sj_call("tracks", $track_id);
		if($res == false)
			return false;
		return $res;
	}
	
	public function get_playlists($playlist_id = null) {
		$res = $this->_sj_call("playlists", $playlist_id);
		if($res == false)
			return false;
		return $res;
	}
	
	public function create_playlist($playlist_name) {
		if(empty($playlist_name) || strlen($playlist_name) <= 0)
			return false;
		
		$data = array('mutations'=>array(
						array(
							'create'=>array("name"=>$playlist_name),
						)
				)
			);
		$res = $this->_sj_call("playlistbatch", json_encode($data));
		if($res == false || empty($res['mutate_response']))
			return false;
		return $this->get_playlists($res['mutate_response']['id']);
	}
	
	
	
	/* private function */
	private function _sj_call($service_name, $args=null) {
		$protocol = GM_Protocol::getSJProtocol($service_name);
		if($protocol == false) {
			GMAPIPrintDebug("cannot find sj protocol \"".$service_name."\"");
			return false;
		}
		$result = $this->_gm_session->open_sj_https_url($protocol, $args);
		if($result == false)
			return false;
		
		return json_decode($result,true);
	}
	
	private function _wc_call($service_name, $args=null) {
		$protocol = GM_Protocol::getWCProtocol($service_name);
		if($protocol == false) {
			GMAPIPrintDebug("cannot find wc protocol \"".$service_name."\"");
			return false;
		}
		
		$query = $protocol->buildTransaction($args);
		$result = $this->_gm_session->open_authed_https_url($protocol, $query);
		if($result == false)
			return false;
		
		return json_decode($result,true);
	}
	
	private function _mm_pb_call($service_name, $req = null) {
		$res = $this->_mm_protocol->make_pb($service_name."_response");

		if($req == null) {
			$req = $this->_mm_protocol->make_pb($service_name."_request");
			if(!$req) {
				$req = $this->_mm_protocol->make_pb($service_name);
			}
		}
		
		$url = $this->_mm_protocol->getpb_services($service_name);
		
		$result = $this->_gm_session->protopost($url, $req);
		if($result) {
			$res->ParseFromString($result);
			return $res;
		}
		return false;
	}
	
}

?>
