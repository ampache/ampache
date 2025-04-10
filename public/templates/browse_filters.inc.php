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

use Ampache\Module\Api\Ajax;
use Ampache\Module\System\Core;
use Ampache\Module\System\Dba;
use Ampache\Repository\Model\Browse;
use Ampache\Repository\Model\User;

/** @var Browse $browse */

if (!Core::is_session_started()) {
    session_start();
}
$browse_type     = $browse->get_type();
$browse_filters  = Browse::get_allowed_filters($browse_type);
$allowed_filters = ['starts_with', 'minimum_count', 'rated', 'unplayed', 'playlist_type', 'object_type', 'catalog', 'show_art'];
if (!empty($browse_filters) && !empty(array_intersect($browse_filters, $allowed_filters))) { ?>
<li>
    <h4><?php echo T_('Filters'); ?></h4>
    <div class="sb3">
    <?php if (in_array('starts_with', $browse_filters) && array_key_exists('catalog', $_SESSION)) {
        $browse->set_catalog($_SESSION['catalog']); ?>
        <form id="multi_alpha_filter_form" action="javascript:void(0);">
            <label id="multi_alpha_filterLabel" for="multi_alpha_filter"><?php echo T_('Starts With'); ?></label>
            <input type="text" id="multi_alpha_filter" name="multi_alpha_filter" value="<?php echo scrub_out((string)$browse->get_filter('starts_with')); ?>" onBlur="delayRun(this, '400', 'ajaxState', '<?php echo Ajax::url('?page=browse&action=browse&browse_id=' . $browse->id . '&key=starts_with'); ?>', 'multi_alpha_filter');">
        </form>
    <?php }
    if (in_array('minimum_count', $browse_filters)) { ?>
        <input id="mincountCB" type="checkbox" value="1" />
        <label id="mincountLabel" for="mincountCB"><?php echo T_('Minimum Count'); ?></label><br />
        <?php echo Ajax::observe('mincountCB', 'click', Ajax::action('?page=browse&action=browse&browse_id=' . $browse->id . '&key=min_count&value=1', ''));
    }
    if (in_array('rated', $browse_filters)) { ?>
        <input id="ratedCB" type="checkbox" value="1" />
        <label id="ratedLabel" for="ratedCB"><?php echo T_('Rated'); ?></label><br />
        <?php echo Ajax::observe('ratedCB', 'click', Ajax::action('?page=browse&action=browse&browse_id=' . $browse->id . '&key=rated&value=1', ''));
    }
    if (in_array('unplayed', $browse_filters)) { ?>
        <input id="unplayedCB" type="checkbox" <?php echo ($browse->get_filter('unplayed')) ? 'checked="checked"' : ''; ?>/>
        <label id="unplayedLabel" for="unplayedCB"><?php echo T_('Unplayed'); ?></label><br />
        <?php echo Ajax::observe('unplayedCB', 'click', Ajax::action('?page=browse&action=browse&browse_id=' . $browse->id . '&key=unplayed&value=1', ''));
    }
    if (in_array('playlist_type', $browse_filters)) { ?>
        <input id="show_allplCB" type="checkbox" value="1" <?php echo (bool)($browse->get_filter('playlist_type')) ? 'checked="checked"' : ''; ?>/>
        <label id="show_allplLabel" for="show_allplCB"><?php echo T_('All Playlists'); ?></label><br />
        <?php echo Ajax::observe('show_allplCB', 'click', Ajax::action('?page=browse&action=browse&browse_id=' . $browse->id . '&key=playlist_type&value=1', ''));
    }
    if (in_array('catalog', $browse_filters)) { ?>
        <form method="post" id="catalog_choice" action="javascript.void(0);">
            <label id="catalogLabel" for="catalog_select"><?php echo T_('Catalog'); ?></label><br />
            <select id="catalog_select" name="catalog_key">
                <option value="0"><?php echo T_('All'); ?></option>
                <?php $results = [];
        $catalogs              = implode(',', User::get_user_catalogs($_SESSION['userdata']['uid']));
        if (!empty($catalogs)) {
            // Only show the catalogs this user is allowed to access
            $sql        = 'SELECT `id`, `name` FROM `catalog` WHERE `id` IN (' . $catalogs . ') ORDER BY `name`';
            $db_results = Dba::read($sql);
            while ($data = Dba::fetch_assoc($db_results)) {
                $results[] = $data;
            }
        }
        foreach ($results as $entries) {
            echo '<option value="' . $entries['id'] . '" ';
            if (array_key_exists('catalog', $_SESSION) && $_SESSION['catalog'] == $entries['id']) {
                echo ' selected="selected" ';
            }
            echo '>' . $entries['name'] . '</options>';
        } ?>
            </select>
        <?php echo Ajax::observe('catalog_select', 'change', Ajax::action('?page=browse&action=browse&browse_id=' . $browse->id, 'catalog_select', 'catalog_choice')); ?>
        </form>
    <?php }
    if (in_array('show_art', $browse_filters)) { ?>
        <?php echo T_('Toggle Artwork'); ?>&nbsp;<input id="show_artCB" type="checkbox" checked="checked"/>
        <?php echo Ajax::observe('show_artCB', 'click', Ajax::action('?page=browse&action=show_art&browse_id=' . $browse->id, ''));
    } ?>
    </div>
</li>
<?php } ?>
