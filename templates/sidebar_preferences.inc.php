<?php 
/* This one is a little dynamic as we add plugins or localplay modules
 * they can have their own preference sections so we need to build the
 * links based on that, always ignore 'internal' though
 */
$catagories = Preference::get_catagories();  
?>
<ul class="sb2" id="sb_preferences">
  <li><h4><?php echo _('Preferences'); ?></h4>
    <ul class="sb3" id="sb_preferences_sections">
<?php 
	foreach ($catagories as $name) { 
		if ($name == 'system') { continue; } 
		$f_name = ucfirst($name); 
?>
      <li id="sb_preferences_sections_<?php echo $f_name; ?>"><a href="<?php echo $web_path; ?>/preferences.php?tab=<?php echo $name; ?>"><?php echo _($f_name); ?></a></li>
<?php } ?>
      <li id="sb_preferences_sections_account"><a href="<?php echo $web_path; ?>/preferences.php?tab=account"><?php echo _('Account'); ?></a></li>
    </ul>
  </li>
<?php if ($GLOBALS['user']->has_access('100')) { ?>
  <li><h4><?php echo _('Server Config'); ?></h4>
    <ul class="sb3" id="sb_preferences_sc">
<?php 
        foreach ($catagories as $name) { 
                $f_name = ucfirst($name);
?>
      <li id="sb_preferences_sc_<?php echo $f_name; ?>"><a href="<?php echo $web_path; ?>/preferences.php?action=admin&amp;tab=<?php echo $name; ?>"><?php echo _($f_name); ?></a></li>
<?php } ?>
    </ul>
  </li>
</ul>
<?php } ?>
