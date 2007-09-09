<ul class="sb2" id="sb_admin">
  <li><h4><?php echo _('Catalogs'); ?></h4>
    <div class="sb3"><a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_add_catalog"><?php echo _('Add a Catalog'); ?></a></div>
    <div class="sb3"><a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_catalogs"><?php echo _('Show Catalogs'); ?></a></div>
  </li>

  <li><h4><?php echo _('User Tools'); ?></h4>
    <ul class="sb3" id="sb_admin_ut">
      <li id="sb_admin_ut_AddUser"><a href="<?php echo $web_path; ?>/admin/users.php?action=show_add_user"><?php echo _('Add User'); ?></a></li>
      <li id="sb_admin_ut_BrowseUsers"><a href="<?php echo $web_path; ?>/admin/users.php"><?php echo _('Browse Users'); ?></a></li>
    </ul>
  </li>

  <li><h4><?php echo _('Other Tools'); ?></h4>
    <ul class="sb3" id="sb_admin_ot">
      <li id="sb_admin_ot_ClearNowPlaying"><a href="<?php echo $web_path; ?>/admin/catalog.php?action=clear_now_playing"><?php echo _('Clear Now Playing'); ?></a></li>
      <li id="sb_admin_ot_ClearCatStats"><a href="<?php echo $web_path; ?>/admin/catalog.php?action=clear_stats"><?php echo _('Clear Catalog Stats'); ?></a></li>
      <li id="sb_admin_ot_GatherArt"><a href="<?php echo $web_path; ?>/admin/catalog.php?action=gather_album_art"><?php echo _('Gather Album Art'); ?></a></li>
    </ul>
  </li>

</ul>


