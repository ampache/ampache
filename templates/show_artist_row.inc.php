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
        <?php echo Ajax::button('?page=stream&action=directplay&playtype=artist&artist_id=' . $artist->id,'play', T_('Play'),'play_artist_' . $artist->id); ?>
        <?php if (Stream_Playlist::check_autoplay_append()) { ?>
            <?php echo Ajax::button('?page=stream&action=directplay&playtype=artist&artist_id=' . $artist->id . '&append=true','play_add', T_('Play last'),'addplay_artist_' . $artist->id); ?>
        <?php } ?>
<?php } ?>
    </div>
</td>
<td class="cel_artist"><?php echo $artist->f_name_link; ?></td>
<td class="cel_add">
    <span class="cel_item_add">
        <?php echo Ajax::button('?action=basket&type=artist&id=' . $artist->id,'add', T_('Add to temporary playlist'),'add_artist_' . $artist->id); ?>
        <?php echo Ajax::button('?action=basket&type=artist_random&id=' . $artist->id,'random', T_('Random to temporary playlist'),'random_artist_' . $artist->id); ?>
        <a id="<?php echo 'add_playlist_'.$artist->id ?>" onclick="showPlaylistDialog(event, 'artist', '<?php echo $artist->id ?>')">
            <?php echo UI::get_icon('playlist_add', T_('Add to existing playlist')); ?>
        </a>
    </span>
</td>
<td class="cel_songs"><?php echo $artist->songs; ?></td>
<td class="cel_albums"><?php echo $artist->albums; ?></td>
<td class="cel_time"><?php echo $artist->f_time; ?></td>
<td class="cel_tags" title="<?php echo $artist->f_tags; ?>"><?php echo $artist->f_tags; ?></td>
<?php if (AmpConfig::get('ratings')) { ?>
<td class="cel_rating" id="rating_<?php echo $artist->id; ?>_artist"><?php Rating::show($artist->id,'artist'); ?></td>
<?php } ?>
<?php if (AmpConfig::get('userflags')) { ?>
<td class="cel_userflag" id="userflag_<?php echo $artist->id; ?>_artist"><?php Userflag::show($artist->id,'artist'); ?></td>
<?php } ?>
<td class="cel_action">
<?php if (Access::check_function('batch_download')) { ?>
    <a href="<?php echo AmpConfig::get('web_path'); ?>/batch.php?action=artist&amp;id=<?php echo $artist->id; ?>">
            <?php echo UI::get_icon('batch_download','', T_('Batch Download')); ?>
        </a>
<?php } ?>
<?php if (Access::check('interface','50')) { ?>
    <a id="<?php echo 'edit_artist_'.$artist->id ?>" onclick="showEditDialog('artist_row', '<?php echo $artist->id ?>', '<?php echo 'edit_artist_'.$artist->id ?>', '<?php echo T_('Artist edit') ?>', 'artist_', 'refresh_artist')">
        <?php echo UI::get_icon('edit', T_('Edit')); ?>
    </a>
<?php } ?>
</td>
