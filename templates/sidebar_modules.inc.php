<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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
<ul class="sb2" id="sb_modules">
<li><h4><?php echo T_('Modules'); ?></h4>
        <ul class="sb3" id="sb_Modules">
        <li id="sb_preferences_mo_localplay"><a href="<?php echo $web_path; ?>/admin/modules.php?action=show_localplay"><?php echo T_('Localplay Modules'); ?></a></li>
        <li id="sb_preferences_mo_plugins"><a href="<?php echo $web_path; ?>/admin/modules.php?action=show_plugins"><?php echo T_('Available Plugins'); ?></a></li>
        </ul>
</li>
  <li><h4><?php echo T_('Other Tools'); ?></h4>
    <ul class="sb3" id="sb_admin_ot">
      <li id="sb_admin_ot_Duplicates"><a href="<?php echo $web_path; ?>/admin/duplicates.php"><?php echo T_('Find Duplicates'); ?></a></li>
      <li id="sb_admin_ot_Mail"><a href="<?php echo $web_path; ?>/admin/mail.php"><?php echo T_('Mail Users'); ?></a></li>
      <li id="sb_admin_ot_ManageFlagged"><a href="<?php echo $web_path; ?>/admin/flag.php"><?php echo T_('Manage Flagged'); ?></a></li>
      <li id="sb_admin_ot_ShowDisabled"><a href="<?php echo $web_path; ?>/admin/flag.php?action=show_disabled"><?php echo T_('Show Disabled'); ?></a></li>
    </ul>
  </li>
<!--
<?php if (Config::get('allow_democratic_playback')) { ?>
  <li><h4><?php echo T_('Democratic'); ?></h4>
    <ul class="sb3" id="sb_home_democratic">
      <li id="sb_home_democratic_playlist"><a href="<?php echo $web_path; ?>/democratic.php?action=manage_playlists"><?php echo T_('Manage Playlist'); ?></a></li>
    </ul>
  </li>
<?php } ?>
-->
</ul>
