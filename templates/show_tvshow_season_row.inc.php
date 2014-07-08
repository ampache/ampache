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
?>
<td class="cel_play">
    <span class="cel_play_content">&nbsp;</span>
    <div class="cel_play_hover">
    <?php if (AmpConfig::get('directplay')) { ?>
        <?php echo Ajax::button('?page=stream&action=directplay&object_type=tvshow_season&object_id=' . $libitem->id,'play', T_('Play'),'play_season_' . $libitem->id); ?>
        <?php if (Stream_Playlist::check_autoplay_append()) { ?>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=tvshow_season&object_id=' . $libitem->id . '&append=true','play_add', T_('Play last'),'addplay_season_' . $libitem->id); ?>
        <?php } ?>
<?php } ?>
    </div>
</td>
<?php
if (Art::is_enabled()) {
?>
<td class="cel_cover">
    <?php Art::display('tvshow_season', $libitem->id, $libitem->f_name, 6, $libitem->link); ?>
</td>
<?php } ?>
<td class="cel_season"><?php echo $libitem->f_link; ?></td>
<td class="cel_tvshow"><?php echo $libitem->f_tvshow_link; ?></td>
<td class="cel_episodes"><?php echo $libitem->episodes; ?></td>
<?php if (AmpConfig::get('ratings')) { ?>
<td class="cel_rating" id="rating_<?php echo $libitem->id; ?>_tvshow_season"><?php Rating::show($libitem->id,'tvshow_season'); ?></td>
<?php } ?>
<?php if (AmpConfig::get('userflags')) { ?>
<td class="cel_userflag" id="userflag_<?php echo $libitem->id; ?>_tvshow_season"><?php Userflag::show($libitem->id,'tvshow_season'); ?></td>
<?php } ?>
<td class="cel_action">
<?php if (Access::check('interface','50')) { ?>
    <a id="<?php echo 'edit_tvshow_season_'.$libitem->id ?>" onclick="showEditDialog('tvshow_season_row', '<?php echo $libitem->id ?>', '<?php echo 'edit_tvshow_season_'.$libitem->id ?>', '<?php echo T_('Season edit') ?>', 'tvshow_season_')">
        <?php echo UI::get_icon('edit', T_('Edit')); ?>
    </a>
<?php } ?>
</td>
