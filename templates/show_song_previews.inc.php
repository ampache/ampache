<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
?>
<table class="tabledata" cellpadding="0" cellspacing="0">
    <thead>
        <tr class="th-top">
            <?php if (AmpConfig::get('echonest_api_key')) {
    ?>
                <th class="cel_play"></th>
            <?php
} ?>
            <th class="cel_song"><?php echo T_('Song Title'); ?></th>
            <?php if (AmpConfig::get('echonest_api_key')) {
        ?>
                <th class="cel_add"></th>
            <?php
    } ?>
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
            <?php require AmpConfig::get('prefix') . UI::find_template('show_song_preview_row.inc.php'); ?>
        </tr>
        <?php
        } ?>
    </tbody>
</table>
<?php UI::show_box_bottom(); ?>
