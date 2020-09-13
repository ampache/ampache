<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */ ?>
<?php UI::show_box_top(T_('Missing Albums'), 'info-box'); ?>
<table class="tabledata">
    <thead>
        <tr class="th-top">
            <th class="cel_album"><?php echo T_('Album'); ?></th>
            <th class="cel_artist"><?php echo T_('Artist'); ?></th>
            <th class="cel_year"><?php echo T_('Year'); ?></th>
            <th class="cel_user"><?php echo T_('User'); ?></th>
            <th class="cel_action"><?php echo T_('Actions'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($walbums) {
            foreach ($walbums as $libitem) { ?>
        <tr id="walbum_<?php echo $libitem->mbid; ?>" class="<?php echo UI::flip_class(); ?>">
            <?php require AmpConfig::get('prefix') . UI::find_template('show_wanted_album_row.inc.php'); ?>
        </tr>
        <?php
            }
        } ?>
        <?php if (!$walbums || !count($walbums)) { ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td colspan="<?php echo $thcount; ?>"><span class="nodata"><?php echo T_('No missing albums found'); ?></span></td>
        </tr>
        <?php
        } ?>
    </tbody>
</table>
<?php UI::show_box_bottom(); ?>
