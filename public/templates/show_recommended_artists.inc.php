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

use Ampache\Config\AmpConfig;
use Ampache\Repository\Model\Art;
use Ampache\Repository\Model\Artist;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Util\Ui;

$show_ratings = User::is_registered() && (AmpConfig::get('ratings') || AmpConfig::get('userflags'));
$thcount      = 8;
//mashup and grid view need different css
$cel_cover   = "cel_cover";
$cel_album   = "cel_album";
$cel_artist  = "cel_artist";
$cel_tags    = "cel_tags";
$cel_time    = "cel_time";
$cel_counter = "cel_counter"; ?>
<?php UI::show_box_top(T_('Similar Artists'), 'info-box'); ?>
<table class="tabledata striped-rows">
    <thead>
        <tr class="th-top">
            <th class="cel_play"></th>
            <?php if (Art::is_enabled()) {
    ++$thcount; ?>
                <th class="<?php echo $cel_cover; ?> optional"><?php echo T_('Art'); ?></th>
            <?php
} ?>
            <th class="<?php echo $cel_artist; ?>"><?php echo T_('Artist'); ?></th>
            <th class="cel_add"></th>
            <th class="cel_songs"><?php echo T_('Songs');  ?></th>
            <th class="cel_albums"><?php echo T_('Albums'); ?></th>
            <th class="<?php echo $cel_time; ?>"><?php echo T_('Time'); ?></th>
            <?php if (AmpConfig::get('show_played_times')) { ?>
                <th class="<?php echo $cel_counter; ?> optional"><?php echo T_('# Played') ?></th>
                <?php
            } ?>
            <th class="<?php echo $cel_tags; ?>"><?php echo T_('Genres'); ?></th>
            <?php if ($show_ratings) {
                ++$thcount; ?>
                <th class="cel_ratings optional"><?php echo T_('Rating'); ?></th>
                <?php
            } ?>
            <th class="cel_action"> <?php echo T_('Action'); ?> </th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Cache the ratings we are going to use
        if (AmpConfig::get('ratings')) {
            Rating::build_cache('artist', $object_ids);
        }
        // Cache the userflags we are going to use
        if (AmpConfig::get('userflags')) {
            Userflag::build_cache('artist', $object_ids);
        }

        /* Foreach through every artist that has been passed to us */
        foreach ($object_ids as $artist_id) {
            $libitem = new Artist($artist_id);
            $libitem->format(); ?>
        <tr id="artist_<?php echo $libitem->id; ?>">
            <?php require Ui::find_template('show_artist_row.inc.php'); ?>
        </tr>
        <?php
        } ?>
        <?php
        /* Foreach through every missing artist that has been passed to us */
        foreach ($missing_objects as $missing) { ?>
        <tr id="missing_artist_<?php echo $missing['mbid']; ?>">
            <td></td>
            <td colspan="<?php echo($thcount - 1); ?>"><a class="missing_album" href="<?php echo AmpConfig::get('web_path'); ?>/artists.php?action=show_missing&mbid=<?php echo $missing['mbid']; ?>" title="<?php echo scrub_out($missing['name']); ?>"><?php echo scrub_out($missing['name']); ?></a></td>
        </tr>
        <?php
        } ?>
        <?php if ((!$object_ids || !count($object_ids)) && (!$missing_objects || !count($missing_objects))) { ?>
        <tr>
            <td colspan="<?php echo $thcount; ?>"><span class="nodata"><?php echo T_('No similar artist found'); ?></span></td>
        </tr>
        <?php
        } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_play"></th>
            <?php if (Art::is_enabled()) { ?>
            <th class="<?php echo $cel_cover; ?>"><?php echo T_('Art'); ?></th>
            <?php
            } ?>
            <th class="<?php echo $cel_artist; ?>"><?php echo T_('Artist'); ?></th>
            <th class="cel_add"></th>
            <th class="cel_songs"> <?php echo T_('Songs');  ?> </th>
            <th class="cel_albums"> <?php echo T_('Albums'); ?> </th>
            <th class="<?php echo $cel_time; ?>"> <?php echo T_('Time'); ?> </th>
            <th class="<?php echo $cel_tags; ?>"><?php echo T_('Genres'); ?></th>
            <?php if ($show_ratings) { ?>
            <th class="cel_ratings optional"><?php echo T_('Rating'); ?></th>
            <?php
            } ?>
            <th class="cel_action"> <?php echo T_('Action'); ?> </th>
        </tr>
    </tfoot>
</table>
<?php Ui::show_box_bottom(); ?>
