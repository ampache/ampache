<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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
  <li><h4><?php echo T_('Browse'); ?></h4>
  <?php
    // Build the selected dealie
    if (isset($_REQUEST['action'])) {
        $text = scrub_in($_REQUEST['action']) . '_ac';
        ${$text} = ' selected="selected"';
    }
  ?>
    <ul class="sb3" id="sb_browse_bb">
      <li id="sb_browse_bb_SongTitle"><a href="<?php echo $web_path; ?>/browse.php?action=song"><?php echo T_('Song Titles'); ?></a></li>
      <li id="sb_browse_bb_Album"><a href="<?php echo $web_path; ?>/browse.php?action=album"><?php echo T_('Albums'); ?></a></li>
      <li id="sb_browse_bb_Artist"><a href="<?php echo $web_path; ?>/browse.php?action=artist"><?php echo T_('Artists'); ?></a></li>
      <li id="sb_browse_bb_Tags"><a href="<?php echo $web_path; ?>/browse.php?action=tag"><?php echo T_('Tag Cloud'); ?></a></li>
      <li id="sb_browse_bb_Playlist"><a href="<?php echo $web_path; ?>/browse.php?action=playlist"><?php echo T_('Playlists'); ?></a></li>
      <li id="sb_browse_bb_SmartPlaylist"><a href="<?php echo $web_path; ?>/browse.php?action=smartplaylist"><?php echo T_('Smart Playlists'); ?></a></li>
      <li id="sb_browse_bb_RadioStation"><a href="<?php echo $web_path; ?>/browse.php?action=live_stream"><?php echo T_('Radio Stations'); ?></a></li>
      <li id="sb_browse_bb_Video"><a href="<?php echo $web_path; ?>/browse.php?action=video"><?php echo T_('Videos'); ?></a></li>
    </ul>
  </li>
<?php Ajax::start_container('browse_filters'); ?>
<?php Ajax::end_container(); ?>
  <li><h4><?php echo T_('Playlist'); ?></h4>
    <ul class="sb3" id="sb_home_info">
      <li id="sb_home_info_CurrentlyPlaying"><a href="<?php echo $web_path; ?>/index.php"><?php echo T_('Currently Playing'); ?></a></li>
<?php if (Config::get('allow_democratic_playback')) { ?>
      <li id="sb_home_democratic_playlist"><a href="<?php echo $web_path; ?>/democratic.php?action=show_playlist"><?php echo T_('Democratic'); ?></a></li>
<?php } ?>
<?php if ($server_allow = Config::get('allow_localplay_playback') AND $controller = Config::get('localplay_controller') AND $access_check = Access::check('localplay','5')) { ?>
<?php
        // Little bit of work to be done here
        $localplay = new Localplay(Config::get('localplay_controller'));
        $current_instance = $localplay->current_instance();
        $class = $current_instance ? '' : ' class="active_instance"';
?>
        <li id="sb_localplay_info_show"><a href="<?php echo $web_path; ?>/localplay.php?action=show_playlist"><?php echo T_('Localplay'); ?></a></li>
<?php } ?>
      <li id="sb_browse_bb_Playlist"><a href="<?php echo $web_path; ?>/playlist.php?action=show_import_playlist"><?php echo T_('Import'); ?></a></li>
    </ul>
  </li>
  <li><h4><?php echo T_('Random'); ?></h4>
    <ul class="sb3" id="sb_home_random">
      <li id="sb_home_random_album"><?php echo Ajax::text('?page=random&action=album', T_('Album'),'home_random_album'); ?></li>
      <li id="sb_home_random_artist"><?php echo Ajax::text('?page=random&action=artist', T_('Artist'),'home_random_artist'); ?></li>
      <li id="sb_home_random_playlist"><?php echo Ajax::text('?page=random&action=playlist', T_('Playlist'),'home_random_playlist'); ?></li>
      <li id="sb_home_random_advanced"><a href="<?php echo $web_path; ?>/random.php?action=advanced&type=song"><?php echo T_('Advanced'); ?></a></li>
    </ul>
  </li>
  <li><h4><?php echo T_('Information'); ?></h4>
    <ul class="sb3" id="sb_home_info">
      <li id="sb_home_info_Statistics"><a href="<?php echo $web_path; ?>/stats.php?action=show"><?php echo T_('Statistics'); ?></a></li>
      <li id="sb_home_info_Newest"><a href="<?php echo $web_path; ?>/stats.php?action=newest"><?php echo T_('Newest'); ?></a></li>
      <li id="sb_home_info_Popular"><a href="<?php echo $web_path; ?>/stats.php?action=popular"><?php echo T_('Popular'); ?></a></li>
    </ul>
  </li>
  <li><h4><?php echo T_('Search'); ?></h4>
    <ul class="sb3" id="sb_home_search">
      <li id="sb_home_search_song"><a href="<?php echo $web_path; ?>/search.php?type=song"><?php echo T_('Songs'); ?></a></li>
      <li id="sb_home_search_album"><a href="<?php echo $web_path; ?>/search.php?type=album"><?php echo T_('Albums'); ?></a></li>
      <li id="sb_home_search_artist"><a href="<?php echo $web_path; ?>/search.php?type=artist"><?php echo T_('Artists'); ?></a></li>
      <li id="sb_home_search_video"><a href="<?php echo $web_path; ?>/search.php?type=video"><?php echo T_('Videos'); ?></a></li>
    </ul>
  </li>
</ul>
