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
?>
<?php show_box_top(_('Manage Radio Stations'),'info-box'); ?>
<div id="information_actions">
<ul>
<li>
	<a href="<?php echo Config::get('web_path'); ?>/radio.php?action=show_create"><?php echo get_user_icon('add'); ?></a> <?php echo _('Add Radio Station'); ?>
</li>
<li>
	<a href="<?php echo Config::get('web_path'); ?>/playlist.php?action=show_import_playlist"><?php echo get_user_icon('world_link',_('Import')); ?></a> <?php echo _('Import'); ?>
</li>
</ul>
</div>
<?php show_box_bottom(); ?>
