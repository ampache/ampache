<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
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

declare(strict_types=0);

namespace Ampache\Module\User;

use Ampache\Module\Util\Mailer;
use Ampache\Repository\UserRepositoryInterface;
use PHPMailer\PHPMailer\Exception;

final class NewPasswordSender implements NewPasswordSenderInterface
{
    private PasswordGeneratorInterface $passwordGenerator;

    private UserRepositoryInterface $userRepository;

    public function __construct(
        PasswordGeneratorInterface $passwordGenerator,
        UserRepositoryInterface $userRepository
    ) {
        $this->passwordGenerator = $passwordGenerator;
        $this->userRepository    = $userRepository;
    }

    /**
     * @throws Exception
     */
    public function send(
        string $email,
        string $current_ip
    ): bool {
        // get the Client and set the new password
        $client = $this->userRepository->findByEmail($email);

        // do not do anything if they aren't a user
        if ($client === null) {
            return false;
        }

        // do not allow administrator password resets
        if ($client->has_access(100)) {
            debug_event(__CLASS__, 'Administrator can\'t reset their password.', 1);

            return false;
        }
        if ($client->email == $email && Mailer::is_mail_enabled()) {
            $newpassword = $this->passwordGenerator->generate();
            $client->update_password($newpassword);

            $mailer = new Mailer();
            $mailer->set_default_sender();
            $mailer->subject        = T_('Lost Password');
            $mailer->recipient_name = $client->fullname;
            $mailer->recipient      = $client->email;

            $message  = sprintf(
                /* HINT: %1 IP Address, %2 Username */
                T_('A user from "%1$s" has requested a password reset for "%2$s"'),
                $current_ip,
                $client->username
            );
            $message .= "\n";
            $message .= sprintf(T_("The password has been set to: %s"), $newpassword);
            $mailer->message = $message;

            return $mailer->send();
        }

        return false;
    }
}
