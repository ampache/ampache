<?php $ajax_info = Config::get('ajax_url'); ?>
<h4><?php echo _('Browse By'); ?></h4>
<form id="browse_type" name="browse_type" action="<?php echo Config::get('web_path'); ?>/browse.php" method="post">
<select name="action" onchange="document.getElementById('browse_type').submit();" >
	<option value="">-- <?php echo _('Type'); ?> --</option>
	<option value="song"><?php echo _('Song Title'); ?></option>
	<option value="album"><?php echo _('Albums'); ?></option>
	<option value="artist"><?php echo _('Artist'); ?></option>
	<option value="genre"><?php echo _('Genre'); ?></option>
</select>
</form>
<hr />
<h4><?php echo _('Filters'); ?></h4>
<?php show_alphabet_list($_REQUEST['alpha_match'],$_REQUEST['action']); ?>
<hr />
<input type="checkbox" onclick="ajaxPut('<?php echo $ajax_info; ?>?action=browse&amp;key=show_art&amp;value=1');return true;" value="1" />
	<?php echo _('Show Art'); ?><br />
<input type="checkbox" onclick="ajaxPut('<?php echo $ajax_info; ?>?action=browse&amp;key=min_count&amp;value=1');return true;" value="1" />
	<?php echo _('Minimum Count'); ?><br />
<input type="checkbox" onclick="ajaxPut('<?php echo $ajax_info; ?>?action=browse&amp;key=unplayed&amp;value=1');return true;" value="1" />
	<?php echo _('Unplayed'); ?><br />
<input type="checkbox" onclick="ajaxPut('<?php echo $ajax_info; ?>?action=browse&amp;key=rated&amp;value=1');return true;" value="1" />
	<?php echo _('Rated'); ?><br />
<hr />
