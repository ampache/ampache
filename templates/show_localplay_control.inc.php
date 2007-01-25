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

$required_info 	= conf('ajax_info') . $add_info;
$ajax_url	= conf('ajax_url');

/* If we actually got something back */
if (is_object($localplay)) { 
?>
<div class="localplaycontrol" style="display:table-cell;cursor:pointer;padding:2px;">
<?php if ($localplay->has_function('prev')) { ?>
<span class="prev_button" onclick="ajaxPut('<?php echo $ajax_url; ?>?action=localplay&amp;cmd=prev<?php echo $required_info; ?>');return true;">
	<?php echo get_user_icon('prev','prev_hover'); ?>
</span>
<?php } ?>
<span class="stop_button" onclick="ajaxPut('<?php echo $ajax_url; ?>?action=localplay&amp;cmd=stop<?php echo $required_info; ?>');return true;">
	<?php echo get_user_icon('stop','stop_hover'); ?>
</span>
<?php if ($localplay->has_function('pause')) { ?>
<span class="pause_button" onclick="ajaxPut('<?php echo $ajax_url; ?>?action=localplay&amp;cmd=pause<?php echo $required_info; ?>');return true;">
	<?php echo get_user_icon('pause','pause_hover'); ?>
</span>
<?php } ?>
<span class="play_button" onclick="ajaxPut('<?php echo $ajax_url; ?>?action=localplay&amp;cmd=play<?php echo $required_info; ?>');return true;">
	<?php echo get_user_icon('play','play_hover'); ?>
</span>
<?php if ($localplay->has_function('next')) { ?>
<span class="next_button" onclick="ajaxPut('<?php echo $ajax_url; ?>?action=localplay&amp;cmd=next<?php echo $required_info; ?>');return true;">
	<?php echo get_user_icon('next','next_hover'); ?>
</span>
<?php } ?>
</div>
<?php } // End if localplay object ?>
