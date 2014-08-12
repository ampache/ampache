<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */
?>
<ul class="sb2" id="sb_home">
    <li><h4 class="header"><span class="sidebar-header-title" title="<?php echo T_('Browse Music'); ?>"><?php echo T_('Browse Music'); ?></span><span class="sprite sprite-icon_all <?php echo isset($_COOKIE['sb_home_browse_music']) ? $_COOKIE['sb_home_browse_music'] : 'expanded'; ?>" id="browse_music" lt="<?php echo T_('Expand/Collapse'); ?>" title="<?php echo T_('Expand/Collapse'); ?>"></span></h4>
        <?php
        if (isset($_REQUEST['action'])) {
            $text = scrub_in($_REQUEST['action']) . '_ac';
            ${$text} = ' selected="selected"';
        }
        ?>
        <ul class="sb3" id="sb_browse_music">
            <li id="sb_home_browse_music_songTitle"><a href="<?php echo $web_path; ?>/browse.php?action=song"><?php echo T_('Song Titles'); ?></a></li>
            <li id="sb_home_browse_music_album"><a href="<?php echo $web_path; ?>/browse.php?action=album"><?php echo T_('Albums'); ?></a></li>
            <li id="sb_home_browse_music_artist"><a href="<?php echo $web_path; ?>/browse.php?action=artist"><?php echo T_('Artists'); ?></a></li>
            <li id="sb_home_browse_music_tags"><a href="<?php echo $web_path; ?>/browse.php?action=tag"><?php echo T_('Tag Cloud'); ?></a></li>
            <li id="sb_home_browse_music_playlist"><a href="<?php echo $web_path; ?>/browse.php?action=playlist"><?php echo T_('Playlists'); ?></a></li>
            <li id="sb_home_browse_music_smartPlaylist"><a href="<?php echo $web_path; ?>/browse.php?action=smartplaylist"><?php echo T_('Smart Playlists'); ?></a></li>
            <li id="sb_home_browse_music_channel"><a href="<?php echo $web_path; ?>/browse.php?action=channel"><?php echo T_('Channels'); ?></a></li>
            <?php if (AmpConfig::get('broadcast')) { ?>
            <li id="sb_home_browse_music_broadcast"><a href="<?php echo $web_path; ?>/browse.php?action=broadcast"><?php echo T_('Broadcasts'); ?></a></li>
            <?php } ?>
            <li id="sb_home_browse_music_radioStation"><a href="<?php echo $web_path; ?>/browse.php?action=live_stream"><?php echo T_('Radio Stations'); ?></a></li>
            <?php if (AmpConfig::get('allow_upload')) { ?>
            <li id="sb_home_browse_music_upload"><a href="<?php echo $web_path; ?>/upload.php"><?php echo T_('Upload'); ?></a></li>
            <?php } ?>
        </ul>
    </li>
    <?php if (AmpConfig::get('allow_video')) { ?>
        <li><h4 class="header"><span class="sidebar-header-title"><?php echo T_('Browse Video'); ?></span><span class="sprite sprite-icon_all <?php echo isset($_COOKIE['sb_home_browse_video']) ? $_COOKIE['sb_home_browse_video'] : 'expanded'; ?>" id="browse_video" lt="<?php echo T_('Expand/Collapse'); ?>" title="<?php echo T_('Expand/Collapse'); ?>"></span></h4>
            <ul class="sb3" id="sb_home_browse_video">
                <li id="sb_home_browse_video_clip"><a href="<?php echo $web_path; ?>/browse.php?action=clip"><?php echo T_('Music Clips'); ?></a></li>
                <li id="sb_home_browse_video_tvShow"><a href="<?php echo $web_path; ?>/browse.php?action=tvshow"><?php echo T_('TV Shows'); ?></a></li>
                <li id="sb_home_browse_video_movie"><a href="<?php echo $web_path; ?>/browse.php?action=movie"><?php echo T_('Movies'); ?></a></li>
                <li id="sb_home_browse_video_video"><a href="<?php echo $web_path; ?>/browse.php?action=personal_video"><?php echo T_('Personal Videos'); ?></a></li>
                <li id="sb_home_browse_video_tagsVideo"><a href="<?php echo $web_path; ?>/browse.php?action=tag&type=video"><?php echo T_('Tag Cloud'); ?></a></li>
            </ul>
        </li>
    <?php } ?>
    <?php Ajax::start_container('browse_filters'); ?>
    <?php Ajax::end_container(); ?>
    <li>
        <h4 class="header"><span class="sidebar-header-title" title="<?php echo T_('Playlist'); ?>"><?php echo T_('Playlist'); ?></span><span class="sprite sprite-icon_all <?php echo isset($_COOKIE['sb_home_playlist']) ? $_COOKIE['sb_home_playlist'] : 'expanded'; ?>" id="playlist" alt="<?php echo T_('Expand/Collapse'); ?>" title="<?php echo T_('Expand/Collapse'); ?>"></span></h4>
        <ul class="sb3" id="sb_home_playlist">
            <li id="sb_home_playlist_currentlyPlaying"><a href="<?php echo AmpConfig::get('web_path'); ?>"><?php echo T_('Currently Playing'); ?></a></li>
            <?php if (AmpConfig::get('allow_democratic_playback')) { ?>
            <li id="sb_home_playlist_playlist"><a href="<?php echo $web_path; ?>/democratic.php?action=show_playlist"><?php echo T_('Democratic'); ?></a></li>
            <?php } ?>
            <?php if ($server_allow = AmpConfig::get('allow_localplay_playback') AND $controller = AmpConfig::get('localplay_controller') AND $access_check = Access::check('localplay','5')) { ?>
            <?php
                // Little bit of work to be done here
                $localplay = new Localplay(AmpConfig::get('localplay_controller'));
                $current_instance = $localplay->current_instance();
                $class = $current_instance ? '' : ' class="active_instance"';
            ?>
            <li id="sb_home_playlist_show"><a href="<?php echo $web_path; ?>/localplay.php?action=show_playlist"><?php echo T_('Localplay'); ?></a></li>
            <?php } ?>
            <li id="sb_home_playlist_playlist"><a href="<?php echo $web_path; ?>/playlist.php?action=show_import_playlist"><?php echo T_('Import'); ?></a></li>
        </ul>
    </li>
    <li>
        <h4 class="header"><span class="sidebar-header-title" title="<?php echo T_('Random'); ?>"><?php echo T_('Random'); ?></span><span class="sprite sprite-icon_all <?php echo isset($_COOKIE['sb_home_random']) ? $_COOKIE['sb_home_random'] : 'expanded'; ?>" id="random" alt="<?php echo T_('Expand/Collapse'); ?>" title="<?php echo T_('Expand/Collapse'); ?>"></span></h4>
        <ul class="sb3" id="sb_home_random">
            <li id="sb_home_random_album"><?php echo Ajax::text('?page=random&action=song', T_('Song'),'home_random_song'); ?></li>
            <li id="sb_home_random_album"><?php echo Ajax::text('?page=random&action=album', T_('Album'),'home_random_album'); ?></li>
            <li id="sb_home_random_artist"><?php echo Ajax::text('?page=random&action=artist', T_('Artist'),'home_random_artist'); ?></li>
            <li id="sb_home_random_playlist"><?php echo Ajax::text('?page=random&action=playlist', T_('Playlist'),'home_random_playlist'); ?></li>
            <li id="sb_home_random_advanced"><a href="<?php echo $web_path; ?>/random.php?action=advanced&type=song"><?php echo T_('Advanced'); ?></a></li>
        </ul>
    </li>
    <li>
        <h4 class="header"><span class="sidebar-header-title" title="<?php echo T_('Information'); ?>"><?php echo T_('Information'); ?></span><span class="sprite sprite-icon_all <?php echo isset($_COOKIE['sb_home_info']) ? $_COOKIE['sb_home_info'] : 'expanded'; ?>" id="information" alt="<?php echo T_('Expand/Collapse'); ?>" title="<?php echo T_('Expand/Collapse'); ?>"></span></h4>
        <ul class="sb3" id="sb_home_info">
            <li id="sb_home_info_recent"><a href="<?php echo $web_path; ?>/stats.php?action=recent"><?php echo T_('Recent'); ?></a></li>
            <li id="sb_home_info_newest"><a href="<?php echo $web_path; ?>/stats.php?action=newest"><?php echo T_('Newest'); ?></a></li>
            <li id="sb_home_info_popular"><a href="<?php echo $web_path; ?>/stats.php?action=popular"><?php echo T_('Popular'); ?></a></li>
            <?php if (AmpConfig::get('ratings')) { ?>
            <li id="sb_home_info_highest"><a href="<?php echo $web_path; ?>/stats.php?action=highest"><?php echo T_('Top Rated'); ?></a></li>
            <?php } ?>
            <?php if (AmpConfig::get('userflags')) { ?>
            <li id="sb_home_info_userFlag"><a href="<?php echo $web_path; ?>/stats.php?action=userflag"><?php echo T_('Favorites'); ?></a></li>
            <?php } ?>
            <?php if (AmpConfig::get('wanted')) { ?>
            <li id="sb_home_info_wanted"><a href="<?php echo $web_path; ?>/stats.php?action=wanted"><?php echo T_('Wanted List'); ?></a></li>
            <?php } ?>
            <?php if (AmpConfig::get('share')) { ?>
            <li id="sb_home_info_share"><a href="<?php echo $web_path; ?>/stats.php?action=share"><?php echo T_('Shared Objects'); ?></a></li>
            <?php } ?>
            <?php if (AmpConfig::get('allow_upload')) { ?>
            <li id="sb_home_info_upload"><a href="<?php echo $web_path; ?>/stats.php?action=upload"><?php echo T_('Uploads'); ?></a></li>
            <?php } ?>
            <li id="sb_home_info_statistic"><a href="<?php echo $web_path; ?>/stats.php?action=show"><?php echo T_('Statistics'); ?></a></li>
        </ul>
    </li>
    <li>
        <h4 class="header"><span class="sidebar-header-title" title="<?php echo T_('Search'); ?>"><?php echo T_('Search'); ?></span><span class="sprite sprite-icon_all <?php echo isset($_COOKIE['sb_home_search']) ? $_COOKIE['sb_home_search'] : 'expanded'; ?>" id="search" alt="<?php echo T_('Expand/Collapse'); ?>" title="<?php echo T_('Expand/Collapse'); ?>"></span></h4>
        <ul class="sb3" id="sb_home_search">
          <li id="sb_home_search_song"><a href="<?php echo $web_path; ?>/search.php?type=song"><?php echo T_('Songs'); ?></a></li>
          <li id="sb_home_search_album"><a href="<?php echo $web_path; ?>/search.php?type=album"><?php echo T_('Albums'); ?></a></li>
          <li id="sb_home_search_artist"><a href="<?php echo $web_path; ?>/search.php?type=artist"><?php echo T_('Artists'); ?></a></li>
          <li id="sb_home_search_playlist"><a href="<?php echo $web_path; ?>/search.php?type=playlist"><?php echo T_('Playlists'); ?></a></li>
          <li id="sb_home_search_video"><a href="<?php echo $web_path; ?>/search.php?type=video"><?php echo T_('Videos'); ?></a></li>
        </ul>
    </li>
</ul>
