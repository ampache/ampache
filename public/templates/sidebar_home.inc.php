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
use Ampache\Repository\Model\Clip;
use Ampache\Repository\Model\Movie;
use Ampache\Repository\Model\Personal_Video;
use Ampache\Repository\Model\TVShow_Episode;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Video;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Playback\Localplay\LocalPlay;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Ui;
use Ampache\Repository\VideoRepositoryInterface;

/** @var string $t_browse */
/** @var string $t_expander */

global $dic;
$server_allow    = AmpConfig::get('allow_localplay_playback');
$controller      = AmpConfig::get('localplay_controller');
$videoRepository = $dic->get(VideoRepositoryInterface::class);
$allowVideo      = AmpConfig::get('allow_video') && $videoRepository->getItemCount(Video::class);
$allowDemocratic = AmpConfig::get('allow_democratic_playback');
$showAlbumArtist = AmpConfig::get('show_album_artist');
$showArtist      = AmpConfig::get('show_artist');
$allowLabel      = AmpConfig::get('label');
$allowPodcast    = AmpConfig::get('podcast');
$access50        = Access::check('interface', 50);
$web_path        = AmpConfig::get('web_path'); ?>
<ul class="sb2" id="sb_home">
    <?php if (AmpConfig::get('browse_filter')) {
    echo "<li>";
    Ajax::start_container('browse_filters');
    Ajax::end_container();
    echo "</li>";
} ?>
    <li class="sb2_music"><h4 class="header"><span class="sidebar-header-title"><?php echo $t_browse; ?></span><?php echo Ui::get_icon('all', $t_expander, 'browse_music', 'header-img ' . ((isset($_COOKIE['sb_browse_music']) && $_COOKIE['sb_browse_music'] == 'collapsed') ? 'collapsed' : 'expanded')); ?></h4>
        <?php
        $text = (string) scrub_in(Core::get_request('action')) . '_ac';
        if ($text) {
            ${$text} = ' selected="selected"';
        } ?>
        <ul class="sb3" id="sb_browse_music">
            <li id="sb_home_browse_music_songTitle"><a href="<?php echo $web_path; ?>/browse.php?action=song"><?php echo $t_songs ?></a></li>
            <li id="sb_home_browse_music_album"><a href="<?php echo $web_path; ?>/browse.php?action=album"><?php echo $t_albums; ?></a></li>
            <?php if ($showAlbumArtist || (!$showArtist)) { ?>
                <li id="sb_home_browse_music_artist"><a href="<?php echo $web_path; ?>/browse.php?action=album_artist"><?php echo $t_a_artists; ?></a></li>
            <?php } ?>
            <?php if ($showArtist) { ?>
                <li id="sb_home_browse_music_artist"><a href="<?php echo $web_path; ?>/browse.php?action=artist"><?php echo $t_artists; ?></a></li>
            <?php } ?>
            <?php if ($allowLabel) { ?>
                <li id="sb_home_browse_music_label"><a href="<?php echo $web_path; ?>/browse.php?action=label"><?php echo $t_labels ?></a></li>
            <?php } ?>
            <?php if (AmpConfig::get('channel')) { ?>
                <li id="sb_home_browse_music_channel"><a href="<?php echo $web_path; ?>/browse.php?action=channel"><?php echo $t_channels ?></a></li>
            <?php } ?>
            <?php if (AmpConfig::get('broadcast')) { ?>
                <li id="sb_home_browse_music_broadcast"><a href="<?php echo $web_path; ?>/browse.php?action=broadcast"><?php echo $t_broadcasts ?></a></li>
            <?php } ?>
            <?php if (AmpConfig::get('live_stream')) { ?>
                <li id="sb_home_browse_music_radioStation"><a href="<?php echo $web_path; ?>/browse.php?action=live_stream"><?php echo $t_radioStations ?></a></li>
            <?php } ?>
            <?php if ($allowPodcast) { ?>
                <li id="sb_home_browse_music_podcast"><a href="<?php echo $web_path; ?>/browse.php?action=podcast"><?php echo $t_podcasts ?></a></li>
            <?php } ?>
            <?php if ($allowVideo) { ?>
                <li id="sb_home_browse_video_video"><a href="<?php echo $web_path; ?>/browse.php?action=video"><?php echo $t_videos ?></a></li>
                <?php } ?>
        <li id="sb_home_browse_music_tags"><a href="<?php echo $web_path; ?>/browse.php?action=tag&type=artist"><?php echo $t_genres; ?></a></li>
            <?php if (AmpConfig::get('allow_upload')) { ?>
              <li id="sb_home_info_upload"><a href="<?php echo $web_path; ?>/stats.php?action=upload"><?php echo $t_uploads ?></a></li>
            <?php
        } ?>
        </ul>
    </li>
    <?php if (User::is_registered()) { ?>
    <li class="sb2_dashboard">
        <h4 class="header"><span class="sidebar-header-title"><?php echo $t_dashboards; ?></span><?php echo Ui::get_icon('all', $t_expander, 'dashboard', 'header-img ' . ((isset($_COOKIE['sb_dashboard']) && $_COOKIE['sb_dashboard'] == 'expanded') ? 'expanded' : 'collapsed')); ?></h4>
        <ul class="sb3" id="sb_home_dash" style="<?php if (!(isset($_COOKIE['sb_dashboard']))) {
            echo 'display: none;';
        } ?>">
            <li id="sb_home_dash_albums"><a href="<?php echo $web_path; ?>/mashup.php?action=album"><?php echo $t_albums ?></a></li>
            <li id="sb_home_dash_artists"><a href="<?php echo $web_path; ?>/mashup.php?action=artist"><?php echo $t_artists ?></a></li>
            <li id="sb_home_dash_playlists"><a href="<?php echo $web_path; ?>/mashup.php?action=playlist"><?php echo $t_playlists ?></a></li>
            <?php if ($allowPodcast) { ?>
                <li id="sb_home_dash_podcast_episodes"><a href="<?php echo $web_path; ?>/mashup.php?action=podcast_episode"><?php echo $t_podcastEpisodes ?></a></li>
            <?php } ?>
            <?php if ($allowVideo) { ?>
                <li id="sb_home_dash_videos"><a href="<?php echo $web_path; ?>/mashup.php?action=video"><?php echo $t_videos ?></a></li>
            <?php } ?>
        </ul>
    </li>
    <?php } ?>
    <?php if ($allowVideo) { ?>
        <li class="sb2_video"><h4 class="header"><span class="sidebar-header-title"><?php echo $t_videos ?></span><?php echo Ui::get_icon('all', $t_expander, 'browse_video', 'header-img ' . ((isset($_COOKIE['sb_browse_video']) && $_COOKIE['sb_browse_video'] == 'collapsed') ? 'collapsed' : 'expanded')); ?></h4>
            <ul class="sb3" id="sb_home_browse_video">
          <?php if ($videoRepository->getItemCount(Clip::class)) { ?>
                <li id="sb_home_browse_video_clip"><a href="<?php echo $web_path; ?>/browse.php?action=clip"><?php echo $t_musicClips ?></a></li>
          <?php } ?>
          <?php if ($videoRepository->getItemCount(TVShow_Episode::class)) { ?>
                <li id="sb_home_browse_video_tvShow"><a href="<?php echo $web_path; ?>/browse.php?action=tvshow"><?php echo $t_tvShows ?></a></li>
          <?php } ?>
          <?php if ($videoRepository->getItemCount(Movie::class)) { ?>
                <li id="sb_home_browse_video_movie"><a href="<?php echo $web_path; ?>/browse.php?action=movie"><?php echo $t_movies ?></a></li>
          <?php } ?>
          <?php if ($videoRepository->getItemCount(Personal_Video::class)) { ?>
                <li id="sb_home_browse_video_personal"><a href="<?php echo $web_path; ?>/browse.php?action=personal_video"><?php echo $t_personalVideos ?></a></li>
          <?php } ?>
                <li id="sb_home_browse_video_tagsVideo"><a href="<?php echo $web_path; ?>/browse.php?action=tag&type=video"><?php echo $t_genres ?></a></li>
            </ul>
        </li>
    <?php } ?>
    <li class="sb2_search">
        <h4 class="header"><span class="sidebar-header-title"><?php echo $t_search; ?></span><img src="<?php echo AmpConfig::get('web_path') . AmpConfig::get('theme_path'); ?>/images/icons/icon_all.png" class="header-img <?php echo (isset($_COOKIE['sb_search']) && $_COOKIE['sb_search'] == 'collapsed') ? 'collapsed' : 'expanded'; ?>" id="search" alt="<?php echo $t_expander; ?>" title="<?php echo $t_expander; ?>" /></h4>
        <ul class="sb3" id="sb_home_search">
          <li id="sb_home_search_song"><a href="<?php echo $web_path; ?>/search.php?type=song"><?php echo $t_songs; ?></a></li>
          <li id="sb_home_search_album"><a href="<?php echo $web_path; ?>/search.php?type=album"><?php echo $t_albums; ?></a></li>
          <li id="sb_home_search_artist"><a href="<?php echo $web_path; ?>/search.php?type=artist"><?php echo $t_artists; ?></a></li>
          <?php if ($allowLabel) { ?>
              <li id="sb_home_search_label"><a href="<?php echo $web_path; ?>/search.php?type=label"><?php echo $t_labels; ?></a></li>
          <?php } ?>
              <li id="sb_home_search_playlist"><a href="<?php echo $web_path; ?>/search.php?type=playlist"><?php echo $t_playlists; ?></a></li>
          <?php if ($allowVideo) { ?>
              <li id="sb_home_search_video"><a href="<?php echo $web_path; ?>/search.php?type=video"><?php echo $t_videos ?></a></li>
          <?php } ?>
              <li id="sb_home_random_advanced"><a href="<?php echo $web_path; ?>/random.php?action=advanced&type=song"><?php echo $t_random; ?></a></li>
        </ul>
    </li>
    <?php if (Access::check('interface', 25)) { ?>
    <li class="sb2_playlist">
    <h4 class="header"><span class="sidebar-header-title"><?php echo $t_playlists; ?></span><?php echo Ui::get_icon('all', $t_expander, 'playlist', 'header-img ' . ((isset($_COOKIE['sb_home_playlist']) && $_COOKIE['sb_home_playlist'] == 'collapsed') ? 'collapsed' : 'expanded')); ?></h4>
        <?php if (AmpConfig::get('home_now_playing') || $allowDemocratic || $access50) { ?>
        <ul class="sb3" id="sb_home_playlist">
            <li id="sb_home_browse_music_playlist"><a href="<?php echo $web_path; ?>/browse.php?action=playlist"><?php echo $t_playlists; ?></a></li>
            <li id="sb_home_browse_music_smartPlaylist"><a href="<?php echo $web_path; ?>/browse.php?action=smartplaylist"><?php echo $t_smartPlaylists; ?></a></li>
            <?php if ($allowDemocratic) { ?>
              <li id="sb_home_playlist_playlist"><a href="<?php echo $web_path; ?>/democratic.php?action=show_playlist"><?php echo $t_democratic ?></a></li>
            <?php } ?>
            <?php if ($server_allow && $controller) { ?>
            <?php
                // Little bit of work to be done here
                $localplay             = new LocalPlay(AmpConfig::get('localplay_controller'));
                $current_instance      = $localplay->current_instance();
                $class                 = $current_instance ? '' : ' class="active_instance"'; ?>
                <li id="sb_home_playlist_show"><a href="<?php echo $web_path; ?>/localplay.php?action=show_playlist"><?php echo $t_localplay ?></a></li>
            <?php
            } ?>
        </ul>
        <?php
        } ?>
    </li>
    <?php
    } ?>
    <li class="sb2_information">
        <h4 class="header"><span class="sidebar-header-title"><?php echo $t_information; ?></span><?php echo Ui::get_icon('all', $t_expander, 'information', 'header-img ' . ((isset($_COOKIE['sb_info']) && $_COOKIE['sb_info'] == 'expanded') ? 'expanded' : 'collapsed')); ?></h4>
        <ul class="sb3" id="sb_home_info" style="<?php if (!(isset($_COOKIE['sb_info']))) {
        echo 'display: none;';
    } ?>">
            <li id="sb_home_info_recent"><a href="<?php echo $web_path; ?>/stats.php?action=recent"><?php echo $t_recent; ?></a></li>
            <li id="sb_home_info_newest"><a href="<?php echo $web_path; ?>/stats.php?action=newest"><?php echo $t_newest; ?></a></li>
            <li id="sb_home_info_popular"><a href="<?php echo $web_path; ?>/stats.php?action=popular"><?php echo $t_popular; ?></a></li>
            <?php if (User::is_registered()) { ?>
                <?php if (AmpConfig::get('ratings')) { ?>
                <li id="sb_home_info_highest"><a href="<?php echo $web_path; ?>/stats.php?action=highest"><?php echo $t_topRated ?></a></li>
                <li id="sb_home_info_userFlag"><a href="<?php echo $web_path?>/stats.php?action=userflag"><?php echo $t_favorites ?></a></li>
                <?php } ?>
                <?php if (AmpConfig::get('wanted')) { ?>
                <li id="sb_home_info_wanted"><a href="<?php echo $web_path; ?>/stats.php?action=wanted"><?php echo $t_wanted ?></a></li>
                <?php } ?>
                <?php if (AmpConfig::get('share')) { ?>
                <li id="sb_home_info_share"><a href="<?php echo $web_path; ?>/stats.php?action=share"><?php echo $t_shares ?></a></li>
                <?php } ?>
                <?php if ($access50) { ?>
                    <li id="sb_home_info_statistic"><a href="<?php echo $web_path; ?>/stats.php?action=show"><?php echo $t_statistics ?></a></li>
                <?php } ?>
            <?php } ?>
        </ul>
    </li>
</ul>
