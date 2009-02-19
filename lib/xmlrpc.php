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
 * XML-RPC Library
 * If you want an honest answer NFC. Copy and paste baby!
 * @package XMLRPC
 * @catagory Server
 * @author Karl Vollmer
 * @copyright Ampache.org 2001 - 2006
 */

/**
 * remote_catalog_query
 * this is the initial contact and response to a xmlrpc request from another ampache server
 * this returns a list of catalog names 
 * @package XMLRPC
 * @catagory Server
 */
function remote_catalog_query($m) {
	

	$var = $m->getParam(0);
	$key = $var->scalarval();

	/* Verify the KEY */
	if (!remote_key_verify($key,$_SERVER['REMOTE_ADDR'],'5')) { 
		return new xmlrpcresp(0,'503','Key/IP Mis-match Access Denied');			
	}
	
        $result = array();

        // we only want to send the local entries
        $sql = "SELECT name,COUNT(song.id) FROM catalog " . 
		"LEFT JOIN song ON catalog.id = song.catalog " . 
		"WHERE catalog_type='local' GROUP BY catalog.id";
	$db_results = Dba::read($sql); 

        while ( $i = Dba::fetch_row($db_result) ) {
                $result[] = $i;
        }
	
	set_time_limit(0);
        $encoded_array = php_xmlrpc_encode($result);
	
	debug_event('xmlrpc-server',"Encoded Catalogs: " . count($result),'3');
	
        return new xmlrpcresp($encoded_array);

} // remote_server_query

/**
 * remote_song_query
 * return all local songs and their information it pulls all local catalogs, then all enabled songs
 * then it strings togeather their information sepearted by :: and then base64 encodes it before sending it along
 * @package XMLRPC
 * @catagory Server
 * @todo Add catalog level access control 
 * @todo fix for the smallint(1) change of song.status
 * @todo serialize the information rather than cheat with the :: 
 */
function remote_song_query($params) { 

        $var = $params->getParam(0);
        $key = $var->scalarval();

        /* Verify the KEY */
        if (!remote_key_verify($key,$_SERVER['REMOTE_ADDR'],'5')) {           
                return new xmlrpcresp(0,'503','Key/IP Mis-match Access Denied');
        }

	$start	= $params->params['1']->me['int'];
	$step 	= $params->params['2']->me['int'];
	

	// Get me a list of all local catalogs
	$sql = "SELECT catalog.id FROM catalog WHERE catalog_type='local'";
	$db_results = Dba::read($sql); 

	$results = array();
	
	$sql = "SELECT song.id FROM song WHERE song.enabled='1' AND (";
	
	// Get the catalogs and build the query!
	while ($r = Dba::fetch_object($db_results)) { 
			$sql .= " song.catalog='$r->id' OR";
	} // build query

	$sql = rtrim($sql,"OR");

	$sql .= ") LIMIT $start,$step";
	
	$db_results = Dba::read($sql); 
	
	// Recurse through the songs and build a results
	// array that is base64_encoded
	while ($r = Dba::fetch_object($db_results)) { 

		$song 		= new Song($r->id);
		$song->fill_ext_info(); 
		$song->album 	= $song->get_album_name();
		$song->artist 	= $song->get_artist_name();
		$song->genre	= $song->get_genre_name();

		// Format the output
		$output = '';
		$output = $song->artist . "::" . $song->album . "::" . $song->title . "::" . $song->comment .
			  "::" . $song->year . "::" . $song->bitrate . "::" . $song->rate . "::" . $song->mode .
			  "::" . $song->size . "::" . $song->time . "::" . $song->track . "::" . $song->genre . "::" . $r->id;
		$output = base64_encode($output);
		$results[] = $output; 
	
		// Prevent Timeout
		set_time_limit(0);

	} // while songs

	set_time_limit(0);
	$encoded_array = php_xmlrpc_encode($results);

	debug_event('xmlrpc-server',"Encoded Song Query Results ($start,$step):" . count($results),'3');

	return new xmlrpcresp($encoded_array);

} // remote_song_query

/**
 * remote_session_verify
 * This checks the session on THIS server and returns a true false 
 * The problem with this funcion is that we don't have the key from 
 * the other server... this needs to be fixed potential security flaw
 * Other server still needs read xml-rpc permissions, but no key
 * @package XMLRPC
 * @catagory Server
 */
function remote_session_verify($params) { 

	/* We may need to do this correctly.. :S */
	$var	= $params->getParam(0);
	$sid	= $var->scalarval();

	if (session_exists($sid)) { 
		$data = true;		
	}
	else { 
		$data = false;
	}

	$encoded_data = php_xmlrpc_encode($data);
	
	debug_event('xmlrpc-server',"Encoded Session Verify: $data Recieved: $sid",'3'); 
	
	return new xmlrpcresp($encoded_data);

} // remote_session_verify

/**
 * remote_server_denied
 * Access Denied Sucka!
 * @package XMLRPC
 * @catagory Server
 */
function remote_server_denied() { 

        $result = array();

        $result['access_denied'] = "Access Denied: Sorry, but " . $_SERVER['REMOTE_ADDR'] . " does not have access to " .
				"this server's catalog. Please make sure that you have been added to this server's access list.\n";

        $encoded_array = php_xmlrpc_encode($result);

	debug_event('xmlrpc-server',"Access Denied: " . $_SERVER['REMOTE_ADDR'],'3');
	
        return new xmlrpcresp($encoded_array);

} // remote_server_denied

/**
 * remote_key_verify
 * This does a ACCESS control check against
 * the incomming xml-rpc request. it takes the 
 * passed key and makes sure the IP+KEY+LEVEL 
 * matches in the local ACL
 */
function remote_key_verify($key,$ip,$level) { 

	$access = new Access();
	if ($access->check('xml-rpc',$ip,'',$level,$key)) { 
		return true;
	}

	return false;

} // remote_key_verify


?>
