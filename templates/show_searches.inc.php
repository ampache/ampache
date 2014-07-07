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

?>
<?php if ($browse->get_show_header()) require AmpConfig::get('prefix') . '/templates/list_header.inc.php' ?>
<table class="tabledata" cellpadding="0" cellspacing="0" data-objecttype="smartplaylist">
    <thead>
        <tr class="th-top">
            <th class="cel_play essential"></th>
            <th class="cel_playlist essential"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=smartplaylist&sort=name', T_('Playlist Name'),'playlist_sort_name'); ?></th>
            <th class="cel_add essential"></th>
            <th class="cel_type optional"><?php echo T_('Type'); ?></th>
            <th class="cel_random optional"><?php echo T_('Random'); ?></th>
            <th class="cel_limit optional"><?php echo T_('Item Limit'); ?></th>
            <th class="cel_owner optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=smartplaylist&sort=user', T_('Owner'),'playlist_sort_owner'); ?></th>
            <th class="cel_action essential"><?php echo T_('Actions'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($object_ids as $playlist_id) {
            $libitem = new Search($playlist_id, 'song');
            $libitem->format();
        ?>
        <tr class="<?php echo UI::flip_class(); ?>" id="smartplaylist_row_<?php echo $libitem->id; ?>">
            <?php require AmpConfig::get('prefix') . '/templates/show_search_row.inc.php'; ?>
        </tr>
        <?php } // end foreach ($playlists as $playlist) ?>
        <?php if (!count($object_ids)) { ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td colspan="6"><span class="nodata"><?php echo T_('No smart playlist found'); ?></span></td>
        </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_play"></th>
            <th class="cel_playlist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=playlist&sort=name', T_('Playlist Name'),'playlist_sort_name_bottom'); ?></th>
            <th class="cel_add"></th>
            <th class="cel_type"><?php echo T_('Type'); ?></th>
            <th class="cel_random"><?php echo T_('Random'); ?></th>
            <th class="cel_limit"><?php echo T_('Item Limit'); ?></th>
            <th class="cel_owner"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=playlist&sort=user', T_('Owner'),'playlist_sort_owner_bottom'); ?></th>
            <th class="cel_action"><?php echo T_('Actions'); ?></th>
        </tr>
    </tfoot>
</table>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/javascript/tabledata.js" language="javascript" type="text/javascript"></script>
<?php if ($browse->get_show_header()) require AmpConfig::get('prefix') . '/templates/list_header.inc.php' ?>
