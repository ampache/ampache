<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
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
if (!defined('AJAX_INCLUDE')) { exit; }

switch ($_REQUEST['action']) {
    case 'reject':
        if (!Access::check('interface','75')) {
            $results['rfc3514'] = '0x1';
            break;
        }

        // Remove the flag from the table
        $flag = new Flag($_REQUEST['flag_id']);
        $flag->delete();

        $flagged = Flag::get_all();
        ob_start();
        $browse = new Browse();
        $browse->set_type('flagged');
        $browse->set_static_content(true);
        $browse->save_objects($flagged);
        $browse->show_objects($flagged);
        $browse->store();
        $results['browse_content'] = ob_get_contents();
        ob_end_clean();

    break;
    case 'accept':
        if (!Access::check('interface','75')) {
            $results['rfc3514'] = '0x1';
            break;
        }

        $flag = new Flag($_REQUEST['flag_id']);
        $flag->approve();
        $flag->format();
        ob_start();
        require_once Config::get('prefix') . '/templates/show_flag_row.inc.php';
        $results['flagged_' . $flag->id] = ob_get_contents();
        ob_end_clean();

    break;
    default:
        $results['rfc3514'] = '0x1';
    break;
} // switch on action;

// We always do this
echo xml_from_array($results);
?>
