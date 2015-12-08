<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Sub-Ajax page, requires AJAX_INCLUDE
 */
if (!defined('AJAX_INCLUDE')) {
    exit;
}

switch ($_REQUEST['action']) {
    case 'flip_state':
        if (!Access::check('interface', '75')) {
            debug_event('DENIED', $GLOBALS['user']->username . ' attempted to change the state of a catalog', '1');
            exit;
        }

        $catalog     = Catalog::create_from_id($_REQUEST['catalog_id']);
        $new_enabled = $catalog->enabled ? false : true;
        $catalog->update_enabled($new_enabled, $catalog->id);
        $catalog->enabled = $new_enabled;
        $catalog->format();

        //Return the new Ajax::button
        $id           = 'button_flip_state_' . $catalog->id;
        $button       = $catalog->enabled ? 'disable' : 'enable';
        $results[$id] = Ajax::button('?page=catalog&action=flip_state&catalog_id=' . $catalog->id, $button, T_(ucfirst($button)), 'flip_state_' . $catalog->id);

    break;
    default:
        $results['rfc3514'] = '0x1';
    break;
} // switch on action;

// We always do this
echo xoutput_from_array($results);
