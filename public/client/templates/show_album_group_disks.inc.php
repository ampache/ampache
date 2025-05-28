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
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Rss\Type\RssFeedTypeEnum;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\Upload;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Share;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;

/** @var Album $album */
/** @var bool $isAlbumEditable */

$web_path = AmpConfig::get_web_path('/client');
// Title for this album
$f_album_name     = (string)$album->get_artist_fullname();
$simple           = (string)$album->get_fullname(true);
$f_name           = (string)$album->get_fullname(false, true);
$url_f_album_name = rawurlencode($f_album_name);
$url_f_simple     = rawurlencode($simple);
$title            = ($album->album_artist > 0)
    ? scrub_out($f_name) . '&nbsp;-&nbsp;' . ((string)$album->get_f_parent_link())
    : scrub_out($f_name);

$current_user         = Core::get_global('user');
$access50             = Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER);
$access25             = ($access50 || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER));
$show_playlist_add    = $access25;
$show_direct_play_cfg = AmpConfig::get('directplay');
$directplay_limit     = AmpConfig::get('direct_play_limit');
$hide_array           = (AmpConfig::get('hide_single_artist') && $album->get_artist_count() == 1)
    ? ['cel_artist', 'cel_album', 'cel_year', 'cel_drag']
    : ['cel_album', 'cel_year', 'cel_drag'];

$show_direct_play = $show_direct_play_cfg;
if ($directplay_limit > 0) {
    $show_playlist_add = ($album->song_count <= $directplay_limit);
    if ($show_direct_play) {
        $show_direct_play = $show_playlist_add;
    }
}
global $dic; // @todo remove after refactoring
$zipHandler = $dic->get(ZipHandlerInterface::class);
$batch_dl   = Access::check_function(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD);
$zip_album  = $batch_dl && $zipHandler->isZipable('album');
$zip_albumD = $batch_dl && $zipHandler->isZipable('album_disk');
$can_shout  = AmpConfig::get('sociable');
$can_share  = AmpConfig::get('share');

Ui::show_box_top($title, 'info-box'); ?>
<div class="item_right_info">
    <div class="external_links">
<?php if (AmpConfig::get('external_links_google')) {
    echo "<a href=\"https://www.google.com/search?q=%22" . $url_f_album_name . "%22+%22" . $url_f_simple . "%22\" target=\"_blank\">" . Ui::get_icon('google', T_('Search on Google ...')) . "</a>";
}
if (AmpConfig::get('external_links_duckduckgo')) {
    echo "<a href=\"https://www.duckduckgo.com/?q=" . $url_f_album_name . "+" . $url_f_simple . "\" target=\"_blank\">" . Ui::get_icon('duckduckgo', T_('Search on DuckDuckGo ...')) . "</a>";
}
if (AmpConfig::get('external_links_wikipedia')) {
    echo "<a href=\"https://en.wikipedia.org/wiki/Special:Search?search=%22" . $url_f_simple . "%22&go=Go\" target=\"_blank\">" . Ui::get_icon('wikipedia', T_('Search on Wikipedia ...')) . "</a>";
}
if (AmpConfig::get('external_links_lastfm')) {
    echo "<a href=\"https://www.last.fm/search?q=%22" . $url_f_album_name . "%22+%22" . $url_f_simple . "%22&type=album\" target=\"_blank\">" . Ui::get_icon('lastfm', T_('Search on Last.fm ...')) . "</a>";
}
if (AmpConfig::get('external_links_bandcamp')) {
    echo "<a href=\"https://bandcamp.com/search?q=" . $url_f_album_name . "+" . $url_f_simple . "&item_type=a\" target=\"_blank\">" . Ui::get_icon('bandcamp', T_('Search on Bandcamp ...')) . "</a>";
}
if (AmpConfig::get('external_links_discogs')) {
    echo "<a href=\"https://www.discogs.com/search/?q=" . rawurlencode(($f_album_name == 'Various Artists') ? 'Various' : $f_album_name) . "+" . $url_f_simple . "&type=master\" target=\"_blank\">" . Ui::get_icon('discogs', T_('Search on Discogs ...')) . "</a>";
}
if (AmpConfig::get('external_links_musicbrainz')) {
    if ($album->mbid) {
        echo "<a href=\"https://musicbrainz.org/release/" . $album->mbid . "\" target=\"_blank\">" . Ui::get_icon('musicbrainz', T_('Search on Musicbrainz ...')) . "</a>";
    } else {
        echo "<a href=\"https://musicbrainz.org/search?query=%22" . $url_f_simple . "%22&type=release\" target=\"_blank\">" . Ui::get_icon('musicbrainz', T_('Search on Musicbrainz ...')) . "</a>";
    }
} ?>
    </div>
    <?php
    if ($album->name != T_('Unknown (Orphaned)')) {
        $name = '[' . $f_album_name . '] ' . scrub_out($f_name);
        Art::display('album', $album->id, $name, ['width' => 384, 'height' => 384], null, true, false);
    } ?>
</div>
<?php if (User::is_registered()) {
    if (AmpConfig::get('ratings')) { ?>
        <span id="rating_<?php echo $album->id; ?>_album">
        <?php echo Rating::show($album->id, 'album'); ?>
    </span>
        <span id="userflag_<?php echo $album->id; ?>_album">
        <?php echo Userflag::show($album->id, 'album'); ?>
    </span>
    <?php } ?>
<?php
} ?>
<?php if (AmpConfig::get('show_played_times')) { ?>
    <br />
    <div style="display:inline;">
        <?php echo T_('Played') . ' ' .
        /* HINT: Number of times an object has been played */
        sprintf(nT_('%d time', '%d times', $album->total_count), $album->total_count); ?>
    </div>
<?php } ?>
<?php
$owner_id = $album->get_user_owner();
if (AmpConfig::get('sociable') && $owner_id > 0) {
    $owner = new User($owner_id); ?>
    <div class="item_uploaded_by">
        <?php echo T_('Uploaded by'); ?> <?php echo $owner->get_f_link(); ?>
    </div>
    <?php
} ?>
<div id="information_actions">
    <h3><?php echo T_('Actions'); ?></h3>
    <ul>
<?php if ($show_direct_play) {
    $play     = T_('Play');
    $playnext = T_('Play next');
    $playlast = T_('Play last'); ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=album&object_id=' . $album->id, 'play_circle', $play, 'directplay_full_' . $album->id); ?>
        </li>
    <?php if (Stream_Playlist::check_autoplay_next()) { ?>
            <li>
                <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=album&object_id=' . $album->id . '&playnext=true', 'menu_open', $playnext, 'nextplay_album_' . $album->id); ?>
            </li>
    <?php }
    if (Stream_Playlist::check_autoplay_append()) { ?>
            <li>
                <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=album&object_id=' . $album->id . '&append=true', 'low_priority', $playlast, 'addplay_album_' . $album->id); ?>
            </li>
    <?php }
    }
if ($show_playlist_add) {
    $addtotemp  = T_('Add to Temporary Playlist');
    $randtotemp = T_('Random to Temporary Playlist');
    $addtoexist = T_('Add to playlist'); ?>
        <li>
            <?php echo Ajax::button_with_text('?action=basket&type=album&id=' . $album->id, 'add_circle', $addtotemp, 'play_full_' . $album->id); ?>
        </li>
        <li>
            <?php echo Ajax::button_with_text('?action=basket&type=album_random&id=' . $album->id, 'shuffle', $randtotemp, 'play_random_' . $album->id); ?>
        </li>
        <li>
            <a id="<?php echo 'add_to_playlist_' . $album->id; ?>" onclick="showPlaylistDialog(event, 'album', '<?php echo $album->id; ?>')">
                <?php echo Ui::get_material_symbol('playlist_add', $addtoexist);
    echo $addtoexist; ?>
            </a>
        </li>
<?php
}
if (AmpConfig::get('use_rss')) { ?>
        <li>
            <?php echo Ui::getRssLink(
                RssFeedTypeEnum::LIBRARY_ITEM,
                $current_user,
                T_('RSS Feed'),
                ['object_type' => 'album', 'object_id' => (string)$album->id]
            ); ?>
        </li>
<?php }
if (!AmpConfig::get('use_auth') || $access25) {
    if (AmpConfig::get('sociable')) {
        $postshout = "&nbsp;" . T_('Post Shout'); ?>
            <li>
                <a href="<?php echo $web_path; ?>/shout.php?action=show_add_shout&type=album&id=<?php echo $album->id; ?>">
                    <?php echo Ui::get_material_symbol('comment', $postshout);
        echo $postshout; ?>
                </a>
            </li>
    <?php
    }
}
if ($access25 && AmpConfig::get('share')) { ?>
            <li>
                <?php echo Share::display_ui('album', $album->id); ?>
            </li>
    <?php } else {
        $link = "&nbsp;" . T_('Link'); ?>
    <li>
        <a href="<?php echo $album->get_link(); ?>" target=_blank>
            <?php echo Ui::get_material_symbol('open_in_new', $link);
        echo $link; ?>
        </a>
    </li>
<?php }
    if ((!empty($owner_id) && $owner_id == $current_user?->getId()) || $access50) {
        if (AmpConfig::get('statistical_graphs') && is_dir(__DIR__ . '/../../../vendor/szymach/c-pchart/src/Chart/')) { ?>
            <li>
                <a href="<?php echo $web_path; ?>/stats.php?action=graph&object_type=album&object_id=<?php echo $album->id; ?>">
                    <?php echo Ui::get_material_symbol('bar_chart', T_('Graphs'));
            echo T_('Graphs'); ?>
                </a>
            </li>
    <?php } ?>
        <li>
            <a href="javascript:NavigateTo('<?php echo $web_path; ?>/albums.php?action=update_from_tags&album_id=<?php echo $album->id; ?>');" onclick="return confirm('<?php echo T_('Do you really want to update from tags?'); ?>');">
                <?php echo Ui::get_material_symbol('sync_alt', T_('Update from tags'));
        echo "&nbsp;" . T_('Update from tags'); ?>
            </a>
        </li>
<?php
    }
if ($isAlbumEditable) {
    $t_upload = "&nbsp;" . T_('Upload');
    if (Upload::can_upload($current_user) && $album->album_artist > 0) { ?>
                <li>
                    <a href="<?php echo $web_path; ?>/upload.php?artist=<?php echo $album->album_artist; ?>&album=<?php echo $album->id; ?>">
                        <?php echo Ui::get_material_symbol('upload', $t_upload);
        echo $t_upload; ?>
                    </a>
                </li>
    <?php } ?>
            <li>
                <a id="<?php echo 'edit_album_' . $album->id; ?>" onclick="showEditDialog('album_row', '<?php echo $album->id; ?>', '<?php echo 'edit_album_' . $album->id; ?>', '<?php echo addslashes(T_('Album Edit')); ?>', '')">
                    <?php echo Ui::get_material_symbol('edit', T_('Edit'));
    echo "&nbsp;" . T_('Edit Album'); ?>
                </a>
            </li>
<?php
}
if ($zip_album) {
    $download = "&nbsp;" . T_('Download'); ?>
            <li>
                <a class="nohtml" href="<?php echo $web_path; ?>/batch.php?action=album&id=<?php echo $album->id; ?>">
                    <?php echo Ui::get_material_symbol('folder_zip', $download);
    echo $download; ?>
                </a>
            </li>
            <?php
}
if (Catalog::can_remove($album)) {
    $delete = T_('Delete'); ?>
            <li>
                <a id="<?php echo 'delete_album_' . $album->id; ?>" href="<?php echo $web_path; ?>/albums.php?action=delete&album_id=<?php echo $album->id; ?>">
                    <?php echo Ui::get_material_symbol('close', $delete);
    echo $delete; ?>
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
<?php
define('TABLE_RENDERED', 1);
foreach ($album->getDisks() as $album_disk) {
    $sub_title  = (!empty($album_disk->disksubtitle))
        ? $album_disk->get_f_link() . "<span class=\"discnb disc" . $album_disk->disk . "\">: " . scrub_out($album_disk->disksubtitle) . "</span>"
        : $album_disk->get_f_link();
    if ($directplay_limit > 0) {
        $show_playlist_add = ($album_disk->song_count <= $directplay_limit);
        if ($show_direct_play) {
            $show_direct_play = $show_playlist_add;
        }
    } ?>
    <div class="album_group_disks_title"><span><?php echo $sub_title; ?></span></div>
    <div class="album_group_disks_actions">
        <?php
        if ($show_direct_play) {
            echo Ajax::button('?page=stream&action=directplay&object_type=album_disk&object_id=' . $album_disk->id, 'play_circle', T_('Play'), 'directplay_full_' . $album_disk->id);
            if (Stream_Playlist::check_autoplay_next()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=album_disk&object_id=' . $album_disk->id . '&playnext=true', 'menu_open', T_('Play next'), 'nextplay_album_disk_' . $album_disk->id);
            }
            if (Stream_Playlist::check_autoplay_append()) {
                echo Ajax::button('?page=stream&action=directplay&object_type=album_disk&object_id=' . $album_disk->id . '&append=true', 'low_priority', T_('Play last'), 'addplay_album_disk_' . $album_disk->id);
            }
        }
    if ($show_playlist_add) {
        echo Ajax::button('?action=basket&type=album_disk&id=' . $album_disk->id, 'new_window', T_('Add to Temporary Playlist'), 'play_full_' . $album_disk->id);
        echo Ajax::button('?action=basket&type=album_disk_random&id=' . $album_disk->id, 'shuffle', T_('Random to Temporary Playlist'), 'play_random_' . $album_disk->id);
    }
    if ((!AmpConfig::get('use_auth') || $access25) && $can_shout) { ?>
            <a href="<?php echo $web_path; ?>/shout.php?action=show_add_shout&type=album_disk&id=<?php echo $album_disk->id; ?>"><?php echo Ui::get_material_symbol('comment', T_('Post Shout')); ?></a>
    <?php }
    if ($access25 && $can_share) {
        echo Share::display_ui('album_disk', $album_disk->id, false);
    } else {
        $link = "&nbsp;" . T_('Link'); ?>
        <li>
            <a href="<?php echo $album_disk->get_link(); ?>" target=_blank>
                <?php echo Ui::get_material_symbol('open_in_new', $link);
        echo $link; ?>
            </a>
        </li>
    <?php }
    if ($isAlbumEditable) { ?>
        <a id="<?php echo 'edit_album_disk_' . $album_disk->id; ?>" onclick="showEditDialog('album_disk_row', '<?php echo $album_disk->id; ?>', '<?php echo 'edit_album_disk_' . $album_disk->id; ?>', '<?php echo addslashes(T_('Album Edit')); ?>', '')">
                    <?php echo Ui::get_material_symbol('edit', T_('Edit')); ?>
                </a>
    <?php }
    if ($zip_albumD) { ?>
            <a class="nohtml" href="<?php echo $web_path; ?>/batch.php?action=album_disk&id=<?php echo $album_disk->id; ?>"><?php echo Ui::get_material_symbol('folder_zip', T_('Download')); ?></a>
        <?php } ?>
    </div>
    <div id='reordered_list_<?php echo $album_disk->id; ?>'>
        <?php
        $browse = new Browse();
    $browse->set_show_header(false);
    $browse->set_type('song');
    $browse->set_simple_browse(true);
    $browse->set_skip_catalog_check(true);
    $browse->set_sort('track', 'ASC');
    $browse->set_filter('album_disk', $album_disk->id);
    $browse->get_objects();
    $browse->show_objects([], ['hide' => $hide_array]);
    $browse->store(); ?>
    </div><br />
    <?php
} ?>
