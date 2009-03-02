<?php
/*

 Copyright (c) Ampache.org
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
?>
<ul class="sb2" id="sb_home">
  <li><h4><?php echo _('Browse'); ?></h4>
  <?php
	$allowed_filters = Browse::get_allowed_filters();
	// Build the selected dealie
	$text = scrub_in($_REQUEST['action']) . '_ac';
	${$text} = ' selected="selected"';
  ?>
    <ul class="sb3" id="sb_browse_bb">
      <li id="sb_browse_bb_SongTitle"><a href="<?php echo $web_path; ?>/browse.php?action=song"><?php echo _('Song Title'); ?></a></li>
      <li id="sb_browse_bb_Album"><a href="<?php echo $web_path; ?>/browse.php?action=album"><?php echo _('Albums'); ?></a></li>
      <li id="sb_browse_bb_Artist"><a href="<?php echo $web_path; ?>/browse.php?action=artist"><?php echo _('Artist'); ?></a></li>
  <!--    <li id="sb_browse_bb_Tags"><a href="<?php echo $web_path; ?>/browse.php?action=tag"><?php echo _('Tag Cloud'); ?></a></li> -->
      <li id="sb_browse_bb_Playlist"><a href="<?php echo $web_path; ?>/browse.php?action=playlist"><?php echo _('Playlist'); ?></a></li>
      <li id="sb_browse_bb_RadioStation"><a href="<?php echo $web_path; ?>/browse.php?action=live_stream"><?php echo _('Radio Stations'); ?></a></li>
      <li id="sb_browse_bb_Video"><a href="<?php echo $web_path; ?>/browse.php?action=video"><?php echo _('Video'); ?></a></li>
    </ul>
  </li>
<?php if (count($allowed_filters)) { ?>
  <li><h4><?php echo _('Filters'); ?></h4>
    <div class="sb3">
        <?php if (in_array('starts_with',$allowed_filters)) { ?>
        <form id="multi_alpha_filter_form" method="post" action="javascript:void(0);">
                <label id="multi_alpha_filterLabel" for="multi_alpha_filter"><?php echo _('Starts With'); ?></label>
                <input type="text" id="multi_alpha_filter" name="multi_alpha_filter" value="<?php echo scrub_out(Browse::get_filter('starts_with')); ?>" onKeyUp="DelayRun(this,'400','ajaxState','<?php echo Config::get('ajax_url'); ?>?page=browse&action=browse&type=<?php echo Browse::get_type(); ?>&key=starts_with','multi_alpha_filter');">
        </form>
        <?php } // end if alpha_match ?>
        <?php if (in_array('minimum_count',$allowed_filters)) { ?>
                <input id="mincountCB" type="checkbox" onclick="ajaxPut('<?php echo $ajax_info; ?>?action=browse&amp;key=min_count&amp;type=<?php echo Browse::get_type(); ?>&amp;value=1');return true;" value="1" />
                <label id="mincountLabel" for="mincountCB"><?php echo _('Minimum Count'); ?></label><br />
        <?php } ?>
        <?php if (in_array('rated',$allowed_filters)) { ?>
                <input id="ratedCB" type="checkbox" onclick="ajaxPut('<?php echo $ajax_info; ?>?action=browse&amp;type=<?php echo Browse::get_type(); ?>&amp;key=rated&amp;value=1');return true;" value="1" />
                <label id="ratedLabel" for="ratedCB"><?php echo _('Rated'); ?></label><br />
        <?php } ?>
        <?php if (in_array('unplayed',$allowed_filters)) { ?>
                <input id="unplayedCB" type="checkbox" <?php echo $string = Browse::get_filter('unplayed') ? 'checked="checked"' : ''; ?>/>
                <label id="unplayedLabel" for="unplayedCB"><?php echo _('Unplayed'); ?></label><br />
        <?php } ?>
        <?php if (in_array('show_art',$allowed_filters)) { ?>
                <input id="show_artCB" type="checkbox" <?php echo $string = Browse::get_filter('show_art') ? 'checked="checked"' : ''; ?>/>
                <label id="show_artLabel" for="show_artCB"><?php echo _('Show Art'); ?></label><br />
                <?php echo Ajax::observe('show_artCB','click',Ajax::action('?page=browse&action=browse&type=' . Browse::get_type() . '&key=show_art&value=1','')); ?>
        <?php } // if show_art ?>
        <?php if (in_array('playlist_type',$allowed_filters)) { ?>
                <input id="show_allplCB" type="checkbox" <?php echo $string = Browse::get_filter('playlist_type') ? 'checked="checked"' : ''; ?>/>
                <label id="show_allplLabel" for="showallplCB"><?php echo _('All Playlists'); ?></label><br />
                <?php echo Ajax::observe('show_allplCB','click',Ajax::action('?page=browse&action=browse&type=' . Browse::get_type() . '&key=playlist_type&value=1','')); ?>
        <?php } // if playlist_type ?>
	<?php if (in_array('object_type',$allowed_filters)) { ?>
		<input id="typeSongRadio" type="radio" name="object_type" value="1" />
		<label id="typeSongLabel" for="typeSongRadio"><?php echo _('Song Title'); ?></label><br />
		<?php echo Ajax::observe('typeSongRadio','click',Ajax::action('?page=tag&action=browse&type=song','')); ?>
		<input id="typeAlbumRadio" type="radio" name="object_type" value="1" />
		<label id="typeAlbumLabel" for="typeAlbumRadio"><?php echo _('Albums'); ?></label><br />
		<?php echo Ajax::observe('typeAlbumRadio','click',Ajax::action('?page=tag&action=browse&type=album','')); ?>
		<input id="typeArtistRadio" type="radio" name="object_type" value="1" />
		<label id="typeArtistLabel" for="typeArtistRadio"><?php echo _('Artist'); ?></label><br />
		<?php echo Ajax::observe('typeArtistRadio','click',Ajax::action('?page=tag&action=browse&type=artist','')); ?>
	<?php } ?>
    </div>
  </li>
<?php } ?>
  <li><h4><?php echo _('Playlist'); ?></h4>
    <ul class="sb3" id="sb_home_info">
      <li id="sb_home_info_CurrentlyPlaying"><a href="<?php echo $web_path; ?>/index.php"><?php echo _('Currently Playing'); ?></a></li>
<?php if (Config::get('allow_democratic_playback')) { ?>
      <li id="sb_home_democratic_playlist"><a href="<?php echo $web_path; ?>/democratic.php?action=show_playlist"><?php echo _('Democratic'); ?></a></li>
<?php } ?>
<?php if ($server_allow = Config::get('allow_localplay_playback') AND $controller = Config::get('localplay_controller') AND $access_check = Access::check('localplay','5')) { ?>
<?php
        // Little bit of work to be done here
        $localplay = new Localplay(Config::get('localplay_controller'));
        $current_instance = $localplay->current_instance();
        $class = $current_instance ? '' : ' class="active_instance"';
?>
        <li id="sb_localplay_info_show"><a href="<?php echo $web_path; ?>/localplay.php?action=show_playlist"><?php echo _('Localplay'); ?></a></li>
<?php } ?>
      <li id="sb_browse_bb_Playlist"><a href="<?php echo $web_path; ?>/playlist.php?action=show_import_playlist"><?php echo _('Import'); ?></a></li>
    </ul>
  </li>
  <li><h4><?php echo _('Random'); ?></h4>
    <ul class="sb3" id="sb_home_random">
      <li id="sb_home_random_album"><?php echo Ajax::text('?page=random&action=album',_('Album'),'home_random_album'); ?></li>
      <li id="sb_home_random_artist"><?php echo Ajax::text('?page=random&action=artist',_('Artist'),'home_random_artist'); ?></li>
      <li id="sb_home_random_playlist"><?php echo Ajax::text('?page=random&action=playlist',_('Playlist'),'home_random_playlist'); ?></li>
      <li id="sb_home_random_advanced"><a href="<?php echo $web_path; ?>/random.php?action=advanced"><?php echo _('Advanced'); ?></a></li>
    </ul>
  </li>
  <li><h4><?php echo _('Information'); ?></h4>
    <ul class="sb3" id="sb_home_info">
      <li id="sb_home_info_Statistics"><a href="<?php echo $web_path; ?>/stats.php?action=show"><?php echo _('Statistics'); ?></a></li>
      <li id="sb_home_info_Newest"><a href="<?php echo $web_path; ?>/stats.php?action=newest"><?php echo _('Newest'); ?></a></li>
      <li id="sb_home_info_Popular"><a href="<?php echo $web_path; ?>/stats.php?action=popular"><?php echo _('Popular'); ?></a></li>
    </ul>
  </li>
</ul>
