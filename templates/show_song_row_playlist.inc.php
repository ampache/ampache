<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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

<div id="musicbox" class="musicbox absolute box-shadow" style="top: 450px; left: 1439px; display: block;">
    <div id="head-add" class="header_add p_10 relative">
        <div style="display: block;">
            <div class="floatl block_cover">
                <div class="cover" style="margin-bottom:0">
                    <img class="img_main" src="http://cdn-images.deezer.com/images/cover/e8cd709b7505068baec252a3d24ed920/30x30-000000-80-0-0.jpg" width="30" height="30" alt="">
                </div>
            </div>
            <div class="floatl label">
                <dl>
                    <dt class="to_e">Dying Kings</dt>
                    <dd class="to_e">WE ARE MATCH</dd>
                </dl>
            </div>
        </div>
        <p style="display: none;"></p>
        <div class="clearfix"></div>
    </div>
    <div class="list_playlists">
        <p class="" style="height30px;line-height:30px"><b>Ajouter à une playlist existante</b></p>
        <div id="add-content" style="height: 110px;">
            <div class="tinyscroll_viewport">
                <div class="tinyscroll_overview" style="top: 0px;">
                    <ul id="add-playlist-content"><li id="add-playlist" data-id="11714796">
                            <a href="javascript:void(0);" onclick="musicbox.addToPlaylist($(this).parent().attr('data-id')); return false;" style="overflow:hidden;height:22px;line-height:22px">Autre</a>
                        </li><li id="add-playlist" data-id="8445567">
                            <a href="javascript:void(0);" onclick="musicbox.addToPlaylist($(this).parent().attr('data-id')); return false;" style="overflow:hidden;height:22px;line-height:22px">Cascada Perfect Day</a>
                        </li><li id="add-playlist" data-id="49229476">
                            <a href="javascript:void(0);" onclick="musicbox.addToPlaylist($(this).parent().attr('data-id')); return false;" style="overflow:hidden;height:22px;line-height:22px">Groupe</a>
                        </li><li id="add-playlist" data-id="13358479">
                            <a href="javascript:void(0);" onclick="musicbox.addToPlaylist($(this).parent().attr('data-id')); return false;" style="overflow:hidden;height:22px;line-height:22px">Hit Dance</a>
                        </li><li id="add-playlist" data-id="33847922">
                            <a href="javascript:void(0);" onclick="musicbox.addToPlaylist($(this).parent().attr('data-id')); return false;" style="overflow:hidden;height:22px;line-height:22px">Manix</a>
                        </li><li id="add-playlist" data-id="13710068">
                            <a href="javascript:void(0);" onclick="musicbox.addToPlaylist($(this).parent().attr('data-id')); return false;" style="overflow:hidden;height:22px;line-height:22px">max puissant</a>
                        </li><li id="add-playlist" data-id="11714814">
                            <a href="javascript:void(0);" onclick="musicbox.addToPlaylist($(this).parent().attr('data-id')); return false;" style="overflow:hidden;height:22px;line-height:22px">Playlist Christophe</a>
                        </li><li id="add-playlist" data-id="26402207">
                            <a href="javascript:void(0);" onclick="musicbox.addToPlaylist($(this).parent().attr('data-id')); return false;" style="overflow:hidden;height:22px;line-height:22px">ROCK</a>
                        </li></ul>
                </div>
            </div>
        </div>
        <div id="bottom-add" class="options_musicbox">
            <ul>
                <li id="btn-new-list">
                    <a href="javascript:void(0)" class="to_e" style="display:block" onclick="musicbox.openCreatePlaylist(); return false;">
                        <i class="icn icon icon-playlist pull-left"></i>
                        <span>Créer une nouvelle playlist</span>
                        <input id="input-new-title" style="display: none; height: 19px; width: 142px; border: 1px solid rgb(205, 205, 205); background-color: rgb(238, 238, 238); background-position: initial initial; background-repeat: initial initial;" type="text" class="floatl m_0 mt_5 p_0 pl_7 rad_2" value="Nouvelle playlist" onfocus="this.select();" onblur="if (this.value == '') { this.value = 'Nouvelle playlist'; }" onkeypress="if(event.keyCode == 13) { musicbox.createPlaylist($(this)); }">
                    </a>
                </li>
            </ul>
            <div class="clearfix"></div>
        </div>
    </div>
</div>


<div>
    <form method="post" id="edit_album_<?php echo $album->id; ?>" class="edit_dialog_content">
        <table class="tabledata" cellspacing="0" cellpadding="0">
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Name') ?></td>
                <td><input type="text" name="name" value="<?php echo scrub_out($album->full_name); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Artist') ?></td>
                <td>
                    <?php
                    /*if ($album->artist_count == '1') {*/
                        show_artist_select('artist', $album->artist_id);
                    /*} else {
                        echo T_('Various');
                    }*/
                    ?>
                </td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Year') ?></td>
                <td><input type="text" name="year" value="<?php echo scrub_out($album->year); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Disk') ?></td>
                <td><input type="text" name="disk" value="<?php echo scrub_out($album->disk); ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('MusicBrainz ID') ?></td>
                <td><input type="text" name="mbid" value="<?php echo $album->mbid; ?>" /></td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"><?php echo T_('Tags') ?></td>
                <td>
                    <input type="text" name="edit_tags" id="edit_tags" value="<?php echo Tag::get_display($album->tags); ?>" />
                </td>
            </tr>
            <tr>
                <td class="edit_dialog_content_header"></td>
                <td><input type="checkbox" name="apply_childs" value="checked" /><?php echo T_(' Apply tags to all childs (override tags for songs)') ?></td>
            </tr>
        </table>
        <input type="hidden" name="id" value="<?php echo $album->id; ?>" />
        <input type="hidden" name="type" value="album_row" />
    </form>
</div>
