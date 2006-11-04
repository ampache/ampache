<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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
	@header Preferences Library
	@discussion This contains all of the functions needed for the preferences
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

/*!
	@function clean_preference_name
	@discussion s/_/ /g & upper case first
*/
function clean_preference_name($name) { 

	$name = str_replace("_"," ",$name);
	$name = ucwords($name);

	return $name;

} // clean_preference_name

/*!
	@function update_preferences
	@discussion grabs the current keys that should be added
		and then runs throught $_REQUEST looking for those
		values and updates them for this user
*/
function update_preferences($pref_id=0) { 
	
	$pref_user = new User($pref_id);
	
	/* Get current keys */
	$sql = "SELECT id,name,type FROM preferences";

	/* If it isn't the System Account's preferences */
	if ($pref_id != '-1') { $sql .= " WHERE type!='system'"; }
	
	$db_results = mysql_query($sql, dbh());

	// Collect the current possible keys
	while ($r = mysql_fetch_assoc($db_results)) { 
		$results[] = array('id' => $r['id'], 'name' => $r['name'],'type' => $r['type']);
	} // end collecting keys

	/* Foreach through possible keys and assign them */
	foreach ($results as $data) { 
		/* Get the Value from POST/GET var called $data */
		$type 		= $data['type'];
		$name 		= $data['name'];
		$apply_to_all	= "check_" . $data['name'];
		$id		= $data['id'];
		$value 		= sql_escape(scrub_in($_REQUEST[$name]));

		/* Some preferences require some extra checks to be performed */
		switch ($name) { 
			case 'theme_name':
				// If the theme exists and it's different then our current one reset the colors
				if (theme_exists($value) AND $pref_user->prefs['theme_name'] != $value) { 
					set_theme_colors($value,$pref_id);
				}
			break;
			case 'sample_rate':
				$value = validate_bitrate($value);
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
 * @package Preferences
 * @catagory Update
 */
function update_preference($username,$name,$pref_id,$value) { 

	$apply_check = "check_" . $name;

	/* First see if they are an administrator and we are applying this to everything */
	if ($GLOBALS['user']->has_access(100) AND make_bool($_REQUEST[$apply_check])) { 
		$sql = "UPDATE user_preference SET `value`='$value' WHERE preference='$pref_id'";
		$db_results = mysql_query($sql, dbh());
		/* Reset everyones colors! */
		if ($name =='theme_name') { 
			set_theme_colors($value,0);
		}
		return true;
	}
	
	/* Else make sure that the current users has the right to do this */
	if (has_preference_access($name)) { 
		$sql = "UPDATE user_preference SET `value`='$value' WHERE preference='$pref_id' AND user='$username'";
		$db_resutls = mysql_query($sql, dbh());
		return true;
	}

	return false;

} // update_preference

/*!
	@function has_preference_access
	@discussion makes sure that the user has sufficient
		rights to actually set this preference, handle
		as allow all, deny X
	//FIXME:
	// This is no longer needed, we just need to check against preferences.level
*/
function has_preference_access($name) { 

	/* If it's a demo they don't get jack */
        if (conf('demo_mode')) {
	        return false;
        }

	$name = sql_escape($name);

	/* Check Against the Database Row */
	$sql = "SELECT level FROM preferences " . 
		"WHERE name='$name'";
	$db_results = mysql_query($sql, dbh());

	$data = mysql_fetch_assoc($db_results);

	$level = $data['level'];

	if ($GLOBALS['user']->has_access($level)) { 
		return true;
	}

	return false;

} // has_preference_access


/*!
	@function create_preference_input
	@discussion takes the key and then creates
		the correct type of input for updating it
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
		elseif ($name == 'upload_dir' || $name == 'quarantine_dir') { 
			/* Show Nothing */
			echo "&nbsp;";
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
			if (conf('allow_stream_playback')) { 
				echo "\t<option value=\"stream\" $is_stream>" . _('Stream') . "</option>\n";
			}
			if (conf('allow_downsample_playback')) { 
				echo "\t<option value=\"downsample\" $is_down>" . _('Downsample') . "</option>\n";
			}
			if (conf('allow_democratic_playback')) { 
				echo "\t<option value=\"democratic\" $is_vote>" . _('Democratic') . "</option>\n";
			}
			if (conf('allow_localplay_playback')) { 
				echo "\t<option value=\"localplay\" $is_local>" . _('Localplay') . "</option>\n";	
			} 
			echo "\t<option value=\"xspf_player\" $is_xspf_player>" . _('XSPF Player') . "</option>\n";
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
			if ($GLOBALS['user']->prefs['localplay_level'] == '2') { $is_full = 'selected="selected"'; } 
			elseif ($GLOBALS['user']->prefs['localplay_level'] == '1') { $is_global = 'selected="selected"'; } 
			echo "<select name=\"$name\">\n";
			echo "<option value=\"0\">" . _('Disabled') . "</option>\n";
			echo "<option value=\"1\" $is_global>" . _('Global') . "</option>\n";
			echo "<option value=\"2\" $is_full>" . _('Full') . "</option>\n";
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
		case 'quarantine_dir':
		case 'upload_dir':
			if (!$GLOBALS['user']->has_access(100)) { 
				break;
			}
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
        $sql = "SELECT preferences.name,user_preference.value FROM preferences,user_preference WHERE user_preference.user='-1' " .
                " AND user_preference.preference = preferences.id AND preferences.catagory='system'";
        $db_results = mysql_query($sql, dbh());

        while ($r = mysql_fetch_assoc($db_results)) {
                $name = $r['name'];
                $results[$name] = $r['value'];
        } // end while sys prefs

        /* Now we need to allow the user to override some stuff that's been set by the above */
        $user_id = '-1';
        if ($GLOBALS['user']->username) {
                $user_id = sql_escape($GLOBALS['user']->id);
        }

        $sql = "SELECT preferences.name,user_preference.value FROM preferences,user_preference WHERE user_preference.user='$user_id' " .
                " AND user_preference.preference = preferences.id AND preferences.catagory != 'system'";
        $db_results = mysql_query($sql, dbh());

        while ($r = mysql_fetch_assoc($db_results)) {
                $name = $r['name'];
                $results[$name] = $r['value'];
        } // end while

        /* Set the Theme mojo */
        if (strlen($results['theme_name']) > 0) {
                $results['theme_path'] = '/themes/' . $results['theme_name'];
        }

        conf($results,1);

        return true;

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

?>
