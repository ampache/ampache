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
if(!defined('IN_GMAPI')) { die('...'); }

class MM_Protocol {
	
	private $_mac;
	private $_hostname;
	
	private $_pb_service;
	private $_pre_define;
	
	public function __construct() {
		$this->_hostname = GMAPI_COMPUTER_NAME;
		
	}
	
	public function initProtocol($mac_address) {
		$this->_mac = $this->getMac($mac_address);
		
		$this->_pre_define = array();
		
		array_push($this->_pre_define, array(
			"name"=>"upload_auth_filled",
			"fields"=>array(
				"address"=>$this->_mac,
				"hostname"=>$this->_hostname
			)
		));
		
		
		array_push($this->_pre_define, array(
			"name"=>"metadata_request_filled",
			"fields"=>array(
				"address"=>$this->_mac
			)
		));
		
		$this->_pb_services = array(
			"upload_auth"=>'upauth',
			"client_state"=>'clientstate',
			"metadata"=>'metadata?version=1');
	}
	
	public function make_pb($pb_name) {
		$name = "PB_".GMAPIUtils::to_camel_case($pb_name);
		
		if(!class_exists($name))
			return false;
		
		$pb = new $name();
		
		$pb_filled = GMAPIUtils::getAttr($this->_pre_define, $pb_name."_filled");

		if($pb_filled)
		{
			$pb_filled = $pb_filled['fields'];
			foreach($pb_filled as $key=>$value) {
				$pb->setValue($key, $value);
			}
		}
		return $pb;
	}

	public function make_metadata_request($files, &$filemap) {
		$files_upload = array();
		$temp_filemap = array();
		
		if(is_array($files)) {
			if(is_array($files[0])) {
				foreach($files as $file_detail) {
					$files_upload[] = array("filepath"=>$file_detail['filepath'],
											"track"=>$file_detail['track']);
				}
			} else {
				foreach($files as $file) {
					$files_upload[] = array("filepath"=>$file);
				}
			}
		} else {
			$files_upload[] = array("filepath"=>$files);
		}

		$tracks = array();
		foreach($files_upload as $upload) {
			$filepath = $upload['filepath'];
			if(!file_exists($filepath)) {
				GMAPIPrintDebug("cannot find file ".$filepath);
				continue;
			}
			
			$content = file_get_contents($filepath);
			if(!$content) {
				GMAPIPrintDebug("cannot load file ".$filepath);
				continue;
			}
			
			$filename = basename($filepath);
			$filemap = array();
			
			$metadata_pb = $this->make_pb("metadata_request");
			$metadata_track = $this->make_pb("track");
			
			$hash = base64_encode(md5($content,true));
			$hash = str_replace("=","",$hash);
			$id = $hash;
			$temp_filemap[$id] = array("filename"=>$filename,
								"filepath"=>$filepath);
			
			if(empty($upload['track']))
				$track = array();
			else
				$track = $upload['track'];
			
			$track['id'] = $id;
			if(empty($track['title']))
				$track['title'] = $filename;
			
			if(empty($track['fileSize']))
				$track['fileSize'] = filesize($filepath);
		
			foreach($track as $key=>$value) {
				$metadata_track->setValue($key, $value);
			}
			
			$tracks[] = $metadata_track;
		}
		
		if(count($tracks) == 0)
			return false;
		
		$filemap = $temp_filemap;
		$metadata_pb->tracks =  $tracks;
		
		return $metadata_pb;
	}
	
	public function make_upload_session_requests($filemap, $server_response) {
		$session = array();
		$uploads = $server_response->response->uploads;
		
		foreach($uploads as $upload) {
			$file = $filemap[$upload->id];
			$filename = $file['filename'];
			$filepath = $file['filepath'];
			$upload_title = $filename;

			$inlined = array(
				"title"=>"jumper-uploader-title-42",
				"ClientId"=>$upload->id,
				"ClientTotalSongCount"=>count($uploads),
				"CurrentTotalUploadedCount"=>"0",
				"CurrentUploadingTrack"=>$upload_title,
				"ServerId"=> $upload->serverId,
				"SyncNow"=>"true",
				"TrackBitRate"=>800,
				"TrackDoNotRematch"=>"false",
				"UploaderId"=>$this->_mac
			);

			$payload = array(
				"clientId"=>"Jumper Uploader",
				"createSessionRequest"=>array(
					"fields"=>array(
						array(
							"external"=> array(
								"filename"=>$filename,
								"name"=>$filename,
								"put"=>(object)null,
								"size"=>filesize($filepath)
							)
						)
					)
				),
				"protocolVersion"=>"0.8"
			);
			
			foreach($inlined as $key=>$value) {
				array_push($payload['createSessionRequest']['fields'],array(
					"inlined"=>array(
						"content"=>$value."",
						"name"=>$key
					)
				));
			}

			array_push($session, array(
				$filename,
				$filepath,
				$upload->serverId,
				$payload
			));
		}
		
		return $session;
	}
	
	public function getpb_services($service_name) {
		return $this->_pb_services[$service_name];
	}
	
	public function getMac($mac_address) {
		$mac = strtolower($mac_address);
		$mac = str_replace("-",":",$mac);
		return $mac;
	}
}

?>