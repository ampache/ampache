<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2014 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

$web_path = AmpConfig::get('web_path');
?>
<?php
UI::show_box_top($wartist['name'], 'info-box');
?>
<?php
if (AmpConfig::get('lastfm_api_key')) {
    echo Ajax::observe('window', 'load', Ajax::action('?page=index&action=artist_info&fullname=' . rawurlencode($wartist['name']), 'artist_info'));
?>
    <div id="artist_biography">
        <?php echo T_('Loading...'); ?>
    </div>
<?php } ?>
<?php UI::show_box_bottom(); ?>

<?php
if (AmpConfig::get('wanted')) {
    echo Ajax::observe('window', 'load', Ajax::action('?page=index&action=wanted_missing_albums&artist_mbid='.$wartist['mbid'], 'missing_albums'));
?>
    <div id="missing_albums"></div>
<?php } ?>
