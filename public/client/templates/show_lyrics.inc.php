<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Art;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Song;

/** @var Song $song */
/** @var array{text?: null|string, url?: null|string} $lyrics */

$web_path = AmpConfig::get_web_path('/client');
Ui::show_box_top("Song Lyrics", 'box box_lyrics_song');

// Prepare the variables
$title  = scrub_out($song->title);
$album  = scrub_out($song->f_album_full);
$artist = scrub_out($song->get_artist_fullname()); ?>
<?php
if ($album != T_('Unknown (Orphaned)')) {
    Art::display('album', $song->album, $album, 2);
} ?>

<div class="np_group">
  <div class="np_cell">
      <label><?php echo T_('Song'); ?>:</label>
      <a title="<?php echo scrub_out($song->title); ?>" href="<?php echo $web_path; ?>/song.php?action=show_song&song_id=<?php echo $song->id; ?>">
          <?php echo $title; ?>
      </a>
  </div>

  <div class="np_cell">
      <label><?php echo T_('Album'); ?>:</label>
      <a title="<?php echo $album; ?>" href="<?php echo $web_path; ?>/albums.php?action=show&album=<?php echo $song->album; ?>">
              <?php echo $album; ?>
      </a>
  </div>

  <div class="np_cell">
      <label><?php echo T_('Artist'); ?>:</label>
      <a title="<?php echo $artist; ?>" href="<?php echo $web_path; ?>/artists.php?action=show&artist=<?php echo $song->artist; ?>">
              <?php echo $artist; ?>
      </a>
  </div>
</div>
<br /><br />
<div class="lyrics">
    <div id="lyrics_text"><?php echo($lyrics['text']) ?? T_("No lyrics found."); ?></div>
<?php if (array_key_exists('url', $lyrics) && !empty($lyrics['url'])) { ?>
    <div id="lyrics_url"><a href="<?php echo $lyrics['url']; ?>" target="_blank"><?php echo T_('Show more'); ?></a></div>
<?php } ?>
</div>

<?php Ui::show_box_bottom(); ?>
