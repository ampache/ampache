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
<table class="tabledata" cellspacing="0" cellpadding="0">
<tr>
	<td colspan="6">
	<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
	</td>
</tr>
<tr class="table-header">
	<th><?php echo _('Add'); ?></th>
	<th><?php echo Ajax::text('?page=browse&action=set_sort&sort=name',_('Name'),'live_stream_sort_name'); ?></th>
	<th><?php echo Ajax::text('?page=browse&action=set_sort&sort=call_sign',_('Callsign'),'live_stream_call_sign');  ?></th>
	<th><?php echo Ajax::text('?page=browse&action=set_sort&sort=frequency',_('Frequency'),'live_stream_frequency'); ?></th>
	<th><?php echo _('Genre'); ?></th> 
	<th><?php echo _('Action'); ?> </th>
</tr>
<?php 
foreach ($object_ids as $radio_id) { 
	$radio = new Radio($radio_id); 
	$radio->format(); 
?>
<tr id="live_stream_<?php echo $radio->id; ?>" class="<?php echo flip_class(); ?>">
	<?php require Config::get('prefix') . '/templates/show_live_stream_row.inc.php'; ?>
</tr>
<?php } //end foreach ($artists as $artist) ?>
<tr>
	<td colspan="6">
	<?php require Config::Get('prefix') . '/templates/list_header.inc.php'; ?>
	</td>
</tr>
</table>
