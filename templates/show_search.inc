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
/* Setup the needed variables that check the correct boxes */
$final_javascript = "<script type=\"text/javascript\" language=\"javascript\">\n<!-- Begin \n";

$i = 0;
foreach ($_REQUEST['search_object'] as $key) { 
	$name = "check_" . $key;
	$field_name = $key . "_string";
	${$name} = "checked=\"checked\"";
	$final_javascript .= "flipField('$field_name'); \n";
        if($i++ == 0) /* Focus the first checked field */
            $final_javascript .= "selectField('$field_name'); \n";
}
if($i == 0) /* Select the artist/title/album one by default */
{
    $final_javascript .= "flipField('all_string'); \n";
    $final_javascript .= "selectField('all_string'); \n";
    $check_all = "checked=\"checked\"";
}

$final_javascript .= " // END-->\n </script>";

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
	<th colspan="4"><?php echo _("Search Ampache"); ?>...</th>
</tr>
<tr class="<?php echo flip_class(); ?>">
        <td><?php echo _("Keywords") ?></td>
        <td>
            <input type="checkbox" name="search_object[]" value="all" onclick="flipField('all_string');" <?php echo $check_all; ?> />
            <input type="text" id="all_string" name="all_string" value="<?php echo scrub_out($_REQUEST['all_string']); ?>" disabled="disabled" />
	    <?php $GLOBALS['error']->print_error('keyword'); ?>
        </td>
        <td colspan="2">&nbsp;</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _("Title"); ?></td>
	<td>
		<input type="checkbox" name="search_object[]" value="title" onclick="flipField('title_string');" <?php echo $check_title; ?> />
		<input type="text" id="title_string" name="title_string" value="<?php echo scrub_out($_REQUEST['title_string']); ?>" disabled="disabled" />
	</td>
	<td><?php echo _("Artist"); ?></td>
	<td>
		<input type="checkbox" name="search_object[]" value="artist" onclick="flipField('artist_string');" <?php echo $check_artist; ?> />
		<input type="text" id="artist_string" name="artist_string" value="<?Php echo scrub_out($_REQUEST['artist_string']); ?>" disabled="disabled" />
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _("Album"); ?></td>
	<td>
		<input type="checkbox" name="search_object[]" value="album" onclick="flipField('album_string');" <?php echo $check_album; ?> />
		<input type="text" id="album_string" name="album_string" value="<?php echo scrub_out($_REQUEST['album_string']); ?>" disabled="disabled" />
	</td>
	<td><?php echo _("Genre"); ?></td>
	<td>
		<input type="checkbox" name="search_object[]" value="genre" onclick="flipField('genre_string');" <?php echo $check_genre; ?> />
		<input type="text" id="genre_string" name="genre_string" value="<?php echo scrub_out($_REQUEST['genre_string']); ?>" disabled="disabled" />
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _("Year"); ?></td>
	<td>
		<input type="checkbox" name="search_object[]" value="year" onclick="flipField('year_string');" <?php echo $check_year; ?> />
		<input type="text" id="year_string" name="year_string" value="<?php echo scrub_out($_REQUEST['year_string']); ?>" disabled="disabled" />
	</td>
	<td><?php echo _("Filename"); ?></td>
	<td>
		<input type="checkbox" name="search_object[]" value="filename" onclick="flipField('filename_string');" <?php echo $check_filename; ?> />
		<input type="text" id="filename_string" name="filename_string" value="<?php echo scrub_out($_REQUEST['filename_string']); ?>" disabled="disabled" />
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _("Played"); ?></td>
	<td>
		<input type="checkbox" name="search_object[]" value="played" onclick="flipField('played_string');" <?php echo $check_played; ?> />
		<select id="played_string" name="played_string" disabled="disabled">
			<option value="1"><?php echo _("Yes"); ?></option>
			<option value="0"><?php echo _("No"); ?></option>
		</select>
	</td>
	<td><?php echo _("Min Bitrate"); ?></td>
	<td>
		<input type="checkbox" name="search_object[]" value="minbitrate" onclick="flipField('minbitrate_string');" <?php echo $check_minbitrate; ?> />
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
	<td><?php echo _("Operator"); ?>:</td>
	<td>
		<select name="operator">
			<option value="or"><?php echo _("OR"); ?></option>
			<option value="and"><?php echo _("AND"); ?></option>
		</select>
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><?php echo _("Method"); ?>:</td>
	<td>
		<select name="method">
			<option value="fuzzy"><?php echo _("Fuzzy"); ?></option>
			<option value="exact"><?php echo _("Exact"); ?></option>
		</select>
	</td>
	<td><?php echo _("Maxium Results"); ?>:</td>
	<td>
		<select name="limit">
			<option value="0"><?php echo _("Unlimited"); ?></option>
			<option value="25">25</option>
			<option value="50">50</option>
			<option value="100">100</option>
			<option value="500">500</option>
		</select>
	</td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td>&nbsp;</td>
	<td>
	        <input type="submit" value="<?php echo  _("Search");?>" />&nbsp;&nbsp;
	        <input type="reset" value="<?php echo  _("Reset Form");?>" />
	        <input type="hidden" name="action" value="search" />
	</td>
	<td colspan="2">&nbsp;</td>
</tr>
</table>
</form>
<?php echo $final_javascript; ?>

<?php if (isset($_REQUEST['search_object'])) { ?>
<br />
<form method="post" action="<?php echo conf('web_path'); ?>/playlist.php?action=add_dyn_song">
<?php echo _('Save Search As Track on'); ?>:
<?php show_playlist_dropdown(); ?>
<input type="submit" value="<?php echo _('Save'); ?>" /></form>
<?php } ?>
<br />

