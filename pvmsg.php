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

$a_root = realpath(__DIR__);
require_once $a_root . '/lib/init.php';

if (!Access::check('interface', 25) || !AmpConfig::get('sociable')) {
    debug_event('pvmsg', 'Access Denied: sociable features are not enabled.', 3);
    UI::access_denied();

    return false;
}

UI::show_header();
$action = isset($_REQUEST['action']) ? Core::get_request('action') : '';

switch ($_REQUEST['action']) {
    case 'show_add_message':
        if (isset($_REQUEST['reply_to'])) {
            $pvmsg = new PrivateMsg($_REQUEST['reply_to']);
            if ($pvmsg->id && ($pvmsg->from_user === Core::get_global('user')->id || $pvmsg->to_user === Core::get_global('user')->id)) {
                $to_user             = new User($pvmsg->from_user);
                $_REQUEST['to_user'] = $to_user->username;
                /* HINT: Shorthand for e-mail reply */
                $_REQUEST['subject'] = T_('RE') . ": " . $pvmsg->subject;
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
        if (filter_has_var(INPUT_POST, 'from_user')) {
            unset($_POST['from_user']);
        }
        if (filter_has_var(INPUT_POST, 'creation_date')) {
            unset($_POST['creation_date']);
        }
        if (filter_has_var(INPUT_POST, 'is_read')) {
            unset($_POST['is_read']);
        }

        $pvmsg_id = PrivateMsg::create($_POST);
        if (!$pvmsg_id) {
            require_once AmpConfig::get('prefix') . UI::find_template('show_add_pvmsg.inc.php');
        } else {
            show_confirmation(T_('No Problem'), T_('Message has been sent'), AmpConfig::get('web_path') . '/browse.php?action=pvmsg');
        }
        break;
    case 'set_is_read':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $msgs = explode(',', $_REQUEST['msgs']);
        foreach ($msgs as $msg_id) {
            $pvmsg = new PrivateMsg((int) ($msg_id));
            if ($pvmsg->id && $pvmsg->to_user === Core::get_global('user')->id) {
                $read = (int) $_REQUEST['read'];
                $pvmsg->set_is_read($read);
            } else {
                debug_event('pvmsg', 'Unknown or unauthorized private message `' . $pvmsg->id . '`.', 3);
                UI::access_denied();

                return false;
            }
        }

        show_confirmation(T_('No Problem'), T_("Message's state has been changed"), AmpConfig::get('web_path') . "/browse.php?action=pvmsg");
        break;
    case 'delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $msgs = scrub_out($_REQUEST['msgs']);
        show_confirmation(T_('Are You Sure?'),
            T_('The Message will be deleted'),
                AmpConfig::get('web_path') . "/pvmsg.php?action=confirm_delete&msgs=" . $msgs,
                1,
                'delete_message'
        );
        break;
    case 'confirm_delete':
        if (AmpConfig::get('demo_mode')) {
            break;
        }

        $msgs = explode(',', $_REQUEST['msgs']);
        foreach ($msgs as $msg_id) {
            $msg_id = (int) ($msg_id);
            $pvmsg  = new PrivateMsg($msg_id);
            if ($pvmsg->id && $pvmsg->to_user === Core::get_global('user')->id) {
                $pvmsg->delete();
            } else {
                debug_event('pvmsg', 'Unknown or unauthorized private message #' . $msg_id . '.', 3);
                UI::access_denied();

                return false;
            }
        }

        show_confirmation(T_('No Problem'), T_('Messages have been deleted'), AmpConfig::get('web_path') . "/browse.php?action=pvmsg");
        break;
    case 'show':
    default:
        $msg_id = (int) filter_input(INPUT_GET, 'pvmsg_id', FILTER_SANITIZE_NUMBER_INT);
        $pvmsg  = new PrivateMsg($msg_id);
        if ($pvmsg->id && $pvmsg->to_user === Core::get_global('user')->id) {
            $pvmsg->format();
            if (!$pvmsg->is_read) {
                $pvmsg->set_is_read(1);
            }
            require_once AmpConfig::get('prefix') . UI::find_template('show_pvmsg.inc.php');
        } else {
            debug_event('pvmsg', 'Unknown or unauthorized private message #' . $msg_id . '.', 3);
            UI::access_denied();

            return false;
        }
        break;
}

// Show the Footer
UI::show_query_stats();
UI::show_footer();
