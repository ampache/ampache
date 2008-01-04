<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

?>

<ul class="sb2" id="sb_home">
  <li><h4><?php echo _('Information'); ?></h4>
    <ul class="sb3" id="sb_home_info">
      <li id="sb_home_info_CurrentlyPlaying"><a href="<?php echo $web_path; ?>/index.php"><?php echo _('Currently Playing'); ?></a></li>
      <li id="sb_home_info_Statistics"><a href="<?php echo $web_path; ?>/stats.php"><?php echo _('Statistics'); ?></a></li>
      <li id="sb_home_info_AddStationRadio"><a href="<?php echo $web_path; ?>/radio.php?action=show_create"><?php echo _('Add Radio Station'); ?></a></li>
    </ul>
  </li>
<?php if (Config::get('allow_democratic_playback')) { ?>
  <li><h4><?php echo _('Democratic'); ?></h4>
    <ul class="sb3" id="sb_home_democratic">
      <li id="sb_home_democratic_playlist"><a href="<?php echo $web_path; ?>/democratic.php?action=show_playlist"><?php echo _('Show Playlist'); ?></a></li>
      <li id="sb_home_democratic_playlist"><a href="<?php echo $web_path; ?>/democratic.php?action=manage_playlists"><?php echo _('Manage Playlist'); ?></a></li>
    </ul>
  </li>
<?php } ?>
  <li><h4><?php echo _('Random'); ?></h4>
    <ul class="sb3" id="sb_home_random">
      <li id="sb_home_random_album"><?php echo Ajax::text('?page=random&action=album',_('Album'),'home_random_album'); ?></li>
      <li id="sb_home_random_artist"><?php echo Ajax::text('?page=random&action=artist',_('Artist'),'home_random_artist'); ?></li>
      <li id="sb_home_random_playlist"><?php echo Ajax::text('?page=random&action=playlist',_('Playlist'),'home_random_playlist'); ?></li>
      <li id="sb_home_random_advanced"><a href="<?php echo $web_path; ?>/random.php?action=advanced"><?php echo _('Advanced'); ?></a></li>
    </ul>
  </li>
</ul>
