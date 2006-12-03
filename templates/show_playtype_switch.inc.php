<?php 
$name = "is_" . $GLOBALS['user']->prefs['play_type'];
${$name} = 'selected="selected" ';
?>
<select style="font-size:0.9em;" name="type"> 
	<?php if (conf('allow_stream_playback')) { ?>
		<option onclick="ajaxPut('<?php echo $ajax_url; ?>?action=change_play_type&amp;type=stream&amp;playlist_type=m3u<?php echo $required_info; ?>');return true;" <?php echo $is_stream; ?>><?php echo _('Stream'); ?></option>
	<?php } if (conf('allow_localplay_playback')) { ?>
		<option onclick="ajaxPut('<?php echo $ajax_url; ?>?action=change_play_type&amp;type=localplay<?php echo $required_info; ?>');return true;" <?php echo $is_localplay; ?>><?php echo _('Localplay'); ?></option>
	<?php } if (conf('allow_downsample_playback')) { ?>
		<option onclick="ajaxPut('<?php echo $ajax_url; ?>?action=change_play_type&amp;type=downsample<?php echo $required_info; ?>');return true;" <?php echo $is_downsample; ?>><?php echo _('Downsample'); ?></option>
	<?php } if (conf('allow_democratic_playback')) { ?>
	<option onclick="ajaxPut('<?php echo $ajax_url; ?>?action=change_play_type&amp;type=democratic<?php echo $required_info; ?>');return true;" <?php echo $is_democratic; ?>><?php echo _('Democratic'); ?></option>
	<?php } ?>
	<option onclick="ajaxPut('<?php echo $ajax_url; ?>?action=change_play_type&amp;type=xspf_player&amp;playlist_type=xspf<?php echo $required_info; ?>');return true;" <?php echo $is_xspf_player; ?>><?php echo _('XSPF Player'); ?></option>
</select>
