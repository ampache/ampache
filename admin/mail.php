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

require_once '../lib/init.php';

if (!Access::check('interface', '75')) {
    UI::access_denied();
    exit();
}

UI::show_header();

// Action switch
switch ($_REQUEST['action']) {
    case 'send_mail':
        if (AmpConfig::get('demo_mode')) {
            UI::access_denied();
            exit;
        }

        // Multi-byte Character Mail
        if (function_exists('mb_language')) {
            $ini_default_charset = version_compare(PHP_VERSION, '5.6', '<') ? 'mbstring.internal_encoding' : 'default_charset';
            if (ini_get($ini_default_charset)) {
                ini_set($ini_default_charset, "UTF-8");
            }
            mb_language("uni");
        }

        $mailer = new Mailer();

        // Set the vars on the object
        $mailer->subject = $_REQUEST['subject'];
        $mailer->message = $_REQUEST['message'];

        if ($_REQUEST['from'] == 'system') {
            $mailer->set_default_sender();
        } else {
            $mailer->sender      = $GLOBALS['user']->email;
            $mailer->sender_name = $GLOBALS['user']->fullname;
        }

        if ($mailer->send_to_group($_REQUEST['to'])) {
            $title  = T_('E-mail Sent');
            $body   = T_('Your E-mail was successfully sent.');
        } else {
            $title     = T_('E-mail Not Sent');
            $body      = T_('Your E-mail was not sent.');
        }
        $url = AmpConfig::get('web_path') . '/admin/mail.php';
        show_confirmation($title, $body, $url);

    break;
    default:
        require_once AmpConfig::get('prefix') . UI::find_template('show_mail_users.inc.php');
    break;
} // end switch

UI::show_footer();
