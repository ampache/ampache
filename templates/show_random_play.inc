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
<form name="random" method="post" enctype="multipart/form-data" action="<?php echo conf('web_path'); ?>/song.php">
<table class="border" border="0" cellpadding="3" cellspacing="1"><!-- Random Play Table -->
<tr class="table-header">
	<td colspan="4"><?php echo _("Play Random Selection"); ?></td>
</tr>
<tr class="even">
<td>
	<table border="0">
		<tr class="even">
			<td><?php echo _("Item count"); ?></td>
			<td>
				<select name="random">
				<option value="1">1</option>
				<option value="5">5</option>
				<option value="10">10</option>
				<option value="20">20</option>
				<option value="30">30</option>
				<option value="50">50</option>
				<option value="100">100</option>
				<option value="500">500</option>
				<option value="1000">1000</option>
				<option value="-1"><?php echo _("All"); ?></option>
				</select>
			</td>
			<td rowspan="3" valign="top"><?php echo  _("From genre"); ?></td>
			<td rowspan="3">
			<?php show_genre_pulldown('genre','','5'); ?>
			</td>
		</tr>
		<tr class="even">
			<td colspan="2">
				Type:
				<select name="random_type">
					<option value="normal"><?php echo _("Standard"); ?></option>
					<option value="unplayed"><?php echo _("Favor Unplayed"); ?></option>
					<option value="full_album"><?php echo _("Full Albums"); ?></option>
					<option value="full_artist"><?php echo _("Full Artist"); ?></option>
				</select>
			</td>
		</tr>
		<tr class="even">
			<td nowrap="nowrap"><?php echo _("from catalog"); ?></td>
			<td>
			<?php show_catalog_pulldown('catalog',''); ?>
			</td>
		</tr>
		<tr>
			<td colspan="4">
				<input type="hidden" name="aaction" value="Play!" />
				<input type="hidden" name="action" value="m3u" />
				<input class="button" type="submit" name="aaction" value="<?php echo _("Play Random Songs"); ?>" />
			</td>
		</tr>
	</table>
</td>
</tr>
</table><!-- End Random Play Table -->
</form>
