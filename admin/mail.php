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

$a_root = realpath(__DIR__ . "/../");
require_once $a_root . '/lib/init.php';

if (!Access::check('interface', 75)) {
    UI::access_denied();

    return false;
}

UI::show_header();

// Switch on the actions
switch ($_REQUEST['action']) {
    case 'send_mail':
        if (AmpConfig::get('demo_mode')) {
            UI::access_denied();

            return false;
        }

        // Multi-byte Character Mail
        if (function_exists('mb_language')) {
            $ini_default_charset = 'default_charset';
            if (ini_get($ini_default_charset)) {
                ini_set($ini_default_charset, "UTF-8");
            }
            mb_language("uni");
        }

        if (Mailer::is_mail_enabled()) {
            $mailer = new Mailer();

            // Set the vars on the object
            $mailer->subject = $_REQUEST['subject'];
            $mailer->message = $_REQUEST['message'];

            if (Core::get_request('from') == 'system') {
                $mailer->set_default_sender();
            } else {
                $mailer->sender      = Core::get_global('user')->email;
                $mailer->sender_name = Core::get_global('user')->fullname;
            }

            if ($mailer->send_to_group($_REQUEST['to'])) {
                $title  = T_('No Problem');
                $body   = T_('Your e-mail has been sent');
            } else {
                $title     = T_("There Was a Problem");
                $body      = T_('Your e-mail has not been sent');
            }
            $url = AmpConfig::get('web_path') . '/admin/mail.php';
            show_confirmation($title, $body, $url);
        }

        break;
    default:
        require_once AmpConfig::get('prefix') . UI::find_template('show_mail_users.inc.php');
        break;
} // end switch

// Show the Footer
UI::show_query_stats();
UI::show_footer();
