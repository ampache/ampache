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

$embed = $_REQUEST['embed'];

require_once AmpConfig::get('prefix') . '/templates/show_html5_player_headers.inc.php';

if (empty($embed)) {
    UI::show_box_top(T_('Shared on') . ' ' . AmpConfig::get('site_title'), 'box box_share');
    echo T_('by') . ' ' . $share->f_user . '<br />';
    echo "<a href='" . $share->public_url . "'>" . $share->public_url . "</a><br />";
    echo "<br /><br />";

    if ($share->allow_download) {
        echo "<a href=\"" . AmpConfig::get('web_path') . "/share.php?action=download&id=" . $share->id . "&secret=" . $share->secret . "\">" . UI::get_icon('download', T_('Download')) . "</a> ";
        echo "<a href=\"" . AmpConfig::get('web_path') . "/share.php?action=download&id=" . $share->id . "&secret=" . $share->secret . "\">" . T_('Download') . "</a>";
    }
}

$is_share = true;
$iframed = true;
$playlist = $share->create_fake_playlist();
require AmpConfig::get('prefix') . '/templates/show_web_player.inc.php';

if (!empty($embed)) {
    UI::show_box_bottom();
}
