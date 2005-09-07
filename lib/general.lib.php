<?php
/*
	@header General Library
	This is the general library that contains misc functions
	that doesn't have a home elsewhere
*/

/*!
	@function sql_escape
	@discussion this takes a sql statement
	and properly escapes it before a query is run 
	against it. 
*/
function sql_escape($sql,$dbh=0) {

	if (!is_resource($dbh)) { 
		$dbh = dbh();
	}

	if (function_exists('mysql_real_escape_string')) {
		$sql = mysql_real_escape_string($sql,$dbh);
	}
	else {
		$sql = mysql_escape_string($sql);
	}

	return $sql;

} // sql_escape

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

/*!
	@function show_template
	@discussion show a template from the /templates directory, automaticly appends .inc
		to the passed filename
	@param 	$template	Name of Template
*/
function show_template($template) {

	/* Check for a 'Theme' template */
	if (is_readable(conf('prefix') . conf('theme_path') . "/templates/$template".".inc")) { 
		require (conf('prefix') . conf('theme_path') . "/templates/$template".".inc");
	}
	else {
	        require (conf('prefix') . "/templates/$template".".inc");
	}

} // show_template


/*!
	@function read_config
	@discussion reads the config file for ampache
*/
function read_config($config_file, $debug=0, $test=0) {

    $fp = @fopen($config_file,'r');
    if(!is_resource($fp)) return false;
    $file_data = fread($fp,filesize($config_file));
    fclose($fp);

    // explode the var by \n's
    $data = explode("\n",$file_data);
    if($debug) echo "<pre>";

    $count = 0;

    foreach($data as $value) {
        $count++;

        $value = trim($value);

        if (preg_match("/^\[([A-Za-z]+)\]$/",$value,$matches)) {
                // If we have previous data put it into $results...
                if (isset($config_name) && isset(${$config_name}) && count(${$config_name})) {
                                $results[$config_name] = ${$config_name};
                }

                $config_name = $matches[1];

        } // if it is a [section] name


        elseif (isset($config_name)) {

                // if it's not a comment
                if (preg_match("/^([\w\d]+)\s+=\s+[\"]{1}(.*?)[\"]{1}$/",$value,$matches)
                        || preg_match("/^([\w\d]+)\s+=\s+[\']{1}(.*?)[\']{1}$/", $value, $matches)
                        || preg_match("/^([\w\d]+)\s+=\s+[\'\"]{0}(.*)[\'\"]{0}$/",$value,$matches)) {

                    if (isset(${$config_name}[$matches[1]]) && is_array(${$config_name}[$matches[1]]) && isset($matches[2]) ) {
                        if($debug) echo "Adding value <strong>$matches[2]</strong> to existing key <strong>$matches[1]</strong>\n";
                        array_push(${$config_name}[$matches[1]], $matches[2]);
                    }

                    elseif (isset(${$config_name}[$matches[1]]) && isset($matches[2]) ) {
                        if($debug) echo "Adding value <strong>$matches[2]</strong> to existing key $matches[1]</strong>\n";
                        ${$config_name}[$matches[1]] = array(${$config_name}[$matches[1]],$matches[2]);
                    }

                    elseif ($matches[2] !== "") {
                        if($debug) echo "Adding value <strong>$matches[2]</strong> for key <strong>$matches[1]</strong>\n";
                        ${$config_name}[$matches[1]] = $matches[2];
                    }

                    // if there is something there and it's not a comment
                    elseif ($value{0} !== "#" AND strlen(trim($value)) > 0 AND !$test AND strlen($matches[2]) > 0) {
                        echo "Error Invalid Config Entry --> Line:$count"; return false;
                    } // elseif it's not a comment and there is something there

                    else {
                        if($debug) echo "Key <strong>$matches[1]</strong> defined, but no value set\n";
                    }
                } // end if it's not a comment

        } // elseif no config_name


        elseif (preg_match("/^([\w\d]+)\s+=\s+[\"]{1}(.*?)[\"]{1}$/",$value,$matches)
                        || preg_match("/^([\w\d]+)\s+=\s+[\']{1}(.*?)[\']{1}$/", $value, $matches)
                        || preg_match("/^([\w\d]+)\s+=\s+[\'\"]{0}(.*)[\'\"]{0}$/",$value,$matches)) {


                if (is_array($results[$matches[1]]) && isset($matches[2]) ) {
                        if($debug) echo "Adding value <strong>$matches[2]</strong> to existing key <strong>$matches[1]</strong>\n";
                        array_push($results[$matches[1]], $matches[2]);
                }

                elseif (isset($results[$matches[1]]) && isset($matches[2]) ) {
                        if($debug) echo "Adding value <strong>$matches[2]</strong> to existing key $matches[1]</strong>\n";
                        $results[$matches[1]] = array($results[$matches[1]],$matches[2]);
                }

                elseif ($matches[2] !== "") {
                        if($debug) echo "Adding value <strong>$matches[2]</strong> for key <strong>$matches[1]</strong>\n";
                        $results[$matches[1]] = $matches[2];
                }

                // if there is something there and it's not a comment
                elseif ($value{0} !== "#" AND strlen(trim($value)) > 0 AND !$test AND strlen($matches[2]) > 0) {
                        echo "Error Invalid Config Entry --> Line:$count"; return false;
                } // elseif it's not a comment and there is something there

                else {
                        if($debug) echo "Key <strong>$matches[1]</strong> defined, but no value set\n";
                }

        } // end else

    } // foreach

    if (isset($config_name) && isset(${$config_name}) && count(${$config_name})) {
        $results[$config_name] = ${$config_name};
    }

    if($debug) echo "</pre>";

    return $results;


} // read_config

/*
 * Conf function by Robert Hopson
 * call it with a $parm name to retrieve
 * a var, pass it a array to set them
 * to reset a var pass the array plus
 * Clobber! replaces global $conf;
*/
function conf($param,$clobber=0)
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

function libglue_param($param,$clobber=0)
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
                if(isset($params[$param])) return $params[$param];
                else return false;
        }
}

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


/*!
	@function dbh
	@discussion retrieves the DBH 
*/
function dbh() { return check_sess_db('local'); }

/*!
	@function fix_preferences
	@discussion cleans up the preferences
*/
function fix_preferences($results) { 

	foreach ($results as $key=>$data) { 
		if (strcasecmp($data, "yes") == "0") { $data = 1; }
		if (strcasecmp($data,"true") == "0") { $data = 1; }
		if (strcasecmp($data,"enabled") == "0") { $data = 1; }
		if (strcasecmp($data,"disabled") == "0") { $data = 0; }
		if (strcasecmp($data,"false") == "0") { $data = 0; }
		if (strcasecmp($data,"no") == "0") { $data = 0; }
		$results[$key] = $data;
	}

	return $results;

} // fix_preferences

/**
 * session_exists
 * checks to make sure they've specified a valid session, can handle xmlrpc
 * @package General
 * @cataogry Verify
 * @todo Have XMLRPC check extend remote session 
 * @todo actually check
 */
function session_exists($sid,$xml_rpc=0) { 

	$found = true;

	$sql = "SELECT * FROM session WHERE id = '$sid'";
	$db_results = mysql_query($sql, dbh());

	if (!mysql_num_rows($db_results)) { 
		$found = false;
	}

	/* If we need to check the remote session */
	if ($xml_rpc) { 
		$server = rawurldecode($_GET['xml_server']);
		$path	= "/" . rawurldecode($_GET['xml_path']) . "/server.php";
		$port	= $_GET['xml_port'];

		$path = str_replace("//","/",$path);
		
		$client = new xmlrpc_client($path,$server,$port);

		$query = new xmlrpcmsg('remote_session_verify',array(new xmlrpcval($sid,"string")) );
	
		if (conf('debug')) { log_event($_SESSION['userdata']['username'],' xmlrpc-client ',"Checking for Valid Remote Session:$sid"); }

		$response = $client->send($query,30);

		$value = $response->value();

		if (!$response->faultCode()) { 
			$data = php_xmlrpc_decode($value);
			$found = $data;
		}
		
	} // xml_rpc

	return $found;

} // session_exists

/*!
	@function extend_session
	@discussion just update the expire time
*/
function extend_session($sid) { 

	$new_time = time() + conf('local_length');

	if ($_COOKIE['amp_longsess'] == '1') { $new_time = time() + 86400*364; }

	$sql = "UPDATE session SET expire='$new_time' WHERE id='$sid'";
	$db_results = mysql_query($sql, dbh());

} // extend_session

/*!
	@function get_tag_type
	@discussion finds out what tag the audioinfo
		results returned
*/
function get_tag_type($results) {

         // Check and see if we are dealing with an ogg
         // If so order will be a little different
         if ($results['ogg']) {
        	$order[0] = 'ogg';
         } // end if ogg
         elseif ($results['rm']) {
		$order[0] = 'rm';
         }
	 elseif ($results['flac']) { 
	 	$order[0] = 'flac';
	 }
         elseif ($results['asf']) {
                $order[0] = 'asf';
         }
	 elseif ($results['m4a']) { 
	 	$order[0] = 'm4a';
	 }
	 elseif ($results['mpc']) { 
	 	$order[0] = 'mpc';
	 }
         else {
	        $order = conf('id3tag_order');
         } // end else

         if (!is_array($order)) {
	         $order = array($order);
         }

        // set the $key to the first found tag style (according to their prefs)
        foreach($order as $key) {
                if ($results[$key]) {
                	break;
        	}
	}

	return $key;

} // get_tag_type


/*!
	@function clean_tag_info
	@discussion cleans up the tag information
*/
function clean_tag_info($results,$key,$filename) { 

	$info = array();

	$clean_array = array("\n","\t","\r","\0");
	$wipe_array  = array("","","","");

	$info['file']		= $filename;
	$info['title']        	= stripslashes(trim($results[$key]['title']));
	$info['year']         	= intval($results[$key]['year']);
	$info['comment']      	= sql_escape(str_replace($clean_array,$wipe_array,$results[$key]['comment']));
	$info['bitrate']      	= intval($results['avg_bit_rate']);
	$info['rate']         	= intval($results['sample_rate']);
	$info['mode']         	= $results['bitrate_mode'];
	$info['size']         	= filesize($filename); 
	$info['time']         	= intval($results['playing_time']);
	$info['track']		= intval($results[$key]['track']);

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
       @function batch_ok()
       @discussion return boolean if user can batch download
*/
function batch_ok( ) {
	global $user;
	// i check this before showing any link
	// should make it easy to tie to a new pref if you choose to add it
	if (conf('allow_zip_download')) { 
		return( $user->prefs['download'] );
	} // if allowed zip downloads

	return false;

} // batch_ok

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
        $limit          = $options['limit'];

        /* If they've passed -1 as limit then don't get everything */
        if ($options['limit'] == "-1") { unset($options['limit']); }
        else { $options['limit'] = "LIMIT " . $limit; }


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
                        else {
                                $value = sql_escape($value);
                                $where .= " AND $type='$value' ";
                        }
            }



        if ($options['random_type'] == 'full_album') {
                $query = "SELECT album.id FROM song,album WHERE song.album=album.id AND $where GROUP BY song.album ORDER BY RAND() " . $options['limit'];
                $db_results = mysql_query($query, $dbh);
                while ($data = mysql_fetch_row($db_results)) {
                        $albums_where .= " OR song.album=" . $data[0];
                }
                $albums_where = ltrim($albums_where," OR");
                $query = "SELECT song.id FROM song WHERE $albums_where ORDER BY song.track ASC";
        }
        elseif ($options['random_type'] == 'full_artist') {
                $query = "SELECT artist.id FROM song,artist WHERE song.artist=artist.id AND $where GROUP BY song.artist ORDER BY RAND() " . $options['limit'];
                $db_results = mysql_query($query, $dbh);
                while ($data = mysql_fetch_row($db_results)) {
                        $artists_where .= " OR song.artist=" . $data[0];
                }
                $artists_where = ltrim($artists_where," OR");
                $query = "SELECT song.id FROM song WHERE $artists_where ORDER BY RAND()";
        }
        elseif ($options['random_type'] == 'unplayed') {
                $uid = $_SESSION['userdata']['username'];
                $query = "SELECT song.id FROM song LEFT JOIN object_count ON song.id = object_count.object_id " .
                         "WHERE ($where) AND ((object_count.object_type='song' AND userid = '$uid') OR object_count.count IS NULL ) " .
                         "ORDER BY CASE WHEN object_count.count IS NULL THEN RAND() WHEN object_count.count > 4 THEN RAND()*RAND()*object_count.count " .
                         "ELSE RAND()*object_count.count END " . $options['limit'];
        } // If unplayed
        else {
                $query = "SELECT id FROM song WHERE $where ORDER BY RAND() " . $options['limit'];
        }
        $db_result = mysql_query($query, $dbh);

        $songs = array();

        while ( $r = mysql_fetch_array($db_result) ) {
                $songs[] = $r[0];
        }

        return ($songs);

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

        $dbh = dbh();
        
        $sql = "SELECT object_id, SUM(count) as count FROM object_count" .
                " WHERE object_type = '$type'" .
                " GROUP BY object_id" .
                " ORDER BY count DESC LIMIT " . conf('popular_threshold');
        $db_result = mysql_query($sql, $dbh);
        
        $items = array();
        $web_path = conf('web_path');
        
        while ( $r = @mysql_fetch_object($db_result) ) {
		/* If Songs */
                if ( $type == 'song' ) {
                        $song = new Song($r->object_id);
                        $artist = $song->get_artist_name();
                        $text = "$artist - $song->title";
                        /* Add to array */
                        $items[] = "<li> <a href=\"$web_path/song.php?action=m3u&amp;song=$song->id\" title=\"". htmlspecialchars($text) ."\">" .
	                                   htmlspecialchars(truncate_with_ellipse($text, conf('ellipse_threshold_title')+3)) . "&nbsp;($r->count)</a> </li>";
                } // if it's a song
                
		/* If Artist */
                elseif ( $type == 'artist' ) {
                        $artist  = get_artist_name($r->object_id);
                        $items[] = "<li> <a href=\"$web_path/artists.php?action=show&amp;artist=$r->object_id\" title=\"". htmlspecialchars($artist) ."\">" .
                        	           htmlspecialchars(truncate_with_ellipse($artist, conf('ellipse_threshold_artist')+3)) . "&nbsp;($r->count)</a> </li>";
                } // if type isn't artist

		/* If Album */
                elseif ( $type == 'album' ) {
                        $album   = new Album($r->object_id);
                        $items[] = "<li> <a href=\"$web_path/albums.php?action=show&amp;album=$r->object_id\" title=\"". htmlspecialchars($album->name) ."\">" . 
                        	           htmlspecialchars(truncate_with_ellipse($album->name,conf('ellipse_threshold_album')+3)) . "&nbsp;($r->count)</a> </li>";
                } // else not album

		elseif ($type == 'genre') { 
			$genre 	 = new Genre($r->object_id);
			$items[] = "<li> <a href=\"$web_path/browse.php?action=genre&amp;genre=$r->object_id\" title=\"" . htmlspecialchars($genre->name) . "\">" .
					htmlspecialchars(truncate_with_ellipse($genre->name,conf('ellipse_threshold_title')+3)) . "&nbsp;($r->count)</a> </li>";
		} // end if genre
        } // end while
       
	if (count($items) == 0) { 
		$items[] = "<li style=\"list-style-type: none\"><span class=\"error\">" . _("Not Enough Data") . "</span></li>\n";
	}
 
        return $items;

} // get_global_popular

/** 
 * gen_newest
 * Get a list of newest $type (which can then be handed to show_info_box
 * @package Web Interface
 * @catagory Get
 * @todo Add Genre
 */
function get_newest ($type = 'artist') {

        $dbh = dbh();

        if (conf('popular_threshold') < 1) { conf(array('popular_threshold'=>'10'),1); }

        $sql = "SELECT DISTINCT $type FROM song ORDER BY addition_time " .
                "DESC LIMIT " . conf('popular_threshold');
        $db_result = mysql_query($sql, $dbh);

        $items = array();
        $web_path = conf('web_path');

        while ( $item = mysql_fetch_row($db_result) ) {
                if ( $type == 'artist' ) {
                        $artist = new Artist($item[0]);
                        $artist->format_artist();
                        $items[] = "<li>" . $artist->link . "</li>\n";
                }
                elseif ( $type == 'album' ) {
                        $album = new Album($item[0]);
                        $album->format_album();
                        $items[] = "<li>" . $album->f_name . "</li>";
                }
        }
        return $items;
} // get_newest

/** 
 * show_info_box
 * This shows the basic box that popular and newest stuff goes into
 * @package Web Interface
 * @catagory Display
 * @todo make this use a template
 */
function show_info_box ($title, $type, $items) {

        $web_path = conf('web_path');
        $popular_threshold = conf('popular_threshold');

       echo "<table class=\"border\" cellspacing=\"1\" cellpadding=\"3\" width=\"100%\" border=\"0\">";
       echo "   <tr class=\"table-header\">";


        if ($type == 'your_song') {
                echo "<td>$title - <a href=\"$web_path/song.php?action=m3u&amp;your_popular_songs=$popular_threshold\">Play</a></td>\n";
        }
        elseif ($type == 'song') {
                echo "<td>$title - <a href=\"$web_path/song.php?action=m3u&amp;popular_songs=$popular_threshold\">Play</a></td>\n";
        }
        else {
                echo "<td>$title</td>\n";
        }

        print <<<ECHO
  </tr>
  <tr class="even">
    <td align="left">
      <ol>

ECHO;

        foreach ($items as $item) {
                echo "$item\n";
        }

        print <<<ECHO
      </ol>
    </td>
  </tr>
</table>

ECHO;

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
 * tbl_name
 * This function takes a SQL table name and returns it with any prefix 
 * that might be needed to make it work, 
 * @package General
 * @catagory Database
 */
function tbl_name($table) { 

	/* For now we just return the table name */
	return $table;

} // tbl_name

/**
 * clear_catalog_stats()
 *
 * Use this to clear the stats for the entire Ampache server.
 * @package Catalog
 * @catagory Clear
 */
function clear_catalog_stats() {

        $dbh = dbh();

        /* Wipe out the object_count table */
        $sql = "TRUNCATE object_count";
        $results = mysql_query($sql, $dbh);

        /* Set every song to unplayed */
        $sql = "UPDATE song SET played='0'";
        $results = mysql_query($sql, $dbh);
     
} // clear_catalog_stats

/**
 * check_username
 * this function checks to make sure the specified username doesn't already exist 
 * @package General
 * @catagory Users
 */
function check_username($username) { 

	$sql = "SELECT username FROM user WHERE username = '" . sql_escape($username) . "'";
	$db_results = mysql_query($sql, dbh());

	if (mysql_fetch_row($db_results)) { 
		return fakse; 
	}

	return true;

} // check_username

?>
