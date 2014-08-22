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

// Title for this album
$title = scrub_out($album->name) . '&nbsp;(' . $album->year . ')';
if ($album->disk) {
    $title .= "<span class=\"discnb disc" . $album->disk . "\">, " . T_('Disk') . " " . $album->disk . "</span>";
}
$title .= '&nbsp;-&nbsp;' . (($album->f_album_artist_link) ? $album->f_album_artist_link : $album->f_artist_link);

$show_direct_play = AmpConfig::get('directplay');
$show_playlist_add = Access::check('interface', '25');
$directplay_limit = AmpConfig::get('direct_play_limit');

if ($directplay_limit > 0) {
    $show_playlist_add = ($album->song_count <= $directplay_limit);
    if ($show_direct_play) {
        $show_direct_play = $show_playlist_add;
    }
}
?>

<div class="details-container">
    <div class="artist-details-row details-row">
        <div class="details-title-container">
            <h1 class="item-title"><?php echo trim($title); ?></h1>
        </div>
        <div class="details-metadata-container">
            <div class="artist-details-metadata-container">
                <div class="metadata-right pull-right">
                    <div class="metadata-tags">
                        <?php
                            echo trim($album->f_tags);
                        ?>
                    </div>
                </div>
                <p class="metadata-labels">
                    <span><?php show_rating($album->id, 'album'); ?></span>
                    <?php if (AmpConfig::get('userflags')) { ?>
                    <span><?php Userflag::show($album->id, 'album'); ?></span>
                    <?php } ?>
                </p>
            </div>
            <div class="album-list-container details-list-container">
                <div class="list album-list">
                    <div id='reordered_list_<?php echo $album->id; ?>'>
                        <?php
                            $browse = new Browse();
                            $browse->set_type('song');
                            $browse->set_simple_browse(true);
                            $browse->set_filter('album', $album->id);
                            $browse->set_sort('track', 'ASC');
                            $browse->get_objects();
                            $browse->show_objects(null, true); // true argument is set to show the reorder column
                            $browse->store();
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="details-poster-container">
            <div class="media-poster-container" href="#">
                <div class="artist-poster media-poster" style="background-image: url(<?php echo AmpConfig::get('web_path') . "/image.php?object_id=" . $album->id . "&object_type=album&thumb=2" ?>);">
                    <div class="media-poster-overlay"></div>
                    <?php 
                        if (AmpConfig::get('show_played_times')) {
                            echo '<span class="unwatched-count-badge badge badge-lg">'.$album->object_cnt.'</span>';
                        }
                    ?>
                </div>
                <div class="media-poster-actions">
                    <button class="play-btn media-poster-btn btn-link" tabindex="-1">
                        <a rel="nohtml" href="<?php echo AmpConfig::get('ajax_url') . '?page=stream&action=directplay&object_type=album&object_id=' . $album->id; ?>">
                            <i class="fa fa-play fa-lg"></i>
                        </a>
                    </button>
                    <button class="edit-btn media-poster-btn btn-link" tabindex="-1">
                        <?php if (Access::check('interface','50')) { ?>
                        <a rel="nohtml" id="<?php echo 'edit_album_'.$album->id ?>" onclick="showEditDialog('album_row', '<?php echo $album->id ?>', '<?php echo 'edit_album_'.$album->id ?>', '<?php echo T_('Album edit') ?>', '')">
                            <i class="fa fa-pencil fa-lg"></i>
                        </a>
                        <?php } else { ?>
                        <a rel="nohtml" class="disabled" href="#">
                            <i class="fa fa-pencil fa-lg"></i>
                        </a>
                        <?php } ?>
                    </button>
                    <button class="more-btn media-poster-btn btn-link nav-dropdown dropdown" tabindex="-1">
                        <a rel="nohtml" class="dropdown-toggle" data-toggle="dropdown" data-original-title="" title="<?php echo T_('More'); ?>">
                            <i class="fa fa-ellipsis-h fa-lg"></i>
                        </a>
                        <ul class="media-actions-dropdown dropdown-menu">
                            <?php if (Stream_Playlist::check_autoplay_append()) { ?>
                            <li>
                                <a rel="nohtml" class="add-to-up-next-btn" href="<?php echo AmpConfig::get('ajax_url') . '?page=stream&action=directplay&object_type=album&object_id=' . $album->id . '&append=true'; ?>" tabindex="-1">
                                    <?php echo T_('Play next'); ?>
                                </a>
                            </li>
                            <?php } ?>
                            <li>
                                <a rel="nohtml" class="add-to-playlist-btn" href="<?php echo AmpConfig::get('ajax_url') . '?action=basket&type=album&id=' . $album->id; ?>" tabindex="-1">
                                    <?php echo T_('Add to temporary playlist'); ?>
                                </a>
                            </li>
                            <li>
                                <a rel="nohtml" class="random-to-playlist-btn" href="<?php echo AmpConfig::get('ajax_url') . '?action=basket&type=album_random&id=' . $album->id; ?>" tabindex="-1">
                                    <?php echo T_('Random to temporary playlist'); ?>
                                </a>
                            </li>
                            <li>
                                <a rel="nohtml" lass="random-to-playlist-btn" onclick="submitNewItemsOrder('<?php echo $album->id; ?>', 'reorder_songs_table_<?php echo $album->id; ?>', 'song_',
                                    '<?php echo AmpConfig::get('web_path'); ?>/albums.php?action=set_track_numbers', 'refresh_album_songs')">
                                    <?php echo T_('Save Tracks Order'); ?>
                                </a>
                            </li>
                            <?php if (AmpConfig::get('sociable')) { ?>
                            <li>
                                <a rel="nohtml" href="<?php echo AmpConfig::get('web_path'); ?>/shout.php?action=show_add_shout&type=album&id=<?php echo $album->id; ?>">
                                    <?php echo T_('Post Shout'); ?>
                                </a>
                            </li>
                            <?php } ?>
                            <?php if (AmpConfig::get('share')) { ?>
                            <li>
                                <a rel="nohtml" href="<?php echo AmpConfig::get('web_path'); ?>/share.php?action=show_create&type=album&id=<?php echo $album->id; ?>">
                                    <?php echo T_('Share'); ?>
                                </a>
                            </li>
                            <?php } ?>
                            <?php if (Access::check_function('batch_download')) { ?>
                            <li>
                                <a rel="nohtml" href="<?php echo AmpConfig::get('web_path'); ?>/batch.php?action=album&<?php echo $album->get_http_album_query_ids('id'); ?>">
                                    <?php echo T_('Batch Download'); ?>
                                </a>
                            </li>
                            <?php } ?>
                            
                            <li class="divider"></li>
                            
                            <li>
                                <a rel="nohtml" href="http://www.google.com/search?q=%22<?php echo rawurlencode($album->f_artist); ?>%22+%22<?php echo rawurlencode($album->f_name); ?>%22" target="_blank"><?php echo T_('Search on Google ...'); ?></a>
                            </li>
                            <li>
                                <a rel="nohtml" href="http://en.wikipedia.org/wiki/Special:Search?search=%22<?php echo rawurlencode($album->f_name); ?>%22&go=Go" target="_blank"><?php echo T_('Search on Wikipedia ...'); ?></a>
                            </li>
                            <li>
                                <a rel="nohtml" href="http://www.last.fm/search?q=%22<?php echo rawurlencode($album->f_artist); ?>%22+%22<?php echo rawurlencode($album->f_name); ?>%22&type=album" target="_blank"><?php echo T_('Search on Last.fm ...'); ?></a>
                            </li>
                        </ul>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

