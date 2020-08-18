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

/**
 * Sub-Ajax page, requires AJAX_INCLUDE
 */
if (!defined('AJAX_INCLUDE')) {
    return false;
}

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'flip_state':
        if (!Access::check('interface', 75)) {
            debug_event('catalog.ajax', Core::get_global('user')->username . ' attempted to change the state of a catalog', 1);

            return false;
        }

        $catalog     = Catalog::create_from_id($_REQUEST['catalog_id']);
        $new_enabled = $catalog->enabled ? '0' : '1';
        Catalog::update_enabled($new_enabled, $catalog->id);
        $catalog->enabled = (int) $new_enabled;
        $catalog->format();

        // Return the new Ajax::button
        $id  = 'button_flip_state_' . $catalog->id;
        if ($catalog->enabled) {
            $button     = 'disable';
            $buttontext = T_('Disable');
        } else {
            $button     = 'enable';
            $buttontext = T_('Enable');
        }
        $results[$id] = Ajax::button('?page=catalog&action=flip_state&catalog_id=' . $catalog->id, $button, $buttontext, 'flip_state_' . $catalog->id);

        break;
    default:
        $results['rfc3514'] = '0x1';
        break;
} // switch on action;

// We always do this
echo (string) xoutput_from_array($results);
