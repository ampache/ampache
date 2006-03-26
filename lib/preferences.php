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

        if (conf('demo_mode')) {
	        return false;
        }

	switch($name) {

		case 'download':
		case 'upload':
		case 'quarantine':
		case 'upload_dir':
		case 'sample_rate':
		case 'direct_link':
			$level = 100;
		break;
		default:
			$level = 25;
		break;
	} // end switch key


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
			if ($value == 'local_play') { $is_local = "selected=\"selected\""; }
			elseif ($value == 'icecast2') { $is_ice = "selected=\"selected\""; }
			elseif ($value == 'downsample') { $is_down = "selected=\"selected\""; }
			elseif ($value == 'mpd') { $is_mpd = "selected=\"selected\""; }
			elseif ($value == 'slim') { $is_slim = "selected=\"selected\""; }
			else { $is_stream = "selected=\"selected\""; } 
			echo "<select name=\"$name\">\n";
			if (conf('allow_local_playback')) { 
				echo "\t<option value=\"local_play\" $is_local>" . _("Local") . "</option>\n";
			}
			if (conf('allow_stream_playback')) { 
				echo "\t<option value=\"stream\" $is_stream>" . _("Stream") . "</option>\n";
			}
			if (conf('allow_icecast_playback')) { 
				echo "\t<option value=\"icecast2\" $is_ice>" . _("IceCast") . "</option>\n";
			}
			if (conf('allow_downsample_playback')) { 
				echo "\t<option value=\"downsample\" $is_down>" . _("Downsample") . "</option>\n";
			}
			if (conf('allow_mpd_playback')) { 
				echo "\t<option value=\"mpd\" $is_mpd>" . _("Music Player Daemon") . "</option>\n";
			}
			if (conf('allow_slim_playback')) { 
				echo "\t<option value=\"slim\" $is_slim>" . _("SlimServer") . "</option>\n";
			}
			
			echo "</select>\n";
		break;
		case 'playlist_type':
			$var_name = $value . "_type";
			${$var_name} = "selected=\"selected\""; 
			echo "<select name=\"$name\">\n";
			echo "\t<option value=\"m3u\" $m3u_type>" . _("M3U") . "</option>\n";
			echo "\t<option value=\"simple_m3u\" $simple_m3u_type>" . _("Simple M3U") . "</option>\n";
			echo "\t<option value=\"pls\" $pls_type>" . _("PLS") . "</option>\n";
			echo "\t<option value=\"asx\" $asx_type>" . _("Asx") . "</option>\n";
			echo "\t<option value=\"ram\" $ram_type>" . _("RAM") . "</option>\n";
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

?>
