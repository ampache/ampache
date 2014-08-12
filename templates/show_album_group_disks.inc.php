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

$album->allow_group_disks = true;
// Title for this album
$title = scrub_out($album->name) . '&nbsp;(' . $album->year . ')&nbsp;-&nbsp;' . $album->f_artist_link;
?>
<?php UI::show_box_top($title, 'info-box'); ?>
<div class="item_right_info">
    <div class="external_links">
        <a href="http://www.google.com/search?q=%22<?php echo rawurlencode($album->f_artist); ?>%22+%22<?php echo rawurlencode($album->f_name); ?>%22" target="_blank"><?php echo UI::get_icon('google', T_('Search on Google ...')); ?></a>
        <a href="http://en.wikipedia.org/wiki/Special:Search?search=%22<?php echo rawurlencode($album->f_name); ?>%22&go=Go" target="_blank"><?php echo UI::get_icon('wikipedia', T_('Search on Wikipedia ...')); ?></a>
        <a href="http://www.last.fm/search?q=%22<?php echo rawurlencode($album->f_artist); ?>%22+%22<?php echo rawurlencode($album->f_name); ?>%22&type=album" target="_blank"><?php echo UI::get_icon('lastfm', T_('Search on Last.fm ...')); ?></a>
    </div>
    <?php
    if ($album->name != T_('Unknown (Orphaned)')) {
        $name = '[' . $album->f_artist . '] ' . scrub_out($album->full_name);
        Art::display('album', $album->id, $name, 2);
    }
    ?>
</div>
<div id="information_actions">
    <h3><?php echo T_('Actions'); ?>:</h3>
    <ul>
        <?php if (AmpConfig::get('directplay')) { ?>
        <li>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=album&' . $album->get_http_album_query_ids('object_id'), 'play', T_('Play'), 'directplay_full_'); ?>
            <?php echo Ajax::text('?page=stream&action=directplay&object_type=album&' . $album->get_http_album_query_ids('object_id'), T_('Play'), 'directplay_full_text_'); ?>
        </li>
        <?php } ?>
        <?php if (Stream_Playlist::check_autoplay_append()) { ?>
        <li>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=album&' . $album->get_http_album_query_ids('object_id') . '&append=true', 'play_add', T_('Play last'), 'addplay_album_'); ?>
            <?php echo Ajax::text('?page=stream&action=directplay&object_type=album&' . $album->get_http_album_query_ids('object_id') . '&append=true', T_('Play last'), 'addplay_album_text_'); ?>
        </li>
        <?php } ?>
        <li>
            <?php echo Ajax::button('?action=basket&type=album&' . $album->get_http_album_query_ids('id'), 'add', T_('Add to temporary playlist'), 'play_full_'); ?>
            <?php echo Ajax::text('?action=basket&type=album&' . $album->get_http_album_query_ids('id'), T_('Add to temporary playlist'), 'play_full_text_'); ?>
        </li>
        <li>
            <?php echo Ajax::button('?action=basket&type=album_random&' . $album->get_http_album_query_ids('id'), 'random', T_('Random to temporary playlist'), 'play_random_'); ?>
            <?php echo Ajax::text('?action=basket&type=album_random&' . $album->get_http_album_query_ids('id'), T_('Random to temporary playlist'), 'play_random_text_'); ?>
        </li>
        <?php if (Access::check_function('batch_download')) { ?>
        <li>
            <a rel="nohtml" href="<?php echo $web_path; ?>/batch.php?action=album&<?php echo $album->get_http_album_query_ids('id'); ?>"><?php echo UI::get_icon('batch_download', T_('Download')); ?></a>
            <a rel="nohtml" href="<?php echo $web_path; ?>/batch.php?action=album&<?php echo $album->get_http_album_query_ids('id'); ?>"><?php echo T_('Download'); ?></a>
        </li>
        <?php } ?>
    </ul>
</div>
<?php UI::show_box_bottom(); ?>
<div id="additional_information">
&nbsp;
</div>
<?php
    $album_suite = $album->get_group_disks_ids();
    foreach ($album_suite as $album_id) {
        $c_album = new Album($album_id);
        $c_album->format();
        $c_title = scrub_out($c_album->name) . "&nbsp;<span class=\"discnb disc" . $c_album->disk . "\">, " . T_('Disk') . " " . $c_album->disk . "</span>";
?>
    <div class="album_group_disks_title"><span> <?php echo $c_title; ?></span></div>
    <div class="album_group_disks_actions">
        <?php
            if (AmpConfig::get('directplay')) {
                echo Ajax::button('?page=stream&action=directplay&object_type=album&' . $c_album->get_http_album_query_ids('object_id'), 'play', T_('Play'), 'directplay_full_' . $c_album->id);
            }
            if (Stream_Playlist::check_autoplay_append()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=album&' . $c_album->get_http_album_query_ids('object_id') . '&append=true', 'play_add', T_('Play last'), 'addplay_album_' . $c_album->id);
            }
            echo Ajax::button('?action=basket&type=album&' . $c_album->get_http_album_query_ids('id'), 'add', T_('Add to temporary playlist'), 'play_full_' . $c_album->id);
            echo Ajax::button('?action=basket&type=album_random&' . $c_album->get_http_album_query_ids('id'), 'random', T_('Random to temporary playlist'), 'play_random_' . $c_album->id);
        ?>
        <a onclick="submitNewItemsOrder('<?php echo $c_album->id; ?>', 'reorder_songs_table_<?php echo $c_album->id; ?>', 'song_',
                                        '<?php echo AmpConfig::get('web_path'); ?>/albums.php?action=set_track_numbers', 'refresh_album_songs')">
            <?php echo UI::get_icon('save', T_('Save Tracks Order')); ?>
        </a>
        <?php if (AmpConfig::get('sociable')) { ?>
            <a href="<?php echo AmpConfig::get('web_path'); ?>/shout.php?action=show_add_shout&type=album&id=<?php echo $c_album->id; ?>"><?php echo UI::get_icon('comment', T_('Post Shout')); ?></a>
        <?php } ?>
        <?php if (AmpConfig::get('share')) { ?>
            <a href="<?php echo $web_path; ?>/share.php?action=show_create&type=album&id=<?php echo $c_album->id; ?>"><?php echo UI::get_icon('share', T_('Share')); ?></a>
        <?php } ?>
        <?php if (Access::check_function('batch_download')) { ?>
            <a rel="nohtml" href="<?php echo $web_path; ?>/batch.php?action=album&<?php echo $c_album->get_http_album_query_ids('id'); ?>"><?php echo UI::get_icon('batch_download', T_('Download')); ?></a>
        <?php } ?>
        <?php if (Access::check('interface','50')) { ?>
            <a id="<?php echo 'edit_album_'.$c_album->id ?>" onclick="showEditDialog('album_row', '<?php echo $c_album->id ?>', '<?php echo 'edit_album_'.$c_album->id ?>', '<?php echo T_('Album edit') ?>', '')">
                <?php echo UI::get_icon('edit', T_('Edit')); ?>
            </a>
        <?php } ?>
    </div>
    <div id='reordered_list_<?php echo $album_id; ?>'>
    <?php
        $browse = new Browse();
        $browse->set_show_header(false);
        $browse->set_type('song');
        $browse->set_simple_browse(true);
        $browse->set_filter('album', $album_id);
        $browse->set_sort('track', 'ASC');
        $browse->get_objects();
        $browse->show_objects(null, true); // true argument is set to show the reorder column
        $browse->store();
    ?>
    </div><br />
<?php } ?>
