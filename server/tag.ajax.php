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

$results = array();
$action  = Core::get_request('action');

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'show_add_tag':
        break;
    case 'get_tag_map':
        $tags            = Tag::get_display(Tag::get_tags());
        $results['tags'] = $tags;
        break;
    case 'get_labels':
        $labels            = Label::get_display(Label::get_all_labels());
        $results['labels'] = $labels;
        break;
    case 'add_tag':
        if (!Tag::can_edit_tag_map(filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES), filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT), false)) {
            debug_event('tag.ajax', Core::get_global('user')->username . ' attempted to add unauthorized tag map', 1);

            return false;
        }
        debug_event('tag.ajax', 'Adding new tag...', 5);
        Tag::add_tag_map(filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES), (int) filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT), (int) $_GET['tag_id'], false);
        break;
    case 'add_tag_by_name':
        if (!Access::check('interface', 75)) {
            debug_event('tag.ajax', Core::get_global('user')->username . ' attempted to add new tag', 1);

            return false;
        }
        debug_event('tag.ajax', 'Adding new tag by name...', 5);
        Tag::add(filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES), filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT), $_GET['tag_name'], false);
        break;
    case 'delete':
        if (!Access::check('interface', 75)) {
            debug_event('tag.ajax', Core::get_global('user')->username . ' attempted to delete tag', 1);

            return false;
        }
        debug_event('tag.ajax', 'Deleting tag...', 5);
        $tag = new Tag($_GET['tag_id']);
        $tag->delete();
        header('Location: ' . AmpConfig::get('web_path') . '/browse.php?action=tag');

        return false;
    case 'remove_tag_map':
        if (!Tag::can_edit_tag_map(filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES), filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT), false)) {
            debug_event('tag.ajax', Core::get_global('user')->username . ' attempted to delete unauthorized tag map', 1);

            return false;
        }
        debug_event('tag.ajax', 'Removing tag map...', 5);
        $tag = new Tag($_GET['tag_id']);
        $tag->remove_map(filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES), filter_input(INPUT_GET, 'object_id', FILTER_SANITIZE_NUMBER_INT), false);
        break;
    case 'browse_type':
        $browse = new Browse($_GET['browse_id']);
        $browse->set_filter('object_type', filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $browse->store();
        break;
    case 'add_filter':
        $browse = new Browse($_GET['browse_id']);
        $browse->set_filter('tag', $_GET['tag_id']);
        $object_ids = $browse->get_objects();
        ob_start();
        $browse->show_objects($object_ids);
        $results[$browse->get_content_div()] = ob_get_clean();
        $browse->store();
        // Retrieve current objects of type based on combined filters
        break;
    default:
        $results['rfc3514'] = '0x1';
        break;
} // switch on action;


// We always do this
echo (string) xoutput_from_array($results);
