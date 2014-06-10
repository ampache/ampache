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
<ul class="sb2" id="sb_admin">
  <li><h4 class="header"><?php echo T_('Catalogs'); ?><span class="sprite sprite-icon_all <?php echo isset($_COOKIE['sb_catalogs']) ? $_COOKIE['sb_catalogs'] : 'expanded'; ?>" id="catalogs" alt="<?php echo T_('Expand/Collapse'); ?>" title="<?php echo T_('Expand/Collapse'); ?>"></h4>
   <ul class="sb3" id="sb_admin_catalogs">
    <li id="sb_admin_catalogs_Add"><?php echo UI::create_link('content', 'catalog', array('action' => 'show_add_catalog'), T_('Add a Catalog'), 'admin_catalog_show_add_catalog'); ?></li>
    <li id="sb_admin_catalogs_Show"><?php echo UI::create_link('content', 'catalog', array('action' => 'show_catalog'), T_('Show Catalogs'), 'admin_catalog_show_catalog'); ?></li>
   </ul>
  </li>

  <li><h4 class="header"><?php echo T_('User Tools'); ?><span class="sprite sprite-icon_all <?php echo isset($_COOKIE['sb_user_tools']) ? $_COOKIE['sb_user_tools'] : 'expanded'; ?>" id="user_tools" alt="<?php echo T_('Expand/Collapse'); ?>" title="<?php echo T_('Expand/Collapse'); ?>"></h4>
    <ul class="sb3" id="sb_admin_ut">
      <li id="sb_admin_ut_AddUser"><?php echo UI::create_link('content', 'users', array('action' => 'show_add_user'), T_('Add User'), 'admin_user_show_add_user'); ?></li>
      <li id="sb_admin_ut_BrowseUsers"><?php echo UI::create_link('content', 'users', array(), T_('Browse Users'), 'admin_user_show_browse_user'); ?></li>
    </ul>
  </li>
  <li><h4 class="header"><?php echo T_('Access Control'); ?><span class="sprite sprite-icon_all <?php echo isset($_COOKIE['sb_access_control']) ? $_COOKIE['sb_access_control'] : 'expanded'; ?>" id="access_control" alt="<?php echo T_('Expand/Collapse'); ?>" title="<?php echo T_('Expand/Collapse'); ?>"></h4>
    <ul class="sb3" id="sb_admin_acl">
      <li id="sb_admin_acl_AddAccess"><?php echo UI::create_link('content', 'access', array('action' => 'show_add_advanced'), T_('Add ACL'), 'admin_access_show_add_advanced'); ?></li>
      <li id="sb_admin_acl_ShowAccess"><?php echo UI::create_link('content', 'access', array(), T_('Show ACL(s)'), 'admin_access_show_acl'); ?></li>
    </ul>
  </li>
  <li><h4 class="header"><?php echo T_('Other Tools'); ?><span class="sprite sprite-icon_all <?php echo isset($_COOKIE['sb_ad_other_tools']) ? $_COOKIE['sb_ad_other_tools'] : 'expanded'; ?>" id="ad_other_tools" alt="<?php echo T_('Expand/Collapse'); ?>" title="<?php echo T_('Expand/Collapse'); ?>"></h4>
    <ul class="sb3" id="sb_admin_ot">
      <li id="sb_admin_ot_Debug"><?php echo UI::create_link('content', 'system', array('action' => 'show_debug'), T_('Ampache Debug'), 'admin_system_show_debug'); ?></li>
      <li id="sb_admin_ot_ClearNowPlaying"><?php echo UI::create_link('content', 'catalog', array('action' => 'clear_now_playing'), T_('Clear Now Playing'), 'admin_catalog_clear_now_playing'); ?></li>
      <li id="sb_admin_ot_ExportCatalog"><?php echo UI::create_link('content', 'export', array(), T_('Export Catalog'), 'admin_export_export_catalog'); ?></li>
      <?php if (AmpConfig::get('sociable')) { ?>
      <li id="sb_admin_ot_ManageShoutbox"><?php echo UI::create_link('content', 'shout', array(), T_('Manage Shoutbox'), 'admin_shout_manage_shoutbox'); ?></li>
      <?php } ?>
    </ul>
  </li>
<?php if (Access::check('interface','100')) { ?>
  <li><h4 class="header"><?php echo T_('Server Config'); ?><span class="sprite sprite-icon_all <?php echo isset($_COOKIE['sb_server_config']) ? $_COOKIE['sb_server_config'] : 'expanded'; ?>" id="server_config" alt="<?php echo T_('Expand/Collapse'); ?>" title="<?php echo T_('Expand/Collapse'); ?>"></h4>
    <ul class="sb3" id="sb_preferences_sc">
<?php
    $catagories = Preference::get_catagories();
        foreach ($catagories as $name) {
                $f_name = ucfirst($name);
?>
      <li id="sb_preferences_sc_<?php echo $f_name; ?>"><?php echo UI::create_link('content', 'preferences', array('action' => 'admin', 'tab' => $name), T_($f_name), 'admin_preferences' . $name); ?></li>
<?php } ?>
    </ul>
  </li>
<?php } ?>
</ul>
