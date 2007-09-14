<ul class="sb2" id="sb_localplay">
<?php if (Config::get('allow_localplay_playback')) { ?>
<?php if ($GLOBALS['user']->has_access('50')) { ?>
  <li><h4><?php echo _('Localplay'); ?></h4>
    <ul class="sb3" id="sb_localplay_info">
	<li id="sb_localplay_info_add_instance"><a href="<?php echo $web_path; ?>/localplay.php?action=show_add_instance"><?php echo _('Add Instance'); ?></a></li>
    </ul>
  </li>
<?php } ?>
  <li><h4><?php echo _('Active Instance'); ?></h4>
    <ul class="sb3" id="sb_localplay_instances">
	<li id="sb_localplay_instances_none"><?php echo Ajax::text('?page=localplay&action=set_instance&instance=0',_('None'),'localplay_instance_none');  ?></li>
	<?php 
		// Requires a little work.. :(
		$localplay = new Localplay($GLOBALS['user']->prefs['localplay_controller']); 
		$instances = $localplay->get_instances(); 
		foreach ($instances as $uid=>$name) { 
	?>
	<li id="sb_localplay_instances_<?php echo $uid; ?>"><?php echo Ajax::text('?page=localplay&action=set_instance&instance=' . $uid,$name,'localplay_instance_' . $uid); ?></li>
	<?php } ?>
    </ul>
  </li>
<?php } else { ?>
  <li><h4><?php echo _('Localplay Disabled'); ?></h4></li>
<?php } ?>
</ul>
