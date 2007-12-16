<ul class="sb2" id="sb_admin">
  <li><h4><?php echo _('Catalogs'); ?></h4>
   <ul class="sb3" id="sb_admin_catalogs">
    <li id="sb_admin_catalogs_Add"><a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_add_catalog"><?php echo _('Add a Catalog'); ?></a></li>
    <li id="sb_admin_catalogs_Show"><a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_catalogs"><?php echo _('Show Catalogs'); ?></a></li>
   </ul>
  </li>

  <li><h4><?php echo _('User Tools'); ?></h4>
    <ul class="sb3" id="sb_admin_ut">
      <li id="sb_admin_ut_AddUser"><a href="<?php echo $web_path; ?>/admin/users.php?action=show_add_user"><?php echo _('Add User'); ?></a></li>
      <li id="sb_admin_ut_BrowseUsers"><a href="<?php echo $web_path; ?>/admin/users.php"><?php echo _('Browse Users'); ?></a></li>
    </ul>
  </li>
  <li><h4><?php echo _('Access Control'); ?></h4>
    <ul class="sb3" id="sb_admin_acl">
      <li id="sb_admin_acl_AddAccess"><a href="<?php echo $web_path; ?>/admin/access.php?action=show_add_host"><?php echo _('Add ACL'); ?></a></li>
      <li id="sb_admin_acl_ShowAccess"><a href="<?php echo $web_path; ?>/admin/access.php"><?php echo _('Show ACL(s)'); ?></a></li>
    </ul>
  </li>
  <li><h4><?php echo _('Other Tools'); ?></h4>
    <ul class="sb3" id="sb_admin_ot">
      <li id="sb_admin_ot_Duplicates"><a href="<?php echo $web_path; ?>/admin/duplicates.php"><?php echo _('Find Duplicates'); ?></a></li>
      <li id="sb_admin_ot_ClearNowPlaying"><a href="<?php echo $web_path; ?>/admin/catalog.php?action=clear_now_playing"><?php echo _('Clear Now Playing'); ?></a></li>
      <li id="sb_admin_ot_ClearCatStats"><a href="<?php echo $web_path; ?>/admin/catalog.php?action=clear_stats"><?php echo _('Clear Catalog Stats'); ?></a></li>
    </ul>
  </li>

</ul>


