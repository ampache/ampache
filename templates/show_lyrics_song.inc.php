<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
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

show_box_top($song->title);

/* Prepare the variables */
$title = scrub_out(truncate_with_ellipsis($song->title));
$album = scrub_out(truncate_with_ellipsis($song->f_album_full));
$artist = scrub_out(truncate_with_ellipsis($song->f_artist_full));
?>
<div class="np_group">
  <?php if (Config::get('show_album_art')) { ?>
  <div class="np_cell cel_albumart">
    <a target="_blank" href="<?php echo $web_path; ?>/image.php?id=<?php echo $song->album; ?>&amp;type=popup&amp;sid=<?php echo session_id(); ?>" onclick="popupWindow('<?php echo $web_path; ?>/image.php?id=<?php echo $song->album; ?>&amp;type=popup&amp;sid=<?php echo session_id(); ?>'); return false;">
      <img align="middle" src="<?php echo $web_path; ?>/image.php?id=<?php echo $song->album; ?>&amp;thumb=1&amp;sid=<?php echo session_id(); ?>" alt="<?php echo scrub_out($song->f_album_full); ?>" title="<?php echo scrub_out($song->f_album_full); ?>" height="75" width="75" />
    </a>
  </div>
  <?php } // end play album art ?>

</div>

<div class="np_group">
  <div class="np_cell cel_song">
  	<label><?php echo _('Song'); ?></label>
  	<a title="<?php echo scrub_out($song->title); ?>" href="<?php echo $web_path; ?>/stream.php?action=single_song&amp;song_id=<?php echo $song->id; ?>">
          <?php echo $title; ?>
  	</a>
  </div>

  <div class="np_cell cel_album">
  	<label><?php echo _('Album'); ?></label>
  	<a title="<?php echo scrub_out($song->f_album_full); ?>" href="<?php echo $web_path; ?>/albums.php?action=show&amp;album=<?php echo $song->album; ?>">
          	<?php echo $album; ?>
  	</a>
  </div>

  <div class="np_cell cel_artist">
  	<label><?php echo _('Artist'); ?></label>
  	<a title="<?php echo scrub_out($song->f_artist_full); ?>" href="<?php echo $web_path; ?>/artists.php?action=show&amp;artist=<?php echo $song->artist; ?>">
  	        <?php echo $artist; ?>
  	</a>
  </div>

</div>


<?php show_box_bottom(); ?>