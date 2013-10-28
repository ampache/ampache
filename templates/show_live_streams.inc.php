<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

$web_path = Config::get('web_path');

?>
<?php require Config::get('prefix') . '/templates/list_header.inc.php'; ?>
<table class="tabledata" cellpadding="0" cellspacing="0">
<colgroup>
  <col id="col_add" />
  <col id="col_streamname" />
  <col id="col_callsign" />
  <col id="col_frequency" />
  <col id="col_tag" />
  <col id="col_action" />
</colgroup>
<tr class="th-top">
    <th class="cel_add"><?php echo T_('Add'); ?></th>
    <th class="cel_streamname"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=name', T_('Name'),'live_stream_sort_name'); ?></th>
    <th class="cel_callsign"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=call_sign', T_('Callsign'),'live_stream_call_sign');  ?></th>
    <th class="cel_frequency"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=frequency', T_('Frequency'),'live_stream_frequency'); ?></th>
    <th class="cel_genre"><?php echo T_('Tag'); ?></th>
    <th class="cel_action"><?php echo T_('Action'); ?> </th>
</tr>
<?php
foreach ($object_ids as $radio_id) {
    $radio = new Radio($radio_id);
    $radio->format();
?>
<tr id="live_stream_<?php echo $radio->id; ?>" class="<?php echo UI::flip_class(); ?>">
    <?php require Config::get('prefix') . '/templates/show_live_stream_row.inc.php'; ?>
</tr>
<?php } //end foreach ($artists as $artist) ?>
<?php if (!count($object_ids)) { ?>
<tr>
    <td colspan="6"><span class="fatalerror"><?php echo T_('Not Enough Data'); ?></span></td>
</tr>
<?php } ?>
<tr class="th-bottom">
    <th class="cel_add"><?php echo T_('Add'); ?></th>
    <th class="cel_streamname"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=name', T_('Name'),'live_stream_sort_name_bottom'); ?></th>
    <th class="cel_callsign"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=call_sign', T_('Callsign'),'live_stream_call_sign_bottom');  ?></th>
    <th class="cel_frequency"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=frequency', T_('Frequency'),'live_stream_frequency_bottom'); ?></th>
    <th class="cel_genre"><?php echo T_('Tag'); ?></th>
    <th class="cel_action"><?php echo T_('Action'); ?> </th>
</tr>
</table>
<?php require Config::Get('prefix') . '/templates/list_header.inc.php'; ?>
