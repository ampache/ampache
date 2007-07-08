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

/*!
	@function get_site_preferences
	@discussion gets all of the preferences for this Ampache site
*/
function get_site_preferences() { 

	$results = array();

	$sql = "SELECT preferences.name, preferences.type, user_preference.value, preferences.description FROM preferences,user_preference " .
		" WHERE preferences.id=user_preference.preference AND user_preference.user = '-1' ORDER BY `type`,`name`";
	$db_results = mysql_query($sql, dbh());

	while ($r = mysql_fetch_object($db_results)) { 
		$results[] = $r;
	}

	return $results;

} // get_site_preferences

/*!
	@function set_site_preferences
	@discussion sets the conf() function with the current site preferences from the db
*/
function set_site_preferences() { 

	$results = array();

	$sql = "SELECT preferences.name,user_preference.value FROM preferences,user_preference WHERE user='-1' AND user_preference.preference=preferences.id";
	$db_results = mysql_query($sql, dbh());

	while ($r = mysql_fetch_object($db_results)) { 
		$results[$r->name] = $r->value;
	} // db results

	if (strlen($results['theme_name']) > 0) { 
		$results['theme_path'] = "/themes/" . $results['theme_name'];
	}

	conf($results,1);

} // set_site_preferences

/**
 * clean_preference_name
 * s/_/ /g & upper case first
 */
function clean_preference_name($name) { 

	$name = str_replace("_"," ",$name);
	$name = ucwords($name);

	return $name;

} // clean_preference_name

/*
 * update_preferences
 * grabs the current keys that should be added
 * and then runs throught $_REQUEST looking for those
 * values and updates them for this user
 */
function update_preferences($pref_id=0) { 
	
	$pref_user = new User($pref_id);
	
	/* Get current keys */
	$sql = "SELECT `id`,`name`,`type` FROM `preference`";

	/* If it isn't the System Account's preferences */
	if ($pref_id != '-1') { $sql .= " WHERE `type` != 'system'"; }
	
	$db_results = Dba::query($sql);

	// Collect the current possible keys
	while ($r = Dba::fetch_assoc($db_results)) { 
		$results[] = array('id' => $r['id'], 'name' => $r['name'],'type' => $r['type']);
	} // end collecting keys

	/* Foreach through possible keys and assign them */
	foreach ($results as $data) { 
		/* Get the Value from POST/GET var called $data */
		$type 		= $data['type'];
		$name 		= $data['name'];
		$apply_to_all	= "check_" . $data['name'];
		$id		= $data['id'];
		$value 		= Dba::escape(scrub_in($_REQUEST[$name]));

		/* Some preferences require some extra checks to be performed */
		switch ($name) { 
			case 'sample_rate':
				$value = validate_bitrate($value);
			break;
			/* MD5 the LastFM so it's not plainTXT */
			case 'lastfm_pass':
				/* If it's our default blanking thing then don't use it */
				if ($value == '******') { unset($_REQUEST[$name]); break; } 
				$value = md5($value); 
			break;
			default: 
			break;
		}
		
		/* Run the update for this preference only if it's set */
		if (isset($_REQUEST[$name])) { 
			update_preference($pref_id,$name,$id,$value);
		}

	} // end foreach preferences


} // update_preferences

/**
 * update_preference
 * This function updates a single preference and is called by the update_preferences function
 */
function update_preference($user_id,$name,$pref_id,$value) { 

	$apply_check = "check_" . $name;

	/* First see if they are an administrator and we are applying this to everything */
	if ($GLOBALS['user']->has_access(100) AND make_bool($_REQUEST[$apply_check])) { 
		$sql = "UPDATE `user_preference` SET `value`='$value' WHERE `preference`='$pref_id'";
		$db_results = Dba::query($sql);
		return true;
	}
	
	/* Else make sure that the current users has the right to do this */
	if (has_preference_access($name)) { 
		$sql = "UPDATE `user_preference` SET `value`='$value' WHERE `preference`='$pref_id' AND `user`='$user_id'";
		$db_results = Dba::query($sql);
		return true;
	}

	return false;

} // update_preference

/**
 * has_preference_access
 * makes sure that the user has sufficient
 * rights to actually set this preference, handle
 * as allow all, deny X
 */
function has_preference_access($name) { 

	/* If it's a demo they don't get jack */
        if (Config::get('demo_mode')) {
	        return false;
        }

	$name = Dba::escape($name);

	/* Check Against the Database Row */
	$sql = "SELECT `level` FROM `preference` " . 
		"WHERE `name`='$name'";
	$db_results = Dba::query($sql);

	$data = Dba::fetch_assoc($db_results);

	if ($GLOBALS['user']->has_access($data['level'])) { 
		return true;
	}

	return false;

} //has_preference_access


/**
 * create_preference_input
 * takes the key and then creates the correct type of input for updating it
 */
function create_preference_input($name,$value) { 

	$len = strlen($value);
	if ($len <= 1) { $len = 8; }

	if (!has_preference_access($name)) { 
		if ($value == '1') { 
			echo "Enabled";
		}
		elseif ($value == '0') { 
			echo "Disabled";
		}
		else {
			echo $value; 
		}
		return;
	} // if we don't have access to it

	switch($name) {
		case 'display_menu':
		case 'download':
		case 'quarantine':
		case 'upload':
		case 'access_list':
		case 'lock_songs':
		case 'xml_rpc':
		case 'force_http_play':
		case 'no_symlinks':
		case 'use_auth':
		case 'access_control':
		case 'allow_stream_playback':
		case 'allow_downsample_playback':
		case 'allow_democratic_playback':
		case 'allow_localplay_playback':
		case 'demo_mode':
		case 'condPL':
		case 'rio_track_stats':
		case 'rio_global_stats':
		case 'embed_xspf':
		case 'direct_link':
			if ($value == '1') { $is_true = "selected=\"selected\""; } 
			else { $is_false = "selected=\"selected\""; }
			echo "<select name=\"$name\">\n";
			echo "\t<option value=\"1\" $is_true>" . _("Enable") . "</option>\n";
			echo "\t<option value=\"0\" $is_false>" . _("Disable") . "</option>\n";
			echo "</select>\n";
		break;
		case 'play_type':
			if ($value == 'downsample') { $is_down = 'selected="selected"'; }
			elseif ($value == 'localplay') { $is_local = 'selected="selected"'; } 
			elseif ($value == 'democratic') { $is_vote = 'selected="selected"'; } 
			elseif ($value == 'xspf_player') { $is_xspf_player = 'selected="selected"'; } 
			else { $is_stream = "selected=\"selected\""; } 
			echo "<select name=\"$name\">\n";
			echo "\t<option value=\"\">" . _('None') . "</option>\n";
			if (Config::get('allow_stream_playback')) { 
				echo "\t<option value=\"stream\" $is_stream>" . _('Stream') . "</option>\n";
			}
			if (Config::get('allow_democratic_playback')) { 
				echo "\t<option value=\"democratic\" $is_vote>" . _('Democratic') . "</option>\n";
			}
			if (Config::get('allow_localplay_playback')) { 
				echo "\t<option value=\"localplay\" $is_local>" . _('Localplay') . "</option>\n";	
			} 
			echo "\t<option value=\"xspf_player\" $is_xspf_player>" . _('Flash Player') . "</option>\n";
			echo "</select>\n";
		break;
		case 'playlist_type':
			$var_name = $value . "_type";
			${$var_name} = "selected=\"selected\""; 
			echo "<select name=\"$name\">\n";
			echo "\t<option value=\"m3u\" $m3u_type>" . _('M3U') . "</option>\n";
			echo "\t<option value=\"simple_m3u\" $simple_m3u_type>" . _('Simple M3U') . "</option>\n";
			echo "\t<option value=\"pls\" $pls_type>" . _('PLS') . "</option>\n";
			echo "\t<option value=\"asx\" $asx_type>" . _('Asx') . "</option>\n";
			echo "\t<option value=\"ram\" $ram_type>" . _('RAM') . "</option>\n";
			echo "\t<option value=\"xspf\" $xspf_type>" . _('XSPF') . "</option>\n";
			echo "</select>\n";
		break;
		case 'lang':
			$languages = get_languages();
			$var_name = $value . "_lang";
			${$var_name} = "selected=\"selected\"";
			
			echo "<select name=\"$name\">\n";
			
			foreach ($languages as $lang=>$name) { 
				$var_name = $lang . "_lang";
				
				echo "\t<option value=\"$lang\" " . ${$var_name} . ">$name</option>\n";
			} // end foreach
			echo "</select>\n";
		break;
		case 'localplay_controller':
			$controllers = get_localplay_controllers();
			echo "<select name=\"$name\">\n";
			foreach ($controllers as $controller) { 
				$is_selected = '';
				if ($value == $controller) { $is_selected = 'selected="selected"'; } 
				echo "\t<option value=\"" . $controller . "\" $is_selected>" . ucfirst($controller) . "</option>\n";
			} // end foreach
			echo "\t<option value=\"\">" . _('None') . "</option>\n";
			echo "</select>\n";
		break;
		case 'localplay_level':
			if ($value == '2') { $is_full = 'selected="selected"'; } 
			elseif ($value == '1') { $is_global = 'selected="selected"'; } 
			echo "<select name=\"$name\">\n";
			echo "<option value=\"0\">" . _('Disabled') . "</option>\n";
			echo "<option value=\"1\" $is_global>" . _('Global') . "</option>\n";
			echo "<option value=\"2\" $is_full>" . _('Local') . "</option>\n";
			echo "</select>\n";
		break;
		case 'theme_name':
			$themes = get_themes();
			echo "<select name=\"$name\">\n";
			foreach ($themes as $theme) { 
				$is_selected = "";
				if ($value == $theme['path']) { $is_selected = "selected=\"selected\""; }
				echo "\t<option value=\"" . $theme['path'] . "\" $is_selected>" . $theme['name'] . "</option>\n";
			} // foreach themes
			echo "</select>\n";
		break;
		case 'random_method': 
			echo "<select name=\"$name\">\n"; 
			echo "\t<option value=\"genre\">" . _('Similar Genre') . "</option>"; 
			echo "</select>\n"; 
		break;
		case 'lastfm_pass':
			echo "<input type=\"password\" size=\"16\" name=\"$name\" value=\"******\" />";
		break;
		case 'playlist_add': 
			echo "<select name=\"$name\">\n"; 
			echo "\t<option value=\"append\">" . _('Append to Existing') . "</option>\n"; 
			echo "\t<option value=\"default\">" . _('Default') . "</option>\n"; 
			echo "</select>\n"; 
		break;
		case 'playlist_method': 
			echo "<select name=\"$name\">\n"; 
			echo "\t<option value=\"send\">" . _('Send on Add') . "</option>\n"; 
			echo "\t<option value=\"send\">" . _('Send and Clear') . "</option>\n"; 
			echo "\t<option value=\"default\">" . _('Default') . "</option>\n"; 
			echo "</select>\n"; 
		break;
		default:
			echo "<input type=\"text\" size=\"$len\" name=\"$name\" value=\"$value\" />";
		break;

	} 

} // create_preference_input

/** 
 * get_preference_id
 * This takes the name of a preference and returns it's id this is usefull for calling
 * the user classes update_preference function
 * @package Preferences
 * @catagory Get
 */
function get_preference_id($name) { 

	$sql = "SELECT id FROM preferences WHERE name='" . sql_escape($name) . "'";
	$db_results = mysql_query($sql, dbh());

	$results = mysql_fetch_assoc($db_results);

	return $results['id'];

} // get_preference_id

/**
 * get_preference_name
 * This does the inverse of the above function and returns the preference name from the ID
 * This is usefull for doing... the opposite of above. Amazing isn't it. 
 */
function get_preference_name($id) { 

	$id = sql_escape($id); 

	$sql = "SELECT name FROM preferences WHERE id='$id'"; 
	$db_results = mysql_query($sql,dbh()); 

	$results = mysql_fetch_assoc($db_results); 

	return $results['name']; 

} // get_preference_name

/**
 * insert_preference
 * This creates a new preference record in the
 * preferences table this is used by the modules
 */
function insert_preference($name,$description,$default,$level,$type,$catagory) { 

	/* Clean the incomming variables */
	$name		= sql_escape($name);
	$description 	= sql_escape($description);
	$default	= sql_escape($default);
	$level		= sql_escape($level);
	$type		= sql_escape($type);
	$catagory	= sql_escape($catagory);


	/* Form the sql statement */
	$sql = "INSERT INTO preferences (`name`,`description`,`value`,`type`,`level`,`catagory`) VALUES " . 
		" ('$name','$description','$default','$type','$level','$catagory')";
	$db_results = mysql_query($sql, dbh());

	if ($db_results) { return true; }

	return false;

} // insert_preference

/**
 * init_preferences
 * Third times the charm, why rename a function once when you can do it three times :(
 * This grabs the preferences and then loads them into conf it should be run on page load
 * to initialize the needed variables
 */
function init_preferences() {

        /* Get Global Preferences */
        $sql = "SELECT preference.name,user_preference.value FROM preference,user_preference WHERE user_preference.user='-1' " .
                " AND user_preference.preference = preference.id AND preference.catagory='system'";
        $db_results = Dba::query($sql);

        while ($r = Dba::fetch_assoc($db_results)) {
                $name = $r['name'];
                $results[$name] = $r['value'];
        } // end while sys prefs

        /* Now we need to allow the user to override some stuff that's been set by the above */
        $user_id = '-1';
        if ($GLOBALS['user']->username) {
                $user_id = Dba::escape($GLOBALS['user']->id);
        }

        $sql = "SELECT preference.name,user_preference.value FROM preference,user_preference WHERE user_preference.user='$user_id' " .
                " AND user_preference.preference = preference.id AND preference.catagory != 'system'";
        $db_results = Dba::query($sql);

        while ($r = Dba::fetch_assoc($db_results)) {
                $name = $r['name'];
                $results[$name] = $r['value'];
        } // end while

        /* Set the Theme mojo */
        if (strlen($results['theme_name']) > 0) {
                $results['theme_path'] = '/themes/' . $results['theme_name'];
        }
	// Default to the classic theme if we don't get anything from their
	// preferenecs because we're going to want at least something otherwise
	// the page is going to be really ugly
	else { 	
		$results['theme_path'] = '/themes/classic'; 
	} 

        Config::set_by_array($results,1);

} // init_preferences

/**
 * show_import_playlist
 * This just shows the template for importing playlists
 * from something outside Ampache such as a m3u
 */
function show_import_playlist() { 

	require_once(conf('prefix') . '/templates/show_import_playlist.inc.php');

} // show_import_playlist

/**
 * get_preferences
 * This returns an array of all current preferences in the
 * preferences table, this isn't a users preferences
 */
function get_preferences() {

        $sql = "SELECT * FROM preferences";
        $db_results = mysql_query($sql, dbh());

        $results = array();

        while ($r = mysql_fetch_assoc($db_results)) {
                $results[] = $r;
        }

        return $results;

} // get_preferences

/**
 * update_preference_level
 * This function updates the level field in the preferences table
 * this has nothing to do with a users actuall preferences
 */
function update_preference_level($name,$level) { 

	$name 	= sql_escape($name);
	$level 	= sql_escape($level);

	$sql = "UPDATE preferences SET `level`='$level' WHERE `name`='$name'";
	$db_results = mysql_query($sql,dbh());

	return true;

} // update_preference_level

/**
 * fix_all_users_prefs
 * This function is used by install/uninstall and fixes all of the users preferences
 */
function fix_all_users_prefs() {

        $sql = "SELECT * FROM user";
        $db_results = mysql_query($sql,dbh());

        $user = new User();
        $user->fix_preferences('-1');

        while ($r = mysql_fetch_assoc($db_results)) {
  	      $user->fix_preferences($r['username']);
        }

	return true;

} // fix_all_users_prefs

/**
 * fix_preferences
 * This takes the preferences, explodes what needs to 
 * become an array and boolean everythings
 */
function fix_preferences($results) {

	$results['auth_methods'] 	= explode(",",$results['auth_methods']); 
	$results['tag_order']		= explode(",",$results['tag_order']); 
	$results['album_art_order']	= explode(",",$results['album_art_order']); 
	$results['amazon_base_urls']	= explode(",",$results['amazon_base_urls']); 

        foreach ($results as $key=>$data) {
                if (strcasecmp($data,"true") == "0") { $results[$key] = 1; }
                if (strcasecmp($data,"false") == "0") { $results[$key] = 0; }
        }

        return $results;

} // fix_preferences

?>
