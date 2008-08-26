<?php
/*

 Copyright (c) Ampache.org
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
<?php show_box_top(_('Play Random Selection')); ?>
<form id="random" method="post" enctype="multipart/form-data" action="<?php echo Config::get('web_path'); ?>/random.php?action=get_advanced">
<table class="table-data" cellspacing="0" cellpadding="3">
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
	<td rowspan="5" valign="top"><?php echo  _('From genre'); ?></td>
	<td rowspan="5">
	<?php show_genre_select('genre[]','','6'); ?>
	</td>
</tr>
<tr>
	<td><?php echo _('Length'); ?></td>
	<td>
		<select name="length">
			<option value="0"><?php echo _('Unlimited'); ?></option>
			<option value="15"><?php printf(ngettext('%d minute','%d minutes',15), "15"); ?></option>
			<option value="30"><?php printf(ngettext('%d minute','%d minutes',30), "30"); ?></option>
			<option value="60"><?php printf(ngettext('%d hour','%d hours',1), "1"); ?></option>
			<option value="120"><?php printf(ngettext('%d hour','%d hours',2), "2"); ?></option>
			<option value="240"><?php printf(ngettext('%d hour','%d hours',4), "4"); ?></option>
			<option value="480"><?php printf(ngettext('%d hour','%d hours',8), "8"); ?></option>
			<option value="960"><?php printf(ngettext('%d hour','%d hours',16), "16"); ?></option>
		</select>
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
			<?php if (Config::get('ratings')) { ?>
			<option value="high_rating"><?php echo _('Highest Rated'); ?></option>
			<?php } ?>
		</select>
	</td>
</tr>
<tr>
	<td nowrap="nowrap"><?php echo _('From catalog'); ?></td>
	<td>
	<?php show_catalog_select('catalog',''); ?>
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
</table>
<div class="formValidation">
	<input type="submit" value="<?php echo _('Enqueue'); ?>" />
</div>
</form>
<?php show_box_bottom(); ?>
<div id="browse">
<?php
	if (is_array($object_ids)) { 
		Browse::reset_filters(); 
		Browse::set_type('song');
		Browse::save_objects($object_ids); 
		Browse::show_objects(); 
		echo Ajax::observe('window','load',Ajax::action('?action=refresh_rightbar','playlist_refresh_load'));
	}  
?>	
</div>
