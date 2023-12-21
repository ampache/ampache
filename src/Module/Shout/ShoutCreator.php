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
 *
 */

namespace Ampache\Module\Shout;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\User\Activity\UserActivityPosterInterface;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Repository\Model\library_item;
use Ampache\Repository\Model\ModelFactoryInterface;
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
    private UtilityFactoryInterface $utilityFactory;
    private ModelFactoryInterface $modelFactory;

    public function __construct(
        UserActivityPosterInterface $userActivityPoster,
        ConfigContainerInterface $configContainer,
        ShoutRepositoryInterface $shoutRepository,
        UtilityFactoryInterface $utilityFactory,
        ModelFactoryInterface $modelFactory
    ) {
        $this->userActivityPoster = $userActivityPoster;
        $this->configContainer    = $configContainer;
        $this->shoutRepository    = $shoutRepository;
        $this->utilityFactory     = $utilityFactory;
        $this->modelFactory       = $modelFactory;
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
            $objectType,
            $objectId,
            $date->getTimestamp()
        );

        // send email to the item owner
        $item_owner_id = $libItem->get_user_owner();
        if ($item_owner_id) {
            $item_owner = $this->modelFactory->createUser($item_owner_id);
            if ($item_owner->getPreferenceValue(ConfigurationKeyEnum::NOTIFY_EMAIL)) {
                $emailAddress = $item_owner->email;

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
                        $this->configContainer->getWebPath(),
                        $objectType,
                        $libItem->getId(),
                        $shout->getId()
                    );

                    $this->utilityFactory->createMailer()
                        ->set_default_sender()
                        ->setRecipient($emailAddress, (string) $item_owner->get_fullname())
                        ->setSubject(T_('New shout on your content'))
                        ->setMessage($message)
                        ->send();
                }
            }
        }
    }
}
