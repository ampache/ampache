<?php

/*

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

/*

 Interface file for SonicBlue Rio Receiver integration.
 Written by Jon C. Hodgson - ampache@jonq.com
 v0.2 - 2004.08.08
 
 Note: You can test any query with debug info by simply adding &debug (or ?debug, as appropriate) to the request string
 
*/ 
 
 
 /*
 	ToDo:
 	=====
 	(in no particular order)
 	Access Lists
 	Associate private playlists with current unit if hide_private_playlists=1 and we're tracking play counts
 	Note in docs that Select Music...Playlist...Play All Music does not include "Special" Playlists
 	Allow user-specifed playlist order
 	Loosen query format to handle mixed case params (not really a big deal since receiver's requests are hard-coded)
 
 */
 

define('NO_SESSION',1); 

// We MUST use output buffering to get around Chunked Transfer Encoding
ob_start();

header('Content-Type: text/plain');

// This is UBER-IMPORTANT! to disable sessions. Make sure this is set BEFORE including init.php

// Ampache Library
require_once("../../lib/init.php");


//  ===============================================================================================
//  DECLARE VARIABLES & LOAD CONFIG FILE
//  ===============================================================================================
	
	// Defaults;
		$hide_private_playlists=0;
		$filter_from_beginning=1;
		$query_limit=3000;
		$track_receiver_stats=1;
		$group_receivers_together=0;
		$automatically_create_users=1;
		$rio_user['0.0.0.0']='rio';
		$playlist_global_most_popular_songs=0;
		$playlist_user_most_popular_songs=0;
		$playlist_newest_albums=0;
		$playlist_global_most_popular_albums=0;
		$playlist_user_most_popular_albums=0;
		$playlist_global_most_popular_artists=0;
		$playlist_user_most_popular_artists=0;
		$favorites_newest_albums=0;
		$favorites_global_most_popular_albums=0;
		$favorites_user_most_popular_albums=0;
		$favorites_global_most_popular_artists=0;
		$favorites_user_most_popular_artists=0;
		$favorites_dividers=0;
		$playlist_dividers=0;
//		$debug=1;		
	
	// Read in config settings
		$fh = fopen ('rio.conf', 'r') or die('Could not open config file');
		
		while ($line = fgets($fh))
		{		
			// Look for known parameters
			if 	(preg_match("/^\s*hide_private_playlists\s*=\s*([0|1])\s*($|\/\/)/i",$line,$matches))				{$hide_private_playlists=$matches[1]; }
			elseif (preg_match("/^\s*filter_from_beginning\s*=\s*([0|1])\s*($|\/\/)/i",$line,$matches))				{$filter_from_beginning=$matches[1]; }
			elseif (preg_match("/^\s*query_limit\s*=\s*(\d+)\s*($|\/\/)/i",$line,$matches)) 						{$query_limit=$matches[1]; }
			elseif (preg_match("/^\s*track_receiver_stats\s*=\s*([0|1])\s*($|\/\/)/i",$line,$matches))				{$track_receiver_stats=$matches[1]; }
			elseif (preg_match("/^\s*group_receivers_together\s*=\s*([0|1])\s*($|\/\/)/i",$line,$matches))				{$group_receivers_together=$matches[1]; }
			elseif (preg_match("/^\s*automatically_create_users\s*=\s*([0|1])\s*($|\/\/)/i",$line,$matches))			{$automatically_create_users=$matches[1]; }
			elseif (preg_match("/^\s*rio_user_(\d+\.\d+\.\d+\.\d+)\s*=\s*(\S+)\s*($|\/\/)/i",$line,$matches)) 			{$rio_user[$matches[1]]=$matches[2]; }
			elseif (preg_match("/^\s*playlist_global_most_popular_songs\s*=\s*(\d+)\s*($|\/\/)/i",$line,$matches)) 		{$playlist_global_most_popular_songs=$matches[1]; }
			elseif (preg_match("/^\s*playlist_user_most_popular_songs\s*=\s*(\d+)\s*($|\/\/)/i",$line,$matches)) 		{$playlist_user_most_popular_songs=$matches[1]; }
			elseif (preg_match("/^\s*playlist_global_most_popular_albums\s*=\s*(\d+)\s*($|\/\/)/i",$line,$matches))		{$playlist_global_most_popular_albums=$matches[1]; }
			elseif (preg_match("/^\s*playlist_user_most_popular_albums\s*=\s*(\d+)\s*($|\/\/)/i",$line,$matches))		{$playlist_user_most_popular_albums=$matches[1]; }
			elseif (preg_match("/^\s*playlist_global_most_popular_artists\s*=\s*(\d+)\s*($|\/\/)/i",$line,$matches))		{$playlist_global_most_popular_artists=$matches[1]; }
			elseif (preg_match("/^\s*playlist_user_most_popular_artists\s*=\s*(\d+)\s*($|\/\/)/i",$line,$matches)) 		{$playlist_user_most_popular_artists=$matches[1]; }
			elseif (preg_match("/^\s*playlist_newest_albums\s*=\s*(\d+)\s*($|\/\/)/i",$line,$matches)) 				{$playlist_newest_albums=$matches[1]; }
			elseif (preg_match("/^\s*favorites_newest_albums\s*=\s*(\d+)\s*($|\/\/)/i",$line,$matches)) 				{$favorites_newest_albums=$matches[1]; }
			elseif (preg_match("/^\s*favorites_global_most_popular_albums\s*=\s*(\d+)\s*($|\/\/)/i",$line,$matches)) 	{$favorites_global_most_popular_albums=$matches[1]; }
			elseif (preg_match("/^\s*favorites_user_most_popular_albums\s*=\s*(\d+)\s*($|\/\/)/i",$line,$matches)) 		{$favorites_user_most_popular_albums=$matches[1]; }
			elseif (preg_match("/^\s*favorites_global_most_popular_artists\s*=\s*(\d+)\s*($|\/\/)/i",$line,$matches)) 	{$favorites_global_most_popular_artists=$matches[1]; }
			elseif (preg_match("/^\s*favorites_user_most_popular_artists\s*=\s*(\d+)\s*($|\/\/)/i",$line,$matches)) 		{$favorites_user_most_popular_artists=$matches[1]; }
			elseif (preg_match("/^\s*favorites_dividers\s*=\s*([0|1])\s*($|\/\/)/i",$line,$matches))					{$favorites_dividers=$matches[1]; }
			elseif (preg_match("/^\s*playlist_dividers\s*=\s*([0|1])\s*($|\/\/)/i",$line,$matches))					{$playlist_dividers=$matches[1]; }
			
		}
		
		fclose($fh);
		
		// Post validation -- Keep people honest
		// -------------------------------------
		if ($playlist_newest_albums>100)				{$playlist_newest_albums=100;}
		if ($playlist_global_most_popular_albums>100)	{$playlist_global_most_popular_albums=100;}
		if ($playlist_user_most_popular_albums>100)		{$playlist_user_most_popular_albums=100;}
		if ($playlist_global_most_popular_artists>100)	{$playlist_global_most_popular_artists=100;}
		if ($playlist_user_most_popular_artists>100)		{$playlist_user_most_popular_artists=100;}

		

	// Amount to add to a track to protect special keyword 100
	$track_add=101;
	
	// Amount to add to a playlist to make 0=0x10000000
	$playlist_add=268435355;
	
	// Amount to add to a playlist to make 0=0x20000000
	$special_add=536870811;
	
	// How far into the song do you count the track as having being played?
	// This compensates for songs you simply fast forward thru, not tracking them etc.
	// Each multiple of 32768 is ~1 second, but that totally depends on bitrate etc.
	$countTrackBytes=32768*30;	



// ================================================================================================
// Parse URL
// ================================================================================================
// Very strict parse rules are enforced, particularly to ensure that we catch unexpected query
// variances for untested versions of the receiver.arf, expecially community-hacked versions.


		// Host
		$rioIP = $_SERVER['REMOTE_ADDR'];


		// Path
		$path = $_SERVER['SCRIPT_NAME'];
	
	
		// "content" "list" "query" "results" "tags"
		if (preg_match("#^/(content|list|query|results|tags)(/(.*)|$)#",$path,$match)) {
			
			$queryType=$match[1];

			// Validate
			if (preg_match("#^[0-9A-Fa-f]*$#",$match[3])) {
				
				// "content" & "list" require a value
				if (($queryType=="content" || $queryType=="list") && strlen($match[3])==0) {
					exit ("ERROR: Invalid '$queryType' query options: '$match[3]' -- Should have a value, eg. '/$queryType/1d3f'");
				}
				
				// "query" & "results" should have no value
				elseif (($queryType=="query" || $queryType=="results") && strlen($match[2])!=0) {
					exit ("ERROR: Invalid '$queryType' query options: '$match[2]' -- Cannot have a value, must be '/$queryType' ");	
				} 
				
				else {
					$queryOpts=$match[3];
				}
				
			} else {
				exit ("ERROR: Invalid '$queryType' query options: '$match[3]'");	
			}			
		}


		// "favourites"
		elseif (preg_match("#^/favourites(/(.*)|$)#",$path,$match)) {
			$queryType="favourites";
			
			// Validate
			if ($match[2]=="all") {
				$queryOpts=$match[2];
			} else {
				exit ("ERROR: Invalid '$queryType' query options: '$match[2]'");	
			}			
		}


		// ERROR
		else {
			exit ("ERROR: Invalid query: '$match[1]'");
		}





// ================================================================================================
// PARSE REQUEST PARAMETERS
// ================================================================================================

		foreach ($_GET as $var => $val) {
			
			
			// "artist" "source" "genre" "title"
			if (preg_match("/^(artist|source|genre|title)$/",$var,$match)) {
				
				if (isset($queryCategory)) {
					exit ("ERROR: You cannot specify another Query Category '$match[1]', '$queryCategory' already set.");
				}
				
				$queryCategory=$match[1];
				$queryFilter=$val;
			}
			
			
			// "extended"
			elseif (strtolower($var)=="_extended") {
				
				if (preg_match("/^[0-2]$/",$val)) {
					$extended=$val;
				} elseif ($val=="") {
					$extended=0;	
				} else {
					exit ("ERROR: Invalid value '$val' for '$var', must be blank or 0-2.");
				}
				
			}
			
			
			// "shuffle"
			elseif (strtolower($var)=="shuffle") {
				
				if (preg_match("/^[0-1]$/",$val)) {
					$shuffle=$val;
				} elseif ($val=="") {
					$shuffle=0;	
				} else {
					exit ("ERROR: Invalid value '$val' for '$var', must be blank or 0-1.");
				}
				
			}
			
			
			// begin
			elseif (strtolower($var)=="_begin") {
				
				if (preg_match("/^([0-9]*)$/",$val,$match)) {
					if (strlen($match[1])>0) {
						$begin=intval($match[1]);
					}
				} else {
					exit ("ERROR: Invalid value '$val' for '$var', must be blank or an integer >= 0");
				}
				
			}
			
			
			// end
			elseif (strtolower($var)=="_end") {
				
				if (preg_match("/^([0-9]*)$/",$val,$match)) {
					if (strlen($match[1])>0) {
						$end=intval($match[1]);
					}
				} else {
					exit ("ERROR: Invalid value '$val' for '$var', must be blank or an integer >= 0");
				}
				
			}
			

			// Newest Albums
			elseif (strtolower($var)=="shoutcast") {
				
				if (preg_match("#^(\S+)\s*(http://.*)$#",$val,$match)) {
					
					$favouriteType=$var;
					$favourite="$match[2]";
					
				} else {
					
					//Ignore
					//exit ("ERROR: Unexpected parameter '$var' with value '$val' -- please remove from query.");
	
				}
									
			}
			
			
			// Debug
			elseif (strtolower($var)=="debug") {
				$debug=1;
			}


			// Divider
			elseif (strtolower($var)=="divider") {
				
				// Do nothing... no usable response
				print "Divider\n";
				header('Content-Length: '.strlen(ob_get_contents()));
				ob_end_flush();
				exit;
									
			}
			
			
			// ERROR
			else {
				exit ("ERROR: Unexpected parameter '$var' with value '$val' -- please remove from query.");
			}
			
		}





// ================================================================================================
// ADDITIONAL VALIDATION
// ================================================================================================ 

		// begin & end
		if (strlen($begin)>0 && strlen($end)>0) {
		
			if ($begin > $end) {
				exit ("ERROR: _begin '$begin' must be less than _end '$end'");
			} else {
				$range=$end-$begin+1;	
			}
						
		} else {
			
			// Both need to be set
			$begin="";
			$end="";
		
		}
		
		
		// Regex Query
		if (preg_match("/^(\[[0-9A-Za-z]+\])+$/",$queryFilter)) {
			$queryFilterRegex=1;	
		} else {
			$queryFilterRegex=0;
		}


		// extended default
		if ($extended=="") {
			$extended=0;
		}
		
		
		// shuffle default
		if ($shuffle=="") {
			$shuffle=0;
		}


		// ampache usernames for each recevier
		if ($group_receivers_together) {
			$rio_user_IP="0.0.0.0";
		} else {
			$rio_user_IP=$rioIP;
		}
		
		if ($rio_user[$rio_user_IP]=="") {$rio_user[$rio_user_IP]="rio-$rioIP";}

		// this unit's ampache user name
		$ampacheUsername=$rio_user[$rio_user_IP];

		// ampache user id
		if ($track_receiver_stats) {
			
			// v.1.1 Change
			$userID=mysql_fetch_row(mysql_query("SELECT id FROM user WHERE username='$ampacheUsername'"));
			$ampacheUserID=$userID[0];
			//v.1.0 Code
			//$row=mysql_fetch_row(mysql_query("SELECT id FROM user WHERE username='$ampacheUsername'"));
			//$ampacheUserID=$row[0];

		}

// ================================================================================================
// DEBUG
// ================================================================================================
	if ($debug) {
		
		print "DEBUG MODE\n==========\n\n";
		
		$pad=38;
		
		print "Path: $path\n";
		print str_repeat("-",$pad+1)."\n";
		$format="%".$pad."s: %s\n";
		printf($format,"Rio IP",$rioIP);
		printf($format,"Query Type",$queryType);
		printf($format,"Query Options",$queryOpts);
		printf($format,"Query Category",$queryCategory);
		printf($format,"Query Filter",$queryFilter);
		printf($format,"Query Filter Regex",$queryFilterRegex);
		printf($format,"Extended",$extended);
		printf($format,"Shuffle",$shuffle);
		printf($format,"Begin",$begin);
		printf($format,"End",$end);
		printf($format,"Range","$range");
		printf($format,"Favourite Type","$favouriteType");
		printf($format,"Favourite","$favourite");
		
		print "\nConfig File Settings:\n";
		print str_repeat("-",$pad+1)."\n";
		printf($format,"hide_private_playlists",$hide_private_playlists);
		printf($format,"filter_from_beginning",$filter_from_beginning);
		printf($format,"query_limit",$query_limit);
		printf($format,"track_receiver_stats",$track_receiver_stats);
		printf($format,"group_receivers_together",$group_receivers_together);
		printf($format,"automatically_create_users",$automatically_create_users);
		printf($format,"Rio User IP",$rio_user_IP);
		printf($format,"Ampache User ID",$ampacheUserID);
		printf($format,"Ampache Username",$ampacheUsername);
		foreach ($rio_user as $var => $val) {
			printf($format,"rio_user_$var (Mapping)",$val);	
		}
		printf($format,"Print Playlist Dividers",$playlist_dividers);
		printf($format,"Print Favorites Dividers",$favorites_dividers);


	
		/*
		printf($format,"Playlist: Newest Albums",$playlist_newest_albums);
		printf($format,"Playlist: Global Most Popular Songs",$playlist_global_most_popular_songs);
		printf($format,"Playlist: Global Most Popular Albums",$playlist_global_most_popular_albums);
		printf($format,"Playlist: Global Most Popular Artists",$playlist_global_most_popular_artists);
		printf($format,"Playlist: User Most Popular Songs",$playlist_user_most_popular_songs);
		printf($format,"Playlist: User Most Popular Albums",$playlist_user_most_popular_albums);
		printf($format,"Playlist: User Most Popular Artists",$playlist_user_most_popular_artists);
		
		printf($format,"Favorites: Newest Albums",$favorites_newest_albums);
		printf($format,"Favorites: Global Most Popular Albums",$favorites_global_most_popular_albums);
		printf($format,"Favorites: Global Most Popular Artists",$favorites_global_most_popular_artists);
		printf($format,"Favorites: User Most Popular Albums",$favorites_user_most_popular_albums);
		printf($format,"Favorites: User Most Popular Artists",$favorites_user_most_popular_artists);
		*/
				
		print "\nVariable/Value Pairs\n";
		print str_repeat("-",$pad+1)."\n";
		foreach ($_GET as $var => $val) {
			printf($format,$var,$val);	
		}
		print "\n\n";
	}





// ================================================================================================
// PROCESS STATIC REQUESTS (ones that do not require standard DB access)
// ================================================================================================

	
	// Ampache user integration -- creating users & updating last_seen etc.
	// --------------------------------------------------------------------
	if ($queryType=="tags" && $queryOpts=="") {
		// Since this is only run at startup, we use this section to auto-create the username if unspecified.
		if ($track_receiver_stats) {
			
			if ($automatically_create_users && !$debug) {
			
				// Create a new user if it doesn't exist
				if ($ampacheUserID=="") {
				
					if ($debug) { print "\nCreating new ampache user '$ampacheUsername'... ";}
					
						$user = new User();
				
						if (!$new_user = $user->create($ampacheUsername,"Rio Receiver - $rio_user_IP", "" , mt_rand(10000000,99999999) , "5")) {
							if ($debug) { print "FAILED\n\n";}
							debug_event('RioPlayer',"Creation of new User failed",1);
						} else {
						    // Below is needed to set the account to disabled to prevent GUI logins..
						    $user = new User($new_user);
						    $validation = str_rand(20);
						    $user->update_validation($validation);
							if ($debug) { print "SUCCEEDED\n\n";}
							debug_event('RioPlayer',"Creation of new User SUCCEEDED",1);
						}

				}
			}
		}
	}
	// Setup Ampache User Class
	$user = new User($ampacheUserID);	
	
	// Update last_seen except for content calls for tracks... (too expensive, we'll do that again later in content for just the first request)
	if ($track_receiver_stats && !($queryType=="content" && rio_isTrack($queryOpts))) {
		$user->update_last_seen();
		$user->insert_ip_history();
//		mysql_query("UPDATE user SET last_seen='" . time() . "' WHERE username='$ampacheUsername'");
	}





	if ($queryType=="tags" && $queryOpts=="") {
	
		$static=1;
				
		// Print list of known tags, these map to the KEYs in the tag format of 0-15
		
		                         // Key   Description			Ampache table.field
		                         // ---   -----------------------   --------------------------------------------------------------------------------
		print "fid\n";			//   0   File ID (Decimal)		song.id converted to FID-decimal or playlist.id
		print "title\n";         //   1   Title					song.title or playlist.name                                                    
		print "artist\n";        //   2   Artist				song.artist                                                   
		print "source\n";        //   3   Album 				song.album                                                    
		print "year\n";          //   4   Year   				song.year                                                     
		print "comment\n";       //   5   ID3 Comment			song.comment                                                  
		print "length\n";        //   6   Filesize (bytes)  		song.size (mp3s) or #_of_tracks*4 (playlists) or #_of_playlist*4 (/tags/100) <- I dunno why                                                  
		print "type\n";          //   7   Sourcetype ("tune") 		"tune" (mp3s) or "playlist" (playlist) <-- dunno what else this could be                                                   
		print "path\n";          //   8   File Path 				song.file                                                     
		print "genre\n";         //   9   Genre 				song.genre                                                    
		print "bitrate\n";       //  10   Bitrate				song.mode vbr=vs cbr=fs & song.bitrate/1024(or 1000?) eg. fs128 vs 193
		print "playlist\n";      //  11   Playlist name			playlist.name ?                                               
		print "codec\n";         //  12   CODEC (MP3 or WMA)		"mp3" <-- base on extension in song.file?
		print "offset\n";        //  13   Data Offset               ??? <-- It doesn't seem to be needed
		print "duration\n";      //  14   Duration (milliseconds) 	song.time * 1000                                              
		print "tracknr\n";		//  15   Track Number		 	song.track
		
	}
	
	
	elseif ($queryType=="tags" && $queryOpts==100) {
		
		$static=1;
		
		// Special semi-static Query for All Tracks Playlist
		
		// Get playlist counts	
		if ($hide_private_playlists) {
			
			$sql2="SELECT COUNT(*) FROM playlist WHERE playlist.type='public'";
		
		} else {
		
			$sql2="SELECT COUNT(*) FROM playlist";
		}
		
		
		// Process query 2 & populate variables
		$row = mysql_fetch_row(mysql_query($sql2));
		
		$length=$row[0]*4;	// Don't ask me why, it just is.
	
		// LET'S OUTPUT SOME DWORDS!
		
		print rio_tagdata(0,"256");
		print rio_tagdata(1,"All Tracks");
		print rio_tagdata(7,"playlist");
		print rio_tagdata(6,$length);
		print pack("C",255); // EOF	
	
	} elseif ($queryType=="favourites") {
		
		// Handle staticly for now
		$static=1;
		
		// Don't put an entry > 99 or the unit will freak out
		
		//This special entry prevents the rest of the list from being displayed
		//print "99:Whachatalkingaboutwillis\n";
		
		// If you pad the URL with 36 characters you can hide the URL from the UI
		//print "0:artist=\n";
		//print "1:fave&artist=sting\n";
		//print "5:shoutcast=label                               http://sc2.magnatune.com:8008/listen.pls\n";
		//print "6:http://sc2.magnatune.com:8008/listen.pls=shoutcast2\n";


		// FAVORITES
		// ---------
		
		$index=0;
		
		if ($favorites_newest_albums) {
			
			if ($favorites_dividers) {
				print "$index:divider=----".$favorites_newest_albums." Newest Albums----\n";
				$index++;
			}
			
			$sql="SELECT DISTINCT album.name FROM song,album WHERE song.album=album.id ORDER BY song.addition_time DESC LIMIT $favorites_newest_albums";
			$db_results = mysql_query($sql);
			
			while (($row=mysql_fetch_row($db_results)) && $index < 100) {
				
				print "$index:source=$row[0]\n";					
				$index++;
			}	
			
		}
		
		if ($favorites_global_most_popular_albums) {
			
			if ($favorites_dividers) {
				print "$index:divider=---".$favorites_global_most_popular_albums." Fav Albums (G)----\n";
				$index++;
			}
			
			$sql="SELECT a.name,COUNT(object_count.id) AS `count` FROM object_count, album as a ".
				"WHERE object_type='album' AND a.id=object_count.object_id ".
				"GROUP BY object_id ORDER BY `count` DESC LIMIT $favorites_global_most_popular_albums";
			debug_event('RioPlayer',"FAV Albums (G):\n".$sql,1); 
			$db_results = mysql_query($sql);
			
			while (($row=mysql_fetch_row($db_results)) && $index < 100) {
				
				print "$index:source=$row[0]\n";					
				$index++;
			}

		}
		
		if ($favorites_user_most_popular_albums) {
			
			if ($favorites_dividers) {
				print "$index:divider=---".$favorites_user_most_popular_albums." Fav Albums (U)----\n";
				$index++;
			}
			
			$sql="SELECT album.name FROM object_count LEFT JOIN album ON object_count.object_id=album.id WHERE object_count.object_type='album' AND object_count.userid='$ampacheUserID' ORDER BY object_count.count DESC LIMIT $favorites_user_most_popular_albums";
			$db_results = mysql_query($sql);
			
			while (($row=mysql_fetch_row($db_results)) && $index < 100) {
				
				print "$index:source=$row[0]\n";					
				$index++;
			}

		}
		
		if ($favorites_global_most_popular_artists) {
			
			if ($favorites_dividers) {
				print "$index:divider=---".$favorites_global_most_popular_artists." Fav Artists (G)----\n";
				$index++;
			}
			
//			$sql="SELECT artist.name,sum(object_count.count) AS total_count FROM object_count LEFT JOIN artist ON object_count.object_id=artist.id WHERE object_count.object_type='artist' GROUP BY object_count.object_id ORDER BY total_count DESC LIMIT $favorites_global_most_popular_artists";
			$sql="SELECT a.name,COUNT(object_count.id) AS `count` FROM object_count, artist as a ".
				"WHERE object_type='artist' AND a.id=object_count.object_id ".
				"GROUP BY object_id ORDER BY `count` DESC LIMIT $favorites_global_most_popular_artists";
			debug_event('RioPlayer',"FAV Artist (G):\n".$sql,1); 
			$db_results = mysql_query($sql);
			
			while (($row=mysql_fetch_row($db_results)) && $index < 100) {
				
				print "$index:artist=$row[0]\n";					
				$index++;	
			}

		}
		
		if ($favorites_user_most_popular_artists) {
			
			if ($favorites_dividers) {
				print "$index:divider=---".$favorites_user_most_popular_artists." Fav Artists (U)----\n";
				$index++;
			}
			
			$sql="SELECT artist.name FROM object_count LEFT JOIN artist ON object_count.object_id=artist.id WHERE object_count.object_type='artist' AND object_count.userid='$ampacheUserID' ORDER BY object_count.count DESC LIMIT $favorites_user_most_popular_artists";
			$db_results = mysql_query($sql);
			
			while (($row=mysql_fetch_row($db_results)) && $index < 100) {
				
				print "$index:artist=$row[0]\n";					
				$index++;
			}
	
		}
		
		// Print a footer if there were previous entries
		if ($favorites_dividers && $index>0) {
			print "$index:divider=-----------------------\n";
			$index++;
		}
		


	} elseif ($queryType=="tags" && rio_isSpecial($queryOpts)) {
		
		// Special Playlists
		$static=1;
		
		$special=rio_fid2special($queryOpts);
		
		if ($special==1) {$title="Popular Songs (Global)";}
		elseif ($special==2) {$title="Popular Songs (User)";}
		elseif ($special>=100 && $special<200) {$num=$special-99;$title="Newest Album $num";}
		elseif ($special>=200 && $special<300) {$num=$special-199;$title="Popular Albums $num (Global)";}
		elseif ($special>=300 && $special<400) {$num=$special-299;$title="Popular Albums $num (User)"	;}
		elseif ($special>=400 && $special<500) {$num=$special-399;$title="Popular Artists $num (Global)";}
		elseif ($special>=500 && $special<600) {$num=$special-499;$title="Popular Artists $num (User)";}
		else {$title="";} // Including special=0 (dividers)
		
		
		
		$length=0;	// This doesn't seem to be needed either (should be # of entries *4)
	
		// LET'S OUTPUT SOME DWORDS!			
		print rio_tagdata(0,hexdec($queryOpts));
		print rio_tagdata(1,$title);
		print rio_tagdata(7,"playlist");
		print rio_tagdata(6,$length);
		print pack("C",255); // EOF
			
	} elseif ($queryType=="content" && $queryOpts==20000000) {
		
		// Divider -- Do nothing
		$static=1;
	
		//print rio_track2fid($row[0])."=T$row[1]\n";
	
	}


	if ($static) {
		// Send the Content-Length header to disable Chunked Transfer Encoding
		header('Content-Length: '.strlen(ob_get_contents()));
		
		// This is where the page actually gets written
		ob_end_flush();
		
		exit;
	}





// ================================================================================================
// BUILD SQL QUERY
// ================================================================================================

	// query
	if ($queryType=="query") {
		
		// This ORDER BY only affects the display in the UI. This does not affect play order!
		
		if ($queryCategory=='title') {
			$select="title";
			$from="song";
			$orderby="RAND()"; // Since we have to limit this query anyway, might as well randomize it
		} elseif ($queryCategory=='artist') {
			$select="name";
			$from="artist";
			$orderby=$select;
		} elseif ($queryCategory=='source') {
			$select="name";
			$from="album";
			$orderby=$select;
		} elseif ($queryCategory=='genre') {
			$select="name";
			$from="genre";
			$orderby=$select;
		}
		
		$where=$select;
		

	} elseif ($queryType=="results") {
		
		if ($favouriteType=="shoutcast") {
			
			//testing
			$queryCategory="artist";
			$queryFilter="Sting";
			//exit;
		}
		
		
		// This ORDER BY sorting does not affect UI list, but does affects playback order
		
		$select="song.id,song.title,song.size";
		$from="song";
						
		if ($queryCategory=="title") {	// Verifed query results as complete
			
			$where="song.title";
			$orderby="RAND()"; // Since we limit the list anyway
			//$orderby="song.artist,song.album,song.track,song.file";
			
		} elseif ($queryCategory=="artist") {	// Verifed query results as complete
			
			$where="artist.name";
			$leftjoin="artist ON song.artist=artist.id LEFT JOIN album ON song.album=album.id";
			$orderby="artist.name,album.name,song.track,song.file";
		
		} elseif ($queryCategory=="source") {	// Verifed query results as complete
			
			$where="album.name";
			$leftjoin="album ON song.album=album.id";
			$orderby="album.name,song.track,song.file";
			
		} elseif ($queryCategory=="genre") {	// Verifed query results as complete
			
			$where="genre.name";
			$leftjoin="genre ON song.genre=genre.id LEFT JOIN artist ON song.artist=artist.id LEFT JOIN album ON song.album=album.id";
			$orderby="genre.name,artist.name,album.name,song.track,song.file";	
			
		}
		
	} elseif ($queryType=="tags") {		
		
		if (rio_isTrack($queryOpts)) {
	
			// Oh my, this is gonna be fun...

			$select="song.title,artist.name,album.name,song.year,song_ext_data.comment,song.size,song.file,song.genre,song.bitrate,song.mode,song.time,song.track";
			$from="song,song_ext_data";
			$leftjoin="artist ON song.artist=artist.id LEFT JOIN album on song.album=album.id";
			$where="song.id";
			$queryFilter=rio_fid2track($queryOpts);  // actual Track ID
			$range=1;
			
		} elseif (rio_isPlaylist($queryOpts)) {
			// /tags/<PlaylistID>
			// ------------------
			// Select Music...Playlist...<PlaylistID>
			// Returns extended info redarding that playlist
			// /content/<playlistID> is also called at this time
			
			$select="name";
			$from="playlist";
			$where="id";
			$queryFilter=rio_fid2playlist($queryOpts);
			$range=1;
			
		}
			
	} elseif ($queryType=="content") {
		
		if ($queryOpts==100) {
			// Select Music...Playlist
			// Returns a list of all playlists
		
			$select="id,name";
			$from="playlist";
			
			// Hide private playlists if param is set
			if ($hide_private_playlists) {
				$where="type";
				$queryFilter="public";
			 }
			 
		} elseif (rio_isTrack($queryOpts)) {
			
			$select="file";
			$from="song";
			$where="id";
			$queryFilter=rio_fid2track($queryOpts);  // actual Track ID
			$range=1;
		
		} elseif (rio_isPlaylist($queryOpts)) {
			
			// content/<PlaylistID>?_extended=1
			// --------------------------------
			// Select Music...Playlist...<PlaylistID>
			// Returns a list of tracks for the specific playlist
			// /tags/<playlistID> is also called at this time
			
			$select="song.id,song.title";
			$from="song";
			$leftjoin="playlist_data ON song.id=playlist_data.song";
			$where="playlist_data.playlist";
			$queryFilter=rio_fid2playlist($queryOpts);  // actual Playlist ID
			$orderby="playlist_data.track";
			
		} elseif (rio_isSpecial($queryOpts)) {
			
			// Special Playlists
		
			$special=rio_fid2special($queryOpts);
			
			// Global Most Popular Songs
			if ($special==1) {
				$sql="SELECT song.id,song.title,sum(object_count.count) AS total_count FROM object_count LEFT JOIN song ON object_count.object_id=song.id WHERE object_count.object_type='song' GROUP BY object_count.object_id ORDER BY total_count DESC LIMIT $playlist_global_most_popular_songs";
			}
			
			// User Most Popular Songs
			elseif ($special==2) {
				$sql="SELECT song.id,song.title FROM object_count LEFT JOIN song ON object_count.object_id=song.id LEFT JOIN user ON object_count.userid=user.id WHERE object_count.object_type='song' AND user.username='$ampacheUsername' ORDER BY object_count.count DESC LIMIT $playlist_user_most_popular_songs";
			}
			
			// Newest Albums
			elseif ($special >= 100 && $special < 200) {
				
				$num=$special-100;
				
				// Get album id
				$sql="SELECT DISTINCT album.id FROM song,album WHERE song.album=album.id ORDER BY song.addition_time DESC LIMIT $num,1";
				$row= mysql_fetch_row(mysql_query($sql));
				
				$sql="SELECT song.id,song.title FROM song LEFT JOIN album ON song.album=album.id WHERE album.id='$row[0]' ORDER BY song.track,song.file";
				
			}
				
			// Global Most Popular Albums
			elseif ($special >= 200 && $special < 300) {
				
				$num=$special-200;
		
				// Get album id
				$sql="SELECT album.id,sum(object_count.count) AS total_count FROM object_count LEFT JOIN album ON object_count.object_id=album.id WHERE object_count.object_type='album' GROUP BY object_count.object_id ORDER BY total_count DESC LIMIT $num,1";
				$row= mysql_fetch_row(mysql_query($sql));
				
				$sql="SELECT song.id,song.title FROM song LEFT JOIN album ON song.album=album.id WHERE album.id='$row[0]' ORDER BY song.track,song.file";
			
			}	
				
			// User Most Popular Albums
			elseif ($special >= 300 && $special < 400) {	
				
				$num=$special-300;
				
				// Get album id
				$sql="SELECT album.id FROM object_count LEFT JOIN album ON object_count.object_id=album.id WHERE object_count.object_type='album' AND object_count.userid='$ampacheUserID' ORDER BY object_count.count DESC LIMIT $num,1";
				$row= mysql_fetch_row(mysql_query($sql));
				
				$sql="SELECT song.id,song.title FROM song LEFT JOIN album ON song.album=album.id WHERE album.id='$row[0]' ORDER BY song.track,song.file";
			
			}	
				
			// Global Most Popular Artists
			elseif ($special >= 400 && $special < 500) {
				
				$num=$special-400;
				
				// Get artist id
				$sql="SELECT artist.id,sum(object_count.count) AS total_count FROM object_count LEFT JOIN artist ON object_count.object_id=artist.id WHERE object_count.object_type='artist' GROUP BY object_count.object_id ORDER BY total_count DESC LIMIT $num,1";
				$row= mysql_fetch_row(mysql_query($sql));
			
				$sql="SELECT song.id,song.title FROM song LEFT JOIN artist ON song.artist=artist.id LEFT JOIN album ON song.album=album.id WHERE artist.id='$row[0]' ORDER BY album.name,song.track,song.file";
			
			}	
				
			// User Most Popular Artists
			elseif ($special >= 500 && $special < 600) {	
				
				$num=$special-500;
				
				// Get artist id
				$sql="SELECT artist.id FROM object_count LEFT JOIN artist ON object_count.object_id=artist.id WHERE object_count.object_type='artist' AND object_count.userid='$ampacheUserID' ORDER BY object_count.count DESC LIMIT $favorites_user_most_popular_artists";
			
				$row= mysql_fetch_row(mysql_query($sql));
				$sql="SELECT song.id,song.title FROM song LEFT JOIN artist ON song.artist=artist.id LEFT JOIN album ON song.album=album.id WHERE artist.id='$row[0]' ORDER BY album.name,song.track,song.file";
				
			} else {
				exit("ERROR: Unexpected Special Query '$special'\n");	
			}
			
				
		}
	
	} elseif ($queryType=="list") {
			
		$select="song.id,song.size";
			
		if ($queryOpts==100) {
			// /list/100?shuffle=<0|1>&_extended=2
			// -----------------------------------
			// Selct Music...Playlist...Play All Music
			// Returns a list of all songs in all playlist
			
			if ($hide_private_playlists) {
				
				$from="song,playlist_data,playlist";
				$where="song.id";
				$queryFilter="playlist_data.song AND playlist.id=playlist_data.playlist AND playlist.type='public'";
					
			} else {
				
				$from="song,playlist_data";
				$where="song.id";
				$queryFilter="playlist_data.song";
			}
		
			$orderby="playlist_data.playlist,playlist_data.track";

		} elseif (rio_isTrack($queryOpts)) {
	
			$from="song";
			$where="id";
			$queryFilter=rio_fid2track($queryOpts);  // actual Track ID
		
		} elseif (rio_isPlaylist($queryOpts)) {
			// /list/<PlaylistID>?shuffle=<0|1>&_extended=2
			// -----------------------------------
			// Selct Music...Playlist...<PlaylistID>...Play All Music
			// Returns a list of songs in the playlist
			
			$from="song";
			$leftjoin="playlist_data ON song.id=playlist_data.song";
			$where="playlist_data.playlist";
			$queryFilter=rio_fid2playlist($queryOpts);  // actual Playlist ID
			$orderby="playlist_data.track";

		} elseif (rio_isSpecial($queryOpts)) {
			
			// Special Playlists
			
			$special=rio_fid2special($queryOpts);
			
			// Global Most Popular Songs
			if ($special==1) {
				
				$sql="SELECT song.id,song.size,sum(object_count.count) AS total_count FROM object_count LEFT JOIN song ON object_count.object_id=song.id WHERE object_count.object_type='song' GROUP BY object_count.object_id";
				if ($shuffle) {
					$sql.=" ORDER BY RAND() ";
				} else {
					$sql.=" ORDER BY total_count "; 
				}
				$sql.="DESC LIMIT $playlist_global_most_popular_songs";
			}
			
			// User Most Popular Songs
			elseif ($special==2) {
				
				$sql="SELECT song.id,song.size FROM object_count LEFT JOIN song ON object_count.object_id=song.id LEFT JOIN user ON object_count.userid=user.id WHERE object_count.object_type='song' AND user.username='$ampacheUsername'";
				if ($shuffle) {
					$sql.=" ORDER BY RAND() ";
				} else {
					$sql.=" ORDER BY object_count.count "; 
				}
				$sql.="DESC LIMIT $playlist_user_most_popular_songs";	
			}
			
			// Newest Albums
			elseif ($special >= 100 && $special < 200) {
				
				$num=$special-100;
				
				// Get album id
				$sql="SELECT DISTINCT album.id FROM song,album WHERE song.album=album.id ORDER BY song.addition_time DESC LIMIT $num,1";
				$row= mysql_fetch_row(mysql_query($sql));
				
				$sql="SELECT song.id,song.size FROM song LEFT JOIN album on song.album=album.id WHERE album.id='$row[0]'";
				if ($shuffle) {
					$sql.=" ORDER BY RAND() ";
				} else {
					$sql.=" ORDER BY song.track,song.file "; 
				}
			
			}
			
			// Global Most Popular Albums
			elseif ($special >= 200 && $special < 300) {
				
				$num=$special-200;
		
				// Get album id
				$sql="SELECT album.id,sum(object_count.count) AS total_count FROM object_count LEFT JOIN album ON object_count.object_id=album.id WHERE object_count.object_type='album' GROUP BY object_count.object_id ORDER BY total_count DESC LIMIT $num,1";
				$row= mysql_fetch_row(mysql_query($sql));
				
				$sql="SELECT song.id,song.size FROM song LEFT JOIN album on song.album=album.id WHERE album.id='$row[0]'";
				if ($shuffle) {
					$sql.=" ORDER BY RAND() ";
				} else {
					$sql.=" ORDER BY song.track,song.file "; 
				}
			
			}	
				
			// Global Most Popular Albums
			elseif ($special >= 300 && $special < 400) {	
				
				$num=$special-300;
				
				$sql="SELECT album.id FROM object_count LEFT JOIN album ON object_count.object_id=album.id WHERE object_count.object_type='album' AND object_count.userid='$ampacheUserID' ORDER BY object_count.count DESC LIMIT $num,1";
				$row= mysql_fetch_row(mysql_query($sql));
				
				$sql="SELECT song.id,song.size FROM song LEFT JOIN album on song.album=album.id WHERE album.id='$row[0]'";
				if ($shuffle) {
					$sql.=" ORDER BY RAND() ";
				} else {
					$sql.=" ORDER BY song.track,song.file "; 
				}
			
			}	
				
			// Global Most Popular Artists
			elseif ($special >= 400 && $special < 500) {
				
				$num=$special-400;
				
				// Get artist id
				$sql="SELECT artist.id,sum(object_count.count) AS total_count FROM object_count LEFT JOIN artist ON object_count.object_id=artist.id WHERE object_count.object_type='artist' GROUP BY object_count.object_id ORDER BY total_count DESC LIMIT $num,1";
				$row= mysql_fetch_row(mysql_query($sql));
			
				$sql="SELECT song.id,song.size FROM song LEFT JOIN artist ON song.artist=artist.id LEFT JOIN album ON song.album=album.id WHERE artist.id='$row[0]'";
				if ($shuffle) {
					$sql.=" ORDER BY RAND() ";
				} else {
					$sql.=" ORDER BY album.name,song.track,song.file "; 
				}
			
			}	
				
			// User Most Popular Artists
			elseif ($special >= 500 && $special < 600) {	
				
				$num=$special-500;
				
				// Get artist id
				$sql="SELECT artist.id FROM object_count LEFT JOIN artist ON object_count.object_id=artist.id WHERE object_count.object_type='artist' AND object_count.userid='$ampacheUserID' ORDER BY object_count.count DESC LIMIT $favorites_user_most_popular_artists";
			
				$row= mysql_fetch_row(mysql_query($sql));
				$sql="SELECT song.id,song.size FROM song LEFT JOIN artist ON song.artist=artist.id LEFT JOIN album ON song.album=album.id WHERE artist.id='$row[0]'";
				if ($shuffle) {
					$sql.=" ORDER BY RAND() ";
				} else {
					$sql.=" ORDER BY album.name,song.track,song.file "; 
				}
				
			} else {
				exit("ERROR: Unexpected Special Query '$special'\n");	
			}
			
		}
	
	} 
	
	
	
	
	
// ================================================================================================	
// BUILD FINAL SQL QUERY
// ================================================================================================

	if($sql=="") {
		
		// SELECT
		$sql="SELECT $select FROM $from";

		
		// LEFT JOIN
		if ($leftjoin) {
			$sql.=" LEFT JOIN $leftjoin";	
		}
		
		
		// WHERE
		if ($queryFilter!="") {
			
			$sql.=" WHERE $where";
			
			if ($queryFilterRegex) {
				// Must compensate for non alphanumeric characters (basically only matching on alpha numeric chars)
				// [1abc][8tuv][3def][6mno] should match "-Av%e    Mar?ia-"
				// We do this by prepending each pattern set with '[^0-9a-z]*' (MySQL REGEXP queries are case insensitive) 
				$queryFilter=preg_replace("/(\[[0-9A-Za-z]+\])/","[^0-9a-z]*$1",$queryFilter);
				
				$sql.=" REGEXP '";
				if($filter_from_beginning){$sql.="^";}
				$sql.="$queryFilter'";
			} else {
				
				if ($queryType=="query") {
					$queryFilter=mysql_escape_string($queryFilter);
					$sql.=" LIKE '";
					if($filter_from_beginning){$sql.="%";}
					$sql.="$queryFilter%'";
				} elseif ($queryType=="list") {
					$sql.="=$queryFilter";
				} else {
					$queryFilter=mysql_escape_string($queryFilter);
					$sql.="='$queryFilter'";
				}
			}
		}
	
		
		// ORDER BY
		if ($shuffle) {
			$sql.=" ORDER BY RAND()";
		} elseif ($orderby) {
			$sql.=" ORDER BY $orderby";
		}
	
		
		// LIMIT
		
		if ($range=="" || $range > $query_limit) {
			
			// Selective filtering alternative for potentially large queries - I commented it out to limit ALL queries
			//($queryType=="query"   &&   ($queryCategory=="title" && $range=="")       || $range > $query_limit)  ||
			//($queryType=="results" &&  (($queryCategory=="title" || $queryFilter=="") || $range > $query_limit)) 
		  	//) {
				
			// Handle catalog length issue
			// The reciver can hang or reboot on large queries. Use this setting to specify a Query Limit
			// eg. query_limit=3000 to limit large queries to 3000 entries. This is the default.
			// The breaking point varies possibly depending on specific data returned and usage conditions (that use cache etc.?)
			// If your rio hangs or reboots on some queries, try lowering this number.
			
			// This parameter is set in rio.conf
			
			if ($debug) {print "NOTE: Query limited to $query_limit to prevent hanging of unit.\n\n";}
			$range=$query_limit;	
		}
		
		if ($begin && $range) {
			$sql.=" LIMIT $begin,$range";	
		} elseif ($range) {
			$sql.=" LIMIT $range";
		}

		// We shouldn't be here if there's no SQL query
		if ($select==""){exit("ERROR: Unexpected Query - Missing SELECT statement\n");}

	}
	
		// EXECUTE SQL QUERY	
		if ($debug) {print "SQL STATEMENT:\n".str_repeat("-",strlen($sql))."\n$sql\n";}
		
		$db_results = mysql_query($sql);
				
		if ($debug) {print "\n\nQUERY RESPONSE:\n===============\n";}
		//debug_event('RioPlayer',"Final SQL Query:\n" . $sql,1);
//		$user->update_stats($num);







// ================================================================================================
// PROCESS QUERY RESULTS
// ================================================================================================

	// Set begin counter if no specified
	if($begin==""){$begin=0;}
	
	$firstRun=1;

	while($row=mysql_fetch_row($db_results)) {
	
		// query
		if ($queryType=="query") {
			
			// select="title" or "name" (depending on query)
			
			if ($firstRun) {print "matches=\n";}
			
			// NOTE: The first digit ("1") of the returned output (1,0,0) indicates a count for that record.
  			// In the interest of query speed, i've omitted this code completely.
			print "$begin=1,0,0:$row[0]\n";
			$begin++;
			
		} elseif ($queryType=="results") {
			
			// select="song.id,song.title,song.size"
			
			if ($extended=="1") {
	
				print rio_track2fid($row[0])."=T$row[1]\n";
		
			} elseif ($extended=="2") {
				
				print rio_track2pfid($row[0]).pack("L2",$row[2],$mpeg_data_offset);
				
			} else {
				
				// The rio never seems to query this, format gotten by querying original server	
				print rio_track2pfid($row[0]).pack("L1",$row[2]);
			
			}
			
			
		} elseif ($queryType=="tags") {		
			
			if (rio_isTrack($queryOpts)) {
		
				// select="song.title,artist.name,album.name,song.year,song.comment,song.size,song.file,song.genre,song.bitrate,song.mode,song.time,song.track"
				
				// Oh my, this is gonna be fun...
			
				$title=$row[0];
				$artist=$row[1];
				$source=$row[2];
				$year=$row[3];
				$comment=$row[4];
				$length=$row[5];
				$path=$row[6];
				$genre=$row[7];
				
				// Mode
				if ($row[9]=="vbr") {$mode="vs";}
				elseif ($row[9]=="cbr") {$mode="fs";}
				else {$mode="xx";}
				
				$bitrate=$mode.round($row[8]/1000);
				$duration=$row[10]*1000;
				$tracknr=$row[11];
				
				// Codec
				if (preg_match("/\.mp3$/i",$path)) {$codec="mp3";}
				elseif (preg_match("/\.wma$/i",$path)) {$codec="wma";}
				else {$codec="xxx";}
				
				
				// LET'S OUTPUT SOME DWORDS!
				
				print rio_tagdata(0,hexdec($queryOpts));
				print rio_tagdata(1,$title);
				print rio_tagdata(2,$artist);
				print rio_tagdata(3,$source);
				print rio_tagdata(4,$year);
				print rio_tagdata(5,$comment);
				print rio_tagdata(6,$length);
				print rio_tagdata(7,"tune");
				print rio_tagdata(8,$path);
				print rio_tagdata(9,$genre);
				print rio_tagdata(10,$bitrate);
				//print rio_tagdata(11,$);$playlist	// <--- I still don't know what this is for
				print rio_tagdata(12,$codec);
				//print rio_tagdata(13,$);$offset		// <--- Not needed
				print rio_tagdata(14,$duration);
				print rio_tagdata(15,$tracknr);
				print pack("C",255); // EOF
				
			} elseif (rio_isPlaylist($queryOpts)) {
				
				// select="name"
				
				// Process query & populate variables
			
				$title=$row[0];
				
				$sql2="SELECT COUNT(*) FROM playlist_data WHERE playlist='$playlist'";
				
				// Process query 2 & populate variables
				$row = mysql_fetch_row(mysql_query($sql2));
				
				$length=$row[0]*4;	// Don't ask me why, it just is.
			
				// LET'S OUTPUT SOME DWORDS!
				
				print rio_tagdata(0,hexdec($queryOpts));
				print rio_tagdata(1,$title);
				print rio_tagdata(7,"playlist");
				print rio_tagdata(6,$length);
				print pack("C",255); // EOF	
				
			}
				
		} elseif ($queryType=="content") {
			
			if ($queryOpts==100) {
			
				// Add "Special" Playlists to the list
				if ($firstRun) {
				
					$line=0;
				
					// Special Playlists
					
					// FID 1
					if ($playlist_global_most_popular_songs) {
						
						print rio_special2fid(1)."=PPopular Songs (Global)\n";
						$line++;
					}
					
					// FID 2
					if ($playlist_user_most_popular_songs && $track_receiver_stats) {
						print rio_special2fid(2)."=PPopular Songs (User)\n";
						$line++;
					}
					
					// FIDs 100 thru 199
					if ($playlist_newest_albums) {
					
						if ($playlist_dividers && $line) {print "20000000=P------------------------------\n";}
					
						$sql="SELECT DISTINCT album.name FROM song,album WHERE song.album=album.id ORDER BY song.addition_time DESC LIMIT $playlist_newest_albums";

						
						$index=100;
						
						$db_results2 = mysql_query($sql);
						
						while ($row2=mysql_fetch_row($db_results2)) {
								
							print rio_special2fid($index)."=Pnew-$row2[0]\n";	
							$index++;
							$line++;
						}
					}
					
					// FIDs 200 thru 299
					if ($playlist_global_most_popular_albums) {
					
						if ($playlist_dividers && $line) {print "20000000=P------------------------------\n";}
					
						$sql="SELECT album.name,sum(object_count.count) AS total_count FROM object_count LEFT JOIN album ON object_count.object_id=album.id WHERE object_count.object_type='album' GROUP BY object_count.object_id ORDER BY total_count DESC LIMIT $favorites_global_most_popular_albums";
		
						$index=200;
						
						$db_results2 = mysql_query($sql);
						
						while ($row2=mysql_fetch_row($db_results2)) {
								
							print rio_special2fid($index)."=PgAl-$row2[0]\n";	
							$index++;
							$line++;
						}
					}
					
					// FIDs 300 thru 399
					if ($playlist_user_most_popular_albums) {
					
						if ($playlist_dividers && $line) {print "20000000=P------------------------------\n";}
					
						$sql="SELECT album.name FROM object_count LEFT JOIN album ON object_count.object_id=album.id WHERE object_count.object_type='album' AND object_count.userid='$ampacheUserID' ORDER BY object_count.count DESC LIMIT $favorites_user_most_popular_albums";
			
						$index=300;
						
						$db_results2 = mysql_query($sql);
						
						while ($row2=mysql_fetch_row($db_results2)) {
								
							print rio_special2fid($index)."=PuAl-$row2[0]\n";	
							$index++;
							$line++;
						}
					}
					
					// FIDs 400 thru 499
					if ($playlist_global_most_popular_artists) {
					
						if ($playlist_dividers && $line) {print "20000000=P------------------------------\n";}
					
						$sql="SELECT artist.name,sum(object_count.count) AS total_count FROM object_count LEFT JOIN artist ON object_count.object_id=artist.id WHERE object_count.object_type='artist' GROUP BY object_count.object_id ORDER BY total_count DESC LIMIT $favorites_global_most_popular_artists";
			
						$index=400;
						
						$db_results2 = mysql_query($sql);
						
						while ($row2=mysql_fetch_row($db_results2)) {
								
							print rio_special2fid($index)."=PgAr-$row2[0]\n";	
							$index++;
							$line++;
						}
					}
					
					// FIDs 500 thru 599
					if ($playlist_user_most_popular_artists) {
						
						if ($playlist_dividers && $line) {print "20000000=P------------------------------\n";}
						
						$sql="SELECT artist.name FROM object_count LEFT JOIN artist ON object_count.object_id=artist.id WHERE object_count.object_type='artist' AND object_count.userid='$ampacheUserID' ORDER BY object_count.count DESC LIMIT $favorites_user_most_popular_artists";
			
						$index=500;
						
						$db_results2 = mysql_query($sql);
						
						while ($row2=mysql_fetch_row($db_results2)) {
								
							print rio_special2fid($index)."=PuAr-$row2[0]\n";	
							$index++;
							$line++;
						}
					}
					
					if ($playlist_dividers && $line) {print "20000000=P------------------------------\n";}
					
				}
				
				
				// select="id,name"
				print rio_playlist2fid($row[0])."=P$row[1]\n";
				
			} elseif (rio_isTrack($queryOpts)) {
		
				// select="file"
				
				$file=$row[0];
					
				// open the file in binary mode
				$fp = fopen($file, 'rb');
				
				$filesize=filesize($file);
				$length=$filesize;
				
				// Handle range headers a-b only right now	
				if (preg_match("/^bytes=(\d+)-(\d*)$/",$_SERVER['HTTP_RANGE'],$matches)) {
				
					$isRange=1;
						
					$start=$matches[1];
					$end=$matches[2];
					
					// Stupid Receiver requests data beyond EOF & apache drops the connection and the receiver HANGS...
					if ($end < $filesize) {
						$length=$end-$start+1;
					} else {	
						$length=$filesize-$start;
						$end=$filesize;
					}
					
				}
				
				
				// Update ampache last_seen and favorites only after a certain number of bytes,
				// To compensate for fast forwarding to a specific song
				
				if ($start >= $countTrackBytes && $start < $countTrackBytes+32768) {

					//START 1.1 MODIFICATION
					// Modified for compatibility with Ampache v.3.2.1					

					if ($ampacheUserID!="") {
				
						// last seen
						mysql_query("UPDATE user SET last_seen='" . time() . "' WHERE id='$userID[0]'");
					
						//user stats
						
							//update_user_stats($ampacheUserID, $queryFilter);
						
							// Ampache went to user classes so i have to do this...
							$user = new User($ampacheUsername,$userID[0]);
							$user->update_stats($queryFilter);
						
					// END 1.1 MODIFICATION
					
					}
					
				}				

						
				// open the file in binary mode
				$fh = fopen($file, 'rb');
				
				// seek to the offset if Ranged
				if ($isRange) {fseek($fh,$start);}
				
				// read in the data
				$contents = fread($fh,$length);
				
				// close file
				fclose($fh);
		
				// Make sure the output buffer is empty
				ob_clean();		
				
				// Set Headers
				header("Content-Type: audio/mpeg");
				header("Content-Length: ".$length);
				if ($isRange) {header("Content-Range: bytes ".$start."-"."$end");}
		
				// print data block
				print $contents;
				
				// Flush the output buffer
				ob_end_flush();
				
				// End
				exit;
				
							
			} elseif (rio_isPlaylist($queryOpts) || rio_isSpecial($queryOpts)) {
				
				// select="song.id,song.title"
				print rio_track2fid($row[0])."=T$row[1]\n";
					
			}
			
		} elseif ($queryType=="list") {
			
			// select="song.id,song.size"
			print rio_track2pfid($row[0]).pack("L2",$row[1],$mpeg_data_offset);
							
		}	

		// Flag to indicate that a loop has already iterated once
		$firstRun=0;
		
	} // end while		





// Send the Content-Length header to disable Chunked Transfer Encoding
header('Content-Length: '.strlen(ob_get_contents()));

// This is where the page actually gets written
ob_end_flush();





// ================================================================================================
// FUNCTIONS
// ================================================================================================
// These conversion functions compensate for the fact that rio FIDs could be either a track or 
// playlist based on their range, as well as deal with the special '100' FID. It offsets all Track 
// IDs by 101 and offsets all playlist IDs by 268,435,355, chosen simply so that you could tell a 
// playlist by looking at it's hex value. There are also "Special" FIDs, which handle new functionality
// list dynamic playlists.


// A FID can be up to      0xFFFFFFFF
//                    ID   0xFID
// Track               0 = 0x00000065
// Track     268,435,253 = 0x0FFFFF9a
// Playlist            0 = 0x10000000
// Playlist  268,435,354 = 0x1FFFFF9a
// Special             0 = 0x20000000
// Special 3,758,096,383 = 0xFFFFFFFF





// Track Functions
// ---------------

function rio_track2pfid ($track) {
	global $track_add;
	return pack("L",$track+$track_add);
}

function rio_pfid2track ($pfid) {
	global $track_add;	
	$array=unpack("L*",$pfid);
	return $array[1]-$track_add;
}

function rio_track2fid ($track) {
	global $track_add;	
	return dechex($track+$track_add);
}

function rio_fid2track ($fid) {
	global $track_add;	
	return hexdec($fid)-$track_add;	
}





// Playlist Functions
// ------------------

function rio_playlist2pfid ($playlist) {
	global $playlist_add;
	return rio_track2pfid($playlist+$playlist_add);
}

function rio_pfid2playlist ($pfid) {
	global $playlist_add;
	return rio_pfid2track($pfid)-$playlist_add;
}

function rio_playlist2fid ($playlist) {
	global $playlist_add;	
	return rio_track2fid($playlist+$playlist_add);
}

function rio_fid2playlist ($fid) {
	global $playlist_add;
	return rio_fid2track($fid)-$playlist_add;	
}





// Special Functions
// -----------------

function rio_special2pfid ($special) {
	global $special_add;
	return rio_track2pfid($special+$special_add);
}

function rio_pfid2special ($pfid) {
	global $special_add;
	return rio_pfid2track($pfid)-$special_add;
}

function rio_special2fid ($special) {
	global $special_add;	
	return rio_track2fid($special+$special_add);
}

function rio_fid2special ($fid) {
	global $special_add;
	return rio_fid2track($fid)-$special_add;	
}





// FID Identification Functions
// ----------------------------

function rio_isTrack($fid) {
	global $playlist_add;
	return hexdec($fid) < $playlist_add;	
}

function rio_isPlaylist($fid) {
	global $playlist_add;
	global $special_add;
	return (hexdec($fid) >= $playlist_add && hexdec($fid) < $special_add);	
}

function rio_isSpecial($fid) {
	global $special_add;
	return hexdec($fid) >= $special_add;	
}





// encode tag data
// ---------------

function rio_tagdata($key,$data) {

	$data=trim($data);

	if ($data=="") {
		
		return;
		
	} else {

		return pack("CCa*",$key,strlen($data),$data);
		
	}
}





?>
