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

$web_path = AmpConfig::get('web_path');
$thcount = 5;
?>
<?php if ($browse->get_show_header()) require AmpConfig::get('prefix') . '/templates/list_header.inc.php'; ?>
<table class="tabledata" cellpadding="0" cellspacing="0">
    <tr class="th-top">
        <?php if (AmpConfig::get('directplay')) { ++$thcount; ?>
        <th class="cel_directplay"><?php echo T_('Play'); ?></th>
    <?php } ?>
        <th class="cel_add"><?php echo T_('Add'); ?></th>
        <th class="cel_streamname"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=name', T_('Name'),'live_stream_sort_name'); ?></th>
        <th class="cel_streamurl"><?php echo T_('Stream URL'); ?></th>
        <th class="cel_codec"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=codec', T_('Codec'),'live_stream_codec');  ?></th>
        <th class="cel_action"><?php echo T_('Action'); ?></th>
    </tr>
    <?php
    foreach ($object_ids as $radio_id) {
        $radio = new Radio($radio_id);
        $radio->format();
    ?>
    <tr id="live_stream_<?php echo $radio->id; ?>" class="<?php echo UI::flip_class(); ?>">
        <?php require AmpConfig::get('prefix') . '/templates/show_live_stream_row.inc.php'; ?>
    </tr>
    <?php } //end foreach ($artists as $artist) ?>
    <?php if (!count($object_ids)) { ?>
    <tr>
        <td colspan="<?php echo $thcount; ?>"><span class="nodata"><?php echo T_('No live stream found'); ?></span></td>
    </tr>
    <?php } ?>
    <tr class="th-bottom">
        <?php if (AmpConfig::get('directplay')) { ?>
        <th class="cel_directplay"><?php echo T_('Play'); ?></th>
    <?php } ?>
        <th class="cel_add"><?php echo T_('Add'); ?></th>
        <th class="cel_streamname"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=name', T_('Name'),'live_stream_sort_name_bottom'); ?></th>
        <th class="cel_streamurl"><?php echo T_('Stream URL'); ?></th>
        <th class="cel_codec"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=codec', T_('Codec'),'live_stream_codec_bottom');  ?></th>
        <th class="cel_action"><?php echo T_('Action'); ?> </th>
    </tr>
</table>
<?php if ($browse->get_show_header()) require AmpConfig::Get('prefix') . '/templates/list_header.inc.php'; ?>
