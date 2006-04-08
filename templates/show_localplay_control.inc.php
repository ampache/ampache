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

$web_path = conf('web_path'); 
$data = $localplay->status();

$required_info 	= "&user_id=" . $GLOBALS['user']->id . "&sessid=" . session_id(); 
$ajax_url	= $web_path . '/server/ajax.server.php';

?>
<?php if ($localplay->has_function('prev')) { ?>
<span id="prev_button" onclick="ajaxPut('<?php echo $ajax_url; ?>','action=localplay&cmd=prev<?php echo $required_info; ?>','localplay_state');return true;">
	<img src="<?php echo $web_path; ?>/images/localplay/prev.gif">
</span>
<?php } ?>
<span id="stop_button" onclick="ajaxPut('<?php echo $ajax_url; ?>','action=localplay&cmd=stop<?php echo $required_info; ?>','localplay_state');return true;">
	<img src="<?php echo $web_path; ?>/images/localplay/stop.gif">
</span>
<?php if ($localplay->has_function('pause')) { ?>
<span id="pause_button" onclick="ajaxPut('<?php echo $ajax_url; ?>','action=localplay&cmd=pause<?php echo $requird_info; ?>','localplay_state');return true;">
	<img src="<?Php echo $web_path; ?>/images/localplay/pause.gif">
</span>
<?php } ?>
<span id="play_button" onclick="ajaxPut('<?php echo $ajax_url; ?>','action=localplay&cmd=play<?php echo $required_info; ?>','localplay_state');return true;">
	<img src="<?php echo $web_path; ?>/images/localplay/play.gif">
</span>
<?php if ($localplay->has_function('next')) { ?>
<span id="next_button" onclick="ajaxPut('<?php echo $ajax_url; ?>','action=localplay&cmd=next<?php echo $required_info; ?>','localplay_state');return true;">
	<img src="<?php echo $web_path; ?>/images/localplay/next.gif">
</span>
<?php } ?>
<br />
Current State:<span id="localplay_state"><?php echo $data['state']; ?></span><br />
<span id="play_type">
<?php if (conf('play_type') == 'localplay' AND strlen($_SESSION['data']['old_play_type'])) { ?>
<span style="text-decoration:underline;cursor:pointer;" onclick="ajaxPut('<?php echo $ajax_url; ?>','action=change_play_type&type=<?php echo $_SESSION['data']['old_play_type'] . $required_info; ?>','play_type');return true;">
	<?php echo ucfirst($_SESSION['data']['old_play_type']) . ' ' . _('Mode'); ?>
</span>
<?php } else { ?>
<span style="text-decoration:underline;cursor:pointer;"  onclick="ajaxPut('<?php echo $ajax_url; ?>','action=change_play_type&type=localplay<?php echo $required_info; ?>','play_type');return true;">
	<?php echo _('Localplay Mode'); ?>
</span>
<?php } ?>
</span><br />
