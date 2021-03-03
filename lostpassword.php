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

define('NO_SESSION', '1');
$a_root = realpath(__DIR__);
require_once $a_root . '/lib/init.php';

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
        if (filter_has_var(INPUT_POST, 'email') && Core::get_post('email')) {
            /* Get the email address and the current ip*/
            $email      = scrub_in(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
            $current_ip = filter_has_var(INPUT_SERVER, 'HTTP_X_FORWARDED_FOR') ? Core::get_server('HTTP_X_FORWARDED_FOR') : Core::get_server('REMOTE_ADDR');
            $result     = send_newpassword($email, $current_ip);
        }
        // Do not acknowledge a password has been sent or failed and go back to login
        require AmpConfig::get('prefix') . UI::find_template('show_login_form.inc.php');
        break;
    default:
        require AmpConfig::get('prefix') . UI::find_template('show_lostpassword_form.inc.php');
}

/**
 * @param $email
 * @param $current_ip
 * @return boolean
 * @throws \PHPMailer\PHPMailer\Exception
 */
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
    if ($client->email == $email && Mailer::is_mail_enabled() && !AmpConfig::get('simple_user_mode')) {
        $newpassword = generate_password();
        $client->update_password($newpassword);

        $mailer = new Mailer();
        $mailer->set_default_sender();
        $mailer->subject        = T_("Lost Password");
        $mailer->recipient_name = $client->fullname;
        $mailer->recipient      = $client->email;

        $message  = sprintf(
            /* HINT: %1 IP Address, %2 Username */
            T_('A user from "%1$s" has requested a password reset for "%2$s"'), $current_ip, $client->username);
        $message .= "\n";
        $message .= sprintf(T_("The password has been set to: %s"), $newpassword);
        $mailer->message = $message;

        return $mailer->send();
    }

    return false;
}
