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
$thcount  = 5;
$is_table = $browse->is_grid_view();
//mashup and grid view need different css
$cel_cover = ($is_table) ? "cel_cover" : 'grid_cover';
$cel_flag  = ($is_table) ? "cel_userflag" : 'grid_userflag'; ?>
<div id="information_actions">
    <ul>
        <?php if (Access::check('interface', 75)) { ?>
        <li>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/podcast.php?action=show_create"><?php echo UI::get_icon('add', T_('Add')); ?> <?php echo T_('Subscribe to Podcast'); ?></a>
        </li>
        <?php
} ?>
    </ul>
</div>
<?php if ($browse->is_show_header()) {
    require AmpConfig::get('prefix') . UI::find_template('list_header.inc.php');
} ?>
<table class="tabledata <?php echo $browse->get_css_class() ?>" data-objecttype="podcast">
    <thead>
        <tr class="th-top">
            <th class="cel_play essential"></th>
        <?php if (Art::is_enabled()) {
    ++$thcount; ?>
            <th class="<?php echo $cel_cover; ?>"><?php echo T_('Art'); ?></th>
        <?php
} ?>
            <th class="cel_title essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=title', T_('Title'), 'podcast_sort_title'); ?></th>
            <th class="cel_episodes optional"><?php echo T_('Episodes'); ?></th>
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
            Rating::build_cache('podcast', $object_ids);
        }
        if (AmpConfig::get('userflags')) {
            Userflag::build_cache('podcast', $object_ids);
        }

        foreach ($object_ids as $podcast_id) {
            $libitem = new Podcast($podcast_id);
            $libitem->format(); ?>
        <tr id="podcast_<?php echo $libitem->id; ?>" class="<?php echo UI::flip_class(); ?>">
            <?php require AmpConfig::get('prefix') . UI::find_template('show_podcast_row.inc.php'); ?>
        </tr>
        <?php
        } ?>
        <?php if (!count($object_ids)) { ?>
        <tr class="<?php echo UI::flip_class(); ?>">
            <td colspan="<?php echo $thcount; ?>"><span class="nodata"><?php echo T_('No podcast found'); ?></span></td>
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
            <th class="cel_title"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=title', T_('Title'), 'podcast_sort_title_bottom'); ?></th>
            <th class="cel_episodes"><?php echo T_('Episodes'); ?></th>
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
