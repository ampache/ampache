<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2023
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
 */

namespace Ampache\Module\Shout;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\User\Activity\UserActivityPosterInterface;
use Ampache\Module\Util\Mailer;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\Preference;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShoutRepositoryInterface;
use DateTime;
use PHPMailer\PHPMailer\Exception;

/**
 * Creates a new shout item
 */
final class ShoutCreator implements ShoutCreatorInterface
{
    private UserActivityPosterInterface $userActivityPoster;

    private ConfigContainerInterface $configContainer;

    private ShoutRepositoryInterface $shoutRepository;

    public function __construct(
        UserActivityPosterInterface $userActivityPoster,
        ConfigContainerInterface $configContainer,
        ShoutRepositoryInterface $shoutRepository
    ) {
        $this->userActivityPoster = $userActivityPoster;
        $this->configContainer    = $configContainer;
        $this->shoutRepository    = $shoutRepository;
    }

    /**
     * Creates a new shout item
     *
     * This will create a new shout item and inform the owning user about the shout (if enabled)
     *
     * @throws Exception
     */
    public function create(
        User $user,
        library_item $libItem,
        string $objectType,
        string $text,
        bool $isSticky,
        int $offset
    ): void {
        $date    = new DateTime();
        $comment = strip_tags($text);

        $insertId = $this->shoutRepository->create(
            $user,
            $date,
            $comment,
            $isSticky,
            $libItem,
            $objectType,
            $offset
        );

        if ($insertId !== 0) {
            $this->userActivityPoster->post(
                $user->getId(),
                'shout',
                $objectType,
                $libItem->getId(),
                $date->getTimestamp()
            );

            // send email to the item owner
            $item_owner_id = $libItem->get_user_owner();
            if ($item_owner_id) {
                if (Preference::get_by_user($item_owner_id, 'notify_email')) {
                    $item_owner = new User($item_owner_id);
                    if (!empty($item_owner->email) && Mailer::is_mail_enabled()) {
                        if (method_exists($libItem, 'format')) {
                            $libItem->format();
                        }
                        $mailer = new Mailer();
                        $mailer->set_default_sender();
                        $mailer->recipient      = $item_owner->email;
                        $mailer->recipient_name = $item_owner->fullname;
                        $mailer->subject        = T_('New shout on your content');
                        /* HINT: %1 username %2 item name being commented on */
                        $mailer->message = sprintf(
                            T_('You just received a new shout from %1$s on your content %2$s'),
                            $user->fullname,
                            $libItem->get_fullname()
                        );
                        $mailer->message .= "\n\n----------------------\n\n";
                        $mailer->message .= $comment;
                        $mailer->message .= "\n\n----------------------\n\n";
                        $mailer->message .= sprintf(
                            '%s/shout.php?action=show_add_shout&type=%s&id=%d#shout%d',
                            $this->configContainer->getWebPath(),
                            $objectType,
                            $libItem->getId(),
                            $insertId
                        );
                        $mailer->send();
                    }
                }
            }
        }
    }
}
