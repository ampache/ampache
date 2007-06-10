<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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
<?php show_box_top(_('Active Playlist')); ?>
<ul>
	<li><a href="<?php echo Config::get('web_path'); ?>/stream.php?action=basket"><?php echo get_user_icon('all'); ?></a></li>
<?php if (Access::check_function('batch_download')) { ?>
	<li>
	<a href="<?php echo Config::get('web_path'); ?>/batch.php?action=tmp_playlist&amp;id=<?php echo $GLOBALS['user']->playlist->id; ?>">
	        <?php echo get_user_icon('batch_download','',_('Batch Download')); ?>
        </a>
	</li>
<?php } ?>
	<li><span onclick="ajaxPut('<?php echo Config::get('ajax_url'); ?>?action=basket&amp;type=clear_all');return true;">
		<?php echo get_user_icon('delete','',_('Clear Playlist')); ?>
	</span></li>
</ul>
<div id="current_playlist">
<table cellpadding="0" cellspacing="0">
<?php 
	$objects = $GLOBALS['user']->playlist->get_items(); 
	foreach ($objects as $song_id) { 
		$song = new Song($song_id); 
		$song->format(); 
?>
<tr class="<?php echo flip_class(); ?>">
	<td>
	<?php echo $song->f_link; ?>
	</td>
	<td>
		<span onclick="ajaxPut('<?php echo Config::get('ajax_url'); ?>?action=current_playlist&amp;type=delete&amp;id=<?php echo $song_id; ?>');return true;">
		<?php echo get_user_icon('delete','',_('Delete')); ?>
		</span>
	</td>
</tr>
<?php } if (!count($objects)) { ?>
	<tr><td class="error"><?php echo _('Not Enough Data'); ?></td></tr>
<?php } ?>
</table>
<?php show_box_bottom(); ?> 
