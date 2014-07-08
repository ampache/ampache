<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
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
?>
<?php if ($browse->get_show_header()) require AmpConfig::get('prefix') . '/templates/list_header.inc.php'; ?>
<table class="tabledata" cellpadding="0" cellspacing="0" data-objecttype="live_stream">
    <thead>
        <tr class="th-top">
            <th class="cel_play essential"></th>
            <th class="cel_streamname essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=name', T_('Name'),'live_stream_sort_name'); ?></th>
            <th class="cel_streamurl optional"><?php echo T_('Stream URL'); ?></th>
            <th class="cel_codec optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=codec', T_('Codec'),'live_stream_codec');  ?></th>
            <th class="cel_action essential"><?php echo T_('Action'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($object_ids as $radio_id) {
            $libitem = new Live_Stream($radio_id);
            $libitem->format();
        ?>
        <tr id="live_stream_<?php echo $libitem->id; ?>" class="<?php echo UI::flip_class(); ?>">
            <?php require AmpConfig::get('prefix') . '/templates/show_live_stream_row.inc.php'; ?>
        </tr>
        <?php } //end foreach ($artists as $artist) ?>
        <?php if (!count($object_ids)) { ?>
        <tr>
            <td colspan="6"><span class="nodata"><?php echo T_('No live stream found'); ?></span></td>
        </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_play"></th>
            <th class="cel_streamname"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=name', T_('Name'),'live_stream_sort_name'); ?></th>
            <th class="cel_streamurl"><?php echo T_('Stream URL'); ?></th>
            <th class="cel_codec"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=codec', T_('Codec'),'live_stream_codec_bottom');  ?></th>
            <th class="cel_action"><?php echo T_('Action'); ?> </th>
        </tr>
    </tfoot>
</table>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/javascript/tabledata.js" language="javascript" type="text/javascript"></script>
<?php if ($browse->get_show_header()) require AmpConfig::Get('prefix') . '/templates/list_header.inc.php'; ?>
