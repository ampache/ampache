<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Movie;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\Model\Video;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\ObjectTypeToClassNameMapper;
use Ampache\Module\Util\Ui;

?>
<?php Ui::show_box_top($video->f_title . ' ' . T_('Details'), 'box box_video_details'); ?>
<div class="item_right_info">
<?php
$gart = false;
// The release type is not the video itself, we probably want preview
if (get_class($video) != Movie::class) {
    $gart = Art::display('video', $video->id, $video->f_title, 8, null, false, 'preview');
}
if (!$gart) {
    $gart = Art::display('video', $video->id, $video->f_title, 7);
} ?>
<?php if (AmpConfig::get('encode_srt')) { ?>
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
<?php if (User::is_registered()) { ?>
    <?php if (AmpConfig::get('ratings')) { ?>
        <dt><?php echo T_('Rating'); ?></dt>
        <dd>
            <div id="rating_<?php echo $video->id; ?>_video">
                <?php echo Rating::show($video->id, 'video'); ?>
            </div>
        </dd>
    <?php
        } ?>

    <?php if (AmpConfig::get('userflags')) { ?>
        <dt><?php echo T_('Fav.'); ?></dt>
        <dd>
            <div id="userflag_<?php echo $video->id; ?>_video">
                <?php echo Userflag::show($video->id, 'video'); ?>
            </div>
        </dd>
    <?php
        } ?>
<?php
    } ?>
<dt><?php echo T_('Action'); ?></dt>
    <dd>
        <?php if (AmpConfig::get('directplay')) { ?>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=video&object_id=' . $video->id, 'play', T_('Play'), 'play_video_' . $video->id); ?>
            <?php if (Stream_Playlist::check_autoplay_next()) { ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=video&object_id=' . $video->id . '&playnext=true', 'play_next', T_('Play next'), 'nextplay_video_' . $video->id); ?>
                <?php
            } ?>
            <?php if (Stream_Playlist::check_autoplay_append()) { ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=video&object_id=' . $video->id . '&append=true', 'play_add', T_('Play last'), 'addplay_video_' . $video->id); ?>
            <?php
        } ?>
        <?php
    } ?>
        <?php echo Ajax::button('?action=basket&type=video&id=' . $video->id, 'add', T_('Add to Temporary Playlist'), 'add_video_' . $video->id); ?>
        <?php if (!AmpConfig::get('use_auth') || Access::check('interface', 25)) { ?>
            <?php if (AmpConfig::get('sociable')) { ?>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/shout.php?action=show_add_shout&type=video&id=<?php echo $video->id; ?>"><?php echo Ui::get_icon('comment', T_('Post Shout')); ?></a>
            <?php
        } ?>
        <?php
    } ?>
    <?php if (Access::check('interface', 25)) { ?>
            <?php if (AmpConfig::get('share')) { ?>
                <?php echo Share::display_ui('video', $video->id, false); ?>
            <?php
        } ?>
        <?php
    } ?>
        <?php if (Access::check_function('download')) { ?>
            <a class="nohtml" href="<?php echo $video->play_url(); ?>"><?php echo Ui::get_icon('link', T_('Link')); ?></a>
            <a class="nohtml" href="<?php echo AmpConfig::get('web_path'); ?>/stream.php?action=download&video_id=<?php echo $video->id; ?>"><?php echo Ui::get_icon('download', T_('Download')); ?></a>
        <?php
    } ?>
        <?php if (Access::check('interface', 50)) { ?>
            <?php if (AmpConfig::get('statistical_graphs') && is_dir(__DIR__ . '/../../vendor/szymach/c-pchart/src/Chart/')) { ?>
                <a href="<?php echo AmpConfig::get('web_path'); ?>/stats.php?action=graph&object_type=video&object_id=<?php echo $video->id; ?>"><?php echo Ui::get_icon('statistics', T_('Graphs')); ?></a>
            <?php
        } ?>
            <a onclick="showEditDialog('video_row', '<?php echo $video->id ?>', '<?php echo 'edit_video_' . $video->id ?>', '<?php echo T_('Video Edit') ?>', '')">
                <?php echo Ui::get_icon('edit', T_('Edit')); ?>
            </a>
        <?php
    } ?>
        <?php if (Catalog::can_remove($video)) { ?>
            <a id="<?php echo 'delete_video_' . $video->id ?>" href="<?php echo AmpConfig::get('web_path'); ?>/video.php?action=delete&video_id=<?php echo $video->id; ?>">
                <?php echo Ui::get_icon('delete', T_('Delete')); ?>
            </a>
        <?php
    } ?>
    </dd>
<?php
  $videoprops[T_('Title')]   = scrub_out($video->f_title);
  $videoprops[T_('Length')]  = scrub_out($video->f_time);
if (get_class($video) != Video::class) {
    require Ui::find_template('show_partial_' . ObjectTypeToClassNameMapper::reverseMap(get_class($video)) . '.inc.php');
}
  $videoprops[T_('Release Date')]    = scrub_out($video->f_release_date);
  $videoprops[T_('Codec')]           = scrub_out($video->f_codec);
  $videoprops[T_('Resolution')]      = scrub_out($video->f_resolution);
  $videoprops[T_('Display')]         = scrub_out($video->f_display);
  $videoprops[T_('Audio Bitrate')]   = scrub_out($video->f_bitrate);
  $videoprops[T_('Video Bitrate')]   = scrub_out($video->f_video_bitrate);
  $videoprops[T_('Frame Rate')]      = scrub_out($video->f_frame_rate);
  $videoprops[T_('Channels')]        = scrub_out($video->channels);
  if (Access::check('interface', 75)) {
      $videoprops[T_('Filename')]   = scrub_out($video->file) . " " . $video->f_size;
  }
  if ($video->update_time) {
      $videoprops[T_('Last Updated')]   = get_datetime((int) $video->update_time);
  }
  $videoprops[T_('Added')]   = get_datetime((int) $video->addition_time);
  if (AmpConfig::get('show_played_times')) {
      $videoprops[T_('# Played')]   = scrub_out($video->object_cnt);
  }

    foreach ($videoprops as $key => $value) {
        if (trim($value)) {
            echo "<dt>" . T_($key) . "</dt><dd>" . $value . "</dd>";
        }
    } ?>
</dl>
<?php Ui::show_box_bottom(); ?>
