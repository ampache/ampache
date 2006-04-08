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

/* Because this is accessed via Ajax we are going to allow the session_id 
 * as part of the get request
 */

$no_session = true;
require_once('../modules/init.php');

/* Verify the existance of the Session they passed in */
if (!session_exists($_REQUEST['sessid'])) { exit(); }

$GLOBALS['user'] = new User($_REQUEST['user_id']);
$action = scrub_in($_REQUEST['action']);

switch ($action) { 
	case 'localplay':
		init_preferences();
		$localplay = init_localplay();
		$localplay->connect();
		$function = scrub_in($_GET['cmd']);
		$localplay->$function(); 
		echo $function;
	break;
	case 'change_play_type':
		init_preferences();
		session_id(scrub_in($_REQUEST['sessid']));
		session_start(); 
		$_SESSION['data']['old_play_type'] = conf('play_type'); 
		$pref_id = get_preference_id('play_type');
		$GLOBALS['user']->update_preference($pref_id,$_GET['type']);

		/* Now Replace the text as you should */
		$ajax_url       = conf('web_path') . '/server/ajax.server.php';
		$required_info  = "&user_id=" . $GLOBALS['user']->id . "&sessid=" . session_id();
		${$_GET['type']} = 'id="pt_active"';
		?>
                <span <?php echo $stream; ?> onclick="ajaxPut('<?php echo $ajax_url; ?>','action=change_play_type&type=stream<?php echo $required_info; ?>','play_type');return true;">
                        <?php echo _('Stream') ?>
                </span>&nbsp;&nbsp;
                <span <?php echo $localplay; ?> onclick="ajaxPut('<?php echo $ajax_url; ?>','action=change_play_type&type=localplay<?php echo $required_info; ?>','play_type');return true;">
                        <?php echo _('Localplay'); ?>
                </span>
		<?php 
	break;
	default:
		echo "Default Action";
	break;
} // end switch action
?>
