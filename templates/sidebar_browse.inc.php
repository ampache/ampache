<?php $ajax_info = Config::get('ajax_url'); $web_path = Config::get('web_path'); ?>
<ul class="sb2" id="sb_browse">
  <li><?php echo _('Browse By'); ?>
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
  <li><?php echo _('Filters'); ?>
    <div class="sb3">
      <?php show_alphabet_list($_REQUEST['alpha_match'],$_REQUEST['action']); ?>
  <!--
  <input type="checkbox" onclick="ajaxPut('<?php echo $ajax_info; ?>?action=browse&amp;key=min_count&amp;value=1');return true;" value="1" />
  	<?php echo _('Minimum Count'); ?><br />
  <input type="checkbox" onclick="ajaxPut('<?php echo $ajax_info; ?>?action=browse&amp;key=rated&amp;value=1');return true;" value="1" />
  	<?php echo _('Rated'); ?><br />
  -->
      <input id="unplayedCB" type="checkbox" <? echo $string = Browse::get_filter('unplayed') ? 'checked="checked"' : ''; ?>/>
  	  <label id="unplayedLabel" for="unplayedCB"><?php echo _('Unplayed'); ?></label><br />
	 <?php echo Ajax::observe('unplayedCB','click',Ajax::action('?page=browse&action=browse&key=unplayed&value=1','')); ?>
      <input id="show_artCB" type="checkbox" <? echo $string = Browse::get_filter('show_art') ? 'checked="checked"' : ''; ?>/>
      	  <label id="show_artLabel" for="show_artCB"><?php echo _('Show Art'); ?></label><br />
	<?php echo Ajax::observe('show_artCB','click',Ajax::action('?page=browse&action=browse&key=show_art&value=1','')); ?>
    </div>
  </li>
</ul>
