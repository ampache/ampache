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

$web_path = Config::get('web_path');
$ajax_url = Config::get('ajax_url'); 

// Title for this album
if ($album->disk)
{
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
	<div style="display:table-cell;" id="rating_<?php echo $album->id; ?>_album">
			<?php Rating::show($album->id,'album'); ?>
	</div>
	<div id="information_actions">
	
	
	<h3><?php echo _('Actions'); ?>:</h3>
	<ul>
	<li>Tags:
	<?php
	$tags = Tag::get_object_tags('album',$album->id);
	foreach($tags as $i)
	  echo ($i['name']) . ' ';
	?>
	</li>
	<li>
	<form type=POST action=coin>
<?php
echo Ajax::text('?page=tag&action=add&type=album&id=' . $album->id . "&val='+document.getElementById('tagname').value+'", _("Add tag"), 'tag_album');
?>
<input type="text" size="10" maxlength="10"  id="tagname"></input></form>
</li>
	<li><?php echo Ajax::text('?action=basket&type=album&id=' . $album->id,_('Add Album'),'play_full_' . $album->id); ?></li>
	<li><?php echo Ajax::text('?action=basket&type=album_random&id=' . $album->id,_('Add Random from Album'),'play_random_' . $album->id); ?></li>
	<?php if ($GLOBALS['user']->has_access('75')) { ?>
	<li><a href="<?php echo $web_path; ?>/albums.php?action=clear_art&amp;album_id=<?php echo $album->id; ?>"><?php echo _('Reset Album Art'); ?></a></li>
	<?php } ?>
	<li><a href="<?php echo $web_path; ?>/albums.php?action=find_art&amp;album_id=<?php echo $album->id; ?>"><?php echo _('Find Album Art'); ?></a></li>
	<?php  if (($GLOBALS['user']->has_access('75')) || (!Config::get('use_auth'))) { ?>
	<li><a href="<?php echo $web_path; ?>/albums.php?action=update_from_tags&amp;album_id=<?php echo $album->id; ?>"><?php echo _('Update from tags'); ?></a></li>
	<?php  } ?>
	<?php if (Access::check_function('batch_download')) { ?>
	<li><a href="<?php echo $web_path; ?>/batch.php?action=album&amp;id=<?php echo $album->id; ?>"><?php echo _('Download'); ?></a></li>
	<?php } ?>
	<?php if (Plugin::is_installed('OpenStrands')) { ?>
	<li><?php echo Ajax::text('?page=stats&action=show_check_album_tracks&id=' . $album->id,_('Find Missing Tracks'),'album_missing_tracks'); ?></li>
	<?php } ?>
	</ul>
  </div>
<?php show_box_bottom(); ?>
<div id="additional_information">
&nbsp;
</div>
<?php 
	Browse::set_type('song');
	Browse::set_filter('album', $album->id);
	Browse::get_objects(); 
	Browse::show_objects(); 
?>
