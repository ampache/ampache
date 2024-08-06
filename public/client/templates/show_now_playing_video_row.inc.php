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
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Userflag;
use Ampache\Repository\Model\Video;

/** @var Video $media */
/** @var Ampache\Repository\Model\User $np_user */
/** @var string $web_path */
/** @var string $agent */

$media->format(); ?>
<div class="np_group" id="np_group_1">
    <div class="np_cell cel_username">
        <label><?php echo T_('Username'); ?></label>
        <a title="<?php echo scrub_out($agent); ?>" href="<?php echo $web_path; ?>/stats.php?action=show_user&user_id=<?php echo $np_user->id ?? -1; ?>">
        <?php echo scrub_out($np_user->fullname);
if ($np_user->f_avatar_medium) {
    echo '<div>' . $np_user->f_avatar_medium . '</div>';
} ?>
        </a>
    </div>
</div>

<div class="np_group" id="np_group_2">
    <div class="np_cell cel_video">
        <label><?php echo T_('Video'); ?></label>
        <?php echo $media->get_f_link(); ?>
    </div>
</div>

<div class="np_group" id="np_group_3">
    <div class="np_cell cel_video">
        <?php $art_showed = false;
if ($media->get_default_art_kind() == 'preview') {
    $art_showed = Art::display('video', $media->id, (string)$media->getFileName(), 9, $media->get_link(), false, 'preview');
}
if (!$art_showed) {
    Art::display('video', $media->id, (string)$media->getFileName(), 6, $media->get_link());
} ?>
    </div>
</div>

<div class="np_group" id="np_group_4">
<?php if (AmpConfig::get('ratings')) { ?>
        <span id="rating_<?php echo $media->id; ?>_video">
            <?php echo Rating::show($media->id, 'video'); ?>
        </span>
        <span id="userflag_<?php echo $media->id; ?>_video">
            <?php echo Userflag::show($media->id, 'video'); ?>
        </span>
    <?php } ?>
</div>
