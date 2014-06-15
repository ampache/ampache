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
    <li><h4 class="header"><?php echo T_('Browse'); ?><span class="sprite sprite-icon_all <?php echo isset($_COOKIE['sb_browse']) ? $_COOKIE['sb_browse'] : 'expanded'; ?>" id="browse" lt="<?php echo T_('Expand/Collapse'); ?>" title="<?php echo T_('Expand/Collapse'); ?>"></span></h4>
        <?php
        if (isset($_REQUEST['action'])) {
            $text = scrub_in($_REQUEST['action']) . '_ac';
            ${$text} = ' selected="selected"';
        }
        ?>
        <ul class="sb3" id="sb_browse_bb">
            <li id="sb_browse_bb_SongTitle"><?php echo UI::create_link('content', 'browse', array('action' => 'song'), T_('Song Titles'), 'home_browse_song'); ?></li>
            <li id="sb_browse_bb_Album"><?php echo UI::create_link('content', 'browse', array('action' => 'album'), T_('Albums'), 'home_browse_album'); ?></li>
            <li id="sb_browse_bb_Artist"><?php echo UI::create_link('content', 'browse', array('action' => 'artist'), T_('Artists'), 'home_browse_artist'); ?></li>
            <li id="sb_browse_bb_Tags"><?php echo UI::create_link('content', 'browse', array('action' => 'tag'), T_('Tag Cloud'), 'home_browse_tag'); ?></li>
            <li id="sb_browse_bb_Playlist"><?php echo UI::create_link('content', 'browse', array('action' => 'playlist'), T_('Playlists'), 'home_browse_playlist'); ?></li>
            <li id="sb_browse_bb_SmartPlaylist"><?php echo UI::create_link('content', 'browse', array('action' => 'smartplaylist'), T_('Smart Playlists'), 'home_browse_smartplaylist'); ?></li>
            <li id="sb_browse_bb_Channel"><?php echo UI::create_link('content', 'browse', array('action' => 'channel'), T_('Channel'), 'home_browse_channel'); ?></li>
            <?php if (AmpConfig::get('broadcast')) { ?>
            <li id="sb_browse_bb_Broadcast"><?php echo UI::create_link('content', 'browse', array('action' => 'broadcast'), T_('Broadcasts'), 'home_browse_broadcast'); ?></li>
            <?php } ?>
            <li id="sb_browse_bb_RadioStation"><?php echo UI::create_link('content', 'browse', array('action' => 'live_stream'), T_('Radio Stations'), 'home_browse_live_stream'); ?></li>
            <li id="sb_browse_bb_Video"><?php echo UI::create_link('content', 'browse', array('action' => 'video'), T_('Videos'), 'home_browse_video'); ?></li>
            <?php if (AmpConfig::get('allow_upload')) { ?>
            <li id="sb_browse_bb_Upload"><?php echo UI::create_link('content', 'upload', array(), T_('Upload'), 'home_upload'); ?></li>
            <?php } ?>
        </ul>
    </li>
    <?php Ajax::start_container('browse_filters'); ?>
    <?php Ajax::end_container(); ?>
    <li>
        <h4 class="header"><?php echo T_('Playlist'); ?><span class="sprite sprite-icon_all <?php echo isset($_COOKIE['sb_playlist']) ? $_COOKIE['sb_playlist'] : 'expanded'; ?>" id="playlist" alt="<?php echo T_('Expand/Collapse'); ?>" title="<?php echo T_('Expand/Collapse'); ?>"></span></h4>
        <ul class="sb3" id="sb_home_info">
            <li id="sb_home_info_CurrentlyPlaying"><?php echo UI::create_link('content', 'index', array('action' => 'currently_playing'), T_('Currently Playing'), 'home_index_currently_playing'); ?></li>
            <?php if (AmpConfig::get('allow_democratic_playback')) { ?>
            <li id="sb_home_democratic_playlist"><?php echo UI::create_link('content', 'democratic', array('action' => 'show_playlist'), T_('Democratic'), 'home_democratic_show_playlist'); ?></li>
            <?php } ?>
            <?php if ($server_allow = AmpConfig::get('allow_localplay_playback') AND $controller = AmpConfig::get('localplay_controller') AND $access_check = Access::check('localplay','5')) { ?>
            <?php
            // Little bit of work to be done here
            $localplay = new Localplay(AmpConfig::get('localplay_controller'));
            $current_instance = $localplay->current_instance();
            $class = $current_instance ? '' : ' class="active_instance"';
            ?>
            <li id="sb_localplay_info_show"><?php echo UI::create_link('content', 'localplay', array('action' => 'show_playlist'), T_('Localplay'), 'home_localplay_show_playlist'); ?></li>
            <?php } ?>
            <li id="sb_browse_bb_Playlist"><?php echo UI::create_link('content', 'playlist', array('action' => 'show_import_playlist'), T_('Import'), 'home_playlist_import_playlist'); ?></li>
        </ul>
    </li>
    <li>
        <h4 class="header"><?php echo T_('Random'); ?><span class="sprite sprite-icon_all <?php echo isset($_COOKIE['sb_random']) ? $_COOKIE['sb_random'] : 'expanded'; ?>" id="random" alt="<?php echo T_('Expand/Collapse'); ?>" title="<?php echo T_('Expand/Collapse'); ?>"></span></h4>
        <ul class="sb3" id="sb_home_random">
            <li id="sb_home_random_album"><?php echo Ajax::text('?page=random&action=song', T_('Song'),'home_random_song'); ?></li>
            <li id="sb_home_random_album"><?php echo Ajax::text('?page=random&action=album', T_('Album'),'home_random_album'); ?></li>
            <li id="sb_home_random_artist"><?php echo Ajax::text('?page=random&action=artist', T_('Artist'),'home_random_artist'); ?></li>
            <li id="sb_home_random_playlist"><?php echo Ajax::text('?page=random&action=playlist', T_('Playlist'),'home_random_playlist'); ?></li>
            <li id="sb_home_random_advanced"><?php echo UI::create_link('content', 'random', array('action' => 'advanced', 'type' => 'song'), T_('Advanced'), 'home_random_advanced'); ?></li>
        </ul>
    </li>
    <li>
        <h4 class="header"><?php echo T_('Information'); ?><span class="sprite sprite-icon_all <?php echo isset($_COOKIE['sb_information']) ? $_COOKIE['sb_information'] : 'expanded'; ?>" id="information" alt="<?php echo T_('Expand/Collapse'); ?>" title="<?php echo T_('Expand/Collapse'); ?>"></span></h4>
        <ul class="sb3" id="sb_home_info">
            <li id="sb_home_info_Recent"><?php echo UI::create_link('content', 'stats', array('action' => 'recent'), T_('Recent'), 'home_stats_recent'); ?></li>
            <li id="sb_home_info_Newest"><?php echo UI::create_link('content', 'stats', array('action' => 'newest'), T_('Newest'), 'home_stats_newest'); ?></li>
            <li id="sb_home_info_Popular"><?php echo UI::create_link('content', 'stats', array('action' => 'popular'), T_('Popular'), 'home_stats_popular'); ?></li>
            <?php if (AmpConfig::get('ratings')) { ?>
            <li id="sb_home_info_Highest"><?php echo UI::create_link('content', 'stats', array('action' => 'highest'), T_('Top Rated'), 'home_stats_highest'); ?></li>
            <?php } ?>
            <?php if (AmpConfig::get('userflags')) { ?>
            <li id="sb_home_info_UserFlag"><?php echo UI::create_link('content', 'stats', array('action' => 'userflag'), T_('Favorites'), 'home_stats_userflag'); ?></li>
            <?php } ?>
            <?php if (AmpConfig::get('wanted')) { ?>
            <li id="sb_home_info_Wanted"><?php echo UI::create_link('content', 'stats', array('action' => 'wanted'), T_('Wanted List'), 'home_stats_wanted'); ?></li>
            <?php } ?>
            <?php if (AmpConfig::get('share')) { ?>
            <li id="sb_home_info_Share"><?php echo UI::create_link('content', 'stats', array('action' => 'share'), T_('Shared Objects'), 'home_stats_share'); ?></li>
            <?php } ?>
            <?php if (AmpConfig::get('allow_upload')) { ?>
            <li id="sb_browse_bb_Upload"><?php echo UI::create_link('content', 'stats', array('action' => 'upload'), T_('Uploads'), 'home_stats_upload'); ?></li>
            <?php } ?>
             <li id="sb_home_info_Statistics"><?php echo UI::create_link('content', 'stats', array('action' => 'show'), T_('Statistics'), 'home_stats_show'); ?></li>
        </ul>
    </li>
    <li>
        <h4 class="header"><?php echo T_('Search'); ?><span class="sprite sprite-icon_all <?php echo isset($_COOKIE['sb_search']) ? $_COOKIE['sb_search'] : 'expanded'; ?>" id="search" alt="<?php echo T_('Expand/Collapse'); ?>" title="<?php echo T_('Expand/Collapse'); ?>"></span></h4>
        <ul class="sb3" id="sb_home_search">
          <li id="sb_home_search_song"><?php echo UI::create_link('content', 'search', array('action' => 'search', 'type' => 'song'), T_('Songs'), 'home_search_song'); ?></li>
          <li id="sb_home_search_album"><?php echo UI::create_link('content', 'search', array('action' => 'search', 'type' => 'album'), T_('Albums'), 'home_search_album'); ?></li>
          <li id="sb_home_search_artist"><?php echo UI::create_link('content', 'search', array('action' => 'search', 'type' => 'artist'), T_('Artists'), 'home_search_artist'); ?></li>
          <li id="sb_home_search_playlist"><?php echo UI::create_link('content', 'search', array('action' => 'search', 'type' => 'playlist'), T_('Playlists'), 'home_search_playlist'); ?></li>
          <li id="sb_home_search_video"><?php echo UI::create_link('content', 'search', array('action' => 'search', 'type' => 'video'), T_('Videos'), 'home_search_video'); ?></li>
        </ul>
    </li>
</ul>
