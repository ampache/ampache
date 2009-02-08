<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

show_box_top(sprintf(_('%s Playlist') ,$democratic->name)); 
?>
<div id="information_actions">
<ul>
<li>
	<?php echo _('Cooldown'); ?>:<?php echo $democratic->f_cooldown; ?>
</li>
<li>
	<?php echo Ajax::button('?page=democratic&action=send_playlist&democratic_id=' . scrub_out($democratic->id),'all',_('Play'),'play_democratic'); ?>
	<?php echo _('Play'); ?>
</li>
<li>
	<?php echo Ajax::button('?page=democratic&action=clear_playlist&democratic_id=' . scrub_out($democratic->id),'delete',_('Clear Playlist'),'clear_democratic'); ?>
	<?php echo _('Clear Playlist'); ?>
</li>
</ul>

</div>
<?php show_box_bottom(); ?>
