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
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\AccessFunctionEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\Playback\Stream_Playlist;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Rss\Type\RssFeedTypeEnum;
use Ampache\Module\Util\Ui;
use Ampache\Module\Util\Upload;
use Ampache\Module\Util\ZipHandlerInterface;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\Catalog;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;

/** @var Artist $artist */
/** @var array $multi_object_ids */
/** @var array $object_ids */
/** @var string $object_type */
/** @var GuiGatekeeperInterface $gatekeeper */

$web_path          = (string)AmpConfig::get('web_path', '');
$show_direct_play  = AmpConfig::get('directplay');
$show_playlist_add = Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER);
$show_similar      = AmpConfig::get('show_similar');
$directplay_limit  = (int)AmpConfig::get('direct_play_limit', 0);
$use_label         = AmpConfig::get('label');
$use_wanted        = AmpConfig::get('wanted');
$is_album_type     = $object_type == 'album' || $object_type == 'album_disk';

if ($directplay_limit > 0) {
    $show_playlist_add = ($artist->song_count <= $directplay_limit);
    if ($show_direct_play) {
        $show_direct_play = $show_playlist_add;
    }
}
/** @var User $current_user */
$current_user = Core::get_global('user');
$f_name       = (string)$artist->get_fullname();
$title        = scrub_out($f_name);
Ui::show_box_top($title, 'info-box'); ?>
<div class="item_right_info">
    <div class="external_links">
        <a href="https://www.google.com/search?q=%22<?php echo rawurlencode($f_name); ?>%22" target="_blank"><?php echo Ui::get_icon('google', T_('Search on Google ...')); ?></a>
        <a href="https://www.duckduckgo.com/?q=%22<?php echo rawurlencode($f_name); ?>%22" target="_blank"><?php echo Ui::get_icon('duckduckgo', T_('Search on DuckDuckGo ...')); ?></a>
        <a href="https://en.wikipedia.org/wiki/Special:Search?search=%22<?php echo rawurlencode($f_name); ?>%22&go=Go" target="_blank"><?php echo Ui::get_icon('wikipedia', T_('Search on Wikipedia ...')); ?></a>
        <a href="https://www.last.fm/search?q=%22<?php echo rawurlencode($f_name); ?>%22&type=artist" target="_blank"><?php echo Ui::get_icon('lastfm', T_('Search on Last.fm ...')); ?></a>
    <?php if (!empty($artist->mbid)) { ?>
        <a href="https://musicbrainz.org/artist/<?php echo $artist->mbid; ?>" target="_blank"><?php echo Ui::get_icon('musicbrainz', T_('Search on Musicbrainz ...')); ?></a>
    <?php } else { ?>
        <a href="https://musicbrainz.org/search?query=%22<?php echo rawurlencode($f_name); ?>%22&type=artist" target="_blank"><?php echo Ui::get_icon('musicbrainz', T_('Search on Musicbrainz ...')); ?></a>
    <?php } ?>
    </div>
<?php if (AmpConfig::get('lastfm_api_key')) {
    echo Ajax::observe('window', 'load', Ajax::action('?page=index&action=artist_info&artist=' . $artist->id, 'artist_info')); ?>
        <div id="artist_biography">
            <?php echo T_('Loading...'); ?>
        </div>
<?php } else {
    $thumb = 32;
    Art::display('artist', $artist->id, $title, $thumb);
} ?>
</div>
<?php if (User::is_registered()) { ?>
    <?php if (AmpConfig::get('ratings')) { ?>
    <span id="rating_<?php echo (int) ($artist->id); ?>_artist">
        <?php echo Rating::show($artist->id, 'artist', true); ?>
    </span>
    <span id="userflag_<?php echo $artist->id; ?>_artist">
        <?php echo Userflag::show($artist->id, 'artist'); ?>
    </span>
    <?php }
    }
if (AmpConfig::get('show_played_times')) { ?>
<br />
<div style="display:inline;"><?php echo T_('Played') . ' ' .
            /* HINT: Number of times an object has been played */
            sprintf(nT_('%d time', '%d times', $artist->total_count), $artist->total_count); ?>
</div>
<?php }
$owner_id = $artist->get_user_owner();
if (AmpConfig::get('sociable') && $owner_id > 0) {
    $owner = new User($owner_id); ?>
<div class="item_uploaded_by">
    <?php echo T_('Uploaded by'); ?> <?php echo $owner->get_f_link(); ?>
</div>
<?php } ?>
<div id="information_actions">
    <h3><?php echo T_('Actions'); ?>:</h3>
    <ul>
<?php if ($is_album_type) {
    $original_year = AmpConfig::get('use_original_year') ? "original_year" : "year";
    $sort_type     = AmpConfig::get('album_sort');
    switch ($sort_type) {
        case 'name_asc':
            $sort  = 'name';
            $order = 'ASC';
            break;
        case 'name_desc':
            $sort  = 'name';
            $order = 'DESC';
            break;
        case 'year_asc':
            $sort  = $original_year;
            $order = 'ASC';
            break;
        case 'year_desc':
            $sort  = $original_year;
            $order = 'DESC';
            break;
        default:
            $sort  = 'name_' . $original_year;
            $order = 'ASC';
    } ?>
        <li>
            <a href="<?php echo $web_path; ?>/artists.php?action=show_songs&amp;artist=<?php echo $artist->id; ?>">
                <?php echo Ui::get_material_symbol('search', T_('Show Artist Songs')); ?>
                <?php echo T_('Show Artist Songs'); ?>
            </a>
        </li>
        <li>
            <a href="<?php echo $web_path; ?>/artists.php?action=show_all_songs&amp;artist=<?php echo $artist->id; ?>">
                <?php echo Ui::get_material_symbol('search', T_('Show All')); ?>
                <?php echo T_('Show All'); ?>
            </a>
        </li>
<?php } else { ?>
        <li>
            <a href="<?php echo $web_path; ?>/artists.php?action=show&amp;artist=<?php echo $artist->id; ?>">
                <?php echo Ui::get_material_symbol('search', T_('Show Albums')); ?>
                <?php echo T_('Show Albums'); ?>
            </a>
        </li>
<?php } ?>
<?php if ($show_direct_play) { ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=artist&object_id=' . $artist->id, 'play_circle', T_('Play All'), 'directplay_full_' . $artist->id); ?>
        </li>
    <?php if (Stream_Playlist::check_autoplay_next()) { ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=artist&object_id=' . $artist->id . '&playnext=true', 'menu_open', T_('Play All Next'), 'nextplay_artist_' . $artist->id); ?>
        </li>
    <?php } ?>
    <?php if (Stream_Playlist::check_autoplay_append()) { ?>
        <li>
            <?php echo Ajax::button_with_text('?page=stream&action=directplay&object_type=artist&object_id=' . $artist->id . '&append=true', 'playlist_add', T_('Play All Last'), 'addplay_artist_' . $artist->id); ?>
        </li>
    <?php } ?>
<?php } ?>
<?php if ($show_playlist_add) { ?>
        <li>
            <?php echo Ajax::button_with_text('?action=basket&type=artist&id=' . $artist->id, 'new_window', T_('Add All to Temporary Playlist'), 'add_' . $artist->id); ?>
        </li>
        <li>
            <?php echo Ajax::button_with_text('?action=basket&type=artist_random&id=' . $artist->id, 'shuffle', T_('Random All to Temporary Playlist'), 'random_' . $artist->id); ?>
        </li>
<?php } ?>
<?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)) { ?>
        <li>
            <a href="javascript:NavigateTo('<?php echo $web_path; ?>/artists.php?action=update_from_tags&amp;artist=<?php echo $artist->id; ?>');" onclick="return confirm('<?php echo T_('Do you really want to update from tags?'); ?>');">
                <?php echo Ui::get_material_symbol('sync_alt', T_('Update from tags')); ?>
                <?php echo T_('Update from tags'); ?>
            </a>
        </li>
    <?php if (!empty($artist->mbid) && Preference::get_by_user($current_user->id, 'mb_overwrite_name')) { ?>
        <li>
            <a href="javascript:NavigateTo('<?php echo $web_path; ?>/artists.php?action=update_from_musicbrainz&amp;artist=<?php echo $artist->id; ?>');" onclick="return confirm('<?php echo T_('Are you sure? This will overwrite Artist details using MusicBrainz data'); ?>');">
                <?php echo Ui::get_icon('musicbrainz', T_('Update details from MusicBrainz')); ?>
                <?php echo T_('Update details from MusicBrainz'); ?>
            </a>
        </li>
    <?php } ?>
<?php } ?>
<?php if (AmpConfig::get('use_rss')) { ?>
        <li>
            <?php echo Ui::getRssLink(
                RssFeedTypeEnum::LIBRARY_ITEM,
                $current_user,
                T_('RSS Feed'),
                array('object_type' => 'artist', 'object_id' => (string)$artist->id)
            ); ?>
        </li>
<?php } ?>
<?php if (!AmpConfig::get('use_auth') || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) { ?>
    <?php if (AmpConfig::get('sociable')) {
        $postshout = T_('Post Shout'); ?>
        <li>
            <a href="<?php echo $web_path; ?>/shout.php?action=show_add_shout&type=artist&id=<?php echo $artist->id; ?>">
        <?php echo Ui::get_material_symbol('comment', $postshout); ?>
        <?php echo $postshout; ?>
            </a>
        </li>
    <?php }
    }
global $dic; // @todo remove after refactoring
$zipHandler = $dic->get(ZipHandlerInterface::class);
if (Access::check_function(AccessFunctionEnum::FUNCTION_BATCH_DOWNLOAD) && $zipHandler->isZipable('artist')) {
    $download = T_('Download'); ?>
        <li>
            <a class="nohtml" href="<?php echo $web_path; ?>/batch.php?action=artist&id=<?php echo $artist->id; ?>">
                <?php echo Ui::get_material_symbol('folder_zip', $download); ?>
                <?php echo $download; ?>
            </a>
        </li>
<?php }
if (($owner_id > 0 && $owner_id == $current_user->getId()) || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)) { ?>
            <?php if (AmpConfig::get('statistical_graphs') && is_dir(__DIR__ . '/../../vendor/szymach/c-pchart/src/Chart/')) { ?>
                <li>
                    <a href="<?php echo $web_path; ?>/stats.php?action=graph&object_type=artist&object_id=<?php echo $artist->id; ?>">
                        <?php echo Ui::get_material_symbol('bar_chart', T_('Graphs')); ?>
                        <?php echo T_('Graphs'); ?>
                    </a>
                </li>
            <?php }
            }
if (canEditArtist($artist, $gatekeeper->getUserId())) {
    if (Upload::can_upload($current_user)) {
        $t_upload = T_('Upload'); ?>
                <li>
                    <a href="<?php echo $web_path; ?>/upload.php?artist=<?php echo $artist->id; ?>">
                        <?php echo Ui::get_material_symbol('upload', $t_upload); ?>
                        <?php echo $t_upload; ?>
                    </a>
                </li>
            <?php
    } ?>
            <li>
                <a id="<?php echo 'edit_artist_' . $artist->id; ?>" onclick="showEditDialog('artist_row', '<?php echo $artist->id; ?>', '<?php echo 'edit_artist_' . $artist->id; ?>', '<?php echo addslashes(T_('Artist Edit')); ?>', '')">
                    <?php echo Ui::get_material_symbol('edit', T_('Edit')); ?>
                    <?php echo T_('Edit Artist'); ?>
                </a>
            </li>
        <?php }
if (Catalog::can_remove($artist)) {
    $delete = T_('Delete'); ?>
        <li>
            <a id="<?php echo 'delete_artist_' . $artist->id; ?>" href="<?php echo $web_path; ?>/artists.php?action=delete&artist_id=<?php echo $artist->id; ?>">
                <?php echo Ui::get_material_symbol('close', $delete); ?>
                <?php echo $delete; ?>
            </a>
        </li>
        <?php } ?>
    </ul>
</div>
<?php Ui::show_box_bottom(); ?>
<div class="tabs_wrapper">
    <div id="tabs_container">
        <ul id="tabs">
            <li class="tab_active"><a href="#albums"><?php echo T_('Albums'); ?></a></li>
            <li><a id="top_tracks_link" href="#top_tracks"><?php echo T_('Top Tracks'); ?></a></li>
<?php if ($use_wanted) { ?>
            <li><a id="missing_albums_link" href="#missing_albums"><?php echo T_('Missing Albums'); ?></a></li>
<?php }
if ($show_similar) { ?>
            <li><a id="similar_artist_link" href="#similar_artist"><?php echo T_('Similar Artists'); ?></a></li>
            <li><a id="similar_songs_link" href="#similar_songs"><?php echo T_('Similar Songs'); ?></a></li>
<?php }
if ($use_label) { ?>
            <li><a id="labels_link" href="#labels"><?php echo T_('Labels'); ?></a></li>
<?php } ?>
            <!-- Needed to avoid the 'only one' bug -->
            <li></li>
        </ul>
    </div>
    <div id="tabs_content">
        <div id="albums" class="tab_content" style="display: block;">
<?php if (empty($multi_object_ids)) {
    $multi_object_ids = array('' => $object_ids);
}

foreach ($multi_object_ids as $key => $object_ids) {
    $title  = (!empty($key)) ? ucwords($key) : '';
    $browse = new Browse();
    $browse->set_type($object_type);
    $browse->set_use_filters(false);
    if ($is_album_type) {
        $browse->set_sort($sort, $order);
    }
    $browse->set_use_alpha(false, false);
    if (!empty($key)) {
        $browse->set_content_div_ak($key);
    }
    $browse->show_objects($object_ids, array('group_disks' => true, 'title' => $title));
    $browse->store();
} ?>
        </div>
        <?php echo Ajax::observe('top_tracks_link', 'click', Ajax::action('?page=index&action=top_tracks&artist=' . $artist->id, 'top_tracks')); ?>
        <div id="top_tracks" class="tab_content">
            <?php Ui::show_box_top('', 'info-box');
echo T_('Loading...');
Ui::show_box_bottom(); ?>
        </div>

<?php if ($use_wanted) {
    echo Ajax::observe('missing_albums_link', 'click', Ajax::action('?page=index&action=wanted_missing_albums&artist=' . $artist->id, 'missing_albums')); ?>
        <div id="missing_albums" class="tab_content">
        <?php Ui::show_box_top(T_('Missing Albums'), 'info-box');
    echo T_('Loading...');
    Ui::show_box_bottom(); ?>
        </div>
<?php }
if ($show_similar) {
    echo Ajax::observe('similar_artist_link', 'click', Ajax::action('?page=index&action=similar_artist&artist=' . $artist->id, 'similar_artist')); ?>
        <div id="similar_artist" class="tab_content">
        <?php Ui::show_box_top(T_('Similar Artists'), 'info-box');
    echo T_('Loading...');
    Ui::show_box_bottom(); ?>
        </div>
        <?php echo Ajax::observe('similar_songs_link', 'click', Ajax::action('?page=index&action=similar_songs&artist=' . $artist->id, 'similar_songs')); ?>
        <div id="similar_songs" class="tab_content">
            <?php Ui::show_box_top('', 'info-box');
    echo T_('Loading...');
    Ui::show_box_bottom(); ?>
        </div>
<?php
}
if ($use_label) {
    echo Ajax::observe('labels_link', 'click', Ajax::action('?page=index&action=labels&artist=' . $artist->id, 'labels')); ?>
        <div id="labels" class="tab_content">
        <?php Ui::show_box_top(T_('Labels'), 'info-box');
    echo T_('Loading...');
    Ui::show_box_bottom(); ?>
        </div>
<?php
} ?>
    </div>
</div>
