<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\Util\Ui;

/** @var Ampache\Repository\Model\Podcast_Episode $episode */

$web_path = (string)AmpConfig::get('web_path', '');

Ui::show_box_top($episode->get_fullname() . ' - ' . $episode->getPodcastLink(), 'box box_podcast_episode_details'); ?>
<dl class="media_details">
<?php if (User::is_registered()) { ?>
    <?php if (AmpConfig::get('ratings')) { ?>
        <dt><?php echo T_('Rating'); ?></dt>
        <dd>
            <div id="rating_<?php echo $episode->id; ?>_podcast_episode">
                <?php echo Rating::show($episode->id, 'podcast_episode'); ?>
            </div>
        </dd>
        <dt><?php echo T_('Fav.'); ?></dt>
        <dd>
            <div id="userflag_<?php echo $episode->id; ?>_podcast_episode">
                <?php echo Userflag::show($episode->id, 'podcast_episode'); ?>
            </div>
        </dd>
    <?php } ?>
<?php } ?>
    <?php if (AmpConfig::get('waveform')) { ?>
        <dt><?php echo T_('Waveform'); ?></dt>
        <dd>
            <div id="waveform_<?php echo $episode->id; ?>">
                <img src="<?php echo $web_path; ?>/waveform.php?podcast_episode=<?php echo $episode->id; ?>" />
            </div>
        </dd>
        <?php } ?>
<dt><?php echo T_('Action'); ?></dt>
    <dd>
        <?php if (!empty($episode->file)) { ?>
        <?php if (AmpConfig::get('directplay')) { ?>
            <?php echo Ajax::button('?page=stream&action=directplay&object_type=podcast_episode&object_id=' . $episode->id, 'play_circle', T_('Play'), 'play_podcast_episode_' . $episode->id); ?>
            <?php if (Stream_Playlist::check_autoplay_next()) { ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=podcast_episode&object_id=' . $episode->id . '&playnext=true', 'menu_open', T_('Play next'), 'addnext_podcast_episode_' . $episode->id); ?>
            <?php } ?>
            <?php if (Stream_Playlist::check_autoplay_append()) { ?>
                <?php echo Ajax::button('?page=stream&action=directplay&object_type=podcast_episode&object_id=' . $episode->id . '&append=true', 'playlist_add', T_('Play last'), 'addplay_podcast_episode_' . $episode->id); ?>
            <?php } ?>
        <?php } ?>
        <?php echo Ajax::button('?action=basket&type=podcast_episode&id=' . $episode->id, 'new_window', T_('Add to Temporary Playlist'), 'add_podcast_episode_' . $episode->id); ?>
        <?php } ?>
        <?php if (!AmpConfig::get('use_auth') || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) { ?>
            <?php if (AmpConfig::get('sociable')) { ?>
                <a href="<?php echo $web_path; ?>/shout.php?action=show_add_shout&type=podcast_episode&id=<?php echo $episode->id; ?>">
                <?php echo Ui::get_material_symbol('comment', T_('Post Shout')); ?>
                </a>
            <?php } ?>
        <?php } ?>
        <?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) { ?>
            <?php if (AmpConfig::get('share')) { ?>
                <?php echo Share::display_ui('podcast_episode', $episode->id, false); ?>
            <?php } ?>
        <?php } ?>
        <?php if (Access::check_function(AccessFunctionEnum::FUNCTION_DOWNLOAD) && !empty($episode->file)) { ?>
            <a class="nohtml" href="<?php echo $episode->play_url(); ?>"><?php echo Ui::get_material_symbol('link', T_('Link')); ?></a>
            <a class="nohtml" href="<?php echo $web_path; ?>/stream.php?action=download&podcast_episode_id=<?php echo $episode->id; ?>"><?php echo Ui::get_material_symbol('download', T_('Download')); ?></a>
        <?php } ?>
        <?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)) { ?>
            <?php if (AmpConfig::get('statistical_graphs') && is_dir(__DIR__ . '/../../vendor/szymach/c-pchart/src/Chart/')) { ?>
                <a href="<?php echo $web_path; ?>/stats.php?action=graph&object_type=podcast_episode&object_id=<?php echo $episode->id; ?>"><?php echo Ui::get_material_symbol('bar_chart', T_('Graphs')); ?></a>
            <?php } ?>
            <a onclick="showEditDialog('podcast_episode_row', '<?php echo $episode->id; ?>', '<?php echo 'edit_podcast_episode_' . $episode->id; ?>', '<?php echo addslashes(T_('Podcast Episode Edit')); ?>', '')">
                <?php echo Ui::get_material_symbol('edit', T_('Edit')); ?>
            </a>
        <?php } ?>
        <?php if (Catalog::can_remove($episode)) { ?>
            <a href="<?php echo $web_path; ?>/podcast_episode.php?action=delete&podcast_episode_id=<?php echo $episode->id; ?>">
                <?php echo Ui::get_material_symbol('close', T_('Delete')); ?>
            </a>
        <?php } ?>
    </dd>
<?php
    $songprops[T_('Title')]        = $episode->get_fullname();
$songprops[T_('Description')]      = $episode->get_description();
$songprops[T_('Category')]         = $episode->getCategory();
$songprops[T_('Author')]           = $episode->getAuthor();
$songprops[T_('Publication Date')] = $episode->getPubDate()->format(DATE_ATOM);
$songprops[T_('Status')]           = $episode->getState()->toDescription();
$songprops[T_('Website')]          = $episode->getWebsite();
if ($episode->time > 0) {
    $songprops[T_('Length')] = $episode->f_time;
}

if (!empty($episode->file)) {
    $songprops[T_('File')]     = $episode->file;
    $songprops[T_('Size')]     = $episode->getSizeFormatted();
    $songprops[T_('Bitrate')]  = scrub_out($episode->getBitrateFormatted());
    $songprops[T_('Channels')] = scrub_out((string)$episode->channels);
}

foreach ($songprops as $key => $value) {
    if (trim($value)) {
        echo "<dt>" . T_($key) . "</dt><dd>" . $value . "</dd>";
    }
} ?>
</dl>
<?php Ui::show_box_bottom(); ?>
