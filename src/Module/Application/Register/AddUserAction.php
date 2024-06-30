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

namespace Ampache\Module\Application\Register;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\User\Registration;
use Ampache\Module\Util\Captcha\captcha;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\Ui;
use Ampache\Repository\UserRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AddUserAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'add_user';

    private ConfigContainerInterface $configContainer;

    private ModelFactoryInterface $modelFactory;

    private UserRepositoryInterface $userRepository;

    private Registration\RegistrationAgreementRendererInterface $registrationAgreementRenderer;

    private UiInterface $ui;

    public function __construct(
        ConfigContainerInterface $configContainer,
        ModelFactoryInterface $modelFactory,
        UserRepositoryInterface $userRepository,
        Registration\RegistrationAgreementRendererInterface $registrationAgreementRenderer,
        UiInterface $ui
    ) {
        $this->configContainer               = $configContainer;
        $this->modelFactory                  = $modelFactory;
        $this->userRepository                = $userRepository;
        $this->registrationAgreementRenderer = $registrationAgreementRenderer;
        $this->ui                            = $ui;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        // Check allow_public_registration
        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_PUBLIC_REGISTRATION) === false
        ) {
            throw new AccessDeniedException('Error `allow_public_registration` disabled');
        }
        // Check for confirmation email requirements when mail is disabled
        if (
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ALLOW_PUBLIC_REGISTRATION) === true &&
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::USER_NO_EMAIL_CONFIRM) === false &&
            !Mailer::is_mail_enabled()
        ) {
            throw new AccessDeniedException('Error `mail_enable` failed. Enable `user_no_email_confirm` to disable mail requirements');
        }

        /**
         * User information has been entered
         * we need to check the database for possible existing username first
         * if username exists, error and say "Please choose a different name."
         * if username does not exist, insert user information into database
         * then allow the user to 'click here to login'
         * possibly by logging them in right then and there with their current info
         * and 'click here to login' would just be a link back to index.php
         */
        $fullname = (string) scrub_in(Core::get_post('fullname'));
        $username = trim(scrub_in(Core::get_post('username')));
        $email    = (string) scrub_in(Core::get_post('email'));
        $pass1    = Core::get_post('password_1');
        $pass2    = Core::get_post('password_2');
        $website  = (string) scrub_in(Core::get_post('website'));
        $state    = (string) scrub_in(Core::get_post('state'));
        $city     = (string) scrub_in(Core::get_post('city'));

        /* If we're using the captcha stuff */
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::CAPTCHA_PUBLIC_REG) === true) {
            $captcha = captcha::solved();
            if (!isset($captcha)) {
                AmpError::add('captcha', T_('Captcha is required'));
            }
            if (isset($captcha)) {
                if ($captcha) {
                    $msg = "SUCCESS";
                } else {
                    AmpError::add('captcha', T_('Captcha failed'));
                }
            } // end if we've got captcha
        } // end if it's enabled

        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::USER_AGREEMENT) === true) {
            if (!$_POST['accept_agreement']) {
                AmpError::add('user_agreement', T_('You must accept the user agreement'));
            }
        } // if they have to agree to something

        if ($username === '') {
            AmpError::add('username', T_('You must enter a Username'));
        }

        // Check the mail for correct address formation.
        if (!Mailer::validate_address($email)) {
            AmpError::add('email', T_('Invalid e-mail address'));
        }

        $mandatory_fields = (array) $this->configContainer->get(ConfigurationKeyEnum::REGISTRATION_MANDATORY_FIELDS);
        if (in_array('fullname', $mandatory_fields) && !$fullname) {
            AmpError::add('fullname', T_("Please fill in your full name (first name, last name)"));
        }
        if (in_array('website', $mandatory_fields) && !$website) {
            AmpError::add('website', T_("Please fill in your website"));
        }
        if (in_array('state', $mandatory_fields) && !$state) {
            AmpError::add('state', T_("Please fill in your state"));
        }
        if (in_array('city', $mandatory_fields) && !$city) {
            AmpError::add('city', T_("Please fill in your city"));
        }

        if (!$pass1) {
            AmpError::add('password', T_('You must enter a password'));
        }

        if ($pass1 != $pass2) {
            AmpError::add('password', T_('Passwords do not match'));
        }

        if ($this->userRepository->idByUsername((string) $username) > 0) {
            AmpError::add('duplicate_user', T_('That Username already exists'));
        }

        // If we've hit an error anywhere up there break!
        if (AmpError::occurred()) {
            $this->ui->show(
                'show_user_registration.inc.php',
                [
                    'registrationAgreementRenderer' => $this->registrationAgreementRenderer,
                ]
            );

            return null;
        }

        /* Attempt to create the new user */
        switch ($this->configContainer->get(ConfigurationKeyEnum::AUTO_USER)) {
            case 'admin':
                $access = AccessLevelEnum::ADMIN;
                break;
            case 'user':
                $access = AccessLevelEnum::USER;
                break;
            case 'guest':
            default:
                $access = AccessLevelEnum::GUEST;
                break;
        } // auto-user level

        $userId = User::create(
            $username,
            $fullname,
            $email,
            $website,
            $pass1,
            $access,
            0,
            $state,
            $city,
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ADMIN_ENABLE_REQUIRED)
        );

        if ($userId <= 0) {
            AmpError::add('duplicate_user', T_("Failed to create user"));

            $this->ui->show(
                'show_user_registration.inc.php',
                [
                    'registrationAgreementRenderer' => $this->registrationAgreementRenderer,
                ]
            );

            return null;
        }

        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::USER_NO_EMAIL_CONFIRM) === false) {
            $client     = $this->modelFactory->createUser($userId);
            $validation = md5(uniqid((string) rand(), true));
            $client->update_validation($validation);

            // Notify user and/or admins
            Registration::send_confirmation($username, $fullname, $email, $website, $validation);
        }

        require_once Ui::find_template('show_registration_confirmation.inc.php');

        return null;
    }
}
