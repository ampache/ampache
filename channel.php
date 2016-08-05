<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2016 Ampache.org
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

require_once 'lib/init.php';

if (!AmpConfig::get('channel')) {
    UI::access_denied();
    exit;
}

UI::show_header();

/* Switch on the action passed in */
switch ($_REQUEST['action']) {
    case 'show_create':
        $type = Channel::format_type($_REQUEST['type']);
        if (!empty($type) && !empty($_REQUEST['id'])) {
            $object = new $type($_REQUEST['id']);
            if ($object->id) {
                $object->format();
                require_once AmpConfig::get('prefix') . UI::find_template('show_add_channel.inc.php');
            }
        }
        UI::show_footer();
        exit;
    case 'create':
        if (AmpConfig::get('demo_mode')) {
            UI::access_denied();
            exit;
        }

        if (!Core::form_verify('add_channel', 'post')) {
            UI::access_denied();
            exit;
        }

        $created = Channel::create($_REQUEST['name'], $_REQUEST['description'], $_REQUEST['url'], $_REQUEST['type'], $_REQUEST['id'], $_REQUEST['interface'], $_REQUEST['port'], $_REQUEST['admin_password'], $_REQUEST['private'] ?: 0, $_REQUEST['max_listeners'], $_REQUEST['random'] ?: 0, $_REQUEST['loop'] ?: 0, $_REQUEST['stream_type'], $_REQUEST['bitrate']);

        if (!$created) {
            require_once AmpConfig::get('prefix') . UI::find_template('show_add_channel.inc.php');
        } else {
            $title = T_('Channel Created');
            show_confirmation($title, $body, AmpConfig::get('web_path') . '/browse.php?action=channel');
        }
        UI::show_footer();
        exit;
    case 'show_delete':
        $id = $_REQUEST['id'];

        $next_url = AmpConfig::get('web_path') . '/channel.php?action=delete&id=' . scrub_out($id);
        show_confirmation(T_('Channel Delete'), T_('Confirm Deletion Request'), $next_url, 1, 'delete_channel');
        UI::show_footer();
        exit;
    case 'delete':
        if (AmpConfig::get('demo_mode')) {
            UI::access_denied();
            exit;
        }

        $id      = $_REQUEST['id'];
        $channel = new Channel($id);
        if ($channel->delete()) {
            $next_url = AmpConfig::get('web_path') . '/browse.php?action=channel';
            show_confirmation(T_('Channel Deleted'), T_('The Channel has been deleted'), $next_url);
        }
        UI::show_footer();
        exit;
} // switch on the action

UI::show_footer();
