<?php $ajax_info = Config::get('ajax_url'); $web_path = Config::get('web_path'); ?>
<h4><?php echo _('Browse By'); ?></h4>
<?php 
	// Build the selected dealie
	$text = scrub_in($_REQUEST['action']) . '_ac';
	${$text} = ' selected="selected"'; 
?>
<ul id="sb_BrowseBy">
<li id="sb_BB_SongTitle"><a href="<?php echo $web_path; ?>/browse.php?action=song"><?php echo _('Song Title'); ?></a></li>
<li id="sb_BB_Album"><a href="<?php echo $web_path; ?>/browse.php?action=album"><?php echo _('Albums'); ?></a></li>
<li id="sb_BB_Artist"><a href="<?php echo $web_path; ?>/browse.php?action=artist"><?php echo _('Artist'); ?></a></li>
<li id="sb_BB_Genre"><a href="<?php echo $web_path; ?>/browse.php?action=genre"><?php echo _('Genre'); ?></a></li>
<li id="sb_BB_Playlist"><a href="<?php echo $web_path; ?>/browse.php?action=playlist"><?php echo _('Playlist'); ?></a></li>
<li id="sb_BB_RadioStation"><a href="<?php echo $web_path; ?>/browse.php?action=live_stream"><?php echo _('Radio Stations'); ?></a></li>
</ul>
<hr />
<h4><?php echo _('Filters'); ?></h4>
<?php show_alphabet_list($_REQUEST['alpha_match'],$_REQUEST['action']); ?>
<hr />
<!--
<input type="checkbox" onclick="ajaxPut('<?php echo $ajax_info; ?>?action=browse&amp;key=show_art&amp;value=1');return true;" value="1" />
	<?php echo _('Show Art'); ?><br />
<input type="checkbox" onclick="ajaxPut('<?php echo $ajax_info; ?>?action=browse&amp;key=min_count&amp;value=1');return true;" value="1" />
	<?php echo _('Minimum Count'); ?><br />
<input type="checkbox" onclick="ajaxPut('<?php echo $ajax_info; ?>?action=browse&amp;key=rated&amp;value=1');return true;" value="1" />
	<?php echo _('Rated'); ?><br />
-->
<input id="unplayedCB" type="checkbox" onclick="ajaxPut('<?php echo $ajax_info; ?>?action=browse&amp;key=unplayed&amp;value=1');return true;" value="1" />
	<label id="unplayedLabel" for="unplayedCB"><?php echo _('Unplayed'); ?></label><br />
<hr />
