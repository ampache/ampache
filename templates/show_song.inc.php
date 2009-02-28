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
$rowparity = flip_class(); 
$icon = $song->enabled ? 'disable' : 'enable'; 
$button_flip_state_id = 'button_flip_state_' . $song->id;
?>
<?php show_box_top($song->title . ' ' . _('Details')); ?>
<dl class="song_details">
<dt class="<?php echo $rowparity; ?>"><?php echo _('Action'); ?></dt>
	<dd class"<?php echo $rowparity; ?>">
		<?php echo Ajax::button('?action=basket&type=song&id=' . $song->id,'add',_('Add'),'add_song_' . $song->id); ?>
		<?php if (Access::check_function('download')) { ?>
			<a href="<?php echo Song::play_url($song->id); ?>"><?php echo get_user_icon('link'); ?></a>
			<a href="<?php echo Config::get('web_path'); ?>/stream.php?action=download&amp;song_id=<?php echo $song->id; ?>"><?php echo get_user_icon('download'); ?></a>
		<?php } ?>
		<?php if (Access::check('interface','75')) { ?>
			<span id="<?php echo($button_flip_state_id); ?>">
			<?php echo Ajax::button('?page=song&action=flip_state&song_id=' . $song->id,$icon,_(ucfirst($icon)),'flip_song_' . $song->id); ?>
			</span>
		<?php } ?>
	</dd>
<?php 
  $songprops['Title']   = scrub_out($song->title);
  $songprops['Artist']  = $song->f_artist_link;
  $songprops['Album']   = $song->f_album_link . " (" . scrub_out($song->year). ")";
  $songprops['Genre']   = $song->f_genre_link;
  $songprops['Length']  = scrub_out($song->f_time); 
  $songprops['Comment'] = scrub_out($song->comment);
  $songprops['Label']   = scrub_out($song->label);
  $songprops['Language']= scrub_out($song->language); 
  $songprops['Catalog Number']   = scrub_out($song->catalog_number);
  $songprops['Bitrate']   = scrub_out($song->f_bitrate);
  if (Access::check('interface','75')) {
    $songprops['Filename']   = scrub_out($song->file) . " " . $song->f_size . "MB";
  }
  if ($song->update_time) {
    $songprops['Last Updated']   = date("d/m/Y H:i",$song->update_time);
  }
  $songprops['Added']   = date("d/m/Y H:i",$song->addition_time);

  foreach ($songprops as $key => $value)
  {
    if(trim($value))
    {
      $rowparity = flip_class();
      echo "<dt class=\"".$rowparity."\">" . _($key) . "</dt><dd class=\"".$rowparity."\">" . $value . "</dd>";
    }
  }
?> 
<?php show_box_bottom(); ?>
