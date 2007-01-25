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
?>
<?php show_box_top(ucfirst($localplay->type) . ' ' . _('Localplay')); ?>
<table>
<tr>
	<td valign="top">
		<strong><?php echo _('Actions'); ?>:</strong><br />
		<?php if ($localplay->has_function('delete_all')) { ?>
			<div class="text-action"><a href="<?php echo $web_path; ?>/localplay.php?action=delete_all"><?php echo _('Clear Playlist'); ?></a></div>
		<?php } ?>
	</td><td>
		<?php $add_info = "&amp;return=1"; ?>
		<?php require_once(conf('prefix') . '/templates/show_localplay_status.inc.php'); ?>
	</td>
</tr>
</table>
<?php show_box_bottom(); ?>


<?php show_box_top(_('Current Playlist')); ?>
<div id="lp_playlist">
<?php require_once(conf('prefix') . '/templates/show_localplay_playlist.inc.php'); ?>
</div>
<?php show_box_bottom(); ?>
