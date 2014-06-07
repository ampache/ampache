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
<ul class="sb2" id="sb_modules">
<li><h4 class="header"><?php echo T_('Modules'); ?><span class="sprite sprite-icon_all <?php echo isset($_COOKIE['sb_modules']) ? $_COOKIE['sb_modules'] : 'expanded'; ?>" id="modules" alt="<?php echo T_('Expand/Collapse'); ?>" title="<?php echo T_('Expand/Collapse'); ?>"></span></h4>
        <ul class="sb3" id="sb_Modules">
        <li id="sb_preferences_mo_localplay"><?php echo Ajax::text(Ajax::make_url('content', 'modules', 'show_localplay'), T_('Localplay Modules'), 'modules_show_localplay'); ?></li>
        <li id="sb_preferences_mo_catalog_types"><?php echo Ajax::text(Ajax::make_url('content', 'modules', 'show_catalog_types'), T_('Catalog Modules'), 'modules_show_catalog'); ?></li>
        <li id="sb_preferences_mo_plugins"><?php echo Ajax::text(Ajax::make_url('content', 'modules', 'show_plugins'), T_('Available Plugins'), 'modules_show_plugins'); ?></li>
        </ul>
</li>
  <li><h4 class="header"><?php echo T_('Other Tools'); ?><span class="sprite sprite-icon_all <?php echo isset($_COOKIE['sb_md_other_tools']) ? $_COOKIE['sb_md_other_tools'] : 'expanded'; ?>" id="md_other_tools" alt="<?php echo T_('Expand/Collapse'); ?>" title="<?php echo T_('Expand/Collapse'); ?>"></span></h4>
    <ul class="sb3" id="sb_admin_ot">
      <li id="sb_admin_ot_Duplicates"><?php echo Ajax::text(Ajax::make_url('content', 'duplicates'), T_('Find Duplicates'), 'modules_duplicates_'); ?></li>
      <li id="sb_admin_ot_Mail"><?php echo Ajax::text(Ajax::make_url('content', 'mail'), T_('Mail Users'), 'modules_mail'); ?></li>
    </ul>
  </li>
<!--
<?php if (AmpConfig::get('allow_democratic_playback')) { ?>
  <li><h4><?php echo T_('Democratic'); ?></h4>
    <ul class="sb3" id="sb_home_democratic">
      <li id="sb_home_democratic_playlist"><a href="<?php echo $web_path; ?>/democratic.php?action=manage_playlists"><?php echo T_('Manage Playlist'); ?></a></li>
    </ul>
  </li>
<?php } ?>
-->
</ul>
