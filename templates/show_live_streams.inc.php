<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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

$web_path = Config::get('web_path');

?>
<table class="tabledata" cellspacing="0" cellpadding="0" border="0">
<tr class="table-header" align="center">
	<td colspan="5">
	<?php if ($GLOBALS['view']->offset_limit) { require Config::get('prefix') . '/templates/list_header.inc'; } ?>
	</td>
</tr>
<tr class="table-header">
	<th><?php echo _('Add'); ?></th>
	<th><?php echo _('Name'); ?></th>
	<th><?php echo _('Callsign');  ?></th>
	<th><?php echo _('Frequency'); ?></th>
	<th><?php echo _('Genre'); ?></th> 
	<th><?php echo _('Action'); ?> </th>
</tr>
<?php 
foreach ($object_ids as $radio_id) { 
	$radio = new Radio($radio_id); 
	$radio->format(); 
?>
<tr id="radio_<?php echo $radio->id; ?>" class="<?php echo flip_class(); ?>">
	<?php require Config::get('prefix') . '/templates/show_live_stream_row.inc.php'; ?>
</tr>
<?php } //end foreach ($artists as $artist) ?>
<tr class="even" align="center">
	<td colspan="4">
	<?php if ($view->offset_limit) { require (conf('prefix') . "/templates/list_header.inc"); } ?>
	</td>
</tr>
</table>
