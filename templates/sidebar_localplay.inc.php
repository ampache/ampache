<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */ ?>

<ul class="sb2" id="sb_localplay">
<?php
$server_allow = AmpConfig::get('allow_localplay_playback');
$controller   = AmpConfig::get('localplay_controller');
$access_check = Access::check('localplay', 5);
if ($server_allow && $controller && $access_check) { ?>
<?php
    // Little bit of work to be done here
    $localplay        = new Localplay(AmpConfig::get('localplay_controller'));
    $current_instance = $localplay->current_instance();
    $class            = $current_instance ? '' : ' class="active_instance"'; ?>
<?php if (Access::check('localplay', 25)) { ?>
    <?php if (AmpConfig::get('browse_filter')) {
        echo "<li>";
        Ajax::start_container('browse_filters');
        Ajax::end_container();
        echo "</li>";
    } ?>
  <li><h4 class="header"><span class="sidebar-header-title"><?php echo T_('Localplay'); ?></span><?php echo UI::get_icon('all', T_('Expand/Collapse'), 'localplay', 'header-img ' . ((filter_has_var(INPUT_COOKIE, 'sb_localplay')) ? $_COOKIE['sb_localplay'] : 'expanded')); ?></h4>
    <ul class="sb3" id="sb_localplay_info">
<?php if (Access::check('localplay', 75)) { ?>
    <li id="sb_localplay_info_add_instance"><a href="<?php echo $web_path; ?>/localplay.php?action=show_add_instance"><?php echo T_('Add Instance'); ?></a></li>
    <li id="sb_localplay_info_show_instances"><a href="<?php echo $web_path; ?>/localplay.php?action=show_instances"><?php echo T_('Show Instances'); ?></a></li>
<?php
        } ?>
    <li id="sb_localplay_info_show"><a href="<?php echo $web_path; ?>/localplay.php?action=show_playlist"><?php echo T_('Show Playlist'); ?></a></li>
    </ul>
  </li>
<?php
    } ?>
  <li><h4 class="header"><span class="sidebar-header-title"><?php echo T_('Active Instance'); ?></span><?php echo UI::get_icon('all', T_('Expand/Collapse'), 'active_instance', 'header-img ' . ((filter_has_var(INPUT_COOKIE, 'sb_active_instance')) ? $_COOKIE['sb_active_instance'] : 'expanded')); ?></h4>
    <ul class="sb3" id="sb_localplay_instances">
    <li id="sb_localplay_instances_none"<?php echo $class; ?>><?php echo Ajax::text('?page=localplay&action=set_instance&instance=0', T_('None'), 'localplay_instance_none'); ?></li>
    <?php
        // Requires a little work.. :(
        $instances = $localplay->get_instances();
    foreach ($instances as $uid => $name) {
        $name  = scrub_out($name);
        $class = '';
        if ($uid == $current_instance) {
            $class = ' class="active_instance"';
        } ?>
    <li id="sb_localplay_instances_<?php echo $uid; ?>"<?php echo $class; ?>><?php echo Ajax::text('?page=localplay&action=set_instance&instance=' . $uid, $name, 'localplay_instance_' . $uid); ?></li>
    <?php
    } ?>
    </ul>
  </li>
<?php
} else { ?>
  <li><h4 class="header"><span class="sidebar-header-title"><?php echo T_('Localplay Disabled'); ?></span><?php echo UI::get_icon('all', T_('Expand/Collapse'), 'localplay_disabled', 'header-img ' . ((filter_has_var(INPUT_COOKIE, 'sb_localplay_disabled')) ? $_COOKIE['sb_localplay_disabled'] : 'expanded')); ?></h4></li>
  <?php if (!$server_allow) { ?>
    <li><?php echo T_('Allow Localplay Set to False'); ?></li>
  <?php
        } elseif (!$controller) { ?>
    <li><?php echo T_('Localplay Controller Not Defined'); ?></li>
  <?php
        } elseif (!$access_check) { ?>
    <li><?php echo T_('Access Denied'); ?></li>
  <?php
        } ?>
<?php
    } ?>
</ul>
