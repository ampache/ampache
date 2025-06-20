<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\Model\Video;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;

/** @var Video $video */

$web_path = AmpConfig::get_web_path();

$fullname = $video->get_fullname() ?? '';
Ui::show_box_top($fullname, 'box box_video_details'); ?>
<div class="item_right_info">
<?php
$gart = Art::display('video', $video->id, $fullname, ['width' => 200, 'height' => 300], null, true, false); ?>
<?php if (AmpConfig::get('encode_srt')) { ?>
<div class="subtitles">
<?php echo T_('Subtitle'); ?>:
<select name="subtitle" id="play_setting_subtitle">
    <option value=''><?php echo T_("None"); ?></option>
    <?php $subtitles = ($video->file) ? $video->get_subtitles() : [];
    foreach ($subtitles as $subtitle) {
        echo "<option value='" . $subtitle['lang_code'] . "' ";
        if (array_key_exists('iframe', $_SESSION) && array_key_exists('subtitle', $_SESSION['iframe']) && $_SESSION['iframe']['subtitle'] == $subtitle['lang_code']) {
            echo "selected";
        }
        echo ">" . $subtitle['lang_name'] . "</option>";
    } ?>
</select>
</div>
<?php } ?>
</div>
<dl class="media_details">
<?php if (User::is_registered() && AmpConfig::get('ratings')) { ?>
    <dt><?php echo T_('Rating'); ?></dt>
    <dd>
        <div id="rating_<?php echo $video->id; ?>_video">
            <?php echo Rating::show($video->id, 'video'); ?>
        </div>
    </dd>
    <dt><?php echo T_('Fav.'); ?></dt>
    <dd>
        <div id="userflag_<?php echo $video->id; ?>_video">
            <?php echo Userflag::show($video->id, 'video'); ?>
        </div>
    </dd>
<?php } ?>
<dt><?php echo T_('Action'); ?></dt>
    <dd>
        <?php if (AmpConfig::get('directplay')) { ?>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=video&object_id=' . $video->id, 'play_circle', T_('Play'), 'play_video_' . $video->id); ?>
            <?php if (Stream_Playlist::check_autoplay_next()) { ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=video&object_id=' . $video->id . '&playnext=true', 'menu_open', T_('Play next'), 'nextplay_video_' . $video->id); ?>
                <?php } ?>
            <?php if (Stream_Playlist::check_autoplay_append()) { ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=video&object_id=' . $video->id . '&append=true', 'low_priority', T_('Play last'), 'addplay_video_' . $video->id); ?>
            <?php } ?>
        <?php } ?>
        <?php echo Ajax::button('?action=basket&type=video&id=' . $video->id, 'new_window', T_('Add to Temporary Playlist'), 'add_video_' . $video->id); ?>
        <?php if (!AmpConfig::get('use_auth') || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) { ?>
            <?php if (AmpConfig::get('sociable')) { ?>
                <a href="<?php echo $web_path; ?>/shout.php?action=show_add_shout&type=video&id=<?php echo $video->id; ?>"><?php echo Ui::get_material_symbol('comment', T_('Post Shout')); ?></a>
            <?php } ?>
        <?php } ?>
    <?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER) && AmpConfig::get('share')) { ?>
            <?php echo Share::display_ui('video', $video->id, false); ?>
        <?php } else {
            $link = "&nbsp;" . T_('Link'); ?>
        <li>
            <a href="<?php echo $video->get_link(); ?>" target=_blank>
                <?php echo Ui::get_material_symbol('open_in_new', $link);
            echo $link; ?>
            </a>
        </li>
    <?php } ?>
        <?php if (Access::check_function(AccessFunctionEnum::FUNCTION_DOWNLOAD)) { ?>
            <a class="nohtml" href="<?php echo $video->play_url(); ?>"><?php echo Ui::get_material_symbol('link', T_('Link')); ?></a>
            <a class="nohtml" href="<?php echo $web_path; ?>/stream.php?action=download&video_id=<?php echo $video->id; ?>"><?php echo Ui::get_material_symbol('download', T_('Download')); ?></a>
        <?php } ?>
        <?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)) { ?>
            <?php if (AmpConfig::get('statistical_graphs') && is_dir(__DIR__ . '/../vendor/szymach/c-pchart/src/Chart/')) { ?>
                <a href="<?php echo $web_path; ?>/stats.php?action=graph&object_type=video&object_id=<?php echo $video->id; ?>"><?php echo Ui::get_material_symbol('bar_chart', T_('Graphs')); ?></a>
            <?php } ?>
            <a onclick="showEditDialog('video_row', '<?php echo $video->id; ?>', '<?php echo 'edit_video_' . $video->id; ?>', '<?php echo addslashes(T_('Video Edit')); ?>', '')">
                <?php echo Ui::get_material_symbol('edit', T_('Edit')); ?>
            </a>
        <?php } ?>
        <?php if (Catalog::can_remove($video)) { ?>
            <a id="<?php echo 'delete_video_' . $video->id; ?>" href="<?php echo $web_path; ?>/video.php?action=delete&video_id=<?php echo $video->id; ?>">
                <?php echo Ui::get_material_symbol('close', T_('Delete')); ?>
            </a>
        <?php } ?>
    </dd>
<?php
$videoprops[T_('Title')]  = scrub_out($fullname);
$videoprops[T_('Length')] = scrub_out($video->get_f_time());
if (get_class($video) != Video::class) {
    require Ui::find_template('show_partial_' . $video->getMediaType()->value . '.inc.php');
}
$videoprops[T_('Release Date')]  = scrub_out(($video->release_date) ? get_datetime((int) $video->release_date, 'short', 'none') : '');
$videoprops[T_('Codec')]         = scrub_out($video->video_codec . ' / ' . $video->audio_codec);
$videoprops[T_('Resolution')]    = scrub_out($video->get_f_resolution());
$videoprops[T_('Display')]       = scrub_out($video->get_f_display());
$videoprops[T_('Audio Bitrate')] = scrub_out((int) ($video->bitrate / 1024) . "-" . strtoupper((string) $video->mode));
$videoprops[T_('Video Bitrate')] = scrub_out((string)($video->video_bitrate / 1024));
$videoprops[T_('Frame Rate')]    = scrub_out(($video->frame_rate) ? $video->frame_rate . ' fps' : '');
$videoprops[T_('Channels')]      = scrub_out((string)$video->channels);
if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::MANAGER)) {
    $data                       = pathinfo($video->file);
    $videoprops[T_('Path')]     = scrub_out((string)($data['dirname'] ?? ''));
    $videoprops[T_('Filename')] = (isset($data['extension']))
        ? scrub_out($data['filename'] . "." . $data['extension'])
        : '';
    $videoprops[T_('Size')] = Ui::format_bytes($video->size);
}
if ($video->update_time) {
    $videoprops[T_('Last Updated')] = get_datetime((int) $video->update_time);
}
$videoprops[T_('Added')] = get_datetime((int) $video->addition_time);
if (AmpConfig::get('show_played_times')) {
    $videoprops[T_('Played')] = scrub_out((string)$video->total_count);
}

foreach ($videoprops as $key => $value) {
    if (trim((string)$value)) {
        echo "<dt>" . T_($key) . "</dt><dd>" . $value . "</dd>";
    }
} ?>
</dl>
<?php Ui::show_box_bottom(); ?>
