<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

// show_genres.inc.php

use Ampache\Config\AmpConfig;
use Ampache\Module\Database\Query\Browse;
use Ampache\Module\Util\Ui;
use Ampache\Repository\Model\Tag;

/** @var Browse $browse */
/** @var list<int> $object_ids */

$show_video = (bool) AmpConfig::get('allow_video');
$thcount    = ($show_video) ? 9 : 8;
// Translations
$t_art     = T_('Art');
$t_genre   = T_('Genre');
$t_songs   = T_('Songs');
$t_albums  = T_('Albums');
$t_artists = T_('Artists');
$t_videos  = T_('Videos');
$t_action  = T_('Action');
// The rows keep the order they arrive in; a curated list is the reason this view exists
if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
<table class="tabledata striped-rows" data-objecttype="tag">
    <thead>
        <tr class="th-top">
            <th class="cel_play essential"></th>
            <th class="cel_cover optional"><?php echo $t_art; ?></th>
            <th class="cel_genre essential persist"><?php echo $t_genre; ?></th>
            <th class="cel_add_list essential"></th>
            <th class="cel_songs optional"><?php echo $t_songs; ?></th>
            <th class="cel_albums optional"><?php echo $t_albums; ?></th>
            <th class="cel_artists optional"><?php echo $t_artists; ?></th>
            <?php if ($show_video) { ?>
            <th class="cel_videos optional"><?php echo $t_videos; ?></th>
            <?php } ?>
            <th class="cel_action essential"><?php echo $t_action; ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($object_ids as $genre_id) {
            $libitem = new Tag((int) $genre_id);
            if ($libitem->isNew()) {
                continue;
            } ?>
        <tr id="genre_<?php echo $libitem->id; ?>">
            <?php require Ui::find_template('show_genre_row.inc.php'); ?>
        </tr>
        <?php } ?>
        <?php if (!count($object_ids)) { ?>
        <tr>
            <td colspan="<?php echo $thcount; ?>"><span class="nodata"><?php echo T_('No Genre found'); ?></span></td>
        </tr>
        <?php } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_play essential"></th>
            <th class="cel_cover optional"><?php echo $t_art; ?></th>
            <th class="cel_genre essential persist"><?php echo $t_genre; ?></th>
            <th class="cel_add_list essential"></th>
            <th class="cel_songs optional"><?php echo $t_songs; ?></th>
            <th class="cel_albums optional"><?php echo $t_albums; ?></th>
            <th class="cel_artists optional"><?php echo $t_artists; ?></th>
            <?php if ($show_video) { ?>
            <th class="cel_videos optional"><?php echo $t_videos; ?></th>
            <?php } ?>
            <th class="cel_action essential"><?php echo $t_action; ?></th>
        </tr>
    </tfoot>
</table>

<?php show_table_render(); ?>
<?php if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
