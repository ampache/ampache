<?php
/*

 Copyright (c) 2004 Ampache.org
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
/*!
	@header Show Album
	@discussion shows a single album
*/

// Build array of the table classes we are using
$row_classes = array('even','odd');

//FIXME: I hate having to create this here again... LAME
$user = $GLOBALS['user'];

// Generate variables for the flash ratings
//FIXME:
$album_id=$album->id;
$artist_id=$album->artist_id;
$username=$user->username;

?>
<br />
<table class="border" cellspacing="1" cellpadding="3" border="0">
<tr class="table-header">
	<td colspan="2">
		<font size="+1"><?php echo  htmlspecialchars($album->name); ?> -- 
		<?php echo $album->f_artist; ?></font>
	</td>
</tr>
<tr class="even">
	<td width="140" align="center">
	<?php 
        if ($album_name != "Unknown (Orphaned)") {
                echo "<a target=\"_blank\" href=\"" . conf('web_path') . "/albumart.php?id=" . $album->id . "\">";
		echo "<img border=\"0\" src=\"" . conf('web_path') . "/albumart.php?id=" . $album->id . "&amp;thumb=2\" alt=\"Album Art\" height=\"128\" />";
		echo "</a>\n";
        }
	?>
	</td>
	<td valign="top">
		<?php
		if (conf('ratings')) { 
			show_rating($album->id,'album');
		} // end if ratings
		echo "<br />\n";
		?>
		<b>Actions:</b><br />
		&nbsp;&nbsp;<a href="<?php echo  conf('web_path'); ; ?>/song.php?action=m3u&amp;album=<?php echo  $album->id; ; ?>"><?php echo  _("Play Album"); ; ?></a><br />
		&nbsp;&nbsp;<a href="<?php echo  conf('web_path'); ; ?>/song.php?action=m3u&amp;album_random=<?php echo  $album->id; ; ?>"><?php echo  _("Play Random from Album"); ; ?></a><br />
		&nbsp;&nbsp;<a href="<?php echo  conf('web_path'); ; ?>/albums.php?action=clear_art&amp;album_id=<?php echo  $album->id; ; ?>"><?php echo  _("Reset Album Art"); ; ?></a><br />
		&nbsp;&nbsp;<a href="<?php echo  conf('web_path'); ; ?>/albums.php?action=find_art&amp;album_id=<?php echo  $album->id; ; ?>"><?php echo  _("Find Album Art"); ; ?></a><br />
		<?php  if ($user->has_access('100') || !conf('use_auth')) { ?>
		&nbsp;&nbsp;<a href="<?php echo  conf('web_path'); ; ?>/albums.php?action=update_from_tags&amp;album_id=<?php echo  $album->id; ; ?>"><?php echo  _("Update from tags"); ; ?></a><br />
		<?php  } ?>
		<?php if( batch_ok() ) { ?>
	        &nbsp;&nbsp;<a href="<?php echo  conf('web_path'); ; ?>/batch.php?action=alb&amp;id=<?php echo  $album->id; ; ?>"><?php echo _("Download"); ?></a><br />
                <?php } ?>
	</td>
</tr>
</table>
<br />
