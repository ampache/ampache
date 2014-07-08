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

UI::show_box_top($tvshow->f_name, 'info-box');
?>
<div class="item_right_info">
    <?php
    Art::display('tvshow', $tvshow->id, $tvshow->f_name, 6);
    ?>
    <?php if ($tvshow->summary) { ?>
    <div id="item_summary">
        <?php echo $tvshow->summary; ?>
    </div>
    <?php } ?>
</div>
<?php
if (AmpConfig::get('ratings')) {
?>
<div id="rating_<?php echo intval($tvshow->id); ?>_tvshow" style="display:inline;">
    <?php show_rating($tvshow->id, 'tvshow'); ?>
</div>
<?php } ?>
<?php if (AmpConfig::get('userflags')) { ?>
<div style="display:table-cell;" id="userflag_<?php echo $tvshow->id; ?>_tvshow">
        <?php Userflag::show($tvshow->id,'tvshow'); ?>
</div>
<?php } ?>
<div id="information_actions">
    <h3><?php echo T_('Actions'); ?>:</h3>
    <ul>
        <?php if (AmpConfig::get('directplay')) { ?>
        <li>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=tvshow&object_id=' . $tvshow->id,'play', T_('Play all'),'directplay_full_' . $tvshow->id); ?>
            <?php echo Ajax::text('?page=stream&action=directplay&object_type=tvshow&object_id=' . $tvshow->id, T_('Play all'),'directplay_full_text_' . $tvshow->id); ?>
        </li>
        <?php } ?>
        <?php if (Stream_Playlist::check_autoplay_append()) { ?>
        <li>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=tvshow&object_id=' . $tvshow->id . '&append=true','play_add', T_('Play all last'),'addplay_tvshow_' . $tvshow->id); ?>
            <?php echo Ajax::text('?page=stream&action=directplay&object_type=tvshow&object_id=' . $tvshow->id . '&append=true', T_('Play all last'),'addplay_tvshow_text_' . $tvshow->id); ?>
        </li>
        <?php } ?>
        <?php if (Access::check('interface','50')) { ?>
            <a id="<?php echo 'edit_tvshow_'.$tvshow->id ?>" onclick="showEditDialog('tvshow_row', '<?php echo $tvshow->id ?>', '<?php echo 'edit_tvshow_'.$tvshow->id ?>', '<?php echo T_('TV Show edit') ?>', '')">
                <?php echo UI::get_icon('edit', T_('Edit')); ?>
            </a>
            <a id="<?php echo 'edit_tvshow_'.$tvshow->id ?>" onclick="showEditDialog('tvshow_row', '<?php echo $tvshow->id ?>', '<?php echo 'edit_tvshow_'.$tvshow->id ?>', '<?php echo T_('TV Show edit') ?>', '')">
                <?php echo T_('Edit TV Show'); ?>
            </a>
        <?php } ?>
    </ul>
</div>
<?php UI::show_box_bottom(); ?>
<div class="tabs_wrapper">
    <div id="tabs_container">
        <ul id="tabs">
            <li class="tab_active"><a href="#seasons"><?php echo T_('Seasons'); ?></a></li>
            <!-- Needed to avoid the 'only one' bug -->
            <li></li>
        </ul>
    </div>
    <div id="tabs_content">
        <div id="seasons" class="tab_content" style="display: block;">
<?php
    $browse->show_objects($object_ids, true);
    $browse->store();
?>
        </div>
    </div>
</div>
