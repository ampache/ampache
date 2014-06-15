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
        <?php echo Ajax::button('?page=stream&action=directplay&playtype=tvshow&tvshow_id=' . $tvshow->id,'play', T_('Play'),'play_tvshow_' . $tvshow->id); ?>
        <?php if (Stream_Playlist::check_autoplay_append()) { ?>
            <?php echo Ajax::button('?page=stream&action=directplay&playtype=tvshow&tvshow_id=' . $tvshow->id . '&append=true','play_add', T_('Play last'),'addplay_tvshow_' . $tvshow->id); ?>
        <?php } ?>
<?php } ?>
    </div>
</td>
<td class="cel_tvshow"><?php echo $tvshow->f_link; ?></td>
<td class="cel_add">
    <span class="cel_item_add">
        <?php echo Ajax::button('?action=basket&type=tvshow&id=' . $artist->id,'add', T_('Add to temporary playlist'),'add_artist_' . $tvshow->id); ?>
        <a id="<?php echo 'add_playlist_'.$tvshow->id ?>" onclick="showPlaylistDialog(event, 'artist', '<?php echo $tvshow->id ?>')">
            <?php echo UI::get_icon('playlist_add', T_('Add to existing playlist')); ?>
        </a>
    </span>
</td>
<td class="cel_episodes"><?php echo $tvshow->episodes; ?></td>
<td class="cel_seasons"><?php echo $tvshow->seasons; ?></td>
<td class="cel_tags"><?php echo $tvshow->f_tags; ?></td>
<?php if (AmpConfig::get('ratings')) { ?>
<td class="cel_rating" id="rating_<?php echo $tvshow->id; ?>_tvshow"><?php Rating::show($tvshow->id,'tvshow'); ?></td>
<?php } ?>
<?php if (AmpConfig::get('userflags')) { ?>
<td class="cel_userflag" id="userflag_<?php echo $tvshow->id; ?>_tvshow"><?php Userflag::show($tvshow->id,'tvshow'); ?></td>
<?php } ?>
<td class="cel_action">
<?php if (Access::check('interface','50')) { ?>
    <a id="<?php echo 'edit_tvshow_'.$tvshow->id ?>" onclick="showEditDialog('tvshow_row', '<?php echo $tvshow->id ?>', '<?php echo 'edit_tvshow_'.$tvshow->id ?>', '<?php echo T_('TV Show edit') ?>', 'tvshow_', 'refresh_tvshow')">
        <?php echo UI::get_icon('edit', T_('Edit')); ?>
    </a>
<?php } ?>
</td>
