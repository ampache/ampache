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
?>
<?php 
  show_box_top($song->title . ' ' . _('Details')); 
  
  $songprops['Title']   = scrub_out($song->title) . Ajax::button('?action=basket&type=song&id=' . $song->id,'add',_('Add'),'add_' . $song->id);
  $songprops['Artist']  = $song->f_artist_link;
  $songprops['Album']   = $song->f_album_link . " (" . scrub_out($song->year). ")";
  $songprops['Genre']   = $song->f_genre_link;
  $songprops['Length']  = scrub_out($song->f_time); 
  $songprops['Comment'] = scrub_out($song->comment);
  $songprops['Label']   = scrub_out($song->label);
  $songprops['Language']= scrub_out($song->language); 
  $songprops['Catalog Number']   = scrub_out($song->catalog_number);
  $songprops['Bitrate']   = scrub_out($song->f_bitrate);
  if ($GLOBALS['user']->has_access('75')) {
    $songprops['Filename']   = scrub_out($song->file) . " " . $song->f_size . "MB";
  }
  if (Config::get('download')) {
  	$songprops['Filename'] = "<a href=\"" . Config::get('web_path') . "/stream.php?action=download&amp;song_id=" . $song->id . "\">" . $songprops['Filename'] . "</a>";
	}
  if ($song->update_time) {
    $songprops['Last Updated']   = date("d/m/Y H:i",$song->update_time);
  }
  $songprops['Added']   = date("d/m/Y H:i",$song->addition_time);
  ?>
  
  <dl class="song_details">
  <?php
  foreach ($songprops as $key => $value)
  {
    if(trim($value))
    {
      $rowparity = flip_class();
      echo "<dt class=\"".$rowparity."\">" . _($key) . "</dt><dd class=\"".$rowparity."\">" . $value . "</dd>";
    }
  }?>
  </dl>
  
<?php show_box_bottom(); ?>
