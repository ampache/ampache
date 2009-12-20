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
		$client = new XML_RPC_Client($path,$server,$port);

		/* Encode the SID of the incomming client */
		$encoded_sid 		= new XML_RPC_Value($sid,"string");

		$query = new XML_RPC_Message('remote_session_verify',array($encoded_sid) );

		/* Log this event */	
		debug_event('xmlrpc-client',"Checking for Valid Remote Session:$sid",'3'); 

		$response = $client->send($query,30);

		$value = $response->value();

		if (!$response->faultCode()) { 
			$data = XML_RPC_Decode($value);
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
	                           	scrub_out(truncate_with_ellipsis($text, Config::get('ellipse_threshold_title')+3)) . "&nbsp;(" . $r['count'] . ")</a>";
			$items[] = $song;
                } // if it's a song
                
		/* If Artist */
                elseif ( $type == 'artist' ) {
                        $artist = new Artist($r['object_id']);
			$artist->format();
                        $artist->link = "<a href=\"$web_path/artists.php?action=show&amp;artist=" . $r['object_id'] . "\" title=\"". scrub_out($artist->full_name) ."\">" .
                        	           truncate_with_ellipsis($artist->full_name, Config::get('ellipse_threshold_artist')+3) . "&nbsp;(" . $r['count'] . ")</a>";
			$items[] = $artist;
                } // if type isn't artist

		/* If Album */
                elseif ( $type == 'album' ) {
                        $album   = new Album($r['object_id']);
			$album->format(); 
                        $album->link = "<a href=\"$web_path/albums.php?action=show&amp;album=" . $r['object_id'] . "\" title=\"". scrub_out($album->name) ."\">" . 
                        	           scrub_out(truncate_with_ellipsis($album->name,Config::get('ellipse_threshold_album')+3)) . "&nbsp;(" . $r['count'] . ")</a>";
			$items[] = $album;
                } // else not album

		elseif ($type == 'genre') { 
			$genre 	 = new Genre($r['object_id']);
			$genre->format(); 
			$genre->link = "<a href=\"$web_path/browse.php?action=genre&amp;genre=" . $r['object_id'] . "\" title=\"" . scrub_out($genre->name) . "\">" .
					scrub_out(truncate_with_ellipsis($genre->name,Config::get('ellipse_threshold_title')+3)) . "&nbsp;(" . $r['count'] . ")</a>";
			$items[] = $genre;
		} // end if genre
        } // end foreach
       
        return $items;

} // get_global_popular

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
	$results['en_US'] = 'English (US)';

	while ($file = readdir($handle)) { 

		$full_file = Config::get('prefix') . '/locale/' . $file;

		/* Check to see if it's a directory */
		if (is_dir($full_file) AND substr($file,0,1) != '.' AND $file != 'base') { 
				
			switch($file) {
				case 'af_ZA'; $name = 'Afrikaans'; break; /* Afrikaans */
				case 'ca_ES'; $name = 'Catal&#224;'; break; /* Catalan */
				case 'cs_CZ'; $name = '&#x010c;esky'; break; /* Czech */
				case 'da_DK'; $name = 'Dansk'; break; /* Danish */
				case 'de_DE'; $name = 'Deutsch'; break; /* German */
				case 'en_US'; $name = 'English (US)'; break; /* English */
				case 'en_GB'; $name = 'English (UK)'; break; /* English */
				case 'es_ES'; $name = 'Espa&#241;ol'; break; /* Spanish */
				case 'es_MX'; $name = 'Espa&#241;ol (MX)'; break; /* Spanish */
				case 'es_AR'; $name = 'Espa&#241;ol (AR)'; break; /* Spanish */
				case 'et_EE'; $name = 'Eesti'; break; /* Estonian */
				case 'eu_ES'; $name = 'Euskara'; break; /* Basque */
				case 'fr_FR'; $name = 'Fran&#231;ais'; break; /* French */
				case 'ga_IE'; $name = 'Gaeilge'; break; /* Irish */
				case 'el_GR'; $name = 'Greek'; break; /* Greek */
				case 'is_IS'; $name = 'Icelandic'; break; /* Icelandic */
				case 'it_IT'; $name = 'Italiano'; break; /* Italian */
				case 'lv_LV'; $name = 'Latvie&#353;u'; break; /* Latvian */
				case 'lt_LT'; $name = 'Lietuvi&#371;'; break; /* Lithuanian */
				case 'hu_HU'; $name = 'Magyar'; break; /* Hungarian */
				case 'nl_NL'; $name = 'Nederlands'; break; /* Dutch */
				case 'no_NO'; $name = 'Norsk bokm&#229;l'; break; /* Norwegian */
				case 'pl_PL'; $name = 'Polski'; break; /* Polish */
				case 'pt_BR'; $name = 'Portugu&#234;s Brasileiro'; break; /* Portuguese */
				case 'pt_PT'; $name = 'Portugu&#234;s'; break; /* Portuguese */
				case 'ro_RO'; $name = 'Rom&#226;n&#259;'; break; /* Romanian */
				case 'sk_SK'; $name = 'Sloven&#269;ina'; break; /* Slovak */
				case 'sl_SI'; $name = 'Sloven&#353;&#269;ina'; break; /* Slovenian */
				case 'sr_CS'; $name = 'Srpski'; break; /* Serbian */
				case 'fi_FI'; $name = 'Suomi'; break; /* Finnish */
				case 'sv_SE'; $name = 'Svenska'; break; /* Swedish */
				case 'uk_UA'; $name = 'Українська'; break; /* Ukrainian */
				case 'vi_VN'; $name = 'Ti&#7871;ng Vi&#7879;t'; break; /* Vietnamese */
				case 'tr_TR'; $name = 'T&#252;rk&#231;e'; break; /* Turkish */
				case 'bg_BG'; $name = '&#x0411;&#x044a;&#x043b;&#x0433;&#x0430;&#x0440;&#x0441;&#x043a;&#x0438;'; break; /* Bulgarian */
				case 'ru_RU'; $name = '&#1056;&#1091;&#1089;&#1089;&#1082;&#1080;&#1081;'; break; /* Russian */
				case 'zh_CN'; $name = '&#31616;&#20307;&#20013;&#25991;'; break; /* Chinese */
				case 'zn_TW'; $name = '&#32321;&#39636;&#20013;&#25991;'; break; /* Chinese */
				case 'ko_KR'; $name = '&#xd55c;&#xad6d;&#xb9d0;'; break; /* Korean */
				case 'ja_JP'; $name = '&#x65e5;&#x672c;&#x8a9e;'; break; /* Japanese */
				case 'nb_NO'; $name = 'Norsk'; break; /* Norwegian */
				/* These languages are right to left. */
				case 'ar_SA'; $name = '&#1575;&#1604;&#1593;&#1585;&#1576;&#1610;&#1577;'; break; /* Arabic */
				case 'he_IL'; $name = '&#1506;&#1489;&#1512;&#1497;&#1514;'; break; /* Hebrew */
				case 'fa_IR'; $name = '&#1601;&#1575;&#1585;&#1587;&#1610;'; break; /* Farsi */
				default: $name = _('Unknown'); break;
			} // end switch

		
			$results[$file] = $name;
		}

	} // end while

	return $results;

} // get_languages

/**
 * format_time
 * This formats seconds into minutes:seconds
 * //FIXME This should be removed, no reason for it!
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
