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
$thcount  = 7;
$is_table = $browse->is_grid_view();
//mashup and grid view need different css
$cel_cover   = ($is_table) ? "cel_cover" : 'grid_cover';
$cel_flag    = ($is_table) ? "cel_userflag" : 'grid_userflag';
$cel_time    = ($is_table) ? "cel_time" : 'grid_time';
$cel_counter = ($is_table) ? "cel_counter" : 'grid_counter'; ?>
<?php if ($browse->is_show_header()) {
    require AmpConfig::get('prefix') . UI::find_template('list_header.inc.php');
} ?>
<table class="tabledata <?php echo $browse->get_css_class() ?>" data-objecttype="podcast_episode">
    <thead>
        <tr class="th-top">
            <th class="cel_play essential"></th>
            <th class="cel_title essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=title', T_('Title'), 'podcast_episode_sort_title'); ?></th>
            <th class="cel_add essential"></th>
            <th class="cel_podcast optional"><?php echo T_('Podcast'); ?></th>
            <th class="<?php echo $cel_time; ?> optional"><?php echo T_('Time'); ?></th>
            <?php if (AmpConfig::get('show_played_times')) { ?>
                <th class="<?php echo $cel_counter; ?> optional"><?php echo T_('# Played'); ?></th>
                <?php
            } ?>
            <th class="cel_pubdate optional"><?php echo T_('Publication Date'); ?></th>
            <th class="cel_state optional"><?php echo T_('State'); ?></th>
            <?php if (User::is_registered()) { ?>
                <?php if (AmpConfig::get('ratings')) {
                ++$thcount; ?>
                    <th class="cel_rating optional"><?php echo T_('Rating'); ?></th>
                <?php
            } ?>
                <?php if (AmpConfig::get('userflags')) {
                ++$thcount; ?>
                    <th class="<?php echo $cel_flag; ?> optional"><?php echo T_('Fav.'); ?></th>
                <?php
            } ?>
            <?php
} ?>
            <th class="cel_action essential"><?php echo T_('Actions'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (AmpConfig::get('ratings')) {
            Rating::build_cache('podcast_episode', $object_ids);
        }
        if (AmpConfig::get('userflags')) {
            Userflag::build_cache('podcast_episode', $object_ids);
        }

        foreach ($object_ids as $episode_id) {
            $libitem = new Podcast_Episode($episode_id);
            $libitem->format(); ?>
        <tr id="podcast_episode_<?php echo $libitem->id; ?>" class="<?php echo UI::flip_class(); ?>">
            <?php require AmpConfig::get('prefix') . UI::find_template('show_podcast_episode_row.inc.php'); ?>
        </tr>
        <?php
        } ?>
        <?php if (!count($object_ids)) { ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td colspan="<?php echo $thcount; ?>"><span class="nodata"><?php echo T_('No podcast episode found'); ?></span></td>
        </tr>
        <?php
        } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_play"></th>
        <?php if (Art::is_enabled()) { ?>
            <th class="<?php echo $cel_cover; ?>"><?php echo T_('Art'); ?></th>
        <?php
        } ?>
            <th class="cel_title"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=title', T_('Title'), 'podcast_episode_sort_title_bottom'); ?></th>
            <th class="cel_add"></th>
            <th class="cel_podcast"><?php echo T_('Podcast'); ?></th>
            <th class="<?php echo $cel_time; ?>"><?php echo T_('Time'); ?></th>
            <?php if (AmpConfig::get('show_played_times')) { ?>
                <th class="<?php echo $cel_counter; ?> optional"><?php echo T_('# Played'); ?></th>
                <?php
            } ?>
            <th class="cel_pubdate"><?php echo T_('Publication Date'); ?></th>
            <th class="cel_state"><?php echo T_('State'); ?></th>
            <?php if (User::is_registered()) { ?>
                <?php if (AmpConfig::get('ratings')) { ?>
                    <th class="cel_rating"><?php echo T_('Rating'); ?></th>
                <?php
            } ?>
                <?php if (AmpConfig::get('userflags')) { ?>
                    <th class="<?php echo $cel_flag; ?>"><?php echo T_('Fav.'); ?></th>
                <?php
            } ?>
            <?php
        } ?>
            <th class="cel_action"><?php echo T_('Actions'); ?></th>
        </tr>
    <tfoot>
</table>
<?php show_table_render(); ?>
<?php if ($browse->is_show_header()) {
            require AmpConfig::get('prefix') . UI::find_template('list_header.inc.php');
        } ?>
