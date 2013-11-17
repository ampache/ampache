<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

UI::show_box_top($song->title , 'box box_lyrics_song');

/* Prepare the variables */
$title = scrub_out(UI::truncate($song->title));
$album = scrub_out(UI::truncate($song->f_album_full));
$artist = scrub_out(UI::truncate($song->f_artist_full));
?>
<div class="np_group">
  <?php if (Config::get('show_album_art')) { ?>
  <div class="np_cell cel_albumart">
    <a href="<?php echo $web_path; ?>/image.php?id=<?php echo $song->album; ?>&amp;sid=<?php echo session_id(); ?>" onclick="TINY.box.show({image:'<?php echo $web_path; ?>/image.php?id=<?php echo $song->album; ?>&amp;sid=<?php echo session_id(); ?>',boxid:'frameless',animate:true}); return false;">
      <img align="middle" src="<?php echo $web_path; ?>/image.php?id=<?php echo $song->album; ?>&amp;thumb=1&amp;sid=<?php echo session_id(); ?>" alt="<?php echo scrub_out($song->f_album_full); ?>" title="<?php echo scrub_out($song->f_album_full); ?>" height="75" width="75" />
    </a>
  </div>
  <?php } // end play album art ?>

</div>

<div class="np_group">
  <div class="np_cell cel_song">
      <label><?php echo T_('Song'); ?></label>
      <a title="<?php echo scrub_out($song->title); ?>" href="<?php echo $web_path; ?>/stream.php?action=single_song&amp;song_id=<?php echo $song->id; ?>">
          <?php echo $title; ?>
      </a>
  </div>

  <div class="np_cell cel_album">
      <label><?php echo T_('Album'); ?></label>
      <a title="<?php echo scrub_out($song->f_album_full); ?>" href="<?php echo $web_path; ?>/albums.php?action=show&amp;album=<?php echo $song->album; ?>">
              <?php echo $album; ?>
      </a>
  </div>

  <div class="np_cell cel_artist">
      <label><?php echo T_('Artist'); ?></label>
      <a title="<?php echo scrub_out($song->f_artist_full); ?>" href="<?php echo $web_path; ?>/artists.php?action=show&amp;artist=<?php echo $song->artist; ?>">
              <?php echo $artist; ?>
      </a>
  </div>

</div>


<?php UI::show_box_bottom(); ?>
