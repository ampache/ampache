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

<ul class="sb2" id="sb_localplay">
<?php if ($server_allow = Config::get('allow_localplay_playback') AND $controller = Config::get('localplay_controller') AND $access_check = Access::check('localplay','5')) { ?>
<?php
	// Little bit of work to be done here
	$localplay = new Localplay(Config::get('localplay_controller')); 
	$current_instance = $localplay->current_instance(); 
	$class = $current_instance ? '' : ' class="active_instance"';
?>
<?php if (Access::check('localplay','25')) { ?>
  <li><h4><?php echo _('Localplay'); ?></h4>
    <ul class="sb3" id="sb_localplay_info">
<?php if (Access::check('localplay','75')) { ?>
	<li id="sb_localplay_info_add_instance"><a href="<?php echo $web_path; ?>/localplay.php?action=show_add_instance"><?php echo _('Add Instance'); ?></a></li>
	<li id="sb_localplay_info_show_instances"><a href="<?php echo $web_path; ?>/localplay.php?action=show_instances"><?php echo _('Show instances'); ?></a></li>
<?php } ?>
	<li id="sb_localplay_info_show"><a href="<?php echo $web_path; ?>/localplay.php?action=show_playlist"><?php echo _('Show Playlist'); ?></a></li>
    </ul>
  </li>
<?php } ?>
  <li><h4><?php echo _('Active Instance'); ?></h4>
    <ul class="sb3" id="sb_localplay_instances">
	<li id="sb_localplay_instances_none"<?php echo $class; ?>><?php echo Ajax::text('?page=localplay&action=set_instance&instance=0',_('None'),'localplay_instance_none');  ?></li>
	<?php 
		// Requires a little work.. :(
		$instances = $localplay->get_instances(); 
		foreach ($instances as $uid=>$name) { 
			$name = scrub_out($name); 
			$class = '';
			if ($uid == $current_instance) { 
				$class = ' class="active_instance"'; 
			} 
	?>
	<li id="sb_localplay_instances_<?php echo $uid; ?>"<?php echo $class; ?>><?php echo Ajax::text('?page=localplay&action=set_instance&instance=' . $uid,$name,'localplay_instance_' . $uid); ?></li>
	<?php } ?>
    </ul>
  </li>
<?php } else { ?>
  <li><h4><?php echo _('Localplay Disabled'); ?></h4></li>
  <?php if (!$server_allow) { ?>
	<li><?php echo _('Allow Localplay set to False'); ?></li>
  <?php } elseif (!$controller) { ?>
	<li><?php echo _('Localplay Controller Not Defined'); ?></li>
  <?php } elseif (!$access_check) { ?>
	<li><?php echo _('Access Denied'); ?></li>
  <?php } ?>
<?php } ?>
</ul>
