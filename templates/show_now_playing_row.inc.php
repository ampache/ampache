<?php
/*

Copyright (c) 2001 - 2006 Ampache.org
All rights reserved.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/
?>
<span class="np_row">
	<span class="np_cell"><?php echo scrub_out($np_user->fullname); ?></span>
	<span class="np_cell">
	<a title="<?php echo scrub_out($song->f_title); ?>" href="<?php echo $web_path; ?>/song.php?action=single_song&amp;song_id=<?php echo $song->id; ?>">
		<?php echo scrub_out($song->f_title); ?>
	</a>
	</span>
	<span class="np_cell">
	<a title="<?php echo scrub_out($song->f_album); ?>" href="<?php echo $web_path; ?>/albums.php?action=show&amp;album=<?php echo $song->album; ?>">
		<?php echo scrub_out($song->f_album); ?></a> /
	<a title="<?php echo scrub_out($song->f_artist); ?>" href="<?php echo $web_path; ?>/artists.php?action=show&amp;artist=<?php echo $song->artist; ?>">
		<?php echo scrub_out($song->f_artist); ?>
	</a>
	</span>
	<?php if (conf('play_album_art')) { ?>
	<span class="np_cell">
	<a target="_blank" href="<?php echo $web_path; ?>/albumart.php?id=<?php echo $song->album; ?>&amp;type=popup" onclick="popup_art('<?php echo $web_path; ?>/albumart.php?id=<?php echo $song->album; ?>&amp;type=popup'); return false;">
	<img align="middle" border="0" src="<?php echo $web_path; ?>/albumart.php?id=<?php echo $song->album; ?>&amp;fast=1&amp;thumb=1" alt="Album Art" height="75" /></a>
	</span>
	<?php } // end play album art ?>
</span>
