<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2020 Ampache.org
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
 */

/* This one is a little dynamic as we add plugins or Localplay modules
 * they can have their own preference sections so we need to build the
 * links based on that, always ignore 'internal' though
 */
$categories = Preference::get_categories(); ?>
<?php
$t_preferences = T_('Preferences'); ?>
<ul class="sb2" id="sb_preferences">
  <li><h4 class="header"><span class="sidebar-header-title" title="<?php echo $t_preferences; ?>"><?php echo $t_preferences; ?></span><?php echo UI::get_icon('all', T_('Expand/Collapse'), 'preferences', 'header-all ' . ((filter_has_var(INPUT_COOKIE, 'sb_preferences')) ? $_COOKIE['sb_preferences'] : 'expanded')); ?></h4>
    <ul class="sb3" id="sb_preferences_sections">
<?php
    foreach ($categories as $name) {
        if ($name == 'system') {
            continue;
        }
        $f_name = ucfirst($name); ?>
      <li id="sb_preferences_sections_<?php echo $f_name; ?>"><a href="<?php echo $web_path; ?>/preferences.php?tab=<?php echo $name; ?>"><?php echo T_($f_name); ?></a></li>
<?php
    } ?>
      <li id="sb_preferences_sections_account"><a href="<?php echo $web_path; ?>/preferences.php?tab=account"><?php echo T_('Account'); ?></a></li>



    </ul>
  </li>
        <?php if (Access::check('interface', '50')) { ?>
    <li>
    <h4 class="header"><span class="sidebar-header-title" title="<?php echo T_('Playlist'); ?>"><?php echo T_('Playlist'); ?></span><img src="<?php echo AmpConfig::get('web_path') . AmpConfig::get('theme_path'); ?>/images/icons/icon_all.png" class="header-img <?php echo ($_COOKIE['sb_home_playlist'] == 'collapsed') ? 'collapsed' : 'expanded'; ?>" id="playlist" alt="<?php echo T_('Expand/Collapse'); ?>" title="<?php echo T_('Expand/Collapse'); ?>" /></h4>
    <ul class="sb3" id="sb_home_playlist">
<li id="sb_home_playlist_playlist"><a href="<?php echo $web_path ?>/playlist.php?action=show_import_playlist"><?php echo T_('Import') ?></a></li>
    </li>
</ul>    <?php
    } ?>
</ul>
