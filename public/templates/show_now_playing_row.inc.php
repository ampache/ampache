<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\AlbumDisk;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Api\Ajax;

/** @var Song $media */
/** @var Ampache\Repository\Model\User $np_user */
/** @var string $web_path */
/** @var string $agent */

$showAlbum = AmpConfig::get('album_group');
?>
<div class="np_group" id="np_group_1">
    <div class="np_cell cel_username">
        <label><?php echo T_('Username'); ?></label>
        <a title="<?php echo scrub_out($agent); ?>" href="<?php echo $web_path; ?>/stats.php?action=show_user&user_id=<?php echo $np_user->id ?? -1; ?>">
        <?php
            echo scrub_out($np_user->fullname);
if ($np_user->f_avatar_medium) {
    echo '<div>' . $np_user->f_avatar_medium . '</div>';
} ?>
        </a>
    </div>
</div>

<div class="np_group" id="np_group_2">
    <div class="np_cell cel_left">
        <label><?php echo T_('Song'); ?></label>
        <?php echo $media->get_f_link(); ?>
    </div>
    <div class="np_cell cel_left">
        <label><?php echo T_('Album'); ?></label>
        <?php echo ($showAlbum) ? $media->get_f_album_link() : $media->get_f_album_disk_link(); ?>
    </div>
    <div class="np_cell cel_left">
        <label><?php echo T_('Artist'); ?></label>
        <?php echo $media->get_f_artist_link(); ?>
    </div>
    <div class="np_cell cel_left">
        <label><?php echo T_('Year'); ?></label>
        <?php echo $media->f_year_link; ?>
    </div>
    <?php
        if (!empty($media->f_tags)) { ?>
            <div id="np_song_tags_<?php echo $media->id?>" class="np_cell cel_left">
                <label><?php echo T_('Genres'); ?></label>
                <?php echo $media->f_tags; ?>
            </div>
        <?php } ?>
</div>

<div class="np_group" id="np_group_3">
  <div id="album_<?php echo $media->album ?>" class="np_cell cel_albumart libitem_menu">
      <?php
      if (AmpConfig::get('show_song_art') && Art::has_db($media->id, 'song')) {
          $playing = new Song($media->id);
      } elseif ($showAlbum) {
          $playing = new Album($media->album);
      } else {
          $playing = new AlbumDisk($media->get_album_disk());
      }
      if ($playing->id) {
          $playing->format();
          $playing->display_art(1);
      } ?>
  </div>
</div>

<?php if (AmpConfig::get('show_similar')) { ?>
<div class="np_group similars" id="similar_items_<?php echo $media->id; ?>">
    <div class="np_group similars">
        <div class="np_cell cel_similar">
            <label><?php echo T_('Similar Artists'); ?></label>
            <p><?php echo T_('Loading...'); ?></p>
        </div>
    </div>
    <div class="np_group similars">
        <div class="np_cell cel_similar">
            <label><?php echo T_('Similar Songs'); ?></label>
            <p><?php echo T_('Loading...'); ?></p>
        </div>
    </div>
</div>
<script>
$(document).ready(function(){
    <?php echo Ajax::action('?page=index&action=similar_now_playing&media_id=' . $media->id . '&media_artist=' . $media->artist, 'similar_now_playing'); ?>
});
</script>
<?php } ?>

<?php if (Access::check('interface', 25)) { ?>
        <div class="np_group" id="np_group_4">
    <?php if (AmpConfig::get('ratings')) { ?>
            <span id="rating_<?php echo $media->id; ?>_song">
                <?php echo Rating::show($media->id, 'song'); ?>
            </span>
            <span id="userflag_<?php echo $media->id; ?>_song">
                <?php echo Userflag::show($media->id, 'song'); ?>
            </span>
        <?php } ?>
        </div>
    <?php } ?>
