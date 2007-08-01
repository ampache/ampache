<h4><?php echo _('Sections'); ?></h4>
<ul id="sb_Preferences">
<li id="sb_Pref_Interface"><a href="<?php echo $web_path; ?>/preferences.php?tab=interface"><?php echo _('Interface'); ?></a></li>
<li id="sb_Pref_Playlist"><a href="<?php echo $web_path; ?>/preferences.php?tab=playlist"><?php echo _('Playlist'); ?></a></li>
<li id="sb_Pref_Streaming"><a href="<?php echo $web_path; ?>/preferences.php?tab=streaming"><?php echo _('Streaming'); ?></a></li>
<li id="sb_Pref_Options"><a href="<?php echo $web_path; ?>/preferences.php?tab=options"><?php echo _('Options'); ?></a></li>
<li id="sb_Pref_Account"><a href="<?php echo $web_path; ?>/preferences.php?tab=account"><?php echo _('Account'); ?></a></li>
</ul>
<hr />
<?php if ($GLOBALS['user']->has_access('100')) { ?>
<h4><?php echo _('Server Config'); ?></h4>
<ul id="sb_ServerConfig">
<li id="sb_SC_Interface"><a href="<?php echo $web_path; ?>/preferences.php?action=admin&amp;tab=interface"><?php echo _('Interface'); ?></a></li>
<li id="sb_SC_Playlist"><a href="<?php echo $web_path; ?>/preferences.php?action=admin&amp;tab=playlist"><?php echo _('Playlist'); ?></a></li>
<li id="sb_SC_Streaming"><a href="<?php echo $web_path; ?>/preferences.php?action=admin&amp;tab=streaming"><?php echo _('Streaming'); ?></a></li>
<li id="sb_SC_Options"><a href="<?php echo $web_path; ?>/preferences.php?action=admin&amp;tab=options"><?php echo _('Options'); ?></a></li>
<li id="sb_SC_System"><a href="<?php echo $web_path; ?>/preferences.php?action=admin&amp;tab=system"><?php echo _('System'); ?></a></li>
</ul>
<hr />
<?php } ?>
