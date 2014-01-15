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

$web_path = AmpConfig::get('web_path');

// Title for this album
$title = scrub_out($album->name) . '&nbsp;(' . $album->year . ')';
if ($album->disk) {
    $title .= "<span class=\"discnb disc" . $album->disk . "\">, " . T_('Disk') . " " . $album->disk . "</span>";
}
$title .= '&nbsp;-&nbsp;' . $album->f_artist_link;
?>
<?php UI::show_box_top($title,'info-box'); ?>
<div class="album_art">
    <?php
    if ($album->name != T_('Unknown (Orphaned)')) {
        $name = '[' . $album->f_artist . '] ' . scrub_out($album->full_name);

        $aa_url = $web_path . "/image.php?id=" . $album->id . "&amp;sid=" . session_id();
        echo "<a href=\"$aa_url\" rel=\"prettyPhoto\">";
        echo "<img src=\"" . $web_path . "/image.php?id=" . $album->id . "&amp;thumb=2\" alt=\"".$name."\" alt=\"".$name."\" height=\"128\" width=\"128\" />";
        echo "</a>\n";
    }
    ?>
</div>
<?php if (AmpConfig::get('ratings')) { ?>
<div style="display:table-cell;" id="rating_<?php echo $album->id; ?>_album">
        <?php Rating::show($album->id,'album'); ?>
</div>
<?php } ?>
<?php if (AmpConfig::get('userflags')) { ?>
<div style="display:table-cell;" id="userflag_<?php echo $album->id; ?>_album">
        <?php Userflag::show($album->id,'album'); ?>
</div>
<?php } ?>
<?php
if (AmpConfig::get('show_played_times')) {
?>
<br />
<div style="display:inline;"><?php echo T_('Played') . ' ' . $album->object_cnt . ' ' . T_('times'); ?></div>
<?php
}
?>
<div id="information_actions">
<h3><?php echo T_('Actions'); ?>:</h3>
<ul>
    <?php if (AmpConfig::get('directplay')) { ?>
    <li>
        <?php echo Ajax::button('?page=stream&action=directplay&playtype=album&album_id=' . $album->id,'play', T_('Play album'),'directplay_full_' . $album->id); ?>
        <?php echo Ajax::text('?page=stream&action=directplay&playtype=album&album_id=' . $album->id, T_('Play Album'),'directplay_full_text_' . $album->id); ?>
    </li>
    <?php } ?>
    <?php if (Stream_Playlist::check_autoplay_append()) { ?>
    <li>
        <?php echo Ajax::button('?page=stream&action=directplay&playtype=album&album_id=' . $album->id . '&append=true','play_add', T_('Play Add Album'),'addplay_album_' . $album->id); ?>
        <?php echo Ajax::text('?page=stream&action=directplay&playtype=album&album_id=' . $album->id . '&append=true', T_('Play Add Album'),'addplay_album_text_' . $album->id); ?>
    </li>
    <?php } ?>
    <li>
        <?php echo Ajax::button('?action=basket&type=album&id=' . $album->id,'add', T_('Add'),'play_full_' . $album->id); ?>
        <?php echo Ajax::text('?action=basket&type=album&id=' . $album->id, T_('Add Album'), 'play_full_text_' . $album->id); ?>
    </li>
    <li>
        <?php echo Ajax::button('?action=basket&type=album_random&id=' . $album->id,'random', T_('Random'),'play_random_' . $album->id); ?>
        <?php echo Ajax::text('?action=basket&type=album_random&id=' . $album->id, T_('Add Random from Album'), 'play_random_text_' . $album->id); ?>
    </li>
    <li>
        <a onclick="submitNewItemsOrder('<?php echo $album->id; ?>', 'reorder_songs_table', 'song_',
                                        '<?php echo AmpConfig::get('web_path'); ?>/albums.php?action=set_track_numbers', 'refresh_album_songs')">
            <?php echo UI::get_icon('save', T_('Save Tracks Order')); ?>
            &nbsp;&nbsp;<?php echo T_('Save Tracks Order'); ?>
        </a>
    </li>
    <?php if (Access::check('interface','75')) { ?>
    <li>
        <a href="<?php echo $web_path; ?>/albums.php?action=clear_art&amp;album_id=<?php echo $album->id; ?>" onclick="return confirm('<?php echo T_('Do you really want to reset album art?'); ?>');"><?php echo UI::get_icon('delete', T_('Reset Album Art')); ?></a>
        <a href="<?php echo $web_path; ?>/albums.php?action=clear_art&amp;album_id=<?php echo $album->id; ?>" onclick="return confirm('<?php echo T_('Do you really want to reset album art?'); ?>');"><?php echo T_('Reset Album Art'); ?></a>
    </li>
    <?php } ?>
    <li>
        <a href="<?php echo $web_path; ?>/albums.php?action=find_art&amp;album_id=<?php echo $album->id; ?>"><?php echo UI::get_icon('view', T_('Find Album Art')); ?></a>
        <a href="<?php echo $web_path; ?>/albums.php?action=find_art&amp;album_id=<?php echo $album->id; ?>"><?php echo T_('Find Album Art'); ?></a>
    </li>
    <?php  if ((Access::check('interface','50'))) { ?>
    <li>
        <a href="<?php echo $web_path; ?>/albums.php?action=update_from_tags&amp;album_id=<?php echo $album->id; ?>" onclick="return confirm('<?php echo T_('Do you really want to update from tags?'); ?>');"><?php echo UI::get_icon('cog', T_('Update from tags')); ?></a>
        <a href="<?php echo $web_path; ?>/albums.php?action=update_from_tags&amp;album_id=<?php echo $album->id; ?>" onclick="return confirm('<?php echo T_('Do you really want to update from tags?'); ?>');"><?php echo T_('Update from tags'); ?></a>
    </li>
    <?php  } ?>
    <?php if (AmpConfig::get('sociable')) { ?>
        <a href="<?php echo AmpConfig::get('web_path'); ?>/shout.php?action=show_add_shout&type=album&id=<?php echo $album->id; ?>"><?php echo UI::get_icon('comment', T_('Post Shout')); ?></a>
        <a href="<?php echo AmpConfig::get('web_path'); ?>/shout.php?action=show_add_shout&type=album&id=<?php echo $album->id; ?>"><?php echo T_('Post Shout'); ?></a>
    <?php } ?>
    <?php if (AmpConfig::get('share')) { ?>
    <li>
        <a href="<?php echo $web_path; ?>/share.php?action=show_create&type=album&id=<?php echo $album->id; ?>"><?php echo UI::get_icon('share', T_('Share')); ?></a>
        <a href="<?php echo $web_path; ?>/share.php?action=show_create&type=album&id=<?php echo $album->id; ?>"><?php echo T_('Share'); ?></a>
    </li>
    <?php } ?>
    <?php if (Access::check_function('batch_download')) { ?>
    <li>
        <a href="<?php echo $web_path; ?>/batch.php?action=album&amp;id=<?php echo $album->id; ?>"><?php echo UI::get_icon('batch_download', T_('Download')); ?></a>
        <a href="<?php echo $web_path; ?>/batch.php?action=album&amp;id=<?php echo $album->id; ?>"><?php echo T_('Download'); ?></a>
    </li>
    <?php } ?>
</ul>
</div>
<?php UI::show_box_bottom(); ?>
<div id="additional_information">
&nbsp;
</div>
<div id='reordered_list'>
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
