<?php
/*
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

declare(strict_types=1);

namespace Ampache\Module\User;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Repository\Model\User;
use Ampache\Module\Util\UtilityFactoryInterface;
use Ampache\Repository\UserRepositoryInterface;

/**
 * Disables/Enables users
 */
final class UserStateToggler implements UserStateTogglerInterface
{
    private ConfigContainerInterface $configContainer;

    private UtilityFactoryInterface $utilityFactory;

    private UserRepositoryInterface $userRepository;

    public function __construct(
        ConfigContainerInterface $configContainer,
        UtilityFactoryInterface $utilityFactory,
        UserRepositoryInterface $userRepository
    ) {
        $this->configContainer = $configContainer;
        $this->utilityFactory  = $utilityFactory;
        $this->userRepository  = $userRepository;
    }

    public function enable(User $user): bool
    {
        $this->userRepository->enable($user->getId());

        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::USER_NO_EMAIL_CONFIRM) === false) {
            $mailer = $this->utilityFactory->createMailer();
            $mailer->set_default_sender();

            /* HINT: Ampache site_title */
            $mailer->subject = sprintf(
                T_('Account enabled at %s'),
                $this->configContainer->get(ConfigurationKeyEnum::SITE_TITLE)
            );

            /* HINT: Username */
            $mailer->message = sprintf(T_('A new user has been enabled. %s'), $user->username) .
                /* HINT: Ampache Login Page */"\n\n" .
                sprintf(
                    T_('You can log in at the following address %s'),
                    $this->configContainer->getWebPath()
                );
            $mailer->recipient      = $user->email;
            $mailer->recipient_name = $user->fullname;

            $mailer->send();
        }

        return true;
    }

    public function disable(User $user): bool
    {
        return $user->disable();
    }
}
