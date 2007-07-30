<h4><?php echo _('Information'); ?></h4>
<span><a href="<?php echo $web_path; ?>/index.php"><?php echo _('Currently Playing'); ?></a></span>
<span><a href="<?php echo $web_path; ?>/stats.php"><?php echo _('Statistics'); ?></a></span>
<span><a href="<?php echo $web_path; ?>/radio.php?action=show_create"><?php echo _('Add Radio Station'); ?></a></span>
<hr />
<!-- RANDOM, Hidden for now cause its broken
<h4><?php echo _('Search'); ?></h4>
<div id="sidebar_subsearch">
	<form name="sub_search" method="post" action="<?php echo $web_path; ?>/search.php" enctype="multipart/form-data" style="Display:inline">
        <input type="text" name="search_string" value="" size="5" />
<br />
        <input class="smallbutton" type="submit" value="<?php echo _('Search'); ?>" />
        <input type="hidden" name="action" value="quick_search" />
        <input type="hidden" name="method" value="fuzzy" />
        <input type="hidden" name="object_type" value="song" />
        </form>
</div>
<hr />
<h4><?php echo _('Random'); ?></h4>
	<form name="sub_random" method="post" enctype="multipart/form-data" action="<?php echo $web_path; ?>/song.php?action=random&amp;method=stream" style="Display:inline">
        <select name="random" style="width:80px;">
        	<option value="1">1</option>
                <option value="5" selected="selected">5</option>
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="30">30</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="500">500</option>
                <option value="1000">1000</option>
                <option value="-1"><?php echo _('All'); ?></option>
	</select>
<!-- GENRE PULLDOWN -->
<br />
	<select name="random_type" style="width:80px;">
        	<option value="Songs"><?php echo _('Songs'); ?></option>
                <option value="length"><?php echo _('Minutes'); ?></option>
                <option value="full_artist"><?php echo _('Artists'); ?></option>
                <option value="full_album"><?php echo _('Albums'); ?></option>
                <option value="unplayed"><?php echo _('Less Played'); ?></option>
	</select>
        <br />
<!-- CATALOG PULLDOWN -->
        <input class="smallbutton" type="submit" value="<?php echo _('Enqueue'); ?>" />
	</form>
<hr />
-->
<h4><?php echo _('Playlists'); ?></h4>
<span><a href="<?php echo $web_path; ?>/playlist.php?action=show_all"><?php echo _('View All'); ?></a></span>
<hr />
<div style="left-padding:5px;">
<?php 
	$playlists = Playlist::get_users($GLOBALS['user']->id); 
	foreach ($playlists as $playlist_id) { 
		$playlist = new Playlist($playlist_id); 
		$playlist->format(); 
?>
<span>
	<?php echo Ajax::button('?action=basket&type=playlist&id=' . $playlist_id,'all',_('Play This Playlist'),'leftbar_playlist_' . $playlist_id); ?>
	<?php echo $playlist->f_link; ?>
</span>
<?php } // end foreach playlist ?>
</div>
