<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

use Ampache\Config\AmpConfig;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Preference;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\Ui;

/** @var string $web_path */
/** @var string $t_expander */ ?>
<ul class="sb2" id="sb_admin">
    <?php if (AmpConfig::get('browse_filter')) {
        echo "<li>";
        Ajax::start_container('browse_filters');
        Ajax::end_container();
        echo "</li>";
    } ?>
  <li>
    <h4 class="header">
        <span class="sidebar-header-title"><?php echo T_('Catalogs'); ?></span>
        <?php echo Ui::get_material_symbol('chevron_right', $t_expander, 'admin_catalogs', 'header-img ' . ((isset($_COOKIE['sb_admin_catalogs'])) ? $_COOKIE['sb_admin_catalogs'] : 'expanded')); ?>
    </h4>
    <ul class="sb3" id="sb_admin_catalogs">
      <li id="sb_admin_catalogs_Add"><a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_add_catalog"><?php echo T_('Add Catalog'); ?></a></li>
      <li id="sb_admin_catalogs_Show"><a href="<?php echo $web_path; ?>/admin/catalog.php?action=show_catalogs"><?php echo T_('Show Catalogs'); ?></a></li>
      <li id="sb_admin_ot_ExportCatalog"><a href="<?php echo $web_path; ?>/admin/export.php"><?php echo T_('Export Catalog'); ?></a></li>
      <?php if (AmpConfig::get('catalog_filter')) { ?>
        <li id="sb_admin_filter_Add"><a href="<?php echo $web_path; ?>/admin/filter.php?action=show_add_filter"><?php echo T_('Add Catalog Filter'); ?></a></li>
        <li id="sb_admin_filter_Browse"><a href="<?php echo $web_path; ?>/admin/filter.php"><?php echo T_('Show Catalog Filters'); ?></a></li>
      <?php } ?>
      <?php if (AmpConfig::get('licensing')) { ?>
        <li id="sb_admin_ot_ManageLicense"><a href="<?php echo $web_path; ?>/admin/license.php"><?php echo T_('Manage Licenses'); ?></a></li>
      <?php } ?>
    </ul>
  </li>
  <?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::ADMIN)) { ?>
  <li>
      <h4 class="header">
          <span class="sidebar-header-title"><?php echo T_('User Tools'); ?></span>
          <?php echo Ui::get_material_symbol('chevron_right', $t_expander, 'admin_users', 'header-img ' . ((isset($_COOKIE['sb_admin_users'])) ? $_COOKIE['sb_admin_users'] : 'expanded')); ?>
      </h4>
      <ul class="sb3" id="sb_admin_users">
        <li id="sb_admin_users_AddUser"><a href="<?php echo $web_path; ?>/admin/users.php?action=show_add_user"><?php echo T_('Add User'); ?></a></li>
        <li id="sb_admin_users_BrowseUsers"><a href="<?php echo $web_path; ?>/admin/users.php"><?php echo T_('Browse Users'); ?></a></li>
        <?php if (Mailer::is_mail_enabled()) { ?>
          <li id="sb_admin_ot_Mail"><a href="<?php echo $web_path; ?>/admin/mail.php"><?php echo T_('E-mail Users'); ?></a></li>
        <?php
        }
      if (AmpConfig::get('allow_upload')) { ?>
            <li id="sb_admin_users_Uploads"><a href="<?php echo $web_path; ?>/admin/uploads.php"><?php echo T_('Browse Uploads'); ?></a></li>
        <?php
      }
      if (AmpConfig::get('sociable')) { ?>
          <li id="sb_admin_ot_ManageShoutbox"><a href="<?php echo $web_path; ?>/admin/shout.php"><?php echo T_('Manage Shoutbox'); ?></a></li>
        <?php } ?>
        <li id="sb_admin_ot_ClearNowPlaying"><a href="<?php echo $web_path; ?>/admin/catalog.php?action=clear_now_playing"><?php echo T_('Clear Now Playing'); ?></a></li>
      </ul>
  </li>
  <li>
    <h4 class="header">
      <span class="sidebar-header-title"><?php echo T_('Access Control'); ?></span>
      <?php echo Ui::get_material_symbol('chevron_right', $t_expander, 'admin_access', 'header-img ' . ((isset($_COOKIE['sb_admin_access'])) ? $_COOKIE['sb_admin_access'] : 'expanded')); ?>
    </h4>
    <ul class="sb3" id="sb_admin_access">
      <li id="sb_admin_access_AddAccess"><a href="<?php echo $web_path; ?>/admin/access.php?action=show_add_advanced"><?php echo T_('Add ACL'); ?></a></li>
      <li id="sb_admin_access_ShowAccess"><a href="<?php echo $web_path; ?>/admin/access.php"><?php echo T_('Show ACL(s)'); ?></a></li>
    </ul>
  </li>
    <li>
      <h4 class="header">
        <span class="sidebar-header-title"><?php echo T_('Modules'); ?></span>
        <?php echo Ui::get_material_symbol('chevron_right', $t_expander, 'admin_modules', 'header-img ' . ((isset($_COOKIE['sb_admin_modules'])) ? $_COOKIE['sb_admin_modules'] : 'expanded')); ?>
      </h4>
      <ul class="sb3" id="sb_admin_modules">
        <li id="sb_admin_modules_localplay"><a href="<?php echo $web_path; ?>/admin/modules.php?action=show_localplay"><?php echo T_('Localplay Controllers'); ?></a></li>
        <li id="sb_admin_modules_catalog_types"><a href="<?php echo $web_path; ?>/admin/modules.php?action=show_catalog_types"><?php echo T_('Catalog Types'); ?></a></li>
        <li id="sb_admin_modules_plugins"><a href="<?php echo $web_path; ?>/admin/modules.php?action=show_plugins"><?php echo T_('Manage Plugins'); ?></a></li>
      </ul>
    </li>
  <li>
    <h4 class="header">
      <span class="sidebar-header-title"><?php echo T_('Server Config'); ?></span>
      <?php echo Ui::get_material_symbol('chevron_right', $t_expander, 'admin_server', 'header-img ' . ((isset($_COOKIE['sb_admin_server'])) ? $_COOKIE['sb_admin_server'] : 'expanded')); ?>
    </h4>
    <ul class="sb3" id="sb_admin_server">
      <li id="sb_admin_ot_Debug"><a href="<?php echo $web_path; ?>/admin/system.php?action=show_debug"><?php echo T_('Ampache Debug'); ?></a></li>
    <?php $categories = Preference::get_categories();
      foreach ($categories as $name) {
          $f_name = ucfirst($name); ?>
      <li id="sb_admin_server_<?php echo $f_name; ?>"><a href="<?php echo $web_path; ?>/preferences.php?action=admin&amp;tab=<?php echo $name; ?>"><?php echo T_($f_name); ?></a></li>
    <?php
      } ?>
    </ul>
  </li>
<?php } ?>
</ul>
