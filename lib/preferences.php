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
	if ($pref_id != '-1') { $sql .= " WHERE `catagory` != 'system'"; }
	
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
		$apply_to_all	= 'check_' . $data['name'];
		$new_level	= 'level_' . $data['name']; 
		$id		= $data['id'];
		$value 		= scrub_in($_REQUEST[$name]);

		/* Some preferences require some extra checks to be performed */
		switch ($name) { 
			case 'sample_rate':
				$value = Stream::validate_bitrate($value);
			break;
			/* MD5 the LastFM & MyStrands so it's not plainTXT */
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
			Preference::update($id,$pref_id,$value,$_REQUEST[$apply_to_all]); 
			if (Access::check('interface','100') AND $_REQUEST[$new_level]) { 
				Preference::update_level($id,$_REQUEST[$new_level]); 
			} 
		}

	} // end foreach preferences

	// Now that we've done that we need to invalidate the cached preverences
	Preference::clear_from_session(); 

} // update_preferences

/**
 * update_preference
 * This function updates a single preference and is called by the update_preferences function
 */
function update_preference($user_id,$name,$pref_id,$value) { 

	$apply_check = "check_" . $name;
	$level_check = "level_" . $name; 

	/* First see if they are an administrator and we are applying this to everything */
	if ($GLOBALS['user']->has_access(100) AND make_bool($_REQUEST[$apply_check])) { 
		Preference::update_all($pref_id,$value); 
		return true;
	}

	/* Check and see if they are an admin and the level def is set */
	if ($GLOBALS['user']->has_access(100) AND make_bool($_REQUEST[$level_check])) { 
		Preference::update_level($pref_id,$_REQUEST[$level_check]); 
	} 
	
	/* Else make sure that the current users has the right to do this */
	if (Preference::has_access($name)) { 
		$sql = "UPDATE `user_preference` SET `value`='$value' WHERE `preference`='$pref_id' AND `user`='$user_id'";
		$db_results = Dba::query($sql);
		return true;
	}

	return false;

} // update_preference

/**
 * create_preference_input
 * takes the key and then creates the correct type of input for updating it
 */
function create_preference_input($name,$value) { 

	// Escape it for output
	$value = scrub_out($value); 

	$len = strlen($value);
	if ($len <= 1) { $len = 8; }

	if (!Preference::has_access($name)) { 
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
			if ($value == 'localplay') { $is_local = 'selected="selected"'; } 
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
			$controllers = Localplay::get_controllers();
			echo "<select name=\"$name\">\n";
			echo "\t<option value=\"\">" . _('None') . "</option>\n";
			foreach ($controllers as $controller) { 
				if (!Localplay::is_enabled($controller)) { continue; } 
				$is_selected = '';
				if ($value == $controller) { $is_selected = 'selected="selected"'; } 
				echo "\t<option value=\"" . $controller . "\" $is_selected>" . ucfirst($controller) . "</option>\n";
			} // end foreach
			echo "</select>\n";
		break;
		case 'localplay_level':
			if ($value == '25') { $is_user = 'selected="selected"'; } 
			elseif ($value == '100') { $is_admin = 'selected="selected"'; } 
			elseif ($value == '50') { $is_manager = 'selected="selected"'; } 
			echo "<select name=\"$name\">\n";
			echo "<option value=\"0\">" . _('Disabled') . "</option>\n";
			echo "<option value=\"25\" $is_user>" . _('User') . "</option>\n";
			echo "<option value=\"50\" $is_manager>" . _('Manager') . "</option>\n";
			echo "<option value=\"100\" $is_admin>" . _('Admin') . "</option>\n";
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
		case 'lastfm_pass':
			echo "<input type=\"password\" size=\"16\" name=\"$name\" value=\"******\" />";
		break;
		case 'playlist_method': 
			${$value} = ' selected="selected"'; 
			echo "<select name=\"$name\">\n"; 
			echo "\t<option value=\"send\"$send>" . _('Send on Add') . "</option>\n"; 
			echo "\t<option value=\"send_clear\"$send_clear>" . _('Send and Clear on Add') . "</option>\n"; 
			echo "\t<option value=\"clear\"$clear>" . _('Clear on Send') . "</option>\n"; 
			echo "\t<option value=\"default\"$default>" . _('Default') . "</option>\n"; 
			echo "</select>\n"; 
		break;
		case 'transcode':
			${$value} = ' selected="selected"'; 
			echo "<select name=\"$name\">\n"; 
			echo "\t<option value=\"never\"$never>" . _('Never') . "</option>\n"; 
			echo "\t<option value=\"default\"$default>" . _('Default') . "</option>\n"; 
			echo "\t<option value=\"always\"$always>" . _('Always') . "</option>\n"; 
			echo "</select>\n";
		break;
		case 'show_lyrics':
			if ($value == '1') { $is_true = "selected=\"selected\""; } 
			else { $is_false = "selected=\"selected\""; }
			echo "<select name=\"$name\">\n";
			echo "\t<option value=\"1\" $is_true>" . _("Enable") . "</option>\n";
			echo "\t<option value=\"0\" $is_false>" . _("Disable") . "</option>\n";
			echo "</select>\n";
		break;
		default:
			echo "<input type=\"text\" size=\"$len\" name=\"$name\" value=\"$value\" />";
		break;

	} 

} // create_preference_input

?>
