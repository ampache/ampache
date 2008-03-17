<?php 
/*

 Copyright (c) 2001 - 2007 Ampache.org
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

$ajax_info = Config::get('ajax_url'); $web_path = Config::get('web_path'); 
$allowed_filters = Browse::get_allowed_filters(); 
?>
<ul class="sb2" id="sb_browse">
  <li><h4><?php echo _('Browse By'); ?></h4>
  <?php 
	  // Build the selected dealie
	  $text = scrub_in($_REQUEST['action']) . '_ac';
	  ${$text} = ' selected="selected"'; 
  ?>
    <ul class="sb3" id="sb_browse_bb">
      <li id="sb_browse_bb_SongTitle"><a href="<?php echo $web_path; ?>/browse.php?action=song"><?php echo _('Song Title'); ?></a></li>
      <li id="sb_browse_bb_Album"><a href="<?php echo $web_path; ?>/browse.php?action=album"><?php echo _('Albums'); ?></a></li>
      <li id="sb_browse_bb_Artist"><a href="<?php echo $web_path; ?>/browse.php?action=artist"><?php echo _('Artist'); ?></a></li>
      <li id="sb_browse_bb_Genre"><a href="<?php echo $web_path; ?>/browse.php?action=genre"><?php echo _('Genre'); ?></a></li>
      <li id="sb_browse_bb_Playlist"><a href="<?php echo $web_path; ?>/browse.php?action=playlist"><?php echo _('Playlist'); ?></a></li>
      <li id="sb_browse_bb_RadioStation"><a href="<?php echo $web_path; ?>/browse.php?action=live_stream"><?php echo _('Radio Stations'); ?></a></li>
    </ul>
  </li>
<?php if (count($allowed_filters)) { ?>
  <li><h4><?php echo _('Filters'); ?></h4>
    <div class="sb3">
	<?php if (in_array('alpha_match',$allowed_filters)) { ?>
	<?php show_alphabet_list($_REQUEST['alpha_match'],$_REQUEST['action']); ?>
	<form id="multi_alpha_filter_form" method="post" action="javascript:void(0);">
		<input type="textbox" id="multi_alpha_filter" name="value" value="<?php echo scrub_out($_REQUEST['alpha_match']); ?>" onKeyUp="<?php echo Ajax::action('?page=browse&action=browse&key=alpha_match','multi_alpha_filter','multi_alpha_filter_form'); ?>">
	 	<label id="multi_alpha_filterLabel" for="multi_alpha_filter"><?php echo _('Starts With'); ?></label>
	</form>
	<?php } // end if alpha_match ?>
	<?php if (in_array('minimum_count',$allowed_filters)) { ?>
		<input id="mincountCB" type="checkbox" onclick="ajaxPut('<?php echo $ajax_info; ?>?action=browse&amp;key=min_count&amp;value=1');return true;" value="1" />
	  	<label id="mincountLabel" for="mincountCB"><?php echo _('Minimum Count'); ?></label><br />
	<?php } ?>
	<?php if (in_array('rated',$allowed_filters)) { ?>
		<input id="ratedCB" type="checkbox" onclick="ajaxPut('<?php echo $ajax_info; ?>?action=browse&amp;key=rated&amp;value=1');return true;" value="1" />
  		<label id="ratedLabel" for="ratedCB"><?php echo _('Rated'); ?></label><br />
	<?php } ?>
	<?php if (in_array('unplayed',$allowed_filters)) { ?>
		<input id="unplayedCB" type="checkbox" <?php echo $string = Browse::get_filter('unplayed') ? 'checked="checked"' : ''; ?>/>
		<label id="unplayedLabel" for="unplayedCB"><?php echo _('Unplayed'); ?></label><br />
 	<?php } ?>
	<?php if (in_array('show_art',$allowed_filters)) { ?>
		<input id="show_artCB" type="checkbox" <?php echo $string = Browse::get_filter('show_art') ? 'checked="checked"' : ''; ?>/>
		<label id="show_artLabel" for="show_artCB"><?php echo _('Show Art'); ?></label><br />
		<?php echo Ajax::observe('show_artCB','click',Ajax::action('?page=browse&action=browse&key=show_art&value=1','')); ?>
	<?php } // if show_art ?>
	<?php if (in_array('playlist_type',$allowed_filters)) { ?>
		<input id="show_allplCB" type="checkbox" <?php echo $string = Browse::get_filter('playlist_type') ? 'checked="checked"' : ''; ?>/>
		<label id="show_allplLabel" for="showallplCB"><?php echo _('All Playlists'); ?></label><br />
		<?php echo Ajax::observe('show_allplCB','click',Ajax::action('?page=browse&action=browse&key=playlist_type&value=1','')); ?>
	<?php } // if playlist_type ?>
    </div>
  </li>
<?php } ?>
</ul>
