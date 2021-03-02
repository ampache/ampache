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
 */

$thcount  = 8; ?>
<table class="tabledata">
    <thead>
        <tr class="th-top">
            <th class="cel_play"></th>
            <?php if (Art::is_enabled()) {
    ++$thcount; ?>
                <th class="cel_cover optional"><?php echo T_('Art'); ?></th>
            <?php
} ?>
            <th class="cel_artist"><?php echo T_('Artist'); ?></th>
            <th class="cel_add"></th>
            <th class="cel_songs"><?php echo T_('Songs');  ?></th>
            <th class="cel_albums"><?php echo T_('Albums'); ?></th>
            <th class="cel_time"><?php echo T_('Time'); ?></th>
            <th class="cel_tags"><?php echo T_('Tags'); ?></th>
        <?php if (AmpConfig::get('ratings')) {
        ++$thcount; ?>
            <th class="cel_rating"><?php echo T_('Rating'); ?></th>
        <?php
    } ?>
        <?php if (AmpConfig::get('userflags')) {
        ++$thcount; ?>
            <th class="cel_userflag"><?php echo T_('Fav.'); ?></th>
        <?php
    } ?>
            <th class="cel_action"> <?php echo T_('Action'); ?> </th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Cache the ratings we are going to use
        if (AmpConfig::get('ratings')) {
            Rating::build_cache('artist', $object_ids);
        }
        // Cache the userflags we are going to use
        if (AmpConfig::get('userflags')) {
            Userflag::build_cache('artist', $object_ids);
        }

        /* Foreach through every artist that has been passed to us */
        foreach ($object_ids as $artist_id) {
            $libitem = new Artist($artist_id);
            $libitem->format(); ?>
        <tr id="artist_<?php echo $libitem->id; ?>" class="<?php echo UI::flip_class(); ?>">
            <?php require AmpConfig::get('prefix') . UI::find_template('show_artist_row.inc.php'); ?>
        </tr>
        <?php
        } ?>
        <?php
        /* Foreach through every missing artist that has been passed to us */
        foreach ($missing_objects as $missing) { ?>
        <tr id="missing_artist_<?php echo $missing['mbid']; ?>" class="<?php echo UI::flip_class(); ?>">
            <td></td>
            <td colspan="<?php echo($thcount - 1); ?>"><a class="missing_album" href="<?php echo AmpConfig::get('web_path'); ?>/artists.php?action=show_missing&mbid=<?php echo $missing['mbid']; ?>" title="<?php echo scrub_out($missing['name']); ?>"><?php echo scrub_out($missing['name']); ?></a></td>
        </tr>
        <?php
        } ?>
        <?php if ((!$object_ids || !count($object_ids)) && (!$missing_objects || !count($missing_objects))) { ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td colspan="<?php echo $thcount; ?>"><span class="nodata"><?php echo T_('No similar artist found'); ?></span></td>
        </tr>
        <?php
        } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_play"></th>
            <?php if (Art::is_enabled()) { ?>
                <th class="cel_cover"><?php echo T_('Art'); ?></th>
            <?php
        } ?>
            <th class="cel_artist"><?php echo T_('Artist'); ?></th>
            <th class="cel_add"></th>
            <th class="cel_songs"> <?php echo T_('Songs');  ?> </th>
            <th class="cel_albums"> <?php echo T_('Albums'); ?> </th>
            <th class="cel_time"> <?php echo T_('Time'); ?> </th>
            <th class="cel_tags"><?php echo T_('Tags'); ?></th>
        <?php if (AmpConfig::get('ratings')) { ?>
            <th class="cel_rating"><?php echo T_('Rating'); ?></th>
        <?php
        } ?>
        <?php if (AmpConfig::get('userflags')) { ?>
            <th class="cel_userflag"><?php echo T_('Fav.'); ?></th>
        <?php
        } ?>
            <th class="cel_action"> <?php echo T_('Action'); ?> </th>
        </tr>
    </tfoot>
</table>
<?php UI::show_box_bottom(); ?>
