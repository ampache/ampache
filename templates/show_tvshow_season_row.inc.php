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
        <?php echo Ajax::button('?page=stream&action=directplay&playtype=tvshow_season&season_id=' . $season->id,'play', T_('Play'),'play_season_' . $season->id); ?>
        <?php if (Stream_Playlist::check_autoplay_append()) { ?>
            <?php echo Ajax::button('?page=stream&action=directplay&playtype=tvshow_season&season_id=' . $season->id . '&append=true','play_add', T_('Play last'),'addplay_season_' . $season->id); ?>
        <?php } ?>
<?php } ?>
    </div>
</td>
<?php
if (Art::is_enabled()) {
?>
<td class="cel_cover">
    <?php Art::display('tvshow_season', $season->id, $season->f_name, 6, $season->link); ?>
</td>
<?php } ?>
<td class="cel_season"><?php echo $season->f_link; ?></td>
<td class="cel_add">
    <span class="cel_item_add">
        <?php echo Ajax::button('?action=basket&type=tvshow_season&id=' . $season->id,'add', T_('Add to temporary playlist'),'add_season_' . $season->id); ?>
        <a id="<?php echo 'add_playlist_'.$season->id ?>" onclick="showPlaylistDialog(event, 'tvshow_season', '<?php echo $season->id ?>')">
            <?php echo UI::get_icon('playlist_add', T_('Add to existing playlist')); ?>
        </a>
    </span>
</td>
<td class="cel_tvshow"><?php echo $season->f_tvshow_link; ?></td>
<td class="cel_episodes"><?php echo $season->episodes; ?></td>
<?php if (AmpConfig::get('ratings')) { ?>
<td class="cel_rating" id="rating_<?php echo $season->id; ?>_tvshow_season"><?php Rating::show($season->id,'tvshow_season'); ?></td>
<?php } ?>
<?php if (AmpConfig::get('userflags')) { ?>
<td class="cel_userflag" id="userflag_<?php echo $season->id; ?>_tvshow_season"><?php Userflag::show($season->id,'tvshow_season'); ?></td>
<?php } ?>
<td class="cel_action">
<?php if (Access::check('interface','50')) { ?>
    <a id="<?php echo 'edit_tvshow_season_'.$season->id ?>" onclick="showEditDialog('tvshow_season_row', '<?php echo $season->id ?>', '<?php echo 'edit_tvshow_season_'.$season->id ?>', '<?php echo T_('Season edit') ?>', 'tvshow_season_', 'refresh_tvshow_season')">
        <?php echo UI::get_icon('edit', T_('Edit')); ?>
    </a>
<?php } ?>
</td>
