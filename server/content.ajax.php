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

/**
 * Sub-Ajax page, requires AJAX_INCLUDE
 */
if (!defined('AJAX_INCLUDE')) {
    exit;
}

$results = array();
ob_start();

switch ($_REQUEST['subpage']) {
    case 'browse':
        require_once AmpConfig::get('prefix') . '/browse.php';
        break;
    case 'index':
        require_once AmpConfig::get('prefix') . '/templates/show_index.inc.php';
        break;
    case 'democratic':
        require_once AmpConfig::get('prefix') . '/democratic.php';
        break;
    case 'localplay':
        require_once AmpConfig::get('prefix') . '/localplay.php';
        break;
    case 'playlist':
        require_once AmpConfig::get('prefix') . '/playlist.php';
        break;
    case 'random':
        require_once AmpConfig::get('prefix') . '/random.php';
        break;
    case 'stats':
        require_once AmpConfig::get('prefix') . '/stats.php';
        break;
    case 'search':
        require_once AmpConfig::get('prefix') . '/search.php';
        break;
    case 'catalog':
        require_once AmpConfig::get('prefix') . '/admin/catalog.php';
        break;
    case 'users':
        require_once AmpConfig::get('prefix') . '/admin/users.php';
        break;
    case 'access':
        require_once AmpConfig::get('prefix') . '/admin/access.php';
        break;
    case 'system':
        require_once AmpConfig::get('prefix') . '/admin/system.php';
        break;
    case 'export':
        require_once AmpConfig::get('prefix') . '/admin/export.php';
        break;
    case 'shout':
        require_once AmpConfig::get('prefix') . '/admin/shout.php';
        break;
    case 'preferences':
        require_once AmpConfig::get('prefix') . '/preferences.php';
        break;
    case 'modules':
        require_once AmpConfig::get('prefix') . '/admin/modules.php';
        break;
    case 'duplicates':
        require_once AmpConfig::get('prefix') . '/admin/duplicates.php';
        break;
    case 'mail':
        require_once AmpConfig::get('prefix') . '/admin/mail.php';
        break;
    default:
        // Ne rien faire
        break;

} // switch on action

$results[$_REQUEST['page']] = ob_get_clean();
ob_end_clean();

// We always do this
echo xoutput_from_array($results);
