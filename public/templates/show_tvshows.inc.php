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
use Ampache\Repository\Model\Rating;
use Ampache\Repository\Model\TvShow;
use Ampache\Repository\Model\User;
use Ampache\Repository\Model\Userflag;
use Ampache\Module\Api\Ajax;
use Ampache\Module\Util\Ui;

/** @var Ampache\Repository\Model\Browse $browse */
/** @var array $object_ids */

session_start();

$web_path = AmpConfig::get('web_path');
$thcount  = 9;
$is_table = $browse->is_grid_view();
//mashup and grid view need different css
$cel_cover = ($is_table) ? "cel_cover" : 'grid_cover';
$cel_tags  = ($is_table) ? "cel_tags" : 'grid_tags';?>
<?php if ($browse->is_show_header()) {
    require Ui::find_template('list_header.inc.php');
} ?>
<table class="tabledata striped-rows <?php echo $browse->get_css_class() ?>" data-objecttype="tvshow">
    <thead>
        <tr class="th-top">
            <th class="cel_play essential"></th>
            <th class="<?php echo $cel_cover; ?>"><?php echo T_('Art'); ?></th>
            <th class="cel_tvshow essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=tvshow&sort=name', T_('TV Show'), 'tvshow_sort_name'); ?></th>
            <th class="cel_episodes optional"><?php echo T_('Episodes');  ?></th>
            <th class="cel_seasons optional"><?php echo T_('Seasons'); ?></th>
            <th class="<?php echo $cel_tags; ?> optional"><?php echo T_('Genres'); ?></th>
            <?php if ($show_ratings) {
    ++$thcount; ?>
                <th class="cel_ratings optional"><?php echo T_('Rating'); ?></th>
                <?php
} ?>
            <th class="cel_action essential"><?php echo T_('Action'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Cache the ratings we are going to use
        if (AmpConfig::get('ratings')) {
            Rating::build_cache('tvshow', $object_ids);
        }
        if (AmpConfig::get('userflags')) {
            Userflag::build_cache('tvshow', $object_ids);
        }

        /* Foreach through every tv show that has been passed to us */
        foreach ($object_ids as $tvshow_id) {
            $libitem = new TVShow($tvshow_id);
            $libitem->format(); ?>
        <tr id="tvshow_<?php echo $libitem->id; ?>">
            <?php require Ui::find_template('show_tvshow_row.inc.php'); ?>
        </tr>
        <?php
        } //end foreach?>
        <?php if (!count($object_ids)) { ?>
        <tr>
            <td colspan="<?php echo $thcount; ?>"><span class="nodata"><?php echo T_('No TV show found'); ?></span></td>
        </tr>
        <?php
        } ?>
    </tbody>
    <tfoot>
        <tr class="th-bottom">
            <th class="cel_play essential"></th>
            <th class="<?php echo $cel_cover; ?>"><?php echo T_('Art'); ?></th>
            <th class="cel_tvshow essential persist"><?php echo Ajax::text('?page=browse&action=set_sort&browse_id=' . $browse->id . '&type=tvshow&sort=name', T_('TV Show'), 'tvshow_sort_name'); ?></th>
            <th class="cel_episodes optional"><?php echo T_('Episodes');  ?></th>
            <th class="cel_seasons optional"><?php echo T_('Seasons'); ?></th>
            <th class="<?php echo $cel_tags; ?> optional"><?php echo T_('Genres'); ?></th>
            <?php if ($show_ratings) { ?>
                <th class="cel_ratings optional"><?php echo T_('Rating'); ?></th>
                <?php
            } ?>
            <th class="cel_action essential"> <?php echo T_('Action'); ?> </th>
        </tr>
    </tfoot>
</table>
<?php show_table_render(); ?>
<?php if ($browse->is_show_header()) {
                require Ui::find_template('list_header.inc.php');
            } ?>
