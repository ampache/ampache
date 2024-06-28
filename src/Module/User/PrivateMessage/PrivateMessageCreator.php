<?php

declare(strict_types=1);

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

namespace Ampache\Module\User\PrivateMessage;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Repository\PrivateMessageRepositoryInterface;

final class PrivateMessageCreator implements PrivateMessageCreatorInterface
{
    private PrivateMessageRepositoryInterface $privateMessageRepository;

    private UtilityFactoryInterface $utilityFactory;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        PrivateMessageRepositoryInterface $privateMessageRepository,
        UtilityFactoryInterface $utilityFactory,
        ConfigContainerInterface $configContainer
    ) {
        $this->privateMessageRepository = $privateMessageRepository;
        $this->utilityFactory           = $utilityFactory;
        $this->configContainer          = $configContainer;
    }

    /**
     * Sends a private message to a user
     *
     * @throws Exception\PrivateMessageCreationException
     * @throws \PHPMailer\PHPMailer\Exception
     */
    public function create(
        ?User $recipient,
        User $sender,
        string $subject,
        string $message
    ): void {
        $messageId = $this->privateMessageRepository->create(
            $recipient,
            $sender,
            $subject,
            $message
        );

        if (
            $recipient !== null &&
            Preference::get_by_user($recipient->getId(), 'notify_email')
        ) {
            $mailer = $this->utilityFactory->createMailer();
            if (!empty($recipient->email) && $mailer->isMailEnabled()) {
                /* HINT: User fullname */
                $message = sprintf(
                    T_('You received a new private message from %s.'),
                    $sender->fullname
                );
                $message .= "\n\n----------------------\n\n";
                $message .= $message;
                $message .= "\n\n----------------------\n\n";
                $message .= $this->configContainer->getWebPath() . "/pvmsg.php?action=show&pvmsg_id=" . $messageId;

                $mailer->set_default_sender();
                $mailer->setRecipient((string) $recipient->email, (string) $recipient->get_fullname());
                $mailer->setSubject(sprintf('[%s] %s', T_('Private Message'), $subject));
                $mailer->setMessage($message);
                $mailer->send();
            }
        }
    }
}
