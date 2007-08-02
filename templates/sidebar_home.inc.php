<h4><?php echo _('Information'); ?></h4>
<ul id="sb_Information">
<li id="sb_Info_CurrentlyPlaying"><a href="<?php echo $web_path; ?>/index.php"><?php echo _('Currently Playing'); ?></a></li>
<li id="sb_Info_Statistics"><a href="<?php echo $web_path; ?>/stats.php"><?php echo _('Statistics'); ?></a></li>
<li id="sb_Info_AddStationRadio"><a href="<?php echo $web_path; ?>/radio.php?action=show_create"><?php echo _('Add Radio Station'); ?></a></li>
</ul>
<hr />
<?php /*
<!-- RANDOM, Hidden for now cause its broken
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
<!- GENRE PULLDOWN ->
<br />
	<select name="random_type" style="width:80px;">
        	<option value="Songs"><?php echo _('Songs'); ?></option>
                <option value="length"><?php echo _('Minutes'); ?></option>
                <option value="full_artist"><?php echo _('Artists'); ?></option>
                <option value="full_album"><?php echo _('Albums'); ?></option>
                <option value="unplayed"><?php echo _('Less Played'); ?></option>
	</select>
        <br />
<!- CATALOG PULLDOWN ->
        <input class="smallbutton" type="submit" value="<?php echo _('Enqueue'); ?>" />
	</form>
<hr />
--> */ ?>
<h4><?php echo _('Playlists'); ?></h4>
<a id="sb_ViewAll" href="<?php echo $web_path; ?>/playlist.php?action=show_all"><?php echo _('View All'); ?></a>
<hr />
