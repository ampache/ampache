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
?>
<?php UI::show_box_top(T_('Missing Albums'), 'info-box'); ?>
<table class="tabledata" cellpadding="0" cellspacing="0">
    <tr class="th-top">
        <th class="cel_album"><?php echo T_('Album'); ?></th>
        <th class="cel_artist"><?php echo T_('Artist'); ?></th>
        <th class="cel_year"><?php echo T_('Year'); ?></th>
        <th class="cel_user"><?php echo T_('User'); ?></th>
        <th class="cel_action"><?php echo T_('Actions'); ?></th>
    </tr>
    <?php
    foreach ($walbums as $walbum) {
    ?>
    <tr id="walbum_<?php echo $walbum->mbid; ?>" class="<?php echo UI::flip_class(); ?>">
        <?php require AmpConfig::get('prefix') . '/templates/show_wanted_album_row.inc.php'; ?>
    </tr>
    <?php } ?>
</table>
<?php UI::show_box_bottom(); ?>
