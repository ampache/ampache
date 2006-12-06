<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation. 

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
$web_path = conf('web_path');
?>
<?php show_box_top(_('Similar Artists')); ?>
<form name="artists" method="post" enctype="multipart/form-data" action="<?php echo $web_path; ?>/artists.php?action=rename_similar&artist=<?php echo $artist_id;?>" style="Display:inline;">
	<h3><?php _('Please check the artists you want to merge with the current one'); ?> (<span style='text-decoration: underline;'><?php echo $artist_name;?></span>)</h3>
	<table class="tabledata" cellspacing="0" cellpadding="0" border="0">
	<tr class="table-header">
		<th>&nbsp;&nbsp;Select</th>
		<th><?php echo _("Artist"); ?></th>
	</tr>
<?php
if (count($similar_artists) > 0) {
	$show = true;
	foreach ($similar_artists as $artist_array) {
?>
	<tr class="<?php echo flip_class(); ?>">
		<td align="center">
			<input type="checkbox" name="artists[]" value="<?php echo $artist_array[0]; ?>" />
		</td>
		<td>
			<a href="<?php echo $web_path; ?>/artists.php?action=show&amp;artist=<?php echo $artist_array[0]; ?>" title="<?php echo htmlspecialchars($artist_array[1]); ?>" <?php echo $text_class; ?>><?php echo htmlspecialchars($artist_array[1]); ?></a>
		</td>
	</tr>
<?php
	}// foreach loop
} else { // no similar artists
	$show = false;
?>
	<tr class="<?php echo flip_class(); ?>">
		<td align="center">
			&nbsp;
		</td>
		<td>
			No Similar Artists found
		</td>
	</tr>
	<tr class="<?php echo flip_class(); ?>">
		<td align="center">
			&nbsp;
		</td>
		<td>
			<input class="button" type="button" name="action" value="<?php echo _("Back"); ?>" onclick="window.location.href = '<?php echo $web_path; ?>/artists.php?action=show&artist=<?php echo $artist_id;?>';" />
		</td>
	</tr>		
<?php
}
?>
<?php
if ($show) {
?>
<tr align="left" class="<?php echo flip_class(); ?>">
	<td colspan='2'>
		<input class="button" type="button" name="action" value="<?php echo _("Rename selected"); ?>" onclick="document.artists.submit();" />
		<input class="button" type="button" name="action" value="<?php echo _("Cancel"); ?>" onclick="window.location.href = '<?php echo $web_path; ?>/artists.php?action=show&artist=<?php echo $artist_id;?>';" />
	</td>
</tr>
<?php
}
?>
</table>
</form>
<?php show_box_bottom(); ?>
<?php show_box_top(_('Advanced Options')); ?>
<form name='advanced' action='<?php echo $web_path; ?>/artists.php?action=show_similar&artist=<?php echo $artist_id; ?>' method='POST'>
<table class="border" cellspacing="0" cellpadding="0" border="0" style='margin-left: 10px; margin-top: 20px;'>
<!--Advanced-->
	<tr class="<?php echo flip_class(); ?>">
		<th colspan='2' style='text-align: left;'>Normalize options</th>
	</tr>
	<tr class="<?php echo flip_class(); ?>">
		<td colspan='2'>
			<label for='n_rep_uml' style='display: inline;'>Replace ümlauts and áccents with normal letters</label>
			<input type='checkbox' name='n_rep_uml' id='n_rep_uml' value='yes' style='display: inline;' <?php echo (is_null($_POST['n_rep_uml']) ||  make_bool($_POST['n_rep_uml']) ? "checked='checked'" : ''); ?>/>
			<br/>
			<label for='n_filter' style='display: inline;'>filter out (regexp)</label>
			<input type='text' name='n_filter' id='n_filter' value='<?php echo (is_null($_POST['n_filter']) ? "/[^a-z ]/" : $_POST['n_filter']); ?>' style='display: inline;'/>
			<a href='javascript:default_filter();'>default</a>
			<br/>
			<label for='n_ignore' style='display: inline;'>ignore (regexp)</label>
			<input type='text' name='n_ignore' id='n_ignore' value='<?php echo (is_null($_POST['n_ignore']) ? "/\s(the|an?)\s/" : $_POST['n_ignore']); ?>' style='display: inline;'/>
			<a href='javascript:default_ignore();'>default</a>
			<br/>			
		</td>
	</tr>
	<tr class="<?php echo flip_class(); ?>">
		<th colspan='2' style='text-align: left;'>Compare options</th>
	</tr>
	<tr class="<?php echo flip_class(); ?>">
		<td>
			<label for='c_mode' style='display: inline;'>Line mode</label>
			<input type='radio' name='c_mode' id='c_mode' value='line' style='display: inline;' <?php echo (is_null($_POST['c_mode']) || $_POST['c_mode']  == "line" ? "checked='checked'" : ''); ?> />
		</td>
		<td>
			&lt;No Options&gt;		
		</td>	
	</tr>	
	
	<tr class="<?php echo flip_class(); ?>">
		<td>
			<label for='c_mode' style='display: inline;'>Word mode</label>
			<input type='radio' name='c_mode' id='c_mode' value='word' style='display: inline;' <?php echo (is_null($_POST['c_mode']) || $_POST['c_mode']  == "word" ? "checked='checked'" : ''); ?> />
		</td>
		<td>
			<label for='c_count_w' style='display: inline;'># words to match (0=disable)</label>
			<input type='text' name='c_count_w' id='c_count_w' value='<?php echo (is_null($_POST['c_count_w']) ? "0" : $_POST['c_count_w']); ?>' style='display: inline;'  size='2'/>
			<br/>OR<br/>
			<label for='c_percent_w' style='display: inline;'>% words to match (0=disable)</label>
			<input type='text' name='c_percent_w' id='c_percent_w' value='<?php echo (is_null($_POST['c_percent_w']) ? "50" : $_POST['c_percent_w']); ?>' style='display: inline;' size='4'/>%
			<br/>			
		</td>	
	</tr>
	<tr class="<?php echo flip_class(); ?>">
		<td>
			<label for='c_mode' style='display: inline;'>Letter mode</label>
			<input type='radio' name='c_mode' id='c_mode' value='letter' style='display: inline;' <?php echo ($_POST['c_mode']  == "letter" ? "checked='checked'" : ''); ?> />
		</td>
		<td>
			<label for='c_distance_l' style='display: inline;'>Max (levenshtein) difference, the larger the looser</label>
			<input type='text' name='c_distance_l' id='c_distance_l' value='<?php echo (is_null($_POST['c_distance_l']) ? "2" : $_POST['c_distance_l']); ?>' style='display: inline;'  size='2'/>
		</td>
	</tr>
	<tr class="<?php echo flip_class(); ?>">
		<td colspan='2'>
			<input class="button" type="button" name="action" value="<?php echo _("Search Again"); ?>" onclick="document.advanced.submit();" />
		</td>
	</tr>
	
</table>
</form>
<?php show_box_bottom(); ?>
<script language='javascript'>
	function default_filter() {
		document.getElementById('n_filter').value = "/[^a-z ]/";
	}
	function default_ignore() {
		document.getElementById('n_ignore').value = "/\s(the|an?)\s/";
	}	
</script>
