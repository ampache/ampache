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

$web_path = AmpConfig::get('web_path'); ?>
<?php
UI::show_box_top($wartist['name'], 'info-box'); ?>
<?php
if (AmpConfig::get('lastfm_api_key')) {
    echo Ajax::observe('window', 'load', Ajax::action('?page=index&action=artist_info&fullname=' . rawurlencode($wartist['name']), 'artist_info')); ?>
    <div id="artist_biography">
        <?php echo T_('Loading...'); ?>
    </div>
<?php
} ?>
<?php UI::show_box_bottom(); ?>

<?php
if (AmpConfig::get('wanted')) {
        echo Ajax::observe('window', 'load', Ajax::action('?page=index&action=wanted_missing_albums&artist_mbid=' . $wartist['mbid'], 'missing_albums')); ?>
    <div id="missing_albums"></div>
<?php
    } ?>
