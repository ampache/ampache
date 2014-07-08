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

$web_path = AmpConfig::get('web_path');
?>
<?php
$browse = new Browse();
$browse->set_type($object_type);

UI::show_box_top($season->f_name . ' - ' . $season->f_tvshow_link, 'info-box');
?>
<div class="item_right_info">
    <?php
    Art::display('tvshow_season', $season->id, $season->f_name, 6);
    ?>
</div>
<?php
if (AmpConfig::get('ratings')) {
?>
<div id="rating_<?php echo intval($season->id); ?>_tvshow_season" style="display:inline;">
    <?php show_rating($season->id, 'tvshow_season'); ?>
</div>
<?php } ?>
<?php if (AmpConfig::get('userflags')) { ?>
<div style="display:table-cell;" id="userflag_<?php echo $season->id; ?>_tvshow_season">
        <?php Userflag::show($season->id,'tvshow_season'); ?>
</div>
<?php } ?>
<div id="information_actions">
    <h3><?php echo T_('Actions'); ?>:</h3>
    <ul>
        <?php if (AmpConfig::get('directplay')) { ?>
        <li>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=tvshow_season&object_id=' . $season->id,'play', T_('Play all'),'directplay_full_' . $season->id); ?>
            <?php echo Ajax::text('?page=stream&action=directplay&object_type=tvshow_season&object_id=' . $season->id, T_('Play all'),'directplay_full_text_' . $season->id); ?>
        </li>
        <?php } ?>
        <?php if (Stream_Playlist::check_autoplay_append()) { ?>
        <li>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=season&object_id=' . $season->id . '&append=true','play_add', T_('Play all last'),'addplay_season_' . $season->id); ?>
            <?php echo Ajax::text('?page=stream&action=directplay&object_type=season&object_id=' . $season->id . '&append=true', T_('Play all last'),'addplay_season_text_' . $season->id); ?>
        </li>
        <?php } ?>
        <?php if (Access::check('interface','50')) { ?>
            <a id="<?php echo 'edit_tvshow_season_'.$season->id ?>" onclick="showEditDialog('tvshow_season_row', '<?php echo $season->id ?>', '<?php echo 'edit_tvshow_season_'.$season->id ?>', '<?php echo T_('Season edit') ?>', '')">
                <?php echo UI::get_icon('edit', T_('Edit')); ?>
            </a>
            <a id="<?php echo 'edit_tvshow_season_'.$season->id ?>" onclick="showEditDialog('tvshow_season_row', '<?php echo $season->id ?>', '<?php echo 'edit_tvshow_season_'.$season->id ?>', '<?php echo T_('Season edit') ?>', '')">
                <?php echo T_('Edit Season'); ?>
            </a>
        <?php } ?>
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
    $browse->store();
?>
        </div>
    </div>
</div>
