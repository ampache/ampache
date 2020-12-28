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

$browse = new Browse();
$browse->set_type($object_type);

UI::show_box_top($podcast->f_title, 'info-box'); ?>
<div class="item_right_info">
    <?php
    $thumb = UI::is_grid_view('podcast') ? 2 : 11;
    Art::display('podcast', $podcast->id, $podcast->f_title, $thumb); ?>
    <?php if ($podcast->description) { ?>
    <div id="item_summary">
        <?php echo $podcast->description; ?>
    </div>
    <?php
    } ?>
</div>
<?php if (User::is_registered()) { ?>
    <?php
    if (AmpConfig::get('ratings')) { ?>
    <div id="rating_<?php echo (int) ($podcast->id); ?>_podcast" style="display:inline;">
        <?php show_rating($podcast->id, 'podcast'); ?>
    </div>
    <?php
    } ?>
    <?php if (AmpConfig::get('userflags')) { ?>
    <div style="display:table-cell;" id="userflag_<?php echo $podcast->id; ?>_podcast">
            <?php Userflag::show($podcast->id, 'podcast'); ?>
    </div>
    <?php
    } ?>
<?php
    } ?>
<div id="information_actions">
    <h3><?php echo T_('Actions'); ?>:</h3>
    <ul>
        <?php if (AmpConfig::get('directplay')) { ?>
        <li>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=podcast&object_id=' . $podcast->id, 'play', T_('Play All'), 'directplay_full_' . $podcast->id); ?>
            <?php echo Ajax::text('?page=stream&action=directplay&object_type=podcast&object_id=' . $podcast->id, T_('Play All'), 'directplay_full_text_' . $podcast->id); ?>
        </li>
        <?php
    } ?>
        <?php if (Stream_Playlist::check_autoplay_append()) { ?>
        <li>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=podcast&object_id=' . $podcast->id . '&append=true', 'play_add', T_('Play All Last'), 'addplay_podcast_' . $podcast->id); ?>
            <?php echo Ajax::text('?page=stream&action=directplay&object_type=podcast&object_id=' . $podcast->id . '&append=true', T_('Play All Last'), 'addplay_podcast_text_' . $podcast->id); ?>
        </li>
        <?php
    } ?>
        <?php if (Access::check('interface', 50)) { ?>
        <?php if (AmpConfig::get('statistical_graphs') && is_dir(AmpConfig::get('prefix') . '/lib/vendor/szymach/c-pchart/src/Chart/')) { ?>
            <li>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/stats.php?action=graph&object_type=podcast&object_id=<?php echo $podcast->id; ?>"><?php echo UI::get_icon('statistics', T_('Graphs')); ?></a>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/stats.php?action=graph&object_type=podcast&object_id=<?php echo $podcast->id; ?>"><?php echo T_('Graphs'); ?></a>
            </li>
        <?php
        } ?>
    <?php if (AmpConfig::get('use_rss')) {
            ?>
        <li>
            <?php echo Ampache_RSS::get_display('podcast', -1, T_('RSS Feed'), array('object_type' => 'podcast', 'object_id' => $podcast->id)); ?>
        </li>
        <?php
        } ?>
        <li>
        <?php echo " <a href=\"" . $podcast->website . "\" target=\"_blank\">" . UI::get_icon('link', T_('Website')) . "</a>"; ?>
        <?php echo " <a href=\"" . $podcast->website . "\" target=\"_blank\">" . T_('Website') . "</a>"; ?>
        </li>
        <li>
            <a id="<?php echo 'edit_podcast_' . $podcast->id ?>" onclick="showEditDialog('podcast_row', '<?php echo $podcast->id ?>', '<?php echo 'edit_podcast_' . $podcast->id ?>', '<?php echo T_('Podcast Edit') ?>', '')">
                <?php echo UI::get_icon('edit', T_('Edit')); ?>
            </a>
            <a id="<?php echo 'edit_podcast_' . $podcast->id ?>" onclick="showEditDialog('podcast_row', '<?php echo $podcast->id ?>', '<?php echo 'edit_podcast_' . $podcast->id ?>', '<?php echo T_('Podcast Edit') ?>', '')">
                <?php echo T_('Edit Podcast'); ?>
            </a>
        </li>
        <li>
            <?php echo Ajax::button('?page=podcast&action=sync&podcast_id=' . $podcast->id, 'file_refresh', T_('Sync'), 'sync_podcast_' . $podcast->id); ?>
            <?php echo Ajax::text('?page=podcast&action=sync&podcast_id=' . $podcast->id, T_('Sync'), 'sync_podcast_text_' . $podcast->id); ?>
        </li>
        <?php
    } ?>
        <?php if (Access::check('interface', 75)) { ?>
        <li>
            <a id="<?php echo 'delete_podcast_' . $podcast->id ?>" href="<?php echo AmpConfig::get('web_path'); ?>/podcast.php?action=delete&podcast_id=<?php echo $podcast->id; ?>">
                <?php echo UI::get_icon('delete', T_('Delete')); ?> &nbsp;<?php echo T_('Delete'); ?>
            </a>
        </li>
        <?php
    } ?>
    </ul>
</div>
<?php UI::show_box_bottom(); ?>
<div class="tabs_wrapper">
    <div id="tabs_container">
        <ul id="tabs">
            <li class="tab_active"><a href="#episodes"><?php echo T_('Episodes'); ?></a></li>
            <!-- Needed to avoid the 'only one' bug -->
            <li></li>
        </ul>
    </div>
    <div id="tabs_content">
        <div id="episodes" class="tab_content" style="display: block;">
<?php
    $browse->show_objects($object_ids, true);
    $browse->store(); ?>
        </div>
    </div>
</div>
