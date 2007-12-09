<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; version 2
 of the License.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
$status = $localplay->status();  

?>
<?php show_box_top(_('Localplay Control')); ?>
<span id="lp_state"><?php echo $localplay->get_user_state($status['state']) ?></span><br />
<div id="lp_box_vol">
	<?php echo Ajax::button('?page=localplay&action=command&command=volume_up','volumeup',_('Increase Volume'),'localplay_volume_up'); ?>
	<?php echo Ajax::button('?page=localplay&action=command&command=volume_down','volumedn',_('Decrease Volume'),'localplay_volume_dn'); ?>
	<?php echo Ajax::button('?page=localplay&action=command&command=volume_mute','volumemute',_('Mute'),'localplay_mute'); ?>
	<?php echo _('Volume'); ?>:<span id="lp_volume"><?php echo $status['volume']; ?></span>
</div>
<br />
	<?php echo _('Repeat') . ":" . print_boolean($status['repeat']); ?> | 
	<?php echo Ajax::text('?page=localplay&action=repeat&value=' . invert_boolean($status['repeat']),print_boolean(invert_boolean($status['repeat'])),'localplay_repeat'); ?>
	<br />
	<?php echo _('Random') . ":" . print_boolean($status['random']); ?> | 
	<?php echo Ajax::text('?page=localplay&action=random&value=' . invert_boolean($status['random']),print_boolean(invert_boolean($status['random'])),'localplay_random'); ?>
	<br />
<?php show_box_bottom(); ?>
