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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Module\Util\Upload;
use Ampache\Repository\Model\Clip;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\VideoRepositoryInterface;

global $dic;

/** @var string $web_path */
/** @var string $t_a_artists */
/** @var string $t_albums */
/** @var string $t_artists */
/** @var string $t_broadcasts */
/** @var string $t_browse */
/** @var string $t_dashboards */
/** @var string $t_democratic */
/** @var string $t_expander */
/** @var string $t_favorites */
/** @var string $t_genres */
/** @var string $t_information */
/** @var string $t_labels */
/** @var string $t_localplay */
/** @var string $t_musicClips */
/** @var string $t_newest */
/** @var string $t_playlists */
/** @var string $t_podcastEpisodes */
/** @var string $t_podcasts */
/** @var string $t_popular */
/** @var string $t_radioStations */
/** @var string $t_random */
/** @var string $t_recent */
/** @var string $t_search */
/** @var string $t_shares */
/** @var string $t_smartPlaylists */
/** @var string $t_songs */
/** @var string $t_statistics */
/** @var string $t_topRated */
/** @var string $t_uploads */
/** @var string $t_videos */
/** @var string $t_wanted */
$server_allow    = AmpConfig::get('allow_localplay_playback');
$controller      = AmpConfig::get('localplay_controller');
$videoRepository = $dic->get(VideoRepositoryInterface::class);
$allowVideo      = AmpConfig::get('allow_video') && $videoRepository->getItemCount(Video::class);
$allowDemocratic = AmpConfig::get('allow_democratic_playback');
$showAlbumArtist = AmpConfig::get('show_album_artist');
$showArtist      = AmpConfig::get('show_artist');
$allowLabel      = AmpConfig::get('label');
$allowPodcast    = AmpConfig::get('podcast');
$access50        = Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER);
$access25        = ($access50 || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER));
$current_user    = $current_user ?? Core::get_global('user');
$allow_upload    = $allow_upload ?? $access25 && Upload::can_upload($current_user);
$albumString     = (AmpConfig::get('album_group'))
    ? 'album'
    : 'album_disk';
// expanded by default
$state_home_browse = (isset($_COOKIE['sb_home_browse']) && $_COOKIE['sb_home_browse'] == 'collapsed')
    ? 'collapsed'
    : 'expanded';
$state_home_video = (isset($_COOKIE['sb_home_video']) && $_COOKIE['sb_home_video'] == 'collapsed')
    ? 'collapsed'
    : 'expanded';
$state_home_search = (isset($_COOKIE['sb_home_search']) && $_COOKIE['sb_home_search'] == 'collapsed')
    ? 'collapsed'
    : 'expanded';
$state_home_playlist = (isset($_COOKIE['sb_home_playlist']) && $_COOKIE['sb_home_playlist'] == 'collapsed')
    ? 'collapsed'
    : 'expanded';
// collapsed by default
$state_home_dashboard = (!isset($_COOKIE['sb_home_dashboard']) || $_COOKIE['sb_home_dashboard'] == 'expanded')
    ? 'expanded'
    : 'collapsed';
$state_home_information = (!isset($_COOKIE['sb_home_information']) || $_COOKIE['sb_home_information'] == 'expanded')
    ? 'expanded'
    : 'collapsed';
?>
<ul class="sb2" id="sb_home">
<?php if (AmpConfig::get('browse_filter')) {
    echo "<li>";
    Ajax::start_container('browse_filters');
    Ajax::end_container();
    echo "</li>";
}
if (!AmpConfig::get('sidebar_hide_browse', false)) { ?>
    <li class="sb2_browse">
        <h4 class="header">
            <span class="sidebar-header-title"><?php echo $t_browse; ?></span>
            <?php echo Ui::get_material_symbol('chevron_right', $t_expander, 'home_browse', 'header-img ' . $state_home_browse); ?>
        </h4>
        <?php $text = (string)scrub_in(Core::get_request('action')) . '_ac';
    if ($text !== '_ac') {
        ${$text} = ' selected="selected"';
    } ?>
        <ul class="sb3" id="sb_home_browse">
            <li id="sb_home_browse_songTitle"><a href="<?php echo $web_path; ?>/browse.php?action=song"><?php echo $t_songs; ?></a></li>
            <li id="sb_home_browse_album"><a href="<?php echo $web_path; ?>/browse.php?action=<?php echo $albumString; ?>"><?php echo $t_albums; ?></a></li>
<?php if ($showArtist) { ?>
                <li id="sb_home_browse_artist"><a href="<?php echo $web_path; ?>/browse.php?action=artist"><?php echo $t_artists; ?></a></li>
<?php } ?>
<?php if ($showAlbumArtist || !$showArtist) { ?>
                <li id="sb_home_browse_artist"><a href="<?php echo $web_path; ?>/browse.php?action=album_artist"><?php echo $t_a_artists; ?></a></li>
<?php } ?>
<?php if ($allowLabel) { ?>
                <li id="sb_home_browse_label"><a href="<?php echo $web_path; ?>/browse.php?action=label"><?php echo $t_labels; ?></a></li>
<?php } ?>
<?php if (AmpConfig::get('broadcast')) { ?>
                <li id="sb_home_browse_broadcast"><a href="<?php echo $web_path; ?>/browse.php?action=broadcast"><?php echo $t_broadcasts; ?></a></li>
<?php } ?>
<?php if (AmpConfig::get('live_stream')) { ?>
                <li id="sb_home_browse_radioStation"><a href="<?php echo $web_path; ?>/browse.php?action=live_stream"><?php echo $t_radioStations; ?></a></li>
<?php } ?>
<?php if ($allowPodcast) { ?>
                <li id="sb_home_browse_podcast"><a href="<?php echo $web_path; ?>/browse.php?action=podcast"><?php echo $t_podcasts; ?></a></li>
                <li id="sb_home_browse_podcast"><a href="<?php echo $web_path; ?>/browse.php?action=podcast_episode"><?php echo $t_podcastEpisodes; ?></a></li>
<?php } ?>
<?php if ($allowVideo) { ?>
                <li id="sb_home_browse_video"><a href="<?php echo $web_path; ?>/browse.php?action=video"><?php echo $t_videos; ?></a></li>
                <?php } ?>
        <li id="sb_home_browse_tags"><a href="<?php echo $web_path; ?>/browse.php?action=tag&type=artist"><?php echo $t_genres; ?></a></li>
<?php if ($allow_upload) { ?>
              <li id="sb_home_browse_upload"><a href="<?php echo $web_path; ?>/stats.php?action=upload"><?php echo $t_uploads; ?></a></li>
<?php } ?>
        </ul>
    </li>
<?php }
if (
    User::is_registered() &&
    !AmpConfig::get('sidebar_hide_dashboard', false)
) { ?>
    <li class="sb2_dashboard">
        <h4 class="header">
            <span class="sidebar-header-title"><?php echo $t_dashboards; ?></span>
            <?php echo Ui::get_material_symbol('chevron_right', $t_expander, 'home_dashboard', 'header-img ' . $state_home_dashboard); ?>
        </h4>
        <ul class="sb3" id="sb_home_dashboard">
            <li id="sb_home_dashboard_albums"><a href="<?php echo $web_path; ?>/mashup.php?action=<?php echo $albumString; ?>"><?php echo $t_albums; ?></a></li>
            <li id="sb_home_dashboard_artists"><a href="<?php echo $web_path; ?>/mashup.php?action=artist"><?php echo $t_artists; ?></a></li>
    <?php if ($access25) { ?>
            <li id="sb_home_dashboard_playlists"><a href="<?php echo $web_path; ?>/mashup.php?action=playlist"><?php echo $t_playlists; ?></a></li>
    <?php } ?>
    <?php if ($allowPodcast) { ?>
                <li id="sb_home_dashboard_podcast_episodes"><a href="<?php echo $web_path; ?>/mashup.php?action=podcast_episode"><?php echo $t_podcastEpisodes; ?></a></li>
    <?php } ?>
    <?php if ($allowVideo) { ?>
                <li id="sb_home_dashboard_videos"><a href="<?php echo $web_path; ?>/mashup.php?action=video"><?php echo $t_videos; ?></a></li>
    <?php } ?>
        </ul>
    </li>
<?php } ?>
<?php if (
    $allowVideo &&
    !AmpConfig::get('sidebar_hide_video', false)
) { ?>
        <li class="sb2_video">
            <h4 class="header">
                <span class="sidebar-header-title"><?php echo $t_videos; ?></span>
                <?php echo Ui::get_material_symbol('chevron_right', $t_expander, 'home_video', 'header-img ' . $state_home_video); ?>
            </h4>
            <ul class="sb3" id="sb_home_video">
    <?php if ($videoRepository->getItemCount(Clip::class)) { ?>
                <li id="sb_home_video_clip"><a href="<?php echo $web_path; ?>/browse.php?action=clip"><?php echo $t_musicClips; ?></a></li>
    <?php } ?>
                <li id="sb_home_video_tagsVideo"><a href="<?php echo $web_path; ?>/browse.php?action=tag&type=video"><?php echo $t_genres; ?></a></li>
            </ul>
        </li>
<?php } ?>
<?php if (!AmpConfig::get('sidebar_hide_search', false)) { ?>
    <li class="sb2_search">
        <h4 class="header">
            <span class="sidebar-header-title"><?php echo $t_search; ?></span>
            <?php echo Ui::get_material_symbol('chevron_right', $t_expander, 'home_search', 'header-img ' . $state_home_search); ?>
        </h4>
        <ul class="sb3" id="sb_home_search">
          <li id="sb_home_search_song"><a href="<?php echo $web_path; ?>/search.php?type=song"><?php echo $t_songs; ?></a></li>
          <li id="sb_home_search_album"><a href="<?php echo $web_path; ?>/search.php?type=<?php echo $albumString; ?>"><?php echo $t_albums; ?></a></li>
          <li id="sb_home_search_artist"><a href="<?php echo $web_path; ?>/search.php?type=artist"><?php echo $t_artists; ?></a></li>
    <?php if ($allowLabel) { ?>
              <li id="sb_home_search_label"><a href="<?php echo $web_path; ?>/search.php?type=label"><?php echo $t_labels; ?></a></li>
    <?php } ?>
    <?php if ($access25) { ?>
              <li id="sb_home_search_playlist"><a href="<?php echo $web_path; ?>/search.php?type=playlist"><?php echo $t_playlists; ?></a></li>
    <?php } ?>
    <?php if ($allowPodcast) { ?>
              <li id="sb_home_search_podcast"><a href="<?php echo $web_path; ?>/search.php?type=podcast"><?php echo $t_podcasts; ?></a></li>
              <li id="sb_home_search_podcast_episode"><a href="<?php echo $web_path; ?>/search.php?type=podcast_episode"><?php echo $t_podcastEpisodes; ?></a></li>
    <?php } ?>
    <?php if ($allowVideo) { ?>
              <li id="sb_home_search_video"><a href="<?php echo $web_path; ?>/search.php?type=video"><?php echo $t_videos; ?></a></li>
    <?php } ?>
              <li id="sb_home_random_advanced"><a href="<?php echo $web_path; ?>/random.php?action=advanced&type=song"><?php echo $t_random; ?></a></li>
        </ul>
    </li>
<?php } ?>
<?php if (
    $access25 &&
    !AmpConfig::get('sidebar_hide_playlist', false)
) {  ?>
    <li class="sb2_playlist">
        <h4 class="header">
            <span class="sidebar-header-title"><?php echo $t_playlists; ?></span>
            <?php echo Ui::get_material_symbol('chevron_right', $t_expander, 'home_playlist', 'header-img ' . $state_home_playlist); ?>
        </h4>
    <?php if (AmpConfig::get('home_now_playing') || $allowDemocratic || $access50) { ?>
        <ul class="sb3" id="sb_home_playlist">
            <li id="sb_home_playlist_playlist"><a href="<?php echo $web_path; ?>/browse.php?action=playlist"><?php echo $t_playlists; ?></a></li>
            <li id="sb_home_playlist_smartPlaylist"><a href="<?php echo $web_path; ?>/browse.php?action=smartplaylist"><?php echo $t_smartPlaylists; ?></a></li>
        <?php if ($allowDemocratic) { ?>
              <li id="sb_home_playlist_playlist"><a href="<?php echo $web_path; ?>/democratic.php?action=show_playlist"><?php echo $t_democratic; ?></a></li>
        <?php } ?>
        <?php if ($server_allow && $controller) { ?>
            <?php $localplay      = new LocalPlay(AmpConfig::get('localplay_controller'));
            $current_instance     = $localplay->current_instance();
            $class                = $current_instance ? '' : ' class="active_instance"'; ?>
                <li id="sb_home_playlist_show"><a href="<?php echo $web_path; ?>/localplay.php?action=show_playlist"><?php echo $t_localplay; ?></a></li>
        <?php } ?>
        </ul>
    <?php } ?>
    </li>
<?php } ?>
<?php if (!AmpConfig::get('sidebar_hide_information', false)) { ?>
    <li class="sb2_information">
        <h4 class="header">
            <span class="sidebar-header-title"><?php echo $t_information; ?></span>
            <?php echo Ui::get_material_symbol('chevron_right', $t_expander, 'home_information', 'header-img ' . $state_home_information); ?>
        </h4>
        <ul class="sb3" id="sb_home_information">
            <li id="sb_home_information_recent"><a href="<?php echo $web_path; ?>/stats.php?action=recent_<?php echo $albumString; ?>"><?php echo $t_recent; ?></a></li>
            <li id="sb_home_information_newest"><a href="<?php echo $web_path; ?>/stats.php?action=newest_<?php echo $albumString; ?>"><?php echo $t_newest; ?></a></li>
            <li id="sb_home_information_popular"><a href="<?php echo $web_path; ?>/stats.php?action=popular_<?php echo $albumString; ?>"><?php echo $t_popular; ?></a></li>
    <?php if (User::is_registered()) { ?>
        <?php if (AmpConfig::get('ratings')) { ?>
                <li id="sb_home_information_highest"><a href="<?php echo $web_path; ?>/stats.php?action=highest_<?php echo $albumString; ?>"><?php echo $t_topRated; ?></a></li>
                <li id="sb_home_information_userFlag"><a href="<?php echo $web_path; ?>/stats.php?action=userflag_<?php echo $albumString; ?>"><?php echo $t_favorites; ?></a></li>
        <?php } ?>
        <?php if (AmpConfig::get('wanted')) { ?>
                <li id="sb_home_information_wanted"><a href="<?php echo $web_path; ?>/stats.php?action=wanted"><?php echo $t_wanted; ?></a></li>
        <?php } ?>
        <?php if (AmpConfig::get('share')) { ?>
                <li id="sb_home_information_share"><a href="<?php echo $web_path; ?>/stats.php?action=share"><?php echo $t_shares; ?></a></li>
                <?php } ?>
        <?php if ($access50) { ?>
                    <li id="sb_home_information_statistic"><a href="<?php echo $web_path; ?>/stats.php?action=show"><?php echo $t_statistics; ?></a></li>
        <?php } ?>
    <?php } ?>
        </ul>
    </li>
<?php } ?>
</ul>
