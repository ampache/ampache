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
?>
<table class="text-box">
<tr>
	<td>
		<span class="header1"><?php echo _("Albums by") . " " . $artist->full_name; ?></span>
		<br /><?php if (conf('ratings')) { show_rating($artist->id, 'artist'); } // end if ratings ?><br />
		<ul>
			<li><a href="<?php echo $web_path; ?>/artists.php?action=show_all_songs&amp;artist=<?php echo $artist_id; ?>"><?php echo _("Show All Songs By") . " " . $artist->full_name; ?></a></li>
			<li><a href="<?php echo $web_path; ?>/song.php?action=m3u&amp;artist=<?php echo $artist_id; ?>"><?php echo _("Play All Songs By") . " " . $artist->full_name; ?></a></li>
			<li><a href="<?php echo $web_path; ?>/song.php?action=m3u&amp;artist_random=<?php echo $artist_id; ?>"><?php echo _("Play Random Songs By") . " " . $artist->full_name; ?></a></li>
			<?php  if ($user->has_access('100')) { ?>
				<li><a href="<?php echo $web_path; ?>/artists.php?action=update_from_tags&amp;artist=<?php echo $artist_id; ?>"><?php echo _("Update from tags"); ?></a></li>
				<li><a href="<?php echo $web_path; ?>/artists.php?action=show_rename&amp;artist=<?php echo $artist_id; ?>"><?php echo _("Rename Artist"); ?></a></li>
				<li><a href="<?php echo $web_path; ?>/artists.php?action=show_similar&amp;artist=<?php echo $artist_id; ?>"><?php echo _("Find duplicate artists"); ?></a></li>
			<?php } ?>
		</ul>
	</td>
</tr>
</table>
