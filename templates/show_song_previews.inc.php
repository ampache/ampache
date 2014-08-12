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
<table class="tabledata" cellpadding="0" cellspacing="0">
    <thead>
        <tr class="th-top">
            <?php if (AmpConfig::get('echonest_api_key')) { ?>
                <th class="cel_play"></th>
            <?php } ?>
            <th class="cel_song"><?php echo T_('Song Title'); ?></th>
            <?php if (AmpConfig::get('echonest_api_key')) { ?>
                <th class="cel_add"></th>
            <?php } ?>
            <th class="cel_artist"><?php echo T_('Artist'); ?></th>
            <th class="cel_album"><?php echo T_('Album'); ?></th>
            <th class="cel_track"><?php echo T_('Track'); ?></th>
            <th class="cel_disk"><?php echo T_('Disk'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($object_ids as $libitem) {
        ?>
        <tr id="song_preview_<?php echo $libitem->id; ?>" class="<?php echo UI::flip_class(); ?>">
            <?php require AmpConfig::get('prefix') . '/templates/show_song_preview_row.inc.php'; ?>
        </tr>
        <?php } ?>
    </tbody>
</table>
<?php UI::show_box_bottom(); ?>
