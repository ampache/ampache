<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All Rights Reserved

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
$title = _('Albums by') . " " . $artist->full_name; 
?>
<?php require (conf('prefix') . '/templates/show_box_top.inc.php'); ?>
<?php 
if (conf('ratings')) { 
	echo "<span id=\"rating_" . $artist->id . "_artist\" style=\"display:inline;\">";
	show_rating($artist->id, 'artist'); 
	echo "</span>";
} // end if ratings ?>
<strong><?php echo _('Actions'); ?>:</strong><br />
&nbsp;&nbsp;<a href="<?php echo $web_path; ?>/artists.php?action=show_all_songs&amp;artist=<?php echo $artist_id; ?>"><?php echo _("Show All Songs By") . " " . $artist->full_name; ?></a><br />
&nbsp;&nbsp;<a href="<?php echo $web_path; ?>/song.php?action=artist&amp;artist_id=<?php echo $artist_id; ?>"><?php echo _("Play All Songs By") . " " . $artist->full_name; ?></a><br />
&nbsp;&nbsp;<a href="<?php echo $web_path; ?>/song.php?action=artist_random&amp;artist_id=<?php echo $artist_id; ?>"><?php echo _("Play Random Songs By") . " " . $artist->full_name; ?></a><br />
<?php  if ($GLOBALS['user']->has_access('100')) { ?>
	&nbsp;&nbsp;<a href="<?php echo $web_path; ?>/artists.php?action=update_from_tags&amp;artist=<?php echo $artist_id; ?>"><?php echo _("Update from tags"); ?></a><br />
	&nbsp;&nbsp;<a href="<?php echo $web_path; ?>/artists.php?action=show_rename&amp;artist=<?php echo $artist_id; ?>"><?php echo _("Rename Artist"); ?></a><br />
	&nbsp;&nbsp;<a href="<?php echo $web_path; ?>/artists.php?action=show_similar&amp;artist=<?php echo $artist_id; ?>"><?php echo _("Find duplicate artists"); ?></a><br />
<?php } ?>
<?php require (conf('prefix') . '/templates/show_box_bottom.inc.php'); ?>
<?php unset($title); ?>
