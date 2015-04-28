<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2015 Ampache.org
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

require_once 'lib/init.php';

if (!Access::check('interface','25') || !AmpConfig::get('sociable')) {
    debug_event('UI::access_denied', 'Access Denied: sociable features are not enabled.', '3');
    UI::access_denied();
    exit();
}

UI::show_header();
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

switch ($action) {
    case 'show_add_message':
        if (isset($_REQUEST['reply_to'])) {
            $pvmsg = new PrivateMsg($_REQUEST['reply_to']);
            if ($pvmsg->id && ($pvmsg->from_user === $GLOBALS['user']->id || $pvmsg->to_user === $GLOBALS['user']->id)) {
                $to_user = new User($pvmsg->from_user);
                $_REQUEST['to_user'] = $to_user->username;
                $_REQUEST['subject'] = "RE: " . $pvmsg->subject;
                $_REQUEST['message'] = "\n\n\n---\n> " . str_replace("\n", "\n> ", $pvmsg->message);
            }
        }
        require_once AmpConfig::get('prefix') . '/templates/show_add_pvmsg.inc.php';
    break;
    case 'add_message':
        // Remove unauthorized defined values from here
        if (isset($_POST['from_user'])) {
            unset($_POST['from_user']);
        }
        if (isset($_POST['creation_date'])) {
            unset($_POST['creation_date']);
        }
        if (isset($_POST['is_read'])) {
            unset($_POST['is_read']);
        }

        $pvmsg_id = PrivateMsg::create($_POST);
        if (!$pvmsg_id) {
            require_once AmpConfig::get('prefix') . '/templates/show_add_pvmsg.inc.php';
        } else {
            $body = T_('Message Sent');
            $title = '';
            show_confirmation($title, $body, AmpConfig::get('web_path') . '/browse.php?action=pvmsg');
        }
    break;
    case 'show':
    default:
        $pvmsg = new PrivateMsg($_REQUEST['pvmsg_id']);
        $pvmsg->format();
        if (!$pvmsg->is_read) {
            $pvmsg->set_is_read(true);
        }
        require_once AmpConfig::get('prefix') . '/templates/show_pvmsg.inc.php';
    break;
}

UI::show_footer();
