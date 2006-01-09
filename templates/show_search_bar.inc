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
<form name="search" method="post" action="<?php echo conf('web_path'); ?>/search.php" enctype="multipart/form-data" style="Display:inline">
<table class="tabledata" cellspacing="0" cellpadding="3" border="0" style="clear:both;" width="100%">
<tr class="table-header">
	<td colspan="4"><b><?php echo  _('Search Ampache'); ?>...</b></td>
</tr>
<tr class="<?php echo flip_class(); ?>">
	<td><input type="text" name="search_string" value="<?php echo  scrub_out($_REQUEST['search_string']); ?>" /></td>
	<td>
		<input class="button" type="submit" value="<?php echo  _('Search'); ; ?>" />&nbsp;&nbsp;
		<input type="hidden" name="action" value="quick_search" />
		<input type="hidden" name="method" value="fuzzy" />
		<input type="hidden" name="object_type" value="song" />
		<input type="hidden" name="search_object[]" value="all" />
	</td>
</tr>
</table>
</form>
<script language="JavaScript" type="text/javascript"> document.search.search_string.focus(); </script>
