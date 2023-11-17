<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Song;

/** @var array $artists */
/** @var array $songs */
?>

<?php
$web_path = (string)AmpConfig::get('web_path', '');
$wanted   = AmpConfig::get('wanted');
if ($artists) { ?>
<div class="np_group similars">
    <div class="np_cell cel_similar">
        <label><?php echo T_('Similar Artists'); ?></label>
        <?php foreach ($artists as $artistArray) { ?>
            <div class="np_cell cel_similar_artist">
            <?php
                if ($artistArray['id'] === null) {
                    if ($wanted && $artistArray['mbid']) {
                        echo "<a class=\"missing_album\" href=\"" . $web_path . "/artists.php?action=show_missing&mbid=" . $artistArray['mbid'] . "\" title=\"" . scrub_out($artistArray['name']) . "\">" . scrub_out($artistArray['name']) . "</a>";
                    } else {
                        echo scrub_out($artistArray['name']);
                    }
                } else {
                    $artist = new Artist($artistArray['id']);
                    echo $artist->get_f_link();
                } ?>
            </div>
        <?php } ?>
    </div>
</div>
<?php } ?>

<?php if ($songs) { ?>
<div class="np_group similars">
    <div class="np_cell cel_similar">
        <label><?php echo T_('Similar Songs'); ?></label>
        <?php foreach ($songs as $songArray) { ?>
            <div class="np_cell cel_similar_song">
            <?php $song = new Song($songArray['id']);
            echo $song->get_f_link(); ?>
            </div>
        <?php } ?>
    </div>
</div>
<?php } ?>
