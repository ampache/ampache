<ul class="sb2" id="sb_preferences">
  <li><?php echo _('Sections'); ?>
    <ul class="sb3" id="sb_preferences_sections">
      <li id="sb_preferences_sections_Interface"><a href="<?php echo $web_path; ?>/preferences.php?tab=interface"><?php echo _('Interface'); ?></a></li>
      <li id="sb_preferences_sections_Playlist"><a href="<?php echo $web_path; ?>/preferences.php?tab=playlist"><?php echo _('Playlist'); ?></a></li>
      <li id="sb_preferences_sections_Streaming"><a href="<?php echo $web_path; ?>/preferences.php?tab=streaming"><?php echo _('Streaming'); ?></a></li>
      <li id="sb_preferences_sections_Options"><a href="<?php echo $web_path; ?>/preferences.php?tab=options"><?php echo _('Options'); ?></a></li>
      <li id="sb_preferences_sections_Account"><a href="<?php echo $web_path; ?>/preferences.php?tab=account"><?php echo _('Account'); ?></a></li>
    </ul>
  </li>
<?php if ($GLOBALS['user']->has_access('100')) { ?>
  <li><?php echo _('Server Config'); ?>
    <ul class="sb3" id="sb_preferences_sc">
      <li id="sb_preferences_sc_Interface"><a href="<?php echo $web_path; ?>/preferences.php?action=admin&amp;tab=interface"><?php echo _('Interface'); ?></a></li>
      <li id="sb_preferences_sc_Playlist"><a href="<?php echo $web_path; ?>/preferences.php?action=admin&amp;tab=playlist"><?php echo _('Playlist'); ?></a></li>
      <li id="sb_preferences_sc_Streaming"><a href="<?php echo $web_path; ?>/preferences.php?action=admin&amp;tab=streaming"><?php echo _('Streaming'); ?></a></li>
      <li id="sb_preferences_sc_Options"><a href="<?php echo $web_path; ?>/preferences.php?action=admin&amp;tab=options"><?php echo _('Options'); ?></a></li>
      <li id="sb_preferences_sc_System"><a href="<?php echo $web_path; ?>/preferences.php?action=admin&amp;tab=system"><?php echo _('System'); ?></a></li>
    </ul>
  </li>
<li><?php echo _('Modules'); ?>
	<ul class="sb3" id="sb_Modules">
	<li id="sb_preferences_mo_localplay"><a href="<?php echo $web_path; ?>/admin/modules.php?action=show_localplay"><?php echo _('Localplay Modules'); ?></a></li>
	<li id="sb_preferences_mo_plugins"><a href="<?php echo $web_path; ?>/admin/modules.php?action=show_plugins"><?php echo _('Available Plugins'); ?></a></li>
	</ul>
</li>
</ul>
<?php } ?>
