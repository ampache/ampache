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

// Gotta do some math here!
$total_images = count($images);

$rows 	= floor($total_images/6);
$spare 	= $total_images - ($rows * 6);

$i = 0;
?>

<table class="text-box"> 
<?php 
while ($i <= $rows) { 
	$images[$i];
	$ii = $i+1;
	$iii = $i+2;
?>
	<tr>
	<td align="center">
		<a href="<?php echo $images[$i]['url']; ?>" target="_blank">
		<img src="<?php echo scrub_out($images[$i]['url']); ?>" border="0" height="175" width="175" /><br />
		</a>
		<p align="center">
			[<a href="<?php echo conf('web_path'); ?>/albums.php?action=select_art&amp;image=<?php echo $i; ?>&amp;album_id=<?php echo urlencode($_REQUEST['album_id']); ?>">Select</a>]
		</p>
	</td>
	<td align="center">
		<?php if (isset($images[$ii])) { ?>
		<a href="<?php echo $images[$ii]['url']; ?>" target="_blank">
		<img src="<?php echo scrub_out($images[$ii]['url']); ?>" border="0" height="175" width="175" /><br />
		</a>
		<p align="center">
			[<a href="<?php echo conf('web_path'); ?>/albums.php?action=select_art&amp;image=<?php echo $ii; ?>&amp;album_id=<?php echo urlencode($_REQUEST['album_id']); ?>">Select</a>]
		</p>
		<?php } ?>
	</td>
	<td align="center">
		<?php if (isset($images[$iii])) { ?>
		<a href="<?php echo $images[$iii]['url']; ?>" target="_blank">
		<img src="<?php echo scrub_out($images[$iii]['url']); ?>" border="0" height="175" width="175" /><br />
		</a>
		<p align="center">
			[<a href="<?php echo conf('web_path'); ?>/albums.php?action=select_art&amp;image=<?php echo $iii; ?>&amp;album_id=<?php echo urlencode($_REQUEST['album_id']); ?>">Select</a>]
		</p>
		<?php } ?>
	</td>
	</tr>
<?php 
	$i++;
} // end while
?>
</table>
