<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPLv3)
 * Copyright 2001 - 2015 Ampache.org
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

define('NO_SESSION','1');
require_once 'lib/init.php';

$action = (isset($_POST['action'])) ? $_POST['action'] : "";

switch ($action) {
    case 'send':
        /* Check for posted email */
        $result = false;
        if (isset($_POST['email']) && $_POST['email']) {
            /* Get the email address and the current ip*/
            $email      = scrub_in($_POST['email']);
            $current_ip =(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] :$_SERVER['REMOTE_ADDR'];
            $result     = send_newpassword($email, $current_ip);
        }
        if ($result) {
            AmpError::add('general', T_('Password has been sent'));
        } else {
            AmpError::add('general', T_('Password has not been sent'));
        }

        require AmpConfig::get('prefix') . UI::find_template('show_login_form.inc.php');
        break;
    default:
        require AmpConfig::get('prefix') . UI::find_template('show_lostpassword_form.inc.php');
}

function send_newpassword($email,$current_ip)
{
    /* get the Client and set the new password */
    $client = User::get_from_email($email);
    if ($client && $client->email == $email) {
        $newpassword = generate_password(6);
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
