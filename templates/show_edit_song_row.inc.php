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
<td colspan="8">
<form method="post" id="edit_song_<?php echo $song->id; ?>">
<table cellpadding="3" cellspacing="0">
<td>
	<input type="textbox" name="title" value="<?php echo scrub_out($song->title); ?>" />
</td>
<td>
	<?php show_artist_select('artist',$song->artist,true,$song->id); ?>
	<div id="artist_select_song_<?php echo $song->id ?>"></div>
<?php echo Ajax::observe('artist_select_'.$song->id,'change','check_inline_song_edit("artist", '.$song->id.')'); ?>
</td>
<td>
	<?php show_album_select('album',$song->album,true,$song->id); ?>
	<div id="album_select_song_<?php echo $song->id ?>"></div>
<?php echo Ajax::observe('album_select_'.$song->id,'change','check_inline_song_edit("album", '.$song->id.')'); ?>
</td>
<td>
	<?php show_genre_select('genre',$song->genre,'',true,$song->id); ?>
	<div id="genre_select_song_<?php echo $song->id ?>"></div>
<?php echo Ajax::observe('genre_select_'.$song->id,'change','check_inline_song_edit("genre", '.$song->id.')'); ?>
</td>
<td>
	<input type="textbox" name="track" size="3" value="<?php echo scrub_out($song->track); ?>" />
</td>
<td>
	<input type="hidden" name="id" value="<?php echo $song->id; ?>" />
	<input type="hidden" name="type" value="song" />
	<?php echo Ajax::button('?action=edit_object&id=' . $song->id . '&type=song','download',_('Save Changes'),'save_song_' . $song->id,'edit_song_' . $song->id); ?>
</td>
</table>
</form>
</td>
