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
?>

<form name="coverart" method="get" action="<?php echo conf('web_path'); ?>/albums.php" style="Displain:inline;">
<table class="text-box">
<tr>
	<td>
		<span class="header1"><?php echo _("Customize Search"); ?></span>
	</td>
</tr>
<tr>
	<td>
		<?php echo _("Artist"); ?>&nbsp;
	</td>
	<td>
		<input type="text" size="20" id="artist_name" name="artist_name" value="<?php echo $artistname; ?>" />
	</td>
</tr>
<tr>
	<td>
	 	<?php echo _("Album"); ?>&nbsp;
	</td>
	<td>
		<input type="text" size="20" id="album_name" name="album_name" value="<?php echo $albumname; ?>" />
	</td>
</tr>
<tr>
	<td>
		<?php echo _("Direct URL to Image"); ?>
	</td>
	<td>
		<input type="text" size="40" id="cover" name="cover" value="" />
	</td>
</tr>
<tr>
	<td>
		<input type="hidden" name="action" value="find_art" />
		<input type="hidden" name="album_id" value="<?php echo $album->id; ?>" />
		<input type="submit" value="<?php echo _("Get Art"); ?>" />
	</td>
</tr>
</table>
</form>
