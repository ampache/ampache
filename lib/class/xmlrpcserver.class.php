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

/**
 * xmlRpcServer
 * This class contains all the methods that the /server/xmlrpc.server.php will respond to 
 * to add a new method, just define a new public static function in here and it will be automagicaly
 * populated to xmlrpcserver.<FUNCTION> in /server/xmlrpc.server.php 
 */

class xmlRpcServer {

	/**
 	 * get_catalogs
	 * This returns a list of the current non-remote catalogs hosted on this Ampache instance
	 * It requires a key be passed as the first element
	 * //FIXME: USE TOKEN!
	 */
	public static function get_catalogs($xmlrpc_object) { 

		// Pull out the key
		$variable = $xmlrpc_object->getParam(0); 
		$key = $variable->scalarval(); 

		// Check it and make sure we're super green
		if (!vauth::session_exists('xml-rpc',$key)) { 
			debug_event('XMLSERVER','Error ' . $_SERVER['REMOTE_ADDR'] . ' with key ' . $key . ' does not match any ACLs','1'); 
			return new XML_RPC_Response(0,'503','Key/IP Mis-match Access Denied'); 
		} 

		// Go ahead and gather up the information they are legit
		$results = array(); 

		$sql = "SELECT `catalog`.`name`,COUNT(`song`.`id`) AS `count`,`catalog`.`id` AS `catalog_id` FROM `catalog` ". 
			"LEFT JOIN `song` ON `catalog`.`id`=`song`.`catalog` WHERE `catalog`.`catalog_type`='local' " . 
			"GROUP BY `catalog`.`id`"; 
		$db_results = Dba::query($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$results[] = $row; 
		} 

		// We need to set time limit at this point as who know how long this data is going to take
		// to return to the client
		set_time_limit(0); 

		$encoded_array = XML_RPC_encode($results); 
		debug_event('XMLSERVER','Returning data about ' . count($results) . ' catalogs to ' . $_SERVER['REMOTE_ADDR'],'5'); 

		return new XML_RPC_Response($encoded_array);
	} // get_catalogs

	
	
	/**
	 * get_songs
	 * This is a basic function to return all of the song data in a serialized format. It takes a start and end point
	 * as well as the TOKEN for auth mojo
	 * //FIXME: USE TOKEN!
  	 */
	public static function get_songs($xmlrpc_object) { 

		// We're going to be here a while
		set_time_limit(0); 
	
		// Pull out the key
		$variable = $xmlrpc_object->getParam(0);
		$key = $variable->scalarval();

		// Check it and make sure we're super green
		if (!vauth::session_exists('xml-rpc',$key)) {
			debug_event('XMLSERVER','Error ' . $_SERVER['REMOTE_ADDR'] . ' with key ' . $key . ' does not match any ACLs','1');
			return new XML_RPC_Response(0,'503','Key/IP Mis-match Access Denied');
		}
		
		// Now pull out the start and end
		$start	= intval($xmlrpc_object->params['1']->me['int']); 
		$end	= intval($xmlrpc_object->params['2']->me['int']);

		// Get Catalogs first
		$sql = "SELECT `catalog`.`id` FROM `catalog` WHERE `catalog`.`catalog_type`='local'"; 
		$db_results = Dba::query($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$where_sql .= "`song`.`catalog`='" . $row['id'] . "' OR"; 
		} 

		$where_sql = rtrim($where_sql,'OR'); 

		$sql = "SELECT `song`.`id` FROM `song` WHERE `song`.`enabled`='1' AND ($where_sql) LIMIT $start,$end"; 
		$db_results = Dba::query($sql); 

		while ($row = Dba::fetch_assoc($db_results)) { 
			$song = new Song($row['id']); 
			$song->fill_ext_info(); 
			$song->album	= $song->get_album_name(); 
			$song->artist	= $song->get_artist_name();
			//$song->genre	= $song->get_genre_name();  // TODO: Needs to be implemented 

			$output = serialize($song); 
			$results[] = $output ; 
		} // end while

		$encoded_array = XML_RPC_encode($results); 
		debug_event('XMLSERVER','Encoded ' . count($results) . ' songs (' . $start . ',' . $end . ')','5'); 

		return new XML_RPC_Response($encoded_array);

	} // get_songs
	
	/**
	 * get_album_images
	 * Returns the images information of the albums
	 */
	public static function get_album_images($xmlrpc_object) {
		// We're going to be here a while
		set_time_limit(0); 
	
		// Pull out the key
		$variable = $xmlrpc_object->getParam(0);
		$key = $variable->scalarval();

		// Check it and make sure we're super green
		if (!vauth::session_exists('xml-rpc',$key)) {
			debug_event('XMLSERVER','Error ' . $_SERVER['REMOTE_ADDR'] . ' with key ' . $key . ' does not match any ACLs','1');
			return new XML_RPC_Response(0,'503','Key/IP Mis-match Access Denied');
		}
		
		// Get Albums first
		$sql = "SELECT `album`.`id` FROM `album` "; 
		$db_results = Dba::query($sql);

		while ($row = Dba::fetch_assoc($db_results)) { 
			// Load the current album
			$album = new Album($row['id']);
			$art = $album->get_db_art();

			// Only return album ids with art
			if (count($art) != 0) {
				$output = serialize($album);
				$results[] = $output;
			}
		}
		
		$encoded_array = XML_RPC_encode($results); 
		debug_event('XMLSERVER','Encoded ' . count($results) . 'albums with art','5'); 

		return new XML_RPC_Response($encoded_array);
	}
	
	/**
	 * create_stream_session
	 * This creates a new stream session and returns the SID in question, this requires a TOKEN as generated by the handshake
	 */
	public static function create_stream_session($xmlrpc_object) { 

		// Pull out the key
		$variable = $xmlrpc_object->getParam(0);
		$key = $variable->scalarval();

		// Check it and make sure we're super green
		if (!vauth::session_exists('xml-rpc',$key)) {
			debug_event('XMLSERVER','Error ' . $_SERVER['REMOTE_ADDR'] . ' with key ' . $key . ' does not match any ACLs','1');
			return new XML_RPC_Response(0,'503','Key/IP Mis-match Access Denied');
		}

		if (!Stream::insert_session($key,'-1')) { 
			debug_event('XMLSERVER','Failed to create stream session','1'); 
			return new XML_RPC_Response(0,'503','Failed to Create Stream Session','1'); 
		} 
		
		return new XML_RPC_Response(XML_RPC_encode($key));
	} // create_stream_session

	/**
	 * check_song
	 * This checks remote catalog
	 */
	public static function check_song($xmlrpc_object) {

		// Pull out the key
		$variable = $xmlrpc_object->getParam(1);
		$key = $variable->scalarval();

		// Check it and make sure we're super green
		if (!vauth::session_exists('xml-rpc',$key)) {
			debug_event('XMLSERVER','Error ' . $_SERVER['REMOTE_ADDR'] . ' with key ' . $key . ' does not match any ACLs','1');
			return new XML_RPC_Response(0,'503','Key/IP Mis-match Access Denied');
		}

		$var = $xmlrpc_object->params['0']->me['int'];
		$sql = "SELECT `song`.`id` FROM `song` WHERE `id`='" . Dba::escape($var) ."'";
		$db_results = Dba::read($sql);
		if(Dba::num_rows($db_results) == '0') {
			$return = 0;
		} else {
			$return = 1;
		}

		return new XML_RPC_Response(XML_RPC_encode($return));

	}

	/**
	 * handshake
	 * This should be run before any other XMLRPC actions, it checks the KEY encoded with a timestamp then returns a valid TOKEN to be
	 * used in all further communication 
	 */
	public static function handshake($xmlrpc_object) { 
		debug_event('XMLSERVER','handshake: ' . print_r ($xmlrpc_object, true),'5');
		
		// Pull out the params
		$encoded_key 	= $xmlrpc_object->params['0']->me['string']; 
		$timestamp	= $xmlrpc_object->params['1']->me['int'];

		// Check the timestamp make sure it's recent
		if ($timestamp < (time() - 14400)) { 
			debug_event('XMLSERVER','Handshake failure, timestamp too old','1'); 
			return new XML_RPC_Response(0,'503','Handshake failure, timestamp too old');
		} 
		
		// Log the attempt
		debug_event('XMLSERVER','Login Attempt, IP: ' . $_SERVER['REMOTE_ADDR'] . ' Time: ' . $timestamp . ' Hash:' . $encoded_key,'1'); 

		// Convert the IP Address to an int
		$ip = Dba::escape(inet_pton($_SERVER['REMOTE_ADDR']));

		// Run the query and return the key's for ACLs of type RPC that would match this IP 
		$sql = "SELECT * FROM `access_list` WHERE `type`='rpc' AND `start` <= '$ip' AND `end` >= '$ip'"; 
		$db_results = Dba::query($sql);

		while ($row = Dba::fetch_assoc($db_results)) { 
			
			// Build our encoded passphrase
			$sha256pass = hash('sha256',$timestamp . hash('sha256',$row['key']));
			if ($sha256pass == $encoded_key) { 
				$data['type'] = 'xml-rpc';
				$data['username'] = 'System'; 
				$data['value'] = 'Handshake'; 
				$token = vauth::session_create($data);
				
				return new XML_RPC_Response(XML_RPC_encode($token)); 
			} 

		} // end while rows

		return new XML_RPC_Response(0,'503', 'Handshake failure, Key/IP Incorrect');

	} // handshake

} // xmlRpcServer
?>
