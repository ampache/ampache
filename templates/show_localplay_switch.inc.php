<span <?php echo $stream; ?> onclick="ajaxPut('<?php echo $ajax_url; ?>?action=change_play_type&amp;type=stream<?php echo $required_info; ?>','play_type');return true;">
	<?php echo _('Stream') ?>
</span><br /><br />
<span <?php echo $localplay; ?> onclick="ajaxPut('<?php echo $ajax_url; ?>?action=change_play_type&amp;type=localplay<?php echo $required_info; ?>','play_type');return true;">
	<?php echo _('Localplay'); ?>
</span>
