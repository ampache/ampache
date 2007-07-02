<h4><?php echo _('Sections'); ?></h4>
<span><a href="<?php echo $web_path; ?>/preferences.php?tab=interface"><?php echo _('Interface'); ?></a></span>
<span><a href="<?php echo $web_path; ?>/preferences.php?tab=playlist"><?php echo _('Playlist'); ?></a></span>
<span><a href="<?php echo $web_path; ?>/preferences.php?tab=streaming"><?php echo _('Streaming'); ?></a></span>
<span><a href="<?php echo $web_path; ?>/preferences.php?tab=options"><?php echo _('Options'); ?></a></span>
<span><a href="<?php echo $web_path; ?>/preferences.php?tab=account"><?php echo _('Account'); ?></a></span>
<hr />
<?php if ($GLOBALS['user']->has_access('100')) { ?>
<h4><?php echo _('Server Config'); ?></h4>
<span><a href="<?php echo $web_path; ?>/preferences.php?action=admin&amp;tab=interface"><?php echo _('Interface'); ?></a></span>
<span><a href="<?php echo $web_path; ?>/preferences.php?action=admin&amp;tab=playlist"><?php echo _('Playlist'); ?></a></span>
<span><a href="<?php echo $web_path; ?>/preferences.php?action=admin&amp;tab=streaming"><?php echo _('Streaming'); ?></a></span>
<span><a href="<?php echo $web_path; ?>/preferences.php?action=admin&amp;tab=options"><?php echo _('Options'); ?></a></span>
<span><a href="<?php echo $web_path; ?>/preferences.php?action=admin&amp;tab=system"><?php echo _('System'); ?></a></span>
<hr />
<?php } ?>
