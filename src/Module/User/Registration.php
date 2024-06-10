<?php

declare(strict_types=0);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2024
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

namespace Ampache\Module\User;

use Ampache\Module\Util\Mailer;
use Ampache\Config\AmpConfig;

/**
 * Registration Class
 *
 * This class handles all the doodlys for the registration
 * stuff in Ampache
 */
class Registration
{
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
     */
    public static function send_confirmation($username, $fullname, $email, $website, $validation): bool
    {
        if (!Mailer::is_mail_enabled()) {
            return false;
        }

        $mailer = new Mailer();

        // We are the system
        $mailer->set_default_sender();

        /* HINT: Ampache site_title */
        $mailer->setSubject(sprintf(T_("New User Registration at %s"), AmpConfig::get('site_title')));

        $message = T_('Thank you for registering') . "\n";
        $message .= T_('Please keep this e-mail for your records. Your account information is as follows:') . "\n";
        $message .= "----------------------\n";
        $message .= T_('Username') . ": $username" . "\n";
        $message .= "----------------------\n";
        $message .= T_('To begin using your account, you must verify your e-mail address by vising the following link:') . "\n\n";
        $message .= AmpConfig::get('web_path') . "/register.php?action=validate&username=" . urlencode($username) . "&auth=$validation";

        $mailer->setRecipient($email, $fullname);
        $mailer->setMessage($message);

        if (!AmpConfig::get('admin_enable_required')) {
            $mailer->send();
        }

        // Check to see if the admin should be notified
        if (AmpConfig::get('admin_notify_reg')) {
            $mailer = new Mailer();

            // We are the system
            $mailer->set_default_sender();
            $mailer->setSubject(sprintf(T_("New User Registration at %s"), AmpConfig::get('site_title')));

            $message = T_("A new user has registered, the following values were entered:") . "\n\n";
            $message .= T_("Username") . ": $username\n";
            $message .= T_("Fullname") . ": $fullname\n";
            $message .= T_("E-mail") . ": $email\n";
            if (!empty($website)) {
                $message .= T_("Website") . ": $website\n";
            }
            $mailer->setMessage($message);
            $mailer->send_to_group('admins');
        }

        return true;
    }
}
