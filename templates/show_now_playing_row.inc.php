<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2020 Ampache.org
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
 */ ?>
<div class="np_group" id="np_group_1">
    <div class="np_cell cel_username">
        <label><?php echo T_('Username'); ?></label>
        <a title="<?php echo scrub_out($agent); ?>" href="<?php echo $web_path; ?>/stats.php?action=show_user&user_id=<?php echo $np_user->id; ?>">
        <?php
            echo scrub_out($np_user->fullname);
            if ($np_user->f_avatar_medium) {
                echo '<div>' . $np_user->f_avatar_medium . '</div>';
            } ?>
        </a>
    </div>
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
    <div class="np_cell cel_year">
        <label><?php echo T_('Year'); ?></label>
        <?php echo $media->f_year_link; ?>
    </div>
    <?php
        if (!empty($media->f_tags)) { ?>
            <div id="np_song_tags_<?php echo $media->id?>" class="np_cell cel_artist">
                <label><?php echo T_('Tags'); ?></label>
                <?php echo $media->f_tags; ?>
            </div>
        <?php
        } ?>
</div>

<?php if (Art::is_enabled()) { ?>
<div class="np_group" id="np_group_3">
  <div id="album_<?php echo $media->album ?>" class="np_cell cel_albumart libitem_menu">
      <?php
      $playing = (AmpConfig::get('show_song_art')) ? new Song($media->id) : new Album($media->album);
      if ($playing->id) {
          $playing->format();
          $playing->display_art(1);
      } ?>
  </div>
</div>
<?php
    } ?>

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
<?php
    } ?>

<?php
    if (Access::check('interface', 25)) { ?>
        <div class="np_group" id="np_group_4">
    <?php
        if (AmpConfig::get('ratings')) { ?>
            <div class="np_cell cel_rating">
                <label><?php echo T_('Rating'); ?></label>
                <div id="rating_<?php echo $media->id; ?>_song">
                    <?php Rating::show($media->id, 'song'); ?>
                </div>
            </div>
        <?php
        }
        if (AmpConfig::get('userflags')) { ?>
            <div class="np_cell cel_userflag">
                <label><?php echo T_('Fav.'); ?></label>
                <div id="userflag_<?php echo $media->id; ?>_song">
                    <?php Userflag::show($media->id, 'song'); ?>
                </div>
            </div>
        <?php
        } ?>
        </div>
    <?php
    } ?>
