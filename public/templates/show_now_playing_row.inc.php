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
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
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
/** @var string $t_username */
/** @var string $t_song */
/** @var string $t_album */
/** @var string $t_artist */
/** @var string $t_year */
/** @var string $t_genres */
/** @var string $t_similar_artists */
/** @var string $t_loading */
/** @var string $t_similar_songs */

$showAlbum = AmpConfig::get('album_group'); ?>
<div class="np_group" id="np_group_1">
    <div class="np_cell cel_username">
        <label><?php echo $t_username; ?></label>
        <a title="<?php echo scrub_out($agent); ?>" href="<?php echo $web_path; ?>/stats.php?action=show_user&user_id=<?php echo $np_user->id ?? -1; ?>">
        <?php echo scrub_out($np_user->fullname);
echo '<div>' . $np_user->get_f_avatar('f_avatar_medium') . '</div>'; ?>
        </a>
    </div>
</div>
<div class="np_group" id="np_group_2">
    <div class="np_cell cel_left">
        <label><?php echo $t_song; ?></label>
        <?php echo $media->get_f_link(); ?>
    </div>
    <div class="np_cell cel_left">
        <label><?php echo $t_album; ?></label>
        <?php echo ($showAlbum) ? $media->get_f_album_link() : $media->get_f_album_disk_link(); ?>
    </div>
    <div class="np_cell cel_left">
        <label><?php echo $t_artist; ?></label>
        <?php echo $media->get_f_parent_link(); ?>
    </div>
    <div class="np_cell cel_left">
        <label><?php echo $t_year; ?></label>
        <?php echo "<a href=\"" . $web_path . "/search.php?type=album&action=search&limit=0&rule_1=year&rule_1_operator=2&rule_1_input=" . $media->year . "\">" . $media->year . "</a>"; ?>
    </div>
    <?php
        if (!empty($media->get_tags())) { ?>
            <div id="np_song_tags_<?php echo $media->id; ?>" class="np_cell cel_left">
                <label><?php echo $t_genres; ?></label>
                <?php echo $media->get_f_tags(); ?>
            </div>
        <?php } ?>
</div>

<div class="np_group" id="np_group_3">
  <div id="album_<?php echo $media->album; ?>" class="np_cell cel_albumart libitem_menu">
      <?php
      if (AmpConfig::get('show_song_art') && Art::has_db($media->id, 'song')) {
          $playing = $media;
      } else {
          $playing = ($showAlbum)
              ? new Album($media->album)
              : new AlbumDisk($media->album_disk);
      }
if ($playing->isNew() === false) {
    $playing->display_art(['width' => 100, 'height' => 100]);
} ?>
  </div>
</div>

<?php if (AmpConfig::get('show_similar')) { ?>
<div class="np_group similars" id="similar_items_<?php echo $media->id; ?>">
    <div class="np_group similars">
        <div class="np_cell cel_similar">
            <label><?php echo $t_similar_artists; ?></label>
            <p><?php echo $t_loading; ?></p>
        </div>
    </div>
    <div class="np_group similars">
        <div class="np_cell cel_similar">
            <label><?php echo $t_similar_songs; ?></label>
            <p><?php echo $t_loading; ?></p>
        </div>
    </div>
</div>
<script>
$(document).ready(function(){
    <?php echo Ajax::action('?page=index&action=similar_now_playing&media_id=' . $media->id . '&media_artist=' . $media->artist, 'similar_now_playing'); ?>
});
</script>
<?php } ?>

<?php if (Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::USER)) { ?>
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
