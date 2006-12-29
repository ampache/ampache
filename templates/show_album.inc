<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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
/*!
	@header Show Album
	@discussion shows a single album
*/
$web_path = conf('web_path');
// Build array of the table classes we are using
$row_classes = array('even','odd');

//FIXME:
$album_id	= $album->id;
$artist_id	= $album->artist_id;
$username	= $GLOBALS['user']->username;
$title		= scrub_out($album->name) . ' -- ' . $album->f_artist; 
?>
<?php require (conf('prefix') . '/templates/show_box_top.inc.php'); ?>
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
		if (conf('ratings')) {	
			echo "<div style=\"float:left; display:inline;\" id=\"rating_" . $album->id . "_album\">";
			show_rating($album->id, 'album');} // end if ratings
			echo "</div>";
		echo "<br />\n";
		?>
		<strong><?php echo _('Actions'); ?>:</strong><br />
		&nbsp;&nbsp;<a href="<?php echo $web_path; ?>/song.php?action=album&amp;album_id=<?php echo $album->id; ?>"><?php echo  _("Play Album"); ; ?></a><br />
		&nbsp;&nbsp;<a href="<?php echo $web_path; ?>/song.php?action=album_random&amp;album_id=<?php echo $album->id; ?>"><?php echo  _("Play Random from Album"); ; ?></a><br />
		<?php if ( ($GLOBALS['user']->has_access('75')) || (!conf('use_auth'))) { ?>
		&nbsp;&nbsp;<a href="<?php echo $web_path; ?>/albums.php?action=clear_art&amp;album_id=<?php echo $album->id; ?>"><?php echo  _("Reset Album Art"); ; ?></a><br />
		<?php } ?>
		&nbsp;&nbsp;<a href="<?php echo $web_path; ?>/albums.php?action=find_art&amp;album_id=<?php echo $album->id; ?>"><?php echo  _("Find Album Art"); ; ?></a><br />
		<?php  if (($GLOBALS['user']->has_access('100')) || (!conf('use_auth'))) { ?>
		&nbsp;&nbsp;<a href="<?php echo $web_path; ?>/albums.php?action=update_from_tags&amp;album_id=<?php echo $album->id; ?>"><?php echo  _("Update from tags"); ?></a><br />
		<?php  } ?>
		<?php if (batch_ok()) { ?>
		&nbsp;&nbsp;<a href="<?php echo $web_path; ?>/batch.php?action=alb&amp;id=<?php echo $album->id; ?>"><?php echo _('Download'); ?></a><br />
		<?php } ?>
	</div>
<?php require (conf('prefix') . '/templates/show_box_bottom.inc.php'); ?>
