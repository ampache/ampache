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

define('NO_SESSION','1');
require_once 'lib/init.php';

$action = (isset($_POST['action'])) ? $_POST['action'] : "";

switch ($action) {
    case 'send':
        /* Check for posted email */
        $result = false;
        if (isset($_POST['email']) && $_POST['email']) {
            /* Get the email address and the current ip*/
            $email = scrub_in($_POST['email']);
            $current_ip =(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) ? $_SERVER['HTTP_X_FORWARDED_FOR'] :$_SERVER['REMOTE_ADDR'];
            $result = send_newpassword($email, $current_ip);
        }
        if ($result) {
            Error::add('general', T_('Password has been sent'));
        } else {
            Error::add('general', T_('Password has not been sent'));
        }

        require AmpConfig::get('prefix') . '/templates/show_login_form.inc.php';
        break;
    default:
        require AmpConfig::get('prefix') . '/templates/show_lostpassword_form.inc.php';
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
        $mailer->subject = T_("Lost Password");
        $mailer->recipient_name = $client->fullname;
        $mailer->recipient = $client->email;

        $message  = sprintf(T_("A user from %s has requested a password reset for '%s'."), $current_ip, $client->username);
        $message .= "\n";
        $message .= sprintf(T_("The password has been set to: %s"), $newpassword);
        $mailer->message = $message;

        return $mailer->send();
    }
    return false;
}
