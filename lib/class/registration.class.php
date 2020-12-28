<?php
declare(strict_types=0);
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

/**
 * Registration Class
 *
 * This class handles all the doodlys for the registration
 * stuff in Ampache
 */
class Registration
{
    /**
     * constructor
     * This is what is called when the class is loaded
     */
    public function __construct()
    {
        // Rien a faire
    } // constructor

    /**
     * send_confirmation
     * This sends the confirmation e-mail for the specified user
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param string $username
     * @param string $fullname
     * @param string $email
     * @param string $website
     * @param string $validation
     * @return boolean
     */
    public static function send_confirmation($username, $fullname, $email, $website, $validation)
    {
        if (!Mailer::is_mail_enabled()) {
            return false;
        }

        $mailer = new Mailer();

        // We are the system
        $mailer->set_default_sender();

        /* HINT: Ampache site_title */
        $mailer->subject = sprintf(T_("New User Registration at %s"), AmpConfig::get('site_title'));

        $mailer->message = T_("Thank you for registering") . "\n";
        $mailer->message .= T_("Please keep this e-mail for your records. Your account information is as follows:") . "\n";
        $mailer->message .= "----------------------\n";
        $mailer->message .= T_("Username") . ": $username" . "\n";
        $mailer->message .= "----------------------\n";
        $mailer->message .= T_("To begin using your account, you must verify your e-mail address by vising the following link:") . "\n\n";
        $mailer->message .= AmpConfig::get('web_path') . "/register.php?action=validate&username=$username&auth=$validation";
        $mailer->recipient      = $email;
        $mailer->recipient_name = $fullname;

        if (!AmpConfig::get('admin_enable_required')) {
            $mailer->send();
        }

        // Check to see if the admin should be notified
        if (AmpConfig::get('admin_notify_reg')) {
            $mailer->message = T_("A new user has registered, the following values were entered:") . "\n\n";
            $mailer->message .= T_("Username") . ": $username\n";
            $mailer->message .= T_("Fullname") . ": $fullname\n";
            $mailer->message .= T_("E-mail") . ": $email\n";
            $mailer->message .= T_("Website") . ": $website\n";
            $mailer->send_to_group('admins');
        }

        return true;
    } // send_confirmation

    /**
     * send_account_enabled
     * This sends the account enabled email for the specified user
     *
     * @param string $username
     * @param string $fullname
     * @param $email
     */
    public static function send_account_enabled($username, $fullname, $email)
    {
        $mailer = new Mailer();
        $mailer->set_default_sender();

        /* HINT: Ampache site_title */
        $mailer->subject = sprintf(T_("Account enabled at %s"), AmpConfig::get('site_title'));
        /* HINT: Username */
        $mailer->message = sprintf(T_("A new user has been enabled. %s"), $username) .
                /* HINT: Ampache Login Page */
                "\n\n " . sprintf(T_("You can log in at the following address %s"), AmpConfig::get('web_path') . "/login.php");
        $mailer->recipient      = $email;
        $mailer->recipient_name = $fullname;

        $mailer->send();
    }

    /**
      * show_agreement
     * This shows the registration agreement, /config/registration_agreement.php
     * @return boolean
     */
    public static function show_agreement()
    {
        $filename = AmpConfig::get('prefix') . '/config/registration_agreement.php';

        if (!file_exists($filename)) {
            return false;
        }

        /* Check for existence */
        $filepointer = fopen($filename, 'r');

        if (!$filepointer) {
            return false;
        }

        $data = fread($filepointer, filesize($filename));

        /* Scrub and show */
        echo $data;

        return true;
    } // show_agreement
} // end registration.class
