<ul class="sb2" id="sb_home">
  <li><h4><?php echo _('Information'); ?></h4>
    <ul class="sb3" id="sb_home_info">
      <li id="sb_home_info_CurrentlyPlaying"><a href="<?php echo $web_path; ?>/index.php"><?php echo _('Currently Playing'); ?></a></li>
      <li id="sb_home_info_Statistics"><a href="<?php echo $web_path; ?>/stats.php"><?php echo _('Statistics'); ?></a></li>
      <li id="sb_home_info_AddStationRadio"><a href="<?php echo $web_path; ?>/radio.php?action=show_create"><?php echo _('Add Radio Station'); ?></a></li>
    </ul>
  </li>
  <li><h4><?php echo _('Random'); ?></h4>
    <ul class="sb3" id="sb_home_random">
      <li id="sb_home_random_album"><?php echo Ajax::text('?page=random&action=album',_('Album'),'home_random_album'); ?></li>
      <li id="sb_home_random_artist"><?php echo Ajax::text('?page=random&action=artist',_('Artist'),'home_random_artist'); ?></li>
      <li id="sb_home_random_playlist"><?php echo Ajax::text('?page=random&action=playlist',_('Playlist'),'home_random_playlist'); ?></li>
      <li id="sb_home_random_advanced"><a href="<?php echo $web_path; ?>/random.php?action=advanced"><?php echo _('Advanced'); ?></a></li>
    </ul>
</ul>
