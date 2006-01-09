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
<br />
<form name="songs" method="post" action="<?php echo conf('web_path'); ?>/admin/catalog.php" enctype="multipart/form-data" style="Display:inline">
<table class="tabledata" cellspacing="0" cellpadding="3" border="0" width="100%">
<tr class="table-header">
	<td class="table-header"><a href="#" onclick="check_songs(); return false;">Select</a></td>
	<td>Title</td>
	<td>Album</td>
	<td>Artist</td>
	<td>Filename</td>
	<td>Addition Time</td>
</tr>
<?php foreach ($songs as $song) { 
	$class = (++$i%2)?'odd':'even';		    
?>
<tr class="<?php echo $class; ?>">
	<td><input type="checkbox" name="song[]" value="<?php echo $song->id; ?>" /></td>
	<td><?php echo $song->title; ?></td>
	<td><?php echo $song->get_album_name($song->album); ?></td>
	<td><?php echo $song->get_artist_name($song->album); ?></td>
	<td><?php echo $song->file; ?></td>
	<td><?php echo date("h:i:s, m/d/y",$song->addition_time); ?></td>

</tr>
<?php } ?>
<?php $class = (++$i%2)?'odd':'even'; ?>
<tr class="<?php echo $class; ?>">
	<td>&nbsp;</td>
	<td colspan="10">
		<input type="submit" value="Remove" />&nbsp;&nbsp;
		<input type="reset" value="Reset Form" />
		<input type="hidden" name="action" value="remove_disabled" />
	</td>
</tr>
<tr>
	<td colspan="10" class="table-header">&nbsp;</td>
</tr>
</table>
</form>

