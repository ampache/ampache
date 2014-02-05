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

$tags_list = Tag::get_display(Tag::get_tags());
?>
<?php if ($browse->get_show_header()) require AmpConfig::get('prefix') . '/templates/list_header.inc.php' ?>
<table class="tabledata" cellpadding="0" cellspacing="0">
    <tr class="th-top">
        <th class="cel_play"></th>
        <th class="cel_name"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=broadcast&sort=name', T_('Name'),'broadcast_sort_name'); ?></th>
        <th class="cel_genre"><?php echo T_('Genre'); ?></th>
        <th class="cel_started"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=broadcast&sort=started', T_('Started'),'broadcast_sort_started'); ?></th>
        <th class="cel_listeners"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=broadcast&sort=listeners', T_('Listeners'),'broadcast_sort_listeners'); ?></th>
        <th class="cel_action"><?php echo T_('Actions'); ?></th>
    </tr>
    <?php
    foreach ($object_ids as $broadcast_id) {
        $broadcast = new Broadcast($broadcast_id);
        $broadcast->format();
    ?>
    <tr class="<?php echo UI::flip_class(); ?>" id="channel_row_<?php echo $channel->id; ?>">
        <?php require AmpConfig::get('prefix') . '/templates/show_broadcast_row.inc.php'; ?>
    </tr>
    <?php } ?>
    <?php if (!count($object_ids)) { ?>
    <tr class="<?php echo UI::flip_class(); ?>">
        <td colspan="6"><span class="nodata"><?php echo T_('No broadcast found'); ?></span></td>
    </tr>
    <?php } ?>
</table>
<?php if ($browse->get_show_header()) require AmpConfig::get('prefix') . '/templates/list_header.inc.php' ?>
