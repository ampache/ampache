<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2019 Ampache.org
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

define('NO_SESSION', '1');
require_once 'lib/init.php';

/* Check Perms */
if (!Mailer::is_mail_enabled() || AmpConfig::get('demo_mode')) {
    UI::access_denied();

    return false;
}

$action = Core::get_post('action');

switch ($_REQUEST['action']) {
    case 'send':
        /* Check for posted email */
        $result = false;
        if (isset($_POST['email']) && Core::get_post('email')) {
            /* Get the email address and the current ip*/
            $email      = scrub_in(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
            $current_ip = filter_has_var(INPUT_SERVER, 'HTTP_X_FORWARDED_FOR') ? filter_input(INPUT_SERVER, 'HTTP_X_FORWARDED_FOR', FILTER_VALIDATE_IP) : filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP);
            $result     = send_newpassword($email, $current_ip);
        }
        /* Do not acknowledge a password has been sent or failed
        if ($result) {
            AmpError::add('general', T_('Password has been sent'));
        } else {
            AmpError::add('general', T_('Password has not been sent'));
        }*/

        require AmpConfig::get('prefix') . UI::find_template('show_login_form.inc.php');
        break;
    default:
        require AmpConfig::get('prefix') . UI::find_template('show_lostpassword_form.inc.php');
}

function send_newpassword($email, $current_ip)
{
    // get the Client and set the new password
    $client = User::get_from_email($email);

    // do not do anything if they aren't a user
    if (!$client) {
        return false;
    }

    // do not allow administrator password resets
    if ($client->has_access(100)) {
        return false;
    }
    if ($client && $client->email == $email && Mailer::is_mail_enabled()) {
        $newpassword = generate_password();
        $client->update_password($newpassword);

        $mailer = new Mailer();
        $mailer->set_default_sender();
        $mailer->subject        = T_("Lost Password");
        $mailer->recipient_name = $client->fullname;
        $mailer->recipient      = $client->email;

        $message  = sprintf(T_("A user from %s has requested a password reset for '%s'."), $current_ip, $client->username);
        $message .= "\n";
        $message .= sprintf(T_("The password has been set to: %s"), $newpassword);
        $mailer->message = $message;

        return $mailer->send();
    }

    return false;
}
