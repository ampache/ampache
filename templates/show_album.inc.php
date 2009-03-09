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

$web_path = Config::get('web_path');
$ajax_url = Config::get('ajax_url'); 

// Title for this album
if ($album->disk) {
	$disk = "<span class=\"discnb disc" .$album->disk. "\">, " . _('Disk') . " " . $album->disk . "</span>";
}
$title		= scrub_out($album->name) . '&nbsp;(' . $album->year . ')' . $disk .'&nbsp;-&nbsp;' . $album->f_artist_link;
?>
<?php show_box_top($title,'info-box'); ?>
<div class="album_art">
	<?php 
	if ($album_name != _('Unknown (Orphaned)')) {
        $name = '[' . $album->f_artist . '] ' . scrub_out($album->full_name);  

		    $aa_url = $web_path . "/image.php?id=" . $album->id . "&amp;type=popup&amp;sid=" . session_id();
		    echo "<a target=\"_blank\" href=\"$aa_url\" onclick=\"popup_art('$aa_url'); return false;\">";
		    echo "<img src=\"" . $web_path . "/image.php?id=" . $album->id . "&amp;thumb=2\" alt=\"".$name."\" title=\"".$name."\" height=\"128\" width=\"128\" />";
		    echo "</a>\n";
	}
	?>
</div>
<div id="information_actions">
<div style="display:table-cell;" id="rating_<?php echo $album->id; ?>_album">
		<?php Rating::show($album->id,'album'); ?>
</div>
<h3><?php echo _('Actions'); ?>:</h3>
<ul>
	<li>
		<?php echo Ajax::button('?action=basket&type=album&id=' . $album->id,'add',_('Add'),'play_full_' . $album->id); ?> 
		<?php echo _('Add Album'); ?>
	</li>
	<li>
		<?php echo Ajax::button('?action=basket&type=album_random&id=' . $album->id,'random',_('Random'),'play_random_' . $album->id); ?>
		<?php echo _('Add Random from Album'); ?>
	</li>
	<?php if (Access::check('interface','75')) { ?>
	<li>
		<a href="<?php echo $web_path; ?>/albums.php?action=clear_art&amp;album_id=<?php echo $album->id; ?>"><?php echo get_user_icon('delete'); ?></a>
		<?php echo _('Reset Album Art'); ?>
	</li>
	<?php } ?>
	<li>
		<a href="<?php echo $web_path; ?>/albums.php?action=find_art&amp;album_id=<?php echo $album->id; ?>"><?php echo get_user_icon('view'); ?></a>
		<?php echo _('Find Album Art'); ?>
	</li>
	<?php  if ((Access::check('interface','50'))) { ?>
	<li>
		<a href="<?php echo $web_path; ?>/albums.php?action=update_from_tags&amp;album_id=<?php echo $album->id; ?>"><?php echo get_user_icon('cog'); ?></a>
		<?php echo _('Update from tags'); ?>
	</li>
	<?php  } ?>
	<?php if (Access::check_function('batch_download')) { ?>
	<li>
		<a href="<?php echo $web_path; ?>/batch.php?action=album&amp;id=<?php echo $album->id; ?>"><?php echo get_user_icon('batch_download'); ?></a>
		<?php echo _('Download'); ?>
	</li>
	<?php } ?>
</ul>
</div>
<?php show_box_bottom(); ?>
<div id="additional_information">
&nbsp;
</div>
<?php 
	Browse::set_type('song');
	Browse::set_simple_browse(1); 
	Browse::set_filter('album', $album->id);
	Browse::set_sort('track','ASC'); 
	Browse::get_objects(); 
	Browse::show_objects(); 
?>
