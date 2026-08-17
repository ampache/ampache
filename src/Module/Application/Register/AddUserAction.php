<?php

declare(strict_types=1);

/**
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright Ampache.org, 2001-2026
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

use Ampache\Config\AmpConfig;
use Ampache\Config\ConfigContainerInterface;
use Ampache\Config\ConfigurationKeyEnum;
use Ampache\Gui\Register\RegistrationConfirmationView;
use Ampache\Gui\Register\RegistrationView;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\AmpError;
use Ampache\Module\System\Core;
use Ampache\Module\User\Registration;
use Ampache\Module\User\Registration\RegistrationAgreementRendererInterface;
use Ampache\Module\Util\Mailer;
use Ampache\Module\Util\UiInterface;
use Ampache\Repository\Model\ModelFactoryInterface;
use Ampache\Repository\Model\User;
use Ampache\Repository\UserRepositoryInterface;
use Gregwar\Captcha\PhraseBuilder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AddUserAction implements ApplicationActionInterface
{
    public const string REQUEST_KEY = 'add_user';

    public function __construct(
        private readonly ConfigContainerInterface $configContainer,
        private readonly ModelFactoryInterface $modelFactory,
        private readonly UserRepositoryInterface $userRepository,
        private readonly RegistrationAgreementRendererInterface $registrationAgreementRenderer,
        public UiInterface $ui,
    ) {}

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
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::USER_NO_EMAIL_CONFIRM) === false
            && !Mailer::is_mail_enabled()
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
        $passOne  = Core::get_post('password_1');
        $passTwo  = Core::get_post('password_2');
        $website  = (string) scrub_in(Core::get_post('website'));
        $state    = (string) scrub_in(Core::get_post('state'));
        $city     = (string) scrub_in(Core::get_post('city'));

        // the answer lives in the session, so the form cannot post the phrase it is being checked against
        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::CAPTCHA_PUBLIC_REG)) {
            $expected = $_SESSION[RegistrationView::CAPTCHA_SESSION_KEY] ?? null;
            $answer   = (string) ($_POST['captcha_user'] ?? '');
            unset($_SESSION[RegistrationView::CAPTCHA_SESSION_KEY]);

            if (!is_string($expected) || !PhraseBuilder::comparePhrases($expected, $answer)) {
                AmpError::add('captcha_user', T_('Captcha failed'));
            }
        }

        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::USER_AGREEMENT) && !$_POST['accept_agreement']) {
            AmpError::add('user_agreement', T_('You must accept the user agreement'));
        } // if they have to agree to something

        if ($username === '') {
            AmpError::add('username', T_('You must enter a Username'));
        }

        // Check the mail for correct address formation.
        if (!Mailer::validate_address($email)) {
            AmpError::add('email', T_('Invalid e-mail address'));
        }

        $mandatory_fields = $this->configContainer->getArray(ConfigurationKeyEnum::REGISTRATION_MANDATORY_FIELDS);
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

        if ($passOne === '' || $passOne === '0') {
            AmpError::add('password', T_('You must enter a password'));
        }

        if ($passOne !== $passTwo) {
            AmpError::add('password', T_('Passwords do not match'));
        }

        if ($this->userRepository->idByUsername($username) > 0) {
            AmpError::add('duplicate_user', T_('That name already exists'));
        }

        // If we've hit an error anywhere up there break!
        if (AmpError::occurred()) {
            echo new RegistrationView(
                AmpConfig::get_web_path('/client'),
                $this->registrationAgreementRenderer
            )->render();

            return null;
        }

        // Attempt to create the new user
        $access = match ($this->configContainer->get(ConfigurationKeyEnum::AUTO_USER)) {
            'admin' => AccessLevelEnum::ADMIN,
            'user' => AccessLevelEnum::USER,
            default => AccessLevelEnum::GUEST,
        };

        $userId = User::create(
            $username,
            $fullname,
            $email,
            $website,
            $passOne,
            $access,
            0,
            $state,
            $city,
            $this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::ADMIN_ENABLE_REQUIRED)
        );

        if ($userId <= 0) {
            AmpError::add('duplicate_user', T_("Failed to create user"));

            echo new RegistrationView(
                AmpConfig::get_web_path('/client'),
                $this->registrationAgreementRenderer
            )->render();

            return null;
        }

        if ($this->configContainer->isFeatureEnabled(ConfigurationKeyEnum::USER_NO_EMAIL_CONFIRM) === false) {
            $client     = $this->modelFactory->createUser($userId);
            $validation = Core::generate_random_key();
            $client->update_validation($validation);

            // Notify user and/or admins
            Registration::send_confirmation($username, $fullname, $email, $website, $validation);
        }

        $_SESSION['login'] = true;

        echo new RegistrationConfirmationView(
            AmpConfig::get_web_path('/client'),
            str_replace('_', '-', (string) AmpConfig::get('lang', 'en_US')),
            (string) AmpConfig::get('site_charset', 'UTF-8'),
            (string) AmpConfig::get('site_title'),
            (bool) AmpConfig::get('admin_enable_required'),
            !AmpConfig::get('user_no_email_confirm')
        )->render();

        return null;
    }
}
