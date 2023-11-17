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
use Ampache\Repository\Model\Album;
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Authorization\Access;
use Ampache\Module\Authorization\GatekeeperFactoryInterface;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Util\Ui;

/** @var Ampache\Repository\Model\Browse $browse */
/** @var array $object_ids */
/** @var string $limit_threshold */
/** @var bool $group_release */

$web_path          = (string)AmpConfig::get('web_path', '');
$access25          = Access::check('interface', 25);
$show_playlist_add = $access25;
$show_direct_play  = AmpConfig::get('directplay');
$directplay_limit  = AmpConfig::get('direct_play_limit', 0);
// album_row data and options
$thcount           = 9;
$show_ratings      = User::is_registered() && (AmpConfig::get('ratings'));
$original_year     = AmpConfig::get('use_original_year');
$hide_genres       = AmpConfig::get('hide_genres');
$show_played_times = AmpConfig::get('show_played_times');
$is_table          = $browse->is_grid_view();
$year_sort         = ($original_year) ? "&sort=original_year" : "&sort=year";
// translate once
$album_text  = T_('Album');
$artist_text = T_('Album Artist');
$songs_text  = T_('Songs');
$year_text   = T_('Year');
$count_text  = T_('Played');
$genres_text = T_('Genres');
$rating_text = T_('Rating');
$action_text = T_('Actions');
// mashup and grid view need different css
$cel_cover   = ($is_table) ? "cel_cover" : 'grid_cover';
$cel_album   = ($is_table) ? "cel_album" : 'grid_album';
$cel_artist  = ($is_table) ? "cel_artist" : 'grid_artist';
$cel_tags    = ($is_table) ? "cel_tags" : 'grid_tags';
$cel_counter = ($is_table) ? "cel_counter" : 'grid_counter';
$album_link  = Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=name', $album_text, 'album_sort_name');
$artist_link = Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=album_artist', $artist_text, 'album_sort_artist');
$songs_link  = Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=song_count', $songs_text, 'album_sort_song_count');
$year_link   = Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . $year_sort, $year_text, 'album_sort_year');
$count_link  = Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=total_count', $count_text, 'album_sort_total_count');
$rating_link = Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&sort=rating', $rating_text, 'album_sort_rating');

if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
<table class="tabledata striped-rows <?php echo $browse->get_css_class(); ?>" data-objecttype="album">
    <thead>
        <tr class="th-top">
        <div class="libitem_menu">
            <th class="cel_play essential"></th>
            <th class="<?php echo $cel_cover; ?> optional"><?php echo T_('Art'); ?></th>
</div>
            <th class="<?php echo $cel_album; ?> essential persist"><?php echo $album_link; ?></th>
            <th class="cel_add essential"></th>
            <th class="<?php echo $cel_artist; ?> essential"><?php echo $artist_link; ?></th>
            <th class="cel_songs optional"><?php echo $songs_link; ?></th>
            <th class="cel_year essential"><?php echo $year_link; ?></th>
            <?php if ($show_played_times) { ?>
            <th class="<?php echo $cel_counter; ?> optional"><?php echo $count_link; ?></th>
            <?php } ?>
            <?php if (!$hide_genres) {
                ++$thcount; ?>
            <th class="<?php echo $cel_tags; ?> optional"><?php echo $genres_text; ?></th>
            <?php
            } ?>
            <?php if ($show_ratings) {
                ++$thcount; ?>
                <th class="cel_ratings optional"><?php echo $rating_link; ?></th>
                <?php
            } ?>
            <th class="cel_action essential"><?php echo $action_text; ?></th>
        </tr>
    </thead>
    <tbody>
        <?php global $dic;
$talFactory = $dic->get(TalFactoryInterface::class);
$guiFactory = $dic->get(GuiFactoryInterface::class);
$gatekeeper = $dic->get(GatekeeperFactoryInterface::class)->createGuiGatekeeper();

if (AmpConfig::get('ratings')) {
    Rating::build_cache('album', $object_ids);
    Userflag::build_cache('album', $object_ids);
}
/* Foreach through the albums */
foreach ($object_ids as $album_id) {
    $libitem = new Album($album_id);
    $libitem->format(true, $limit_threshold);
    if ($directplay_limit > 0) {
        $show_playlist_add = $access25 && ($libitem->song_count <= $directplay_limit);
    } ?>
        <tr id="album_<?php echo $libitem->id; ?>" class="libitem_menu">
            <?php $content = $talFactory->createTalView()
            ->setContext('USER_IS_REGISTERED', User::is_registered())
            ->setContext('USING_RATINGS', User::is_registered() && (AmpConfig::get('ratings')))
            ->setContext('ALBUM', $guiFactory->createAlbumViewAdapter($gatekeeper, $browse, $libitem))
            ->setContext('CONFIG', $guiFactory->createConfigViewAdapter())
            ->setContext('IS_TABLE_VIEW', $is_table)
            ->setContext('IS_HIDE_GENRE', $hide_genres)
            ->setContext('IS_SHOW_PLAYED_TIMES', $show_played_times)
            ->setContext('IS_SHOW_PLAYLIST_ADD', $show_playlist_add)
            ->setContext('CLASS_COVER', $cel_cover)
            ->setContext('CLASS_ALBUM', $cel_album)
            ->setContext('CLASS_ARTIST', $cel_artist)
            ->setContext('CLASS_TAGS', $cel_tags)
            ->setContext('CLASS_COUNTER', $cel_counter)
            ->setTemplate('album_row.xhtml')
            ->render();

    echo $content; ?>
        </tr>
        <?php
} ?>
        <?php if (!count($object_ids)) { ?>
        <tr>
            <td colspan="<?php echo $thcount; ?>"><span class="nodata"><?php echo T_('No Album found'); ?></span></td>
        </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_play"></th>
            <th class="<?php echo $cel_cover; ?>"><?php echo T_('Art'); ?></th>
            <th class="<?php echo $cel_album; ?>"><?php echo $album_text; ?></th>
            <th class="cel_add"></th>
            <th class="<?php echo $cel_artist; ?>"><?php echo $artist_text; ?></th>
            <th class="cel_songs"><?php echo $songs_text; ?></th>
            <th class="cel_year"><?php echo $year_text; ?></th>
            <?php if ($show_played_times) { ?>
            <th class="<?php echo $cel_counter; ?> optional"><?php echo $count_text; ?></th>
            <?php } ?>
            <?php if (!$hide_genres) { ?>
            <th class="<?php echo $cel_tags; ?>"><?php echo $genres_text; ?></th>
            <?php } ?>
            <?php if ($show_ratings) { ?>
                <th class="cel_ratings optional"><?php echo $rating_text; ?></th>
                <?php } ?>
            <th class="cel_action"><?php echo $action_text; ?></th>
        </tr>
    <tfoot>
</table>

<?php show_table_render(); ?>
<?php if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
