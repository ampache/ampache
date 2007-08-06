<ul class="sb2" id="sb_home">
  <li><?php echo _('Information'); ?>
    <ul class="sb3" id="sb_home_info">
      <li id="sb_home_info_CurrentlyPlaying"><a href="<?php echo $web_path; ?>/index.php"><?php echo _('Currently Playing'); ?></a></li>
      <li id="sb_home_info_Statistics"><a href="<?php echo $web_path; ?>/stats.php"><?php echo _('Statistics'); ?></a></li>
      <li id="sb_home_info_AddStationRadio"><a href="<?php echo $web_path; ?>/radio.php?action=show_create"><?php echo _('Add Radio Station'); ?></a></li>
    </ul>
  </li>
  <li><?php echo _('Playlists'); ?>
    <ul class="sb3" id="sb_home_playlists">
     <li id="sb_home_playlists_ViewAll"><a id="sb_ViewAll" href="<?php echo $web_path; ?>/playlist.php?action=show_all"><?php echo _('View All'); ?></a></li>
    </ul>
  </li>
</ul>
