<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
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

/* This one is a little dynamic as we add plugins or Localplay modules
 * they can have their own preference sections so we need to build the
 * links based on that, always ignore 'internal' though
 */

use Ampache\Config\AmpConfig;
use Ampache\Module\System\Core;
use Ampache\Module\Util\Upload;
use Ampache\Repository\Model\Preference;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Util\Ui;

/** @var string $web_path */
/** @var string $t_browse */
/** @var string $t_expander */
/** @var string $t_preferences */
/** @var string $t_playlist */
/** @var string $t_uploads */
/** @var string $t_upload */

$access50     = Access::check('interface', 50);
$access25     = ($access50 || Access::check('interface', 25));
$categories   = Preference::get_categories();
$current_user = $current_user ?? Core::get_global('user');
$allow_upload = $allow_upload ?? $access25 && Upload::can_upload($current_user); ?>
<ul class="sb2" id="sb_preferences">
    <?php if (AmpConfig::get('browse_filter')) {
        echo "<li>";
        Ajax::start_container('browse_filters');
        Ajax::end_container();
        echo "</li>";
    } ?>
  <li>
    <h4 class="header">
        <span class="sidebar-header-title"><?php echo $t_preferences; ?></span>
        <?php echo Ui::get_icon('all', $t_expander, 'preference_prefs', 'header-img ' . ((isset($_COOKIE['sb_preference_prefs'])) ? $_COOKIE['sb_preference_prefs'] : 'expanded')); ?>
    </h4>
    <ul class="sb3" id="sb_preference_prefs">
<?php foreach ($categories as $name) {
    if ($name == 'system') {
        continue;
    }
    $f_name = ucfirst($name); ?>
      <li id="sb_preference_prefs_<?php echo $f_name; ?>"><a href="<?php echo $web_path; ?>/preferences.php?tab=<?php echo $name; ?>"><?php echo T_($f_name); ?></a></li>
<?php
} ?>
      <li id="sb_preference_prefs_account"><a href="<?php echo $web_path; ?>/preferences.php?tab=account"><?php echo T_('Account'); ?></a></li>
    </ul>
  </li>
<?php if ($access50) { ?>
  <li>
    <h4 class="header">
        <span class="sidebar-header-title"><?php echo $t_playlist; ?></span>
        <?php echo Ui::get_icon('all', $t_expander, 'preference_playlist', 'header-img ' . ((isset($_COOKIE['sb_preference_playlist'])) ? $_COOKIE['sb_preference_playlist'] : 'expanded')); ?>
    </h4>
    <ul class="sb3" id="sb_preference_playlist">
      <li id="sb_preference_prefs_playlist_import"><a href="<?php echo $web_path; ?>/playlist.php?action=show_import_playlist"><?php echo T_('Import'); ?></a></li>
    </ul>
  </li>
<?php }
if ($allow_upload) { ?>
    <li>
    <h4 class="header">
        <span class="sidebar-header-title"><?php echo $t_uploads; ?></span>
        <?php echo Ui::get_icon('all', $t_expander, 'preference_upload', 'header-img ' . ((isset($_COOKIE['sb_preference_upload'])) ? $_COOKIE['sb_preference_upload'] : 'expanded')); ?>
    </h4>
      <ul class="sb3" id="sb_preference_upload">
        <li id="sb_preference_upload_browse"><a href="<?php echo $web_path; ?>/stats.php?action=upload"><?php echo $t_browse; ?></a></li>
        <li id="sb_preference_upload_upload"><a href="<?php echo $web_path; ?>/upload.php"><?php echo $t_upload; ?></a></li>
      </ul>
    </li>
<?php }
if (!AmpConfig::get('simple_user_mode')) { ?>
    <li>
      <h4 class="header">
        <span class="sidebar-header-title"><?php echo T_('Help'); ?></span>
        <?php echo Ui::get_icon('all', $t_expander, 'preference_help', 'header-img ' . ((isset($_COOKIE['sb_preference_help'])) ? $_COOKIE['sb_preference_help'] : 'expanded')); ?>
      </h4>
      <ul class="sb3" id="sb_preference_help">
        <li id="sb_preference_help_wiki"><a href="https://github.com/ampache/ampache/wiki" target=\"_blank\"><?php echo T_('Ampache Wiki'); ?></a></li>
        <li id="sb_preference_help_api"><a href="https://ampache.org/api/" target=\"_blank\"><?php echo T_('API Documentation'); ?></a></li>
        <?php if (AmpConfig::get('cookie_disclaimer')) { ?>
            <li id="sb_preference_help_cookies"><a href="<?php echo $web_path; ?>/cookie_disclaimer.php"><?php echo T_('Cookie Information'); ?></a></li>
        <?php } ?>
      </ul>
    </li>
<?php } ?>
</ul>
