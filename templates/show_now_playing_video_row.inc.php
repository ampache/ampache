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
 */
$media = Video::create_from_id($media->id);
$media->format(); ?>
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
    <div class="np_cell cel_video">
        <label><?php echo T_('Video'); ?></label>
        <?php echo $media->f_link; ?>
    </div>
</div>

<?php
    if (Art::is_enabled()) { ?>
        <div class="np_group" id="np_group_3">
            <div class="np_cell cel_albumart">
                <?php
                    //$release_art = $media->get_release_item_art();
        //Art::display($release_art['object_type'], $release_art['object_id'], $media->get_fullname(), 6, $media->link);
            $art_showed = false;
        if ($media->get_default_art_kind() == 'preview') {
            $art_showed = Art::display('video', $media->id, $media->f_full_title, 9, $media->link, false, 'preview');
        }
        if (!$art_showed) {
            Art::display('video', $media->id, $media->f_full_title, 6, $media->link);
        } ?>
            </div>
        </div>
    <?php
    } ?>

<div class="np_group" id="np_group_4">
<?php
    if (AmpConfig::get('ratings')) { ?>
        <div class="np_cell cel_rating">
            <label><?php echo T_('Rating'); ?></label>
            <div id="rating_<?php echo $media->id; ?>_video">
                <?php Rating::show($media->id, 'video'); ?>
            </div>
        </div>
        <div class="np_cell cel_userflag">
            <label><?php echo T_('Fav.'); ?></label>
            <div id="userflag_<?php echo $media->id; ?>_video">
                <?php Userflag::show($media->id, 'video'); ?>
            </div>
        </div>
    <?php
    } ?>
</div>
