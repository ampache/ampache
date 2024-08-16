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

namespace Ampache\Module\Shout;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\User\Activity\UserActivityPosterInterface;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\LibraryItemEnum;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShoutRepositoryInterface;
use Ampache\Repository\UserRepositoryInterface;
use DateTime;
use PHPMailer\PHPMailer\Exception;

/**
 * Creates a new shout item
 */
final readonly class ShoutCreator implements ShoutCreatorInterface
{
    public function __construct(
        private UserActivityPosterInterface $userActivityPoster,
        private ConfigContainerInterface $configContainer,
        private ShoutRepositoryInterface $shoutRepository,
        private UtilityFactoryInterface $utilityFactory,
        private UserRepositoryInterface $userRepository,
    ) {
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
        LibraryItemEnum $objectType,
        string $text,
        bool $isSticky,
        int $offset
    ): void {
        $date     = new DateTime();
        $objectId = $libItem->getId();

        $shout = $this->shoutRepository->prototype()
            ->setDate($date)
            ->setUser($user)
            ->setText($text)
            ->setSticky($isSticky)
            ->setObjectType($objectType)
            ->setObjectId($objectId)
            ->setOffset($offset);

        $shout->save();

        $this->userActivityPoster->post(
            $user->getId(),
            'shout',
            $objectType->value,
            $objectId,
            $date->getTimestamp()
        );

        // send email to the item owner
        $itemOwnerId = $libItem->get_user_owner();
        if ($itemOwnerId) {
            $itemOwner = $this->userRepository->findById($itemOwnerId);
            if ($itemOwner?->getPreferenceValue(ConfigurationKeyEnum::NOTIFY_EMAIL)) {
                $emailAddress = $itemOwner->email;

                if (!empty($emailAddress) && Mailer::is_mail_enabled()) {
                    /* HINT: %1 username %2 item name being commented on */
                    $message = sprintf(
                        T_('You just received a new shout from %1$s on your content %2$s'),
                        $user->get_fullname(),
                        $libItem->get_fullname()
                    );
                    $message .= "\n\n----------------------\n\n";
                    $message .= $shout->getText();
                    $message .= "\n\n----------------------\n\n";
                    $message .= sprintf(
                        '%s/shout.php?action=show_add_shout&type=%s&id=%d#shout%d',
                        $this->configContainer->getWebPath('/client'),
                        $objectType->value,
                        $libItem->getId(),
                        $shout->getId()
                    );

                    $this->utilityFactory->createMailer()
                        ->set_default_sender()
                        ->setRecipient($emailAddress, (string) $itemOwner->get_fullname())
                        ->setSubject(T_('New shout on your content'))
                        ->setMessage($message)
                        ->send();
                }
            }
        }
    }
}
