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


/**
 * search template
 * This is the template for the searches... amazing!
 * @package Search
 * @catagory Display
 */
?>

<form name="search" method="post" action="<?php echo  conf('web_path'); ?>/search.php" enctype="multipart/form-data" style="Display:inline">
<table class="tabledata" cellspacing="0" cellpadding="3" border="0">
<tr class="table-header">
	<th colspan="4"><?php echo _("Search Ampache"); ?>...</th
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _("Title"); ?></td>
	<td>
		<input type="checkbox" name="search_object[]" value="title" onclick="flipField('title_string');" />
		<input type="textbox" id="title_string" name="title_string" value="<?php echo scrub_out($_REQUEST['title_string']); ?>" disabled="disabled" />
	</td>
	<td><?php echo _("Artist"); ?></td>
	<td>
		<input type="checkbox" name="search_object[]" value="artist" onclick="flipField('artist_string');" />
		<input type="textbox" id="artist_string" name="artist_string" value="<?Php echo scrub_out($_REQUEST['artist_string']); ?>" disabled="disabled" />
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _("Album"); ?></td>
	<td>
		<input type="checkbox" name="search_object[]" value="album" onclick="flipField('album_string');" />
		<input type="textbox" id="album_string" name="album_string" value="<?php echo scrub_out($_REQUEST['album_string']); ?>" disabled="disabled" />
	</td>
	<td><?php echo _("Genre"); ?></td>
	<td>
		<input type="checkbox" name="search_object[]" value="genre" onclick="flipField('genre_string');" />
		<input type="textbox" id="genre_string" name="genre_string" value="<?php echo scrub_out($_REQUEST['genre_string']); ?>" disabled="disabled" />
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _("Year"); ?></td>
	<td>
		<input type="checkbox" name="search_object[]" value="year" onclick="flipField('year_string');" />
		<input type="textbox" id="year_string" name="year_string" value="<?php echo scrub_out($_REQUEST['year_string']); ?>" disabled="disabled" />
	</td>
	<td><?php echo _("Filename"); ?></td>
	<td>
		<input type="checkbox" name="search_object[]" value="filename" onclick="flipField('filename_string');" />
		<input type="textbox" id="filename_string" name="filename_string" value="<?php echo scrub_out($_REQUEST['filename_string']); ?>" disabled="disabled" />
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _("Played"); ?></td>
	<td>
		<input type="checkbox" name="search_object[]" value="played" onclick="flipField('played_string');" />
		<select id="played_string" name="played_string" disabled="disabled">
			<option value="1"><?php echo _("Yes"); ?></option>
			<option value="0"><?php echo _("No"); ?></option>
		</select>
	</td>
	<td><?php echo _("Min Bitrate"); ?></td>
	<td>
		<input type="checkbox" name="search_object[]" value="minbitrate" onclick="flipField('minbitrate_string');" />
		<select id="minbitrate_string" name="minbitrate_string" disabled="disabled">
			<option value="32">32</option>
			<option value="40">40</option>
			<option value="48">48</option>
			<option value="56">56</option>
			<option value="64">64</option>
			<option value="80">80</option>
			<option value="96">96</option>
			<option value="112">112</option>
			<option value="128">128</option>
			<option value="160">160</option>
			<option value="192">192</option>
			<option value="224">224</option>
			<option value="256">256</option>
			<option value="320">320</option>
		</select>
	</td>
</tr>		
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _("Object Type"); ?>:</td>
	<td>
		<select name="object_type">
			<option value="song"><?php echo _("Songs"); ?></option>
			<option value="album"><?php echo _("Albums"); ?></option>
			<option value="artist"><?php echo _("Artists"); ?></option>
			<option value="genre"><?php echo _("Genres"); ?></option>
		</select>
	</td>
	<td><?php echo _("Method"); ?>:</td>
	<td>
		<select name="method">
			<option value="fuzzy"><?php echo _("Fuzzy"); ?></option>
			<option value="exact"><?php echo _("Exact"); ?></option>
		</select>
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td>&nbsp;</td>
	<td>
	        <input type="submit" value="<?php echo  _("Search"); ; ?>" />&nbsp;&nbsp;
	        <input type="reset" value="Reset Form" />
	        <input type="hidden" name="action" value="search" />
	</td>
	<td colspan="2">&nbsp;</td>
</tr>
</table>

<!-- OLD SKOOL
<form name="search" method="post" action="<?php echo  conf('web_path'); ?>/search.php" enctype="multipart/form-data" style="Display:inline">
<table class="tabledata" cellspacing="0" cellpadding="3" border="0" width="450" style="clear:both;">
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
-->
