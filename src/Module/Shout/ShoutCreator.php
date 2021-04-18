<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
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
 */

declare(strict_types=1);

namespace Ampache\Module\Shout;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Preference\UserPreferenceRetrieverInterface;
use Ampache\Module\User\Activity\UserActivityPosterInterface;
use Ampache\Module\Util\InterfaceImplementationChecker;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\ShoutRepositoryInterface;

/**
 * Creates a new user shout entry
 */
final class ShoutCreator implements ShoutCreatorInterface
{
    private UserActivityPosterInterface $userActivityPoster;

    private ModelFactoryInterface $modelFactory;

    private UserPreferenceRetrieverInterface $userPreferenceRetriever;

    private UtilityFactoryInterface $utilityFactory;

    private ShoutRepositoryInterface $shoutRepository;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        UserActivityPosterInterface $userActivityPoster,
        ModelFactoryInterface $modelFactory,
        UserPreferenceRetrieverInterface $userPreferenceRetriever,
        UtilityFactoryInterface $utilityFactory,
        ShoutRepositoryInterface $shoutRepository,
        ConfigContainerInterface $configContainer
    ) {
        $this->userActivityPoster      = $userActivityPoster;
        $this->modelFactory            = $modelFactory;
        $this->userPreferenceRetriever = $userPreferenceRetriever;
        $this->utilityFactory          = $utilityFactory;
        $this->shoutRepository         = $shoutRepository;
        $this->configContainer         = $configContainer;
    }

    /**
     * This takes a key'd array of data as input and inserts a new shoutbox entry
     */
    public function create(
        User $user,
        array $data
    ): void {
        if (!InterfaceImplementationChecker::is_library_item($data['object_type'])) {
            return;
        }

        $userId = $user->getId();

        $sticky  = isset($data['sticky']) ? 1 : 0;
        $date    = (int)($data['date'] ?: time());
        $comment = strip_tags($data['comment']);

        $insertId = $this->shoutRepository->insert(
            $userId,
            $date,
            $comment,
            $sticky,
            (int) $data['object_id'],
            $data['object_type'],
            $data['data']
        );

        $this->userActivityPoster->post(
            $userId,
            'shout',
            $data['object_type'],
            (int) $data['object_id'],
            time()
        );

        // Never send email in case of user impersonation
        if (!isset($data['user']) && $insertId !== null) {
            $libitem = $this->modelFactory->mapObjectType(
                $data['object_type'],
                (int) $data['object_id']
            );
            $item_owner_id = $libitem->get_user_owner();
            if ($item_owner_id) {
                $preference = $this->userPreferenceRetriever->retrieve($item_owner_id, 'notify_email');
                if ($preference) {
                    $item_owner = $this->modelFactory->createUser($item_owner_id);
                    if (!empty($item_owner->email) && Mailer::is_mail_enabled()) {
                        $libitem->format();

                        $mailer = $this->utilityFactory->createMailer();
                        $mailer->set_default_sender();
                        $mailer->recipient      = $item_owner->email;
                        $mailer->recipient_name = $item_owner->fullname;
                        $mailer->subject        = T_('New shout on your content');
                        /* HINT: %1 username %2 item name being commented on */
                        $mailer->message = sprintf(
                            T_('You just received a new shout from %1$s on your content %2$s'),
                            $user->fullname,
                            $libitem->get_fullname()
                        );
                        $mailer->message .= "\n\n----------------------\n\n";
                        $mailer->message .= $comment;
                        $mailer->message .= "\n\n----------------------\n\n";
                        $mailer->message .= sprintf(
                            '%s/shout.php?action=show_add_shout&type=%s&id=%s#shout%s',
                            $this->configContainer->getWebPath(),
                            $data['object_type'],
                            $data['object_id'],
                            $insertId
                        );
                        $mailer->send();
                    }
                }
            }
        }
    }
}
