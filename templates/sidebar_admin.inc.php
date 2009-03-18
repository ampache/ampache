<?php
/*

 Copyright (c) Ampache.org
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
      <li id="sb_admin_acl_AddAccess"><a href="<?php echo $web_path; ?>/admin/access.php?action=show_add_advanced"><?php echo _('Add ACL'); ?></a></li>
      <li id="sb_admin_acl_ShowAccess"><a href="<?php echo $web_path; ?>/admin/access.php"><?php echo _('Show ACL(s)'); ?></a></li>
    </ul>
  </li>
  <li><h4><?php echo _('Other Tools'); ?></h4>
    <ul class="sb3" id="sb_admin_ot">
      <li id="sb_admin_ot_Debug"><a href="<?php echo $web_path; ?>/admin/system.php?action=show_debug"><?php echo _('Ampache Debug'); ?></a></li>
      <li id="sb_admin_ot_ClearNowPlaying"><a href="<?php echo $web_path; ?>/admin/catalog.php?action=clear_now_playing"><?php echo _('Clear Now Playing'); ?></a></li>
      <li id="sb_admin_ot_ExportCatalog"><a href="<?php echo $web_path; ?>/admin/export.php"><?php echo _('Export Catalog'); ?></a></li>
      <?php if (Config::get('shoutbox')) { ?>
      <li id="sb_admin_ot_ManageShoutbox"><a href="<?php echo $web_path; ?>/admin/shout.php"><?php echo _('Manage Shoutbox'); ?></a></li>
      <?php } ?>
    </ul>
  </li>
<?php if (Access::check('interface','100')) { ?>
  <li><h4><?php echo _('Server Config'); ?></h4>
    <ul class="sb3" id="sb_preferences_sc">
<?php
	$catagories = Preference::get_catagories();
        foreach ($catagories as $name) {
                $f_name = ucfirst($name);
?>
      <li id="sb_preferences_sc_<?php echo $f_name; ?>"><a href="<?php echo $web_path; ?>/preferences.php?action=admin&amp;tab=<?php echo $name; ?>"><?php echo _($f_name); ?></a></li>
<?php } ?>
    </ul>
  </li>
<?php } ?>
</ul>


