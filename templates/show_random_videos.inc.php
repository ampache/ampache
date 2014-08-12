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
$button = Ajax::button('?page=index&action=random_videos','random', T_('Refresh'),'random_video_refresh');
?>
<?php UI::show_box_top(T_('Videos of the Moment') . ' ' . $button, 'box box_random_videos'); ?>
<?php
if ($videos) {
    foreach ($videos as $video_id) {
        $video = Video::create_from_id($video_id);
        $video->format();
    ?>
    <div class="random_video">
        <div class="art_album">
            <?php if (Art::is_enabled()) {
                $release_art = $video->get_release_item_art();
                Art::display($release_art['object_type'], $release_art['object_id'], $video->get_fullname(), 6, $video->link);
            } else { ?>
                <?php echo $video->get_fullname(); ?>
            <?php } ?>
        </div>
        <div class="play_video">
        <?php if (AmpConfig::get('directplay')) { ?>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=video&object_id=' . $video->id,'play', T_('Play'),'play_album_' . $video->id); ?>
            <?php if (Stream_Playlist::check_autoplay_append()) { ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=video&object_id=' . $video->id . '&append=true','play_add', T_('Play last'),'addplay_video_' . $video->id); ?>
            <?php } ?>
        <?php } ?>
        </div>
        <?php
        if (AmpConfig::get('ratings')) {
            echo "<div id=\"rating_" . $video->id . "_video\">";
            show_rating($video->id, 'video');
            echo "</div>";
        }
        ?>
    </div>
    <?php } ?>
<?php } ?>

<?php UI::show_box_bottom(); ?>
