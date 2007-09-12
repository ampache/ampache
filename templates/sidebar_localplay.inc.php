<ul class="sb2" id="sb_localplay">
  <li><h4><?php echo _('Localplay'); ?></h4>
    <ul class="sb3" id="sb_localplay_info">
	<li id="sb_localplay_info_add_instance"><a href="<?php echo $web_path; ?>/localplay.php?action=show_add_instance"><?php echo _('Add Instance'); ?></a></li>
    </ul>
  </li>
  <li><h4><?php echo _('Active Instance'); ?></h4>
    <ul class="sb3" id="sb_localplay_instances">
	<li id="sb_localplay_instances_none"><?php echo Ajax::text('?page=localplay&action=set_instance&instance=0',_('None'),'localplay_instance_none');  ?></li>
    </ul>
  </li>
</ul>
