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
?>
<div class="np_group" id="np_group_1">
  <div class="np_cell cel_username">
    <label><?php echo T_('Username'); ?></label>
      <a title="<?php echo scrub_out($agent); ?>" href="<?php echo $web_path; ?>/stats.php?action=show_user&amp;user_id=<?php echo $np_user->id; ?>">
      <?php echo scrub_out($np_user->fullname); ?>
    </a>
  </div>

  <div class="np_cell cel_rating">
    <label><?php echo T_('Rating'); ?></label>
    <div id="rating_<?php echo $media->id; ?>_song">
      <?php Rating::show($media->id,'song'); ?>
    </div>
  </div>

  <?php if (Config::get('show_lyrics')) {?>
  <div class="np_cell cel_lyrics">
      <label>&nbsp;</label>
      <a title="<?php echo scrub_out($media->title); ?>" href="<?php echo $web_path; ?>/song.php?action=show_lyrics&amp;song_id=<?php echo $media->id; ?>">
      <?php echo T_('Show Lyrics');?>
      </a>
  </div>
  <?php } ?>
</div>

<div class="np_group" id="np_group_2">
  <div class="np_cell cel_song">
      <label><?php echo T_('Song'); ?></label>
    <?php echo $media->f_link; ?>
  </div>

  <div class="np_cell cel_album">
      <label><?php echo T_('Album'); ?></label>
    <?php echo $media->f_album_link; ?>
  </div>

  <div class="np_cell cel_artist">
      <label><?php echo T_('Artist'); ?></label>
    <?php echo $media->f_artist_link; ?>
  </div>
</div>

<?php if (Art::is_enabled()) { ?>
<div class="np_group" id="np_group_3">
  <div class="np_cell cel_albumart">
      <a href="<?php echo $web_path; ?>/image.php?id=<?php echo $media->album; ?>" onclick="TINY.box.show({image:'<?php echo $web_path; ?>/image.php?id=<?php echo $media->album; ?>',boxid:'frameless',animate:true}); return false;">
        <img align="middle" src="<?php echo $web_path; ?>/image.php?id=<?php echo $media->album; ?>&amp;thumb=1&amp;sid=<?php echo session_id(); ?>" alt="<?php echo scrub_out($media->f_album_full); ?>" title="<?php echo scrub_out($media->f_album_full); ?>" height="80" width="80" />
      </a>
  </div>
</div>
<?php } // end play album art ?>

<?php if (Config::get('show_similar')) { ?>
<div class="np_group">
<?php if ($artists = Recommendation::get_artists_like($media->artist, 3, false)) { ?>
    <div class="np_cel cel_similar">
        <label><?php echo T_('Similar Artists'); ?></label>
        <?php    foreach ($artists as $a) { ?>
            <div class="np_cel cel_similar_artist">
            <?php
            if (is_null($a['id'])) {
                echo scrub_out(UI::truncate($a['name']), Config::get('ellipse_threshold_artist'));
            }
            else {
                $artist = new Artist($a['id']);
                $artist->format();
                echo $artist->f_name_link;
            }
            ?>
            </div>
        <?php } // end foreach ?> 
    </div>
<?php } // end show similar artists ?>
<?php if ($songs = Recommendation::get_songs_like($media->id, 3)) { ?>
    <div class="np_cel cel_similar">
        <label><?php echo T_('Similar Songs'); ?></label>
        <?php    foreach ($songs as $s) { ?>
            <div class="np_cel cel_similar_song">
            <?php
            $song = new Song($s['id']);
            $song->format();
            echo $song->f_link;
            ?>
            </div>
        <?php } // end foreach ?>
    </div>
<?php } // end show similar songs ?>
</div>
<?php } // end show similar things ?>
