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
$localplay = init_localplay();

$required_info 	= "&amp;user_id=" . $GLOBALS['user']->id . "&amp;sessid=" . session_id(); 
$ajax_url	= $web_path . '/server/ajax.server.php';

?>
<div class="localplaycontrol" style="display:table-cell;cursor:pointer;border:1px solid black;padding:2px;">
<?php if ($localplay->has_function('prev')) { ?>
<span id="prev_button" onclick="ajaxPut('<?php echo $ajax_url; ?>?action=localplay&amp;cmd=prev<?php echo $required_info; ?>','localplay_state');return true;">
	<img src="<?php echo $web_path; ?>/images/localplay/prev.gif" alt="prev" />
</span>
<?php } ?>
<span id="stop_button" onclick="ajaxPut('<?php echo $ajax_url; ?>?action=localplay&amp;cmd=stop<?php echo $required_info; ?>','localplay_state');return true;">
	<img src="<?php echo $web_path; ?>/images/localplay/stop.gif" alt="stop" />
</span>
<?php if ($localplay->has_function('pause')) { ?>
<span id="pause_button" onclick="ajaxPut('<?php echo $ajax_url; ?>?action=localplay&amp;cmd=pause<?php echo $required_info; ?>','localplay_state');return true;">
	<img src="<?php echo $web_path; ?>/images/localplay/pause.gif" alt="pause" />
</span>
<?php } ?>
<span id="play_button" onclick="ajaxPut('<?php echo $ajax_url; ?>?action=localplay&amp;cmd=play<?php echo $required_info; ?>','localplay_state');return true;">
	<img src="<?php echo $web_path; ?>/images/localplay/play.gif" alt="play" />
</span>
<?php if ($localplay->has_function('next')) { ?>
<span id="next_button" onclick="ajaxPut('<?php echo $ajax_url; ?>?action=localplay&amp;cmd=next<?php echo $required_info; ?>','localplay_state');return true;">
	<img src="<?php echo $web_path; ?>/images/localplay/next.gif" alt="next" />
</span>
<?php } ?>
<br />
<?php if ($localplay->has_function('volume_up')) { ?>
<span id="up_button" onclick="ajaxPut('<?php echo $ajax_url; ?>?action=localplay&amp;cmd=volume_up<?php echo $required_info; ?>','localplay_state');return true;">
	<img src="<?php echo $web_path; ?>/images/localplay/volup.gif" alt="volume up" />
</span>
<?php } ?>
<?php if ($localplay->has_function('volume_down')) { ?>
<span id="down_button" onclick="ajaxPut('<?php echo $ajax_url; ?>?action=localplay&amp;cmd=volume_down<?php echo $required_info; ?>','localplay_state');return true;">
	<img src="<?php echo $web_path; ?>/images/localplay/voldn.gif" alt="volume down" />
</span>
<?php } ?>
<?php if ($localplay->has_function('volume_mute')) { ?>
<span id="mute_button" onclick="ajaxPut('<?php echo $ajax_url; ?>?action=localplay&amp;cmd=volume_mute<?php echo $required_info; ?>','localplay_state');return true;">
	<img src="<?php echo $web_path; ?>/images/localplay/volmute.gif" alt="volume mute" />
</span>
<?php } ?>
</div>
