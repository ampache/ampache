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
if (!defined('AJAX_INCLUDE')) { exit; }

$results = array();
switch ($_REQUEST['action']) {
    case 'show_add_tag':

    break;
    case 'get_tag_map':
        $tags = Tag::get_display(Tag::get_tags());
        $results['tags'] = $tags;
    break;
    case 'add_tag':
        debug_event('tag.ajax', 'Adding new tag...', '5');
        Tag::add_tag_map($_GET['type'],$_GET['object_id'],$_GET['tag_id']);
    break;
    case 'add_tag_by_name':
        debug_event('tag.ajax', 'Adding new tag by name...', '5');
        Tag::add($_GET['type'],$_GET['object_id'],$_GET['tag_name'], false);
    break;
    case 'delete':
        debug_event('tag.ajax', 'Deleting tag...', '5');
        $tag = new Tag($_GET['tag_id']);
        $tag->delete();
        header('Location: ' . AmpConfig::get('web_path') . '/browse.php?action=tag');
        exit;
    case 'remove_tag_map':
        debug_event('tag.ajax', 'Removing tag map...', '5');
        $tag = new Tag($_GET['tag_id']);
        $tag->remove_map($_GET['type'],$_GET['object_id']);
    break;
    case 'browse_type':
        $browse = new Browse($_GET['browse_id']);
        $browse->set_filter('object_type', $_GET['type']);
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
echo xoutput_from_array($results);
