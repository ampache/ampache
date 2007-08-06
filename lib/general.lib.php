<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

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
	@header General Library
	This is the general library that contains misc functions
	that doesn't have a home elsewhere
*/

/*!
	@function ip2int
	@discussion turns a dotted quad ip into an
		int
*/
function ip2int($ip) { 

        $a=explode(".",$ip);
	return $a[0]*256*256*256+$a[1]*256*256+$a[2]*256+$a[3];

} // ip2int

/*!
	@function int2ip
	@discussion turns a int into a dotted quad
*/
function int2ip($i) { 
        $d[0]=(int)($i/256/256/256);
        $d[1]=(int)(($i-$d[0]*256*256*256)/256/256);
        $d[2]=(int)(($i-$d[0]*256*256*256-$d[1]*256*256)/256);
        $d[3]=$i-$d[0]*256*256*256-$d[1]*256*256-$d[2]*256;
	return "$d[0].$d[1].$d[2].$d[3]";
} // int2ip

/*
 * Conf function by Robert Hopson
 * call it with a $parm name to retrieve
 * a var, pass it a array to set them
 * to reset a var pass the array plus
 * Clobber! replaces global $conf;
*/
/*function conf($param,$clobber=0)
{
        static $params = array();

        if(is_array($param))
        //meaning we are setting values
        {
                foreach ($param as $key=>$val)
                {
                        if(!$clobber && isset($params[$key]))
                        {
                                echo "Error: attempting to clobber $key = $val\n";
                                exit();
                        }
                        $params[$key] = $val;
                }
                return true;
        }
        else
        //meaning we are trying to retrieve a parameter
        {
                if($params[$param]) return $params[$param];
                else return;
        }
} //conf

function error_results($param,$clobber=0)
{               
        static $params = array();
        
        if(is_array($param))
        //meaning we are setting values
        {
                foreach ($param as $key=>$val)
                {       
                        if(!$clobber && isset($params[$key]))
                        {
                                echo "Error: attempting to clobber $key = $val\n";
                                exit(); 
                        }
                        $params[$key] = $val;
                }
                return true;
        }               
        else            
        //meaning we are trying to retrieve a parameter
        {
                if($params[$param]) return $params[$param];
                else return;
        }
} //error_results
*/

/**
 * session_exists
 * checks to make sure they've specified a valid session, can handle xmlrpc
 */
function session_exists($sid,$xml_rpc=0) { 

	$found = true;

	$sql = "SELECT * FROM `session` WHERE `id` = '$sid'";
	$db_results = Dba::query($sql);

	if (!Dba::num_rows($db_results)) { 
		$found = false;
	}

	/* If we need to check the remote session */
	if ($xml_rpc) { 
		$server = rawurldecode($_GET['xml_server']);
		$path	= "/" . rawurldecode($_GET['xml_path']) . "/server/xmlrpc.server.php";
		$port	= $_GET['xml_port'];

		$path = str_replace("//","/",$path);
		
		/* Create the XMLRPC client */
		$client = new xmlrpc_client($path,$server,$port);

		/* Encode the SID of the incomming client */
		$encoded_sid 		= new xmlrpcval($sid,"string");

		$query = new xmlrpcmsg('remote_session_verify',array($encoded_sid) );

		/* Log this event */	
		debug_event('xmlrpc-client',"Checking for Valid Remote Session:$sid",'3'); 

		$response = $client->send($query,30);

		$value = $response->value();

		if (!$response->faultCode()) { 
			$data = php_xmlrpc_decode($value);
			$found = $data;
		}
		
	} // xml_rpc

	return $found;

} // session_exists

/**
 * extend_session
 * just updates the expire time of the specified session this 
 * is used by the the play script after a song finishes
 */
function extend_session($sid) { 

	$new_time = time() + Config::get('session_length');

	if ($_COOKIE['amp_longsess'] == '1') { $new_time = time() + 86400*364; }

	$sql = "UPDATE `session` SET `expire`='$new_time' WHERE `id`='$sid'";
	$db_results = Dba::query($sql);

} // extend_session

/**
 * get_tag_type
 * This takes the result set, and the the tag_order
 * As defined by your config file and trys to figure out
 * which tag type it should use, if your tag_order
 * doesn't match anything then it just takes the first one
 * it finds in the results. 
 */
function get_tag_type($results) {

	/* Pull In the config option */
	$order = Config::get('tag_order');

        if (!is_array($order)) {
		$order = array($order);
        }

	/* Foreach through the defined key order
	 * the first one we find is the first one we use 
	 */
        foreach($order as $key) {
                if ($results[$key]) {
			$returned_key = $key;
                	break;
        	}
	}

	/* If we didn't find anything then default it to the
	 * first in the results set
	 */
	if (!isset($returned_key)) { 
		$keys = array_keys($results);
		$returned_key = $keys['0'];
	}

	return $returned_key;

} // get_tag_type


/**
 * clean_tag_info
 * This function takes the array from vainfo along with the 
 * key we've decided on and the filename and returns it in a 
 * sanatized format that ampache can actually use
 */
function clean_tag_info($results,$key,$filename) { 

	$info = array();

	$clean_array = array("\n","\t","\r","\0");
	$wipe_array  = array("","","","");

	$info['file']		= $filename;
	$info['title']        	= stripslashes(trim($results[$key]['title']));
	$info['year']         	= intval($results[$key]['year']);
	$info['track']		= intval($results[$key]['track']);
	$info['comment']      	= Dba::escape(str_replace($clean_array,$wipe_array,$results[$key]['comment']));

	/* This are pulled from the info array */
	$info['bitrate']      	= intval($results['info']['bitrate']);
	$info['rate']         	= intval($results['info']['sample_rate']);
	$info['mode']         	= $results['info']['bitrate_mode'];
	$info['size']         	= $results['info']['filesize']; 
	$info['mime']		= $results['info']['mime'];
	$into['encoding']	= $results['info']['encoding'];
	$info['time']         	= intval($results['info']['playing_time']);
	$info['channels']	= intval($results['info']['channels']);

        /* These are used to generate the correct ID's later */
        $info['artist'] 	= trim($results[$key]['artist']);
	$info['album']  	= trim($results[$key]['album']);
        $info['genre']  	= trim($results[$key]['genre']);

	return $info;

} // clean_tag_info

/*!
	@function scrub_in()
	@discussion Run on inputs, stuff that might get stuck in our db
*/
function scrub_in($str) {
 
        if (!is_array($str)) {
                return stripslashes( htmlspecialchars( strip_tags($str) ) );
        }
        else {
                $ret = array();
                foreach($str as $string) $ret[] = scrub_in($string);
                return $ret;
        }
} // scrub_in

/*!
	@function set_memory_limit
	@discussion this function attempts to change the
		php memory limit using init_set but it will 
		never reduce it
*/
function set_memory_limit($new_limit) { 

	/* Check their PHP Vars to make sure we're cool here */
	// Up the memory
	$current_memory = ini_get('memory_limit');
	$current_memory = substr($current_memory,0,strlen($current_memory)-1);
	if ($current_memory < $new_limit) { 
	        $php_memory = $new_limit . "M"; 
	        ini_set (memory_limit, "$php_memory");
	        unset($php_memory);
	}

} // set_memory_limit

/*!
	@function get_random_songs
	@discussion Returns a random set of songs/albums or artists
		matchlist is an array of the WHERE mojo and options
		defines special unplayed,album,artist,limit info
*/
function get_random_songs( $options, $matchlist) {

        $dbh = dbh();
	
        /* Define the options */
        $limit          = intval($options['limit']);

        /* If they've passed -1 as limit then don't get everything */
        if ($options['limit'] == "-1") { unset($options['limit']); }
	elseif ($options['random_type'] == 'length') { /* Rien a faire */ } 
        else { $limit_sql = "LIMIT " . $limit; }


        $where = "1=1 ";
        if(is_array($matchlist))
            foreach ($matchlist as $type => $value) {
                        if (is_array($value)) {
                                foreach ($value as $v) {
                                        $v = sql_escape($v);
                                        if ($v != $value[0]) { $where .= " OR $type='$v' "; }
                                        else { $where .= " AND ( $type='$v'"; }
                                }
                                $where .= " ) ";
                        }
                        elseif (strlen($value)) {
                                $value = sql_escape($value);
                                $where .= " AND $type='$value' ";
                        }
            }



        if ($options['random_type'] == 'full_album') {
                $query = "SELECT album.id FROM song,album WHERE song.album=album.id AND $where GROUP BY song.album ORDER BY RAND() " . $limit_sql;
                $db_results = mysql_query($query, $dbh);
                while ($data = mysql_fetch_row($db_results)) {
                        $albums_where .= " OR song.album=" . $data[0];
                }
                $albums_where = ltrim($albums_where," OR");
                $query = "SELECT song.id,song.size,song.time FROM song WHERE $albums_where ORDER BY song.album,song.track ASC";
        }
        elseif ($options['random_type'] == 'full_artist') {
                $query = "SELECT artist.id FROM song,artist WHERE song.artist=artist.id AND $where GROUP BY song.artist ORDER BY RAND() " . $limit_sql;
                $db_results = mysql_query($query, $dbh);
                while ($data = mysql_fetch_row($db_results)) {
                        $artists_where .= " OR song.artist=" . $data[0];
                }
                $artists_where = ltrim($artists_where," OR");
                $query = "SELECT song.id,song.size,song.time FROM song WHERE $artists_where ORDER BY RAND()";
        }
/* TEMP DISABLE */
//        elseif ($options['random_type'] == 'unplayed') {
//                $uid = $GLOBALS['user']->id;
//                $query = "SELECT song.id,song.size FROM song LEFT JOIN object_count ON song.id = object_count.object_id " .
//                         "WHERE ($where) AND ((object_count.object_type='song' AND user = '$uid') OR object_count.count IS NULL ) " .
//                         "ORDER BY CASE WHEN object_count.count IS NULL THEN RAND() WHEN object_count.count > 4 THEN RAND()*RAND()*object_count.count " .
//                         "ELSE RAND()*object_count.count END " . $limit_sql;
//        } // If unplayed
        else {
                $query = "SELECT id,size,time FROM song WHERE $where ORDER BY RAND() " . $limit_sql;
        }
        
	$db_result = mysql_query($query, $dbh);

        $songs = array();

        while ( $r = mysql_fetch_assoc($db_result) ) {
		/* If they've specified a filesize limit */
		if ($options['size_limit']) { 
			/* Turn it into MB */
			$new_size = ($r['size'] / 1024) / 1024;

			/* If we would go over the allowed size skip to the next song */
			if (($total + $new_size) > $options['size_limit']) { continue; }
			
			$total = $total + $new_size;
			$songs[] = $r['id']; 

			/* If we are within 4mb then that's good enough for Vollmer work */
			if (($options['size_limit'] - floor($total)) < 4) { return $songs; }

		} // end if we are defining a size limit

		/* If they've specified a length */
		if ($options['random_type'] == 'length') { 
			/* Turn the length into min's */
			$new_time = floor($r['time'] / 60); 

			if ($fuzzy_count > 10) { return $songs; } 

			/* If the new one would go over skip to the next song with a limit */
			if (($total + $new_time) > $options['limit']) { $fuzzy_count++; continue; } 

			$total = $total + $new_time; 
			$songs[] = $r['id'];	

			if (($options['limit'] - $total) < 2) { return $songs; } 

		} // if length

		/* If we aren't using a limit */
		else { 
	                $songs[] = $r['id'];
		}
        } // while we fetch results

        return $songs;

} // get_random_songs

/**
 *	cleanup_and_exit
 *	used specificly for the play/index.php file
 *		this functions nukes now playing and then exits
 * 	@package Streaming
 * 	@catagory Clean
 */
function cleanup_and_exit($playing_id) { 

	/* Clear now playing */
	// 900 = 15 min
	$expire = time() - 900;
	$sql = "DELETE FROM now_playing WHERE now_playing.id='$lastid' OR now_playing.start_time < $expire";

	$db_results = @mysql_query($sql, dbh());

	exit();

} // cleanup_and_exit

/** 
 * 	get_global_popular
 *	this function gets the current globally popular items
 * 	from the object_count table, depending on type passed
 * 	@package Web Interface
 * 	@catagory Get
 */
function get_global_popular($type) {

	$stats = new Stats();
	$count = Config::get('popular_threshold');
        $web_path = Config::get('web_path');

	/* Pull the top */
	$results = $stats->get_top($count,$type);

	foreach ($results as $r) { 
		/* If Songs */
                if ( $type == 'song' ) {
                        $song = new Song($r['object_id']);
			$song->format();
                        $text = "$song->f_artist_full - $song->title";
                        /* Add to array */
                        $song->link = "<a href=\"$web_path/stream.php?action=single_song&amp;song_id=$song->id\" title=\"". scrub_out($text) ."\">" .
	                           	scrub_out(truncate_with_ellipsis($text, Config::get('ellipsis_threshold_title')+3)) . "&nbsp;(" . $r['count'] . ")</a>";
			$items[] = $song;
                } // if it's a song
                
		/* If Artist */
                elseif ( $type == 'artist' ) {
                        $artist = new Artist($r['object_id']);
			$artist->format();
                        $artist->link = "<a href=\"$web_path/artists.php?action=show&amp;artist=" . $r['object_id'] . "\" title=\"". scrub_out($artist->full_name) ."\">" .
                        	           truncate_with_ellipsis($artist->full_name, Config::get('ellipsis_threshold_artist')+3) . "&nbsp;(" . $r['count'] . ")</a>";
			$items[] = $artist;
                } // if type isn't artist

		/* If Album */
                elseif ( $type == 'album' ) {
                        $album   = new Album($r['object_id']);
			$album->format(); 
                        $album->link = "<a href=\"$web_path/albums.php?action=show&amp;album=" . $r['object_id'] . "\" title=\"". scrub_out($album->name) ."\">" . 
                        	           scrub_out(truncate_with_ellipsis($album->name,Config::get('ellipsis_threshold_album')+3)) . "&nbsp;(" . $r['count'] . ")</a>";
			$items[] = $album;
                } // else not album

		elseif ($type == 'genre') { 
			$genre 	 = new Genre($r['object_id']);
			$genre->format(); 
			$genre->link = "<a href=\"$web_path/browse.php?action=genre&amp;genre=" . $r['object_id'] . "\" title=\"" . scrub_out($genre->name) . "\">" .
					scrub_out(truncate_with_ellipsis($genre->name,Config::get('ellipsis_threshold_title')+3)) . "&nbsp;(" . $r['count'] . ")</a>";
			$items[] = $genre;
		} // end if genre
        } // end foreach
       
/*	if (count($items) == 0) { 
		$itemis[''] = "<li style=\"list-style-type: none\"><span class=\"error\">" . _('Not Enough Data') . "</span></li>\n";
	}
 */
        return $items;

} // get_global_popular

/** 
 * show_info_box
 * This shows the basic box that popular and newest stuff goes into
 */
function show_info_box ($title, $type, $items) {

        $web_path = Config::get('web_path');
        $popular_threshold = Config::get('popular_threshold');
	require Config::get('prefix') . '/templates/show_box.inc.php';

} // show_info_box

/*!
	@function get_file_extension
	@discussion returns all characters after the last "." in $filename
	Should I be using pathinfo() instead?
*/
function get_file_extension( $filename ) {
	$file_name_parts = explode( ".", $filename );
	$num_parts = count( $file_name_parts );
	if( $num_parts <= 1 ) {
		return;
	} else {
		return $file_name_parts[$num_parts - 1];
	}
} // get_file_extension

/**
 * generate_password
 * This generates a random password, of the specified
 * length
 */
function generate_password($length) { 

    $vowels = 'aAeEuUyY12345';
    $consonants = 'bBdDgGhHjJmMnNpPqQrRsStTvVwWxXzZ6789';
    $password = '';
    
    $alt = time() % 2;

    for ($i = 0; $i < $length; $i++) {
        if ($alt == 1) {
            $password .= $consonants[(rand() % 39)];
            $alt = 0;
        } else {
            $password .= $vowels[(rand() % 17)];
            $alt = 1;
        }
    }

    return $password;
	
} // generate_password

/**
 * scrub_out
 * This function is used to escape user data that is getting redisplayed
 * onto the page, it htmlentities the mojo
 */
function scrub_out($str) {

	if (get_magic_quotes_gpc()) { 
		$str = stripslashes($str);
	}

        $str = htmlentities($str,ENT_QUOTES,Config::get('site_charset'));

        return $str;

} // scrub_out

/**
 * revert_string
 * This returns a scrubed string to it's most normal state
 * Uhh yea better way to do this please? 
 */
function revert_string($string) { 

	$string = unhtmlentities($string,ENT_QUOTES,conf('site_charset'));
	return $string;

} // revert_string

/**
 * make_bool
 * This takes a value and returns what I consider to be the correct boolean value
 * This is used instead of settype alone because settype considers 0 and "false" to 
 * be true
 * @package General
 */
function make_bool($string) { 

	if (strcasecmp($string,'false') == 0) { 
		return '0';
	}

	if ($string == '0') { 
		return '0';
	}

	if (strlen($string) < 1) { 
		return '0';
	}
	
	return settype($string,"bool");

} // make_bool

/**
 * get_languages
 * This function does a dir of ./locale and pulls the names of the
 * different languages installed, this means that all you have to do
 * is drop one in and it will show up on the context menu. It returns
 * in the form of an array of names 
 */
function get_languages() { 

	/* Open the locale directory */
	$handle	= @opendir(Config::get('prefix') . '/locale');

	if (!is_resource($handle)) { 
		debug_event('language','Error unable to open locale directory','1'); 
	}

	$results = array(); 

	/* Prepend English */
	$results['en_US'] = _('English');

	while ($file = readdir($handle)) { 

		$full_file = Config::get('prefix') . '/locale/' . $file;

		/* Check to see if it's a directory */
		if (is_dir($full_file) AND substr($file,0,1) != '.' AND $file != 'base') { 
				
			switch($file) { 
				case 'de_DE'; $name = 'Deutsch'; break;
				case 'en_US'; $name = _('English'); break;
				case 'en_GB'; $name = _('British English'); break;
				case 'es_ES'; $name = 'Espa&ntilde;ol'; break;
				case 'fr_FR'; $name = 'Fran&ccedil;ais'; break;
				case 'it_IT'; $name = 'Italiano'; break;
				case 'is_IS'; $name = '&Iacute;slenska'; break;
				case 'nl_NL'; $name = 'Nederlands'; break;
				case 'tr_TR'; $name = _('Turkish'); break;
				case 'zh_CN'; $name = _('Simplified Chinese') . " (&#x7b80;&#x4f53;&#x4e2d;&#x6587;)"; break;
				case 'ru_RU'; $name = 'Russian (&#x0420;&#x0443;&#x0441;&#x0441;&#x043a;&#x0438;&#x0439;)'; break;
				default: $name = _('Unknown'); break;
			} // end switch

		
			$results[$file] = $name;
		}

	} // end while

	return $results;

} // get_languages

/**
 * logout
 * This is the function that is called to log a user out! 
 */
function logout() { 

	/* First destory their session */
	vauth_logout(session_id());

	/* Redirect them to the login page */
	header ('Location: ' . Config::get('web_path') . '/login.php');
	
	return true;

} // logout

/**
 * format_time
 * This formats seconds into minutes:seconds
 */

function format_time($seconds) {

return sprintf ("%d:%02d", $seconds/60, $seconds % 60);

} //format_time

/**
 * translate_pattern_code
 * This just contains a key'd array which it checks against to give you the 'tag' name
 * that said pattern code corrasponds to, it returns false if nothing is found
 */
function translate_pattern_code($code) { 

	$code_array = array('%A'=>'album',
			'%a'=>'artist',
			'%c'=>'comment',
			'%g'=>'genre',
			'%T'=>'track',
			'%t'=>'title',
			'%y'=>'year',
			'%o'=>'zz_other');
	
	if (isset($code_array[$code])) { 
		return $code_array[$code];
	}
	

	return false;
	
} // translate_pattern_code


/**
 * print_boolean
 * This function takes a boolean value and then print out  a friendly
 * text message, usefull if you have a 0/1 that you need to turn into 
 * a "Off" "On"
 */
function print_boolean($value) { 


	if ($value) { 
		$string = '<span class="item_on">' . _('On') . '</span>'; 
	} 
	else { 
		$string = '<span class="item_off">' . _('Off') . '</span>';
	}	

	return $string;

} // print_boolean

/**
 * invert_boolean
 * This returns the opposite of what you've got
 */
function invert_boolean($value) { 
	
	if (make_bool($value)) { 
		return '0';
	}
	else { 
		return '1';
	}

} // invert_boolean

/**
 * unhtmlentities
 * This is required to make thing work.. but holycrap is it ugly
 */
function unhtmlentities ($string)  {

        $trans_tbl = get_html_translation_table (HTML_ENTITIES);
        $trans_tbl = array_flip ($trans_tbl);
        $ret = strtr ($string, $trans_tbl);
        return preg_replace('/&#(\d+);/me', "chr('\\1')",$ret);

} // unhtmlentities

/**
 * __autoload
 * This function automatically loads any missing
 * classes as they are called so that we don't have to have
 * a million include statements, and load more then we need
 */
function __autoload($class) {
	// Lowercase the class
        $class = strtolower($class);

	$file = Config::get('prefix') . "/lib/class/$class.class.php";

	// See if it exists
        if (is_readable($file)) {
                require_once $file;
                if (is_callable($class . '::_auto_init')) {
                        call_user_func(array($class, '_auto_init'));
                }               
        }
	// Else log this as a fatal error
        else {
                debug_event('__autoload', "'$class' not found!",'1');
        }

} // __autoload

?>
