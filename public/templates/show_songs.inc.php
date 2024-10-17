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
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\TalFactoryInterface;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\AccessTypeEnum;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\Util\Ui;

/** @var Ampache\Repository\Model\Browse $browse */
/** @var array $object_ids */
/** @var array $hide_columns */
/** @var string $argument_param */

$web_path     = AmpConfig::get_web_path();
$show_ratings = User::is_registered() && AmpConfig::get('ratings');
$hide_genres  = AmpConfig::get('hide_genres');
$thcount      = 7;
$is_table     = $browse->is_grid_view();
$is_group     = AmpConfig::get('album_group');
$albumString  = $is_group
    ? 'album'
    : 'album_disk';
// hide columns you don't always need
$hide_artist   = in_array('cel_artist', $hide_columns);
$hide_album    = in_array('cel_album', $hide_columns);
$hide_year     = in_array('cel_year', $hide_columns);
$hide_drag     = in_array('cel_drag', $hide_columns);
$show_license  = AmpConfig::get('licensing') && AmpConfig::get('show_license');
$show_track    = !empty($argument) && $is_table;
$cel_play_text = ($show_track)
    ? Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=track' . $argument_param, '#', 'song_sort_track' . $browse->id)
    : '';
//mashup and grid view need different css
$cel_song    = ($is_table) ? "cel_song" : 'grid_song';
$cel_album   = ($is_table) ? "cel_album" : 'grid_album';
$cel_artist  = ($is_table) ? "cel_artist" : 'grid_artist';
$cel_tags    = ($is_table) ? "cel_tags" : 'grid_tags';
$cel_time    = ($is_table) ? "cel_time" : 'grid_time';
$cel_license = ($is_table) ? "cel_license" : 'grid_license';
$cel_counter = ($is_table) ? "cel_counter" : 'grid_counter'; ?>
<?php if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
<table id="reorder_songs_table_<?php echo $browse->get_filter('album'); ?>" class="tabledata striped-rows <?php echo $browse->get_css_class(); ?>" data-objecttype="song" data-offset="<?php echo $browse->get_start(); ?>">
    <thead>
        <tr class="th-top">
            <th class="cel_play essential"><?php echo $cel_play_text; ?></th>
            <th class="<?php echo $cel_song; ?> essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=title' . $argument_param, T_('Song Title'), 'song_sort_title' . $browse->id); ?></th>
            <th class="cel_add essential"></th>
            <?php if (!$hide_artist) {
                ++$thcount; ?>
            <th class="<?php echo $cel_artist; ?> optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=artist' . $argument_param, T_('Song Artist'), 'song_sort_artist' . $browse->id); ?></th>
            <?php
            } ?>
            <?php if (!$hide_album) {
                ++$thcount; ?>
            <th class="<?php echo $cel_album; ?> essential"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=' . $albumString . $argument_param, T_('Album'), 'song_sort_' . $albumString . $browse->id); ?></th>
            <?php
            } ?>
            <?php if (!$hide_year) {
                ++$thcount; ?>
            <th class="cel_year"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=year', T_('Year'), 'song_sort_year'); ?></th>
            <?php
            } ?>
            <?php if (!$hide_genres) {
                ++$thcount; ?>
                <th class="<?php echo $cel_tags; ?> optional"><?php echo T_('Genres'); ?></th>
            <?php
            } ?>
            <th class="<?php echo $cel_time; ?> optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=time' . $argument_param, T_('Time'), 'song_sort_time' . $browse->id); ?></th>
            <?php if ($show_license) {
                ++$thcount; ?>
            <th class="<?php echo $cel_license; ?> optional"><?php echo T_('License'); ?></th>
            <?php
            } ?>
            <?php if (AmpConfig::get('show_played_times')) {
                ++$thcount; ?>
            <th class="<?php echo $cel_counter; ?> optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=total_count' . $argument_param, T_('Played'), 'song_sort_total_count' . $browse->id); ?></th>
            <?php
            } ?>
            <?php if (AmpConfig::get('show_skipped_times')) {
                ++$thcount; ?>
            <th class="<?php echo $cel_counter; ?> optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=total_skip' . $argument_param, T_('Skipped'), 'song_sort_total_skip' . $browse->id); ?></th>
            <?php
            } ?>
            <?php if ($show_ratings) {
                ++$thcount; ?>
            <th class="cel_ratings optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=rating' . $argument_param, T_('Rating'), 'song_sort_rating'); ?></th>
                <?php if (AmpConfig::get('ratings')) {
                    Rating::build_cache('song', $object_ids);
                    Userflag::build_cache('song', $object_ids);
                } ?>
                <?php
            } ?>
            <th class="cel_action essential"><?php echo T_('Action'); ?></th>

            <?php if (isset($argument) && $argument && !$hide_drag) {
                ++$thcount; ?>
                <th class="cel_drag essential"></th>
            <?php
            } ?>
        </tr>
    </thead>
    <tbody id="sortableplaylist_<?php echo $browse->get_filter('album'); ?>">
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
                <?php if ($libitem->enabled || Access::check(AccessTypeEnum::INTERFACE, AccessLevelEnum::CONTENT_MANAGER)) {
                    $content = $talFactory->createTalView()
                        ->setContext('USER_IS_REGISTERED', User::is_registered())
                        ->setContext('USING_RATINGS', User::is_registered() && (AmpConfig::get('ratings')))
                        ->setContext('SONG', $guiFactory->createSongViewAdapter($gatekeeper, $libitem))
                        ->setContext('CONFIG', $guiFactory->createConfigViewAdapter())
                        ->setContext('ARGUMENT_PARAM', $argument_param)
                        ->setContext('IS_TABLE_VIEW', $is_table)
                        ->setContext('IS_ALBUM_GROUP', $is_group)
                        ->setContext('IS_SHOW_TRACK', $show_track)
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
            <td colspan="<?php echo $thcount; ?>"><span class="nodata"><?php echo T_('No song found'); ?></span></td>
        </tr>
    <?php } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_play"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=track' . $argument_param, '#', 'song_sort_track' . $browse->id); ?></th>
            <th class="<?php echo $cel_song; ?>"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=title' . $argument_param, T_('Song Title'), 'song_sort_title' . $browse->id); ?></th>
            <th class="cel_add"></th>
            <?php if (!$hide_artist) { ?>
            <th class="<?php echo $cel_artist; ?>"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=artist' . $argument_param, T_('Song Artist'), 'song_sort_artist' . $browse->id); ?></th>
            <?php } ?>
            <?php if (!$hide_album) { ?>
                <th class="<?php echo $cel_album; ?>"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=album' . $argument_param, T_('Album'), 'song_sort_album' . $browse->id); ?></th>
            <?php } ?>
            <?php if (!$hide_genres) { ?>
            <th class="<?php echo $cel_tags; ?>"><?php echo T_('Genres'); ?></th>
            <?php } ?>
            <th class="<?php echo $cel_time; ?>"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=time' . $argument_param, T_('Time'), 'song_sort_time' . $browse->id); ?></th>
            <?php if ($show_license) { ?>
            <th class="<?php echo $cel_license; ?>"><?php echo T_('License'); ?></th>
            <?php } ?>
            <?php if (AmpConfig::get('show_played_times')) { ?>
            <th class="<?php echo $cel_counter; ?> optional"><?php echo T_('Played'); ?></th>
            <?php } ?>
            <?php if (AmpConfig::get('show_skipped_times')) { ?>
            <th class="<?php echo $cel_counter; ?> optional"><?php echo T_('Skipped'); ?></th>
            <?php } ?>
            <?php if ($show_ratings) { ?>
            <th class="cel_ratings optional"><?php echo T_('Rating'); ?></th>
            <?php } ?>
            <th class="cel_action"></th>
            <?php if (isset($argument) && $argument && !$hide_drag) { ?>
            <th class="cel_drag"></th>
            <?php } ?>
        </tr>
    </tfoot>
</table>

<?php show_table_render($argument ?? false); ?>
<?php if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
