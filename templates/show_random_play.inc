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


?>
<form id="random" method="post" enctype="multipart/form-data" action="<?php echo conf('web_path'); ?>/song.php">
<?php show_box_top(_('Play Random Selection')); ?>
<table>
<tr>
	<td><?php echo _('Item count'); ?></td>
	<td>
		<select name="random">
		<option value="1">1</option>
		<option value="5" selected="selected">5</option>
		<option value="10">10</option>
		<option value="20">20</option>
		<option value="30">30</option>
		<option value="50">50</option>
		<option value="100">100</option>
		<option value="500">500</option>
		<option value="1000">1000</option>
		<option value="-1"><?php echo _('All'); ?></option>
		</select>
	</td>
	<td rowspan="4" valign="top"><?php echo  _('From genre'); ?></td>
	<td rowspan="4">
	<?php show_genre_pulldown('genre','','5'); ?>
	</td>
</tr>
<tr>
	<td><?php echo _('Type'); ?></td>
	<td>
		<select name="random_type">
			<option value="normal"><?php echo _('Standard'); ?></option>
			<option value="unplayed"><?php echo _('Less Played'); ?></option>
			<option value="full_album"><?php echo _('Full Albums'); ?></option>
			<option value="full_artist"><?php echo _('Full Artist'); ?></option>
			<option value="length"><?php echo _('Minutes'); ?></option>
		</select>
	</td>
</tr>
<tr>
	<td nowrap="nowrap"><?php echo _('From catalog'); ?></td>
	<td>
	<?php show_catalog_pulldown('catalog',''); ?>
	</td>
</tr>
<tr>
	<td><?php echo _('Size Limit'); ?></td>
	<td>
		<select name="size_limit">
			<option value="0"><?php echo _('Unlimited'); ?></option>
			<option value="64">64MB</option>
			<option value="128">128MB</option>
			<option value="256">256MB</option>
			<option value="512">512MB</option>
			<option value="1024">1024MB</option>
		</select>
	</td>
</tr>
<tr>
	<td colspan="4">
		<input type="hidden" name="action" value="random" />
		<input class="button" type="button" value="<?php echo _('Play'); ?>" onclick="return SubmitToPage('random','<?php echo conf('web_path'); ?>/song.php?action=random&amp;method=stream');" />
		<?php if (batch_ok()) { ?>
			<input class="button" type="button" value="<?php echo _('Download'); ?>" onclick="return SubmitToPage('random','<?php echo conf('web_path'); ?>/song.php?action=random&amp;method=download');" />
		<?php } ?>
	</td>
</tr>
</table>
<?php show_box_bottom(); ?>
</form>
