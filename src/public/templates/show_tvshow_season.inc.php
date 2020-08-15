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

$web_path = AmpConfig::get('web_path'); ?>
<?php
$browse = new Browse();
$browse->set_type($object_type);

UI::show_box_top($season->f_name . ' - ' . $season->f_tvshow_link, 'info-box'); ?>
<div class="item_right_info">
    <?php
    Art::display('tvshow_season', $season->id, $season->f_name, 6); ?>
</div>
<?php if (User::is_registered()) { ?>
    <?php
    if (AmpConfig::get('ratings')) { ?>
    <div id="rating_<?php echo (int) ($season->id); ?>_tvshow_season" style="display:inline;">
        <?php show_rating($season->id, 'tvshow_season'); ?>
    </div>
    <?php
    } ?>
    <?php if (AmpConfig::get('userflags')) { ?>
    <div style="display:table-cell;" id="userflag_<?php echo $season->id; ?>_tvshow_season">
            <?php Userflag::show($season->id, 'tvshow_season'); ?>
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
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=tvshow_season&object_id=' . $season->id, 'play', T_('Play All'), 'directplay_full_' . $season->id); ?>
            <?php echo Ajax::text('?page=stream&action=directplay&object_type=tvshow_season&object_id=' . $season->id, T_('Play All'), 'directplay_full_text_' . $season->id); ?>
        </li>
        <?php
    } ?>
        <?php if (Stream_Playlist::check_autoplay_append()) { ?>
        <li>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=season&object_id=' . $season->id . '&append=true', 'play_add', T_('Play All Last'), 'addplay_season_' . $season->id); ?>
            <?php echo Ajax::text('?page=stream&action=directplay&object_type=season&object_id=' . $season->id . '&append=true', T_('Play All Last'), 'addplay_season_text_' . $season->id); ?>
        </li>
        <?php
    } ?>
        <?php if (Access::check('interface', 50)) { ?>
        <li>
            <a id="<?php echo 'edit_tvshow_season_' . $season->id ?>" onclick="showEditDialog('tvshow_season_row', '<?php echo $season->id ?>', '<?php echo 'edit_tvshow_season_' . $season->id ?>', '<?php echo T_('Season Edit') ?>', '')">
                <?php echo UI::get_icon('edit', T_('Edit')); ?>
            </a>
            <a id="<?php echo 'edit_tvshow_season_' . $season->id ?>" onclick="showEditDialog('tvshow_season_row', '<?php echo $season->id ?>', '<?php echo 'edit_tvshow_season_' . $season->id ?>', '<?php echo T_('Season Edit') ?>', '')">
                <?php echo T_('Edit Season'); ?>
            </a>
        </li>
        <?php
    } ?>
        <?php if (Catalog::can_remove($season)) { ?>
        <li>
            <a id="<?php echo 'delete_tvshow_season_' . $season->id ?>" href="<?php echo AmpConfig::get('web_path'); ?>/tvshow_seasons.php?action=delete&tvshow_season_id=<?php echo $season->id; ?>">
                <?php echo UI::get_icon('delete', T_('Delete')); ?> <?php echo T_('Delete'); ?>
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
