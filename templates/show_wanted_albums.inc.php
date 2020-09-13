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
<table class="tabledata <?php echo $browse->get_css_class() ?>" data-objecttype="wanted">
    <thead>
        <tr class="th-top">
            <th class="cel_album essential"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=wanted&sort=name', T_('Album'), 'sort_wanted_album'); ?></th>
            <th class="cel_artist essential"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=wanted&sort=artist', T_('Artist'), 'sort_wanted_artist'); ?></th>
            <th class="cel_year optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=wanted&sort=year', T_('Year'), 'sort_wanted_year'); ?></th>
            <th class="cel_user optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=wanted&sort=user', T_('User'), 'sort_wanted_user'); ?></th>
            <th class="cel_action essential"><?php echo T_('Actions'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($object_ids as $wanted_id) {
            $libitem = new Wanted($wanted_id);
            $libitem->format(); ?>
        <tr id="walbum_<?php echo $libitem->mbid; ?>" class="<?php echo UI::flip_class(); ?>">
            <?php require AmpConfig::get('prefix') . UI::find_template('show_wanted_album_row.inc.php'); ?>
        </tr>
        <?php
        } ?>
    </tbody>
</table>
<?php UI::show_box_bottom(); ?>
