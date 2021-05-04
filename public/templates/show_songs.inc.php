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
use Ampache\Gui\GuiFactoryInterface;
use Ampache\Gui\TalFactoryInterface;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\Song;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\Util\Ui;

$web_path = AmpConfig::get('web_path');
$thcount  = 8;
$is_table = $browse->is_grid_view();
//mashup and grid view need different css
$cel_song    = ($is_table) ? "cel_song" : 'grid_song';
$cel_album   = ($is_table) ? "cel_album" : 'grid_album';
$cel_artist  = ($is_table) ? "cel_artist" : 'grid_artist';
$cel_tags    = ($is_table) ? "cel_tags" : 'grid_tags';
$cel_flag    = ($is_table) ? "cel_userflag" : 'grid_userflag';
$cel_time    = ($is_table) ? "cel_time" : 'grid_time';
$cel_license = ($is_table) ? "cel_license" : 'grid_license';
$cel_counter = ($is_table) ? "cel_counter" : 'grid_counter'; ?>
<?php if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
<table id="reorder_songs_table_<?php echo $browse->get_filter('album'); ?>" class="tabledata striped-rows <?php echo $browse->get_css_class() ?>" data-objecttype="song" data-offset="<?php echo $browse->get_start(); ?>">
    <thead>
        <tr class="th-top">
            <th class="cel_play essential"></th>
            <th class="<?php echo $cel_song; ?> essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=title' . $argument_param, T_('Song Title'), 'sort_song_title' . $browse->id); ?></th>
            <th class="cel_add essential"></th>
            <th class="<?php echo $cel_artist; ?> optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=artist' . $argument_param, T_('Song Artist'), 'sort_song_artist' . $browse->id); ?></th>
            <th class="<?php echo $cel_album; ?> essential"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=album' . $argument_param, T_('Album'), 'sort_song_album' . $browse->id); ?></th>
            <th class="cel_year"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=year', T_('Year'), 'album_sort_year_bottom'); ?></th>
            <th class="<?php echo $cel_tags; ?> optional"><?php echo T_('Genres'); ?></th>
            <th class="<?php echo $cel_time; ?> optional"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=time' . $argument_param, T_('Time'), 'sort_song_time' . $browse->id); ?></th>
            <?php if (AmpConfig::get('licensing')) { ?>
            <th class="<?php echo $cel_license; ?> optional"><?php echo T_('License'); ?></th>
            <?php
} ?>
            <?php if (AmpConfig::get('show_played_times')) { ?>
            <th class="<?php echo $cel_counter; ?> optional"><?php echo T_('# Played'); ?></th>
            <?php } ?>
            <?php if (AmpConfig::get('show_skipped_times')) { ?>
            <th class="<?php echo $cel_counter; ?> optional"><?php echo T_('# Skipped'); ?></th>
            <?php
    } ?>
            <?php if (User::is_registered()) { ?>
                <?php if (AmpConfig::get('ratings')) {
        ++$thcount;
        Rating::build_cache('song', $object_ids); ?>
                    <th class="cel_rating optional"><?php echo T_('Rating'); ?></th>
                <?php
    } ?>
                <?php if (AmpConfig::get('userflags')) {
        ++$thcount;
        Userflag::build_cache('song', $object_ids); ?>
                <th class="<?php echo $cel_flag; ?> optional"><?php echo T_('Fav.'); ?></th>
            <?php
    } ?>
                <?php
    } ?>
                <th class="cel_action essential"><?php echo T_('Action'); ?></th>
            <?php if (isset($argument) && $argument) {
        ++$thcount; ?>
                <th class="cel_drag essential"></th>
            <?php
    } ?>
        </tr>
    </thead>
    <tbody id="sortableplaylist_<?php echo $browse->get_filter('album'); ?>">
        <?php
            global $dic;
            $talFactory = $dic->get(TalFactoryInterface::class);
            $guiFactory = $dic->get(GuiFactoryInterface::class);
            $gatekeeper = $dic->get(GatekeeperFactoryInterface::class)->createGuiGatekeeper();

            foreach ($object_ids as $song_id) {
                $libitem = new Song($song_id, $limit_threshold);
                $libitem->format(); ?>
            <tr id="song_<?php echo $libitem->id; ?>">
                <?php
                if ($libitem->enabled || Access::check('interface', 50)) {
                    $content = $talFactory->createTalView()
                        ->setContext('BROWSE_ARGUMENT', isset($argument) ? $argument : '')
                        ->setContext('USER_IS_REGISTERED', User::is_registered())
                        ->setContext('SONG', $guiFactory->createSongViewAdapter($gatekeeper, $libitem))
                        ->setContext('CONFIG', $guiFactory->createConfigViewAdapter())
                        ->setContext('IS_TABLE_VIEW', $is_table)
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
    <?php
            } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_play"></th>
            <th class="<?php echo $cel_song; ?>"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=title' . $argument_param, T_('Song Title'), 'sort_song_title' . $browse->id); ?></th>
            <th class="cel_add"></th>
            <th class="<?php echo $cel_artist; ?>"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=artist' . $argument_param, T_('Song Artist'), 'sort_song_artist' . $browse->id); ?></th>
            <th class="<?php echo $cel_album; ?>"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=album' . $argument_param, T_('Album'), 'sort_song_album' . $browse->id); ?></th>
            <th class="<?php echo $cel_tags; ?>"><?php echo T_('Genres'); ?></th>
            <th class="<?php echo $cel_time; ?>"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=time' . $argument_param, T_('Time'), 'sort_song_time' . $browse->id); ?></th>
            <?php if (AmpConfig::get('licensing')) { ?>
            <th class="<?php echo $cel_license; ?>"><?php echo T_('License'); ?></th>
            <?php
            } ?>
            <?php if (AmpConfig::get('show_played_times')) { ?>
            <th class="<?php echo $cel_counter; ?> optional"><?php echo T_('# Played'); ?></th>
            <?php } ?>
            <?php if (AmpConfig::get('show_skipped_times')) { ?>
            <th class="<?php echo $cel_counter; ?> optional"><?php echo T_('# Skipped'); ?></th>
            <?php } ?>
            <?php if (User::is_registered()) { ?>
                <?php if (AmpConfig::get('ratings')) { ?>
                    <th class="cel_rating"><?php echo T_('Rating'); ?></th>
                <?php
                } ?>
                <?php if (AmpConfig::get('userflags')) { ?>
                    <th class="<?php echo $cel_flag; ?>"></th>
                <?php
                } ?>
            <?php
            } ?>
                <th class="cel_action"></th>
            <?php if (isset($argument) && $argument) { ?>
                <th class="cel_drag"></th>
            <?php
            } ?>
        </tr>
    </tfoot>
</table>

<?php show_table_render($argument); ?>
<?php if ($browse->is_show_header()) {
                require Ui::find_template('list_header.inc.php');
            } ?>
