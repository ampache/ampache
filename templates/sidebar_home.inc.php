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
 */ ?>
<?php
// strings for the main page and templates
$t_songs     = T_('Songs');
$t_artists   = T_('Artists');
$t_albums    = T_('Albums');
$t_labels    = T_('Labels');
$t_playlists = T_('Playlists');
$t_playlist  = T_('Playlist');
$t_videos    = T_('Videos');
$t_tagcloud  = T_('Tag Cloud');
$t_expander  = T_('Expand/Collapse');
$t_search    = T_('Search'); ?>
<ul class="sb2" id="sb_home">
    <?php if (AmpConfig::get('browse_filter')) {
    echo "<li>";
    Ajax::start_container('browse_filters');
    Ajax::end_container();
    echo "</li>";
} ?>
    <li class="sb2_music"><h4 class="header"><span class="sidebar-header-title" title="<?php echo T_('Browse Music'); ?>"><?php echo T_('Music'); ?></span><?php echo UI::get_icon('all', $t_expander, 'browse_music', 'header-img ' . (($_COOKIE['sb_browse_music'] == 'collapsed') ? 'collapsed' : 'expanded')); ?></h4>
        <?php
        $text = (string) scrub_in(Core::get_request('action')) . '_ac';
        if ($text) {
            ${$text} = ' selected="selected"';
        } ?>
        <ul class="sb3" id="sb_browse_music">
            <li id="sb_home_browse_music_songTitle"><a href="<?php echo $web_path; ?>/browse.php?action=song"><?php echo T_('Browse Library') ?></a></li>
            <li id="sb_home_browse_music_album"><a href="<?php echo $web_path; ?>/mashup.php?action=album"><?php echo $t_albums; ?></a></li>
            <li id="sb_home_browse_music_artist"><a href="<?php echo $web_path; ?>/mashup.php?action=artist"><?php echo $t_artists; ?></a></li>
            <?php if (AmpConfig::get('label')) { ?>
            <li id="sb_home_browse_music_label"><a href="<?php echo $web_path ?>/browse.php?action=label"><?php echo $t_labels ?></a></li>
            <?php
        } ?>
            <?php if (AmpConfig::get('channel')) { ?>
            <li id="sb_home_browse_music_channel"><a href="<?php echo $web_path ?>/browse.php?action=channel"><?php echo T_('Channels') ?></a></li>
            <?php
        } ?>
            <?php if (AmpConfig::get('broadcast')) { ?>
            <li id="sb_home_browse_music_broadcast"><a href="<?php echo $web_path ?>/browse.php?action=broadcast"><?php echo T_('Broadcasts') ?></a></li>
            <?php
        } ?>
            <?php if (AmpConfig::get('live_stream')) { ?>
            <li id="sb_home_browse_music_radioStation"><a href="<?php echo $web_path ?>/browse.php?action=live_stream"><?php echo T_('Radio Stations') ?></a></li>
            <?php
        } ?>
            <?php if (AmpConfig::get('podcast')) { ?>
            <li id="sb_home_browse_music_podcast"><a href="<?php echo $web_path ?>/browse.php?action=podcast"><?php echo T_('Podcasts') ?></a></li>
            <?php
        } ?>
            <?php if (AmpConfig::get('allow_upload') && Access::check('interface', 25)) { ?>
            <li id="sb_home_browse_music_upload"><a href="<?php echo $web_path ?>/upload.php"><?php echo T_('Upload') ?></a></li>
            <?php
        } ?>
        </ul>
    </li>
    <?php if (AmpConfig::get('allow_video') && Video::get_item_count('Video')) { ?>
        <li class="sb2_video"><h4 class="header"><span class="sidebar-header-title"><?php echo $t_videos ?></span><?php echo UI::get_icon('all', $t_expander, 'browse_video', 'header-img ' . (($_COOKIE['sb_browse_video'] == 'collapsed') ? 'collapsed' : 'expanded')); ?></h4>
            <ul class="sb3" id="sb_home_browse_video">
          <?php if (Video::get_item_count('Clip')) { ?>
                <li id="sb_home_browse_video_clip"><a href="<?php echo $web_path ?>/browse.php?action=clip"><?php echo T_('Music Clips') ?></a></li>
          <?php
            } ?>
          <?php if (Video::get_item_count('TVShow_Episode')) { ?>
                <li id="sb_home_browse_video_tvShow"><a href="<?php echo $web_path ?>/browse.php?action=tvshow"><?php echo T_('TV Shows') ?></a></li>
          <?php
            } ?>
          <?php if (Video::get_item_count('Movie')) { ?>
                <li id="sb_home_browse_video_movie"><a href="<?php echo $web_path ?>/browse.php?action=movie"><?php echo T_('Movies') ?></a></li>
          <?php
            } ?>
          <?php if (Video::get_item_count('Personal_Video')) { ?>
                <li id="sb_home_browse_video_video"><a href="<?php echo $web_path ?>/browse.php?action=personal_video"><?php echo T_('Personal Videos') ?></a></li>
          <?php
            } ?>
                <li id="sb_home_browse_video_tagsVideo"><a href="<?php echo $web_path ?>/browse.php?action=tag&type=video"><?php echo $t_tagcloud ?></a></li>
            </ul>
        </li>
    <?php
        } ?>
    <li class="sb2_random">
        <h4 class="header"><span class="sidebar-header-title"><?php echo T_('Random'); ?></span><img src="<?php echo AmpConfig::get('web_path') . AmpConfig::get('theme_path'); ?>/images/icons/icon_all.png" class="header-img <?php echo ($_COOKIE['sb_random'] == 'expanded') ? 'expanded' : 'collapsed'; ?>" id="random" alt="<?php echo $t_expander; ?>" title="<?php echo $t_expander; ?>" /></h4>
        <ul class="sb3" id="sb_home_random" style="<?php if (!(filter_has_var(INPUT_COOKIE, 'sb_random'))) {
            echo 'display: none;';
        } ?>">
            <li id="sb_home_random_song"><?php echo Ajax::text('?page=random&action=song', $t_songs, 'home_random_song'); ?></li>
            <li id="sb_home_random_album"><?php echo Ajax::text('?page=random&action=album', T_('Album'), 'home_random_album'); ?></li>
            <li id="sb_home_random_artist"><?php echo Ajax::text('?page=random&action=artist', T_('Artist'), 'home_random_artist'); ?></li>
            <li id="sb_home_random_playlist"><?php echo Ajax::text('?page=random&action=playlist', T_('Playlist'), 'home_random_playlist'); ?></li>
            <li id="sb_home_random_advanced"><a href="<?php echo $web_path; ?>/random.php?action=advanced&type=song"><?php echo T_('Advanced'); ?></a></li>
        </ul>
    </li>
    <li class="sb2_search">
        <h4 class="header"><span class="sidebar-header-title"><?php echo $t_search; ?></span><img src="<?php echo AmpConfig::get('web_path') . AmpConfig::get('theme_path'); ?>/images/icons/icon_all.png" class="header-img <?php echo ($_COOKIE['sb_search'] == 'expanded') ? 'expanded' : 'collapsed'; ?>" id="search" alt="<?php echo $t_expander; ?>" title="<?php echo $t_expander; ?>" /></h4>
        <ul class="sb3" id="sb_home_search" style="<?php if (!(filter_has_var(INPUT_COOKIE, 'sb_search'))) {
            echo 'display: none;';
        } ?>">
          <li id="sb_home_search_song"><a href="<?php echo $web_path; ?>/search.php?type=song"><?php echo $t_songs; ?></a></li>
          <li id="sb_home_search_album"><a href="<?php echo $web_path; ?>/search.php?type=album"><?php echo $t_albums; ?></a></li>
          <li id="sb_home_search_artist"><a href="<?php echo $web_path; ?>/search.php?type=artist"><?php echo $t_artists; ?></a></li>
          <?php if (AmpConfig::get('label')) { ?>
          <li id="sb_home_search_label"><a href="<?php echo $web_path; ?>/search.php?type=label"><?php echo $t_labels; ?></a></li>
                <?php
            } ?>
          <li id="sb_home_search_playlist"><a href="<?php echo $web_path; ?>/search.php?type=playlist"><?php echo $t_playlists; ?></a></li>
          <?php if (AmpConfig::get('allow_video') && Video::get_item_count('Video')) { ?>
            <li id="sb_home_search_video"><a href="<?php echo $web_path ?>/search.php?type=video"><?php echo $t_videos ?></a></li>
          <?php
        } ?>
        </ul>
    </li>
    <?php if (Access::check('interface', 25)) { ?>
    <li class="sb2_playlist">
    <h4 class="header"><span class="sidebar-header-title"><?php echo $t_playlist; ?></span><?php echo UI::get_icon('all', $t_expander, 'playlist', 'header-img ' . (($_COOKIE['sb_home_playlist'] == 'collapsed') ? 'collapsed' : 'expanded')); ?></h4>
        <?php if (AmpConfig::get('home_now_playing') || AmpConfig::get('allow_democratic_playback') || Access::check('interface', 50)) { ?>
        <ul class="sb3" id="sb_home_playlist">
            <li id="sb_home_browse_music_playlist"><a href="<?php echo $web_path; ?>/browse.php?action=playlist"><?php echo $t_playlists; ?></a></li>
            <li id="sb_home_browse_music_smartPlaylist"><a href="<?php echo $web_path; ?>/browse.php?action=smartplaylist"><?php echo T_('Smart Playlists'); ?></a></li>
            <?php if (AmpConfig::get('allow_democratic_playback')) { ?>
            <li id="sb_home_playlist_playlist"><a href="<?php echo $web_path ?>/democratic.php?action=show_playlist"><?php echo T_('Democratic') ?></a></li>
            <?php
            } ?>
            <?php if (($server_allow == AmpConfig::get('allow_localplay_playback')) && ($controller == AmpConfig::get('localplay_controller')) && ($access_check == Access::check('localplay', 5))) { ?>
            <?php
                // Little bit of work to be done here
                $localplay             = new Localplay(AmpConfig::get('localplay_controller'));
                $current_instance      = $localplay->current_instance();
                $class                 = $current_instance ? '' : ' class="active_instance"'; ?>
            <li id="sb_home_playlist_show"><a href="<?php echo $web_path ?>/localplay.php?action=show_playlist"><?php echo T_('Localplay') ?></a></li>
            <?php
            } ?>
        </ul>
        <?php
        } ?>
    </li>
    <?php
    } ?>
    <li class="sb2_information">
        <h4 class="header"><span class="sidebar-header-title"><?php echo T_('Information'); ?></span><?php echo UI::get_icon('all', $t_expander, 'information', 'header-img ' . (($_COOKIE['sb_info'] == 'collapsed') ? 'collapsed' : 'expanded')); ?></h4>
        <ul class="sb3" id="sb_home_info">
            <li id="sb_home_info_recent"><a href="<?php echo $web_path; ?>/stats.php?action=recent"><?php echo T_('Recent'); ?></a></li>
            <li id="sb_home_info_newest"><a href="<?php echo $web_path; ?>/stats.php?action=newest"><?php echo T_('Newest'); ?></a></li>
            <li id="sb_home_info_popular"><a href="<?php echo $web_path; ?>/stats.php?action=popular"><?php echo T_('Popular'); ?></a></li>
            <?php if (User::is_registered()) { ?>
                <?php if (AmpConfig::get('ratings')) { ?>
                <li id="sb_home_info_highest"><a href="<?php echo $web_path ?>/stats.php?action=highest"><?php echo T_('Top Rated') ?></a></li>
                <?php
        } ?>
                <?php if (AmpConfig::get('userflags')) { ?>
                <li id="sb_home_info_userFlag"><a href="<?php echo $web_path?>/stats.php?action=userflag"><?php echo T_('Favorites') ?></a></li>
                <?php
        } ?>
                <?php if (AmpConfig::get('wanted')) { ?>
                <li id="sb_home_info_wanted"><a href="<?php echo $web_path ?>/stats.php?action=wanted"><?php echo T_('Wanted') ?></a></li>
                <?php
        } ?>
        <li id="sb_home_browse_music_tags"><a href="<?php echo $web_path; ?>/browse.php?action=tag"><?php echo $t_tagcloud; ?></a></li>
                <?php if (AmpConfig::get('share')) { ?>
                <li id="sb_home_info_share"><a href="<?php echo $web_path ?>/stats.php?action=share"><?php echo T_('Shares') ?></a></li>
                <?php
        } ?>
                <?php if (AmpConfig::get('allow_upload')) { ?>
                <li id="sb_home_info_upload"><a href="<?php echo $web_path ?>/stats.php?action=upload"><?php echo T_('Uploads') ?></a></li>
                <?php
        } ?>
                <?php if (Access::check('interface', 50)) { ?>
                    <li id="sb_home_info_statistic"><a href="<?php echo $web_path ?>/stats.php?action=show"><?php echo T_('Statistics') ?></a></li>
                <?php
        } ?>
            <?php
    } ?>
        </ul>
    </li>
</ul>
