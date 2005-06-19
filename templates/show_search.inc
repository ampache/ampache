<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
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
 @header search template
 @discussion This is the template for the searches... amazing!

*/

?>
<form name="search" method="post" action="<?php echo  conf('web_path'); ?>/search.php" enctype="multipart/form-data" style="Display:inline">
<table class="tabledata" cellspacing="0" cellpadding="3" border="0" width="450px" style="clear:both;">
<tr class="table-header">
	<td colspan="2"><b><?php echo  _("Search Ampache"); ?>...</b></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo  _("Search"); ?>:</td>
	<td><input type="text" name="search_string" value="<?php echo  $_REQUEST['search_string']; ?>" /></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo  _("Object Type"); ?>:</td>
	<td>
		<?php
		$search_type = $_REQUEST['search_field'];
               if (isset($_REQUEST['search_field'])) {
                       $search_field = $_REQUEST['search_field'];
                       ${$search_field} = 1;
               } else {
                       $search_field = conf('search_field');
                       ${$search_field} = 1;
               }

               if (isset($_REQUEST['search_type'])) {
                       $search_type = $_REQUEST['search_type'];
                       ${$search_type} = 1;
               } else {
               		$search_type = conf('search_type');
                	${$search_type} = 1;
                } 
		?>
		<select name="search_field">
			<option <?php  if ($artist) { echo "selected=\"selected\""; } ?> value="artist"  > Artist</option>
			<option <?php  if ($album) { echo "selected=\"selected\""; } ?> value="album"  > Album</option>
			<option <?php  if ($song_title) { echo "selected=\"selected\""; } ?> value="song_title"  > Song Title</option>
			<option <?php  if ($song_genre) { echo "selected=\"selected\""; } ?> value="song_genre"  > Song Genre</option>
			<option <?php  if ($song_year) { echo "selected=\"selected\""; } ?> value="song_year"  > Song Year</option>
			<option <?php  if ($song_bitrate) { echo "selected=\"selected\""; } ?> value="song_bitrate"  > Song Bitrate</option>
			<option <?php  if ($song_min_bitrate) { echo "selected=\"selected\""; } ?> value="song_min_bitrate"  > Minimum Bitrate</option>
			<option <?php  if ($song_filename) { echo "selected=\"selected\""; } ?> value="song_filename"  > Song Filename</option>
		</select>
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo  _("Search Type"); ?>:</td>
	<td>
		<input type="radio" name="search_type" value="exact" <?php  if ($_REQUEST['search_type'] === 'exact') { echo "checked=\"checked\"";}?> /> Exact<br />
		<input type="radio" name="search_type" value="fuzzy" <?php  if ($_REQUEST['search_type'] !== 'exact') { echo "checked=\"checked\"";}?> /> Fuzzy<br />
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td>&nbsp;</td>
	<td>
		<input type="submit" value="<?php echo  _("Search"); ; ?>" />&nbsp;&nbsp;
		<input type="reset" value="Reset Form" />
		<input type="hidden" name="action" value="search" />
	</td>
</tr>
</table>
</form>
