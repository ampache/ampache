<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */

/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2019 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */ ?>
<ul class="sb2" id="sb_admin">
  <li class="<?php if (!(filter_has_var(INPUT_COOKIE, 'sb_catalogs'))) {
    echo 'collapsed';
} else {
    echo $_COOKIE['sb_catalogs'];
} ?>">
    <h4 class="header"><span class="sidebar-header-title" title="<?php echo T_('Catalogs'); ?>"><?php echo T_('Catalogs'); ?></span><?php echo UI::get_icon('all', T_('Expand/Collapse'), 'catalogs', 'header-img'); ?></h4>
    <ul class="sb3" id="sb_admin_catalogs">
      <li id="sb_admin_catalogs_Add"><a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_add_catalog"><?php echo T_('Add Catalog'); ?></a></li>
      <li id="sb_admin_catalogs_Show"><a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_catalogs"><?php echo T_('Show Catalogs'); ?></a></li>
      <li id="sb_admin_ot_ExportCatalog"><a href="<?php echo $web_path; ?>/admin/export.php"><?php echo T_('Export Catalog'); ?></a></li>
      <li id="sb_admin_ot_Duplicates"><a href="<?php echo $web_path; ?>/admin/duplicates.php"><?php echo T_('Find Duplicates'); ?></a></li>
      <?php if (AmpConfig::get('licensing')) { ?>
        <li id="sb_admin_ot_ManageLicense"><a href="<?php echo $web_path; ?>/admin/license.php"><?php echo T_('Manage Licenses'); ?></a></li>
      <?php
} ?>
    </ul>
  </li>
  <?php if (Access::check('interface', '100')) {
    ?>
    <li class="<?php if (!(filter_has_var(INPUT_COOKIE, 'sb_catalogs'))) {
        echo 'collapsed';
    } else {
        echo $_COOKIE['sb_catalogs'];
    } ?>">
      <h4 class="header"><span class="sidebar-header-title" title="<?php echo T_('User Tools'); ?>"><?php echo T_('User Tools'); ?></span><?php echo UI::get_icon('all', T_('Expand/Collapse'), 'user_tools', 'header-img'); ?></h4>
      <ul class="sb3" id="sb_admin_ut">
        <li id="sb_admin_ut_AddUser"><a href="<?php echo $web_path; ?>/admin/users.php?action=show_add_user"><?php echo T_('Add User'); ?></a></li>
        <li id="sb_admin_ut_BrowseUsers"><a href="<?php echo $web_path; ?>/admin/users.php"><?php echo T_('Browse Users'); ?></a></li>
        <?php
          if (Mailer::is_mail_enabled()) { ?>
          <li id="sb_admin_ot_Mail"><a href="<?php echo $web_path; ?>/admin/mail.php"><?php echo T_('E-mail Users'); ?></a></li>
        <?php
          }
    if (AmpConfig::get('sociable')) { ?>
          <li id="sb_admin_ot_ManageShoutbox"><a href="<?php echo $web_path; ?>/admin/shout.php"><?php echo T_('Manage Shoutbox'); ?></a></li>
        <?php
        } ?>
        <li id="sb_admin_ot_ClearNowPlaying"><a href="<?php echo $web_path; ?>/admin/catalog.php?action=clear_now_playing"><?php echo T_('Clear Now Playing'); ?></a></li>
      </ul>
    </li>
    <li class="<?php if (!(filter_has_var(INPUT_COOKIE, 'sb_access_control'))) {
            echo 'collapsed';
        } else {
            echo $_COOKIE['sb_access_control'];
        } ?>">
      <h4 class="header"><span class="sidebar-header-title" title="<?php echo T_('Access Control'); ?>"><?php echo T_('Access Control'); ?></span><?php echo UI::get_icon('all', T_('Expand/Collapse'), 'access_control', 'header-img'); ?></h4>
      <ul class="sb3" id="sb_admin_acl">
        <li id="sb_admin_acl_AddAccess"><a href="<?php echo $web_path; ?>/admin/access.php?action=show_add_advanced"><?php echo T_('Add ACL'); ?></a></li>
        <li id="sb_admin_acl_ShowAccess"><a href="<?php echo $web_path; ?>/admin/access.php"><?php echo T_('Show ACL(s)'); ?></a></li>
      </ul>
    </li>
    <li>
      <?php Ajax::start_container('browse_filters'); ?>
      <?php Ajax::end_container(); ?>
    </li>
    <li class="<?php if (!(filter_has_var(INPUT_COOKIE, 'sb_modules'))) {
            echo 'collapsed';
        } else {
            echo $_COOKIE['sb_modules'];
        } ?>">
      <h4 class="header"><span class="sidebar-header-title" title="<?php echo T_('Modules'); ?>"><?php echo T_('Modules'); ?></span><?php echo UI::get_icon('all', T_('Expand/Collapse'), 'modules', 'header-img'); ?></h4>
      <ul class="sb3" id="sb_Modules">
        <li id="sb_preferences_mo_localplay"><a href="<?php echo $web_path; ?>/admin/modules.php?action=show_localplay"><?php echo T_('Localplay Controllers'); ?></a></li>
        <li id="sb_preferences_mo_catalog_types"><a href="<?php echo $web_path; ?>/admin/modules.php?action=show_catalog_types"><?php echo T_('Catalog Types'); ?></a></li>
        <li id="sb_preferences_mo_plugins"><a href="<?php echo $web_path; ?>/admin/modules.php?action=show_plugins"><?php echo T_('Manage Plugins'); ?></a></li>
      </ul>
    </li>
    <li class="<?php if (!(filter_has_var(INPUT_COOKIE, 'sb_server_config'))) {
            echo 'collapsed';
        } else {
            echo $_COOKIE['sb_server_config'];
        } ?>">
      <h4 class="header"><span class="sidebar-header-title" title="<?php echo T_('Server Config'); ?>"><?php echo T_('Server Config'); ?></span><?php echo UI::get_icon('all', T_('Expand/Collapse'), 'server_config', 'header-img'); ?></h4>
      <ul class="sb3" id="sb_preferences_sc">
        <li id="sb_admin_ot_Debug"><a href="<?php echo $web_path; ?>/admin/system.php?action=show_debug"><?php echo T_('Ampache Debug'); ?></a></li>
        <?php
          $categories = Preference::get_categories();
    foreach ($categories as $name) {
        $f_name = ucfirst($name); ?>
          <li id="sb_preferences_sc_<?php echo $f_name; ?>"><a href="<?php echo $web_path; ?>/preferences.php?action=admin&amp;tab=<?php echo $name; ?>"><?php echo T_($f_name); ?></a></li>
        <?php
    } ?>
      </ul>
    </li>
</ul>
<?php
} ?>