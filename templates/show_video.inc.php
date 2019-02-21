<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2017 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

?>
<?php UI::show_box_top($video->f_title . ' ' . T_('Details'), 'box box_video_details'); ?>
<div class="item_right_info">
<?php
$gart = false;
// The release type is not the video itself, we probably want preview
if (strtolower(get_class($video)) != 'movie') {
    $gart = Art::display('video', $video->id, $video->f_title, 8, null, false, 'preview');
}
if (!$gart) {
    $gart = Art::display('video', $video->id, $video->f_title, 7);
}
?>
<?php if (AmpConfig::get('encode_srt')) {
    ?>
<div class="subtitles">
<?php echo T_('Subtitle'); ?>:
<select name="subtitle" id="play_setting_subtitle">
    <option value=''><?php echo T_("None"); ?></option>
<?php
$subtitles = $video->get_subtitles();
    foreach ($subtitles as $subtitle) {
        echo "<option value='" . $subtitle['lang_code'] . "' ";
        if (isset($_SESSION['iframe']['subtitle']) && $_SESSION['iframe']['subtitle'] == $subtitle['lang_code']) {
            echo "selected";
        }
        echo ">" . $subtitle['lang_name'] . "</option>";
    } ?>
</select>
</div>
<?php
} ?>
</div>
<dl class="media_details">
<?php if (User::is_registered()) {
        ?>
    <?php if (AmpConfig::get('ratings')) {
            ?>
        <?php $rowparity = UI::flip_class(); ?>
        <dt class="<?php echo $rowparity; ?>"><?php echo T_('Rating'); ?></dt>
        <dd class="<?php echo $rowparity; ?>">
            <div id="rating_<?php echo $video->id; ?>_video"><?php Rating::show($video->id, 'video'); ?>
            </div>
        </dd>
    <?php
        } ?>

    <?php if (AmpConfig::get('userflags')) {
            ?>
        <?php $rowparity = UI::flip_class(); ?>
        <dt class="<?php echo $rowparity; ?>"><?php echo T_('Fav.'); ?></dt>
        <dd class="<?php echo $rowparity; ?>">
            <div id="userflag_<?php echo $video->id; ?>_video"><?php Userflag::show($video->id, 'video'); ?>
            </div>
        </dd>
    <?php
        } ?>
<?php
    } ?>
<?php $rowparity = UI::flip_class(); ?>
<dt class="<?php echo $rowparity; ?>"><?php echo T_('Action'); ?></dt>
    <dd class="<?php echo $rowparity; ?>">
        <?php if (AmpConfig::get('directplay')) {
        ?>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=video&object_id=' . $video->id, 'play', T_('Play'), 'play_video_' . $video->id); ?>
            <?php if (Stream_Playlist::check_autoplay_append()) {
            ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=video&object_id=' . $video->id . '&append=true', 'play_add', T_('Play last'), 'addplay_video_' . $video->id); ?>
            <?php
        } ?>
        <?php
    } ?>
        <?php echo Ajax::button('?action=basket&type=video&id=' . $video->id, 'add', T_('Add to temporary playlist'), 'add_video_' . $video->id); ?>
        <?php if (!AmpConfig::get('use_auth') || Access::check('interface', '25')) {
        ?>
            <?php if (AmpConfig::get('sociable')) {
            ?>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/shout.php?action=show_add_shout&type=video&id=<?php echo $video->id; ?>"><?php echo UI::get_icon('comment', T_('Post Shout')); ?></a>
            <?php
        } ?>
        <?php
    }
    ?>
    <?php if (Access::check('interface', '25')) {
        ?>
            <?php if (AmpConfig::get('share')) {
            ?>
                <?php Share::display_ui('video', $video->id, false); ?>
            <?php
        } ?>
        <?php
    } ?>
        <?php if (Access::check_function('download')) {
        ?>
            <a rel="nohtml" href="<?php echo Video::play_url($video->id); ?>"><?php echo UI::get_icon('link', T_('Link')); ?></a>
            <a rel="nohtml" href="<?php echo AmpConfig::get('web_path'); ?>/stream.php?action=download&video_id=<?php echo $video->id; ?>"><?php echo UI::get_icon('download', T_('Download')); ?></a>
        <?php
    } ?>
        <?php if (Access::check('interface', '50')) {
        ?>
            <?php if (AmpConfig::get('statistical_graphs')) {
            ?>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/stats.php?action=graph&object_type=video&object_id=<?php echo $video->id; ?>"><?php echo UI::get_icon('statistics', T_('Graphs')); ?></a>
            <?php
        } ?>
            <a onclick="showEditDialog('video_row', '<?php echo $video->id ?>', '<?php echo 'edit_video_' . $video->id ?>', '<?php echo T_('Edit') ?>', '')">
                <?php echo UI::get_icon('edit', T_('Edit')); ?>
            </a>
        <?php
    } ?>
        <?php if (Catalog::can_remove($video)) {
        ?>
            <a id="<?php echo 'delete_video_' . $video->id ?>" href="<?php echo AmpConfig::get('web_path'); ?>/video.php?action=delete&video_id=<?php echo $video->id; ?>">
                <?php echo UI::get_icon('delete', T_('Delete')); ?>
            </a>
        <?php
    } ?>
    </dd>
<?php
  $videoprops[gettext_noop('Title')]   = scrub_out($video->f_title);
  $videoprops[gettext_noop('Length')]  = scrub_out($video->f_time);
if (strtolower(get_class($video)) != 'video') {
    require AmpConfig::get('prefix') . UI::find_template('show_partial_' . strtolower(get_class($video)) . '.inc.php');
}
  $videoprops[gettext_noop('Release Date')]    = scrub_out($video->f_release_date);
  $videoprops[gettext_noop('Codec')]           = scrub_out($video->f_codec);
  $videoprops[gettext_noop('Resolution')]      = scrub_out($video->f_resolution);
  $videoprops[gettext_noop('Display')]         = scrub_out($video->f_display);
  $videoprops[gettext_noop('Audio Bitrate')]   = scrub_out($video->f_bitrate);
  $videoprops[gettext_noop('Video Bitrate')]   = scrub_out($video->f_video_bitrate);
  $videoprops[gettext_noop('Frame Rate')]      = scrub_out($video->f_frame_rate);
  $videoprops[gettext_noop('Channels')]        = scrub_out($video->channels);
  if (Access::check('interface', '75')) {
      $videoprops[gettext_noop('Filename')]   = scrub_out($video->file) . " " . $video->f_size;
  }
  if ($video->update_time) {
      $videoprops[gettext_noop('Last Updated')]   = date("d/m/Y H:i", $video->update_time);
  }
  $videoprops[gettext_noop('Added')]   = date("d/m/Y H:i", $video->addition_time);
  if (AmpConfig::get('show_played_times')) {
      $videoprops[gettext_noop('# Played')]   = scrub_out($video->object_cnt);
  }

    foreach ($videoprops as $key => $value) {
        if (trim($value)) {
            $rowparity = UI::flip_class();
            echo "<dt class=\"" . $rowparity . "\">" . T_($key) . "</dt><dd class=\"" . $rowparity . "\">" . $value . "</dd>";
        }
    }
?>
</dl>
<?php UI::show_box_bottom(); ?>
