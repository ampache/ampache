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
<table class="tabledata" cellpadding="0" cellspacing="0" data-objecttype="wanted">
    <thead>
        <tr class="th-top">
            <th class="cel_album essential"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=wanted&sort=name', T_('Album'),'sort_wanted_album'); ?></th>
            <th class="cel_artist essential"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=wanted&sort=artist', T_('Artist'),'sort_wanted_artist'); ?></th>
            <th class="cel_year optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=wanted&sort=year', T_('Year'),'sort_wanted_year'); ?></th>
            <th class="cel_user optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=wanted&sort=user', T_('User'),'sort_wanted_user'); ?></th>
            <th class="cel_action essential"><?php echo T_('Actions'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($object_ids as $wanted_id) {
            $libitem = new Wanted($wanted_id);
            $libitem->format();
        ?>
        <tr id="walbum_<?php echo $libitem->mbid; ?>" class="<?php echo UI::flip_class(); ?>">
            <?php require AmpConfig::get('prefix') . '/templates/show_wanted_album_row.inc.php'; ?>
        </tr>
        <?php } ?>
    </tbody>
</table>
<script src="<?php echo AmpConfig::get('web_path'); ?>/lib/javascript/tabledata.js" language="javascript" type="text/javascript"></script>
<?php UI::show_box_bottom(); ?>
