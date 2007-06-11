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

// Title for this album
$title		= scrub_out($album->name) . ' -- ' . $album->f_artist; 
?>
<?php show_box_top($title); ?>
	<div style="float:left;display:table-cell;width:140px;">
	<?php 
        if ($album_name != "Unknown (Orphaned)") {
		$aa_url = $web_path . "/image.php?id=" . $album->id . "&amp;type=popup&amp;sid=" . session_id();
		echo "<a target=\"_blank\" href=\"$aa_url\" onclick=\"popup_art('$aa_url'); return false;\">";
		echo "<img border=\"0\" src=\"" . $web_path . "/image.php?id=" . $album->id . "&amp;thumb=2&amp;sid=" . session_id() . "\" alt=\"Album Art\" height=\"128\" />";
		echo "</a>\n";
        }
	?>
	</div>
	<div style="display:table-cell;vertical-align:top;">
		<?php
		if (Config::get('ratings')) {	
			echo "<div style=\"float:left; display:inline;\" id=\"rating_" . $album->id . "_album\">";
			show_rating($album->id, 'album');} // end if ratings
			echo "</div>";
		echo "<br />\n";
		?>
		<strong><?php echo _('Actions'); ?>:</strong><br />
		&nbsp;&nbsp;<a href="<?php echo $web_path; ?>/stream.php?action=album&amp;album_id=<?php echo $album->id; ?>"><?php echo  _("Play Album"); ; ?></a><br />
		&nbsp;&nbsp;<a href="<?php echo $web_path; ?>/stream.php?action=album_random&amp;album_id=<?php echo $album->id; ?>"><?php echo  _("Play Random from Album"); ; ?></a><br />
		<?php if ( ($GLOBALS['user']->has_access('75')) || (!Config::get('use_auth'))) { ?>
		&nbsp;&nbsp;<a href="<?php echo $web_path; ?>/albums.php?action=clear_art&amp;album_id=<?php echo $album->id; ?>"><?php echo  _("Reset Album Art"); ; ?></a><br />
		<?php } ?>
		&nbsp;&nbsp;<a href="<?php echo $web_path; ?>/albums.php?action=find_art&amp;album_id=<?php echo $album->id; ?>"><?php echo  _("Find Album Art"); ; ?></a><br />
		<?php  if (($GLOBALS['user']->has_access('100')) || (!Config::get('use_auth'))) { ?>
		&nbsp;&nbsp;<a href="<?php echo $web_path; ?>/albums.php?action=update_from_tags&amp;album_id=<?php echo $album->id; ?>"><?php echo  _("Update from tags"); ?></a><br />
		<?php  } ?>
		<?php if (Access::check_function('batch_download')) { ?>
		&nbsp;&nbsp;<a href="<?php echo $web_path; ?>/batch.php?action=alb&amp;id=<?php echo $album->id; ?>"><?php echo _('Download'); ?></a><br />
		<?php } ?>
	</div>
<?php show_box_bottom(); ?>
<?php 
	show_box_top(_('Songs')); 
	$object_ids = $album->get_songs(); 
	require Config::get('prefix') . '/templates/show_songs.inc.php'; 
	show_box_bottom(); 
?>
