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

 7/16/05 Do it in smaller bar format - sigger

*/

?>
<form name="search" method="post" action="<?php echo conf('web_path'); ?>/search.php" enctype="multipart/form-data" style="Display:inline">
<table class="tabledata" cellspacing="0" cellpadding="3" border="0" style="clear:both;" width="100%">
<tr class="table-header">
	<td colspan="4"><b><?php echo  _("Search Ampache"); ?>...</b></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><input type="text" name="search_string" value="<?php echo  $_REQUEST['search_string']; ?>" /></td>
	<td>
		<select name="search_object[]">
			<option value="title"><?php echo _("Song Title"); ?></option>
			<option value="artist"><?php echo _("Artist"); ?></option>
			<option value="album"><?php echo _("Album"); ?></option>
			<option value="genre"><?php echo _("Song Genre"); ?></option>
			<option value="year"><?php echo _("Song Year"); ?></option>
			<option value="minbitrate"><?php echo _("Minimum Bitrate"); ?></option>
			<option value="filename"><?php echo _("Filename"); ?></option>
		</select>
	</td>
	<td>
		<input class="button" type="submit" value="<?php echo  _("Search"); ; ?>" />&nbsp;&nbsp;
		<input type="hidden" name="action" value="quick_search" />
		<input type="hidden" name="method" value="fuzzy" />
		<input type="hidden" name="object_type" value="song" />
	</td>
</tr>
</table>
</form>
<script language="JavaScript" type="text/javascript"> document.search.search_string.focus(); </script>
