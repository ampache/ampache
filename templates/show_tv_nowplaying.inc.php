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
/* Pull the Now Playing Information */
$results = get_now_playing(); 
$web_path = conf('web_path'); 
?>
<table cellpadding="0">
<?php 
foreach ($results as $row) { 
	$title = scrub_out(truncate_with_ellipse($row['song']->title,'25'));
	$album = scrub_out(truncate_with_ellipse($row['song']->f_album_full,'25'));
	$artist = scrub_out(truncate_with_ellipse($row['song']->f_artist_full,'25'));
?>
<tr>
	<td>
		<a target="_blank" href="<?php echo $web_path; ?>/image.php?id=<?php echo $row['song']->album; ?>&amp;type=popup&amp;sid=<?php echo session_id(); ?>" onclick="popup_art('<?php echo $web_path; ?>/image.php?id=<?php echo $row['song']->album; ?>&amp;type=popup&amp;sid=<?php echo session_id(); ?>'); return false;">
		<img align="middle" border="0" src="<?php echo $web_path; ?>/image.php?id=<?php echo $row['song']->album; ?>&amp;fast=1" width="275" height="275" />
		</a>
	</td>
</tr>
<tr>
	<td><?php echo $title; ?> - (<?php echo $album; ?> / <?php echo $artist; ?> )</td>
</tr>
<?php } // end foreach ?>
</table>
