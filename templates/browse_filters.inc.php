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

if (!Core::is_session_started()) {
    session_start();
} ?>

<?php $allowed_filters = Browse::get_allowed_filters($browse->get_type()); ?>
<li>
    <h4><?php echo T_('Filters'); ?></h4>
    <div class="sb3">
    <?php if (in_array('starts_with', $allowed_filters)) { ?>
        <form id="multi_alpha_filter_form" action="javascript:void(0);">
            <label id="multi_alpha_filterLabel" for="multi_alpha_filter"><?php echo T_('Starts With'); ?></label>
            <input type="text" id="multi_alpha_filter" name="multi_alpha_filter" value="<?php $browse->set_catalog($_SESSION['catalog']);
    echo scrub_out($browse->get_filter('starts_with')); ?>" onKeyUp="delayRun(this, '400', 'ajaxState', '<?php echo Ajax::url('?page=browse&action=browse&browse_id=' . $browse->id . '&key=starts_with'); ?>', 'multi_alpha_filter');">
        </form>
    <?php
} // end if alpha_match?>
    <?php if (in_array('minimum_count', $allowed_filters)) { ?>
        <input id="mincountCB" type="checkbox" value="1" />
        <label id="mincountLabel" for="mincountCB"><?php echo T_('Minimum Count'); ?></label><br />
        <?php echo Ajax::observe('mincountCB', 'click', Ajax::action('?page=browse&action=browse&browse_id=' . $browse->id . '&key=min_count&value=1', '')); ?>
    <?php
    } ?>
    <?php if (in_array('rated', $allowed_filters)) { ?>
        <input id="ratedCB" type="checkbox" value="1" />
        <label id="ratedLabel" for="ratedCB"><?php echo T_('Rated'); ?></label><br />
        <?php echo Ajax::observe('ratedCB', 'click', Ajax::action('?page=browse&action=browse&browse_id=' . $browse->id . '&key=rated&value=1', '')); ?>
    <?php
    } ?>
    <?php if (in_array('unplayed', $allowed_filters)) { ?>
        <input id="unplayedCB" type="checkbox" <?php echo $string = $browse->get_filter('unplayed') ? 'checked="checked"' : ''; ?>/>
        <label id="unplayedLabel" for="unplayedCB"><?php echo T_('Unplayed'); ?></label><br />
    <?php
    } ?>
    <?php if (in_array('playlist_type', $allowed_filters)) { ?>
        <input id="show_allplCB" type="checkbox" <?php echo $string = $browse->get_filter('playlist_type') ? 'checked="checked"' : ''; ?>/>
        <label id="show_allplLabel" for="showallplCB"><?php echo T_('All Playlists'); ?></label><br />
        <?php echo Ajax::observe('show_allplCB', 'click', Ajax::action('?page=browse&action=browse&browse_id=' . $browse->id . '&key=playlist_type&value=1', '')); ?>
    <?php
    } // if playlist_type?>
    <?php if (in_array('object_type', $allowed_filters)) { ?>
        <?php $string     = 'otype_' . $browse->get_filter('object_type');
        ${$string}        = 'selected="selected"'; ?>
        <input id="typeSongRadio" type="radio" name="object_type" value="1" <?php echo $otype_song; ?>/>
        <label id="typeSongLabel" for="typeSongRadio"><?php echo T_('Song Title'); ?></label><br />
        <?php echo Ajax::observe('typeSongRadio', 'click', Ajax::action('?page=tag&action=browse_type&browse_id=' . $browse->id . '&type=song', '')); ?>
        <input id="typeAlbumRadio" type="radio" name="object_type" value="1" />
        <label id="typeAlbumLabel" for="typeAlbumRadio"><?php echo T_('Albums'); ?></label><br />
        <?php echo Ajax::observe('typeAlbumRadio', 'click', Ajax::action('?page=tag&action=browse_type&browse_id=' . $browse->id . '&type=album', '')); ?>
        <input id="typeArtistRadio" type="radio" name="object_type" value="1" />
        <label id="typeArtistLabel" for="typeArtistRadio"><?php echo T_('Artist'); ?></label><br />
        <?php echo Ajax::observe('typeArtistRadio', 'click', Ajax::action('?page=tag&action=browse_type&browse_id=' . $browse->id . '&type=artist', '')); ?>
    <?php
    } ?>

    <?php if (in_array('catalog', $allowed_filters)) { ?>
        <form method="post" id="catalog_choice" action="javascript.void(0);">
            <label id="catalogLabel" for="catalog_select"><?php echo T_('Catalog'); ?></label><br />
            <select id="catalog_select" name="catalog_key">
                <option value="0"><?php echo T_('All'); ?></option>
                <?php
                    $sql     = 'SELECT `id`, `name` FROM `catalog`';
        $db_results          = Dba::read($sql);
        while ($data = Dba::fetch_assoc($db_results)) {
            $results[] = $data;
        }

        foreach ($results as $entries) {
            echo '<option value="' . $entries['id'] . '" ';
            if ($_SESSION['catalog'] == $entries['id']) {
                echo ' selected="selected" ';
            }
            echo '>' . $entries['name'] . '</options>';
        } ?>

            </select>
        <?php echo Ajax::observe('catalog_select', 'change', Ajax::action('?page=browse&action=browse&browse_id=' . $browse->id, 'catalog_select', 'catalog_choice')); ?>
        </form>
    <?php
    } ?>
    <?php if (in_array('show_art', $allowed_filters)) { ?>
        <?php echo T_('Toggle Artwork'); ?>&nbsp;<input id="show_artCB" type="checkbox" <?php echo Art::is_enabled() ? 'checked="checked"' : ''; ?>/>
        <?php echo Ajax::observe('show_artCB', 'click', Ajax::action('?page=browse&action=show_art&browse_id=' . $browse->id, '')); ?>
    <?php
    } // if show_art?>
    </div>
</li>
