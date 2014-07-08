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
<table class="tabledata" cellpadding="0" cellspacing="0" data-objecttype="share">
    <thead>
    <tr class="th-top">
            <th class="cel_object essential"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=share&sort=object', T_('Object'),'sort_share_object'); ?></th>
            <th class="cel_object_type optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=share&sort=object_type', T_('Object Type'),'sort_share_object_type'); ?></th>
            <th class="cel_user optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=share&sort=user', T_('User'),'sort_share_user'); ?></th>
            <th class="cel_creation_date optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=share&sort=creation_date', T_('Creation Date'),'sort_share_creation_date'); ?></th>
            <th class="cel_lastvisit_date optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=share&sort=lastvisit_date', T_('Last Visit'),'sort_share_lastvisit_date'); ?></th>
            <th class="cel_counter optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=share&sort=counter', T_('Counter'),'sort_share_counter'); ?></th>
            <th class="cel_max_counter optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=share&sort=max_counter', T_('Max Counter'),'sort_share_max_counter'); ?></th>
            <th class="cel_allow_stream optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=share&sort=allow_stream', T_('Allow Stream'),'sort_share_allow_stream'); ?></th>
            <th class="cel_allow_download optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=share&sort=allow_download', T_('Allow Download'),'sort_share_allow_download'); ?></th>
            <th class="cel_expire optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=share&sort=expire', T_('Expire Days'),'sort_share_expire'); ?></th>
            <th class="cel_public_url essential"><?php echo T_('Public Url'); ?></th>
            <th class="cel_action  essential"><?php echo T_('Actions'); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php
    foreach ($object_ids as $share_id) {
        $libitem = new Share($share_id);
        $libitem->format();
    ?>
    <tr id="share_<?php echo $libitem->id; ?>" class="<?php echo UI::flip_class(); ?>">
        <?php require AmpConfig::get('prefix') . '/templates/show_shared_object_row.inc.php'; ?>
    </tr>
    <?php } ?>
    </tbody>
</table>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/javascript/tabledata.js" language="javascript" type="text/javascript"></script>
<?php UI::show_box_bottom(); ?>
