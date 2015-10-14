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
        require_once AmpConfig::get('prefix') . UI::find_template('show_add_pvmsg.inc.php');
    break;
    case 'add_message':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

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
            require_once AmpConfig::get('prefix') . UI::find_template('show_add_pvmsg.inc.php');
        } else {
            $body = T_('Message Sent');
            $title = '';
            show_confirmation($title, $body, AmpConfig::get('web_path') . '/browse.php?action=pvmsg');
        }
    break;
    case 'set_is_read':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $msgs = split(",", $_REQUEST['msgs']);
        foreach ($msgs as $msg_id) {
            $pvmsg = new PrivateMsg(intval($msg_id));
            if ($pvmsg->id && $pvmsg->to_user === $GLOBALS['user']->id) {
                $read = intval($_REQUEST['read']) !== 0;
                $pvmsg->set_is_read($read);
            } else {
                debug_event('UI::access_denied', 'Unknown or unauthorized private message `' . $pvmsg->id . '`.', '3');
                UI::access_denied();
                exit();
            }
        }

        show_confirmation(T_('Messages State Changed'), T_('Messages state have been changed.'), AmpConfig::get('web_path') . "/browse.php?action=pvmsg");
    break;
    case 'delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $msgs = scrub_out($_REQUEST['msgs']);
        show_confirmation(
            T_('Message Deletion'),
            T_('Are you sure you want to permanently delete the selected messages?'),
            AmpConfig::get('web_path')."/pvmsg.php?action=confirm_delete&msgs=" . $msgs,
            1,
            'delete_message'
        );
    break;
    case 'confirm_delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $msgs = split(",", $_REQUEST['msgs']);
        foreach ($msgs as $msg_id) {
            $msg_id = intval($msg_id);
            $pvmsg = new PrivateMsg($msg_id);
            if ($pvmsg->id && $pvmsg->to_user === $GLOBALS['user']->id) {
                $pvmsg->delete();
            } else {
                debug_event('UI::access_denied', 'Unknown or unauthorized private message #' . $msg_id . '.', '3');
                UI::access_denied();
                exit();
            }
        }

        show_confirmation(T_('Messages Deletion'), T_('Messages have been deleted.'), AmpConfig::get('web_path') . "/browse.php?action=pvmsg");
    break;
    case 'show':
    default:
        $msg_id = intval($_REQUEST['pvmsg_id']);
        $pvmsg = new PrivateMsg($msg_id);
        if ($pvmsg->id && $pvmsg->to_user === $GLOBALS['user']->id) {
            $pvmsg->format();
            if (!$pvmsg->is_read) {
                $pvmsg->set_is_read(true);
            }
            require_once AmpConfig::get('prefix') . UI::find_template('show_pvmsg.inc.php');
        } else {
            debug_event('UI::access_denied', 'Unknown or unauthorized private message #' . $msg_id . '.', '3');
            UI::access_denied();
            exit();
        }
    break;
}

UI::show_footer();
