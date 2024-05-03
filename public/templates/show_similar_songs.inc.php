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
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\TalFactoryInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;

/** @var Ampache\Repository\Model\Artist $artist */
/** @var Ampache\Repository\Model\Browse $browse */
/** @var array $object_ids */
/** @var array $hide_columns */
/** @var string $argument_param */

$web_path     = (string)AmpConfig::get('web_path', '');
$show_ratings = User::is_registered() && (AmpConfig::get('ratings'));
$hide_genres  = AmpConfig::get('hide_genres');
$is_table     = true;
$is_group     = (AmpConfig::get('album_group'));
// hide columns you don't always need
$hide_artist  = in_array('cel_artist', $hide_columns);
$hide_album   = in_array('cel_album', $hide_columns);
$hide_year    = in_array('cel_year', $hide_columns);
$hide_drag    = in_array('cel_drag', $hide_columns);
$show_license = AmpConfig::get('licensing') && AmpConfig::get('show_license');
?>
<table id="similar_tracks" class="tabledata striped-rows">
    <thead>
    <tr class="th-top">
        <th class="cel_play essential"></th>
        <th class="cel_song essential persist"><?php echo T_('Song Title'); ?></th>
        <th class="cel_add essential"></th>
        <?php if (!$hide_artist) { ?>
            <th class="cel_artist optional"><?php echo T_('Song Artist'); ?></th>
        <?php } ?>
        <?php if (!$hide_album) { ?>
            <th class="cel_album essential"><?php echo T_('Album'); ?></th>
        <?php } ?>
        <?php if (!$hide_year) { ?>
            <th class="cel_year"><?php echo T_('Year'); ?></th>
        <?php } ?>
        <?php if (!$hide_genres) { ?>
            <th class="cel_tags optional"><?php echo T_('Genres'); ?></th>
        <?php } ?>
        <th class="cel_time optional"><?php echo T_('Time'); ?></th>
        <?php if (AmpConfig::get('show_played_times')) { ?>
            <th class="cel_counter optional"><?php echo T_('Played'); ?></th>
            <?php } ?>
        <?php if (AmpConfig::get('show_skipped_times')) { ?>
            <th class="cel_counter optional"><?php echo T_('Skipped'); ?></th>
            <?php } ?>
        <?php if ($show_ratings) { ?>
            <th class="cel_ratings optional"><?php echo T_('Rating'); ?></th>
            <?php if (AmpConfig::get('ratings')) {
                Rating::build_cache('song', $object_ids);
                Userflag::build_cache('song', $object_ids);
            }
        } ?>
        <th class="cel_action essential"><?php echo T_('Actions'); ?></th>
    </tr>
    </thead>
    <tbody>
        <?php global $dic;
$talFactory = $dic->get(TalFactoryInterface::class);
$guiFactory = $dic->get(GuiFactoryInterface::class);
$gatekeeper = $dic->get(GatekeeperFactoryInterface::class)->createGuiGatekeeper();

foreach ($object_ids as $song_id) {
    $libitem = new Song($song_id);
    if ($libitem->isNew()) {
        continue;
    }
    $libitem->format(); ?>

            <tr id="song_<?php echo $libitem->id; ?>">
                <?php
        if ($libitem->enabled || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)) {
            $content = $talFactory->createTalView()
                ->setContext('USER_IS_REGISTERED', User::is_registered())
                ->setContext('USING_RATINGS', User::is_registered() && (AmpConfig::get('ratings')))
                ->setContext('SONG', $guiFactory->createSongViewAdapter($gatekeeper, $libitem))
                ->setContext('CONFIG', $guiFactory->createConfigViewAdapter())
                ->setContext('ARGUMENT_PARAM', '')
                ->setContext('IS_TABLE_VIEW', $is_table)
                ->setContext('IS_ALBUM_GROUP', $is_group)
                ->setContext('IS_SHOW_TRACK', !empty($argument))
                ->setContext('IS_SHOW_LICENSE', $show_license)
                ->setContext('IS_HIDE_GENRE', $hide_genres)
                ->setContext('IS_HIDE_ARTIST', $hide_artist)
                ->setContext('IS_HIDE_ALBUM', $hide_album)
                ->setContext('IS_HIDE_YEAR', $hide_year)
                ->setContext('IS_HIDE_DRAG', (empty($argument) || $hide_drag))
                ->setTemplate('song_row.xhtml')
                ->render();

            echo $content;
        } ?>
            </tr>
            <?php
} ?>

        <?php if (!count($object_ids)) { ?>
            <tr>
                <td colspan="100"><span class="nodata"><?php echo T_('No song found'); ?></span></td>
            </tr>
        <?php } ?>
    </tbody>
</table>
