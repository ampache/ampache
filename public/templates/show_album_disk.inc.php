<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\System\Core;
use Ampache\Module\Util\AmpacheRss;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Repository\Model\Browse;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\ZipHandlerInterface;

/** @var Ampache\Repository\Model\AlbumDisk $albumDisk */
/** @var bool $isAlbumEditable */

global $dic;
$zipHandler = $dic->get(ZipHandlerInterface::class);
$batch_dl   = Access::check_function('batch_download');
$zip_albumD = $batch_dl && $zipHandler->isZipable('album_disk');
// Title for this album
$web_path = AmpConfig::get('web_path');
$simple   = $albumDisk->get_fullname(true);
$f_name   = $albumDisk->get_fullname(false, true);
$title    = ($albumDisk->album_artist !== null)
    ? scrub_out($f_name) . '&nbsp;-&nbsp;' . $albumDisk->get_f_artist_link()
    : scrub_out($f_name);

$access50          = Access::check('interface', 50);
$access25          = ($access50 || Access::check('interface', 25));
$show_direct_play  = AmpConfig::get('directplay');
$show_playlist_add = $access25;
$directplay_limit  = AmpConfig::get('direct_play_limit');
$hide_array        = (AmpConfig::get('hide_single_artist') && $albumDisk->get_artist_count() == 1)
    ? array('cel_artist', 'cel_album', 'cel_year', 'cel_drag')
    : array('cel_album', 'cel_year', 'cel_drag');

if ($directplay_limit > 0) {
    $show_playlist_add = ($albumDisk->song_count <= $directplay_limit);
    if ($show_direct_play) {
        $show_direct_play = $show_playlist_add;
    }
} ?>
<?php Ui::show_box_top($title, 'info-box'); ?>

<div class="item_right_info">
    <div class="external_links">
        <a href="http://www.google.com/search?q=%22<?php echo rawurlencode($albumDisk->get_artist_fullname()); ?>%22+%22<?php echo rawurlencode($simple); ?>%22" target="_blank"><?php echo Ui::get_icon('google', T_('Search on Google ...')); ?></a>
        <a href="https://www.duckduckgo.com/?q=%22<?php echo rawurlencode($albumDisk->f_artist_name); ?>%22+%22<?php echo rawurlencode($simple); ?>%22" target="_blank"><?php echo Ui::get_icon('duckduckgo', T_('Search on DuckDuckGo ...')); ?></a>
        <a href="http://en.wikipedia.org/wiki/Special:Search?search=%22<?php echo rawurlencode($simple); ?>%22&go=Go" target="_blank"><?php echo Ui::get_icon('wikipedia', T_('Search on Wikipedia ...')); ?></a>
        <a href="http://www.last.fm/search?q=%22<?php echo rawurlencode($albumDisk->f_artist_name); ?>%22+%22<?php echo rawurlencode($simple); ?>%22&type=album" target="_blank"><?php echo Ui::get_icon('lastfm', T_('Search on Last.fm ...')); ?></a>
    <?php if ($albumDisk->mbid) { ?>
        <a href="https://musicbrainz.org/release/<?php echo $albumDisk->mbid; ?>" target="_blank"><?php echo Ui::get_icon('musicbrainz', T_('Search on Musicbrainz ...')); ?></a>
    <?php } else { ?>
        <a href="https://musicbrainz.org/search?query=%22<?php echo rawurlencode($simple); ?>%22&type=release" target="_blank"><?php echo Ui::get_icon('musicbrainz', T_('Search on Musicbrainz ...')); ?></a>
    <?php } ?>
    </div>
    <?php
        $name  = '[' . scrub_out($albumDisk->f_artist_name) . '] ' . scrub_out($f_name);
        $thumb = Ui::is_grid_view('album') ? 32 : 11;
        Art::display('album', $albumDisk->album_id, $name, $thumb); ?>
</div>
<?php if (User::is_registered()) { ?>
    <?php if (AmpConfig::get('ratings')) { ?>
        <span id="rating_<?php echo $albumDisk->id; ?>_album_disk">
            <?php echo Rating::show($albumDisk->id, 'album_disk', true); ?>
        </span>
        <span id="userflag_<?php echo $albumDisk->id; ?>_album_disk">
            <?php echo Userflag::show($albumDisk->id, 'album_disk'); ?>
        </span>
        <?php } ?>
    <?php } ?>
<?php
if (AmpConfig::get('show_played_times')) { ?>
<br />
<div style="display:inline;">
    <?php echo T_('Played') . ' ' .
        /* HINT: Number of times an object has been played */
        sprintf(nT_('%d time', '%d times', $albumDisk->total_count), $albumDisk->total_count); ?>
</div>
<?php } ?>

<?php
$owner_id = $albumDisk->get_user_owner();
if (AmpConfig::get('sociable') && $owner_id > 0) {
    $owner = new User($owner_id); ?>
<div class="item_uploaded_by">
    <?php echo T_('Uploaded by'); ?> <?php echo $owner->get_f_link(); ?>
</div>
<?php
} ?>

<div id="information_actions">
    <h3><?php echo T_('Actions'); ?>:</h3>
    <ul>
        <?php if ($show_direct_play) {
        $play       = T_('Play');
        $playnext   = T_('Play next');
        $playlast   = T_('Play last'); ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=album_disk&object_id=' . $albumDisk->id, 'play', $play, 'directplay_full_' . $albumDisk->id); ?>
        </li>
            <?php if (Stream_Playlist::check_autoplay_next()) { ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=album_disk&object_id=' . $albumDisk->id . '&playnext=true', 'play_next', $playnext, 'nextplay_album_' . $albumDisk->id); ?>
        </li>
            <?php } ?>
            <?php if (Stream_Playlist::check_autoplay_append()) { ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=album_disk&object_id=' . $albumDisk->id . '&append=true', 'play_add', $playlast, 'addplay_album_' . $albumDisk->id); ?>
        </li>
            <?php } ?>
        <?php
    } ?>

        <?php if ($show_playlist_add) {
        $addtotemp  = T_('Add to Temporary Playlist');
        $randtotemp = T_('Random to Temporary Playlist');
        $addtoexist = T_('Add to playlist'); ?>
        <li>
            <?php echo Ajax::button_with_text('?action=basket&type=album_disk&id=' . $albumDisk->id, 'add', $addtotemp, 'play_full_' . $albumDisk->id); ?>
        </li>
        <li>
            <?php echo Ajax::button_with_text('?action=basket&type=album_disk_random&id=' . $albumDisk->id, 'random', $randtotemp, 'play_random_' . $albumDisk->id); ?>
        </li>
        <li>
            <a id="<?php echo 'add_playlist_' . $albumDisk->id ?>" onclick="showPlaylistDialog(event, 'album_disk', '<?php echo $albumDisk->id ?>')">
                <?php echo Ui::get_icon('playlist_add', $addtoexist); ?>
                <?php echo $addtoexist ?>
            </a>
        </li>
        <?php
    } ?>
        <?php if (AmpConfig::get('use_rss')) { ?>
        <li>
            <?php echo AmpacheRss::get_display('podcast', Core::get_global('user')->id, T_('RSS Feed'), array('object_type' => 'album', 'object_id' => $albumDisk->id)); ?>
        </li>
        <?php } ?>
        <?php if (!AmpConfig::get('use_auth') || $access25) { ?>
            <?php if (AmpConfig::get('sociable')) {
        $postshout = T_('Post Shout'); ?>
            <li>
                <a href="<?php echo $web_path; ?>/shout.php?action=show_add_shout&type=album_disk&id=<?php echo $albumDisk->id; ?>">
                    <?php echo Ui::get_icon('comment', $postshout); ?>
                    <?php echo $postshout; ?>
                </a>
            </li>
            <?php
    } ?>
        <?php } ?>
    <?php if ($access25) { ?>
            <?php if (AmpConfig::get('share')) { ?>
            <li>
                <?php echo Share::display_ui('album_disk', $albumDisk->id); ?>
            </li>
            <?php } ?>
        <?php } ?>
        <?php if (($owner_id > 0 && !empty(Core::get_global('user')) && $owner_id == (int) Core::get_global('user')->id) || $access50) {
        $saveorder  = T_('Save Track Order'); ?>
        <?php if (AmpConfig::get('statistical_graphs') && is_dir(__DIR__ . '/../../vendor/szymach/c-pchart/src/Chart/')) { ?>
            <li>
                <a href="<?php echo $web_path; ?>/stats.php?action=graph&object_type=album_disk&object_id=<?php echo $albumDisk->id; ?>">
                    <?php echo Ui::get_icon('statistics', T_('Graphs')); ?>
                    <?php echo T_('Graphs'); ?>
                </a>
            </li>
        <?php } ?>
        <li>
            <a href="javascript:NavigateTo('<?php echo $web_path; ?>/albums.php?action=update_disk_from_tags&amp;album_disk=<?php echo $albumDisk->id; ?>');" onclick="return confirm('<?php echo T_('Do you really want to update from tags?'); ?>');">
                <?php echo Ui::get_icon('file_refresh', T_('Update from tags')); ?>
                <?php echo T_('Update from tags'); ?>
            </a>
        </li>
        <?php
    } ?>
        <?php if ($isAlbumEditable) {
        $t_upload = T_('Upload');
        if (AmpConfig::get('allow_upload') && $albumDisk->album_artist > 0) { ?>
                <li>
                    <a href="<?php echo $web_path; ?>/upload.php?artist=<?php echo $albumDisk->album_artist; ?>&album=<?php echo $albumDisk->album_id ?>">
                        <?php echo Ui::get_icon('upload', $t_upload); ?>
                        <?php echo $t_upload; ?>
                    </a>
                </li>
            <?php } ?>
            <li>
                <a id="<?php echo 'edit_album_' . $albumDisk->album_id ?>" onclick="showEditDialog('album_row', '<?php echo $albumDisk->album_id ?>', '<?php echo 'edit_album_' . $albumDisk->album_id ?>', '<?php echo addslashes(T_('Album Edit')) ?>', '')">
                    <?php echo Ui::get_icon('edit', T_('Edit')); ?>
                    <?php echo T_('Edit Album'); ?>
                </a>
            </li>
            <?php
    } ?>
        <?php
        if ($zip_albumD) {
            $download   = T_('Download'); ?>
        <li>
            <a class="nohtml" href="<?php echo $web_path; ?>/batch.php?action=album_disk&id=<?php echo $albumDisk->id; ?>">
                <?php echo Ui::get_icon('batch_download', $download); ?>
                <?php echo $download; ?>
            </a>
        </li>
        <?php
        } ?>
        <?php if (Catalog::can_remove($albumDisk)) {
            $delete = T_('Delete'); ?>
        <li>
            <a id="<?php echo 'delete_album_' . $albumDisk->id ?>" href="<?php echo $web_path; ?>/albums.php?action=delete&album_id=<?php echo $albumDisk->id; ?>">
                <?php echo Ui::get_icon('delete', $delete); ?>
                <?php echo $delete; ?>
            </a>
        </li>
        <?php
        } ?>
    </ul>
</div>
<?php Ui::show_box_bottom(); ?>
<div id="additional_information">
&nbsp;
</div>
<div id='reordered_list_<?php echo $albumDisk->id; ?>'>
<?php
    $browse = new Browse();
    $browse->set_type('song');
    $browse->set_simple_browse(true);
    $browse->set_filter('album_disk', $albumDisk->id);
    $browse->set_sort('track', 'ASC');
    $browse->get_objects();
    $browse->show_objects(null, array('hide' => $hide_array));
    $browse->store(); ?>
</div>
